@extends('v3.layout')
@section('title','Kegiatan & belanja')
@section('content')
<p class="muted">Mulai dari kegiatan, tambahkan belanja, lalu lengkapi dokumen pertanggungjawaban.</p>
<form method="get" action="{{ route('dashboard') }}" class="card actions">
<label for="dashboard-year">Tahun anggaran<select name="tahun" id="dashboard-year">@foreach($years as $year)<option value="{{ $year }}" @selected($selectedYear===$year)>{{ $year }}</option>@endforeach</select></label>
@if(request()->filled('q'))<input type="hidden" name="q" value="{{ request('q') }}">@endif
<button type="submit">Tampilkan tahun</button><span class="muted">Menampilkan data tahun {{ $selectedYear }}</span>
</form>
<div class="stats">@foreach(['draf'=>'Draf','diajukan'=>'Menunggu pemeriksaan','disetujui'=>'Disetujui','revisi'=>'Perlu revisi'] as $key=>$label)<div class="stat"><small>{{ $label }}</small><strong>{{ $counts[$key]??0 }}</strong></div>@endforeach</div>
@include('v3.budget-summary')
<section class="card"><div class="between"><h2>Daftar kegiatan</h2>@if(auth()->user()->isBendahara())<button type="button" id="open-activity-dialog" aria-haspopup="dialog" aria-controls="activity-dialog">+ Tambah kegiatan</button>@endif<form class="actions" method="get"><input type="hidden" name="tahun" value="{{ $selectedYear }}"><input aria-label="Cari kegiatan" name="q" value="{{ request('q') }}" placeholder="Cari nama kegiatan"><button class="secondary">Cari</button></form></div><div class="table-wrap"><table><thead><tr><th>Kegiatan</th><th>Tahun / Dana</th><th>Transaksi</th><th class="number">Nilai bruto aktif</th><th></th></tr></thead><tbody>@forelse($activities as $a)<tr><td><strong>{{ $a->nama }}</strong></td><td>{{ $a->budget->tahun }} / {{ $a->budget->sumber_dana }}</td><td>{{ $a->purchases_count }}</td><td class="number">Rp{{ number_format($a->purchases_sum_bruto??0,0,',','.') }}</td><td><a href="{{ route('v3.activity',$a->id) }}">Buka →</a></td></tr>@empty<tr><td colspan="5" class="empty">Tidak ada kegiatan yang sesuai pada tahun {{ $selectedYear }}. Periksa pencarian atau tambahkan kegiatan.</td></tr>@endforelse</tbody></table></div><div class="pagination">{{ $activities->links() }}</div></section>
@if(auth()->user()->isBendahara())
<dialog id="activity-dialog" aria-labelledby="activity-dialog-title" data-reopen="{{ old('_activity_form')==='1'?'yes':'no' }}">
<div class="between"><h2 id="activity-dialog-title">Tambah kegiatan</h2><button type="button" class="secondary" data-close-activity aria-label="Tutup formulir kegiatan">Tutup</button></div>
@if($budgets->isEmpty())
<p>Tambahkan anggaran sekolah terlebih dahulu agar kegiatan dapat dihubungkan ke anggaran.</p><a class="button" href="{{ route('v3.budgets') }}">Tambah anggaran</a>
@else
@if($errors->any() && old('_activity_form')==='1')<div class="notice error" role="alert"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<form method="post" action="{{ route('v3.activity.store') }}">@csrf
<input type="hidden" name="_activity_form" value="1">
<div class="grid"><label class="wide">Nama / jenis kegiatan<input name="nama" required maxlength="255" value="{{ old('nama') }}" placeholder="Contoh: Pengecatan lapangan" autofocus></label>
<label>Anggaran<select name="budget_id" required>@foreach($budgets as $b)<option value="{{ $b->id }}" @selected(old('budget_id')==$b->id)>{{ $b->tahun }} · {{ $b->sumber_dana }}</option>@endforeach</select></label>
<label>Keterangan<input name="keterangan" value="{{ old('keterangan') }}" maxlength="2000"></label></div>
<div class="actions"><button type="button" class="secondary" data-close-activity>Batal</button><button type="submit">Simpan kegiatan</button></div>
</form>
@endif
</dialog>
@endif
@endsection
