@php
    $penugasanAwal = $item?->penugasanPeran?->values() ?? collect([null]);
@endphp
<div class="row g-3">
    <div class="col-md-6"><label class="form-label">Nama Pengguna</label><input class="form-control" name="nama_pengguna" value="{{ old('nama_pengguna', $item?->nama_pengguna) }}" required></div>
    <div class="col-md-6"><label class="form-label">Nama Tampilan</label><input class="form-control" name="nama_tampilan" value="{{ old('nama_tampilan', $item?->nama_tampilan) }}" required></div>
    <div class="col-md-6"><label class="form-label">Surel</label><input type="email" class="form-control" name="surel" value="{{ old('surel', $item?->surel) }}"></div>
    <div class="col-md-6"><label class="form-label">Telepon</label><input class="form-control" name="telepon" value="{{ old('telepon', $item?->telepon) }}"></div>
    @if (! $item)
        <div class="col-md-6"><label class="form-label">Kata Sandi</label><input type="password" class="form-control" name="kata_sandi" required></div>
        <div class="col-md-6"><label class="form-label">Ulangi Kata Sandi</label><input type="password" class="form-control" name="kata_sandi_confirmation" required></div>
    @endif
    <div class="col-12"><hr><div class="d-flex align-items-center justify-content-between mb-2"><div><h6 class="mb-0">Penugasan Role dan Cabang</h6><small class="text-muted">Cabang kosong berarti role berlaku untuk semua cabang.</small></div><button type="button" class="btn btn-sm btn-outline-primary" data-tambah-penugasan data-target="wadah-{{ $prefix }}">Tambah Baris</button></div>
        <div id="wadah-{{ $prefix }}" data-wadah-penugasan>
            @foreach ($penugasanAwal as $penugasan)
                <div class="row g-2 mb-2" data-baris-penugasan>
                    <div class="col-md-5"><select class="form-select" data-field-peran required><option value="">Pilih role</option>@foreach ($peran as $role)<option value="{{ $role->id_peran }}" @selected($penugasan?->id_peran === $role->id_peran)>{{ $role->nama_peran }}</option>@endforeach</select></div>
                    <div class="col-md-5"><select class="form-select" data-field-cabang><option value="">Semua cabang</option>@foreach ($cabang as $unit)<option value="{{ $unit->id_cabang }}" @selected($penugasan?->id_cabang === $unit->id_cabang)>{{ $unit->nama_cabang }}</option>@endforeach</select></div>
                    <div class="col-md-2"><button type="button" class="btn btn-outline-danger w-100" data-hapus-penugasan>Hapus</button></div>
                </div>
            @endforeach
        </div>
    </div>
</div>
