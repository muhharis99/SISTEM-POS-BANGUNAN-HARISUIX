<div class="modal fade" id="modalPenawaran" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <form class="modal-content" method="POST" action="{{ route('penjualan.penawaran.simpan') }}">
            @csrf
            <div class="modal-header"><h5 class="modal-title">Penawaran Penjualan</h5><button class="btn-close" data-bs-dismiss="modal" type="button"></button></div>
            <div class="modal-body">
                <div class="row g-3 mb-3">
                    <div class="col-md-4"><label class="form-label">Pelanggan</label><select class="form-select" name="id_pelanggan"><option value="">Pelanggan umum</option>@foreach($pelangganPilihan as $p)<option value="{{ $p->id_pelanggan }}">{{ $p->kode_pelanggan }} — {{ $p->nama_pelanggan }}</option>@endforeach</select></div>
                    <div class="col-md-3"><label class="form-label">Tanggal</label><input class="form-control" type="date" name="tanggal_penawaran" value="{{ now()->toDateString() }}" required></div>
                    <div class="col-md-3"><label class="form-label">Berlaku Sampai</label><input class="form-control" type="date" name="berlaku_sampai"></div>
                    <div class="col-md-2"><label class="form-label">Biaya Kirim</label><input class="form-control" type="number" min="0" step="0.01" name="biaya_pengiriman" value="0"></div>
                    <div class="col-md-6"><label class="form-label">Alamat Penagihan</label><textarea class="form-control" name="alamat_penagihan" rows="2"></textarea></div>
                    <div class="col-md-6"><label class="form-label">Alamat Pengiriman</label><textarea class="form-control" name="alamat_pengiriman" rows="2"></textarea></div>
                </div>
                <div class="table-responsive"><table class="table table-bordered align-middle"><thead><tr><th>Barang/Satuan</th><th>Jumlah</th><th>Harga</th><th>Diskon %</th><th>Pajak</th><th>Keterangan</th></tr></thead><tbody>
                    <tr><td><select class="form-select" name="detail[0][id_barang_satuan]" required><option value="">Pilih</option>@foreach($barangSatuanPilihan as $b)<option value="{{ $b->id_barang_satuan }}">{{ $b->kode_barang }} — {{ $b->nama_barang }} ({{ $b->nama_satuan }})</option>@endforeach</select></td><td><input class="form-control" name="detail[0][jumlah]" type="number" min="0.001" step="0.001" required></td><td><input class="form-control" name="detail[0][harga_satuan]" type="number" min="0" step="0.01" required></td><td><input class="form-control" name="detail[0][potongan_persen]" type="number" min="0" max="100" step="0.0001" value="0"></td><td><select class="form-select" name="detail[0][id_tarif_pajak]"><option value="">Tanpa pajak</option>@foreach($pajakPilihan as $p)<option value="{{ $p->id_tarif_pajak }}">{{ $p->nama_tarif_pajak }} ({{ $p->persen_pajak }}%)</option>@endforeach</select></td><td><input class="form-control" name="detail[0][keterangan]"></td></tr>
                </tbody></table></div>
                <div class="row g-3"><div class="col-md-6"><label class="form-label">Syarat dan Ketentuan</label><textarea class="form-control" name="syarat_ketentuan" rows="2"></textarea></div><div class="col-md-6"><label class="form-label">Keterangan</label><textarea class="form-control" name="keterangan" rows="2"></textarea></div></div>
            </div>
            <div class="modal-footer"><button class="btn btn-light" type="button" data-bs-dismiss="modal">Tutup</button><button class="btn btn-primary">Simpan Draf</button></div>
        </form>
    </div>
</div>
