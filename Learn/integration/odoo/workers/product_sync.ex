defmodule Center.Integration.Odoo.Workers.ProductSync do
  @moduledoc """
  Oban worker that synchronizes products from Odoo `product.template`.

  Resolves `categ_id` → local `product_category_id` and `company_id`.
  The `sale_ok` field determines if a product is available for sales.
  """
  use Oban.Worker, queue: :sync, max_attempts: 3

  require Logger
  require Ash.Query

  alias Center.Integration.Odoo.{Client, SyncEvents}
  alias Center.Catalog.Product

  @fields [
    "name",
    "display_name",
    "default_code",
    "type",
    "list_price",
    "standard_price",
    "qty_available",
    "barcode",
    "sale_ok",
    "description_sale",
    "uom_id",
    "categ_id",
    "company_id",
    "seller_ids",
    "active"
  ]

  @impl Oban.Worker
  def perform(_job) do
    Logger.info("ProductSync: Starting product sync from Odoo")
    SyncEvents.broadcast_started(SyncEvents.topic_products())

    domain = ["|", ["active", "=", true], ["active", "=", false]]

    case Client.search_read("product.template", domain, @fields, limit: 10000) do
      {:ok, records} when is_list(records) ->
        Logger.info("ProductSync: Received #{length(records)} products from Odoo")
        sync_records(records)

      {:ok, other} ->
        Logger.error("ProductSync: Unexpected response: #{inspect(other)}")
        {:error, "unexpected_response"}

      {:error, reason} ->
        Logger.error("ProductSync: Failed to fetch products: #{inspect(reason)}")
        {:error, reason}
    end
  end

  defp sync_records(records) do
    now = DateTime.utc_now(:second)

    results =
      Enum.map(records, fn record ->
        category_id = resolve_category_id(record["categ_id"])
        company_id = resolve_company_id(record["company_id"])

        attrs = %{
          odoo_id: record["id"],
          name: record["display_name"] || record["name"],
          default_code: to_string_or_nil(record["default_code"]),
          product_type: to_string_or_nil(record["type"]),
          list_price: record["list_price"],
          standard_price: record["standard_price"],
          qty_available: record["qty_available"],
          barcode: to_string_or_nil(record["barcode"]),
          sale_ok: record["sale_ok"] || false,
          description_sale: to_string_or_nil(record["description_sale"]),
          uom: extract_name(record["uom_id"]),
          product_category_id: category_id,
          vendor_id: resolve_vendor_id(record["seller_ids"]),
          company_id: company_id,
          active: record["active"] || true,
          odoo_data: record,
          synced_at: now
        }

        case Product |> Ash.Changeset.for_create(:upsert_from_odoo, attrs) |> Ash.create() do
          {:ok, product} ->
            {:ok, product}

          {:error, error} ->
            Logger.warning("ProductSync: Failed to upsert product #{record["id"]}: #{inspect(error)}")

            {:error, error}
        end
      end)

    ok_count = Enum.count(results, &match?({:ok, _}, &1))
    err_count = Enum.count(results, &match?({:error, _}, &1))
    Logger.info("ProductSync: Completed — #{ok_count} synced, #{err_count} errors")
    SyncEvents.broadcast(SyncEvents.topic_products(), %{synced: ok_count, errors: err_count})

    :ok
  end

  # categ_id comes as [id, "name"] or false
  defp resolve_category_id([odoo_id, _name]) when is_integer(odoo_id) do
    case Center.Catalog.ProductCategory |> Ash.Query.filter(odoo_id == ^odoo_id) |> Ash.read_first() do
      {:ok, category} when not is_nil(category) -> category.id
      _ -> nil
    end
  end

  defp resolve_category_id(_), do: nil

  # company_id comes as [id, "name"] or false
  defp resolve_company_id([odoo_id, _name]) when is_integer(odoo_id) do
    case Center.MasterData.Company |> Ash.Query.filter(odoo_id == ^odoo_id) |> Ash.read_first() do
      {:ok, company} when not is_nil(company) -> company.id
      _ -> nil
    end
  end

  defp resolve_company_id(_), do: nil

  defp resolve_vendor_id([seller_id | _]) when is_integer(seller_id),
    do: resolve_partner_id_from_supplierinfo(seller_id)

  defp resolve_vendor_id(_), do: nil

  defp resolve_partner_id_from_supplierinfo(_supplierinfo_id) do
    nil
  end

  defp extract_name([_id, name]) when is_binary(name), do: name
  defp extract_name(_), do: nil

  defp to_string_or_nil(false), do: nil
  defp to_string_or_nil(nil), do: nil
  defp to_string_or_nil(val) when is_binary(val), do: val
  defp to_string_or_nil(_), do: nil
end
