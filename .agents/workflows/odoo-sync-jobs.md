---
description: How to process and retry Odoo Sync Jobs dynamically
---

# Menjalankan Sync & Job Odoo

Semua komunikasi (PUSH) ke Odoo berjalan asinkron via Laravel Queue (Horizon).

1. **Pengecekan Job Gagal**
// turbo
php artisan queue:failed

2. **Retry Job Tertentu**
// Menjalankan retry menggunakan UUID dari failed_jobs
php artisan queue:retry {id}

3. **Mengecek Horizon Status**
// turbo
php artisan horizon:status

Catatan:
Jika Job terus gagal (state `:failed`), sistem menawarkan tombol "Duplikasi & Bersihkan" di UI. Dokumen lama akan di-soft-delete dan payload baru akan dikirim.
