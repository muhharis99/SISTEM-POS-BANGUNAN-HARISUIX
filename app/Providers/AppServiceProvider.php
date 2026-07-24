<?php

namespace App\Providers;

use App\Http\Controllers\InputPenjualanFinalController;
use App\Http\Controllers\PenjualanController;
use App\Http\Controllers\PenjualanFinalController;
use App\Services\LayananKeuangan;
use App\Services\LayananKeuanganFinal;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(PenjualanController::class, PenjualanFinalController::class);
        $this->app->bind(LayananKeuangan::class, LayananKeuanganFinal::class);
    }

    public function boot(): void
    {
        if (! app()->routesAreCached()) {
            Route::middleware('web')->group(base_path('routes/fase8.php'));
            Route::middleware('web')->group(base_path('routes/fase9.php'));
            Route::middleware('web')->group(base_path('routes/fase10.php'));
            Route::middleware('web')->group(base_path('routes/fase11.php'));
        }

        $this->app->booted(function (): void {
            foreach (Route::getRoutes() as $route) {
                $actionName = $route->getActionName();
                $awalan = PenjualanController::class.'@';

                if (! str_starts_with($actionName, $awalan)) {
                    continue;
                }

                $metode = substr($actionName, strlen($awalan));
                $controller = in_array($metode, ['simpanPenawaran', 'simpanPesanan', 'simpanPenjualan'], true)
                    ? InputPenjualanFinalController::class
                    : PenjualanFinalController::class;

                $action = $route->getAction();
                $action['uses'] = $controller.'@'.$metode;
                $action['controller'] = $controller.'@'.$metode;
                $route->setAction($action);
            }
        });
    }
}
