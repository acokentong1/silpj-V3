
@extends('v3.layout')
@section('title','Anggaran sekolah')
@section('content')
<section class="card">
<div class="between">
<h2>Anggaran per tahun & sumber dana</h2>
@if(auth()->user()->isBendahara())<button type="button" id="open-budget-dialog" aria-haspopup="dialog" aria-controls="budget-dialog">+ Tambah anggaran</button>
@endif
</div>
<p class="muted">Anggaran hanya dapat dihapus jika belum digunakan oleh kegiatan. Penghapusan memerlukan alasan dan dicatat dalam riwayat.</p>
<div class="table-wrap">
<table>
<thead>
<tr>
<th>Tahun</th>
<th>Sumber dana</th>
<th class="number">Nilai anggaran</th>
@if(auth()->user()->isBendahara())<th>Aksi</th>
@endif
</tr>
</thead>
<tbody>
@forelse($budgets as $b)<tr>
<td>{{ $b->tahun }}</td>
<td>{{ $b->sumber_dana }}</td>
<td class="number">Rp{{ number_format($b->nilai,0,',','.') }}</td>
@if(auth()->user()->isBendahara())<td>
<div class="actions">
<a class="button secondary" href="{{ route('v3.budgets.edit',$b->id) }}">Edit</a>
<form method="post" action="{{ route('v3.budgets.destroy',$b->id) }}" data-confirm="Hapus anggaran {{ $b->tahun }} — {{ $b->sumber_dana }}? Tindakan ini tidak dapat dibatalkan.">
@csrf
 
@method('DELETE')<button type="submit" class="danger">Hapus</button>
</form>
</div>
</td>
@endif
</tr>
@empty<tr>
<td colspan="{{ auth()->user()->isBendahara()?4:3 }}">Belum ada anggaran.</td>
</tr>
@endforelse
</tbody>
</table>
</div>
</section>
@if(auth()->user()->isBendahara())<dialog id="budget-dialog" aria-labelledby="budget-dialog-title" data-reopen="{{ old('_budget_form')==='1'?'yes':'no' }}">
<div class="between">
<h2 id="budget-dialog-title">Tambah anggaran</h2>
<button type="button" class="secondary" data-close-budget>Tutup</button>
</div>
@if($errors->any() && old('_budget_form')==='1')<div class="notice error" role="alert">
<ul>
@foreach($errors->all() as $error)<li>{{ $error }}</li>
@endforeach
</ul>
</div>
@endif
<p class="muted">Tahun dan sumber dana yang sama memperbarui nilai anggaran. Draf transaksi ikut mencadangkan anggaran; transaksi dibatalkan tidak dihitung.</p>
<form method="post" action="{{ route('v3.budgets.save') }}">
@csrf
<input type="hidden" name="_budget_form" value="1">
<div class="grid">
<label>Tahun<input type="number" min="2000" max="2100" name="tahun" value="{{ old('tahun',date('Y')) }}" required>
</label>
<label>Sumber dana<input name="sumber_dana" value="{{ old('sumber_dana','BOS') }}" required maxlength="100">
</label>
<label>Nilai anggaran (Rp)<input type="number" data-rupiah min="0" max="99999999999999" step="1" name="nilai" value="{{ old('nilai') }}" required>
</label>
</div>
<div class="actions">
<button type="button" class="secondary" data-close-budget>Batal</button>
<button type="submit">Simpan anggaran</button>
</div>
</form>
</dialog>
@endif
@endsection
