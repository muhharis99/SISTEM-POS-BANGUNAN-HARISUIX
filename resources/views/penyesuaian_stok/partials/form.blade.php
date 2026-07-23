@php
    $barisDetail = $item ? ($detail[$item->id_penyesuaian_stok] ?? collect()) : collect([null]);
@endphp
<div class="row g-3">
    <div class="col-md-3"><label class="form-label">Gudang <span class="text-danger">*</span></label><select class="form-select" name="id_gudang" data-gudang-penyesuaian required><option value="">Pilih gudang</option>@foreach($gudangPilihan as $gudang)<option value="{{ $gudang->id_gudang }}" @selected((int)($item->id_gudang ?? 0)===(int)$gudang->id_gudang)>{{ $gudang->nama_gudang }}</option>@endforeach</select></div>
    <div class="col-md-3"><label class="form-label">Tanggal</label><input class="form-control" type="date" name="tanggal_penyesuaian" value="{{ old('tanggal_penyesuaian',$item->tanggal_penyesuaian ?? now()->toDateString()) }}" required></div>
    <div class="col-md-6"><label class="form-label">Alasan penyesuaian <span class="text-danger">*</span></label><input class="form-control" name="alasan_penyesuaian" value="{{ old('alasan_penyesuaian',$item->alasan_penyesuaian ?? '') }}" maxlength="255" required></div>
    <div class="col-12"><label class="form-label">Keterangan</label><input class="form-control" name="keterangan" value="{{ old('keterangan',$item->keterangan ?? '') }}"></div>
</div>
<div class="d-flex justify-content-between align-items-center mt-4 mb-2"><div><h6 class="mb-0">Detail penyesuaian</h6><small class="text-muted">Jumlah memakai satuan dasar barang.</small></div><button class="btn btn-sm btn-outline-primary" type="button" data-tambah-penyesuaian data-target="penyesuaian-{{ $prefix }}">Tambah Baris</button></div>
<div class="table-responsive"><table class="table table-bordered align-middle"><thead><tr><th style="min-width:250px">Barang</th><th style="min-width:200px">Lokasi</th><th>Jenis</th><th>Jumlah dasar</th><th>HPP</th><th>Nomor lot</th><th></th></tr></thead><tbody id="penyesuaian-{{ $prefix }}" data-wadah-penyesuaian>
@foreach($barisDetail as $baris)
<tr data-baris-penyesuaian>
<td><select class="form-select" data-field="id_barang" required><option value="">Pilih barang</option>@foreach($barangPilihan as $barang)<option value="{{ $barang->id_barang }}" @selected((int)($baris->id_barang ?? 0)===(int)$barang->id_barang)>{{ $barang->kode_barang }} — {{ $barang->nama_barang }} / {{ $barang->kode_satuan }}</option>@endforeach</select></td>
<td><select class="form-select" data-field="id_lokasi_gudang" data-lokasi-penyesuaian required><option value="">Pilih lokasi</option>@foreach($lokasiPilihan as $lokasi)<option value="{{ $lokasi->id_lokasi_gudang }}" data-gudang="{{ $lokasi->id_gudang }}" @selected((int)($baris->id_lokasi_gudang ?? 0)===(int)$lokasi->id_lokasi_gudang)>{{ $lokasi->kode_lokasi }} — {{ $lokasi->nama_lokasi }}</option>@endforeach</select></td>
<td><select class="form-select" data-field="jenis_penyesuaian"><option value="TAMBAH" @selected(($baris->jenis_penyesuaian ?? '')==='TAMBAH')>Tambah</option><option value="KURANG" @selected(($baris->jenis_penyesuaian ?? '')==='KURANG')>Kurang</option></select></td>
<td><input class="form-control" type="number" min="0" step="0.001" data-field="jumlah_dasar" value="{{ $baris->jumlah_dasar ?? 1 }}" required></td>
<td><input class="form-control" type="number" min="0" step="0.0001" data-field="harga_pokok" value="{{ $baris->harga_pokok ?? 0 }}" required></td>
<td><input class="form-control" data-field="nomor_lot" value="{{ $baris->nomor_lot ?? '' }}"></td>
<td><button class="btn btn-sm btn-outline-danger" type="button" data-hapus-penyesuaian>Hapus</button></td>
</tr>
@endforeach
</tbody></table></div>
