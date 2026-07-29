<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        @yield('title', 'Dashboard Monitoring Operasional')
    </title>

    <link rel="preconnect" href="https://fonts.gstatic.com">

    <link
        href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;600;700;800&display=swap"
        rel="stylesheet"
    >

    <link
        rel="stylesheet"
        href="{{ asset('mazer/assets/css/bootstrap.css') }}"
    >

    <link
        rel="stylesheet"
        href="{{ asset('mazer/assets/vendors/iconly/bold.css') }}"
    >

    <link
        rel="stylesheet"
        href="{{ asset('mazer/assets/vendors/bootstrap-icons/bootstrap-icons.css') }}"
    >

    <link
        rel="stylesheet"
        href="{{ asset('mazer/assets/vendors/perfect-scrollbar/perfect-scrollbar.css') }}"
    >

    <link
        rel="stylesheet"
        href="{{ asset('mazer/assets/css/app.css') }}"
    >

    <link
        rel="stylesheet"
        href="{{ asset('css/operational-dashboard.css') }}"
    >

    @stack('styles')
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
                <div
                    class="d-flex flex-column flex-lg-row
                           justify-content-between
                           align-items-lg-center gap-3"
                >
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

            <footer class="mt-4">
                <div class="footer clearfix mb-0 text-muted">
                    <div class="float-start">
                        <p>
                            {{ date('Y') }}
                            &copy; Dashboard Monitoring Operasional
                        </p>
                    </div>

                    <div class="float-end">
                        <p>
                            Team Operational ATL &amp; SOH
                        </p>
                    </div>
                </div>
            </footer>
        </div>
    </div>

    <script
        src="{{ asset('mazer/assets/vendors/perfect-scrollbar/perfect-scrollbar.min.js') }}"
    ></script>

    <script
        src="{{ asset('mazer/assets/js/bootstrap.bundle.min.js') }}"
    ></script>

    <script
        src="{{ asset('mazer/assets/js/main.js') }}"
    ></script>

    @stack('scripts')
</body>

</html>
