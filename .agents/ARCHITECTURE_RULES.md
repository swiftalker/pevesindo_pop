# **7. System Stack - Laravel**

**ℹ️ Catatan:** Baseline ini adalah adaptasi dari stack Elixir/Phoenix/Ash ke Laravel. Semua fungsi domain bisnis dipertahankan sepenuhnya; hanya implementasi teknisnya yang berbeda.

| **Komponen**                | **Package / Tool**                                     | **Keterangan**                                                           |
| --------------------------- | ------------------------------------------------------ | ------------------------------------------------------------------------ |
| **Runtime**                 | PHP 8.3+                                               | Minimum PHP 8.2, disarankan 8.3                                          |
| **Framework**               | Laravel 12                                             | Framework utama                                                          |
| **Admin Panel**             | FilamentPHP 3                                          | Admin & backoffice panel (pengganti ash_admin)                           |
| **Reactive UI**             | Livewire 3                                             | Reactive components (pengganti Phoenix LiveView)                         |
| **ORM**                     | Eloquent ORM (bawaan Laravel)                          | Pengganti Ash Framework data layer                                       |
| **Database**                | PostgreSQL 15+                                         | Sama persis dengan baseline original                                     |
| **State Machine**           | spatie/laravel-model-states                            | Pengganti ash_state_machine. State lifecycle dokumen.                    |
| **Audit Trail**             | owen-it/laravel-auditing                               | Pengganti ash_paper_trail. Paper trail otomatis.                         |
| **Soft Delete**             | SoftDeletes (bawaan Laravel)                           | Pengganti ash_archival. Soft-delete berbasis deleted_at.                 |
| **Role & Permission**       | spatie/laravel-permission                              | Role-based access control untuk seluruh hirarki.                         |
| **Background Jobs**         | Laravel Queue + Horizon                                | Pengganti Oban. Job queue untuk semua operasi Odoo.                      |
| **Queue Dashboard**         | Laravel Horizon UI                                     | Pengganti oban_web. Dashboard monitoring queue.                          |
| **Realtime WebSocket**      | Laravel Reverb (self-hosted)                           | Pengganti Phoenix PubSub. WebSocket server gratis.                       |
| **Broadcast Client**        | Laravel Echo                                           | JavaScript client untuk subscribe ke channel realtime.                   |
| **Domain Events**           | Laravel Events & Listeners (bawaan)                    | Pengganti ash_events. Event-driven architecture.                         |
| **Auth**                    | Laravel Fortify / Breeze                               | Pengganti ash_authentication + ash_authentication_phoenix.               |
| **Multi-Tenancy (Company)** | spatie/laravel-multitenancy atau Eloquent Global Scope | Isolasi data per company_id.                                             |
| **Money / Currency**        | brick/money atau casts decimal                         | Pengganti ash_money.                                                     |
| **CSV Export**              | maatwebsite/excel atau spatie/simple-excel             | Pengganti ash_csv.                                                       |
| **Odoo Integration**        | Custom Service Class (XML-RPC / JSON-RPC)              | HTTP client bawaan Laravel (Http facade). Tidak perlu library tambahan.  |
| **Cache**                   | Laravel Cache (Redis)                                  | Cache data referensi Odoo (produk, pricelist, jurnal). TTL configurable. |

---

# **9. Realtime & State Model**

## **9.1 Sync State Model**

Semua operasi bersifat realtime via Livewire. Setiap aksi user langsung mengubah local state (optimistic), kemudian antrian sync ke Odoo dijalankan di background via Laravel Queue.

| **State**      | **Badge**     | **Keterangan**                                     |
| -------------- | ------------- | -------------------------------------------------- |
| :local_draft   | Abu-abu       | Dibuat di Center-App, belum pernah dikirim ke Odoo |
| :syncing       | Biru berputar | Job Queue aktif, request sedang dikirim            |
| :pending_retry | Kuning        | Request gagal, menunggu retry berikutnya           |
| :dirty         | Oranye        | Data lokal berbeda dengan yang diketahui Odoo      |
| :ok            | Hijau         | Odoo telah mengkonfirmasi, data valid dan sinkron  |
| :failed        | Merah         | Semua retry habis, perlu intervensi manual         |

## **9.2 Queue System (Laravel Horizon)**

Setiap operasi ke Odoo dijalankan sebagai Laravel Job. Worker utama: OdooSyncJob.

| **Queue Priority** | **Operasi**                                            |
| ------------------ | ------------------------------------------------------ |
| critical           | Konfirmasi SO, konfirmasi invoice, validasi pembayaran |
| default            | Create/update SO draft, create invoice draft           |
| low                | Sync report, pull data referensi                       |

| **Retry Policy** | **Konfigurasi Laravel**                                                                       |
| ---------------- | --------------------------------------------------------------------------------------------- |
| Max attempts     | $tries = 20 pada Job class                                                                   |
| Backoff strategy | public function backoff(): array { return [2, 4, 8, 16, ...]; } - eksponensial              |
| Job lifecycle    | queued → processing → processed / released / failed                                           |
| Failed jobs      | Tersimpan di tabel failed_jobs. User dapat retry manual atau clone via Duplikasi & Bersihkan. |

**⚙️ Duplikasi & Bersihkan:** Dokumen :failed dapat di-clone ke dokumen baru dengan payload yang telah dibersihkan. Dokumen lama di-soft-delete (deleted_at). Dokumen baru memulai siklus sync dari awal.

---

# **12. Audit & Traceability**

## **12.1 Paper Trail (owen-it/laravel-auditing)**

Setiap perubahan pada resource penting dicatat secara otomatis: siapa yang mengubah (user_id), kapan (timestamp), nilai sebelum dan sesudah, serta aksi yang dilakukan.

- Resource wajib: SaleOrder, Invoice, Payment, Project, ProjectTask, SurveyReport, RAB, Expense
- Implementasi: Tambahkan interface Auditable dan use AuditableTrait pada setiap model
- Data disimpan di tabel audits bawaan package

## **12.2 Domain Events (Laravel Events)**

| **Event Class**     | **Trigger**                                   |
| ------------------- | --------------------------------------------- |
| SaleOrderConfirmed  | Saat action_confirm berhasil di Odoo          |
| InvoicePosted       | Saat action_post invoice berhasil             |
| PaymentValidated    | Saat pembayaran dikonfirmasi                  |
| SurveyAssigned      | Saat survey task di-assign ke Surveyor        |
| RABApproved         | Saat customer menyetujui RAB                  |
| ProjectTaskAssigned | Saat task di-assign ke Teknisi                |
| OdooSyncSucceeded   | Saat Job Odoo berhasil                        |
| OdooSyncFailed      | Saat Job Odoo gagal setelah semua retry habis |

Event-event ini menjadi sumber trigger untuk: Broadcasting (Reverb) → Livewire, OdooSyncJob berikutnya jika ada dependency, dan audit log.

## **12.3 Soft Delete / Archival**

- Dokumen yang diarsipkan tidak dihapus dari database - soft-delete berbasis deleted_at timestamp (SoftDeletes trait bawaan Laravel)
- Dokumen :failed yang digantikan duplikat baru di-soft-delete secara otomatis
- Data arsip hanya dapat di-restore oleh role Head ke atas (dikontrol via Spatie Permission)

---

# **13. Multi-Company Context**

Setiap user terikat ke satu company_id default berdasarkan data employee. Seluruh domain mewarisi company_id dari user yang login.

## **13.1 Implementasi**

- Gunakan Eloquent Global Scope CompanyScope yang otomatis memfilter query berdasarkan Auth::user()->company_id
- Atau gunakan package spatie/laravel-multitenancy dengan single-database strategy
- Tidak ada transaksi lintas perusahaan tanpa explicit override dari Head ke atas
- Jurnal, akun, dan analitik di-resolve otomatis berdasarkan company_id aktif via CompanyConfigResolver service class

## **13.2 Resolusi Otomatis Per Perusahaan**

| **Company**         | **Jurnal Jual** | **Jurnal Bank** | **Jurnal Kas** | **Analitik** |
| ------------------- | --------------- | --------------- | -------------- | ------------ |
| Pevesindo Godean    | INV2            | OCBC2           | CSH2           | Godean       |
| Pevesindo Wonosari  | INV3            | OCBC3           | CSH3           | Wonosari     |
| Pevesindo Franchise | INV1            | OCBC1           | CSH1           | Franchise    |
