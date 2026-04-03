# **4. Mekanisme Penjualan**

| **Jenis**                           | **Karakteristik**                                                                          | **Contoh**                                                |
| ----------------------------------- | ------------------------------------------------------------------------------------------ | --------------------------------------------------------- |
| **Penjualan Tertutup (Close)**      | Transaksi selesai dalam satu rangkaian, tidak memerlukan retention tambahan                | Penjualan material atau barang langsung                   |
| **Penjualan Terbuka (Open/Proyek)** | Memerlukan retention, melibatkan banyak aksi dan aktivitas, tidak selesai dalam satu waktu | Proyek renovasi: survey → RAB → pengerjaan → serah terima |

---

# **6. Workflow Penjualan**

Penjualan selalu dimulai dengan Sales Intent - CSSR menentukan jenis penjualan sebagai titik masuk seluruh alur.

## **6.1 Penjualan Tertutup (Close)**

| **#** | **Aktor**  | **Aksi**         | **Keterangan**                                                                                           |
| ----- | ---------- | ---------------- | -------------------------------------------------------------------------------------------------------- |
| 1     | **CSSR**   | create_intent    | Pilih sales_type = close                                                                                 |
| 2     | **Sistem** | Tampilkan form   | customer_name, addresses, shipping_date, price_list, order_lines                                         |
| 3     | **CSSR**   | save_draft       | Sync ke Odoo → sale.order draft, nomor SO terbit                                                         |
| 4     | **CSSR**   | confirm_order    | action_confirm pada sale.order, state = sale. Panel payment_action muncul.                               |
| 5     | **CSSR**   | create_invoice   | Pilih: [A] Regular Invoice, [B] DP Persentase, [C] DP Jumlah Tetap → confirm_invoice → action_post |
| 6     | **CSSR**   | register_payment | Isi journal_id, amount, payment_date, memo → validate_payment                                            |
| 7     | **Sistem** | Evaluasi state   | Jika payment_state = paid → rekomendasi mark_as_done. Jika masih ada sisa → arahkan ke Langkah 5         |

## **6.2 Penjualan Terbuka (Open / Proyek)**

| **#** | **Aktor**                 | **Aksi**                                                       | **Keterangan**                                                                                       |
| ----- | ------------------------- | -------------------------------------------------------------- | ---------------------------------------------------------------------------------------------------- |
| 1     | **CSSR**                  | create_intent                                                  | Pilih sales_type = open                                                                              |
| 2     | **Sistem**                | Form minimal                                                   | customer_name, project_address, note, price_list. order_lines belum diisi.                           |
| 3     | **CSSR**                  | save_draft + assign_survey                                     | Draft SO dibuat. Survey task terbit, notifikasi dikirim ke Surveyor.                                 |
| 4     | **Surveyor**              | fulfill_survey_task                                            | Input survey_findings, measurement_data, photo, recommended_products                                 |
| 5     | **Surveyor**              | create_rab                                                     | Buat RAB: rab_lines, rab_total, project_duration_days, technician_needed. submit_rab ke CSSR.        |
| 6     | **CSSR**                  | present_rab_to_customer                                        | [A] Approved → Langkah 7. [B] Revisi → kembali ke Surveyor. [C] Rejected → intent closed_lost. |
| 7     | **CSSR**                  | convert_rab_to_so                                              | rab_lines → order_lines. Project dibuat otomatis di Odoo.                                            |
| 8-11  | **CSSR**                  | save_draft → confirm_order → create_invoice → register_payment | Identik dengan Penjualan Tertutup. Lazim menggunakan DP 50% di awal.                                 |
| 12    | **Surveyor / Kepala Ops** | assign_project_task                                            | Delegasi task ke Teknisi: technician_id, task_name, deadline, project_id                             |
| 13    | **Teknisi Lapangan**      | input_project_progress                                         | progress_percentage, progress_notes, photo_attachment, milestone_status                              |
| 14    | **Teknisi / Surveyor**    | input_project_expense                                          | expense_description, amount, category → ke Finance untuk approval                                    |
| 15    | **Surveyor**              | project_handover                                               | handover_notes, customer_signature, photo_final. Project state → done.                               |
| 16    | **CSSR**                  | final_invoice                                                  | Invoice pelunasan. amount_residual = amount_total SO − total invoice dibayar.                        |
| 17    | **Sistem**                | Evaluasi akhir                                                 | Semua invoice lunas DAN project done → mark_as_done                                                  |
