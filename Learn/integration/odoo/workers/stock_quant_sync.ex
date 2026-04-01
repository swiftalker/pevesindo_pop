defmodule Center.Integration.Odoo.Workers.StockQuantSync do
  @moduledoc """
  Oban worker that synchronizes stock on hand from Odoo `stock.quant`.
  """
  use Oban.Worker, queue: :sync, max_attempts: 3

  require Logger
  require Ash.Query

  alias Center.Integration.Odoo.{Client, Inventory, SyncEvents}
  alias Center.Inventory.Location
  alias Center.Catalog.ProductVariant

  @fields ["product_id", "location_id", "quantity", "reserved_quantity"]

  @impl Oban.Worker
  def perform(_job) do
    Logger.info("StockQuantSync: Starting stock quants sync from Odoo")
    SyncEvents.broadcast_started(SyncEvents.topic_stock_quants())

    domain = [["quantity", ">", 0]]

    case Client.search_read("stock.quant", domain, @fields) do
      {:ok, records} when is_list(records) ->
        Logger.info("StockQuantSync: Received #{length(records)} quants from Odoo")
        sync_records(records)

      {:ok, other} ->
        Logger.error("StockQuantSync: Unexpected response: #{inspect(other)}")
        {:error, "unexpected_response"}

      {:error, reason} ->
        Logger.error("StockQuantSync: Failed to fetch stock quants: #{inspect(reason)}")
        {:error, reason}
    end
  end

  defp sync_records(records) do
    now = DateTime.utc_now(:second)

    results =
      Enum.map(records, fn record ->
        location_id = resolve_location_id(record["location_id"])
        variant_id = resolve_variant_id(record["product_id"])

        if is_nil(location_id) or is_nil(variant_id) do
          {:error, :missing_relation}
        else
          attrs = %{
            odoo_id: record["id"],
            quantity: record["quantity"] || 0.0,
            reserved_quantity: record["reserved_quantity"] || 0.0,
            location_id: location_id,
            product_variant_id: variant_id,
            odoo_data: record,
            synced_at: now
          }

          case Inventory.upsert_stock_quant(attrs) do
            {:ok, quant} -> {:ok, quant}
            {:error, error} ->
              Logger.warning("StockQuantSync: Failed to upsert quant #{record["id"]}: #{inspect(error)}")
              {:error, error}
          end
        end
      end)

    ok_count = Enum.count(results, &match?({:ok, _}, &1))
    err_count = Enum.count(results, &match?({:error, _}, &1))

    Logger.info("StockQuantSync: Completed — #{ok_count} synced, #{err_count} skipped/errors")
    SyncEvents.broadcast(SyncEvents.topic_stock_quants(), %{synced: ok_count, errors: err_count})

    :ok
  end

  defp resolve_location_id([odoo_id, _name]) when is_integer(odoo_id) do
    case Location |> Ash.Query.filter(odoo_id == ^odoo_id) |> Ash.read_first() do
      {:ok, loc} when not is_nil(loc) -> loc.id
      _ -> nil
    end
  end
  defp resolve_location_id(_), do: nil

  defp resolve_variant_id([odoo_id, _name]) when is_integer(odoo_id) do
    case ProductVariant |> Ash.Query.filter(odoo_id == ^odoo_id) |> Ash.read_first() do
      {:ok, var} when not is_nil(var) -> var.id
      _ -> nil
    end
  end
  defp resolve_variant_id(_), do: nil
end
