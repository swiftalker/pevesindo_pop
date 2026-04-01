defmodule Center.Integration.Odoo.Workers.SaleOrderPush do
  @moduledoc """
  Oban worker that pushes a locally-created sale order to Odoo as a draft/quotation.
  """
  use Oban.Worker, queue: :sync, max_attempts: 3

  require Logger

  alias Center.Sales.SaleOrder
  alias Center.Integration.Odoo.{SaleOrders, SyncEvents}

  @permanent_error_types [
    :missing_product_variant,
    :no_customer,
    :customer_push_failed
  ]

  @impl Oban.Worker
  def perform(%Oban.Job{args: %{"sale_order_id" => order_id}}) do
    Logger.info("SaleOrderPush: Pushing order ##{order_id} to Odoo")

    case SaleOrder |> Ash.get(order_id, load: [:customer, :lines, :company, :pricelist]) do
      {:ok, order} ->
        if order.sync_error != nil && order.status != "draft" do
          Logger.info("SaleOrderPush: Order ##{order_id} is frozen (status=#{order.status}, sync_error present). Skipping.")
          :ok
        else
          do_push(order)
        end

      {:error, _} ->
        Logger.error("SaleOrderPush: SaleOrder ##{order_id} not found")
        {:error, :order_not_found}
    end
  end

  defp do_push(%SaleOrder{odoo_id: odoo_id} = order) when is_integer(odoo_id) do
    Logger.info("SaleOrderPush: Order ##{order.id} already has Odoo ID #{odoo_id}, updating")

    case SaleOrders.push_update(order) do
      {:ok, _updated} ->
        Logger.info("SaleOrderPush: Order ##{order.id} updated")
        clear_sync_error(order)
        SyncEvents.broadcast(SyncEvents.topic_sale_orders(), %{pushed: 1, order_id: order.id})
        :ok

      {:error, reason} ->
        handle_failure(order, reason)
    end
  end

  defp do_push(%SaleOrder{} = order) do
    case SaleOrders.push_draft(order) do
      {:ok, updated} ->
        Logger.info("SaleOrderPush: Order ##{order.id} pushed → Odoo ##{updated.odoo_id}")
        clear_sync_error(order)
        SyncEvents.broadcast(SyncEvents.topic_sale_orders(), %{pushed: 1, order_id: order.id})
        :ok

      {:error, reason} ->
        handle_failure(order, reason)
    end
  end

  defp handle_failure(order, :rate_limited) do
    Logger.warning("SaleOrderPush: Odoo rate limited for order ##{order.id} — snoozing 60s")
    {:snooze, 60}
  end

  defp handle_failure(order, reason) do
    Logger.error("SaleOrderPush: Failed to push order ##{order.id}: #{inspect(reason)}")

    error_message = format_error_message(reason)
    set_sync_error(order, error_message)

    if permanent_failure?(reason) do
      Logger.warning("SaleOrderPush: Order ##{order.id} has a permanent sync error. Freezing order.")
      SyncEvents.broadcast(SyncEvents.topic_sale_orders(), %{sync_failed: true, order_id: order.id, error: error_message})
      :ok
    else
      SyncEvents.broadcast(SyncEvents.topic_sale_orders(), %{sync_failed: true, order_id: order.id, error: error_message})
      {:error, reason}
    end
  end

  defp permanent_failure?({error_type, _msg}) when error_type in @permanent_error_types, do: true
  defp permanent_failure?(error_type) when error_type in @permanent_error_types, do: true
  defp permanent_failure?(%{status: 404, message: message}) when is_binary(message) do
    String.contains?(message, "Record does not exist") or String.contains?(message, "has been deleted")
  end
  defp permanent_failure?(_), do: false

  defp format_error_message({:missing_product_variant, msg}) when is_binary(msg), do: msg
  defp format_error_message({:customer_push_failed, inner}), do: "Gagal menyinkronkan data customer ke Odoo: #{inspect(inner)}"
  defp format_error_message(:no_customer), do: "Order tidak memiliki customer."
  defp format_error_message(%{status: status, message: message}) when is_binary(message), do: "Odoo error (#{status}): #{String.slice(message, 0, 300)}"
  defp format_error_message(reason), do: "Sync error: #{inspect(reason) |> String.slice(0, 300)}"

  defp set_sync_error(order, error_message) do
    order |> Ash.Changeset.for_update(:update, %{sync_error: error_message}) |> Ash.update()
  end

  defp clear_sync_error(order) do
    if order.sync_error != nil do
      order |> Ash.Changeset.for_update(:update, %{sync_error: nil}) |> Ash.update()
    else
      :ok
    end
  end
end
