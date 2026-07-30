<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Dashboard Monitoring Operasional</title>
    <link rel="preconnect" href="https://fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('mazer/assets/css/bootstrap.css') }}">
    <link rel="stylesheet" href="{{ asset('mazer/assets/vendors/bootstrap-icons/bootstrap-icons.css') }}">
    <link rel="stylesheet" href="{{ asset('css/operational-dashboard.css') }}">
</head>

<body>
    <main class="nova-card-login-page">
        <div class="nova-card-bg nova-card-bg-one"></div>
        <div class="nova-card-bg nova-card-bg-two"></div>

        <section class="nova-card-login-shell">
            <div class="nova-card-visual">
                <nav class="nova-card-brand">
                    <img src="{{ asset('images/Logo SNS HD.png') }}" alt="Logo SNS AC">
                    <span>Monitoring Operasional</span>
                </nav>

                <div class="nova-card-copy">
                    <span>ATL &amp; SOH Workspace</span>
                    <h1>Monitor operasional dalam satu dashboard.</h1>
                    <p>Dealer, mekanik, presensi, precheck/postcheck, potensi, laporan, dan notifikasi tersaji lebih cepat untuk pengambilan keputusan.</p>
                </div>

                <div class="nova-card-illustration">
                    <img src="{{ asset('images/nova-hero-img.svg') }}" alt="Ilustrasi dashboard monitoring">
                </div>

                <div class="nova-card-metrics">
                    <div><strong>605</strong><span>Dealer</span></div>
                    <div><strong>1.315</strong><span>Mekanik</span></div>
                    <div><strong>92%</strong><span>Siap Pantau</span></div>
                </div>
            </div>

            <div class="nova-card-form-panel">
                <div class="nova-form-header">
                    <span>Secure Login</span>
                    <h2>Masuk</h2>
                    <p>Gunakan NIP dan password untuk membuka dashboard sesuai role.</p>
                </div>

                <form method="POST" action="{{ route('login.store') }}">
                    @csrf

                    <div class="nova-field mb-3">
                        <label for="nip">NIP</label>
                        <div class="nova-input">
                            <i class="bi bi-person"></i>
                            <input
                                id="nip"
                                type="text"
                                name="nip"
                                value="{{ old('nip') }}"
                                class="form-control @error('nip') is-invalid @enderror"
                                placeholder="Masukkan NIP"
                                autocomplete="username"
                                autofocus
                                required
                            >
                        </div>
                        @error('nip')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>

                    <div class="nova-field mb-3">
                        <label for="password">Password</label>
                        <div class="nova-input">
                            <i class="bi bi-lock"></i>
                            <input
                                id="password"
                                type="password"
                                name="password"
                                class="form-control @error('password') is-invalid @enderror"
                                placeholder="Masukkan password"
                                autocomplete="current-password"
                                required
                            >
                            <button type="button" class="nova-password-toggle" aria-label="Tampilkan password" data-password-toggle><i class="bi bi-eye"></i></button>
                        </div>
                        @error('password')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div class="form-check">
                            <input id="remember" type="checkbox" name="remember" class="form-check-input">
                            <label for="remember" class="form-check-label">Ingat saya</label>
                        </div>
                        <span class="nova-helper">WIB</span>
                    </div>

                    <button type="submit" class="btn nova-login-button w-100">Masuk Dashboard <i class="bi bi-arrow-right ms-1"></i></button>
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
            this.setAttribute('aria-label', showPassword ? 'Sembunyikan password' : 'Tampilkan password');
            icon.classList.toggle('bi-eye', ! showPassword);
            icon.classList.toggle('bi-eye-slash', showPassword);
        });
    </script>
</body>

</html>
