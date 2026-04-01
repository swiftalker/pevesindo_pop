# **5. Konfigurasi Odoo - Akun, Jurnal & Analitik**

## **5.1 Perusahaan (Multi-Company)**

| **ID** | **Nama**            | **Tipe**                      |
| ------ | ------------------- | ----------------------------- |
| 1      | Pevesindo Franchise | Main Branch                   |
| 2      | Pevesindo Godean    | Branch of Pevesindo Franchise |
| 3      | Pevesindo Wonosari  | Branch of Pevesindo Franchise |

## **5.2 Distribusi Analitik**

Terdapat tiga Analytic Plan yang digunakan:

| **Analytic Plan**                 | **Akun**                       | **Keterangan**                  |
| --------------------------------- | ------------------------------ | ------------------------------- |
| **Plan 1 - Jenis Penjualan**      | Terbuka                        | Penjualan proyek                |
|                                   | Tertutup                          | Penjualan barang langsung      |
|                                   | Internal                          | Penjualan ke toko sendiri      |
| **Plan 2 - Lokasi Cabang**        | Franchise                      | Kantor pusat / franchise        |
|                                   | Godean                            | Cabang Godean, DI Yogyakarta   |
|                                   | Wonosari                          | Cabang Wonosari, DI Yogyakarta |
| **Plan 3 - Kategori Operasional** | Penjualan                      | Aktivitas penjualan ke customer |
|                                   | Pembelian                         | Aktivitas procurement / PO     |
|                                   | Pengeluaran                       | Biaya operasional proyek       |
|                                   | Pengiriman                        | Ongkos kirim / jasa pengiriman |

## **5.3 Resolusi Otomatis Per Perusahaan**

| **Company**         | **Jurnal Penjualan**       | **Jurnal Bank**             | **Jurnal Kas**       |
| ------------------- | -------------------------- | --------------------------- | -------------------- |
| Pevesindo Godean    | Penjualan Godean (INV2)    | Bank OCBC Godean (OCBC2)    | Kas Godean (CSH2)    |
| Pevesindo Wonosari  | Penjualan Wonosari (INV3)  | Bank OCBC Wonosari (OCBC3)  | Kas Wonosari (CSH3)  |
| Pevesindo Franchise | Penjualan Franchise (INV1) | Bank OCBC Franchise (OCBC1) | Kas Franchise (CSH1) |

---

# **10. Odoo API Integration Layer**

## **10.1 Implementasi di Laravel**

Odoo API diakses melalui Service Class terpusat menggunakan Laravel Http facade. Tidak diperlukan library pihak ketiga.

| **Class**                  | **Keterangan**                                                                             |
| -------------------------- | ------------------------------------------------------------------------------------------ |
| App\Services\OdooService | Service class utama untuk semua komunikasi dengan Odoo via XML-RPC/JSON-RPC                |
| App\Jobs\OdooSyncJob     | Job class untuk semua push ke Odoo. Tidak pernah dipanggil langsung dari Livewire handle.  |
| App\Jobs\OdooPullJob     | Job class untuk pull data referensi (produk, pricelist, jurnal). Dijalankan via scheduler. |

## **10.2 Kapabilitas API**

| **Arah**   | **Operasi**                                                                                                                                                                       |
| ---------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **PULL**   | Produk, varian, stok · Customer/partner · Pricelist · Template SO · Jurnal & akun akuntansi · Analytic plan & akun · Status SO, invoice, payment · Data project & task            |
| **PUSH**   | Create/update sale.order (draft→confirmed) · Create/update account.move · Create/update account.payment · Create/update project.project & project.task · Create/update hr.expense |
| **REPORT** | Cetak SO/Quotation · Cetak Invoice · Cetak Delivery Order · Cetak RAB (jika dikonfigurasi di Odoo)                                                                                |

## **10.3 Constraint & Rate Limiter**

| **Aturan**                     | **Implementasi Laravel**                                                       |
| ------------------------------ | ------------------------------------------------------------------------------ |
| Quota: 100.000 req/bulan       | Log setiap request ke tabel odoo_api_logs, increment counter via Cache         |
| Minimum pull interval: 5 menit | Laravel Scheduler dengan frequency()->everyFiveMinutes()                       |
| Cache data referensi           | Cache::remember() dengan TTL configurable (default: 1 jam). Disimpan di Redis. |
| Push hanya via Job             | OdooPushJob dispatch dari Livewire, tidak pernah Http::post() langsung         |

## **10.4 Idempotency**

Setiap job yang push ke Odoo harus idempotent untuk mencegah duplikasi data.

- Setiap payload membawa pop_app_ref sebagai external reference
- Sebelum create record baru, sistem memeriksa apakah pop_app_ref sudah ada di Odoo
- Jika sudah ada → update. Jika belum ada → create baru
- Implementasi: UniqueJobMiddleware pada OdooSyncJob menggunakan job unique key berbasis pop_app_ref
