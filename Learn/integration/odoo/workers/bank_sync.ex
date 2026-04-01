defmodule Center.Integration.Odoo.Workers.BankSync do
  @moduledoc """
  Syncs `res.bank` master data from Odoo → local DB via Ash upsert.
  """
  use Oban.Worker, queue: :sync, max_attempts: 3

  require Logger

  alias Center.Integration.Odoo.{Client, SyncEvents}
  alias Center.MasterData.Bank

  @topic SyncEvents.topic_banks()
  @fields ~w[id name bic active]

  @impl Oban.Worker
  def perform(%Oban.Job{}) do
    SyncEvents.broadcast_started(@topic)

    case Client.search_read("res.bank", [], @fields) do
      {:ok, items} ->
        synced = upsert_all(items)
        SyncEvents.broadcast(@topic, %{synced: synced})
        :ok

      {:error, reason} ->
        Center.Integration.Odoo.SyncTracker.mark_completed(@topic)
        {:error, reason}
    end
  end

  defp upsert_all(items) do
    now = DateTime.utc_now(:second)

    Enum.count(items, fn item ->
      case Bank.upsert_from_odoo(%{
             odoo_id: item["id"],
             name: item["name"],
             bic: item["bic"],
             active: item["active"] || false,
             odoo_data: item,
             synced_at: now
           }) do
        {:ok, _} -> true
        {:error, err} ->
          Logger.warning("BankSync: Failed to upsert bank #{item["id"]}: #{inspect(err)}")
          false
      end
    end)
  end
end
