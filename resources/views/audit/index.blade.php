@extends('layouts.admin')

@section('judul', 'Audit Aktivitas')

@section('breadcrumb')
    <div class="d-flex align-items-center justify-content-between py-3">
        <div><h4 class="fs-18 fw-semibold mb-1">Audit Aktivitas</h4><p class="text-muted mb-0">Riwayat keamanan dan perubahan pada cabang aktif.</p></div>
    </div>
@endsection

@section('content')
    <div class="card">
        <div class="card-header">
            <form method="GET" class="row g-2">
                <div class="col-md-5"><input class="form-control" name="pencarian" value="{{ $pencarian }}" placeholder="Cari modul, aktivitas, keterangan, atau IP"></div>
                <div class="col-auto"><button class="btn btn-primary" type="submit">Cari</button></div>
                @if ($pencarian !== '')<div class="col-auto"><a class="btn btn-light" href="{{ route('audit.index') }}">Reset</a></div>@endif
            </form>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead><tr><th>Waktu</th><th>Pengguna</th><th>Modul</th><th>Aktivitas</th><th>Referensi</th><th>Keterangan</th><th>IP</th></tr></thead>
                <tbody>
                    @forelse ($aktivitas as $item)
                        <tr>
                            <td class="text-nowrap">{{ optional($item->tanggal_aktivitas)->format('d-m-Y H:i:s') }}</td>
                            <td>{{ $item->id_pengguna ?: '-' }}</td>
                            <td>{{ $item->nama_modul }}</td>
                            <td><span class="badge badge-soft-primary">{{ $item->jenis_aktivitas }}</span></td>
                            <td>{{ $item->nama_tabel ? $item->nama_tabel.' #'.$item->id_referensi : '-' }}</td>
                            <td>{{ $item->keterangan ?: '-' }}</td>
                            <td>{{ $item->alamat_ip ?: '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-4">Tidak ada data audit.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $aktivitas->links() }}</div>
    </div>
@endsection
