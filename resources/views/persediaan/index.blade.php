@extends('layouts.admin')

@section('judul', 'Persediaan')

@section('breadcrumb')
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 py-3">
        <div>
            <h4 class="fs-18 fw-semibold mb-1">Persediaan dan Mutasi Stok</h4>
            <p class="text-muted mb-0">Saldo per gudang/lokasi, stok tersedia, stok rusak, dan riwayat pergerakan barang.</p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            @if (auth()->user()->memilikiHakAkses('STOK_AWAL_KELOLA', session('id_cabang_aktif')))
                <a class="btn btn-outline-primary" href="{{ route('stok-awal.index') }}">Stok Awal</a>
            @endif
            @if (auth()->user()->memilikiHakAkses('TRANSFER_STOK_KELOLA', session('id_cabang_aktif')))
                <a class="btn btn-outline-primary" href="{{ route('transfer-stok.index') }}">Transfer</a>
            @endif
            @if (auth()->user()->memilikiHakAkses('STOK_OPNAME_KELOLA', session('id_cabang_aktif')))
                <a class="btn btn-outline-primary" href="{{ route('stok-opname.index') }}">Stok Opname</a>
            @endif
            @if (auth()->user()->memilikiHakAkses('PENYESUAIAN_STOK_KELOLA', session('id_cabang_aktif')))
                <a class="btn btn-primary" href="{{ route('penyesuaian-stok.index') }}">Penyesuaian</a>
            @endif
        </div>
    </div>
@endsection

@section('content')
    @if (session('berhasil'))
        <div class="alert alert-success">{{ session('berhasil') }}</div>
    @endif

    <div class="row g-3 mb-3">
        @foreach ([
            ['Barang bersaldo', $ringkasan->jumlah_barang ?? 0, 'package-search'],
            ['Stok fisik', number_format($ringkasan->jumlah_stok ?? 0, 3, ',', '.'), 'boxes'],
            ['Stok tersedia', number_format($ringkasan->jumlah_tersedia ?? 0, 3, ',', '.'), 'badge-check'],
            ['Stok rusak', number_format($ringkasan->jumlah_rusak ?? 0, 3, ',', '.'), 'package-x'],
        ] as [$label, $nilai, $ikon])
            <div class="col-xl-3 col-md-6">
                <div class="card mb-0 h-100">
                    <div class="card-body d-flex align-items-center justify-content-between">
                        <div><p class="text-muted mb-1">{{ $label }}</p><h4 class="mb-0">{{ $nilai }}</h4></div>
                        <span class="avatar-md bg-primary-subtle text-primary rounded-circle d-flex align-items-center justify-content-center"><i data-lucide="{{ $ikon }}"></i></span>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="card mb-3">
        <div class="card-header">
            <form class="row g-2 align-items-end">
                <div class="col-lg-5">
                    <label class="form-label">Pencarian</label>
                    <input class="form-control" name="pencarian" value="{{ $pencarian }}" placeholder="Kode/nama barang, gudang, atau lokasi">
                </div>
                <div class="col-lg-3">
                    <label class="form-label">Gudang</label>
                    <select class="form-select" name="id_gudang">
                        <option value="">Semua gudang</option>
                        @foreach ($gudang as $item)
                            <option value="{{ $item->id_gudang }}" @selected($idGudang === (int) $item->id_gudang)>{{ $item->nama_gudang }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-2">
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" name="menipis" value="1" id="menipis" @checked($hanyaMenipis)>
                        <label class="form-check-label" for="menipis">Stok menipis</label>
                    </div>
                </div>
                <div class="col-auto"><button class="btn btn-primary">Terapkan</button></div>
                <div class="col-auto"><a class="btn btn-light" href="{{ route('persediaan.index') }}">Reset</a></div>
            </form>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead><tr><th>Barang</th><th>Gudang / Lokasi</th><th class="text-end">Fisik</th><th class="text-end">Dipesan</th><th class="text-end">Rusak</th><th class="text-end">Tersedia</th><th class="text-end">HPP rata-rata</th><th></th></tr></thead>
                <tbody>
                    @forelse ($saldo as $item)
                        @php $menipis = (float) $item->jumlah_tersedia <= (float) $item->stok_minimum; @endphp
                        <tr>
                            <td><strong>{{ $item->nama_barang }}</strong><br><small class="text-muted">{{ $item->kode_barang }} · {{ $item->satuan_dasar }}</small></td>
                            <td>{{ $item->nama_gudang }}<br><small class="text-muted">{{ $item->kode_lokasi }} — {{ $item->nama_lokasi }}</small></td>
                            <td class="text-end">{{ number_format($item->jumlah_stok, 3, ',', '.') }}</td>
                            <td class="text-end">{{ number_format($item->jumlah_dipesan, 3, ',', '.') }}</td>
                            <td class="text-end">{{ number_format($item->jumlah_rusak, 3, ',', '.') }}</td>
                            <td class="text-end"><span class="badge badge-soft-{{ $menipis ? 'danger' : 'success' }}">{{ number_format($item->jumlah_tersedia, 3, ',', '.') }}</span></td>
                            <td class="text-end">Rp{{ number_format($item->harga_pokok_rata_rata, 2, ',', '.') }}</td>
                            <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="{{ route('persediaan.kartu', $item->id_barang) }}">Kartu stok</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-muted py-4">Belum ada saldo stok pada cabang aktif.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $saldo->links() }}</div>
    </div>

    <div class="card">
        <div class="card-header"><h5 class="mb-0">30 Mutasi Terakhir</h5></div>
        <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0">
                <thead><tr><th>Waktu</th><th>Dokumen</th><th>Barang</th><th>Gudang / Lokasi</th><th>Jenis</th><th class="text-end">Masuk</th><th class="text-end">Keluar</th><th class="text-end">Saldo</th></tr></thead>
                <tbody>
                    @forelse ($mutasi as $item)
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($item->tanggal_mutasi)->format('d-m-Y H:i') }}</td>
                            <td>{{ $item->nomor_dokumen ?: '-' }}<br><small class="text-muted">{{ $item->jenis_dokumen }}</small></td>
                            <td>{{ $item->kode_barang }} — {{ $item->nama_barang }}</td>
                            <td>{{ $item->nama_gudang }} / {{ $item->nama_lokasi }}</td>
                            <td><span class="badge badge-soft-info">{{ str_replace('_', ' ', $item->jenis_mutasi) }}</span></td>
                            <td class="text-end text-success">{{ number_format($item->jumlah_masuk, 3, ',', '.') }}</td>
                            <td class="text-end text-danger">{{ number_format($item->jumlah_keluar, 3, ',', '.') }}</td>
                            <td class="text-end fw-semibold">{{ number_format($item->saldo_setelah, 3, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-muted py-4">Belum ada mutasi stok.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
