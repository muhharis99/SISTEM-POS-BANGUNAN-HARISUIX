<!doctype html>
<html lang="id">
<head>
    @include('partials.head')
</head>
<body class="authentication-bg position-relative">
    <div class="account-pages pt-2 pt-sm-5 pb-4 pb-sm-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-xl-5 col-lg-6 col-md-8">
                    <div class="card">
                        <div class="card-body p-4">
                            <div class="text-center mb-4">
                                <h3 class="mb-1">Sistem POS Toko Bangunan</h3>
                                <p class="text-muted mb-0">Masuk menggunakan akun yang telah diberikan.</p>
                            </div>

                            @if ($errors->any())
                                <div class="alert alert-danger">{{ $errors->first() }}</div>
                            @endif

                            @if (session('berhasil'))
                                <div class="alert alert-success">{{ session('berhasil') }}</div>
                            @endif

                            <form method="POST" action="{{ route('masuk.proses') }}">
                                @csrf
                                <div class="mb-3">
                                    <label for="nama_pengguna" class="form-label">Nama Pengguna</label>
                                    <input class="form-control" id="nama_pengguna" name="nama_pengguna" value="{{ old('nama_pengguna') }}" autocomplete="username" required autofocus>
                                </div>
                                <div class="mb-3">
                                    <label for="kata_sandi" class="form-label">Kata Sandi</label>
                                    <input type="password" class="form-control" id="kata_sandi" name="kata_sandi" autocomplete="current-password" required>
                                </div>
                                <div class="alert alert-light border small">Session berakhir otomatis sesuai kebijakan aplikasi. Fitur “ingat saya” tidak digunakan karena skema paten tidak memiliki token persisten.</div>
                                <button type="submit" class="btn btn-primary w-100">Masuk</button>
                            </form>
                        </div>
                    </div>
                    <p class="text-center text-muted">HARISUIX · Autentikasi aman tanpa CDN eksternal</p>
                </div>
            </div>
        </div>
    </div>
    @include('partials.scripts')
</body>
</html>
