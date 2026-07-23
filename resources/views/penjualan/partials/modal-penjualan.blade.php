<div class="modal fade" id="modalPenjualan" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <form class="modal-content" method="POST" action="{{ route('penjualan.transaksi.simpan') }}">
            @csrf
            <div class="modal-header"><h5 class="modal-title">Transaksi Penjualan</h5><button class="btn-close" data-bs-dismiss="modal" type="button"></button></div>
            <div class="modal-body">
                <div class="row g-3 mb-3">
                    <div class="col-md-3"><label class="form-label">Gudang</label><select class="form-select" name="id_gudang" required><option value="">Pilih</option>@foreach($gudangPilihan as $g)<option value="{{ $g->id_gudang }}">{{ $g->nama_gudang }}</option>@endforeach</select></div>
                    <div class="col-md-3"><label class="form-label">Pelanggan</label><select class="form-select" name="id_pelanggan"><option value="">Pelanggan tunai</option>@foreach($pelangganPilihan as $p)<option value="{{ $p->id_pelanggan }}">{{ $p->kode_pelanggan }} — {{ $p->nama_pelanggan }}</option>@endforeach</select></div>
                    <div class="col-md-3"><label class="form-label">Pesanan</label><select class="form-select" name="id_pesanan_penjualan"><option value="">Tanpa pesanan</option>@foreach($pesananPilihan as $p)<option value="{{ $p->id_pesanan_penjualan }}">{{ $p->nomor_pesanan }}</option>@endforeach</select></div>
                    <div class="col-md-3"><label class="form-label">Tanggal</label><input class="form-control" type="datetime-local" name="tanggal_penjualan" value="{{ now()->format('Y-m-d\TH:i') }}" required></div>
                    <div class="col-md-2"><label class="form-label">Jenis</label><select class="form-select" name="jenis_penjualan"><option>TUNAI</option><option>TEMPO</option></select></div>
                    <div class="col-md-2"><label class="form-label">Jatuh Tempo</label><input class="form-control" type="date" name="tanggal_jatuh_tempo"></div>
                    <div class="col-md-2"><label class="form-label">Kas/Bank</label><select class="form-select" name="id_kas_bank"><option value="">Pilih</option>@foreach($kasPilihan as $k)<option value="{{ $k->id_kas_bank }}">{{ $k->nama_kas_bank }}</option>@endforeach</select></div>
                    <div class="col-md-2"><label class="form-label">Metode</label><select class="form-select" name="id_metode_pembayaran"><option value="">Pilih</option>@foreach($metodePilihan as $m)<option value="{{ $m->id_metode_pembayaran }}">{{ $m->nama_metode_pembayaran }}</option>@endforeach</select></div>
                    <div class="col-md-2"><label class="form-label">Total Dibayar</label><input class="form-control" type="number" min="0" step="0.01" name="total_dibayar" value="0"></div>
                    <div class="col-md-2"><label class="form-label">Status Kirim</label><select class="form-select" name="status_pengiriman"><option>BELUM_DIKIRIM</option><option>DIAMBIL_SENDIRI</option></select></div>
                </div>
                <div class="table-responsive"><table class="table table-bordered align-middle"><thead><tr><th>Detail Pesanan</th><th>Barang</th><th>Lokasi</th><th>Jumlah</th><th>Harga</th><th>Diskon %</th><th>Pajak</th></tr></thead><tbody><tr>
                    <td><select class="form-select" name="detail[0][id_pesanan_penjualan_detail]"><option value="">Tanpa detail pesanan</option>@foreach($pesananDetailPilihan as $d)<option value="{{ $d->id_pesanan_penjualan_detail }}">{{ $d->nomor_pesanan }} — {{ $d->nama_barang }}</option>@endforeach</select></td>
                    <td><select class="form-select" name="detail[0][id_barang_satuan]" required><option value="">Pilih</option>@foreach($barangSatuanPilihan as $b)<option value="{{ $b->id_barang_satuan }}">{{ $b->kode_barang }} — {{ $b->nama_barang }} ({{ $b->nama_satuan }})</option>@endforeach</select></td>
                    <td><select class="form-select" name="detail[0][id_lokasi_gudang]" required><option value="">Pilih</option>@foreach($lokasiPilihan as $l)<option value="{{ $l->id_lokasi_gudang }}">{{ $l->nama_gudang }} — {{ $l->nama_lokasi }}</option>@endforeach</select></td>
                    <td><input class="form-control" name="detail[0][jumlah]" type="number" min="0.001" step="0.001" required></td><td><input class="form-control" name="detail[0][harga_satuan]" type="number" min="0" step="0.01" required></td><td><input class="form-control" name="detail[0][potongan_persen]" type="number" min="0" max="100" step="0.0001" value="0"></td><td><select class="form-select" name="detail[0][id_tarif_pajak]"><option value="">Tanpa pajak</option>@foreach($pajakPilihan as $p)<option value="{{ $p->id_tarif_pajak }}">{{ $p->nama_tarif_pajak }}</option>@endforeach</select></td>
                </tr></tbody></table></div>
                <div class="row g-3"><div class="col-md-3"><label class="form-label">Biaya Kirim</label><input class="form-control" type="number" min="0" step="0.01" name="biaya_pengiriman" value="0"></div><div class="col-md-3"><label class="form-label">Biaya Lain</label><input class="form-control" type="number" min="0" step="0.01" name="biaya_lain" value="0"></div><div class="col-md-3"><label class="form-label">Pembulatan</label><input class="form-control" type="number" step="0.01" name="pembulatan" value="0"></div><div class="col-md-3"><label class="form-label">Keterangan</label><input class="form-control" name="keterangan"></div></div>
            </div>
            <div class="modal-footer"><button class="btn btn-light" type="button" data-bs-dismiss="modal">Tutup</button><button class="btn btn-primary">Simpan Draf</button></div>
        </form>
    </div>
</div>
