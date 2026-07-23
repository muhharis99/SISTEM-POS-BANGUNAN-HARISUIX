@extends('layouts.admin')

@section('judul', 'Kas, Bank, dan Akuntansi')

@section('breadcrumb')
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 py-3">
        <div>
            <h4 class="fs-18 fw-semibold mb-1">Kas, Bank, dan Akuntansi</h4>
            <p class="text-muted mb-0">Transaksi kas, jurnal umum, buku besar, neraca saldo, laba rugi, dan posisi keuangan.</p>
        </div>
        <form class="d-flex flex-wrap gap-2" method="GET" action="{{ route('keuangan.index') }}">
            <input class="form-control" type="date" name="tanggal_awal" value="{{ $tanggalAwal }}" required>
            <input class="form-control" type="date" name="tanggal_akhir" value="{{ $tanggalAkhir }}" required>
            <button class="btn btn-primary" type="submit"><i data-lucide="filter" class="me-1"></i> Terapkan</button>
            <a class="btn btn-light" href="{{ route('keuangan.index') }}">Reset</a>
        </form>
    </div>
@endsection

@section('content')
    @php
        $idCabangAktif = session('id_cabang_aktif');
        $penggunaAktif = auth()->user();
        $punya = fn (string $izin): bool => (bool) $penggunaAktif?->memilikiHakAkses($izin, $idCabangAktif);
        $bolehKelolaKas = $punya('TRANSAKSI_KAS_KELOLA');
        $bolehSetujuiKas = $punya('TRANSAKSI_KAS_SETUJUI');
        $bolehKelolaJurnal = $punya('JURNAL_UMUM_KELOLA');
        $bolehPostingJurnal = $punya('JURNAL_UMUM_POSTING');
        $bolehKelolaAkun = $punya('AKUN_KEUANGAN_KELOLA');
        $bolehPemetaan = $punya('PEMETAAN_AKUN_KELOLA');
    @endphp

    @if ($errors->any())
        <div class="alert alert-danger">
            <div class="fw-semibold mb-1">Data belum dapat diproses:</div>
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row g-3 mb-3">
        @foreach ([
            ['Saldo Kas & Bank', $ringkasan['total_saldo_kas_bank'], 'wallet-cards'],
            ['Pendapatan Periode', $ringkasan['total_pendapatan'], 'trending-up'],
            ['Beban Periode', $ringkasan['total_beban'], 'trending-down'],
            ['Laba / Rugi', $ringkasan['laba_rugi'], 'badge-dollar-sign'],
            ['Total Aset', $ringkasan['total_aset'], 'landmark'],
            ['Kewajiban + Modal', $ringkasan['total_kewajiban_modal'], 'scale'],
        ] as [$label, $nilai, $ikon])
            <div class="col-xl-2 col-md-4 col-sm-6">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center gap-3">
                            <span class="avatar-md bg-primary-subtle text-primary rounded d-flex align-items-center justify-content-center">
                                <i data-lucide="{{ $ikon }}"></i>
                            </span>
                            <div class="min-w-0">
                                <div class="text-muted small">{{ $label }}</div>
                                <div class="fs-18 fw-semibold text-truncate {{ $label === 'Laba / Rugi' && $nilai < 0 ? 'text-danger' : '' }}">
                                    Rp {{ number_format($nilai, 0, ',', '.') }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="card">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h5 class="mb-1">Periode {{ \Carbon\Carbon::parse($tanggalAwal)->format('d-m-Y') }} s.d. {{ \Carbon\Carbon::parse($tanggalAkhir)->format('d-m-Y') }}</h5>
                <div class="text-muted small">Hanya jurnal berstatus DIPOSTING yang masuk ke laporan akuntansi.</div>
            </div>
            <div class="d-flex flex-wrap gap-2">
                @if ($bolehKelolaKas)
                    <button class="btn btn-outline-primary" type="button" data-bs-toggle="modal" data-bs-target="#modalTransaksiKas">
                        <i data-lucide="circle-dollar-sign" class="me-1"></i> Transaksi Kas
                    </button>
                @endif
                @if ($bolehKelolaJurnal)
                    <button class="btn btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#modalJurnalUmum">
                        <i data-lucide="book-open-check" class="me-1"></i> Jurnal Manual
                    </button>
                @endif
                @if ($bolehKelolaAkun)
                    <button class="btn btn-outline-secondary" type="button" data-bs-toggle="modal" data-bs-target="#modalAkunKeuangan">
                        <i data-lucide="list-tree" class="me-1"></i> Tambah Akun
                    </button>
                @endif
                @if ($bolehPemetaan)
                    <button class="btn btn-outline-secondary" type="button" data-bs-toggle="modal" data-bs-target="#modalPemetaanAkun">
                        <i data-lucide="waypoints" class="me-1"></i> Pemetaan Akun
                    </button>
                @endif
            </div>
        </div>

        <div class="card-body">
            <ul class="nav nav-tabs flex-nowrap overflow-auto" role="tablist">
                @foreach ([
                    'saldo-kas' => 'Saldo Kas & Bank',
                    'transaksi-kas' => 'Transaksi Kas',
                    'jurnal' => 'Jurnal Umum',
                    'neraca-saldo' => 'Neraca Saldo',
                    'akun' => 'Bagan Akun',
                    'pemetaan' => 'Pemetaan Akun',
                ] as $tab => $label)
                    <li class="nav-item" role="presentation">
                        <button class="nav-link {{ $loop->first ? 'active' : '' }}" type="button" data-bs-toggle="tab" data-bs-target="#tab-{{ $tab }}">{{ $label }}</button>
                    </li>
                @endforeach
            </ul>

            <div class="tab-content pt-3">
                <div class="tab-pane fade show active" id="tab-saldo-kas">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead><tr><th>Kode</th><th>Kas/Bank</th><th>Jenis</th><th class="text-end">Saldo Awal</th><th class="text-end">Masuk</th><th class="text-end">Keluar</th><th class="text-end">Saldo Berjalan</th></tr></thead>
                            <tbody>
                                @forelse ($saldoKasBank as $item)
                                    <tr>
                                        <td><span class="badge badge-soft-secondary">{{ $item->kode_kas_bank }}</span></td>
                                        <td><strong>{{ $item->nama_kas_bank }}</strong>@if($item->nomor_rekening)<br><small class="text-muted">{{ $item->nama_bank }} — {{ $item->nomor_rekening }}</small>@endif</td>
                                        <td>{{ $item->jenis_kas_bank }}</td>
                                        <td class="text-end">Rp {{ number_format($item->saldo_awal, 0, ',', '.') }}</td>
                                        <td class="text-end text-success">Rp {{ number_format($item->total_masuk, 0, ',', '.') }}</td>
                                        <td class="text-end text-danger">Rp {{ number_format($item->total_keluar, 0, ',', '.') }}</td>
                                        <td class="text-end fw-semibold">Rp {{ number_format($item->saldo_berjalan, 0, ',', '.') }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="7" class="text-center text-muted py-4">Belum ada kas atau bank aktif pada cabang ini.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="tab-pane fade" id="tab-transaksi-kas">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead><tr><th>Nomor</th><th>Tanggal</th><th>Jenis</th><th>Sumber</th><th>Tujuan/Kategori</th><th>Keterangan</th><th class="text-end">Nilai</th><th>Status</th><th class="text-end">Aksi</th></tr></thead>
                            <tbody>
                                @forelse ($transaksiKas as $item)
                                    <tr>
                                        <td><strong>{{ $item->nomor_transaksi }}</strong>@if($item->nomor_sumber)<br><small class="text-muted">Ref: {{ $item->nomor_sumber }}</small>@endif</td>
                                        <td>{{ \Carbon\Carbon::parse($item->tanggal_transaksi)->format('d-m-Y H:i') }}</td>
                                        <td><span class="badge badge-soft-info">{{ $item->jenis_transaksi }}</span></td>
                                        <td>{{ $item->nama_kas_bank }}</td>
                                        <td>{{ $item->nama_kas_bank_tujuan ?: ($item->nama_kategori_biaya ?: '-') }}</td>
                                        <td>{{ $item->keterangan }}</td>
                                        <td class="text-end fw-semibold">Rp {{ number_format($item->nilai_transaksi, 0, ',', '.') }}</td>
                                        <td><span class="badge badge-soft-{{ $item->status_transaksi === 'DISETUJUI' ? 'success' : ($item->status_transaksi === 'DIBATALKAN' ? 'danger' : 'warning') }}">{{ $item->status_transaksi }}</span></td>
                                        <td class="text-end text-nowrap">
                                            @if ($item->status_transaksi === 'DRAF' && $bolehSetujuiKas)
                                                <form class="d-inline" method="POST" action="{{ route('keuangan.transaksi-kas.setujui', $item->id_transaksi_kas) }}">
                                                    @csrf @method('PATCH')
                                                    <button class="btn btn-sm btn-success" onclick="return confirm('Setujui transaksi dan posting jurnal otomatis?')">Setujui</button>
                                                </form>
                                            @endif
                                            @if ($item->status_transaksi === 'DRAF' && $bolehKelolaKas)
                                                <form class="d-inline" method="POST" action="{{ route('keuangan.transaksi-kas.batalkan', $item->id_transaksi_kas) }}">
                                                    @csrf @method('PATCH')
                                                    <button class="btn btn-sm btn-light" onclick="return confirm('Batalkan transaksi ini?')">Batalkan</button>
                                                </form>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="9" class="text-center text-muted py-4">Belum ada transaksi kas pada periode ini.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="tab-pane fade" id="tab-jurnal">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead><tr><th>Nomor</th><th>Tanggal</th><th>Sumber</th><th>Keterangan</th><th class="text-end">Debet</th><th class="text-end">Kredit</th><th>Status</th><th class="text-end">Aksi</th></tr></thead>
                            <tbody>
                                @forelse ($jurnal as $item)
                                    <tr>
                                        <td><strong>{{ $item->nomor_jurnal }}</strong>@if($item->nomor_sumber)<br><small class="text-muted">{{ $item->nomor_sumber }}</small>@endif</td>
                                        <td>{{ \Carbon\Carbon::parse($item->tanggal_jurnal)->format('d-m-Y') }}</td>
                                        <td>{{ $item->sumber_jurnal ?: 'MANUAL' }}</td>
                                        <td>{{ $item->keterangan }}</td>
                                        <td class="text-end">Rp {{ number_format($item->total_debet, 0, ',', '.') }}</td>
                                        <td class="text-end">Rp {{ number_format($item->total_kredit, 0, ',', '.') }}</td>
                                        <td><span class="badge badge-soft-{{ $item->status_jurnal === 'DIPOSTING' ? 'success' : ($item->status_jurnal === 'DIBATALKAN' ? 'danger' : 'warning') }}">{{ $item->status_jurnal }}</span></td>
                                        <td class="text-end text-nowrap">
                                            @if ($item->status_jurnal === 'DRAF' && $bolehPostingJurnal)
                                                <form class="d-inline" method="POST" action="{{ route('keuangan.jurnal.posting', $item->id_jurnal_umum) }}">
                                                    @csrf @method('PATCH')
                                                    <button class="btn btn-sm btn-success" onclick="return confirm('Posting jurnal ini? Setelah diposting jurnal tidak dapat diubah.')">Posting</button>
                                                </form>
                                            @endif
                                            @if ($item->status_jurnal === 'DRAF' && $bolehKelolaJurnal)
                                                <form class="d-inline" method="POST" action="{{ route('keuangan.jurnal.batalkan', $item->id_jurnal_umum) }}">
                                                    @csrf @method('PATCH')
                                                    <button class="btn btn-sm btn-light" onclick="return confirm('Batalkan jurnal ini?')">Batalkan</button>
                                                </form>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="8" class="text-center text-muted py-4">Belum ada jurnal pada periode ini.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="tab-pane fade" id="tab-neraca-saldo">
                    <div class="row g-3 mb-3">
                        <div class="col-md-6"><div class="alert alert-info mb-0"><strong>Laba/Rugi Periode:</strong> Rp {{ number_format($ringkasan['laba_rugi'], 0, ',', '.') }}</div></div>
                        <div class="col-md-6"><div class="alert {{ abs($ringkasan['total_aset'] - $ringkasan['total_kewajiban_modal']) < 0.01 ? 'alert-success' : 'alert-warning' }} mb-0"><strong>Persamaan Akuntansi:</strong> Aset Rp {{ number_format($ringkasan['total_aset'], 0, ',', '.') }} vs Kewajiban + Modal + Laba Rp {{ number_format($ringkasan['total_kewajiban_modal'], 0, ',', '.') }}</div></div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead><tr><th>Kode</th><th>Nama Akun</th><th>Kelompok</th><th>Saldo Normal</th><th class="text-end">Debet</th><th class="text-end">Kredit</th><th class="text-end">Saldo</th></tr></thead>
                            <tbody>
                                @forelse ($saldoAkun as $item)
                                    <tr>
                                        <td><strong>{{ $item->kode_akun }}</strong></td><td>{{ $item->nama_akun }}</td><td>{{ $item->kelompok_akun }}</td><td>{{ $item->saldo_normal }}</td>
                                        <td class="text-end">Rp {{ number_format($item->total_debet, 0, ',', '.') }}</td><td class="text-end">Rp {{ number_format($item->total_kredit, 0, ',', '.') }}</td><td class="text-end fw-semibold">Rp {{ number_format($item->saldo, 0, ',', '.') }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="7" class="text-center text-muted py-4">Belum ada akun rincian aktif.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="tab-pane fade" id="tab-akun">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead><tr><th>Kode</th><th>Nama Akun</th><th>Induk</th><th>Kelompok</th><th>Normal</th><th>Tipe</th><th>Status</th><th class="text-end">Aksi</th></tr></thead>
                            <tbody>
                                @forelse ($akun as $item)
                                    <tr>
                                        <td><strong>{{ $item->kode_akun }}</strong></td><td>{{ $item->nama_akun }}</td><td>{{ $item->kode_akun_induk ? $item->kode_akun_induk.' — '.$item->nama_akun_induk : '-' }}</td><td>{{ $item->kelompok_akun }}</td><td>{{ $item->saldo_normal }}</td><td>{{ $item->akun_rincian ? 'Rincian' : 'Induk' }}</td><td><span class="badge badge-soft-{{ $item->status_aktif ? 'success' : 'secondary' }}">{{ $item->status_aktif ? 'AKTIF' : 'NONAKTIF' }}</span></td>
                                        <td class="text-end">@if($bolehKelolaAkun)<button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalUbahAkun{{ $item->id_akun_keuangan }}">Ubah</button>@endif</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="8" class="text-center text-muted py-4">Bagan akun belum disiapkan.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="tab-pane fade" id="tab-pemetaan">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead><tr><th>Kunci</th><th>Cakupan</th><th>Akun</th><th>Keterangan</th></tr></thead>
                            <tbody>
                                @forelse ($pemetaan as $item)
                                    <tr><td><code>{{ $item->kunci_pemetaan }}</code></td><td>{{ $item->id_cabang ? 'Cabang aktif' : 'Global' }}</td><td><strong>{{ $item->kode_akun }}</strong> — {{ $item->nama_akun }}</td><td>{{ $item->keterangan ?: '-' }}</td></tr>
                                @empty
                                    <tr><td colspan="4" class="text-center text-muted py-4">Belum ada pemetaan akun.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if ($bolehKelolaKas)
        <div class="modal fade" id="modalTransaksiKas" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                <form class="modal-content" method="POST" action="{{ route('keuangan.transaksi-kas.simpan') }}">
                    @csrf
                    <div class="modal-header"><h5 class="modal-title">Transaksi Kas / Bank</h5><button class="btn-close" type="button" data-bs-dismiss="modal"></button></div>
                    <div class="modal-body">
                        <div class="alert alert-info">Transaksi disimpan sebagai DRAF. Saldo dan jurnal baru berubah setelah transaksi disetujui.</div>
                        <div class="row g-3">
                            <div class="col-md-4"><label class="form-label">Jenis Transaksi</label><select class="form-select" name="jenis_transaksi" required><option value="MASUK">Kas Masuk</option><option value="KELUAR">Kas Keluar</option><option value="PINDAH">Pindah Kas/Bank</option></select></div>
                            <div class="col-md-4"><label class="form-label">Kas/Bank Sumber</label><select class="form-select" name="id_kas_bank" required><option value="">Pilih</option>@foreach($kasBankPilihan as $item)<option value="{{ $item->id_kas_bank }}">{{ $item->kode_kas_bank }} — {{ $item->nama_kas_bank }}</option>@endforeach</select></div>
                            <div class="col-md-4"><label class="form-label">Kas/Bank Tujuan <small class="text-muted">(khusus PINDAH)</small></label><select class="form-select" name="id_kas_bank_tujuan"><option value="">Tidak ada</option>@foreach($kasBankPilihan as $item)<option value="{{ $item->id_kas_bank }}">{{ $item->kode_kas_bank }} — {{ $item->nama_kas_bank }}</option>@endforeach</select></div>
                            <div class="col-md-4"><label class="form-label">Kategori Biaya <small class="text-muted">(kas keluar)</small></label><select class="form-select" name="id_kategori_biaya"><option value="">Beban operasional umum</option>@foreach($kategoriBiayaPilihan as $item)<option value="{{ $item->id_kategori_biaya }}">{{ $item->kode_kategori_biaya }} — {{ $item->nama_kategori_biaya }}</option>@endforeach</select></div>
                            <div class="col-md-4"><label class="form-label">Tanggal dan Waktu</label><input class="form-control" type="datetime-local" name="tanggal_transaksi" value="{{ now()->format('Y-m-d\TH:i') }}" required></div>
                            <div class="col-md-4"><label class="form-label">Nilai</label><input class="form-control" type="number" name="nilai_transaksi" min="0.01" step="0.01" required></div>
                            <div class="col-md-4"><label class="form-label">Sumber Transaksi</label><input class="form-control" name="sumber_transaksi" placeholder="Mis. SETORAN_MODAL"></div>
                            <div class="col-md-4"><label class="form-label">ID Sumber</label><input class="form-control" type="number" min="1" name="id_sumber"></div>
                            <div class="col-md-4"><label class="form-label">Nomor Referensi</label><input class="form-control" name="nomor_sumber"></div>
                            <div class="col-12"><label class="form-label">Keterangan</label><textarea class="form-control" name="keterangan" rows="3" required></textarea></div>
                        </div>
                    </div>
                    <div class="modal-footer"><button class="btn btn-light" type="button" data-bs-dismiss="modal">Tutup</button><button class="btn btn-primary" type="submit">Simpan Draf</button></div>
                </form>
            </div>
        </div>
    @endif

    @if ($bolehKelolaJurnal)
        <div class="modal fade" id="modalJurnalUmum" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-scrollable">
                <form class="modal-content" method="POST" action="{{ route('keuangan.jurnal.simpan') }}">
                    @csrf
                    <div class="modal-header"><h5 class="modal-title">Jurnal Umum Manual</h5><button class="btn-close" type="button" data-bs-dismiss="modal"></button></div>
                    <div class="modal-body">
                        <div class="row g-3 mb-3">
                            <div class="col-md-3"><label class="form-label">Tanggal</label><input class="form-control" type="date" name="tanggal_jurnal" value="{{ now()->toDateString() }}" required></div>
                            <div class="col-md-3"><label class="form-label">Sumber</label><input class="form-control" name="sumber_jurnal" value="MANUAL"></div>
                            <div class="col-md-3"><label class="form-label">ID Sumber</label><input class="form-control" type="number" min="1" name="id_sumber"></div>
                            <div class="col-md-3"><label class="form-label">Nomor Referensi</label><input class="form-control" name="nomor_sumber"></div>
                            <div class="col-12"><label class="form-label">Keterangan</label><textarea class="form-control" name="keterangan" rows="2" required></textarea></div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-bordered align-middle" id="tabelDetailJurnal">
                                <thead><tr><th style="min-width:260px">Akun Rincian</th><th style="min-width:150px">Debet</th><th style="min-width:150px">Kredit</th><th style="min-width:220px">Keterangan</th><th width="60"></th></tr></thead>
                                <tbody>
                                    @for($i = 0; $i < 2; $i++)
                                        <tr class="baris-jurnal">
                                            <td><select class="form-select" name="detail[{{ $i }}][id_akun_keuangan]" required><option value="">Pilih akun</option>@foreach($akunRincian as $item)<option value="{{ $item->id_akun_keuangan }}">{{ $item->kode_akun }} — {{ $item->nama_akun }}</option>@endforeach</select></td>
                                            <td><input class="form-control" type="number" name="detail[{{ $i }}][debet]" min="0" step="0.01" value="0"></td>
                                            <td><input class="form-control" type="number" name="detail[{{ $i }}][kredit]" min="0" step="0.01" value="0"></td>
                                            <td><input class="form-control" name="detail[{{ $i }}][keterangan]"></td>
                                            <td><button class="btn btn-sm btn-outline-danger hapus-baris-jurnal" type="button" title="Hapus"><i data-lucide="trash-2"></i></button></td>
                                        </tr>
                                    @endfor
                                </tbody>
                            </table>
                        </div>
                        <button class="btn btn-sm btn-outline-primary" id="tambahBarisJurnal" type="button"><i data-lucide="plus" class="me-1"></i> Tambah Baris</button>
                        <div class="text-muted small mt-2">Setiap baris hanya boleh memiliki nilai pada salah satu sisi. Total debet wajib sama dengan total kredit.</div>
                    </div>
                    <div class="modal-footer"><button class="btn btn-light" type="button" data-bs-dismiss="modal">Tutup</button><button class="btn btn-primary" type="submit">Simpan Draf</button></div>
                </form>
            </div>
        </div>
    @endif

    @if ($bolehKelolaAkun)
        <div class="modal fade" id="modalAkunKeuangan" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <form class="modal-content" method="POST" action="{{ route('keuangan.akun.simpan') }}">
                    @csrf
                    <div class="modal-header"><h5 class="modal-title">Tambah Akun Keuangan</h5><button class="btn-close" type="button" data-bs-dismiss="modal"></button></div>
                    <div class="modal-body">
                        @include('keuangan.partials.form-akun', ['akunData' => null])
                    </div>
                    <div class="modal-footer"><button class="btn btn-light" type="button" data-bs-dismiss="modal">Tutup</button><button class="btn btn-primary" type="submit">Simpan</button></div>
                </form>
            </div>
        </div>

        @foreach($akun as $akunData)
            <div class="modal fade" id="modalUbahAkun{{ $akunData->id_akun_keuangan }}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <form class="modal-content" method="POST" action="{{ route('keuangan.akun.ubah', $akunData->id_akun_keuangan) }}">
                        @csrf @method('PUT')
                        <div class="modal-header"><h5 class="modal-title">Ubah Akun {{ $akunData->kode_akun }}</h5><button class="btn-close" type="button" data-bs-dismiss="modal"></button></div>
                        <div class="modal-body">@include('keuangan.partials.form-akun', ['akunData' => $akunData])</div>
                        <div class="modal-footer"><button class="btn btn-light" type="button" data-bs-dismiss="modal">Tutup</button><button class="btn btn-primary" type="submit">Simpan Perubahan</button></div>
                    </form>
                </div>
            </div>
        @endforeach
    @endif

    @if ($bolehPemetaan)
        <div class="modal fade" id="modalPemetaanAkun" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <form class="modal-content" method="POST" action="{{ route('keuangan.pemetaan.simpan') }}">
                    @csrf
                    <div class="modal-header"><h5 class="modal-title">Pemetaan Akun Cabang</h5><button class="btn-close" type="button" data-bs-dismiss="modal"></button></div>
                    <div class="modal-body">
                        <div class="alert alert-warning">Pemetaan cabang akan diprioritaskan dibanding pemetaan global. Gunakan kunci yang konsisten, misalnya <code>KAS_BANK_1</code> atau <code>BEBAN_OPERASIONAL</code>.</div>
                        <div class="row g-3">
                            <div class="col-md-5"><label class="form-label">Kunci Pemetaan</label><input class="form-control text-uppercase" name="kunci_pemetaan" maxlength="100" required></div>
                            <div class="col-md-7"><label class="form-label">Akun Rincian</label><select class="form-select" name="id_akun_keuangan" required><option value="">Pilih akun</option>@foreach($akunRincian as $item)<option value="{{ $item->id_akun_keuangan }}">{{ $item->kode_akun }} — {{ $item->nama_akun }}</option>@endforeach</select></div>
                            <div class="col-12"><label class="form-label">Keterangan</label><textarea class="form-control" name="keterangan" rows="2"></textarea></div>
                        </div>
                    </div>
                    <div class="modal-footer"><button class="btn btn-light" type="button" data-bs-dismiss="modal">Tutup</button><button class="btn btn-primary" type="submit">Simpan Pemetaan</button></div>
                </form>
            </div>
        </div>
    @endif

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const tabel = document.querySelector('#tabelDetailJurnal tbody');
            const tombolTambah = document.getElementById('tambahBarisJurnal');
            if (!tabel || !tombolTambah) return;

            function susunUlangNama() {
                tabel.querySelectorAll('.baris-jurnal').forEach(function (baris, indeks) {
                    baris.querySelectorAll('[name]').forEach(function (input) {
                        input.name = input.name.replace(/detail\[\d+\]/, 'detail[' + indeks + ']');
                    });
                });
            }

            tombolTambah.addEventListener('click', function () {
                const contoh = tabel.querySelector('.baris-jurnal');
                if (!contoh) return;
                const baru = contoh.cloneNode(true);
                baru.querySelectorAll('input').forEach(function (input) { input.value = input.type === 'number' ? '0' : ''; });
                baru.querySelectorAll('select').forEach(function (select) { select.selectedIndex = 0; });
                tabel.appendChild(baru);
                susunUlangNama();
            });

            tabel.addEventListener('click', function (event) {
                const tombol = event.target.closest('.hapus-baris-jurnal');
                if (!tombol) return;
                if (tabel.querySelectorAll('.baris-jurnal').length <= 2) return;
                tombol.closest('.baris-jurnal').remove();
                susunUlangNama();
            });
        });
    </script>
@endsection
