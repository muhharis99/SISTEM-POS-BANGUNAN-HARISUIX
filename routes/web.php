<?php

use App\Http\Controllers\AuditController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\CabangAktifController;
use App\Http\Controllers\DashboardController;
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

    Route::get('/audit', [AuditController::class, 'index'])
        ->middleware('hak.akses:AUDIT_LIHAT')
        ->name('audit.index');
});
