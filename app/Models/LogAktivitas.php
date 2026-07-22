<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LogAktivitas extends Model
{
    protected $table = 'log_aktivitas';

    protected $primaryKey = 'id_log_aktivitas';

    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'tanggal_aktivitas' => 'datetime',
            'data_sebelum' => 'array',
            'data_sesudah' => 'array',
        ];
    }
}
