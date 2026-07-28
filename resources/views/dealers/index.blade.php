@extends('layouts.dashboard')

@section('title', 'Data Dealer')
@section('page-title', 'Data Dealer')
@section('page-description', 'Dealer/cabang yang dapat diakses sesuai role dan wilayah login.')

@section('content')
    <div class="row row-cols-1 row-cols-md-4 g-4 mb-4">
        <div class="col"><div class="card mb-0"><div class="card-body"><p class="kpi-title">Total Dealer</p><p class="kpi-value">{{ number_format($kpis['total'], 0, ',', '.') }}</p></div></div></div>
        <div class="col"><div class="card mb-0"><div class="card-body"><p class="kpi-title">Dealer Aktif</p><p class="kpi-value">{{ number_format($kpis['active'], 0, ',', '.') }}</p></div></div></div>
        <div class="col"><div class="card mb-0"><div class="card-body"><p class="kpi-title">Ada Realisasi</p><p class="kpi-value">{{ number_format($kpis['with_service'], 0, ',', '.') }}</p></div></div></div>
        <div class="col"><div class="card mb-0"><div class="card-body"><p class="kpi-title">Perlu Perhatian</p><p class="kpi-value">{{ number_format($kpis['attention'], 0, ',', '.') }}</p></div></div></div>
    </div>

    <form class="monitoring-filter" method="GET" action="{{ route('dealers.index') }}">
        <input type="hidden" name="role" value="{{ $role }}">
        <div class="row g-3 align-items-end">
            <div class="col-12 col-md-3">
                <label class="form-label">Periode</label>
                <input type="month" name="period" value="{{ $period }}" class="form-control">
            </div>
            @if (($role ?? 'atl') === 'soh')
                <div class="col-12 col-md-3">
                    <label class="form-label">ATL</label>
                    <select name="atl_id" class="form-select">
                        <option value="">Semua ATL</option>
                        @foreach ($atls as $atl)
                            <option value="{{ $atl->urutan }}" @selected((string) request('atl_id') === (string) $atl->urutan)>{{ $atl->nama ?? $atl->username ?? $atl->nip_atl }} - {{ $atl->nama_wilayah }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            <div class="col-12 col-md-2">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="">Semua Status</option>
                    <option value="Aktif" @selected(request('status') === 'Aktif')>Aktif</option>
                    <option value="Tidak Aktif" @selected(request('status') === 'Tidak Aktif')>Tidak Aktif</option>
                </select>
            </div>
            <div class="col-12 col-md-3">
                <label class="form-label">Cari Dealer</label>
                <input type="search" name="search" value="{{ request('search') }}" class="form-control" placeholder="Dealer, cabang, kota, kode">
            </div>
            <div class="col-12 col-md-1">
                <button class="btn btn-primary w-100"><i class="bi bi-search"></i></button>
            </div>
        </div>
    </form>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Kode Dealer</th>
                            <th>Nama Dealer</th>
                            <th>ATL</th>
                            <th>Kota</th>
                            <th>Mekanik</th>
                            <th>Target</th>
                            <th>Realisasi</th>
                            <th>Pencapaian</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($dealers as $dealer)
                            @php
                                $office = $officeDealerPerformance[$dealer->id] ?? null;
                                $target = 100;
                                $realization = (int) ($office->unit_total ?? ($serviceCounts[$dealer->id] ?? 0));
                                $achievement = min(100, round(($realization / $target) * 100));
                            @endphp
                            <tr>
                                <td>{{ $dealer->kode ?? '-' }}</td>
                                <td>{{ $dealer->dealer }}<small class="d-block text-muted">{{ $dealer->cabang }}</small></td>
                                <td>{{ $dealer->atl ?? 'ATL '.$dealer->no_atl }}</td>
                                <td>{{ $dealer->kotakab ?? '-' }}</td>
                                <td>{{ $mechanicCounts[$dealer->dealer.'|'.$dealer->cabang] ?? 0 }}</td>
                                <td>{{ $target }}</td>
                                <td>
                                    {{ number_format($realization, 0, ',', '.') }}
                                    <small class="d-block text-muted">Rp {{ number_format((float) ($office->omset_total ?? 0), 0, ',', '.') }}</small>
                                </td>
                                <td>
                                    <div class="progress progress-primary" style="height: 7px;"><div class="progress-bar" style="width: {{ $achievement }}%"></div></div>
                                    <small>{{ $achievement }}%</small>
                                </td>
                                <td><span class="badge bg-light-success text-success">{{ $dealer->status_kontrak ?? 'Aktif' }}</span></td>
                                <td><a href="{{ route('dealers.show', ['dealer' => $dealer->id, 'role' => $role]) }}" class="btn btn-sm btn-light-primary">Detail</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="10" class="text-center text-muted py-4">Belum ada data dealer.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $dealers->links() }}
        </div>
    </div>
@endsection
