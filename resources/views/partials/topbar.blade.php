@php
    $user = auth()->user() ?? request()->user();
    $userRole = $user->dashboard_role ?? 'atl';
    $activeRole = strtolower((string) ($role ?? $userRole));

    if ($userRole === 'atl') {
        $activeRole = 'atl';
    }
@endphp

<header class="dashboard-topbar mb-3">
    <a href="#" class="burger-btn d-block d-xl-none">
        <i class="bi bi-justify fs-3"></i>
    </a>

    <div class="dashboard-topbar-actions">
        @if ($userRole === 'soh')
            <a
                href="{{ route('dashboard', ['role' => 'atl']) }}"
                class="btn btn-sm {{ $activeRole === 'atl' ? 'btn-primary' : 'btn-outline-primary' }}"
            >
                <i class="bi bi-person-badge me-1"></i>
                Mode ATL
            </a>

            <a
                href="{{ route('dashboard', ['role' => 'soh']) }}"
                class="btn btn-sm {{ $activeRole === 'soh' ? 'btn-primary' : 'btn-outline-primary' }}"
            >
                <i class="bi bi-diagram-3 me-1"></i>
                Mode SOH
            </a>
        @endif

        <a href="{{ route('notifications.index', ['role' => $activeRole]) }}" class="topbar-icon-button">
            <i class="bi bi-bell-fill"></i>
            <span></span>
        </a>

        <div class="topbar-user">
            <strong>{{ $user->nama ?? $user->username ?? 'Pengguna' }}</strong>
            <small>{{ strtoupper($userRole) }} • {{ now('Asia/Jakarta')->format('d-m-Y H:i') }} WIB</small>
        </div>
    </div>
</header>
