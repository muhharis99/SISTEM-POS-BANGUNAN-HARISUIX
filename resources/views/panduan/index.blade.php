@extends('layouts.admin')

@section('judul', 'Pusat Bantuan')

@section('breadcrumb')
    <div class="d-flex align-items-center justify-content-between py-3">
        <div>
            <h4 class="fs-18 fw-semibold mb-1">Pusat Bantuan</h4>
            <p class="text-muted mb-0">Panduan yang ditampilkan telah disesuaikan dengan hak akses Anda pada cabang aktif.</p>
        </div>
        <span class="badge badge-soft-primary fs-13">{{ $jumlahPanduan }} panduan tersedia</span>
    </div>
@endsection

@section('content')
    <div class="row g-3 mb-3">
        <div class="col-lg-8">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-start gap-3">
                        <div class="avatar-lg bg-primary-subtle rounded d-flex align-items-center justify-content-center flex-shrink-0">
                            <i data-lucide="book-open-check" class="text-primary"></i>
                        </div>
                        <div>
                            <h5 class="mb-2">Gunakan panduan sesuai alur kerja</h5>
                            <p class="text-muted mb-2">Mulai dari pemeriksaan cabang aktif, isi dokumen secara lengkap, lalu lakukan persetujuan hanya setelah nilai dan pihak terkait sudah benar.</p>
                            <p class="mb-0"><strong>Penting:</strong> jangan membagikan kata sandi dan jangan mengubah database secara manual tanpa prosedur serta backup.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-body">
                    <label class="form-label" for="pencarian-panduan">Cari panduan</label>
                    <div class="input-group">
                        <span class="input-group-text"><i data-lucide="search" class="icon-sm"></i></span>
                        <input class="form-control" id="pencarian-panduan" type="search" placeholder="Contoh: penjualan atau stok" autocomplete="off">
                    </div>
                    <small class="text-muted">Pencarian dilakukan pada judul, ringkasan, dan langkah panduan.</small>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4" id="daftar-panduan">
        @foreach ($panduan as $item)
            <div class="col-xl-4 col-md-6 panduan-item" data-panduan-teks="{{ Str::lower($item['judul'].' '.$item['ringkasan'].' '.implode(' ', $item['langkah'])) }}">
                <div class="card h-100">
                    <div class="card-body d-flex flex-column">
                        <div class="d-flex align-items-start justify-content-between gap-3 mb-3">
                            <div class="avatar-md bg-primary-subtle rounded d-flex align-items-center justify-content-center">
                                <i data-lucide="{{ $item['ikon'] }}" class="text-primary"></i>
                            </div>
                            <span class="badge badge-soft-secondary">{{ count($item['langkah']) }} langkah</span>
                        </div>
                        <h5>{{ $item['judul'] }}</h5>
                        <p class="text-muted">{{ $item['ringkasan'] }}</p>
                        <ol class="ps-3 mb-3">
                            @foreach ($item['langkah'] as $langkah)
                                <li class="mb-2">{{ $langkah }}</li>
                            @endforeach
                        </ol>
                        @if ($item['route'] !== null && Route::has($item['route']))
                            <div class="mt-auto">
                                <a class="btn btn-sm btn-outline-primary" href="{{ route($item['route']) }}">
                                    Buka modul <i data-lucide="arrow-right" class="ms-1 icon-sm"></i>
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="alert alert-info d-none" id="panduan-kosong" role="alert">
        Tidak ada panduan yang cocok dengan pencarian Anda.
    </div>

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Prosedur umum yang wajib diingat</h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                @foreach ($prosedurUmum as $prosedur)
                    <div class="col-lg-4">
                        <div class="border rounded p-3 h-100">
                            <div class="d-flex align-items-center gap-2 mb-3">
                                <i data-lucide="{{ $prosedur['ikon'] }}" class="text-primary"></i>
                                <strong>{{ $prosedur['judul'] }}</strong>
                            </div>
                            <ul class="ps-3 mb-0">
                                @foreach ($prosedur['langkah'] as $langkah)
                                    <li class="mb-2">{{ $langkah }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const input = document.getElementById('pencarian-panduan');
            const itemPanduan = Array.from(document.querySelectorAll('.panduan-item'));
            const informasiKosong = document.getElementById('panduan-kosong');

            input?.addEventListener('input', function () {
                const kataKunci = input.value.trim().toLowerCase();
                let jumlahTampil = 0;

                itemPanduan.forEach(function (item) {
                    const cocok = kataKunci === '' || item.dataset.panduanTeks.includes(kataKunci);
                    item.classList.toggle('d-none', !cocok);
                    jumlahTampil += cocok ? 1 : 0;
                });

                informasiKosong.classList.toggle('d-none', jumlahTampil !== 0);
            });
        });
    </script>
@endpush
