defmodule Center.Integration.Odoo.Workers.PricelistSync do
  @moduledoc """
  Oban worker that synchronizes pricelists from Odoo `product.pricelist`.
  """
  use Oban.Worker, queue: :sync, max_attempts: 3

  require Logger
  require Ash.Query

  alias Center.Integration.Odoo.{Client, SyncEvents}
  alias Center.Catalog.Pricelist

  @fields [
    "name",
    "active",
    "currency_id",
    "company_id"
  ]

  @impl Oban.Worker
  def perform(_job) do
    Logger.info("PricelistSync: Starting pricelist sync from Odoo")
    SyncEvents.broadcast_started(SyncEvents.topic_pricelists())

    case Client.search_read("product.pricelist", [], @fields) do
      {:ok, records} when is_list(records) ->
        Logger.info("PricelistSync: Received #{length(records)} pricelists from Odoo")
        sync_records(records)

      {:ok, other} ->
        Logger.error("PricelistSync: Unexpected response: #{inspect(other)}")
        {:error, "unexpected_response"}

      {:error, reason} ->
        Logger.error("PricelistSync: Failed to fetch pricelists: #{inspect(reason)}")
        {:error, reason}
    end
  end

  defp sync_records(records) do
    now = DateTime.utc_now(:second)

    results =
      Enum.map(records, fn record ->
        company_id = resolve_company_id(record["company_id"])

        attrs = %{
          odoo_id: record["id"],
          name: record["name"],
          active: record["active"] || true,
          currency_id: extract_id(record["currency_id"]),
          company_id: company_id,
          odoo_data: record,
          synced_at: now
        }

        case Pricelist |> Ash.Changeset.for_create(:upsert_from_odoo, attrs) |> Ash.create() do
          {:ok, pricelist} ->
            {:ok, pricelist}

          {:error, error} ->
            Logger.warning("PricelistSync: Failed to upsert pricelist #{record["id"]}: #{inspect(error)}")

            {:error, error}
        end
      end)

    ok_count = Enum.count(results, &match?({:ok, _}, &1))
    err_count = Enum.count(results, &match?({:error, _}, &1))

    Logger.info("PricelistSync: Completed — #{ok_count} synced, #{err_count} skipped/errors")
    SyncEvents.broadcast(SyncEvents.topic_pricelists(), %{synced: ok_count, errors: err_count})

    :ok
  end

  defp resolve_company_id([odoo_id, _name]) when is_integer(odoo_id) do
    case Center.MasterData.Company |> Ash.Query.filter(odoo_id == ^odoo_id) |> Ash.read_first() do
      {:ok, company} when not is_nil(company) -> company.id
      _ -> nil
    end
  end

  defp resolve_company_id(_), do: nil

  defp extract_id([odoo_id, _name]) when is_integer(odoo_id), do: odoo_id
  defp extract_id(_), do: nil
end
