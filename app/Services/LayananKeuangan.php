<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LayananKeuangan
{
    public function __construct(private readonly LayananPersediaan $persediaan) {}

    public function nomorBerikutnya(int $idCabang, string $jenis, string $awalan, ?string $tanggal = null): string
    {
        return $this->persediaan->nomorBerikutnya($idCabang, $jenis, $awalan, $tanggal);
    }

    public function kasBank(int $idCabang, int $idKasBank): object
    {
        $kasBank = DB::table('kas_bank')
            ->where('id_kas_bank', $idKasBank)
            ->where('id_cabang', $idCabang)
            ->where('status_aktif', 1)
            ->whereNull('deleted_at')
            ->first();

        if (! $kasBank) {
            throw ValidationException::withMessages([
                'id_kas_bank' => 'Kas atau bank tidak valid untuk cabang aktif.',
            ]);
        }

        return $kasBank;
    }

    public function akunRincian(int $idAkun): object
    {
        $akun = DB::table('akun_keuangan')
            ->where('id_akun_keuangan', $idAkun)
            ->where('akun_rincian', 1)
            ->where('status_aktif', 1)
            ->whereNull('deleted_at')
            ->first();

        if (! $akun) {
            throw ValidationException::withMessages([
                'id_akun_keuangan' => 'Akun tidak valid, bukan akun rincian, atau tidak aktif.',
            ]);
        }

        return $akun;
    }

    public function akunPemetaan(int $idCabang, string $kunci): object
    {
        $pemetaan = DB::table('pemetaan_akun as p')
            ->join('akun_keuangan as a', 'a.id_akun_keuangan', '=', 'p.id_akun_keuangan')
            ->where('p.kunci_pemetaan', $kunci)
            ->where(function ($query) use ($idCabang): void {
                $query->where('p.id_cabang', $idCabang)->orWhereNull('p.id_cabang');
            })
            ->where('a.akun_rincian', 1)
            ->where('a.status_aktif', 1)
            ->whereNull('a.deleted_at')
            ->orderByRaw('p.id_cabang IS NULL')
            ->select('a.*', 'p.id_cabang as id_cabang_pemetaan', 'p.kunci_pemetaan')
            ->first();

        if (! $pemetaan) {
            throw ValidationException::withMessages([
                'pemetaan_akun' => "Pemetaan akun {$kunci} belum tersedia untuk cabang aktif.",
            ]);
        }

        return $pemetaan;
    }

    public function akunKasBank(int $idCabang, object $kasBank): object
    {
        try {
            return $this->akunPemetaan($idCabang, 'KAS_BANK_'.$kasBank->id_kas_bank);
        } catch (ValidationException) {
            return $this->akunPemetaan($idCabang, $kasBank->jenis_kas_bank === 'BANK' ? 'BANK' : 'KAS');
        }
    }

    public function validasiDetailJurnal(array $detail): array
    {
        $hasil = [];
        $totalDebet = 0.0;
        $totalKredit = 0.0;

        foreach ($detail as $index => $baris) {
            $akun = $this->akunRincian((int) $baris['id_akun_keuangan']);
            $debet = round((float) ($baris['debet'] ?? 0), 2);
            $kredit = round((float) ($baris['kredit'] ?? 0), 2);

            if (($debet <= 0 && $kredit <= 0) || ($debet > 0 && $kredit > 0)) {
                throw ValidationException::withMessages([
                    "detail.{$index}.debet" => 'Setiap baris jurnal wajib mengisi tepat salah satu sisi debet atau kredit.',
                ]);
            }

            $totalDebet += $debet;
            $totalKredit += $kredit;
            $hasil[] = [
                'akun' => $akun,
                'debet' => $debet,
                'kredit' => $kredit,
                'keterangan' => $baris['keterangan'] ?? null,
            ];
        }

        $totalDebet = round($totalDebet, 2);
        $totalKredit = round($totalKredit, 2);

        if ($totalDebet <= 0 || abs($totalDebet - $totalKredit) > 0.009) {
            throw ValidationException::withMessages([
                'detail' => 'Jurnal tidak seimbang. Total debet harus sama dengan total kredit dan bernilai lebih dari nol.',
            ]);
        }

        return [$hasil, $totalDebet, $totalKredit];
    }

    public function simpanJurnal(
        int $idCabang,
        string $tanggal,
        string $keterangan,
        array $detail,
        int $idPengguna,
        ?string $sumber = null,
        ?int $idSumber = null,
        ?string $nomorSumber = null,
        bool $langsungPosting = false
    ): int {
        [$rincian] = $this->validasiDetailJurnal($detail);

        if ($sumber && $idSumber) {
            $sudahAda = DB::table('jurnal_umum')
                ->where('id_cabang', $idCabang)
                ->where('sumber_jurnal', $sumber)
                ->where('id_sumber', $idSumber)
                ->where('status_jurnal', '!=', 'DIBATALKAN')
                ->whereNull('deleted_at')
                ->exists();

            if ($sudahAda) {
                throw ValidationException::withMessages([
                    'sumber_jurnal' => 'Jurnal untuk dokumen sumber ini sudah pernah dibuat.',
                ]);
            }
        }

        $nomorJurnal = $this->nomorBerikutnya($idCabang, 'JURNAL_UMUM', 'JU', $tanggal);
        $idJurnal = (int) DB::table('jurnal_umum')->insertGetId([
            'id_cabang' => $idCabang,
            'nomor_jurnal' => $nomorJurnal,
            'tanggal_jurnal' => $tanggal,
            'sumber_jurnal' => $sumber,
            'id_sumber' => $idSumber,
            'nomor_sumber' => $nomorSumber,
            'keterangan' => $keterangan,
            'status_jurnal' => $langsungPosting ? 'DIPOSTING' : 'DRAF',
            'id_pengguna_pemosting' => $langsungPosting ? $idPengguna : null,
            'tanggal_diposting' => $langsungPosting ? now() : null,
            'created_at' => now(),
            'created_by' => $idPengguna,
        ]);

        foreach ($rincian as $baris) {
            DB::table('jurnal_umum_detail')->insert([
                'id_jurnal_umum' => $idJurnal,
                'id_akun_keuangan' => $baris['akun']->id_akun_keuangan,
                'debet' => $baris['debet'],
                'kredit' => $baris['kredit'],
                'keterangan' => $baris['keterangan'],
                'created_at' => now(),
                'created_by' => $idPengguna,
            ]);
        }

        return $idJurnal;
    }

    public function setujuiTransaksiKas(int $idCabang, int $idTransaksi, int $idPengguna): int
    {
        return DB::transaction(function () use ($idCabang, $idTransaksi, $idPengguna): int {
            $transaksi = DB::table('transaksi_kas')
                ->where('id_transaksi_kas', $idTransaksi)
                ->where('id_cabang', $idCabang)
                ->whereNull('deleted_at')
                ->lockForUpdate()
                ->first();

            if (! $transaksi) {
                abort(404);
            }
            if ($transaksi->status_transaksi !== 'DRAF') {
                throw ValidationException::withMessages([
                    'status_transaksi' => 'Hanya transaksi kas berstatus DRAF yang dapat disetujui.',
                ]);
            }

            $kasAsal = $this->kasBank($idCabang, (int) $transaksi->id_kas_bank);
            $akunAsal = $this->akunKasBank($idCabang, $kasAsal);
            $nilai = round((float) $transaksi->nilai_transaksi, 2);

            if ($nilai <= 0) {
                throw ValidationException::withMessages(['nilai_transaksi' => 'Nilai transaksi harus lebih dari nol.']);
            }

            if ($transaksi->jenis_transaksi === 'MASUK') {
                $akunLawan = $this->akunPemetaan($idCabang, 'PENDAPATAN_LAIN');
                $detail = [
                    ['id_akun_keuangan' => $akunAsal->id_akun_keuangan, 'debet' => $nilai, 'kredit' => 0, 'keterangan' => $kasAsal->nama_kas_bank],
                    ['id_akun_keuangan' => $akunLawan->id_akun_keuangan, 'debet' => 0, 'kredit' => $nilai, 'keterangan' => $transaksi->keterangan],
                ];
            } elseif ($transaksi->jenis_transaksi === 'KELUAR') {
                $kunci = $transaksi->id_kategori_biaya ? 'KATEGORI_BIAYA_'.$transaksi->id_kategori_biaya : 'BEBAN_OPERASIONAL';
                try {
                    $akunLawan = $this->akunPemetaan($idCabang, $kunci);
                } catch (ValidationException) {
                    $akunLawan = $this->akunPemetaan($idCabang, 'BEBAN_OPERASIONAL');
                }
                $detail = [
                    ['id_akun_keuangan' => $akunLawan->id_akun_keuangan, 'debet' => $nilai, 'kredit' => 0, 'keterangan' => $transaksi->keterangan],
                    ['id_akun_keuangan' => $akunAsal->id_akun_keuangan, 'debet' => 0, 'kredit' => $nilai, 'keterangan' => $kasAsal->nama_kas_bank],
                ];
            } else {
                if (! $transaksi->id_kas_bank_tujuan) {
                    throw ValidationException::withMessages(['id_kas_bank_tujuan' => 'Tujuan wajib dipilih untuk pemindahan kas/bank.']);
                }
                if ((int) $transaksi->id_kas_bank_tujuan === (int) $transaksi->id_kas_bank) {
                    throw ValidationException::withMessages(['id_kas_bank_tujuan' => 'Kas/bank tujuan harus berbeda dari sumber.']);
                }

                $kasTujuan = $this->kasBank($idCabang, (int) $transaksi->id_kas_bank_tujuan);
                $akunTujuan = $this->akunKasBank($idCabang, $kasTujuan);
                if ((int) $akunTujuan->id_akun_keuangan === (int) $akunAsal->id_akun_keuangan) {
                    throw ValidationException::withMessages([
                        'pemetaan_akun' => 'Kas/bank sumber dan tujuan harus dipetakan ke akun rincian yang berbeda.',
                    ]);
                }
                $detail = [
                    ['id_akun_keuangan' => $akunTujuan->id_akun_keuangan, 'debet' => $nilai, 'kredit' => 0, 'keterangan' => $kasTujuan->nama_kas_bank],
                    ['id_akun_keuangan' => $akunAsal->id_akun_keuangan, 'debet' => 0, 'kredit' => $nilai, 'keterangan' => $kasAsal->nama_kas_bank],
                ];
            }

            $idJurnal = $this->simpanJurnal(
                $idCabang,
                date('Y-m-d', strtotime($transaksi->tanggal_transaksi)),
                $transaksi->keterangan,
                $detail,
                $idPengguna,
                'TRANSAKSI_KAS',
                (int) $transaksi->id_transaksi_kas,
                $transaksi->nomor_transaksi,
                true
            );

            DB::table('transaksi_kas')->where('id_transaksi_kas', $idTransaksi)->update([
                'status_transaksi' => 'DISETUJUI',
                'id_pengguna_penyetuju' => $idPengguna,
                'tanggal_disetujui' => now(),
                'updated_at' => now(),
                'updated_by' => $idPengguna,
            ]);

            return $idJurnal;
        });
    }

    public function postingJurnal(int $idCabang, int $idJurnal, int $idPengguna): void
    {
        DB::transaction(function () use ($idCabang, $idJurnal, $idPengguna): void {
            $jurnal = DB::table('jurnal_umum')
                ->where('id_jurnal_umum', $idJurnal)
                ->where('id_cabang', $idCabang)
                ->whereNull('deleted_at')
                ->lockForUpdate()
                ->first();

            if (! $jurnal) {
                abort(404);
            }
            if ($jurnal->status_jurnal !== 'DRAF') {
                throw ValidationException::withMessages([
                    'status_jurnal' => 'Hanya jurnal berstatus DRAF yang dapat diposting.',
                ]);
            }

            $detail = DB::table('jurnal_umum_detail')
                ->where('id_jurnal_umum', $idJurnal)
                ->lockForUpdate()
                ->get();

            $this->validasiDetailJurnal($detail->map(fn (object $baris): array => [
                'id_akun_keuangan' => $baris->id_akun_keuangan,
                'debet' => $baris->debet,
                'kredit' => $baris->kredit,
                'keterangan' => $baris->keterangan,
            ])->all());

            DB::table('jurnal_umum')->where('id_jurnal_umum', $idJurnal)->update([
                'status_jurnal' => 'DIPOSTING',
                'id_pengguna_pemosting' => $idPengguna,
                'tanggal_diposting' => now(),
                'updated_at' => now(),
                'updated_by' => $idPengguna,
            ]);
        });
    }

    public function saldoKasBank(int $idCabang, ?string $tanggalSampai = null): Collection
    {
        $tanggalSampai ??= now()->toDateString();

        return DB::table('kas_bank as k')
            ->where('k.id_cabang', $idCabang)
            ->where('k.status_aktif', 1)
            ->whereNull('k.deleted_at')
            ->select('k.*')
            ->orderBy('k.nama_kas_bank')
            ->get()
            ->map(function (object $kasBank) use ($idCabang, $tanggalSampai): object {
                $saldoAwal = (! $kasBank->tanggal_saldo_awal || $kasBank->tanggal_saldo_awal <= $tanggalSampai)
                    ? (float) $kasBank->saldo_awal
                    : 0.0;

                $masuk = (float) DB::table('transaksi_kas')
                    ->where('id_cabang', $idCabang)
                    ->where('status_transaksi', 'DISETUJUI')
                    ->whereDate('tanggal_transaksi', '<=', $tanggalSampai)
                    ->whereNull('deleted_at')
                    ->where(function ($query) use ($kasBank): void {
                        $query->where(function ($sub) use ($kasBank): void {
                            $sub->where('jenis_transaksi', 'MASUK')->where('id_kas_bank', $kasBank->id_kas_bank);
                        })->orWhere(function ($sub) use ($kasBank): void {
                            $sub->where('jenis_transaksi', 'PINDAH')->where('id_kas_bank_tujuan', $kasBank->id_kas_bank);
                        });
                    })
                    ->sum('nilai_transaksi');

                $keluar = (float) DB::table('transaksi_kas')
                    ->where('id_cabang', $idCabang)
                    ->where('status_transaksi', 'DISETUJUI')
                    ->whereDate('tanggal_transaksi', '<=', $tanggalSampai)
                    ->whereNull('deleted_at')
                    ->whereIn('jenis_transaksi', ['KELUAR', 'PINDAH'])
                    ->where('id_kas_bank', $kasBank->id_kas_bank)
                    ->sum('nilai_transaksi');

                $kasBank->total_masuk = round($masuk, 2);
                $kasBank->total_keluar = round($keluar, 2);
                $kasBank->saldo_berjalan = round($saldoAwal + $masuk - $keluar, 2);

                return $kasBank;
            });
    }
}
