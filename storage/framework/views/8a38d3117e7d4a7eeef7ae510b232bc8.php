<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Dokumen · <?php echo e($doc->nomor); ?></title>
<style>
<?php echo $__env->make('v3.print-styles', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php if(!request()->boolean('pdf')): ?>
<?php echo $__env->make('v3.preview-styles', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php endif; ?>
</style>
</head>
<body>
<?php if(!request()->boolean('pdf')): ?>
<?php
 $previewTitle = ['nopes'=>'Nota Pesanan Barang', 'bapb'=>'Berita Acara Pemeriksaan Barang', 'kuitansi'=>'Kuitansi'][$doc->jenis] ?? 'Dokumen';
?>
<header class="preview-toolbar">
 <div class="preview-toolbar-inner">
  <a class="preview-button preview-back" href="<?php echo e(route('v3.purchase',$doc->purchase_id)); ?>">
   <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m12 5-7 7 7 7M5 12h14"/></svg>
   <span>Kembali ke transaksi</span>
  </a>
  <div class="preview-brand">SILPJ <span>V3</span></div>
  <nav class="preview-actions" aria-label="Tindakan dokumen">
   <button type="button" class="preview-button" onclick="window.print()">
    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 8V3h10v5M7 17H4V9h16v8h-3M7 14h10v7H7zM17 11h.01"/></svg>
    <span>Cetak</span>
   </button>
   <a class="preview-button preview-primary" href="<?php echo e(route('v3.document',['id'=>$doc->id,'pdf'=>1])); ?>">
    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3v12m-5-5 5 5 5-5M5 16v5h14v-5"/></svg>
    <span>Unduh PDF</span>
   </a>
  </nav>
 </div>
</header>
<main class="preview-workspace">
 <section class="preview-heading" aria-labelledby="preview-title">
  <div class="preview-heading-copy"><p class="preview-eyebrow">PRATINJAU DOKUMEN</p><h1 id="preview-title"><?php echo e($previewTitle); ?></h1><p class="preview-number"><?php echo e($doc->nomor); ?></p></div>
  <div class="preview-metadata"><span class="preview-status <?php echo e($doc->status==='terbit'?'is-active':'is-archive'); ?>"><?php echo e($doc->status==='terbit'?'Terbit':'Arsip · '.ucfirst($doc->status)); ?></span><span>Versi <?php echo e($doc->versi); ?> · A4</span></div>
 </section>
 <p class="preview-tip">Gunakan Unduh PDF untuk menyimpan dokumen atau Cetak untuk mencetaknya.</p>
 <div class="preview-scroll" role="region" aria-label="Pratinjau kertas A4, geser ke samping pada layar kecil" tabindex="0">
  <article class="preview-paper" aria-label="Isi dokumen">
   <?php echo $__env->make('v3.print-document',['doc'=>$doc,'s'=>$s], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
  </article>
 </div>
</main>
<?php else: ?>
<?php echo $__env->make('v3.print-document',['doc'=>$doc,'s'=>$s], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php endif; ?>
</body>
</html>
<?php /**PATH D:\04 DATA ACO\S8\Capstone Project\silpj-V3\resources\views/v3/print-v1.blade.php ENDPATH**/ ?>