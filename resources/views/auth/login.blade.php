<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Login Dashboard Monitoring Operasional</title>

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
        href="{{ asset('mazer/assets/vendors/bootstrap-icons/bootstrap-icons.css') }}"
    >

    <link
        rel="stylesheet"
        href="{{ asset('css/operational-dashboard.css') }}"
    >
</head>

<body>
    <main class="auth-page">
        <section class="auth-shell">
            <div class="auth-visual">
                <div class="auth-visual-header">
                    <img
                        src="{{ asset('images/Logo SNS HD.png') }}"
                        alt="Logo SNS AC"
                        class="auth-visual-logo"
                    >

                    <span>
                        <i class="bi bi-shield-check"></i>
                        Secure Access
                    </span>
                </div>

                <div class="auth-visual-content">
                    <h2>Monitoring operasional yang lebih terkendali.</h2>

                    <p>
                        Akses dashboard berdasarkan role untuk memantau performa
                        ATL, dealer, mekanik, presensi, dan aktivitas operasional.
                    </p>
                </div>

                <div class="auth-dashboard-preview">
                    <div class="auth-preview-topbar">
                        <span></span>
                        <span></span>
                        <span></span>
                    </div>

                    <div class="auth-preview-grid">
                        <div class="auth-preview-stat">
                            <i class="bi bi-diagram-3-fill"></i>
                            <div>
                                <strong>SOH</strong>
                                <small>Area overview</small>
                            </div>
                        </div>

                        <div class="auth-preview-stat">
                            <i class="bi bi-person-badge-fill"></i>
                            <div>
                                <strong>ATL</strong>
                                <small>Wilayah aktif</small>
                            </div>
                        </div>
                    </div>

                    <div class="auth-preview-chart">
                        <span></span>
                        <span></span>
                        <span></span>
                        <span></span>
                        <span></span>
                    </div>

                    <div class="auth-preview-progress">
                        <div>
                            <span>Dealer aktif</span>
                            <strong>86%</strong>
                        </div>

                        <i></i>
                    </div>

                    <div class="auth-preview-progress">
                        <div>
                            <span>Presensi mekanik</span>
                            <strong>92%</strong>
                        </div>

                        <i></i>
                    </div>
                </div>
            </div>

            <div class="auth-card">
                <div class="auth-logo-panel">
                    <img
                        src="{{ asset('images/Logo SNS HD.png') }}"
                        alt="Logo SNS AC"
                    >
                </div>

                <div class="auth-brand">
                    <h1>Monitoring Operasional</h1>
                    <p>Masuk menggunakan NIP dan password untuk mengakses dashboard.</p>
                </div>

                <form method="POST" action="{{ route('login.store') }}">
                    @csrf

                    <div class="mb-3">
                        <label for="nip" class="form-label">NIP</label>

                        <input
                            id="nip"
                            type="text"
                            name="nip"
                            value="{{ old('nip') }}"
                            class="form-control @error('nip') is-invalid @enderror"
                            autocomplete="username"
                            autofocus
                            required
                        >

                        @error('nip')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>

                        <div class="password-field">
                            <input
                                id="password"
                                type="password"
                                name="password"
                                class="form-control @error('password') is-invalid @enderror"
                                autocomplete="current-password"
                                required
                            >

                            <button
                                type="button"
                                class="password-toggle"
                                aria-label="Tampilkan password"
                                data-password-toggle
                            >
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>

                        @error('password')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>

                    <div class="form-check mb-4">
                        <input
                            id="remember"
                            type="checkbox"
                            name="remember"
                            class="form-check-input"
                        >

                        <label for="remember" class="form-check-label">
                            Ingat saya
                        </label>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-box-arrow-in-right me-1"></i>
                        Masuk
                    </button>
                </form>
            </div>
        </section>
    </main>

    <script>
        document.querySelector('[data-password-toggle]')?.addEventListener('click', function () {
            const passwordInput = document.querySelector('#password');
            const icon = this.querySelector('i');
            const showPassword = passwordInput.type === 'password';

            passwordInput.type = showPassword ? 'text' : 'password';
            this.setAttribute(
                'aria-label',
                showPassword ? 'Sembunyikan password' : 'Tampilkan password'
            );
            icon.classList.toggle('bi-eye', ! showPassword);
            icon.classList.toggle('bi-eye-slash', showPassword);
        });
    </script>
</body>

</html>
