defmodule Center.Integration.Odoo.SaleOrders do
  @moduledoc """
  Odoo integration adapter for Sale Orders (`sale.order`).
  """

  @behaviour Center.Integration.Odoo.Integration

  require Logger
  require Ash.Query

  alias Center.Integration.Odoo.Client
  alias Center.Sales.Ecto.SaleOrder, as: EctoSaleOrder
  alias Center.Sales.SaleOrder
  alias Center.Catalog.ProductVariant
  alias Center.MasterData.Employee

  @model "sale.order"

  @read_fields [
    "name",
    "state",
    "partner_id",
    "user_id",
    "company_id",
    "pricelist_id",
    "date_order",
    "validity_date",
    "commitment_date",
    "note",
    "amount_untaxed",
    "amount_tax",
    "amount_total",
    "order_line"
  ]

  @impl true
  def push_draft(%EctoSaleOrder{} = order) do
    # We load via Ash to ensure we have the right struct for subsequent Ash updates
    case SaleOrder |> Ash.get(order.id) do
      {:ok, ash_order} ->
        push_draft_ash(ash_order)
      {:error, reason} ->
        {:error, reason}
    end
  end

  def push_draft(%SaleOrder{} = order) do
    push_draft_ash(order)
  end

  def push_draft_ash(%SaleOrder{} = order) do
    order =
      order
      |> Ash.load!([
        :customer,
        :company,
        :salesperson,
        :pricelist,
        lines: [:product, :product_variant]
      ])

    with {:ok, customer_odoo_id} <- resolve_customer_odoo_id(order),
         company_odoo_id <- resolve_company_odoo_id(order),
         {:ok, values} <- build_order_values(order, customer_odoo_id, company_odoo_id),
         {:ok, odoo_id} <- Client.create(@model, values) do
      fetch_and_update_local(order, odoo_id)
    else
      {:error, reason} ->
        Logger.error("SaleOrders.push_draft: Failed for order ##{order.id}: #{inspect(reason)}")
        {:error, reason}
    end
  end

  @impl true
  def push_confirm(%EctoSaleOrder{odoo_id: nil}) do
    {:error, :not_pushed_yet}
  end

  def push_confirm(%EctoSaleOrder{odoo_id: odoo_id} = order) when is_integer(odoo_id) do
    case Client.call(@model, "action_confirm", %{ids: [odoo_id]}) do
      {:ok, _} ->
        Logger.info("SaleOrders.push_confirm: Confirmed order ##{order.id} (Odoo ##{odoo_id})")
        
        case SaleOrder |> Ash.get(order.id) do
          {:ok, ash_order} -> fetch_and_update_local(ash_order, odoo_id)
          {:error, reason} -> {:error, reason}
        end

      {:error, reason} ->
        Logger.error("SaleOrders.push_confirm: Failed for order ##{order.id}: #{inspect(reason)}")
        {:error, reason}
    end
  end

  def push_confirm(%SaleOrder{odoo_id: nil}), do: {:error, :not_pushed_yet}

  def push_confirm(%SaleOrder{odoo_id: odoo_id} = order) when is_integer(odoo_id) do
    case Client.call(@model, "action_confirm", %{ids: [odoo_id]}) do
      {:ok, _} ->
        Logger.info("SaleOrders.push_confirm: Confirmed order ##{order.id} (Odoo ##{odoo_id})")
        fetch_and_update_local(order, odoo_id)

      {:error, reason} ->
        Logger.error("SaleOrders.push_confirm: Failed for order ##{order.id}: #{inspect(reason)}")
        {:error, reason}
    end
  end

  @impl true
  def push_cancel(%EctoSaleOrder{odoo_id: nil}) do
    {:error, :not_pushed_yet}
  end

  def push_cancel(%EctoSaleOrder{odoo_id: odoo_id} = order) when is_integer(odoo_id) do
    case Client.call(@model, "action_cancel", %{ids: [odoo_id]}) do
      {:ok, _} ->
        Logger.info("SaleOrders.push_cancel: Cancelled order ##{order.id} (Odoo ##{odoo_id})")

        case SaleOrder |> Ash.get(order.id) do
          {:ok, ash_order} ->
            ash_order
            |> Ash.Changeset.for_update(:update, %{synced_at: DateTime.utc_now(:second)})
            |> Ash.update()
          {:error, reason} -> {:error, reason}
        end

      {:error, reason} ->
        Logger.error("SaleOrders.push_cancel: Failed for order ##{order.id}: #{inspect(reason)}")
        {:error, reason}
    end
  end

  def push_cancel(%SaleOrder{odoo_id: nil}), do: {:error, :not_pushed_yet}

  def push_cancel(%SaleOrder{odoo_id: odoo_id} = order) when is_integer(odoo_id) do
    case Client.call(@model, "action_cancel", %{ids: [odoo_id]}) do
      {:ok, _} ->
        Logger.info("SaleOrders.push_cancel: Cancelled order ##{order.id} (Odoo ##{odoo_id})")

        order
        |> Ash.Changeset.for_update(:update, %{synced_at: DateTime.utc_now(:second)})
        |> Ash.update()

      {:error, reason} ->
        Logger.error("SaleOrders.push_cancel: Failed for order ##{order.id}: #{inspect(reason)}")
        {:error, reason}
    end
  end

  @impl true
  def pull(odoo_id) when is_integer(odoo_id) do
    case Client.read(@model, [odoo_id], @read_fields) do
      {:ok, [record | _]} ->
        {:ok, record}

      {:ok, []} ->
        {:error, :not_found}

      {:error, reason} ->
        {:error, reason}
    end
  end

  @doc """
  Updates an existing Odoo sale order with current local data.
  Used when editing a draft order that's already been pushed.
  """
  def push_update(%EctoSaleOrder{odoo_id: nil}), do: {:error, :not_pushed_yet}

  def push_update(%EctoSaleOrder{odoo_id: odoo_id} = order) when is_integer(odoo_id) do
    case SaleOrder |> Ash.get(order.id) do
      {:ok, ash_order} ->
        push_update_ash(ash_order, odoo_id)
      {:error, reason} ->
        {:error, reason}
    end
  end

  def push_update(%SaleOrder{odoo_id: nil}), do: {:error, :not_pushed_yet}

  def push_update(%SaleOrder{odoo_id: odoo_id} = order) when is_integer(odoo_id) do
    push_update_ash(order, odoo_id)
  end

  def push_update_ash(%SaleOrder{odoo_id: odoo_id} = order, odoo_id) do
    order =
      order
      |> Ash.load!([
        :customer,
        :company,
        :salesperson,
        :pricelist,
        lines: [:product, :product_variant]
      ])

    with {:ok, customer_odoo_id} <- resolve_customer_odoo_id(order) do
      values =
        %{
          partner_id: customer_odoo_id,
          note: order.notes || false,
          commitment_date: format_datetime(order.delivery_date),
          validity_date: format_date(order.expiry_date)
        }
        |> reject_false_values()

      with {:ok, line_commands} <- build_line_commands_for_update(order) do
        values = Map.put(values, :order_line, line_commands)

        case Client.write(@model, [odoo_id], values) do
          {:ok, _} ->
            Logger.info("SaleOrders.push_update: Updated order ##{order.id} (Odoo ##{odoo_id})")

            fetch_and_update_local(order, odoo_id)

          {:error, reason} ->
            Logger.error("SaleOrders.push_update: Failed for order ##{order.id}: #{inspect(reason)}")

            {:error, reason}
        end
      end
    end
  end

  @doc """
  Updates ONLY the notes on an Odoo sale order.
  Maps to the `note` field in Odoo.
  """
  def update_notes(odoo_id, notes) when is_integer(odoo_id) do
    Logger.info("SaleOrders: Updating notes for order Odoo##{odoo_id}")

    vals = %{"note" => notes || ""}

    case Client.write(@model, [odoo_id], vals) do
      {:ok, true} ->
        {:ok, :updated}

      {:error, reason} ->
        Logger.error("SaleOrders: Failed to update notes for order Odoo##{odoo_id}: #{inspect(reason)}")

        {:error, reason}
    end
  end

  defp fetch_and_update_local(order, odoo_id) do
    case Client.read(@model, [odoo_id], @read_fields) do
      {:ok, [odoo_record | _]} ->
        odoo_name = odoo_record["name"] || order.order_number

        order
        |> Ash.Changeset.for_update(:update, %{
          odoo_id: odoo_id,
          odoo_name: odoo_name,
          order_number: odoo_name,
          odoo_data: odoo_record,
          synced_at: DateTime.utc_now(:second)
        })
        |> Ash.update()

      {:error, _read_reason} ->
        order
        |> Ash.Changeset.for_update(:update, %{
          odoo_id: odoo_id,
          synced_at: DateTime.utc_now(:second)
        })
        |> Ash.update()
    end
  end

  defp build_order_values(order, customer_odoo_id, company_odoo_id) do
    analytic_dist = resolve_sales_analytic_distribution(order.company)

    with {:ok, line_commands} <- build_line_commands(order.lines, analytic_dist) do
      base = %{
        partner_id: customer_odoo_id,
        note: order.notes || false,
        commitment_date: format_datetime(order.delivery_date),
        validity_date: format_date(order.expiry_date),
        order_line: line_commands
      }

      base =
        if company_odoo_id,
          do: Map.put(base, :company_id, company_odoo_id),
          else: base

      {:ok,
       base
       |> maybe_put_pricelist(order)
       |> maybe_put_salesperson(order)
       |> reject_false_values()}
    end
  end

  defp build_line_commands(lines, analytic_dist) do
    lines
    |> Enum.map(fn line ->
      case line.display_type do
        "line_section" ->
          {:ok, [0, 0, %{display_type: "line_section", name: line.description || "Section"}]}

        _ ->
          case resolve_line_product_odoo_id(line) do
            {:ok, product_odoo_id} ->
              values = %{
                product_id: product_odoo_id,
                product_uom_qty: Decimal.to_float(line.quantity || Decimal.new(0)),
                price_unit: Decimal.to_float(line.unit_price || Decimal.new(0)),
                discount: Decimal.to_float(line.discount_percent || Decimal.new(0)),
                name: line.description || (line.product && line.product.name) || "Product"
              }

              values = maybe_put_analytic_distribution(values, analytic_dist)

              {:ok, [0, 0, values]}

            {:error, reason} ->
              {:error, reason}
          end
      end
    end)
    |> collect_line_results()
  end

  defp build_line_commands_for_update(order) do
    existing_line_ids =
      case order.odoo_data do
        %{"order_line" => ids} when is_list(ids) -> ids
        _ -> []
      end

    delete_commands = Enum.map(existing_line_ids, fn id -> [2, id, 0] end)

    analytic_dist = resolve_sales_analytic_distribution(order.company)

    create_results =
      order.lines
      |> Enum.map(fn line ->
        case line.display_type do
          "line_section" ->
            {:ok, [0, 0, %{display_type: "line_section", name: line.description || "Section"}]}

          _ ->
            case resolve_line_product_odoo_id(line) do
              {:ok, product_odoo_id} ->
                values = %{
                  product_id: product_odoo_id,
                  product_uom_qty: Decimal.to_float(line.quantity || Decimal.new(0)),
                  price_unit: Decimal.to_float(line.unit_price || Decimal.new(0)),
                  discount: Decimal.to_float(line.discount_percent || Decimal.new(0)),
                  name: line.description || (line.product && line.product.name) || "Product"
                }

                values = maybe_put_analytic_distribution(values, analytic_dist)

                {:ok, [0, 0, values]}

              {:error, reason} ->
                {:error, reason}
            end
        end
      end)
      |> collect_line_results()

    case create_results do
      {:ok, create_commands} -> {:ok, delete_commands ++ create_commands}
      {:error, reason} -> {:error, reason}
    end
  end

  defp resolve_customer_odoo_id(%{customer: %{odoo_id: odoo_id}}) when is_integer(odoo_id) do
    {:ok, odoo_id}
  end

  defp resolve_customer_odoo_id(%{customer: customer}) when not is_nil(customer) do
    # Here customer is probably an Ecto struct if loaded via Ecto or Ash struct if loaded via Ash
    # Center.Integration.Odoo.Customers.push_to_odoo should handle both if properly refactored.
    # Currently it takes %Center.MasterData.Ecto.Customer{}
    case Center.Integration.Odoo.Customers.push_to_odoo(customer) do
      {:ok, updated} -> {:ok, updated.odoo_id}
      {:error, :already_pushed} -> {:ok, customer.odoo_id}
      {:error, reason} -> {:error, {:customer_push_failed, reason}}
    end
  end

  defp resolve_customer_odoo_id(_), do: {:error, :no_customer}

  defp resolve_company_odoo_id(%{company: %{odoo_id: odoo_id}}) when is_integer(odoo_id) do
    odoo_id
  end

  defp resolve_company_odoo_id(_), do: nil

  # Resolves the Odoo `product.product` (variant) ID for a sale order line.
  defp resolve_line_product_odoo_id(line) do
    case resolve_product_variant_odoo_id(line) do
      {:ok, odoo_id} ->
        {:ok, odoo_id}

      :not_found ->
        case resolve_default_variant_odoo_id(line) do
          {:ok, odoo_id} ->
            {:ok, odoo_id}

          :not_found ->
            product_name = (line.product && line.product.name) || "unknown"
            product_id = line.product_id

            Logger.error(
              "SaleOrders: No product.product (variant) found for product template " <>
                "\"#{product_name}\" (id=#{product_id})."
            )

            {:error,
             {:missing_product_variant,
              "Product \"#{product_name}\" has no synced variant in Odoo."}}
        end
    end
  end

  defp resolve_product_variant_odoo_id(%{product_variant: %{odoo_id: odoo_id}})
       when is_integer(odoo_id) do
    {:ok, odoo_id}
  end

  defp resolve_product_variant_odoo_id(_), do: :not_found

  # Look up the default (first) variant for a product template from the local DB.
  defp resolve_default_variant_odoo_id(%{product: %{id: product_id}})
       when is_integer(product_id) do
    query = 
      ProductVariant
      |> Ash.Query.filter(product_id == ^product_id and not is_nil(odoo_id))
      |> Ash.Query.sort(id: :asc)
      |> Ash.Query.limit(1)

    case Ash.read_first(query) do
      {:ok, %{odoo_id: odoo_id}} when not is_nil(odoo_id) -> {:ok, odoo_id}
      _ -> :not_found
    end
  end

  defp resolve_default_variant_odoo_id(_), do: :not_found

  # Collects line build results, returning {:ok, commands} or the first {:error, reason}
  defp collect_line_results(results) do
    Enum.reduce_while(results, {:ok, []}, fn
      {:ok, command}, {:ok, acc} -> {:cont, {:ok, acc ++ [command]}}
      {:error, reason}, _acc -> {:halt, {:error, reason}}
    end)
  end

  defp maybe_put_pricelist(values, %{pricelist: %{odoo_id: odoo_id}})
       when is_integer(odoo_id) do
    Map.put(values, :pricelist_id, odoo_id)
  end

  defp maybe_put_pricelist(values, _), do: values

  defp maybe_put_salesperson(values, %{salesperson: %{odoo_data: %{"user_id" => [uid, _]}}})
       when is_integer(uid) do
    Map.put(values, :user_id, uid)
  end

  defp maybe_put_salesperson(values, %{salesperson_id: nil}), do: values

  defp maybe_put_salesperson(values, %{salesperson_id: salesperson_id}) do
    case Employee |> Ash.get(salesperson_id) do
      {:ok, %{odoo_data: %{"user_id" => [uid, _]}}} when is_integer(uid) ->
        Map.put(values, :user_id, uid)

      _ ->
        values
    end
  end

  defp maybe_put_salesperson(values, _), do: values

  defp format_date(nil), do: false
  defp format_date(%Date{} = d), do: Date.to_iso8601(d)
  defp format_date(%DateTime{} = dt), do: DateTime.to_date(dt) |> Date.to_iso8601()

  defp format_datetime(nil), do: false

  defp format_datetime(%Date{} = d) do
    NaiveDateTime.new!(d, ~T[00:00:00]) |> format_odoo_datetime()
  end

  defp format_datetime(%DateTime{} = dt) do
    dt |> DateTime.to_naive() |> format_odoo_datetime()
  end

  defp format_datetime(%NaiveDateTime{} = ndt), do: format_odoo_datetime(ndt)

  defp format_odoo_datetime(%NaiveDateTime{} = ndt) do
    Calendar.strftime(ndt, "%Y-%m-%d %H:%M:%S")
  end

  defp reject_false_values(map) do
    Map.reject(map, fn {_k, v} -> v == false end)
  end

  defp resolve_sales_analytic_distribution(nil), do: nil

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
        Logger.warning("SaleOrders: Analytic account not found for ref=#{ref}")
        nil
    end
  end

  defp resolve_sales_analytic_distribution(_), do: nil

  defp maybe_put_analytic_distribution(values, nil), do: values

  defp maybe_put_analytic_distribution(values, dist),
    do: Map.put(values, :analytic_distribution, dist)
end
