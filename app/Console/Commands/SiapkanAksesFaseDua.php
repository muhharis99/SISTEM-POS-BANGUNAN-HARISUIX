<?php

namespace App\Console\Commands;

use App\Models\HakAkses;
use App\Models\Pengguna;
use App\Models\PenggunaPeran;
use App\Models\Peran;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SiapkanAksesFaseDua extends Command
{
    protected $signature = 'fase2:siapkan
        {--nama-pengguna= : Nama pengguna administrator awal}
        {--kata-sandi= : Kata sandi administrator awal}
        {--nama-tampilan=Administrator : Nama tampilan administrator awal}';

    protected $description = 'Menyiapkan katalog hak akses Fase 2 dan administrator awal tanpa menambah tabel';

    public function handle(): int
    {
        $hakAkses = [
            ['DASHBOARD_LIHAT', 'Melihat dashboard', 'DASHBOARD'],
            ['CABANG_PILIH', 'Memilih cabang aktif', 'ORGANISASI'],
            ['PENGGUNA_LIHAT', 'Melihat pengguna', 'PENGGUNA'],
            ['PENGGUNA_BUAT', 'Menambah pengguna', 'PENGGUNA'],
            ['PENGGUNA_UBAH', 'Mengubah pengguna', 'PENGGUNA'],
            ['PENGGUNA_UBAH_STATUS', 'Mengaktifkan atau menonaktifkan pengguna', 'PENGGUNA'],
            ['PENGGUNA_RESET_KATA_SANDI', 'Mereset kata sandi pengguna', 'PENGGUNA'],
            ['PERAN_LIHAT', 'Melihat peran dan hak akses', 'PERAN'],
            ['PERAN_BUAT', 'Menambah peran', 'PERAN'],
            ['PERAN_UBAH', 'Mengubah peran dan hak akses', 'PERAN'],
            ['PERAN_UBAH_STATUS', 'Mengaktifkan atau menonaktifkan peran', 'PERAN'],
            ['AUDIT_LIHAT', 'Melihat log aktivitas', 'AUDIT'],
            ['PROFIL_UBAH_KATA_SANDI', 'Mengubah kata sandi sendiri', 'PROFIL'],
        ];

        DB::transaction(function () use ($hakAkses): void {
            foreach ($hakAkses as [$kode, $nama, $modul]) {
                HakAkses::query()->updateOrCreate(
                    ['kode_hak_akses' => $kode],
                    [
                        'nama_hak_akses' => $nama,
                        'nama_modul' => $modul,
                        'keterangan' => null,
                        'status_aktif' => 1,
                        'updated_at' => now(),
                        'deleted_at' => null,
                        'deleted_by' => null,
                    ]
                );
            }

            $semuaHak = HakAkses::query()->aktif()->pluck('id_hak_akses')->all();
            $hakDasar = HakAkses::query()->aktif()->whereIn('kode_hak_akses', [
                'DASHBOARD_LIHAT', 'CABANG_PILIH', 'PROFIL_UBAH_KATA_SANDI',
            ])->pluck('id_hak_akses')->all();
            $hakPemilik = HakAkses::query()->aktif()->whereIn('kode_hak_akses', [
                'DASHBOARD_LIHAT', 'CABANG_PILIH', 'PROFIL_UBAH_KATA_SANDI',
                'PENGGUNA_LIHAT', 'PERAN_LIHAT', 'AUDIT_LIHAT',
            ])->pluck('id_hak_akses')->all();

            foreach (Peran::query()->aktif()->get() as $peran) {
                $daftar = match ($peran->kode_peran) {
                    'ADMINISTRATOR' => $semuaHak,
                    'PEMILIK' => $hakPemilik,
                    default => $hakDasar,
                };

                foreach ($daftar as $idHakAkses) {
                    DB::table('peran_hak_akses')->updateOrInsert(
                        ['id_peran' => $peran->id_peran, 'id_hak_akses' => $idHakAkses],
                        ['created_at' => now(), 'created_by' => null, 'deleted_at' => null, 'deleted_by' => null]
                    );
                }
            }
        });

        $this->info('Katalog hak akses dan matriks role Fase 2 berhasil disiapkan.');

        if ($this->option('nama-pengguna')) {
            return $this->siapkanAdministrator();
        }

        $this->line('Administrator awal tidak dibuat karena opsi --nama-pengguna tidak diberikan.');

        return self::SUCCESS;
    }

    private function siapkanAdministrator(): int
    {
        $namaPengguna = trim((string) $this->option('nama-pengguna'));
        $kataSandi = (string) $this->option('kata-sandi');
        $namaTampilan = trim((string) $this->option('nama-tampilan'));

        if (! preg_match('/^[A-Za-z0-9._-]{3,100}$/', $namaPengguna)) {
            $this->error('Nama pengguna minimal 3 karakter dan hanya boleh berisi huruf, angka, titik, garis bawah, atau minus.');

            return self::FAILURE;
        }

        if (Str::length($kataSandi) < 8 || ! preg_match('/[A-Z]/', $kataSandi) || ! preg_match('/[a-z]/', $kataSandi) || ! preg_match('/[0-9]/', $kataSandi)) {
            $this->error('Kata sandi minimal 8 karakter serta mengandung huruf besar, huruf kecil, dan angka.');

            return self::FAILURE;
        }

        DB::transaction(function () use ($namaPengguna, $kataSandi, $namaTampilan): void {
            $pengguna = Pengguna::query()->updateOrCreate(
                ['nama_pengguna' => $namaPengguna],
                [
                    'kata_sandi' => Hash::make($kataSandi),
                    'nama_tampilan' => $namaTampilan,
                    'status_aktif' => 1,
                    'percobaan_masuk' => 0,
                    'dikunci_sampai' => null,
                    'updated_at' => now(),
                    'deleted_at' => null,
                    'deleted_by' => null,
                ]
            );

            $administrator = Peran::query()->where('kode_peran', 'ADMINISTRATOR')->firstOrFail();
            PenggunaPeran::query()->updateOrCreate(
                [
                    'id_pengguna' => $pengguna->id_pengguna,
                    'id_peran' => $administrator->id_peran,
                    'id_cabang' => null,
                ],
                ['created_at' => now(), 'created_by' => null, 'deleted_at' => null, 'deleted_by' => null]
            );
        });

        $this->info('Administrator awal berhasil disiapkan.');

        return self::SUCCESS;
    }
}
