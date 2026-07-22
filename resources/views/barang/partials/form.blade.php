@php
    $barisSatuan = $item ? ($satuanBarang[$item->id_barang] ?? collect()) : collect([null]);
    if ($barisSatuan->isEmpty()) $barisSatuan = collect([null]);
    $prefix = $prefix ?? 'barang';
@endphp
<div class="row g-3">
    <div class="col-md-4"><label class="form-label">Kode Barang</label><input class="form-control" name="kode_barang" value="{{ $item->kode_barang ?? '' }}" required></div>
    <div class="col-md-8"><label class="form-label">Nama Barang/Jasa</label><input class="form-control" name="nama_barang" value="{{ $item->nama_barang ?? '' }}" required></div>
    <div class="col-md-4"><label class="form-label">Kategori</label><select class="form-select" name="id_kategori_barang" required><option value="">Pilih</option>@foreach ($kategori as $opsi)<option value="{{ $opsi->id_kategori_barang }}" @selected(($item->id_kategori_barang ?? null) == $opsi->id_kategori_barang)>{{ $opsi->nama_kategori }}</option>@endforeach</select></div>
    <div class="col-md-4"><label class="form-label">Merek</label><select class="form-select" name="id_merek_barang"><option value="">Tanpa merek</option>@foreach ($merek as $opsi)<option value="{{ $opsi->id_merek_barang }}" @selected(($item->id_merek_barang ?? null) == $opsi->id_merek_barang)>{{ $opsi->nama_merek }}</option>@endforeach</select></div>
    <div class="col-md-4"><label class="form-label">Satuan Dasar</label><select class="form-select" name="id_satuan_dasar" required>@foreach ($satuan as $opsi)<option value="{{ $opsi->id_satuan }}" data-desimal="{{ $opsi->jumlah_desimal }}" @selected(($item->id_satuan_dasar ?? null) == $opsi->id_satuan)>{{ $opsi->nama_satuan }} ({{ $opsi->jumlah_desimal }} desimal)</option>@endforeach</select></div>
    <div class="col-md-4"><label class="form-label">Jenis</label><select class="form-select" name="jenis_barang"><option value="BARANG" @selected(($item->jenis_barang ?? 'BARANG') === 'BARANG')>Barang</option><option value="JASA" @selected(($item->jenis_barang ?? '') === 'JASA')>Jasa</option></select></div>
    <div class="col-md-4"><label class="form-label">Metode Persediaan</label><select class="form-select" name="metode_persediaan"><option value="RATA_RATA" @selected(($item->metode_persediaan ?? 'RATA_RATA') === 'RATA_RATA')>Rata-rata</option><option value="MASUK_PERTAMA_KELUAR_PERTAMA" @selected(($item->metode_persediaan ?? '') === 'MASUK_PERTAMA_KELUAR_PERTAMA')>FIFO</option></select></div>
    <div class="col-md-2"><label class="form-label">Stok Min.</label><input class="form-control" type="number" step="any" name="stok_minimum" value="{{ $item->stok_minimum ?? 0 }}" required></div>
    <div class="col-md-2"><label class="form-label">Stok Maks.</label><input class="form-control" type="number" step="any" name="stok_maksimum" value="{{ $item->stok_maksimum ?? 0 }}" required></div>
    <div class="col-md-3"><label class="form-label">Berat (kg)</label><input class="form-control" type="number" step="0.001" name="berat_kilogram" value="{{ $item->berat_kilogram ?? 0 }}"></div>
    <div class="col-md-3"><label class="form-label">Panjang (cm)</label><input class="form-control" type="number" step="0.001" name="panjang_sentimeter" value="{{ $item->panjang_sentimeter ?? 0 }}"></div>
    <div class="col-md-3"><label class="form-label">Lebar (cm)</label><input class="form-control" type="number" step="0.001" name="lebar_sentimeter" value="{{ $item->lebar_sentimeter ?? 0 }}"></div>
    <div class="col-md-3"><label class="form-label">Tinggi (cm)</label><input class="form-control" type="number" step="0.001" name="tinggi_sentimeter" value="{{ $item->tinggi_sentimeter ?? 0 }}"></div>
    <div class="col-md-4"><label class="form-label">Warna</label><input class="form-control" name="warna" value="{{ $item->warna ?? '' }}"></div>
    <div class="col-md-4"><label class="form-label">Ukuran</label><input class="form-control" name="ukuran" value="{{ $item->ukuran ?? '' }}"></div>
    <div class="col-12"><label class="form-label">Spesifikasi</label><textarea class="form-control" name="spesifikasi" rows="3">{{ $item->spesifikasi ?? '' }}</textarea></div>
    <div class="col-12 d-flex flex-wrap gap-3">
        @foreach ([['bisa_dibeli','Bisa Dibeli',1],['bisa_dijual','Bisa Dijual',1],['wajib_nomor_lot','Wajib Nomor Lot',0],['wajib_tanggal_kedaluwarsa','Wajib Kedaluwarsa',0]] as [$nama,$label,$default])
            <div class="form-check"><input type="hidden" name="{{ $nama }}" value="0"><input class="form-check-input" type="checkbox" name="{{ $nama }}" value="1" @checked((bool) ($item->{$nama} ?? $default))><label class="form-check-label">{{ $label }}</label></div>
        @endforeach
    </div>
</div>

<hr class="my-4">
<div class="d-flex justify-content-between align-items-center mb-2"><div><h6 class="mb-0">Satuan, Konversi, Barcode, dan Harga Acuan</h6><small class="text-muted">Satuan dasar otomatis menggunakan konversi 1. Kuantitas mengikuti jumlah desimal pada master satuan.</small></div><button type="button" class="btn btn-sm btn-outline-primary" data-tambah-satuan data-target="satuan-{{ $prefix }}">Tambah Satuan</button></div>
<div class="table-responsive"><table class="table table-sm align-middle"><thead><tr><th>Satuan</th><th>Barcode</th><th>Konversi</th><th>Harga Beli</th><th>Harga Jual</th><th>Beli Utama</th><th>Jual Utama</th><th></th></tr></thead><tbody id="satuan-{{ $prefix }}" data-wadah-satuan>
@foreach ($barisSatuan as $baris)
<tr data-baris-satuan>
    <td><select class="form-select form-select-sm" data-field="id_satuan">@foreach ($satuan as $opsi)<option value="{{ $opsi->id_satuan }}" data-desimal="{{ $opsi->jumlah_desimal }}" @selected(($baris->id_satuan ?? null) == $opsi->id_satuan)>{{ $opsi->kode_satuan }}</option>@endforeach</select></td>
    <td><input class="form-control form-control-sm" data-field="kode_batang" value="{{ $baris->kode_batang ?? '' }}"></td>
    <td><input class="form-control form-control-sm" type="number" step="0.000001" min="0.000001" data-field="nilai_konversi" value="{{ $baris->nilai_konversi ?? 1 }}"></td>
    <td><input class="form-control form-control-sm" type="number" step="0.01" min="0" data-field="harga_beli_acuan" value="{{ $baris->harga_beli_acuan ?? 0 }}"></td>
    <td><input class="form-control form-control-sm" type="number" step="0.01" min="0" data-field="harga_jual_acuan" value="{{ $baris->harga_jual_acuan ?? 0 }}"></td>
    <td class="text-center"><input class="form-check-input" type="checkbox" value="1" data-field="satuan_utama_pembelian" @checked((bool) ($baris->satuan_utama_pembelian ?? false))></td>
    <td class="text-center"><input class="form-check-input" type="checkbox" value="1" data-field="satuan_utama_penjualan" @checked((bool) ($baris->satuan_utama_penjualan ?? false))></td>
    <td><button type="button" class="btn btn-sm btn-outline-danger" data-hapus-satuan>×</button></td>
</tr>
@endforeach
</tbody></table></div>
