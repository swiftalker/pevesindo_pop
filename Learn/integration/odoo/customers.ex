defmodule Center.Integration.Odoo.Customers do
  @moduledoc """
  Odoo integration for `res.partner` push operations.
  """

  alias Center.MasterData.Customer
  alias Center.Integration.Odoo.Client

  @doc """
  Pushes a locally-created customer to Odoo.
  Creates `res.partner` in Odoo, stores returned `odoo_id`.
  """
  def push_to_odoo(%Customer{odoo_id: nil} = customer) do
    values = %{
      name: customer.name,
      email: customer.email || false,
      phone: customer.phone || false,
      street: customer.street || false,
      city: customer.city || false,
      is_company: customer.is_company || false,
      country_id: 100,
      customer_rank: 1
    }

    case Client.create("res.partner", values) do
      {:ok, odoo_id} when is_integer(odoo_id) ->
        customer
        |> Ash.Changeset.for_update(:update, %{odoo_id: odoo_id, synced_at: DateTime.utc_now(:second)})
        |> Ash.update()

      {:error, reason} ->
        {:error, reason}
    end
  end

  def push_to_odoo(%Customer{odoo_id: odoo_id}) when not is_nil(odoo_id) do
    {:error, :already_pushed}
  end

  @doc "Pushes an update to an existing Odoo customer."
  def push_update_to_odoo(%Customer{odoo_id: odoo_id} = customer) when is_integer(odoo_id) do
    values = %{
      name: customer.name,
      email: customer.email || false,
      phone: customer.phone || false,
      street: customer.street || false,
      city: customer.city || false,
      country_id: 100,
      is_company: customer.is_company || false
    }

    case Client.write("res.partner", [odoo_id], values) do
      {:ok, true} ->
        customer
        |> Ash.Changeset.for_update(:update, %{synced_at: DateTime.utc_now(:second)})
        |> Ash.update()

      {:error, reason} ->
        {:error, reason}

      _ ->
        {:error, "Unknown Odoo update failure"}
    end
  end

  def push_update_to_odoo(%Customer{odoo_id: nil}), do: {:error, :not_pushed_yet}
end
