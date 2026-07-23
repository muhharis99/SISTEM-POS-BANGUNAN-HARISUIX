@extends('layouts.admin')

@section('judul','Stok Opname')

@section('breadcrumb')
<div class="d-flex align-items-center justify-content-between py-3"><div><h4 class="fs-18 fw-semibold mb-1">Stok Opname</h4><p class="text-muted mb-0">Pembekuan saldo sistem, pencatatan fisik, selisih, dan penyesuaian setelah persetujuan.</p></div><button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambahOpname">Tambah Opname</button></div>
@endsection

@section('content')
@if(session('berhasil'))<div class="alert alert-success">{{ session('berhasil') }}</div>@endif
@if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<div class="alert alert-info">Alur: <strong>DRAF → PROSES → SELESAI → DISETUJUI</strong>. Persetujuan membentuk dokumen penyesuaian otomatis untuk setiap selisih.</div>
<div class="card"><div class="card-header"><form class="row g-2"><div class="col-md-6"><input class="form-control" name="pencarian" value="{{ $pencarian }}" placeholder="Nomor opname, gudang, atau keterangan"></div><div class="col-auto"><button class="btn btn-primary">Cari</button></div><div class="col-auto"><a class="btn btn-light" href="{{ route('stok-opname.index') }}">Reset</a></div></form></div><div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead><tr><th>Dokumen</th><th>Gudang</th><th>Tanggal</th><th>Detail / Selisih</th><th>Status</th><th class="text-end">Aksi</th></tr></thead><tbody>
@forelse($dokumen as $item)
@php $barisDokumen=$detail[$item->id_stok_opname]??collect(); $jumlahSelisih=$barisDokumen->sum('jumlah_selisih'); @endphp
<tr><td><strong>{{ $item->nomor_stok_opname }}</strong><br><small class="text-muted">{{ $item->keterangan ?: '-' }}</small></td><td>{{ $item->nama_gudang }}</td><td>{{ \Carbon\Carbon::parse($item->tanggal_stok_opname)->format('d-m-Y') }}</td><td>{{ $barisDokumen->count() }} baris<br><small class="text-muted">Total selisih: {{ number_format($jumlahSelisih,3,',','.') }}</small></td><td><span class="badge badge-soft-{{ $item->status_stok_opname==='DISETUJUI'?'success':($item->status_stok_opname==='DIBATALKAN'?'danger':'warning') }}">{{ $item->status_stok_opname }}</span></td><td class="text-end text-nowrap">
@if(in_array($item->status_stok_opname,['DRAF','PROSES'],true))<button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalEditOpname{{ $item->id_stok_opname }}">Edit</button>@endif
@if($item->status_stok_opname==='DRAF')<form class="d-inline" method="POST" action="{{ route('stok-opname.mulai',$item->id_stok_opname) }}">@csrf @method('PATCH')<button class="btn btn-sm btn-warning" onclick="return confirm('Mulai opname dan bekukan saldo sistem?')">Mulai</button></form>@endif
@if($item->status_stok_opname==='PROSES')<form class="d-inline" method="POST" action="{{ route('stok-opname.selesai',$item->id_stok_opname) }}">@csrf @method('PATCH')<button class="btn btn-sm btn-info" onclick="return confirm('Selesaikan penghitungan fisik?')">Selesai</button></form>@endif
@if($item->status_stok_opname==='SELESAI' && auth()->user()->memilikiHakAkses('STOK_OPNAME_SETUJUI',session('id_cabang_aktif')))<form class="d-inline" method="POST" action="{{ route('stok-opname.setujui',$item->id_stok_opname) }}">@csrf @method('PATCH')<button class="btn btn-sm btn-success" onclick="return confirm('Setujui dan proses seluruh selisih ke saldo stok?')">Setujui</button></form>@endif
@if(in_array($item->status_stok_opname,['DRAF','PROSES','SELESAI'],true))<form class="d-inline" method="POST" action="{{ route('stok-opname.batalkan',$item->id_stok_opname) }}">@csrf @method('PATCH')<button class="btn btn-sm btn-outline-danger" onclick="return confirm('Batalkan stok opname ini?')">Batalkan</button></form>@endif
</td></tr>
@empty<tr><td colspan="6" class="text-center text-muted py-4">Belum ada stok opname.</td></tr>@endforelse
</tbody></table></div><div class="card-footer">{{ $dokumen->links() }}</div></div>

<div class="modal fade" id="modalTambahOpname" tabindex="-1"><div class="modal-dialog modal-xl"><div class="modal-content"><form method="POST" action="{{ route('stok-opname.simpan') }}">@csrf<div class="modal-header"><h5 class="modal-title">Tambah Stok Opname</h5><button class="btn-close" type="button" data-bs-dismiss="modal"></button></div><div class="modal-body">@include('stok_opname.partials.form',['item'=>null,'prefix'=>'tambah'])</div><div class="modal-footer"><button class="btn btn-light" type="button" data-bs-dismiss="modal">Batal</button><button class="btn btn-primary">Simpan Draf</button></div></form></div></div></div>
@foreach($dokumen as $item) @if(in_array($item->status_stok_opname,['DRAF','PROSES'],true))
<div class="modal fade" id="modalEditOpname{{ $item->id_stok_opname }}" tabindex="-1"><div class="modal-dialog modal-xl"><div class="modal-content"><form method="POST" action="{{ route('stok-opname.ubah',$item->id_stok_opname) }}">@csrf @method('PUT')<div class="modal-header"><h5 class="modal-title">Edit {{ $item->nomor_stok_opname }}</h5><button class="btn-close" type="button" data-bs-dismiss="modal"></button></div><div class="modal-body">@include('stok_opname.partials.form',['item'=>$item,'prefix'=>'edit'.$item->id_stok_opname])</div><div class="modal-footer"><button class="btn btn-light" type="button" data-bs-dismiss="modal">Batal</button><button class="btn btn-primary">Simpan Perubahan</button></div></form></div></div></div>
@endif @endforeach
@endsection

@push('scripts')
<script>
function namaOpname(wadah){wadah.querySelectorAll('[data-baris-opname]').forEach((baris,index)=>baris.querySelectorAll('[data-field]').forEach(field=>field.name=`detail_opname[${index}][${field.dataset.field}]`));}
function saringOpname(form){const gudang=form.querySelector('[data-gudang-opname]')?.value||'';form.querySelectorAll('[data-lokasi-opname]').forEach(select=>{Array.from(select.options).forEach(option=>{if(!option.value)return;option.hidden=option.dataset.gudang!==gudang;option.disabled=option.dataset.gudang!==gudang;});if(select.selectedOptions[0]?.disabled)select.value='';});}
document.querySelectorAll('[data-wadah-opname]').forEach(namaOpname);document.querySelectorAll('form').forEach(saringOpname);document.addEventListener('change',event=>{if(event.target.matches('[data-gudang-opname]'))saringOpname(event.target.closest('form'));});document.addEventListener('click',event=>{const tambah=event.target.closest('[data-tambah-opname]');if(tambah){const wadah=document.getElementById(tambah.dataset.target);const baris=wadah.querySelector('[data-baris-opname]').cloneNode(true);baris.querySelectorAll('select').forEach(select=>select.value='');baris.querySelectorAll('input').forEach(input=>input.value=input.type==='number'?0:'');wadah.appendChild(baris);namaOpname(wadah);saringOpname(wadah.closest('form'));}const hapus=event.target.closest('[data-hapus-opname]');if(hapus){const wadah=hapus.closest('[data-wadah-opname]');if(wadah.querySelectorAll('[data-baris-opname]').length>1)hapus.closest('[data-baris-opname]').remove();namaOpname(wadah);}});
</script>
@endpush
