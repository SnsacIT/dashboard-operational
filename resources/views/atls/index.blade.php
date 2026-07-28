@extends('layouts.dashboard')

@section('title', 'Data ATL')
@section('page-title', 'Data ATL')
@section('page-description', 'Daftar Area Technical Lead dalam cakupan SOH login.')

@section('content')
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Nama ATL</th>
                            <th>Wilayah</th>
                            <th>Total Dealer</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($atls as $atl)
                            <tr>
                                <td>{{ $atl->nama ?? $atl->username ?? $atl->nip_atl }}</td>
                                <td>{{ $atl->nama_wilayah }}</td>
                                <td>{{ $dealerCounts[$atl->urutan] ?? 0 }}</td>
                                <td><span class="badge bg-light-success text-success">Aktif</span></td>
                                <td>
                                    <a href="{{ route('atl-regions.show', $atl->urutan) }}" class="btn btn-sm btn-light-primary">Detail</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">Belum ada data ATL.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $atls->links() }}
        </div>
    </div>
@endsection
