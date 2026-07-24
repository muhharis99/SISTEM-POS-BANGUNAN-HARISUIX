<?php

namespace App\Providers;

use App\Http\Controllers\PenjualanController;
use App\Http\Controllers\PenjualanOperasionalFinalController;
use App\Services\LayananKeuangan;
use App\Services\LayananKeuanganFinal;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(PenjualanController::class, PenjualanOperasionalFinalController::class);
        $this->app->bind(LayananKeuangan::class, LayananKeuanganFinal::class);
    }

    public function boot(): void
    {
        if (app()->routesAreCached()) {
            return;
        }

        Route::middleware('web')->group(base_path('routes/fase8.php'));
        Route::middleware('web')->group(base_path('routes/fase9.php'));
        Route::middleware('web')->group(base_path('routes/fase10.php'));
        Route::middleware('web')->group(base_path('routes/fase11.php'));
    }
}
