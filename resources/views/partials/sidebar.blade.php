@php
    $userRole = auth()->user()->dashboard_role ?? 'atl';
    $activeRole = strtolower((string) ($role ?? $userRole));

    if (! in_array($activeRole, ['atl', 'soh'], true)) {
        $activeRole = $userRole;
    }

    if ($userRole === 'atl') {
        $activeRole = 'atl';
    }

    $isSoh = $activeRole === 'soh';
@endphp

<div id="sidebar" class="active">
    <div class="sidebar-wrapper active">
        <div class="sidebar-header">
            <div class="d-flex justify-content-between">
                <a href="{{ route('dashboard', ['role' => $activeRole]) }}" class="dashboard-brand">
                    <span class="dashboard-brand-logo-wrap">
                        <img
                            src="{{ asset('images/Logo SNS HD.png') }}"
                            alt="Logo SNS AC"
                            class="dashboard-brand-logo"
                        >
                    </span>

                    <span class="dashboard-brand-title">
                        Monitoring Operasional
                    </span>
                </a>

                <div class="toggler">
                    <a href="#" class="sidebar-hide d-xl-none d-block">
                        <i class="bi bi-x bi-middle"></i>
                    </a>
                </div>
            </div>
        </div>

        <div class="sidebar-role-card">
            <div>
                <small>Mode pengguna</small>
                <strong>{{ $isSoh ? 'Service Operation Head' : 'Area Technical Lead' }}</strong>
            </div>

            <span class="badge bg-light-primary text-primary">
                {{ strtoupper($activeRole) }}
            </span>
        </div>

        <div class="sidebar-menu">
            <ul class="menu">
                <li class="sidebar-title">Monitoring Utama</li>

                <li class="sidebar-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <a href="{{ route('dashboard', ['role' => $activeRole]) }}" class="sidebar-link">
                        <i class="bi bi-grid-fill"></i>
                        <span>Dashboard</span>
                    </a>
                </li>

                @if ($isSoh)
                    <li class="sidebar-item {{ request()->routeIs('atl-regions.*') ? 'active' : '' }}">
                        <a href="{{ route('atl-regions.index', ['role' => $activeRole]) }}" class="sidebar-link">
                            <i class="bi bi-diagram-3-fill"></i>
                            <span>Data ATL</span>
                        </a>
                    </li>

                    <li class="sidebar-item {{ request()->routeIs('atl-comparisons.*') ? 'active' : '' }}">
                        <a href="{{ route('atl-comparisons.index', ['role' => $activeRole]) }}" class="sidebar-link">
                            <i class="bi bi-bar-chart-fill"></i>
                            <span>Perbandingan ATL</span>
                        </a>
                    </li>
                @endif

                <li class="sidebar-title">Data Operasional</li>

                <li class="sidebar-item {{ request()->routeIs('dealers.*') ? 'active' : '' }}">
                    <a href="{{ route('dealers.index', ['role' => $activeRole]) }}" class="sidebar-link">
                        <i class="bi bi-shop"></i>
                        <span>Data Dealer</span>
                    </a>
                </li>

                <li class="sidebar-item has-sub {{ request()->routeIs('mechanics.*') || request()->routeIs('attendances.*') ? 'active' : '' }}">
                    <a href="#" class="sidebar-link">
                        <i class="bi bi-people-fill"></i>
                        <span>Data Mekanik</span>
                    </a>
                    <ul class="submenu {{ request()->routeIs('mechanics.*') || request()->routeIs('attendances.*') ? 'active' : '' }}">
                        <li class="submenu-item {{ request()->routeIs('mechanics.index') ? 'active' : '' }}">
                            <a href="{{ route('mechanics.index', ['role' => $activeRole]) }}">Daftar Mekanik</a>
                        </li>
                        <li class="submenu-item {{ request()->routeIs('mechanics.attendances.daily') || request()->routeIs('attendances.index') ? 'active' : '' }}">
                            <a href="{{ route('mechanics.attendances.daily', ['role' => $activeRole]) }}">Presensi Harian</a>
                        </li>
                        <li class="submenu-item {{ request()->routeIs('mechanics.attendances.recap') ? 'active' : '' }}">
                            <a href="{{ route('mechanics.attendances.recap', ['role' => $activeRole]) }}">Rekap Presensi</a>
                        </li>
                    </ul>
                </li>

                <li class="sidebar-title">Inspeksi &amp; Analisis</li>

                <li class="sidebar-item has-sub {{ request()->routeIs('inspections.*') ? 'active' : '' }}">
                    <a href="#" class="sidebar-link">
                        <i class="bi bi-clipboard-check-fill"></i>
                        <span>Precheck &amp; Postcheck</span>
                    </a>
                    <ul class="submenu {{ request()->routeIs('inspections.*') ? 'active' : '' }}">
                        <li class="submenu-item {{ request()->routeIs('inspections.index') ? 'active' : '' }}">
                            <a href="{{ route('inspections.index', ['role' => $activeRole]) }}">Monitoring Pemeriksaan</a>
                        </li>
                        <li class="submenu-item {{ request()->routeIs('inspections.pending-postcheck') ? 'active' : '' }}">
                            <a href="{{ route('inspections.pending-postcheck', ['role' => $activeRole]) }}">Menunggu Postcheck</a>
                        </li>
                        <li class="submenu-item {{ request()->routeIs('inspections.verification') ? 'active' : '' }}">
                            <a href="{{ route('inspections.verification', ['role' => $activeRole]) }}">Perlu Verifikasi</a>
                        </li>
                    </ul>
                </li>

                <li class="sidebar-item has-sub {{ request()->routeIs('potentials.*') ? 'active' : '' }}">
                    <a href="#" class="sidebar-link">
                        <i class="bi bi-graph-up-arrow"></i>
                        <span>Monitoring Potensi</span>
                    </a>
                    <ul class="submenu {{ request()->routeIs('potentials.*') ? 'active' : '' }}">
                        <li class="submenu-item {{ request()->routeIs('potentials.index') ? 'active' : '' }}">
                            <a href="{{ route('potentials.index', ['role' => $activeRole]) }}">Ringkasan</a>
                        </li>
                        <li class="submenu-item {{ request()->routeIs('potentials.ranking') ? 'active' : '' }}">
                            <a href="{{ route('potentials.ranking', ['role' => $activeRole]) }}">Ranking Dealer</a>
                        </li>
                        <li class="submenu-item {{ request()->routeIs('potentials.follow-ups') ? 'active' : '' }}">
                            <a href="{{ route('potentials.follow-ups', ['role' => $activeRole]) }}">Tindak Lanjut</a>
                        </li>
                    </ul>
                </li>

                <li class="sidebar-item {{ request()->routeIs('notifications.*') ? 'active' : '' }}">
                    <a href="{{ route('notifications.index', ['role' => $activeRole]) }}" class="sidebar-link">
                        <i class="bi bi-bell-fill"></i>
                        <span>Notifikasi</span>
                    </a>
                </li>

                <li class="sidebar-title">Akun</li>

                <li class="sidebar-item {{ request()->routeIs('profile.*') ? 'active' : '' }}">
                    <a href="{{ route('profile.index', ['role' => $activeRole]) }}" class="sidebar-link">
                        <i class="bi bi-person-circle"></i>
                        <span>Profil</span>
                    </a>
                </li>

                <li class="sidebar-item">
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf

                        <button type="submit" class="sidebar-link sidebar-logout-button">
                            <i class="bi bi-box-arrow-right"></i>
                            <span>Keluar</span>
                        </button>
                    </form>
                </li>
            </ul>
        </div>

        <button class="sidebar-toggler btn x">
            <i data-feather="x"></i>
        </button>
    </div>
</div>
