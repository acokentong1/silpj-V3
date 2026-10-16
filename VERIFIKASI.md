# Catatan verifikasi paket

Tanggal: 11 Oktober 2026.

## Lulus di lingkungan pembuatan

- `node --check public/v3.js`: sintaks JavaScript valid.
- JavaScript asli dijalankan menggunakan Node VM dengan DOM tiruan: komponen termasuk harga tidak mengubah total; persentase sebagai penambah/potongan; volume pecahan; tambah/hapus baris; penomoran ulang field; kewajiban field sesuai metode pajak.
- Pemeriksaan statis keseimbangan delimiter PHP dan keberadaan handler rute V3.
- Pemeriksaan referensi rute/template aktif, batas data dan isolasi koneksi.
- Pemeriksaan isi ZIP untuk tidak menyertakan `.env`, data V2, cache lama, atau basis data V2.

## Belum diverifikasi

- Sintaks melalui `php -l`, migrasi, 14 tes PHPUnit, dan HTTP integrasi Laravel.
- Tampilan melalui browser sungguhan pada desktop/mobile.
- Hasil PDF, pagination panjang, logo, dan Excel pada runtime PHP.
- Operasi backup/restore terhadap SQLite operasional.

PHP dan Chromium tidak tersedia. Unduhan paket diblokir oleh jaringan lingkungan. Pengujian Node/DOM tiruan tidak membuktikan aplikasi Laravel sudah berjalan.

## Uji penerimaan setelah instalasi

1. Pengaturan awal menghasilkan satu sekolah/admin dan menutup setup publik.
2. Pengguna sekolah lain tidak dapat membuka data/dokumen/bukti sekolah ini.
3. Isi anggaran, kegiatan, dua transaksi berbeda rekanan, dan beberapa barang.
4. Gunakan contoh 14 × 190000; tambah nilai nominal/persentase dan periksa bruto/netto.
5. Unggah dua logo, cetak tiga PDF; periksa kop, tabel multi-halaman, terbilang, tanggal dan tanda tangan.
6. Coba volume negatif, potongan melebihi bruto, nomor bukti ganda, tanggal tidak berurutan, dan kelebihan anggaran; semua harus ditolak.
7. Ajukan dengan bukti. Periksa tidak ada tombol perubahan dan request langsung juga ditolak.
8. Setujui dari akun lain. Coba menyetujui transaksi sendiri; harus ditolak.
9. Buka revisi, ubah harga, terbitkan ulang; arsip lama tetap memakai rincian sebelumnya dan diberi penanda arsip.
10. Ganti logo/profil; cetak arsip tetap memakai logo/profil semula.
11. Ekspor Excel dan CSV; nama barang/rekanan yang diawali `=` tidak dijalankan sebagai rumus.
12. Uji cadangan/pemulihan pada salinan V3 dan pastikan aplikasi pertama tetap berjalan dengan data aslinya.

Jangan menganggap paket siap produksi sampai pengujian runtime dan cetakan selesai.
