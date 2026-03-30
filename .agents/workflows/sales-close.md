---
description: Workflow for Penjualan Tertutup (Direct Sale) without project elements
---

# Penjualan Tertutup (Close) Workflow

Untuk melakukan transaksi penjualan langsung (tanpa survey, tanpa teknisi), ikuti langkah-langkah berikut secara berurutan:

1. **Create Intent**: CSSR membuat instansiasi `SalesIntent` dan memilih `sales_type = close`.
2. **Isi Form Penjualan**: Sistem menampilkan form lengkap (Customer Name, Addresses, Shipping Date, Price List, dan Order Lines). CSSR melengkapi form.
3. **Save Draft (Sync ke Odoo)**: Data disubmit dan masuk ke Job `OdooSyncJob`. Draft Sale Order di Odoo terbentuk dan nomor SO didapatkan.
4. **Confirm Order**: CSSR melakukan action_confirm pada SO. State berubah menjadi `sale` dan Payment Action panel muncul.
5. **Create Invoice**: Pilih jenis penagihan (Regular Invoice, DP Persentase, atau DP Jumlah Tetap). Lakukan `confirm_invoice` lalu `action_post` di Odoo.
6. **Register Payment**: Isi jurnal pembayaran, jumlah (`amount`), tanggal (`payment_date`), dan memo. Jalankan `validate_payment`.
7. **Mark As Done**: Jika payment status di Odoo bernilai `paid` (lunas), sistem akan merekomendasikan `mark_as_done`. Jika belum, arahkan kembali ke pembuatan invoice pelunasan.
