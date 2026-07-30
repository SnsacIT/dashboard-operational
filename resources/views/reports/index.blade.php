@extends('layouts.dashboard')

@section('title', 'Laporan')
@section('page-title', 'Laporan Operasional')
@section('page-description', 'Preview laporan dealer, presensi, pemeriksaan, dan potensi berdasarkan periode.')

@section('content')
    <div class="attendance-page">
        <div class="attendance-kpi-grid three-items">
            <div class="attendance-kpi-card primary"><span>Dealer</span><strong>{{ number_format($kpis['dealers'], 0, ',', '.') }}</strong></div>
            <div class="attendance-kpi-card success"><span>Mekanik</span><strong>{{ number_format($kpis['mechanics'], 0, ',', '.') }}</strong></div>
            <div class="attendance-kpi-card info"><span>Presensi Terakhir</span><strong>{{ number_format($kpis['attendance'], 0, ',', '.') }}</strong></div>
        </div>
        <div class="attendance-kpi-grid three-items">
            <div class="attendance-kpi-card warning"><span>Precheck</span><strong>{{ number_format($kpis['prechecks'], 0, ',', '.') }}</strong></div>
            <div class="attendance-kpi-card success"><span>Postcheck</span><strong>{{ number_format($kpis['postchecks'], 0, ',', '.') }}</strong></div>
            <div class="attendance-kpi-card danger"><span>Rasio Check</span><strong>{{ number_format($kpis['ratio'], 1, ',', '.') }}%</strong></div>
        </div>

        <div class="card attendance-filter-card"><div class="card-body"><form method="GET" action="{{ route('reports.index') }}"><input type="hidden" name="role" value="{{ $role }}"><div class="row g-3 align-items-end"><div class="col-12 col-lg-3"><label class="form-label">Periode</label><input type="month" name="period" value="{{ $period }}" class="form-control"></div>@if ($role === 'soh')<div class="col-12 col-lg-3"><label class="form-label">ATL</label><select name="atl_id" class="form-select"><option value="">Semua ATL</option>@foreach ($atls as $atl)<option value="{{ $atl->urutan }}" @selected((string) request('atl_id') === (string) $atl->urutan)>{{ $atl->nama ?? $atl->username ?? $atl->nip_atl }} - {{ $atl->nama_wilayah }}</option>@endforeach</select></div>@endif<div class="col-12 col-lg-4"><label class="form-label">Dealer</label><select name="dealer_id" class="form-select"><option value="">Semua Dealer</option>@foreach ($dealers as $dealer)<option value="{{ $dealer->id }}" @selected((string) request('dealer_id') === (string) $dealer->id)>{{ $dealer->nama_dealer ?? trim(($dealer->dealer ?? '').' '.($dealer->cabang ?? '')) }}{{ $dealer->kotakab ? ' - '.$dealer->kotakab : '' }}</option>@endforeach</select></div><div class="col-12 col-lg-2"><button class="btn btn-primary w-100">Terapkan</button></div></div></form></div></div>

        <div class="row g-4 mb-4">
            @foreach ($reports as $report)
                <div class="col-12 col-lg-3"><div class="report-action-card"><span>{{ $report->status }}</span><h5>{{ $report->name }}</h5><p>{{ $report->description }}</p><a href="{{ $report->route }}" class="btn btn-sm btn-light-primary">Preview</a></div></div>
            @endforeach
        </div>

        <div class="card attendance-table-card">
            <div class="card-header"><h4 class="mb-1">Preview Performa Dealer</h4><p class="text-muted mb-0">Top dealer berdasarkan jumlah postcheck pada periode {{ $period }}.</p></div>
            <div class="card-body p-0"><div class="table-responsive attendance-table-wrap"><table class="table table-hover align-middle mb-0 attendance-table"><thead><tr><th>Dealer</th><th>Cabang</th><th>Unit AC</th><th>Postcheck</th><th>Rasio</th></tr></thead><tbody>
                @forelse ($dealerPerformance as $dealer)
                    @php $ratio = (int) ($dealer->unit_ac ?? 0) > 0 ? round(((int) $dealer->postchecks / (int) $dealer->unit_ac) * 100, 1) : 0; @endphp
                    <tr><td><strong>{{ $dealer->dealer }}</strong></td><td>{{ $dealer->cabang }}</td><td>{{ number_format($dealer->unit_ac, 0, ',', '.') }}</td><td>{{ number_format($dealer->postchecks, 0, ',', '.') }}</td><td><span class="badge bg-light-primary text-primary">{{ number_format($ratio, 1, ',', '.') }}%</span></td></tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-5">Belum ada data laporan.</td></tr>
                @endforelse
            </tbody></table></div></div>
        </div>
    </div>
@endsection
