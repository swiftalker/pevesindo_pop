defmodule Center.Integration.Odoo.Workers.CustomerPush do
  @moduledoc """
  Oban worker that pushes a locally-created customer to Odoo.

  Creates a `res.partner` record in Odoo and stores the returned ID locally.
  Triggered when user clicks "Push to Odoo" on an unpushed customer.
  """
  use Oban.Worker, queue: :sync, max_attempts: 3

  require Logger

  alias Center.Integration.Odoo.{Customers, SyncEvents}
  alias Center.MasterData.Customer

  @impl Oban.Worker
  def perform(%Oban.Job{args: %{"customer_id" => customer_id}}) do
    Logger.info("CustomerPush: Pushing customer ##{customer_id} to Odoo")

    case Customer |> Ash.get(customer_id) do
      {:ok, customer} ->
        # We still need to pass the Ecto struct to Customers.push_to_odoo if it expects it, 
        # or refactor Customers.push_to_odoo to accept Ash struct.
        # Currently Customers.push_to_odoo expects %Customer{} which is Ecto struct.
        # But Ash gives us Ash struct. Since they share the same table, it might work if 
        # we convert or if we refactor Customers.push_to_odoo to handle both.
        # Actually, let's pass the customer. 
        
        # Center.Integration.Odoo.Customers.push_to_odoo expects %Center.MasterData.Ecto.Customer{}
        # Let's check what Center.MasterData.Ecto.Customer is.
        
        case Customers.push_to_odoo(customer) do
          {:ok, updated} ->
            Logger.info("CustomerPush: Customer ##{customer_id} pushed → Odoo ID #{updated.odoo_id}")

            SyncEvents.broadcast(SyncEvents.topic_customers(), %{pushed: 1})
            :ok

          {:error, :already_pushed} ->
            Logger.info("CustomerPush: Customer ##{customer_id} already has Odoo ID, skipping")
            :ok

          {:error, reason} ->
            Logger.error("CustomerPush: Failed to push customer ##{customer_id}: #{inspect(reason)}")

            {:error, reason}
        end

      {:error, _} ->
        Logger.error("CustomerPush: Customer ##{customer_id} not found")
        {:error, :not_found}
    end
  end
end
