@php
    $barisDetail = $item ? ($detail[$item->id_transfer_stok] ?? collect()) : collect([null]);
@endphp
<div class="row g-3">
    <div class="col-md-3">
        <label class="form-label">Gudang asal <span class="text-danger">*</span></label>
        <select class="form-select" name="id_gudang_asal" data-gudang-asal required>
            <option value="">Pilih gudang asal</option>
            @foreach ($gudangPilihan as $gudang)<option value="{{ $gudang->id_gudang }}" @selected((int) ($item->id_gudang_asal ?? 0) === (int) $gudang->id_gudang)>{{ $gudang->nama_gudang }}</option>@endforeach
        </select>
    </div>
    <div class="col-md-3">
        <label class="form-label">Gudang tujuan <span class="text-danger">*</span></label>
        <select class="form-select" name="id_gudang_tujuan" data-gudang-tujuan required>
            <option value="">Pilih gudang tujuan</option>
            @foreach ($gudangPilihan as $gudang)<option value="{{ $gudang->id_gudang }}" @selected((int) ($item->id_gudang_tujuan ?? 0) === (int) $gudang->id_gudang)>{{ $gudang->nama_gudang }}</option>@endforeach
        </select>
    </div>
    <div class="col-md-3"><label class="form-label">Tanggal transfer</label><input class="form-control" type="date" name="tanggal_transfer" value="{{ old('tanggal_transfer', $item->tanggal_transfer ?? now()->toDateString()) }}" required></div>
    <div class="col-md-3"><label class="form-label">Tanggal kebutuhan</label><input class="form-control" type="date" name="tanggal_kebutuhan" value="{{ old('tanggal_kebutuhan', $item->tanggal_kebutuhan ?? '') }}"></div>
    <div class="col-12"><label class="form-label">Keterangan</label><input class="form-control" name="keterangan" value="{{ old('keterangan', $item->keterangan ?? '') }}"></div>
</div>

<div class="d-flex justify-content-between align-items-center mt-4 mb-2">
    <div><h6 class="mb-0">Detail transfer</h6><small class="text-muted">Jumlah dikonversi ke satuan dasar saat pengiriman.</small></div>
    <button class="btn btn-sm btn-outline-primary" type="button" data-tambah-transfer data-target="transfer-{{ $prefix }}">Tambah Baris</button>
</div>
<div class="table-responsive">
    <table class="table table-bordered align-middle">
        <thead><tr><th style="min-width:250px">Barang / Satuan</th><th style="min-width:190px">Lokasi asal</th><th style="min-width:190px">Lokasi tujuan</th><th>Jumlah</th><th>Nomor lot</th><th>Kedaluwarsa</th><th></th></tr></thead>
        <tbody id="transfer-{{ $prefix }}" data-wadah-transfer>
            @foreach ($barisDetail as $baris)
                <tr data-baris-transfer>
                    <td><select class="form-select" data-field="id_barang_satuan" required><option value="">Pilih barang</option>@foreach ($barangSatuanPilihan as $pilihan)<option value="{{ $pilihan->id_barang_satuan }}" @selected((int) ($baris->id_barang_satuan ?? 0) === (int) $pilihan->id_barang_satuan)>{{ $pilihan->kode_barang }} — {{ $pilihan->nama_barang }} / {{ $pilihan->kode_satuan }}</option>@endforeach</select></td>
                    <td><select class="form-select" data-field="id_lokasi_asal" data-lokasi-asal required><option value="">Pilih lokasi</option>@foreach ($lokasiPilihan as $lokasi)<option value="{{ $lokasi->id_lokasi_gudang }}" data-gudang="{{ $lokasi->id_gudang }}" @selected((int) ($baris->id_lokasi_asal ?? 0) === (int) $lokasi->id_lokasi_gudang)>{{ $lokasi->kode_lokasi }} — {{ $lokasi->nama_lokasi }}</option>@endforeach</select></td>
                    <td><select class="form-select" data-field="id_lokasi_tujuan" data-lokasi-tujuan required><option value="">Pilih lokasi</option>@foreach ($lokasiPilihan as $lokasi)<option value="{{ $lokasi->id_lokasi_gudang }}" data-gudang="{{ $lokasi->id_gudang }}" @selected((int) ($baris->id_lokasi_tujuan ?? 0) === (int) $lokasi->id_lokasi_gudang)>{{ $lokasi->kode_lokasi }} — {{ $lokasi->nama_lokasi }}</option>@endforeach</select></td>
                    <td><input class="form-control" type="number" min="0" step="0.001" data-field="jumlah_diminta" value="{{ $baris->jumlah_diminta ?? 1 }}" required></td>
                    <td><input class="form-control" data-field="nomor_lot" value="{{ $baris->nomor_lot ?? '' }}"></td>
                    <td><input class="form-control" type="date" data-field="tanggal_kedaluwarsa" value="{{ $baris->tanggal_kedaluwarsa ?? '' }}"></td>
                    <td><button class="btn btn-sm btn-outline-danger" type="button" data-hapus-transfer>Hapus</button></td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
