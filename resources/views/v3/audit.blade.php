@extends('v3.layout')
@section('title','Jejak audit')
@section('content')
<form method="get" class="card grid"><label>Dari<input type="date" name="from" value="{{ request('from') }}"></label><label>Sampai<input type="date" name="to" value="{{ request('to') }}"></label><label>Pelaku<select name="actor"><option value="">Semua</option>@foreach($users as $u)<option value="{{ $u->id }}" @selected(request('actor')==$u->id)>{{ $u->name }}</option>@endforeach</select></label><label>Aksi<input name="action" value="{{ request('action') }}" placeholder="contoh: setujui"></label><button>Terapkan</button><a href="{{ route('v3.audit',array_merge(request()->query(),['csv'=>1])) }}">Ekspor CSV</a></form>
<section class="card"><table><tr><th>Waktu</th><th>Pelaku</th><th>Aksi</th><th>Alasan / rincian</th></tr>@foreach($events as $e)<tr><td>{{ $e->created_at }}</td><td>{{ $e->name }}</td><td>{{ $e->action }}</td><td>{{ $e->reason }}<details><summary>Rincian</summary><pre style="white-space:pre-wrap;overflow-wrap:anywhere">{{ $e->details }}</pre></details></td></tr>@endforeach</table>{{ $events->links() }}</section>
@endsection
