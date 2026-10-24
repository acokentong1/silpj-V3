
<?php $__env->startPush('page-assets'); ?>
<link rel="stylesheet" href="<?php echo e(asset('transaction-documents.css')); ?>?v=1">
<script src="<?php echo e(asset('transaction-documents.js')); ?>?v=1" defer>
</script>
<?php $__env->stopPush(); ?>
<?php
 $documentLabels=['nopes'=>'Nota Pesanan','bapb'=>'Berita Acara Pemeriksaan Barang','kuitansi'=>'Kuitansi'];
 $documentDescriptions=['nopes'=>'Rincian pemesanan barang atau jasa kepada rekanan.','bapb'=>'Hasil pemeriksaan kondisi dan jumlah barang yang diterima.','kuitansi'=>'Bukti pembayaran dan pelunasan transaksi kepada rekanan.'];
 $currentDocs=$p->documents->where('status','terbit')->where('revision',$p->revision)->keyBy('jenis');
?>
<section class="card document-workspace" aria-labelledby="documents-title">
<div class="document-section-heading">
<div>
<p class="eyebrow">KELENGKAPAN TRANSAKSI</p>
<h2 id="documents-title">Siapkan dokumen LPJ</h2>
<p class="muted">Mulai dari nota pesanan, lanjutkan pemeriksaan barang, lalu terbitkan kuitansi.</p>
</div>
<span class="document-progress"><?php echo e($currentDocs->count()); ?> dari 3 dokumen terbit</span>
</div>
<div class="document-cards">
<?php $__currentLoopData = $documentLabels; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $kind=>$label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
<?php
 $currentDoc=$currentDocs->get($kind);
 $previousKind=match($kind){'bapb'=>'nopes','kuitansi'=>'bapb',default=>null};
 $previousDoc=$previousKind?$currentDocs->get($previousKind):null;
 $ready=$kind==='nopes'||($currentDocs->has('nopes')&&($kind!=='kuitansi'||$currentDocs->has('bapb')));
 $defaultDate=max((string)$p->tanggal,(string)($previousDoc?->tanggal??$p->tanggal));
 $minDate=$previousDoc?->tanggal??($p->tahun.'-01-01');
?>
<article class="document-option <?php echo e($currentDoc?'document-complete':(!$ready?'document-waiting':'')); ?>">
<div class="document-option-top">
<span class="document-step" aria-label="Langkah <?php echo e($loop->iteration); ?>">0<?php echo e($loop->iteration); ?></span>
<span class="document-state <?php echo e($currentDoc?'state-complete':($edit&&$ready?'state-ready':'state-waiting')); ?>"><?php echo e($currentDoc?'Sudah terbit':(!$edit?'Belum terbit':($ready?'Siap dibuat':'Menunggu'))); ?></span>
</div>
<div class="document-paper" aria-hidden="true">
<svg viewBox="0 0 40 48" fill="none">
<path d="M8 3h17l9 9v33H8z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
<path d="M25 3v10h9M14 23h14M14 29h14M14 35h9" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
</svg>
</div>
<h3><?php echo e($label); ?></h3>
<p class="document-description"><?php echo e($documentDescriptions[$kind]); ?></p>
<div class="document-option-bottom">
<?php if($currentDoc): ?><div class="document-number"><?php echo e($currentDoc->nomor); ?></div>
<div class="document-card-actions">
<a class="button" href="<?php echo e(route('v3.document',$currentDoc->id)); ?>">Lihat / cetak</a>
<a class="button secondary" href="<?php echo e(route('v3.document',['id'=>$currentDoc->id,'pdf'=>1])); ?>">PDF</a>
</div>
<?php elseif($edit&&$ready): ?><p class="document-hint">Lengkapi isian sebelum dokumen diterbitkan.</p>
<button type="button" class="document-create" data-document-open="document-form-<?php echo e($kind); ?>" aria-haspopup="dialog">Buat <?php echo e($kind==='bapb'?'berita acara':strtolower($label)); ?> <span aria-hidden="true">↗</span>
</button>
<?php else: ?><p class="document-hint"><?php echo e(!$edit?'Penerbitan tidak tersedia untuk akun atau status transaksi ini.':'Terbitkan '.$documentLabels[$previousKind].' terlebih dahulu.'); ?></p>
<button type="button" class="document-create" disabled><?php echo e(!$edit?'Penerbitan tidak tersedia':'Menunggu dokumen sebelumnya'); ?></button>
<?php endif; ?>
</div>
</article>
<?php if($edit&&!$currentDoc&&$ready): ?>
<dialog class="document-dialog <?php echo e($kind==='bapb'?'document-dialog-wide':''); ?>" id="document-form-<?php echo e($kind); ?>" aria-labelledby="document-title-<?php echo e($kind); ?>" 
<?php if($errors->any()&&old('jenis')===$kind): ?> data-document-reopen="true" 
<?php endif; ?>
>
<div class="document-dialog-header">
<div>
<p class="eyebrow">BUAT DOKUMEN · LANGKAH <?php echo e($loop->iteration); ?></p>
<h2 id="document-title-<?php echo e($kind); ?>"><?php echo e($label); ?></h2>
</div>
<button type="button" class="secondary document-close" data-document-close aria-label="Tutup formulir <?php echo e($label); ?>">×</button>
</div>
<p class="document-dialog-intro">Transaksi <strong><?php echo e($p->nomor_bukti); ?></strong> · <?php echo e($p->vendor->nama); ?></p>
<?php if($errors->any()&&old('jenis')===$kind): ?><div class="notice error" role="alert">
<strong>Periksa kembali isian.</strong>
<ul>
<?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><li><?php echo e($error); ?></li>
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</ul>
</div>
<?php endif; ?>
<form method="post" action="<?php echo e(route('v3.issue',$p->id)); ?>">
<?php echo csrf_field(); ?>
<input type="hidden" name="jenis" value="<?php echo e($kind); ?>">
<div class="document-form-section">
<h3>Informasi dokumen</h3>
<div class="grid">
<label for="document-date-<?php echo e($kind); ?>">Tanggal <?php echo e($kind==='bapb'?'berita acara':strtolower($label)); ?><input id="document-date-<?php echo e($kind); ?>" name="tanggal" type="date" min="<?php echo e($minDate); ?>" max="<?php echo e($p->tahun); ?>-12-31" required value="<?php echo e(old('jenis')===$kind?old('tanggal',$defaultDate):$defaultDate); ?>">
</label>
<?php if($kind==='kuitansi'): ?><label for="document-paid-date">Tanggal lunas bayar<input id="document-paid-date" name="tanggal_lunas" type="date" min="<?php echo e($minDate); ?>" max="<?php echo e($p->tahun); ?>-12-31" required value="<?php echo e(old('jenis')===$kind?old('tanggal_lunas',$defaultDate):$defaultDate); ?>">
</label>
<?php endif; ?>
</div>
<p class="document-field-help">Gunakan tanggal dalam tahun anggaran <?php echo e($p->tahun); ?>

<?php if($previousDoc): ?>
, pada atau setelah tanggal dokumen sebelumnya
<?php endif; ?>
.</p>
</div>
<?php if($kind==='bapb'): ?><div class="document-form-section">
<h3>Hasil pemeriksaan barang</h3>
<p class="document-field-help">Jumlah baik dan tidak baik harus sama dengan volume pesanan. Jika tahap penerimaan sudah dicatat, sistem memakai total tahap tersebut dan mengabaikan isian jumlah di bawah.</p>
<div class="table-wrap document-inspection">
<table>
<thead>
<tr>
<th>Barang / pesanan</th>
<th>Baik</th>
<th>Tidak baik</th>
<th>Catatan pemeriksaan</th>
</tr>
</thead>
<tbody>
<?php $__currentLoopData = $p->items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><tr>
<td>
<strong><?php echo e($item->nama); ?></strong>
<br>
<small><?php echo e((float)$item->volume); ?> <?php echo e($item->satuan); ?></small>
</td>
<td>
<input aria-label="Volume baik <?php echo e($item->nama); ?>" name="inspection[<?php echo e($item->urutan); ?>][baik]" type="number" min="0" max="1000000" step="0.001" required value="<?php echo e(old('inspection.'.$item->urutan.'.baik',$received[$item->urutan]['baik']??$item->volume)); ?>">
</td>
<td>
<input aria-label="Volume tidak baik <?php echo e($item->nama); ?>" name="inspection[<?php echo e($item->urutan); ?>][rusak]" type="number" min="0" max="1000000" step="0.001" required value="<?php echo e(old('inspection.'.$item->urutan.'.rusak',$received[$item->urutan]['rusak']??0)); ?>">
</td>
<td>
<input aria-label="Catatan pemeriksaan <?php echo e($item->nama); ?>" name="inspection[<?php echo e($item->urutan); ?>][catatan]" maxlength="255" placeholder="Opsional" value="<?php echo e(old('inspection.'.$item->urutan.'.catatan')); ?>">
</td>
</tr>
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</tbody>
</table>
</div>
</div>
<?php endif; ?>
<div class="document-form-section">
<label for="document-notes-<?php echo e($kind); ?>">Catatan tambahan <span class="muted">(opsional)</span>
<textarea id="document-notes-<?php echo e($kind); ?>" name="catatan" maxlength="2000" rows="3" placeholder="Tambahkan keterangan bila diperlukan"><?php echo e(old('jenis')===$kind?old('catatan'):''); ?></textarea>
</label>
</div>
<div class="document-publish-note">Dokumen yang diterbitkan menyimpan salinan data transaksi saat ini.</div>
<div class="document-dialog-footer">
<button type="button" class="secondary" data-document-close>Kembali</button>
<button type="submit">Terbitkan <?php echo e($kind==='bapb'?'berita acara':strtolower($label)); ?></button>
</div>
</form>
</dialog>
<?php endif; ?>
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</div>
<div class="document-history">
<div class="document-section-heading">
<div>
<h3>Riwayat dokumen</h3>
<p class="muted">Dokumen aktif dan arsip versi sebelumnya.</p>
</div>
</div>
<div class="table-wrap">
<table>
<thead>
<tr>
<th>Dokumen / nomor</th>
<th>Tanggal</th>
<th>Versi / status</th>
<th>Tindakan</th>
</tr>
</thead>
<tbody>
<?php $__empty_1 = true; $__currentLoopData = $p->documents; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $doc): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><tr>
<td>
<strong><?php echo e($documentLabels[$doc->jenis]??strtoupper($doc->jenis)); ?></strong>
<br>
<small><?php echo e($doc->nomor); ?></small>
</td>
<td><?php echo e($doc->tanggal); ?></td>
<td>v<?php echo e($doc->versi); ?> · <?php echo e($doc->status); ?></td>
<td>
<div class="actions">
<a href="<?php echo e(route('v3.document',$doc->id)); ?>">Lihat / cetak</a>
<a href="<?php echo e(route('v3.document',['id'=>$doc->id,'pdf'=>1])); ?>">PDF</a>
<?php if($edit&&$doc->status==='terbit'): ?><form method="post" action="<?php echo e(route('v3.document.cancel',$doc->id)); ?>" data-confirm="Batalkan <?php echo e($documentLabels[$doc->jenis]??strtoupper($doc->jenis)); ?> dan dokumen turunannya?">
<?php echo csrf_field(); ?>
<button class="secondary small">Batalkan</button>
</form>
<?php endif; ?>
</div>
</td>
</tr>
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><tr>
<td colspan="4" class="empty">Belum ada dokumen terbit. Mulai dari nota pesanan di atas.</td>
</tr>
<?php endif; ?>
</tbody>
</table>
</div>
</div>
</section>
<?php /**PATH D:\04 DATA ACO\S8\Capstone Project\silpj-V3\resources\views/v3/partials/transaction-documents.blade.php ENDPATH**/ ?>