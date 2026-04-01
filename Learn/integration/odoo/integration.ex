defmodule Center.Integration.Odoo.Integration do
  @moduledoc """
  Behaviour and utilities for Odoo integration adapters.

  Each domain (sales, deliveries, invoices, projects) implements this behaviour
  to handle push/pull operations with Odoo. The adapters translate between
  Center's internal data model and Odoo's JSON-2 API.

  ## Architecture

  ```
  Center App (LiveView / Context)
       │
       ├── Sales.create_order/1  ──► Oban Worker ──► SaleOrders.push_draft/1
       ├── Sales.confirm_order/1 ──► Oban Worker ──► SaleOrders.push_confirm/1
       │
       ├── Deliveries (future)
       ├── Invoices (future)
       └── Projects (future)
  ```

  Each adapter is responsible for:
  - Mapping local data to Odoo field format
  - Calling the Odoo API via `Center.Integration.Odoo.Client`
  - Updating local records with Odoo response data (IDs, names)
  - Broadcasting sync events for real-time UI updates
  """

  @type push_result :: {:ok, map()} | {:error, term()}

  @doc "Pushes a local record to Odoo as a draft/quotation."
  @callback push_draft(record :: struct()) :: push_result()

  @doc "Pushes a confirmation action to Odoo for an existing record."
  @callback push_confirm(record :: struct()) :: push_result()

  @doc "Pushes a cancellation action to Odoo for an existing record."
  @callback push_cancel(record :: struct()) :: push_result()

  @doc "Pulls/syncs a record from Odoo by its Odoo ID."
  @callback pull(odoo_id :: integer()) :: push_result()

  @optional_callbacks [push_confirm: 1, push_cancel: 1, pull: 1]
end
