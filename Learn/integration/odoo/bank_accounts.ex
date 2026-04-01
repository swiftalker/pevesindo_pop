defmodule Center.Integration.Odoo.BankAccounts do
  @moduledoc """
  Odoo integration for `res.partner.bank` sync operations.
  """

  alias Center.MasterData.Ecto.BankAccount
  alias Center.MasterData.BankAccount, as: AshBankAccount
  alias Center.Integration.Odoo.Client

  require Ash.Query

  @doc "Idempotently syncs a local bank account to Odoo."
  def sync_partner_bank(%BankAccount{} = raw_bank_account) do
    # Preload via Ecto for safety if it came from Ecto, though Ecto preload should be done upstream
    # In this case it is better to load via Ash since we need to do updates anyway
    bank_account =
      case AshBankAccount |> Ash.Query.filter(id == ^raw_bank_account.id) |> Ash.Query.load([:partner, :bank]) |> Ash.read_first() do
        {:ok, ba} when not is_nil(ba) -> ba
        _ -> nil
      end

    if is_nil(bank_account) or is_nil(bank_account.partner) or is_nil(bank_account.partner.odoo_id) do
      {:error, :missing_odoo_partner}
    else
      partner_odoo_id = bank_account.partner.odoo_id
      bank_odoo_id = (bank_account.bank && bank_account.bank.odoo_id) || false

      payload = %{
        partner_id: partner_odoo_id,
        acc_number: bank_account.acc_number,
        acc_holder_name: bank_account.acc_holder_name || false,
        bank_id: bank_odoo_id,
        active: bank_account.active
      }

      cond do
        not is_nil(bank_account.odoo_partner_bank_id) ->
          update_in_odoo(bank_account, payload)

        not is_nil(bank_account.odoo_id) ->
          bank_account
          |> Ash.Changeset.for_update(:update, %{odoo_partner_bank_id: bank_account.odoo_id})
          |> Ash.update!()
          |> update_in_odoo(payload)

        true ->
          case find_existing_in_odoo(partner_odoo_id, bank_account.acc_number) do
            {:ok, existing_id} ->
              bank_account
              |> Ash.Changeset.for_update(:update, %{odoo_partner_bank_id: existing_id, odoo_id: existing_id})
              |> Ash.update!()
              |> update_in_odoo(payload)

            {:error, :not_found} ->
              create_in_odoo(bank_account, payload)

            {:error, reason} ->
              {:error, reason}
          end
      end
    end
  end

  @doc "Archives the bank account in Odoo (active = false)."
  def archive_partner_bank(%BankAccount{} = bank_account) do
    case AshBankAccount |> Ash.get(bank_account.id) do
      {:ok, ash_bank_account} ->
        ash_bank_account
        |> Ash.Changeset.for_update(:update, %{active: false})
        |> Ash.update!()
        |> sync_partner_bank()
      _ ->
        {:error, :not_found}
    end
  end

  defp update_in_odoo(bank_account, payload) do
    case Client.write("res.partner.bank", [bank_account.odoo_partner_bank_id], payload) do
      {:ok, true} ->
        bank_account
        |> Ash.Changeset.for_update(:update, %{synced_at: DateTime.utc_now(:second)})
        |> Ash.update()

      {:ok, false} ->
        {:error, :odoo_update_failed}

      {:error, reason} ->
        {:error, reason}

      _ ->
        {:error, :unknown_error}
    end
  end

  defp create_in_odoo(bank_account, payload) do
    case Client.create("res.partner.bank", payload) do
      {:ok, new_id} when is_integer(new_id) ->
        bank_account
        |> Ash.Changeset.for_update(:update, %{
          odoo_partner_bank_id: new_id,
          odoo_id: new_id,
          synced_at: DateTime.utc_now(:second)
        })
        |> Ash.update()

      {:error, reason} ->
        {:error, reason}
    end
  end

  defp find_existing_in_odoo(partner_odoo_id, acc_number) do
    domain = [
      ["partner_id", "=", partner_odoo_id],
      ["acc_number", "=", acc_number]
    ]

    case Client.search_read("res.partner.bank", [domain], %{fields: ["id"], limit: 1, context: %{active_test: false}}) do
      {:ok, [%{"id" => id}]} -> {:ok, id}
      {:ok, []} -> {:error, :not_found}
      {:error, reason} -> {:error, reason}
    end
  end
end
