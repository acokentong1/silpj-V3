@extends('v3.layout')
@section('title','Direktori rekanan')
@section('content')
<section class="card"><form method="get"><label>Cari nama<input name="q" value="{{ request('q') }}"></label><button>Cari</button></form><p>Daftar rekanan dari transaksi. Pilih rekanan tersimpan saat mengisi transaksi baru. Data historis dipertahankan; koreksi identitas dilakukan pada transaksi yang dapat diedit.</p><table><tr><th>Nama</th><th>Alamat</th><th>Penerima</th><th>NPWP</th></tr>@foreach($vendors as $v)<tr><td>{{ $v->nama }}</td><td>{{ $v->alamat }}</td><td>{{ $v->penerima }}</td><td>{{ $v->npwp }}</td></tr>@endforeach</table>{{ $vendors->links() }}</section>
@endsection
