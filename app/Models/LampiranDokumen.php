<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LampiranDokumen extends Model
{
    protected $table = 'lampiran_dokumen';

    protected $primaryKey = 'id_lampiran_dokumen';

    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'ukuran_berkas' => 'integer',
            'created_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }
}
