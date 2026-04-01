defmodule Center.Integration.Odoo.Reports do
  @moduledoc """
  Proxy for Odoo QWeb PDF reports.

  ## How It Works

  This module downloads PDF reports from Odoo using **web session authentication**.

  The `/report/pdf/` endpoint is a web controller that requires a valid browser
  session cookie. We authenticate via `POST /web/session/authenticate` to obtain
  a `session_id` cookie, then `GET /report/pdf/<report_name>/<record_id>` with
  that cookie. Session cookies are cached and automatically refreshed by
  `Center.Integration.Odoo.Session`.

  This is the same mechanism used when you manually log in to Odoo and click
  "Print" — it's the most reliable way to generate PDFs.

  ## Report Names

  ### Sale Orders
  - Quotation (draft):      `sale.report_saleorder`
  - Confirmed order:        `sale.report_saleorder_raw`

  ### Invoices
  - Unpaid invoice:         `account.report_invoice`
  - Paid (with payments):   `account.report_invoice_with_payments`

  ## Configuration

  Required env vars:
  - `ODOO_URL`      — Odoo base URL (e.g. https://mycompany.odoo.com)
  - `ODOO_DATABASE` — Odoo database name
  - `ODOO_LOGIN`    — Odoo username/email for web login
  - `ODOO_PASSWORD` — Odoo password for web login
  """

  require Logger

  alias Center.Integration.Odoo.Config
  alias Center.Integration.Odoo.Session

  # ── Report Name Mapping ────────────────────────────────────────────

  @report_names %{
    "quotation" => "sale.report_saleorder",
    "order" => "sale.report_saleorder_raw",
    "invoice_unpaid" => "account.report_invoice",
    "invoice_paid" => "account.report_invoice_with_payments"
  }

  @doc """
  Returns the Odoo report technical name for a given document type.

  ## Types
  - `"quotation"` — Quotation / Penawaran (draft sale order)
  - `"order"` — Confirmed Sale Order
  - `"invoice_unpaid"` — Invoice without payment info
  - `"invoice_paid"` — Invoice with payment info

  ## Examples

      iex> Center.Integration.Odoo.Reports.report_name("quotation")
      {:ok, "sale.report_saleorder"}

      iex> Center.Integration.Odoo.Reports.report_name("order")
      {:ok, "sale.report_saleorder_raw"}
  """
  def report_name(type) when is_map_key(@report_names, type) do
    {:ok, Map.fetch!(@report_names, type)}
  end

  def report_name(_type), do: {:error, :invalid_type}

  # ── Public API ──────────────────────────────────────────────────────

  @doc """
  Downloads a PDF report from Odoo into memory.

  Uses web session authentication via `/report/pdf/` endpoint.
  Returns `{:ok, pdf_binary}` or `{:error, reason}`.

  ## Examples

      iex> Center.Integration.Odoo.Reports.download_pdf("quotation", 1611)
      {:ok, <<37, 80, 68, 70, ...>>}
  """
  def download_pdf(type, odoo_id) when is_integer(odoo_id) do
    with {:ok, rname} <- report_name(type),
         :ok <- check_config() do
      Logger.info("Odoo Reports: Downloading PDF type=#{type} id=#{odoo_id} report=#{rname}")

      case Session.download_report_pdf(rname, odoo_id) do
        {:ok, pdf_binary} ->
          Logger.info("Odoo Reports: Downloaded PDF type=#{type} id=#{odoo_id} size=#{byte_size(pdf_binary)}")

          {:ok, pdf_binary}

        {:error, reason} = error ->
          Logger.error("Odoo Reports: Failed to download PDF type=#{type} id=#{odoo_id}: #{inspect(reason)}")

          error
      end
    end
  end

  @doc """
  Streams a PDF report from Odoo directly to the Plug connection.

  This function:
  1. Downloads the PDF using web session authentication
  2. Sends it as a response with proper PDF headers

  Returns `{:ok, conn}` on success or `{:error, conn, reason}` on failure.

  ## Parameters
  - `conn` — the Plug connection
  - `type` — one of: `"quotation"`, `"order"`, `"invoice_unpaid"`, `"invoice_paid"`
  - `odoo_id` — the Odoo record ID (integer)
  - `filename` — the filename for the Content-Disposition header

  ## Examples

      stream_pdf_to_client(conn, "quotation", 1611, "Penawaran-SO001.pdf")
  """
  def stream_pdf_to_client(conn, type, odoo_id, filename)
      when is_integer(odoo_id) and is_binary(filename) do
    case download_pdf(type, odoo_id) do
      {:ok, pdf_binary} ->
        Logger.info(
          "Odoo Reports: Sending PDF to client type=#{type} id=#{odoo_id} " <>
            "filename=#{filename} size=#{byte_size(pdf_binary)}"
        )

        conn =
          conn
          |> Plug.Conn.put_resp_content_type("application/pdf")
          |> Plug.Conn.put_resp_header(
            "content-disposition",
            ~s(attachment; filename="#{filename}")
          )
          |> Plug.Conn.put_resp_header(
            "content-length",
            Integer.to_string(byte_size(pdf_binary))
          )
          |> Plug.Conn.put_resp_header(
            "cache-control",
            "no-store, no-cache, must-revalidate"
          )
          |> Plug.Conn.put_resp_header("x-content-type-options", "nosniff")
          |> Plug.Conn.send_resp(200, pdf_binary)

        {:ok, conn}

      {:error, reason} ->
        Logger.error(
          "Odoo Reports: Failed to get PDF for streaming type=#{type} " <>
            "id=#{odoo_id}: #{inspect(reason)}"
        )

        {:error, conn, reason}
    end
  end

  @doc "Downloads a quotation PDF (draft sale order)."
  def download_sale_order_pdf(odoo_id) when is_integer(odoo_id) do
    download_pdf("quotation", odoo_id)
  end

  @doc "Downloads a confirmed sale order PDF."
  def download_order_pdf(odoo_id) when is_integer(odoo_id) do
    download_pdf("order", odoo_id)
  end

  @doc "Downloads an unpaid invoice PDF by invoice odoo_id."
  def download_invoice_pdf(odoo_id) when is_integer(odoo_id) do
    download_pdf("invoice_unpaid", odoo_id)
  end

  @doc "Downloads a paid invoice PDF (with payment details) by invoice odoo_id."
  def download_invoice_with_payments_pdf(odoo_id) when is_integer(odoo_id) do
    download_pdf("invoice_paid", odoo_id)
  end

  @doc """
  Invalidate cached Odoo session.

  Forces re-authentication on the next PDF download request.
  """
  def invalidate_session do
    if Config.session_auth_configured?() do
      Session.invalidate_session()
    else
      :ok
    end
  end

  # ── Config Check ───────────────────────────────────────────────────

  defp check_config do
    if Config.session_auth_configured?() do
      :ok
    else
      Logger.error("""
      Odoo Reports: Session authentication not configured!

      PDF report downloads require ODOO_LOGIN + ODOO_PASSWORD to be set.
      These credentials are used to log in to Odoo's web interface and
      access the /report/pdf/ endpoint (the same way a browser does).

      Please set the appropriate environment variables.
      """)

      {:error, :session_not_configured}
    end
  end
end
