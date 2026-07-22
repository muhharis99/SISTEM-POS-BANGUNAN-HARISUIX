@extends('layouts.admin')

@section('judul', 'Peran dan Hak Akses')

@section('breadcrumb')
    <div class="d-flex align-items-center justify-content-between py-3">
        <div><h4 class="fs-18 fw-semibold mb-1">Peran & Hak Akses</h4><p class="text-muted mb-0">Atur matriks akses berdasarkan modul dan tindakan.</p></div>
        @if (auth()->user()->memilikiHakAkses('PERAN_BUAT', session('id_cabang_aktif')))
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambahPeran">Tambah Peran</button>
        @endif
    </div>
@endsection

@section('content')
    @if ($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <div class="row g-3">
        @foreach ($peran as $item)
            <div class="col-xl-4 col-md-6">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div><h5 class="mb-1">{{ $item->nama_peran }}</h5><code>{{ $item->kode_peran }}</code></div>
                            <span class="badge badge-soft-{{ $item->status_aktif ? 'success' : 'danger' }}">{{ $item->status_aktif ? 'Aktif' : 'Nonaktif' }}</span>
                        </div>
                        <p class="text-muted">{{ $item->keterangan ?: 'Tidak ada keterangan.' }}</p>
                        <div class="d-flex gap-3 text-muted mb-3"><span>{{ $item->hak_akses_count }} hak akses</span><span>{{ $item->penugasan_pengguna_count }} penugasan</span></div>
                        <div class="d-flex gap-2">
                            @if (auth()->user()->memilikiHakAkses('PERAN_UBAH', session('id_cabang_aktif')))
                                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalEditPeran{{ $item->id_peran }}">Edit</button>
                            @endif
                            @if (auth()->user()->memilikiHakAkses('PERAN_UBAH_STATUS', session('id_cabang_aktif')) && $item->kode_peran !== 'ADMINISTRATOR')
                                <form method="POST" action="{{ route('peran.status', $item) }}">@csrf @method('PATCH')<button class="btn btn-sm btn-outline-{{ $item->status_aktif ? 'danger' : 'success' }}" onclick="return confirm('Ubah status peran ini?')">{{ $item->status_aktif ? 'Nonaktifkan' : 'Aktifkan' }}</button></form>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="modal fade" id="modalTambahPeran" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-xl"><div class="modal-content"><form method="POST" action="{{ route('peran.simpan') }}">@csrf
        <div class="modal-header"><h5 class="modal-title">Tambah Peran</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">@include('peran.partials.form', ['item' => null])</div>
        <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button><button class="btn btn-primary">Simpan</button></div>
    </form></div></div></div>

    @foreach ($peran as $item)
        <div class="modal fade" id="modalEditPeran{{ $item->id_peran }}" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-xl"><div class="modal-content"><form method="POST" action="{{ route('peran.ubah', $item) }}">@csrf @method('PUT')
            <div class="modal-header"><h5 class="modal-title">Edit {{ $item->nama_peran }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">@include('peran.partials.form', ['item' => $item])</div>
            <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button><button class="btn btn-primary">Simpan Perubahan</button></div>
        </form></div></div></div>
    @endforeach
@endsection
