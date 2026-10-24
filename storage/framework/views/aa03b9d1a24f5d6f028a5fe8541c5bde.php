<?php $__env->startSection('title','Laporan & riwayat'); ?>
<?php $__env->startPush('page-assets'); ?>
<link rel="stylesheet" href="<?php echo e(asset('reports-workspace.css')); ?>?v=1">
<?php $__env->stopPush(); ?>
<?php $__env->startSection('content'); ?>
<div class="reports-workspace" data-compact-tabs>
<div class="compact-tabbar" aria-label="Bagian laporan" hidden></div>
<div id="report-recap" data-tab-label="Rekap belanja">
<section class="card report-filter">
<form method="get" action="<?php echo e(route('v3.reports')); ?>#report-recap">
<div class="report-filter-main">
<label>Tahun<input type="number" min="2000" max="2100" name="tahun" value="<?php echo e(request('tahun')); ?>">
</label>
<label>Bulan<select name="bulan">
<option value="">Semua bulan</option>
<?php for($m=1;$m<=12;$m++): ?><option value="<?php echo e($m); ?>" <?php if(request('bulan')==$m): echo 'selected'; endif; ?>><?php echo e(['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'][$m-1]); ?></option>
<?php endfor; ?>
</select>
</label>
<label>Status<select name="status">
<option value="">Semua status</option>
<?php $__currentLoopData = ['draf','diajukan','disetujui','revisi','dibatalkan']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $status): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option <?php if(request('status')===$status): echo 'selected'; endif; ?>><?php echo e($status); ?></option>
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</select>
</label>
<div class="actions"><button>Terapkan filter</button><a class="button secondary" href="<?php echo e(route('v3.reports')); ?>#report-recap">Reset</a></div>
</div>
<details class="report-filter-more" <?php if(request()->filled('from') || request()->filled('to') || request()->filled('activity_id') || request()->filled('sumber_dana')): ?> open <?php endif; ?>>
<summary>Filter lainnya <span class="muted">Tanggal, kegiatan, sumber dana</span></summary>
<div class="report-filter-extra">
<label>Dari tanggal<input type="date" name="from" value="<?php echo e(request('from')); ?>">
</label>
<label>Sampai tanggal<input type="date" name="to" value="<?php echo e(request('to')); ?>">
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
</div>
</details>
<p class="report-hint muted">Filter berlaku untuk rekap dan ekspor. Kosongkan filter untuk melihat semua transaksi.</p>
</form>
</section>
<section class="card">
<div class="between">
<h2>Rekap belanja <span class="badge"><?php echo e($rows->total()); ?> transaksi</span></h2>
<div class="actions">
<a class="button" href="<?php echo e(route('v3.excel',request()->except(['page','events_page']))); ?>">Unduh Excel</a>
<a class="button secondary" href="<?php echo e(route('v3.csv',request()->except(['page','events_page']))); ?>">CSV</a>
<a class="button secondary" href="<?php echo e(route('v3.final',array_merge(request()->except(['page','events_page']),['tahun'=>request('tahun')?:date('Y')]))); ?>">LPJ final PDF (disetujui)</a>
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
<?php $__empty_1 = true; $__currentLoopData = $rows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><tr>
<td>
<a href="<?php echo e(route('v3.purchase',$p->id)); ?>"><?php echo e($p->nomor_bukti); ?></a>
<br><?php echo e($p->activity->nama); ?></td>
<td><?php echo e($p->vendor->nama); ?></td>
<td class="number"><?php echo e(number_format($p->bruto,0,',','.')); ?></td>
<td class="number"><?php echo e(number_format($p->potongan,0,',','.')); ?></td>
<td class="number"><?php echo e(number_format($p->netto,0,',','.')); ?></td>
<td><span class="badge <?php echo e($p->status); ?>"><?php echo e(ucfirst($p->status)); ?></span></td>
</tr>
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
<tr><td colspan="6" class="empty">Tidak ada transaksi yang sesuai filter.</td></tr>
<?php endif; ?>
</tbody>
</table>
</div>
<?php echo $__env->make('v3.partials.report-pagination', ['pager'=>$rows, 'anchor'=>'report-recap', 'noun'=>'transaksi'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
</section>
</div>
<section class="card" id="report-history" data-tab-label="Riwayat aktivitas">
<div class="between"><h2>Riwayat aktivitas</h2><span class="badge"><?php echo e($events->total()); ?> aktivitas</span></div>
<p class="report-hint muted">Aktivitas sekolah dari yang terbaru, 10 baris per halaman. Filter rekap tidak berlaku pada riwayat.</p>
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
<?php $__empty_1 = true; $__currentLoopData = $events; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $e): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><tr>
<td><?php echo e($e->created_at); ?></td>
<td><?php echo e($e->name); ?></td>
<td><?php echo e(str_replace('_',' ',$e->action)); ?></td>
<td class="report-reason"><?php if($e->reason): ?><details><summary>Lihat alasan</summary><p><?php echo e($e->reason); ?></p></details><?php else: ?><span class="muted">Tidak ada alasan</span><?php endif; ?></td>
</tr>
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
<tr><td colspan="4" class="empty">Belum ada aktivitas sekolah.</td></tr>
<?php endif; ?>
</tbody>
</table>
</div>
<?php echo $__env->make('v3.partials.report-pagination', ['pager'=>$events, 'anchor'=>'report-history', 'noun'=>'aktivitas'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
</section>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('v3.layout', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\04 DATA ACO\S8\Capstone Project\silpj-V3\resources\views/v3/reports.blade.php ENDPATH**/ ?>