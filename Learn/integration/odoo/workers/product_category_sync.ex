defmodule Center.Integration.Odoo.Workers.ProductCategorySync do
  @moduledoc """
  Oban worker that synchronizes product categories from Odoo `product.category`.

  Two-pass sync: first upsert all categories, then resolve parent hierarchy.
  """
  use Oban.Worker, queue: :sync, max_attempts: 3

  require Logger
  require Ash.Query

  alias Center.Integration.Odoo.{Client, SyncEvents}
  alias Center.Catalog.ProductCategory

  @fields ["name", "complete_name", "parent_id"]

  @impl Oban.Worker
  def perform(_job) do
    Logger.info("ProductCategorySync: Starting product category sync from Odoo")
    SyncEvents.broadcast_started(SyncEvents.topic_product_categories())

    case Client.search_read("product.category", [], @fields) do
      {:ok, records} when is_list(records) ->
        Logger.info("ProductCategorySync: Received #{length(records)} categories from Odoo")
        sync_records(records)

      {:ok, other} ->
        Logger.error("ProductCategorySync: Unexpected response: #{inspect(other)}")
        {:error, "unexpected_response"}

      {:error, reason} ->
        Logger.error("ProductCategorySync: Failed to fetch categories: #{inspect(reason)}")
        {:error, reason}
    end
  end

  defp sync_records(records) do
    now = DateTime.utc_now(:second)

    # First pass: upsert all categories (without parent)
    results =
      Enum.map(records, fn record ->
        attrs = %{
          odoo_id: record["id"],
          name: record["name"],
          complete_name: to_string_or_nil(record["complete_name"]),
          active: true,
          odoo_data: record,
          synced_at: now
        }

        case ProductCategory |> Ash.Changeset.for_create(:upsert_from_odoo, attrs) |> Ash.create() do
          {:ok, category} ->
            {:ok, category}

          {:error, error} ->
            Logger.warning("ProductCategorySync: Failed to upsert category #{record["id"]}: #{inspect(error)}")

            {:error, error}
        end
      end)

    # Second pass: resolve parent_id references
    Enum.each(records, fn record ->
      case extract_parent_odoo_id(record["parent_id"]) do
        nil ->
          :ok

        parent_odoo_id ->
          with {:ok, cat} when not is_nil(cat) <- ProductCategory |> Ash.Query.filter(odoo_id == ^record["id"]) |> Ash.read_first(),
               {:ok, parent} when not is_nil(parent) <- ProductCategory |> Ash.Query.filter(odoo_id == ^parent_odoo_id) |> Ash.read_first() do
            cat
            |> Ash.Changeset.for_update(:update, %{parent_id: parent.id})
            |> Ash.update()
          end
      end
    end)

    ok_count = Enum.count(results, &match?({:ok, _}, &1))
    err_count = Enum.count(results, &match?({:error, _}, &1))
    Logger.info("ProductCategorySync: Completed — #{ok_count} synced, #{err_count} errors")

    SyncEvents.broadcast(SyncEvents.topic_product_categories(), %{
      synced: ok_count,
      errors: err_count
    })

    :ok
  end

  defp extract_parent_odoo_id([id, _name]) when is_integer(id), do: id
  defp extract_parent_odoo_id(_), do: nil

  defp to_string_or_nil(false), do: nil
  defp to_string_or_nil(nil), do: nil
  defp to_string_or_nil(val) when is_binary(val), do: val
  defp to_string_or_nil(_), do: nil
end
