# SILPJ V3 — Belanja sekolah berbasis kegiatan

Paket implementasi terpisah berdasarkan sumber SILPJ V2 yang diberikan pengguna.
Tanggal paket: 11 Oktober 2026.

## Status verifikasi

**Kode V3 telah dibuat, tetapi belum dinyatakan siap produksi.** Lingkungan pembuat tidak memiliki PHP atau Chromium dan pengunduhan runtime diblokir. Karena itu migrasi Laravel, proses login, unggah, PDF, Excel, dan 14 pengujian integrasi PHP belum dijalankan. Pemeriksaan JavaScript (Node), perhitungan serta interaksi dalam DOM tiruan, rute ke controller, dan keseimbangan delimiter PHP telah lolos. Pemeriksaan statis bukan pengganti menjalankan PHP atau pemeriksaan visual PDF.

Jalankan pengujian yang disertakan dan satu transaksi contoh sebelum memasukkan data operasional. Tidak ada klaim bahwa pengujian integrasi telah lulus. Lihat `VERIFIKASI.md`.

## Database terpisah

- Ekstrak ZIP ini ke folder baru, misalnya `C:\silpj-V3`. Jangan menimpa folder aplikasi pertama.
- Satu-satunya koneksi adalah `silpj_v3`, menggunakan SQLite pada `database/silpj-v3.sqlite` dalam folder V3.
- Variabel DB_DATABASE, DB_URL, DB_HOST, dan kredensial aplikasi lama tidak digunakan.
- Paket tidak menyertakan `.env`, database, akun, log, atau dokumen transaksi aplikasi lama.
- Pemasang membuat `.env` baru dari `.env.example`, APP_KEY baru, dan database kosong baru.
- Pemeriksaan identitas database menolak berkas berisi tabel yang bukan SILPJ V3. Berkas database berupa tautan simbolik juga ditolak.
- Direktori V3, `database`, dan `storage` harus benar-benar terpisah; jangan gunakan junction, symlink, atau salinan konfigurasi cache dari aplikasi lama.
- Database, sesi, cache, logo, dan bukti diletakkan di folder V3. Cookie bernama `silpj_v3_session`.
- Skema memakai `school_profiles` dan `users` di database V3 untuk mempertahankan mekanisme autentikasi; tabel transaksi memakai awalan `v3_`. Nama sama tidak menghubungkan data ke database lama.
- Aplikasi pertama tidak perlu dinyalakan atau dimigrasikan. Tidak ada pemindahan data otomatis.

Versi paket ini khusus SQLite lokal. Koneksi MySQL belum disediakan. Untuk pemasangan jaringan dengan banyak penulis bersamaan, lakukan pengujian beban dan rancang koneksi server tersendiri sebelum digunakan.

## Persyaratan

PHP 8.2 atau lebih baru, 64-bit; ekstensi pdo_sqlite, mbstring, dom, xml, zip, gd, bcmath, fileinfo; ekstensi standar Laravel seperti openssl dan tokenizer. Folder `vendor` disertakan dari paket sumber. Jika vendor tidak tersedia, jalankan `composer install` dengan koneksi paket yang tersedia.

Tidak perlu npm build untuk tampilan V3. CSS dan JavaScript mandiri ada di `public/v3.css` dan `public/v3.js`.

## Pemasangan Windows

1. Ekstrak seluruh ZIP ke folder baru `silpj-V3`.
2. Aktifkan ekstensi PHP di `php.ini`. Gunakan PHP 8.2+ 64-bit dari lingkungan Anda; peluncur mencari `php` pada PATH atau `C:\xampp\php\php.exe`.
3. Jalankan `PASANG_SILPJ_V3.bat` satu kali. Jika ada kegagalan, perbaiki pesan yang tampil sebelum melanjutkan.
4. Jalankan `MULAI_SILPJ_V3.bat`.
5. Buka alamat yang tampil di terminal. Peluncur memilih port kosong mulai 8003.
6. Buat identitas sekolah dan admin pertama. Halaman pengaturan awal otomatis ditutup setelah berhasil.
7. Tambahkan akun bendahara dan kepala sekolah melalui menu Pengguna & cadangan.

Alternatif terminal, dari folder V3:

```bash
php scripts/install-v3.php
php scripts/start-local.php
```

Peluncur mengikat server ke 127.0.0.1 untuk penggunaan lokal. Biarkan terminal terbuka selama aplikasi digunakan. Untuk server web, arahkan document root ke `public`, gunakan HTTPS, `APP_ENV=production`, `APP_DEBUG=false`, dan konfigurasi izin berkas yang sesuai. Jangan mengekspos akar proyek.

Batas PHP yang dibutuhkan: `max_input_vars=5000`, `upload_max_filesize=12M`, `post_max_size=24M`. Peluncur lokal menetapkan nilai tersebut langsung; pada Apache/FPM atur melalui konfigurasi PHP server.

## Urutan penggunaan

1. **Pengaturan sekolah**: identitas pemerintah daerah, dinas, sekolah, alamat, tempat cetak, kode surat dan sekolah; nama dan NIP penandatangan. NIP opsional.
2. Unggah dua logo PNG/JPG, masing-masing maksimal 2 MB dan 4000 × 4000 piksel. Pratinjau pilihan baru ditampilkan sebelum menyimpan.
3. **Anggaran**: tahun, sumber dana, nilai. Tahun dan sumber dana yang sama memperbarui anggaran.
4. **Kegiatan & belanja**: isi nama kegiatan dan pilih anggaran. Satu kegiatan dapat mempunyai beberapa transaksi/rekanan.
5. Pada kegiatan, pilih **Tambah transaksi**. Isi identitas rekanan, penerima, nomor bukti, tanggal, serta barang. Maksimal 200 barang; volume mendukung 3 desimal dan harga 2 desimal.
6. Tambahkan pajak/komponen jika diperlukan. Input nominal atau persentase dengan dasar perhitungan eksplisit. Kategori komponen: pajak atau lainnya. Perlakuan: termasuk harga, penambah, atau potongan. Tidak ada tarif pajak otomatis.
7. Simpan. Server menghitung ulang nilai menggunakan BCMath dan pembulatan setengah ke atas ke rupiah penuh per baris/komponen.
8. Terbitkan **Nopes**, kemudian **BAPB**, kemudian **Kuitansi**. Tanggal mengikuti urutan dan tahun anggaran. Tanggal lunas diisi pada kuitansi.
9. Pada BAPB, isi volume baik/tidak baik per barang. Total kedua kondisi harus sama dengan volume pesanan. Catatan dapat ditambahkan per barang.
10. Unggah minimal satu bukti PDF/JPG/PNG maksimal 10 MB, lalu **Ajukan pemeriksaan**.
11. Kepala sekolah/pemeriksa dengan akun berbeda menyetujui atau mengembalikan untuk revisi.
12. Cetak dari halaman dokumen atau unduh PDF. Ekspor seluruh rekap melalui Excel atau CSV.

Peran `user` hanya membaca data sekolahnya. Bendahara/admin mengisi transaksi; kepala sekolah/admin memeriksa. Pembuat atau pengaju tidak dapat menyetujui transaksi sendiri.

## Perhitungan

Subtotal = jumlah nilai barang (volume × harga, dibulatkan per baris).
Bruto = subtotal + komponen penambah.
Netto diterima rekanan = bruto − komponen pemotong.
Komponen termasuk harga dirinci tanpa menambah atau mengurangi jumlah.
Total pajak = penjumlahan komponen berkategori pajak; tidak dianggap sama dengan potongan.
SPPD dapat diinput sebagai label dan dipilih kategori/perlakuannya sendiri.

Contoh: 14 kelang Cat × Rp190.000 = Rp2.660.000. PPN Rp263.603 dengan perlakuan termasuk harga menghasilkan bruto/netto Rp2.660.000. Apabila dipilih sebagai potongan, netto Rp2.396.397. Ini contoh perilaku aplikasi, bukan penetapan tarif atau perlakuan pajak transaksi sebenarnya.

Anggaran dicadangkan memakai bruto seluruh transaksi aktif, termasuk draf. Transaksi dibatalkan tidak mencadangkan anggaran. Rekap memuat seluruh status; jangan menjumlahkan draf sebagai pembayaran yang sudah lunas.

## Dokumen dan perubahan

- Semua dokumen menggunakan kop dua logo: Pemda kiri, sekolah kanan.
- Kuitansi mengambil nama kegiatan pada Untuk Pembayaran, menampilkan rekanan, nilai bruto, potongan, netto, terbilang dan rincian komponen.
- Nopes/BAPB memuat nama barang, volume, satuan, harga, dan jumlah.
- Pemeriksaan disimpan per jenis/satuan barang; volume dengan satuan berbeda tidak dijumlahkan.
- Setiap penerbitan menyimpan snapshot data sekolah, logo dalam bentuk data gambar, rekanan, rincian barang, komponen, serta tanggal terkait.
- Template arsip v1 berada di `resources/views/v3/print-v1.blade.php`. Pertahankan template ini ketika menambahkan versi template baru.
- Mengubah rincian transaksi menambah revisi dan menandai dokumen lama `diganti`; ketiga dokumen harus diterbitkan ulang.
- Batalkan Nopes akan membatalkan BAPB/Kuitansi aktif turunannya; batalkan BAPB membatalkan Kuitansi; batalkan Kuitansi hanya memengaruhi kuitansi tersebut.
- Arsip dokumen dibatalkan/diganti tetap dapat dilihat dengan penanda jelas. Nomor lama tidak dipakai kembali.
- Saat diajukan/disetujui, tombol ubah/hapus tidak muncul. Server juga menolak perubahan.
- Dialog pembatalan/revisi meminta alasan. Dokumen/transaksi berjejak diarsipkan, bukan dihapus permanen.

Asumsi versi awal: satu rekanan, satu pemeriksaan final, satu pelunasan per transaksi. Pembayaran/penerimaan bertahap belum didukung. Rekanan diisi pada formulir transaksi, belum menyediakan halaman master rekanan tersendiri. Hasil pemeriksaan dan pembayaran tersimpan pada snapshot BAPB/Kuitansi, bukan tabel inspection/payment terpisah. Nama kegiatan dipertahankan setelah dibuat; antarmuka perubahan nama kegiatan belum disediakan.

## Cadangan dan pemulihan

Admin dapat mengunduh ZIP cadangan dari Pengguna & cadangan dengan konfirmasi kata sandi. ZIP berisi snapshot SQLite konsisten (`VACUUM INTO`), seluruh logo/bukti, dan identitas `SILPJ-V3-BACKUP.json`. Berkas cadangan tidak dienkripsi; simpan di media yang aksesnya terbatas.

Pemulihan manual khusus V3:

1. Hentikan server V3 dan simpan salinan folder V3 saat ini terlebih dahulu.
2. Pastikan cadangan mempunyai `SILPJ-V3-BACKUP.json` dengan product `silpj-v3` dan berasal dari instalasi yang benar.
3. Ekstrak ke folder sementara; periksa integritas `database/silpj-v3.sqlite` dengan SQLite dan identitas tabel `v3_identity`.
4. Setelah memastikan tidak ada proses V3 yang berjalan, pindahkan database V3 saat ini beserta berkas `silpj-v3.sqlite-wal`/`-shm` (jika ada) ke folder cadangan sementara. Jangan meninggalkan WAL lama berdampingan dengan database yang dipulihkan.
5. Salin database cadangan ke `database/silpj-v3.sqlite` dan berkas logo/bukti ke `storage/app/private/v3` di folder V3.
6. Pertahankan `.env` dan APP_KEY V3 Anda. Jangan salin konfigurasi V2. Hapus sesi lama V3 bila diperlukan untuk memaksa login ulang.
7. Jalankan V3, lalu periksa akun, transaksi dan cetakan. Jika gagal, hentikan server dan pulihkan salinan langkah 1.

Pemulihan melalui UI belum disediakan. Cadangan aplikasi pertama tidak kompatibel dan tidak boleh dimasukkan ke V3.

## Pengujian PHP

Dari folder V3 dengan PHP dan ekstensi lengkap:

```bash
php artisan test --testsuite=V3
```

Pengujian memakai SQLite dalam memori dan penyimpanan palsu; tidak memakai database V3 operasional atau database lama. Jangan mengganti `phpunit.xml` dengan konfigurasi aplikasi pertama. Jalankan saat tidak ada proses produksi pada folder yang sama.

## Berkas utama

- `config/database.php`: koneksi terisolasi.
- `database/migrations/2026_10_11_000000_create_v3.php`: skema baru.
- `app/Services/V3Calculator.php`: aritmetika desimal.
- `app/Services/V3Workflow.php`: simpan transaksi, dokumen, versi, status, dan audit.
- `app/Http/Controllers/V3Controller.php`: layar, validasi, unggah, rekap dan cadangan.
- `resources/views/v3/`: antarmuka serta template cetak v1.
- `tests/V3/WorkflowTest.php`: 14 skenario integrasi untuk dijalankan.
