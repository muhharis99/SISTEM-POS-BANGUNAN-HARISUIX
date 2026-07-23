@extends('layouts.admin')

@section('judul', 'Kartu Stok')

@section('breadcrumb')
    <div class="d-flex align-items-center justify-content-between py-3">
        <div><h4 class="fs-18 fw-semibold mb-1">Kartu Stok {{ $barang->nama_barang }}</h4><p class="text-muted mb-0">{{ $barang->kode_barang }}</p></div>
        <a class="btn btn-light" href="{{ route('persediaan.index') }}">Kembali</a>
    </div>
@endsection

@section('content')
    <div class="card">
        <div class="card-header">
            <form class="row g-2 align-items-end">
                <div class="col-md-3"><label class="form-label">Tanggal awal</label><input class="form-control" type="date" name="tanggal_awal" value="{{ request('tanggal_awal') }}"></div>
                <div class="col-md-3"><label class="form-label">Tanggal akhir</label><input class="form-control" type="date" name="tanggal_akhir" value="{{ request('tanggal_akhir') }}"></div>
                <div class="col-auto"><button class="btn btn-primary">Terapkan</button></div>
            </form>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead><tr><th>Waktu</th><th>Dokumen</th><th>Gudang / Lokasi</th><th>Jenis</th><th class="text-end">Masuk</th><th class="text-end">Keluar</th><th class="text-end">HPP</th><th class="text-end">Saldo</th><th>Keterangan</th></tr></thead>
                <tbody>
                    @forelse ($mutasi as $item)
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($item->tanggal_mutasi)->format('d-m-Y H:i:s') }}</td>
                            <td>{{ $item->nomor_dokumen ?: '-' }}<br><small class="text-muted">{{ $item->jenis_dokumen }}</small></td>
                            <td>{{ $item->nama_gudang }}<br><small class="text-muted">{{ $item->nama_lokasi }}</small></td>
                            <td>{{ str_replace('_', ' ', $item->jenis_mutasi) }}</td>
                            <td class="text-end text-success">{{ number_format($item->jumlah_masuk, 3, ',', '.') }}</td>
                            <td class="text-end text-danger">{{ number_format($item->jumlah_keluar, 3, ',', '.') }}</td>
                            <td class="text-end">Rp{{ number_format($item->harga_pokok, 2, ',', '.') }}</td>
                            <td class="text-end fw-semibold">{{ number_format($item->saldo_setelah, 3, ',', '.') }}</td>
                            <td>{{ $item->keterangan ?: '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="text-center text-muted py-4">Belum ada mutasi untuk barang ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $mutasi->links() }}</div>
    </div>
@endsection
