
@extends('v3.layout')
@section('title','Laporan & riwayat')
@push('page-assets')
<link rel="stylesheet" href="{{ asset('reports-workspace.css') }}?v=1">
@endpush
@section('content')
<div class="reports-workspace" data-compact-tabs>
<div class="compact-tabbar" aria-label="Bagian laporan" hidden></div>
<div id="report-recap" data-tab-label="Rekap belanja">
<section class="card report-filter">
<form method="get" action="{{ route('v3.reports') }}#report-recap">
<div class="report-filter-main">
<label>Tahun<input type="number" min="2000" max="2100" name="tahun" value="{{ request('tahun') }}">
</label>
<label>Bulan<select name="bulan">
<option value="">Semua bulan</option>
@for($m=1;$m<=12;$m++)<option value="{{ $m }}" @selected(request('bulan')==$m)>{{ ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'][$m-1] }}</option>
@endfor
</select>
</label>
<label>Status<select name="status">
<option value="">Semua status</option>
@foreach(['draf','diajukan','disetujui','revisi','dibatalkan'] as $status)<option @selected(request('status')===$status)>{{ $status }}</option>
@endforeach
</select>
</label>
<div class="actions"><button>Terapkan filter</button><a class="button secondary" href="{{ route('v3.reports') }}#report-recap">Reset</a></div>
</div>
<details class="report-filter-more" @if(request()->filled('from') || request()->filled('to') || request()->filled('activity_id') || request()->filled('sumber_dana')) open @endif>
<summary>Filter lainnya <span class="muted">Tanggal, kegiatan, sumber dana</span></summary>
<div class="report-filter-extra">
<label>Dari tanggal<input type="date" name="from" value="{{ request('from') }}">
</label>
<label>Sampai tanggal<input type="date" name="to" value="{{ request('to') }}">
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
</div>
</details>
<p class="report-hint muted">Filter berlaku untuk rekap dan ekspor. Kosongkan filter untuk melihat semua transaksi.</p>
</form>
</section>
<section class="card">
<div class="between">
<h2>Rekap belanja <span class="badge">{{ $rows->total() }} transaksi</span></h2>
<div class="actions">
<a class="button" href="{{ route('v3.excel',request()->except(['page','events_page'])) }}">Unduh Excel</a>
<a class="button secondary" href="{{ route('v3.csv',request()->except(['page','events_page'])) }}">CSV</a>
<a class="button secondary" href="{{ route('v3.final',array_merge(request()->except(['page','events_page']),['tahun'=>request('tahun')?:date('Y')])) }}">LPJ final PDF (disetujui)</a>
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
@forelse($rows as $p)<tr>
<td>
<a href="{{ route('v3.purchase',$p->id) }}">{{ $p->nomor_bukti }}</a>
<br>{{ $p->activity->nama }}</td>
<td>{{ $p->vendor->nama }}</td>
<td class="number">{{ number_format($p->bruto,0,',','.') }}</td>
<td class="number">{{ number_format($p->potongan,0,',','.') }}</td>
<td class="number">{{ number_format($p->netto,0,',','.') }}</td>
<td><span class="badge {{ $p->status }}">{{ ucfirst($p->status) }}</span></td>
</tr>
@empty
<tr><td colspan="6" class="empty">Tidak ada transaksi yang sesuai filter.</td></tr>
@endforelse
</tbody>
</table>
</div>
@include('v3.partials.report-pagination', ['pager'=>$rows, 'anchor'=>'report-recap', 'noun'=>'transaksi'])
</section>
</div>
<section class="card" id="report-history" data-tab-label="Riwayat aktivitas">
<div class="between"><h2>Riwayat aktivitas</h2><span class="badge">{{ $events->total() }} aktivitas</span></div>
<p class="report-hint muted">Aktivitas sekolah dari yang terbaru, 10 baris per halaman. Filter rekap tidak berlaku pada riwayat.</p>
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
@forelse($events as $e)<tr>
<td>{{ $e->created_at }}</td>
<td>{{ $e->name }}</td>
<td>{{ str_replace('_',' ',$e->action) }}</td>
<td class="report-reason">@if($e->reason)<details><summary>Lihat alasan</summary><p>{{ $e->reason }}</p></details>@else<span class="muted">Tidak ada alasan</span>@endif</td>
</tr>
@empty
<tr><td colspan="4" class="empty">Belum ada aktivitas sekolah.</td></tr>
@endforelse
</tbody>
</table>
</div>
@include('v3.partials.report-pagination', ['pager'=>$events, 'anchor'=>'report-history', 'noun'=>'aktivitas'])
</section>
</div>
@endsection
