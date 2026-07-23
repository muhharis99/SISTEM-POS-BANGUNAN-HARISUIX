@extends('layouts.admin')

@section('judul', 'Hutang Pemasok')

@section('breadcrumb')
    <div class="d-flex align-items-center justify-content-between py-3">
        <div><h4 class="fs-18 fw-semibold mb-1">Hutang Pemasok</h4><p class="text-muted mb-0">Pantau jatuh tempo dan alokasikan satu pembayaran ke beberapa faktur.</p></div>
        <div class="d-flex gap-2"><a class="btn btn-light" href="{{ route('pembelian.index') }}">Kembali ke Pembelian</a>@if(auth()->user()->memilikiHakAkses('PEMBAYARAN_HUTANG_KELOLA', session('id_cabang_aktif')))<button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalPembayaran">Tambah Pembayaran</button>@endif</div>
    </div>
@endsection

@section('content')
    @if (session('berhasil'))<div class="alert alert-success">{{ session('berhasil') }}</div>@endif
    @if ($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

    <div class="row g-3 mb-3">
        <div class="col-md-4"><div class="card"><div class="card-body"><div class="text-muted">Total Hutang Berjalan</div><div class="fs-22 fw-semibold">Rp {{ number_format($ringkasan['total_hutang'], 0, ',', '.') }}</div></div></div></div>
        <div class="col-md-4"><div class="card"><div class="card-body"><div class="text-muted">Sudah Jatuh Tempo</div><div class="fs-22 fw-semibold text-danger">Rp {{ number_format($ringkasan['jatuh_tempo'], 0, ',', '.') }}</div></div></div></div>
        <div class="col-md-4"><div class="card"><div class="card-body"><div class="text-muted">Pembayaran Bulan Ini</div><div class="fs-22 fw-semibold text-success">Rp {{ number_format($ringkasan['pembayaran_bulan_ini'], 0, ',', '.') }}</div></div></div></div>
    </div>

    <div class="card mb-3"><div class="card-header"><form class="d-flex gap-2"><input class="form-control" name="pencarian" value="{{ $pencarian }}" placeholder="Pemasok atau nomor faktur"><button class="btn btn-primary">Cari</button><a class="btn btn-light" href="{{ route('hutang-pemasok.index') }}">Reset</a></form></div><div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead><tr><th>Pemasok</th><th>Faktur</th><th>Jatuh Tempo</th><th>Nilai Awal</th><th>Dibayar/Retur</th><th>Sisa</th><th>Status</th></tr></thead><tbody>
        @forelse($hutang as $item)
            <tr><td>{{ $item->nama_pemasok }}</td><td><strong>{{ $item->nomor_faktur_internal }}</strong><br><small class="text-muted">{{ $item->nomor_faktur_pemasok }}</small></td><td class="{{ $item->tanggal_jatuh_tempo && $item->tanggal_jatuh_tempo <= date('Y-m-d') && $item->sisa_hutang > 0 ? 'text-danger fw-semibold' : '' }}">{{ $item->tanggal_jatuh_tempo ? \Carbon\Carbon::parse($item->tanggal_jatuh_tempo)->format('d-m-Y') : '-' }}</td><td>Rp {{ number_format($item->nilai_awal, 0, ',', '.') }}</td><td>Rp {{ number_format($item->nilai_pembayaran + $item->nilai_retur + $item->nilai_penyesuaian, 0, ',', '.') }}</td><td class="fw-semibold">Rp {{ number_format($item->sisa_hutang, 0, ',', '.') }}</td><td><span class="badge badge-soft-info">{{ $item->status_hutang }}</span></td></tr>
        @empty<tr><td colspan="7" class="text-center text-muted py-4">Belum ada hutang pemasok.</td></tr>@endforelse
    </tbody></table></div><div class="card-footer">{{ $hutang->links() }}</div></div>

    <div class="card"><div class="card-header"><h5 class="mb-0">Pembayaran Terakhir</h5></div><div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead><tr><th>Nomor</th><th>Pemasok</th><th>Tanggal</th><th>Kas/Bank</th><th>Metode</th><th>Total</th><th>Status</th><th class="text-end">Aksi</th></tr></thead><tbody>
        @forelse($pembayaran as $item)
            <tr><td><strong>{{ $item->nomor_pembayaran }}</strong></td><td>{{ $item->nama_pemasok }}</td><td>{{ \Carbon\Carbon::parse($item->tanggal_pembayaran)->format('d-m-Y') }}</td><td>{{ $item->nama_kas_bank }}</td><td>{{ $item->nama_metode_pembayaran }}</td><td>Rp {{ number_format($item->total_pembayaran, 0, ',', '.') }}</td><td><span class="badge badge-soft-info">{{ $item->status_pembayaran }}</span></td><td class="text-end text-nowrap">@if($item->status_pembayaran === 'DRAF' && auth()->user()->memilikiHakAkses('PEMBAYARAN_HUTANG_SETUJUI', session('id_cabang_aktif')))<form class="d-inline" method="POST" action="{{ route('hutang-pemasok.setujui', $item->id_pembayaran_hutang) }}">@csrf @method('PATCH')<button class="btn btn-sm btn-success">Setujui</button></form>@endif @if($item->status_pembayaran === 'DRAF')<form class="d-inline" method="POST" action="{{ route('hutang-pemasok.batalkan', $item->id_pembayaran_hutang) }}">@csrf @method('PATCH')<button class="btn btn-sm btn-light">Batalkan</button></form>@endif</td></tr>
        @empty<tr><td colspan="8" class="text-center text-muted py-4">Belum ada pembayaran hutang.</td></tr>@endforelse
    </tbody></table></div></div>

    <div class="modal fade" id="modalPembayaran" tabindex="-1"><div class="modal-dialog modal-xl"><div class="modal-content"><form method="POST" action="{{ route('hutang-pemasok.simpan') }}">@csrf<div class="modal-header"><h5 class="modal-title">Tambah Pembayaran Hutang</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><div class="row g-3"><div class="col-md-3"><label class="form-label">Pemasok</label><select class="form-select" name="id_pemasok" required><option value="">Pilih</option>@foreach($pemasokPilihan as $p)<option value="{{ $p->id_pemasok }}">{{ $p->nama_pemasok }}</option>@endforeach</select></div><div class="col-md-3"><label class="form-label">Kas/Bank</label><select class="form-select" name="id_kas_bank" required><option value="">Pilih</option>@foreach($kasBankPilihan as $k)<option value="{{ $k->id_kas_bank }}">{{ $k->nama_kas_bank }}</option>@endforeach</select></div><div class="col-md-3"><label class="form-label">Metode</label><select class="form-select" name="id_metode_pembayaran" required><option value="">Pilih</option>@foreach($metodePembayaranPilihan as $m)<option value="{{ $m->id_metode_pembayaran }}">{{ $m->nama_metode_pembayaran }}</option>@endforeach</select></div><div class="col-md-3"><label class="form-label">Tanggal</label><input class="form-control" type="date" name="tanggal_pembayaran" value="{{ date('Y-m-d') }}" required></div></div><div class="row g-3 mt-1"><div class="col-md-4"><label class="form-label">Nomor Bukti</label><input class="form-control" name="nomor_bukti"></div><div class="col-md-4"><label class="form-label">Biaya Pembayaran</label><input class="form-control" type="number" step="0.01" name="biaya_pembayaran" value="0"></div><div class="col-md-4"><label class="form-label">Keterangan</label><input class="form-control" name="keterangan"></div></div><div class="mt-3" data-payment-container><div class="border rounded p-3 mb-2" data-payment-row><div class="row g-2 align-items-end"><div class="col-md-5"><label class="form-label">Hutang</label><select class="form-select" data-field="id_hutang_pemasok" required><option value="">Pilih</option>@foreach($hutangPilihan as $h)<option value="{{ $h->id_hutang_pemasok }}">{{ $h->nama_pemasok }} · {{ $h->nomor_faktur_internal }} · Sisa Rp {{ number_format($h->sisa_hutang,0,',','.') }}</option>@endforeach</select></div><div class="col-md-3"><label class="form-label">Dialokasikan</label><input class="form-control" type="number" step="0.01" data-field="nilai_dialokasikan" value="0" required></div><div class="col-md-3"><label class="form-label">Potongan</label><input class="form-control" type="number" step="0.01" data-field="potongan_pembayaran" value="0"></div><div class="col-md-1"><button class="btn btn-outline-danger w-100" type="button" data-remove-payment>&times;</button></div></div></div></div><button class="btn btn-sm btn-light" type="button" data-add-payment>Tambah Alokasi</button></div><div class="modal-footer"><button class="btn btn-light" type="button" data-bs-dismiss="modal">Batal</button><button class="btn btn-primary">Simpan Draf</button></div></form></div></div></div>
@endsection

@push('scripts')
<script>
function susunPembayaran() { document.querySelectorAll('[data-payment-row]').forEach((row, index) => row.querySelectorAll('[data-field]').forEach((field) => field.name = `detail[${index}][${field.dataset.field}]`)); }
document.addEventListener('click', (event) => {
    if (event.target.closest('[data-add-payment]')) { const container = document.querySelector('[data-payment-container]'); const row = container.querySelector('[data-payment-row]').cloneNode(true); row.querySelectorAll('input').forEach((input) => input.value = '0'); row.querySelectorAll('select').forEach((select) => select.value = ''); container.appendChild(row); susunPembayaran(); }
    const remove = event.target.closest('[data-remove-payment]'); if (remove) { const container = remove.closest('[data-payment-container]'); if (container.querySelectorAll('[data-payment-row]').length > 1) remove.closest('[data-payment-row]').remove(); susunPembayaran(); }
});
susunPembayaran();
</script>
@endpush
