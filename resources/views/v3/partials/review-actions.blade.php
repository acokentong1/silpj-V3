<section class="card">
<h2>Pemeriksaan LPJ</h2>
<div class="actions">
@if($edit)<form method="post" action="{{ route('v3.transition',$p->id) }}">
@csrf
<input type="hidden" name="action" value="ajukan">
<button>Ajukan pemeriksaan</button>
</form>
<form method="post" action="{{ route('v3.transition',$p->id) }}" data-confirm="Batalkan transaksi dan seluruh dokumennya?">
@csrf
<input type="hidden" name="action" value="batalkan">
<button class="secondary">Batalkan transaksi</button>
</form>
@endif
@if(auth()->user()->isKepalaSekolah()&&$p->status==='diajukan')
@if(auth()->id()!==$p->created_by&&auth()->id()!==$p->submitted_by)<form method="post" action="{{ route('v3.transition',$p->id) }}">
@csrf
<input type="hidden" name="action" value="setujui">
<details>
<summary>Setujui LPJ</summary>
<p>Nomor: <strong>{{ $p->nomor_bukti }}</strong>
<br>Kegiatan: {{ $p->activity->nama }}<br>Rekanan: {{ $p->vendor->nama }}<br>Nilai bruto: <strong>Rp{{ number_format($p->bruto,2,',','.') }}</strong>
</p>
<label>
<input type="checkbox" name="confirmation" value="1" required> Saya sudah memeriksa transaksi ini dan menyetujui LPJ.</label>
<button>Konfirmasi persetujuan</button>
</details>
</form>
@endif
<form method="post" action="{{ route('v3.transition',$p->id) }}" data-confirm="Kembalikan untuk revisi?">
@csrf
<input type="hidden" name="action" value="revisi">
<button class="secondary">Minta revisi</button>
</form>
@endif
@if(auth()->user()->isKepalaSekolah()&&$p->status==='disetujui')<form method="post" action="{{ route('v3.transition',$p->id) }}" data-confirm="Buka kembali untuk revisi?">
@csrf
<input type="hidden" name="action" value="buka">
<button class="secondary">Buka untuk revisi</button>
</form>
@endif
</div>
</section>
