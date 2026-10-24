<?php $__env->startSection('title','Anggaran sekolah'); ?>
<?php $__env->startSection('content'); ?>
<section class="card">
<div class="between">
<h2>Anggaran per tahun & sumber dana</h2>
<?php if(auth()->user()->isBendahara()): ?><button type="button" id="open-budget-dialog" aria-haspopup="dialog" aria-controls="budget-dialog">+ Tambah anggaran</button>
<?php endif; ?>
</div>
<p class="muted">Anggaran hanya dapat dihapus jika belum digunakan oleh kegiatan. Penghapusan memerlukan alasan dan dicatat dalam riwayat.</p>
<div class="table-wrap">
<table>
<thead>
<tr>
<th>Tahun</th>
<th>Sumber dana</th>
<th class="number">Nilai anggaran</th>
<?php if(auth()->user()->isBendahara()): ?><th>Aksi</th>
<?php endif; ?>
</tr>
</thead>
<tbody>
<?php $__empty_1 = true; $__currentLoopData = $budgets; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $b): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><tr>
<td><?php echo e($b->tahun); ?></td>
<td><?php echo e($b->sumber_dana); ?></td>
<td class="number">Rp<?php echo e(number_format($b->nilai,0,',','.')); ?></td>
<?php if(auth()->user()->isBendahara()): ?><td>
<div class="actions">
<a class="button secondary" href="<?php echo e(route('v3.budgets.edit',$b->id)); ?>">Edit</a>
<form method="post" action="<?php echo e(route('v3.budgets.destroy',$b->id)); ?>" data-confirm="Hapus anggaran <?php echo e($b->tahun); ?> — <?php echo e($b->sumber_dana); ?>? Tindakan ini tidak dapat dibatalkan.">
<?php echo csrf_field(); ?>
 
<?php echo method_field('DELETE'); ?><button type="submit" class="danger">Hapus</button>
</form>
</div>
</td>
<?php endif; ?>
</tr>
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><tr>
<td colspan="<?php echo e(auth()->user()->isBendahara()?4:3); ?>">Belum ada anggaran.</td>
</tr>
<?php endif; ?>
</tbody>
</table>
</div>
</section>
<?php if(auth()->user()->isBendahara()): ?><dialog id="budget-dialog" aria-labelledby="budget-dialog-title" data-reopen="<?php echo e(old('_budget_form')==='1'?'yes':'no'); ?>">
<div class="between">
<h2 id="budget-dialog-title">Tambah anggaran</h2>
<button type="button" class="secondary" data-close-budget>Tutup</button>
</div>
<?php if($errors->any() && old('_budget_form')==='1'): ?><div class="notice error" role="alert">
<ul>
<?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><li><?php echo e($error); ?></li>
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</ul>
</div>
<?php endif; ?>
<p class="muted">Tahun dan sumber dana yang sama memperbarui nilai anggaran. Draf transaksi ikut mencadangkan anggaran; transaksi dibatalkan tidak dihitung.</p>
<form method="post" action="<?php echo e(route('v3.budgets.save')); ?>">
<?php echo csrf_field(); ?>
<input type="hidden" name="_budget_form" value="1">
<div class="grid">
<label>Tahun<input type="number" min="2000" max="2100" name="tahun" value="<?php echo e(old('tahun',date('Y'))); ?>" required>
</label>
<label>Sumber dana<input name="sumber_dana" value="<?php echo e(old('sumber_dana','BOS')); ?>" required maxlength="100">
</label>
<label>Nilai anggaran (Rp)<input type="number" data-rupiah min="0" max="99999999999999" step="0.01" name="nilai" value="<?php echo e(old('nilai')); ?>" required>
</label>
</div>
<div class="actions">
<button type="button" class="secondary" data-close-budget>Batal</button>
<button type="submit">Simpan anggaran</button>
</div>
</form>
</dialog>
<?php endif; ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('v3.layout', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\04 DATA ACO\S8\Capstone Project\silpj-V3\resources\views/v3/budgets.blade.php ENDPATH**/ ?>