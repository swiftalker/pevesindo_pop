defmodule Center.Integration.Odoo.Workers.InvoiceCreate do
  @moduledoc """
  Oban worker that publishes an invoice to Odoo with the full linear flow.
  """

  use Oban.Worker,
    queue: :odoo,
    max_attempts: 5,
    priority: 1,
    unique: [
      period: 3600,
      states: [:available, :scheduled, :executing, :retryable],
      keys: [:sale_invoice_id]
    ]

  require Logger
  require Ash.Query

  alias Center.Sales.SaleInvoice
  alias Center.MasterData.Journal
  alias Center.Integration.Odoo.{Invoices, SyncEvents}

  @impl Oban.Worker
  def perform(%Oban.Job{args: args}) do
    invoice_id = args["sale_invoice_id"]
    payment_ids = args["payment_ids"] || []
    invoice_date = args["invoice_date"]

    case SaleInvoice |> Ash.get(invoice_id, load: [sale_order: [:company]]) do
      {:ok, invoice} ->
        process_invoice(invoice, payment_ids, invoice_date)

      {:error, _} ->
        Logger.error("InvoiceCreate: SaleInvoice ##{invoice_id} not found")
        {:error, :invoice_not_found}
    end
  end

  defp process_invoice(invoice, payment_ids, invoice_date) do
    cond do
      is_nil(invoice.sale_order) ->
        Logger.error("InvoiceCreate: SaleOrder not found for SaleInvoice ##{invoice.id}")
        update_sync_error(invoice, "Sale order tidak ditemukan")
        {:error, :order_not_found}

      is_nil(invoice.sale_order.odoo_id) ->
        Logger.info("InvoiceCreate: SaleOrder ##{invoice.sale_order.id} has no Odoo ID yet — snoozing 15s")
        {:snooze, 15}

      invoice.odoo_invoice_id != nil ->
        Logger.info("InvoiceCreate: SaleInvoice ##{invoice.id} already has Odoo invoice ##{invoice.odoo_invoice_id}. Resuming post & payment flow.")
        resume_flow_from_post(invoice, invoice.odoo_invoice_id, payment_ids, invoice.sale_order)

      true ->
        run_full_flow(invoice, payment_ids, invoice_date)
    end
  end

  defp run_full_flow(invoice, payment_ids, invoice_date) do
    order = invoice.sale_order
    company_odoo_id = if order.company, do: order.company.odoo_id

    if existing_odoo = pull_existing_invoice(order, invoice) do
      Logger.info("InvoiceCreate: Found existing Odoo invoice #{existing_odoo["name"]} for SaleInvoice ##{invoice.id}")

      {:ok, updated_invoice} =
        invoice
        |> Ash.Changeset.for_update(:update, %{
          odoo_invoice_id: existing_odoo["id"],
          invoice_number: normalize_name(existing_odoo["name"]),
          amount_untaxed: to_decimal(existing_odoo["amount_untaxed"]),
          amount_tax: to_decimal(existing_odoo["amount_tax"]),
          amount_total: to_decimal(existing_odoo["amount_total"]),
          amount_residual: to_decimal(existing_odoo["amount_residual"]),
          status: normalize_status(existing_odoo["state"]),
          payment_state: existing_odoo["payment_state"] || "not_paid",
          odoo_data: existing_odoo,
          synced_at: DateTime.utc_now(:second),
          sync_error: nil
        })
        |> Ash.update()

      # Link SO to Odoo invoice too if not set
      _ = 
        order
        |> Ash.Changeset.for_update(:update, %{odoo_invoice_id: existing_odoo["id"]})
        |> Ash.update()

      resume_flow_from_post(updated_invoice, existing_odoo["id"], payment_ids, order)
    else
      journal_odoo_id = resolve_sales_journal_id(order.company_id)

      opts =
        [dp_value: invoice.dp_value]
        |> then(fn o -> if company_odoo_id, do: Keyword.put(o, :company_id, company_odoo_id), else: o end)
        |> then(fn o -> if journal_odoo_id, do: Keyword.put(o, :journal_id, journal_odoo_id), else: o end)
        |> then(fn o -> if invoice_date, do: Keyword.put(o, :invoice_date, invoice_date), else: o end)

      case Invoices.create_invoice_from_order(order.odoo_id, invoice.invoice_type, opts) do
        {:ok, odoo_invoices} when is_list(odoo_invoices) and odoo_invoices != [] ->
          odoo_invoice = List.last(odoo_invoices)
          odoo_invoice_id = odoo_invoice["id"]
          invoice_name = odoo_invoice["name"]

          Logger.info("InvoiceCreate: Created Odoo invoice #{invoice_name} (ID: #{odoo_invoice_id}) for SaleInvoice ##{invoice.id}")

          {:ok, updated_invoice} =
            invoice
            |> Ash.Changeset.for_update(:update, %{
              odoo_invoice_id: odoo_invoice_id,
              invoice_number: normalize_name(invoice_name),
              amount_untaxed: to_decimal(odoo_invoice["amount_untaxed"]),
              amount_tax: to_decimal(odoo_invoice["amount_tax"]),
              amount_total: to_decimal(odoo_invoice["amount_total"]),
              amount_residual: to_decimal(odoo_invoice["amount_residual"]),
              status: normalize_status(odoo_invoice["state"]),
              payment_state: odoo_invoice["payment_state"] || "not_paid",
              odoo_data: odoo_invoice,
              synced_at: DateTime.utc_now(:second),
              sync_error: nil
            })
            |> Ash.update()

          _ =
            order
            |> Ash.Changeset.for_update(:update, %{odoo_invoice_id: odoo_invoice_id})
            |> Ash.update()

          resume_flow_from_post(updated_invoice, odoo_invoice_id, payment_ids, order)

        {:ok, []} ->
          Logger.warning("InvoiceCreate: No invoices returned for SaleInvoice ##{invoice.id}")
          update_sync_error(invoice, "Odoo tidak mengembalikan invoice setelah pembuatan")
          {:error, :no_invoices_returned}

        {:error, :rate_limited} ->
          Logger.warning("InvoiceCreate: Odoo rate limited for SaleInvoice ##{invoice.id} — snoozing 60s")
          {:snooze, 60}

        {:error, reason} ->
          error_msg = format_error(reason)
          Logger.error("InvoiceCreate: Failed to create invoice for SaleInvoice ##{invoice.id}: #{error_msg}")
          update_sync_error(invoice, error_msg)
          {:error, reason}
      end
    end
  end

  defp pull_existing_invoice(order, local_invoice) do
    order_name = (order.odoo_data && order.odoo_data["name"]) || order.order_number

    used_odoo_ids =
      SaleInvoice
      |> Ash.Query.filter(sale_order_id == ^order.id and id != ^local_invoice.id and not is_nil(odoo_invoice_id))
      |> Ash.Query.select([:odoo_invoice_id])
      |> Ash.read!()
      |> Enum.map(& &1.odoo_invoice_id)
      |> MapSet.new()

    case Invoices.pull_for_order(order_name) do
      {:ok, records} ->
        candidates =
          records
          |> Enum.filter(fn r -> r["state"] != "cancel" end)
          |> Enum.reject(fn r -> MapSet.member?(used_odoo_ids, r["id"]) end)

        case candidates do
          [] -> nil
          [one] -> one
          many ->
            local_total = local_invoice.amount_total || Decimal.new(0)
            many
            |> Enum.sort_by(fn r ->
              Decimal.abs(Decimal.sub(local_total, to_decimal(r["amount_total"])))
              |> Decimal.to_float()
            end)
            |> Enum.find(&amount_close_enough_to_local?(&1, local_total))
        end
      _ -> nil
    end
  end

  defp amount_close_enough_to_local?(odoo_inv, local_total) do
    odoo_total = to_decimal(odoo_inv["amount_total"])
    diff = Decimal.abs(Decimal.sub(local_total, odoo_total))
    tol = local_total |> Decimal.mult(Decimal.new("0.15")) |> Decimal.max(Decimal.new(2000))
    Decimal.compare(diff, tol) != :gt
  end

  defp resume_flow_from_post(invoice, odoo_invoice_id, payment_ids, order) do
    with {:ok, updated_invoice} <- post_invoice_step(invoice, odoo_invoice_id),
         :ok <- register_payments_step(updated_invoice, odoo_invoice_id, payment_ids, order) do
      broadcast_success(invoice)
      {:ok, :published}
    else
      {:error, reason} ->
        error_msg = format_error(reason)
        update_sync_error(invoice, "Proses terhenti: #{error_msg}")
        {:error, reason}
    end
  end

  defp resolve_sales_journal_id(nil), do: nil
  defp resolve_sales_journal_id(company_id) do
    case Center.MasterData.resolve_sales_journal(company_id) do
      {:ok, journal} -> journal.odoo_id
      {:error, _} -> nil
    end
  end

  defp post_invoice_step(invoice, odoo_invoice_id) do
    if invoice.status == "draft" do
      case Invoices.validate_invoice(odoo_invoice_id) do
        {:ok, updated_data} ->
          invoice
          |> Ash.Changeset.for_update(:update, %{
            status: normalize_status(updated_data["state"]),
            payment_state: updated_data["payment_state"] || "not_paid",
            amount_residual: to_decimal(updated_data["amount_residual"]),
            odoo_data: updated_data,
            synced_at: DateTime.utc_now(:second)
          })
          |> Ash.update()

        {:error, reason} ->
          Logger.warning("InvoiceCreate: Failed to post invoice Odoo##{odoo_invoice_id}: #{inspect(reason)}")
          {:error, reason}
      end
    else
      {:ok, invoice}
    end
  end

  defp register_payments_step(_invoice, _odoo_invoice_id, [], _order), do: :ok
  defp register_payments_step(invoice, odoo_invoice_id, payment_ids, order) do
    company_odoo_id = if order.company, do: order.company.odoo_id

    payments =
      SalePayment
      |> Ash.Query.filter(id in ^payment_ids)
      |> Ash.Query.sort(payment_date: :asc)
      |> Ash.Query.load([:journal])
      |> Ash.read!()

    Enum.reduce_while(payments, :ok, fn payment, _acc ->
      if payment.status == "synced" do
        {:cont, :ok}
      else
        odoo_journal_id = resolve_payment_journal_id(payment)
        payment_attrs = %{
          amount: payment.amount,
          journal_id: odoo_journal_id,
          payment_date: payment.payment_date,
          memo: payment.memo || (order && order.order_number),
          company_id: company_odoo_id
        }

        case Invoices.register_payment(odoo_invoice_id, payment_attrs) do
          {:ok, %{invoice_data: invoice_data}} ->
            _ = payment |> Ash.Changeset.for_update(:update, %{status: "synced", synced_at: DateTime.utc_now(:second)}) |> Ash.update()
            if invoice_data do
              _ = invoice |> Ash.Changeset.for_update(:update, %{
                payment_state: invoice_data["payment_state"] || "not_paid",
                amount_residual: to_decimal(invoice_data["amount_residual"]),
                status: normalize_status(invoice_data["state"]),
                odoo_data: invoice_data,
                synced_at: DateTime.utc_now(:second)
              }) |> Ash.update()
            end
            {:cont, :ok}
          {:error, reason} ->
            {:halt, {:error, reason}}
        end
      end
    end)
  end

  defp resolve_payment_journal_id(payment) do
    cond do
      payment.journal && payment.journal.odoo_id -> payment.journal.odoo_id
      payment.journal_id ->
        case Journal |> Ash.get(payment.journal_id) do
          {:ok, %{odoo_id: id}} -> id
          _ -> nil
        end
      true -> nil
    end
  end

  defp update_sync_error(invoice, message) do
    invoice |> Ash.Changeset.for_update(:update, %{sync_error: message}) |> Ash.update()
    broadcast_failure(invoice)
  end

  defp broadcast_success(invoice) do
    Phoenix.PubSub.broadcast(Center.PubSub, SyncEvents.topic_sale_orders(), {:invoice_synced, %{order_id: invoice.sale_order_id, invoice_id: invoice.id}})
  end

  defp broadcast_failure(invoice) do
    Phoenix.PubSub.broadcast(Center.PubSub, SyncEvents.topic_sale_orders(), {:invoice_sync_failed, %{order_id: invoice.sale_order_id, invoice_id: invoice.id}})
  end

  defp normalize_status("draft"), do: "draft"
  defp normalize_status("posted"), do: "posted"
  defp normalize_status("cancel"), do: "cancelled"
  defp normalize_status(_), do: "draft"

  defp normalize_name(false), do: nil
  defp normalize_name("/"), do: nil
  defp normalize_name(name) when is_binary(name), do: name
  defp normalize_name(_), do: nil

  defp to_decimal(nil), do: Decimal.new(0)
  defp to_decimal(val) when is_float(val), do: Decimal.from_float(val) |> Decimal.round(2)
  defp to_decimal(val) when is_integer(val), do: Decimal.new(val)
  defp to_decimal(%Decimal{} = val), do: val
  defp to_decimal(val) when is_binary(val) do
    case Decimal.parse(val) do
      {d, _rest} -> d
      :error -> Decimal.new(0)
    end
  end
  defp to_decimal(_), do: Decimal.new(0)

  defp format_error({:invoice_create_failed, reason}), do: "Gagal membuat invoice di Odoo: #{inspect_error(reason)}"
  defp format_error({:invoice_read_failed, reason}), do: "Gagal membaca invoice dari Odoo: #{inspect_error(reason)}"
  defp format_error({:order_read_failed, reason}), do: "Gagal membaca order dari Odoo: #{inspect_error(reason)}"
  defp format_error({:line_read_failed, reason}), do: "Gagal membaca order lines dari Odoo: #{inspect_error(reason)}"
  defp format_error(:no_order_lines), do: "Sale order tidak memiliki order lines"
  defp format_error(:rate_limited), do: "Odoo rate limited — coba lagi nanti"
  defp format_error(:order_not_found), do: "Sale order tidak ditemukan di Odoo"
  defp format_error(:invoice_not_found_after_create), do: "Invoice tidak ditemukan setelah dibuat"
  defp format_error(reason), do: inspect(reason)

  defp inspect_error(%{message: msg}) when is_binary(msg), do: msg
  defp inspect_error(reason), do: inspect(reason)
end
