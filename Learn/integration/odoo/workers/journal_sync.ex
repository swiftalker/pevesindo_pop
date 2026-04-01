defmodule Center.Integration.Odoo.Workers.JournalSync do
  @moduledoc """
  Oban worker that synchronizes accounting journals from Odoo `account.journal`.

  Fetches all active journals and upserts them into the local database,
  resolving company references and bank account links.

  ## Odoo Journal Types Mapping

  Odoo uses these journal type values:
  - `"sale"`     → Sale journal (e.g. "Penjualan Godean")
  - `"purchase"` → Purchase journal (e.g. "Pembelian Godean")
  - `"bank"`     → Bank journal (e.g. "Bank OCBC Indonesia (Godean)")
  - `"cash"`     → Cash journal (e.g. "Kas Pevesindo Godean")
  - `"general"`  → Miscellaneous journal (e.g. "Operasi Lain-lain")

  Can be triggered manually or scheduled via Oban cron.
  """
  use Oban.Worker, queue: :sync, max_attempts: 3

  require Logger
  require Ash.Query

  alias Center.Integration.Odoo.{Client, SyncEvents}
  alias Center.MasterData.{Company, Journal, BankAccount}

  @fields [
    "name",
    "code",
    "type",
    "company_id",
    "default_account_id",
    "bank_account_id",
    "active"
  ]

  @impl Oban.Worker
  def perform(_job) do
    Logger.info("JournalSync: Starting journal sync from Odoo")
    SyncEvents.broadcast_started(SyncEvents.topic_journals())

    case Client.search_read("account.journal", [], @fields) do
      {:ok, records} when is_list(records) ->
        Logger.info("JournalSync: Received #{length(records)} journals from Odoo")
        sync_records(records)

      {:ok, other} ->
        Logger.error("JournalSync: Unexpected response: #{inspect(other)}")
        {:error, "unexpected_response"}

      {:error, reason} ->
        Logger.error("JournalSync: Failed to fetch journals: #{inspect(reason)}")
        {:error, reason}
    end
  end

  defp sync_records(records) do
    now = DateTime.utc_now(:second)

    results =
      Enum.map(records, fn record ->
        company_id = resolve_company_id(record["company_id"])

        if is_nil(company_id) do
          Logger.warning("JournalSync: Skipping journal #{record["id"]} (#{record["name"]}) — no matching company")

          {:error, :no_company}
        else
          {account_code, account_name} = extract_account(record["default_account_id"])
          bank_account_id = resolve_bank_account_id(record["bank_account_id"])

          attrs = %{
            odoo_id: record["id"],
            name: record["name"],
            code: record["code"],
            type: normalize_type(record["type"]),
            company_id: company_id,
            default_account_code: account_code,
            default_account_name: account_name,
            sequence_prefix: nil,
            bank_account_id: bank_account_id,
            active: record["active"] || true,
            odoo_data: record,
            synced_at: now
          }

          case Journal |> Ash.Changeset.for_create(:upsert_from_odoo, attrs) |> Ash.create() do
            {:ok, journal} ->
              {:ok, journal}

            {:error, error} ->
              Logger.warning("JournalSync: Failed to upsert journal #{record["id"]} (#{record["name"]}): #{inspect(error)}")

              {:error, error}
          end
        end
      end)

    ok_count = Enum.count(results, &match?({:ok, _}, &1))
    err_count = Enum.count(results, &match?({:error, _}, &1))
    Logger.info("JournalSync: Completed — #{ok_count} synced, #{err_count} errors")
    SyncEvents.broadcast(SyncEvents.topic_journals(), %{synced: ok_count, errors: err_count})

    :ok
  end

  defp resolve_company_id([odoo_id, _name]) when is_integer(odoo_id) do
    case Company |> Ash.Query.filter(odoo_id == ^odoo_id) |> Ash.read_first() do
      {:ok, company} when not is_nil(company) -> company.id
      _ -> nil
    end
  end

  defp resolve_company_id(_), do: nil

  defp extract_account([_id, display_name]) when is_binary(display_name) do
    case String.split(display_name, " ", parts: 2) do
      [code, name] -> {code, name}
      [code] -> {code, nil}
    end
  end

  defp extract_account(_), do: {nil, nil}

  defp resolve_bank_account_id([odoo_id, _name]) when is_integer(odoo_id) do
    case BankAccount |> Ash.Query.filter(odoo_id == ^odoo_id) |> Ash.read_first() do
      {:ok, ba} when not is_nil(ba) -> ba.id
      _ -> nil
    end
  end

  defp resolve_bank_account_id(_), do: nil

  defp normalize_type("sale"), do: "sale"
  defp normalize_type("purchase"), do: "purchase"
  defp normalize_type("bank"), do: "bank"
  defp normalize_type("cash"), do: "cash"
  defp normalize_type("general"), do: "general"
  defp normalize_type(_), do: "general"
end
