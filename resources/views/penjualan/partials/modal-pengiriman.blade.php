<div class="modal fade" id="modalPengiriman" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <form class="modal-content" method="POST" action="{{ route('penjualan.pengiriman.simpan') }}">
            @csrf
            <div class="modal-header"><h5 class="modal-title">Pengiriman</h5><button class="btn-close" data-bs-dismiss="modal" type="button"></button></div>
            <div class="modal-body">
                <div class="row g-3 mb-3">
                    <div class="col-md-3"><label class="form-label">Penjualan</label><select class="form-select" name="id_penjualan"><option value="">Pilih</option>@foreach($penjualanPilihan as $p)<option value="{{ $p->id_penjualan }}">{{ $p->nomor_penjualan }}</option>@endforeach</select></div>
                    <div class="col-md-3"><label class="form-label">Pesanan</label><select class="form-select" name="id_pesanan_penjualan"><option value="">Pilih</option>@foreach($pesananPilihan as $p)<option value="{{ $p->id_pesanan_penjualan }}">{{ $p->nomor_pesanan }}</option>@endforeach</select></div>
                    <div class="col-md-2"><label class="form-label">Tanggal</label><input class="form-control" type="date" name="tanggal_pengiriman" value="{{ now()->toDateString() }}" required></div>
                    <div class="col-md-2"><label class="form-label">Armada</label><select class="form-select" name="id_armada"><option value="">Tanpa armada</option>@foreach($armadaPilihan as $a)<option value="{{ $a->id_armada }}">{{ $a->nomor_polisi }} — {{ $a->jenis_kendaraan }}</option>@endforeach</select></div>
                    <div class="col-md-2"><label class="form-label">Pengemudi</label><select class="form-select" name="id_pegawai_pengemudi"><option value="">Pilih</option>@foreach($pengemudiPilihan as $p)<option value="{{ $p->id_pegawai }}">{{ $p->nama_pegawai }}</option>@endforeach</select></div>
                    <div class="col-md-4"><label class="form-label">Nama Penerima</label><input class="form-control" name="nama_penerima"></div><div class="col-md-4"><label class="form-label">Telepon Penerima</label><input class="form-control" name="telepon_penerima"></div><div class="col-md-4"><label class="form-label">Rencana Tiba</label><input class="form-control" type="datetime-local" name="tanggal_rencana_tiba"></div>
                    <div class="col-12"><label class="form-label">Alamat Pengiriman</label><textarea class="form-control" name="alamat_pengiriman" rows="2" required></textarea></div>
                </div>
                <div class="table-responsive"><table class="table table-bordered align-middle"><thead><tr><th>Detail Penjualan</th><th>Detail Pesanan</th><th>Barang</th><th>Jumlah Dikirim</th></tr></thead><tbody><tr><td><select class="form-select" name="detail[0][id_penjualan_detail]"><option value="">Pilih</option>@foreach($penjualanDetailPilihan as $d)<option value="{{ $d->id_penjualan_detail }}">{{ $d->nomor_penjualan }} — {{ $d->nama_barang }}</option>@endforeach</select></td><td><select class="form-select" name="detail[0][id_pesanan_penjualan_detail]"><option value="">Pilih</option>@foreach($pesananDetailPilihan as $d)<option value="{{ $d->id_pesanan_penjualan_detail }}">{{ $d->nomor_pesanan }} — {{ $d->nama_barang }}</option>@endforeach</select></td><td><select class="form-select" name="detail[0][id_barang_satuan]" required><option value="">Pilih</option>@foreach($barangSatuanPilihan as $b)<option value="{{ $b->id_barang_satuan }}">{{ $b->nama_barang }} ({{ $b->nama_satuan }})</option>@endforeach</select></td><td><input class="form-control" name="detail[0][jumlah_dikirim]" type="number" min="0.001" step="0.001" required></td></tr></tbody></table></div>
            </div>
            <div class="modal-footer"><button class="btn btn-light" type="button" data-bs-dismiss="modal">Tutup</button><button class="btn btn-primary">Simpan Draf</button></div>
        </form>
    </div>
</div>
