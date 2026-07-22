<div class="sidenav-menu">
    <a href="{{ route('dashboard') }}" class="logo">
        <span class="logo-light"><span class="logo-lg"><img src="{{ asset('assets/admin/images/logo.png') }}" alt="Logo"></span><span class="logo-sm"><img src="{{ asset('assets/admin/images/logo-sm.png') }}" alt="Logo kecil"></span></span>
        <span class="logo-dark"><span class="logo-lg"><img src="{{ asset('assets/admin/images/logo-black.png') }}" alt="Logo"></span><span class="logo-sm"><img src="{{ asset('assets/admin/images/logo-sm.png') }}" alt="Logo kecil"></span></span>
    </a>

    <button class="button-sm-hover" type="button" aria-label="Perkecil sidebar"><i class="ri-circle-line align-middle"></i></button>
    <button class="button-close-fullsidebar" type="button" aria-label="Tutup sidebar"><i data-lucide="x" class="align-middle"></i></button>

    @php
        $penggunaAktif = auth()->user();
        $idCabangAktif = session('id_cabang_aktif');
        $bolehPengguna = $penggunaAktif?->memilikiHakAkses('PENGGUNA_LIHAT', $idCabangAktif);
        $bolehPeran = $penggunaAktif?->memilikiHakAkses('PERAN_LIHAT', $idCabangAktif);
        $bolehAudit = $penggunaAktif?->memilikiHakAkses('AUDIT_LIHAT', $idCabangAktif);
    @endphp

    <div data-simplebar>
        <ul class="side-nav">
            <li class="side-nav-title">Menu utama</li>
            <li class="side-nav-item">
                <a href="{{ route('dashboard') }}" class="side-nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <span class="menu-icon"><i data-lucide="layout-dashboard"></i></span><span class="menu-text">Dashboard</span>
                </a>
            </li>

            @if ($bolehPengguna || $bolehPeran)
                <li class="side-nav-title">Organisasi & akses</li>
                @if ($bolehPengguna)
                    <li class="side-nav-item">
                        <a href="{{ route('pengguna.index') }}" class="side-nav-link {{ request()->routeIs('pengguna.*') ? 'active' : '' }}">
                            <span class="menu-icon"><i data-lucide="users"></i></span><span class="menu-text">Pengguna</span>
                        </a>
                    </li>
                @endif
                @if ($bolehPeran)
                    <li class="side-nav-item">
                        <a href="{{ route('peran.index') }}" class="side-nav-link {{ request()->routeIs('peran.*') ? 'active' : '' }}">
                            <span class="menu-icon"><i data-lucide="shield-check"></i></span><span class="menu-text">Peran & Hak Akses</span>
                        </a>
                    </li>
                @endif
            @endif

            <li class="side-nav-title">Operasional</li>
            @foreach ([
                ['package-search', 'Master Data'], ['warehouse', 'Persediaan'], ['shopping-bag', 'Pembelian'],
                ['shopping-cart', 'Penjualan & POS'], ['truck', 'Pengiriman & Retur'], ['landmark', 'Keuangan'],
                ['chart-no-axes-combined', 'Laporan'],
            ] as [$ikon, $nama])
                <li class="side-nav-item"><span class="side-nav-link text-muted"><span class="menu-icon"><i data-lucide="{{ $ikon }}"></i></span><span class="menu-text">{{ $nama }}</span></span></li>
            @endforeach

            <li class="side-nav-title">Sistem</li>
            @if ($bolehAudit)
                <li class="side-nav-item">
                    <a href="{{ route('audit.index') }}" class="side-nav-link {{ request()->routeIs('audit.*') ? 'active' : '' }}">
                        <span class="menu-icon"><i data-lucide="history"></i></span><span class="menu-text">Audit Aktivitas</span>
                    </a>
                </li>
            @endif
            <li class="side-nav-item">
                <a href="{{ route('profil') }}" class="side-nav-link {{ request()->routeIs('profil*') ? 'active' : '' }}">
                    <span class="menu-icon"><i data-lucide="user-cog"></i></span><span class="menu-text">Profil Saya</span>
                </a>
            </li>
        </ul>
    </div>
</div>
