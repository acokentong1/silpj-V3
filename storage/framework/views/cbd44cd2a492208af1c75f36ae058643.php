
<?php
 $batches=\App\Services\ReceiptService::batches($p);
 $received=\App\Services\ReceiptService::totals($p);
?>
<section class="card">
<h2>Penerimaan barang bertahap</h2>
<p class="muted">Catat setiap kedatangan. BAPB akhir memakai jumlah kumulatif setelah seluruh pesanan diterima. Mengubah transaksi atau mengganti nota pesanan memulai pencatatan penerimaan baru; tahap lama tetap tersimpan dalam riwayat.</p>
<?php $__currentLoopData = $batches; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $batch): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
<details>
<summary>Tahap <?php echo e($loop->iteration); ?> · <?php echo e($batch->tanggal); ?></summary>
<?php $__currentLoopData = json_decode($batch->items,true); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key=>$row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
<p>Barang <?php echo e($key); ?>: <?php echo e($row['baik']); ?> baik, <?php echo e($row['rusak']); ?> tidak baik · <?php echo e($row['catatan']??''); ?></p>
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</details>
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
<?php if($edit&&\App\Services\V3Workflow::current($p,'nopes')&&!\App\Services\V3Workflow::current($p,'bapb')): ?>
<details>
<summary>Catat tahap penerimaan</summary>
<form method="post" action="<?php echo e(route('v3.receipts.store',$p->id)); ?>">
<?php echo csrf_field(); ?>
<input type="hidden" name="token" value="<?php echo e((string)\Illuminate\Support\Str::uuid()); ?>">
<label>Tanggal penerimaan<input type="date" name="tanggal" value="<?php echo e(old('tanggal',$p->tanggal)); ?>" required>
</label>
<div class="table-wrap">
<table>
<thead>
<tr>
<th>Barang / pesanan</th>
<th>Sudah diterima</th>
<th>Baik tahap ini</th>
<th>Tidak baik tahap ini</th>
<th>Catatan</th>
</tr>
</thead>
<tbody>
<?php $__currentLoopData = $p->items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
<tr>
<td><?php echo e($item->nama); ?> / <?php echo e($item->volume); ?> <?php echo e($item->satuan); ?></td>
<td><?php echo e(bcadd($received[$item->urutan]['baik']??'0',$received[$item->urutan]['rusak']??'0',3)); ?></td>
<?php $__currentLoopData = ['baik','rusak']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $field): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
<td>
<input aria-label="<?php echo e($field); ?> <?php echo e($item->nama); ?>" type="number" min="0" step="0.001" max="<?php echo e($item->volume); ?>" name="items[<?php echo e($item->urutan); ?>][<?php echo e($field); ?>]" value="0" required>
</td>
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
<td>
<input aria-label="Catatan <?php echo e($item->nama); ?>" name="items[<?php echo e($item->urutan); ?>][catatan]" maxlength="255">
</td>
</tr>
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</tbody>
</table>
</div>
<button>Simpan tahap penerimaan</button>
</form>
</details>
<?php endif; ?>
</section>
<?php /**PATH D:\04 DATA ACO\S8\Capstone Project\silpj-V3\resources\views/v3/partials/receipts.blade.php ENDPATH**/ ?>