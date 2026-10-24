
@extends('v3.layout')
@section('title','Transaksi '.$p->nomor_bukti)
@push('page-assets')<link rel="stylesheet" href="{{ asset('transaction-editor.css') }}?v=1">@endpush
@section('content')
@php($edit=$p->editable()&&auth()->user()->isBendahara())
@if(auth()->user()->isKepalaSekolah())
<p>
<a class="button secondary" href="{{ route('v3.approvals') }}">← Kembali ke daftar persetujuan</a>
</p>
<div class="review-instruction">Periksa rincian belanja, dokumen, dan bukti transaksi di bawah sebelum memberikan keputusan.</div>
@include('v3.partials.review-actions')
@endif

<p>
<a href="{{ route('v3.activity',$p->activity_id) }}">← {{ $p->activity->nama }}</a> · <span class="badge {{ $p->status }}">{{ $p->status }}</span>
</p>
@if(auth()->user()->isBendahara())
<form method="post" action="{{ route('v3.duplicate',$p->id) }}">@csrf<button class="secondary" type="submit">Duplikasi ke transaksi baru</button></form>
@endif
@if(!$p->editable())<div class="locked">🔒 Transaksi terkunci · {{ $p->status }}. Data dan dokumen tidak dapat diubah.</div>
@endif
<section class="card" aria-label="Kesiapan LPJ">
<h2>Kesiapan LPJ</h2>
@php($missing=\App\Services\LpjReadiness::missing($p))
@if($missing)<ul>@foreach($missing as $message)<li>{{ $message }}</li>@endforeach</ul>
@else<p>Dokumen dan bukti lengkap. {{ $p->status==='disetujui'?'Transaksi telah disetujui.':'Lanjutkan proses pengajuan / persetujuan.' }}</p>@endif
<p class="muted">Konfirmasi pembayaran merupakan pernyataan operator berdasarkan bukti, bukan verifikasi saldo bank.</p>
</section>
<div data-compact-tabs>
<div class="compact-tabbar" aria-label="Bagian transaksi" hidden></div>
<div id="belanja-rincian" data-tab-label="Rincian belanja">
<section class="card">
<div class="between">
<div>
<h2>{{ $p->vendor->nama }}</h2>
<span>{{ $p->vendor->alamat }} · Penerima: {{ $p->vendor->penerima }}</span>
<p class="muted">{{ $p->tanggal }} · {{ $p->activity->budget->sumber_dana }} {{ $p->tahun }} · Revisi data {{ $p->revision }}</p>
</div>
@if($edit)<a class="button secondary" href="{{ route('v3.edit',[$p->activity_id,$p->id]) }}">Ubah transaksi</a>
@endif
</div>
<div class="table-wrap">
<table>
<thead>
<tr>
<th>Barang</th>
<th>Volume</th>
<th>Satuan</th>
<th class="number">Harga</th>
<th class="number">Jumlah</th>
</tr>
</thead>
<tbody>
@foreach($p->items as $item)<tr>
<td>{{ $item->nama }}</td>
<td>{{ (float)$item->volume }}</td>
<td>{{ $item->satuan }}</td>
<td class="number">Rp{{ number_format($item->harga,0,',','.') }}</td>
<td class="number">Rp{{ number_format($item->jumlah,0,',','.') }}</td>
</tr>
@endforeach
</tbody>
</table>
</div>
@if($p->components->count())<h3>Pajak & komponen lain</h3>
<div class="table-wrap">
<table>
<thead>
<tr>
<th>Komponen</th>
<th>Perhitungan</th>
<th>Perlakuan</th>
<th class="number">Nominal</th>
</tr>
</thead>
<tbody>
@foreach($p->components as $c)<tr>
<td>{{ $c->label }} <small>({{ $c->kategori }})</small>
</td>
<td>{{ $c->metode==='persentase'?((float)$c->persentase.'% × Rp'.number_format($c->dasar,0,',','.')):'Nominal manual' }}</td>
<td>{{ ['termasuk'=>'Sudah termasuk','tambah'=>'Penambah','potong'=>'Potongan'][$c->perlakuan] }}</td>
<td class="number">Rp{{ number_format($c->nominal,0,',','.') }}</td>
</tr>
@endforeach
</tbody>
</table>
</div>
@endif
<div class="total-box">
@foreach(['subtotal'=>'Subtotal','bruto'=>'Nilai bruto','potongan'=>'Potongan','netto'=>'Diterima rekanan'] as $key=>$label)<div class="{{ $key==='netto'?'grand':'' }}">
<span>{{ $label }}</span>
<strong>Rp{{ number_format($p->$key,0,',','.') }}</strong>
</div>
@endforeach
</div>
</section>
</div>
<div id="belanja-penerimaan" data-tab-label="Penerimaan">
@include('v3.partials.receipts')
</div>
<div id="belanja-dokumen" data-tab-label="Dokumen LPJ">
@include('v3.partials.transaction-documents')
</div>
<div id="belanja-bukti" data-tab-label="Bukti transaksi">
<section class="card">
<h2>Bukti transaksi</h2>
<div class="evidence-list">
@forelse($attachments as $f)<div class="evidence-row">
<details><summary>Pratinjau bukti</summary>
<p><a href="{{ route('v3.preview',$f->id) }}" target="_blank" rel="noopener">Buka pratinjau di tab baru</a></p>
@if(str_starts_with($f->mime,'image/'))
<img loading="lazy" src="{{ route('v3.preview',$f->id) }}" alt="{{ $f->name }}" style="max-width:100%;max-height:520px;object-fit:contain">
@else
<iframe loading="lazy" title="Pratinjau {{ $f->name }}" src="{{ route('v3.preview',$f->id) }}" style="width:100%;height:520px;border:1px solid #dbe3ed"></iframe>
@endif
</details>
<a class="evidence-name" title="{{ $f->name }}" href="{{ route('v3.attachment',$f->id) }}">{{ $f->name }}</a> <small>{{ \App\Services\LpjReadiness::CATEGORIES[$f->category]??'Pendukung lainnya' }} · {{ $f->created_at }} · revisi {{ $f->revision }} · {{ $f->is_active?'Aktif':'Arsip' }}</small>
@if($edit)<details class="evidence-manage" @if(old('attachment_id')==$f->id) open @endif><summary>Kelola bukti</summary><form class="evidence-fields" method="post" action="{{ route('v3.evidence',$f->id) }}"><input type="hidden" name="attachment_id" value="{{ $f->id }}">@csrf<label>Kategori<select name="category">@foreach(\App\Services\LpjReadiness::CATEGORIES as $key=>$label)<option value="{{ $key }}" @selected($f->category===$key)>{{ $label }}</option>@endforeach</select></label><label>Alasan<input name="reason" minlength="5" maxlength="2000" required></label><select name="action"><option value="confirm">Gunakan untuk revisi {{ $p->revision }}</option><option value="archive">Arsipkan bukti</option></select><button class="secondary">Simpan perubahan</button></form></details>@endif
</div>
@empty<p class="muted">Unggah nota atau bukti pendukung sebelum mengajukan LPJ.</p>
@endforelse
</div>
@if($edit)<details class="evidence-upload" open><summary>Tambah bukti</summary><form class="evidence-fields" method="post" enctype="multipart/form-data" action="{{ route('v3.upload',$p->id) }}">
@csrf
<label>Kategori bukti<select name="category" required>@foreach(\App\Services\LpjReadiness::CATEGORIES as $key=>$label)<option value="{{ $key }}">{{ $label }}</option>@endforeach</select></label>
<input aria-label="Bukti transaksi" type="file" name="file" accept=".pdf,.png,.jpg,.jpeg" required>
<button>Unggah bukti</button>
<small>PDF, PNG, JPG · Maks. 10 MB</small>
</form></details>
@endif
</section>
</div>
<div id="belanja-riwayat" data-tab-label="Riwayat">
<section class="card">
<h3>Riwayat perubahan</h3>
@foreach($events as $e)<p>
<strong>{{ str_replace('_',' ',$e->action) }}</strong> · {{ $e->name }} <small>{{ $e->created_at }}</small>
@if($e->reason)<br>{{ $e->reason }}
@endif
</p>
@include('v3.partials.revision-diff')
@endforeach
</section>
</div>
</div>
@if($edit && !auth()->user()->isKepalaSekolah())
@include('v3.partials.review-actions')
@endif
@endsection
