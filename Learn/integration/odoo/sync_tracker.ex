defmodule Center.Integration.Odoo.SyncTracker do
  @moduledoc """
  Lightweight Agent that tracks which Odoo sync topics are currently running.

  Sync workers call `mark_started/1` and `mark_completed/1`.
  LiveViews can check `syncing?/1` on mount to restore the spinning button state.
  """
  use Agent

  def start_link(_opts) do
    Agent.start_link(fn -> MapSet.new() end, name: __MODULE__)
  end

  @doc "Mark a sync topic as actively running."
  def mark_started(topic) do
    Agent.update(__MODULE__, &MapSet.put(&1, topic))
  end

  @doc "Mark a sync topic as completed."
  def mark_completed(topic) do
    Agent.update(__MODULE__, &MapSet.delete(&1, topic))
  end

  @doc "Check if a sync topic is currently running."
  def syncing?(topic) do
    Agent.get(__MODULE__, &MapSet.member?(&1, topic))
  end

  @doc "Return all currently active sync topics."
  def active_syncs do
    Agent.get(__MODULE__, &MapSet.to_list/1)
  end
end
