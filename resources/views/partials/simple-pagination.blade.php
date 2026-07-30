@if ($paginator->hasPages())
    <div class="simple-pagination d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
        <small class="text-muted">
            Menampilkan {{ $paginator->firstItem() ?? 0 }}-{{ $paginator->lastItem() ?? 0 }} dari {{ $paginator->total() }} data
        </small>

        <div class="d-flex gap-2">
            @if ($paginator->onFirstPage())
                <span class="btn btn-sm btn-light disabled">Sebelumnya</span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" class="btn btn-sm btn-light-primary">Sebelumnya</a>
            @endif

            <span class="btn btn-sm btn-light disabled">Halaman {{ $paginator->currentPage() }} / {{ $paginator->lastPage() }}</span>

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" class="btn btn-sm btn-light-primary">Berikutnya</a>
            @else
                <span class="btn btn-sm btn-light disabled">Berikutnya</span>
            @endif
        </div>
    </div>
@else
    <small class="text-muted">Menampilkan {{ $paginator->total() }} data</small>
@endif
