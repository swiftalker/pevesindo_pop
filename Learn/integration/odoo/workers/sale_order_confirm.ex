defmodule Center.Integration.Odoo.Workers.SaleOrderConfirm do
  @moduledoc """
  Oban worker that confirms a sale order in Odoo.
  """
  use Oban.Worker, queue: :sync, max_attempts: 3

  require Logger

  alias Center.Sales.SaleOrder
  alias Center.Integration.Odoo.{SaleOrders, SyncEvents}

  @impl Oban.Worker
  def perform(%Oban.Job{args: %{"sale_order_id" => order_id}}) do
    Logger.info("SaleOrderConfirm: Confirming order ##{order_id} in Odoo")

    case SaleOrder |> Ash.get(order_id, load: [:customer, :lines, :company, :pricelist]) do
      {:ok, order} ->
        process_confirmation(order)

      {:error, _} ->
        Logger.error("SaleOrderConfirm: SaleOrder ##{order_id} not found")
        {:error, :order_not_found}
    end
  end

  defp process_confirmation(order) do
    case order.odoo_id do
      nil ->
        Logger.error("SaleOrderConfirm: Order ##{order.id} has no Odoo ID, cannot confirm")
        {:error, :not_pushed_yet}

      _odoo_id ->
        case SaleOrders.push_confirm(order) do
          {:ok, _updated} ->
            Logger.info("SaleOrderConfirm: Successfully confirmed order #{order.order_number} in local DB and Odoo.")
            SyncEvents.broadcast(SyncEvents.topic_sale_orders(), %{confirmed: 1, order_id: order.id})
            :ok

          {:error, :rate_limited} ->
            Logger.warning("SaleOrderConfirm: Odoo rate limited for order ##{order.id} — snoozing 60s")
            {:snooze, 60}

          {:error, reason} ->
            Logger.error("SaleOrderConfirm: Failed to confirm order ##{order.id}: #{inspect(reason)}")
            {:error, reason}
        end
    end
  end
end
