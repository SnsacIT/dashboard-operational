@extends('layouts.dashboard')

@section('title', 'Profil')
@section('page-title', 'Profil Singkat')
@section('page-description', 'Informasi pengguna, role, kontak, dan cakupan operasional.')

@section('content')
    <div class="attendance-page profile-page">
        @if (session('profile_status'))
            <div class="alert alert-light-success border-0">{{ session('profile_status') }}</div>
        @endif

        <div class="profile-summary-card compact">
            <div class="profile-avatar">
                @if ($user->profile)
                    <img src="{{ Storage::url($user->profile) }}" alt="Foto {{ $user->nama ?? $user->username }}">
                @else
                    {{ strtoupper(substr($user->nama ?? $user->username ?? 'U', 0, 1)) }}
                @endif
            </div>
            <div class="profile-main-info">
                <span>{{ strtoupper($role) }}</span>
                <h4>{{ $user->nama ?? $user->username ?? 'Pengguna' }}</h4>
                <p>{{ $user->nip ?? '-' }} • {{ $user->posisi ?? 'Operasional' }}</p>
            </div>
            <div class="profile-meta"><small>Login terakhir</small><strong>{{ $user->last_login_at ? \Carbon\Carbon::parse($user->last_login_at)->format('d-m-Y H:i') : '-' }}</strong></div>
        </div>

        <div class="attendance-kpi-grid three-items">
            <div class="attendance-kpi-card primary"><span>Dealer Akses</span><strong>{{ number_format($kpis['dealers'], 0, ',', '.') }}</strong></div>
            <div class="attendance-kpi-card success"><span>Total Mekanik</span><strong>{{ number_format($kpis['mechanics'], 0, ',', '.') }}</strong></div>
            <div class="attendance-kpi-card info"><span>Mode Aktif</span><strong>{{ strtoupper($role) }}</strong></div>
        </div>

        <div class="row g-4 align-items-stretch profile-card-row">
            <div class="col-12 col-xl-6 d-flex">
                <div class="card attendance-table-card profile-compact-card w-100">
                    <div class="card-header">
                        <h4 class="mb-1">Akun & Foto</h4>
                        <p class="text-muted mb-0">Profil singkat pengguna.</p>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('profile.photo.update', ['role' => $role]) }}" enctype="multipart/form-data" class="profile-photo-form">
                            @csrf
                            <label class="form-label">Foto Profil <small class="text-muted">maks. 5 MB</small></label>
                            <div class="profile-upload-row">
                                <input type="file" name="photo" class="form-control @error('photo') is-invalid @enderror" accept="image/jpeg,image/png,image/webp" required>
                                <button class="btn btn-primary">Simpan</button>
                            </div>
                            @error('photo')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </form>

                        <div class="profile-info-grid compact">
                            <div><span>Username</span><strong>{{ $user->username ?? '-' }}</strong></div>
                            <div><span>NIP</span><strong>{{ $user->nip ?? '-' }}</strong></div>
                            <div><span>Kontak</span><strong>{{ $user->kontak ?? '-' }}</strong></div>
                            <div><span>Grade</span><strong>{{ $user->grade ?? '-' }}</strong></div>
                            <div class="wide"><span>Dealer/Cabang</span><strong>{{ trim(($user->dealer ?? '-').' / '.($user->cabang ?? '-')) }}</strong></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-xl-6 d-flex">
                <div class="card attendance-table-card profile-compact-card w-100">
                    <div class="card-header">
                        <h4 class="mb-1">Cakupan Dealer</h4>
                        <p class="text-muted mb-0">Maksimal 8 dealer pertama dalam akses pengguna.</p>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive attendance-table-wrap">
                            <table class="table table-hover align-middle mb-0 attendance-table">
                                <thead><tr><th>Dealer</th><th>Cabang</th><th>ATL</th><th>SOH</th></tr></thead>
                                <tbody>
                                    @forelse ($dealers as $dealer)
                                        <tr><td>{{ $dealer->nama_dealer ?? $dealer->dealer }}</td><td>{{ $dealer->cabang }}</td><td>{{ $dealer->no_atl ?? '-' }}</td><td>{{ $dealer->no_soh ?? '-' }}</td></tr>
                                    @empty
                                        <tr><td colspan="4" class="text-center text-muted py-5">Belum ada cakupan dealer.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
