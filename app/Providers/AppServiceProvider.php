<?php

namespace App\Providers;

use Illuminate\Support\Facades\Date;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Binding service bisnis akan ditambahkan per fase.
    }

    public function boot(): void
    {
        Date::use(Carbon\CarbonImmutable::class);
    }
}
