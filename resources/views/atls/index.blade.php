@extends('layouts.dashboard')

@section('title', 'Data ATL')
@section('page-title', 'Data ATL')
@section('page-description', 'Daftar Area Technical Lead dalam cakupan SOH login beserta dealer, mekanik, presensi, dan performa bulanan.')

@php
    $atlTotal = (int) ($kpis['total'] ?? $atls->count());
    $dealerTotal = (int) ($kpis['dealers'] ?? $atls->sum('dealers'));
    $mechanicTotal = (int) ($kpis['mechanics'] ?? $atls->sum('mechanics'));
    $presenceTotal = (int) ($kpis['present_today'] ?? $atls->sum('present_today'));
@endphp

@section('content')
    <div class="row row-cols-1 row-cols-md-4 g-4 mb-4">
        <div class="col"><div class="card mb-0"><div class="card-body"><p class="kpi-title">Total ATL</p><p class="kpi-value">{{ number_format($atlTotal, 0, ',', '.') }}</p></div></div></div>
        <div class="col"><div class="card mb-0"><div class="card-body"><p class="kpi-title">Total Dealer</p><p class="kpi-value">{{ number_format($dealerTotal, 0, ',', '.') }}</p></div></div></div>
        <div class="col"><div class="card mb-0"><div class="card-body"><p class="kpi-title">Total Mekanik</p><p class="kpi-value">{{ number_format($mechanicTotal, 0, ',', '.') }}</p></div></div></div>
        <div class="col"><div class="card mb-0"><div class="card-body"><p class="kpi-title">Hadir Data Terakhir</p><p class="kpi-value">{{ number_format($presenceTotal, 0, ',', '.') }}</p><small>{{ $kpis['attendance_date'] ?? '-' }}</small></div></div></div>
    </div>

    <form class="monitoring-filter" method="GET" action="{{ route('atl-regions.index') }}">
        <div class="row g-3 align-items-end">
            <div class="col-12 col-md-4">
                <label class="form-label">Periode</label>
                <input type="month" name="period" value="{{ $period }}" class="form-control">
            </div>
            <div class="col-12 col-md-5">
                <label class="form-label">Cari ATL</label>
                <input type="search" name="search" value="{{ request('search') }}" class="form-control" placeholder="Nama ATL, NIP, atau wilayah">
            </div>
            <div class="col-12 col-md-3">
                <button class="btn btn-primary w-100">Terapkan Filter</button>
            </div>
        </div>
    </form>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <h4>Daftar ATL</h4>
                <p class="text-muted mb-0">Performa ringkas per ATL pada periode {{ $period }}.</p>
            </div>
            <a href="{{ route('atl-comparisons.index', ['period' => $period]) }}" class="btn btn-light-primary">Perbandingan ATL</a>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Nama ATL</th>
                            <th>Wilayah</th>
                            <th>Dealer</th>
                            <th>Mekanik</th>
                            <th>Presensi Terakhir</th>
                            <th>Unit Entry</th>
                            <th>Omset</th>
                            <th>Skor</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($atls as $atl)
                            <tr>
                                <td>{{ $atl->name }}<small class="d-block text-muted">NIP {{ $atl->nip_atl }}</small></td>
                                <td>{{ $atl->region }}</td>
                                <td>{{ number_format($atl->dealers, 0, ',', '.') }}</td>
                                <td>{{ number_format($atl->mechanics, 0, ',', '.') }}</td>
                                <td>{{ number_format($atl->present_today, 0, ',', '.') }}</td>
                                <td>{{ number_format($atl->unit_entry, 0, ',', '.') }}</td>
                                <td>Rp {{ number_format($atl->omset_total, 0, ',', '.') }}</td>
                                <td><span class="badge bg-light-primary text-primary">{{ $atl->score }}</span></td>
                                <td><a href="{{ route('atl-regions.show', ['atl' => $atl->urutan, 'period' => $period]) }}" class="btn btn-sm btn-light-primary">Detail</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="9" class="text-center text-muted py-4">Belum ada data ATL.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
