defmodule Center.Integration.Odoo.Config do
  @moduledoc """
  Reads Odoo connection configuration from application env.

  Expected config:

      config :center, Center.Integration.Odoo,
        url: "https://mycompany.odoo.com",
        database: "mycompany",
        api_key: "secret",
        login: "admin",
        password: "admin"

  - `url`, `database`, `api_key` — used for JSON-RPC API calls (CRUD operations)
  - `login`, `password` — used for web session authentication, required for
    PDF report downloads via Odoo's `/report/pdf/` endpoint (which only accepts
    browser-style session cookies, not API key auth)
  """

  @doc "Returns the base URL for the Odoo instance."
  def base_url do
    config() |> Keyword.fetch!(:url) |> String.trim_trailing("/")
  end

  @doc "Returns the Odoo database name."
  def database do
    config() |> Keyword.fetch!(:database)
  end

  @doc "Returns the Odoo API key for Bearer auth (used by JSON-RPC CRUD operations)."
  def api_key do
    config() |> Keyword.fetch!(:api_key)
  end

  @doc """
  Returns the Odoo login (username/email) for session-based auth.

  Falls back to an empty string if not configured.
  """
  def login do
    config()[:login] || ""
  end

  @doc """
  Returns the Odoo password for session-based auth.
  """
  def password do
    config()[:password] || ""
  end

  @doc "Returns true if Odoo integration is configured (api_key present)."
  def configured? do
    case config()[:api_key] do
      nil -> false
      "" -> false
      _ -> true
    end
  end

  @doc "Returns true if session-based auth credentials are available."
  def session_auth_configured? do
    login_val = login()
    password_val = password()

    login_val != "" and password_val != ""
  end

  defp config do
    Application.get_env(:center, Center.Integration.Odoo, [])
  end
end
