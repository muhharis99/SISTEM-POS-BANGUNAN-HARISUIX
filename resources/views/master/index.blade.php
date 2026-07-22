@extends('layouts.admin')

@section('judul', $konfigurasi['judul'])

@section('breadcrumb')
    <div class="d-flex align-items-center justify-content-between py-3">
        <div><h4 class="fs-18 fw-semibold mb-1">{{ $konfigurasi['judul'] }}</h4><p class="text-muted mb-0">{{ $konfigurasi['deskripsi'] }}</p></div>
        @if (auth()->user()->memilikiHakAkses($konfigurasi['izin_kelola'], session('id_cabang_aktif')))
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambah"><i data-lucide="plus" class="me-1"></i>Tambah</button>
        @endif
    </div>
@endsection

@section('content')
    @if (session('berhasil'))<div class="alert alert-success">{{ session('berhasil') }}</div>@endif
    @if ($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

    @php $kolomTabel = array_slice($konfigurasi['kolom'], 0, 5, true); @endphp
    <div class="card">
        <div class="card-header">
            <form method="GET" class="row g-2">
                <div class="col-md-5"><input class="form-control" name="pencarian" value="{{ $pencarian }}" placeholder="Cari {{ strtolower($konfigurasi['judul']) }}"></div>
                <div class="col-auto"><button class="btn btn-primary">Cari</button></div>
                @if ($pencarian !== '')<div class="col-auto"><a href="{{ route('master.index', ['slug' => $slug]) }}" class="btn btn-light">Reset</a></div>@endif
            </form>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead><tr>@foreach ($kolomTabel as $kolom)<th>{{ $kolom['label'] }}</th>@endforeach<th>Status</th><th class="text-end">Aksi</th></tr></thead>
                <tbody>
                    @forelse ($data as $item)
                        <tr>
                            @foreach ($kolomTabel as $nama => $kolom)
                                <td>
                                    @if ($kolom['tipe'] === 'relation')
                                        @php $opsi = collect($opsiRelasi[$nama] ?? [])->firstWhere($kolom['relasi']['kunci'], $item->{$nama}); @endphp
                                        {{ $opsi->{$kolom['relasi']['label']} ?? '-' }}
                                    @elseif ($kolom['tipe'] === 'select')
                                        {{ $kolom['opsi'][$item->{$nama}] ?? $item->{$nama} }}
                                    @elseif (in_array($kolom['tipe'], ['decimal', 'number'], true) && is_numeric($item->{$nama}))
                                        {{ number_format((float) $item->{$nama}, $kolom['tipe'] === 'decimal' ? 2 : 0, ',', '.') }}
                                    @else
                                        {{ \Illuminate\Support\Str::limit((string) ($item->{$nama} ?? '-'), 60) }}
                                    @endif
                                </td>
                            @endforeach
                            <td><span class="badge badge-soft-{{ $item->status_aktif ? 'success' : 'danger' }}">{{ $item->status_aktif ? 'Aktif' : 'Nonaktif' }}</span></td>
                            <td class="text-end text-nowrap">
                                @if (auth()->user()->memilikiHakAkses($konfigurasi['izin_kelola'], session('id_cabang_aktif')))
                                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalEdit{{ $item->{$konfigurasi['kunci']} }}">Edit</button>
                                    <form class="d-inline" method="POST" action="{{ route('master.status', ['slug' => $slug, 'id' => $item->{$konfigurasi['kunci']}]) }}">@csrf @method('PATCH')<button class="btn btn-sm btn-outline-{{ $item->status_aktif ? 'danger' : 'success' }}" onclick="return confirm('Ubah status data ini?')">{{ $item->status_aktif ? 'Nonaktifkan' : 'Aktifkan' }}</button></form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="{{ count($kolomTabel) + 2 }}" class="text-center text-muted py-4">Data belum tersedia.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $data->links() }}</div>
    </div>

    @if (auth()->user()->memilikiHakAkses($konfigurasi['izin_kelola'], session('id_cabang_aktif')))
        <div class="modal fade" id="modalTambah" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content">
            <form method="POST" action="{{ route('master.simpan', ['slug' => $slug]) }}">@csrf
                <div class="modal-header"><h5 class="modal-title">Tambah {{ $konfigurasi['judul'] }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body"><div class="row g-3">
                    @foreach ($konfigurasi['kolom'] as $nama => $kolom)
                        <div class="{{ $kolom['tipe'] === 'textarea' ? 'col-12' : 'col-md-6' }}">
                            <label class="form-label">{{ $kolom['label'] }}</label>
                            @if ($kolom['tipe'] === 'textarea')
                                <textarea class="form-control" name="{{ $nama }}" rows="3">{{ old($nama, $kolom['default'] ?? '') }}</textarea>
                            @elseif ($kolom['tipe'] === 'select')
                                <select class="form-select" name="{{ $nama }}" required><option value="">Pilih</option>@foreach ($kolom['opsi'] as $nilai => $label)<option value="{{ $nilai }}" @selected(old($nama, $kolom['default'] ?? '') == $nilai)>{{ $label }}</option>@endforeach</select>
                            @elseif ($kolom['tipe'] === 'relation')
                                <select class="form-select" name="{{ $nama }}"><option value="">Tanpa induk</option>@foreach ($opsiRelasi[$nama] ?? [] as $opsi)<option value="{{ $opsi->{$kolom['relasi']['kunci']} }}" @selected(old($nama) == $opsi->{$kolom['relasi']['kunci']})>{{ $opsi->{$kolom['relasi']['label']} }}</option>@endforeach</select>
                            @else
                                <input class="form-control" type="{{ in_array($kolom['tipe'], ['number', 'decimal'], true) ? 'number' : $kolom['tipe'] }}" @if ($kolom['tipe'] === 'decimal') step="any" @endif name="{{ $nama }}" value="{{ old($nama, $kolom['default'] ?? '') }}">
                            @endif
                        </div>
                    @endforeach
                </div></div>
                <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button><button class="btn btn-primary">Simpan</button></div>
            </form>
        </div></div></div>

        @foreach ($data as $item)
            <div class="modal fade" id="modalEdit{{ $item->{$konfigurasi['kunci']} }}" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content">
                <form method="POST" action="{{ route('master.ubah', ['slug' => $slug, 'id' => $item->{$konfigurasi['kunci']}]) }}">@csrf @method('PUT')
                    <div class="modal-header"><h5 class="modal-title">Edit {{ $konfigurasi['judul'] }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                    <div class="modal-body"><div class="row g-3">
                        @foreach ($konfigurasi['kolom'] as $nama => $kolom)
                            <div class="{{ $kolom['tipe'] === 'textarea' ? 'col-12' : 'col-md-6' }}">
                                <label class="form-label">{{ $kolom['label'] }}</label>
                                @if ($kolom['tipe'] === 'textarea')
                                    <textarea class="form-control" name="{{ $nama }}" rows="3">{{ $item->{$nama} }}</textarea>
                                @elseif ($kolom['tipe'] === 'select')
                                    <select class="form-select" name="{{ $nama }}" required>@foreach ($kolom['opsi'] as $nilai => $label)<option value="{{ $nilai }}" @selected($item->{$nama} == $nilai)>{{ $label }}</option>@endforeach</select>
                                @elseif ($kolom['tipe'] === 'relation')
                                    <select class="form-select" name="{{ $nama }}"><option value="">Tanpa induk</option>@foreach ($opsiRelasi[$nama] ?? [] as $opsi)@continue($opsi->{$kolom['relasi']['kunci']} == $item->{$konfigurasi['kunci']})<option value="{{ $opsi->{$kolom['relasi']['kunci']} }}" @selected($item->{$nama} == $opsi->{$kolom['relasi']['kunci']})>{{ $opsi->{$kolom['relasi']['label']} }}</option>@endforeach</select>
                                @else
                                    <input class="form-control" type="{{ in_array($kolom['tipe'], ['number', 'decimal'], true) ? 'number' : $kolom['tipe'] }}" @if ($kolom['tipe'] === 'decimal') step="any" @endif name="{{ $nama }}" value="{{ $item->{$nama} }}">
                                @endif
                            </div>
                        @endforeach
                    </div></div>
                    <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button><button class="btn btn-primary">Simpan Perubahan</button></div>
                </form>
            </div></div></div>
        @endforeach
    @endif
@endsection
