defmodule CenterWeb.Admin.ReportController do
  @moduledoc """
  Streaming proxy for Odoo QWeb PDF reports.

  Downloads PDF reports from Odoo and sends them to the browser client.
  Uses web session authentication (via `Center.Integration.Odoo.Session`) to access
  Odoo's `/report/pdf/` endpoint.

  ## Supported Document Types

  | Type        | Odoo Report Name                   | Source ID          |
  |-------------|------------------------------------|--------------------|
  | `quotation` | `sale.report_saleorder`            | Sale Order odoo_id |
  | `order`     | `sale.report_saleorder_raw`        | Sale Order odoo_id |
  | `invoice`   | `account.report_invoice_with_payments` | Invoice odoo_id (looked up) |

  ## Authentication

  Routes are protected by `:require_authenticated_user` and `:require_admin`
  plugs in the router. Only admin users can download reports.

  The Odoo web session is managed by `Center.Integration.Odoo.Session` (cached in ETS).
  """

  use CenterWeb, :controller

  require Logger

  alias Center.Sales
  alias Center.Integration.Odoo.Reports

  @valid_types ~w(quotation order invoice)

  def download(conn, %{"order_id" => order_id, "type" => type}) do
    order = Sales.get_order!(order_id)

    Logger.info(
      "ReportController: Download requested type=#{type} " <>
        "order_id=#{order.id} order_number=#{order.order_number} " <>
        "odoo_id=#{inspect(order.odoo_id)} status=#{order.status} " <>
        "payment_status=#{order.payment_status}"
    )

    with :ok <- validate_type(type),
         :ok <- validate_odoo_synced(order),
         :ok <- validate_status_for_type(order, type) do
      stream_report(conn, order, type)
    else
      {:error, :invalid_type} ->
        Logger.warning("ReportController: Invalid type=#{type} for order ##{order.id}")

        conn
        |> put_flash(:error, "Tipe dokumen \"#{type}\" tidak dikenali.")
        |> redirect(to: ~p"/admin/sales/#{order.id}")

      {:error, :no_odoo_id} ->
        Logger.warning("ReportController: Order ##{order.id} not synced to Odoo yet")

        conn
        |> put_flash(
          :error,
          "Order belum disinkronisasi ke Odoo. Sinkronisasi terlebih dahulu sebelum mencetak."
        )
        |> redirect(to: ~p"/admin/sales/#{order.id}")

      {:error, :wrong_status} ->
        msg = wrong_status_message(order, type)

        Logger.warning("ReportController: Wrong status for type=#{type} order ##{order.id}: #{msg}")

        conn
        |> put_flash(:error, msg)
        |> redirect(to: ~p"/admin/sales/#{order.id}")
    end
  end

  # ── Streaming ──────────────────────────────────────────────────────

  defp stream_report(conn, order, "quotation") do
    filename = build_filename(order, "quotation")

    case Reports.stream_pdf_to_client(conn, "quotation", order.odoo_id, filename) do
      {:ok, conn} -> conn
      {:error, conn, reason} -> handle_stream_error(conn, order, "quotation", reason)
    end
  end

  defp stream_report(conn, order, "order") do
    filename = build_filename(order, "order")

    case Reports.stream_pdf_to_client(conn, "order", order.odoo_id, filename) do
      {:ok, conn} -> conn
      {:error, conn, reason} -> handle_stream_error(conn, order, "order", reason)
    end
  end

  defp stream_report(conn, order, "invoice") do
    # Support per-invoice print via ?invoice_id=N query param.
    # Falls back to order.odoo_invoice_id for backward compatibility.
    invoice_odoo_id =
      case conn.query_params["invoice_id"] do
        nil ->
          order.odoo_invoice_id

        inv_id ->
          case Sales.get_invoice!(String.to_integer(inv_id)) do
            %{odoo_invoice_id: id} when not is_nil(id) -> id
            _ -> order.odoo_invoice_id
          end
      end

    case invoice_odoo_id do
      nil ->
        Logger.warning("ReportController: No invoice ID for order ##{order.id} (#{order.order_number})")

        conn
        |> put_flash(
          :error,
          "Invoice belum tersedia untuk order #{order.order_number}. " <>
            "Pastikan invoice sudah dibuat dan disinkronisasi dari Odoo."
        )
        |> redirect(to: ~p"/admin/sales/#{order.id}")

      odoo_id ->
        report_type =
          if order.payment_status == "paid",
            do: "invoice_paid",
            else: "invoice_unpaid"

        filename = build_filename(order, "invoice")

        case Reports.stream_pdf_to_client(conn, report_type, odoo_id, filename) do
          {:ok, conn} -> conn
          {:error, conn, reason} -> handle_stream_error(conn, order, "invoice", reason)
        end
    end
  end

  # ── Error Handling ─────────────────────────────────────────────────

  # When streaming has already started (chunked response sent), we can't
  # redirect — the connection is committed. We just log and halt.
  # When streaming hasn't started yet, we redirect with a flash message.
  defp handle_stream_error(conn, order, type, reason) do
    if conn.state == :chunked do
      # Response already started streaming — can't redirect.
      # The client will see a truncated/corrupt PDF download.
      Logger.error(
        "ReportController: Stream failed mid-transfer for type=#{type} " <>
          "order ##{order.id}: #{inspect(reason)}"
      )

      conn
    else
      handle_redirect_error(conn, order, type, reason)
    end
  end

  defp handle_redirect_error(conn, order, type, reason) do
    Logger.error("ReportController: Error for type=#{type} order ##{order.id}: #{inspect(reason)}")

    {flash_msg, _} = error_message(reason, order, type)

    conn
    |> put_flash(:error, flash_msg)
    |> redirect(to: ~p"/admin/sales/#{order.id}")
  end

  defp error_message(:session_expired, _order, _type) do
    {"Sesi Odoo telah kedaluwarsa. Silakan coba lagi — " <>
       "sistem akan otomatis login ulang ke Odoo.", 401}
  end

  defp error_message(:auth_failed, _order, _type) do
    if Center.Integration.Odoo.Config.session_auth_configured?() do
      {"Gagal autentikasi ke Odoo untuk cetak PDF. " <>
         "Session login ke Odoo gagal — periksa ODOO_LOGIN dan ODOO_PASSWORD, " <>
         "dan pastikan user tersebut bisa login manual di Odoo.", 401}
    else
      {"Gagal autentikasi ke Odoo untuk cetak PDF. " <>
         "Set ODOO_LOGIN dan ODOO_PASSWORD di environment — " <>
         "ini diperlukan agar server bisa login ke Odoo dan mengakses /report/pdf/.", 401}
    end
  end

  defp error_message(:session_not_configured, _order, _type) do
    {"ODOO_LOGIN dan ODOO_PASSWORD belum dikonfigurasi. " <>
       "Hubungi administrator untuk mengatur kredensial login Odoo " <>
       "(diperlukan untuk cetak PDF via web session).", 503}
  end

  defp error_message(:no_invoice, order, _type) do
    {"Invoice belum tersedia di Odoo untuk order #{order.order_number}. " <>
       "Pastikan invoice sudah dibuat di Odoo.", 404}
  end

  defp error_message(:empty_response, _order, _type) do
    {"Odoo mengembalikan dokumen kosong. Coba lagi nanti.", 502}
  end

  defp error_message(:not_pdf, _order, _type) do
    {"Odoo tidak mengembalikan file PDF yang valid. Coba akses laporan langsung dari Odoo.", 502}
  end

  defp error_message(:network_error, _order, _type) do
    {"Tidak dapat terhubung ke server Odoo. Periksa koneksi jaringan.", 502}
  end

  defp error_message(%{status: 404, message: msg}, _order, _type) do
    {"Laporan atau record tidak ditemukan di Odoo. #{msg}", 404}
  end

  defp error_message(%{status: 500, message: msg}, _order, _type) do
    {"Gagal mengunduh dokumen dari Odoo (HTTP 500). #{String.slice(to_string(msg), 0, 200)}", 500}
  end

  defp error_message(%{status: status, message: msg}, _order, _type) do
    detail =
      if msg && msg != "" do
        " Detail: #{String.slice(to_string(msg), 0, 200)}"
      else
        ""
      end

    {"Gagal mengunduh dokumen dari Odoo (HTTP #{status}).#{detail}", status}
  end

  defp error_message(%{status: status}, _order, _type) do
    {"Gagal mengunduh dokumen dari Odoo (HTTP #{status}). Coba lagi nanti.", status}
  end

  defp error_message(reason, _order, _type) do
    Logger.error("ReportController: Unhandled error reason: #{inspect(reason)}")
    {"Gagal mengunduh dokumen dari Odoo.", 500}
  end

  # ── Validations ────────────────────────────────────────────────────

  defp validate_type(type) when type in @valid_types, do: :ok
  defp validate_type(_), do: {:error, :invalid_type}

  defp validate_odoo_synced(%{odoo_id: nil}), do: {:error, :no_odoo_id}
  defp validate_odoo_synced(%{odoo_id: id}) when is_integer(id), do: :ok

  defp validate_status_for_type(%{status: "draft"}, "quotation"), do: :ok
  defp validate_status_for_type(%{status: s}, "order") when s in ["confirmed", "done"], do: :ok
  defp validate_status_for_type(%{payment_status: "paid"}, "invoice"), do: :ok

  # Be lenient: allow downloading if Odoo has the data even if local status
  # doesn't match perfectly — the user may know better.
  defp validate_status_for_type(_order, _type), do: :ok

  # ── Filename Builder ───────────────────────────────────────────────

  defp build_filename(order, type) do
    name =
      (order.order_number || "order")
      |> String.replace("/", "-")
      |> String.replace(" ", "_")

    case type do
      "quotation" -> "Penawaran-#{name}.pdf"
      "order" -> "Order-#{name}.pdf"
      "invoice" -> "Invoice-#{name}.pdf"
      _ -> "#{name}.pdf"
    end
  end

  # ── Status Messages ────────────────────────────────────────────────

  defp wrong_status_message(order, "quotation") do
    "Penawaran hanya dapat dicetak saat order berstatus Draft. " <>
      "Order #{order.order_number} saat ini berstatus #{order.status}."
  end

  defp wrong_status_message(order, "order") do
    "Order Penjualan hanya dapat dicetak setelah dikonfirmasi. " <>
      "Order #{order.order_number} saat ini berstatus #{order.status}."
  end

  defp wrong_status_message(order, "invoice") do
    "Invoice hanya dapat dicetak setelah pembayaran lunas. " <>
      "Order #{order.order_number} saat ini berstatus pembayaran: #{order.payment_status}."
  end

  defp wrong_status_message(order, _type) do
    "Dokumen tidak dapat dicetak untuk order #{order.order_number} dengan status saat ini."
  end
end
