@extends('layouts.dashboard')

@section('title', 'Data Relasi')
@section('page-title', 'Data Relasi')
@section('page-description', 'Menampilkan data relasi dealer.')

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
            <form class="monitoring-filter" method="GET" action="{{ route('potentials.relasi') }}">
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
                    <a href="{{ route('potentials.export-relasi', request()->all()) }}" class="btn btn-success btn-sm">
                        <i class="iconly-boldDocument me-1"></i> Export Excel
                    </a>
                    @if (Auth::user()->role == '1')
                        <a href="{{ route('potentials.input-relasi') }}" class="btn btn-primary btn-sm">
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
                            <th class="align-middle text-center">SA</th>
                            <th class="align-middle text-center">Concern SA</th>
                            <th class="align-middle text-center">SM</th>
                            <th class="align-middle text-center">Concern SM</th>
                            <th class="align-middle text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if (isset($relasi) && count($relasi) > 0)
                            @foreach ($relasi as $item)
                                <tr>
                                    <td class="text-center">{{ $item->id }}</td>
                                    <td class="text-center">{{ $item->nama_dealer }}</td>
                                    <td class="text-center">
                                        {{ $item->periode ? \Carbon\Carbon::parse($item->periode)->locale('id')->translatedFormat('F Y') : '-' }}
                                    </td>
                                    <td class="text-center" style="text-transform: capitalize;"><b>{{ $item->sa }}</b>
                                    </td>
                                    <td class="text-center">{{ $item->concern_sa }}</td>
                                    <td class="text-center" style="text-transform: capitalize;"><b>{{ $item->sm }}</b>
                                    </td>
                                    <td class="text-center">{{ $item->concern_sm }}</td>
                                    <td class="text-center">
                                        @if (Auth::user()->role == '1')
                                            <button type="button" class="btn btn-sm btn-warning" data-bs-toggle="modal"
                                                data-bs-target="#editModal{{ $item->id }}">
                                                <i class="iconly-boldEdit"></i>
                                            </button>
                                        @endif

                                        <!-- Modal Edit -->
                                        <div class="modal fade text-start" id="editModal{{ $item->id }}" tabindex="-1"
                                            aria-labelledby="editModalLabel{{ $item->id }}" aria-hidden="true">
                                            <div class="modal-dialog modal-dialog-centered">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title" id="editModalLabel{{ $item->id }}">Edit
                                                            Relasi - {{ $item->nama_dealer }}</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                            aria-label="Close"></button>
                                                    </div>
                                                    <form action="{{ route('potentials.update-relasi', $item->id) }}"
                                                        method="POST">
                                                        @csrf
                                                        @method('PUT')
                                                        <div class="modal-body">
                                                            <div class="mb-3">
                                                                <label class="form-label">SA</label>
                                                                <select name="sa" class="form-select">
                                                                    <option value="">- Pilih -</option>
                                                                    <option value="Dekat" @selected($item->sa == 'Dekat')>
                                                                        Dekat</option>
                                                                    <option value="Partner" @selected($item->sa == 'Partner')>
                                                                        Partner</option>
                                                                    <option value="Kenal" @selected($item->sa == 'Kenal')>
                                                                        Kenal</option>
                                                                </select>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label">Concern SA</label>
                                                                <input type="text" class="form-control" name="concern_sa"
                                                                    value="{{ $item->concern_sa }}">
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label">SM</label>
                                                                <select name="sm" class="form-select">
                                                                    <option value="">- Pilih -</option>
                                                                    <option value="Dekat" @selected($item->sm == 'Dekat')>
                                                                        Dekat</option>
                                                                    <option value="Partner" @selected($item->sm == 'Partner')>
                                                                        Partner</option>
                                                                    <option value="Kenal" @selected($item->sm == 'Kenal')>
                                                                        Kenal</option>
                                                                </select>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label">Concern SM</label>
                                                                <input type="text" class="form-control"
                                                                    name="concern_sm" value="{{ $item->concern_sm }}">
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
