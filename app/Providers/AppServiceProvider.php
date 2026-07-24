<?php

namespace App\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Binding service bisnis akan ditambahkan per fase.
    }

    public function boot(): void
    {
        if (app()->routesAreCached()) {
            return;
        }

        Route::middleware('web')->group(base_path('routes/fase8.php'));
        Route::middleware('web')->group(base_path('routes/fase9.php'));
        Route::middleware('web')->group(base_path('routes/fase10.php'));
    }
}
