@extends('layouts.admin')

@section('judul','Penyesuaian Stok')

@section('breadcrumb')
<div class="d-flex align-items-center justify-content-between py-3"><div><h4 class="fs-18 fw-semibold mb-1">Penyesuaian Stok</h4><p class="text-muted mb-0">Koreksi tambah atau kurang dengan alasan dan persetujuan.</p></div><button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambahPenyesuaian">Tambah Penyesuaian</button></div>
@endsection

@section('content')
@if(session('berhasil'))<div class="alert alert-success">{{ session('berhasil') }}</div>@endif
@if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<div class="alert alert-info">Penyesuaian baru mengubah saldo setelah disetujui. Penyesuaian yang berasal dari stok opname bersifat otomatis dan tidak dapat diedit manual.</div>
<div class="card"><div class="card-header"><form class="row g-2"><div class="col-md-6"><input class="form-control" name="pencarian" value="{{ $pencarian }}" placeholder="Nomor, alasan, atau gudang"></div><div class="col-auto"><button class="btn btn-primary">Cari</button></div><div class="col-auto"><a class="btn btn-light" href="{{ route('penyesuaian-stok.index') }}">Reset</a></div></form></div><div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead><tr><th>Dokumen</th><th>Gudang</th><th>Asal</th><th>Detail</th><th class="text-end">Nilai</th><th>Status</th><th class="text-end">Aksi</th></tr></thead><tbody>
@forelse($dokumen as $item)
@php $barisDokumen=$detail[$item->id_penyesuaian_stok]??collect(); @endphp
<tr><td><strong>{{ $item->nomor_penyesuaian }}</strong><br><small class="text-muted">{{ $item->alasan_penyesuaian }}</small></td><td>{{ $item->nama_gudang }}</td><td>{{ $item->nomor_stok_opname ?: 'Manual' }}</td><td>{{ $barisDokumen->count() }} baris<br>@foreach($barisDokumen->take(2) as $baris)<small class="text-muted d-block">{{ $baris->kode_barang }} · {{ $baris->jenis_penyesuaian }} {{ number_format($baris->jumlah_dasar,3,',','.') }}</small>@endforeach</td><td class="text-end">Rp{{ number_format($item->total_nilai,2,',','.') }}</td><td><span class="badge badge-soft-{{ $item->status_penyesuaian==='DISETUJUI'?'success':($item->status_penyesuaian==='DIBATALKAN'?'danger':'warning') }}">{{ $item->status_penyesuaian }}</span></td><td class="text-end text-nowrap">
@if($item->status_penyesuaian==='DRAF' && $item->id_stok_opname===null)<button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalEditPenyesuaian{{ $item->id_penyesuaian_stok }}">Edit</button>@endif
@if($item->status_penyesuaian==='DRAF' && auth()->user()->memilikiHakAkses('PENYESUAIAN_STOK_SETUJUI',session('id_cabang_aktif')))<form class="d-inline" method="POST" action="{{ route('penyesuaian-stok.setujui',$item->id_penyesuaian_stok) }}">@csrf @method('PATCH')<button class="btn btn-sm btn-success" onclick="return confirm('Setujui penyesuaian dan ubah saldo stok?')">Setujui</button></form>@endif
@if($item->status_penyesuaian==='DRAF')<form class="d-inline" method="POST" action="{{ route('penyesuaian-stok.batalkan',$item->id_penyesuaian_stok) }}">@csrf @method('PATCH')<button class="btn btn-sm btn-outline-danger" onclick="return confirm('Batalkan penyesuaian ini?')">Batalkan</button></form>@endif
</td></tr>
@empty<tr><td colspan="7" class="text-center text-muted py-4">Belum ada penyesuaian stok.</td></tr>@endforelse
</tbody></table></div><div class="card-footer">{{ $dokumen->links() }}</div></div>

<div class="modal fade" id="modalTambahPenyesuaian" tabindex="-1"><div class="modal-dialog modal-xl"><div class="modal-content"><form method="POST" action="{{ route('penyesuaian-stok.simpan') }}">@csrf<div class="modal-header"><h5 class="modal-title">Tambah Penyesuaian</h5><button class="btn-close" type="button" data-bs-dismiss="modal"></button></div><div class="modal-body">@include('penyesuaian_stok.partials.form',['item'=>null,'prefix'=>'tambah'])</div><div class="modal-footer"><button class="btn btn-light" type="button" data-bs-dismiss="modal">Batal</button><button class="btn btn-primary">Simpan Draf</button></div></form></div></div></div>
@foreach($dokumen as $item) @if($item->status_penyesuaian==='DRAF' && $item->id_stok_opname===null)
<div class="modal fade" id="modalEditPenyesuaian{{ $item->id_penyesuaian_stok }}" tabindex="-1"><div class="modal-dialog modal-xl"><div class="modal-content"><form method="POST" action="{{ route('penyesuaian-stok.ubah',$item->id_penyesuaian_stok) }}">@csrf @method('PUT')<div class="modal-header"><h5 class="modal-title">Edit {{ $item->nomor_penyesuaian }}</h5><button class="btn-close" type="button" data-bs-dismiss="modal"></button></div><div class="modal-body">@include('penyesuaian_stok.partials.form',['item'=>$item,'prefix'=>'edit'.$item->id_penyesuaian_stok])</div><div class="modal-footer"><button class="btn btn-light" type="button" data-bs-dismiss="modal">Batal</button><button class="btn btn-primary">Simpan Perubahan</button></div></form></div></div></div>
@endif @endforeach
@endsection

@push('scripts')
<script>
function namaPenyesuaian(wadah){wadah.querySelectorAll('[data-baris-penyesuaian]').forEach((baris,index)=>baris.querySelectorAll('[data-field]').forEach(field=>field.name=`detail_penyesuaian[${index}][${field.dataset.field}]`));}
function saringPenyesuaian(form){const gudang=form.querySelector('[data-gudang-penyesuaian]')?.value||'';form.querySelectorAll('[data-lokasi-penyesuaian]').forEach(select=>{Array.from(select.options).forEach(option=>{if(!option.value)return;option.hidden=option.dataset.gudang!==gudang;option.disabled=option.dataset.gudang!==gudang;});if(select.selectedOptions[0]?.disabled)select.value='';});}
document.querySelectorAll('[data-wadah-penyesuaian]').forEach(namaPenyesuaian);document.querySelectorAll('form').forEach(saringPenyesuaian);document.addEventListener('change',event=>{if(event.target.matches('[data-gudang-penyesuaian]'))saringPenyesuaian(event.target.closest('form'));});document.addEventListener('click',event=>{const tambah=event.target.closest('[data-tambah-penyesuaian]');if(tambah){const wadah=document.getElementById(tambah.dataset.target);const baris=wadah.querySelector('[data-baris-penyesuaian]').cloneNode(true);baris.querySelectorAll('select').forEach(select=>select.value=select.dataset.field==='jenis_penyesuaian'?'TAMBAH':'');baris.querySelectorAll('input').forEach(input=>input.value=input.type==='number'?(input.dataset.field==='jumlah_dasar'?1:0):'');wadah.appendChild(baris);namaPenyesuaian(wadah);saringPenyesuaian(wadah.closest('form'));}const hapus=event.target.closest('[data-hapus-penyesuaian]');if(hapus){const wadah=hapus.closest('[data-wadah-penyesuaian]');if(wadah.querySelectorAll('[data-baris-penyesuaian]').length>1)hapus.closest('[data-baris-penyesuaian]').remove();namaPenyesuaian(wadah);}});
</script>
@endpush
