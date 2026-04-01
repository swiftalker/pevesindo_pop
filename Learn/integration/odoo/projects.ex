defmodule Center.Integration.Odoo.Projects do
  @moduledoc """
  Odoo integration adapter for Projects (`project.project` / `project.task`).

  Placeholder for future project sale integration.

  ## Planned Flow

  ```
  Project Sale Order → Create Project → Create Tasks → Track Progress
  ```

  ## Future Implementation

  - `push_draft/1` — create project sale order in Odoo
  - `push_confirm/1` — confirm and trigger project creation
  - `pull_project/1` — fetch project details
  - `pull_tasks/1` — fetch project tasks
  """

  @behaviour Center.Integration.Odoo.Integration

  @impl true
  def push_draft(_record), do: {:error, :not_implemented}

  @impl true
  def push_confirm(_record), do: {:error, :not_implemented}

  @impl true
  def push_cancel(_record), do: {:error, :not_implemented}

  @impl true
  def pull(_odoo_id), do: {:error, :not_implemented}
end
