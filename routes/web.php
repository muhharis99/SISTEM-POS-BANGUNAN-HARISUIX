<?php

use App\Http\Controllers\AuditController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\BarangController;
use App\Http\Controllers\CabangAktifController;
use App\Http\Controllers\DaftarHargaController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GudangController;
use App\Http\Controllers\MasterDataController;
use App\Http\Controllers\PelangganController;
use App\Http\Controllers\PenggunaController;
use App\Http\Controllers\PeranController;
use App\Http\Controllers\ProfilController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::middleware('guest')->group(function (): void {
    Route::get('/masuk', [LoginController::class, 'tampilkan'])->name('masuk');
    Route::post('/masuk', [LoginController::class, 'masuk'])->middleware('throttle:10,1')->name('masuk.proses');
});

Route::middleware(['auth', 'cabang.aktif'])->group(function (): void {
    Route::post('/keluar', [LoginController::class, 'keluar'])->name('keluar');

    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->middleware('hak.akses:DASHBOARD_LIHAT')
        ->name('dashboard');

    Route::post('/cabang-aktif', [CabangAktifController::class, 'ubah'])
        ->middleware('hak.akses:CABANG_PILIH')
        ->name('cabang-aktif.ubah');

    Route::get('/profil', [ProfilController::class, 'tampilkan'])->name('profil');
    Route::put('/profil/kata-sandi', [ProfilController::class, 'ubahKataSandi'])
        ->middleware('hak.akses:PROFIL_UBAH_KATA_SANDI')
        ->name('profil.kata-sandi');

    Route::prefix('pengguna')->name('pengguna.')->group(function (): void {
        Route::get('/', [PenggunaController::class, 'index'])->middleware('hak.akses:PENGGUNA_LIHAT')->name('index');
        Route::post('/', [PenggunaController::class, 'simpan'])->middleware('hak.akses:PENGGUNA_BUAT')->name('simpan');
        Route::put('/{pengguna}', [PenggunaController::class, 'ubah'])->middleware('hak.akses:PENGGUNA_UBAH')->name('ubah');
        Route::patch('/{pengguna}/status', [PenggunaController::class, 'ubahStatus'])->middleware('hak.akses:PENGGUNA_UBAH_STATUS')->name('status');
        Route::put('/{pengguna}/kata-sandi', [PenggunaController::class, 'resetKataSandi'])->middleware('hak.akses:PENGGUNA_RESET_KATA_SANDI')->name('kata-sandi');
    });

    Route::prefix('peran')->name('peran.')->group(function (): void {
        Route::get('/', [PeranController::class, 'index'])->middleware('hak.akses:PERAN_LIHAT')->name('index');
        Route::post('/', [PeranController::class, 'simpan'])->middleware('hak.akses:PERAN_BUAT')->name('simpan');
        Route::put('/{peran}', [PeranController::class, 'ubah'])->middleware('hak.akses:PERAN_UBAH')->name('ubah');
        Route::patch('/{peran}/status', [PeranController::class, 'ubahStatus'])->middleware('hak.akses:PERAN_UBAH_STATUS')->name('status');
    });

    Route::prefix('master/{slug}')->name('master.')->group(function (): void {
        Route::get('/', [MasterDataController::class, 'index'])->name('index');
        Route::post('/', [MasterDataController::class, 'simpan'])->name('simpan');
        Route::put('/{id}', [MasterDataController::class, 'ubah'])->whereNumber('id')->name('ubah');
        Route::patch('/{id}/status', [MasterDataController::class, 'ubahStatus'])->whereNumber('id')->name('status');
    });

    Route::prefix('barang')->name('barang.')->group(function (): void {
        Route::get('/', [BarangController::class, 'index'])->middleware('hak.akses:MASTER_BARANG_LIHAT')->name('index');
        Route::post('/', [BarangController::class, 'simpan'])->middleware('hak.akses:MASTER_BARANG_KELOLA')->name('simpan');
        Route::put('/{id}', [BarangController::class, 'ubah'])->whereNumber('id')->middleware('hak.akses:MASTER_BARANG_KELOLA')->name('ubah');
        Route::patch('/{id}/status', [BarangController::class, 'ubahStatus'])->whereNumber('id')->middleware('hak.akses:MASTER_BARANG_KELOLA')->name('status');
    });

    Route::prefix('pelanggan')->name('pelanggan.')->group(function (): void {
        Route::get('/', [PelangganController::class, 'index'])->middleware('hak.akses:MASTER_PELANGGAN_LIHAT')->name('index');
        Route::post('/', [PelangganController::class, 'simpan'])->middleware('hak.akses:MASTER_PELANGGAN_KELOLA')->name('simpan');
        Route::put('/{id}', [PelangganController::class, 'ubah'])->whereNumber('id')->middleware('hak.akses:MASTER_PELANGGAN_KELOLA')->name('ubah');
        Route::patch('/{id}/status', [PelangganController::class, 'ubahStatus'])->whereNumber('id')->middleware('hak.akses:MASTER_PELANGGAN_KELOLA')->name('status');
    });

    Route::prefix('gudang')->name('gudang.')->group(function (): void {
        Route::get('/', [GudangController::class, 'index'])->middleware('hak.akses:MASTER_GUDANG_LIHAT')->name('index');
        Route::post('/', [GudangController::class, 'simpan'])->middleware('hak.akses:MASTER_GUDANG_KELOLA')->name('simpan');
        Route::put('/{id}', [GudangController::class, 'ubah'])->whereNumber('id')->middleware('hak.akses:MASTER_GUDANG_KELOLA')->name('ubah');
        Route::patch('/{id}/status', [GudangController::class, 'ubahStatus'])->whereNumber('id')->middleware('hak.akses:MASTER_GUDANG_KELOLA')->name('status');
        Route::post('/{idGudang}/lokasi', [GudangController::class, 'simpanLokasi'])->whereNumber('idGudang')->middleware('hak.akses:MASTER_GUDANG_KELOLA')->name('lokasi.simpan');
        Route::put('/{idGudang}/lokasi/{idLokasi}', [GudangController::class, 'ubahLokasi'])->whereNumber(['idGudang', 'idLokasi'])->middleware('hak.akses:MASTER_GUDANG_KELOLA')->name('lokasi.ubah');
        Route::patch('/{idGudang}/lokasi/{idLokasi}/status', [GudangController::class, 'ubahStatusLokasi'])->whereNumber(['idGudang', 'idLokasi'])->middleware('hak.akses:MASTER_GUDANG_KELOLA')->name('lokasi.status');
    });

    Route::prefix('daftar-harga')->name('daftar-harga.')->group(function (): void {
        Route::get('/', [DaftarHargaController::class, 'index'])->middleware('hak.akses:DAFTAR_HARGA_LIHAT')->name('index');
        Route::post('/', [DaftarHargaController::class, 'simpan'])->middleware('hak.akses:DAFTAR_HARGA_KELOLA')->name('simpan');
        Route::put('/{id}', [DaftarHargaController::class, 'ubah'])->whereNumber('id')->middleware('hak.akses:DAFTAR_HARGA_KELOLA')->name('ubah');
        Route::patch('/{id}/status', [DaftarHargaController::class, 'ubahStatus'])->whereNumber('id')->middleware('hak.akses:DAFTAR_HARGA_KELOLA')->name('status');
    });

    Route::get('/audit', [AuditController::class, 'index'])
        ->middleware('hak.akses:AUDIT_LIHAT')
        ->name('audit.index');
});
