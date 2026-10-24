<div class="report-pagination">
<small role="status">@if($pager->total()){{ $pager->firstItem() }}–{{ $pager->lastItem() }} dari {{ $pager->total() }} {{ $noun }}@else 0 {{ $noun }} @endif</small>
@if($pager->hasPages())
<nav aria-label="Halaman {{ $noun }}" class="report-pages">
@if($pager->onFirstPage())<span class="button secondary" aria-disabled="true">Sebelumnya</span>@else<a class="button secondary" href="{{ $pager->previousPageUrl() }}#{{ $anchor }}" rel="prev">Sebelumnya</a>@endif
<span>Halaman {{ $pager->currentPage() }} / {{ $pager->lastPage() }}</span>
@if($pager->hasMorePages())<a class="button secondary" href="{{ $pager->nextPageUrl() }}#{{ $anchor }}" rel="next">Berikutnya</a>@else<span class="button secondary" aria-disabled="true">Berikutnya</span>@endif
</nav>
@endif
</div>
