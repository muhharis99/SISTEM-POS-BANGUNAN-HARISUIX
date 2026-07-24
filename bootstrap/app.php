<?php

use App\Console\Commands\SiapkanAksesFaseDua;
use App\Console\Commands\SiapkanMasterFaseTiga;
use App\Console\Commands\SiapkanPersediaanFaseEmpat;
use App\Console\Commands\VerifikasiSkemaDatabase;
use App\Http\Controllers\InputPenjualanFinalController;
use App\Http\Controllers\PenjualanController;
use App\Http\Controllers\PenjualanFinalController;
use App\Http\Middleware\PastikanCabangAktif;
use App\Http\Middleware\PastikanHakAkses;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function (): void {
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
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $proxyTepercaya = array_values(array_filter(array_map(
            'trim',
            explode(',', (string) env('TRUSTED_PROXIES', ''))
        )));

        if ($proxyTepercaya !== []) {
            $middleware->trustProxies(at: $proxyTepercaya);
        }

        $middleware->redirectGuestsTo('/masuk');
        $middleware->alias([
            'cabang.aktif' => PastikanCabangAktif::class,
            'hak.akses' => PastikanHakAkses::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Konfigurasi exception khusus akan ditambahkan pada fase terkait.
    })
    ->withCommands([
        VerifikasiSkemaDatabase::class,
        SiapkanAksesFaseDua::class,
        SiapkanMasterFaseTiga::class,
        SiapkanPersediaanFaseEmpat::class,
    ])
    ->create();
