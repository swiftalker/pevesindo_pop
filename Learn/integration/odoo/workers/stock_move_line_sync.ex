defmodule Center.Integration.Odoo.Workers.StockMoveLineSync do
  @moduledoc """
  Oban worker that synchronizes stock movements from Odoo `stock.move.line`.
  """
  use Oban.Worker, queue: :sync, max_attempts: 3

  require Logger
  require Ash.Query

  alias Center.Integration.Odoo.{Client, Inventory, SyncEvents}
  alias Center.Inventory.Location
  alias Center.Catalog.ProductVariant

  @fields ["product_id", "location_id", "location_dest_id", "qty_done", "state", "date", "reference"]

  @impl Oban.Worker
  def perform(_job) do
    Logger.info("StockMoveLineSync: Starting stock move lines sync from Odoo")
    SyncEvents.broadcast_started(SyncEvents.topic_stock_move_lines())

    domain = [["state", "=", "done"]]

    case Client.search_read("stock.move.line", domain, @fields) do
      {:ok, records} when is_list(records) ->
        Logger.info("StockMoveLineSync: Received #{length(records)} move lines from Odoo")
        sync_records(records)

      {:ok, other} ->
        Logger.error("StockMoveLineSync: Unexpected response: #{inspect(other)}")
        {:error, "unexpected_response"}

      {:error, reason} ->
        Logger.error("StockMoveLineSync: Failed to fetch stock move lines: #{inspect(reason)}")
        {:error, reason}
    end
  end

  defp sync_records(records) do
    now = DateTime.utc_now(:second)

    results =
      Enum.map(records, fn record ->
        location_id = resolve_location_id(record["location_id"])
        location_dest_id = resolve_location_id(record["location_dest_id"])
        variant_id = resolve_variant_id(record["product_id"])

        if is_nil(location_id) or is_nil(location_dest_id) or is_nil(variant_id) do
          {:error, :missing_relation}
        else
          attrs = %{
            odoo_id: record["id"],
            qty_done: record["qty_done"] || 0.0,
            state: record["state"],
            date: parse_datetime(record["date"]),
            reference: record["reference"],
            location_id: location_id,
            location_dest_id: location_dest_id,
            product_variant_id: variant_id,
            odoo_data: record,
            synced_at: now
          }

          case Inventory.upsert_stock_move_line(attrs) do
            {:ok, line} -> {:ok, line}
            {:error, error} ->
              Logger.warning("StockMoveLineSync: Failed to upsert move line #{record["id"]}: #{inspect(error)}")
              {:error, error}
          end
        end
      end)

    ok_count = Enum.count(results, &match?({:ok, _}, &1))
    err_count = Enum.count(results, &match?({:error, _}, &1))

    Logger.info("StockMoveLineSync: Completed — #{ok_count} synced, #{err_count} skipped/errors")
    SyncEvents.broadcast(SyncEvents.topic_stock_move_lines(), %{synced: ok_count, errors: err_count})

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
