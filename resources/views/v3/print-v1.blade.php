<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Dokumen · {{ $doc->nomor }}</title>
<style>
@include('v3.print-styles')
@if(!request()->boolean('pdf'))
@include('v3.preview-styles')
@endif
</style>
</head>
<body>
@if(!request()->boolean('pdf'))
@php
 $previewTitle = ['nopes'=>'Nota Pesanan Barang', 'bapb'=>'Berita Acara Pemeriksaan Barang', 'kuitansi'=>'Kuitansi'][$doc->jenis] ?? 'Dokumen';
@endphp
<header class="preview-toolbar">
 <div class="preview-toolbar-inner">
  <a class="preview-button preview-back" href="{{ route('v3.purchase',$doc->purchase_id) }}">
   <svg viewBox="0 0 24 24" aria-hidden="true">
<path d="m12 5-7 7 7 7M5 12h14"/>
</svg>
   <span>Kembali ke transaksi</span>
  </a>
  <div class="preview-brand">SILPJ <span>V3</span>
</div>
  <nav class="preview-actions" aria-label="Tindakan dokumen">
   <button type="button" class="preview-button" onclick="window.print()">
    <svg viewBox="0 0 24 24" aria-hidden="true">
<path d="M7 8V3h10v5M7 17H4V9h16v8h-3M7 14h10v7H7zM17 11h.01"/>
</svg>
    <span>Cetak</span>
   </button>
   <a class="preview-button preview-primary" href="{{ route('v3.document',['id'=>$doc->id,'pdf'=>1]) }}">
    <svg viewBox="0 0 24 24" aria-hidden="true">
<path d="M12 3v12m-5-5 5 5 5-5M5 16v5h14v-5"/>
</svg>
    <span>Unduh PDF</span>
   </a>
  </nav>
 </div>
</header>
<main class="preview-workspace">
 <section class="preview-heading" aria-labelledby="preview-title">
  <div class="preview-heading-copy">
<p class="preview-eyebrow">PRATINJAU DOKUMEN</p>
<h1 id="preview-title">{{ $previewTitle }}</h1>
<p class="preview-number">{{ $doc->nomor }}</p>
</div>
  <div class="preview-metadata">
<span class="preview-status {{ $doc->status==='terbit'?'is-active':'is-archive' }}">{{ $doc->status==='terbit'?'Terbit':'Arsip · '.ucfirst($doc->status) }}</span>
<span>Versi {{ $doc->versi }} · A4</span>
</div>
 </section>
 <p class="preview-tip">Gunakan Unduh PDF untuk menyimpan dokumen atau Cetak untuk mencetaknya.</p>
 <div class="preview-scroll" role="region" aria-label="Pratinjau kertas A4, geser ke samping pada layar kecil" tabindex="0">
  <article class="preview-paper" aria-label="Isi dokumen">
   
@include('v3.print-document',['doc'=>$doc,'s'=>$s])
  </article>
 </div>
</main>
@else
@include('v3.print-document',['doc'=>$doc,'s'=>$s])
@endif
</body>
</html>
