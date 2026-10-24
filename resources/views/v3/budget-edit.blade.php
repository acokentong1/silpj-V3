
@extends('v3.layout')
@section('title','Edit anggaran sekolah')
@section('content')
<section class="card">
<h2>Edit anggaran {{ $b->tahun }} — {{ $b->sumber_dana }}</h2>
<p class="muted">Nilai anggaran tidak boleh kurang dari total belanja yang dicadangkan, termasuk draf. Transaksi dibatalkan tidak dihitung.</p>
@if($hasPurchases)<p class="notice">Anggaran sudah memiliki transaksi. Tahun dan sumber dana dikunci untuk menjaga kesesuaian transaksi dan dokumen.</p>
@endif
<form method="post" action="{{ route('v3.budgets.update',$b->id) }}">
@csrf
 
@method('PUT')
<div class="grid">
<label>Tahun<input type="number" min="2000" max="2100" name="tahun" value="{{ $hasPurchases?$b->tahun:old('tahun',$b->tahun) }}" required @readonly($hasPurchases)>
</label>
<label>Sumber dana<input name="sumber_dana" maxlength="100" value="{{ $hasPurchases?$b->sumber_dana:old('sumber_dana',$b->sumber_dana) }}" required @readonly($hasPurchases)>
</label>
<label>Nilai anggaran (Rp)<input type="number" data-rupiah min="0" max="99999999999999" step="0.01" name="nilai" value="{{ old('nilai',$b->nilai) }}" required>
</label>
</div>
<p class="actions">
<button type="submit">Simpan perubahan</button>
<a class="button secondary" href="{{ route('v3.budgets') }}">Batal</a>
</p>
</form>
</section>
@endsection
