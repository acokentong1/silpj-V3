<?php $__env->startSection('title','Persetujuan LPJ'); ?>
<?php $__env->startSection('content'); ?>
<p class="muted">Semua pengajuan sekolah dalam satu halaman. Buka transaksi untuk memeriksa dokumen dan memberikan keputusan.</p>
<div class="approval-summary">
<?php $__currentLoopData = ['diajukan'=>'Menunggu persetujuan','disetujui'=>'Sudah disetujui','revisi'=>'Perlu revisi']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key=>$label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
<a class="approval-summary-card <?php echo e($status===$key?'is-selected':''); ?>" href="<?php echo e(route('v3.approvals',array_filter(['status'=>$key,'tahun'=>request('tahun')]))); ?>" <?php if($status===$key): ?> aria-current="page" <?php endif; ?>>
<span><?php echo e($label); ?></span><strong><?php echo e($counts[$key]??0); ?></strong><small><?php echo e(request('tahun')?'Tahun '.request('tahun'):'Semua tahun anggaran'); ?></small>
</a>
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</div>
<section class="card">
<form method="get" action="<?php echo e(route('v3.approvals')); ?>" class="approval-filters">
<input type="hidden" name="status" value="<?php echo e($status); ?>">
<label for="approval-search">Cari pengajuan<input id="approval-search" name="q" value="<?php echo e(request('q')); ?>" maxlength="100" placeholder="Nomor bukti, kegiatan, atau rekanan"></label>
<label for="approval-year">Tahun anggaran<select id="approval-year" name="tahun"><option value="">Semua tahun</option>
<?php $__currentLoopData = $years; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $year): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
<option value="<?php echo e($year); ?>" <?php if((string)request('tahun')===(string)$year): echo 'selected'; endif; ?>><?php echo e($year); ?></option>
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</select></label>
<div class="actions"><button type="submit">Tampilkan</button><a href="<?php echo e(route('v3.approvals',['status'=>$status])); ?>">Reset</a></div>
</form>
<div class="between approval-list-heading"><h2><?php echo e(['diajukan'=>'Menunggu persetujuan','disetujui'=>'Sudah disetujui','revisi'=>'Perlu revisi'][$status]); ?></h2><span class="muted"><?php echo e($purchases->total()); ?> transaksi</span></div>
<div class="table-wrap">
<table><thead><tr><th>Transaksi / kegiatan</th><th>Rekanan</th><th>Tanggal / dana</th><th class="number">Nilai bruto</th><th>Status</th><th>Tindakan</th></tr></thead><tbody>
<?php $__empty_1 = true; $__currentLoopData = $purchases; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
<tr>
<td><strong><?php echo e($p->nomor_bukti); ?></strong><br><span><?php echo e($p->activity->nama); ?></span></td>
<td><?php echo e($p->vendor->nama); ?></td>
<td><?php echo e($p->tanggal); ?><br><small><?php echo e($p->activity->budget->sumber_dana); ?> · <?php echo e($p->tahun); ?></small></td>
<td class="number">Rp<?php echo e(number_format($p->bruto,0,',','.')); ?></td>
<td><span class="badge <?php echo e($p->status); ?>"><?php echo e(['diajukan'=>'Menunggu','disetujui'=>'Disetujui','revisi'=>'Perlu revisi'][$p->status]); ?></span></td>
<td><a class="button small" href="<?php echo e(route('v3.purchase',$p->id)); ?>"><?php echo e($status==='diajukan'?'Periksa LPJ':'Lihat rincian'); ?></a>
<?php if($status==='diajukan'&&(auth()->id()===$p->created_by||auth()->id()===$p->submitted_by)): ?>
<small class="review-own">Persetujuan memerlukan pemeriksa lain.</small>
<?php endif; ?>
</td>
</tr>
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
<tr><td colspan="6"><div class="approval-empty"><strong><?php echo e(request()->filled('q')?'Tidak ada transaksi yang cocok.':'Belum ada transaksi pada daftar ini.'); ?></strong><p><?php echo e($status==='diajukan'?'Pengajuan dari bendahara akan tampil di sini setelah diajukan.':'Coba pilih status atau tahun anggaran lain.'); ?></p></div></td></tr>
<?php endif; ?>
</tbody></table>
</div>
<div class="pagination"><?php echo e($purchases->links()); ?></div>
</section>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('v3.layout', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\04 DATA ACO\S8\Capstone Project\silpj-V3\resources\views/v3/approvals.blade.php ENDPATH**/ ?>