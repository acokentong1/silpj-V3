<?php $__env->startSection('title','Laporan & riwayat'); ?>
<?php $__env->startSection('content'); ?>
<section class="card">
<form method="get" class="grid">
<label>Dari tanggal<input type="date" name="from" value="<?php echo e(request('from')); ?>">
</label>
<label>Sampai tanggal<input type="date" name="to" value="<?php echo e(request('to')); ?>">
</label>
<label>Tahun<input type="number" min="2000" max="2100" name="tahun" value="<?php echo e(request('tahun')); ?>">
</label>
<label>Bulan<select name="bulan">
<option value="">Semua bulan</option>
<?php for($m=1;$m<=12;$m++): ?><option value="<?php echo e($m); ?>" <?php if(request('bulan')==$m): echo 'selected'; endif; ?>><?php echo e($m); ?></option>
<?php endfor; ?>
</select>
</label>
<label>Status<select name="status">
<option value="">Semua status</option>
<?php $__currentLoopData = ['draf','diajukan','disetujui','revisi','dibatalkan']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $status): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option <?php if(request('status')===$status): echo 'selected'; endif; ?>><?php echo e($status); ?></option>
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</select>
</label>
<label>Kegiatan<select name="activity_id">
<option value="">Semua kegiatan</option>
<?php $__currentLoopData = $activities; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $activity): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($activity->id); ?>" <?php if(request('activity_id')==$activity->id): echo 'selected'; endif; ?>><?php echo e($activity->nama); ?></option>
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</select>
</label>
<label>Sumber dana<select name="sumber_dana">
<option value="">Semua sumber</option>
<?php $__currentLoopData = $funds; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $fund): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option <?php if(request('sumber_dana')===$fund): echo 'selected'; endif; ?>><?php echo e($fund); ?></option>
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</select>
</label>
<div class="actions">
<button>Terapkan filter</button>
<a href="<?php echo e(route('v3.reports')); ?>">Reset</a>
</div>
</form>
<p class="muted">Filter berlaku untuk rekap dan ekspor. Riwayat aktivitas sekolah ditampilkan terpisah.</p>
</section>
<section class="card">
<div class="between">
<h2>Rekap belanja</h2>
<div class="actions">
<a class="button" href="<?php echo e(route('v3.excel',request()->query())); ?>">Unduh Excel</a>
<a class="button secondary" href="<?php echo e(route('v3.csv',request()->query())); ?>">CSV</a>
</div>
</div>
<p class="muted">Transaksi dibatalkan ditampilkan untuk riwayat; keluarkan dari perhitungan realisasi.</p>
<div class="table-wrap">
<table>
<thead>
<tr>
<th>Kegiatan / Nomor</th>
<th>Rekanan</th>
<th>Bruto</th>
<th>Potongan</th>
<th>Netto</th>
<th>Status</th>
</tr>
</thead>
<tbody>
<?php $__currentLoopData = $rows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><tr>
<td>
<a href="<?php echo e(route('v3.purchase',$p->id)); ?>"><?php echo e($p->nomor_bukti); ?></a>
<br><?php echo e($p->activity->nama); ?></td>
<td><?php echo e($p->vendor->nama); ?></td>
<td class="number"><?php echo e(number_format($p->bruto,0,',','.')); ?></td>
<td class="number"><?php echo e(number_format($p->potongan,0,',','.')); ?></td>
<td class="number"><?php echo e(number_format($p->netto,0,',','.')); ?></td>
<td><?php echo e($p->status); ?></td>
</tr>
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</tbody>
</table>
</div><?php echo e($rows->links()); ?></section>
<section class="card">
<h2>100 aktivitas terbaru</h2>
<div class="table-wrap">
<table>
<thead>
<tr>
<th>Waktu</th>
<th>Pengguna</th>
<th>Tindakan</th>
<th>Alasan</th>
</tr>
</thead>
<tbody>
<?php $__currentLoopData = $events; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $e): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><tr>
<td><?php echo e($e->created_at); ?></td>
<td><?php echo e($e->name); ?></td>
<td><?php echo e(str_replace('_',' ',$e->action)); ?></td>
<td><?php echo e($e->reason); ?></td>
</tr>
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</tbody>
</table>
</div>
</section>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('v3.layout', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\04 DATA ACO\S8\Capstone Project\silpj-V3\resources\views/v3/reports.blade.php ENDPATH**/ ?>