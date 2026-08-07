@extends('layouts.dashboard')

@section('title', 'Data Kompetisi')
@section('page-title', 'Data Kompetisi')
@section('page-description', 'Menampilkan data kompetisi dealer.')

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
    </style>
@endsection

@section('content')
    <div class="card mb-4">
        <div class="card-header border-bottom">
            <form class="monitoring-filter" method="GET" action="{{ route('potentials.kompetisi') }}">
                <div class="row g-3 align-items-end">
                    <div class="col-12 col-md-3">
                        <label class="form-label">Bulan & Tahun</label>
                        <input type="month" name="periode"
                            value="{{ request('periode', now('Asia/Jakarta')->format('Y-m')) }}" class="form-control">
                    </div>
                    <div class="col-12 col-md-7">
                        <label class="form-label">Dealer</label>
                        <select name="dealer[]" class="form-select choices multiple-remove" multiple="multiple">
                            <option value="">Semua Dealer</option>
                            @if (isset($dealers))
                                @foreach ($dealers as $dealer)
                                    <option value="{{ $dealer->id }}" @selected(in_array((string) $dealer->id, (array) request('dealer', [])))>
                                        {{ $dealer->nama_dealer }}
                                    </option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                    <div class="col-12 col-md-2">
                        <button class="btn btn-primary w-100">Terapkan Filter</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <div class="card">
        <div class="card-body">
            <div class="d-flex flex-row justify-content-between mb-3">
                <div class="d-flex align-items-center gap-2 align-self-start mt-1">
                    <a href="{{ route('potentials.export-kompetisi', request()->all()) }}" class="btn btn-success btn-sm">
                        <i class="iconly-boldDocument me-1"></i> Export Excel
                    </a>
                    @if (Auth::user()->role == '1')
                        <a href="{{ route('potentials.input-kompetisi') }}" class="btn btn-primary btn-sm">
                            <i class="iconly-boldPlus me-1"></i> Input Data
                        </a>
                    @endif
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle base-table text-nowrap" style="width: 100%;">
                    <thead class="bg-white text-center">
                        <tr>
                            <th class="align-middle text-center">ID</th>
                            <th class="align-middle text-center">Nama Dealer</th>
                            <th class="align-middle text-center">Bulan & Tahun</th>
                            <th class="align-middle text-center">Kompetitor</th>
                            <th class="align-middle text-center">Insentif</th>
                            <th class="align-middle text-center">Harga</th>
                            <th class="align-middle text-center">Grooming</th>
                            <th class="align-middle text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if (isset($kompetisi) && count($kompetisi) > 0)
                            @foreach ($kompetisi as $item)
                                <tr>
                                    <td class="text-center">{{ $item->id }}</td>
                                    <td class="text-center">{{ $item->nama_dealer }}</td>
                                    <td class="text-center">
                                        {{ $item->periode ? \Carbon\Carbon::parse($item->periode)->locale('id')->translatedFormat('F Y') : '-' }}
                                    </td>
                                    <td class="text-center">{{ $item->kompetitor }}</td>
                                    <td class="text-center">{{ $item->insentif }}</td>
                                    <td class="text-center">{{ $item->harga }}</td>
                                    <td class="text-center">{{ $item->grooming }}</td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-sm btn-warning" data-bs-toggle="modal"
                                            data-bs-target="#editModal{{ $item->id }}">
                                            <i class="iconly-boldEdit"></i>
                                        </button>

                                        <!-- Modal Edit -->
                                        <div class="modal fade text-start" id="editModal{{ $item->id }}" tabindex="-1"
                                            aria-labelledby="editModalLabel{{ $item->id }}" aria-hidden="true">
                                            <div class="modal-dialog modal-dialog-centered">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title" id="editModalLabel{{ $item->id }}">Edit
                                                            Kompetisi - {{ $item->nama_dealer }}</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                            aria-label="Close"></button>
                                                    </div>
                                                    <form action="{{ route('potentials.update-kompetisi', $item->id) }}"
                                                        method="POST">
                                                        @csrf
                                                        @method('PUT')
                                                        <div class="modal-body">
                                                            <div class="mb-3">
                                                                <label class="form-label">Kompetitor</label>
                                                                <select name="kompetitor"
                                                                    class="form-select kompetitor-select"
                                                                    onchange="toggleKompetitor(this, {{ $item->id }})">
                                                                    <option value="">- Pilih -</option>
                                                                    <option value="Ada" @selected($item->kompetitor == 'Ada')>Ada
                                                                    </option>
                                                                    <option value="Tidak Ada" @selected($item->kompetitor == 'Tidak Ada')>
                                                                        Tidak Ada</option>
                                                                </select>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label">Insentif</label>
                                                                <select name="insentif"
                                                                    class="form-select dep-select-{{ $item->id }}"
                                                                    data-current="{{ $item->insentif }}">
                                                                    <option value="">- Pilih -</option>
                                                                    <option value="Sangat Unggul" class="opt-sangat-unggul"
                                                                        @selected($item->insentif == 'Sangat Unggul')>Sangat Unggul</option>
                                                                    <option value="Lebih Unggul" class="opt-lain"
                                                                        @selected($item->insentif == 'Lebih Unggul')>Lebih Unggul</option>
                                                                    <option value="Sama dengan Kompetitor" class="opt-lain"
                                                                        @selected($item->insentif == 'Sama dengan Kompetitor')>Sama dengan Kompetitor
                                                                    </option>
                                                                    <option value="Di bawah Kompetitor" class="opt-lain"
                                                                        @selected($item->insentif == 'Di bawah Kompetitor')>Di bawah Kompetitor
                                                                    </option>
                                                                </select>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label">Harga</label>
                                                                <select name="harga"
                                                                    class="form-select dep-select-{{ $item->id }}"
                                                                    data-current="{{ $item->harga }}">
                                                                    <option value="">- Pilih -</option>
                                                                    <option value="Sangat Unggul"
                                                                        class="opt-sangat-unggul"
                                                                        @selected($item->harga == 'Sangat Unggul')>Sangat Unggul</option>
                                                                    <option value="Lebih Unggul" class="opt-lain"
                                                                        @selected($item->harga == 'Lebih Unggul')>Lebih Unggul</option>
                                                                    <option value="Sama dengan Kompetitor"
                                                                        class="opt-lain" @selected($item->harga == 'Sama dengan Kompetitor')>Sama
                                                                        dengan Kompetitor</option>
                                                                    <option value="Di bawah Kompetitor" class="opt-lain"
                                                                        @selected($item->harga == 'Di bawah Kompetitor')>Di bawah Kompetitor
                                                                    </option>
                                                                </select>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label">Grooming</label>
                                                                <select name="grooming"
                                                                    class="form-select dep-select-{{ $item->id }}"
                                                                    data-current="{{ $item->grooming }}">
                                                                    <option value="">- Pilih -</option>
                                                                    <option value="Sangat Unggul"
                                                                        class="opt-sangat-unggul"
                                                                        @selected($item->grooming == 'Sangat Unggul')>Sangat Unggul</option>
                                                                    <option value="Lebih Unggul" class="opt-lain"
                                                                        @selected($item->grooming == 'Lebih Unggul')>Lebih Unggul</option>
                                                                    <option value="Sama dengan Kompetitor"
                                                                        class="opt-lain" @selected($item->grooming == 'Sama dengan Kompetitor')>Sama
                                                                        dengan Kompetitor</option>
                                                                    <option value="Di bawah Kompetitor" class="opt-lain"
                                                                        @selected($item->grooming == 'Di bawah Kompetitor')>Di bawah Kompetitor
                                                                    </option>
                                                                </select>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-light-secondary"
                                                                data-bs-dismiss="modal">Tutup</button>
                                                            <button type="submit" class="btn btn-primary">Simpan
                                                                Perubahan</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function toggleKompetitor(selectElement, id) {
            let val = selectElement.value;
            let deps = document.querySelectorAll('.dep-select-' + id);

            deps.forEach(select => {
                let optSangatUnggul = select.querySelector('.opt-sangat-unggul');
                let optsLain = select.querySelectorAll('.opt-lain');

                if (val === 'Tidak Ada') {
                    // Semua diisi Sangat Unggul dan disable beneran
                    if (optSangatUnggul) optSangatUnggul.style.display = 'block';
                    select.value = 'Sangat Unggul';
                    select.disabled = true;
                    select.style.backgroundColor = '#e9ecef'; // disable look
                } else if (val === 'Ada') {
                    // Sembunyikan Sangat Unggul, tampilkan opsi lain, buka gembok
                    if (optSangatUnggul) optSangatUnggul.style.display = 'none';
                    select.disabled = false;
                    select.style.backgroundColor = '';

                    // Jika saat ini value-nya 'Sangat Unggul', reset atau kembalikan ke current
                    if (select.value === 'Sangat Unggul') {
                        let currentVal = select.getAttribute('data-current');
                        if (currentVal && currentVal !== 'Sangat Unggul') {
                            select.value = currentVal;
                        } else {
                            select.value = '';
                        }
                    }
                } else {
                    // Default jika tidak dipilih apa-apa (opsional)
                    if (optSangatUnggul) optSangatUnggul.style.display = 'block';
                    select.disabled = false;
                    select.style.backgroundColor = '';
                }
            });
        }

        // Jalankan logika on load untuk semua modal agar tampilannya sesuai dari awal
        document.addEventListener('DOMContentLoaded', function() {
            let kompetitorSelects = document.querySelectorAll('.kompetitor-select');
            kompetitorSelects.forEach(select => {
                let id = select.getAttribute('onchange').match(/\d+/)[0];
                if (select.value !== '') {
                    toggleKompetitor(select, id);
                }
            });

            // Pastikan select yang disabled di-enable kembali saat form disubmit agar datanya terkirim
            document.querySelectorAll('form').forEach(form => {
                form.addEventListener('submit', function() {
                    this.querySelectorAll('select:disabled').forEach(s => s.disabled = false);
                });
            });
        });
    </script>
@endpush
