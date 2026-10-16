<?php $__env->startSection('title','Kegiatan & belanja'); ?>
<?php $__env->startSection('content'); ?>
<p class="muted">Mulai dari kegiatan, tambahkan belanja, lalu lengkapi dokumen pertanggungjawaban.</p>
<form method="get" action="<?php echo e(route('dashboard')); ?>" class="card actions">
<label for="dashboard-year">Tahun anggaran<select name="tahun" id="dashboard-year"><?php $__currentLoopData = $years; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $year): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($year); ?>" <?php if($selectedYear===$year): echo 'selected'; endif; ?>><?php echo e($year); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select></label>
<?php if(request()->filled('q')): ?><input type="hidden" name="q" value="<?php echo e(request('q')); ?>"><?php endif; ?>
<button type="submit">Tampilkan tahun</button><span class="muted">Menampilkan data tahun <?php echo e($selectedYear); ?></span>
</form>
<div class="stats"><?php $__currentLoopData = ['draf'=>'Draf','diajukan'=>'Menunggu pemeriksaan','disetujui'=>'Disetujui','revisi'=>'Perlu revisi']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key=>$label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><div class="stat"><small><?php echo e($label); ?></small><strong><?php echo e($counts[$key]??0); ?></strong></div><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></div>
<?php echo $__env->make('v3.budget-summary', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<section class="card"><div class="between"><h2>Daftar kegiatan</h2><?php if(auth()->user()->isBendahara()): ?><button type="button" id="open-activity-dialog" aria-haspopup="dialog" aria-controls="activity-dialog">+ Tambah kegiatan</button><?php endif; ?><form class="actions" method="get"><input type="hidden" name="tahun" value="<?php echo e($selectedYear); ?>"><input aria-label="Cari kegiatan" name="q" value="<?php echo e(request('q')); ?>" placeholder="Cari nama kegiatan"><button class="secondary">Cari</button></form></div><div class="table-wrap"><table><thead><tr><th>Kegiatan</th><th>Tahun / Dana</th><th>Transaksi</th><th class="number">Nilai bruto aktif</th><th></th></tr></thead><tbody><?php $__empty_1 = true; $__currentLoopData = $activities; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $a): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><tr><td><strong><?php echo e($a->nama); ?></strong></td><td><?php echo e($a->budget->tahun); ?> / <?php echo e($a->budget->sumber_dana); ?></td><td><?php echo e($a->purchases_count); ?></td><td class="number">Rp<?php echo e(number_format($a->purchases_sum_bruto??0,0,',','.')); ?></td><td><a href="<?php echo e(route('v3.activity',$a->id)); ?>">Buka →</a></td></tr><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><tr><td colspan="5" class="empty">Tidak ada kegiatan yang sesuai pada tahun <?php echo e($selectedYear); ?>. Periksa pencarian atau tambahkan kegiatan.</td></tr><?php endif; ?></tbody></table></div><div class="pagination"><?php echo e($activities->links()); ?></div></section>
<?php if(auth()->user()->isBendahara()): ?>
<dialog id="activity-dialog" aria-labelledby="activity-dialog-title" data-reopen="<?php echo e(old('_activity_form')==='1'?'yes':'no'); ?>">
<div class="between"><h2 id="activity-dialog-title">Tambah kegiatan</h2><button type="button" class="secondary" data-close-activity aria-label="Tutup formulir kegiatan">Tutup</button></div>
<?php if($budgets->isEmpty()): ?>
<p>Tambahkan anggaran sekolah terlebih dahulu agar kegiatan dapat dihubungkan ke anggaran.</p><a class="button" href="<?php echo e(route('v3.budgets')); ?>">Tambah anggaran</a>
<?php else: ?>
<?php if($errors->any() && old('_activity_form')==='1'): ?><div class="notice error" role="alert"><ul><?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><li><?php echo e($error); ?></li><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></ul></div><?php endif; ?>
<form method="post" action="<?php echo e(route('v3.activity.store')); ?>"><?php echo csrf_field(); ?>
<input type="hidden" name="_activity_form" value="1">
<div class="grid"><label class="wide">Nama / jenis kegiatan<input name="nama" required maxlength="255" value="<?php echo e(old('nama')); ?>" placeholder="Contoh: Pengecatan lapangan" autofocus></label>
<label>Anggaran<select name="budget_id" required><?php $__currentLoopData = $budgets; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $b): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($b->id); ?>" <?php if(old('budget_id')==$b->id): echo 'selected'; endif; ?>><?php echo e($b->tahun); ?> · <?php echo e($b->sumber_dana); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select></label>
<label>Keterangan<input name="keterangan" value="<?php echo e(old('keterangan')); ?>" maxlength="2000"></label></div>
<div class="actions"><button type="button" class="secondary" data-close-activity>Batal</button><button type="submit">Simpan kegiatan</button></div>
</form>
<?php endif; ?>
</dialog>
<?php endif; ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('v3.layout', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\04 DATA ACO\S8\Capstone Project\silpj-V3\resources\views/v3/index.blade.php ENDPATH**/ ?>