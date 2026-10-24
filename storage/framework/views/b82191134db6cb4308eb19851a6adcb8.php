<?php $__env->startSection('title','Cadangan dan penyimpanan'); ?>
<?php $__env->startSection('content'); ?>
<section class="sa-card" id="cadangan">
<h2>Cadangan sistem</h2>
<p>Jadwal aplikasi: setiap hari pukul 01.00 WITA, menyimpan 14 cadangan terakhir. Scheduler server harus aktif. Daftar berikut menunjukkan berkas yang benar-benar tersedia, bukan jaminan scheduler sedang berjalan.</p>
<p>Database: <?php echo e(number_format($databaseBytes/1048576,2,',','.')); ?> MB · <?php echo e($assetCount); ?> berkas: <?php echo e(number_format($assetBytes/1048576,2,',','.')); ?> MB.</p>
<form method="post" action="<?php echo e(route('v3.backup')); ?>">
<?php echo csrf_field(); ?>
<label>Password administrator<input type="password" name="password" autocomplete="current-password" required></label>
<button class="sa-button sa-button-primary">Buat dan unduh cadangan sekarang</button>
</form>
<table><thead><tr><th>Cadangan otomatis / konsol (maks. 30 terbaru)</th><th>Waktu server</th><th>Ukuran</th></tr></thead><tbody>
<?php $__empty_1 = true; $__currentLoopData = $backups; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $backup): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
<tr><td><?php echo e($backup['name']); ?></td><td><?php echo e($backup['time']); ?></td><td><?php echo e(number_format($backup['size']/1048576,2,',','.')); ?> MB</td></tr>
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
<tr><td colspan="3">Belum ada cadangan tersimpan. Periksa aktivasi scheduler server.</td></tr>
<?php endif; ?>
</tbody></table>
</section>
<section class="sa-card">
<h2>Persiapan pemulihan</h2>
<p>Unggah cadangan untuk memeriksa struktur dan checksum (maks. 100 MB). Pemulihan mengganti data semua sekolah, sehingga dilanjutkan oleh administrator server melalui konsol dalam mode pemeliharaan. Berkas lama tanpa checksum tidak diterima oleh pemulihan otomatis ini.</p>
<form method="post" enctype="multipart/form-data" action="<?php echo e(route('system.backups.check')); ?>">
<?php echo csrf_field(); ?>
<label>Cadangan ZIP<input type="file" name="file" accept=".zip" required></label>
<label>Password administrator<input type="password" name="password" autocomplete="current-password" required></label>
<button class="sa-button sa-button-primary">Periksa cadangan</button>
</form>
<?php if(session('restore_file')): ?>
<p>Berkas siap diperiksa lebih lanjut melalui konsol: <code><?php echo e(session('restore_file')); ?></code>. Ikuti PANDUAN_PEMBARUAN.md. Berkas persiapan dihapus otomatis setelah 7 hari jika scheduler aktif.</p>
<?php endif; ?>
</section>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('system.layout', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\04 DATA ACO\S8\Capstone Project\silpj-V3\resources\views/system/backups.blade.php ENDPATH**/ ?>