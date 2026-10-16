<section class="card" aria-labelledby="budget-summary-title">
<h2 id="budget-summary-title">Penggunaan anggaran sekolah · {{ $selectedYear }}</h2>
<p class="muted">Data tahun anggaran {{ $selectedYear }}. Penggunaan dihitung dari nilai bruto belanja aktif, termasuk draf dan transaksi dalam pemeriksaan; transaksi dibatalkan tidak dihitung. Angka ini menunjukkan anggaran terpakai/dicadangkan, bukan hanya pembayaran yang sudah lunas.</p>
<div class="stats budget-stats">
@foreach(['total'=>'Total anggaran','used'=>'Terpakai / dicadangkan','remaining'=>'Sisa anggaran'] as $key=>$label)
<div class="stat"><small>{{ $label }}</small><strong>Rp{{ number_format($budgetSummary[$key],0,',','.') }}</strong></div>
@endforeach
<div class="stat"><small>Persentase penggunaan</small><strong>{{ $budgetSummary['percentage']===null?'—':number_format($budgetSummary['percentage'],2,',','.').'%' }}</strong></div>
</div>
@if($budgetSummary['percentage']!==null)<progress class="budget-progress" max="100" value="{{ min(100,max(0,(float)$budgetSummary['percentage'])) }}" aria-label="Persentase penggunaan anggaran tahun {{ $selectedYear }}"></progress>@else<p class="muted">Persentase belum tersedia karena total anggaran masih nol.</p>@endif
<div class="table-wrap"><table><thead><tr><th>Tahun / Sumber dana</th><th class="number">Anggaran</th><th class="number">Terpakai / dicadangkan</th><th class="number">Sisa</th><th>Penggunaan</th></tr></thead><tbody>
@forelse($budgets as $b)<tr><td>{{ $b->tahun }} · {{ $b->sumber_dana }}</td><td class="number">Rp{{ number_format($b->nilai,0,',','.') }}</td><td class="number">Rp{{ number_format($b->used,0,',','.') }}</td><td class="number">Rp{{ number_format($b->remaining,0,',','.') }}</td><td>
@if($b->percentage!==null)<strong>{{ number_format($b->percentage,2,',','.') }}%</strong><progress class="budget-progress" max="100" value="{{ min(100,max(0,(float)$b->percentage)) }}" aria-label="Penggunaan anggaran {{ $b->tahun }} {{ $b->sumber_dana }}"></progress>@else<span class="muted">— (anggaran nol)</span>@endif
</td></tr>@empty<tr><td colspan="5">Belum ada anggaran sekolah.</td></tr>@endforelse
</tbody></table></div>
</section>
