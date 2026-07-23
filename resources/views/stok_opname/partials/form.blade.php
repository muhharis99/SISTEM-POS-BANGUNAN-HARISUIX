@php
    $barisDetail = $item ? ($detail[$item->id_stok_opname] ?? collect()) : collect([null]);
@endphp
<div class="row g-3">
    <div class="col-md-4"><label class="form-label">Gudang <span class="text-danger">*</span></label><select class="form-select" name="id_gudang" data-gudang-opname required><option value="">Pilih gudang</option>@foreach($gudangPilihan as $gudang)<option value="{{ $gudang->id_gudang }}" @selected((int)($item->id_gudang ?? 0)===(int)$gudang->id_gudang)>{{ $gudang->nama_gudang }}</option>@endforeach</select></div>
    <div class="col-md-4"><label class="form-label">Tanggal opname</label><input class="form-control" type="date" name="tanggal_stok_opname" value="{{ old('tanggal_stok_opname',$item->tanggal_stok_opname ?? now()->toDateString()) }}" required></div>
    <div class="col-md-4"><label class="form-label">Keterangan</label><input class="form-control" name="keterangan" value="{{ old('keterangan',$item->keterangan ?? '') }}"></div>
</div>
<div class="d-flex justify-content-between align-items-center mt-4 mb-2"><div><h6 class="mb-0">Barang yang dihitung</h6><small class="text-muted">Jumlah fisik mengikuti satuan dasar barang.</small></div><button class="btn btn-sm btn-outline-primary" type="button" data-tambah-opname data-target="opname-{{ $prefix }}">Tambah Baris</button></div>
<div class="table-responsive"><table class="table table-bordered align-middle"><thead><tr><th style="min-width:260px">Barang</th><th style="min-width:210px">Lokasi</th><th>Jumlah fisik</th><th>Nomor lot</th><th>Kedaluwarsa</th><th></th></tr></thead><tbody id="opname-{{ $prefix }}" data-wadah-opname>
@foreach($barisDetail as $baris)
<tr data-baris-opname>
    <td><select class="form-select" data-field="id_barang" required><option value="">Pilih barang</option>@foreach($barangPilihan as $barang)<option value="{{ $barang->id_barang }}" @selected((int)($baris->id_barang ?? 0)===(int)$barang->id_barang)>{{ $barang->kode_barang }} — {{ $barang->nama_barang }} / {{ $barang->kode_satuan }}</option>@endforeach</select></td>
    <td><select class="form-select" data-field="id_lokasi_gudang" data-lokasi-opname required><option value="">Pilih lokasi</option>@foreach($lokasiPilihan as $lokasi)<option value="{{ $lokasi->id_lokasi_gudang }}" data-gudang="{{ $lokasi->id_gudang }}" @selected((int)($baris->id_lokasi_gudang ?? 0)===(int)$lokasi->id_lokasi_gudang)>{{ $lokasi->kode_lokasi }} — {{ $lokasi->nama_lokasi }}</option>@endforeach</select></td>
    <td><input class="form-control" type="number" min="0" step="0.001" data-field="jumlah_fisik" value="{{ $baris->jumlah_fisik ?? 0 }}" required></td>
    <td><input class="form-control" data-field="nomor_lot" value="{{ $baris->nomor_lot ?? '' }}"></td>
    <td><input class="form-control" type="date" data-field="tanggal_kedaluwarsa" value="{{ $baris->tanggal_kedaluwarsa ?? '' }}"></td>
    <td><button class="btn btn-sm btn-outline-danger" type="button" data-hapus-opname>Hapus</button></td>
</tr>
@endforeach
</tbody></table></div>
