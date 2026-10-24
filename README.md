# SILPJ V3 — Pembaruan 24 Oktober 2026

## Memindahkan data lama
Hentikan server, worker dan scheduler lama; simpan cadangan folder lama. Ekstrak paket ke folder BARU, jangan jalankan pemasang terlebih dahulu.
Dari folder baru:
php scripts/copy-v3-data.php --source="C:\lokasi\silpj-V3-lama" --stopped
php scripts/install-v3.php
Kemudian jalankan MULAI_SILPJ_V3.bat. Login ulang dan periksa data/cetakan. Jangan menjalankan kedua salinan sekaligus. Jangan menggunakan migrate:fresh pada data operasional.
Instalasi kosong: jalankan PASANG_SILPJ_V3.bat lalu MULAI_SILPJ_V3.bat.
PHP 8.2+ dengan pdo_sqlite, bcmath, mbstring, dom, xml, zip, gd dan fileinfo diperlukan. Vendor disertakan.

## Perbaikan dan fitur
Konflik edit berdasarkan revisi; pencabutan sesi setelah reset password; Excel nominal numerik; konfirmasi bukti per revisi; status persetujuan cetak; template dokumen jasa baru; LPJ final PDF; kas masuk/keluar dan pembayaran bertahap; penutupan periode oleh kepala sekolah; koreksi penerimaan; arsip kegiatan; direktori rekanan historis dengan pilihan ulang; notifikasi internal; audit berfilter dan ekspor; ringkasan anggaran kepala sekolah.
LPJ final dari menu Laporan memerlukan tahun dan hanya memuat transaksi disetujui dengan dokumen/bukti lengkap, maksimal 150 transaksi per PDF. Memuat daftar bukti, bukan penggabungan isi seluruh foto/PDF bukti asli.
Pembayaran lama tidak diimpor dari tanggal kuitansi. Saldo kas adalah catatan aplikasi, bukan saldo bank terverifikasi. Pembayaran aktif harus dibatalkan dengan alasan sebelum mengubah transaksi terkait.
Periode tertutup mengunci perubahan terkait. Identitas kegiatan yang sudah bertransaksi dipertahankan. Notifikasi hanya dalam aplikasi, tidak mengirim WhatsApp/email.

## Backup dan pemulihan
Kewenangan administrator sistem diberikan melalui konsol: php artisan silpj:system-admin email@sekolah.id
Aktifkan scheduler Laravel setiap menit untuk backup pukul 01.00 WITA.
php artisan silpj:backup --keep=14
Retensi berarti jumlah berkas. Opsional .env: SILPJ_BACKUP_PASSWORD untuk enkripsi ZIP AES-256; simpan password terpisah. SILPJ_BACKUP_COPY_DIRECTORY untuk salinan pada media/mount terpisah yang dikelola operator; tidak ada sinkronisasi cloud otomatis.
Normal: hentikan proses, php artisan down, lalu php artisan silpj:restore "backup.zip" --reason="Pemulihan data".
Database rusak/hilang: hentikan semua proses, lalu:
php scripts/emergency-restore.php --file="C:\backup.zip" --reason="Database rusak" --confirm=RESTORE
Maintenance tetap aktif. Periksa hasil sebelum php artisan up. Database asli rusak diamankan sebagai berkas mentah. Backup terenkripsi memerlukan password yang sesuai.

## Pengujian
php vendor/bin/phpunit --testsuite=V3
Pemulihan database rusak/hilang dan migrasi salinan database unggahan telah diuji. Jumlah data utama terjaga; integrity_check dan foreign_key_check berhasil. Contoh PDF 7 halaman telah ditinjau visual.
UAT sekolah, uji beban multiuser, konfigurasi hosting/HTTPS dan penyesuaian format resmi tetap diperlukan. Paket tidak menyertakan database, akun, bukti atau konfigurasi rahasia pengguna.
