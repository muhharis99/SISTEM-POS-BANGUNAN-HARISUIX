<?php

namespace App\Http\Controllers;

use App\Services\AuditAktivitas;
use App\Services\LayananKeuangan;
use App\Services\LayananPenjualan;
use App\Services\LayananPersediaan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PenjualanFinalController extends PenjualanDiperkuatController
{
    public function terimaRetur(Request $request, int $id, LayananPenjualan $layanan, AuditAktivitas $audit): RedirectResponse
    {
        $this->pastikanAksesFinal($request, 'RETUR_PENJUALAN_TERIMA');
        $idCabang = $this->idCabangFinal($request);
        $idTransaksiKas = null;
        $idJurnal = null;
        $idPengirimanPengganti = null;

        DB::transaction(function () use ($request, $id, $idCabang, $layanan, &$idTransaksiKas, &$idJurnal, &$idPengirimanPengganti): void {
            $retur = DB::table('retur_penjualan')
                ->where('id_retur_penjualan', $id)
                ->where('id_cabang', $idCabang)
                ->whereNull('deleted_at')
                ->lockForUpdate()
                ->first();

            if (! $retur) {
                abort(404);
            }

            if ($retur->status_retur !== 'DISETUJUI') {
                throw ValidationException::withMessages(['status_retur' => 'Hanya retur DISETUJUI yang dapat diterima.']);
            }

            $detail = DB::table('retur_penjualan_detail')
                ->where('id_retur_penjualan', $id)
                ->whereNull('deleted_at')
                ->lockForUpdate()
                ->get();

            if ($detail->isEmpty()) {
                throw ValidationException::withMessages(['detail' => 'Retur tidak memiliki detail aktif.']);
            }

            foreach ($detail as $item) {
                $layanan->catatReturStok($idCabang, $retur, $item, (int) $request->user()->id_pengguna);
            }

            $keuangan = app(LayananKeuangan::class);

            if ($retur->cara_pengembalian_dana === 'POTONG_PIUTANG') {
                $piutang = DB::table('piutang_pelanggan')
                    ->where('id_cabang', $idCabang)
                    ->where('id_penjualan', $retur->id_penjualan)
                    ->lockForUpdate()
                    ->first();

                if (! $piutang) {
                    throw ValidationException::withMessages(['cara_pengembalian_dana' => 'Piutang penjualan tidak ditemukan pada cabang aktif.']);
                }

                if ((float) $retur->total_retur > (float) $piutang->sisa_piutang + 0.009) {
                    throw ValidationException::withMessages(['total_retur' => 'Nilai retur melebihi sisa piutang.']);
                }

                DB::table('piutang_pelanggan')->where('id_piutang_pelanggan', $piutang->id_piutang_pelanggan)->update([
                    'nilai_retur' => round((float) $piutang->nilai_retur + (float) $retur->total_retur, 2),
                    'updated_at' => now(),
                    'updated_by' => $request->user()->id_pengguna,
                ]);

                $layanan->perbaruiPiutang((int) $piutang->id_piutang_pelanggan, (int) $request->user()->id_pengguna);

                $akunRetur = $keuangan->akunPemetaan($idCabang, 'RETUR_PENJUALAN');
                $akunPiutang = $keuangan->akunPemetaan($idCabang, 'PIUTANG_USAHA');
                $idJurnal = $keuangan->simpanJurnal(
                    $idCabang,
                    $retur->tanggal_retur,
                    'Retur penjualan '.$retur->nomor_retur.' dipotong dari piutang pelanggan',
                    [
                        ['id_akun_keuangan' => $akunRetur->id_akun_keuangan, 'debet' => $retur->total_retur, 'kredit' => 0, 'keterangan' => 'Retur penjualan'],
                        ['id_akun_keuangan' => $akunPiutang->id_akun_keuangan, 'debet' => 0, 'kredit' => $retur->total_retur, 'keterangan' => 'Pengurangan piutang pelanggan'],
                    ],
                    (int) $request->user()->id_pengguna,
                    'RETUR_PENJUALAN',
                    (int) $retur->id_retur_penjualan,
                    $retur->nomor_retur,
                    true
                );
            } elseif (in_array($retur->cara_pengembalian_dana, ['TUNAI', 'TRANSFER'], true)) {
                if (! $retur->id_kas_bank) {
                    throw ValidationException::withMessages(['id_kas_bank' => 'Kas atau bank pengembalian dana belum ditentukan.']);
                }

                $keuangan->kasBank($idCabang, (int) $retur->id_kas_bank);
                $transaksiKas = DB::table('transaksi_kas')
                    ->where('id_cabang', $idCabang)
                    ->where('sumber_transaksi', 'RETUR_PENJUALAN')
                    ->where('id_sumber', $retur->id_retur_penjualan)
                    ->where('status_transaksi', '!=', 'DIBATALKAN')
                    ->whereNull('deleted_at')
                    ->lockForUpdate()
                    ->first();

                if ($transaksiKas) {
                    $idTransaksiKas = (int) $transaksiKas->id_transaksi_kas;
                } else {
                    $idTransaksiKas = (int) DB::table('transaksi_kas')->insertGetId([
                        'id_cabang' => $idCabang,
                        'id_kas_bank' => $retur->id_kas_bank,
                        'id_kas_bank_tujuan' => null,
                        'id_kategori_biaya' => null,
                        'nomor_transaksi' => $keuangan->nomorBerikutnya($idCabang, 'TRANSAKSI_KAS', 'KB', $retur->tanggal_retur),
                        'tanggal_transaksi' => $retur->tanggal_retur,
                        'jenis_transaksi' => 'KELUAR',
                        'sumber_transaksi' => 'RETUR_PENJUALAN',
                        'id_sumber' => $retur->id_retur_penjualan,
                        'nomor_sumber' => $retur->nomor_retur,
                        'nilai_transaksi' => $retur->total_retur,
                        'keterangan' => 'Pengembalian dana '.$retur->cara_pengembalian_dana.' untuk retur '.$retur->nomor_retur,
                        'status_transaksi' => 'DRAF',
                        'created_at' => now(),
                        'created_by' => $request->user()->id_pengguna,
                    ]);
                }
            } elseif ($retur->cara_pengembalian_dana === 'PENGGANTI_BARANG') {
                $idPengirimanPengganti = $this->buatPengirimanPengganti($retur, $detail, $layanan, (int) $request->user()->id_pengguna);
            } else {
                throw ValidationException::withMessages(['cara_pengembalian_dana' => 'Metode pengembalian dana tidak dikenali.']);
            }

            DB::table('retur_penjualan')->where('id_retur_penjualan', $id)->update([
                'status_retur' => 'DITERIMA',
                'updated_at' => now(),
                'updated_by' => $request->user()->id_pengguna,
            ]);
        });

        $tambahan = $idTransaksiKas
            ? ' Draf transaksi kas #'.$idTransaksiKas.' telah dibuat.'
            : ($idJurnal ? ' Jurnal retur #'.$idJurnal.' telah diposting.' : ($idPengirimanPengganti ? ' Pengiriman pengganti #'.$idPengirimanPengganti.' telah dibuat.' : ''));
        $audit->catat($request, 'PENJUALAN', 'UBAH', 'retur_penjualan', $id, 'Menerima retur, memperbarui stok, dan menyelesaikan konsekuensi keuangan/logistik.'.$tambahan);

        return back()->with('berhasil', 'Retur berhasil diterima.'.$tambahan);
    }

    public function selesaikanRetur(Request $request, int $id, AuditAktivitas $audit): RedirectResponse
    {
        $this->pastikanAksesFinal($request, 'RETUR_PENJUALAN_KELOLA');
        $idCabang = $this->idCabangFinal($request);

        DB::transaction(function () use ($request, $id, $idCabang): void {
            $retur = DB::table('retur_penjualan')
                ->where('id_retur_penjualan', $id)
                ->where('id_cabang', $idCabang)
                ->whereNull('deleted_at')
                ->lockForUpdate()
                ->first();

            if (! $retur) {
                abort(404);
            }

            if ($retur->status_retur !== 'DITERIMA') {
                throw ValidationException::withMessages(['status_retur' => 'Hanya retur DITERIMA yang dapat diselesaikan.']);
            }

            if (in_array($retur->cara_pengembalian_dana, ['TUNAI', 'TRANSFER'], true)) {
                $selesai = DB::table('transaksi_kas')
                    ->where('id_cabang', $idCabang)
                    ->where('sumber_transaksi', 'RETUR_PENJUALAN')
                    ->where('id_sumber', $retur->id_retur_penjualan)
                    ->where('status_transaksi', 'DISETUJUI')
                    ->whereNull('deleted_at')
                    ->exists();

                if (! $selesai) {
                    throw ValidationException::withMessages(['status_retur' => 'Pengeluaran kas/bank untuk retur belum disetujui bagian keuangan.']);
                }
            }

            if ($retur->cara_pengembalian_dana === 'PENGGANTI_BARANG') {
                $selesai = DB::table('pengiriman')
                    ->where('id_cabang', $idCabang)
                    ->where('keterangan', $this->penandaPengganti((int) $retur->id_retur_penjualan))
                    ->where('status_pengiriman', 'DITERIMA')
                    ->whereNull('deleted_at')
                    ->exists();

                if (! $selesai) {
                    throw ValidationException::withMessages(['status_retur' => 'Barang pengganti belum diterima pelanggan.']);
                }
            }

            DB::table('retur_penjualan')->where('id_retur_penjualan', $id)->update([
                'status_retur' => 'SELESAI',
                'updated_at' => now(),
                'updated_by' => $request->user()->id_pengguna,
            ]);
        });

        $audit->catat($request, 'PENJUALAN', 'UBAH', 'retur_penjualan', $id, 'Menyelesaikan retur setelah kewajiban keuangan atau pengiriman pengganti diverifikasi.');

        return back()->with('berhasil', 'Retur penjualan berhasil diselesaikan.');
    }

    public function berangkatkanPengiriman(Request $request, int $id, AuditAktivitas $audit): RedirectResponse
    {
        $pengiriman = DB::table('pengiriman')->where('id_pengiriman', $id)->where('id_cabang', $this->idCabangFinal($request))->whereNull('deleted_at')->first();
        if (! $pengiriman || ! str_starts_with((string) $pengiriman->keterangan, '[PENGGANTI_RETUR:')) {
            return parent::berangkatkanPengiriman($request, $id, $audit);
        }

        $this->pastikanAksesFinal($request, 'PENGIRIMAN_KIRIM');
        $idCabang = $this->idCabangFinal($request);
        $persediaan = app(LayananPersediaan::class);
        $layanan = app(LayananPenjualan::class);

        DB::transaction(function () use ($request, $id, $idCabang, $persediaan, $layanan): void {
            $pengiriman = DB::table('pengiriman')->where('id_pengiriman', $id)->where('id_cabang', $idCabang)->whereNull('deleted_at')->lockForUpdate()->first();
            if (! $pengiriman) {
                abort(404);
            }
            if ($pengiriman->status_pengiriman !== 'DIJADWALKAN') {
                throw ValidationException::withMessages(['status_pengiriman' => 'Hanya pengiriman pengganti terjadwal yang dapat diberangkatkan.']);
            }

            $penjualan = DB::table('penjualan')->where('id_penjualan', $pengiriman->id_penjualan)->where('id_cabang', $idCabang)->lockForUpdate()->first();
            $detail = DB::table('pengiriman_detail')->where('id_pengiriman', $id)->whereNull('deleted_at')->lockForUpdate()->get();

            foreach ($detail as $item) {
                $detailJual = DB::table('penjualan_detail')->where('id_penjualan_detail', $item->id_penjualan_detail)->where('id_penjualan', $penjualan->id_penjualan)->lockForUpdate()->first();
                $barang = $layanan->barangSatuan((int) $item->id_barang_satuan);
                $hpp = (float) DB::table('saldo_stok')
                    ->where('id_gudang', $penjualan->id_gudang)
                    ->where('id_lokasi_gudang', $detailJual->id_lokasi_gudang)
                    ->where('id_barang', $barang->id_barang)
                    ->value('harga_pokok_rata_rata');

                $persediaan->catatMutasi(
                    $idCabang,
                    (int) $penjualan->id_gudang,
                    (int) $detailJual->id_lokasi_gudang,
                    (int) $barang->id_barang,
                    0,
                    (float) $item->jumlah_dasar_dikirim,
                    $hpp,
                    'PENGGANTI_RETUR_KELUAR',
                    'PENGIRIMAN',
                    $id,
                    $pengiriman->nomor_pengiriman,
                    'Barang pengganti retur pelanggan',
                    (int) $request->user()->id_pengguna
                );
            }

            DB::table('pengiriman')->where('id_pengiriman', $id)->update([
                'status_pengiriman' => 'DALAM_PERJALANAN',
                'tanggal_berangkat' => now(),
                'updated_at' => now(),
                'updated_by' => $request->user()->id_pengguna,
            ]);
        });

        $audit->catat($request, 'PENJUALAN', 'UBAH', 'pengiriman', $id, 'Memberangkatkan barang pengganti retur dan mengurangi stok secara atomik.');

        return back()->with('berhasil', 'Barang pengganti sedang dalam perjalanan dan stok telah diperbarui.');
    }

    public function gagalPengiriman(Request $request, int $id, AuditAktivitas $audit): RedirectResponse
    {
        $pengiriman = DB::table('pengiriman')->where('id_pengiriman', $id)->where('id_cabang', $this->idCabangFinal($request))->whereNull('deleted_at')->first();
        if (! $pengiriman || ! str_starts_with((string) $pengiriman->keterangan, '[PENGGANTI_RETUR:')) {
            return parent::gagalPengiriman($request, $id, $audit);
        }

        $this->pastikanAksesFinal($request, 'PENGIRIMAN_KELOLA');
        $idCabang = $this->idCabangFinal($request);
        $persediaan = app(LayananPersediaan::class);
        $layanan = app(LayananPenjualan::class);

        DB::transaction(function () use ($request, $id, $idCabang, $persediaan, $layanan): void {
            $pengiriman = DB::table('pengiriman')->where('id_pengiriman', $id)->where('id_cabang', $idCabang)->whereNull('deleted_at')->lockForUpdate()->first();
            if (! $pengiriman || ! in_array($pengiriman->status_pengiriman, ['DIJADWALKAN', 'DALAM_PERJALANAN'], true)) {
                throw ValidationException::withMessages(['status_pengiriman' => 'Status pengiriman tidak dapat ditandai gagal.']);
            }

            if ($pengiriman->status_pengiriman === 'DALAM_PERJALANAN') {
                $penjualan = DB::table('penjualan')->where('id_penjualan', $pengiriman->id_penjualan)->where('id_cabang', $idCabang)->lockForUpdate()->first();
                $detail = DB::table('pengiriman_detail')->where('id_pengiriman', $id)->whereNull('deleted_at')->lockForUpdate()->get();

                foreach ($detail as $item) {
                    $detailJual = DB::table('penjualan_detail')->where('id_penjualan_detail', $item->id_penjualan_detail)->where('id_penjualan', $penjualan->id_penjualan)->lockForUpdate()->first();
                    $barang = $layanan->barangSatuan((int) $item->id_barang_satuan);
                    $hpp = (float) DB::table('mutasi_stok')
                        ->where('jenis_mutasi', 'PENGGANTI_RETUR_KELUAR')
                        ->where('id_dokumen', $id)
                        ->where('id_barang', $barang->id_barang)
                        ->orderByDesc('id_mutasi_stok')
                        ->value('harga_pokok');

                    $persediaan->catatMutasi(
                        $idCabang,
                        (int) $penjualan->id_gudang,
                        (int) $detailJual->id_lokasi_gudang,
                        (int) $barang->id_barang,
                        (float) $item->jumlah_dasar_dikirim,
                        0,
                        $hpp,
                        'PENGGANTI_RETUR_BATAL',
                        'PENGIRIMAN',
                        $id,
                        $pengiriman->nomor_pengiriman,
                        'Pengembalian stok karena pengiriman pengganti gagal',
                        (int) $request->user()->id_pengguna
                    );
                }
            }

            DB::table('pengiriman')->where('id_pengiriman', $id)->update([
                'status_pengiriman' => 'GAGAL',
                'updated_at' => now(),
                'updated_by' => $request->user()->id_pengguna,
            ]);
        });

        $audit->catat($request, 'PENJUALAN', 'UBAH', 'pengiriman', $id, 'Menandai pengiriman pengganti gagal dan mengembalikan stok bila sudah diberangkatkan.');

        return back()->with('berhasil', 'Pengiriman pengganti ditandai gagal. Stok telah dipulihkan bila sebelumnya sudah keluar.');
    }

    private function buatPengirimanPengganti(object $retur, object $detail, LayananPenjualan $layanan, int $idPengguna): int
    {
        $penjualan = DB::table('penjualan')->where('id_penjualan', $retur->id_penjualan)->where('id_cabang', $retur->id_cabang)->lockForUpdate()->first();
        if (! $penjualan || $penjualan->status_pengiriman !== 'DIKIRIM') {
            throw ValidationException::withMessages(['cara_pengembalian_dana' => 'Penggantian barang hanya dapat dibuat untuk penjualan yang sudah selesai dikirim.']);
        }

        $penanda = $this->penandaPengganti((int) $retur->id_retur_penjualan);
        $sudahAda = DB::table('pengiriman')->where('id_cabang', $retur->id_cabang)->where('keterangan', $penanda)->whereNull('deleted_at')->first();
        if ($sudahAda) {
            return (int) $sudahAda->id_pengiriman;
        }

        $sumber = DB::table('pengiriman')
            ->where('id_penjualan', $penjualan->id_penjualan)
            ->where('status_pengiriman', 'DITERIMA')
            ->whereNull('deleted_at')
            ->orderByDesc('id_pengiriman')
            ->first();

        if (! $sumber) {
            throw ValidationException::withMessages(['cara_pengembalian_dana' => 'Alamat pengiriman sumber tidak ditemukan. Selesaikan pengiriman awal sebelum membuat barang pengganti.']);
        }

        $idPengiriman = (int) DB::table('pengiriman')->insertGetId([
            'id_cabang' => $retur->id_cabang,
            'id_pesanan_penjualan' => null,
            'id_penjualan' => $penjualan->id_penjualan,
            'id_armada' => null,
            'id_pegawai_pengemudi' => null,
            'nomor_pengiriman' => $layanan->nomorBerikutnya((int) $retur->id_cabang, 'PENGIRIMAN', 'DO', $retur->tanggal_retur),
            'tanggal_pengiriman' => $retur->tanggal_retur,
            'tanggal_rencana_tiba' => null,
            'status_pengiriman' => 'DRAF',
            'nama_penerima' => $sumber->nama_penerima,
            'telepon_penerima' => $sumber->telepon_penerima,
            'alamat_pengiriman' => $sumber->alamat_pengiriman,
            'garis_lintang' => $sumber->garis_lintang,
            'garis_bujur' => $sumber->garis_bujur,
            'biaya_pengiriman' => 0,
            'keterangan' => $penanda,
            'created_at' => now(),
            'created_by' => $idPengguna,
        ]);

        foreach ($detail as $item) {
            DB::table('pengiriman_detail')->insert([
                'id_pengiriman' => $idPengiriman,
                'id_pesanan_penjualan_detail' => null,
                'id_penjualan_detail' => $item->id_penjualan_detail,
                'id_barang_satuan' => $item->id_barang_satuan,
                'nilai_konversi' => $item->nilai_konversi,
                'jumlah_dikirim' => $item->jumlah,
                'jumlah_dasar_dikirim' => $item->jumlah_dasar,
                'jumlah_diterima' => 0,
                'jumlah_dasar_diterima' => 0,
                'keterangan' => 'Barang pengganti retur '.$retur->nomor_retur,
                'created_at' => now(),
                'created_by' => $idPengguna,
            ]);
        }

        return $idPengiriman;
    }

    private function penandaPengganti(int $idRetur): string
    {
        return '[PENGGANTI_RETUR:'.$idRetur.']';
    }

    private function idCabangFinal(Request $request): int
    {
        $idCabang = (int) $request->session()->get('id_cabang_aktif');
        if ($idCabang <= 0) {
            abort(403, 'Cabang aktif belum dipilih.');
        }

        return $idCabang;
    }

    private function pastikanAksesFinal(Request $request, string $kode): void
    {
        abort_unless($request->user()?->memilikiHakAkses($kode, $this->idCabangFinal($request)), 403);
    }
}
