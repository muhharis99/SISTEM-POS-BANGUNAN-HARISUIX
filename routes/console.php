<?php

use Illuminate\Support\Facades\Artisan;

Artisan::command('tentang-proyek', function (): void {
    $this->info('Sistem Informasi POS Toko Bangunan HARISUIX');
    $this->line('Fase 1: Fondasi proyek dan baseline database.');
})->purpose('Menampilkan identitas dan fase aktif proyek.');
