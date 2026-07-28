@extends('layouts.dashboard')

@section('title', $isDailyRoute ? 'Presensi Harian' : 'Rekap Presensi')
@section('page-title', $isDailyRoute ? 'Presensi Harian' : 'Rekap Presensi')
@section('page-description', $isDailyRoute ? 'Presensi mekanik pada tanggal tertentu.' : 'Rekap presensi mekanik pada periode bulanan.')

@section('content')
    <div class="row row-cols-1 row-cols-md-3 row-cols-xl-6 g-4 mb-4">
        <div class="col"><div class="card mb-0"><div class="card-body"><p class="kpi-title">Total Presensi</p><p class="kpi-value">{{ number_format($kpis['total'], 0, ',', '.') }}</p></div></div></div>
        <div class="col"><div class="card mb-0"><div class="card-body"><p class="kpi-title">Reguler</p><p class="kpi-value">{{ number_format($kpis['regular'], 0, ',', '.') }}</p></div></div></div>
        <div class="col"><div class="card mb-0"><div class="card-body"><p class="kpi-title">Terlambat</p><p class="kpi-value">{{ number_format($kpis['late'], 0, ',', '.') }}</p></div></div></div>
        <div class="col"><div class="card mb-0"><div class="card-body"><p class="kpi-title">Backup</p><p class="kpi-value">{{ number_format($kpis['backup'], 0, ',', '.') }}</p></div></div></div>
        <div class="col"><div class="card mb-0"><div class="card-body"><p class="kpi-title">Piket</p><p class="kpi-value">{{ number_format($kpis['piket'], 0, ',', '.') }}</p></div></div></div>
        <div class="col"><div class="card mb-0"><div class="card-body"><p class="kpi-title">Standby</p><p class="kpi-value">{{ number_format($kpis['standby'], 0, ',', '.') }}</p></div></div></div>
    </div>

    <form class="monitoring-filter" method="GET" action="{{ $isDailyRoute ? route('mechanics.attendances.daily') : route('attendances.index') }}">
        <input type="hidden" name="role" value="{{ $role }}">
        <div class="row g-3 align-items-end">
            <div class="col-12 col-md-3">
                <label class="form-label">{{ $isDailyRoute ? 'Tanggal' : 'Periode' }}</label>
                @if ($isDailyRoute)
                    <input type="date" name="date" value="{{ $date }}" class="form-control">
                @else
                    <input type="month" name="period" value="{{ $period }}" class="form-control">
                @endif
            </div>
            <div class="col-12 col-md-5">
                <label class="form-label">Dealer</label>
                <select name="dealer_id" class="form-select">
                    <option value="">Semua Dealer</option>
                    @foreach ($dealers as $dealer)
                        <option value="{{ $dealer->id }}" @selected((string) request('dealer_id') === (string) $dealer->id)>{{ $dealer->nama_dealer ?? trim(($dealer->dealer ?? '').' '.($dealer->cabang ?? '')) }}{{ $dealer->kotakab ? ' - '.$dealer->kotakab : '' }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-md-3"><button class="btn btn-primary w-100">Terapkan Filter</button></div>
        </div>
    </form>

    <div class="card"><div class="card-body"><div class="table-responsive"><table class="table table-hover align-middle"><thead><tr><th>Tanggal</th><th>NIP</th><th>Mekanik</th><th>Dealer</th><th>Jam</th><th>Status</th><th>Keterangan</th></tr></thead><tbody>@forelse ($attendances as $attendance)<tr><td>{{ $attendance->date }}</td><td>{{ $attendance->nip }}</td><td>{{ $attendance->name }}</td><td>{{ $attendance->dealer }} - {{ $attendance->cabang }}</td><td>{{ $attendance->time ?? '-' }}</td><td>{{ $attendance->category ?? '-' }}</td><td>{{ $attendance->des ?? '-' }}</td></tr>@empty<tr><td colspan="7" class="text-center text-muted py-4">Belum ada data presensi.</td></tr>@endforelse</tbody></table></div>{{ $attendances->links() }}</div></div>
@endsection
