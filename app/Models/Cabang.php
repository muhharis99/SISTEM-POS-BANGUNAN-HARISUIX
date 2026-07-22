<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cabang extends Model
{
    protected $table = 'cabang';

    protected $primaryKey = 'id_cabang';

    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'status_aktif' => 'boolean',
            'deleted_at' => 'datetime',
        ];
    }

    public function scopeAktif(Builder $query): Builder
    {
        return $query
            ->where('cabang.status_aktif', 1)
            ->whereNull('cabang.deleted_at');
    }

    public function penugasanPeran(): HasMany
    {
        return $this->hasMany(PenggunaPeran::class, 'id_cabang', 'id_cabang');
    }
}
