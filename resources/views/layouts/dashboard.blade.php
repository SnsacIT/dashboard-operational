<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Dashboard Monitoring Operasional')</title>
    <link rel="preconnect" href="https://fonts.gstatic.com">
    @include('partials.styles')
</head>

@php
    $user = auth()->user();
    $userRole = $user->dashboard_role ?? 'atl';
    $activeRole = strtolower((string) ($role ?? $userRole));

    if (! in_array($activeRole, ['atl', 'soh'], true)) {
        $activeRole = $userRole;
    }

    if ($userRole === 'atl') {
        $activeRole = 'atl';
    }
@endphp

<body>
    <div id="app">
        @include('partials.sidebar')
        <div id="main">
            @include('partials.topbar')
            <div class="page-heading">
                <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
                    <div>
                        <h3 class="mb-1">
                            @yield('page-title', 'Dashboard')
                        </h3>

                        @hasSection('page-description')
                            <p class="text-muted mb-0">
                                @yield('page-description')
                            </p>
                        @endif
                    </div>
                </div>
            </div>

            <div class="page-content">
                @yield('content')
            </div>

            @include('partials.footer')
        </div>
    </div>

    @include('partials.scripts')
</body>

</html>
