defmodule Center.Integration.Odoo.Workers.DeliveryPush do
  @moduledoc """
  Oban worker that pushes a delivery validation to Odoo.
  """
  use Oban.Worker, queue: :odoo_sync, max_attempts: 3

  require Logger
  require Ash.Query

  alias Center.Inventory.Delivery
  alias Center.Sales.SaleOrder
  alias Center.Integration.Odoo.Deliveries

  @impl Oban.Worker
  def perform(%Oban.Job{args: %{"delivery_id" => delivery_id}}) do
    case Delivery |> Ash.get(delivery_id) do
      {:ok, delivery} ->
        process_delivery(delivery)

      {:error, _} ->
        Logger.warning("DeliveryPush worker failed: Delivery #{delivery_id} not found.")
        :ok
    end
  end

  defp process_delivery(delivery) do
    case SaleOrder |> Ash.get(delivery.sale_order_id) do
      {:ok, order} when not is_nil(order.odoo_name) ->
        sync_with_odoo(order)

      {:ok, _} ->
        Logger.warning("Cannot push delivery to Odoo: order hasn't been synced yet or odoo_name is missing.")
        {:snooze, 60}

      {:error, _} ->
        Logger.error("DeliveryPush: SaleOrder ##{delivery.sale_order_id} not found")
        {:error, :order_not_found}
    end
  end

  defp sync_with_odoo(order) do
    case Deliveries.pull_for_order(order.odoo_name) do
      {:ok, [picking | _]} ->
        odoo_id = picking["id"]
        case Deliveries.push_validate(odoo_id) do
          {:ok, _} ->
            Logger.info("Successfully pushed delivery validation to Odoo for #{order.order_number}")
            :ok

          {:error, reason} ->
            Logger.error("Failed to validate picking #{odoo_id}: #{inspect(reason)}")
            {:error, reason}
        end

      {:ok, []} ->
        Logger.warning("No picking found in Odoo for origin #{order.odoo_name}")
        :ok

      {:error, reason} ->
        {:error, reason}
    end
  end
end
