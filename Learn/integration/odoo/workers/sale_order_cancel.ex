defmodule Center.Integration.Odoo.Workers.SaleOrderCancel do
  @moduledoc """
  Oban worker that cancels a sale order in Odoo.
  """
  use Oban.Worker, queue: :sync, max_attempts: 3

  require Logger

  alias Center.Sales.SaleOrder
  alias Center.Integration.Odoo.{SaleOrders, SyncEvents}

  @impl Oban.Worker
  def perform(%Oban.Job{args: %{"sale_order_id" => order_id}}) do
    Logger.info("SaleOrderCancel: Cancelling order ##{order_id} in Odoo")

    case SaleOrder |> Ash.get(order_id) do
      {:ok, order} ->
        process_cancel(order)

      {:error, _} ->
        Logger.error("SaleOrderCancel: SaleOrder ##{order_id} not found")
        {:error, :order_not_found}
    end
  end

  defp process_cancel(order) do
    case order.odoo_id do
      nil ->
        Logger.info("SaleOrderCancel: Order ##{order.id} has no Odoo ID, skipping")
        :ok

      _odoo_id ->
        case SaleOrders.push_cancel(order) do
          {:ok, _updated} ->
            Logger.info("SaleOrderCancel: Order ##{order.id} cancelled in Odoo")
            SyncEvents.broadcast(SyncEvents.topic_sale_orders(), %{cancelled: 1, order_id: order.id})
            :ok

          {:error, reason} ->
            Logger.error("SaleOrderCancel: Failed to cancel order ##{order.id}: #{inspect(reason)}")
            {:error, reason}
        end
    end
  end
end
