@extends('layouts.dashboard')

@section('title', 'Detail Dealer')
@section('page-title', $dealer->dealer)
@section('page-description', 'Detail dealer, mekanik, presensi, precheck, postcheck, dan aktivitas periode aktif.')

@section('content')
    <div class="attendance-page dealer-detail-page">
        <div class="mechanic-profile-hero">
            <div class="mechanic-profile-avatar">{{ strtoupper(substr($dealer->dealer ?? 'D', 0, 1)) }}</div>
            <div class="mechanic-profile-main">
                <span>Dealer Operasional</span>
                <h4>{{ $dealer->dealer }}</h4>
                <p>{{ $dealer->cabang }}{{ $dealer->kotakab ? ' • '.$dealer->kotakab : '' }} • Periode {{ $period }}</p>
            </div>
            <a href="{{ route('dealers.index', ['role' => $role]) }}" class="btn btn-light-primary">Kembali</a>
        </div>

        <div class="attendance-kpi-grid four-items">
            <div class="attendance-kpi-card primary"><span>Mekanik</span><strong>{{ number_format($kpis['mechanics'], 0, ',', '.') }}</strong></div>
            <div class="attendance-kpi-card success"><span>Presensi Periode</span><strong>{{ number_format($kpis['presences'], 0, ',', '.') }}</strong></div>
            <div class="attendance-kpi-card info"><span>Precheck Periode</span><strong>{{ number_format($kpis['prechecks'], 0, ',', '.') }}</strong></div>
            <div class="attendance-kpi-card warning"><span>Postcheck Periode</span><strong>{{ number_format($kpis['postchecks'], 0, ',', '.') }}</strong></div>
        </div>

        <div class="row g-4">
            <div class="col-12 col-xl-4">
                <div class="card attendance-table-card mechanic-info-card">
                    <div class="card-header"><h4 class="mb-1">Ringkasan Dealer</h4><p class="text-muted mb-0">Informasi penempatan dan cakupan.</p></div>
                    <div class="card-body">
                        <div class="mechanic-info-grid">
                            <div><span>Kode</span><strong>{{ $dealer->kode ?? '-' }}</strong></div>
                            <div><span>Status</span><strong>{{ $dealer->status_kontrak ?? 'Aktif' }}</strong></div>
                            <div><span>Cabang</span><strong>{{ $dealer->cabang ?? '-' }}</strong></div>
                            <div><span>Kota</span><strong>{{ $dealer->kotakab ?? '-' }}</strong></div>
                            <div><span>ATL</span><strong>{{ $dealer->atl ?? 'ATL '.($dealer->no_atl ?? '-') }}</strong></div>
                            <div><span>SOH</span><strong>{{ $dealer->soh ?? 'SOH '.($dealer->no_soh ?? '-') }}</strong></div>
                        </div>
                        <div class="dealer-address-box"><span>Alamat</span><p>{{ $dealer->alamat ?? '-' }}</p></div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-xl-8">
                <div class="card attendance-table-card">
                    <div class="card-header d-flex flex-column flex-lg-row justify-content-between gap-2"><div><h4 class="mb-1">Mekanik Dealer</h4><p class="text-muted mb-0">Daftar mekanik yang ditempatkan di dealer ini.</p></div><span class="attendance-count-badge">{{ $mechanics->total() }} data</span></div>
                    <div class="card-body p-0">
                        <div class="table-responsive attendance-table-wrap">
                            <table class="table table-hover align-middle mb-0 attendance-table">
                                <thead><tr><th>NIP</th><th>Nama</th><th>Penempatan</th><th>Grade</th><th>Status</th><th>Aksi</th></tr></thead>
                                <tbody>
                                    @forelse ($mechanics as $mechanic)
                                        <tr><td><strong>{{ $mechanic->nip }}</strong></td><td>{{ $mechanic->nama ?? $mechanic->username }}</td><td>{{ $mechanic->dealer ?? '-' }}<small class="d-block text-muted">{{ $mechanic->cabang ?? '-' }}</small></td><td>{{ $mechanic->grade ?? '-' }}</td><td><span class="badge bg-light-success text-success">Aktif</span></td><td><a href="{{ route('mechanics.show', ['mechanic' => $mechanic->id, 'role' => $role]) }}" class="btn btn-sm btn-light-primary">Detail</a></td></tr>
                                    @empty
                                        <tr><td colspan="6" class="text-center text-muted py-5">Belum ada mekanik.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer bg-white">@include('partials.simple-pagination', ['paginator' => $mechanics])</div>
                </div>

                <div class="card attendance-table-card mt-4">
                    <div class="card-header d-flex flex-column flex-lg-row justify-content-between gap-2"><div><h4 class="mb-1">Aktivitas Pre/Postcheck Terbaru</h4><p class="text-muted mb-0">Aktivitas pemeriksaan terbaru dari precheck dan postcheck.</p></div><span class="attendance-count-badge">{{ $activities->total() }} data</span></div>
                    <div class="card-body p-0">
                        <div class="table-responsive attendance-table-wrap">
                            <table class="table table-hover align-middle mb-0 attendance-table">
                                <thead><tr><th>Waktu</th><th>Jenis</th><th>Plat</th><th>Mekanik</th><th>Hasil</th></tr></thead>
                                <tbody>
                                    @forelse ($activities as $activity)
                                        <tr><td>{{ $activity->created_at }}</td><td><span class="badge {{ $activity->type === 'Postcheck' ? 'bg-light-primary text-primary' : 'bg-light-info text-info' }}">{{ $activity->type }}</span></td><td><strong>{{ $activity->noplat ?? '-' }}</strong></td><td>{{ $activity->teknisi ?? '-' }}</td><td>{{ $activity->result ?? '-' }}</td></tr>
                                    @empty
                                        <tr><td colspan="5" class="text-center text-muted py-5">Belum ada aktivitas pemeriksaan.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer bg-white">@include('partials.simple-pagination', ['paginator' => $activities])</div>
                </div>
            </div>
        </div>
    </div>
@endsection
