@extends('layouts.dashboard')

@section('title', 'Detail ATL')
@section('page-title', $atl->nama ?? $atl->username ?? $atl->nip_atl)
@section('page-description', 'Detail wilayah, dealer, dan mekanik dalam cakupan ATL.')

@section('content')
    <div class="row">
        <div class="col-12 col-lg-4">
            <div class="card">
                <div class="card-body">
                    <h4>{{ $atl->nama ?? $atl->username ?? $atl->nip_atl }}</h4>
                    <p class="text-muted mb-2">{{ $atl->nama_wilayah }}</p>
                    <span class="badge bg-light-primary text-primary">NIP {{ $atl->nip_atl }}</span>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-8">
            <div class="card">
                <div class="card-header"><h4>Dealer di Wilayah Ini</h4></div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Dealer</th>
                                    <th>Cabang</th>
                                    <th>Mekanik</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($dealers as $dealer)
                                    <tr>
                                        <td>{{ $dealer->dealer }}</td>
                                        <td>{{ $dealer->cabang }}</td>
                                        <td>{{ $dealer->status_kontrak ?? 'Aktif' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center text-muted py-4">Belum ada dealer.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
