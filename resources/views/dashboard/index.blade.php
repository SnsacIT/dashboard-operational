@php
    $activeRole = strtolower((string) ($role ?? 'atl'));
    $isSoh = $activeRole === 'soh';
    $kpiCards = [
        ['title' => 'Total ATL', 'value' => $kpis['atls'] ?? 0, 'icon' => 'bi-diagram-3-fill', 'color' => 'purple', 'show' => $isSoh],
        ['title' => 'Total Dealer', 'value' => $kpis['dealers'] ?? 0, 'icon' => 'bi-shop', 'color' => 'blue', 'show' => true],
        ['title' => 'Total Mekanik', 'value' => $kpis['mechanics'] ?? 0, 'icon' => 'bi-people-fill', 'color' => 'green', 'show' => true],
        ['title' => 'Hadir Hari Ini', 'value' => $kpis['present_today'] ?? 0, 'icon' => 'bi-calendar-check-fill', 'color' => 'blue', 'show' => true],
        ['title' => 'Hadir Data Terakhir', 'value' => $kpis['present_latest'] ?? 0, 'icon' => 'bi-clock-history', 'color' => 'purple', 'show' => true],
        ['title' => 'Potensi Open', 'value' => $kpis['potential_open'] ?? 0, 'icon' => 'bi-graph-up-arrow', 'color' => 'red', 'show' => true],
    ];
@endphp

@extends('layouts.dashboard')

@section('title', $isSoh ? 'Dashboard Area SOH' : 'Dashboard Wilayah ATL')

@section('page-title')
    {{ $isSoh ? 'Dashboard Area SOH' : 'Dashboard Wilayah ATL' }}
@endsection

@section('page-description')
    {{ $isSoh
        ? 'Monitoring seluruh ATL, dealer, mekanik, presensi, precheck/postcheck, dan potensi dalam area SOH.'
        : 'Monitoring dealer, mekanik, presensi, precheck/postcheck, dan potensi dalam wilayah ATL.' }}
@endsection

@section('content')
    <section class="row">
        <div class="col-12">
            <form class="monitoring-filter" method="GET" action="{{ route('dashboard') }}">
                <input type="hidden" name="role" value="{{ $activeRole }}">

                <div class="row g-3 align-items-end">
                    <div class="col-12 col-md-6 col-xl-3">
                        <label class="form-label">Periode</label>
                        <input type="month" name="period" value="{{ $period }}" class="form-control">
                    </div>

                    @if ($isSoh)
                        <div class="col-12 col-md-6 col-xl-3">
                            <label class="form-label">ATL</label>
                            <select name="atl_id" class="form-select">
                                <option value="">Semua ATL</option>
                                @foreach ($atls as $atl)
                                    <option value="{{ $atl->urutan }}" @selected((string) request('atl_id') === (string) $atl->urutan)>
                                        {{ $atl->nama_wilayah }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <div class="col-12 col-md-6 col-xl-3">
                        <label class="form-label">Dealer</label>
                        <select name="dealer_id" class="form-select">
                            <option value="">Semua Dealer</option>
                            @foreach ($allDealers as $dealer)
                                <option value="{{ $dealer->id }}" @selected((string) request('dealer_id') === (string) $dealer->id)>
                                    {{ $dealer->dealer }} - {{ $dealer->cabang }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-12 col-md-6 col-xl-3">
                        <button class="btn btn-primary w-100">
                            <i class="bi bi-funnel-fill me-1"></i>
                            Terapkan Filter
                        </button>
                    </div>
                </div>

                @if (! empty($kpis['latest_attendance_date']))
                    <p class="text-muted small mt-3 mb-0">
                        Data presensi terakhir tersedia pada {{ \Carbon\Carbon::parse($kpis['latest_attendance_date'])->format('d-m-Y') }}.
                    </p>
                @endif
            </form>
        </div>

        <div class="col-12 mb-4">
            <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-4 g-4">
                @foreach ($kpiCards as $kpi)
                    @continue(! $kpi['show'])
                    <div class="col">
                        <div class="card kpi-card h-100 mb-0">
                            <div class="card-body px-3 py-4">
                                <div class="row align-items-center">
                                    <div class="col-4">
                                        <div class="stats-icon {{ $kpi['color'] }}">
                                            <i class="bi {{ $kpi['icon'] }}"></i>
                                        </div>
                                    </div>
                                    <div class="col-8">
                                        <p class="kpi-title">{{ $kpi['title'] }}</p>
                                        <p class="kpi-value">{{ number_format((float) $kpi['value'], 0, ',', '.') }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="col-12 col-xl-8">
            <div class="card">
                <div class="card-header">
                    <h4>
                        {{ $isSoh ? 'Grafik Performa ATL' : 'Grafik Performa Dealer' }}
                    </h4>
                    <p class="text-muted mb-0">
                        Visualisasi ringkas performa operasional berdasarkan cakupan akses.
                    </p>
                </div>

                <div class="card-body">
                    <div class="placeholder-chart">
                        <div>
                            <i class="bi bi-bar-chart-line fs-1 d-block mb-3"></i>
                            {{ $isSoh
                                ? 'Grafik perbandingan performa antar-ATL akan ditampilkan di sini.'
                                : 'Grafik performa dealer wilayah ATL akan ditampilkan di sini.' }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-4">
            <div class="card">
                <div class="card-header">
                    <h4>Status Operasional</h4>
                    <p class="text-muted mb-0">Ringkasan kondisi data dalam cakupan saat ini.</p>
                </div>
                <div class="card-body">
                    <div class="status-summary-item">
                        <div><span class="status-dot status-success"></span>Dealer Aktif</div>
                        <span class="badge bg-light-success text-success">{{ number_format((float) ($kpis['dealers'] ?? 0), 0, ',', '.') }}</span>
                    </div>
                    <div class="status-summary-item">
                        <div><span class="status-dot status-warning"></span>Potensi Open</div>
                        <span class="badge bg-light-warning text-warning">{{ number_format((float) ($kpis['potential_open'] ?? 0), 0, ',', '.') }}</span>
                    </div>
                    <div class="status-summary-item mb-0">
                        <div><span class="status-dot status-danger"></span>Terlambat Hari Ini</div>
                        <span class="badge bg-light-danger text-danger">{{ number_format((float) ($kpis['late_attendances'] ?? 0), 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>
        </div>

        @if ($isSoh)
            <div class="col-12 col-xl-6">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div>
                            <h4>Ringkasan ATL</h4>
                            <p class="text-muted mb-0">Dealer dan mekanik per wilayah ATL.</p>
                        </div>
                        <a href="{{ route('atl-regions.index') }}" class="btn btn-sm btn-light-primary">Data ATL</a>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead><tr><th>ATL</th><th>Wilayah</th><th>Dealer</th><th>Mekanik</th></tr></thead>
                                <tbody>
                                    @forelse ($atlSummaries as $atl)
                                        <tr>
                                            <td>{{ $atl->name }}</td>
                                            <td>{{ $atl->region }}</td>
                                            <td>{{ $atl->dealers }}</td>
                                            <td>{{ $atl->mechanics }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="4" class="text-center text-muted py-4">Belum ada data ATL.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <div class="col-12 {{ $isSoh ? 'col-xl-6' : '' }}">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h4>Ringkasan Dealer</h4>
                        <p class="text-muted mb-0">Jumlah mekanik per dealer dalam cakupan akses.</p>
                    </div>
                    <a href="{{ route('dealers.index', ['role' => $activeRole]) }}" class="btn btn-sm btn-light-primary">Data Dealer</a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead><tr><th>Dealer</th><th>Cabang</th><th>Mekanik</th><th>ATL</th></tr></thead>
                            <tbody>
                                @forelse ($dealerSummaries as $dealer)
                                    <tr>
                                        <td>{{ $dealer->dealer }}</td>
                                        <td>{{ $dealer->cabang }}</td>
                                        <td>{{ $dealer->mechanics }}</td>
                                        <td>ATL {{ $dealer->no_atl }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="text-center text-muted py-4">Belum ada data dealer.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-6">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h4>Mekanik Aktif</h4>
                        <p class="text-muted mb-0">Mekanik dalam cakupan akses saat ini.</p>
                    </div>
                    <a href="{{ route('mechanics.index', ['role' => $activeRole]) }}" class="btn btn-sm btn-light-primary">Data Mekanik</a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead><tr><th>NIP</th><th>Nama</th><th>Dealer</th></tr></thead>
                            <tbody>
                                @forelse ($mechanics as $mechanic)
                                    <tr>
                                        <td>{{ $mechanic->nip }}</td>
                                        <td><a href="{{ route('mechanics.show', ['mechanic' => $mechanic->id, 'role' => $activeRole]) }}">{{ $mechanic->nama ?? $mechanic->username }}</a></td>
                                        <td>{{ $mechanic->dealer }} - {{ $mechanic->cabang }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="text-center text-muted py-4">Belum ada data mekanik.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-6">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h4>Presensi Terbaru</h4>
                        <p class="text-muted mb-0">Aktivitas presensi terakhir dari wilayah yang bisa diakses.</p>
                    </div>
                    <a href="{{ route('attendances.index', ['role' => $activeRole]) }}" class="btn btn-sm btn-light-primary">Rekap Presensi</a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead><tr><th>Tanggal</th><th>Mekanik</th><th>Dealer</th><th>Status</th></tr></thead>
                            <tbody>
                                @forelse ($recentAttendances as $attendance)
                                    <tr>
                                        <td>{{ $attendance->date }} {{ $attendance->time }}</td>
                                        <td>{{ $attendance->name }}</td>
                                        <td>{{ $attendance->dealer }} - {{ $attendance->cabang }}</td>
                                        <td>{{ $attendance->category ?? '-' }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="text-center text-muted py-4">Belum ada data presensi.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    </section>
@endsection
