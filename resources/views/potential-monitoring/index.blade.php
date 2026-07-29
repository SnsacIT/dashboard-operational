@extends('layouts.dashboard')

@section('title', 'Monitoring Potensi')
@section('page-title', 'Monitoring Potensi')
@section('page-description', 'Ringkasan potensi dealer dan estimasi revenue operasional.')

@section('content')
    <form class="monitoring-filter" method="GET" action="{{ route('potentials.index') }}">
        <div class="row g-3 align-items-end">
            <div class="col-12 col-md-3">
                <label class="form-label">Tanggal Awal</label>
                <input type="date" name="start_date" value="{{ $startDate }}" class="form-control">
            </div>
            <div class="col-12 col-md-3">
                <label class="form-label">Tanggal Akhir</label>
                <input type="date" name="end_date" value="{{ $endDate }}" class="form-control">
            </div>
            <div class="col-12 col-md-4">
                <label class="form-label">Dealer</label>
                <select name="dealer" class="form-select choices">
                    <option value="">Semua Dealer</option>
                    @foreach ($dealers as $dealer)
                        <option value="{{ $dealer->id }}" @selected((string) request('dealer') === (string) $dealer->id)>
                            {{ $dealer->nama_dealer }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-md-2">
                <button class="btn btn-primary w-100">Terapkan Filter</button>
            </div>
        </div>
    </form>
    <div class="card">
        <div class="card-body">
            <table class="table table-hover align-middle base-table text-nowrap" style="width: 100%;">
                <thead class="bg-white text-center">
                    <tr>
                        <th rowspan="2" class="align-middle text-center">No</th>
                        <th rowspan="2" class="align-middle text-center">Dealer</th>
                        <th rowspan="2" class="align-middle text-center">Cabang</th>
                        <th colspan="6" class="align-middle text-center">Potensi</th>
                    </tr>
                    <tr>
                        <th class="align-middle">UE</th>
                        <th class="align-middle">UAC</th>
                        <th class="align-middle">%CR</th>
                        <th class="align-middle">RP/UE</th>
                        <th class="align-middle">RP/UAC</th>
                        <th class="align-middle">CR Rp</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Data Potensi -->
                    @forelse ($potentials as $item)
                    <tr>
                        <td class="text-center">{{ $loop->iteration }}</td>
                        <td>{{ $item->dealer }}</td>
                        <td>{{ $item->nama_dealer }}</td>
                        <td class="text-end">{{ number_format($item->unit_entry, 0, ',', '.') }}</td>
                        <td class="text-end">{{ number_format($item->unit_ac, 0, ',', '.') }}</td>
                        <td class="text-end">{{ $item->cr_percent ?? 0 }}%</td>
                        <td class="text-end"><i>belum diinput</i></td>
                        <td class="text-end">Rp {{ number_format($item->rp_uac, 0, ',', '.') }}</td>
                        <td class="text-end">Rp 0</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="text-center text-muted">Tidak ada data untuk periode ini</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
