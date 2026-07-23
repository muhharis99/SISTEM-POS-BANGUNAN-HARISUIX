@extends('layouts.admin')

@section('judul', 'Penjualan')

@section('breadcrumb')
    <div class="d-flex align-items-center justify-content-between py-3">
        <div>
            <h4 class="fs-18 fw-semibold mb-1">Penjualan, Piutang, dan Pengiriman</h4>
            <p class="text-muted mb-0">Penawaran sampai retur penjualan terintegrasi dengan stok dan piutang pelanggan.</p>
        </div>
        <a class="btn btn-outline-primary" href="{{ route('piutang-pelanggan.index') }}">Piutang Pelanggan</a>
    </div>
@endsection

@section('content')
    @if (session('berhasil'))
        <div class="alert alert-success">{{ session('berhasil') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row g-3 mb-3">
        @foreach ([
            ['Penawaran Aktif', $ringkasan['penawaran_aktif'], 'file-text'],
            ['Pesanan Aktif', $ringkasan['pesanan_aktif'], 'shopping-cart'],
            ['Penjualan Bulan Ini', 'Rp '.number_format($ringkasan['nilai_penjualan_bulan_ini'], 0, ',', '.'), 'badge-dollar-sign'],
            ['Pengiriman Aktif', $ringkasan['pengiriman_aktif'], 'truck'],
        ] as [$label, $nilai, $ikon])
            <div class="col-md-3">
                <div class="card h-100">
                    <div class="card-body d-flex align-items-center gap-3">
                        <span class="avatar-md bg-primary-subtle text-primary rounded d-flex align-items-center justify-content-center"><i data-lucide="{{ $ikon }}"></i></span>
                        <div><div class="text-muted">{{ $label }}</div><div class="fs-20 fw-semibold">{{ $nilai }}</div></div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="card">
        <div class="card-header d-flex flex-wrap gap-2 justify-content-between">
            <form class="d-flex gap-2">
                <input class="form-control" name="pencarian" value="{{ $pencarian }}" placeholder="Cari nomor atau pelanggan">
                <button class="btn btn-primary">Cari</button>
                <a class="btn btn-light" href="{{ route('penjualan.index') }}">Reset</a>
            </form>
            <div class="d-flex flex-wrap gap-2">
                @if (auth()->user()->memilikiHakAkses('PENAWARAN_PENJUALAN_KELOLA', session('id_cabang_aktif')))
                    <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalPenawaran">Penawaran</button>
                @endif
                @if (auth()->user()->memilikiHakAkses('PESANAN_PENJUALAN_KELOLA', session('id_cabang_aktif')))
                    <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalPesanan">Pesanan</button>
                @endif
                @if (auth()->user()->memilikiHakAkses('TRANSAKSI_PENJUALAN_KELOLA', session('id_cabang_aktif')))
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalPenjualan">Penjualan</button>
                @endif
                @if (auth()->user()->memilikiHakAkses('PENGIRIMAN_KELOLA', session('id_cabang_aktif')))
                    <button class="btn btn-outline-success" data-bs-toggle="modal" data-bs-target="#modalPengiriman">Pengiriman</button>
                @endif
                @if (auth()->user()->memilikiHakAkses('RETUR_PENJUALAN_KELOLA', session('id_cabang_aktif')))
                    <button class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#modalRetur">Retur</button>
                @endif
            </div>
        </div>
        <div class="card-body">
            <ul class="nav nav-tabs" role="tablist">
                @foreach (['penawaran' => 'Penawaran', 'pesanan' => 'Pesanan', 'penjualan' => 'Penjualan', 'pengiriman' => 'Pengiriman', 'retur' => 'Retur'] as $tab => $label)
                    <li class="nav-item"><button class="nav-link {{ $loop->first ? 'active' : '' }}" data-bs-toggle="tab" data-bs-target="#tab-{{ $tab }}" type="button">{{ $label }}</button></li>
                @endforeach
            </ul>
            <div class="tab-content pt-3">
                <div class="tab-pane fade show active" id="tab-penawaran">
                    <div class="table-responsive"><table class="table table-hover align-middle"><thead><tr><th>Nomor</th><th>Pelanggan</th><th>Tanggal</th><th>Total</th><th>Status</th><th class="text-end">Aksi</th></tr></thead><tbody>
                        @forelse ($penawaran as $item)
                            <tr>
                                <td><strong>{{ $item->nomor_penawaran }}</strong></td>
                                <td>{{ $item->nama_pelanggan ?: 'Pelanggan umum' }}</td>
                                <td>{{ \Carbon\Carbon::parse($item->tanggal_penawaran)->format('d-m-Y') }}</td>
                                <td>Rp {{ number_format($item->total_bersih, 0, ',', '.') }}</td>
                                <td><span class="badge badge-soft-info">{{ $item->status_penawaran }}</span></td>
                                <td class="text-end text-nowrap">
                                    @if ($item->status_penawaran === 'DRAF')
                                        <form class="d-inline" method="POST" action="{{ route('penjualan.penawaran.kirim', $item->id_penawaran_penjualan) }}">@csrf @method('PATCH')<button class="btn btn-sm btn-outline-primary">Kirim</button></form>
                                    @endif
                                    @if ($item->status_penawaran === 'DIKIRIM')
                                        <form class="d-inline" method="POST" action="{{ route('penjualan.penawaran.terima', $item->id_penawaran_penjualan) }}">@csrf @method('PATCH')<button class="btn btn-sm btn-success">Diterima</button></form>
                                        <form class="d-inline" method="POST" action="{{ route('penjualan.penawaran.tolak', $item->id_penawaran_penjualan) }}">@csrf @method('PATCH')<button class="btn btn-sm btn-outline-danger">Ditolak</button></form>
                                    @endif
                                    @if ($item->status_penawaran === 'DISETUJUI_PELANGGAN')
                                        <form class="d-inline" method="POST" action="{{ route('penjualan.penawaran.jadikan-pesanan', $item->id_penawaran_penjualan) }}">@csrf <button class="btn btn-sm btn-primary">Jadikan Pesanan</button></form>
                                    @endif
                                    @if (in_array($item->status_penawaran, ['DRAF','DIKIRIM'], true))
                                        <form class="d-inline" method="POST" action="{{ route('penjualan.penawaran.batalkan', $item->id_penawaran_penjualan) }}">@csrf @method('PATCH')<button class="btn btn-sm btn-light">Batalkan</button></form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-4">Belum ada penawaran penjualan.</td></tr>
                        @endforelse
                    </tbody></table></div>
                </div>

                <div class="tab-pane fade" id="tab-pesanan">
                    <div class="table-responsive"><table class="table table-hover align-middle"><thead><tr><th>Nomor</th><th>Pelanggan</th><th>Tanggal</th><th>Pembayaran</th><th>Total</th><th>Status</th><th class="text-end">Aksi</th></tr></thead><tbody>
                        @forelse ($pesanan as $item)
                            <tr>
                                <td><strong>{{ $item->nomor_pesanan }}</strong></td><td>{{ $item->nama_pelanggan }}</td><td>{{ \Carbon\Carbon::parse($item->tanggal_pesanan)->format('d-m-Y') }}</td><td>{{ $item->cara_pembayaran }}</td><td>Rp {{ number_format($item->total_bersih, 0, ',', '.') }}</td><td><span class="badge badge-soft-info">{{ $item->status_pesanan }}</span></td>
                                <td class="text-end text-nowrap">
                                    @if ($item->status_pesanan === 'DRAF' && auth()->user()->memilikiHakAkses('PESANAN_PENJUALAN_SETUJUI', session('id_cabang_aktif')))
                                        <form class="d-inline" method="POST" action="{{ route('penjualan.pesanan.setujui', $item->id_pesanan_penjualan) }}">@csrf @method('PATCH')<button class="btn btn-sm btn-success">Setujui</button></form>
                                    @endif
                                    @if (in_array($item->status_pesanan, ['DRAF','DISETUJUI'], true))
                                        <form class="d-inline" method="POST" action="{{ route('penjualan.pesanan.batalkan', $item->id_pesanan_penjualan) }}">@csrf @method('PATCH')<button class="btn btn-sm btn-light">Batalkan</button></form>
                                    @endif
                                </td>
                            </tr>
                        @empty<tr><td colspan="7" class="text-center text-muted py-4">Belum ada pesanan penjualan.</td></tr>@endforelse
                    </tbody></table></div>
                </div>

                <div class="tab-pane fade" id="tab-penjualan">
                    <div class="table-responsive"><table class="table table-hover align-middle"><thead><tr><th>Nomor</th><th>Pelanggan</th><th>Gudang</th><th>Jenis</th><th>Total</th><th>Stok/Pengiriman</th><th>Status</th><th class="text-end">Aksi</th></tr></thead><tbody>
                        @forelse ($penjualan as $item)
                            <tr>
                                <td><strong>{{ $item->nomor_penjualan }}</strong><br><small class="text-muted">{{ \Carbon\Carbon::parse($item->tanggal_penjualan)->format('d-m-Y H:i') }}</small></td><td>{{ $item->nama_pelanggan ?: 'Pelanggan tunai' }}</td><td>{{ $item->nama_gudang }}</td><td>{{ $item->jenis_penjualan }}</td><td>Rp {{ number_format($item->total_bersih, 0, ',', '.') }}</td><td>{{ $item->status_pengiriman }}</td><td><span class="badge badge-soft-info">{{ $item->status_penjualan }}</span></td>
                                <td class="text-end text-nowrap">
                                    @if ($item->status_penjualan === 'DRAF' && auth()->user()->memilikiHakAkses('TRANSAKSI_PENJUALAN_SETUJUI', session('id_cabang_aktif')))
                                        <form class="d-inline" method="POST" action="{{ route('penjualan.transaksi.setujui', $item->id_penjualan) }}">@csrf @method('PATCH')<button class="btn btn-sm btn-success" onclick="return confirm('Setujui penjualan dan kurangi stok?')">Setujui</button></form>
                                    @endif
                                    @if ($item->status_penjualan === 'DRAF')
                                        <form class="d-inline" method="POST" action="{{ route('penjualan.transaksi.batalkan', $item->id_penjualan) }}">@csrf @method('PATCH')<button class="btn btn-sm btn-light">Batalkan</button></form>
                                    @endif
                                </td>
                            </tr>
                        @empty<tr><td colspan="8" class="text-center text-muted py-4">Belum ada transaksi penjualan.</td></tr>@endforelse
                    </tbody></table></div>
                </div>

                <div class="tab-pane fade" id="tab-pengiriman">
                    <div class="table-responsive"><table class="table table-hover align-middle"><thead><tr><th>Nomor</th><th>Penjualan</th><th>Tanggal</th><th>Armada</th><th>Status</th><th class="text-end">Aksi</th></tr></thead><tbody>
                        @forelse ($pengiriman as $item)
                            <tr><td><strong>{{ $item->nomor_pengiriman }}</strong></td><td>{{ $item->nomor_penjualan ?: '-' }}</td><td>{{ \Carbon\Carbon::parse($item->tanggal_pengiriman)->format('d-m-Y') }}</td><td>{{ $item->nomor_polisi ?: '-' }}</td><td><span class="badge badge-soft-info">{{ $item->status_pengiriman }}</span></td><td class="text-end text-nowrap">
                                @if ($item->status_pengiriman === 'DRAF')<form class="d-inline" method="POST" action="{{ route('penjualan.pengiriman.jadwalkan', $item->id_pengiriman) }}">@csrf @method('PATCH')<button class="btn btn-sm btn-outline-primary">Jadwalkan</button></form>@endif
                                @if ($item->status_pengiriman === 'DIJADWALKAN')<form class="d-inline" method="POST" action="{{ route('penjualan.pengiriman.berangkat', $item->id_pengiriman) }}">@csrf @method('PATCH')<button class="btn btn-sm btn-primary">Berangkat</button></form>@endif
                                @if ($item->status_pengiriman === 'DALAM_PERJALANAN')<form class="d-inline" method="POST" action="{{ route('penjualan.pengiriman.terima', $item->id_pengiriman) }}">@csrf @method('PATCH')<button class="btn btn-sm btn-success">Diterima</button></form>@endif
                                @if (in_array($item->status_pengiriman, ['DRAF','DIJADWALKAN'], true))<form class="d-inline" method="POST" action="{{ route('penjualan.pengiriman.batalkan', $item->id_pengiriman) }}">@csrf @method('PATCH')<button class="btn btn-sm btn-light">Batalkan</button></form>@endif
                            </td></tr>
                        @empty<tr><td colspan="6" class="text-center text-muted py-4">Belum ada pengiriman.</td></tr>@endforelse
                    </tbody></table></div>
                </div>

                <div class="tab-pane fade" id="tab-retur">
                    <div class="table-responsive"><table class="table table-hover align-middle"><thead><tr><th>Nomor</th><th>Penjualan</th><th>Pelanggan</th><th>Total</th><th>Pengembalian</th><th>Status</th><th class="text-end">Aksi</th></tr></thead><tbody>
                        @forelse ($retur as $item)
                            <tr><td><strong>{{ $item->nomor_retur }}</strong></td><td>{{ $item->nomor_penjualan }}</td><td>{{ $item->nama_pelanggan ?: '-' }}</td><td>Rp {{ number_format($item->total_retur, 0, ',', '.') }}</td><td>{{ $item->cara_pengembalian_dana }}</td><td><span class="badge badge-soft-info">{{ $item->status_retur }}</span></td><td class="text-end text-nowrap">
                                @if ($item->status_retur === 'DRAF' && auth()->user()->memilikiHakAkses('RETUR_PENJUALAN_SETUJUI', session('id_cabang_aktif')))<form class="d-inline" method="POST" action="{{ route('penjualan.retur.setujui', $item->id_retur_penjualan) }}">@csrf @method('PATCH')<button class="btn btn-sm btn-success">Setujui</button></form>@endif
                                @if ($item->status_retur === 'DISETUJUI' && auth()->user()->memilikiHakAkses('RETUR_PENJUALAN_TERIMA', session('id_cabang_aktif')))<form class="d-inline" method="POST" action="{{ route('penjualan.retur.terima', $item->id_retur_penjualan) }}">@csrf @method('PATCH')<button class="btn btn-sm btn-primary">Terima Barang</button></form>@endif
                                @if ($item->status_retur === 'DITERIMA')<form class="d-inline" method="POST" action="{{ route('penjualan.retur.selesai', $item->id_retur_penjualan) }}">@csrf @method('PATCH')<button class="btn btn-sm btn-outline-success">Selesai</button></form>@endif
                                @if (in_array($item->status_retur, ['DRAF','DISETUJUI'], true))<form class="d-inline" method="POST" action="{{ route('penjualan.retur.batalkan', $item->id_retur_penjualan) }}">@csrf @method('PATCH')<button class="btn btn-sm btn-light">Batalkan</button></form>@endif
                            </td></tr>
                        @empty<tr><td colspan="7" class="text-center text-muted py-4">Belum ada retur penjualan.</td></tr>@endforelse
                    </tbody></table></div>
                </div>
            </div>
        </div>
    </div>

    @include('penjualan.partials.modal-penawaran')
    @include('penjualan.partials.modal-pesanan')
    @include('penjualan.partials.modal-penjualan')
    @include('penjualan.partials.modal-pengiriman')
    @include('penjualan.partials.modal-retur')
@endsection
