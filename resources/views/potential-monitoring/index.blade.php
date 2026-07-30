@extends('layouts.dashboard')

@section('title', 'Monitoring Potensi')
@section('page-title', 'Monitoring Potensi')
@section('page-description', 'Ringkasan potensi dealer dan estimasi revenue operasional.')

@section('content')
    <div class="card mb-4">
        <div class="card-header border-bottom">
            <h5 class="card-title mb-0">Total Pareto</h5>
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
                            <button class="btn btn-sm btn-outline-primary w-100 mt-3 btn-detail-pareto" data-type="UE" data-title="Detail Unit Entry (UE)">Lihat Detail</button>
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
                                    <h6 class="font-extrabold mb-0">0</h6>
                                </div>
                            </div>
                            <button class="btn btn-sm btn-outline-success w-100 mt-3 btn-detail-pareto" data-type="UAC" data-title="Detail Unit AC (UAC)">Lihat Detail</button>
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
                                    <h6 class="font-extrabold mb-0">Rp 0</h6>
                                </div>
                            </div>
                            <button class="btn btn-sm btn-outline-info w-100 mt-3 btn-detail-pareto" data-type="RP/UE" data-title="Detail RP/UE">Lihat Detail</button>
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
                                    <h6 class="font-extrabold mb-0">Rp 0</h6>
                                </div>
                            </div>
                            <button class="btn btn-sm btn-outline-danger w-100 mt-3 btn-detail-pareto" data-type="RP/UAC" data-title="Detail RP/UAC">Lihat Detail</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

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
                    @foreach ($potentials as $item)
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
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection

@push('scripts')
{{-- Start Dynamic Pareto Modal --}}
<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.body.addEventListener('click', function (e) {
            if (e.target && e.target.classList.contains('btn-detail-pareto')) {
                e.preventDefault();
                let type = e.target.getAttribute('data-type');
                let title = e.target.getAttribute('data-title');
                
                let tbodyHtml = '';
                let tfootHtml = '';
                
                if (type === 'UE') {
                    let paretoUe = @json($paretoUe);
                    if (paretoUe.length > 0) {
                        let totalUe = {{ $totalUe }};
                        let pareto80 = {{ $pareto80Ue }};
                        let cumulativeUe = 0;
                        
                        paretoUe.forEach((item, index) => {
                            let itemUe = parseInt(item.unit_entry);
                            let isTop80 = cumulativeUe < pareto80; // Baris pertama yang melebihi batas akan ter-highlight biru (karena < dievaluasi sebelum +=)
                            cumulativeUe += itemUe;
                            
                            let rowClass = isTop80 ? 'table-primary' : 'table-warning';
                            
                            tbodyHtml += `
                                <tr class="${rowClass}">
                                    <td class="text-center">${index + 1}</td>
                                    <td>${item.nama_dealer} - ${item.cabang}</td>
                                    <td class="text-center">${new Intl.NumberFormat('id-ID').format(itemUe)}</td>
                                    <td class="text-center">${new Intl.NumberFormat('id-ID').format(cumulativeUe)}</td>
                                </tr>
                            `;
                        });
                        
                        tfootHtml = `
                            <tfoot class="table-success">
                                <tr>
                                    <th colspan="2" class="text-end pe-3">Total :</th>
                                    <th colspan="2" class="text-center">${new Intl.NumberFormat('id-ID').format(totalUe)}</th>
                                </tr>
                                <tr>
                                    <th colspan="2" class="text-end pe-3">Pareto (Total x 80%) :</th>
                                    <th colspan="2" class="text-center">${new Intl.NumberFormat('id-ID').format(Math.round(pareto80))}</th>
                                </tr>
                            </tfoot>
                        `;
                    } else {
                        tbodyHtml = `<tr><td colspan="4" class="text-center text-muted py-5"><em>Data tidak tersedia</em></td></tr>`;
                    }
                } else {
                     tbodyHtml = `
                        <tr>
                            <td colspan="4" class="text-center text-muted py-5">
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
                                <div class="table-responsive m-3">
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
                modalElement.addEventListener('hidden.bs.modal', function () {
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
@endpush
