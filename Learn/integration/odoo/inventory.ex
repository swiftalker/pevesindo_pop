defmodule Center.Integration.Odoo.Inventory do
  @moduledoc """
  Context module for managing Odoo Inventory (Locations, Quants, and Move Lines).
  Uses Ash resources for domain models.
  """

  require Ash.Query
  alias Center.Integration.Odoo.{Client}
  alias Center.Inventory.{Warehouse, Location, StockQuant, StockMoveLine}

  # --- Warehouses ---

  @doc "Syncs all warehouses from Odoo."
  def sync_warehouses do
    fields = ["name", "code", "company_id"]

    case Client.search_read("stock.warehouse", [], fields) do
      {:ok, data} ->
        Enum.map(data, &upsert_warehouse/1)

      {:error, reason} ->
        {:error, reason}
    end
  end

  @doc "Gets a warehouse by Odoo ID."
  def get_warehouse_by_odoo_id(odoo_id) do
    case Warehouse |> Ash.Query.filter(odoo_id == ^odoo_id) |> Ash.read_first() do
      {:ok, result} -> result
      _ -> nil
    end
  end

  @doc "Upserts a warehouse from Odoo data."
  def upsert_warehouse(data) do
    company_id =
      case data["company_id"] do
        [id, _] -> id
        id when is_integer(id) -> id
        _ -> nil
      end

    attrs = %{
      odoo_id: data["id"],
      name: data["name"],
      code: data["code"],
      company_id: company_id,
      odoo_data: data,
      synced_at: DateTime.utc_now(:second)
    }

    Warehouse
    |> Ash.Changeset.for_create(:upsert_from_odoo, attrs)
    |> Ash.create()
  end

  # --- Locations ---

  @doc "Gets a location by Odoo ID."
  def get_location_by_odoo_id(odoo_id) do
    case Location |> Ash.Query.filter(odoo_id == ^odoo_id) |> Ash.read_first() do
      {:ok, result} -> result
      _ -> nil
    end
  end

  @doc "Upserts a location from Odoo data."
  def upsert_location(attrs) do
    warehouse_id_val =
      case attrs[:warehouse_id] || attrs["warehouse_id"] do
        [id, _] -> id
        id when is_integer(id) -> id
        _ -> nil
      end

    # Resolve local warehouse UUID if odoo_id was passed
    local_warehouse_id =
      if warehouse_id_val do
        case Warehouse |> Ash.Query.filter(odoo_id == ^warehouse_id_val) |> Ash.read_first() do
          {:ok, w} when not is_nil(w) -> w.id
          _ -> nil
        end
      else
        nil
      end

    actual_attrs =
      attrs
      |> Map.put(:warehouse_id, local_warehouse_id)

    Location
    |> Ash.Changeset.for_create(:upsert_from_odoo, actual_attrs)
    |> Ash.create()
  end

  # --- Stock Quants (Current Stock per Location) ---

  @doc "Gets a stock quant by Odoo ID."
  def get_quant_by_odoo_id(odoo_id) do
    case StockQuant |> Ash.Query.filter(odoo_id == ^odoo_id) |> Ash.read_first() do
      {:ok, result} -> result
      _ -> nil
    end
  end

  @doc "Upserts a stock quant from Odoo data."
  def upsert_stock_quant(attrs) do
    StockQuant
    |> Ash.Changeset.for_create(:upsert_from_odoo, attrs)
    |> Ash.create()
  end

  @doc """
  Gets total stock on hand for a product variant grouped by location.
  Only includes locations where usage is 'internal'.
  Returns a list of maps: `%{location: %Location{}, quantity: float}`
  """
  def get_stock_by_variant(variant_id) do
    StockQuant
    |> Ash.Query.filter(product_variant_id == ^variant_id and location.usage == "internal" and quantity > 0.0)
    |> Ash.Query.load([:location])
    |> Ash.read!()
    |> Enum.map(fn quant ->
      %{
        location: quant.location,
        quantity: Decimal.to_float(quant.quantity)
      }
    end)
  end

  @doc "Gets the total accumulated stock for a product variant across all internal locations."
  def get_total_stock_for_variant(variant_id) do
    StockQuant
    |> Ash.Query.filter(product_variant_id == ^variant_id and location.usage == "internal")
    |> Ash.read!()
    |> Enum.reduce(0.0, fn quant, acc ->
      acc + Decimal.to_float(quant.quantity)
    end)
  end

  # --- Stock Move Lines (Movements) ---

  @doc "Gets a stock move line by Odoo ID."
  def get_move_line_by_odoo_id(odoo_id) do
    case StockMoveLine |> Ash.Query.filter(odoo_id == ^odoo_id) |> Ash.read_first() do
      {:ok, result} -> result
      _ -> nil
    end
  end

  @doc "Upserts a stock move line from Odoo data."
  def upsert_stock_move_line(attrs) do
    StockMoveLine
    |> Ash.Changeset.for_create(:upsert_from_odoo, attrs)
    |> Ash.create()
  end

  @doc """
  Returns all completed stock move lines for a variant, enriched with direction info.
  """
  def get_stock_moves_by_variant(variant_id) do
    StockMoveLine
    |> Ash.Query.filter(product_variant_id == ^variant_id and state == "done")
    |> Ash.Query.sort(date: :desc)
    |> Ash.Query.load([:location, :location_dest])
    |> Ash.read!()
    |> Enum.map(&enrich_move/1)
  end

  @doc """
  Returns a paginated list of stock moves for a variant.
  """
  def get_stock_moves_paginated(variant_id, params \\ %{}) do
    # Note: We are using Ash pagination here. If params is Flop-based, 
    # we might need to map them or use Ash natively.
    # For now, let's just return a basic Ash-paginated result that looks like what the caller expects.
    
    query = 
      StockMoveLine
      |> Ash.Query.filter(product_variant_id == ^variant_id and state == "done")
      |> Ash.Query.sort(date: :desc)
      |> Ash.Query.load([:location, :location_dest])

    case Ash.read(query, page: params) do
      {:ok, page} ->
        enriched_results = Enum.map(page.results, &enrich_move/1)
        # Mimic Flop return structure if needed, but let's try returning the page
        {:ok, {enriched_results, page}}
      
      {:error, error} -> {:error, error}
    end
  end

  defp enrich_move(move) do
    direction = determine_move_direction(move.location, move.location_dest)

    %{
      move: move,
      # :in (diterima), :out (dikirim), :internal (transfer)
      direction: direction,
      source: move.location.complete_name || move.location.name,
      destination: move.location_dest.complete_name || move.location_dest.name
    }
  end

  # Determines direction based on Odoo usage rules
  defp determine_move_direction(%Location{usage: src_usage}, %Location{usage: dest_usage}) do
    cond do
      src_usage != "internal" and dest_usage == "internal" -> :in
      src_usage == "internal" and dest_usage != "internal" -> :out
      true -> :internal
    end
  end

  defp determine_move_direction(_, _), do: :internal

  @doc "Deletes missing location Odoo IDs to keep local state clean if needed."
  def delete_missing_locations(current_odoo_ids) do
    Location
    |> Ash.Query.filter(odoo_id not in ^current_odoo_ids)
    |> Ash.read!()
    |> Enum.each(&Ash.destroy/1)
  end
end
