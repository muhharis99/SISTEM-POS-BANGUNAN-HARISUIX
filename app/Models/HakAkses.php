<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class HakAkses extends Model
{
    protected $table = 'hak_akses';

    protected $primaryKey = 'id_hak_akses';

    public $timestamps = false;

    protected $guarded = [];

    public function scopeAktif(Builder $query): Builder
    {
        return $query
            ->where('hak_akses.status_aktif', 1)
            ->whereNull('hak_akses.deleted_at');
    }

    public function peran(): BelongsToMany
    {
        return $this->belongsToMany(
            Peran::class,
            'peran_hak_akses',
            'id_hak_akses',
            'id_peran',
            'id_hak_akses',
            'id_peran'
        )->wherePivotNull('deleted_at');
    }
}
