
@php($changes=\App\Services\RevisionDiff::rows($e->details))
@if(count($changes))
<details>
<summary>Lihat perbandingan sebelum dan sesudah</summary>
<div class="table-wrap">
<table>
<thead>
<tr>
<th>Bagian</th>
<th>Sebelumnya</th>
<th>Terbaru</th>
</tr>
</thead>
<tbody>
@foreach($changes as $change)
<tr>
<td>{{ str_replace(['items.','components.','vendor.'],['Barang (indeks mulai 0) ','Komponen (indeks mulai 0) ','Rekanan '],$change['field']) }}</td>
<td>{{ $change['before'] ?? '—' }}</td>
<td>{{ $change['after'] ?? '—' }}</td>
</tr>
@endforeach
</tbody>
</table>
</div>
</details>
@endif
