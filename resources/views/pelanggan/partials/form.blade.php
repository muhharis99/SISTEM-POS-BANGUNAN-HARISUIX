@php
    $barisAlamat = $item ? ($alamat[$item->id_pelanggan] ?? collect()) : collect([null]);
    if ($barisAlamat->isEmpty()) $barisAlamat = collect([null]);
    $prefix = $prefix ?? 'pelanggan';
@endphp
<div class="row g-3">
    <div class="col-md-4"><label class="form-label">Kode Pelanggan</label><input class="form-control" name="kode_pelanggan" value="{{ $item->kode_pelanggan ?? '' }}" required></div>
    <div class="col-md-8"><label class="form-label">Nama Pelanggan</label><input class="form-control" name="nama_pelanggan" value="{{ $item->nama_pelanggan ?? '' }}" required></div>
    <div class="col-md-4"><label class="form-label">Jenis Pelanggan</label><select class="form-select" name="id_jenis_pelanggan" required>@foreach($jenisPelanggan as $opsi)<option value="{{ $opsi->id_jenis_pelanggan }}" @selected(($item->id_jenis_pelanggan ?? null)==$opsi->id_jenis_pelanggan)>{{ $opsi->nama_jenis_pelanggan }}</option>@endforeach</select></div>
    <div class="col-md-4"><label class="form-label">Jenis Identitas</label><select class="form-select" name="jenis_identitas"><option value="">Tanpa identitas</option>@foreach(['KTP','SIM','PASPOR','LAINNYA'] as $jenis)<option value="{{ $jenis }}" @selected(($item->jenis_identitas ?? null)===$jenis)>{{ $jenis }}</option>@endforeach</select></div>
    <div class="col-md-4"><label class="form-label">Nomor Identitas</label><input class="form-control" name="nomor_identitas" value="{{ $item->nomor_identitas ?? '' }}"></div>
    <div class="col-md-4"><label class="form-label">NPWP</label><input class="form-control" name="nomor_pokok_wajib_pajak" value="{{ $item->nomor_pokok_wajib_pajak ?? '' }}"></div>
    <div class="col-md-4"><label class="form-label">Telepon</label><input class="form-control" name="telepon" value="{{ $item->telepon ?? '' }}"></div>
    <div class="col-md-4"><label class="form-label">WhatsApp</label><input class="form-control" name="nomor_whatsapp" value="{{ $item->nomor_whatsapp ?? '' }}"></div>
    <div class="col-md-6"><label class="form-label">Surel</label><input class="form-control" type="email" name="surel" value="{{ $item->surel ?? '' }}"></div>
    <div class="col-md-6"><label class="form-label">Nama Kontak</label><input class="form-control" name="nama_kontak" value="{{ $item->nama_kontak ?? '' }}"></div>
    <div class="col-12"><label class="form-label">Alamat Utama</label><textarea class="form-control" name="alamat_utama" rows="2">{{ $item->alamat_utama ?? '' }}</textarea></div>
    <div class="col-md-3"><label class="form-label">Provinsi</label><input class="form-control" name="provinsi" value="{{ $item->provinsi ?? '' }}"></div>
    <div class="col-md-3"><label class="form-label">Kabupaten/Kota</label><input class="form-control" name="kabupaten_kota" value="{{ $item->kabupaten_kota ?? '' }}"></div>
    <div class="col-md-3"><label class="form-label">Kecamatan</label><input class="form-control" name="kecamatan" value="{{ $item->kecamatan ?? '' }}"></div>
    <div class="col-md-3"><label class="form-label">Kelurahan</label><input class="form-control" name="kelurahan" value="{{ $item->kelurahan ?? '' }}"></div>
    <div class="col-md-3"><label class="form-label">Kode Pos</label><input class="form-control" name="kode_pos" value="{{ $item->kode_pos ?? '' }}"></div>
    <div class="col-md-3"><label class="form-label">Batas Piutang</label><input class="form-control" type="number" step="0.01" min="0" name="batas_piutang" value="{{ $item->batas_piutang ?? 0 }}"></div>
    <div class="col-md-3"><label class="form-label">Jatuh Tempo (hari)</label><input class="form-control" type="number" min="0" name="lama_jatuh_tempo" value="{{ $item->lama_jatuh_tempo ?? 0 }}"></div>
    <div class="col-md-3"><label class="form-label">Potongan (%)</label><input class="form-control" type="number" step="0.0001" min="0" max="100" name="potongan_persen" value="{{ $item->potongan_persen ?? 0 }}"></div>
</div>
<hr class="my-4">
<div class="d-flex justify-content-between align-items-center mb-2"><div><h6 class="mb-0">Alamat Tambahan / Proyek</h6><small class="text-muted">Digunakan untuk alamat pengiriman dan lokasi proyek pelanggan.</small></div><button type="button" class="btn btn-sm btn-outline-primary" data-tambah-alamat data-target="alamat-{{ $prefix }}">Tambah Alamat</button></div>
<div id="alamat-{{ $prefix }}" data-wadah-alamat>
@foreach($barisAlamat as $baris)
<div class="border rounded p-3 mb-2" data-baris-alamat><div class="row g-2">
    <div class="col-md-3"><input class="form-control form-control-sm" data-field="nama_alamat" placeholder="Nama alamat" value="{{ $baris->nama_alamat ?? '' }}"></div>
    <div class="col-md-3"><input class="form-control form-control-sm" data-field="nama_penerima" placeholder="Nama penerima" value="{{ $baris->nama_penerima ?? '' }}"></div>
    <div class="col-md-3"><input class="form-control form-control-sm" data-field="telepon_penerima" placeholder="Telepon penerima" value="{{ $baris->telepon_penerima ?? '' }}"></div>
    <div class="col-md-3"><div class="form-check mt-2"><input class="form-check-input" type="checkbox" value="1" data-field="alamat_utama" @checked((bool)($baris->alamat_utama ?? false))><label class="form-check-label">Alamat utama tambahan</label></div></div>
    <div class="col-12"><textarea class="form-control form-control-sm" data-field="alamat" rows="2" placeholder="Alamat lengkap">{{ $baris->alamat ?? '' }}</textarea></div>
    <div class="col-md-3"><input class="form-control form-control-sm" data-field="provinsi" placeholder="Provinsi" value="{{ $baris->provinsi ?? '' }}"></div>
    <div class="col-md-3"><input class="form-control form-control-sm" data-field="kabupaten_kota" placeholder="Kabupaten/Kota" value="{{ $baris->kabupaten_kota ?? '' }}"></div>
    <div class="col-md-2"><input class="form-control form-control-sm" data-field="kecamatan" placeholder="Kecamatan" value="{{ $baris->kecamatan ?? '' }}"></div>
    <div class="col-md-2"><input class="form-control form-control-sm" data-field="kelurahan" placeholder="Kelurahan" value="{{ $baris->kelurahan ?? '' }}"></div>
    <div class="col-md-2"><input class="form-control form-control-sm" data-field="kode_pos" placeholder="Kode pos" value="{{ $baris->kode_pos ?? '' }}"></div>
    <div class="col-md-3"><input class="form-control form-control-sm" type="number" step="0.0000001" data-field="garis_lintang" placeholder="Latitude" value="{{ $baris->garis_lintang ?? '' }}"></div>
    <div class="col-md-3"><input class="form-control form-control-sm" type="number" step="0.0000001" data-field="garis_bujur" placeholder="Longitude" value="{{ $baris->garis_bujur ?? '' }}"></div>
    <div class="col-md-6 text-end"><button type="button" class="btn btn-sm btn-outline-danger" data-hapus-alamat>Hapus Baris</button></div>
</div></div>
@endforeach
</div>
