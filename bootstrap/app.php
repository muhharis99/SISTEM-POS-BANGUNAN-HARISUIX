<?php

use App\Console\Commands\SiapkanAksesFaseDua;
use App\Console\Commands\SiapkanMasterFaseTiga;
use App\Console\Commands\SiapkanPersediaanFaseEmpat;
use App\Console\Commands\VerifikasiSkemaDatabase;
use App\Http\Middleware\PastikanCabangAktif;
use App\Http\Middleware\PastikanHakAkses;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');
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
