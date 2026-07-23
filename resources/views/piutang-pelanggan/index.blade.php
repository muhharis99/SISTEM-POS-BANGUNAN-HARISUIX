@extends('layouts.admin')

@section('judul', 'Piutang Pelanggan')

@section('breadcrumb')
    <div class="d-flex align-items-center justify-content-between py-3">
        <div>
            <h4 class="fs-18 fw-semibold mb-1">Piutang Pelanggan</h4>
            <p class="text-muted mb-0">Saldo, jatuh tempo, pembayaran, potongan, dan pelunasan piutang pelanggan.</p>
        </div>
        <a class="btn btn-outline-primary" href="{{ route('penjualan.index') }}">Kembali ke Penjualan</a>
    </div>
@endsection

@section('content')
    @if (session('berhasil'))<div class="alert alert-success">{{ session('berhasil') }}</div>@endif
    @if ($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

    <div class="row g-3 mb-3">
        @foreach ([
            ['Total Piutang', 'Rp '.number_format($ringkasan['total_piutang'], 0, ',', '.'), 'wallet-cards'],
            ['Lewat Jatuh Tempo', 'Rp '.number_format($ringkasan['jatuh_tempo'], 0, ',', '.'), 'triangle-alert'],
            ['Pelanggan Berpiutang', $ringkasan['pelanggan_berpiutang'], 'users'],
            ['Pembayaran Bulan Ini', 'Rp '.number_format($ringkasan['pembayaran_bulan_ini'], 0, ',', '.'), 'circle-dollar-sign'],
        ] as [$label, $nilai, $ikon])
            <div class="col-md-3"><div class="card h-100"><div class="card-body d-flex align-items-center gap-3"><span class="avatar-md bg-primary-subtle text-primary rounded d-flex align-items-center justify-content-center"><i data-lucide="{{ $ikon }}"></i></span><div><div class="text-muted">{{ $label }}</div><div class="fs-20 fw-semibold">{{ $nilai }}</div></div></div></div></div>
        @endforeach
    </div>

    <div class="card">
        <div class="card-header d-flex flex-wrap gap-2 justify-content-between">
            <form class="d-flex gap-2"><input class="form-control" name="pencarian" value="{{ $pencarian }}" placeholder="Cari penjualan atau pelanggan"><button class="btn btn-primary">Cari</button><a class="btn btn-light" href="{{ route('piutang-pelanggan.index') }}">Reset</a></form>
            @if (auth()->user()->memilikiHakAkses('PEMBAYARAN_PIUTANG_KELOLA', session('id_cabang_aktif')))<button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalPembayaranPiutang">Pembayaran Piutang</button>@endif
        </div>
        <div class="card-body">
            <ul class="nav nav-tabs"><li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tabPiutang">Piutang</button></li><li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabPembayaran">Pembayaran</button></li></ul>
            <div class="tab-content pt-3">
                <div class="tab-pane fade show active" id="tabPiutang">
                    <div class="table-responsive"><table class="table table-hover align-middle"><thead><tr><th>Penjualan</th><th>Pelanggan</th><th>Tanggal</th><th>Jatuh Tempo</th><th>Nilai Awal</th><th>Dibayar/Retur</th><th>Sisa</th><th>Status</th></tr></thead><tbody>
                        @forelse($piutang as $item)
                            <tr><td><strong>{{ $item->nomor_penjualan }}</strong></td><td>{{ $item->nama_pelanggan }}</td><td>{{ \Carbon\Carbon::parse($item->tanggal_piutang)->format('d-m-Y') }}</td><td>{{ $item->tanggal_jatuh_tempo ? \Carbon\Carbon::parse($item->tanggal_jatuh_tempo)->format('d-m-Y') : '-' }}</td><td>Rp {{ number_format($item->nilai_awal, 0, ',', '.') }}</td><td>Rp {{ number_format($item->nilai_pembayaran + $item->nilai_retur + $item->nilai_penyesuaian, 0, ',', '.') }}</td><td><strong>Rp {{ number_format($item->sisa_piutang, 0, ',', '.') }}</strong></td><td><span class="badge badge-soft-info">{{ $item->status_piutang }}</span></td></tr>
                        @empty<tr><td colspan="8" class="text-center text-muted py-4">Belum ada piutang pelanggan.</td></tr>@endforelse
                    </tbody></table></div>
                </div>
                <div class="tab-pane fade" id="tabPembayaran">
                    <div class="table-responsive"><table class="table table-hover align-middle"><thead><tr><th>Nomor</th><th>Pelanggan</th><th>Tanggal</th><th>Kas/Bank</th><th>Total</th><th>Status</th><th class="text-end">Aksi</th></tr></thead><tbody>
                        @forelse($pembayaran as $item)
                            <tr><td><strong>{{ $item->nomor_pembayaran }}</strong></td><td>{{ $item->nama_pelanggan }}</td><td>{{ \Carbon\Carbon::parse($item->tanggal_pembayaran)->format('d-m-Y') }}</td><td>{{ $item->nama_kas_bank }}</td><td>Rp {{ number_format($item->total_pembayaran, 0, ',', '.') }}</td><td><span class="badge badge-soft-info">{{ $item->status_pembayaran }}</span></td><td class="text-end text-nowrap">
                                @if($item->status_pembayaran === 'DRAF' && auth()->user()->memilikiHakAkses('PEMBAYARAN_PIUTANG_SETUJUI', session('id_cabang_aktif')))<form class="d-inline" method="POST" action="{{ route('piutang-pelanggan.setujui', $item->id_pembayaran_piutang) }}">@csrf @method('PATCH')<button class="btn btn-sm btn-success">Setujui</button></form>@endif
                                @if($item->status_pembayaran === 'DRAF')<form class="d-inline" method="POST" action="{{ route('piutang-pelanggan.batalkan', $item->id_pembayaran_piutang) }}">@csrf @method('PATCH')<button class="btn btn-sm btn-light">Batalkan</button></form>@endif
                            </td></tr>
                        @empty<tr><td colspan="7" class="text-center text-muted py-4">Belum ada pembayaran piutang.</td></tr>@endforelse
                    </tbody></table></div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalPembayaranPiutang" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <form class="modal-content" method="POST" action="{{ route('piutang-pelanggan.simpan') }}">
                @csrf
                <div class="modal-header"><h5 class="modal-title">Pembayaran Piutang</h5><button class="btn-close" type="button" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="row g-3 mb-3"><div class="col-md-4"><label class="form-label">Pelanggan</label><select class="form-select" name="id_pelanggan" required><option value="">Pilih</option>@foreach($pelangganPilihan as $p)<option value="{{ $p->id_pelanggan }}">{{ $p->kode_pelanggan }} — {{ $p->nama_pelanggan }}</option>@endforeach</select></div><div class="col-md-3"><label class="form-label">Kas/Bank</label><select class="form-select" name="id_kas_bank" required><option value="">Pilih</option>@foreach($kasPilihan as $k)<option value="{{ $k->id_kas_bank }}">{{ $k->nama_kas_bank }}</option>@endforeach</select></div><div class="col-md-3"><label class="form-label">Metode</label><select class="form-select" name="id_metode_pembayaran" required><option value="">Pilih</option>@foreach($metodePilihan as $m)<option value="{{ $m->id_metode_pembayaran }}">{{ $m->nama_metode_pembayaran }}</option>@endforeach</select></div><div class="col-md-2"><label class="form-label">Tanggal</label><input class="form-control" type="date" name="tanggal_pembayaran" value="{{ now()->toDateString() }}" required></div></div>
                    <div class="table-responsive"><table class="table table-bordered align-middle"><thead><tr><th>Piutang</th><th>Alokasi</th><th>Potongan</th><th>Keterangan</th></tr></thead><tbody><tr><td><select class="form-select" name="detail[0][id_piutang_pelanggan]" required><option value="">Pilih</option>@foreach($piutangPilihan as $p)<option value="{{ $p->id_piutang_pelanggan }}">{{ $p->nama_pelanggan }} — {{ $p->nomor_penjualan }} — Sisa Rp {{ number_format($p->sisa_piutang, 0, ',', '.') }}</option>@endforeach</select></td><td><input class="form-control" type="number" min="0.01" step="0.01" name="detail[0][nilai_dialokasikan]" required></td><td><input class="form-control" type="number" min="0" step="0.01" name="detail[0][potongan_pembayaran]" value="0"></td><td><input class="form-control" name="detail[0][keterangan]"></td></tr></tbody></table></div>
                    <div class="row g-3"><div class="col-md-3"><label class="form-label">Biaya Pembayaran</label><input class="form-control" type="number" min="0" step="0.01" name="biaya_pembayaran" value="0"></div><div class="col-md-3"><label class="form-label">Nomor Bukti</label><input class="form-control" name="nomor_bukti"></div><div class="col-md-6"><label class="form-label">Keterangan</label><input class="form-control" name="keterangan"></div></div>
                </div>
                <div class="modal-footer"><button class="btn btn-light" type="button" data-bs-dismiss="modal">Tutup</button><button class="btn btn-primary">Simpan Draf</button></div>
            </form>
        </div>
    </div>
@endsection
