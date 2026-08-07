@extends('layouts.dashboard')

@section('title', 'Data Mekanik')
@section('page-title', 'Data Mekanik')
@section('page-description', 'Data mekanik aktif dengan format tabel pegawai operasional.')

@section('content')
    <div class="attendance-page employee-table-page">
        <div class="attendance-kpi-grid four-items">
            <div class="attendance-kpi-card primary"><span>Total Mekanik</span><strong>{{ number_format($kpis['total'], 0, ',', '.') }}</strong></div>
            <div class="attendance-kpi-card success"><span>Hadir Data Terakhir</span><strong>{{ number_format($kpis['present_today'], 0, ',', '.') }}</strong></div>
            <div class="attendance-kpi-card danger"><span>Terlambat</span><strong>{{ number_format($kpis['late_today'], 0, ',', '.') }}</strong></div>
            <div class="attendance-kpi-card info"><span>Tanggal Presensi</span><strong class="compact-date">{{ $kpis['latest_date'] ?? '-' }}</strong></div>
        </div>

        <div class="card attendance-filter-card employee-filter-card">
            <div class="card-body">
                <form method="GET" action="{{ route('mechanics.index') }}">
                    <input type="hidden" name="role" value="{{ $role }}">
                    <div class="row g-3 align-items-end">
                        <div class="col-12 col-lg-2"><label class="form-label">Keterangan Status</label><select name="status" class="form-select"><option value="">Semua</option><option value="active" @selected(request('status') === 'active')>Active</option><option value="resigned" @selected(request('status') === 'resigned')>Resigned</option></select></div>
                        <div class="col-12 col-lg-2"><label class="form-label">Company</label><select name="company" class="form-select"><option value="">Semua</option>@foreach ($companies as $company)<option value="{{ $company }}" @selected(request('company') === $company)>{{ $company }}</option>@endforeach</select></div>
                        <div class="col-12 col-lg-3"><label class="form-label">Dealer</label><select name="dealer_id" class="form-select"><option value="">Semua Dealer</option>@foreach ($dealers as $dealer)<option value="{{ $dealer->id }}" @selected((string) request('dealer_id') === (string) $dealer->id)>{{ $dealer->nama_dealer ?? trim(($dealer->dealer ?? '').' '.($dealer->cabang ?? '')) }}{{ $dealer->kotakab ? ' - '.$dealer->kotakab : '' }}</option>@endforeach</select></div>
                        <div class="col-12 col-lg-3"><label class="form-label">Cari Nama Dealer...</label><input type="search" name="search" value="{{ request('search') }}" class="form-control" placeholder="NIP, nama, dealer, cabang"></div>
                        <div class="col-12 col-lg-2"><button class="btn btn-primary w-100">Cari</button></div>
                    </div>
                    <div class="employee-table-toolbar mt-3">
                        <label>Show <select name="per_page" class="form-select form-select-sm" onchange="this.form.submit()"><option value="10" @selected($perPage === 10)>10</option><option value="25" @selected($perPage === 25)>25</option><option value="50" @selected($perPage === 50)>50</option><option value="100" @selected($perPage === 100)>100</option></select> entries</label>
                    </div>
                </form>
            </div>
        </div>

        <div class="card attendance-table-card employee-data-card">
            <div class="card-header d-flex flex-column flex-lg-row justify-content-between gap-2"><div><h4 class="mb-1">Data Pegawai Mekanik</h4><p class="text-muted mb-0">Format tabel pegawai dengan data mekanik dari tabel users.</p></div><span class="attendance-count-badge">{{ $mechanics->total() }} data</span></div>
            <div class="card-body p-0">
                <div class="table-responsive employee-table-wrap">
                    <table class="table table-hover align-middle mb-0 employee-table">
                        <thead><tr><th>No</th><th>Aksi</th><th>NIP</th><th>Nama Karyawan</th><th>Company</th><th>Agama</th><th>Grade</th><th>Status Karyawan</th><th>Jenis Karyawan</th><th>Jenis Kelamin</th><th>Jabatan</th><th>Area/Dealer</th><th>Divisi / Departemen</th><th>Lokasi Kerja</th><th>Status Pernikahan</th><th>Tempat Lahir</th><th>Tanggal Lahir</th><th>Kota KTP</th><th>Provinsi KTP</th><th>No Telepon</th><th>Email</th><th>Pendidikan Terakhir</th><th>Usia</th><th>Bank</th><th>Nomor Rekening</th></tr></thead>
                        <tbody>
                            @forelse ($mechanics as $mechanic)
                                @php
                                    $rowNumber = ($mechanics->currentPage() - 1) * $mechanics->perPage() + $loop->iteration;
                                    $statusEmployee = $mechanic->resign_date && $mechanic->resign_date <= now('Asia/Jakarta')->toDateString() ? 'Resigned' : 'Active';
                                    $employeeType = $mechanic->status ?: ($mechanic->grade ? 'Kontrak' : 'On Job Training');
                                @endphp
                                <tr>
                                    <td>{{ $rowNumber }}</td>
                                    <td><a href="{{ route('mechanics.show', ['mechanic' => $mechanic->id, 'role' => $role]) }}" class="btn btn-sm btn-light-primary">Detail</a></td>
                                    <td><strong>{{ $mechanic->nip }}</strong></td>
                                    <td>{{ $mechanic->nama ?? $mechanic->username ?? '-' }}</td>
                                    <td>{{ $mechanic->company ?? '-' }}</td>
                                    <td>{{ $mechanic->agama ?? '-' }}</td>
                                    <td>{{ $mechanic->grade ?? '-' }}</td>
                                    <td><span class="badge {{ $statusEmployee === 'Active' ? 'bg-light-success text-success' : 'bg-light-danger text-danger' }}">{{ $statusEmployee }}</span></td>
                                    <td>{{ $employeeType }}</td>
                                    <td>{{ $mechanic->jk ?? '-' }}</td>
                                    <td>{{ $mechanic->posisi ?? 'Mekanik' }}</td>
                                    <td>{{ $mechanic->dealer ?? '-' }}</td>
                                    <td>{{ $mechanic->department ?? 'Mekanik' }}</td>
                                    <td>{{ $mechanic->cabang ?? '-' }}</td>
                                    <td>{{ $mechanic->status ?? '-' }}</td>
                                    <td>{{ $mechanic->tempat_lahir ?? '-' }}</td>
                                    <td>{{ $mechanic->tanggal_lahir ?? '-' }}</td>
                                    <td>{{ $mechanic->city_ktp ?? '-' }}</td>
                                    <td>{{ $mechanic->province_ktp ?? '-' }}</td>
                                    <td>{{ $mechanic->kontak ?? '-' }}</td>
                                    <td>{{ $mechanic->email ?? '-' }}</td>
                                    <td>{{ $mechanic->pendidikan ?? '-' }}</td>
                                    <td>{{ $mechanic->usia ?? '-' }}</td>
                                    <td>{{ $mechanic->bank ?? '-' }}</td>
                                    <td>{{ $mechanic->nomor_rekening ?? '-' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="25" class="text-center text-muted py-5">Belum ada data mekanik.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer bg-white">@include('partials.simple-pagination', ['paginator' => $mechanics])</div>
        </div>
    </div>
@endsection
