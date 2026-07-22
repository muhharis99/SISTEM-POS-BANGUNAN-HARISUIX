@extends('layouts.admin')

@section('judul', 'Dashboard')

@section('breadcrumb')
    <div class="d-flex align-items-center justify-content-between py-3">
        <div>
            <h4 class="fs-18 fw-semibold mb-1">Dashboard Fondasi</h4>
            <p class="text-muted mb-0">Status awal Sistem POS Toko Bangunan.</p>
        </div>
        <span class="badge badge-soft-primary fs-sm">Fase 1</span>
    </div>
@endsection

@section('content')
    <div class="row g-3 mb-4">
        @php
            $ringkasan = [
                ['judul' => 'Framework', 'nilai' => 'Laravel 13', 'ikon' => 'boxes', 'kelas' => 'primary'],
                ['judul' => 'Runtime', 'nilai' => 'PHP 8.4', 'ikon' => 'code-xml', 'kelas' => 'success'],
                ['judul' => 'Database', 'nilai' => 'MySQL 8', 'ikon' => 'database', 'kelas' => 'warning'],
                ['judul' => 'Template', 'nilai' => 'UBold', 'ikon' => 'panel-top', 'kelas' => 'info'],
            ];
        @endphp

        @foreach ($ringkasan as $item)
            <div class="col-xl-3 col-sm-6">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center gap-3">
                            <div class="avatar-md rounded bg-{{ $item['kelas'] }}-subtle text-{{ $item['kelas'] }} d-flex align-items-center justify-content-center">
                                <i data-lucide="{{ $item['ikon'] }}" class="fs-24"></i>
                            </div>
                            <div>
                                <p class="text-muted mb-1">{{ $item['judul'] }}</p>
                                <h4 class="mb-0">{{ $item['nilai'] }}</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="row g-3">
        <div class="col-xl-8">
            <div class="card">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h5 class="card-title mb-0">Checklist Fondasi</h5>
                    <i data-lucide="list-checks" class="text-muted"></i>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-custom table-centered table-hover mb-0">
                            <thead class="bg-light bg-opacity-25">
                                <tr>
                                    <th>Komponen</th>
                                    <th>Status</th>
                                    <th>Keterangan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Struktur Laravel</td>
                                    <td><span class="badge badge-soft-success">Tersedia</span></td>
                                    <td>Bootstrap aplikasi, route, config, dan view dasar.</td>
                                </tr>
                                <tr>
                                    <td>Template UBold</td>
                                    <td><span class="badge badge-soft-success">Terintegrasi</span></td>
                                    <td>Layout, topbar, sidebar, footer, dan pengaturan tema.</td>
                                </tr>
                                <tr>
                                    <td>Baseline database</td>
                                    <td><span class="badge badge-soft-warning">Perlu dijalankan</span></td>
                                    <td>Jalankan migration pada database development kosong.</td>
                                </tr>
                                <tr>
                                    <td>Autentikasi</td>
                                    <td><span class="badge badge-soft-secondary">Fase 2</span></td>
                                    <td>Belum diaktifkan pada fase fondasi.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Perintah Verifikasi</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted">Sesudah konfigurasi database dan migration selesai:</p>
                    <pre class="bg-light p-3 rounded mb-3"><code>php artisan skema:verifikasi --rinci</code></pre>
                    <div class="alert alert-info mb-0">
                        Perintah memeriksa tabel, view, kolom, tipe data, nullable, default, index, dan foreign key terhadap SQL final.
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
