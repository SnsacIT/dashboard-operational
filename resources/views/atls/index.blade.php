@extends('layouts.dashboard')

@section('title', 'Data ATL')
@section('page-title', 'Data ATL')
@section('page-description', 'Daftar Area Technical Lead beserta dealer, mekanik, dan presensi terakhir.')

@php
    $atlTotal = (int) ($kpis['total'] ?? $atls->count());
    $dealerTotal = (int) ($kpis['dealers'] ?? $atls->sum('dealers'));
    $mechanicTotal = (int) ($kpis['mechanics'] ?? $atls->sum('mechanics'));
    $presenceTotal = (int) ($kpis['present_today'] ?? $atls->sum('present_today'));
@endphp

@section('content')
    <div class="attendance-page">
        <div class="attendance-kpi-grid four-items">
            <div class="attendance-kpi-card primary"><span>Total ATL</span><strong>{{ number_format($atlTotal, 0, ',', '.') }}</strong></div>
            <div class="attendance-kpi-card success"><span>Total Dealer</span><strong>{{ number_format($dealerTotal, 0, ',', '.') }}</strong></div>
            <div class="attendance-kpi-card info"><span>Total Mekanik</span><strong>{{ number_format($mechanicTotal, 0, ',', '.') }}</strong></div>
            <div class="attendance-kpi-card warning"><span>Presensi Terakhir</span><strong>{{ number_format($presenceTotal, 0, ',', '.') }}</strong><small>{{ $kpis['attendance_date'] ?? '-' }}</small></div>
        </div>

        <div class="card attendance-filter-card"><div class="card-body"><form method="GET" action="{{ route('atl-regions.index') }}"><div class="row g-3 align-items-end"><div class="col-12 col-lg-2"><label class="form-label">Periode</label><input type="month" name="period" value="{{ $period }}" class="form-control"></div><div class="col-12 col-lg-3"><label class="form-label">SOH</label><select name="soh_id" class="form-select"><option value="">Semua SOH</option>@foreach ($sohs as $soh)<option value="{{ $soh->id }}" @selected((string) request('soh_id') === (string) $soh->id)>{{ $soh->name }}</option>@endforeach</select></div><div class="col-12 col-lg-5"><label class="form-label">Cari ATL</label><input type="search" name="search" value="{{ request('search') }}" class="form-control" placeholder="Nama ATL, NIP, atau wilayah"></div><div class="col-12 col-lg-2"><button class="btn btn-primary w-100"><i class="bi bi-search me-1"></i>Cari</button></div></div></form></div></div>

        <div class="card attendance-table-card">
            <div class="card-header d-flex flex-column flex-lg-row justify-content-between gap-2"><div><h4 class="mb-1">Daftar ATL</h4><p class="text-muted mb-0">Performa ringkas per ATL pada periode {{ $period }}.</p></div><a href="{{ route('atl-comparisons.index', ['period' => $period, 'soh_id' => request('soh_id')]) }}" class="btn btn-light-primary">Perbandingan ATL</a></div>
            <div class="card-body p-0"><div class="table-responsive attendance-table-wrap"><table class="table table-hover align-middle mb-0 attendance-table"><thead><tr><th>ATL</th><th>Wilayah</th><th>Dealer</th><th>Mekanik</th><th>Presensi</th><th>Aksi</th></tr></thead><tbody>
                @forelse ($atls as $atl)
                    <tr><td><strong>{{ $atl->name }}</strong><small class="d-block text-muted">NIP {{ $atl->nip_atl }}</small></td><td>{{ $atl->region }}</td><td>{{ number_format($atl->dealers, 0, ',', '.') }}</td><td>{{ number_format($atl->mechanics, 0, ',', '.') }}</td><td>{{ number_format($atl->present_today, 0, ',', '.') }}</td><td><a href="{{ route('atl-regions.show', ['atl' => $atl->urutan, 'period' => $period]) }}" class="btn btn-sm btn-light-primary">Detail</a></td></tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-5">Belum ada data ATL.</td></tr>
                @endforelse
            </tbody></table></div></div>
        </div>
    </div>
@endsection
