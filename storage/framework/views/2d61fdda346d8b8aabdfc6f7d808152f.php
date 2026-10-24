<div class="component" data-component>
<label>Nama komponen<input data-field="label" name="components[<?php echo e($i); ?>][label]" value="<?php echo e($c['label']); ?>" placeholder="PPN / PPh21 / SPPD" maxlength="60" required>
</label>
<label>Kategori<select data-field="kategori" name="components[<?php echo e($i); ?>][kategori]">
<?php $__currentLoopData = ['pajak'=>'Pajak','lainnya'=>'Komponen lain']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $v=>$label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($v); ?>" <?php if($c['kategori']===$v): echo 'selected'; endif; ?>><?php echo e($label); ?></option>
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</select>
</label>
<label>Metode<select data-field="metode" name="components[<?php echo e($i); ?>][metode]">
<?php $__currentLoopData = ['nominal'=>'Nominal','persentase'=>'Persentase']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $v=>$label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($v); ?>" <?php if($c['metode']===$v): echo 'selected'; endif; ?>><?php echo e($label); ?></option>
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</select>
</label>
<label>Perlakuan<select data-field="perlakuan" name="components[<?php echo e($i); ?>][perlakuan]">
<?php $__currentLoopData = ['termasuk'=>'Sudah termasuk harga','tambah'=>'Menambah tagihan','potong'=>'Memotong pembayaran']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $v=>$label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($v); ?>" <?php if($c['perlakuan']===$v): echo 'selected'; endif; ?>><?php echo e($label); ?></option>
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</select>
</label>
<label data-mode="nominal">Nominal (Rp)<input data-field="nominal" name="components[<?php echo e($i); ?>][nominal]" type="number" data-rupiah min="0" max="99999999999999" step="0.01" value="<?php echo e($c['nominal']??0); ?>">
</label>
<label data-mode="persentase">Persentase (%)<input data-field="persentase" name="components[<?php echo e($i); ?>][persentase]" type="number" min="0" max="100" step="0.0001" value="<?php echo e($c['persentase']??''); ?>">
</label>
<label data-mode="persentase">Dasar perhitungan (Rp)<input data-field="dasar" name="components[<?php echo e($i); ?>][dasar]" type="number" data-rupiah min="0" max="99999999999999" step="0.01" value="<?php echo e($c['dasar']??''); ?>">
</label>
<div class="actions">
<strong data-component-amount>Rp0</strong>
<button class="secondary small" type="button" data-remove-component>Hapus</button>
</div>
</div>
<?php /**PATH D:\04 DATA ACO\S8\Capstone Project\silpj-V3\resources\views/v3/component-row.blade.php ENDPATH**/ ?>