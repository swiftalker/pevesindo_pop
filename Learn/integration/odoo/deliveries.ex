defmodule Center.Integration.Odoo.Deliveries do
  @moduledoc """
  Odoo integration adapter for Deliveries/Pickings (`stock.picking`).

  Handles delivery order operations with Odoo:
  - Pull delivery orders created by sale order confirmation
  - Push delivery validation (goods shipped)
  - Track stock moves and locations

  ## Odoo Delivery Flow (triggered by Sale Order confirmation)

  ```
  Sale Order Confirmed
       │
       ├── stock.picking (OUT) created automatically
       │     ├── stock.move (per product line)
       │     └── stock.move.line (per lot/serial/location)
       │
       └── Admin Gudang validates picking → goods shipped
  ```

  ## Future Implementation

  - `pull_for_order/1` — fetch all pickings linked to a sale order
  - `push_validate/1` — validate a picking (mark as done/shipped)
  - `push_assign_location/2` — assign specific stock locations to moves
  """

  @behaviour Center.Integration.Odoo.Integration

  require Logger

  alias Center.Integration.Odoo.Client

  @model "stock.picking"

  @read_fields [
    "name",
    "state",
    "partner_id",
    "origin",
    "scheduled_date",
    "date_done",
    "location_id",
    "location_dest_id",
    "move_ids",
    "move_line_ids"
  ]

  @impl true
  def push_draft(_record) do
    {:error, :not_implemented}
  end

  @impl true
  def push_confirm(_record) do
    {:error, :not_implemented}
  end

  @impl true
  def push_cancel(_record) do
    {:error, :not_implemented}
  end

  @doc """
  Validates a picking in Odoo (marks it as done/shipped).
  This usually requires calling a button action in Odoo via XML-RPC.
  """
  def push_validate(odoo_picking_id) when is_integer(odoo_picking_id) do
    # In Odoo, validating a picking is usually done by calling `button_validate`
    case Client.execute_kw(@model, "button_validate", [[odoo_picking_id]]) do
      {:ok, result} ->
        Logger.info("Odoo picking #{odoo_picking_id} validated successfully.")
        {:ok, result}

      {:error, reason} ->
        Logger.error("Failed to validate Odoo picking #{odoo_picking_id}: #{inspect(reason)}")
        {:error, reason}
    end
  end

  @impl true
  def pull(odoo_id) when is_integer(odoo_id) do
    case Client.read(@model, [odoo_id], @read_fields) do
      {:ok, [record | _]} -> {:ok, record}
      {:ok, []} -> {:error, :not_found}
      {:error, reason} -> {:error, reason}
    end
  end

  @doc """
  Fetches all delivery orders linked to a sale order's Odoo name.
  """
  def pull_for_order(odoo_order_name) when is_binary(odoo_order_name) do
    domain = [["origin", "=", odoo_order_name]]

    case Client.search_read(@model, domain, @read_fields) do
      {:ok, records} when is_list(records) -> {:ok, records}
      {:error, reason} -> {:error, reason}
    end
  end
end
