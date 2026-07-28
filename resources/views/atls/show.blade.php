@extends('layouts.dashboard')

@section('title', 'Detail ATL')
@section('page-title', $atl->nama ?? $atl->username ?? $atl->nip_atl)
@section('page-description', 'Detail wilayah, dealer, mekanik, presensi, postcheck, dan performa office dalam cakupan ATL.')

@section('content')
    <div class="row row-cols-1 row-cols-md-4 g-4 mb-4">
        <div class="col"><div class="card mb-0"><div class="card-body"><p class="kpi-title">Dealer</p><p class="kpi-value">{{ number_format($kpis['dealers'], 0, ',', '.') }}</p></div></div></div>
        <div class="col"><div class="card mb-0"><div class="card-body"><p class="kpi-title">Mekanik</p><p class="kpi-value">{{ number_format($kpis['mechanics'], 0, ',', '.') }}</p></div></div></div>
        <div class="col"><div class="card mb-0"><div class="card-body"><p class="kpi-title">Hadir Data Terakhir</p><p class="kpi-value">{{ number_format($kpis['present_today'], 0, ',', '.') }}</p><small>{{ $kpis['attendance_date'] ?? '-' }}</small></div></div></div>
        <div class="col"><div class="card mb-0"><div class="card-body"><p class="kpi-title">Postcheck</p><p class="kpi-value">{{ number_format($kpis['postchecks'], 0, ',', '.') }}</p></div></div></div>
    </div>

    <div class="row row-cols-1 row-cols-md-3 g-4 mb-4">
        <div class="col"><div class="card mb-0"><div class="card-body"><p class="kpi-title">Unit Entry Office</p><p class="kpi-value">{{ number_format((float) ($officePerformance->unit_entry ?? 0), 0, ',', '.') }}</p></div></div></div>
        <div class="col"><div class="card mb-0"><div class="card-body"><p class="kpi-title">Omset Office</p><p class="kpi-value">Rp {{ number_format((float) ($officePerformance->omset_total ?? 0), 0, ',', '.') }}</p></div></div></div>
        <div class="col"><div class="card mb-0"><div class="card-body"><p class="kpi-title">Rasio Postcheck</p><p class="kpi-value">{{ number_format((float) ($postcheckRatio->ratio ?? 0), 1, ',', '.') }}%</p></div></div></div>
    </div>

    <div class="row">
        <div class="col-12 col-lg-4">
            <div class="card">
                <div class="card-body">
                    <h4>{{ $atl->nama ?? $atl->username ?? $atl->nip_atl }}</h4>
                    <p class="text-muted mb-2">{{ $atl->nama_wilayah }}</p>
                    <span class="badge bg-light-primary text-primary">NIP {{ $atl->nip_atl }}</span>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-8">
            <div class="card">
                <div class="card-header"><h4>Dealer di Wilayah Ini</h4></div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead><tr><th>Dealer</th><th>Cabang</th><th>Kota</th><th>Status</th></tr></thead>
                            <tbody>
                                @forelse ($dealers as $dealer)
                                    <tr>
                                        <td>{{ $dealer->nama_dealer ?? $dealer->dealer }}</td>
                                        <td>{{ $dealer->cabang }}</td>
                                        <td>{{ $dealer->kotakab ?? '-' }}</td>
                                        <td><span class="badge bg-light-success text-success">{{ $dealer->status_kontrak ?? 'Aktif' }}</span></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="text-center text-muted py-4">Belum ada dealer.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
