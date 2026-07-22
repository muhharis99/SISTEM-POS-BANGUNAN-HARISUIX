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
        $punya = fn (string $izin): bool => (bool) $penggunaAktif?->memilikiHakAkses($izin, $idCabangAktif);
        $bolehPengguna = $punya('PENGGUNA_LIHAT');
        $bolehPeran = $punya('PERAN_LIHAT');
        $bolehAudit = $punya('AUDIT_LIHAT');
        $menuMaster = [
            ['izin' => 'MASTER_BARANG_LIHAT', 'ikon' => 'package-search', 'nama' => 'Barang', 'route' => route('barang.index'), 'aktif' => request()->routeIs('barang.*')],
            ['izin' => 'MASTER_BARANG_LIHAT', 'ikon' => 'folders', 'nama' => 'Kategori Barang', 'route' => route('master.index', ['slug' => 'kategori-barang']), 'aktif' => request()->routeIs('master.*') && request()->route('slug') === 'kategori-barang'],
            ['izin' => 'MASTER_BARANG_LIHAT', 'ikon' => 'badge', 'nama' => 'Merek Barang', 'route' => route('master.index', ['slug' => 'merek-barang']), 'aktif' => request()->routeIs('master.*') && request()->route('slug') === 'merek-barang'],
            ['izin' => 'MASTER_BARANG_LIHAT', 'ikon' => 'ruler', 'nama' => 'Satuan', 'route' => route('master.index', ['slug' => 'satuan']), 'aktif' => request()->routeIs('master.*') && request()->route('slug') === 'satuan'],
            ['izin' => 'MASTER_PELANGGAN_LIHAT', 'ikon' => 'contact', 'nama' => 'Pelanggan', 'route' => route('pelanggan.index'), 'aktif' => request()->routeIs('pelanggan.*')],
            ['izin' => 'MASTER_PELANGGAN_LIHAT', 'ikon' => 'users-round', 'nama' => 'Jenis Pelanggan', 'route' => route('master.index', ['slug' => 'jenis-pelanggan']), 'aktif' => request()->routeIs('master.*') && request()->route('slug') === 'jenis-pelanggan'],
            ['izin' => 'MASTER_PEMASOK_LIHAT', 'ikon' => 'factory', 'nama' => 'Pemasok', 'route' => route('master.index', ['slug' => 'pemasok']), 'aktif' => request()->routeIs('master.*') && request()->route('slug') === 'pemasok'],
            ['izin' => 'MASTER_GUDANG_LIHAT', 'ikon' => 'warehouse', 'nama' => 'Gudang & Lokasi', 'route' => route('gudang.index'), 'aktif' => request()->routeIs('gudang.*')],
            ['izin' => 'DAFTAR_HARGA_LIHAT', 'ikon' => 'tags', 'nama' => 'Daftar Harga', 'route' => route('daftar-harga.index'), 'aktif' => request()->routeIs('daftar-harga.*')],
            ['izin' => 'MASTER_KEUANGAN_LIHAT', 'ikon' => 'landmark', 'nama' => 'Kas & Bank', 'route' => route('master.index', ['slug' => 'kas-bank']), 'aktif' => request()->routeIs('master.*') && request()->route('slug') === 'kas-bank'],
            ['izin' => 'MASTER_KEUANGAN_LIHAT', 'ikon' => 'credit-card', 'nama' => 'Metode Pembayaran', 'route' => route('master.index', ['slug' => 'metode-pembayaran']), 'aktif' => request()->routeIs('master.*') && request()->route('slug') === 'metode-pembayaran'],
            ['izin' => 'MASTER_KEUANGAN_LIHAT', 'ikon' => 'wallet-cards', 'nama' => 'Kategori Biaya', 'route' => route('master.index', ['slug' => 'kategori-biaya']), 'aktif' => request()->routeIs('master.*') && request()->route('slug') === 'kategori-biaya'],
            ['izin' => 'MASTER_ARMADA_LIHAT', 'ikon' => 'truck', 'nama' => 'Armada', 'route' => route('master.index', ['slug' => 'armada']), 'aktif' => request()->routeIs('master.*') && request()->route('slug') === 'armada'],
            ['izin' => 'MASTER_PAJAK_LIHAT', 'ikon' => 'percent', 'nama' => 'Tarif Pajak', 'route' => route('master.index', ['slug' => 'tarif-pajak']), 'aktif' => request()->routeIs('master.*') && request()->route('slug') === 'tarif-pajak'],
        ];
        $adaMaster = collect($menuMaster)->contains(fn (array $item): bool => $punya($item['izin']));
    @endphp

    <div data-simplebar>
        <ul class="side-nav">
            <li class="side-nav-title">Menu utama</li>
            <li class="side-nav-item">
                <a href="{{ route('dashboard') }}" class="side-nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <span class="menu-icon"><i data-lucide="layout-dashboard"></i></span><span class="menu-text">Dashboard</span>
                </a>
            </li>

            @if ($adaMaster)
                <li class="side-nav-title">Master data</li>
                @foreach ($menuMaster as $item)
                    @if ($punya($item['izin']))
                        <li class="side-nav-item">
                            <a href="{{ $item['route'] }}" class="side-nav-link {{ $item['aktif'] ? 'active' : '' }}">
                                <span class="menu-icon"><i data-lucide="{{ $item['ikon'] }}"></i></span><span class="menu-text">{{ $item['nama'] }}</span>
                            </a>
                        </li>
                    @endif
                @endforeach
            @endif

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

            <li class="side-nav-title">Operasional berikutnya</li>
            @foreach ([['boxes', 'Persediaan'], ['shopping-bag', 'Pembelian'], ['shopping-cart', 'Penjualan & POS'], ['chart-no-axes-combined', 'Laporan']] as [$ikon, $nama])
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
