@extends('layouts.dashboard')

@section('title', 'Detail ATL')
@section('page-title', $atl->nama ?? $atl->username ?? $atl->nip_atl)
@section('page-description', 'Detail wilayah, dealer, mekanik, presensi, dan postcheck dalam cakupan ATL.')

@section('content')
    <div class="attendance-page atl-detail-page">
        <div class="mechanic-profile-hero">
            <div class="mechanic-profile-avatar">{{ strtoupper(substr($atl->nama ?? $atl->username ?? 'A', 0, 1)) }}</div>
            <div class="mechanic-profile-main">
                <span>Area Technical Lead</span>
                <h4>{{ $atl->nama ?? $atl->username ?? $atl->nip_atl }}</h4>
                <p>{{ $atl->nama_wilayah }} • NIP {{ $atl->nip_atl }}</p>
            </div>
            <a href="{{ route('atl-regions.index', ['role' => $role, 'period' => $period]) }}" class="btn btn-light-primary">Kembali</a>
        </div>

        <div class="attendance-kpi-grid four-items">
            <div class="attendance-kpi-card primary"><span>Dealer</span><strong>{{ number_format($kpis['dealers'], 0, ',', '.') }}</strong></div>
            <div class="attendance-kpi-card success"><span>Mekanik</span><strong>{{ number_format($kpis['mechanics'], 0, ',', '.') }}</strong></div>
            <div class="attendance-kpi-card info"><span>Hadir Data Terakhir</span><strong>{{ number_format($kpis['present_today'], 0, ',', '.') }}</strong><small>{{ $kpis['attendance_date'] ?? '-' }}</small></div>
            <div class="attendance-kpi-card warning"><span>Postcheck Periode</span><strong>{{ number_format($kpis['postchecks'], 0, ',', '.') }}</strong></div>
        </div>

        <div class="row g-4">
            <div class="col-12 col-xl-4">
                <div class="card attendance-table-card mechanic-info-card">
                    <div class="card-header"><h4 class="mb-1">Profil ATL</h4><p class="text-muted mb-0">Informasi wilayah dan identitas ATL.</p></div>
                    <div class="card-body">
                        <div class="mechanic-info-grid">
                            <div><span>Nama</span><strong>{{ $atl->nama ?? $atl->username ?? '-' }}</strong></div>
                            <div><span>NIP</span><strong>{{ $atl->nip_atl ?? '-' }}</strong></div>
                            <div><span>Wilayah</span><strong>{{ $atl->nama_wilayah ?? '-' }}</strong></div>
                            <div><span>Periode</span><strong>{{ $period }}</strong></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-xl-8">
                <div class="card attendance-table-card">
                    <div class="card-header d-flex flex-column flex-lg-row justify-content-between gap-2"><div><h4 class="mb-1">Dealer di Wilayah Ini</h4><p class="text-muted mb-0">Dealer dalam cakupan ATL, ditampilkan per halaman agar tidak terlalu panjang.</p></div><span class="attendance-count-badge">{{ $dealers->total() }} data</span></div>
                    <div class="card-body p-0">
                        <div class="table-responsive attendance-table-wrap">
                            <table class="table table-hover align-middle mb-0 attendance-table">
                                <thead><tr><th>Entitas</th><th>Kota</th><th>Status</th><th>Aksi</th></tr></thead>
                                <tbody>
                                    @forelse ($dealers as $dealer)
                                        <tr><td><div class="dealer-entity"><strong>{{ $dealer->nama_dealer ?? $dealer->dealer }}</strong><span>{{ $dealer->cabang }}</span></div></td><td>{{ $dealer->kotakab ?? '-' }}</td><td><span class="badge {{ ($dealer->status_kontrak ?? 'Aktif') === 'Aktif' ? 'bg-light-success text-success' : 'bg-light-warning text-warning' }}">{{ $dealer->status_kontrak ?? 'Aktif' }}</span></td><td><a href="{{ route('dealers.show', ['dealer' => $dealer->id, 'role' => $role]) }}" class="btn btn-sm btn-light-primary">Detail</a></td></tr>
                                    @empty
                                        <tr><td colspan="4" class="text-center text-muted py-5">Belum ada dealer.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer bg-white">@include('partials.simple-pagination', ['paginator' => $dealers])</div>
                </div>
            </div>
        </div>
    </div>
@endsection
