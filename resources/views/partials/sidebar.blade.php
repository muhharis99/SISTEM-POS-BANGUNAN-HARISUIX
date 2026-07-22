<div class="sidenav-menu">
    <a href="{{ route('dashboard') }}" class="logo">
        <span class="logo-light">
            <span class="logo-lg"><img src="{{ asset('assets/admin/images/logo.png') }}" alt="Logo"></span>
            <span class="logo-sm"><img src="{{ asset('assets/admin/images/logo-sm.png') }}" alt="Logo kecil"></span>
        </span>
        <span class="logo-dark">
            <span class="logo-lg"><img src="{{ asset('assets/admin/images/logo-black.png') }}" alt="Logo"></span>
            <span class="logo-sm"><img src="{{ asset('assets/admin/images/logo-sm.png') }}" alt="Logo kecil"></span>
        </span>
    </a>

    <button class="button-sm-hover" type="button" aria-label="Perkecil sidebar">
        <i class="ri-circle-line align-middle"></i>
    </button>

    <button class="button-close-fullsidebar" type="button" aria-label="Tutup sidebar">
        <i data-lucide="x" class="align-middle"></i>
    </button>

    <div data-simplebar>
        <ul class="side-nav">
            <li class="side-nav-title">Menu utama</li>

            <li class="side-nav-item">
                <a href="{{ route('dashboard') }}" class="side-nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <span class="menu-icon"><i data-lucide="layout-dashboard"></i></span>
                    <span class="menu-text">Dashboard</span>
                </a>
            </li>

            <li class="side-nav-title">Organisasi & akses</li>
            <li class="side-nav-item">
                <a data-bs-toggle="collapse" href="#menu-organisasi" aria-expanded="false" aria-controls="menu-organisasi" class="side-nav-link">
                    <span class="menu-icon"><i data-lucide="users-round"></i></span>
                    <span class="menu-text">Organisasi & Akses</span>
                    <span class="menu-arrow"></span>
                </a>
                <div class="collapse" id="menu-organisasi">
                    <ul class="sub-menu">
                        <li class="side-nav-item"><span class="side-nav-link text-muted">Cabang</span></li>
                        <li class="side-nav-item"><span class="side-nav-link text-muted">Pegawai</span></li>
                        <li class="side-nav-item"><span class="side-nav-link text-muted">Pengguna</span></li>
                        <li class="side-nav-item"><span class="side-nav-link text-muted">Peran & Hak Akses</span></li>
                    </ul>
                </div>
            </li>

            <li class="side-nav-title">Operasional</li>
            @php
                $menuModul = [
                    ['id' => 'menu-master', 'ikon' => 'package-search', 'nama' => 'Master Data', 'anak' => ['Barang', 'Pelanggan', 'Pemasok', 'Gudang', 'Kas & Bank']],
                    ['id' => 'menu-persediaan', 'ikon' => 'warehouse', 'nama' => 'Persediaan', 'anak' => ['Saldo Stok', 'Mutasi Stok', 'Stok Awal', 'Transfer', 'Stok Opname']],
                    ['id' => 'menu-pembelian', 'ikon' => 'shopping-bag', 'nama' => 'Pembelian', 'anak' => ['Permintaan', 'Pesanan Pembelian', 'Penerimaan', 'Faktur', 'Hutang']],
                    ['id' => 'menu-penjualan', 'ikon' => 'shopping-cart', 'nama' => 'Penjualan & POS', 'anak' => ['Penawaran', 'Pesanan Penjualan', 'Kasir / POS', 'Piutang']],
                    ['id' => 'menu-pengiriman', 'ikon' => 'truck', 'nama' => 'Pengiriman & Retur', 'anak' => ['Pengiriman', 'Retur Pembelian', 'Retur Penjualan']],
                    ['id' => 'menu-keuangan', 'ikon' => 'landmark', 'nama' => 'Keuangan', 'anak' => ['Transaksi Kas', 'Akun Keuangan', 'Jurnal Umum']],
                    ['id' => 'menu-laporan', 'ikon' => 'chart-no-axes-combined', 'nama' => 'Laporan', 'anak' => ['Penjualan', 'Pembelian', 'Persediaan', 'Keuangan', 'Audit']],
                ];
            @endphp

            @foreach ($menuModul as $menu)
                <li class="side-nav-item">
                    <a data-bs-toggle="collapse" href="#{{ $menu['id'] }}" aria-expanded="false" aria-controls="{{ $menu['id'] }}" class="side-nav-link">
                        <span class="menu-icon"><i data-lucide="{{ $menu['ikon'] }}"></i></span>
                        <span class="menu-text">{{ $menu['nama'] }}</span>
                        <span class="menu-arrow"></span>
                    </a>
                    <div class="collapse" id="{{ $menu['id'] }}">
                        <ul class="sub-menu">
                            @foreach ($menu['anak'] as $anak)
                                <li class="side-nav-item">
                                    <span class="side-nav-link text-muted">{{ $anak }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </li>
            @endforeach

            <li class="side-nav-title">Sistem</li>
            <li class="side-nav-item">
                <span class="side-nav-link text-muted">
                    <span class="menu-icon"><i data-lucide="settings"></i></span>
                    <span class="menu-text">Pengaturan</span>
                </span>
            </li>
        </ul>
    </div>
</div>
