@extends('layouts.dashboard')

@section('title', $isDailyRoute ? 'Presensi Harian' : 'Rekap Presensi')
@section('page-title', $isDailyRoute ? 'Presensi Harian' : 'Rekap Presensi')
@section('page-description', $isDailyRoute ? 'Presensi mekanik pada tanggal tertentu.' : 'Rekap presensi mekanik pada periode bulanan.')

@section('content')
    <div class="attendance-page">
        <div class="attendance-kpi-grid">
            @foreach ([
                ['label' => 'Total Presensi', 'value' => $kpis['total'], 'tone' => 'primary'],
                ['label' => 'Reguler', 'value' => $kpis['regular'], 'tone' => 'success'],
                ['label' => 'Terlambat', 'value' => $kpis['late'], 'tone' => 'danger'],
                ['label' => 'Backup', 'value' => $kpis['backup'], 'tone' => 'warning'],
                ['label' => 'Piket', 'value' => $kpis['piket'], 'tone' => 'info'],
                ['label' => 'Standby', 'value' => $kpis['standby'], 'tone' => 'secondary'],
            ] as $item)
                <div class="attendance-kpi-card {{ $item['tone'] }}">
                    <span>{{ $item['label'] }}</span>
                    <strong>{{ number_format((float) $item['value'], 0, ',', '.') }}</strong>
                </div>
            @endforeach
        </div>

        <div class="card attendance-filter-card">
            <div class="card-body">
                <form method="GET" action="{{ $isDailyRoute ? route('mechanics.attendances.daily') : (($isRecapRoute ?? false) ? route('mechanics.attendances.recap') : route('attendances.index')) }}">
                    <input type="hidden" name="role" value="{{ $role }}">
                    <div class="row g-3 align-items-end">
                        <div class="col-12 col-lg-3">
                            <label class="form-label">{{ $isDailyRoute ? 'Tanggal Presensi' : 'Periode Rekap' }}</label>
                            @if ($isDailyRoute)
                                <input type="date" name="date" value="{{ $date }}" class="form-control">
                            @else
                                <input type="month" name="period" value="{{ $period }}" class="form-control">
                            @endif
                        </div>

                        <div class="col-12 col-lg-6">
                            <label class="form-label">Dealer</label>
                            <select name="dealer_id" class="form-select">
                                <option value="">Semua Dealer</option>
                                @foreach ($dealers as $dealer)
                                    <option value="{{ $dealer->id }}" @selected((string) request('dealer_id') === (string) $dealer->id)>
                                        {{ $dealer->nama_dealer ?? trim(($dealer->dealer ?? '').' '.($dealer->cabang ?? '')) }}{{ $dealer->kotakab ? ' - '.$dealer->kotakab : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-12 col-lg-3">
                            <button class="btn btn-primary w-100">
                                <i class="bi bi-funnel-fill me-1"></i>
                                Terapkan Filter
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card attendance-table-card">
            <div class="card-header d-flex flex-column flex-lg-row justify-content-between gap-2">
                <div>
                    <h4 class="mb-1">{{ $isDailyRoute ? 'Daftar Presensi Harian' : 'Rekap Presensi Bulanan' }}</h4>
                    <p class="text-muted mb-0">
                        {{ $isDailyRoute ? 'Tanggal '.$date : 'Periode '.$period }}
                    </p>
                </div>
                <span class="attendance-count-badge">{{ $attendances->total() }} data</span>
            </div>

            <div class="card-body p-0">
                <div class="table-responsive attendance-table-wrap">
                    @if (! $isDailyRoute)
                        <table class="table table-hover align-middle mb-0 attendance-table">
                            <thead>
                                <tr>
                                    <th>Mekanik</th>
                                    <th>Dealer</th>
                                    <th>Total</th>
                                    <th>Reguler</th>
                                    <th>Terlambat</th>
                                    <th>Backup</th>
                                    <th>Piket</th>
                                    <th>Standby</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($recaps as $recap)
                                    <tr>
                                        <td><strong>{{ $recap->name }}</strong><small class="d-block text-muted">NIP {{ $recap->nip }}</small></td>
                                        <td>{{ $recap->dealer ?? '-' }}<small class="d-block text-muted">{{ $recap->cabang ?? '-' }}</small></td>
                                        <td><span class="badge bg-light-primary text-primary">{{ $recap->total_presensi }}</span></td>
                                        <td>{{ $recap->total_reguler }}</td>
                                        <td><span class="text-danger fw-bold">{{ $recap->total_terlambat }}</span></td>
                                        <td>{{ $recap->total_backup }}</td>
                                        <td>{{ $recap->total_piket }}</td>
                                        <td>{{ $recap->total_standby }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="8" class="text-center text-muted py-5">Belum ada data rekap presensi.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    @else
                        <table class="table table-hover align-middle mb-0 attendance-table">
                            <thead>
                                <tr><th>Tanggal</th><th>Mekanik</th><th>Dealer</th><th>Jam</th><th>Status</th><th>Keterangan</th></tr>
                            </thead>
                            <tbody>
                                @forelse ($attendances as $attendance)
                                    <tr>
                                        <td><strong>{{ \Carbon\Carbon::parse($attendance->date)->format('d M Y') }}</strong><small class="d-block text-muted">NIP {{ $attendance->nip }}</small></td>
                                        <td>{{ $attendance->name ?? '-' }}</td>
                                        <td>{{ $attendance->dealer ?? '-' }}<small class="d-block text-muted">{{ $attendance->cabang ?? '-' }}</small></td>
                                        <td>{{ $attendance->time ?? '-' }}</td>
                                        <td><span class="badge bg-light-primary text-primary">{{ $attendance->category ?? 'Tidak Ada' }}</span></td>
                                        <td class="attendance-note">{{ $attendance->des ?? '-' }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="text-center text-muted py-5">Belum ada data presensi.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    @endif
                </div>
            </div>

            <div class="card-footer bg-white">
                {{ ($isDailyRoute ? $attendances : $recaps)->links() }}
            </div>
        </div>
    </div>
@endsection
