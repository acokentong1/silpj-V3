@extends('system.layout')
@section('title','Cadangan dan penyimpanan')
@section('content')
@if($backupStatus)<div class="notice {{ $backupStatus['ok']?'':'error' }}">Backup terakhir: {{ $backupStatus['at'] }} · {{ $backupStatus['ok']?'Berhasil':'GAGAL — periksa log dan tujuan salinan' }} · {{ !empty($backupStatus['encrypted'])?'Terenkripsi':'Status enkripsi: lihat konfigurasi' }}</div>@else<div class="notice">Belum ada status backup. Pastikan scheduler aktif.</div>@endif
<section class="sa-card" id="cadangan">
<h2>Cadangan sistem</h2>
<p>Jadwal aplikasi: setiap hari pukul 01.00 WITA, menyimpan 14 cadangan terakhir. Scheduler server harus aktif. Daftar berikut menunjukkan berkas yang benar-benar tersedia, bukan jaminan scheduler sedang berjalan.</p>
<p>Database: {{ number_format($databaseBytes/1048576,2,',','.') }} MB · {{ $assetCount }} berkas: {{ number_format($assetBytes/1048576,2,',','.') }} MB.</p>
<form method="post" action="{{ route('v3.backup') }}">
@csrf
<label>Password administrator<input type="password" name="password" autocomplete="current-password" required></label>
<button class="sa-button sa-button-primary">Buat dan unduh cadangan sekarang</button>
</form>
<table><thead><tr><th>Cadangan otomatis / konsol (maks. 30 terbaru)</th><th>Waktu server</th><th>Ukuran</th></tr></thead><tbody>
@forelse($backups as $backup)
<tr><td>{{ $backup['name'] }}</td><td>{{ $backup['time'] }}</td><td>{{ number_format($backup['size']/1048576,2,',','.') }} MB</td></tr>
@empty
<tr><td colspan="3">Belum ada cadangan tersimpan. Periksa aktivasi scheduler server.</td></tr>
@endforelse
</tbody></table>
</section>
<section class="sa-card">
<h2>Persiapan pemulihan</h2>
<p>Unggah cadangan untuk memeriksa struktur dan checksum (maks. 100 MB). Pemulihan mengganti data semua sekolah, sehingga dilanjutkan oleh administrator server melalui konsol dalam mode pemeliharaan. Berkas lama tanpa checksum tidak diterima oleh pemulihan otomatis ini.</p>
<form method="post" enctype="multipart/form-data" action="{{ route('system.backups.check') }}">
@csrf
<label>Cadangan ZIP<input type="file" name="file" accept=".zip" required></label>
<label>Password administrator<input type="password" name="password" autocomplete="current-password" required></label>
<button class="sa-button sa-button-primary">Periksa cadangan</button>
</form>
@if(session('restore_file'))
<p>Berkas siap diperiksa lebih lanjut melalui konsol: <code>{{ session('restore_file') }}</code>. Ikuti PANDUAN_PEMBARUAN.md. Berkas persiapan dihapus otomatis setelah 7 hari jika scheduler aktif.</p>
@endif
</section>
@endsection
