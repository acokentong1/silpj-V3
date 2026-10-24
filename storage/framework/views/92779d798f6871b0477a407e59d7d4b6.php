<?php $__env->startSection('title',$p?'Ubah transaksi belanja':'Tambah transaksi belanja'); ?>
<?php $__env->startSection('content'); ?>
<p>
<a href="<?php echo e(route('v3.activity',$a->id)); ?>">← <?php echo e($a->nama); ?></a> · <?php echo e($a->budget->tahun); ?> / <?php echo e($a->budget->sumber_dana); ?></p>
<div class="steps">
<span>1 · Kegiatan dipilih</span>
<span>2 · Rekanan & barang</span>
<span>3 · Pajak mandiri</span>
<span>4 · Dokumen</span>
</div>
<form method="post" action="<?php echo e($p?route('v3.update',[$a->id,$p->id]):route('v3.store',$a->id)); ?>" id="purchase-form">
<?php echo csrf_field(); ?>
 
<?php if($p): ?>
<?php echo method_field('PUT'); ?>
<?php endif; ?>
<section class="card">
<h2>
<span class="section-num">2</span>Identitas transaksi & rekanan</h2>
<div class="grid">
<label>Nomor bukti<input name="nomor_bukti" required maxlength="100" value="<?php echo e(old('nomor_bukti',$p?->nomor_bukti)); ?>">
</label>
<label>Tanggal transaksi<input type="date" name="tanggal" required value="<?php echo e(old('tanggal',$p?->tanggal??($a->budget->tahun.'-'.date('m-d')))); ?>">
</label>
<label>Kategori<select name="kategori">
<?php $__currentLoopData = ['Barang','Jasa']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $v): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option <?php if(old('kategori',$p?->kategori)==$v): echo 'selected'; endif; ?>><?php echo e($v); ?></option>
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</select>
</label>
<label>Nama toko / rekanan<input name="vendor_nama" required maxlength="255" value="<?php echo e(old('vendor_nama',$p?->vendor?->nama)); ?>">
</label>
<label>Nama pihak penerima<input name="vendor_penerima" required maxlength="255" value="<?php echo e(old('vendor_penerima',$p?->vendor?->penerima)); ?>">
</label>
<label>NPWP (opsional)<input name="vendor_npwp" maxlength="50" value="<?php echo e(old('vendor_npwp',$p?->vendor?->npwp)); ?>">
</label>
<label class="wide">Alamat rekanan<input name="vendor_alamat" maxlength="1000" value="<?php echo e(old('vendor_alamat',$p?->vendor?->alamat)); ?>">
</label>
</div>
</section>
<section class="card">
<div class="between">
<h2>Rincian barang</h2>
<button type="button" class="secondary" id="add-item">+ Tambah barang</button>
</div>
<div class="table-wrap">
<table class="item-table">
<thead>
<tr>
<th>Nama barang</th>
<th>Volume</th>
<th>Satuan</th>
<th>Harga satuan (Rp)</th>
<th class="number">Jumlah</th>
<th>
</th>
</tr>
</thead>
<tbody id="items">
<?php $__currentLoopData = old('items',$p?->items?->toArray()??[['nama'=>'','volume'=>1,'satuan'=>'','harga'=>0]]); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i=>$item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
<tr data-item>
<td class="name">
<input aria-label="Nama barang" data-field="nama" name="items[<?php echo e($i); ?>][nama]" value="<?php echo e($item['nama']); ?>" required maxlength="255">
</td>
<td>
<input aria-label="Volume" data-field="volume" name="items[<?php echo e($i); ?>][volume]" type="number" min="0.001" max="1000000" step="0.001" value="<?php echo e($item['volume']); ?>" required>
</td>
<td>
<input aria-label="Satuan" data-field="satuan" name="items[<?php echo e($i); ?>][satuan]" value="<?php echo e($item['satuan']); ?>" required maxlength="40">
</td>
<td>
<input aria-label="Harga satuan" data-field="harga" name="items[<?php echo e($i); ?>][harga]" type="number" data-rupiah min="0" max="1000000000" step="0.01" value="<?php echo e($item['harga']); ?>" required>
</td>
<td class="number" data-amount>0</td>
<td>
<button type="button" class="secondary small" data-remove-item aria-label="Hapus barang">×</button>
</td>
</tr>
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</tbody>
</table>
</div>
</section>
<section class="card">
<div class="between">
<h2>
<span class="section-num">3</span>Pajak & komponen lain</h2>
<button type="button" class="secondary" id="add-component">+ Tambah komponen</button>
</div>
<p class="muted">Masukkan nominal atau persentase sendiri. Pilih “Sudah termasuk” jika nilai hanya dirinci dan tidak mengubah total.</p>
<div id="components">
<?php $__currentLoopData = old('components',$p?->components?->toArray()??[]); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i=>$c): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
<?php echo $__env->make('v3.component-row',['i'=>$i,'c'=>$c], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</div>
<template id="component-template">
<?php echo $__env->make('v3.component-row',['i'=>'INDEX','c'=>['label'=>'','kategori'=>'pajak','metode'=>'nominal','perlakuan'=>'termasuk']], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?></template>
<div class="total-box">
<div>
<span>Subtotal barang</span>
<strong id="sum-subtotal">Rp0</strong>
</div>
<div>
<span>Penambah</span>
<strong id="sum-add">Rp0</strong>
</div>
<div>
<span>Nilai bruto</span>
<strong id="sum-bruto">Rp0</strong>
</div>
<div>
<span>Potongan</span>
<strong id="sum-potong">Rp0</strong>
</div>
<div class="grand">
<span>Diterima rekanan</span>
<strong id="sum-netto">Rp0</strong>
</div>
<small>Total akan dihitung ulang ketika disimpan.</small>
</div>
</section>
<?php if($p): ?><section class="card">
<label>Alasan perubahan<textarea name="reason" required minlength="5" maxlength="2000"><?php echo e(old('reason')); ?></textarea>
</label>
<p class="muted">Dokumen terbit sebelumnya menjadi arsip. Terbitkan kembali dokumen berdasarkan rincian terbaru.</p>
</section>
<?php endif; ?>
<div class="actions">
<button>Simpan transaksi</button>
<a class="button secondary" href="<?php echo e(route('v3.activity',$a->id)); ?>">Kembali</a>
</div>
</form>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('v3.layout', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\04 DATA ACO\S8\Capstone Project\silpj-V3\resources\views/v3/form.blade.php ENDPATH**/ ?>