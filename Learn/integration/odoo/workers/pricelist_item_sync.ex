defmodule Center.Integration.Odoo.Workers.PricelistItemSync do
  @moduledoc """
  Oban worker that synchronizes pricelist items (rules) from Odoo `product.pricelist.item`.
  """
  use Oban.Worker, queue: :sync, max_attempts: 3

  require Logger
  require Ash.Query

  alias Center.Integration.Odoo.{Client, SyncEvents}
  alias Center.Catalog.PricelistItem

  @fields [
    "pricelist_id",
    "applied_on",
    "categ_id",
    "product_tmpl_id",
    "product_id",
    "min_quantity",
    "date_start",
    "date_end",
    "compute_price",
    "fixed_price",
    "percent_price",
    "base"
  ]

  @impl Oban.Worker
  def perform(_job) do
    Logger.info("PricelistItemSync: Starting pricelist items sync from Odoo")
    SyncEvents.broadcast_started(SyncEvents.topic_pricelist_items())

    case Client.search_read("product.pricelist.item", [], @fields) do
      {:ok, records} when is_list(records) ->
        Logger.info("PricelistItemSync: Received #{length(records)} pricelist items from Odoo")
        sync_records(records)

      {:ok, other} ->
        Logger.error("PricelistItemSync: Unexpected response: #{inspect(other)}")
        {:error, "unexpected_response"}

      {:error, reason} ->
        Logger.error("PricelistItemSync: Failed to fetch pricelist items: #{inspect(reason)}")
        {:error, reason}
    end
  end

  defp sync_records(records) do
    now = DateTime.utc_now(:second)

    results =
      Enum.map(records, fn record ->
        pricelist_id = resolve_pricelist_id(record["pricelist_id"])

        if is_nil(pricelist_id) do
          {:error, :missing_parent_pricelist}
        else
          attrs = %{
            odoo_id: record["id"],
            pricelist_id: pricelist_id,
            applied_on: record["applied_on"],
            product_category_id: resolve_category_id(record["categ_id"]),
            product_id: resolve_product_id(record["product_tmpl_id"]),
            product_variant_id: resolve_variant_id(record["product_id"]),
            min_quantity: record["min_quantity"] || 0.0,
            date_start: parse_datetime(record["date_start"]),
            date_end: parse_datetime(record["date_end"]),
            compute_price: record["compute_price"] || "fixed",
            fixed_price: record["fixed_price"] || 0.0,
            percent_price: record["percent_price"] || 0.0,
            base: record["base"],
            odoo_data: record,
            synced_at: now
          }

          case PricelistItem |> Ash.Changeset.for_create(:upsert_from_odoo, attrs) |> Ash.create() do
            {:ok, item} ->
              {:ok, item}

            {:error, error} ->
              Logger.warning("PricelistItemSync: Failed to upsert item #{record["id"]}: #{inspect(error)}")

              {:error, error}
          end
        end
      end)

    # Clean up obsolete rules
    # Group the received records by pricelist_id and delete ones missing from Odoo
    records
    |> Enum.group_by(&resolve_pricelist_id(&1["pricelist_id"]))
    |> Enum.each(fn {p_id, items} ->
      if p_id do
        current_ids = Enum.map(items, & &1["id"])
        Center.Catalog.delete_missing_pricelist_items(p_id, current_ids)
      end
    end)

    ok_count = Enum.count(results, &match?({:ok, _}, &1))
    err_count = Enum.count(results, &match?({:error, _}, &1))

    Logger.info("PricelistItemSync: Completed — #{ok_count} synced, #{err_count} skipped/errors")

    SyncEvents.broadcast(SyncEvents.topic_pricelist_items(), %{
      synced: ok_count,
      errors: err_count
    })

    :ok
  end

  defp resolve_pricelist_id([odoo_id, _name]) when is_integer(odoo_id) do
    case Center.Catalog.Pricelist |> Ash.Query.filter(odoo_id == ^odoo_id) |> Ash.read_first() do
      {:ok, pricelist} when not is_nil(pricelist) -> pricelist.id
      _ -> nil
    end
  end

  defp resolve_pricelist_id(_), do: nil

  defp resolve_category_id([odoo_id, _name]) when is_integer(odoo_id) do
    case Center.Catalog.ProductCategory |> Ash.Query.filter(odoo_id == ^odoo_id) |> Ash.read_first() do
      {:ok, cat} when not is_nil(cat) -> cat.id
      _ -> nil
    end
  end

  defp resolve_category_id(_), do: nil

  defp resolve_product_id([odoo_id, _name]) when is_integer(odoo_id) do
    case Center.Catalog.Product |> Ash.Query.filter(odoo_id == ^odoo_id) |> Ash.read_first() do
      {:ok, prod} when not is_nil(prod) -> prod.id
      _ -> nil
    end
  end

  defp resolve_product_id(_), do: nil

  defp resolve_variant_id([odoo_id, _name]) when is_integer(odoo_id) do
    case Center.Catalog.ProductVariant |> Ash.Query.filter(odoo_id == ^odoo_id) |> Ash.read_first() do
      {:ok, var} when not is_nil(var) -> var.id
      _ -> nil
    end
  end

  defp resolve_variant_id(_), do: nil

  defp parse_datetime(false), do: nil
  defp parse_datetime(nil), do: nil

  defp parse_datetime(val) when is_binary(val) do
    case NaiveDateTime.from_iso8601(val) do
      {:ok, ndt} -> DateTime.from_naive!(ndt, "Etc/UTC")
      _ -> nil
    end
  end

  defp parse_datetime(_), do: nil
end
