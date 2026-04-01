defmodule Center.Integration.Odoo.Workers.NoteSync do
  @moduledoc """
  Worker for syncing notes from Center to Odoo asynchronously.
  Can handle both SaleOrder and SaleInvoice notes.
  """
  use Oban.Worker,
    queue: :odoo,
    max_attempts: 5,
    unique: [period: 30, keys: [:record_type, :record_id]]

  require Logger

  alias Center.Sales

  @impl Oban.Worker
  def perform(%Oban.Job{args: %{"record_type" => "sale_order", "record_id" => id}}) do
    order = Sales.get_order!(id)

    if order.odoo_id do
      Logger.info("NoteSync: Syncing notes for SaleOrder ##{id} to Odoo##{order.odoo_id}")
      Center.Integration.Odoo.SaleOrders.update_notes(order.odoo_id, order.notes)
    else
      Logger.debug("NoteSync: SaleOrder ##{id} is not yet synced to Odoo. Skipping.")
      :ok
    end
  end

  def perform(%Oban.Job{args: %{"record_type" => "sale_invoice", "record_id" => id}}) do
    invoice = Sales.get_invoice!(id)

    if invoice.odoo_invoice_id do
      Logger.info("NoteSync: Syncing notes for SaleInvoice ##{id} to Odoo##{invoice.odoo_invoice_id}")

      Center.Integration.Odoo.Invoices.update_notes(invoice.odoo_invoice_id, invoice.notes)
    else
      Logger.debug("NoteSync: SaleInvoice ##{id} is not yet synced to Odoo. Skipping.")
      :ok
    end
  end
end
