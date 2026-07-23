<?php

use App\Http\Controllers\AuditController;
use App\Http\Controllers\LampiranDokumenController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'cabang.aktif'])->group(function (): void {
    Route::prefix('lampiran')->name('lampiran.')->group(function (): void {
        Route::get('/', [LampiranDokumenController::class, 'index'])
            ->middleware('hak.akses:LAMPIRAN_LIHAT')
            ->name('index');
        Route::post('/', [LampiranDokumenController::class, 'simpan'])
            ->middleware('hak.akses:LAMPIRAN_UNGGAH')
            ->name('simpan');
        Route::get('/{id}/unduh', [LampiranDokumenController::class, 'unduh'])
            ->whereNumber('id')
            ->middleware('hak.akses:LAMPIRAN_UNDUH')
            ->name('unduh');
        Route::delete('/{id}', [LampiranDokumenController::class, 'hapus'])
            ->whereNumber('id')
            ->middleware('hak.akses:LAMPIRAN_HAPUS')
            ->name('hapus');
    });

    Route::get('/audit/{id}', [AuditController::class, 'detail'])
        ->whereNumber('id')
        ->middleware('hak.akses:AUDIT_LIHAT_DATA')
        ->name('audit.detail');
    Route::get('/audit-unduh/csv', [AuditController::class, 'unduh'])
        ->middleware('hak.akses:AUDIT_UNDUH')
        ->name('audit.unduh');
});
