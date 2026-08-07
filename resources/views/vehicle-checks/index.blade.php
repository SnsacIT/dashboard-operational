@extends('layouts.dashboard')

@section('title', $title ?? 'Precheck & Postcheck')
@section('page-title', $title ?? 'Precheck & Postcheck')
@section('page-description', $description ?? 'Monitoring pemeriksaan kendaraan sebelum dan sesudah servis.')

@php
    $tabs = [
        ['label' => 'Monitoring', 'route' => route('inspections.index', request()->query()), 'active' => $mode === 'monitoring'],
        ['label' => 'Menunggu Postcheck', 'route' => route('inspections.pending-postcheck', request()->query()), 'active' => $mode === 'pending'],
        ['label' => 'Perlu Verifikasi', 'route' => route('inspections.verification', request()->query()), 'active' => $mode === 'verification'],
    ];
@endphp

@section('content')
    <div class="attendance-page inspection-page">
        <div class="attendance-kpi-grid three-items">
            <div class="attendance-kpi-card primary"><span>Precheck</span><strong>{{ number_format($kpis['prechecks'], 0, ',', '.') }}</strong></div>
            <div class="attendance-kpi-card success"><span>Postcheck</span><strong>{{ number_format($kpis['postchecks'], 0, ',', '.') }}</strong></div>
            <div class="attendance-kpi-card info"><span>Rasio Selesai</span><strong>{{ number_format($kpis['ratio'], 1, ',', '.') }}%</strong></div>
        </div>

        <div class="attendance-kpi-grid three-items">
            <div class="attendance-kpi-card warning"><span>Menunggu Postcheck</span><strong>{{ number_format($kpis['pending'], 0, ',', '.') }}</strong></div>
            <div class="attendance-kpi-card success"><span>Selesai</span><strong>{{ number_format($kpis['completed'], 0, ',', '.') }}</strong></div>
            <div class="attendance-kpi-card danger"><span>Perlu Verifikasi</span><strong>{{ number_format($kpis['verification'], 0, ',', '.') }}</strong></div>
        </div>

        <div class="inspection-flow-card">
            <div class="inspection-flow-step done"><span>1</span><strong>Precheck</strong><small>Pemeriksaan awal kendaraan</small></div>
            <div class="inspection-flow-line"></div>
            <div class="inspection-flow-step {{ $kpis['pending'] > 0 ? 'warning' : 'done' }}"><span>2</span><strong>Postcheck</strong><small>{{ number_format($kpis['pending'], 0, ',', '.') }} menunggu</small></div>
            <div class="inspection-flow-line"></div>
            <div class="inspection-flow-step {{ $kpis['verification'] > 0 ? 'danger' : 'done' }}"><span>3</span><strong>Verifikasi</strong><small>{{ number_format($kpis['verification'], 0, ',', '.') }} perlu cek</small></div>
        </div>

        <div class="card attendance-filter-card">
            <div class="card-body">
                <form method="GET" action="{{ url()->current() }}">
                    <input type="hidden" name="role" value="{{ $role }}">
                    <div class="row g-3 align-items-end">
                        <div class="col-12 col-lg-3"><label class="form-label">Periode</label><input type="month" name="period" value="{{ $period }}" class="form-control"></div>
                        @if ($role === 'soh')
                            <div class="col-12 col-lg-3"><label class="form-label">ATL</label><select name="atl_id" class="form-select"><option value="">Semua ATL</option>@foreach ($atls as $atl)@continue(! is_object($atl) || ! isset($atl->urutan))<option value="{{ $atl->urutan }}" @selected((string) request('atl_id') === (string) $atl->urutan)>{{ $atl->nama ?? $atl->username ?? $atl->nip_atl }} - {{ $atl->nama_wilayah }}</option>@endforeach</select></div>
                        @endif
                        <div class="col-12 col-lg-4"><label class="form-label">Dealer</label><select name="dealer_id" class="form-select"><option value="">Semua Dealer</option>@foreach ($dealers as $dealer)@continue(! is_object($dealer) || ! isset($dealer->id))<option value="{{ $dealer->id }}" @selected((string) request('dealer_id') === (string) $dealer->id)>{{ $dealer->nama_dealer ?? trim(($dealer->dealer ?? '').' '.($dealer->cabang ?? '')) }}{{ $dealer->kotakab ? ' - '.$dealer->kotakab : '' }}</option>@endforeach</select></div>
                        <div class="col-12 col-lg-2"><button class="btn btn-primary w-100">Terapkan</button></div>
                    </div>
                </form>
            </div>
        </div>

        <div class="inspection-tabs">
            @foreach ($tabs as $tab)
                <a href="{{ $tab['route'] }}" class="{{ $tab['active'] ? 'active' : '' }}">{{ $tab['label'] }}</a>
            @endforeach
        </div>

        <div class="card attendance-table-card">
            <div class="card-header d-flex flex-column flex-lg-row justify-content-between gap-2">
                <div><h4 class="mb-1">Daftar Pemeriksaan</h4><p class="text-muted mb-0">Data mengikuti periode, ATL, dealer, dan status flow yang dipilih.</p></div>
                <span class="attendance-count-badge">{{ $checks->total() }} data</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive attendance-table-wrap">
                    <table class="table table-hover align-middle mb-0 attendance-table">
                        <thead><tr><th>Kendaraan</th><th>Dealer</th><th>Mekanik</th><th>Hasil</th><th>Status</th><th>Tanggal</th><th>Detail</th></tr></thead>
                        <tbody>
                            @forelse ($checks as $check)
                                @php
                                    $isPrecheck = ($check->source ?? '') === 'precheck';
                                    $hasPostcheckPair = ! $isPrecheck && filled($check->precheck_id ?? null);
                                    $needsVerification = ! $isPrecheck && (blank($check->hasil ?? null) || (filled($check->catatan ?? null) && ($check->catatan ?? '-') !== '-'));
                                    if ($needsVerification) {
                                        $statusText = 'Perlu Verifikasi';
                                        $statusClass = 'bg-light-danger text-danger';
                                    } elseif ($isPrecheck) {
                                        $statusText = 'Precheck';
                                        $statusClass = 'bg-light-warning text-warning';
                                    } elseif ($hasPostcheckPair) {
                                        $statusText = 'Selesai';
                                        $statusClass = 'bg-light-success text-success';
                                    } else {
                                        $statusText = 'Postcheck';
                                        $statusClass = 'bg-light-primary text-primary';
                                    }
                                    $verificationReasons = [];
                                    if (blank($check->hasil ?? null)) {
                                        $verificationReasons[] = 'Hasil postcheck belum terisi';
                                    }
                                    if (filled($check->catatan ?? null) && ($check->catatan ?? '-') !== '-') {
                                        $verificationReasons[] = 'Ada catatan tindak lanjut';
                                    }
                                    if (! $isPrecheck && blank($check->precheck_id ?? null)) {
                                        $verificationReasons[] = 'Belum terhubung dengan precheck';
                                    }
                                @endphp
                                <tr>
                                    <td><strong>{{ $check->noplat ?? '-' }}</strong><small class="d-block text-muted">{{ $check->jenismobil ?? '-' }}{{ $check->nowo ? ' / WO '.$check->nowo : '' }}</small></td>
                                    <td>{{ $check->dealer ?? '-' }}<small class="d-block text-muted">{{ $check->cabang ?? '-' }}</small></td>
                                    <td>{{ $check->teknisi ?? '-' }}<small class="d-block text-muted">{{ $check->nip ?? '-' }}</small></td>
                                    <td>{{ $check->hasil ?? '-' }}@if (! empty($check->catatan) && $check->catatan !== '-')<small class="d-block text-muted">{{ Str::limit($check->catatan, 70) }}</small>@endif</td>
                                    <td><span class="badge {{ $statusClass }}">{{ $statusText }}</span></td>
                                    <td>{{ $check->created_at ? \Carbon\Carbon::parse($check->created_at)->format('d-m-Y H:i') : '-' }}</td>
                                    <td>
                                        <button class="btn btn-sm btn-light-primary" type="button" data-bs-toggle="collapse" data-bs-target="#inspection-detail-{{ $check->source }}-{{ $check->id }}">Lihat</button>
                                    </td>
                                </tr>
                                <tr class="collapse inspection-detail-row" id="inspection-detail-{{ $check->source }}-{{ $check->id }}">
                                    <td colspan="7">
                                        <div class="inspection-detail-box">
                                            <div><span>Alasan Dicek</span><strong>{{ $verificationReasons ? implode(', ', $verificationReasons) : 'Data pemeriksaan lengkap' }}</strong></div>
                                            <div><span>Hasil Lengkap</span><p>{{ $check->hasil ?? '-' }}</p></div>
                                            <div><span>Catatan</span><p>{{ filled($check->catatan ?? null) ? $check->catatan : '-' }}</p></div>
                                            <div><span>Referensi</span><p>Precheck ID: {{ $check->precheck_id ?? '-' }}{{ $check->nowo ? ' • WO: '.$check->nowo : '' }}</p></div>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="text-center text-muted py-5">Belum ada data pemeriksaan.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer bg-white">@include('partials.simple-pagination', ['paginator' => $checks])</div>
        </div>
    </div>
@endsection
