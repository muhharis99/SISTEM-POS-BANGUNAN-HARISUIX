@extends('layouts.admin')

@section('judul', 'Stok Awal')

@section('breadcrumb')
    <div class="d-flex align-items-center justify-content-between py-3">
        <div><h4 class="fs-18 fw-semibold mb-1">Stok Awal</h4><p class="text-muted mb-0">Saldo pembukaan persediaan. Saldo baru terbentuk setelah dokumen disetujui.</p></div>
        @if (auth()->user()->memilikiHakAkses('STOK_AWAL_KELOLA', session('id_cabang_aktif')))
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambahStokAwal">Tambah Stok Awal</button>
        @endif
    </div>
@endsection

@section('content')
    @if (session('berhasil'))<div class="alert alert-success">{{ session('berhasil') }}</div>@endif
    @if ($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

    <div class="alert alert-info">Stok awal yang sudah disetujui tidak dapat diedit atau dibatalkan. Setiap detail akan menghasilkan mutasi <strong>STOK_AWAL</strong>.</div>

    <div class="card">
        <div class="card-header">
            <form class="row g-2"><div class="col-md-6"><input class="form-control" name="pencarian" value="{{ $pencarian }}" placeholder="Nomor dokumen, gudang, atau keterangan"></div><div class="col-auto"><button class="btn btn-primary">Cari</button></div><div class="col-auto"><a class="btn btn-light" href="{{ route('stok-awal.index') }}">Reset</a></div></form>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead><tr><th>Dokumen</th><th>Gudang</th><th>Tanggal</th><th>Detail</th><th>Status</th><th class="text-end">Aksi</th></tr></thead>
                <tbody>
                    @forelse ($dokumen as $item)
                        <tr>
                            <td><strong>{{ $item->nomor_stok_awal }}</strong><br><small class="text-muted">{{ $item->keterangan ?: '-' }}</small></td>
                            <td>{{ $item->nama_gudang }}</td>
                            <td>{{ \Carbon\Carbon::parse($item->tanggal_stok_awal)->format('d-m-Y') }}</td>
                            <td>{{ ($detail[$item->id_stok_awal] ?? collect())->count() }} baris<br>@foreach (($detail[$item->id_stok_awal] ?? collect())->take(2) as $baris)<small class="text-muted d-block">{{ $baris->kode_barang }} / {{ $baris->kode_satuan }} · {{ number_format($baris->jumlah, 3, ',', '.') }}</small>@endforeach</td>
                            <td><span class="badge badge-soft-{{ $item->status_stok_awal === 'DISETUJUI' ? 'success' : ($item->status_stok_awal === 'DIBATALKAN' ? 'danger' : 'warning') }}">{{ $item->status_stok_awal }}</span></td>
                            <td class="text-end text-nowrap">
                                @if ($item->status_stok_awal === 'DRAF' && auth()->user()->memilikiHakAkses('STOK_AWAL_KELOLA', session('id_cabang_aktif')))
                                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalEditStokAwal{{ $item->id_stok_awal }}">Edit</button>
                                    <form class="d-inline" method="POST" action="{{ route('stok-awal.batalkan', $item->id_stok_awal) }}">@csrf @method('PATCH')<button class="btn btn-sm btn-outline-danger" onclick="return confirm('Batalkan dokumen stok awal ini?')">Batalkan</button></form>
                                @endif
                                @if ($item->status_stok_awal === 'DRAF' && auth()->user()->memilikiHakAkses('STOK_AWAL_SETUJUI', session('id_cabang_aktif')))
                                    <form class="d-inline" method="POST" action="{{ route('stok-awal.setujui', $item->id_stok_awal) }}">@csrf @method('PATCH')<button class="btn btn-sm btn-success" onclick="return confirm('Setujui stok awal dan bentuk saldo stok?')">Setujui</button></form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-4">Belum ada dokumen stok awal.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $dokumen->links() }}</div>
    </div>

    <div class="modal fade" id="modalTambahStokAwal" tabindex="-1"><div class="modal-dialog modal-xl"><div class="modal-content"><form method="POST" action="{{ route('stok-awal.simpan') }}">@csrf<div class="modal-header"><h5 class="modal-title">Tambah Stok Awal</h5><button class="btn-close" type="button" data-bs-dismiss="modal"></button></div><div class="modal-body">@include('stok_awal.partials.form', ['item' => null, 'prefix' => 'tambah'])</div><div class="modal-footer"><button class="btn btn-light" type="button" data-bs-dismiss="modal">Batal</button><button class="btn btn-primary">Simpan Draf</button></div></form></div></div></div>

    @foreach ($dokumen as $item)
        @if ($item->status_stok_awal === 'DRAF')
            <div class="modal fade" id="modalEditStokAwal{{ $item->id_stok_awal }}" tabindex="-1"><div class="modal-dialog modal-xl"><div class="modal-content"><form method="POST" action="{{ route('stok-awal.ubah', $item->id_stok_awal) }}">@csrf @method('PUT')<div class="modal-header"><h5 class="modal-title">Edit {{ $item->nomor_stok_awal }}</h5><button class="btn-close" type="button" data-bs-dismiss="modal"></button></div><div class="modal-body">@include('stok_awal.partials.form', ['item' => $item, 'prefix' => 'edit'.$item->id_stok_awal])</div><div class="modal-footer"><button class="btn btn-light" type="button" data-bs-dismiss="modal">Batal</button><button class="btn btn-primary">Simpan Perubahan</button></div></form></div></div></div>
        @endif
    @endforeach
@endsection

@push('scripts')
<script>
function susunNamaDetail(wadah) {
    wadah.querySelectorAll('[data-baris-detail]').forEach((baris, index) => {
        baris.querySelectorAll('[data-field]').forEach((field) => field.name = `detail_stok[${index}][${field.dataset.field}]`);
    });
}
function saringLokasi(form) {
    const gudang = form.querySelector('[data-pilih-gudang]')?.value || '';
    form.querySelectorAll('[data-pilih-lokasi]').forEach((select) => {
        Array.from(select.options).forEach((option) => {
            if (!option.value) return;
            option.hidden = option.dataset.gudang !== gudang;
            option.disabled = option.dataset.gudang !== gudang;
        });
        if (select.selectedOptions[0]?.disabled) select.value = '';
    });
}
document.querySelectorAll('[data-wadah-detail]').forEach(susunNamaDetail);
document.querySelectorAll('form').forEach(saringLokasi);
document.addEventListener('change', (event) => { if (event.target.matches('[data-pilih-gudang]')) saringLokasi(event.target.closest('form')); });
document.addEventListener('click', (event) => {
    const tambah = event.target.closest('[data-tambah-baris]');
    if (tambah) {
        const wadah = document.getElementById(tambah.dataset.target);
        const baris = wadah.querySelector('[data-baris-detail]').cloneNode(true);
        baris.querySelectorAll('input').forEach((input) => input.value = input.type === 'number' ? (input.dataset.field === 'jumlah' ? 1 : 0) : '');
        baris.querySelectorAll('select').forEach((select) => select.value = '');
        wadah.appendChild(baris);
        susunNamaDetail(wadah);
        saringLokasi(wadah.closest('form'));
    }
    const hapus = event.target.closest('[data-hapus-baris]');
    if (hapus) {
        const wadah = hapus.closest('[data-wadah-detail]');
        if (wadah.querySelectorAll('[data-baris-detail]').length > 1) hapus.closest('[data-baris-detail]').remove();
        susunNamaDetail(wadah);
    }
});
</script>
@endpush
