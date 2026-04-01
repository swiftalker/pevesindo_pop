defmodule Center.Integration.Odoo.Gateway do
  @moduledoc """
  Odoo HTTP gateway. Delegates to `Center.Integration.Odoo.Client`.

  This is the canonical entry point for all Odoo API communication.
  No business logic allowed here — only HTTP transport.
  """

  defdelegate call(model, method, args, opts \\ []), to: Center.Integration.Odoo.Client
  defdelegate search_read(model, domain, fields, opts \\ []), to: Center.Integration.Odoo.Client
  defdelegate create(model, values, opts \\ []), to: Center.Integration.Odoo.Client
  defdelegate write(model, ids, values, opts \\ []), to: Center.Integration.Odoo.Client
  defdelegate read(model, ids, fields, opts \\ []), to: Center.Integration.Odoo.Client
end
