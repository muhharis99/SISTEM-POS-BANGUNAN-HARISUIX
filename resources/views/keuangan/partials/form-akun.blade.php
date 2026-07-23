@php
    $nilai = fn (string $kolom, mixed $bawaan = null): mixed => old($kolom, $akunData?->{$kolom} ?? $bawaan);
@endphp

<div class="row g-3">
    <div class="col-md-4">
        <label class="form-label">Kode Akun</label>
        <input class="form-control" name="kode_akun" maxlength="30" value="{{ $nilai('kode_akun') }}" required>
    </div>
    <div class="col-md-8">
        <label class="form-label">Nama Akun</label>
        <input class="form-control" name="nama_akun" maxlength="150" value="{{ $nilai('nama_akun') }}" required>
    </div>
    <div class="col-md-6">
        <label class="form-label">Akun Induk</label>
        <select class="form-select" name="id_akun_induk">
            <option value="">Tanpa induk</option>
            @foreach($akunInduk as $induk)
                @if(!$akunData || $induk->id_akun_keuangan !== $akunData->id_akun_keuangan)
                    <option value="{{ $induk->id_akun_keuangan }}" @selected((string) $nilai('id_akun_induk') === (string) $induk->id_akun_keuangan)>{{ $induk->kode_akun }} — {{ $induk->nama_akun }}</option>
                @endif
            @endforeach
        </select>
    </div>
    <div class="col-md-3">
        <label class="form-label">Kelompok</label>
        <select class="form-select" name="kelompok_akun" required>
            @foreach(['ASET','KEWAJIBAN','MODAL','PENDAPATAN','BEBAN'] as $kelompok)
                <option value="{{ $kelompok }}" @selected($nilai('kelompok_akun', 'ASET') === $kelompok)>{{ $kelompok }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3">
        <label class="form-label">Saldo Normal</label>
        <select class="form-select" name="saldo_normal" required>
            @foreach(['DEBET','KREDIT'] as $normal)
                <option value="{{ $normal }}" @selected($nilai('saldo_normal', 'DEBET') === $normal)>{{ $normal }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-6">
        <label class="form-label">Tipe Akun</label>
        <select class="form-select" name="akun_rincian" required>
            <option value="0" @selected((string) $nilai('akun_rincian', 1) === '0')>Akun Induk</option>
            <option value="1" @selected((string) $nilai('akun_rincian', 1) === '1')>Akun Rincian / Transaksi</option>
        </select>
    </div>
    <div class="col-md-6">
        <label class="form-label">Status</label>
        <select class="form-select" name="status_aktif" required>
            <option value="1" @selected((string) $nilai('status_aktif', 1) === '1')>Aktif</option>
            <option value="0" @selected((string) $nilai('status_aktif', 1) === '0')>Nonaktif</option>
        </select>
    </div>
</div>
