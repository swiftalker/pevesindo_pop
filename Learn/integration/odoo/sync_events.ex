defmodule Center.Integration.Odoo.SyncEvents do
  @moduledoc """
  PubSub helper for Odoo sync events.

  Sync workers broadcast when they start/finish, and LiveViews subscribe
  to auto-refresh their data without page reload. Also integrates with
  `SyncTracker` to persist sync state across LiveView navigations.

  ## Topics

    - `"odoo:sync:companies"` — CompanySync
    - `"odoo:sync:employees"` — EmployeeSync
    - `"odoo:sync:customers"` — CustomerSync
    - `"odoo:sync:product_categories"` — ProductCategorySync
    - `"odoo:sync:products"` — ProductSync
    - `"odoo:sync:journals"` — JournalSync
  """

  alias Center.Integration.Odoo.SyncTracker

  @pubsub Center.PubSub

  @doc "Subscribe the current process to a sync topic."
  def subscribe(topic) do
    Phoenix.PubSub.subscribe(@pubsub, topic)
  end

  @doc "Broadcast that a sync has started. Also marks it in SyncTracker."
  def broadcast_started(topic) do
    SyncTracker.mark_started(topic)
    Phoenix.PubSub.broadcast(@pubsub, topic, {:sync_started, topic, %{}})
  end

  @doc "Broadcast that a sync has completed. Also clears it in SyncTracker and creates a Global Notification."
  def broadcast(topic, payload \\ %{}) do
    SyncTracker.mark_completed(topic)

    # 1. Standard PubSub for LiveViews
    Phoenix.PubSub.broadcast(@pubsub, topic, {:sync_completed, topic, payload})

    # 2. Push to Universal Notifications system securely
    label = topic_label(topic)
    count = payload[:synced] || payload[:pushed] || 0

    message =
      if count > 0 do
        "Successfully synchronized #{count} records for #{label} from Odoo."
      else
        "Sync completed for #{label}. No new records updated."
      end

    Center.Accounts.Notifications.broadcast_global(%{
      title: "Odoo Sync: #{label}",
      message: message,
      type: "odoo_sync",
      action_url: action_url_for_topic(topic),
      metadata: %{"topic" => topic, "count" => count}
    })
  end

  # --- Notification Mapping Helpers ---

  defp topic_label("odoo:sync:companies"), do: "Companies"
  defp topic_label("odoo:sync:employees"), do: "Employees"
  defp topic_label("odoo:sync:customers"), do: "Customers"
  defp topic_label("odoo:sync:product_categories"), do: "Product Categories"
  defp topic_label("odoo:sync:products"), do: "Products"
  defp topic_label("odoo:sync:product_variants"), do: "Product Variants"
  defp topic_label("odoo:sync:pricelists"), do: "Pricelists"
  defp topic_label("odoo:sync:pricelist_items"), do: "Pricelist Items"
  defp topic_label("odoo:sync:locations"), do: "Locations"
  defp topic_label("odoo:sync:stock_quants"), do: "Stock Quants"
  defp topic_label("odoo:sync:stock_move_lines"), do: "Stock Moves"
  defp topic_label("odoo:sync:banks"), do: "Banks"
  defp topic_label("odoo:sync:bank_accounts"), do: "Bank Accounts"
  defp topic_label("odoo:sync:journals"), do: "Journals"
  defp topic_label("odoo:sync:sale_orders"), do: "Sale Orders"
  defp topic_label(_), do: "Data"

  defp action_url_for_topic("odoo:sync:companies"), do: "/admin/companies"
  defp action_url_for_topic("odoo:sync:employees"), do: "/admin/employees"
  defp action_url_for_topic("odoo:sync:customers"), do: "/admin/customers"
  defp action_url_for_topic("odoo:sync:products"), do: "/admin/products"
  defp action_url_for_topic("odoo:sync:banks"), do: "/admin/banks"
  defp action_url_for_topic("odoo:sync:journals"), do: "/admin/companies"
  defp action_url_for_topic("odoo:sync:sale_orders"), do: "/admin/sales"
  defp action_url_for_topic(_), do: nil

  @doc "Broadcast a single record upserted during sync (for real-time streaming)."
  def broadcast_record(topic, record) do
    Phoenix.PubSub.broadcast(@pubsub, topic, {:sync_record, topic, record})
  end

  # Convenience topic constants
  def topic_companies, do: "odoo:sync:companies"
  def topic_employees, do: "odoo:sync:employees"
  def topic_customers, do: "odoo:sync:customers"
  def topic_product_categories, do: "odoo:sync:product_categories"
  def topic_products, do: "odoo:sync:products"
  def topic_product_variants, do: "odoo:sync:product_variants"
  def topic_pricelists, do: "odoo:sync:pricelists"
  def topic_pricelist_items, do: "odoo:sync:pricelist_items"
  def topic_locations, do: "odoo:sync:locations"
  def topic_stock_quants, do: "odoo:sync:stock_quants"
  def topic_stock_move_lines, do: "odoo:sync:stock_move_lines"
  def topic_banks, do: "odoo:sync:banks"
  def topic_bank_accounts, do: "odoo:sync:bank_accounts"
  def topic_journals, do: "odoo:sync:journals"
  def topic_sale_orders, do: "odoo:sync:sale_orders"
end
