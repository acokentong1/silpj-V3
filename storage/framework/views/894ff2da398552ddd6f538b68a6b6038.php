
<?php ($changes=\App\Services\RevisionDiff::rows($e->details)); ?>
<?php if(count($changes)): ?>
<details>
<summary>Lihat perbandingan sebelum dan sesudah</summary>
<div class="table-wrap">
<table>
<thead>
<tr>
<th>Bagian</th>
<th>Sebelumnya</th>
<th>Terbaru</th>
</tr>
</thead>
<tbody>
<?php $__currentLoopData = $changes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $change): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
<tr>
<td><?php echo e(str_replace(['items.','components.','vendor.'],['Barang (indeks mulai 0) ','Komponen (indeks mulai 0) ','Rekanan '],$change['field'])); ?></td>
<td><?php echo e($change['before'] ?? '—'); ?></td>
<td><?php echo e($change['after'] ?? '—'); ?></td>
</tr>
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</tbody>
</table>
</div>
</details>
<?php endif; ?>
<?php /**PATH D:\04 DATA ACO\S8\Capstone Project\silpj-V3\resources\views/v3/partials/revision-diff.blade.php ENDPATH**/ ?>