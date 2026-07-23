@extends('layouts.admin')

@section('judul', 'Transfer Stok')

@section('breadcrumb')
    <div class="d-flex align-items-center justify-content-between py-3">
        <div><h4 class="fs-18 fw-semibold mb-1">Transfer Stok</h4><p class="text-muted mb-0">Stok asal berkurang saat dikirim dan stok tujuan bertambah saat diterima.</p></div>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambahTransfer">Tambah Transfer</button>
    </div>
@endsection

@section('content')
    @if (session('berhasil'))<div class="alert alert-success">{{ session('berhasil') }}</div>@endif
    @if ($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <div class="alert alert-info">Transfer menggunakan alur <strong>DRAF → DISETUJUI → DIKIRIM → DITERIMA</strong>. Transfer yang sudah dikirim tidak dapat dibatalkan.</div>

    <div class="card">
        <div class="card-header"><form class="row g-2"><div class="col-md-6"><input class="form-control" name="pencarian" value="{{ $pencarian }}" placeholder="Nomor transfer atau nama gudang"></div><div class="col-auto"><button class="btn btn-primary">Cari</button></div><div class="col-auto"><a class="btn btn-light" href="{{ route('transfer-stok.index') }}">Reset</a></div></form></div>
        <div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead><tr><th>Dokumen</th><th>Asal → Tujuan</th><th>Tanggal</th><th>Detail</th><th>Status</th><th class="text-end">Aksi</th></tr></thead><tbody>
            @forelse ($dokumen as $item)
                @php $barisDokumen = $detail[$item->id_transfer_stok] ?? collect(); @endphp
                <tr>
                    <td><strong>{{ $item->nomor_transfer }}</strong><br><small class="text-muted">{{ $item->keterangan ?: '-' }}</small></td>
                    <td>{{ $item->nama_gudang_asal }} <i data-lucide="arrow-right" class="d-inline-block mx-1" style="width:16px"></i> {{ $item->nama_gudang_tujuan }}</td>
                    <td>{{ \Carbon\Carbon::parse($item->tanggal_transfer)->format('d-m-Y') }}</td>
                    <td>{{ $barisDokumen->count() }} baris<br>@foreach ($barisDokumen->take(2) as $baris)<small class="text-muted d-block">{{ $baris->kode_barang }} · diminta {{ number_format($baris->jumlah_diminta, 3, ',', '.') }}</small>@endforeach</td>
                    <td><span class="badge badge-soft-{{ $item->status_transfer === 'DITERIMA' ? 'success' : ($item->status_transfer === 'DIBATALKAN' ? 'danger' : 'warning') }}">{{ $item->status_transfer }}</span></td>
                    <td class="text-end text-nowrap">
                        @if ($item->status_transfer === 'DRAF')
                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalEditTransfer{{ $item->id_transfer_stok }}">Edit</button>
                            @if (auth()->user()->memilikiHakAkses('TRANSFER_STOK_SETUJUI', session('id_cabang_aktif')))<form class="d-inline" method="POST" action="{{ route('transfer-stok.setujui', $item->id_transfer_stok) }}">@csrf @method('PATCH')<button class="btn btn-sm btn-success" onclick="return confirm('Setujui transfer ini?')">Setujui</button></form>@endif
                        @endif
                        @if ($item->status_transfer === 'DISETUJUI' && auth()->user()->memilikiHakAkses('TRANSFER_STOK_KIRIM', session('id_cabang_aktif')))<form class="d-inline" method="POST" action="{{ route('transfer-stok.kirim', $item->id_transfer_stok) }}">@csrf @method('PATCH')<button class="btn btn-sm btn-warning" onclick="return confirm('Kirim transfer dan kurangi stok asal?')">Kirim</button></form>@endif
                        @if ($item->status_transfer === 'DIKIRIM' && auth()->user()->memilikiHakAkses('TRANSFER_STOK_TERIMA', session('id_cabang_aktif')))<form class="d-inline" method="POST" action="{{ route('transfer-stok.terima', $item->id_transfer_stok) }}">@csrf @method('PATCH')<button class="btn btn-sm btn-success" onclick="return confirm('Terima transfer dan tambah stok tujuan?')">Terima</button></form>@endif
                        @if (in_array($item->status_transfer, ['DRAF','DISETUJUI'], true))<form class="d-inline" method="POST" action="{{ route('transfer-stok.batalkan', $item->id_transfer_stok) }}">@csrf @method('PATCH')<button class="btn btn-sm btn-outline-danger" onclick="return confirm('Batalkan transfer ini?')">Batalkan</button></form>@endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center text-muted py-4">Belum ada transfer stok.</td></tr>
            @endforelse
        </tbody></table></div><div class="card-footer">{{ $dokumen->links() }}</div>
    </div>

    <div class="modal fade" id="modalTambahTransfer" tabindex="-1"><div class="modal-dialog modal-xl"><div class="modal-content"><form method="POST" action="{{ route('transfer-stok.simpan') }}">@csrf<div class="modal-header"><h5 class="modal-title">Tambah Transfer Stok</h5><button class="btn-close" type="button" data-bs-dismiss="modal"></button></div><div class="modal-body">@include('transfer_stok.partials.form', ['item' => null, 'prefix' => 'tambah'])</div><div class="modal-footer"><button class="btn btn-light" type="button" data-bs-dismiss="modal">Batal</button><button class="btn btn-primary">Simpan Draf</button></div></form></div></div></div>
    @foreach ($dokumen as $item) @if ($item->status_transfer === 'DRAF')
        <div class="modal fade" id="modalEditTransfer{{ $item->id_transfer_stok }}" tabindex="-1"><div class="modal-dialog modal-xl"><div class="modal-content"><form method="POST" action="{{ route('transfer-stok.ubah', $item->id_transfer_stok) }}">@csrf @method('PUT')<div class="modal-header"><h5 class="modal-title">Edit {{ $item->nomor_transfer }}</h5><button class="btn-close" type="button" data-bs-dismiss="modal"></button></div><div class="modal-body">@include('transfer_stok.partials.form', ['item' => $item, 'prefix' => 'edit'.$item->id_transfer_stok])</div><div class="modal-footer"><button class="btn btn-light" type="button" data-bs-dismiss="modal">Batal</button><button class="btn btn-primary">Simpan Perubahan</button></div></form></div></div></div>
    @endif @endforeach
@endsection

@push('scripts')
<script>
function namaTransfer(wadah){wadah.querySelectorAll('[data-baris-transfer]').forEach((baris,index)=>baris.querySelectorAll('[data-field]').forEach(field=>field.name=`detail_transfer[${index}][${field.dataset.field}]`));}
function saringTransfer(form){const asal=form.querySelector('[data-gudang-asal]')?.value||'';const tujuan=form.querySelector('[data-gudang-tujuan]')?.value||'';form.querySelectorAll('[data-lokasi-asal]').forEach(select=>{Array.from(select.options).forEach(option=>{if(!option.value)return;option.hidden=option.dataset.gudang!==asal;option.disabled=option.dataset.gudang!==asal;});if(select.selectedOptions[0]?.disabled)select.value='';});form.querySelectorAll('[data-lokasi-tujuan]').forEach(select=>{Array.from(select.options).forEach(option=>{if(!option.value)return;option.hidden=option.dataset.gudang!==tujuan;option.disabled=option.dataset.gudang!==tujuan;});if(select.selectedOptions[0]?.disabled)select.value='';});}
document.querySelectorAll('[data-wadah-transfer]').forEach(namaTransfer);document.querySelectorAll('form').forEach(saringTransfer);
document.addEventListener('change',event=>{if(event.target.matches('[data-gudang-asal],[data-gudang-tujuan]'))saringTransfer(event.target.closest('form'));});
document.addEventListener('click',event=>{const tambah=event.target.closest('[data-tambah-transfer]');if(tambah){const wadah=document.getElementById(tambah.dataset.target);const baris=wadah.querySelector('[data-baris-transfer]').cloneNode(true);baris.querySelectorAll('select').forEach(select=>select.value='');baris.querySelectorAll('input').forEach(input=>input.value=input.type==='number'?1:'');wadah.appendChild(baris);namaTransfer(wadah);saringTransfer(wadah.closest('form'));}const hapus=event.target.closest('[data-hapus-transfer]');if(hapus){const wadah=hapus.closest('[data-wadah-transfer]');if(wadah.querySelectorAll('[data-baris-transfer]').length>1)hapus.closest('[data-baris-transfer]').remove();namaTransfer(wadah);}});
</script>
@endpush
