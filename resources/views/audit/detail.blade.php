@extends('layouts.admin')

@section('judul', 'Detail Audit Aktivitas')

@section('breadcrumb')
    <div class="d-flex align-items-center justify-content-between py-3">
        <div>
            <h4 class="fs-18 fw-semibold mb-1">Detail Audit Aktivitas</h4>
            <p class="text-muted mb-0">Data sebelum dan sesudah telah melalui penyamaran field sensitif.</p>
        </div>
        <a class="btn btn-light" href="{{ url()->previous() }}">Kembali</a>
    </div>
@endsection

@section('content')
    <div class="row g-3">
        <div class="col-lg-5">
            <div class="card h-100">
                <div class="card-header"><strong>Informasi Aktivitas</strong></div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">Waktu</dt><dd class="col-sm-8">{{ optional($aktivitas->tanggal_aktivitas)->format('d-m-Y H:i:s') }}</dd>
                        <dt class="col-sm-4">Pengguna</dt><dd class="col-sm-8">{{ $aktivitas->nama_tampilan ?: $aktivitas->nama_pengguna ?: '-' }}</dd>
                        <dt class="col-sm-4">Cabang</dt><dd class="col-sm-8">{{ $aktivitas->nama_cabang ?: 'Global' }}</dd>
                        <dt class="col-sm-4">Modul</dt><dd class="col-sm-8">{{ $aktivitas->nama_modul }}</dd>
                        <dt class="col-sm-4">Aktivitas</dt><dd class="col-sm-8"><span class="badge badge-soft-primary">{{ $aktivitas->jenis_aktivitas }}</span></dd>
                        <dt class="col-sm-4">Referensi</dt><dd class="col-sm-8">{{ $aktivitas->nama_tabel ? $aktivitas->nama_tabel.' #'.$aktivitas->id_referensi : '-' }}</dd>
                        <dt class="col-sm-4">IP</dt><dd class="col-sm-8">{{ $aktivitas->alamat_ip ?: '-' }}</dd>
                        <dt class="col-sm-4">Peramban</dt><dd class="col-sm-8 text-break">{{ $aktivitas->peramban ?: '-' }}</dd>
                        <dt class="col-sm-4">Keterangan</dt><dd class="col-sm-8">{{ $aktivitas->keterangan ?: '-' }}</dd>
                    </dl>
                </div>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="card mb-3">
                <div class="card-header"><strong>Data Sebelum</strong></div>
                <div class="card-body">
                    <pre class="bg-light rounded p-3 mb-0 text-wrap">{{ json_encode($aktivitas->data_sebelum, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '-' }}</pre>
                </div>
            </div>
            <div class="card">
                <div class="card-header"><strong>Data Sesudah</strong></div>
                <div class="card-body">
                    <pre class="bg-light rounded p-3 mb-0 text-wrap">{{ json_encode($aktivitas->data_sesudah, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '-' }}</pre>
                </div>
            </div>
        </div>
    </div>
@endsection
