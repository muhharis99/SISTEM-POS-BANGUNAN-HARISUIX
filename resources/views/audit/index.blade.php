@extends('layouts.admin')

@section('judul', 'Audit Aktivitas')

@section('breadcrumb')
    <div class="d-flex align-items-center justify-content-between py-3">
        <div>
            <h4 class="fs-18 fw-semibold mb-1">Audit Aktivitas</h4>
            <p class="text-muted mb-0">Riwayat keamanan, perubahan data, unduhan, dan persetujuan pada cabang aktif.</p>
        </div>
        @if ($bolehUnduh)
            <a class="btn btn-outline-primary" href="{{ route('audit.unduh', request()->query()) }}">
                <i data-lucide="download" class="me-1"></i> Unduh CSV
            </a>
        @endif
    </div>
@endsection

@section('content')
    <div class="card mb-3">
        <div class="card-header"><strong>Filter Audit</strong></div>
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-2">
                    <label class="form-label">Tanggal Awal</label>
                    <input class="form-control" type="date" name="tanggal_awal" value="{{ $filter['tanggal_awal'] }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Tanggal Akhir</label>
                    <input class="form-control" type="date" name="tanggal_akhir" value="{{ $filter['tanggal_akhir'] }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Pengguna</label>
                    <select class="form-select" name="id_pengguna">
                        <option value="">Semua</option>
                        @foreach ($penggunaPilihan as $pengguna)
                            <option value="{{ $pengguna->id_pengguna }}" @selected((int) $filter['id_pengguna'] === (int) $pengguna->id_pengguna)>
                                {{ $pengguna->nama_tampilan }} ({{ $pengguna->nama_pengguna }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Modul</label>
                    <select class="form-select" name="nama_modul">
                        <option value="">Semua</option>
                        @foreach ($modulPilihan as $modul)
                            <option value="{{ $modul }}" @selected($filter['nama_modul'] === $modul)>{{ $modul }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Aktivitas</label>
                    <select class="form-select" name="jenis_aktivitas">
                        <option value="">Semua</option>
                        @foreach ($jenisPilihan as $jenis)
                            <option value="{{ $jenis }}" @selected($filter['jenis_aktivitas'] === $jenis)>{{ $jenis }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Alamat IP</label>
                    <input class="form-control" name="alamat_ip" value="{{ $filter['alamat_ip'] }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Nama Tabel</label>
                    <input class="form-control" name="nama_tabel" value="{{ $filter['nama_tabel'] }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">ID Referensi</label>
                    <input class="form-control" type="number" min="1" name="id_referensi" value="{{ $filter['id_referensi'] ?: '' }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Pencarian</label>
                    <input class="form-control" name="pencarian" value="{{ $filter['pencarian'] }}" placeholder="Modul, pengguna, keterangan, atau IP">
                </div>
                <div class="col-auto"><button class="btn btn-primary">Terapkan</button></div>
                <div class="col-auto"><a class="btn btn-light" href="{{ route('audit.index') }}">Reset</a></div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Waktu</th>
                        <th>Pengguna</th>
                        <th>Modul</th>
                        <th>Aktivitas</th>
                        <th>Referensi</th>
                        <th>Keterangan</th>
                        <th>IP</th>
                        @if ($bolehLihatData)<th class="text-end">Detail</th>@endif
                    </tr>
                </thead>
                <tbody>
                    @forelse ($aktivitas as $item)
                        <tr>
                            <td class="text-nowrap">{{ optional($item->tanggal_aktivitas)->format('d-m-Y H:i:s') }}</td>
                            <td>{{ $item->nama_tampilan ?: $item->nama_pengguna ?: '-' }}</td>
                            <td>{{ $item->nama_modul }}</td>
                            <td><span class="badge badge-soft-primary">{{ $item->jenis_aktivitas }}</span></td>
                            <td>{{ $item->nama_tabel ? $item->nama_tabel.' #'.$item->id_referensi : '-' }}</td>
                            <td>{{ $item->keterangan ?: '-' }}</td>
                            <td>{{ $item->alamat_ip ?: '-' }}</td>
                            @if ($bolehLihatData)
                                <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="{{ route('audit.detail', $item->id_log_aktivitas) }}">Lihat</a></td>
                            @endif
                        </tr>
                    @empty
                        <tr><td colspan="{{ $bolehLihatData ? 8 : 7 }}" class="text-center text-muted py-4">Tidak ada data audit pada filter ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $aktivitas->links() }}</div>
    </div>
@endsection
