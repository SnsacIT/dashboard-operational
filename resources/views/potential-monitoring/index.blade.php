@extends('layouts.dashboard')

@section('title', 'Monitoring Potensi')
@section('page-title', 'Monitoring Potensi')
@section('page-description', 'Ringkasan potensi dealer dan estimasi revenue operasional.')

@section('content')
    <div class="row row-cols-1 row-cols-md-4 g-4 mb-4">
        <div class="col"><div class="card mb-0"><div class="card-body"><p class="kpi-title">Unit AC Office</p><p class="kpi-value">{{ number_format((float) ($postcheckRatio->unit_ac ?? 0), 0, ',', '.') }}</p></div></div></div>
        <div class="col"><div class="card mb-0"><div class="card-body"><p class="kpi-title">Postcheck Office</p><p class="kpi-value">{{ number_format((float) ($postcheckRatio->postchecks ?? 0), 0, ',', '.') }}</p></div></div></div>
        <div class="col"><div class="card mb-0"><div class="card-body"><p class="kpi-title">Terverifikasi</p><p class="kpi-value">{{ number_format((float) ($postcheckRatio->verified ?? 0), 0, ',', '.') }}</p></div></div></div>
        <div class="col"><div class="card mb-0"><div class="card-body"><p class="kpi-title">Rasio</p><p class="kpi-value">{{ number_format((float) ($postcheckRatio->ratio ?? 0), 1, ',', '.') }}%</p></div></div></div>
    </div>
    <form class="monitoring-filter" method="GET" action="{{ route('potentials.index') }}"><div class="row g-3 align-items-end"><div class="col-12 col-md-4"><label class="form-label">Periode</label><input type="month" name="period" value="{{ request('period') }}" class="form-control"></div><div class="col-12 col-md-4"><label class="form-label">Dealer</label><select name="dealer_id" class="form-select"><option value="">Semua Dealer</option>@foreach ($dealers as $dealer)<option value="{{ $dealer->id }}" @selected((string) request('dealer_id') === (string) $dealer->id)>{{ $dealer->nama_dealer ?? trim(($dealer->dealer ?? '').' '.($dealer->cabang ?? '')) }}{{ $dealer->kotakab ? ' - '.$dealer->kotakab : '' }}</option>@endforeach</select></div><div class="col-12 col-md-3"><button class="btn btn-primary w-100">Terapkan Filter</button></div></div></form>
    <div class="card"><div class="card-body"><div class="table-responsive"><table class="table table-hover align-middle"><thead><tr><th>Dealer</th><th>Cabang</th><th>Total Service</th><th>Total Unit</th><th>Status</th></tr></thead><tbody>@forelse ($potentials as $potential)<tr><td>{{ $potential->dealer }}</td><td>{{ $potential->cabang }}</td><td>{{ $potential->service_count }}</td><td>{{ $potential->unit_count }}</td><td>Potensial</td></tr>@empty<tr><td colspan="5" class="text-center text-muted py-4">Belum ada data potensi.</td></tr>@endforelse</tbody></table></div>{{ $potentials->links() }}</div></div>
@endsection
