<header class="app-topbar">
    <div class="container-fluid topbar-menu">
        <div class="d-flex align-items-center gap-2">
            <div class="logo-topbar">
                <a href="{{ route('dashboard') }}" class="logo-light"><span class="logo-lg"><img src="{{ asset('assets/admin/images/logo.png') }}" alt="Logo" height="30"></span><span class="logo-sm"><img src="{{ asset('assets/admin/images/logo-sm.png') }}" alt="Logo kecil" height="30"></span></a>
                <a href="{{ route('dashboard') }}" class="logo-dark"><span class="logo-lg"><img src="{{ asset('assets/admin/images/logo-black.png') }}" alt="Logo" height="30"></span><span class="logo-sm"><img src="{{ asset('assets/admin/images/logo-sm.png') }}" alt="Logo kecil" height="30"></span></a>
            </div>
            <button class="sidenav-toggle-button btn btn-default btn-icon" type="button" aria-label="Buka atau tutup menu"><i data-lucide="menu"></i></button>
            <div class="topbar-item d-none d-md-flex"><span class="topbar-link fw-semibold">Sistem POS Toko Bangunan</span></div>
        </div>

        <div class="d-flex align-items-center gap-1">
            @if (isset($cabangTersedia) && $cabangTersedia->count() > 1)
                <div class="topbar-item d-none d-md-flex">
                    <form method="POST" action="{{ route('cabang-aktif.ubah') }}" class="d-flex align-items-center">
                        @csrf
                        <select name="id_cabang" class="form-select form-select-sm" onchange="this.form.submit()" aria-label="Pilih cabang aktif">
                            @foreach ($cabangTersedia as $itemCabang)
                                <option value="{{ $itemCabang->id_cabang }}" @selected((int) session('id_cabang_aktif') === (int) $itemCabang->id_cabang)>{{ $itemCabang->nama_cabang }}</option>
                            @endforeach
                        </select>
                    </form>
                </div>
            @else
                <div class="topbar-item d-none d-md-flex"><span class="topbar-link text-muted"><i data-lucide="building-2" class="me-1"></i>{{ session('nama_cabang_aktif') }}</span></div>
            @endif

            <div id="fullscreen-toggler" class="topbar-item d-none d-md-flex"><button class="topbar-link" type="button" data-toggle="fullscreen" aria-label="Layar penuh"><i data-lucide="maximize" class="topbar-link-icon"></i><i data-lucide="minimize" class="topbar-link-icon d-none"></i></button></div>
            <div class="topbar-item d-none d-sm-flex"><button class="topbar-link btn-theme-setting" data-bs-toggle="offcanvas" data-bs-target="#theme-settings-offcanvas" type="button" aria-label="Pengaturan tema"><i data-lucide="settings" class="topbar-link-icon"></i></button></div>

            <div class="topbar-item nav-user">
                <div class="dropdown">
                    <a class="topbar-link dropdown-toggle drop-arrow-none px-2" data-bs-toggle="dropdown" href="#" aria-expanded="false">
                        <span class="avatar-sm rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center me-lg-2"><i data-lucide="user-round"></i></span>
                        <div class="d-lg-flex align-items-center gap-1 d-none"><h5 class="my-0">{{ auth()->user()->nama_tampilan }}</h5><i data-lucide="chevron-down" class="align-middle"></i></div>
                    </a>
                    <div class="dropdown-menu dropdown-menu-end">
                        <div class="dropdown-header noti-title"><h6 class="text-overflow m-0">{{ auth()->user()->nama_pengguna }}</h6></div>
                        <a href="{{ route('profil') }}" class="dropdown-item"><i data-lucide="user-cog" class="me-2"></i>Profil Saya</a>
                        <div class="dropdown-divider"></div>
                        <form method="POST" action="{{ route('keluar') }}">@csrf<button type="submit" class="dropdown-item text-danger"><i data-lucide="log-out" class="me-2"></i>Keluar</button></form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>
