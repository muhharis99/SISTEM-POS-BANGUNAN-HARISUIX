<?php

use App\Http\Controllers\StatusKesehatanController;
use Illuminate\Support\Facades\Route;

Route::get('/kesiapan', [StatusKesehatanController::class, 'siap'])
    ->middleware('throttle:60,1')
    ->name('kesiapan-produksi');
