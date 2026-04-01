# **1. Latar Belakang & Tujuan**

Pop-App dibangun sebagai middleware antara pengguna lapangan/operasional harian dan Odoo sebagai ERP inti. Keputusan ini muncul dari tiga masalah nyata yang dihadapi ketika menggunakan Odoo secara langsung:

### **Masalah Biaya yang Tidak Proporsional**

Odoo menerapkan model vendor lock-in berbasis per-user. Menambah satu pengguna berarti membayar biaya lisensi baru. Untuk perusahaan rintisan dengan banyak cabang dan banyak staf lapangan, ini tidak masuk akal secara finansial.

### **Keterbatasan Kustomisasi**

Karena keterbatasan anggaran, perusahaan hanya berlangganan Odoo Online yang tidak dapat dikustomisasi lebih lanjut tanpa menyewa VPS sendiri atau upgrade layanan.

### **Kesulitan Penggunaan & Risiko Keamanan**

Semua pengguna berbagi satu akun super-admin. Antarmuka Odoo terlalu kompleks untuk pengguna lapangan, dan tracking perilaku pengguna - siapa yang login, jam berapa, melakukan apa - menjadi tidak mungkin.

**✅ Solusi:** Pop-App memegang kredensial Odoo sebagai super-user. Di sisi Odoo, semua aktivitas tampak dari satu pengguna. Di sisi Pop-App, puluhan individu dari berbagai cabang bekerja secara terisolasi dan terlacak sepenuhnya.

## **Prioritas Pengembangan**

| **Fase**   | **Deskripsi**                                                                                                 |
| ---------- | ------------------------------------------------------------------------------------------------------------- |
| **Fase 1** | Penjualan dan orkestrasi kebutuhan penjualan: procurement, pengiriman, penerimaan barang, pendelegasian tugas |
| **Fase 2** | KPI & Role Controlling, HR Services, Payroll Services, dan Statistik Aplikasi                                 |
| Fase 3+    | Akan didefinisikan setelah Fase 1 dan 2 selesai                                                               |
