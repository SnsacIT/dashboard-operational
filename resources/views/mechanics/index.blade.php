@extends('layouts.dashboard')

@section('title', 'Data Mekanik')
@section('page-title', 'Data Mekanik')
@section('page-description', 'Mekanik aktif berdasarkan dealer/cabang dengan status presensi terakhir.')

@section('content')
    <div class="attendance-page">
        <div class="attendance-kpi-grid four-items">
            <div class="attendance-kpi-card primary"><span>Total Mekanik</span><strong>{{ number_format($kpis['total'], 0, ',', '.') }}</strong></div>
            <div class="attendance-kpi-card success"><span>Hadir Data Terakhir</span><strong>{{ number_format($kpis['present_today'], 0, ',', '.') }}</strong></div>
            <div class="attendance-kpi-card danger"><span>Terlambat</span><strong>{{ number_format($kpis['late_today'], 0, ',', '.') }}</strong></div>
            <div class="attendance-kpi-card info"><span>Tanggal Presensi</span><strong class="compact-date">{{ $kpis['latest_date'] ?? '-' }}</strong></div>
        </div>

        <div class="card attendance-filter-card">
            <div class="card-body">
                <form method="GET" action="{{ route('mechanics.index') }}">
                    <input type="hidden" name="role" value="{{ $role }}">
                    <div class="row g-3 align-items-end">
                        <div class="col-12 col-lg-4">
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
                        <div class="col-12 col-lg-5">
                            <label class="form-label">Cari Mekanik</label>
                            <input type="search" name="search" value="{{ request('search') }}" class="form-control" placeholder="NIP, nama, dealer, cabang">
                        </div>
                        <div class="col-12 col-lg-3">
                            <button class="btn btn-primary w-100"><i class="bi bi-search me-1"></i>Terapkan Filter</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card attendance-table-card">
            <div class="card-header d-flex flex-column flex-lg-row justify-content-between gap-2">
                <div>
                    <h4 class="mb-1">Daftar Mekanik Aktif</h4>
                    <p class="text-muted mb-0">Status presensi mengikuti data terakhir {{ $kpis['latest_date'] ?? '-' }}.</p>
                </div>
                <span class="attendance-count-badge">{{ $mechanics->total() }} data</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive attendance-table-wrap">
                    <table class="table table-hover align-middle mb-0 attendance-table">
                        <thead>
                            <tr><th>NIP</th><th>Nama Mekanik</th><th>Dealer</th><th>ATL</th><th>Status Presensi</th><th>Status</th><th>Aksi</th></tr>
                        </thead>
                        <tbody>
                            @forelse ($mechanics as $mechanic)
                                @php $status = $attendanceToday[$mechanic->nip] ?? 'Belum Presensi'; @endphp
                                <tr>
                                    <td><strong>{{ $mechanic->nip }}</strong></td>
                                    <td>{{ $mechanic->nama ?? $mechanic->username }}<small class="d-block text-muted">{{ $mechanic->posisi ?? 'Mekanik' }}</small></td>
                                    <td>{{ $mechanic->dealer }}<small class="d-block text-muted">{{ $mechanic->cabang }}</small></td>
                                    <td><span class="badge bg-light-primary text-primary">ATL {{ $mechanic->no_atl ?? '-' }}</span></td>
                                    <td><span class="badge {{ $status === 'Belum Presensi' ? 'bg-light-warning text-warning' : 'bg-light-success text-success' }}">{{ $status }}</span></td>
                                    <td><span class="badge bg-light-success text-success">Aktif</span></td>
                                    <td><a href="{{ route('mechanics.show', ['mechanic' => $mechanic->id, 'role' => $role]) }}" class="btn btn-sm btn-light-primary">Detail</a></td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="text-center text-muted py-5">Belum ada data mekanik.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer bg-white">{{ $mechanics->links() }}</div>
        </div>
    </div>
@endsection
