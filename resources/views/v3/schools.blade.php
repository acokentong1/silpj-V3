
@extends('system.layout')
@section('title','Kelola sekolah')
@section('content')
<section class="sa-heading">
<div>
<p class="sa-eyebrow">ADMINISTRASI MULTISEKOLAH</p>
<h1>Kelola sekolah</h1>
<p>Atur sekolah, pantau status, dan kelola akses dalam satu tempat.</p>
</div>
<button type="button" class="sa-button sa-button-primary" data-open-dialog="create-school">
<span aria-hidden="true">＋</span> Tambah sekolah</button>
</section>
<section class="sa-stats" aria-label="Ringkasan seluruh sekolah">
<div class="sa-stat">
<div>
<p>Total sekolah</p>
<strong>{{ number_format($stats['total'],0,',','.') }}</strong>
<small>Seluruh sekolah terdaftar</small>
</div>
<span class="sa-stat-icon" aria-hidden="true">▦</span>
</div>
<div class="sa-stat">
<div>
<p>Sekolah aktif</p>
<strong>{{ number_format($stats['active'],0,',','.') }}</strong>
<small>Dapat mengakses ruang kerja</small>
</div>
<span class="sa-stat-icon sa-icon-active" aria-hidden="true">✓</span>
</div>
<div class="sa-stat">
<div>
<p>Sekolah nonaktif</p>
<strong>{{ number_format($stats['inactive'],0,',','.') }}</strong>
<small>Akses dihentikan sementara</small>
</div>
<span class="sa-stat-icon sa-icon-inactive" aria-hidden="true">Ⅱ</span>
</div>
</section>
<section class="sa-panel" id="daftar-sekolah" aria-labelledby="school-list-title">
<div class="sa-panel-heading">
<div>
<h2 id="school-list-title">Daftar sekolah <span class="sa-count">{{ $schools->total() }}</span>
</h2>
<p>Setiap sekolah memiliki ruang kerja dan data tersendiri.</p>
</div>
<span class="sa-caption">Ringkasan di atas mencakup semua sekolah</span>
</div>
<form class="sa-filters" method="get" action="{{ route('system.schools') }}">
<div class="sa-search">
<label for="search-school">Cari sekolah</label>
<input id="search-school" type="search" name="q" value="{{ $search }}" maxlength="100" placeholder="Nama sekolah atau NPSN">
</div>
<div>
<label for="school-status">Status</label>
<select id="school-status" name="status">
<option value="">Semua status</option>
<option value="active" @selected($status==='active')>Aktif</option>
<option value="inactive" @selected($status==='inactive')>Nonaktif</option>
</select>
</div>
<button class="sa-button sa-button-secondary" type="submit">Terapkan</button>
@if($search!=='' || $status!=='')<a class="sa-reset" href="{{ route('system.schools') }}">Reset</a>
@endif
</form>
<div class="sa-table-scroll" role="region" aria-label="Tabel daftar sekolah" tabindex="0">
<table class="sa-table">
<thead>
<tr>
<th scope="col">Sekolah</th>
<th scope="col">NPSN</th>
<th scope="col">Status</th>
<th scope="col">Pengguna</th>
<th scope="col" class="sa-align-right">Tindakan</th>
</tr>
</thead>
<tbody>
@forelse($schools as $school)<tr>
<td>
<div class="sa-school-cell">
<span class="sa-school-icon" aria-hidden="true">{{ mb_strtoupper(mb_substr($school->nama_sekolah??'S',0,1)) }}</span>
<div>
<strong>{{ $school->nama_sekolah }}</strong>
<small>{{ $school->alamat ?: 'Alamat belum dilengkapi' }}</small>
</div>
</div>
</td>
<td class="sa-npsn">{{ $school->npsn ?: 'Belum diisi' }}</td>
<td>
<span class="sa-status {{ $school->is_active?'is-active':'is-inactive' }}">
<i aria-hidden="true">
</i>{{ $school->is_active?'Aktif':'Nonaktif' }}</span>
</td>
<td>{{ $school->users_count }} <span class="sa-muted">akun</span>
</td>
<td class="sa-align-right">
<button type="button" class="sa-button sa-button-secondary sa-button-small" data-open-dialog="school-status-{{ $school->id }}" aria-label="Kelola status {{ $school->nama_sekolah }}">Kelola status</button>
</td>
</tr>
@empty<tr>
<td colspan="5">
<div class="sa-empty">
<span aria-hidden="true">▦</span>
<h3>{{ $stats['total'] ? 'Sekolah tidak ditemukan' : 'Belum ada sekolah' }}</h3>
<p>{{ $stats['total'] ? 'Coba kata kunci lain atau ubah filter status.' : 'Tambahkan sekolah pertama beserta akun adminnya.' }}</p>
@if($stats['total'])<a href="{{ route('system.schools') }}" class="sa-reset">Hapus filter</a>
@else<button type="button" class="sa-button sa-button-primary" data-open-dialog="create-school">Tambah sekolah</button>
@endif
</div>
</td>
</tr>
@endforelse
</tbody>
</table>
</div>
<div class="sa-table-footer">
<span>Menampilkan {{ $schools->firstItem() ?? 0 }}–{{ $schools->lastItem() ?? 0 }} dari {{ $schools->total() }} sekolah</span>
<nav class="sa-pagination" aria-label="Halaman daftar sekolah">
@if($schools->onFirstPage())<span aria-disabled="true">Sebelumnya</span>
@else<a href="{{ $schools->previousPageUrl() }}" rel="prev">Sebelumnya</a>
@endif
<strong>{{ $schools->currentPage() }}</strong>
@if($schools->hasMorePages())<a href="{{ $schools->nextPageUrl() }}" rel="next">Berikutnya</a>
@else<span aria-disabled="true">Berikutnya</span>
@endif
</nav>
</div>
</section>
<section id="cadangan" class="sa-backup" aria-labelledby="backup-title">
<div class="sa-backup-copy">
<span class="sa-eyebrow">PEMELIHARAAN</span>
<h2 id="backup-title">Cadangan sistem</h2>
<p>Simpan salinan seluruh sekolah dan berkas pendukung dalam satu ZIP.</p>
<div class="sa-backup-note">Khusus super admin. Unduh saat tidak ada perubahan data, lalu simpan arsip di tempat yang aman.</div>
</div>
<form method="post" action="{{ route('v3.backup') }}" class="sa-backup-form">
@csrf
<label for="backup-password">Konfirmasi kata sandi Anda</label>
<input id="backup-password" type="password" name="password" required autocomplete="current-password" placeholder="Masukkan kata sandi">
<button class="sa-button sa-button-primary" type="submit">↓ &nbsp; Unduh cadangan</button>
<small>Arsip mencakup data seluruh sekolah.</small>
</form>
</section>
<dialog id="create-school" class="sa-dialog" aria-labelledby="create-title" 
@if($errors->any() && old('_form')==='create') data-reopen="true" 
@endif
>
<div class="sa-dialog-heading">
<div>
<p class="sa-eyebrow">SEKOLAH BARU</p>
<h2 id="create-title">Tambah sekolah</h2>
</div>
<button type="button" class="sa-close" data-close-dialog aria-label="Tutup formulir">×</button>
</div>
<p class="sa-dialog-intro">Daftarkan identitas sekolah dan siapkan akun admin pertamanya.</p>
@if($errors->any() && old('_form')==='create')<div class="sa-alert sa-alert-error" role="alert">
<strong>Periksa kembali isian.</strong>
<ul>
@foreach($errors->all() as $error)<li>{{ $error }}</li>
@endforeach
</ul>
</div>
@endif
<form method="post" action="{{ route('system.schools.store') }}">
@csrf
<input type="hidden" name="_form" value="create">
<fieldset>
<legend>
<span>01</span> Identitas sekolah</legend>
<div class="sa-form-grid">
<div>
<label for="new-school-name">Nama sekolah</label>
<input id="new-school-name" name="nama_sekolah" required maxlength="255" value="{{ old('nama_sekolah') }}" placeholder="Contoh: SD Negeri 01">
</div>
<div>
<label for="new-school-npsn">NPSN</label>
<input id="new-school-npsn" name="npsn" inputmode="numeric" required pattern="[0-9]{8}" maxlength="8" value="{{ old('npsn') }}" placeholder="8 digit NPSN">
</div>
<div class="sa-field-full">
<label for="new-school-address">Alamat sekolah</label>
<textarea id="new-school-address" name="alamat" required maxlength="2000" rows="2" placeholder="Alamat lengkap sekolah">{{ old('alamat') }}</textarea>
</div>
</div>
</fieldset>
<fieldset>
<legend>
<span>02</span> Akun admin sekolah</legend>
<div class="sa-form-grid">
<div>
<label for="new-admin-name">Nama admin</label>
<input id="new-admin-name" name="name" required maxlength="255" value="{{ old('name') }}" autocomplete="name">
</div>
<div>
<label for="new-admin-email">Email admin</label>
<input id="new-admin-email" type="email" name="email" required maxlength="255" value="{{ old('email') }}" autocomplete="email">
</div>
<div>
<label for="new-admin-password">Kata sandi awal</label>
<input id="new-admin-password" type="password" name="password" required minlength="12" autocomplete="new-password" aria-describedby="password-help">
<small id="password-help">Minimal 12 karakter.</small>
</div>
<div>
<label for="new-admin-confirm">Ulangi kata sandi</label>
<input id="new-admin-confirm" type="password" name="password_confirmation" required minlength="12" autocomplete="new-password">
</div>
</div>
</fieldset>
<div class="sa-dialog-footer">
<button type="button" class="sa-button sa-button-secondary" data-close-dialog>Batal</button>
<button type="submit" class="sa-button sa-button-primary">Buat sekolah & admin</button>
</div>
</form>
</dialog>
@foreach($schools as $school)<dialog id="school-status-{{ $school->id }}" class="sa-dialog sa-dialog-small" aria-labelledby="status-title-{{ $school->id }}" 
@if($errors->any() && old('_form')==='status' && (string)old('_school_id')===(string)$school->id) data-reopen="true" 
@endif
>
<div class="sa-dialog-heading">
<h2 id="status-title-{{ $school->id }}">Kelola status sekolah</h2>
<button type="button" class="sa-close" data-close-dialog aria-label="Tutup">×</button>
</div>
<p class="sa-dialog-intro">
<strong>{{ $school->nama_sekolah }}</strong>
<br>NPSN {{ $school->npsn }}</p>
@if($errors->any() && old('_form')==='status' && (string)old('_school_id')===(string)$school->id)<div class="sa-alert sa-alert-error" role="alert">
<ul>
@foreach($errors->all() as $error)<li>{{ $error }}</li>
@endforeach
</ul>
</div>
@endif
<form method="post" action="{{ route('system.schools.update',$school->id) }}">
@csrf
 
@method('PATCH')<input type="hidden" name="_form" value="status">
<input type="hidden" name="_school_id" value="{{ $school->id }}">
<label for="status-{{ $school->id }}">Status sekolah</label>
<select id="status-{{ $school->id }}" name="is_active">
<option value="1" @selected($school->is_active)>Aktif</option>
<option value="0" @selected(!$school->is_active)>Nonaktif</option>
</select>
<p class="sa-help">Sekolah nonaktif tidak dapat membuka ruang kerja. Data dan arsip tetap tersimpan.</p>
<label for="reason-{{ $school->id }}">Alasan perubahan</label>
<textarea id="reason-{{ $school->id }}" name="reason" required minlength="5" maxlength="2000" placeholder="Tuliskan alasan perubahan status">{{ (string)old('_school_id')===(string)$school->id ? old('reason') : '' }}</textarea>
<div class="sa-dialog-footer">
<button type="button" class="sa-button sa-button-secondary" data-close-dialog>Batal</button>
<button type="submit" class="sa-button sa-button-primary">Simpan status</button>
</div>
</form>
</dialog>
@endforeach
@endsection
