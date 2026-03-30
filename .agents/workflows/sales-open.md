---
description: Workflow for Penjualan Terbuka (Project Sale) with survey and handover
---

# Penjualan Terbuka (Open/Proyek) Workflow

Untuk transaksi proyek yang memerlukan survei, pengerjaan teknisi, dan RAB.

1. **Create Intent**: CSSR membuat `SalesIntent` dengan `sales_type = open`.
2. **Form Minimal**: Isi nama kustomer, alamat proyek, catatan, dan price list (tanpa order lines).
3. **Assign Survey**: CSSR menyimpan draft dan menugaskan (assign) tiket survei ke Surveyor. Draft SO dibuat.
4. **Fulfill Survey**: Surveyor menginput data survei (ukuran, rekomendasi produk, foto lokasi).
5. **Create RAB**: Surveyor membuat RAB (detail baris, durasi proyek, kebutuhan teknisi) lalu mensubmit ke CSSR.
6. **Present to Customer**: CSSR mempresentasikan RAB ke customer.
   - Jika disetujui -> Convert RAB to SO.
   - Jika ditolak -> Close Lost.
7. **Create & Confirm SO**: Baris RAB diubah menjadi Order Lines. Odoo otomatis membuat Project. Proses dilanjutkan ke pembuatan Invoice dan Payment (seringkali berupa down payment 50%).
8. **Operasional Proyek**: 
   - Kepala Ops/Surveyor melempar tugas ke Teknisi Lapangan.
   - Teknisi menginput progress dan pengeluaran proyek (expense).
9. **Project Handover**: Surveyor atau Teknisi melengkapi field serah terima dengan tanda tangan pelanggan. State proyek menjadi done.
10. **Final Invoice**: Pembayaran sisa/pelunasan dari total nilai SO.
