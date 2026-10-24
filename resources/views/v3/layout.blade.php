<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>@yield('title','Kegiatan') · SILPJ V3</title>
<link rel="stylesheet" href="{{ asset('v3.css') }}?v=3">
<script src="{{ asset('rupiah.js') }}?v=1" defer>
</script>
<script src="{{ asset('v3.js') }}?v=5" defer>
</script>
<link rel="stylesheet" href="{{ asset('approvals.css') }}?v=1">@stack('page-assets')<link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}?v=20261024"><link rel="shortcut icon" href="{{ asset('favicon.ico') }}?v=20261024"><link rel="stylesheet" href="{{ asset('minimal.css') }}?v=20261025"></head>
<body class="{{ auth()->user()->role==='kepala_sekolah'?'role-head':'role-standard' }}">
@php
 $roleLabel = auth()->user()->isSystemAdmin() ? 'Administrator Sistem' : (['kepala_sekolah'=>'Kepala Sekolah','bendahara'=>'Bendahara','admin'=>'Administrator Sekolah'][auth()->user()->role] ?? 'Pengguna');
@endphp
<header>
<a class="brand" href="{{ route(auth()->user()->isSystemAdmin() ? 'system.schools' : 'dashboard') }}">
<span class="brand-icon">S</span> SILPJ <small>V3</small>
</a>
<div>{{ auth()->user()->schoolProfile?->nama_sekolah }}<span class="muted"> · {{ auth()->user()->name }}</span>
<span class="account-role">{{ $roleLabel }}</span>
</div>
<form action="{{ route('logout') }}" method="post">
@csrf
<button class="secondary">Keluar</button>
</form>
</header>
<div class="shell">
<aside>
<p class="eyebrow">{{ auth()->user()->role==='kepala_sekolah'?'RUANG KEPALA SEKOLAH':'RUANG KERJA' }}</p>
<nav>
@if(auth()->user()->schoolProfile?->is_active)
@if(auth()->user()->isKepalaSekolah())
<a href="{{ route('v3.approvals') }}" class="{{ request()->routeIs('v3.approvals')||(auth()->user()->role==='kepala_sekolah'&&request()->routeIs('v3.purchase'))?'active':'' }}">Persetujuan LPJ</a>
@endif
@if(auth()->user()->role!=='kepala_sekolah')
<a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard','v3.activity*','v3.purchase','v3.create','v3.edit')?'active':'' }}">Kegiatan & belanja</a>
@endif
<a href="{{ route('v3.vendors') }}">Rekanan</a><a href="{{ route('v3.audit') }}">Jejak audit</a><a href="{{ route('v3.notices') }}">Notifikasi ({{ \Illuminate\Support\Facades\DB::table('v3_notices')->where('user_id',auth()->id())->whereNull('read_at')->count() }})</a>
<a href="{{ route('v3.budgets') }}">Anggaran</a>
<a href="{{ route('v3.reports') }}">Laporan & riwayat</a>
<a href="{{ route('v3.settings') }}">Pengaturan sekolah</a>
@if(auth()->user()->isAdmin())<a href="{{ route('admin.users.create') }}">Pengguna sekolah</a>
@endif
 
@endif
 
@if(auth()->user()->isSystemAdmin())<a href="{{ route('system.schools') }}">Administrasi sistem</a>
@endif
</nav>
<div class="aside-note">
@if(auth()->user()->role==='kepala_sekolah')
Periksa dengan jelas.<br>Setujui dengan yakin.<small>Tinjau rincian, dokumen, dan bukti transaksi sebelum memberikan keputusan.</small>
@else
Satu kegiatan.<br>Semua belanja tercatat.<br>
<small>Dokumen terhubung dalam satu alur.</small>
@endif
</div>
</aside>
<main>
<p class="eyebrow">ADMINISTRASI SEKOLAH</p>
<h1>@yield('title','Kegiatan')</h1>
@if(session('success'))<div class="notice" role="status">{{ session('success') }}</div>
@endif
@if(session('status'))<div class="notice">{{ session('status') }}</div>
@endif
@if($errors->any())<div class="notice error" role="alert">
<strong>Periksa kembali isian:</strong>
<ul>
@foreach($errors->all() as $error)<li>{{ $error }}</li>
@endforeach
</ul>
</div>
@endif
@yield('content')</main>
</div>
<dialog id="confirm-dialog">
<form method="dialog">
<h2 id="confirm-title">Konfirmasi tindakan</h2>
<p>Alasan akan disimpan dalam riwayat.</p>
<label>Alasan<textarea id="confirm-reason" required minlength="5" maxlength="2000">
</textarea>
</label>
<div class="actions">
<button value="cancel" formnovalidate class="secondary">Kembali</button>
<button value="ok" class="danger">Lanjutkan</button>
</div>
</form>
</dialog>
</body>
</html>
