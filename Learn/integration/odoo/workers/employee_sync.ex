defmodule Center.Integration.Odoo.Workers.EmployeeSync do
  @moduledoc """
  Oban worker that synchronizes employees from Odoo `hr.employee`.

  Fetches all employees via JSON-2 API and upserts them into the local database.
  Resolves company references using existing synced companies.
  """
  use Oban.Worker, queue: :sync, max_attempts: 3

  require Logger
  require Ash.Query

  alias Center.Integration.Odoo.{Client, SyncEvents}
  alias Center.MasterData.Employee

  @fields [
    "name",
    "job_title",
    "department_id",
    "work_email",
    "work_phone",
    "company_id",
    "user_id",
    "active"
  ]

  @impl Oban.Worker
  def perform(_job) do
    Logger.info("EmployeeSync: Starting employee sync from Odoo")
    SyncEvents.broadcast_started(SyncEvents.topic_employees())

    case Client.search_read("hr.employee", [], @fields) do
      {:ok, records} when is_list(records) ->
        Logger.info("EmployeeSync: Received #{length(records)} employees from Odoo")
        sync_records(records)

      {:ok, other} ->
        Logger.error("EmployeeSync: Unexpected response: #{inspect(other)}")
        {:error, "unexpected_response"}

      {:error, reason} ->
        Logger.error("EmployeeSync: Failed to fetch employees: #{inspect(reason)}")
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
          job_title: to_string_or_nil(record["job_title"]),
          department: extract_name(record["department_id"]),
          work_email: to_string_or_nil(record["work_email"]),
          work_phone: to_string_or_nil(record["work_phone"]),
          company_id: company_id,
          active: record["active"] || true,
          odoo_data: record,
          synced_at: now
        }

        case Employee |> Ash.Changeset.for_create(:upsert_from_odoo, attrs) |> Ash.create() do
          {:ok, employee} ->
            {:ok, employee}

          {:error, error} ->
            Logger.warning("EmployeeSync: Failed to upsert employee #{record["id"]}: #{inspect(error)}")

            {:error, error}
        end
      end)

    ok_count = Enum.count(results, &match?({:ok, _}, &1))
    err_count = Enum.count(results, &match?({:error, _}, &1))
    Logger.info("EmployeeSync: Completed — #{ok_count} synced, #{err_count} errors")
    SyncEvents.broadcast(SyncEvents.topic_employees(), %{synced: ok_count, errors: err_count})

    :ok
  end

  defp resolve_company_id([odoo_id, _name]) when is_integer(odoo_id) do
    case Center.MasterData.Company |> Ash.Query.filter(odoo_id == ^odoo_id) |> Ash.read_first() do
      {:ok, company} when not is_nil(company) -> company.id
      _ -> nil
    end
  end

  defp resolve_company_id(_), do: nil

  defp extract_name([_id, name]) when is_binary(name), do: name
  defp extract_name(_), do: nil

  defp to_string_or_nil(false), do: nil
  defp to_string_or_nil(nil), do: nil
  defp to_string_or_nil(val) when is_binary(val), do: val
  defp to_string_or_nil(_), do: nil
end
