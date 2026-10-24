
@extends('v3.layout')
@section('title',$a->nama)
@section('content')
<p>
<a href="{{ route('dashboard') }}">← Semua kegiatan</a> <span class="badge">{{ $a->budget->tahun }} · {{ $a->budget->sumber_dana }}</span>
</p>
<p class="muted">{{ $a->keterangan }}</p>
<section class="card">
<div class="between">
<h2>Transaksi belanja</h2>
@if(auth()->user()->isBendahara())<a class="button" href="{{ route('v3.create',$a->id) }}">+ Tambah transaksi</a>
@endif
</div>
<div class="table-wrap">
<table>
<thead>
<tr>
<th>Nomor / Tanggal</th>
<th>Rekanan</th>
<th>Status</th>
<th class="number">Bruto</th>
<th>
</th>
</tr>
</thead>
<tbody>
@forelse($purchases as $p)<tr>
<td>{{ $p->nomor_bukti }}<br>
<small>{{ $p->tanggal }}</small>
</td>
<td>{{ $p->vendor->nama }}</td>
<td>
<span class="badge {{ $p->status }}">{{ $p->status }}</span>
</td>
<td class="number">Rp{{ number_format($p->bruto,0,',','.') }}</td>
<td>
<a href="{{ route('v3.purchase',$p->id) }}">Rincian →</a>
</td>
</tr>
@empty<tr>
<td colspan="5" class="empty">Tambahkan rekanan dan barang untuk transaksi pertama.</td>
</tr>
@endforelse
</tbody>
</table>
</div>
</section>
<div class="actions">
<a class="button" href="{{ route('v3.activity.documents',$a->id) }}">Unduh semua dokumen kegiatan (PDF)</a>
</div>
<p class="muted">Satu PDF berisi dokumen aktif yang sudah diterbitkan, berurutan per transaksi: Nopes, Berita Acara, lalu Kuitansi. Dokumen yang belum terbit tidak disertakan; arsip dan transaksi dibatalkan dikecualikan.</p>

<details class="card"><summary>Pengaturan kegiatan {{ $a->is_archived?'(diarsipkan)':'' }}</summary>@if(auth()->user()->isBendahara())<form method="post" action="{{ route('v3.activity.update',$a->id) }}">@csrf @method('PATCH')<label>Nama<input name="nama" value="{{ $a->nama }}" required></label><label>Keterangan<textarea name="keterangan">{{ $a->keterangan }}</textarea></label><label>Status<select name="is_archived"><option value="0" @selected(!$a->is_archived)>Aktif</option><option value="1" @selected($a->is_archived)>Arsip</option></select></label><label>Alasan<textarea name="reason" minlength="5" required></textarea></label><button>Simpan pengaturan</button></form>@endif</details>
@endsection
