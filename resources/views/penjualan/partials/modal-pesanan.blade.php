<div class="modal fade" id="modalPesanan" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <form class="modal-content" method="POST" action="{{ route('penjualan.pesanan.simpan') }}">
            @csrf
            <div class="modal-header"><h5 class="modal-title">Pesanan Penjualan</h5><button class="btn-close" data-bs-dismiss="modal" type="button"></button></div>
            <div class="modal-body">
                <div class="row g-3 mb-3">
                    <div class="col-md-4"><label class="form-label">Pelanggan</label><select class="form-select" name="id_pelanggan" required><option value="">Pilih</option>@foreach($pelangganPilihan as $p)<option value="{{ $p->id_pelanggan }}">{{ $p->kode_pelanggan }} — {{ $p->nama_pelanggan }}</option>@endforeach</select></div>
                    <div class="col-md-2"><label class="form-label">Tanggal</label><input class="form-control" type="date" name="tanggal_pesanan" value="{{ now()->toDateString() }}" required></div>
                    <div class="col-md-2"><label class="form-label">Rencana Kirim</label><input class="form-control" type="date" name="tanggal_rencana_pengiriman"></div>
                    <div class="col-md-2"><label class="form-label">Sumber</label><select class="form-select" name="sumber_pesanan"><option>TOKO</option><option>TELEPON</option><option>WHATSAPP</option><option>SUREL</option><option>WEBSITE</option><option>TENAGA_PENJUAL</option><option>LAINNYA</option></select></div>
                    <div class="col-md-2"><label class="form-label">Pembayaran</label><select class="form-select" name="cara_pembayaran"><option>TUNAI</option><option>TEMPO</option></select></div>
                    <div class="col-md-2"><label class="form-label">Jatuh Tempo (hari)</label><input class="form-control" type="number" min="0" name="lama_jatuh_tempo" value="0"></div>
                    <div class="col-md-2"><label class="form-label">Biaya Kirim</label><input class="form-control" type="number" min="0" step="0.01" name="biaya_pengiriman" value="0"></div>
                    <div class="col-md-2"><label class="form-label">Biaya Lain</label><input class="form-control" type="number" min="0" step="0.01" name="biaya_lain" value="0"></div>
                    <div class="col-md-2"><label class="form-label">Uang Muka</label><input class="form-control" type="number" min="0" step="0.01" name="uang_muka" value="0"></div>
                    <div class="col-md-4"><label class="form-label">Daftar Harga</label><select class="form-select" name="id_daftar_harga"><option value="">Tanpa daftar harga</option>@foreach($daftarHargaPilihan as $d)<option value="{{ $d->id_daftar_harga }}">{{ $d->nama_daftar_harga }}</option>@endforeach</select></div>
                    <div class="col-md-6"><label class="form-label">Alamat Penagihan</label><textarea class="form-control" name="alamat_penagihan" rows="2"></textarea></div>
                    <div class="col-md-6"><label class="form-label">Alamat Pengiriman</label><textarea class="form-control" name="alamat_pengiriman" rows="2"></textarea></div>
                </div>
                <div class="table-responsive"><table class="table table-bordered align-middle"><thead><tr><th>Barang/Satuan</th><th>Jumlah</th><th>Harga</th><th>Diskon %</th><th>Pajak</th></tr></thead><tbody><tr><td><select class="form-select" name="detail[0][id_barang_satuan]" required><option value="">Pilih</option>@foreach($barangSatuanPilihan as $b)<option value="{{ $b->id_barang_satuan }}">{{ $b->kode_barang }} — {{ $b->nama_barang }} ({{ $b->nama_satuan }})</option>@endforeach</select></td><td><input class="form-control" name="detail[0][jumlah]" type="number" min="0.001" step="0.001" required></td><td><input class="form-control" name="detail[0][harga_satuan]" type="number" min="0" step="0.01" required></td><td><input class="form-control" name="detail[0][potongan_persen]" type="number" min="0" max="100" step="0.0001" value="0"></td><td><select class="form-select" name="detail[0][id_tarif_pajak]"><option value="">Tanpa pajak</option>@foreach($pajakPilihan as $p)<option value="{{ $p->id_tarif_pajak }}">{{ $p->nama_tarif_pajak }}</option>@endforeach</select></td></tr></tbody></table></div>
                <label class="form-label">Keterangan</label><textarea class="form-control" name="keterangan" rows="2"></textarea>
            </div>
            <div class="modal-footer"><button class="btn btn-light" type="button" data-bs-dismiss="modal">Tutup</button><button class="btn btn-primary">Simpan Draf</button></div>
        </form>
    </div>
</div>
