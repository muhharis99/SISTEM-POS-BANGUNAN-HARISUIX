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
