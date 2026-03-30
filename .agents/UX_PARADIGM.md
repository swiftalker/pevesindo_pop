# **8. UX Paradigm**

## **8.1 Progressive Disclosure**

Field tidak ditampilkan sekaligus. Field muncul secara bertahap mengikuti konteks state yang aktif - setiap tahap hanya menampilkan input yang relevan.

| **Jenis Penjualan**    | **Step Awal**                                            | **Implementasi Laravel**                                            |
| ---------------------- | -------------------------------------------------------- | ------------------------------------------------------------------- |
| **Penjualan Tertutup** | customer_name → addresses → order_lines → konfirmasi     | Livewire component dengan $step property dan conditional rendering |
| **Penjualan Terbuka**  | customer_name + note → assign survey → RAB → order_lines | Livewire wizard component, state dikontrol dari model SalesIntent   |

## **8.2 Single-Page CRUD / Unified Detail View**

Tidak ada route /edit atau /show yang terpisah. Semua aksi terjadi dalam satu halaman yang sama.

| **Route**      | **Behavior**                                      | **Implementasi**             |
| -------------- | ------------------------------------------------- | ---------------------------- |
| /sales/{id}    | View + edit + state transition dalam satu halaman | Livewire full-page component |
| /projects/{id} | View + progress update + task assign              | Livewire full-page component |
| /tasks/{id}    | View + milestone input + expense                  | Livewire full-page component |

## **8.3 Conditional / Dynamic Form Rendering**

UI berubah berdasarkan state aktif dan nilai field tertentu. Diimplementasikan via Livewire reactive properties.

| **Kondisi**             | **Aksi UI**                                           |
| ----------------------- | ----------------------------------------------------- |
| sales_type == 'close'   | Tampilkan full sales form dengan order_lines langsung |
| sales_type == 'open'    | Tampilkan form minimal - customer + note saja         |
| sync_state == 'syncing' | Disable semua action button, tampilkan spinner        |
| sync_state == 'failed'  | Tampilkan alert merah, tombol Kirim Ulang Manual      |

---

# **11. Notifikasi & Auditory Cue System**

## **11.1 Notifikasi In-App (Realtime)**

Berbasis Laravel Reverb (WebSocket) + Laravel Echo + Livewire. Setiap event penting di-broadcast ke channel yang relevan.

| **Pola Channel**                       | **Contoh**                                               |
| -------------------------------------- | -------------------------------------------------------- |
| notifications.company.{id}             | notifications.company.2 → semua user di Godean           |
| notifications.user.{id}                | notifications.user.42 → user spesifik                    |
| notifications.role.{role}.company.{id} | notifications.role.cssr.company.2 → semua CSSR di Godean |

Implementasi: Gunakan Laravel Broadcasting dengan Reverb. Setiap Livewire component subscribe via @script dan window.Echo.channel(). Notifikasi masuk ke tiga kanal: in-app notification bell (Livewire realtime), auditory cue (browser/PWA), dan toast/flash message.

## **11.2 Auditory Cue System**

Berjalan di sisi client (browser/PWA) via Web Audio API atau preloaded audio files.

| **Event**                    | **Bunyi**                  | **Level** |
| ---------------------------- | -------------------------- | --------- |
| SO baru masuk (untuk WO)     | Ping ascending             | :action   |
| Invoice diterbitkan          | Chime pendek 2x            | :info     |
| Pembayaran diterima          | Chime sukses (mayor)       | :action   |
| Delivery Order terbit        | Notif kirim (drum singkat) | :action   |
| Sinkronisasi Odoo berhasil   | Subtle pop                 | :info     |
| Sinkronisasi Odoo gagal      | Tone peringatan (minor)    | :warning  |
| Queue Job menumpuk (>10 job) | Alert berulang lambat      | :warning  |

## **11.3 Notifikasi Berbasis Role**

| **Role**               | **Menerima Notifikasi Dari**                                            |
| ---------------------- | ----------------------------------------------------------------------- |
| **CSSR**               | SO baru, status pembayaran, konfirmasi pengiriman, update stok dari WO  |
| **Surveyor**           | Task survey baru/re-assign, konfirmasi SO proyek, delegasi task teknisi |
| **Teknisi Lapangan**   | Task proyek baru/re-assign, approval pengeluaran proyek                 |
| **Warehouse Operator** | SO confirmed (trigger DO), PO approved, mutasi stok                     |
| **Driver**             | DO di-assign ke dirinya, instruksi pengiriman                           |
| **Kepala Operasional** | Semua event dalam lingkup cabang                                        |
| **Kepala Gudang**      | Semua event WO, Driver, dan Helper                                      |
