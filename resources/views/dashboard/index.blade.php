@extends('layouts.admin')

@section('judul', 'Dashboard')

@section('breadcrumb')
    <div class="d-flex align-items-center justify-content-between py-3">
        <div>
            <h4 class="fs-18 fw-semibold mb-1">Dashboard</h4>
            <p class="text-muted mb-0">Selamat datang, {{ auth()->user()->nama_tampilan }}.</p>
        </div>
        <span class="badge badge-soft-primary fs-sm">{{ session('nama_cabang_aktif') }}</span>
    </div>
@endsection

@section('content')
    @php
        $rupiah = fn ($nilai) => 'Rp '.number_format((float) $nilai, 0, ',', '.');
        $angka = fn ($nilai) => rtrim(rtrim(number_format((float) $nilai, 3, ',', '.'), '0'), ',');
        $nilaiTrenTertinggi = max(1, (float) $trenPenjualan->max('total_penjualan'));
        $bolehLaporan = collect([
            'LAPORAN_PENJUALAN_LIHAT',
            'LAPORAN_PEMBELIAN_LIHAT',
            'LAPORAN_PERSEDIAAN_LIHAT',
            'LAPORAN_HUTANG_PIUTANG_LIHAT',
            'KEUANGAN_LIHAT',
        ])->contains(fn (string $izin): bool => auth()->user()->memilikiHakAkses($izin, session('id_cabang_aktif')));
    @endphp

    @if ($bolehRingkasanBisnis)
        <div class="card mb-3">
            <div class="card-body">
                <form method="GET" action="{{ route('dashboard') }}" class="row g-3 align-items-end">
                    <input type="hidden" name="jenis_laporan" value="penjualan">
                    <div class="col-md-4 col-lg-3">
                        <label class="form-label">Tanggal awal</label>
                        <input type="date" name="tanggal_awal" class="form-control" value="{{ $filterLaporan['tanggal_awal'] }}" required>
                    </div>
                    <div class="col-md-4 col-lg-3">
                        <label class="form-label">Tanggal akhir</label>
                        <input type="date" name="tanggal_akhir" class="form-control" value="{{ $filterLaporan['tanggal_akhir'] }}" required>
                    </div>
                    <div class="col-md-4 col-lg-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <i data-lucide="calendar-search" class="me-1"></i> Terapkan
                        </button>
                    </div>
                    <div class="col-lg-4 text-lg-end">
                        @if ($bolehLaporan)
                            <a href="{{ route('laporan.index', ['jenis_laporan' => 'penjualan', 'tanggal_awal' => $filterLaporan['tanggal_awal'], 'tanggal_akhir' => $filterLaporan['tanggal_akhir']]) }}" class="btn btn-outline-primary">
                                <i data-lucide="file-chart-column" class="me-1"></i> Pusat Laporan
                            </a>
                        @endif
                    </div>
                </form>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-xxl-3 col-xl-4 col-sm-6">
                <div class="card h-100">
                    <div class="card-body d-flex align-items-center gap-3">
                        <div class="avatar-md rounded bg-primary-subtle text-primary d-flex align-items-center justify-content-center"><i data-lucide="badge-dollar-sign"></i></div>
                        <div><p class="text-muted mb-1">Penjualan Periode</p><h5 class="mb-0">{{ $rupiah($ringkasanBisnis['total_penjualan']) }}</h5><small class="text-muted">{{ number_format($ringkasanBisnis['jumlah_penjualan'], 0, ',', '.') }} transaksi</small></div>
                    </div>
                </div>
            </div>
            <div class="col-xxl-3 col-xl-4 col-sm-6">
                <div class="card h-100">
                    <div class="card-body d-flex align-items-center gap-3">
                        <div class="avatar-md rounded bg-info-subtle text-info d-flex align-items-center justify-content-center"><i data-lucide="shopping-bag"></i></div>
                        <div><p class="text-muted mb-1">Pembelian Periode</p><h5 class="mb-0">{{ $rupiah($ringkasanBisnis['total_pembelian']) }}</h5><small class="text-muted">{{ number_format($ringkasanBisnis['jumlah_pembelian'], 0, ',', '.') }} faktur</small></div>
                    </div>
                </div>
            </div>
            <div class="col-xxl-3 col-xl-4 col-sm-6">
                <div class="card h-100">
                    <div class="card-body d-flex align-items-center gap-3">
                        <div class="avatar-md rounded bg-success-subtle text-success d-flex align-items-center justify-content-center"><i data-lucide="trending-up"></i></div>
                        <div><p class="text-muted mb-1">Laba Kotor</p><h5 class="mb-0">{{ $rupiah($ringkasanBisnis['laba_kotor']) }}</h5><small class="text-muted">Sebelum beban operasional</small></div>
                    </div>
                </div>
            </div>
            <div class="col-xxl-3 col-xl-4 col-sm-6">
                <div class="card h-100">
                    <div class="card-body d-flex align-items-center gap-3">
                        <div class="avatar-md rounded bg-secondary-subtle text-secondary d-flex align-items-center justify-content-center"><i data-lucide="landmark"></i></div>
                        <div><p class="text-muted mb-1">Saldo Kas & Bank</p><h5 class="mb-0">{{ $rupiah($ringkasanBisnis['saldo_kas_bank']) }}</h5><small class="text-muted">Sampai {{ date('d-m-Y', strtotime($filterLaporan['tanggal_akhir'])) }}</small></div>
                    </div>
                </div>
            </div>
            <div class="col-xxl-3 col-xl-4 col-sm-6">
                <div class="card h-100">
                    <div class="card-body d-flex align-items-center gap-3">
                        <div class="avatar-md rounded bg-warning-subtle text-warning d-flex align-items-center justify-content-center"><i data-lucide="hand-coins"></i></div>
                        <div><p class="text-muted mb-1">Sisa Hutang</p><h5 class="mb-0">{{ $rupiah($ringkasanBisnis['sisa_hutang']) }}</h5><small class="text-muted">Seluruh hutang aktif cabang</small></div>
                    </div>
                </div>
            </div>
            <div class="col-xxl-3 col-xl-4 col-sm-6">
                <div class="card h-100">
                    <div class="card-body d-flex align-items-center gap-3">
                        <div class="avatar-md rounded bg-danger-subtle text-danger d-flex align-items-center justify-content-center"><i data-lucide="wallet-cards"></i></div>
                        <div><p class="text-muted mb-1">Sisa Piutang</p><h5 class="mb-0">{{ $rupiah($ringkasanBisnis['sisa_piutang']) }}</h5><small class="text-muted">Seluruh piutang aktif cabang</small></div>
                    </div>
                </div>
            </div>
            <div class="col-xxl-3 col-xl-4 col-sm-6">
                <div class="card h-100">
                    <div class="card-body d-flex align-items-center gap-3">
                        <div class="avatar-md rounded bg-danger-subtle text-danger d-flex align-items-center justify-content-center"><i data-lucide="package-minus"></i></div>
                        <div><p class="text-muted mb-1">Stok Menipis</p><h5 class="mb-0">{{ number_format($ringkasanBisnis['stok_menipis'], 0, ',', '.') }} barang</h5><small class="text-muted">Tersedia ≤ stok minimum</small></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-xl-7">
                <div class="card h-100">
                    <div class="card-header">
                        <h5 class="card-title mb-1">Tren Penjualan Harian</h5>
                        <p class="text-muted mb-0">Periode {{ date('d-m-Y', strtotime($filterLaporan['tanggal_awal'])) }} sampai {{ date('d-m-Y', strtotime($filterLaporan['tanggal_akhir'])) }}</p>
                    </div>
                    <div class="card-body">
                        @forelse ($trenPenjualan as $item)
                            @php $persen = max(2, min(100, ((float) $item->total_penjualan / $nilaiTrenTertinggi) * 100)); @endphp
                            <div class="mb-3">
                                <div class="d-flex justify-content-between gap-3 mb-1">
                                    <span>{{ date('d-m-Y', strtotime($item->tanggal)) }} <small class="text-muted">({{ number_format($item->jumlah_transaksi, 0, ',', '.') }} transaksi)</small></span>
                                    <strong>{{ $rupiah($item->total_penjualan) }}</strong>
                                </div>
                                <div class="progress" style="height: 8px;">
                                    <div class="progress-bar" role="progressbar" style="width: {{ $persen }}%;" aria-valuenow="{{ $persen }}" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                            </div>
                        @empty
                            <div class="text-center text-muted py-5">Belum ada penjualan pada periode ini.</div>
                        @endforelse
                    </div>
                </div>
            </div>
            <div class="col-xl-5">
                <div class="card h-100">
                    <div class="card-header"><h5 class="card-title mb-0">10 Barang Terlaris</h5></div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead><tr><th>Barang</th><th class="text-end">Jumlah</th><th class="text-end">Nilai</th></tr></thead>
                            <tbody>
                                @forelse ($barangTerlaris as $item)
                                    <tr>
                                        <td><div class="fw-semibold">{{ $item->nama_barang }}</div><small class="text-muted">{{ $item->kode_barang }}</small></td>
                                        <td class="text-end">{{ $angka($item->jumlah_dasar) }} {{ $item->kode_satuan }}</td>
                                        <td class="text-end">{{ $rupiah($item->nilai_penjualan) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="text-center text-muted py-4">Belum ada data barang terjual.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <div class="row g-3 mb-4">
        <div class="col-xl col-sm-6"><div class="card h-100"><div class="card-body d-flex align-items-center gap-3"><div class="avatar-md rounded bg-primary-subtle text-primary d-flex align-items-center justify-content-center"><i data-lucide="package-search"></i></div><div><p class="text-muted mb-1">Barang Aktif</p><h4 class="mb-0">{{ number_format($jumlahBarangAktif, 0, ',', '.') }}</h4></div></div></div></div>
        <div class="col-xl col-sm-6"><div class="card h-100"><div class="card-body d-flex align-items-center gap-3"><div class="avatar-md rounded bg-success-subtle text-success d-flex align-items-center justify-content-center"><i data-lucide="contact"></i></div><div><p class="text-muted mb-1">Pelanggan Aktif</p><h4 class="mb-0">{{ number_format($jumlahPelangganAktif, 0, ',', '.') }}</h4></div></div></div></div>
        <div class="col-xl col-sm-6"><div class="card h-100"><div class="card-body d-flex align-items-center gap-3"><div class="avatar-md rounded bg-info-subtle text-info d-flex align-items-center justify-content-center"><i data-lucide="warehouse"></i></div><div><p class="text-muted mb-1">Gudang Cabang</p><h4 class="mb-0">{{ number_format($jumlahGudangAktif, 0, ',', '.') }}</h4></div></div></div></div>
        <div class="col-xl col-sm-6"><div class="card h-100"><div class="card-body d-flex align-items-center gap-3"><div class="avatar-md rounded bg-warning-subtle text-warning d-flex align-items-center justify-content-center"><i data-lucide="tags"></i></div><div><p class="text-muted mb-1">Daftar Harga Berlaku</p><h4 class="mb-0">{{ number_format($jumlahDaftarHargaAktif, 0, ',', '.') }}</h4></div></div></div></div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-lg-6"><div class="card h-100"><div class="card-body d-flex align-items-center gap-3"><div class="avatar-md rounded bg-secondary-subtle text-secondary d-flex align-items-center justify-content-center"><i data-lucide="users"></i></div><div><p class="text-muted mb-1">Pengguna Aktif</p><h4 class="mb-0">{{ number_format($jumlahPenggunaAktif, 0, ',', '.') }}</h4></div></div></div></div>
        <div class="col-lg-6"><div class="card h-100"><div class="card-body d-flex align-items-center gap-3"><div class="avatar-md rounded bg-dark-subtle text-dark d-flex align-items-center justify-content-center"><i data-lucide="shield-check"></i></div><div><p class="text-muted mb-1">Peran Saya</p><h6 class="mb-0">{{ $daftarPeran->join(', ') ?: 'Belum ditetapkan' }}</h6></div></div></div></div>
    </div>

    <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between"><h5 class="card-title mb-0">Aktivitas Terbaru</h5>@if (auth()->user()->memilikiHakAkses('AUDIT_LIHAT', session('id_cabang_aktif')))<a href="{{ route('audit.index') }}" class="btn btn-sm btn-outline-primary">Lihat Semua</a>@endif</div>
        <div class="table-responsive"><table class="table table-hover mb-0"><thead><tr><th>Waktu</th><th>Modul</th><th>Aktivitas</th><th>Keterangan</th><th>IP</th></tr></thead><tbody>
            @forelse ($aktivitasTerbaru as $item)<tr><td>{{ optional($item->tanggal_aktivitas)->format('d-m-Y H:i:s') }}</td><td>{{ $item->nama_modul }}</td><td><span class="badge badge-soft-primary">{{ $item->jenis_aktivitas }}</span></td><td>{{ $item->keterangan ?: '-' }}</td><td>{{ $item->alamat_ip ?: '-' }}</td></tr>@empty<tr><td colspan="5" class="text-center text-muted py-4">Belum ada aktivitas.</td></tr>@endforelse
        </tbody></table></div>
    </div>
@endsection
