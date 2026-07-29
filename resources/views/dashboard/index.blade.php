@php
    $activeRole = strtolower((string) ($role ?? (auth()->user()->dashboard_role ?? 'atl')));
    $isSoh = $activeRole === 'soh';
    $atlTotal = (int) ($kpis['atls'] ?? 0);
    $dealerTotal = (int) ($kpis['dealers'] ?? (isset($allDealers) ? $allDealers->count() : 0));
    $mechanicTotal = (int) ($kpis['mechanics'] ?? (isset($mechanics) ? $mechanics->count() : 0));
    $presentLatest = (int) ($kpis['present_latest'] ?? 0);
    $unitPerMechanic = is_object($productivity ?? null) && ! $productivity instanceof __PHP_Incomplete_Class ? (float) ($productivity->unit_per_mechanic ?? 0) : 0;
    $omsetPerDealer = is_object($productivity ?? null) && ! $productivity instanceof __PHP_Incomplete_Class ? (float) ($productivity->omset_per_dealer ?? 0) : 0;
    $kpiCards = [
        ['title' => 'ATL', 'value' => $atlTotal, 'tone' => 'violet', 'show' => $isSoh],
        ['title' => 'Dealer', 'value' => $dealerTotal, 'tone' => 'blue', 'show' => true],
        ['title' => 'Mekanik', 'value' => $mechanicTotal, 'tone' => 'emerald', 'show' => true],
        ['title' => 'Hadir Terakhir', 'value' => $presentLatest, 'tone' => 'cyan', 'show' => true],
        ['title' => 'Unit Entry', 'value' => $kpis['unit_entry'] ?? 0, 'tone' => 'orange', 'show' => true],
        ['title' => 'Unit AC', 'value' => $kpis['unit_ac'] ?? 0, 'tone' => 'sky', 'show' => true],
        ['title' => 'Potensi Open', 'value' => $kpis['potential_open'] ?? 0, 'tone' => 'rose', 'show' => true],
    ];
@endphp

@extends('layouts.dashboard')

@section('title', $isSoh ? 'Dashboard Area SOH' : 'Dashboard Wilayah ATL')

@section('page-title')
    {{ $isSoh ? 'Dashboard Area SOH' : 'Dashboard Wilayah ATL' }}
@endsection

@section('content')
    <section class="dashboard-vibrant row">
        <div class="col-12">
            <form class="dashboard-filter-card" method="GET" action="{{ route('dashboard') }}">
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
                                    @continue(! is_object($atl) || ! isset($atl->urutan))
                                    <option value="{{ $atl->urutan }}" @selected((string) request('atl_id') === (string) $atl->urutan)>
                                        {{ $atl->nama ?? $atl->username ?? $atl->nip_atl ?? 'ATL '.$atl->urutan }} - {{ $atl->nama_wilayah ?? 'Wilayah '.$atl->urutan }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <div class="col-12 col-md-6 col-xl-3">
                        <label class="form-label">Dealer</label>
                        <select name="dealer_id" id="dealer-select" class="form-select">
                            <option value="">Semua Dealer</option>
                            @if (! empty($selectedDealer))
                                <option value="{{ $selectedDealer->id }}" selected>{{ ($selectedDealer->nama_dealer ?: trim(($selectedDealer->dealer ?? '').' '.($selectedDealer->cabang ?? ''))).($selectedDealer->kotakab ? ' - '.$selectedDealer->kotakab : '') }}</option>
                            @endif
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
            <div class="dashboard-kpi-grid">
                @foreach ($kpiCards as $kpi)
                    @continue(! $kpi['show'])
                    <div class="dashboard-kpi-card {{ $kpi['tone'] }}">
                        <div>
                            <span>{{ $kpi['title'] }}</span>
                            <strong>{{ number_format((float) $kpi['value'], 0, ',', '.') }}</strong>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="col-12 col-xl-8">
            <div class="card dashboard-glass-card">
                <div class="card-header dashboard-section-header">
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
                                $atlLabels = collect($atlChart['labels'] ?? [])->values();
                                $atlDealers = collect($atlChart['dealers'] ?? [])->values();
                                $atlRegions = collect($atlChart['regions'] ?? [])->values();
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
                                                $labelText = is_scalar($label) ? (string) $label : 'ATL';
                                                $dealerCount = (int) ($atlDealers[$index] ?? 0);
                                                $percentage = min(100, round(($dealerCount / $maxDealer) * 100));
                                                $previous = (int) ($atlDealers[$index + 1] ?? $dealerCount);
                                                $delta = $dealerCount - $previous;
                                                $tone = $delta >= 0 ? 'up' : 'down';
                                            @endphp
                                            <div class="market-atl-line {{ $tone }}">
                                                <div class="market-atl-symbol">
                                                    <strong>{{ Str::limit($labelText, 14, '') }}</strong>
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

                        @unless ($isSoh)
                            @php
                                $dealerChartRows = collect($dealerSummaries ?? [])->filter(fn ($dealer) => is_object($dealer))->take(5)->values();
                                $maxMechanics = max(1, (int) $dealerChartRows->max(fn ($dealer) => (int) ($dealer->mechanics ?? 0)));
                            @endphp
                            <div class="col-12">
                                <div class="dealer-performance-chart">
                                    <div class="dealer-performance-head">
                                        <div>
                                            <span>Dealer Performance</span>
                                            <strong>Distribusi Mekanik Aktif</strong>
                                        </div>
                                        <small>Top 5 dealer</small>
                                    </div>

                                    <div class="dealer-performance-grid">
                                        @forelse ($dealerChartRows as $dealer)
                                            @php
                                                $mechanicsCount = (int) ($dealer->mechanics ?? 0);
                                                $percentage = min(100, round(($mechanicsCount / $maxMechanics) * 100));
                                            @endphp
                                            <div class="dealer-performance-line">
                                                <div class="dealer-performance-name">
                                                    <strong>{{ Str::limit($dealer->nama_dealer ?? $dealer->dealer ?? 'Dealer', 24) }}</strong>
                                                    <span>{{ $dealer->cabang ?? '-' }} • ATL {{ $dealer->no_atl ?? '-' }}</span>
                                                </div>
                                                <div class="dealer-performance-track">
                                                    <div class="dealer-performance-fill" style="width: {{ $percentage }}%"></div>
                                                </div>
                                                <div class="dealer-performance-value">
                                                    <strong>{{ number_format($mechanicsCount, 0, ',', '.') }}</strong>
                                                    <span>mekanik</span>
                                                </div>
                                            </div>
                                        @empty
                                            <div class="text-center text-muted py-4">Belum ada data dealer untuk grafik.</div>
                                        @endforelse
                                    </div>
                                </div>
                            </div>
                        @endunless

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
                                <div class="dashboard-productivity-note">
                                    <i class="bi bi-speedometer2"></i>
                                    <span>Produktivitas: {{ number_format($unitPerMechanic, 1, ',', '.') }} unit/mekanik dan Rp {{ number_format($omsetPerDealer, 0, ',', '.') }}/dealer.</span>
                                </div>
                            </div>
                        @endunless
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-4">
            <div class="card dashboard-status-card">
                <div class="card-header dashboard-section-header">
                    <h4>Status Operasional</h4>
                    <p class="text-muted mb-0">Ringkasan kondisi data dalam cakupan saat ini.</p>
                </div>
                <div class="card-body">
                    <div class="status-summary-item vivid">
                        <div><span class="status-dot status-success"></span>Dealer Aktif</div>
                        <span class="badge bg-light-success text-success">{{ number_format((float) ($kpis['dealers'] ?? 0), 0, ',', '.') }}</span>
                    </div>
                    <div class="status-summary-item vivid">
                        <div><span class="status-dot status-success"></span>Pekerjaan Bulan Ini</div>
                        <span class="badge bg-light-success text-success">{{ number_format((float) ($kpis['unit_entry'] ?? $officePerformance->pekerjaan_total ?? 0), 0, ',', '.') }}</span>
                    </div>
                    <div class="status-summary-item vivid">
                        <div><span class="status-dot status-warning"></span>Potensi Open</div>
                        <span class="badge bg-light-warning text-warning">{{ number_format((float) ($kpis['potential_open'] ?? 0), 0, ',', '.') }}</span>
                    </div>
                    <div class="status-summary-item vivid mb-0">
                        <div><span class="status-dot status-danger"></span>Terlambat Data Terakhir</div>
                        <span class="badge bg-light-danger text-danger">{{ number_format((float) ($kpis['late_attendances'] ?? 0), 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>
        </div>

        @if ($isSoh)
            <div class="col-12 col-xl-6">
                            <div class="card dashboard-table-card">
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
                                        @continue(! is_object($atl) || ! isset($atl->name))
                                        <tr>
                                            <td>{{ $atl->name ?? '-' }}</td>
                                            <td>{{ $atl->region ?? '-' }}</td>
                                            <td>{{ $atl->dealers ?? 0 }}</td>
                                            <td>{{ $atl->mechanics ?? 0 }}</td>
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

        <div class="col-12 col-xl-6">
            <div class="card dashboard-table-card">
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
                                    @continue(! is_object($dealer) || ! isset($dealer->dealer))
                                    <tr>
                                        <td>{{ $dealer->nama_dealer ?? $dealer->dealer ?? '-' }}</td>
                                        <td>{{ $dealer->cabang ?? '-' }}</td>
                                        <td>{{ $dealer->mechanics ?? 0 }}</td>
                                        <td>ATL {{ $dealer->no_atl ?? '-' }}</td>
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
            <div class="card dashboard-table-card">
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
                                    @continue(! is_object($mechanic) || ! isset($mechanic->id))
                                    <tr>
                                        <td>{{ $mechanic->nip ?? '-' }}</td>
                                        <td><a href="{{ route('mechanics.show', ['mechanic' => $mechanic->id, 'role' => $activeRole]) }}">{{ $mechanic->nama ?? $mechanic->username ?? 'Mekanik' }}</a></td>
                                        <td>{{ $mechanic->dealer ?? '-' }} - {{ $mechanic->cabang ?? '-' }}</td>
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

        @if ($isSoh)
            <div class="col-12 col-xl-6">
                <div class="card dashboard-table-card">
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
                                        @continue(! is_object($attendance) || ! isset($attendance->date))
                                        <tr>
                                            <td>{{ $attendance->date ?? '-' }} {{ $attendance->time ?? '' }}</td>
                                            <td>{{ $attendance->name ?? '-' }}</td>
                                            <td>{{ $attendance->dealer ?? '-' }} - {{ $attendance->cabang ?? '-' }}</td>
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
        @endif

    </section>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const atlSelect = document.querySelector('select[name="atl_id"]');
            const dealerSelect = document.getElementById('dealer-select');

            if (!dealerSelect) {
                return;
            }

            let timeoutId;

            const loadDealers = () => {
                const params = new URLSearchParams();

                if (atlSelect && atlSelect.value) {
                    params.set('atl_id', atlSelect.value);
                }

                if (dealerSelect.dataset.search) {
                    params.set('search', dealerSelect.dataset.search);
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

            const scheduleLoad = (search = '') => {
                clearTimeout(timeoutId);
                dealerSelect.dataset.search = search;
                timeoutId = setTimeout(loadDealers, 250);
            };

            if (atlSelect) {
                atlSelect.addEventListener('change', () => {
                    dealerSelect.value = '';
                    loadDealers();
                });
            }

            dealerSelect.addEventListener('focus', () => loadDealers());
            dealerSelect.addEventListener('keydown', (event) => {
                if (event.key.length === 1 || event.key === 'Backspace') {
                    const current = dealerSelect.dataset.search || '';
                    scheduleLoad(event.key === 'Backspace' ? current.slice(0, -1) : current + event.key);
                }
            });
        });
    </script>
@endpush
