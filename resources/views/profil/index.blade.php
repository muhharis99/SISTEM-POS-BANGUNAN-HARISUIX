@extends('layouts.admin')

@section('judul', 'Profil Saya')

@section('breadcrumb')
    <div class="py-3">
        <h4 class="fs-18 fw-semibold mb-1">Profil Saya</h4>
        <p class="text-muted mb-0">Informasi akun dan perubahan kata sandi.</p>
    </div>
@endsection

@section('content')
    <div class="row g-3">
        <div class="col-lg-5">
            <div class="card">
                <div class="card-header"><h5 class="card-title mb-0">Informasi Akun</h5></div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-5">Nama tampilan</dt><dd class="col-sm-7">{{ $pengguna->nama_tampilan }}</dd>
                        <dt class="col-sm-5">Nama pengguna</dt><dd class="col-sm-7">{{ $pengguna->nama_pengguna }}</dd>
                        <dt class="col-sm-5">Surel</dt><dd class="col-sm-7">{{ $pengguna->surel ?: '-' }}</dd>
                        <dt class="col-sm-5">Telepon</dt><dd class="col-sm-7">{{ $pengguna->telepon ?: '-' }}</dd>
                        <dt class="col-sm-5">Terakhir masuk</dt><dd class="col-sm-7">{{ optional($pengguna->terakhir_masuk)->format('d-m-Y H:i:s') ?: '-' }}</dd>
                    </dl>
                </div>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="card">
                <div class="card-header"><h5 class="card-title mb-0">Ubah Kata Sandi</h5></div>
                <div class="card-body">
                    @if ($errors->any())
                        <div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
                    @endif
                    <form method="POST" action="{{ route('profil.kata-sandi') }}">
                        @csrf
                        @method('PUT')
                        <div class="mb-3"><label class="form-label" for="kata_sandi_saat_ini">Kata Sandi Saat Ini</label><input type="password" class="form-control" id="kata_sandi_saat_ini" name="kata_sandi_saat_ini" required></div>
                        <div class="mb-3"><label class="form-label" for="kata_sandi_baru">Kata Sandi Baru</label><input type="password" class="form-control" id="kata_sandi_baru" name="kata_sandi_baru" required><div class="form-text">Minimal 8 karakter, huruf besar, huruf kecil, dan angka.</div></div>
                        <div class="mb-3"><label class="form-label" for="kata_sandi_baru_confirmation">Ulangi Kata Sandi Baru</label><input type="password" class="form-control" id="kata_sandi_baru_confirmation" name="kata_sandi_baru_confirmation" required></div>
                        <button type="submit" class="btn btn-primary">Simpan Kata Sandi</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
