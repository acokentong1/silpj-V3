
@php
 $batches=\App\Services\ReceiptService::batches($p);
 $received=\App\Services\ReceiptService::totals($p);
@endphp
<section class="card">
<h2>{{ $p->kategori==='Jasa'?'Progres pekerjaan bertahap':'Penerimaan barang bertahap' }}</h2>
<p class="muted">Catat setiap kedatangan. BAPB akhir memakai jumlah kumulatif setelah seluruh pesanan diterima. Mengubah transaksi atau mengganti nota pesanan memulai pencatatan penerimaan baru; tahap lama tetap tersimpan dalam riwayat.</p>
@foreach($batches as $batch)
<details>
<summary>Tahap {{ $loop->iteration }} · {{ $batch->tanggal }}</summary>
@foreach(json_decode($batch->items,true) as $key=>$row)
<p>Barang {{ $key }}: {{ $row['baik'] }} baik, {{ $row['rusak'] }} tidak baik · {{ $row['catatan']??'' }}</p>
@endforeach
@if($edit)<form method="post" action="{{ route('v3.receipts.cancel',$batch->id) }}" data-confirm="Batalkan tahap penerimaan ini? Dokumen berita acara/kuitansi aktif akan dibatalkan.">@csrf<button class="secondary">Batalkan tahap / koreksi</button></form>@endif
</details>
@endforeach
@if($edit&&\App\Services\V3Workflow::current($p,'nopes')&&!\App\Services\V3Workflow::current($p,'bapb'))
<details>
<summary>Catat tahap penerimaan</summary>
<form method="post" action="{{ route('v3.receipts.store',$p->id) }}">
@csrf
<input type="hidden" name="token" value="{{ (string)\Illuminate\Support\Str::uuid() }}">
<label>Tanggal penerimaan<input type="date" name="tanggal" value="{{ old('tanggal',$p->tanggal) }}" required>
</label>
<div class="table-wrap">
<table>
<thead>
<tr>
<th>Barang / pesanan</th>
<th>Sudah diterima</th>
<th>Baik tahap ini</th>
<th>Tidak baik tahap ini</th>
<th>Catatan</th>
</tr>
</thead>
<tbody>
@foreach($p->items as $item)
<tr>
<td>{{ $item->nama }} / {{ $item->volume }} {{ $item->satuan }}</td>
<td>{{ bcadd($received[$item->urutan]['baik']??'0',$received[$item->urutan]['rusak']??'0',3) }}</td>
@foreach(['baik','rusak'] as $field)
<td>
<input aria-label="{{ $field }} {{ $item->nama }}" type="number" min="0" step="0.001" max="{{ $item->volume }}" name="items[{{ $item->urutan }}][{{ $field }}]" value="0" required>
</td>
@endforeach
<td>
<input aria-label="Catatan {{ $item->nama }}" name="items[{{ $item->urutan }}][catatan]" maxlength="255">
</td>
</tr>
@endforeach
</tbody>
</table>
</div>
<button>Simpan tahap penerimaan</button>
</form>
</details>
@endif
</section>
