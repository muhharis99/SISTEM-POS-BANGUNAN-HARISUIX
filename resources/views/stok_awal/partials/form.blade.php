@php
    $barisDetail = $item ? ($detail[$item->id_stok_awal] ?? collect()) : collect([null]);
    $idGudangTerpilih = old('id_gudang', $item->id_gudang ?? '');
@endphp
<div class="row g-3">
    <div class="col-md-4">
        <label class="form-label">Gudang <span class="text-danger">*</span></label>
        <select class="form-select" name="id_gudang" data-pilih-gudang required>
            <option value="">Pilih gudang</option>
            @foreach ($gudangPilihan as $gudang)
                <option value="{{ $gudang->id_gudang }}" @selected((int) $idGudangTerpilih === (int) $gudang->id_gudang)>{{ $gudang->nama_gudang }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label">Tanggal stok awal <span class="text-danger">*</span></label>
        <input class="form-control" type="date" name="tanggal_stok_awal" value="{{ old('tanggal_stok_awal', $item->tanggal_stok_awal ?? now()->toDateString()) }}" required>
    </div>
    <div class="col-md-4">
        <label class="form-label">Keterangan</label>
        <input class="form-control" name="keterangan" value="{{ old('keterangan', $item->keterangan ?? '') }}" maxlength="255">
    </div>
</div>

<div class="d-flex justify-content-between align-items-center mt-4 mb-2">
    <div><h6 class="mb-0">Detail stok awal</h6><small class="text-muted">Jumlah mengikuti jumlah desimal satuan yang dipilih.</small></div>
    <button class="btn btn-sm btn-outline-primary" type="button" data-tambah-baris data-target="detail-{{ $prefix }}">Tambah Baris</button>
</div>
<div class="table-responsive">
    <table class="table table-bordered align-middle">
        <thead><tr><th style="min-width:260px">Barang / Satuan</th><th style="min-width:210px">Lokasi</th><th>Jumlah</th><th>HPP</th><th>Nomor lot</th><th>Kedaluwarsa</th><th></th></tr></thead>
        <tbody id="detail-{{ $prefix }}" data-wadah-detail>
            @foreach ($barisDetail as $baris)
                <tr data-baris-detail>
                    <td>
                        <select class="form-select" data-field="id_barang_satuan" required>
                            <option value="">Pilih barang</option>
                            @foreach ($barangSatuanPilihan as $pilihan)
                                <option value="{{ $pilihan->id_barang_satuan }}" data-desimal="{{ $pilihan->jumlah_desimal }}" @selected((int) ($baris->id_barang_satuan ?? 0) === (int) $pilihan->id_barang_satuan)>{{ $pilihan->kode_barang }} — {{ $pilihan->nama_barang }} / {{ $pilihan->kode_satuan }}</option>
                            @endforeach
                        </select>
                    </td>
                    <td>
                        <select class="form-select" data-field="id_lokasi_gudang" data-pilih-lokasi required>
                            <option value="">Pilih lokasi</option>
                            @foreach ($lokasiPilihan as $lokasi)
                                <option value="{{ $lokasi->id_lokasi_gudang }}" data-gudang="{{ $lokasi->id_gudang }}" @selected((int) ($baris->id_lokasi_gudang ?? 0) === (int) $lokasi->id_lokasi_gudang)>{{ $lokasi->kode_lokasi }} — {{ $lokasi->nama_lokasi }}</option>
                            @endforeach
                        </select>
                    </td>
                    <td><input class="form-control" type="number" min="0" step="0.001" data-field="jumlah" value="{{ $baris->jumlah ?? 1 }}" required></td>
                    <td><input class="form-control" type="number" min="0" step="0.0001" data-field="harga_pokok" value="{{ $baris->harga_pokok ?? 0 }}" required></td>
                    <td><input class="form-control" data-field="nomor_lot" value="{{ $baris->nomor_lot ?? '' }}" maxlength="100"></td>
                    <td><input class="form-control" type="date" data-field="tanggal_kedaluwarsa" value="{{ $baris->tanggal_kedaluwarsa ?? '' }}"></td>
                    <td><button class="btn btn-sm btn-outline-danger" type="button" data-hapus-baris>Hapus</button></td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
