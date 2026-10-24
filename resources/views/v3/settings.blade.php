
@extends('v3.layout')
@section('title','Pengaturan sekolah')
@push('page-assets')
<link rel="stylesheet" href="{{ asset('school-workspace.css') }}?v=1">
<script src="{{ asset('school-workspace.js') }}?v=1" defer></script>
@endpush
@section('content')
<div class="school-workspace">
<p class="workspace-intro muted">Identitas dan kedua logo akan digunakan pada kop Kuitansi, Nopes, dan BAPB.</p>
<form method="post" enctype="multipart/form-data" action="{{ route('v3.settings.save') }}">
@csrf
<div data-compact-tabs class="settings-tabs">
<div class="compact-tabbar" aria-label="Bagian pengaturan" hidden></div>
<section class="card" id="school-identity" data-tab-label="Identitas & nomor">
<fieldset class="settings-fields" @disabled(!auth()->user()->isBendahara())>
<h2>Identitas dan penomoran</h2>
<div class="grid settings-grid">
@foreach(['nama_pemda'=>'Pemerintah daerah','nama_dinas'=>'Dinas','nama_sekolah'=>'Nama sekolah','npsn'=>'NPSN','tempat_cetak'=>'Tempat penerbitan','kode_surat'=>'Kode klasifikasi surat','kode_sekolah'=>'Kode sekolah untuk nomor dokumen'] as $f=>$label)<label>{{ $label }}<input name="{{ $f }}" value="{{ old($f,$s->$f) }}" @if($errors->has($f)) data-field-error aria-invalid="true" @endif required maxlength="255">
</label>
@endforeach
<label class="wide">Alamat<textarea name="alamat" @if($errors->has('alamat')) data-field-error aria-invalid="true" @endif rows="2" required maxlength="2000">{{ old('alamat',$s->alamat) }}</textarea>
</label>
</div>
</fieldset>
</section>
<section class="card" id="school-logos" data-tab-label="Logo kop surat">
<fieldset class="settings-fields" @disabled(!auth()->user()->isBendahara())>
<h2>Logo kop surat</h2>
<div class="grid">
@foreach(['logo_pemda'=>'Logo Pemda · kiri','logo_sekolah'=>'Logo sekolah · kanan'] as $f=>$label)<div>
<label>{{ $label }}<input type="file" name="{{ $f }}" @if($errors->has($f)) data-field-error aria-invalid="true" @endif accept=".png,.jpg,.jpeg">
</label>
@if($s->$f)<img class="logo-preview" src="{{ route('v3.logo',$f) }}" alt="{{ $label }}">
@endif
<small>PNG/JPG, maks. 2 MB dan 4000 × 4000 piksel.</small>
</div>
@endforeach
</div>
</fieldset>
</section>
<section class="card" id="school-signers" data-tab-label="Penandatangan">
<fieldset class="settings-fields" @disabled(!auth()->user()->isBendahara())>
<h2>Penandatangan</h2>
<div class="grid">
@foreach(['kepala_sekolah'=>'Kepala sekolah','nip_kepala'=>'NIP kepala sekolah (opsional)','bendahara'=>'Bendahara','nip_bendahara'=>'NIP bendahara (opsional)','pemeriksa'=>'Pemeriksa barang','nip_pemeriksa'=>'NIP pemeriksa (opsional)'] as $f=>$label)<label>{{ $label }}<input name="{{ $f }}" value="{{ old($f,$s->$f) }}" @if($errors->has($f)) data-field-error aria-invalid="true" @endif @required(!str_starts_with($f,'nip')) maxlength="255">
</label>
@endforeach
</div>
</fieldset>
</section>
</div>
<div class="settings-save">
<span class="muted" data-settings-status>Perubahan pada ketiga tab disimpan bersama.</span>
@if(auth()->user()->isBendahara())<button>Simpan pengaturan</button>
@endif
</div>
</form>
<details class="card account-password" @if($errors->updatePassword->any()) open @endif>
<summary>Keamanan akun <span class="muted">Ganti kata sandi</span></summary>
<div class="disclosure-body">
<form method="post" action="{{ route('password.update') }}">
@csrf
 
@method('PUT')<div class="grid">
<label>Kata sandi saat ini<input type="password" name="current_password" autocomplete="current-password" required>
</label>
<label>Kata sandi baru<input type="password" name="password" autocomplete="new-password" minlength="8" required>
</label>
<label>Ulangi kata sandi baru<input type="password" name="password_confirmation" autocomplete="new-password" required>
</label>
</div>
@foreach($errors->updatePassword->all() as $error)<p class="error">{{ $error }}</p>
@endforeach
<p>
<button>Perbarui kata sandi</button>
</p>
</form>
</div>
</details>
</div>
@endsection
