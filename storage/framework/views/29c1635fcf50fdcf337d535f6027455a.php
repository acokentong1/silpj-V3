<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?php echo $__env->yieldContent('title','Kegiatan'); ?> · SILPJ V3</title>
<link rel="stylesheet" href="<?php echo e(asset('v3.css')); ?>?v=3">
<script src="<?php echo e(asset('rupiah.js')); ?>?v=1" defer>
</script>
<script src="<?php echo e(asset('v3.js')); ?>?v=5" defer>
</script>
<link rel="stylesheet" href="<?php echo e(asset('approvals.css')); ?>?v=1"><?php echo $__env->yieldPushContent('page-assets'); ?><link rel="icon" type="image/svg+xml" href="<?php echo e(asset('favicon.svg')); ?>?v=20261024"><link rel="shortcut icon" href="<?php echo e(asset('favicon.ico')); ?>?v=20261024"><link rel="stylesheet" href="<?php echo e(asset('minimal.css')); ?>?v=20261025"></head>
<body class="<?php echo e(auth()->user()->role==='kepala_sekolah'?'role-head':'role-standard'); ?>">
<?php
 $roleLabel = auth()->user()->isSystemAdmin() ? 'Administrator Sistem' : (['kepala_sekolah'=>'Kepala Sekolah','bendahara'=>'Bendahara','admin'=>'Administrator Sekolah'][auth()->user()->role] ?? 'Pengguna');
?>
<header>
<a class="brand" href="<?php echo e(route(auth()->user()->isSystemAdmin() ? 'system.schools' : 'dashboard')); ?>">
<span class="brand-icon">S</span> SILPJ <small>V3</small>
</a>
<div><?php echo e(auth()->user()->schoolProfile?->nama_sekolah); ?><span class="muted"> · <?php echo e(auth()->user()->name); ?></span>
<span class="account-role"><?php echo e($roleLabel); ?></span>
</div>
<form action="<?php echo e(route('logout')); ?>" method="post">
<?php echo csrf_field(); ?>
<button class="secondary">Keluar</button>
</form>
</header>
<div class="shell">
<aside>
<p class="eyebrow"><?php echo e(auth()->user()->role==='kepala_sekolah'?'RUANG KEPALA SEKOLAH':'RUANG KERJA'); ?></p>
<nav>
<?php if(auth()->user()->schoolProfile?->is_active): ?>
<?php if(auth()->user()->isKepalaSekolah()): ?>
<a href="<?php echo e(route('v3.approvals')); ?>" class="<?php echo e(request()->routeIs('v3.approvals')||(auth()->user()->role==='kepala_sekolah'&&request()->routeIs('v3.purchase'))?'active':''); ?>">Persetujuan LPJ</a>
<?php endif; ?>
<?php if(auth()->user()->role!=='kepala_sekolah'): ?>
<a href="<?php echo e(route('dashboard')); ?>" class="<?php echo e(request()->routeIs('dashboard','v3.activity*','v3.purchase','v3.create','v3.edit')?'active':''); ?>">Kegiatan & belanja</a>
<?php endif; ?>
<a href="<?php echo e(route('v3.vendors')); ?>">Rekanan</a><a href="<?php echo e(route('v3.audit')); ?>">Jejak audit</a><a href="<?php echo e(route('v3.notices')); ?>">Notifikasi (<?php echo e(\Illuminate\Support\Facades\DB::table('v3_notices')->where('user_id',auth()->id())->whereNull('read_at')->count()); ?>)</a>
<a href="<?php echo e(route('v3.budgets')); ?>">Anggaran</a>
<a href="<?php echo e(route('v3.reports')); ?>">Laporan & riwayat</a>
<a href="<?php echo e(route('v3.settings')); ?>">Pengaturan sekolah</a>
<?php if(auth()->user()->isAdmin()): ?><a href="<?php echo e(route('admin.users.create')); ?>">Pengguna sekolah</a>
<?php endif; ?>
 
<?php endif; ?>
 
<?php if(auth()->user()->isSystemAdmin()): ?><a href="<?php echo e(route('system.schools')); ?>">Administrasi sistem</a>
<?php endif; ?>
</nav>
<div class="aside-note">
<?php if(auth()->user()->role==='kepala_sekolah'): ?>
Periksa dengan jelas.<br>Setujui dengan yakin.<small>Tinjau rincian, dokumen, dan bukti transaksi sebelum memberikan keputusan.</small>
<?php else: ?>
Satu kegiatan.<br>Semua belanja tercatat.<br>
<small>Dokumen terhubung dalam satu alur.</small>
<?php endif; ?>
</div>
</aside>
<main>
<p class="eyebrow">ADMINISTRASI SEKOLAH</p>
<h1><?php echo $__env->yieldContent('title','Kegiatan'); ?></h1>
<?php if(session('success')): ?><div class="notice" role="status"><?php echo e(session('success')); ?></div>
<?php endif; ?>
<?php if(session('status')): ?><div class="notice"><?php echo e(session('status')); ?></div>
<?php endif; ?>
<?php if($errors->any()): ?><div class="notice error" role="alert">
<strong>Periksa kembali isian:</strong>
<ul>
<?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><li><?php echo e($error); ?></li>
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</ul>
</div>
<?php endif; ?>
<?php echo $__env->yieldContent('content'); ?></main>
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
<?php /**PATH D:\04 DATA ACO\S8\Capstone Project\silpj-V3\resources\views/v3/layout.blade.php ENDPATH**/ ?>