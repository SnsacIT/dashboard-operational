@extends('layouts.dashboard')

@section('title', 'Notifikasi')
@section('page-title', 'Notifikasi Operasional')
@section('page-description', 'Peringatan presensi, postcheck, verifikasi, dan tindak lanjut berdasarkan data terbaru.')

@section('content')
    <div class="attendance-page">
        <div class="attendance-kpi-grid three-items">
            <div class="attendance-kpi-card primary"><span>Total Notifikasi</span><strong>{{ number_format($kpis['total'], 0, ',', '.') }}</strong></div>
            <div class="attendance-kpi-card danger"><span>Prioritas Tinggi</span><strong>{{ number_format($kpis['danger'], 0, ',', '.') }}</strong></div>
            <div class="attendance-kpi-card warning"><span>Perlu Dipantau</span><strong>{{ number_format($kpis['warning'], 0, ',', '.') }}</strong></div>
        </div>

        <div class="card attendance-table-card">
            <div class="card-header"><h4 class="mb-1">Daftar Notifikasi</h4><p class="text-muted mb-0">Urutan berdasarkan aktivitas terbaru.</p></div>
            <div class="card-body p-0">
                <div class="notification-list">
                    @forelse ($notifications as $notification)
                        <a href="{{ $notification->route }}" class="notification-row {{ $notification->level }}">
                            <div class="notification-marker"></div>
                            <div class="notification-content"><span>{{ $notification->type }}</span><strong>{{ $notification->title }}</strong><p>{{ $notification->message }}</p></div>
                            <small>{{ $notification->time ? \Carbon\Carbon::parse($notification->time)->format('d-m-Y H:i') : '-' }}</small>
                        </a>
                    @empty
                        <div class="text-center text-muted py-5">Belum ada notifikasi operasional.</div>
                    @endforelse
                </div>
            </div>
            <div class="card-footer bg-white">
                @include('partials.simple-pagination', ['paginator' => $notifications])
            </div>
        </div>
    </div>
@endsection
