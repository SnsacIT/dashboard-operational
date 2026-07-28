@extends('layouts.dashboard')

@section('title', 'Data Mekanik')
@section('page-title', 'Data Mekanik')
@section('page-description', 'Mekanik yang berada dalam dealer/cabang sesuai cakupan akses.')

@section('content')
    <div class="row row-cols-1 row-cols-md-4 g-4 mb-4">
        <div class="col"><div class="card mb-0"><div class="card-body"><p class="kpi-title">Total Mekanik</p><p class="kpi-value">{{ number_format($kpis['total'], 0, ',', '.') }}</p></div></div></div>
        <div class="col"><div class="card mb-0"><div class="card-body"><p class="kpi-title">Hadir Hari Ini</p><p class="kpi-value">{{ number_format($kpis['present_today'], 0, ',', '.') }}</p></div></div></div>
        <div class="col"><div class="card mb-0"><div class="card-body"><p class="kpi-title">Terlambat</p><p class="kpi-value">{{ number_format($kpis['late_today'], 0, ',', '.') }}</p></div></div></div>
        <div class="col"><div class="card mb-0"><div class="card-body"><p class="kpi-title">Data Presensi Terakhir</p><p class="kpi-value" style="font-size: 18px;">{{ $kpis['latest_date'] ?? '-' }}</p></div></div></div>
    </div>

    <form class="monitoring-filter" method="GET" action="{{ route('mechanics.index') }}">
        <input type="hidden" name="role" value="{{ $role }}">
        <div class="row g-3 align-items-end">
            <div class="col-12 col-md-4">
                <label class="form-label">Dealer</label>
                <select name="dealer_id" class="form-select">
                    <option value="">Semua Dealer</option>
                    @foreach ($dealers as $dealer)
                        <option value="{{ $dealer->id }}" @selected((string) request('dealer_id') === (string) $dealer->id)>{{ $dealer->nama_dealer ?? trim(($dealer->dealer ?? '').' '.($dealer->cabang ?? '')) }}{{ $dealer->kotakab ? ' - '.$dealer->kotakab : '' }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-md-5">
                <label class="form-label">Cari Mekanik</label>
                <input type="search" name="search" value="{{ request('search') }}" class="form-control" placeholder="NIP, nama, dealer, cabang">
            </div>
            <div class="col-12 col-md-3">
                <button class="btn btn-primary w-100">Terapkan Filter</button>
            </div>
        </div>
    </form>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead><tr><th>ID Mekanik</th><th>Nama</th><th>Dealer</th><th>ATL</th><th>Status Presensi</th><th>Jumlah Pekerjaan</th><th>Status</th><th>Aksi</th></tr></thead>
                    <tbody>
                        @forelse ($mechanics as $mechanic)
                            <tr>
                                <td>{{ $mechanic->nip }}</td>
                                <td>{{ $mechanic->nama ?? $mechanic->username }}<small class="d-block text-muted">{{ $mechanic->posisi ?? '-' }}</small></td>
                                <td>{{ $mechanic->dealer }} - {{ $mechanic->cabang }}</td>
                                <td>ATL {{ $mechanic->no_atl ?? '-' }}</td>
                                <td>{{ $attendanceToday[$mechanic->nip] ?? 'Belum Presensi' }}</td>
                                <td>{{ $jobCounts[$mechanic->nip] ?? 0 }}</td>
                                <td><span class="badge bg-light-success text-success">Aktif</span></td>
                                <td><a href="{{ route('mechanics.show', ['mechanic' => $mechanic->id, 'role' => $role]) }}" class="btn btn-sm btn-light-primary">Detail</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-center text-muted py-4">Belum ada data mekanik.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $mechanics->links() }}
        </div>
    </div>
@endsection
