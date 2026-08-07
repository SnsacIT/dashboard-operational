@extends('layouts.dashboard')

@section('title', 'Detail Mekanik')
@section('page-title', $mechanic->nama ?? $mechanic->username)
@section('page-description', 'Profil mekanik, dealer penempatan, presensi, dan riwayat pekerjaan.')

@section('content')
    <div class="attendance-page mechanic-detail-page">
        <div class="mechanic-profile-hero">
            <div class="mechanic-profile-avatar">{{ strtoupper(substr($mechanic->nama ?? $mechanic->username ?? 'M', 0, 1)) }}</div>
            <div class="mechanic-profile-main">
                <span>Mekanik</span>
                <h4>{{ $mechanic->nama ?? $mechanic->username }}</h4>
                <p>{{ $mechanic->nip }} • {{ $dealer->dealer ?? $mechanic->dealer }} - {{ $dealer->cabang ?? $mechanic->cabang }}</p>
            </div>
            <a href="{{ route('mechanics.index', ['role' => $role]) }}" class="btn btn-light-primary">Kembali</a>
        </div>

        <div class="attendance-kpi-grid five-items">
            <div class="attendance-kpi-card primary"><span>Total Presensi</span><strong>{{ number_format($kpis['attendance'], 0, ',', '.') }}</strong></div>
            <div class="attendance-kpi-card success"><span>Jumlah Pekerjaan</span><strong>{{ number_format($kpis['jobs'], 0, ',', '.') }}</strong></div>
            <div class="attendance-kpi-card info"><span>Pekerjaan AC</span><strong>{{ number_format($kpis['ac_jobs'], 0, ',', '.') }}</strong></div>
            <div class="attendance-kpi-card warning"><span>Non AC</span><strong>{{ number_format($kpis['non_ac_jobs'], 0, ',', '.') }}</strong></div>
            <div class="attendance-kpi-card danger"><span>Terlambat</span><strong>{{ number_format($kpis['late'], 0, ',', '.') }}</strong></div>
        </div>

        <div class="row g-4">
            <div class="col-12 col-xl-4">
                <div class="card attendance-table-card mechanic-info-card">
                    <div class="card-header"><h4 class="mb-1">Informasi Mekanik</h4><p class="text-muted mb-0">Data penempatan dan kontak.</p></div>
                    <div class="card-body">
                        <div class="mechanic-info-grid">
                            <div><span>NIP</span><strong>{{ $mechanic->nip }}</strong></div>
                            <div><span>Dealer</span><strong>{{ $dealer->dealer ?? $mechanic->dealer }}</strong></div>
                            <div><span>Cabang</span><strong>{{ $dealer->cabang ?? $mechanic->cabang }}</strong></div>
                            <div><span>ATL</span><strong>{{ $dealer->atl ?? 'ATL '.($dealer->no_atl ?? '-') }}</strong></div>
                            <div><span>Kontak</span><strong>{{ $mechanic->kontak ?? '-' }}</strong></div>
                            <div><span>Grade</span><strong>{{ $mechanic->grade ?? '-' }}</strong></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-xl-8">
                <div class="card attendance-table-card">
                    <div class="card-header"><h4 class="mb-1">Presensi Terbaru</h4><p class="text-muted mb-0">10 aktivitas presensi terakhir mekanik.</p></div>
                    <div class="card-body p-0">
                        <div class="table-responsive attendance-table-wrap">
                            <table class="table table-hover align-middle mb-0 attendance-table">
                                <thead><tr><th>Tanggal</th><th>Jam</th><th>Status</th><th>Keterangan</th></tr></thead>
                                <tbody>
                                    @forelse ($attendances as $attendance)
                                        <tr>
                                            <td>{{ $attendance->date ?? '-' }}</td>
                                            <td>{{ $attendance->time ?? '-' }}</td>
                                            <td><span class="badge bg-light-primary text-primary">{{ $attendance->category ?? '-' }}</span></td>
                                            <td>{{ $attendance->des ?? '-' }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="4" class="text-center text-muted py-5">Belum ada presensi.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer bg-white">@include('partials.simple-pagination', ['paginator' => $attendances])</div>
                </div>
            </div>

            <div class="col-12">
                <div class="card attendance-table-card">
                    <div class="card-header"><h4 class="mb-1">Riwayat Pekerjaan</h4><p class="text-muted mb-0">10 postcheck terakhir dengan pemisahan AC dan Non AC.</p></div>
                    <div class="card-body p-0">
                        <div class="table-responsive attendance-table-wrap">
                            <table class="table table-hover align-middle mb-0 attendance-table">
                                <thead><tr><th>Waktu</th><th>Dealer</th><th>Plat</th><th>Jenis</th><th>Hasil</th></tr></thead>
                                <tbody>
                                    @forelse ($checks as $check)
                                        @php
                                            $jobText = strtolower((string) (($check->hasil ?? '').' '.($check->catatan ?? '')));
                                            $isAc = str_contains($jobText, 'ac') || str_contains($jobText, 'freon') || str_contains($jobText, 'evaporator') || str_contains($jobText, 'blower') || str_contains($jobText, 'kompresor') || str_contains($jobText, 'compressor') || str_contains($jobText, 'dryer') || str_contains($jobText, 'filter') || str_contains($jobText, 'suhu') || str_contains($jobText, 'dingin');
                                            $typeLabel = ($check->type ?? 'Postcheck') === 'Precheck' ? 'Precheck' : ($isAc ? 'Postcheck AC' : 'Postcheck Non AC');
                                        @endphp
                                        <tr>
                                            <td>{{ $check->created_at }}</td>
                                            <td>{{ $check->dealer }}<small class="d-block text-muted">{{ $check->cabang }}</small></td>
                                            <td><strong>{{ $check->noplat ?? '-' }}</strong></td>
                                            <td><span class="badge {{ ($check->type ?? 'Postcheck') === 'Precheck' ? 'bg-light-info text-info' : ($isAc ? 'bg-light-primary text-primary' : 'bg-light-secondary text-secondary') }}">{{ $typeLabel }}</span></td>
                                            <td>{{ $check->hasil ?? '-' }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="5" class="text-center text-muted py-5">Belum ada pekerjaan.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer bg-white">@include('partials.simple-pagination', ['paginator' => $checks])</div>
                </div>
            </div>
        </div>
    </div>
@endsection
