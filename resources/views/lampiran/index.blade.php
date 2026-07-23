@extends('layouts.admin')

@section('judul', 'Lampiran Dokumen')

@section('breadcrumb')
    <div class="d-flex align-items-center justify-content-between py-3">
        <div>
            <h4 class="fs-18 fw-semibold mb-1">Lampiran Dokumen</h4>
            <p class="text-muted mb-0">Berkas transaksi tersimpan privat dan hanya dapat diakses dari cabang aktif.</p>
        </div>
        @if (auth()->user()->memilikiHakAkses('LAMPIRAN_UNGGAH', session('id_cabang_aktif')))
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalUnggahLampiran">
                <i data-lucide="upload" class="me-1"></i> Unggah Lampiran
            </button>
        @endif
    </div>
@endsection

@section('content')
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    <div class="card">
        <div class="card-header">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Jenis Dokumen</label>
                    <select class="form-select" name="jenis_dokumen">
                        <option value="">Semua jenis</option>
                        @foreach ($jenisTersedia as $kode => $konfigurasi)
                            <option value="{{ $kode }}" @selected($jenisDokumen === $kode)>{{ $konfigurasi['label'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">ID Dokumen</label>
                    <input class="form-control" type="number" min="1" name="id_dokumen" value="{{ $idDokumen }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Pencarian</label>
                    <input class="form-control" name="pencarian" value="{{ $pencarian }}" placeholder="Nama berkas, tipe, atau keterangan">
                </div>
                <div class="col-auto"><button class="btn btn-primary">Terapkan</button></div>
                <div class="col-auto"><a class="btn btn-light" href="{{ route('lampiran.index') }}">Reset</a></div>
            </form>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Waktu</th>
                        <th>Dokumen</th>
                        <th>Nama Berkas</th>
                        <th>Jenis/Ukuran</th>
                        <th>Pengunggah</th>
                        <th>Keterangan</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($lampiran as $item)
                        @php($label = $jenisTersedia[$item->jenis_dokumen]['label'] ?? $item->jenis_dokumen)
                        <tr>
                            <td class="text-nowrap">{{ optional($item->created_at)->format('d-m-Y H:i') }}</td>
                            <td><strong>{{ $label }}</strong><br><small class="text-muted">ID #{{ $item->id_dokumen }}</small></td>
                            <td>{{ $item->nama_berkas_asli }}</td>
                            <td>{{ $item->jenis_berkas ?: '-' }}<br><small class="text-muted">{{ number_format($item->ukuran_berkas / 1024, 1, ',', '.') }} KB</small></td>
                            <td>{{ $item->nama_pengunggah ?: 'Pengguna #'.$item->created_by }}</td>
                            <td>{{ $item->keterangan ?: '-' }}</td>
                            <td class="text-end text-nowrap">
                                @if (auth()->user()->memilikiHakAkses('LAMPIRAN_UNDUH', session('id_cabang_aktif')))
                                    <a class="btn btn-sm btn-outline-primary" href="{{ route('lampiran.unduh', $item->id_lampiran_dokumen) }}">
                                        <i data-lucide="download"></i>
                                    </a>
                                @endif
                                @if (auth()->user()->memilikiHakAkses('LAMPIRAN_HAPUS', session('id_cabang_aktif')))
                                    <form class="d-inline" method="POST" action="{{ route('lampiran.hapus', $item->id_lampiran_dokumen) }}" onsubmit="return confirm('Hapus lampiran ini secara logis?')">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger"><i data-lucide="trash-2"></i></button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-4">Belum ada lampiran pada filter dan cabang aktif.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $lampiran->links() }}</div>
    </div>

    <div class="modal fade" id="modalUnggahLampiran" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <form class="modal-content" method="POST" action="{{ route('lampiran.simpan') }}" enctype="multipart/form-data">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Unggah Lampiran Dokumen</h5>
                    <button class="btn-close" type="button" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">Berkas disimpan pada storage privat. Maksimum 10 MB dengan format PDF, gambar, dokumen kantor, atau CSV.</div>
                    <div class="row g-3">
                        <div class="col-md-7">
                            <label class="form-label">Jenis Dokumen</label>
                            <select class="form-select" name="jenis_dokumen" required>
                                <option value="">Pilih jenis dokumen</option>
                                @foreach ($jenisTersedia as $kode => $konfigurasi)
                                    <option value="{{ $kode }}" @selected(old('jenis_dokumen', $jenisDokumen) === $kode)>{{ $konfigurasi['label'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label">ID Dokumen</label>
                            <input class="form-control" type="number" min="1" name="id_dokumen" value="{{ old('id_dokumen', $idDokumen) }}" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Berkas</label>
                            <input class="form-control" type="file" name="berkas" accept=".pdf,.jpg,.jpeg,.png,.webp,.csv,.xls,.xlsx,.doc,.docx" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Keterangan</label>
                            <textarea class="form-control" name="keterangan" rows="3">{{ old('keterangan') }}</textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-light" type="button" data-bs-dismiss="modal">Tutup</button>
                    <button class="btn btn-primary">Unggah</button>
                </div>
            </form>
        </div>
    </div>
@endsection
