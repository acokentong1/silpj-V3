
@extends('v3.layout')
@section('title','Laporan & riwayat')
@section('content')
<section class="card">
<form method="get" class="grid">
<label>Dari tanggal<input type="date" name="from" value="{{ request('from') }}">
</label>
<label>Sampai tanggal<input type="date" name="to" value="{{ request('to') }}">
</label>
<label>Tahun<input type="number" min="2000" max="2100" name="tahun" value="{{ request('tahun') }}">
</label>
<label>Bulan<select name="bulan">
<option value="">Semua bulan</option>
@for($m=1;$m<=12;$m++)<option value="{{ $m }}" @selected(request('bulan')==$m)>{{ $m }}</option>
@endfor
</select>
</label>
<label>Status<select name="status">
<option value="">Semua status</option>
@foreach(['draf','diajukan','disetujui','revisi','dibatalkan'] as $status)<option @selected(request('status')===$status)>{{ $status }}</option>
@endforeach
</select>
</label>
<label>Kegiatan<select name="activity_id">
<option value="">Semua kegiatan</option>
@foreach($activities as $activity)<option value="{{ $activity->id }}" @selected(request('activity_id')==$activity->id)>{{ $activity->nama }}</option>
@endforeach
</select>
</label>
<label>Sumber dana<select name="sumber_dana">
<option value="">Semua sumber</option>
@foreach($funds as $fund)<option @selected(request('sumber_dana')===$fund)>{{ $fund }}</option>
@endforeach
</select>
</label>
<div class="actions">
<button>Terapkan filter</button>
<a href="{{ route('v3.reports') }}">Reset</a>
</div>
</form>
<p class="muted">Filter berlaku untuk rekap dan ekspor. Riwayat aktivitas sekolah ditampilkan terpisah.</p>
</section>
<section class="card">
<div class="between">
<h2>Rekap belanja</h2>
<div class="actions">
<a class="button" href="{{ route('v3.excel',request()->query()) }}">Unduh Excel</a>
<a class="button secondary" href="{{ route('v3.csv',request()->query()) }}">CSV</a>
</div>
</div>
<p class="muted">Transaksi dibatalkan ditampilkan untuk riwayat; keluarkan dari perhitungan realisasi.</p>
<div class="table-wrap">
<table>
<thead>
<tr>
<th>Kegiatan / Nomor</th>
<th>Rekanan</th>
<th>Bruto</th>
<th>Potongan</th>
<th>Netto</th>
<th>Status</th>
</tr>
</thead>
<tbody>
@foreach($rows as $p)<tr>
<td>
<a href="{{ route('v3.purchase',$p->id) }}">{{ $p->nomor_bukti }}</a>
<br>{{ $p->activity->nama }}</td>
<td>{{ $p->vendor->nama }}</td>
<td class="number">{{ number_format($p->bruto,0,',','.') }}</td>
<td class="number">{{ number_format($p->potongan,0,',','.') }}</td>
<td class="number">{{ number_format($p->netto,0,',','.') }}</td>
<td>{{ $p->status }}</td>
</tr>
@endforeach
</tbody>
</table>
</div>{{ $rows->links() }}</section>
<section class="card">
<h2>100 aktivitas terbaru</h2>
<div class="table-wrap">
<table>
<thead>
<tr>
<th>Waktu</th>
<th>Pengguna</th>
<th>Tindakan</th>
<th>Alasan</th>
</tr>
</thead>
<tbody>
@foreach($events as $e)<tr>
<td>{{ $e->created_at }}</td>
<td>{{ $e->name }}</td>
<td>{{ str_replace('_',' ',$e->action) }}</td>
<td>{{ $e->reason }}</td>
</tr>
@endforeach
</tbody>
</table>
</div>
</section>
@endsection
