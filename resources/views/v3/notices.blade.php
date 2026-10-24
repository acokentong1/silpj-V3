@extends('v3.layout')
@section('title','Notifikasi')
@section('content')
<section class="card">@forelse($notices as $n)<form method="post" action="{{ route('v3.notices.read',$n->id) }}">@csrf<p>{{ $n->created_at }} · {{ $n->read_at?'Dibaca':'Baru' }} · {{ $n->message }} <button class="secondary">Buka transaksi</button></p></form>@empty<p>Belum ada notifikasi.</p>@endforelse{{ $notices->links() }}</section>
@endsection
