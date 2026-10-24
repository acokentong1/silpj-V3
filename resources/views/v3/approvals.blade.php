
@extends('v3.layout')
@section('title','Persetujuan LPJ')
@section('content')
<p class="muted">Semua pengajuan sekolah dalam satu halaman. Buka transaksi untuk memeriksa dokumen dan memberikan keputusan.</p>
<div class="approval-summary">
@foreach(['diajukan'=>'Menunggu persetujuan','disetujui'=>'Sudah disetujui','revisi'=>'Perlu revisi'] as $key=>$label)
<a class="approval-summary-card {{ $status===$key?'is-selected':'' }}" href="{{ route('v3.approvals',array_filter(['status'=>$key,'tahun'=>request('tahun')])) }}" 
@if($status===$key) aria-current="page" 
@endif
>
<span>{{ $label }}</span>
<strong>{{ $counts[$key]??0 }}</strong>
<small>{{ request('tahun')?'Tahun '.request('tahun'):'Semua tahun anggaran' }}</small>
</a>
@endforeach
</div>
<section class="card">
<form method="get" action="{{ route('v3.approvals') }}" class="approval-filters">
<input type="hidden" name="status" value="{{ $status }}">
<label for="approval-search">Cari pengajuan<input id="approval-search" name="q" value="{{ request('q') }}" maxlength="100" placeholder="Nomor bukti, kegiatan, atau rekanan">
</label>
<label for="approval-year">Tahun anggaran<select id="approval-year" name="tahun">
<option value="">Semua tahun</option>
@foreach($years as $year)
<option value="{{ $year }}" @selected((string)request('tahun')===(string)$year)>{{ $year }}</option>
@endforeach
</select>
</label>
<label>Urutan<select name="sort">
<option value="oldest">Pengajuan paling lama</option>
<option value="newest" @selected(request("sort")==="newest")>Pengajuan terbaru</option>
</select>
</label>
<div class="actions">
<button type="submit">Tampilkan</button>
<a href="{{ route('v3.approvals',['status'=>$status]) }}">Reset</a>
</div>
</form>
<div class="between approval-list-heading">
<h2>{{ ['diajukan'=>'Menunggu persetujuan','disetujui'=>'Sudah disetujui','revisi'=>'Perlu revisi'][$status] }}</h2>
<span class="muted">{{ $purchases->total() }} transaksi</span>
</div>
<div class="table-wrap">
<table>
<thead>
<tr>
<th>Transaksi / kegiatan</th>
<th>Rekanan</th>
<th>Tanggal / dana</th>
<th class="number">Nilai bruto</th>
<th>Status</th>
<th>Tindakan</th>
</tr>
</thead>
<tbody>
@forelse($purchases as $p)
<tr>
<td>
<strong>{{ $p->nomor_bukti }}</strong>
<br>
<span>{{ $p->activity->nama }}</span>
</td>
<td>{{ $p->vendor->nama }}</td>
<td>{{ $p->tanggal }}<br>
<small>Pengajuan: {{ $p->submitted_at?->format("d/m/Y H:i") ?? "Tidak tercatat" }}
@if($status==='diajukan'&&$p->submitted_at) · menunggu {{ (int)$p->submitted_at->diffInDays(now()) }} hari
@endif
</small>
<br>
<small>{{ $p->activity->budget->sumber_dana }} · {{ $p->tahun }}</small>
</td>
<td class="number">Rp{{ number_format($p->bruto,0,',','.') }}</td>
<td>
<span class="badge {{ $p->status }}">{{ ['diajukan'=>'Menunggu','disetujui'=>'Disetujui','revisi'=>'Perlu revisi'][$p->status] }}</span>
</td>
<td>
<a class="button small" href="{{ route('v3.purchase',$p->id) }}">{{ $status==='diajukan'?'Periksa LPJ':'Lihat rincian' }}</a>
@if($status==='diajukan'&&(auth()->id()===$p->created_by||auth()->id()===$p->submitted_by))
<small class="review-own">Persetujuan memerlukan pemeriksa lain.</small>
@endif
</td>
</tr>
@empty
<tr>
<td colspan="6">
<div class="approval-empty">
<strong>{{ request()->filled('q')?'Tidak ada transaksi yang cocok.':'Belum ada transaksi pada daftar ini.' }}</strong>
<p>{{ $status==='diajukan'?'Pengajuan dari bendahara akan tampil di sini setelah diajukan.':'Coba pilih status atau tahun anggaran lain.' }}</p>
</div>
</td>
</tr>
@endforelse
</tbody>
</table>
</div>
<div class="pagination">{{ $purchases->links() }}</div>
</section>
@endsection
