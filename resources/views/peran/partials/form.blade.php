@php
    $hakTerpilih = $item?->hakAkses?->pluck('id_hak_akses')->all() ?? [];
    $peranSistem = in_array($item?->kode_peran, ['ADMINISTRATOR','PEMILIK','KASIR','GUDANG','PEMBELIAN','PENJUALAN','KEUANGAN'], true);
@endphp
<div class="row g-3 mb-3">
    <div class="col-md-4"><label class="form-label">Kode Peran</label><input class="form-control" name="kode_peran" value="{{ old('kode_peran', $item?->kode_peran) }}" pattern="[A-Z0-9_]+" required @readonly($peranSistem)></div>
    <div class="col-md-4"><label class="form-label">Nama Peran</label><input class="form-control" name="nama_peran" value="{{ old('nama_peran', $item?->nama_peran) }}" required></div>
    <div class="col-md-4"><label class="form-label">Keterangan</label><input class="form-control" name="keterangan" value="{{ old('keterangan', $item?->keterangan) }}"></div>
</div>
<div class="alert alert-info">Menu hanya disembunyikan untuk kenyamanan. Akses URL tetap diperiksa oleh middleware pada server.</div>
<div class="row g-3">
    @foreach ($hakAksesPerModul as $modul => $daftarHak)
        <div class="col-lg-4 col-md-6">
            <div class="border rounded p-3 h-100">
                <h6 class="mb-3">{{ $modul }}</h6>
                @foreach ($daftarHak as $hak)
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" name="hak_akses[]" value="{{ $hak->id_hak_akses }}" id="hak-{{ $item?->id_peran ?? 'baru' }}-{{ $hak->id_hak_akses }}" @checked(in_array($hak->id_hak_akses, $hakTerpilih, true))>
                        <label class="form-check-label" for="hak-{{ $item?->id_peran ?? 'baru' }}-{{ $hak->id_hak_akses }}"><strong>{{ $hak->nama_hak_akses }}</strong><br><small class="text-muted">{{ $hak->kode_hak_akses }}</small></label>
                    </div>
                @endforeach
            </div>
        </div>
    @endforeach
</div>
