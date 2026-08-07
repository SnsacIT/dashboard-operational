@extends('layouts.dashboard')

@section('title', 'Perbandingan ATL')
@section('page-title', 'Perbandingan ATL')
@section('page-description', 'Perbandingan ATL berdasarkan dealer, presensi, dan postcheck.')

@section('content')
    <div class="attendance-page">
        <div class="attendance-kpi-grid two-items">
            <div class="attendance-kpi-card success"><span>Dealer Terbanyak</span><strong class="compact-date">{{ $highestOmset->name ?? '-' }}</strong><small>{{ number_format((float) ($highestOmset->dealers ?? 0), 0, ',', '.') }} dealer</small></div>
            <div class="attendance-kpi-card info"><span>Presensi Tertinggi</span><strong class="compact-date">{{ $highestPresence->name ?? '-' }}</strong><small>{{ number_format((float) ($highestPresence->present_today ?? 0), 0, ',', '.') }} hadir</small></div>
        </div>

        <div class="card attendance-filter-card"><div class="card-body"><form method="GET" action="{{ route('atl-comparisons.index') }}"><div class="row g-3 align-items-end"><div class="col-12 col-lg-3"><label class="form-label">Periode</label><input type="month" name="period" value="{{ $period }}" class="form-control"></div><div class="col-12 col-lg-4"><label class="form-label">SOH</label><select name="soh_id" class="form-select"><option value="">Semua SOH</option>@foreach ($sohs as $soh)<option value="{{ $soh->id }}" @selected((string) request('soh_id') === (string) $soh->id)>{{ $soh->name }}</option>@endforeach</select></div><div class="col-12 col-lg-3"><button class="btn btn-primary w-100"><i class="bi bi-funnel-fill me-1"></i>Terapkan Filter</button></div></div></form></div></div>

        <div class="card attendance-table-card">
            <div class="card-header"><h4 class="mb-1">Perbandingan ATL</h4><p class="text-muted mb-0">Ringkasan cakupan dealer, presensi terakhir, dan postcheck untuk periode {{ $period }}.</p></div>
            <div class="card-body p-0"><div class="table-responsive attendance-table-wrap"><table class="table table-hover align-middle mb-0 attendance-table"><thead><tr><th>No</th><th>ATL</th><th>Wilayah</th><th>Dealer</th><th>Hadir</th><th>Postcheck</th></tr></thead><tbody>
                @forelse ($summaries as $summary)
                    <tr><td><span class="badge bg-light-primary text-primary">#{{ $loop->iteration }}</span></td><td><a href="{{ route('atl-regions.show', ['atl' => $summary->urutan, 'period' => $period]) }}"><strong>{{ $summary->name }}</strong></a></td><td>{{ $summary->region }}</td><td>{{ number_format($summary->dealers, 0, ',', '.') }}</td><td>{{ number_format($summary->present_today, 0, ',', '.') }}</td><td>{{ number_format($summary->postchecks, 0, ',', '.') }}</td></tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-5">Belum ada data perbandingan ATL.</td></tr>
                @endforelse
            </tbody></table></div></div>
        </div>
    </div>
@endsection
