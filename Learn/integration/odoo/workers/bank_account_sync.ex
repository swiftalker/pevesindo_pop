defmodule Center.Integration.Odoo.Workers.BankAccountSync do
  @moduledoc """
  Syncs `res.partner.bank` (Customer Bank Accounts) from Odoo to Center DB.
  """
  use Oban.Worker, queue: :sync, max_attempts: 3

  require Logger
  require Ash.Query

  alias Center.Integration.Odoo.Client
  alias Center.MasterData.{Bank, Customer, BankAccount}

  @topic Center.Integration.Odoo.SyncEvents.topic_bank_accounts()

  @impl Oban.Worker
  def perform(%Oban.Job{}) do
    Center.Integration.Odoo.SyncEvents.broadcast_started(@topic)

    fields = [
      "id",
      "acc_number",
      "acc_holder_name",
      "bank_id",
      "partner_id",
      "active"
    ]

    domain = []

    case Client.search_read("res.partner.bank", domain, fields) do
      {:ok, items} ->
        upsert_items(items)
        Center.Integration.Odoo.SyncEvents.broadcast(@topic, %{synced: length(items)})

        # Enqueue Journal Sync automatically so that any new bank accounts
        # correctly link up with Odoo Journals for the Journal-Driven flow.
        %{} |> Center.Integration.Odoo.Workers.JournalSync.new() |> Oban.insert()

        :ok

      {:error, reason} ->
        Center.Integration.Odoo.SyncTracker.mark_completed(@topic)
        {:error, reason}
    end
  end

  defp upsert_items(items) do
    synced_at = DateTime.utc_now(:second)

    # Pre-fetch ID mapping for banks and partners to minimize queries inside the loop
    bank_odoo_ids =
      items
      |> Enum.reject(&(&1["bank_id"] == false))
      |> Enum.map(&Enum.at(&1["bank_id"], 0))
      |> Enum.uniq()

    partner_odoo_ids =
      items
      |> Enum.reject(&(&1["partner_id"] == false))
      |> Enum.map(&Enum.at(&1["partner_id"], 0))
      |> Enum.uniq()

    banks_map =
      case Bank |> Ash.Query.filter(odoo_id in ^bank_odoo_ids) |> Ash.read() do
        {:ok, banks} -> Enum.map(banks, &{&1.odoo_id, &1.id}) |> Enum.into(%{})
        _ -> %{}
      end

    partners_map =
      case Customer |> Ash.Query.filter(odoo_id in ^partner_odoo_ids) |> Ash.read() do
        {:ok, partners} -> Enum.map(partners, &{&1.odoo_id, &1.id}) |> Enum.into(%{})
        _ -> %{}
      end

    Enum.each(items, fn item ->
      bank_local_id =
        if is_list(item["bank_id"]) do
          Map.get(banks_map, Enum.at(item["bank_id"], 0))
        else
          nil
        end

      partner_local_id =
        if is_list(item["partner_id"]) do
          Map.get(partners_map, Enum.at(item["partner_id"], 0))
        else
          nil
        end

      # Required connection to a customer
      if partner_local_id do
        attrs = %{
          odoo_id: item["id"],
          acc_number: item["acc_number"],
          acc_holder_name: item["acc_holder_name"] || nil,
          active: item["active"],
          bank_id: bank_local_id,
          partner_id: partner_local_id,
          odoo_data: item,
          synced_at: synced_at
        }

        case BankAccount |> Ash.Changeset.for_create(:upsert_from_odoo, attrs) |> Ash.create() do
          {:ok, _} -> :ok
          {:error, error} -> Logger.warning("BankAccountSync: Failed to upsert bank account #{item["id"]}: #{inspect(error)}")
        end
      end
    end)
  end
end
