@extends('layouts.dashboard')

@section('title', 'Data Dealer')
@section('page-title', 'Data Dealer')
@section('page-description', 'Dealer/cabang yang dapat diakses sesuai role dan wilayah login.')

@section('content')
    <div class="attendance-page">
        <div class="attendance-kpi-grid three-items">
            <div class="attendance-kpi-card primary"><span>Total Dealer</span><strong>{{ number_format($kpis['total'], 0, ',', '.') }}</strong></div>
            <div class="attendance-kpi-card success"><span>Dealer Aktif</span><strong>{{ number_format($kpis['active'], 0, ',', '.') }}</strong></div>
            <div class="attendance-kpi-card warning"><span>Perlu Perhatian</span><strong>{{ number_format($kpis['attention'], 0, ',', '.') }}</strong></div>
        </div>

        <div class="card attendance-filter-card">
            <div class="card-body">
                <form method="GET" action="{{ route('dealers.index') }}">
                    <input type="hidden" name="role" value="{{ $role }}">
                    <div class="row g-3 align-items-end">
                        <div class="col-12 col-lg-2"><label class="form-label">Periode</label><input type="month" name="period" value="{{ $period }}" class="form-control"></div>
                        @if (($role ?? 'atl') === 'soh')
                            <div class="col-12 col-lg-3"><label class="form-label">ATL</label><select name="atl_id" class="form-select"><option value="">Semua ATL</option>@foreach ($atls as $atl)<option value="{{ $atl->urutan }}" @selected((string) request('atl_id') === (string) $atl->urutan)>{{ $atl->nama ?? $atl->username ?? $atl->nip_atl }} - {{ $atl->nama_wilayah }}</option>@endforeach</select></div>
                        @endif
                        <div class="col-12 col-lg-2"><label class="form-label">Status</label><select name="status" class="form-select"><option value="">Semua</option><option value="Aktif" @selected(request('status') === 'Aktif')>Aktif</option><option value="Tidak Aktif" @selected(request('status') === 'Tidak Aktif')>Tidak Aktif</option></select></div>
                        <div class="col-12 col-lg-3"><label class="form-label">Cari Dealer</label><input type="search" name="search" value="{{ request('search') }}" class="form-control" placeholder="Dealer, cabang, kota, kode"></div>
                        <div class="col-12 col-lg-2"><button class="btn btn-primary w-100"><i class="bi bi-search me-1"></i>Cari</button></div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card attendance-table-card">
            <div class="card-header d-flex flex-column flex-lg-row justify-content-between gap-2"><div><h4 class="mb-1">Daftar Dealer</h4><p class="text-muted mb-0">Dealer aktif dan ringkasan operasional.</p></div><span class="attendance-count-badge">{{ $dealers->total() }} data</span></div>
            <div class="card-body p-0"><div class="table-responsive attendance-table-wrap"><table class="table table-hover align-middle mb-0 attendance-table"><thead><tr><th>Kode</th><th>Entitas</th><th>ATL</th><th>Kota</th><th>Mekanik</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
                @forelse ($dealers as $dealer)
                    <tr><td>{{ $dealer->kode ?? '-' }}</td><td><div class="dealer-entity"><strong>{{ $dealer->nama_dealer ?? $dealer->dealer }}</strong><span>{{ $dealer->cabang ?? '-' }}</span></div></td><td>ATL {{ $dealer->no_atl ?? '-' }}</td><td>{{ $dealer->kotakab ?? '-' }}</td><td>{{ $mechanicCounts[$dealer->dealer.'|'.$dealer->cabang] ?? 0 }}</td><td><span class="badge bg-light-success text-success">{{ $dealer->status_kontrak ?? 'Aktif' }}</span></td><td><a href="{{ route('dealers.show', ['dealer' => $dealer->id, 'role' => $role]) }}" class="btn btn-sm btn-light-primary">Detail</a></td></tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-5">Belum ada data dealer.</td></tr>
                @endforelse
            </tbody></table></div></div><div class="card-footer bg-white">@include('partials.simple-pagination', ['paginator' => $dealers])</div>
        </div>
    </div>
@endsection
