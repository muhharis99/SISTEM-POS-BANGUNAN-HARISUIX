<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Peran extends Model
{
    protected $table = 'peran';

    protected $primaryKey = 'id_peran';

    public $timestamps = false;

    protected $guarded = [];

    public function scopeAktif(Builder $query): Builder
    {
        return $query->where('status_aktif', 1)->whereNull('deleted_at');
    }

    public function hakAkses(): BelongsToMany
    {
        return $this->belongsToMany(
            HakAkses::class,
            'peran_hak_akses',
            'id_peran',
            'id_hak_akses',
            'id_peran',
            'id_hak_akses'
        )->wherePivotNull('deleted_at');
    }

    public function penugasanPengguna(): HasMany
    {
        return $this->hasMany(PenggunaPeran::class, 'id_peran', 'id_peran');
    }
}
