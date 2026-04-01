defmodule Center.Integration.Odoo.Invoices do
  @moduledoc """
  Odoo integration adapter for Invoices (`account.move`).
  """

  @behaviour Center.Integration.Odoo.Integration

  require Logger

  alias Center.Integration.Odoo.Client

  @model "account.move"
  @sale_order_model "sale.order"
  @payment_register_model "account.payment.register"

  @read_fields [
    "name",
    "state",
    "move_type",
    "partner_id",
    "invoice_origin",
    "invoice_date",
    "invoice_date_due",
    "amount_untaxed",
    "amount_tax",
    "amount_total",
    "amount_residual",
    "payment_state",
    "invoice_line_ids",
    "company_id",
    "journal_id"
  ]

  @impl true
  def push_draft(_record) do
    {:error, :not_implemented}
  end

  @impl true
  def push_confirm(_record) do
    {:error, :not_implemented}
  end

  @impl true
  def push_cancel(_record) do
    {:error, :not_implemented}
  end

  @impl true
  def pull(odoo_id) when is_integer(odoo_id) do
    case Client.read(@model, [odoo_id], @read_fields) do
      {:ok, [record | _]} -> {:ok, record}
      {:ok, []} -> {:error, :not_found}
      {:error, reason} -> {:error, reason}
    end
  end

  @doc """
  Fetches all invoices linked to a sale order's Odoo name.
  """
  def pull_for_order(odoo_order_name) when is_binary(odoo_order_name) do
    domain = [["invoice_origin", "=", odoo_order_name]]

    case Client.search_read(@model, domain, @read_fields) do
      {:ok, records} when is_list(records) -> {:ok, records}
      {:error, reason} -> {:error, reason}
    end
  end

  @doc """
  Creates an invoice from a confirmed sale order in Odoo.
  """
  def create_invoice_from_order(odoo_order_id, invoice_type, opts \\ [])
      when is_integer(odoo_order_id) do
    dp_value = Keyword.get(opts, :dp_value)
    company_id = Keyword.get(opts, :company_id)
    journal_id = Keyword.get(opts, :journal_id)
    invoice_date = Keyword.get(opts, :invoice_date)

    Logger.info("Invoices: Creating invoice for SO Odoo##{odoo_order_id}, type=#{invoice_type}, journal=#{inspect(journal_id)}")

    order_fields = [
      "name",
      "partner_id",
      "currency_id",
      "company_id",
      "order_line",
      "amount_total",
      "payment_term_id",
      "fiscal_position_id"
    ]

    with {:ok, [order]} <- Client.read(@sale_order_model, [odoo_order_id], order_fields),
         {:ok, invoice_lines} <- build_direct_lines(order, invoice_type, dp_value) do
      partner_id = extract_m2o_id(order["partner_id"])
      currency_id = extract_m2o_id(order["currency_id"])
      order_company_id = extract_m2o_id(order["company_id"])
      payment_term_id = extract_m2o_id(order["payment_term_id"])
      fiscal_position_id = extract_m2o_id(order["fiscal_position_id"])

      move_vals =
        %{
          "move_type" => "out_invoice",
          "partner_id" => partner_id,
          "currency_id" => currency_id,
          "invoice_origin" => order["name"],
          "invoice_line_ids" => invoice_lines
        }
        |> put_non_nil("invoice_payment_term_id", payment_term_id)
        |> put_non_nil("fiscal_position_id", fiscal_position_id)
        |> put_non_nil("journal_id", journal_id)
        |> put_non_nil("invoice_date", format_date_string_opt(invoice_date))

      context = build_create_context(company_id || order_company_id)

      case Client.call(@model, "create", %{vals_list: [move_vals], context: context}) do
        {:ok, [invoice_id | _]} when is_integer(invoice_id) ->
          fetch_created_invoice(invoice_id, odoo_order_id)

        {:ok, invoice_id} when is_integer(invoice_id) ->
          fetch_created_invoice(invoice_id, odoo_order_id)

        {:error, reason} ->
          Logger.error("Invoices: Failed to create invoice for SO Odoo##{odoo_order_id}: #{inspect(reason)}")

          {:error, {:invoice_create_failed, reason}}
      end
    else
      {:ok, []} ->
        {:error, :order_not_found}

      {:error, reason} ->
        {:error, reason}
    end
  end

  @doc """
  Validates (posts) a draft invoice in Odoo.
  """
  def validate_invoice(odoo_invoice_id) when is_integer(odoo_invoice_id) do
    Logger.info("Invoices: Posting invoice Odoo##{odoo_invoice_id}")

    case Client.call(@model, "action_post", %{ids: [odoo_invoice_id]}) do
      {:ok, _result} ->
        # Re-read the invoice to get updated state
        pull(odoo_invoice_id)

      {:error, reason} ->
        Logger.error("Invoices: Failed to post invoice Odoo##{odoo_invoice_id}: #{inspect(reason)}")

        {:error, {:post_failed, reason}}
    end
  end

  @doc """
  Updates the notes on an Odoo invoice.
  """
  def update_notes(odoo_invoice_id, notes) when is_integer(odoo_invoice_id) do
    Logger.info("Invoices: Updating notes for invoice Odoo##{odoo_invoice_id}")

    vals = %{"narration" => notes || ""}

    case Client.write(@model, [odoo_invoice_id], vals) do
      {:ok, true} ->
        {:ok, :updated}

      {:error, reason} ->
        Logger.error("Invoices: Failed to update notes for invoice Odoo##{odoo_invoice_id}: #{inspect(reason)}")

        {:error, reason}
    end
  end

  @doc """
  Registers a payment against a posted invoice in Odoo.
  """
  def register_payment(odoo_invoice_id, payment_attrs) when is_integer(odoo_invoice_id) do
    amount = Map.get(payment_attrs, :amount)
    journal_id = Map.get(payment_attrs, :journal_id)
    payment_date = Map.get(payment_attrs, :payment_date)
    memo = Map.get(payment_attrs, :memo)
    company_id = Map.get(payment_attrs, :company_id)
    partner_bank_id = Map.get(payment_attrs, :partner_bank_id)

    Logger.info(
      "Invoices: Registering payment for invoice Odoo##{odoo_invoice_id}, " <>
        "amount=#{inspect(amount)}, journal=#{inspect(journal_id)}"
    )

    context = %{
      "active_model" => @model,
      "active_ids" => [odoo_invoice_id],
      "active_id" => odoo_invoice_id,
      "lang" => "id_ID"
    }

    context =
      if company_id do
        Map.put(context, "allowed_company_ids", [company_id])
      else
        context
      end

    wizard_vals = %{}

    wizard_vals =
      if amount do
        Map.put(wizard_vals, :amount, ensure_float(amount))
      else
        wizard_vals
      end

    wizard_vals =
      if journal_id do
        Map.put(wizard_vals, :journal_id, journal_id)
      else
        wizard_vals
      end

    wizard_vals =
      if payment_date do
        Map.put(wizard_vals, :payment_date, format_date_string(payment_date))
      else
        wizard_vals
      end

    wizard_vals =
      if memo do
        Map.put(wizard_vals, :communication, memo)
      else
        wizard_vals
      end

    wizard_vals =
      if partner_bank_id do
        Map.put(wizard_vals, :partner_bank_id, partner_bank_id)
      else
        wizard_vals
      end

    case Client.call(
           @payment_register_model,
           "create",
           %{vals_list: [wizard_vals], context: context}
         ) do
      {:ok, wizard_ids} when is_list(wizard_ids) ->
        wizard_id = List.first(wizard_ids)
        execute_payment_wizard(wizard_id, context, odoo_invoice_id)

      {:ok, wizard_id} when is_integer(wizard_id) ->
        execute_payment_wizard(wizard_id, context, odoo_invoice_id)

      {:error, reason} ->
        Logger.error("Invoices: Failed to create payment wizard for invoice Odoo##{odoo_invoice_id}: #{inspect(reason)}")

        {:error, {:payment_wizard_create_failed, reason}}
    end
  end

  @doc """
  Fetches the current state of an invoice from Odoo and returns
  retrieval functions for normalized invoice data.
  """
  def fetch_invoice_state(odoo_invoice_id) when is_integer(odoo_invoice_id) do
    case pull(odoo_invoice_id) do
      {:ok, data} ->
        {:ok, normalize_invoice_data(data)}

      {:error, reason} ->
        {:error, reason}
    end
  end

  @doc """
  Fetches all invoices for a sale order from Odoo.
  """
  def fetch_invoices_for_order(odoo_order_name) when is_binary(odoo_order_name) do
    case pull_for_order(odoo_order_name) do
      {:ok, records} ->
        {:ok, Enum.map(records, &normalize_invoice_data/1)}

      {:error, reason} ->
        {:error, reason}
    end
  end

  defp build_direct_lines(order, "regular", _dp_value) do
    with {:ok, lines} <- build_full_invoice_lines(order) do
      dp_deductions = build_dp_deduction_lines(order)
      {:ok, lines ++ dp_deductions}
    end
  end

  defp build_direct_lines(order, "dp_percent", dp_value) when not is_nil(dp_value) do
    order_total = order["amount_total"] || 0.0
    amount = order_total * ensure_float(dp_value) / 100.0
    {:ok, [build_dp_line(amount, "Down Payment #{ensure_float(dp_value)}%", order)]}
  end

  defp build_direct_lines(order, "dp_fixed", dp_value) when not is_nil(dp_value) do
    {:ok, [build_dp_line(ensure_float(dp_value), "Down Payment", order)]}
  end

  defp build_direct_lines(order, _type, _dp_value) do
    build_full_invoice_lines(order)
  end

  defp build_full_invoice_lines(order) do
    order_line_ids = order["order_line"] || []

    if order_line_ids == [] do
      {:error, :no_order_lines}
    else
      line_fields = [
        "product_id",
        "product_uom_qty",
        "price_unit",
        "discount",
        "name",
        "tax_ids",
        "product_uom_id",
        "display_type"
      ]

      case Client.read("sale.order.line", order_line_ids, line_fields) do
        {:ok, lines} when is_list(lines) ->
          analytic_dist = resolve_sales_analytic_distribution(order["company_id"])
          {:ok, Enum.map(lines, &so_line_to_invoice_line(&1, analytic_dist))}

        {:error, reason} ->
          {:error, {:line_read_failed, reason}}
      end
    end
  end

  defp so_line_to_invoice_line(line, analytic_dist) do
    display_type = line["display_type"]

    if display_type in ["line_section", "line_note"] do
      [0, 0, %{"display_type" => display_type, "name" => line["name"] || ""}]
    else
      product_id = extract_m2o_id(line["product_id"])
      uom_id = extract_m2o_id(line["product_uom_id"])
      tax_ids = line["tax_ids"] || []

      vals =
        %{
          "product_id" => product_id,
          "name" => line["name"],
          "quantity" => line["product_uom_qty"],
          "price_unit" => line["price_unit"],
          "discount" => line["discount"] || 0.0,
          "tax_ids" => [[6, 0, tax_ids]],
          "sale_line_ids" => [[6, 0, [line["id"]]]]
        }
        |> put_non_nil("product_uom_id", uom_id)

      vals =
        if analytic_dist do
          Map.put(vals, "analytic_distribution", analytic_dist)
        else
          vals
        end

      [0, 0, vals]
    end
  end

  defp build_dp_line(amount, description, order) do
    vals =
      %{"name" => description, "quantity" => 1.0, "price_unit" => amount}
      |> maybe_link_dp_to_sale_line(order)

    [0, 0, vals]
  end

  defp maybe_link_dp_to_sale_line(vals, order) do
    case order["order_line"] do
      [first_id | _] when is_integer(first_id) ->
        Map.put(vals, "sale_line_ids", [[6, 0, [first_id]]])

      _ ->
        vals
    end
  end

  defp build_dp_deduction_lines(order) do
    order_name = order["name"]

    case pull_for_order(order_name) do
      {:ok, records} when is_list(records) and records != [] ->
        records
        |> Enum.filter(fn r -> r["state"] != "cancel" end)
        |> Enum.map(fn inv ->
          inv_name = inv["name"] || "DP"
          amount = inv["amount_total"] || 0.0

          [
            0,
            0,
            %{
              "name" => "Potongan #{inv_name}",
              "quantity" => 1.0,
              "price_unit" => -ensure_float(amount)
            }
          ]
        end)

      _ ->
        []
    end
  end

  defp fetch_created_invoice(invoice_id, odoo_order_id) do
    case Client.read(@model, [invoice_id], @read_fields) do
      {:ok, [invoice]} ->
        Logger.info(
          "Invoices: Created invoice #{invoice["name"]} (ID: #{invoice_id}) " <>
            "for SO Odoo##{odoo_order_id}"
        )

        {:ok, [invoice]}

      {:ok, []} ->
        {:error, :invoice_not_found_after_create}

      {:error, reason} ->
        {:error, {:invoice_read_failed, reason}}
    end
  end

  defp build_create_context(nil), do: %{"lang" => "id_ID"}

  defp build_create_context(company_id),
    do: %{"lang" => "id_ID", "allowed_company_ids" => [company_id]}

  defp put_non_nil(map, _key, nil), do: map
  defp put_non_nil(map, key, value), do: Map.put(map, key, value)

  defp execute_payment_wizard(wizard_id, context, odoo_invoice_id) do
    case Client.call(
           @payment_register_model,
           "action_create_payments",
           %{ids: [wizard_id], context: context}
         ) do
      {:ok, result} ->
        odoo_payment_id =
          cond do
            is_map(result) && result["res_id"] -> result["res_id"]
            is_map(result) && is_list(result["res_ids"]) -> List.first(result["res_ids"])
            true -> nil
          end

        # After payment is created, mark related payments as sent ("Terkirim")
        _ = mark_invoice_payments_as_sent(odoo_invoice_id)

        case pull(odoo_invoice_id) do
          {:ok, invoice_data} ->
            Logger.info(
              "Invoices: Payment registered for invoice Odoo##{odoo_invoice_id}, " <>
                "payment_state=#{invoice_data["payment_state"]}, odoo_payment_id=#{inspect(odoo_payment_id)}"
            )

            {:ok, %{invoice_data: invoice_data, odoo_payment_id: odoo_payment_id}}

          {:error, reason} ->
            Logger.warning("Invoices: Payment likely registered but re-read failed for invoice Odoo##{odoo_invoice_id}: #{inspect(reason)}")

            {:ok, %{invoice_data: nil, odoo_payment_id: odoo_payment_id}}
        end

      {:error, reason} ->
        Logger.error("Invoices: Payment wizard execution failed for invoice Odoo##{odoo_invoice_id}: #{inspect(reason)}")

        {:error, {:payment_execute_failed, reason}}
    end
  end

  defp normalize_invoice_data(data) when is_map(data) do
    %{
      odoo_id: data["id"],
      name: data["name"],
      state: data["state"],
      move_type: data["move_type"],
      invoice_origin: data["invoice_origin"],
      amount_untaxed: data["amount_untaxed"],
      amount_tax: data["amount_tax"],
      amount_total: data["amount_total"],
      amount_residual: data["amount_residual"],
      payment_state: data["payment_state"],
      invoice_date: data["invoice_date"],
      invoice_date_due: data["invoice_date_due"],
      company_id: extract_m2o_id(data["company_id"]),
      journal_id: extract_m2o_id(data["journal_id"]),
      partner_id: extract_m2o_id(data["partner_id"])
    }
  end

  defp extract_m2o_id([id, _name]) when is_integer(id), do: id
  defp extract_m2o_id(id) when is_integer(id), do: id
  defp extract_m2o_id(_), do: nil

  defp ensure_float(val) when is_float(val), do: val
  defp ensure_float(val) when is_integer(val), do: val / 1

  defp ensure_float(%Decimal{} = val) do
    Decimal.to_float(val)
  end

  defp ensure_float(val) when is_binary(val) do
    case Float.parse(val) do
      {f, _} -> f
      :error -> 0.0
    end
  end

  defp ensure_float(_), do: 0.0

  defp format_date_string(%Date{} = d), do: Date.to_iso8601(d)

  defp format_date_string(%DateTime{} = dt) do
    dt |> DateTime.to_date() |> Date.to_iso8601()
  end

  defp format_date_string(val) when is_binary(val) do
    String.slice(val, 0, 10)
  end

  defp format_date_string(_), do: nil

  defp format_date_string_opt(nil), do: nil
  defp format_date_string_opt(val), do: format_date_string(val)

  defp resolve_sales_analytic_distribution(nil), do: nil
  defp resolve_sales_analytic_distribution(false), do: nil

  defp resolve_sales_analytic_distribution([_id, name]) do
    resolve_sales_analytic_distribution(%{name: name})
  end

  defp resolve_sales_analytic_distribution(%{name: name}) when is_binary(name) do
    location =
      name
      |> String.split()
      |> List.last()
      |> String.upcase()

    ref = "SALES-#{location}"

    case Client.search_read(
           "account.analytic.account",
           [["code", "=", ref]],
           ["id"],
           limit: 1
         ) do
      {:ok, [%{"id" => analytic_id} | _]} ->
        %{to_string(analytic_id) => 100}

      _ ->
        Logger.warning("Invoices: Analytic account not found for ref=#{ref}")
        nil
    end
  end

  defp resolve_sales_analytic_distribution(_), do: nil

  defp mark_invoice_payments_as_sent(odoo_invoice_id) do
    case Client.search_read(
           "account.payment",
           [["reconciled_invoice_ids", "in", odoo_invoice_id]],
           ["id", "is_sent"]
         ) do
      {:ok, payments} when is_list(payments) ->
        Enum.each(payments, fn payment ->
          if not payment["is_sent"] do
            Client.write("account.payment", [payment["id"]], %{is_sent: true})
            Logger.info("Invoices: Marked payment Odoo##{payment["id"]} as sent")
          end
        end)

      _ ->
        :ok
    end
  end
end
