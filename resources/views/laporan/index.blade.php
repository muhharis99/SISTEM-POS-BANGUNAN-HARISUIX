@extends('layouts.admin')

@section('judul', 'Laporan Operasional')

@section('breadcrumb')
    <div class="d-flex align-items-center justify-content-between py-3">
        <div>
            <h4 class="fs-18 fw-semibold mb-1">Laporan Operasional</h4>
            <p class="text-muted mb-0">{{ $judulLaporan }} untuk cabang {{ session('nama_cabang_aktif') }}.</p>
        </div>
        <span class="badge badge-soft-primary fs-sm">Fase 9</span>
    </div>
@endsection

@section('content')
    @php
        $rupiah = fn ($nilai) => 'Rp '.number_format((float) $nilai, 0, ',', '.');
        $angka = fn ($nilai) => number_format((float) $nilai, 3, ',', '.');
    @endphp

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('laporan.index') }}" class="row g-3 align-items-end">
                <div class="col-lg-3 col-md-6">
                    <label class="form-label">Jenis laporan</label>
                    <select name="jenis_laporan" class="form-select" required>
                        @foreach ($daftarJenis as $kode => $nama)
                            <option value="{{ $kode }}" @selected($jenis === $kode)>{{ $nama }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-2 col-md-6">
                    <label class="form-label">Tanggal awal</label>
                    <input type="date" name="tanggal_awal" class="form-control" value="{{ $filter['tanggal_awal'] }}" required>
                </div>
                <div class="col-lg-2 col-md-6">
                    <label class="form-label">Tanggal akhir</label>
                    <input type="date" name="tanggal_akhir" class="form-control" value="{{ $filter['tanggal_akhir'] }}" required>
                </div>
                <div class="col-lg-3 col-md-6">
                    <label class="form-label">Pencarian</label>
                    <input type="text" name="pencarian" class="form-control" maxlength="100" value="{{ $filter['pencarian'] }}" placeholder="Nomor, barang, pelanggan, pemasok...">
                </div>
                <div class="col-lg-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-fill">
                        <i data-lucide="search" class="me-1"></i> Tampilkan
                    </button>
                    @if (auth()->user()->memilikiHakAkses('LAPORAN_OPERASIONAL_UNDUH', session('id_cabang_aktif')))
                        <a href="{{ route('laporan.unduh', $filter) }}" class="btn btn-outline-success" title="Unduh CSV">
                            <i data-lucide="download"></i>
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-xl-3 col-sm-6">
            <div class="card h-100">
                <div class="card-body">
                    <p class="text-muted mb-1">Penjualan periode</p>
                    <h5 class="mb-1">{{ $rupiah($ringkasan['total_penjualan']) }}</h5>
                    <small class="text-muted">{{ number_format($ringkasan['jumlah_penjualan'], 0, ',', '.') }} transaksi</small>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6">
            <div class="card h-100">
                <div class="card-body">
                    <p class="text-muted mb-1">Pembelian periode</p>
                    <h5 class="mb-1">{{ $rupiah($ringkasan['total_pembelian']) }}</h5>
                    <small class="text-muted">{{ number_format($ringkasan['jumlah_pembelian'], 0, ',', '.') }} faktur</small>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6">
            <div class="card h-100">
                <div class="card-body">
                    <p class="text-muted mb-1">Laba kotor periode</p>
                    <h5 class="mb-1">{{ $rupiah($ringkasan['laba_kotor']) }}</h5>
                    <small class="text-muted">Sebelum beban operasional</small>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6">
            <div class="card h-100">
                <div class="card-body">
                    <p class="text-muted mb-1">Saldo kas dan bank</p>
                    <h5 class="mb-1">{{ $rupiah($ringkasan['saldo_kas_bank']) }}</h5>
                    <small class="text-muted">Sampai {{ date('d-m-Y', strtotime($filter['tanggal_akhir'])) }}</small>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between">
            <div>
                <h5 class="card-title mb-1">{{ $judulLaporan }}</h5>
                <p class="text-muted mb-0">Periode {{ date('d-m-Y', strtotime($filter['tanggal_awal'])) }} sampai {{ date('d-m-Y', strtotime($filter['tanggal_akhir'])) }}. Tampilan dibatasi 250 baris.</p>
            </div>
            <span class="badge badge-soft-secondary">{{ number_format($baris->count(), 0, ',', '.') }} baris</span>
        </div>
        <div class="table-responsive">
            @switch($jenis)
                @case('penjualan')
                    <table class="table table-hover align-middle mb-0">
                        <thead><tr><th>Tanggal</th><th>Nomor</th><th>Pelanggan</th><th>Jenis / Status</th><th class="text-end">Total</th><th class="text-end">Dibayar</th><th class="text-end">Piutang</th><th class="text-end">Laba Kotor</th><th></th></tr></thead>
                        <tbody>
                            @forelse ($baris as $item)
                                <tr>
                                    <td>{{ date('d-m-Y H:i', strtotime($item->tanggal_penjualan)) }}</td>
                                    <td class="fw-semibold">{{ $item->nomor_penjualan }}</td>
                                    <td>{{ $item->nama_pelanggan ?: 'Pelanggan Umum' }}</td>
                                    <td><span class="badge badge-soft-info">{{ $item->jenis_penjualan }}</span> <span class="badge badge-soft-success">{{ $item->status_penjualan }}</span></td>
                                    <td class="text-end">{{ $rupiah($item->total_bersih) }}</td>
                                    <td class="text-end">{{ $rupiah($item->total_dibayar) }}</td>
                                    <td class="text-end">{{ $rupiah($item->sisa_piutang) }}</td>
                                    <td class="text-end">{{ $rupiah($item->laba_kotor) }}</td>
                                    <td class="text-end">
                                        @if (auth()->user()->memilikiHakAkses('NOTA_PENJUALAN_CETAK', session('id_cabang_aktif')))
                                            <a href="{{ route('penjualan.nota', $item->id_penjualan) }}" target="_blank" class="btn btn-sm btn-outline-primary" title="Cetak nota"><i data-lucide="printer"></i></a>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="9" class="text-center text-muted py-4">Tidak ada data penjualan pada filter ini.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                    @break

                @case('pembelian')
                    <table class="table table-hover align-middle mb-0">
                        <thead><tr><th>Tanggal</th><th>Nomor Internal</th><th>Nomor Pemasok</th><th>Pemasok</th><th>Cara Bayar</th><th>Status</th><th class="text-end">Total</th><th class="text-end">Dibayar</th><th class="text-end">Hutang</th></tr></thead>
                        <tbody>
                            @forelse ($baris as $item)
                                <tr>
                                    <td>{{ date('d-m-Y', strtotime($item->tanggal_faktur)) }}</td>
                                    <td class="fw-semibold">{{ $item->nomor_faktur_internal }}</td>
                                    <td>{{ $item->nomor_faktur_pemasok }}</td>
                                    <td>{{ $item->nama_pemasok }}</td>
                                    <td><span class="badge badge-soft-info">{{ $item->cara_pembayaran }}</span></td>
                                    <td><span class="badge badge-soft-success">{{ $item->status_faktur }}</span></td>
                                    <td class="text-end">{{ $rupiah($item->total_bersih) }}</td>
                                    <td class="text-end">{{ $rupiah($item->total_dibayar) }}</td>
                                    <td class="text-end">{{ $rupiah($item->sisa_hutang) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="9" class="text-center text-muted py-4">Tidak ada data pembelian pada filter ini.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                    @break

                @case('persediaan')
                    <table class="table table-hover align-middle mb-0">
                        <thead><tr><th>Barang</th><th>Gudang / Lokasi</th><th>Satuan</th><th class="text-end">Stok</th><th class="text-end">Dipesan</th><th class="text-end">Rusak</th><th class="text-end">Tersedia</th><th class="text-end">Minimum</th><th>Kondisi</th></tr></thead>
                        <tbody>
                            @forelse ($baris as $item)
                                @php $menipis = (float) $item->jumlah_tersedia <= (float) $item->stok_minimum; @endphp
                                <tr>
                                    <td><div class="fw-semibold">{{ $item->nama_barang }}</div><small class="text-muted">{{ $item->kode_barang }}</small></td>
                                    <td><div>{{ $item->nama_gudang }}</div><small class="text-muted">{{ $item->kode_lokasi }} — {{ $item->nama_lokasi }}</small></td>
                                    <td>{{ $item->satuan_dasar }}</td>
                                    <td class="text-end">{{ $angka($item->jumlah_stok) }}</td>
                                    <td class="text-end">{{ $angka($item->jumlah_dipesan) }}</td>
                                    <td class="text-end">{{ $angka($item->jumlah_rusak) }}</td>
                                    <td class="text-end fw-semibold">{{ $angka($item->jumlah_tersedia) }}</td>
                                    <td class="text-end">{{ $angka($item->stok_minimum) }}</td>
                                    <td><span class="badge {{ $menipis ? 'badge-soft-danger' : 'badge-soft-success' }}">{{ $menipis ? 'MENIPIS' : 'AMAN' }}</span></td>
                                </tr>
                            @empty
                                <tr><td colspan="9" class="text-center text-muted py-4">Tidak ada data persediaan.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                    @break

                @case('hutang')
                @case('piutang')
                    @php $adalahHutang = $jenis === 'hutang'; @endphp
                    <table class="table table-hover align-middle mb-0">
                        <thead><tr><th>Tanggal</th><th>Jatuh Tempo</th><th>{{ $adalahHutang ? 'Pemasok' : 'Pelanggan' }}</th><th>Nomor Dokumen</th><th class="text-end">Nilai Awal</th><th class="text-end">Pembayaran</th><th class="text-end">Retur</th><th class="text-end">Sisa</th><th>Status / Umur</th></tr></thead>
                        <tbody>
                            @forelse ($baris as $item)
                                <tr>
                                    <td>{{ date('d-m-Y', strtotime($adalahHutang ? $item->tanggal_hutang : $item->tanggal_piutang)) }}</td>
                                    <td>{{ $item->tanggal_jatuh_tempo ? date('d-m-Y', strtotime($item->tanggal_jatuh_tempo)) : '-' }}</td>
                                    <td>{{ $adalahHutang ? $item->nama_pemasok : $item->nama_pelanggan }}</td>
                                    <td class="fw-semibold">{{ $adalahHutang ? $item->nomor_faktur_internal : $item->nomor_penjualan }}</td>
                                    <td class="text-end">{{ $rupiah($item->nilai_awal) }}</td>
                                    <td class="text-end">{{ $rupiah($item->nilai_pembayaran) }}</td>
                                    <td class="text-end">{{ $rupiah($item->nilai_retur) }}</td>
                                    <td class="text-end fw-semibold">{{ $rupiah($adalahHutang ? $item->sisa_hutang : $item->sisa_piutang) }}</td>
                                    <td><span class="badge badge-soft-warning">{{ $adalahHutang ? $item->status_hutang : $item->status_piutang }}</span><br><small class="text-muted">{{ $item->jumlah_hari_terlambat }} hari terlambat</small></td>
                                </tr>
                            @empty
                                <tr><td colspan="9" class="text-center text-muted py-4">Tidak ada data {{ $jenis }} pada filter ini.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                    @break

                @case('kas')
                    <table class="table table-hover align-middle mb-0">
                        <thead><tr><th>Waktu</th><th>Nomor</th><th>Kas / Bank</th><th>Tujuan</th><th>Jenis</th><th class="text-end">Nilai</th><th>Status</th><th>Keterangan</th></tr></thead>
                        <tbody>
                            @forelse ($baris as $item)
                                <tr>
                                    <td>{{ date('d-m-Y H:i', strtotime($item->tanggal_transaksi)) }}</td>
                                    <td class="fw-semibold">{{ $item->nomor_transaksi }}</td>
                                    <td>{{ $item->nama_kas_bank }}</td>
                                    <td>{{ $item->nama_kas_bank_tujuan ?: '-' }}</td>
                                    <td><span class="badge badge-soft-info">{{ $item->jenis_transaksi }}</span></td>
                                    <td class="text-end">{{ $rupiah($item->nilai_transaksi) }}</td>
                                    <td><span class="badge badge-soft-success">{{ $item->status_transaksi }}</span></td>
                                    <td>{{ $item->keterangan }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="8" class="text-center text-muted py-4">Tidak ada transaksi kas pada filter ini.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                    @break
            @endswitch
        </div>
    </div>
@endsection
