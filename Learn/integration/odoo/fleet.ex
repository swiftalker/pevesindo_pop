defmodule Center.Integration.Odoo.Fleet do
  @moduledoc """
  Odoo integration for `fleet.vehicle` sync.

  Uses `MasterData.Vehicle` Ash resource directly.
  """

  require Logger

  alias Center.Integration.Odoo.Client
  alias Center.MasterData.Vehicle

  @doc "Syncs all vehicles from Odoo fleet."
  def sync_vehicles do
    fields = ["license_plate", "model_id", "driver_id"]

    case Client.search_read("fleet.vehicle", [], fields) do
      {:ok, vehicles_data} ->
        now = DateTime.utc_now(:second)

        results =
          Enum.map(vehicles_data, fn data ->
            attrs = %{
              odoo_id: data["id"],
              license_plate: data["license_plate"],
              model_name: extract_name(data["model_id"]),
              driver_id: extract_id(data["driver_id"]),
              odoo_data: data,
              synced_at: now
            }

            Vehicle.upsert_from_odoo(attrs)
          end)

        {:ok, results}

      {:error, reason} ->
        {:error, reason}
    end
  end

  defp extract_name([_, name]) when is_binary(name), do: name
  defp extract_name(name) when is_binary(name), do: name
  defp extract_name(_), do: "Unknown Model"

  defp extract_id([id, _]) when is_integer(id), do: id
  defp extract_id(id) when is_integer(id), do: id
  defp extract_id(_), do: nil
end
