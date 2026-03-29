# Pevesindo POP – Pusat Order Penjualan

Aplikasi web manajemen order penjualan untuk Pevesindo.

## Fitur

- **Dashboard** – ringkasan statistik produk, pelanggan, dan order
- **Produk** – kelola data produk (tambah, edit, hapus, cari)
- **Pelanggan** – kelola data pelanggan (tambah, edit, hapus, cari)
- **Order Penjualan** – buat dan kelola order dengan multi-item produk, diskon per item, dan update status (Draft → Confirmed → Selesai / Batal)
- **Laporan** – laporan penjualan bulanan per order dan per produk

## Teknologi

- **Backend**: Python 3 + Flask + Flask-SQLAlchemy
- **Database**: SQLite (default) — dapat diganti via environment variable `DATABASE_URL`
- **Frontend**: Bootstrap 5 + Bootstrap Icons

## Instalasi

```bash
# 1. Clone repositori
git clone https://github.com/swiftalker/pevesindo_pop.git
cd pevesindo_pop

# 2. Buat virtual environment
python -m venv venv
source venv/bin/activate   # Windows: venv\Scripts\activate

# 3. Instal dependensi
pip install -r requirements.txt

# 4. Jalankan aplikasi
python app.py
```

Buka browser di http://localhost:5000

## Konfigurasi

| Environment Variable | Default | Keterangan |
|---|---|---|
| `SECRET_KEY` | `pevesindo-pop-secret-key` | Secret key Flask session |
| `DATABASE_URL` | `sqlite:///pevesindo_pop.db` | URL koneksi database |

## Struktur Proyek

```
pevesindo_pop/
├── app.py                  # Aplikasi utama Flask
├── requirements.txt        # Dependensi Python
├── static/
│   ├── css/style.css       # Custom CSS
│   └── js/app.js           # Custom JavaScript
└── templates/
    ├── base.html            # Layout dasar
    ├── dashboard.html       # Halaman dashboard
    ├── laporan.html         # Halaman laporan
    ├── produk/
    │   ├── list.html        # Daftar produk
    │   └── form.html        # Form tambah/edit produk
    ├── pelanggan/
    │   ├── list.html        # Daftar pelanggan
    │   └── form.html        # Form tambah/edit pelanggan
    └── order/
        ├── list.html        # Daftar order
        ├── form.html        # Form buat/edit order
        └── detail.html      # Detail order
```