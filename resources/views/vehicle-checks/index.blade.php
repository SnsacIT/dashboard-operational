@extends('layouts.dashboard')

@section('title', $title ?? 'Precheck & Postcheck')
@section('page-title', $title ?? 'Precheck & Postcheck')
@section('page-description', $description ?? 'Monitoring pemeriksaan kendaraan sebelum dan sesudah servis.')

@section('content')
    <div class="row row-cols-1 row-cols-md-5 g-4 mb-4">
        <div class="col"><div class="card mb-0"><div class="card-body"><p class="kpi-title">Total Work Order</p><p class="kpi-value">{{ number_format($kpis['work_orders'], 0, ',', '.') }}</p></div></div></div>
        <div class="col"><div class="card mb-0"><div class="card-body"><p class="kpi-title">Precheck</p><p class="kpi-value">{{ number_format($kpis['prechecks'], 0, ',', '.') }}</p></div></div></div>
        <div class="col"><div class="card mb-0"><div class="card-body"><p class="kpi-title">Menunggu Postcheck</p><p class="kpi-value">{{ number_format($kpis['pending'], 0, ',', '.') }}</p></div></div></div>
        <div class="col"><div class="card mb-0"><div class="card-body"><p class="kpi-title">Selesai</p><p class="kpi-value">{{ number_format($kpis['completed'], 0, ',', '.') }}</p></div></div></div>
        <div class="col"><div class="card mb-0"><div class="card-body"><p class="kpi-title">Perlu Verifikasi</p><p class="kpi-value">{{ number_format($kpis['verification'], 0, ',', '.') }}</p></div></div></div>
    </div>

    <form class="monitoring-filter" method="GET" action="{{ url()->current() }}">
        <input type="hidden" name="role" value="{{ $role }}">
        <div class="row g-3 align-items-end">
            <div class="col-12 col-md-4"><label class="form-label">Periode</label><input type="month" name="period" value="{{ $period }}" class="form-control"></div>
            <div class="col-12 col-md-5"><label class="form-label">Dealer</label><select name="dealer_id" class="form-select"><option value="">Semua Dealer</option>@foreach ($dealers as $dealer)<option value="{{ $dealer->id }}" @selected((string) request('dealer_id') === (string) $dealer->id)>{{ $dealer->dealer }} - {{ $dealer->cabang }}</option>@endforeach</select></div>
            <div class="col-12 col-md-3"><button class="btn btn-primary w-100">Terapkan Filter</button></div>
        </div>
    </form>

    <div class="card"><div class="card-body"><div class="table-responsive"><table class="table table-hover align-middle"><thead><tr><th>Nomor WO</th><th>Kendaraan</th><th>Dealer</th><th>Mekanik</th><th>Precheck</th><th>Postcheck</th><th>Status</th><th>Tanggal</th></tr></thead><tbody>@forelse ($checks as $check)<tr><td>{{ $check->nowo ?? '-' }}</td><td>{{ $check->noplat ?? '-' }}<small class="d-block text-muted">{{ $check->jenismobil ?? '-' }}</small></td><td>{{ $check->dealer }} - {{ $check->cabang }}</td><td>{{ $check->teknisi ?? '-' }}</td><td>{{ $check->precheck ? 'Ada' : '-' }}</td><td>{{ $check->hasil ? 'Selesai' : 'Belum' }}</td><td>{{ $check->hasil ? 'Selesai' : 'Perlu Verifikasi' }}</td><td>{{ $check->created_at }}</td></tr>@empty<tr><td colspan="8" class="text-center text-muted py-4">Belum ada data pemeriksaan.</td></tr>@endforelse</tbody></table></div>{{ $checks->links() }}</div></div>
@endsection
