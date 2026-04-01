defmodule Center.Integration.Odoo.Workers.LocationSync do
  @moduledoc """
  Oban worker that synchronizes inventory locations from Odoo `stock.location`.
  """
  use Oban.Worker, queue: :sync, max_attempts: 3

  require Logger

  alias Center.Integration.Odoo.{Client, Inventory, SyncEvents}

  @fields ["name", "complete_name", "usage", "active"]

  @impl Oban.Worker
  def perform(_job) do
    Logger.info("LocationSync: Starting location sync from Odoo")
    SyncEvents.broadcast_started(SyncEvents.topic_locations())

    case Client.search_read("stock.location", [], @fields) do
      {:ok, records} when is_list(records) ->
        Logger.info("LocationSync: Received #{length(records)} locations from Odoo")
        sync_records(records)

      {:ok, other} ->
        Logger.error("LocationSync: Unexpected response: #{inspect(other)}")
        {:error, "unexpected_response"}

      {:error, reason} ->
        Logger.error("LocationSync: Failed to fetch locations: #{inspect(reason)}")
        {:error, reason}
    end
  end

  defp sync_records(records) do
    now = DateTime.utc_now(:second)

    results =
      Enum.map(records, fn record ->
        attrs = %{
          odoo_id: record["id"],
          name: record["name"],
          complete_name: record["complete_name"] || record["name"],
          usage: record["usage"] || "internal",
          active: record["active"] || true,
          odoo_data: record,
          synced_at: now
        }

        case Inventory.upsert_location(attrs) do
          {:ok, location} -> {:ok, location}
          {:error, error} ->
            Logger.warning("LocationSync: Failed to upsert location #{record["id"]}: #{inspect(error)}")
            {:error, error}
        end
      end)

    current_ids = Enum.map(records, & &1["id"])
    Inventory.delete_missing_locations(current_ids)

    ok_count = Enum.count(results, &match?({:ok, _}, &1))
    err_count = Enum.count(results, &match?({:error, _}, &1))

    Logger.info("LocationSync: Completed — #{ok_count} synced, #{err_count} skipped/errors")
    SyncEvents.broadcast(SyncEvents.topic_locations(), %{synced: ok_count, errors: err_count})

    :ok
  end
end
