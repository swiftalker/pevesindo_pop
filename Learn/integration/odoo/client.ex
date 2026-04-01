defmodule Center.Integration.Odoo.Client do
  @moduledoc """
  HTTP client adapter for the Odoo External JSON-2 API.

  All Odoo communication goes through this module. It uses `Req` to make
  HTTP POST requests to `/json/2/<model>/<method>`.

  ## Authentication

  Uses Bearer token (API Key) authentication with `X-Odoo-Database` header.

  ## Error Handling

  Returns `{:ok, data}` on success or `{:error, reason}` on failure.
  All calls include timeout, retry, and structured logging.
  """

  require Logger

  alias Center.Integration.Odoo.Config

  @default_timeout 30_000
  @default_retry_count 2

  @doc """
  Generic JSON-2 API call.

  ## Parameters

    - `model` — Odoo model name, e.g. `"res.company"`
    - `method` — Method to call, e.g. `"search_read"`
    - `params` — Map of params to send as JSON body
    - `opts` — Options: `:timeout`, `:retries`

  ## Examples

      iex> Client.call("res.company", "search_read", %{fields: ["name"]})
      {:ok, [%{"id" => 1, "name" => "My Company"}]}
  """
  def call(model, method, params \\ %{}, opts \\ []) do
    timeout = Keyword.get(opts, :timeout, @default_timeout)
    retries = Keyword.get(opts, :retries, @default_retry_count)

    url = "#{Config.base_url()}/json/2/#{model}/#{method}"

    headers = [
      {"authorization", "bearer #{Config.api_key()}"},
      {"x-odoo-database", Config.database()},
      {"content-type", "application/json; charset=utf-8"},
      {"accept-language", "id-ID,id;q=0.9,en-US;q=0.8,en;q=0.7"},
      {"user-agent", "Center/1.0 Elixir-Req"}
    ]

    started_at = System.monotonic_time(:millisecond)

    # Override Odoo's default API user language with the company's Indonesian context
    # so we receive appropriately translated product names (e.g Wallpanel (15.5cm) instead of Wallpanel (16cm))
    params_with_context =
      params
      |> Map.put_new_lazy(:context, fn -> %{"lang" => "id_ID"} end)
      |> then(fn p ->
        if Map.has_key?(p, "context") and not is_map(p["context"]) do
          # Some methods don't use context cleanly, just safe-guard
          p
        else
          Map.update!(p, :context, &Map.put(&1, "lang", "id_ID"))
        end
      end)

    result =
      Req.new(
        url: url,
        method: :post,
        headers: headers,
        json: params_with_context,
        receive_timeout: timeout,
        retry: fn _request, result ->
          case result do
            %Req.Response{status: status} ->
              # Don't auto-retry on 429 — we handle rate limits at worker level with snooze
              status != 429 and status in [408, 429, 500, 502, 503, 504]

            _exception ->
              # Retry on network/connection errors
              true
          end
        end,
        max_retries: retries
      )
      |> Req.request()

    elapsed = System.monotonic_time(:millisecond) - started_at

    case result do
      {:ok, %Req.Response{status: status, body: body}} when status in 200..299 ->
        Logger.debug("Odoo OK #{model}/#{method} (#{elapsed}ms)")
        {:ok, body}

      {:ok, %Req.Response{status: 429}} ->
        Logger.warning("Odoo RATE LIMITED #{model}/#{method} (#{elapsed}ms) — will snooze at worker level")

        {:error, :rate_limited}

      {:ok, %Req.Response{status: status, body: body}} ->
        error_msg = extract_error_message(body)

        Logger.error("Odoo ERROR #{model}/#{method} status=#{status} (#{elapsed}ms): #{error_msg}")

        {:error, %{status: status, message: error_msg, body: body}}

      {:error, exception} ->
        Logger.error("Odoo NETWORK ERROR #{model}/#{method} (#{elapsed}ms): #{inspect(exception)}")

        {:error, %{status: nil, message: "network_error", exception: exception}}
    end
  end

  @doc """
  Search records by domain.

  ## Parameters

    - `model` — Odoo model name
    - `domain` — Odoo domain filter, e.g. `[["active", "=", true]]`
    - `opts` — `:limit`, `:offset`, `:order`, plus call opts

  ## Examples

      iex> Client.search("res.partner", [["is_company", "=", true]], limit: 10)
      {:ok, [1, 2, 3]}
  """
  def search(model, domain, opts \\ []) do
    {call_opts, search_opts} = Keyword.split(opts, [:timeout, :retries])

    params =
      %{domain: domain}
      |> maybe_put(:limit, search_opts[:limit])
      |> maybe_put(:offset, search_opts[:offset])
      |> maybe_put(:order, search_opts[:order])

    call(model, "search", params, call_opts)
  end

  @doc """
  Read specific records by IDs.

  ## Examples

      iex> Client.read("res.partner", [1, 2], ["name", "email"])
      {:ok, [%{"id" => 1, "name" => "Alice"}, ...]}
  """
  def read(model, ids, fields, opts \\ []) do
    call(model, "read", %{ids: ids, fields: fields}, opts)
  end

  @doc """
  Combined search + read in a single Odoo transaction.

  This is the preferred method for fetching data — it avoids race conditions
  between separate search and read calls.

  ## Parameters

    - `model` — Odoo model name
    - `domain` — Odoo domain filter
    - `fields` — List of field names to return
    - `opts` — `:limit`, `:offset`, `:order`, plus call opts

  ## Examples

      iex> Client.search_read("res.company", [], ["name", "parent_id"])
      {:ok, [%{"id" => 1, "name" => "Pevesindo Franchise"}, ...]}
  """
  def search_read(model, domain, fields, opts \\ []) do
    {call_opts, search_opts} = Keyword.split(opts, [:timeout, :retries])

    params =
      %{domain: domain, fields: fields}
      |> maybe_put(:limit, search_opts[:limit])
      |> maybe_put(:offset, search_opts[:offset])
      |> maybe_put(:order, search_opts[:order])

    call(model, "search_read", params, call_opts)
  end

  @doc """
  Create a new record in Odoo.

  Returns `{:ok, id}` with the new record ID on success.

  ## Examples

      iex> Client.create("res.partner", %{name: "New Customer", is_company: true})
      {:ok, 42}
  """
  def create(model, values, opts \\ []) do
    case call(model, "create", %{vals_list: [values]}, opts) do
      {:ok, [id | _]} when is_integer(id) -> {:ok, id}
      {:ok, id} when is_integer(id) -> {:ok, id}
      other -> other
    end
  end

  @doc """
  Update existing records in Odoo.

  ## Examples

      iex> Client.write("res.partner", [42], %{name: "Updated Name"})
      {:ok, true}
  """
  def write(model, ids, values, opts \\ []) do
    call(model, "write", %{ids: ids, vals: values}, opts)
  end

  @doc """
  Count records matching domain.

  ## Examples

      iex> Client.search_count("res.partner", [["is_company", "=", true]])
      {:ok, 15}
  """
  def search_count(model, domain, opts \\ []) do
    call(model, "search_count", %{domain: domain}, opts)
  end

  @doc """
  Execute a method on a model (XML-RPC style wrapper for JSON-2).
  """
  def execute_kw(model, method, args, params \\ %{}, opts \\ []) do
    full_params = Map.put(params, :args, args)
    call(model, method, full_params, opts)
  end

  # -- Private helpers --

  defp extract_error_message(%{"message" => msg}), do: msg
  defp extract_error_message(body) when is_binary(body), do: body
  defp extract_error_message(body), do: inspect(body)

  defp maybe_put(map, _key, nil), do: map
  defp maybe_put(map, key, value), do: Map.put(map, key, value)
end
