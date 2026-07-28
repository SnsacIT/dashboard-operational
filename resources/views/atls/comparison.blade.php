@extends('layouts.dashboard')

@section('title', 'Perbandingan ATL')
@section('page-title', 'Perbandingan ATL')
@section('page-description', 'Ranking ATL berdasarkan dealer, kehadiran, produktivitas, omset, dan rasio postcheck.')

@section('content')
    <form class="monitoring-filter" method="GET" action="{{ route('atl-comparisons.index') }}">
        <div class="row g-3 align-items-end">
            <div class="col-12 col-md-4">
                <label class="form-label">Periode</label>
                <input type="month" name="period" value="{{ $period }}" class="form-control">
            </div>
            <div class="col-12 col-md-3">
                <button class="btn btn-primary w-100">Terapkan Filter</button>
            </div>
        </div>
    </form>

    <div class="row row-cols-1 row-cols-md-3 g-4 mb-4">
        <div class="col"><div class="card mb-0"><div class="card-body"><p class="kpi-title">Skor Tertinggi</p><p class="kpi-value" style="font-size: 20px;">{{ $leader->name ?? '-' }}</p><small>{{ $leader ? $leader->score.' poin' : '-' }}</small></div></div></div>
        <div class="col"><div class="card mb-0"><div class="card-body"><p class="kpi-title">Omset Tertinggi</p><p class="kpi-value" style="font-size: 20px;">{{ $highestOmset->name ?? '-' }}</p><small>Rp {{ number_format((float) ($highestOmset->omset_total ?? 0), 0, ',', '.') }}</small></div></div></div>
        <div class="col"><div class="card mb-0"><div class="card-body"><p class="kpi-title">Presensi Tertinggi</p><p class="kpi-value" style="font-size: 20px;">{{ $highestPresence->name ?? '-' }}</p><small>{{ number_format((float) ($highestPresence->present_today ?? 0), 0, ',', '.') }} hadir</small></div></div></div>
    </div>

    <div class="card">
        <div class="card-header">
            <h4>Ranking ATL</h4>
            <p class="text-muted mb-0">Perbandingan performa periode {{ $period }}.</p>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Rank</th>
                            <th>ATL</th>
                            <th>Wilayah</th>
                            <th>Dealer</th>
                            <th>Hadir</th>
                            <th>Unit Entry</th>
                            <th>Omset</th>
                            <th>Unit/Mekanik</th>
                            <th>Rasio Postcheck</th>
                            <th>Skor</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($summaries as $summary)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td><a href="{{ route('atl-regions.show', ['atl' => $summary->urutan, 'period' => $period]) }}">{{ $summary->name }}</a></td>
                                <td>{{ $summary->region }}</td>
                                <td>{{ number_format($summary->dealers, 0, ',', '.') }}</td>
                                <td>{{ number_format($summary->present_today, 0, ',', '.') }}</td>
                                <td>{{ number_format($summary->unit_entry, 0, ',', '.') }}</td>
                                <td>Rp {{ number_format($summary->omset_total, 0, ',', '.') }}</td>
                                <td>{{ number_format($summary->unit_per_mechanic, 1, ',', '.') }}</td>
                                <td>{{ number_format($summary->postcheck_ratio, 1, ',', '.') }}%</td>
                                <td><span class="badge bg-light-primary text-primary">{{ $summary->score }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="10" class="text-center text-muted py-4">Belum ada data perbandingan ATL.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
