<?php

use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::view('/dashboard', 'dashboard.index')
    ->name('dashboard');
