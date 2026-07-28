@php
    $activeRole = strtolower((string) ($role ?? (auth()->user()->dashboard_role ?? 'atl')));
    $isSoh = $activeRole === 'soh';
    $atlTotal = (int) ($kpis['atls'] ?? 0);
    $dealerTotal = (int) ($kpis['dealers'] ?? (isset($allDealers) ? $allDealers->count() : 0));
    $mechanicTotal = (int) ($kpis['mechanics'] ?? (isset($mechanics) ? $mechanics->count() : 0));
    $presentLatest = (int) ($kpis['present_latest'] ?? 0);
    $kpiCards = [
        ['title' => 'Total ATL', 'value' => $atlTotal, 'icon' => 'bi-diagram-3-fill', 'color' => 'purple', 'show' => $isSoh],
        ['title' => 'Total Dealer', 'value' => $dealerTotal, 'icon' => 'bi-shop', 'color' => 'blue', 'show' => true],
        ['title' => 'Total Mekanik', 'value' => $mechanicTotal, 'icon' => 'bi-people-fill', 'color' => 'green', 'show' => true],
        ['title' => 'Hadir Data Terakhir', 'value' => $presentLatest, 'icon' => 'bi-clock-history', 'color' => 'purple', 'show' => true],
        ['title' => 'Unit Entry', 'value' => $kpis['unit_entry'] ?? 0, 'icon' => 'bi-car-front-fill', 'color' => 'green', 'show' => true],
        ['title' => 'Unit AC', 'value' => $kpis['unit_ac'] ?? 0, 'icon' => 'bi-snow', 'color' => 'blue', 'show' => true],
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
                                        {{ $atl->nama ?? $atl->username ?? $atl->nip_atl }} - {{ $atl->nama_wilayah }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <div class="col-12 col-md-6 col-xl-3">
                        <label class="form-label">Dealer</label>
                        <input type="search" id="dealer-search" class="form-control mb-2" placeholder="Ketik dealer/daerah, contoh: Bali">
                        <select name="dealer_id" class="form-select">
                            <option value="">Semua Dealer</option>
                            @foreach ($allDealers as $dealer)
                                <option value="{{ $dealer->id }}" @selected((string) request('dealer_id') === (string) $dealer->id)>
                                    {{ $dealer->nama_dealer ?? trim(($dealer->dealer ?? '').' '.($dealer->cabang ?? '')) }}{{ $dealer->kotakab ? ' - '.$dealer->kotakab : '' }}
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
                        {{ $isSoh ? 'Top ATL berdasarkan jumlah dealer aktif.' : 'Visualisasi ringkas performa operasional berdasarkan cakupan akses.' }}
                    </p>
                </div>

                <div class="card-body">
                    <div class="row g-4">
                        @if ($isSoh)
                            @php
                                $atlLabels = collect($atlChart['labels'] ?? []);
                                $atlDealers = collect($atlChart['dealers'] ?? []);
                                $atlRegions = collect($atlChart['regions'] ?? []);
                                $maxDealer = max(1, (int) $atlDealers->max());
                            @endphp
                            <div class="col-12">
                                <div class="market-atl-chart">
                                    <div class="market-atl-head">
                                        <div>
                                            <span>ATL Market View</span>
                                            <strong>Dealer Distribution</strong>
                                        </div>
                                        <small>Top {{ $atlLabels->count() }} ATL aktif</small>
                                    </div>

                                    <div class="market-atl-grid">
                                        @forelse ($atlLabels as $index => $label)
                                            @php
                                                $dealerCount = (int) ($atlDealers[$index] ?? 0);
                                                $percentage = min(100, round(($dealerCount / $maxDealer) * 100));
                                                $previous = (int) ($atlDealers[$index + 1] ?? $dealerCount);
                                                $delta = $dealerCount - $previous;
                                                $tone = $delta >= 0 ? 'up' : 'down';
                                            @endphp
                                            <div class="market-atl-line {{ $tone }}">
                                                <div class="market-atl-symbol">
                                                    <strong>{{ Str::limit($label, 14, '') }}</strong>
                                                    <span>{{ $atlRegions[$index] ?? 'Regional' }}</span>
                                                </div>
                                                <div class="market-atl-track">
                                                    <div class="market-atl-fill" style="width: {{ $percentage }}%"></div>
                                                    <svg class="market-atl-spark" viewBox="0 0 120 22" preserveAspectRatio="none" aria-hidden="true">
                                                        <polyline points="0,15 18,12 34,14 50,7 68,10 84,5 102,8 120,3" />
                                                    </svg>
                                                </div>
                                                <div class="market-atl-price">
                                                    <strong>{{ number_format($dealerCount, 0, ',', '.') }}</strong>
                                                    <span>{{ $delta >= 0 ? '+' : '' }}{{ $delta }}</span>
                                                </div>
                                            </div>
                                        @empty
                                            <div class="text-center text-muted py-4">Belum ada data ATL untuk grafik.</div>
                                        @endforelse
                                    </div>
                                </div>
                            </div>
                        @endif

                        <div class="col-12 col-md-6">
                            <div class="p-3 rounded-4 bg-light-primary h-100">
                                <p class="text-muted mb-1">Omset Bulan Ini</p>
                                <h3 class="mb-0">Rp {{ number_format((float) ($kpis['omset_total'] ?? 0), 0, ',', '.') }}</h3>
                                <small>Diambil dari data pekerjaan dashboard office.</small>
                            </div>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="p-3 rounded-4 bg-light-success h-100">
                                <p class="text-muted mb-1">Rasio Postcheck</p>
                                <h3 class="mb-0">{{ number_format((float) ($kpis['postcheck_ratio'] ?? 0), 1, ',', '.') }}%</h3>
                                <small>Postcheck terhadap unit AC dari data office.</small>
                            </div>
                        </div>
                        @unless ($isSoh)
                        <div class="col-12">
                            <div class="placeholder-chart">
                                <div>
                                    <i class="bi bi-bar-chart-line fs-1 d-block mb-3"></i>
                                    Produktivitas: {{ number_format((float) ($productivity->unit_per_mechanic ?? 0), 1, ',', '.') }} unit/mekanik dan Rp {{ number_format((float) ($productivity->omset_per_dealer ?? 0), 0, ',', '.') }}/dealer.
                                </div>
                            </div>
                        </div>
                        @endunless
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
                        <div><span class="status-dot status-success"></span>Pekerjaan Bulan Ini</div>
                        <span class="badge bg-light-success text-success">{{ number_format((float) ($kpis['unit_entry'] ?? $officePerformance->pekerjaan_total ?? 0), 0, ',', '.') }}</span>
                    </div>
                    <div class="status-summary-item">
                        <div><span class="status-dot status-warning"></span>Potensi Open</div>
                        <span class="badge bg-light-warning text-warning">{{ number_format((float) ($kpis['potential_open'] ?? 0), 0, ',', '.') }}</span>
                    </div>
                    <div class="status-summary-item mb-0">
                        <div><span class="status-dot status-danger"></span>Terlambat Data Terakhir</div>
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

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const atlSelect = document.querySelector('select[name="atl_id"]');
            const dealerSelect = document.querySelector('select[name="dealer_id"]');
            const dealerSearch = document.getElementById('dealer-search');

            if (!dealerSelect || !dealerSearch) {
                return;
            }

            let timeoutId;

            const loadDealers = () => {
                const params = new URLSearchParams();

                if (atlSelect && atlSelect.value) {
                    params.set('atl_id', atlSelect.value);
                }

                if (dealerSearch.value.trim()) {
                    params.set('search', dealerSearch.value.trim());
                }

                dealerSelect.innerHTML = '<option value="">Memuat dealer...</option>';

                fetch(`{{ route('dashboard.dealers.options') }}?${params.toString()}`, {
                    headers: { 'Accept': 'application/json' }
                })
                    .then((response) => response.json())
                    .then((dealers) => {
                        dealerSelect.innerHTML = '<option value="">Semua Dealer</option>';

                        dealers.forEach((dealer) => {
                            const option = document.createElement('option');
                            option.value = dealer.id;
                            option.textContent = dealer.label;
                            dealerSelect.appendChild(option);
                        });
                    })
                    .catch(() => {
                        dealerSelect.innerHTML = '<option value="">Gagal memuat dealer</option>';
                    });
            };

            const scheduleLoad = () => {
                clearTimeout(timeoutId);
                timeoutId = setTimeout(loadDealers, 250);
            };

            if (atlSelect) {
                atlSelect.addEventListener('change', loadDealers);
            }

            dealerSearch.addEventListener('input', scheduleLoad);
        });
    </script>
@endpush
