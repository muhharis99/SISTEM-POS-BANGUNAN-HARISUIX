@php
    $barisDetail = $item ? ($detail[$item->id_daftar_harga] ?? collect()) : collect([null]);
    if ($barisDetail->isEmpty()) $barisDetail = collect([null]);
    $prefix = $prefix ?? 'harga';
@endphp
<div class="row g-3">
    <div class="col-md-3"><label class="form-label">Kode Daftar Harga</label><input class="form-control" name="kode_daftar_harga" value="{{ $item->kode_daftar_harga ?? '' }}" required></div>
    <div class="col-md-5"><label class="form-label">Nama Daftar Harga</label><input class="form-control" name="nama_daftar_harga" value="{{ $item->nama_daftar_harga ?? '' }}" required></div>
    <div class="col-md-4"><label class="form-label">Jenis Pelanggan</label><select class="form-select" name="id_jenis_pelanggan"><option value="">Semua jenis pelanggan</option>@foreach($jenisPelanggan as $jenis)<option value="{{ $jenis->id_jenis_pelanggan }}" @selected(($item->id_jenis_pelanggan ?? null)==$jenis->id_jenis_pelanggan)>{{ $jenis->nama_jenis_pelanggan }}</option>@endforeach</select></div>
    <div class="col-md-4"><label class="form-label">Tanggal Mulai</label><input class="form-control" type="date" name="tanggal_mulai" value="{{ $item->tanggal_mulai ?? now()->format('Y-m-d') }}" required></div>
    <div class="col-md-4"><label class="form-label">Tanggal Selesai</label><input class="form-control" type="date" name="tanggal_selesai" value="{{ $item->tanggal_selesai ?? '' }}"><small class="text-muted">Kosong berarti tidak terbatas.</small></div>
    <div class="col-md-4"><label class="form-label">Prioritas</label><input class="form-control" type="number" min="0" max="65535" name="prioritas" value="{{ $item->prioritas ?? 0 }}" required></div>
</div>
<hr class="my-4">
<div class="d-flex justify-content-between align-items-center mb-2"><div><h6 class="mb-0">Detail Harga</h6><small class="text-muted">Jumlah minimum divalidasi berdasarkan <code>satuan.jumlah_desimal</code>, bukan daftar satuan hardcode.</small></div><button type="button" class="btn btn-sm btn-outline-primary" data-tambah-detail-harga data-target="detail-{{ $prefix }}">Tambah Baris</button></div>
<div class="table-responsive"><table class="table table-sm align-middle"><thead><tr><th style="min-width:300px">Barang & Satuan</th><th>Jumlah Minimum</th><th>Harga Jual</th><th>Potongan (%)</th><th></th></tr></thead><tbody id="detail-{{ $prefix }}" data-wadah-detail-harga>
@foreach($barisDetail as $baris)
<tr data-baris-detail-harga>
<td><select class="form-select form-select-sm" data-field="id_barang_satuan">@foreach($barangSatuan as $opsi)<option value="{{ $opsi->id_barang_satuan }}" data-desimal="{{ $opsi->jumlah_desimal }}" @selected(($baris->id_barang_satuan ?? null)==$opsi->id_barang_satuan)>{{ $opsi->kode_barang }} · {{ $opsi->nama_barang }} / {{ $opsi->kode_satuan }} ({{ $opsi->jumlah_desimal }} desimal)</option>@endforeach</select></td>
<td><input class="form-control form-control-sm" type="number" step="any" min="0.000001" data-field="jumlah_minimum" value="{{ $baris->jumlah_minimum ?? 1 }}"></td>
<td><input class="form-control form-control-sm" type="number" step="0.01" min="0" data-field="harga_jual" value="{{ $baris->harga_jual ?? 0 }}"></td>
<td><input class="form-control form-control-sm" type="number" step="0.0001" min="0" max="100" data-field="potongan_persen" value="{{ $baris->potongan_persen ?? 0 }}"></td>
<td><button type="button" class="btn btn-sm btn-outline-danger" data-hapus-detail-harga>×</button></td>
</tr>
@endforeach
</tbody></table></div>
