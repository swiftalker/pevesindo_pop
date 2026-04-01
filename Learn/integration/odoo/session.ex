defmodule Center.Integration.Odoo.Session do
  @moduledoc """
  Manages Odoo web session authentication for PDF report downloads.

  ## Why This Exists

  Odoo's report endpoints are **web controllers**, not API endpoints.
  They require a valid session cookie obtained by logging in through the
  web interface, plus a CSRF token for POST requests.

  In Odoo 17+ with the Website module enabled, `GET /report/pdf/` returns
  403 Forbidden. The correct approach is to use `POST /report/download`
  with proper CSRF token — this is exactly what the Odoo web client does
  when you click "Print" in the browser.

  ## How It Works

  1. Authenticates via `POST /web/session/authenticate` to obtain a `session_id` cookie
  2. Fetches the CSRF token from the `/web` page HTML
  3. Downloads PDFs via `POST /report/download` with session cookie + CSRF token
  4. Caches session + CSRF in ETS to avoid re-authenticating on every request
  5. Automatically re-authenticates when the session expires

  ## Configuration

  Requires `ODOO_LOGIN` and `ODOO_PASSWORD` to be set in the environment.
  These are read via `Center.Integration.Odoo.Config`.
  """

  use GenServer

  require Logger

  alias Center.Integration.Odoo.Config

  @ets_table :odoo_session_cache
  @session_key :current_session
  # Re-authenticate after 50 minutes (Odoo sessions typically last ~1 hour)
  @session_ttl_ms 50 * 60 * 1000

  # ── Public API ──────────────────────────────────────────────────────

  @doc """
  Starts the session manager GenServer.

  This creates an ETS table for caching the session cookie and CSRF token.
  """
  def start_link(opts \\ []) do
    GenServer.start_link(__MODULE__, opts, name: __MODULE__)
  end

  @doc """
  Returns a valid Odoo session (session_id + csrf_token).

  If no session is cached or the cached session has expired, this will
  authenticate with Odoo first.

  Returns `{:ok, %{session_id: string, csrf_token: string}}` or `{:error, reason}`.
  """
  def get_session do
    case read_cached_session() do
      {:ok, _session_data} = hit ->
        hit

      :miss ->
        if GenServer.whereis(__MODULE__) do
          GenServer.call(__MODULE__, :authenticate, 30_000)
        else
          Logger.error("Odoo Session: GenServer not running, cannot authenticate")
          {:error, :session_not_configured}
        end
    end
  end

  @doc """
  Forces re-authentication, discarding any cached session.

  Useful when a request returns a 302 redirect to `/web/login` or a 401,
  indicating the session has been invalidated server-side.
  """
  def refresh_session do
    if GenServer.whereis(__MODULE__) do
      GenServer.call(__MODULE__, :refresh, 30_000)
    else
      Logger.error("Odoo Session: GenServer not running, cannot refresh session")
      {:error, :session_not_configured}
    end
  end

  @doc """
  Invalidates the cached session without re-authenticating.
  """
  def invalidate_session do
    if GenServer.whereis(__MODULE__) do
      GenServer.cast(__MODULE__, :invalidate)
    else
      :ok
    end
  end

  @doc """
  Downloads a PDF report from Odoo using web session authentication.

  Uses `POST /report/download` with the session cookie and CSRF token.
  This is the same mechanism the Odoo web client uses when you click "Print".

  If the session is expired, it will automatically re-authenticate and retry once.

  Returns `{:ok, pdf_binary}` or `{:error, reason}`.

  ## Parameters

    - `report_name` — Odoo technical report name (e.g. `"sale.report_saleorder"`)
    - `record_id` — The Odoo record ID (integer)

  ## Examples

      iex> Center.Integration.Odoo.Session.download_report_pdf("sale.report_saleorder", 1611)
      {:ok, <<37, 80, 68, 70, ...>>}
  """
  def download_report_pdf(report_name, record_id)
      when is_binary(report_name) and is_integer(record_id) do
    with {:ok, session_data} <- get_session() do
      case do_download_pdf(report_name, record_id, session_data) do
        {:ok, _pdf_binary} = success ->
          success

        {:error, :session_expired} ->
          Logger.info("Odoo Session: Session expired during PDF download, re-authenticating...")

          with {:ok, new_session_data} <- refresh_session() do
            do_download_pdf(report_name, record_id, new_session_data)
          end

        {:error, _reason} = error ->
          error
      end
    end
  end

  # ── GenServer Callbacks ────────────────────────────────────────────

  @impl true
  def init(_opts) do
    if :ets.info(@ets_table) == :undefined do
      :ets.new(@ets_table, [:set, :named_table, :public, read_concurrency: true])
    end

    {:ok, %{}}
  end

  @impl true
  def handle_call(:authenticate, _from, state) do
    case read_cached_session() do
      {:ok, session_data} ->
        {:reply, {:ok, session_data}, state}

      :miss ->
        result = do_authenticate()

        case result do
          {:ok, session_data} ->
            cache_session(session_data)
            {:reply, {:ok, session_data}, state}

          {:error, _reason} = error ->
            {:reply, error, state}
        end
    end
  end

  @impl true
  def handle_call(:refresh, _from, state) do
    clear_cached_session()
    result = do_authenticate()

    case result do
      {:ok, session_data} ->
        cache_session(session_data)
        {:reply, {:ok, session_data}, state}

      {:error, _reason} = error ->
        {:reply, error, state}
    end
  end

  @impl true
  def handle_cast(:invalidate, state) do
    clear_cached_session()
    {:noreply, state}
  end

  # ── Authentication ─────────────────────────────────────────────────

  defp do_authenticate do
    login = Config.login()
    password = Config.password()

    if login == "" or password == "" do
      Logger.error("Odoo Session: ODOO_LOGIN or ODOO_PASSWORD not configured")
      {:error, :session_not_configured}
    else
      authenticate_via_web(login, password)
    end
  end

  defp authenticate_via_web(login, password) do
    url = "#{Config.base_url()}/web/session/authenticate"

    body = %{
      jsonrpc: "2.0",
      method: "call",
      id: System.unique_integer([:positive]),
      params: %{
        db: Config.database(),
        login: login,
        password: password
      }
    }

    Logger.info("Odoo Session: Authenticating as #{login} via #{url}")

    case Req.new(
           url: url,
           method: :post,
           json: body,
           headers: [
             {"content-type", "application/json"},
             {"user-agent", "Center/1.0 Elixir-Req"}
           ],
           receive_timeout: 30_000,
           connect_options: [timeout: 15_000],
           retry: false,
           redirect: false,
           decode_body: false
         )
         |> Req.request() do
      {:ok, %Req.Response{status: 200, headers: headers, body: resp_body}} ->
        with {:ok, session_id} <- extract_session_from_response(headers, resp_body),
             {:ok, csrf_token} <- fetch_csrf_token(session_id) do
          {:ok, %{session_id: session_id, csrf_token: csrf_token}}
        end

      {:ok, %Req.Response{status: status, body: resp_body}} ->
        body_preview = if is_binary(resp_body), do: String.slice(resp_body, 0, 500), else: ""
        Logger.error("Odoo Session: Auth failed with HTTP #{status}: #{body_preview}")
        {:error, :auth_failed}

      {:error, %Req.TransportError{reason: reason}} ->
        Logger.error("Odoo Session: Transport error during auth: #{inspect(reason)}")
        {:error, :network_error}

      {:error, exception} ->
        Logger.error("Odoo Session: Network error during auth: #{inspect(exception)}")
        {:error, :network_error}
    end
  end

  defp extract_session_from_response(headers, resp_body) do
    session_id = extract_session_cookie(headers)
    auth_ok = verify_auth_response(resp_body)

    case {session_id, auth_ok} do
      {nil, _} ->
        Logger.error("Odoo Session: No session_id cookie in response headers")
        Logger.debug("Odoo Session: Response headers: #{inspect(headers)}")
        {:error, :auth_failed}

      {_, {:error, reason}} ->
        Logger.error("Odoo Session: Auth response indicates failure: #{inspect(reason)}")
        {:error, :auth_failed}

      {sid, :ok} ->
        Logger.info("Odoo Session: Successfully authenticated, got session_id")
        {:ok, sid}
    end
  end

  defp fetch_csrf_token(session_id) do
    url = "#{Config.base_url()}/web"

    Logger.info("Odoo Session: Fetching CSRF token from #{url}")

    case Req.new(
           url: url,
           method: :get,
           headers: [
             {"cookie", "session_id=#{session_id}"},
             {"user-agent", "Center/1.0 Elixir-Req"}
           ],
           receive_timeout: 30_000,
           connect_options: [timeout: 15_000],
           retry: false,
           redirect: false,
           decode_body: false
         )
         |> Req.request() do
      {:ok, %Req.Response{status: 200, body: html_body}} ->
        case Regex.run(~r/csrf_token:\s*"([^"]+)"/, html_body) do
          [_, csrf_token] ->
            Logger.info("Odoo Session: Got CSRF token")
            {:ok, csrf_token}

          nil ->
            Logger.error("Odoo Session: Could not find csrf_token in /web response")
            {:error, :auth_failed}
        end

      {:ok, %Req.Response{status: status}} ->
        Logger.error("Odoo Session: Failed to fetch /web for CSRF token, HTTP #{status}")
        {:error, :auth_failed}

      {:error, %Req.TransportError{reason: reason}} ->
        Logger.error("Odoo Session: Transport error fetching CSRF token: #{inspect(reason)}")
        {:error, :network_error}

      {:error, exception} ->
        Logger.error("Odoo Session: Network error fetching CSRF token: #{inspect(exception)}")
        {:error, :network_error}
    end
  end

  defp extract_session_cookie(headers) do
    cookie_headers = get_header_values(headers, "set-cookie")

    Enum.find_value(cookie_headers, fn cookie_str ->
      cookie_str
      |> String.split(";")
      |> List.first()
      |> String.trim()
      |> case do
        "session_id=" <> value -> if value != "", do: value, else: nil
        _ -> nil
      end
    end)
  end

  defp get_header_values(headers, name) when is_map(headers) do
    Map.get(headers, name, [])
  end

  defp get_header_values(headers, name) when is_list(headers) do
    for {k, v} <- headers, String.downcase(to_string(k)) == name, do: v
  end

  defp verify_auth_response(resp_body) when is_binary(resp_body) do
    case Jason.decode(resp_body) do
      {:ok, %{"result" => %{"uid" => uid}}} when is_integer(uid) and uid > 0 ->
        Logger.info("Odoo Session: Authenticated as uid=#{uid}")
        :ok

      {:ok, %{"result" => %{"uid" => false}}} ->
        Logger.error("Odoo Session: Authentication returned uid=false (invalid credentials)")
        {:error, :invalid_credentials}

      {:ok, %{"result" => %{"uid" => nil}}} ->
        Logger.error("Odoo Session: Authentication returned uid=nil (invalid credentials)")
        {:error, :invalid_credentials}

      {:ok, %{"error" => error}} ->
        error_msg = extract_odoo_error(error)
        Logger.error("Odoo Session: Authentication error: #{error_msg}")
        {:error, :auth_failed}

      {:ok, other} ->
        Logger.warning("Odoo Session: Unexpected auth response: #{inspect(Map.keys(other))}")
        :ok

      {:error, _} ->
        Logger.warning("Odoo Session: Could not parse auth response body as JSON")
        :ok
    end
  end

  defp verify_auth_response(resp_body) when is_map(resp_body) do
    case resp_body do
      %{"result" => %{"uid" => uid}} when is_integer(uid) and uid > 0 ->
        Logger.info("Odoo Session: Authenticated as uid=#{uid}")
        :ok

      %{"result" => %{"uid" => false}} ->
        {:error, :invalid_credentials}

      %{"error" => _error} ->
        {:error, :auth_failed}

      _ ->
        :ok
    end
  end

  defp verify_auth_response(_), do: :ok

  # ── PDF Download ───────────────────────────────────────────────────

  defp do_download_pdf(report_name, record_id, %{session_id: session_id, csrf_token: csrf_token}) do
    # Odoo web client downloads reports via POST /report/download
    # with data=["/report/pdf/<report_name>/<record_id>", "qweb-pdf"]
    # and a csrf_token field.
    url = "#{Config.base_url()}/report/download"

    data = Jason.encode!(["/report/pdf/#{report_name}/#{record_id}", "qweb-pdf"])

    form_body =
      URI.encode_query(%{
        "data" => data,
        "csrf_token" => csrf_token
      })

    Logger.info(
      "Odoo Session: Downloading PDF via POST /report/download " <>
        "report=#{report_name} id=#{record_id}"
    )

    case Req.new(
           url: url,
           method: :post,
           headers: [
             {"cookie", "session_id=#{session_id}"},
             {"content-type", "application/x-www-form-urlencoded"},
             {"user-agent", "Mozilla/5.0 (X11; Linux x86_64) Center/1.0"},
             {"referer", "#{Config.base_url()}/web"},
             {"origin", Config.base_url()}
           ],
           body: form_body,
           receive_timeout: 120_000,
           connect_options: [timeout: 15_000],
           retry: false,
           redirect: false,
           decode_body: false
         )
         |> Req.request() do
      {:ok, %Req.Response{status: 200, headers: resp_headers, body: body}} ->
        content_type = get_header_values(resp_headers, "content-type") |> List.first() || ""

        cond do
          byte_size(body) == 0 ->
            Logger.error("Odoo Session: Empty response for #{report_name}/#{record_id}")
            {:error, :empty_response}

          pdf_binary?(body) ->
            Logger.info(
              "Odoo Session: Downloaded PDF size=#{byte_size(body)} " <>
                "from #{report_name}/#{record_id}"
            )

            {:ok, body}

          String.contains?(content_type, "application/pdf") ->
            # Trust content-type even if magic bytes are odd
            Logger.info(
              "Odoo Session: Downloaded PDF (by content-type) size=#{byte_size(body)} " <>
                "from #{report_name}/#{record_id}"
            )

            {:ok, body}

          html_body?(body) ->
            # Check if this is an Odoo error response (JSON wrapped in HTML)
            case try_parse_odoo_error(body) do
              {:ok, error_msg} ->
                Logger.error("Odoo Session: Odoo error for #{report_name}/#{record_id}: #{error_msg}")

                {:error, %{status: 500, message: error_msg}}

              :not_error ->
                Logger.warning(
                  "Odoo Session: Got HTML instead of PDF for #{report_name}/#{record_id} " <>
                    "(session likely expired)"
                )

                {:error, :session_expired}
            end

          true ->
            Logger.error(
              "Odoo Session: Unknown response type for #{report_name}/#{record_id} " <>
                "content-type=#{content_type} size=#{byte_size(body)} " <>
                "first_bytes=#{inspect(binary_part(body, 0, min(byte_size(body), 20)))}"
            )

            {:error, :not_pdf}
        end

      {:ok, %Req.Response{status: 400, body: body}} ->
        body_str = if is_binary(body), do: body, else: ""

        if String.contains?(body_str, "CSRF") or String.contains?(body_str, "Session expired") do
          Logger.warning(
            "Odoo Session: CSRF token rejected for #{report_name}/#{record_id} " <>
              "(session expired)"
          )

          {:error, :session_expired}
        else
          Logger.error("Odoo Session: HTTP 400 for #{report_name}/#{record_id}: #{String.slice(body_str, 0, 300)}")

          {:error, %{status: 400, message: "Bad request"}}
        end

      {:ok, %Req.Response{status: status}} when status in [301, 302, 303, 307, 308] ->
        Logger.warning(
          "Odoo Session: Got #{status} redirect for #{report_name}/#{record_id} " <>
            "(session expired)"
        )

        {:error, :session_expired}

      {:ok, %Req.Response{status: 401}} ->
        Logger.warning("Odoo Session: Got 401 for #{report_name}/#{record_id}")
        {:error, :session_expired}

      {:ok, %Req.Response{status: 403}} ->
        Logger.warning("Odoo Session: Got 403 for #{report_name}/#{record_id} (session expired or CSRF invalid)")

        {:error, :session_expired}

      {:ok, %Req.Response{status: 404, body: body}} ->
        body_preview = if is_binary(body), do: String.slice(body, 0, 300), else: ""
        Logger.error("Odoo Session: Got 404 for #{report_name}/#{record_id}: #{body_preview}")
        {:error, %{status: 404, message: "Report or record not found"}}

      {:ok, %Req.Response{status: 500, body: body}} ->
        body_preview = if is_binary(body), do: String.slice(body, 0, 500), else: ""
        Logger.error("Odoo Session: Got 500 for #{report_name}/#{record_id}: #{body_preview}")
        {:error, %{status: 500, message: "Odoo server error"}}

      {:ok, %Req.Response{status: status, body: body}} ->
        body_preview = if is_binary(body), do: String.slice(body, 0, 300), else: ""

        Logger.error("Odoo Session: HTTP #{status} for #{report_name}/#{record_id}: #{body_preview}")

        {:error, %{status: status, message: "HTTP #{status}"}}

      {:error, %Req.TransportError{reason: reason}} ->
        Logger.error("Odoo Session: Transport error downloading PDF #{report_name}/#{record_id}: #{inspect(reason)}")

        {:error, :network_error}

      {:error, exception} ->
        Logger.error("Odoo Session: Network error downloading PDF #{report_name}/#{record_id}: #{inspect(exception)}")

        {:error, :network_error}
    end
  end

  # ── ETS Cache ──────────────────────────────────────────────────────

  defp read_cached_session do
    case :ets.lookup(@ets_table, @session_key) do
      [{@session_key, session_data, cached_at}] ->
        age_ms = System.monotonic_time(:millisecond) - cached_at

        if age_ms < @session_ttl_ms do
          {:ok, session_data}
        else
          Logger.info("Odoo Session: Cached session expired (age=#{div(age_ms, 1000)}s)")
          clear_cached_session()
          :miss
        end

      [] ->
        :miss
    end
  rescue
    ArgumentError ->
      :miss
  end

  defp cache_session(session_data) do
    :ets.insert(@ets_table, {@session_key, session_data, System.monotonic_time(:millisecond)})
  end

  defp clear_cached_session do
    :ets.delete(@ets_table, @session_key)
  rescue
    ArgumentError -> :ok
  end

  # ── Helpers ────────────────────────────────────────────────────────

  defp pdf_binary?(<<0x25, 0x50, 0x44, 0x46, _rest::binary>>), do: true
  defp pdf_binary?(_), do: false

  defp html_body?(body) when is_binary(body) do
    lower = String.downcase(String.slice(body, 0, 200))
    String.contains?(lower, "<html") or String.contains?(lower, "<!doctype")
  end

  defp html_body?(_), do: false

  defp try_parse_odoo_error(body) when is_binary(body) do
    # Odoo sometimes wraps JSON error responses in HTML.
    # The /report/download endpoint returns errors as JSON inside the HTML body.
    case Jason.decode(body) do
      {:ok, %{"code" => _, "message" => message}} ->
        {:ok, message}

      {:ok, %{"error" => %{"data" => %{"message" => message}}}} ->
        {:ok, message}

      _ ->
        :not_error
    end
  rescue
    _ -> :not_error
  end

  defp try_parse_odoo_error(_), do: :not_error

  defp extract_odoo_error(error) when is_map(error) do
    data_msg = get_in(error, ["data", "message"])
    data_name = get_in(error, ["data", "name"])
    top_msg = error["message"]

    cond do
      data_msg && data_name -> "#{data_name}: #{data_msg}"
      data_msg -> data_msg
      top_msg -> top_msg
      true -> inspect(error)
    end
  end

  defp extract_odoo_error(error), do: inspect(error)
end
