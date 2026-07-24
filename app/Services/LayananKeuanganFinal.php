<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LayananKeuanganFinal extends LayananKeuangan
{
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
                if ($transaksi->sumber_transaksi === 'RETUR_PENJUALAN') {
                    $akunLawan = $this->akunPemetaan($idCabang, 'RETUR_PENJUALAN');
                } else {
                    $kunci = $transaksi->id_kategori_biaya ? 'KATEGORI_BIAYA_'.$transaksi->id_kategori_biaya : 'BEBAN_OPERASIONAL';
                    try {
                        $akunLawan = $this->akunPemetaan($idCabang, $kunci);
                    } catch (ValidationException) {
                        $akunLawan = $this->akunPemetaan($idCabang, 'BEBAN_OPERASIONAL');
                    }
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
}
