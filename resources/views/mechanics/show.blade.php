@extends('layouts.dashboard')

@section('title', 'Detail Mekanik')
@section('page-title', $mechanic->nama ?? $mechanic->username)
@section('page-description', 'Profil mekanik, dealer penempatan, presensi, dan riwayat pekerjaan.')

@section('content')
    <div class="row row-cols-1 row-cols-md-3 g-4 mb-4">
        <div class="col"><div class="card mb-0"><div class="card-body"><p class="kpi-title">Total Presensi</p><p class="kpi-value">{{ number_format($kpis['attendance'], 0, ',', '.') }}</p></div></div></div>
        <div class="col"><div class="card mb-0"><div class="card-body"><p class="kpi-title">Jumlah Pekerjaan</p><p class="kpi-value">{{ number_format($kpis['jobs'], 0, ',', '.') }}</p></div></div></div>
        <div class="col"><div class="card mb-0"><div class="card-body"><p class="kpi-title">Terlambat</p><p class="kpi-value">{{ number_format($kpis['late'], 0, ',', '.') }}</p></div></div></div>
    </div>

    <div class="row">
        <div class="col-12 col-xl-4">
            <div class="card">
                <div class="card-body">
                    <h4>{{ $mechanic->nama ?? $mechanic->username }}</h4>
                    <p class="text-muted mb-2">{{ $mechanic->nip }}</p>
                    <p class="mb-1"><strong>Dealer:</strong> {{ $dealer->dealer ?? $mechanic->dealer }}</p>
                    <p class="mb-1"><strong>Cabang:</strong> {{ $dealer->cabang ?? $mechanic->cabang }}</p>
                    <p class="mb-1"><strong>ATL:</strong> {{ $dealer->atl ?? 'ATL '.($dealer->no_atl ?? '-') }}</p>
                    <p class="mb-1"><strong>Kontak:</strong> {{ $mechanic->kontak ?? '-' }}</p>
                    <p class="mb-0"><strong>Grade:</strong> {{ $mechanic->grade ?? '-' }}</p>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-8">
            <div class="card">
                <div class="card-header"><h4>Presensi Terbaru</h4></div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead><tr><th>Tanggal</th><th>Jam</th><th>Status</th><th>Keterangan</th></tr></thead>
                            <tbody>
                                @forelse ($attendances as $attendance)
                                    <tr><td>{{ $attendance->date }}</td><td>{{ $attendance->time ?? '-' }}</td><td>{{ $attendance->category ?? '-' }}</td><td>{{ $attendance->des ?? '-' }}</td></tr>
                                @empty
                                    <tr><td colspan="4" class="text-center text-muted py-4">Belum ada presensi.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h4>Riwayat Pekerjaan</h4></div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead><tr><th>Waktu</th><th>Dealer</th><th>Plat</th><th>Hasil</th></tr></thead>
                            <tbody>
                                @forelse ($checks as $check)
                                    <tr><td>{{ $check->created_at }}</td><td>{{ $check->dealer }} - {{ $check->cabang }}</td><td>{{ $check->noplat ?? '-' }}</td><td>{{ $check->hasil ?? '-' }}</td></tr>
                                @empty
                                    <tr><td colspan="4" class="text-center text-muted py-4">Belum ada pekerjaan.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
