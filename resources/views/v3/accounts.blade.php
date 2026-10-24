
@extends('v3.layout')
@section('title','Pengguna sekolah')
@push('page-assets')
<link rel="stylesheet" href="{{ asset('school-workspace.css') }}?v=1">
<script src="{{ asset('school-workspace.js') }}?v=1" defer></script>
@endpush
@section('content')
@php($roleLabels = ['admin'=>'Administrator', 'bendahara'=>'Bendahara', 'kepala_sekolah'=>'Kepala sekolah', 'user'=>'Pengguna'])
<div class="school-workspace" data-accounts-workspace>

<section class="card">
<div class="account-toolbar"><h2>Daftar akun <span class="badge">{{ $users->count() }} akun</span></h2><button type="button" class="secondary" data-open-create hidden>+ Tambah pengguna</button></div>
<p class="muted">Gunakan akun bendahara dan pemeriksa yang berbeda untuk persetujuan LPJ.</p>
<div class="account-search" hidden><label>Cari pengguna<input type="search" data-account-search placeholder="Nama, email, atau peran" autocomplete="off"></label><small data-account-count aria-live="polite"></small></div>
@foreach($users as $u)<details class="account-row" data-account-row @if($errors->any() && (string)old('_account_id') === (string)$u->id) open @endif>
<summary><span class="account-identity"><strong>{{ $u->name }} @if($u->id===auth()->id())<small>(Anda)</small>@endif</strong><small>{{ $u->email }}</small></span><span class="badge">{{ $roleLabels[$u->role] ?? $u->role }}</span><span class="badge {{ $u->is_active?'status-active':'status-inactive' }}">{{ $u->is_active?'Aktif':'Nonaktif' }}</span><span class="account-edit">Kelola</span></summary>
<div class="disclosure-body">
@if($u->id!==auth()->id())<form method="post" action="{{ route('v3.accounts.update',$u->id) }}">
@csrf
 
@method('PATCH')<input type="hidden" name="_account_id" value="{{ $u->id }}"><div class="grid">
<label>Peran<select name="role">
@foreach(['admin','bendahara','kepala_sekolah','user'] as $role)<option value="{{ $role }}" @selected((old('_account_id')==$u->id ? old('role',$u->role) : $u->role)===$role)>{{ $roleLabels[$role] }}</option>
@endforeach
</select>
</label>
<label>Status<select name="is_active">
<option value="1" @selected(old('_account_id')==$u->id ? (string)old('is_active')==='1' : $u->is_active)>Aktif</option>
<option value="0" @selected(old('_account_id')==$u->id ? (string)old('is_active')==='0' : !$u->is_active)>Nonaktif</option>
</select>
</label>
<label>Kata sandi baru (opsional)<input type="password" name="password" minlength="8" autocomplete="new-password">
</label>
<label>Ulangi kata sandi baru<input type="password" name="password_confirmation" autocomplete="new-password">
</label>
</div>
<p>
<button>Simpan perubahan</button>
</p>
</form>
@else<p class="muted">Akun yang sedang digunakan. Ganti kata sandi melalui Pengaturan sekolah.</p>
@endif
</div>
</details>
@endforeach
<p class="empty" data-account-empty hidden>Tidak ada pengguna yang cocok.</p>
</section>
<details class="card account-create" id="account-create" @if($errors->any() && old('_form')==='create-account') open @endif>
<summary>Tambah pengguna <span class="muted">Buat akun baru</span></summary>
<div class="disclosure-body">
<form method="post" action="{{ route('v3.accounts.save') }}">
<input type="hidden" name="_form" value="create-account">
@csrf
<div class="grid">
<label>Nama<input name="name" required value="{{ old('name') }}" maxlength="255">
</label>
<label>Email<input type="email" name="email" required value="{{ old('email') }}" maxlength="255">
</label>
<label>Peran<select name="role">
@foreach(['bendahara','kepala_sekolah','user','admin'] as $role)<option value="{{ $role }}" @selected(old('role')===$role)>{{ $roleLabels[$role] }}</option>
@endforeach
</select>
</label>
<label>Kata sandi<input type="password" name="password" minlength="8" required autocomplete="new-password">
</label>
<label>Ulangi kata sandi<input type="password" name="password_confirmation" required autocomplete="new-password">
</label>
</div>
<p>
<button>Buat akun</button>
</p>
</form>
</div>
</details>
</div>
@endsection
