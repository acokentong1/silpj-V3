<section class="card" aria-labelledby="budget-summary-title">
<h2 id="budget-summary-title">Penggunaan anggaran sekolah · <?php echo e($selectedYear); ?></h2>
<p class="muted">Data tahun anggaran <?php echo e($selectedYear); ?>. Penggunaan dihitung dari nilai bruto belanja aktif, termasuk draf dan transaksi dalam pemeriksaan; transaksi dibatalkan tidak dihitung. Angka ini menunjukkan anggaran terpakai/dicadangkan, bukan hanya pembayaran yang sudah lunas.</p>
<div class="stats budget-stats">
<?php $__currentLoopData = ['total'=>'Total anggaran','used'=>'Terpakai / dicadangkan','remaining'=>'Sisa anggaran']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key=>$label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
<div class="stat"><small><?php echo e($label); ?></small><strong>Rp<?php echo e(number_format($budgetSummary[$key],0,',','.')); ?></strong></div>
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
<div class="stat"><small>Persentase penggunaan</small><strong><?php echo e($budgetSummary['percentage']===null?'—':number_format($budgetSummary['percentage'],2,',','.').'%'); ?></strong></div>
</div>
<?php if($budgetSummary['percentage']!==null): ?><progress class="budget-progress" max="100" value="<?php echo e(min(100,max(0,(float)$budgetSummary['percentage']))); ?>" aria-label="Persentase penggunaan anggaran tahun <?php echo e($selectedYear); ?>"></progress><?php else: ?><p class="muted">Persentase belum tersedia karena total anggaran masih nol.</p><?php endif; ?>
<div class="table-wrap"><table><thead><tr><th>Tahun / Sumber dana</th><th class="number">Anggaran</th><th class="number">Terpakai / dicadangkan</th><th class="number">Sisa</th><th>Penggunaan</th></tr></thead><tbody>
<?php $__empty_1 = true; $__currentLoopData = $budgets; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $b): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><tr><td><?php echo e($b->tahun); ?> · <?php echo e($b->sumber_dana); ?></td><td class="number">Rp<?php echo e(number_format($b->nilai,0,',','.')); ?></td><td class="number">Rp<?php echo e(number_format($b->used,0,',','.')); ?></td><td class="number">Rp<?php echo e(number_format($b->remaining,0,',','.')); ?></td><td>
<?php if($b->percentage!==null): ?><strong><?php echo e(number_format($b->percentage,2,',','.')); ?>%</strong><progress class="budget-progress" max="100" value="<?php echo e(min(100,max(0,(float)$b->percentage))); ?>" aria-label="Penggunaan anggaran <?php echo e($b->tahun); ?> <?php echo e($b->sumber_dana); ?>"></progress><?php else: ?><span class="muted">— (anggaran nol)</span><?php endif; ?>
</td></tr><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><tr><td colspan="5">Belum ada anggaran sekolah.</td></tr><?php endif; ?>
</tbody></table></div>
</section>
<?php /**PATH D:\04 DATA ACO\S8\Capstone Project\silpj-V3\resources\views/v3/budget-summary.blade.php ENDPATH**/ ?>