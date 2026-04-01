defmodule Center.Integration.Odoo.Workers.ProductVariantSync do
  @moduledoc """
  Oban worker that synchronizes product variants from Odoo `product.product`.

  Resolves `product_tmpl_id` → local `product_id`.
  """
  use Oban.Worker, queue: :sync, max_attempts: 3

  require Logger
  require Ash.Query

  alias Center.Integration.Odoo.{Client, SyncEvents}
  alias Center.Catalog.ProductVariant

  @fields [
    "name",
    "display_name",
    "default_code",
    "list_price",
    "standard_price",
    "qty_available",
    "barcode",
    "active",
    "uom_id",
    "product_tmpl_id"
  ]

  @impl Oban.Worker
  def perform(_job) do
    Logger.info("ProductVariantSync: Starting product variant sync from Odoo")
    SyncEvents.broadcast_started(SyncEvents.topic_product_variants())

    domain = ["|", ["active", "=", true], ["active", "=", false]]

    case Client.search_read("product.product", domain, @fields, limit: 10000) do
      {:ok, records} when is_list(records) ->
        Logger.info("ProductVariantSync: Received #{length(records)} variants from Odoo")
        sync_records(records)

      {:ok, other} ->
        Logger.error("ProductVariantSync: Unexpected response: #{inspect(other)}")
        {:error, "unexpected_response"}

      {:error, reason} ->
        Logger.error("ProductVariantSync: Failed to fetch variants: #{inspect(reason)}")
        {:error, reason}
    end
  end

  defp sync_records(records) do
    now = DateTime.utc_now(:second)

    results =
      Enum.map(records, fn record ->
        product_id = resolve_product_id(record["product_tmpl_id"])

        if is_nil(product_id) do
          # Skip variants if the parent template isn't synced yet
          {:error, :missing_parent_product}
        else
          attrs = %{
            odoo_id: record["id"],
            name: record["display_name"] || record["name"],
            default_code: to_string_or_nil(record["default_code"]),
            list_price: record["list_price"],
            standard_price: record["standard_price"],
            qty_available: record["qty_available"],
            barcode: to_string_or_nil(record["barcode"]),
            product_id: product_id,
            uom: extract_name(record["uom_id"]),
            active: record["active"] || true,
            odoo_data: record,
            synced_at: now
          }

          case ProductVariant |> Ash.Changeset.for_create(:upsert_from_odoo, attrs) |> Ash.create() do
            {:ok, variant} ->
              {:ok, variant}

            {:error, error} ->
              Logger.warning("ProductVariantSync: Failed to upsert variant #{record["id"]}: #{inspect(error)}")

              {:error, error}
          end
        end
      end)

    ok_count = Enum.count(results, &match?({:ok, _}, &1))
    err_count = Enum.count(results, &match?({:error, _}, &1))

    Logger.info("ProductVariantSync: Completed — #{ok_count} synced, #{err_count} skipped/errors")

    SyncEvents.broadcast(SyncEvents.topic_product_variants(), %{
      synced: ok_count,
      errors: err_count
    })

    :ok
  end

  # product_tmpl_id comes as [id, "name"] or false
  defp resolve_product_id([odoo_id, _name]) when is_integer(odoo_id) do
    case Center.Catalog.Product |> Ash.Query.filter(odoo_id == ^odoo_id) |> Ash.read_first() do
      {:ok, product} when not is_nil(product) -> product.id
      _ -> nil
    end
  end

  defp resolve_product_id(_), do: nil

  defp to_string_or_nil(false), do: nil
  defp to_string_or_nil(nil), do: nil
  defp to_string_or_nil(val) when is_binary(val), do: val
  defp to_string_or_nil(_), do: nil

  defp extract_name([_id, name]) when is_binary(name), do: name
  defp extract_name(_), do: nil
end
