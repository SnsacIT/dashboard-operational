@extends('layouts.dashboard')

@section('title', 'Detail Dealer')
@section('page-title', $dealer->dealer)
@section('page-description', 'Detail dealer, mekanik, presensi, pre/postcheck, potensi, dan aktivitas.')

@section('content')
    <div class="row row-cols-1 row-cols-md-4 g-4 mb-4">
        <div class="col"><div class="card mb-0"><div class="card-body"><p class="kpi-title">Mekanik</p><p class="kpi-value">{{ number_format($kpis['mechanics'], 0, ',', '.') }}</p></div></div></div>
        <div class="col"><div class="card mb-0"><div class="card-body"><p class="kpi-title">Presensi</p><p class="kpi-value">{{ number_format($kpis['presences'], 0, ',', '.') }}</p></div></div></div>
        <div class="col"><div class="card mb-0"><div class="card-body"><p class="kpi-title">Precheck</p><p class="kpi-value">{{ number_format($kpis['prechecks'], 0, ',', '.') }}</p></div></div></div>
        <div class="col"><div class="card mb-0"><div class="card-body"><p class="kpi-title">Postcheck</p><p class="kpi-value">{{ number_format($kpis['postchecks'], 0, ',', '.') }}</p></div></div></div>
    </div>

    <div class="row">
        <div class="col-12 col-xl-4">
            <div class="card">
                <div class="card-header"><h4>Ringkasan Dealer</h4></div>
                <div class="card-body">
                    <h4>{{ $dealer->dealer }}</h4>
                    <p class="text-muted mb-2">{{ $dealer->cabang }} {{ $dealer->kotakab ? '- '.$dealer->kotakab : '' }}</p>
                    <p class="mb-1"><strong>Kode:</strong> {{ $dealer->kode ?? '-' }}</p>
                    <p class="mb-1"><strong>ATL:</strong> {{ $dealer->atl ?? 'ATL '.$dealer->no_atl }}</p>
                    <p class="mb-1"><strong>SOH:</strong> {{ $dealer->soh ?? 'SOH '.$dealer->no_soh }}</p>
                    <p class="mb-1"><strong>Alamat:</strong> {{ $dealer->alamat ?? '-' }}</p>
                    <p class="mb-0"><strong>Status:</strong> {{ $dealer->status_kontrak ?? 'Aktif' }}</p>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-8">
            <div class="card">
                <div class="card-header"><h4>Mekanik</h4></div>
                <div class="card-body"><div class="table-responsive"><table class="table table-hover align-middle"><thead><tr><th>NIP</th><th>Nama</th><th>Grade</th><th>Status</th></tr></thead><tbody>@forelse ($mechanics as $mechanic)<tr><td>{{ $mechanic->nip }}</td><td>{{ $mechanic->nama ?? $mechanic->username }}</td><td>{{ $mechanic->grade ?? '-' }}</td><td>Aktif</td></tr>@empty<tr><td colspan="4" class="text-center text-muted py-4">Belum ada mekanik.</td></tr>@endforelse</tbody></table></div></div>
            </div>

            <div class="card">
                <div class="card-header"><h4>Aktivitas Pre/Postcheck Terbaru</h4></div>
                <div class="card-body"><div class="table-responsive"><table class="table table-hover align-middle"><thead><tr><th>Waktu</th><th>Plat</th><th>Mekanik</th><th>Hasil</th></tr></thead><tbody>@forelse ($postchecks as $check)<tr><td>{{ $check->created_at }}</td><td>{{ $check->noplat ?? '-' }}</td><td>{{ $check->teknisi ?? '-' }}</td><td>{{ $check->hasil ?? '-' }}</td></tr>@empty<tr><td colspan="4" class="text-center text-muted py-4">Belum ada postcheck.</td></tr>@endforelse</tbody></table></div></div>
            </div>
        </div>
    </div>
@endsection
