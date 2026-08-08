@extends('layouts.dashboard')

@section('title', 'Monitoring Potensi')
@section('page-title', 'Monitoring Potensi')
@section('page-description', 'Ringkasan potensi dealer dan estimasi revenue operasional.')

@section('styles')
    <style>
        /* Menghilangkan border hitam/outline saat tab diklik */
        .nav-pills .nav-link:focus,
        .nav-pills .nav-link:active,
        .nav-link:focus,
        .nav-link:active,
        button:focus {
            box-shadow: none !important;
            outline: none !important;
            border-color: transparent !important;
        }

        /* Kembalikan border primary jika sedang aktif tanpa ada outline hitam */
        .nav-pills .nav-link.active.border-primary {
            border-color: #0d6efd !important;
            /* warna default primary */
        }
    </style>
@endsection

@section('content')
    <div class="card mb-4">
        <div class="card-header border-bottom">
            {{-- <h5 class="card-title mb-0">Total Pareto</h5> --}}
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
                        <select name="dealer[]" class="form-select choices multiple-remove" multiple="multiple">
                            <option value="">Semua Dealer</option>
                            @foreach ($dealers as $dealer)
                                <option value="{{ $dealer->id }}" @selected(in_array((string) $dealer->id, (array) request('dealer', [])))>
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
        </div>
        <div class="card-body bg-light py-4">
            <div class="row g-3">
                <!-- UE -->
                <div class="col-6 col-md-3">
                    <div class="card bg-white shadow-sm border mb-0">
                        <div class="card-body px-4 py-4-5">
                            <div class="row">
                                <div class="col-md-4 col-lg-12 col-xl-12 col-xxl-4 d-flex justify-content-start">
                                    <div class="stats-icon blue mb-2">
                                        <i class="iconly-boldProfile"></i>
                                    </div>
                                </div>
                                <div class="col-md-8 col-lg-12 col-xl-12 col-xxl-8">
                                    <h6 class="text-muted font-semibold">UE</h6>
                                    <h6 class="font-extrabold mb-0">{{ number_format($pareto80Ue, 0, ',', '.') }}</h6>
                                </div>
                            </div>
                            <button class="btn btn-sm btn-outline-primary w-100 mt-3 btn-detail-pareto" data-type="UE"
                                data-title="Detail Unit Entry (UE)">Lihat Detail</button>
                        </div>
                    </div>
                </div>
                <!-- UAC -->
                <div class="col-6 col-md-3">
                    <div class="card bg-white shadow-sm border mb-0">
                        <div class="card-body px-4 py-4-5">
                            <div class="row">
                                <div class="col-md-4 col-lg-12 col-xl-12 col-xxl-4 d-flex justify-content-start">
                                    <div class="stats-icon green mb-2">
                                        <i class="iconly-boldAdd-User"></i>
                                    </div>
                                </div>
                                <div class="col-md-8 col-lg-12 col-xl-12 col-xxl-8">
                                    <h6 class="text-muted font-semibold">UAC</h6>
                                    <h6 class="font-extrabold mb-0">{{ number_format($pareto80Uac, 0, ',', '.') }}</h6>
                                </div>
                            </div>
                            <button class="btn btn-sm btn-outline-success w-100 mt-3 btn-detail-pareto" data-type="UAC"
                                data-title="Detail Unit AC (UAC)">Lihat Detail</button>
                        </div>
                    </div>
                </div>
                <!-- RP/UE -->
                <div class="col-6 col-md-3">
                    <div class="card bg-white shadow-sm border mb-0">
                        <div class="card-body px-4 py-4-5">
                            <div class="row">
                                <div class="col-md-4 col-lg-12 col-xl-12 col-xxl-4 d-flex justify-content-start">
                                    <div class="stats-icon purple mb-2">
                                        <i class="iconly-boldWallet"></i>
                                    </div>
                                </div>
                                <div class="col-md-8 col-lg-12 col-xl-12 col-xxl-8">
                                    <h6 class="text-muted font-semibold">RP/UE</h6>
                                    <h6 class="font-extrabold mb-0">Rp {{ number_format($totalRpue, 0, ',', '.') }}</h6>
                                </div>
                            </div>
                            <button class="btn btn-sm btn-outline-info w-100 mt-3 btn-detail-pareto" data-type="RP/UE"
                                data-title="Detail RP/UE">Lihat Detail</button>
                        </div>
                    </div>
                </div>
                <!-- RP/UAC -->
                <div class="col-6 col-md-3">
                    <div class="card bg-white shadow-sm border mb-0">
                        <div class="card-body px-4 py-4-5">
                            <div class="row">
                                <div class="col-md-4 col-lg-12 col-xl-12 col-xxl-4 d-flex justify-content-start">
                                    <div class="stats-icon red mb-2">
                                        <i class="iconly-boldTicket"></i>
                                    </div>
                                </div>
                                <div class="col-md-8 col-lg-12 col-xl-12 col-xxl-8">
                                    <h6 class="text-muted font-semibold">RP/UAC</h6>
                                    <h6 class="font-extrabold mb-0">Rp {{ number_format($totalRpuac, 0, ',', '.') }}</h6>
                                </div>
                            </div>
                            <button class="btn btn-sm btn-outline-danger w-100 mt-3 btn-detail-pareto" data-type="RP/UAC"
                                data-title="Detail RP/UAC">Lihat Detail</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="card">
        <div class="card-body">
            <div class="d-flex flex-row justify-content-between mb-3">
                <ul class="nav nav-pills" id="potensiTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active border border-primary rounded-pill" id="data-potensi-tab"
                            data-bs-toggle="tab" data-bs-target="#data-potensi" type="button" role="tab"
                            aria-controls="data-potensi" aria-selected="true">Data Mekanik</button>
                    </li>
                    <li class="nav-item mx-2" role="presentation">
                        <button class="nav-link border border-primary rounded-pill" id="data-unit-entry-tab"
                            data-bs-toggle="tab" data-bs-target="#data-unit-entry" type="button" role="tab"
                            aria-controls="data-unit-entry" aria-selected="false">Data ATL</button>
                    </li>
                </ul>
                <div class="d-flex align-items-end gap-2 align-self-end mt-1">
                    @if (Auth::user()->role == '1')
                        <a href="{{ route('potentials.input-unit-entry') }}" class="btn btn-primary btn-sm">
                            <i class="iconly-boldEdit me-1"></i> Input Unit Entry
                        </a>
                    @endif
                    <button type="button" class="btn btn-success btn-sm" onclick="exportActiveTable()">
                        <i class="iconly-boldDocument me-1"></i> Export Excel
                    </button>
                </div>

            </div>

            <div class="tab-content" id="potensiTabsContent">
                <!-- Tab Data Potensi -->
                <div class="tab-pane fade show active" id="data-potensi" role="tabpanel"
                    aria-labelledby="data-potensi-tab">
                    <table class="table table-hover align-middle base-table text-nowrap" style="width: 100%;">
                        <thead class="bg-white text-center">
                            <tr>
                                <th rowspan="2" class="align-middle text-center">No</th>
                                <th rowspan="2" class="align-middle text-center">Dealer</th>
                                <th rowspan="2" class="align-middle text-center">Cabang</th>
                                <th rowspan="2" class="align-middle text-center">Bulan dan Tahun</th>
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
                            @foreach ($potentials as $item)
                                <tr>
                                    <td class="text-center">{{ $loop->iteration }}</td>
                                    <td>{{ $item->dealer }}</td>
                                    <td>{{ $item->nama_dealer }}</td>
                                    <td class="text-center">
                                        {{ $item->period ? \Carbon\Carbon::parse($item->period)->locale('id')->translatedFormat('F Y') : '-' }}
                                    </td>
                                    <td class="text-end">{{ number_format($item->unit_entry, 0, ',', '.') }}</td>
                                    <td class="text-end">{{ number_format($item->unit_ac, 0, ',', '.') }}</td>
                                    <td class="text-end">{{ $item->cr_percent ?? 0 }}%</td>
                                    <td class="text-end">Rp {{ number_format($item->rp_unit_entry, 0, ',', '.') }}</td>
                                    <td class="text-end">Rp {{ number_format($item->rp_uac, 0, ',', '.') }}</td>
                                    <td class="text-end">
                                        {{ $item->rp_unit_entry > 0 ? round(($item->rp_uac / $item->rp_unit_entry) * 100, 2) : 0 }}%
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Tab Data Unit Entry (dari input manual) -->
                <div class="tab-pane fade" id="data-unit-entry" role="tabpanel" aria-labelledby="data-unit-entry-tab">
                    <table class="table table-hover align-middle base-table text-nowrap" style="width: 100%;">
                        <thead class="bg-white text-center">
                            <tr>
                                <th rowspan="2" class="align-middle text-center">No</th>
                                <th rowspan="2" class="align-middle text-center">Dealer</th>
                                <th rowspan="2" class="align-middle text-center">Cabang</th>
                                <th rowspan="2" class="align-middle text-center">Bulan dan Tahun</th>
                                <th rowspan="2" class="align-middle text-center">Periode UE</th>
                                <th colspan="6" class="align-middle text-center">Data ATL</th>
                            </tr>
                            <tr>
                                <th class="align-middle text-center">UE</th>
                                <th class="align-middle text-center">UAC</th>
                                <th class="align-middle text-center">%CR</th>
                                <th class="align-middle text-center">RP/UE</th>
                                <th class="align-middle text-center">RP/UAC</th>
                                <th class="align-middle text-center">CR Rp</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($potentials as $item)
                                @php
                                    $atlItem = $potentialsUnitEntry[$loop->index] ?? null;
                                    $atlUe = $atlItem ? $atlItem->unit_entry : 0;
                                    $atlRpUe = $atlItem ? $atlItem->rp_unit_entry : 0;
                                    $atlCrPercent = $atlUe > 0 ? ($item->unit_ac / $atlUe) * 100 : 0;
                                    $atlCrRp = $atlRpUe > 0 ? ($item->rp_uac / $atlRpUe) * 100 : 0;
                                @endphp
                                <tr>
                                    <td class="text-center">{{ $loop->iteration }}</td>
                                    <td>{{ $item->dealer }}</td>
                                    <td>{{ $item->nama_dealer }}</td>
                                    <td class="text-center">
                                        {{ \Carbon\Carbon::parse($startDate)->locale('id')->translatedFormat('F Y') }}
                                    </td>
                                    <td class="text-center text-primary fw-semibold">
                                        {{ $atlItem && $atlItem->period ? \Carbon\Carbon::parse($atlItem->period)->locale('id')->translatedFormat('F Y') : '-' }}
                                    </td>
                                    <td class="text-end">{{ number_format($atlUe, 0, ',', '.') }}</td>
                                    <td class="text-end">{{ number_format($item->unit_ac, 0, ',', '.') }}</td>
                                    <td class="text-end">{{ round($atlCrPercent, 2) }}%</td>
                                    <td class="text-end">Rp {{ number_format($atlRpUe, 0, ',', '.') }}</td>
                                    <td class="text-end">Rp {{ number_format($item->rp_uac, 0, ',', '.') }}</td>
                                    <td class="text-end">{{ round($atlCrRp, 2) }}%</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    {{-- Start Dynamic Pareto Modal --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.body.addEventListener('click', function(e) {
                if (e.target && e.target.classList.contains('btn-detail-pareto')) {
                    e.preventDefault();
                    let type = e.target.getAttribute('data-type');
                    let title = e.target.getAttribute('data-title');

                    let tbodyHtml = '';
                    let tfootHtml = '';

                    if (type === 'UE') {
                        let listUe = @json($listUe);
                        if (listUe.length > 0) {
                            let totalUe = {{ $totalUe }};
                            let pareto80Ue = {{ $pareto80Ue }};
                            let cumulativeUe = 0;

                            listUe.forEach((item, index) => {
                                let itemUe = parseInt(item.unit_entry);
                                let isTop80Ue = cumulativeUe <
                                    pareto80Ue; // Baris pertama yang melebihi batas akan ter-highlight biru (karena < dievaluasi sebelum +=)
                                cumulativeUe += itemUe;

                                let rowClass = isTop80Ue ? 'table-primary' : 'table-warning';

                                tbodyHtml += `
                                <tr class="${rowClass}">
                                    <td class="text-center">${index + 1}</td>
                                    <td>${item.nama_dealer} - ${item.cabang}</td>
                                    <td class="text-end">${new Intl.NumberFormat('id-ID').format(itemUe)}</td>
                                    <td class="text-end">${new Intl.NumberFormat('id-ID').format(cumulativeUe)}</td>
                                </tr>
                            `;
                            });

                            tfootHtml = `
                            <tfoot class="table-success">
                                <tr>
                                    <th colspan="2" class="text-end pe-3">Total :</th>
                                    <th colspan="2" class="text-end">${new Intl.NumberFormat('id-ID').format(totalUe)}</th>
                                </tr>
                                <tr>
                                    <th colspan="2" class="text-end pe-3">Pareto (Total x 80%) :</th>
                                    <th colspan="2" class="text-end">${new Intl.NumberFormat('id-ID').format(Math.round(pareto80Ue))}</th>
                                </tr>
                            </tfoot>
                        `;
                        } else {
                            tbodyHtml =
                                `<tr><td colspan="4" class="text-center text-muted py-5"><em>Data tidak tersedia</em></td></tr>`;
                        }
                    } else if (type == 'UAC') {
                        let listUac = @json($listUac);
                        if (listUac.length > 0) {
                            let totalUac = {{ $totalUac }};
                            let pareto80Uac = {{ $pareto80Uac }};
                            let cumulativeUac = 0;

                            listUac.forEach((item, index) => {
                                let itemUac = parseInt(item.unit_ac);
                                let isTop80Uac = cumulativeUac <
                                    pareto80Uac; // Baris pertama yang melebihi batas akan ter-highlight biru (karena < dievaluasi sebelum +=)
                                cumulativeUac += itemUac;

                                let rowClass = isTop80Uac ? 'table-primary' : 'table-warning';

                                tbodyHtml += `
                                <tr class="${rowClass}">
                                    <td class="text-center">${index + 1}</td>
                                    <td>${item.nama_dealer} - ${item.cabang}</td>
                                    <td class="text-end">${new Intl.NumberFormat('id-ID').format(itemUac)}</td>
                                    <td class="text-end">${new Intl.NumberFormat('id-ID').format(cumulativeUac)}</td>
                                </tr>
                            `;
                            });

                            tfootHtml = `
                            <tfoot class="table-success">
                                <tr>
                                    <th colspan="2" class="text-end pe-3">Total :</th>
                                    <th colspan="2" class="text-end">${new Intl.NumberFormat('id-ID').format(totalUac)}</th>
                                </tr>
                                <tr>
                                    <th colspan="2" class="text-end pe-3">Pareto (Total x 80%) :</th>
                                    <th colspan="2" class="text-end">${new Intl.NumberFormat('id-ID').format(Math.round(pareto80Uac))}</th>
                                </tr>
                            </tfoot>
                        `;
                        } else {
                            tbodyHtml =
                                `<tr><td colspan="4" class="text-center text-muted py-5"><em>Data tidak tersedia</em></td></tr>`;
                        }

                    } else if (type == 'RP/UE') {
                        let listRpue = @json($listRpue);
                        if (listRpue.length > 0) {
                            let totalRpue = {{ $totalRpue }};
                            let pareto80Rpue = {{ $pareto80Rpue }};
                            let cumulativeRpue = 0;

                            listRpue.forEach((item, index) => {
                                let itemRpue = parseInt(item.rp_unit_entry);
                                let isTop80Rpue = cumulativeRpue < pareto80Rpue;
                                cumulativeRpue += itemRpue;

                                let rowClass = isTop80Rpue ? 'table-primary' : 'table-warning';

                                tbodyHtml += `
                                <tr class="${rowClass}">
                                    <td class="text-center">${index + 1}</td>
                                    <td>${item.nama_dealer} - ${item.cabang}</td>
                                    <td class="text-end">Rp ${new Intl.NumberFormat('id-ID').format(itemRpue)}</td>
                                    <td class="text-end">Rp ${new Intl.NumberFormat('id-ID').format(cumulativeRpue)}</td>
                                </tr>
                            `;
                            });

                            tfootHtml = `
                            <tfoot class="table-success">
                                <tr>
                                    <th colspan="2" class="text-end pe-3">Total :</th>
                                    <th colspan="2" class="text-end">Rp ${new Intl.NumberFormat('id-ID').format(totalRpue)}</th>
                                </tr>
                                <tr>
                                    <th colspan="2" class="text-end pe-3">Pareto (Total x 80%) :</th>
                                    <th colspan="2" class="text-end">Rp ${new Intl.NumberFormat('id-ID').format(Math.round(pareto80Rpue))}</th>
                                </tr>
                            </tfoot>
                        `;
                        } else {
                            tbodyHtml =
                                `<tr><td colspan="4" class="text-center text-muted py-5"><em>Data tidak tersedia</em></td></tr>`;
                        }
                    } else if (type == 'RP/UAC') {
                        let listRpuac = @json($listRpuac);
                        if (listRpuac.length > 0) {
                            let totalRpuac = {{ $totalRpuac }};
                            let pareto80Rpuac = {{ $pareto80Rpuac }};
                            let cumulativeRpuac = 0;

                            listRpuac.forEach((item, index) => {
                                let itemRpuac = parseInt(item.rp_uac);
                                let isTop80Rpuac = cumulativeRpuac <
                                    pareto80Rpuac; // Baris pertama yang melebihi batas akan ter-highlight biru (karena < dievaluasi sebelum +=)
                                cumulativeRpuac += itemRpuac;

                                let rowClass = isTop80Rpuac ? 'table-primary' : 'table-warning';

                                tbodyHtml += `
                                <tr class="${rowClass}">
                                    <td class="text-center">${index + 1}</td>
                                    <td>${item.nama_dealer} - ${item.cabang}</td>
                                    <td class="text-end">Rp ${new Intl.NumberFormat('id-ID').format(itemRpuac)}</td>
                                    <td class="text-end">Rp ${new Intl.NumberFormat('id-ID').format(cumulativeRpuac)}</td>
                                </tr>
                            `;
                            });

                            tfootHtml = `
                            <tfoot class="table-success">
                                <tr>
                                    <th colspan="2" class="text-end pe-3">Total :</th>
                                    <th colspan="2" class="text-end">Rp ${new Intl.NumberFormat('id-ID').format(totalRpuac)}</th>
                                </tr>
                                <tr>
                                    <th colspan="2" class="text-end pe-3">Pareto (Total x 80%) :</th>
                                    <th colspan="2" class="text-end">Rp ${new Intl.NumberFormat('id-ID').format(Math.round(pareto80Rpuac))}</th>
                                </tr>
                            </tfoot>
                        `;
                        } else {
                            tbodyHtml =
                                `<tr><td colspan="4" class="text-center text-muted py-5"><em>Data tidak tersedia</em></td></tr>`;
                        }
                    } else {
                        tbodyHtml = `
                        <tr>
                            <td class="text-center text-muted py-5">
                                <em>Data detail tabel belum tersedia (Mockup UI)</em>
                            </td>
                        </tr>
                     `;
                    }

                    // Build Modal HTML
                    let modalHtml = `
                <div class="modal fade" id="dynamicParetoModal" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-xl modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header" style="background-color: #000080;">
                                <h5 class="modal-title text-white">${title}</h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body p-0">
                                <div class="alert alert-info m-3 mb-2" role="alert">
                                    <h6 class="alert-heading mb-1"><i class="iconly-boldInfo-Square me-2"></i>Penjelasan Perhitungan:</h6>
                                    <ul class="mb-0 ps-3" style="font-size: 0.85rem;">
                                        <li><strong>Nilai ${type}:</strong> Diurutkan dari cabang dengan nilai terbesar hingga terkecil.</li>
                                        <li><strong>Kumulatif:</strong> Akumulasi (penjumlahan) nilai dari urutan pertama hingga baris tersebut.</li>
                                        <li><strong>Pareto 80%:</strong> Cabang penyumbang 80% pertama ditandai dengan warna <span class="badge bg-primary">Biru</span>. Sisanya 20% ditandai warna <span class="badge bg-warning text-dark">Kuning</span>.</li>
                                    </ul>
                                </div>
                                <div class="table-responsive m-3 mt-0">
                                    <table class="table table-bordered mb-0 w-100" id="paretoTable">
                                        <thead class="table-light">
                                            <tr>
                                                <th class="text-center" width="5%">No</th>
                                                <th class="text-center" width="50%">Nama Cabang</th>
                                                <th class="text-center" width="22.5%">Nilai ${type}</th>
                                                <th class="text-center" width="22.5%">Kumulatif</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            ${tbodyHtml}
                                        </tbody>
                                        ${tfootHtml}
                                    </table>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-light-secondary" data-bs-dismiss="modal">Tutup</button>
                            </div>
                        </div>
                    </div>
                </div>`;

                    // Append to body
                    document.body.insertAdjacentHTML('beforeend', modalHtml);

                    let modalElement = document.getElementById('dynamicParetoModal');
                    let paretoModal = new bootstrap.Modal(modalElement);

                    // Initialize DataTables for sticky header/footer
                    $('#paretoTable').DataTable({
                        paging: false,
                        searching: true,
                        info: false,
                        scrollY: '55vh',
                        scrollCollapse: true,
                        ordering: false,
                        language: {
                            search: "Cari:",
                            zeroRecords: "Data tidak ditemukan"
                        }
                    });

                    // Destroy after hidden
                    modalElement.addEventListener('hidden.bs.modal', function() {
                        if ($.fn.DataTable.isDataTable('#paretoTable')) {
                            $('#paretoTable').DataTable().destroy(true);
                        }
                        paretoModal.dispose();
                        modalElement.remove();
                    });

                    // Show modal
                    paretoModal.show();
                }
            });
        });
    </script>
    {{-- End Dynamic Pareto Modal --}}

    {{-- Start Export Excel Script --}}
    <script>
        function exportActiveTable() {
            let activeTabId = document.querySelector('.nav-pills .nav-link.active').getAttribute('aria-controls');
            let tab = activeTabId === 'data-potensi' ? 'data-potensi' : 'data-unit-entry';

            let exportUrl = new URL("{{ route('potentials.export') }}");

            // Ambil semua parameter filter dari form pencarian di URL saat ini
            let currentParams = new URLSearchParams(window.location.search);
            for (let [key, value] of currentParams) {
                exportUrl.searchParams.append(key, value);
            }

            // Tambahkan parameter tab yang sedang aktif
            exportUrl.searchParams.set('tab', tab);

            // Redirect ke route export (akan langsung mengunduh file)
            window.location.href = exportUrl.toString();
        }
    </script>
    {{-- End Export Excel Script --}}
@endpush
