@extends('layouts.admin')

@section('judul', 'Pembelian')

@section('breadcrumb')
    <div class="d-flex align-items-center justify-content-between py-3">
        <div>
            <h4 class="fs-18 fw-semibold mb-1">Pembelian dan Pemasok</h4>
            <p class="text-muted mb-0">Permintaan, pesanan, penerimaan, faktur, dan retur pembelian terintegrasi dengan persediaan.</p>
        </div>
        <a class="btn btn-outline-primary" href="{{ route('hutang-pemasok.index') }}">Hutang Pemasok</a>
    </div>
@endsection

@section('content')
    @if (session('berhasil'))<div class="alert alert-success">{{ session('berhasil') }}</div>@endif
    @if ($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

    <div class="row g-3 mb-3">
        @foreach ([
            ['Permintaan Aktif', $ringkasan['permintaan_aktif'], 'clipboard-list'],
            ['Pesanan Aktif', $ringkasan['pesanan_aktif'], 'shopping-bag'],
            ['Penerimaan Bulan Ini', $ringkasan['penerimaan_bulan_ini'], 'package-check'],
            ['Nilai Faktur Bulan Ini', 'Rp '.number_format($ringkasan['nilai_faktur_bulan_ini'], 0, ',', '.'), 'receipt-text'],
        ] as [$label, $nilai, $ikon])
            <div class="col-md-3"><div class="card h-100"><div class="card-body d-flex align-items-center gap-3"><span class="avatar-md bg-primary-subtle text-primary rounded d-flex align-items-center justify-content-center"><i data-lucide="{{ $ikon }}"></i></span><div><div class="text-muted">{{ $label }}</div><div class="fs-20 fw-semibold">{{ $nilai }}</div></div></div></div></div>
        @endforeach
    </div>

    <div class="card">
        <div class="card-header d-flex flex-wrap gap-2 justify-content-between">
            <form class="d-flex gap-2"><input class="form-control" name="pencarian" value="{{ $pencarian }}" placeholder="Cari nomor atau pemasok"><button class="btn btn-primary">Cari</button><a class="btn btn-light" href="{{ route('pembelian.index') }}">Reset</a></form>
            <div class="d-flex flex-wrap gap-2">
                @if (auth()->user()->memilikiHakAkses('PERMINTAAN_PEMBELIAN_KELOLA', session('id_cabang_aktif')))<button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalPermintaan">Permintaan</button>@endif
                @if (auth()->user()->memilikiHakAkses('PESANAN_PEMBELIAN_KELOLA', session('id_cabang_aktif')))<button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalPesanan">Pesanan</button>@endif
                @if (auth()->user()->memilikiHakAkses('PENERIMAAN_BARANG_KELOLA', session('id_cabang_aktif')))<button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalPenerimaan">Penerimaan</button>@endif
                @if (auth()->user()->memilikiHakAkses('FAKTUR_PEMBELIAN_KELOLA', session('id_cabang_aktif')))<button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalFaktur">Faktur</button>@endif
                @if (auth()->user()->memilikiHakAkses('RETUR_PEMBELIAN_KELOLA', session('id_cabang_aktif')))<button class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#modalRetur">Retur</button>@endif
            </div>
        </div>
        <div class="card-body">
            <ul class="nav nav-tabs" role="tablist">
                @foreach (['permintaan' => 'Permintaan', 'pesanan' => 'Pesanan', 'penerimaan' => 'Penerimaan', 'faktur' => 'Faktur', 'retur' => 'Retur'] as $tab => $label)
                    <li class="nav-item"><button class="nav-link {{ $loop->first ? 'active' : '' }}" data-bs-toggle="tab" data-bs-target="#tab-{{ $tab }}" type="button">{{ $label }}</button></li>
                @endforeach
            </ul>
            <div class="tab-content pt-3">
                <div class="tab-pane fade show active" id="tab-permintaan">
                    <div class="table-responsive"><table class="table table-hover align-middle"><thead><tr><th>Nomor</th><th>Tanggal</th><th>Kebutuhan</th><th>Kepentingan</th><th>Status</th><th class="text-end">Aksi</th></tr></thead><tbody>
                        @forelse ($permintaan as $item)
                            <tr><td><strong>{{ $item->nomor_permintaan }}</strong><br><small class="text-muted">{{ $item->nama_peminta ?: '-' }}</small></td><td>{{ \Carbon\Carbon::parse($item->tanggal_permintaan)->format('d-m-Y') }}</td><td>{{ $item->tanggal_kebutuhan ? \Carbon\Carbon::parse($item->tanggal_kebutuhan)->format('d-m-Y') : '-' }}</td><td>{{ $item->tingkat_kepentingan }}</td><td><span class="badge badge-soft-info">{{ $item->status_permintaan }}</span></td><td class="text-end text-nowrap">
                                @if ($item->status_permintaan === 'DRAF')<form class="d-inline" method="POST" action="{{ route('pembelian.permintaan.ajukan', $item->id_permintaan_pembelian) }}">@csrf @method('PATCH')<button class="btn btn-sm btn-outline-primary">Ajukan</button></form>@endif
                                @if ($item->status_permintaan === 'DIAJUKAN' && auth()->user()->memilikiHakAkses('PERMINTAAN_PEMBELIAN_SETUJUI', session('id_cabang_aktif')))<form class="d-inline" method="POST" action="{{ route('pembelian.permintaan.setujui', $item->id_permintaan_pembelian) }}">@csrf @method('PATCH')<button class="btn btn-sm btn-success">Setujui</button></form><form class="d-inline" method="POST" action="{{ route('pembelian.permintaan.tolak', $item->id_permintaan_pembelian) }}">@csrf @method('PATCH')<button class="btn btn-sm btn-outline-danger">Tolak</button></form>@endif
                                @if (in_array($item->status_permintaan, ['DRAF','DIAJUKAN'], true))<form class="d-inline" method="POST" action="{{ route('pembelian.permintaan.batalkan', $item->id_permintaan_pembelian) }}">@csrf @method('PATCH')<button class="btn btn-sm btn-light">Batalkan</button></form>@endif
                            </td></tr>
                        @empty<tr><td colspan="6" class="text-center text-muted py-4">Belum ada permintaan pembelian.</td></tr>@endforelse
                    </tbody></table></div>
                </div>
                <div class="tab-pane fade" id="tab-pesanan">
                    <div class="table-responsive"><table class="table table-hover align-middle"><thead><tr><th>Nomor</th><th>Pemasok</th><th>Tanggal</th><th>Total</th><th>Status</th><th class="text-end">Aksi</th></tr></thead><tbody>
                        @forelse ($pesanan as $item)
                            <tr><td><strong>{{ $item->nomor_pesanan }}</strong></td><td>{{ $item->nama_pemasok }}</td><td>{{ \Carbon\Carbon::parse($item->tanggal_pesanan)->format('d-m-Y') }}</td><td>Rp {{ number_format($item->total_bersih, 0, ',', '.') }}</td><td><span class="badge badge-soft-info">{{ $item->status_pesanan }}</span></td><td class="text-end text-nowrap">
                                @if ($item->status_pesanan === 'DRAF')<form class="d-inline" method="POST" action="{{ route('pembelian.pesanan.ajukan', $item->id_pesanan_pembelian) }}">@csrf @method('PATCH')<button class="btn btn-sm btn-outline-primary">Ajukan</button></form>@endif
                                @if ($item->status_pesanan === 'DIAJUKAN' && auth()->user()->memilikiHakAkses('PESANAN_PEMBELIAN_SETUJUI', session('id_cabang_aktif')))<form class="d-inline" method="POST" action="{{ route('pembelian.pesanan.setujui', $item->id_pesanan_pembelian) }}">@csrf @method('PATCH')<button class="btn btn-sm btn-success">Setujui</button></form>@endif
                                @if (in_array($item->status_pesanan, ['DRAF','DIAJUKAN'], true))<form class="d-inline" method="POST" action="{{ route('pembelian.pesanan.batalkan', $item->id_pesanan_pembelian) }}">@csrf @method('PATCH')<button class="btn btn-sm btn-light">Batalkan</button></form>@endif
                            </td></tr>
                        @empty<tr><td colspan="6" class="text-center text-muted py-4">Belum ada pesanan pembelian.</td></tr>@endforelse
                    </tbody></table></div>
                </div>
                <div class="tab-pane fade" id="tab-penerimaan">
                    <div class="table-responsive"><table class="table table-hover align-middle"><thead><tr><th>Nomor</th><th>Pemasok</th><th>Gudang</th><th>Tanggal</th><th>Status</th><th class="text-end">Aksi</th></tr></thead><tbody>
                        @forelse ($penerimaan as $item)
                            <tr><td><strong>{{ $item->nomor_penerimaan }}</strong></td><td>{{ $item->nama_pemasok }}</td><td>{{ $item->nama_gudang }}</td><td>{{ \Carbon\Carbon::parse($item->tanggal_penerimaan)->format('d-m-Y') }}</td><td><span class="badge badge-soft-info">{{ $item->status_penerimaan }}</span></td><td class="text-end text-nowrap">
                                @if ($item->status_penerimaan === 'DRAF' && auth()->user()->memilikiHakAkses('PENERIMAAN_BARANG_TERIMA', session('id_cabang_aktif')))<form class="d-inline" method="POST" action="{{ route('pembelian.penerimaan.terima', $item->id_penerimaan_barang) }}">@csrf @method('PATCH')<button class="btn btn-sm btn-success" onclick="return confirm('Terima barang dan tambahkan stok?')">Terima</button></form>@endif
                                @if ($item->status_penerimaan === 'DRAF')<form class="d-inline" method="POST" action="{{ route('pembelian.penerimaan.batalkan', $item->id_penerimaan_barang) }}">@csrf @method('PATCH')<button class="btn btn-sm btn-light">Batalkan</button></form>@endif
                            </td></tr>
                        @empty<tr><td colspan="6" class="text-center text-muted py-4">Belum ada penerimaan barang.</td></tr>@endforelse
                    </tbody></table></div>
                </div>
                <div class="tab-pane fade" id="tab-faktur">
                    <div class="table-responsive"><table class="table table-hover align-middle"><thead><tr><th>Internal</th><th>Faktur Pemasok</th><th>Pemasok</th><th>Total</th><th>Sisa Hutang</th><th>Status</th><th class="text-end">Aksi</th></tr></thead><tbody>
                        @forelse ($faktur as $item)
                            <tr><td><strong>{{ $item->nomor_faktur_internal }}</strong></td><td>{{ $item->nomor_faktur_pemasok }}</td><td>{{ $item->nama_pemasok }}</td><td>Rp {{ number_format($item->total_bersih, 0, ',', '.') }}</td><td>Rp {{ number_format($item->sisa_hutang, 0, ',', '.') }}</td><td><span class="badge badge-soft-info">{{ $item->status_faktur }}</span></td><td class="text-end text-nowrap">
                                @if ($item->status_faktur === 'DRAF' && auth()->user()->memilikiHakAkses('FAKTUR_PEMBELIAN_SETUJUI', session('id_cabang_aktif')))<form class="d-inline" method="POST" action="{{ route('pembelian.faktur.setujui', $item->id_faktur_pembelian) }}">@csrf @method('PATCH')<button class="btn btn-sm btn-success">Setujui</button></form>@endif
                                @if ($item->status_faktur === 'DRAF')<form class="d-inline" method="POST" action="{{ route('pembelian.faktur.batalkan', $item->id_faktur_pembelian) }}">@csrf @method('PATCH')<button class="btn btn-sm btn-light">Batalkan</button></form>@endif
                            </td></tr>
                        @empty<tr><td colspan="7" class="text-center text-muted py-4">Belum ada faktur pembelian.</td></tr>@endforelse
                    </tbody></table></div>
                </div>
                <div class="tab-pane fade" id="tab-retur">
                    <div class="table-responsive"><table class="table table-hover align-middle"><thead><tr><th>Nomor</th><th>Pemasok</th><th>Gudang</th><th>Total</th><th>Cara Dana</th><th>Status</th><th class="text-end">Aksi</th></tr></thead><tbody>
                        @forelse ($retur as $item)
                            <tr><td><strong>{{ $item->nomor_retur }}</strong></td><td>{{ $item->nama_pemasok }}</td><td>{{ $item->nama_gudang }}</td><td>Rp {{ number_format($item->total_retur, 0, ',', '.') }}</td><td>{{ $item->cara_pengembalian_dana }}</td><td><span class="badge badge-soft-info">{{ $item->status_retur }}</span></td><td class="text-end text-nowrap">
                                @if ($item->status_retur === 'DRAF' && auth()->user()->memilikiHakAkses('RETUR_PEMBELIAN_SETUJUI', session('id_cabang_aktif')))<form class="d-inline" method="POST" action="{{ route('pembelian.retur.setujui', $item->id_retur_pembelian) }}">@csrf @method('PATCH')<button class="btn btn-sm btn-success">Setujui</button></form>@endif
                                @if ($item->status_retur === 'DISETUJUI' && auth()->user()->memilikiHakAkses('RETUR_PEMBELIAN_KIRIM', session('id_cabang_aktif')))<form class="d-inline" method="POST" action="{{ route('pembelian.retur.kirim', $item->id_retur_pembelian) }}">@csrf @method('PATCH')<button class="btn btn-sm btn-warning" onclick="return confirm('Kirim retur dan kurangi stok?')">Kirim</button></form>@endif
                                @if ($item->status_retur === 'DIKIRIM')<form class="d-inline" method="POST" action="{{ route('pembelian.retur.selesai', $item->id_retur_pembelian) }}">@csrf @method('PATCH')<button class="btn btn-sm btn-primary">Selesai</button></form>@endif
                                @if ($item->status_retur === 'DRAF')<form class="d-inline" method="POST" action="{{ route('pembelian.retur.batalkan', $item->id_retur_pembelian) }}">@csrf @method('PATCH')<button class="btn btn-sm btn-light">Batalkan</button></form>@endif
                            </td></tr>
                        @empty<tr><td colspan="7" class="text-center text-muted py-4">Belum ada retur pembelian.</td></tr>@endforelse
                    </tbody></table></div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalPermintaan" tabindex="-1"><div class="modal-dialog modal-xl"><div class="modal-content"><form method="POST" action="{{ route('pembelian.permintaan.simpan') }}">@csrf<div class="modal-header"><h5 class="modal-title">Tambah Permintaan Pembelian</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><div class="row g-3"><div class="col-md-4"><label class="form-label">Tanggal</label><input class="form-control" type="date" name="tanggal_permintaan" value="{{ date('Y-m-d') }}" required></div><div class="col-md-4"><label class="form-label">Tanggal Kebutuhan</label><input class="form-control" type="date" name="tanggal_kebutuhan"></div><div class="col-md-4"><label class="form-label">Kepentingan</label><select class="form-select" name="tingkat_kepentingan"><option>NORMAL</option><option>TINGGI</option><option>MENDESAK</option><option>RENDAH</option></select></div></div><div class="mt-3" data-detail-container="permintaan">@include('pembelian.partials.baris-permintaan', ['index' => 0])</div><button type="button" class="btn btn-sm btn-light mt-2" data-add-detail="permintaan">Tambah Baris</button><div class="mt-3"><label class="form-label">Keterangan</label><textarea class="form-control" name="keterangan"></textarea></div></div><div class="modal-footer"><button class="btn btn-light" type="button" data-bs-dismiss="modal">Batal</button><button class="btn btn-primary">Simpan Draf</button></div></form></div></div></div>

    <div class="modal fade" id="modalPesanan" tabindex="-1"><div class="modal-dialog modal-xl"><div class="modal-content"><form method="POST" action="{{ route('pembelian.pesanan.simpan') }}">@csrf<div class="modal-header"><h5 class="modal-title">Tambah Pesanan Pembelian</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><div class="row g-3"><div class="col-md-4"><label class="form-label">Pemasok</label><select class="form-select" name="id_pemasok" required><option value="">Pilih</option>@foreach($pemasokPilihan as $p)<option value="{{ $p->id_pemasok }}">{{ $p->nama_pemasok }}</option>@endforeach</select></div><div class="col-md-3"><label class="form-label">Tanggal</label><input class="form-control" type="date" name="tanggal_pesanan" value="{{ date('Y-m-d') }}" required></div><div class="col-md-3"><label class="form-label">Perkiraan Tiba</label><input class="form-control" type="date" name="tanggal_perkiraan_tiba"></div><div class="col-md-2"><label class="form-label">Pembayaran</label><select class="form-select" name="cara_pembayaran"><option>TUNAI</option><option>TEMPO</option></select></div></div><div class="row g-3 mt-1"><div class="col-md-3"><label class="form-label">Jatuh Tempo (hari)</label><input class="form-control" type="number" name="lama_jatuh_tempo" value="0"></div><div class="col-md-3"><label class="form-label">Biaya Kirim</label><input class="form-control" type="number" name="biaya_pengiriman" value="0"></div><div class="col-md-3"><label class="form-label">Biaya Lain</label><input class="form-control" type="number" name="biaya_lain" value="0"></div><div class="col-md-3"><label class="form-label">Alamat Pengiriman</label><input class="form-control" name="alamat_pengiriman"></div></div><div class="mt-3" data-detail-container="pesanan">@include('pembelian.partials.baris-pesanan', ['index' => 0])</div><button type="button" class="btn btn-sm btn-light mt-2" data-add-detail="pesanan">Tambah Baris</button></div><div class="modal-footer"><button class="btn btn-light" type="button" data-bs-dismiss="modal">Batal</button><button class="btn btn-primary">Simpan Draf</button></div></form></div></div></div>

    <div class="modal fade" id="modalPenerimaan" tabindex="-1"><div class="modal-dialog modal-xl"><div class="modal-content"><form method="POST" action="{{ route('pembelian.penerimaan.simpan') }}">@csrf<div class="modal-header"><h5 class="modal-title">Tambah Penerimaan Barang</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><div class="row g-3"><div class="col-md-3"><label class="form-label">Pesanan</label><select class="form-select" name="id_pesanan_pembelian"><option value="">Tanpa Pesanan</option>@foreach($pesananPilihan as $p)<option value="{{ $p->id_pesanan_pembelian }}">{{ $p->nomor_pesanan }} · {{ $p->nama_pemasok }}</option>@endforeach</select></div><div class="col-md-3"><label class="form-label">Pemasok</label><select class="form-select" name="id_pemasok" required><option value="">Pilih</option>@foreach($pemasokPilihan as $p)<option value="{{ $p->id_pemasok }}">{{ $p->nama_pemasok }}</option>@endforeach</select></div><div class="col-md-3"><label class="form-label">Gudang</label><select class="form-select" name="id_gudang" required><option value="">Pilih</option>@foreach($gudangPilihan as $g)<option value="{{ $g->id_gudang }}">{{ $g->nama_gudang }}</option>@endforeach</select></div><div class="col-md-3"><label class="form-label">Tanggal</label><input class="form-control" type="date" name="tanggal_penerimaan" value="{{ date('Y-m-d') }}" required></div></div><div class="row g-3 mt-1"><div class="col-md-4"><label class="form-label">Surat Jalan</label><input class="form-control" name="nomor_surat_jalan"></div><div class="col-md-4"><label class="form-label">Tanggal Surat Jalan</label><input class="form-control" type="date" name="tanggal_surat_jalan"></div></div><div class="mt-3" data-detail-container="penerimaan">@include('pembelian.partials.baris-penerimaan', ['index' => 0])</div><button type="button" class="btn btn-sm btn-light mt-2" data-add-detail="penerimaan">Tambah Baris</button></div><div class="modal-footer"><button class="btn btn-light" type="button" data-bs-dismiss="modal">Batal</button><button class="btn btn-primary">Simpan Draf</button></div></form></div></div></div>

    <div class="modal fade" id="modalFaktur" tabindex="-1"><div class="modal-dialog modal-xl"><div class="modal-content"><form method="POST" action="{{ route('pembelian.faktur.simpan') }}">@csrf<div class="modal-header"><h5 class="modal-title">Tambah Faktur Pembelian</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><div class="row g-3"><div class="col-md-3"><label class="form-label">Pemasok</label><select class="form-select" name="id_pemasok" required><option value="">Pilih</option>@foreach($pemasokPilihan as $p)<option value="{{ $p->id_pemasok }}">{{ $p->nama_pemasok }}</option>@endforeach</select></div><div class="col-md-3"><label class="form-label">Pesanan</label><select class="form-select" name="id_pesanan_pembelian"><option value="">Opsional</option>@foreach($pesananPilihan as $p)<option value="{{ $p->id_pesanan_pembelian }}">{{ $p->nomor_pesanan }}</option>@endforeach</select></div><div class="col-md-3"><label class="form-label">Penerimaan</label><select class="form-select" name="id_penerimaan_barang"><option value="">Opsional</option>@foreach($penerimaanPilihan as $p)<option value="{{ $p->id_penerimaan_barang }}">{{ $p->nomor_penerimaan }}</option>@endforeach</select></div><div class="col-md-3"><label class="form-label">Nomor Faktur Pemasok</label><input class="form-control" name="nomor_faktur_pemasok" required></div></div><div class="row g-3 mt-1"><div class="col-md-3"><label class="form-label">Tanggal Faktur</label><input class="form-control" type="date" name="tanggal_faktur" value="{{ date('Y-m-d') }}" required></div><div class="col-md-3"><label class="form-label">Jatuh Tempo</label><input class="form-control" type="date" name="tanggal_jatuh_tempo"></div><div class="col-md-2"><label class="form-label">Cara Bayar</label><select class="form-select" name="cara_pembayaran"><option>TUNAI</option><option>TEMPO</option></select></div><div class="col-md-2"><label class="form-label">Biaya Kirim</label><input class="form-control" type="number" name="biaya_pengiriman" value="0"></div><div class="col-md-2"><label class="form-label">Biaya Lain</label><input class="form-control" type="number" name="biaya_lain" value="0"></div></div><div class="mt-3" data-detail-container="faktur">@include('pembelian.partials.baris-faktur', ['index' => 0])</div><button type="button" class="btn btn-sm btn-light mt-2" data-add-detail="faktur">Tambah Baris</button></div><div class="modal-footer"><button class="btn btn-light" type="button" data-bs-dismiss="modal">Batal</button><button class="btn btn-primary">Simpan Draf</button></div></form></div></div></div>

    <div class="modal fade" id="modalRetur" tabindex="-1"><div class="modal-dialog modal-xl"><div class="modal-content"><form method="POST" action="{{ route('pembelian.retur.simpan') }}">@csrf<div class="modal-header"><h5 class="modal-title">Tambah Retur Pembelian</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><div class="row g-3"><div class="col-md-3"><label class="form-label">Pemasok</label><select class="form-select" name="id_pemasok" required><option value="">Pilih</option>@foreach($pemasokPilihan as $p)<option value="{{ $p->id_pemasok }}">{{ $p->nama_pemasok }}</option>@endforeach</select></div><div class="col-md-3"><label class="form-label">Faktur</label><select class="form-select" name="id_faktur_pembelian"><option value="">Opsional</option>@foreach($fakturPilihan as $p)<option value="{{ $p->id_faktur_pembelian }}">{{ $p->nomor_faktur_internal }} · {{ $p->nama_pemasok }}</option>@endforeach</select></div><div class="col-md-3"><label class="form-label">Gudang</label><select class="form-select" name="id_gudang" required><option value="">Pilih</option>@foreach($gudangPilihan as $g)<option value="{{ $g->id_gudang }}">{{ $g->nama_gudang }}</option>@endforeach</select></div><div class="col-md-3"><label class="form-label">Tanggal Retur</label><input class="form-control" type="date" name="tanggal_retur" value="{{ date('Y-m-d') }}" required></div></div><div class="row g-3 mt-1"><div class="col-md-5"><label class="form-label">Alasan</label><input class="form-control" name="alasan_retur" required></div><div class="col-md-4"><label class="form-label">Pengembalian Dana</label><select class="form-select" name="cara_pengembalian_dana"><option>POTONG_HUTANG</option><option>TUNAI</option><option>TRANSFER</option><option>PENGGANTI_BARANG</option></select></div><div class="col-md-3"><label class="form-label">Kas/Bank</label><select class="form-select" name="id_kas_bank"><option value="">Opsional</option>@foreach($kasBankPilihan as $k)<option value="{{ $k->id_kas_bank }}">{{ $k->nama_kas_bank }}</option>@endforeach</select></div></div><div class="mt-3" data-detail-container="retur">@include('pembelian.partials.baris-retur', ['index' => 0])</div><button type="button" class="btn btn-sm btn-light mt-2" data-add-detail="retur">Tambah Baris</button></div><div class="modal-footer"><button class="btn btn-light" type="button" data-bs-dismiss="modal">Batal</button><button class="btn btn-primary">Simpan Draf</button></div></form></div></div></div>
@endsection

@push('scripts')
<script>
document.addEventListener('click', (event) => {
    const tambah = event.target.closest('[data-add-detail]');
    if (tambah) {
        const jenis = tambah.dataset.addDetail;
        const wadah = document.querySelector(`[data-detail-container="${jenis}"]`);
        const baris = wadah.querySelector('[data-detail-row]').cloneNode(true);
        baris.querySelectorAll('input').forEach((input) => input.value = input.type === 'number' ? (input.dataset.default || '0') : '');
        baris.querySelectorAll('select').forEach((select) => select.value = '');
        wadah.appendChild(baris);
        susunNama(wadah);
    }
    const hapus = event.target.closest('[data-remove-detail]');
    if (hapus) {
        const wadah = hapus.closest('[data-detail-container]');
        if (wadah.querySelectorAll('[data-detail-row]').length > 1) hapus.closest('[data-detail-row]').remove();
        susunNama(wadah);
    }
});
function susunNama(wadah) {
    wadah.querySelectorAll('[data-detail-row]').forEach((baris, index) => {
        baris.querySelectorAll('[data-field]').forEach((field) => field.name = `detail[${index}][${field.dataset.field}]`);
    });
}
document.querySelectorAll('[data-detail-container]').forEach(susunNama);
</script>
@endpush
