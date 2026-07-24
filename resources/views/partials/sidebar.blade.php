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
        $bolehLampiran = $punya('LAMPIRAN_LIHAT');
        $bolehAudit = $punya('AUDIT_LIHAT');
        $jenisLaporan = match (true) {
            $punya('LAPORAN_PENJUALAN_LIHAT') => 'penjualan',
            $punya('LAPORAN_PEMBELIAN_LIHAT') => 'pembelian',
            $punya('LAPORAN_STOK_LIHAT') => 'persediaan',
            $punya('HUTANG_PEMASOK_LIHAT') => 'hutang',
            $punya('LAPORAN_PIUTANG_LIHAT') => 'piutang',
            $punya('LAPORAN_KAS_BANK_LIHAT') => 'kas',
            default => null,
        };
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
        $menuPersediaan = [
            ['izin' => 'PERSEDIAAN_LIHAT', 'ikon' => 'boxes', 'nama' => 'Saldo & Mutasi', 'route' => route('persediaan.index'), 'aktif' => request()->routeIs('persediaan.*')],
            ['izin' => 'STOK_AWAL_KELOLA', 'ikon' => 'package-plus', 'nama' => 'Stok Awal', 'route' => route('stok-awal.index'), 'aktif' => request()->routeIs('stok-awal.*')],
            ['izin' => 'TRANSFER_STOK_KELOLA', 'ikon' => 'arrow-left-right', 'nama' => 'Transfer Stok', 'route' => route('transfer-stok.index'), 'aktif' => request()->routeIs('transfer-stok.*')],
            ['izin' => 'STOK_OPNAME_KELOLA', 'ikon' => 'clipboard-check', 'nama' => 'Stok Opname', 'route' => route('stok-opname.index'), 'aktif' => request()->routeIs('stok-opname.*')],
            ['izin' => 'PENYESUAIAN_STOK_KELOLA', 'ikon' => 'sliders-horizontal', 'nama' => 'Penyesuaian Stok', 'route' => route('penyesuaian-stok.index'), 'aktif' => request()->routeIs('penyesuaian-stok.*')],
        ];
        $adaPersediaan = collect($menuPersediaan)->contains(fn (array $item): bool => $punya($item['izin']));
        $menuPembelian = [
            ['izin' => 'PEMBELIAN_LIHAT', 'ikon' => 'shopping-bag', 'nama' => 'Pembelian', 'route' => route('pembelian.index'), 'aktif' => request()->routeIs('pembelian.*')],
            ['izin' => 'HUTANG_PEMASOK_LIHAT', 'ikon' => 'hand-coins', 'nama' => 'Hutang Pemasok', 'route' => route('hutang-pemasok.index'), 'aktif' => request()->routeIs('hutang-pemasok.*')],
        ];
        $adaPembelian = collect($menuPembelian)->contains(fn (array $item): bool => $punya($item['izin']));
        $menuPenjualan = [
            ['izin' => 'PENJUALAN_LIHAT', 'ikon' => 'shopping-cart', 'nama' => 'Penjualan', 'route' => route('penjualan.index'), 'aktif' => request()->routeIs('penjualan.*') && ! request()->routeIs('penjualan.nota')],
            ['izin' => 'PIUTANG_PELANGGAN_LIHAT', 'ikon' => 'wallet-cards', 'nama' => 'Piutang Pelanggan', 'route' => route('piutang-pelanggan.index'), 'aktif' => request()->routeIs('piutang-pelanggan.*')],
        ];
        $adaPenjualan = collect($menuPenjualan)->contains(fn (array $item): bool => $punya($item['izin']));
        $menuKeuangan = [
            ['izin' => 'KEUANGAN_LIHAT', 'ikon' => 'chart-no-axes-combined', 'nama' => 'Kas & Akuntansi', 'route' => route('keuangan.index'), 'aktif' => request()->routeIs('keuangan.*')],
        ];
        $adaKeuangan = collect($menuKeuangan)->contains(fn (array $item): bool => $punya($item['izin'])) || $jenisLaporan !== null;
    @endphp

    <div data-simplebar>
        <ul class="side-nav">
            <li class="side-nav-title">Menu utama</li>
            <li class="side-nav-item"><a href="{{ route('dashboard') }}" class="side-nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}"><span class="menu-icon"><i data-lucide="layout-dashboard"></i></span><span class="menu-text">Dashboard</span></a></li>

            @if ($adaMaster)
                <li class="side-nav-title">Master data</li>
                @foreach ($menuMaster as $item)
                    @if ($punya($item['izin']))<li class="side-nav-item"><a href="{{ $item['route'] }}" class="side-nav-link {{ $item['aktif'] ? 'active' : '' }}"><span class="menu-icon"><i data-lucide="{{ $item['ikon'] }}"></i></span><span class="menu-text">{{ $item['nama'] }}</span></a></li>@endif
                @endforeach
            @endif

            @if ($adaPersediaan)
                <li class="side-nav-title">Persediaan</li>
                @foreach ($menuPersediaan as $item)
                    @if ($punya($item['izin']))<li class="side-nav-item"><a href="{{ $item['route'] }}" class="side-nav-link {{ $item['aktif'] ? 'active' : '' }}"><span class="menu-icon"><i data-lucide="{{ $item['ikon'] }}"></i></span><span class="menu-text">{{ $item['nama'] }}</span></a></li>@endif
                @endforeach
            @endif

            @if ($adaPembelian)
                <li class="side-nav-title">Pembelian</li>
                @foreach ($menuPembelian as $item)
                    @if ($punya($item['izin']))<li class="side-nav-item"><a href="{{ $item['route'] }}" class="side-nav-link {{ $item['aktif'] ? 'active' : '' }}"><span class="menu-icon"><i data-lucide="{{ $item['ikon'] }}"></i></span><span class="menu-text">{{ $item['nama'] }}</span></a></li>@endif
                @endforeach
            @endif

            @if ($adaPenjualan)
                <li class="side-nav-title">Penjualan</li>
                @foreach ($menuPenjualan as $item)
                    @if ($punya($item['izin']))<li class="side-nav-item"><a href="{{ $item['route'] }}" class="side-nav-link {{ $item['aktif'] ? 'active' : '' }}"><span class="menu-icon"><i data-lucide="{{ $item['ikon'] }}"></i></span><span class="menu-text">{{ $item['nama'] }}</span></a></li>@endif
                @endforeach
            @endif

            @if ($adaKeuangan)
                <li class="side-nav-title">Keuangan & laporan</li>
                @foreach ($menuKeuangan as $item)
                    @if ($punya($item['izin']))<li class="side-nav-item"><a href="{{ $item['route'] }}" class="side-nav-link {{ $item['aktif'] ? 'active' : '' }}"><span class="menu-icon"><i data-lucide="{{ $item['ikon'] }}"></i></span><span class="menu-text">{{ $item['nama'] }}</span></a></li>@endif
                @endforeach
                @if ($jenisLaporan !== null)
                    <li class="side-nav-item"><a href="{{ route('laporan.index', ['jenis_laporan' => $jenisLaporan]) }}" class="side-nav-link {{ request()->routeIs('laporan.*') ? 'active' : '' }}"><span class="menu-icon"><i data-lucide="file-chart-column"></i></span><span class="menu-text">Laporan Operasional</span></a></li>
                @endif
            @endif

            @if ($bolehPengguna || $bolehPeran)
                <li class="side-nav-title">Organisasi & akses</li>
                @if ($bolehPengguna)<li class="side-nav-item"><a href="{{ route('pengguna.index') }}" class="side-nav-link {{ request()->routeIs('pengguna.*') ? 'active' : '' }}"><span class="menu-icon"><i data-lucide="users"></i></span><span class="menu-text">Pengguna</span></a></li>@endif
                @if ($bolehPeran)<li class="side-nav-item"><a href="{{ route('peran.index') }}" class="side-nav-link {{ request()->routeIs('peran.*') ? 'active' : '' }}"><span class="menu-icon"><i data-lucide="shield-check"></i></span><span class="menu-text">Peran & Hak Akses</span></a></li>@endif
            @endif

            <li class="side-nav-title">Sistem</li>
            @if ($bolehLampiran)<li class="side-nav-item"><a href="{{ route('lampiran.index') }}" class="side-nav-link {{ request()->routeIs('lampiran.*') ? 'active' : '' }}"><span class="menu-icon"><i data-lucide="paperclip"></i></span><span class="menu-text">Lampiran Dokumen</span></a></li>@endif
            @if ($bolehAudit)<li class="side-nav-item"><a href="{{ route('audit.index') }}" class="side-nav-link {{ request()->routeIs('audit.*') ? 'active' : '' }}"><span class="menu-icon"><i data-lucide="history"></i></span><span class="menu-text">Audit Aktivitas</span></a></li>@endif
            <li class="side-nav-item"><a href="{{ route('panduan.index') }}" class="side-nav-link {{ request()->routeIs('panduan.*') ? 'active' : '' }}"><span class="menu-icon"><i data-lucide="life-buoy"></i></span><span class="menu-text">Pusat Bantuan</span></a></li>
            <li class="side-nav-item"><a href="{{ route('profil') }}" class="side-nav-link {{ request()->routeIs('profil*') ? 'active' : '' }}"><span class="menu-icon"><i data-lucide="user-cog"></i></span><span class="menu-text">Profil Saya</span></a></li>
        </ul>
    </div>
</div>
