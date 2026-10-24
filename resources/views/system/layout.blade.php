<!doctype html>
<html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>@yield('title','Kelola sekolah') · SILPJ Super Admin</title>
<link rel="stylesheet" href="{{ asset('system-admin.css') }}?v=1"><script src="{{ asset('system-admin.js') }}?v=1" defer></script><link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}?v=20261024"><link rel="shortcut icon" href="{{ asset('favicon.ico') }}?v=20261024"><link rel="stylesheet" href="{{ asset('minimal.css') }}?v=20261024"></head>
<body class="sa-body"><a class="sa-skip" href="#main">Lewati navigasi</a>
<aside class="sa-sidebar"><a class="sa-brand" href="{{ route('system.schools') }}"><span class="sa-mark">S</span><span>SILPJ<small>ADMINISTRASI SISTEM</small></span></a>
<p class="sa-nav-label">MENU UTAMA</p><nav aria-label="Navigasi super admin"><a class="sa-nav-link {{ request()->routeIs('system.schools*') ? 'is-active' : '' }}" href="{{ route('system.schools') }}" @if(request()->routeIs('system.schools*')) aria-current="page" @endif><span aria-hidden="true">▦</span> Kelola sekolah</a><a class="sa-nav-link" href="{{ route('system.backups') }}"><span aria-hidden="true">↓</span> Cadangan sistem</a>
@if(auth()->user()->schoolProfile?->is_active)<a class="sa-nav-link" href="{{ route('dashboard') }}"><span aria-hidden="true">↗</span> Ruang kerja sekolah</a>@endif
</nav><div class="sa-sidebar-note"><span class="sa-pill">AKSES SISTEM</span><h2>Satu pusat.<br>Banyak sekolah.</h2><p>Kelola sekolah dan aksesnya dari satu tempat.</p></div><div class="sa-sidebar-foot">SILPJ V3 <span>Multisekolah</span></div></aside>
<div class="sa-workspace"><header class="sa-topbar"><div class="sa-breadcrumb">Sistem <span>/</span> <strong>@yield('title','Kelola sekolah')</strong></div><div class="sa-account"><span class="sa-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr(auth()->user()->name,0,1)) }}</span><div><strong>{{ auth()->user()->name }}</strong><small>Super Admin</small></div><form method="post" action="{{ route('logout') }}">@csrf<button class="sa-button sa-button-quiet" type="submit">Keluar</button></form></div></header>
<main id="main" class="sa-main" tabindex="-1">
@if(session('success'))<div class="sa-alert sa-alert-success" role="status">{{ session('success') }}</div>@endif
@if($errors->any() && old('_form') !== 'create')<div class="sa-alert sa-alert-error" role="alert"><strong>Periksa kembali isian.</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
@yield('content')<footer class="sa-page-footer">SILPJ · Sistem Informasi Laporan Pertanggungjawaban <span>Panel super admin</span></footer>
</main></div></body></html>
