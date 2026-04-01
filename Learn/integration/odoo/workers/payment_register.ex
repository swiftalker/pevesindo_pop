defmodule Center.Integration.Odoo.Workers.PaymentRegister do
  @moduledoc """
  Oban worker that registers a payment in Odoo for a local SalePayment record.
  """

  use Oban.Worker,
    queue: :odoo,
    max_attempts: 10,
    priority: 2

  require Logger
  require Ash.Query

  alias Center.Sales.SalePayment
  alias Center.Sales.SaleInvoice
  alias Center.Integration.Odoo.{Invoices, Client, SyncEvents}
  alias Center.Domain.Payments.Validators

  @impl Oban.Worker
  def backoff(%Oban.Job{attempt: attempt}) do
    trunc(:math.pow(2, attempt) * 15 + :rand.uniform(15))
  end

  @impl Oban.Worker
  def perform(%Oban.Job{args: %{"sale_payment_id" => payment_id}} = job) do
    case SalePayment |> Ash.get(payment_id, load: [:sale_invoice, :journal, :sender_bank_account, :receiver_bank_account, sale_order: [:company]]) do
      {:ok, payment} ->
        process_payment(payment, job)

      {:error, _} ->
        Logger.error("PaymentRegister: SalePayment ##{payment_id} not found")
        {:error, :payment_not_found}
    end
  end

  defp process_payment(payment, job) do
    cond do
      payment.status == "confirmed" and payment.odoo_payment_id != nil ->
        Logger.info("PaymentRegister: SalePayment ##{payment.id} already confirmed with Odoo payment ##{payment.odoo_payment_id}")
        {:ok, :already_registered}

      payment.status == "sync_failed" ->
        Logger.info("PaymentRegister: SalePayment ##{payment.id} already marked as sync_failed — skipping")
        {:cancel, :sync_failed}

      is_nil(payment.sale_invoice) ->
        Logger.error("PaymentRegister: SalePayment ##{payment.id} has no linked invoice")
        {:error, :no_invoice}

      is_nil(payment.sale_invoice.odoo_invoice_id) ->
        Logger.info("PaymentRegister: SaleInvoice ##{payment.sale_invoice.id} has no Odoo invoice ID yet — snoozing 15s")
        {:snooze, 15}

      true ->
        register_and_sync(payment, job)
    end
  end

  defp register_and_sync(payment, %Oban.Job{attempt: attempt, max_attempts: max_attempts} = _job) do
    invoice = payment.sale_invoice
    order = payment.sale_order
    odoo_invoice_id = invoice.odoo_invoice_id
    is_last_attempt = attempt >= max_attempts

    {:ok, latest_invoice} = Invoices.fetch_invoice_state(odoo_invoice_id)

    # 1. Validate Sales Flow Integrity
    Validators.validate_invoice_payment_integrity!(payment.amount, latest_invoice)

    external_ref = "Payment-#{payment.id}"
    company_odoo_id = order && order.company && order.company.odoo_id

    try do
      # 2. Resolve Journal
      journal = resolve_odoo_journal!(payment, order.company_id)
      odoo_journal_id = journal.odoo_id

      {:ok, [odoo_journal]} = Client.read("account.journal", [odoo_journal_id], [
        "company_id", "type", "currency_id", "default_account_id", "active"
      ])

      # 3. Validate Consistency
      invoice_company_id = order.company_id
      invoice_partner_id = extract_m2o_id(invoice.odoo_data["partner_id"])

      Validators.validate_company_consistency!(
         invoice_company_id,
         invoice_company_id,
         journal.company_id
      )

      Validators.validate_partner_bank!(invoice_partner_id, payment.sender_bank_account && payment.sender_bank_account.partner_id)
      Validators.validate_outstanding_account_configuration!(odoo_journal)

      payment_type = "inbound"
      Validators.validate_payment_method_and_currency!(
        payment_type, 
        extract_m2o_id(latest_invoice["currency_id"]), 
        odoo_journal
      )

      partner_bank_id = payment.sender_bank_account && payment.sender_bank_account.odoo_partner_bank_id

      payment_attrs = %{
        amount: payment.amount,
        journal_id: odoo_journal_id,
        payment_date: payment.payment_date,
        memo: payment.memo || external_ref,
        company_id: company_odoo_id,
        partner_bank_id: partner_bank_id,
        payment_type: payment_type
      }
      
      execute_payment_registration(payment, odoo_invoice_id, payment_attrs, order)
    rescue
      e ->
        error_msg = Exception.message(e)
        Logger.error("PaymentRegister: Failed for SalePayment ##{payment.id}: #{inspect(e.__struct__)}: #{error_msg}")

        if is_last_attempt do
          _ = payment |> Ash.Changeset.for_update(:mark_sync_failed) |> Ash.update()
          if order, do: update_order_payment_status(order)
          if invoice, do: sync_invoice_residual(invoice.id)
        end

        broadcast_failure(payment, error_msg)
        {:error, error_msg}
    end
  end

  defp extract_m2o_id([id, _name]) when is_integer(id), do: id
  defp extract_m2o_id(id) when is_integer(id), do: id
  defp extract_m2o_id(_), do: nil

  defp execute_payment_registration(payment, odoo_invoice_id, payment_attrs, order) do
    invoice = payment.sale_invoice

    case Invoices.register_payment(odoo_invoice_id, payment_attrs) do
      {:ok, %{invoice_data: invoice_data, odoo_payment_id: odoo_payment_id}} ->
        {:ok, _} =
          payment
          |> Ash.Changeset.for_update(:update, %{
            status: "confirmed",
            odoo_payment_id: odoo_payment_id,
            synced_at: DateTime.utc_now(:second),
            odoo_data: Map.merge(payment.odoo_data || %{}, %{
              "registered_at" => DateTime.utc_now(:second) |> DateTime.to_iso8601(),
              "odoo_invoice_id" => odoo_invoice_id,
              "odoo_payment_id" => odoo_payment_id
            })
          })
          |> Ash.update()

        if invoice_data do
          update_invoice_from_odoo(invoice, invoice_data)
        else
          refresh_invoice_state(invoice, odoo_invoice_id)
        end

        update_order_payment_status(order)
        broadcast_success(payment)
        {:ok, :registered}

      {:error, reason} ->
        error_msg = format_error(reason)
        broadcast_failure(payment, error_msg)
        {:error, reason}
    end
  end

  defp resolve_odoo_journal!(payment, company_id) do
    if is_nil(company_id), do: raise "Journal resolution failed: Missing company context."

    cond do
      payment.journal && payment.journal.odoo_id ->
        if payment.journal.company_id != company_id, do: raise "Journal resolution failed: Journal company mismatch."
        payment.journal
      true ->
        raise "Journal resolution failed: Payment record is missing an explicit Odoo-synced journal."
    end
  end

  defp update_invoice_from_odoo(invoice, odoo_data) do
    changes = %{
      payment_state: odoo_data["payment_state"] || invoice.payment_state,
      amount_residual: to_decimal(odoo_data["amount_residual"]),
      odoo_data: odoo_data,
      synced_at: DateTime.utc_now(:second)
    }

    changes =
      case odoo_data["payment_state"] do
        "paid" -> Map.put(changes, :status, "paid")
        "in_payment" -> Map.put(changes, :status, "posted")
        _ -> changes
      end

    invoice |> Ash.Changeset.for_update(:update, changes) |> Ash.update()
  end

  defp refresh_invoice_state(invoice, odoo_invoice_id) do
    case Invoices.fetch_invoice_state(odoo_invoice_id) do
      {:ok, data} ->
        invoice
        |> Ash.Changeset.for_update(:update, %{
          payment_state: data["payment_state"] || invoice.payment_state,
          amount_residual: to_decimal(data["amount_residual"]),
          synced_at: DateTime.utc_now(:second)
        })
        |> Ash.update()
      {:error, _} -> :ok
    end
  end

  defp update_order_payment_status(nil), do: :ok
  defp update_order_payment_status(order) do
    payments =
      SalePayment
      |> Ash.Query.filter(sale_order_id == ^order.id and status not in ["cancelled", "sync_failed"])
      |> Ash.read!()

    total_paid = Enum.reduce(payments, Decimal.new(0), &Decimal.add(&2, &1.amount))
    grand_total = order.grand_total || Decimal.new(0)

    payment_status =
      cond do
        Decimal.compare(total_paid, grand_total) != :lt -> "paid"
        Decimal.compare(total_paid, 0) == :gt -> "partial"
        true -> "unpaid"
      end

    order |> Ash.Changeset.for_update(:update, %{payment_status: payment_status}) |> Ash.update()
  end

  defp sync_invoice_residual(invoice_id) do
    case SaleInvoice |> Ash.get(invoice_id) do
      {:ok, invoice} ->
        paid = SalePayment
               |> Ash.Query.filter(sale_invoice_id == ^invoice_id and status not in ["cancelled", "sync_failed"])
               |> Ash.read!()
               |> Enum.reduce(Decimal.new(0), &Decimal.add(&2, &1.amount))

        total = invoice.amount_total || Decimal.new(0)
        residual = Decimal.sub(total, paid) |> Decimal.max(Decimal.new(0))

        payment_state =
          cond do
            Decimal.compare(residual, Decimal.new(0)) != :gt -> "paid"
            Decimal.compare(paid, Decimal.new(0)) == :gt -> "partial"
            true -> "not_paid"
          end

        invoice |> Ash.Changeset.for_update(:update, %{amount_residual: residual, payment_state: payment_state}) |> Ash.update()
      _ -> :ok
    end
  end

  defp broadcast_success(payment) do
    Phoenix.PubSub.broadcast(Center.PubSub, SyncEvents.topic_sale_orders(), {:payment_synced, %{order_id: payment.sale_order_id, payment_id: payment.id, invoice_id: payment.sale_invoice_id}})
  end

  defp broadcast_failure(payment, error_msg) do
    Phoenix.PubSub.broadcast(Center.PubSub, SyncEvents.topic_sale_orders(), {:payment_sync_failed, %{order_id: payment.sale_order_id, payment_id: payment.id, error: error_msg}})
  end

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

  defp format_error({:payment_wizard_create_failed, reason}), do: "Gagal membuat wizard pembayaran: #{inspect_error(reason)}"
  defp format_error({:payment_execute_failed, reason}), do: "Gagal menjalankan wizard pembayaran: #{inspect_error(reason)}"
  defp format_error(reason), do: inspect(reason)

  defp inspect_error(%{message: msg}) when is_binary(msg), do: msg
  defp inspect_error(reason), do: inspect(reason)
end
