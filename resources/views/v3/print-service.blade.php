
@php
 $school=$s['school'];$p=$s['purchase'];$vendor=$p['vendor'];$activity=$p['activity'];
 $rp=fn($v)=>'Rp'.number_format((float)$v,0,',','.');
 $date=\Carbon\Carbon::parse($doc->tanggal);
 $titles=['nopes'=>'NOTA PESANAN JASA','bapb'=>'BERITA ACARA PEMERIKSAAN PEKERJAAN','kuitansi'=>'KUITANSI'];
@endphp
@php $currentPurchase=\App\Models\V3\Purchase::where('school_profile_id',$doc->school_profile_id)->find($doc->purchase_id); @endphp
@php($approvedCurrent = $currentPurchase?->status === 'disetujui' && $doc->status === 'terbit' && (int)$doc->revision === (int)$currentPurchase->revision)
@if(!$approvedCurrent)
<div style="font-size:9px;border:1px solid #777;padding:5px;margin-bottom:8px">Status pemeriksaan saat cetak: {{ strtoupper($currentPurchase?->status ?? 'tidak tersedia') }}. DOKUMEN KERJA — BELUM DISETUJUI ATAU BUKAN VERSI AKTIF.</div>
@endif
@if($doc->status!=='terbit')<div class="archive">ARSIP · {{ strtoupper($doc->status) }} · BUKAN DOKUMEN AKTIF</div>
@endif
<table class="kop">
<tr>
<td class="logo">
<img src="{{ $school['logo_pemda_data'] }}" alt="Logo Pemda">
</td>
<td>
<div class="gov">{{ mb_strtoupper($school['nama_pemda']) }}<br>{{ mb_strtoupper($school['nama_dinas']) }}</div>
<strong>{{ mb_strtoupper($school['nama_sekolah']) }}</strong>
<div class="alamat">{{ $school['alamat'] }}</div>
</td>
<td class="logo">
<img src="{{ $school['logo_sekolah_data'] }}" alt="Logo Sekolah">
</td>
</tr>
</table>
<div class="title">{{ $titles[$doc->jenis] }}</div>
<p class="nomor">NOMOR: {{ $doc->nomor }}</p>
@if($doc->jenis==='kuitansi')
<table class="detail">
<tr>
<td class="label">Sudah terima dari</td>
<td>: Bendahara {{ $activity['budget']['sumber_dana'] }} {{ $school['nama_sekolah'] }}</td>
</tr>
<tr>
<td>Banyaknya uang (bruto)</td>
<td>: {{ $rp($p['bruto']) }}</td>
</tr>
<tr>
<td>Terbilang bruto</td>
<td>: {{ \App\Support\Terbilang::rupiah((int)$p['bruto']) }} rupiah</td>
</tr>
<tr>
<td>Untuk Pembayaran</td>
<td>: {{ $activity['nama'] }}</td>
</tr>
<tr>
<td>Yang berhak menerima</td>
<td>: {{ $vendor['nama'] }} / {{ $vendor['penerima'] }}</td>
</tr>
</table>
<h4>RINCIAN PAJAK DAN KOMPONEN LAIN</h4>
<table class="items">
<thead>
<tr>
<th>Komponen</th>
<th>Perlakuan</th>
<th>Nominal</th>
</tr>
</thead>
<tbody>
@forelse($p['components'] as $c)<tr>
<td>{{ $c['label'] }}
@if($c['metode']==='persentase')<br>
<small>{{ (float)$c['persentase'] }}% × {{ $rp($c['dasar']) }}</small>
@endif
</td>
<td>{{ ['tambah'=>'Penambah tagihan','potong'=>'Potongan pembayaran','termasuk'=>'Sudah termasuk harga'][$c['perlakuan']] }}</td>
<td class="num">{{ $rp($c['nominal']) }}</td>
</tr>
@empty<tr>
<td colspan="3">Tidak ada komponen pajak atau komponen lain yang diinput.</td>
</tr>
@endforelse
<tr>
<td colspan="2">Total rincian pajak</td>
<td class="num">{{ $rp($p['total_pajak']) }}</td>
</tr>
<tr>
<td colspan="2">Total potongan pembayaran</td>
<td class="num">{{ $rp($p['potongan']) }}</td>
</tr>
<tr>
<td colspan="2">
<strong>Diterima rekanan (netto)</strong>
</td>
<td class="num">
<strong>{{ $rp($p['netto']) }}</strong>
</td>
</tr>
</tbody>
</table>
<p>Terbilang netto: {{ \App\Support\Terbilang::rupiah((int)$p['netto']) }} rupiah</p>
@else
@if($doc->jenis==='nopes')<p class="right">Kepada Yth.<br>{{ $vendor['nama'] }}<br>{{ $vendor['alamat'] }}</p>
<p>Sehubungan dengan kegiatan <strong>{{ $activity['nama'] }}</strong>, kami memesan pekerjaan/jasa dengan rincian berikut:</p>
@else<p>Pada hari {{ $date->translatedFormat('l') }}, tanggal {{ $date->translatedFormat('d F Y') }}, telah dilakukan pemeriksaan pekerjaan untuk kegiatan <strong>{{ $activity['nama'] }}</strong> dari {{ $vendor['nama'] }}, berdasarkan Nota Pesanan {{ $s['nopes_nomor'] }}.</p>
@endif
<table class="items">
<thead>
<tr>
<th>No.</th>
<th>Uraian pekerjaan</th>
<th>Volume</th>
<th>Satuan</th>
<th>Harga satuan</th>
<th>Jumlah</th>
</tr>
</thead>
<tbody>
@foreach($p['items'] as $item)<tr>
<td>{{ $item['urutan'] }}</td>
<td>{{ $item['nama'] }}</td>
<td class="num">{{ (float)$item['volume'] }}</td>
<td>{{ $item['satuan'] }}</td>
<td class="num">{{ $rp($item['harga']) }}</td>
<td class="num">{{ $rp($item['jumlah']) }}</td>
</tr>
@endforeach
<tr>
<td colspan="5">
<strong>Subtotal pekerjaan</strong>
</td>
<td class="num">
<strong>{{ $rp($p['subtotal']) }}</strong>
</td>
</tr>
@foreach($p['components'] as $c)
@if($c['perlakuan']==='tambah')<tr>
<td colspan="5">{{ $c['label'] }} (penambah)</td>
<td class="num">{{ $rp($c['nominal']) }}</td>
</tr>
@endif
@endforeach
<tr>
<td colspan="5">
<strong>Nilai bruto</strong>
</td>
<td class="num">
<strong>{{ $rp($p['bruto']) }}</strong>
</td>
</tr>
</tbody>
</table>
@if($doc->jenis==='nopes')<p>Pembayaran dibebankan pada dana {{ $activity['budget']['sumber_dana'] }} tahun anggaran {{ $p['tahun'] }}, setelah pemeriksaan dan penerimaan pekerjaan sesuai pesanan.</p>
<p>Demikian pesanan ini disampaikan. Atas kerja samanya, kami mengucapkan terima kasih.</p>
@else<h4>HASIL PEMERIKSAAN</h4>
<table class="items">
<thead>
<tr>
<th>Pekerjaan</th>
<th>Sesuai</th>
<th>Belum sesuai</th>
<th>Satuan</th>
<th>Catatan</th>
</tr>
</thead>
<tbody>
@foreach($s['inspection'] as $row)<tr>
<td>{{ $row['nama'] }}</td>
<td>{{ (float)$row['baik'] }}</td>
<td>{{ (float)$row['rusak'] }}</td>
<td>{{ $row['satuan'] }}</td>
<td>{{ $row['catatan'] }}</td>
</tr>
@endforeach
</tbody>
</table>
<p>Hasil pemeriksaan dicatat sesuai keadaan pekerjaan pada tabel di atas. Berita acara ini dibuat untuk dipergunakan sebagaimana mestinya.</p>
@endif
@endif
@if($s['catatan'])<p class="notes">Catatan: {{ $s['catatan'] }}</p>
@endif
<div class="no-break">
<p class="right">{{ $school['tempat_cetak'] }}, {{ $date->translatedFormat('d F Y') }}
@if($doc->jenis==='kuitansi')<br>@if(empty($s['payment_confirmed']))<small>CATATAN LEGACY: tanggal lunas diinput operator; konfirmasi bukti pembayaran belum tercatat.</small><br>@endif LUNAS BAYAR: {{ \Carbon\Carbon::parse($s['tanggal_lunas'])->translatedFormat('d F Y') }}
@endif
</p>
<table class="sign">
<tr>
@if($doc->jenis==='kuitansi')<td>Penerima,<div class="space">
</div>
<strong>{{ $vendor['penerima'] }}</strong>
<br>{{ $vendor['nama'] }}</td>
<td>Bendahara,<div class="space">
</div>
<strong>{{ $school['bendahara'] }}</strong>
@if($school['nip_bendahara'])<br>NIP. {{ $school['nip_bendahara'] }}
@endif
</td>
@elseif($doc->jenis==='bapb')<td>Pemeriksa,<div class="space">
</div>
<strong>{{ $school['pemeriksa'] }}</strong>
@if($school['nip_pemeriksa'])<br>NIP. {{ $school['nip_pemeriksa'] }}
@endif
</td>
@else<td>
</td>
@endif
<td>Kepala Sekolah,<div class="space">
</div>
<strong>{{ $school['kepala_sekolah'] }}</strong>
@if($school['nip_kepala'])<br>NIP. {{ $school['nip_kepala'] }}
@endif
</td>
</tr>
</table>
</div>
<p class="footer">SILPJ V3 · Versi dokumen {{ $doc->versi }} · Revisi transaksi {{ $doc->revision }}</p>
