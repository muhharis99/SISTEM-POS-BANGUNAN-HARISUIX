@extends('layouts.admin')

@section('judul', 'Manajemen Pengguna')

@section('breadcrumb')
    <div class="d-flex align-items-center justify-content-between py-3">
        <div><h4 class="fs-18 fw-semibold mb-1">Manajemen Pengguna</h4><p class="text-muted mb-0">Kelola akun, role, cabang, status, dan kata sandi.</p></div>
        @if (auth()->user()->memilikiHakAkses('PENGGUNA_BUAT', session('id_cabang_aktif')))
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambahPengguna"><i data-lucide="user-plus" class="me-1"></i>Tambah Pengguna</button>
        @endif
    </div>
@endsection

@section('content')
    @if ($errors->any())
        <div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif

    <div class="card">
        <div class="card-header">
            <form method="GET" class="row g-2">
                <div class="col-md-5"><input class="form-control" name="pencarian" value="{{ $pencarian }}" placeholder="Cari nama pengguna, nama tampilan, atau surel"></div>
                <div class="col-auto"><button class="btn btn-primary" type="submit">Cari</button></div>
                @if ($pencarian !== '')<div class="col-auto"><a class="btn btn-light" href="{{ route('pengguna.index') }}">Reset</a></div>@endif
            </form>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead><tr><th>Pengguna</th><th>Kontak</th><th>Role & Cabang</th><th>Status</th><th>Terakhir Masuk</th><th class="text-end">Aksi</th></tr></thead>
                <tbody>
                    @forelse ($pengguna as $item)
                        <tr>
                            <td><strong>{{ $item->nama_tampilan }}</strong><br><small class="text-muted">{{ $item->nama_pengguna }}</small></td>
                            <td>{{ $item->surel ?: '-' }}<br><small class="text-muted">{{ $item->telepon ?: '-' }}</small></td>
                            <td>
                                @forelse ($item->penugasanPeran as $penugasan)
                                    <span class="badge badge-soft-primary mb-1">{{ $penugasan->peran?->nama_peran }} · {{ $penugasan->cabang?->nama_cabang ?? 'Semua Cabang' }}</span>
                                @empty
                                    <span class="text-muted">Belum ditetapkan</span>
                                @endforelse
                            </td>
                            <td><span class="badge badge-soft-{{ $item->status_aktif ? 'success' : 'danger' }}">{{ $item->status_aktif ? 'Aktif' : 'Nonaktif' }}</span></td>
                            <td>{{ optional($item->terakhir_masuk)->format('d-m-Y H:i') ?: '-' }}</td>
                            <td class="text-end text-nowrap">
                                @if (auth()->user()->memilikiHakAkses('PENGGUNA_UBAH', session('id_cabang_aktif')))
                                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalEditPengguna{{ $item->id_pengguna }}">Edit</button>
                                @endif
                                @if (auth()->user()->memilikiHakAkses('PENGGUNA_RESET_KATA_SANDI', session('id_cabang_aktif')))
                                    <button class="btn btn-sm btn-outline-warning" data-bs-toggle="modal" data-bs-target="#modalReset{{ $item->id_pengguna }}">Reset Sandi</button>
                                @endif
                                @if (auth()->user()->memilikiHakAkses('PENGGUNA_UBAH_STATUS', session('id_cabang_aktif')) && $item->id_pengguna !== auth()->id())
                                    <form method="POST" action="{{ route('pengguna.status', $item) }}" class="d-inline">@csrf @method('PATCH')<button class="btn btn-sm btn-outline-{{ $item->status_aktif ? 'danger' : 'success' }}" onclick="return confirm('Ubah status pengguna ini?')">{{ $item->status_aktif ? 'Nonaktifkan' : 'Aktifkan' }}</button></form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-4">Data pengguna tidak ditemukan.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $pengguna->links() }}</div>
    </div>

    <div class="modal fade" id="modalTambahPengguna" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg"><div class="modal-content">
            <form method="POST" action="{{ route('pengguna.simpan') }}">@csrf
                <div class="modal-header"><h5 class="modal-title">Tambah Pengguna</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">@include('pengguna.partials.form', ['item' => null, 'prefix' => 'tambah'])</div>
                <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button><button class="btn btn-primary">Simpan</button></div>
            </form>
        </div></div>
    </div>

    @foreach ($pengguna as $item)
        <div class="modal fade" id="modalEditPengguna{{ $item->id_pengguna }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg"><div class="modal-content">
                <form method="POST" action="{{ route('pengguna.ubah', $item) }}">@csrf @method('PUT')
                    <div class="modal-header"><h5 class="modal-title">Edit {{ $item->nama_tampilan }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                    <div class="modal-body">@include('pengguna.partials.form', ['item' => $item, 'prefix' => 'edit'.$item->id_pengguna])</div>
                    <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button><button class="btn btn-primary">Simpan Perubahan</button></div>
                </form>
            </div></div>
        </div>

        <div class="modal fade" id="modalReset{{ $item->id_pengguna }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog"><div class="modal-content"><form method="POST" action="{{ route('pengguna.kata-sandi', $item) }}">@csrf @method('PUT')
                <div class="modal-header"><h5 class="modal-title">Reset Kata Sandi</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body"><p>Reset kata sandi untuk <strong>{{ $item->nama_tampilan }}</strong>.</p><div class="mb-3"><label class="form-label">Kata Sandi Baru</label><input type="password" class="form-control" name="kata_sandi_baru" required></div><div><label class="form-label">Ulangi Kata Sandi</label><input type="password" class="form-control" name="kata_sandi_baru_confirmation" required></div></div>
                <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button><button class="btn btn-warning">Reset Kata Sandi</button></div>
            </form></div></div>
        </div>
    @endforeach
@endsection

@push('scripts')
<script>
document.addEventListener('click', function (event) {
    const tambah = event.target.closest('[data-tambah-penugasan]');
    const hapus = event.target.closest('[data-hapus-penugasan]');
    if (tambah) {
        const wadah = document.getElementById(tambah.dataset.target);
        const baris = wadah.querySelector('[data-baris-penugasan]').cloneNode(true);
        baris.querySelectorAll('select').forEach((select) => select.value = '');
        wadah.appendChild(baris);
        aturNamaPenugasan(wadah);
    }
    if (hapus) {
        const wadah = hapus.closest('[data-wadah-penugasan]');
        if (wadah.querySelectorAll('[data-baris-penugasan]').length > 1) hapus.closest('[data-baris-penugasan]').remove();
        aturNamaPenugasan(wadah);
    }
});
function aturNamaPenugasan(wadah) {
    wadah.querySelectorAll('[data-baris-penugasan]').forEach((baris, index) => {
        baris.querySelector('[data-field-peran]').name = `penugasan[${index}][id_peran]`;
        baris.querySelector('[data-field-cabang]').name = `penugasan[${index}][id_cabang]`;
    });
}
document.querySelectorAll('[data-wadah-penugasan]').forEach(aturNamaPenugasan);
</script>
@endpush
