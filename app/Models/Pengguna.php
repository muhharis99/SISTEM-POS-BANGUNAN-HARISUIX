<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Pengguna extends Authenticatable
{
    use Notifiable;

    protected $table = 'pengguna';

    protected $primaryKey = 'id_pengguna';

    public $timestamps = false;

    protected $fillable = [
        'id_pegawai',
        'nama_pengguna',
        'kata_sandi',
        'nama_tampilan',
        'surel',
        'telepon',
        'status_aktif',
        'terakhir_masuk',
        'percobaan_masuk',
        'dikunci_sampai',
        'created_by',
        'updated_at',
        'updated_by',
        'deleted_at',
        'deleted_by',
    ];

    protected $hidden = [
        'kata_sandi',
    ];

    protected function casts(): array
    {
        return [
            'status_aktif' => 'boolean',
            'terakhir_masuk' => 'datetime',
            'dikunci_sampai' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    public function getAuthPasswordName(): string
    {
        return 'kata_sandi';
    }

    public function getAuthPassword(): string
    {
        return (string) $this->kata_sandi;
    }

    public function pegawai(): BelongsTo
    {
        return $this->belongsTo(Pegawai::class, 'id_pegawai', 'id_pegawai');
    }

    public function penugasanPeran(): HasMany
    {
        return $this->hasMany(PenggunaPeran::class, 'id_pengguna', 'id_pengguna')
            ->whereNull('deleted_at');
    }

    public function peran(): BelongsToMany
    {
        return $this->belongsToMany(
            Peran::class,
            'pengguna_peran',
            'id_pengguna',
            'id_peran',
            'id_pengguna',
            'id_peran'
        )->wherePivotNull('deleted_at');
    }

    public function cabang(): BelongsToMany
    {
        return $this->belongsToMany(
            Cabang::class,
            'pengguna_peran',
            'id_pengguna',
            'id_cabang',
            'id_pengguna',
            'id_cabang'
        )->wherePivotNull('deleted_at')->distinct();
    }

    public function scopeAktif(Builder $query): Builder
    {
        return $query
            ->where('status_aktif', 1)
            ->whereNull('deleted_at');
    }

    public function memilikiPeran(string $kodePeran, ?int $idCabang = null): bool
    {
        return $this->penugasanPeran()
            ->whereHas('peran', fn (Builder $query) => $query
                ->where('kode_peran', $kodePeran)
                ->where('status_aktif', 1)
                ->whereNull('deleted_at'))
            ->when($idCabang !== null, fn (Builder $query) => $query
                ->where(function (Builder $cabang) use ($idCabang): void {
                    $cabang->whereNull('id_cabang')->orWhere('id_cabang', $idCabang);
                }))
            ->exists();
    }

    public function memilikiHakAkses(string $kodeHakAkses, ?int $idCabang = null): bool
    {
        if ($this->memilikiPeran('ADMINISTRATOR', $idCabang)) {
            return true;
        }

        return $this->penugasanPeran()
            ->when($idCabang !== null, fn (Builder $query) => $query
                ->where(function (Builder $cabang) use ($idCabang): void {
                    $cabang->whereNull('id_cabang')->orWhere('id_cabang', $idCabang);
                }))
            ->whereHas('peran', fn (Builder $query) => $query
                ->where('status_aktif', 1)
                ->whereNull('deleted_at')
                ->whereHas('hakAkses', fn (Builder $hakAkses) => $hakAkses
                    ->where('kode_hak_akses', $kodeHakAkses)
                    ->where('status_aktif', 1)
                    ->whereNull('hak_akses.deleted_at')))
            ->exists();
    }
}
