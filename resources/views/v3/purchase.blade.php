@extends('v3.layout')
@section('title','Transaksi '.$p->nomor_bukti)
@section('content')
@php($edit=$p->editable()&&auth()->user()->isBendahara())
@if(auth()->user()->isKepalaSekolah())
<p><a class="button secondary" href="{{ route('v3.approvals') }}">← Kembali ke daftar persetujuan</a></p>
<div class="review-instruction">Periksa rincian belanja, dokumen, dan bukti transaksi di bawah sebelum memberikan keputusan.</div>
@include('v3.partials.review-actions')
@endif

<p><a href="{{ route('v3.activity',$p->activity_id) }}">← {{ $p->activity->nama }}</a> · <span class="badge {{ $p->status }}">{{ $p->status }}</span></p>
@if(!$p->editable())<div class="locked">🔒 Transaksi terkunci · {{ $p->status }}. Data dan dokumen tidak dapat diubah.</div>@endif
<section class="card"><div class="between"><div><h2>{{ $p->vendor->nama }}</h2><span>{{ $p->vendor->alamat }} · Penerima: {{ $p->vendor->penerima }}</span><p class="muted">{{ $p->tanggal }} · {{ $p->activity->budget->sumber_dana }} {{ $p->tahun }} · Revisi data {{ $p->revision }}</p></div>@if($edit)<a class="button secondary" href="{{ route('v3.edit',[$p->activity_id,$p->id]) }}">Ubah transaksi</a>@endif</div>
<div class="table-wrap"><table><thead><tr><th>Barang</th><th>Volume</th><th>Satuan</th><th class="number">Harga</th><th class="number">Jumlah</th></tr></thead><tbody>@foreach($p->items as $item)<tr><td>{{ $item->nama }}</td><td>{{ (float)$item->volume }}</td><td>{{ $item->satuan }}</td><td class="number">Rp{{ number_format($item->harga,0,',','.') }}</td><td class="number">Rp{{ number_format($item->jumlah,0,',','.') }}</td></tr>@endforeach</tbody></table></div>
@if($p->components->count())<h3>Pajak & komponen lain</h3><div class="table-wrap"><table><thead><tr><th>Komponen</th><th>Perhitungan</th><th>Perlakuan</th><th class="number">Nominal</th></tr></thead><tbody>@foreach($p->components as $c)<tr><td>{{ $c->label }} <small>({{ $c->kategori }})</small></td><td>{{ $c->metode==='persentase'?((float)$c->persentase.'% × Rp'.number_format($c->dasar,0,',','.')):'Nominal manual' }}</td><td>{{ ['termasuk'=>'Sudah termasuk','tambah'=>'Penambah','potong'=>'Potongan'][$c->perlakuan] }}</td><td class="number">Rp{{ number_format($c->nominal,0,',','.') }}</td></tr>@endforeach</tbody></table></div>@endif
<div class="total-box">@foreach(['subtotal'=>'Subtotal','bruto'=>'Nilai bruto','potongan'=>'Potongan','netto'=>'Diterima rekanan'] as $key=>$label)<div class="{{ $key==='netto'?'grand':'' }}"><span>{{ $label }}</span><strong>Rp{{ number_format($p->$key,0,',','.') }}</strong></div>@endforeach</div></section>
@include('v3.partials.transaction-documents')
<section class="card"><h2>Bukti transaksi</h2>@forelse($attachments as $f)<p><a href="{{ route('v3.attachment',$f->id) }}">{{ $f->name }}</a> <small>{{ $f->created_at }}</small></p>@empty<p class="muted">Unggah nota atau bukti pendukung sebelum mengajukan LPJ.</p>@endforelse
@if($edit)<form class="actions" method="post" enctype="multipart/form-data" action="{{ route('v3.upload',$p->id) }}">@csrf<input aria-label="Bukti transaksi" type="file" name="file" accept=".pdf,.png,.jpg,.jpeg" required><button>Unggah bukti</button><small>PDF, PNG, JPG · Maks. 10 MB</small></form>@endif</section>
@if(!auth()->user()->isKepalaSekolah())
@include('v3.partials.review-actions')
@endif
<section class="card">
<h3>Riwayat perubahan</h3>@foreach($events as $e)<p><strong>{{ str_replace('_',' ',$e->action) }}</strong> · {{ $e->name }} <small>{{ $e->created_at }}</small>@if($e->reason)<br>{{ $e->reason }}@endif</p>@endforeach</section>
@endsection
