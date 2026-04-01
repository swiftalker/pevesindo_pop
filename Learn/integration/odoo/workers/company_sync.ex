defmodule Center.Integration.Odoo.Workers.CompanySync do
  @moduledoc """
  Oban worker that synchronizes companies from Odoo `res.company`.

  Fetches all companies via JSON-2 API and upserts them into the local database.
  Maintains parent-child hierarchy by resolving `parent_id` Odoo references.
  Links each company to its corresponding `res.partner` record via `partner_id`.
  """
  use Oban.Worker, queue: :sync, max_attempts: 3

  require Logger
  require Ash.Query

  alias Center.Integration.Odoo.{Client, SyncEvents}
  alias Center.MasterData.{Company, Customer}

  @fields ["name", "parent_id", "currency_id", "partner_id", "active"]

  @impl Oban.Worker
  def perform(_job) do
    Logger.info("CompanySync: Starting company sync from Odoo")
    SyncEvents.broadcast_started(SyncEvents.topic_companies())

    case Client.search_read("res.company", [], @fields) do
      {:ok, records} when is_list(records) ->
        Logger.info("CompanySync: Received #{length(records)} companies from Odoo")
        sync_records(records)

      {:ok, other} ->
        Logger.error("CompanySync: Unexpected response: #{inspect(other)}")
        {:error, "unexpected_response"}

      {:error, reason} ->
        Logger.error("CompanySync: Failed to fetch companies: #{inspect(reason)}")
        {:error, reason}
    end
  end

  defp sync_records(records) do
    now = DateTime.utc_now(:second)

    # First pass: upsert all companies (without parent or partner yet)
    results =
      Enum.map(records, fn record ->
        attrs = %{
          odoo_id: record["id"],
          name: record["name"],
          currency: extract_currency(record["currency_id"]),
          active: record["active"] || true,
          odoo_data: record,
          synced_at: now
        }

        case Company |> Ash.Changeset.for_create(:upsert_from_odoo, attrs) |> Ash.create() do
          {:ok, company} ->
            {:ok, company}

          {:error, error} ->
            Logger.warning("CompanySync: Failed to upsert company #{record["id"]}: #{inspect(error)}")

            {:error, error}
        end
      end)

    # Second pass: resolve parent_id references
    Enum.each(records, fn record ->
      case extract_parent_odoo_id(record["parent_id"]) do
        nil ->
          :ok

        parent_odoo_id ->
          with {:ok, company} when not is_nil(company) <- Company |> Ash.Query.filter(odoo_id == ^record["id"]) |> Ash.read_first(),
               {:ok, parent} when not is_nil(parent) <- Company |> Ash.Query.filter(odoo_id == ^parent_odoo_id) |> Ash.read_first() do
            company
            |> Ash.Changeset.for_update(:update, %{parent_id: parent.id})
            |> Ash.update()
          end
      end
    end)

    # Third pass: resolve partner_id references
    Enum.each(records, fn record ->
      case extract_partner_odoo_id(record["partner_id"]) do
        nil ->
          :ok

        partner_odoo_id ->
          case Company |> Ash.Query.filter(odoo_id == ^record["id"]) |> Ash.read_first() do
            {:ok, company} when not is_nil(company) ->
              resolve_and_link_partner(company, partner_odoo_id, record)
            _ ->
              :ok
          end
      end
    end)

    ok_count = Enum.count(results, &match?({:ok, _}, &1))
    err_count = Enum.count(results, &match?({:error, _}, &1))
    Logger.info("CompanySync: Completed — #{ok_count} synced, #{err_count} errors")
    SyncEvents.broadcast(SyncEvents.topic_companies(), %{synced: ok_count, errors: err_count})

    :ok
  end

  defp resolve_and_link_partner(company, partner_odoo_id, record) do
    case Customer |> Ash.Query.filter(odoo_id == ^partner_odoo_id) |> Ash.read_first() do
      {:ok, partner} when not is_nil(partner) ->
        Logger.debug("CompanySync: Linking company #{company.name} to existing partner ##{partner.id}")

        company
        |> Ash.Changeset.for_update(:update, %{partner_id: partner.id})
        |> Ash.update()

      _ ->
        Logger.info("CompanySync: Creating partner for company #{company.name} (Odoo partner_id: #{partner_odoo_id})")

        partner_attrs = %{
          odoo_id: partner_odoo_id,
          name: record["name"],
          is_company: true,
          company_id: company.id,
          active: true,
          source: "odoo",
          customer_rank: 0,
          odoo_data: %{"_created_by" => "company_sync", "company_odoo_id" => record["id"]},
          synced_at: DateTime.utc_now(:second)
        }

        case Customer |> Ash.Changeset.for_create(:upsert_from_odoo, partner_attrs) |> Ash.create() do
          {:ok, partner} ->
            company
            |> Ash.Changeset.for_update(:update, %{partner_id: partner.id})
            |> Ash.update()

          {:error, error} ->
            Logger.warning("CompanySync: Failed to create partner for company #{company.name}: #{inspect(error)}")
        end
    end
  end

  defp extract_currency([_id, name]) when is_binary(name), do: name
  defp extract_currency(_), do: nil

  defp extract_parent_odoo_id([id, _name]) when is_integer(id), do: id
  defp extract_parent_odoo_id(_), do: nil

  defp extract_partner_odoo_id([id, _name]) when is_integer(id), do: id
  defp extract_partner_odoo_id(_), do: nil
end
