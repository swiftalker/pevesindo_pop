defmodule Center.Integration.Odoo.Workers.CustomerSync do
  @moduledoc """
  Oban worker that synchronizes customers from Odoo `res.partner`.

  Fetches partners with `customer_rank > 0` and upserts them locally.
  """
  use Oban.Worker, queue: :sync, max_attempts: 3

  require Logger
  require Ash.Query

  alias Center.Integration.Odoo.{Client, SyncEvents}
  alias Center.MasterData.Customer

  @fields [
    "name",
    "email",
    "phone",
    "street",
    "city",
    "company_id",
    "is_company",
    "customer_rank",
    "property_product_pricelist",
    "active"
  ]

  @impl Oban.Worker
  def perform(_job) do
    Logger.info("CustomerSync: Starting customer sync from Odoo")
    SyncEvents.broadcast_started(SyncEvents.topic_customers())

    domain = [["customer_rank", ">", 0]]

    case Client.search_read("res.partner", domain, @fields) do
      {:ok, records} when is_list(records) ->
        Logger.info("CustomerSync: Received #{length(records)} customers from Odoo")
        sync_records(records)

      {:ok, other} ->
        Logger.error("CustomerSync: Unexpected response: #{inspect(other)}")
        {:error, "unexpected_response"}

      {:error, reason} ->
        Logger.error("CustomerSync: Failed to fetch customers: #{inspect(reason)}")
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
          email: to_string_or_nil(record["email"]),
          phone: to_string_or_nil(record["phone"]),
          street: to_string_or_nil(record["street"]),
          city: to_string_or_nil(record["city"]),
          company_id: company_id,
          pricelist_id: resolve_pricelist_id(record["property_product_pricelist"]),
          is_company: record["is_company"] || false,
          customer_rank: record["customer_rank"] || 0,
          active: record["active"] || true,
          source: "odoo",
          odoo_data: record,
          synced_at: now
        }

        case Customer |> Ash.Changeset.for_create(:upsert_from_odoo, attrs) |> Ash.create() do
          {:ok, customer} ->
            {:ok, customer}

          {:error, error} ->
            Logger.warning("CustomerSync: Failed to upsert customer #{record["id"]}: #{inspect(error)}")

            {:error, error}
        end
      end)

    ok_count = Enum.count(results, &match?({:ok, _}, &1))
    err_count = Enum.count(results, &match?({:error, _}, &1))
    Logger.info("CustomerSync: Completed — #{ok_count} synced, #{err_count} errors")
    SyncEvents.broadcast(SyncEvents.topic_customers(), %{synced: ok_count, errors: err_count})

    :ok
  end

  defp resolve_company_id([odoo_id, _name]) when is_integer(odoo_id) do
    case Center.MasterData.Company |> Ash.Query.filter(odoo_id == ^odoo_id) |> Ash.read_first() do
      {:ok, company} when not is_nil(company) -> company.id
      _ -> nil
    end
  end

  defp resolve_company_id(_), do: nil

  defp to_string_or_nil(false), do: nil
  defp to_string_or_nil(nil), do: nil
  defp to_string_or_nil(val) when is_binary(val), do: val
  defp to_string_or_nil(_), do: nil

  defp resolve_pricelist_id([odoo_id, _name]) when is_integer(odoo_id) do
    case Center.Catalog.Pricelist |> Ash.Query.filter(odoo_id == ^odoo_id) |> Ash.read_first() do
      {:ok, pricelist} when not is_nil(pricelist) -> pricelist.id
      _ -> nil
    end
  end

  defp resolve_pricelist_id(_), do: nil
end
