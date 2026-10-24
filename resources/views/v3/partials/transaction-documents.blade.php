
@push('page-assets')
<link rel="stylesheet" href="{{ asset('transaction-documents.css') }}?v=1">
<script src="{{ asset('transaction-documents.js') }}?v=1" defer>
</script>
@endpush
@php
 $documentLabels=['nopes'=>'Nota Pesanan','bapb'=>'Berita Acara Pemeriksaan Barang','kuitansi'=>'Kuitansi'];
 if($p->kategori==='Jasa') $documentLabels['bapb']='Berita Acara Pemeriksaan Pekerjaan';
 $documentDescriptions=['nopes'=>'Rincian pemesanan barang atau jasa kepada rekanan.','bapb'=>'Hasil pemeriksaan kondisi dan jumlah barang yang diterima.','kuitansi'=>'Bukti pembayaran dan pelunasan transaksi kepada rekanan.'];
 $currentDocs=$p->documents->where('status','terbit')->where('revision',$p->revision)->keyBy('jenis');
@endphp
<section class="card document-workspace" aria-labelledby="documents-title">
<div class="document-section-heading">
<div>
<p class="eyebrow">KELENGKAPAN TRANSAKSI</p>
<h2 id="documents-title">Siapkan dokumen LPJ</h2>
<p class="muted">Mulai dari nota pesanan, lanjutkan pemeriksaan barang, lalu terbitkan kuitansi.</p>
</div>
<span class="document-progress">{{ $currentDocs->count() }} dari 3 dokumen terbit</span>
</div>
<div class="document-cards">
@foreach($documentLabels as $kind=>$label)
@php
 $currentDoc=$currentDocs->get($kind);
 $previousKind=match($kind){'bapb'=>'nopes','kuitansi'=>'bapb',default=>null};
 $previousDoc=$previousKind?$currentDocs->get($previousKind):null;
 $ready=$kind==='nopes'||($currentDocs->has('nopes')&&($kind!=='kuitansi'||$currentDocs->has('bapb')));
 $defaultDate=max((string)$p->tanggal,(string)($previousDoc?->tanggal??$p->tanggal));
 $minDate=$previousDoc?->tanggal??($p->tahun.'-01-01');
@endphp
<article class="document-option {{ $currentDoc?'document-complete':(!$ready?'document-waiting':'') }}">
<div class="document-option-top">
<span class="document-step" aria-label="Langkah {{ $loop->iteration }}">0{{ $loop->iteration }}</span>
<span class="document-state {{ $currentDoc?'state-complete':($edit&&$ready?'state-ready':'state-waiting') }}">{{ $currentDoc?'Sudah terbit':(!$edit?'Belum terbit':($ready?'Siap dibuat':'Menunggu')) }}</span>
</div>
<div class="document-paper" aria-hidden="true">
<svg viewBox="0 0 40 48" fill="none">
<path d="M8 3h17l9 9v33H8z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
<path d="M25 3v10h9M14 23h14M14 29h14M14 35h9" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
</svg>
</div>
<h3>{{ $label }}</h3>
<p class="document-description">{{ $documentDescriptions[$kind] }}</p>
<div class="document-option-bottom">
@if($currentDoc)<div class="document-number">{{ $currentDoc->nomor }}</div>
<div class="document-card-actions">
<a class="button" href="{{ route('v3.document',$currentDoc->id) }}">Lihat / cetak</a>
<a class="button secondary" href="{{ route('v3.document',['id'=>$currentDoc->id,'pdf'=>1]) }}">PDF</a>
</div>
@elseif($edit&&$ready)<p class="document-hint">Lengkapi isian sebelum dokumen diterbitkan.</p>
<button type="button" class="document-create" data-document-open="document-form-{{ $kind }}" aria-haspopup="dialog">Buat {{ $kind==='bapb'?'berita acara':strtolower($label) }} <span aria-hidden="true">↗</span>
</button>
@else<p class="document-hint">{{ !$edit?'Penerbitan tidak tersedia untuk akun atau status transaksi ini.':'Terbitkan '.$documentLabels[$previousKind].' terlebih dahulu.' }}</p>
<button type="button" class="document-create" disabled>{{ !$edit?'Penerbitan tidak tersedia':'Menunggu dokumen sebelumnya' }}</button>
@endif
</div>
</article>
@if($edit&&!$currentDoc&&$ready)
<dialog class="document-dialog {{ $kind==='bapb'?'document-dialog-wide':'' }}" id="document-form-{{ $kind }}" aria-labelledby="document-title-{{ $kind }}" 
@if($errors->any()&&old('jenis')===$kind) data-document-reopen="true" 
@endif
>
<div class="document-dialog-header">
<div>
<p class="eyebrow">BUAT DOKUMEN · LANGKAH {{ $loop->iteration }}</p>
<h2 id="document-title-{{ $kind }}">{{ $label }}</h2>
</div>
<button type="button" class="secondary document-close" data-document-close aria-label="Tutup formulir {{ $label }}">×</button>
</div>
<p class="document-dialog-intro">Transaksi <strong>{{ $p->nomor_bukti }}</strong> · {{ $p->vendor->nama }}</p>
@if($errors->any()&&old('jenis')===$kind)<div class="notice error" role="alert">
<strong>Periksa kembali isian.</strong>
<ul>
@foreach($errors->all() as $error)<li>{{ $error }}</li>
@endforeach
</ul>
</div>
@endif
<form method="post" action="{{ route('v3.issue',$p->id) }}">
@csrf
<input type="hidden" name="jenis" value="{{ $kind }}">
<div class="document-form-section">
<h3>Informasi dokumen</h3>
<div class="grid">
<label for="document-date-{{ $kind }}">Tanggal {{ $kind==='bapb'?'berita acara':strtolower($label) }}<input id="document-date-{{ $kind }}" name="tanggal" type="date" min="{{ $minDate }}" max="{{ $p->tahun }}-12-31" required value="{{ old('jenis')===$kind?old('tanggal',$defaultDate):$defaultDate }}">
</label>
@if($kind==='kuitansi')<label for="document-paid-date">Tanggal lunas bayar<input id="document-paid-date" name="tanggal_lunas" type="date" min="{{ $minDate }}" max="{{ $p->tahun }}-12-31" required value="{{ old('jenis')===$kind?old('tanggal_lunas',$defaultDate):$defaultDate }}">
</label>
@endif
</div>
<p class="document-field-help">Gunakan tanggal dalam tahun anggaran {{ $p->tahun }}
@if($previousDoc)
, pada atau setelah tanggal dokumen sebelumnya
@endif
.</p>
</div>
@if($kind==='bapb')<div class="document-form-section">
<h3>Hasil pemeriksaan {{ $p->kategori==='Jasa'?'pekerjaan':'barang' }}</h3>
<p class="document-field-help">Jumlah baik dan tidak baik harus sama dengan volume pesanan. Jika tahap penerimaan sudah dicatat, sistem memakai total tahap tersebut dan mengabaikan isian jumlah di bawah.</p>
<div class="table-wrap document-inspection">
<table>
<thead>
<tr>
<th>Barang / pesanan</th>
<th>{{ $p->kategori==='Jasa'?'Sesuai':'Baik' }}</th>
<th>{{ $p->kategori==='Jasa'?'Belum sesuai':'Tidak baik' }}</th>
<th>Catatan pemeriksaan</th>
</tr>
</thead>
<tbody>
@foreach($p->items as $item)<tr>
<td>
<strong>{{ $item->nama }}</strong>
<br>
<small>{{ (float)$item->volume }} {{ $item->satuan }}</small>
</td>
<td>
<input aria-label="Volume baik {{ $item->nama }}" name="inspection[{{ $item->urutan }}][baik]" type="number" min="0" max="1000000" step="0.001" required value="{{ old('inspection.'.$item->urutan.'.baik',$received[$item->urutan]['baik']??$item->volume) }}">
</td>
<td>
<input aria-label="Volume tidak baik {{ $item->nama }}" name="inspection[{{ $item->urutan }}][rusak]" type="number" min="0" max="1000000" step="0.001" required value="{{ old('inspection.'.$item->urutan.'.rusak',$received[$item->urutan]['rusak']??0) }}">
</td>
<td>
<input aria-label="Catatan pemeriksaan {{ $item->nama }}" name="inspection[{{ $item->urutan }}][catatan]" maxlength="255" placeholder="Opsional" value="{{ old('inspection.'.$item->urutan.'.catatan') }}">
</td>
</tr>
@endforeach
</tbody>
</table>
</div>
</div>
@endif
<div class="document-form-section">
<label for="document-notes-{{ $kind }}">Catatan tambahan <span class="muted">(opsional)</span>
<textarea id="document-notes-{{ $kind }}" name="catatan" maxlength="2000" rows="3" placeholder="Tambahkan keterangan bila diperlukan">{{ old('jenis')===$kind?old('catatan'):'' }}</textarea>
</label>
</div>
<div class="document-publish-note">Dokumen yang diterbitkan menyimpan salinan data transaksi saat ini.</div>
<div class="document-dialog-footer">
<button type="button" class="secondary" data-document-close>Kembali</button>
<button type="submit">Terbitkan {{ $kind==='bapb'?'berita acara':strtolower($label) }}</button>
</div>
</form>
</dialog>
@endif
@endforeach
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
@forelse($p->documents as $doc)<tr>
<td>
<strong>{{ $documentLabels[$doc->jenis]??strtoupper($doc->jenis) }}</strong>
<br>
<small>{{ $doc->nomor }}</small>
</td>
<td>{{ $doc->tanggal }}</td>
<td>v{{ $doc->versi }} · {{ $doc->status }}</td>
<td>
<div class="actions">
<a href="{{ route('v3.document',$doc->id) }}">Lihat / cetak</a>
<a href="{{ route('v3.document',['id'=>$doc->id,'pdf'=>1]) }}">PDF</a>
@if($edit&&$doc->status==='terbit')<form method="post" action="{{ route('v3.document.cancel',$doc->id) }}" data-confirm="Batalkan {{ $documentLabels[$doc->jenis]??strtoupper($doc->jenis) }} dan dokumen turunannya?">
@csrf
<button class="secondary small">Batalkan</button>
</form>
@endif
</div>
</td>
</tr>
@empty<tr>
<td colspan="4" class="empty">Belum ada dokumen terbit. Mulai dari nota pesanan di atas.</td>
</tr>
@endforelse
</tbody>
</table>
</div>
</div>
</section>
