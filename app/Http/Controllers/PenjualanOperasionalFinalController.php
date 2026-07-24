<?php

namespace App\Http\Controllers;

use App\Services\AuditAktivitas;
use App\Services\LayananPenjualan;
use App\Services\LayananPersediaan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PenjualanOperasionalFinalController extends PenjualanFinalController
{
    private const JENIS_MUTASI_SKEMA = 'LAINNYA';

    private const DOKUMEN_PENGGANTI_KELUAR = 'PENGGANTI_RETUR_KELUAR';

    private const DOKUMEN_PENGGANTI_BATAL = 'PENGGANTI_RETUR_BATAL';

    public function berangkatkanPengiriman(Request $request, int $id, AuditAktivitas $audit): RedirectResponse
    {
        $idCabang = $this->idCabangOperasional($request);
        $pengirimanAwal = $this->pengirimanPengganti($idCabang, $id);

        if (! $pengirimanAwal) {
            return parent::berangkatkanPengiriman($request, $id, $audit);
        }

        $this->pastikanAksesOperasional($request, 'PENGIRIMAN_KIRIM');
        $persediaan = app(LayananPersediaan::class);
        $layanan = app(LayananPenjualan::class);

        DB::transaction(function () use ($request, $id, $idCabang, $persediaan, $layanan): void {
            $pengiriman = $this->pengirimanPengganti($idCabang, $id, true);

            if (! $pengiriman) {
                abort(404);
            }

            if ($pengiriman->status_pengiriman !== 'DIJADWALKAN') {
                throw ValidationException::withMessages([
                    'status_pengiriman' => 'Hanya pengiriman pengganti terjadwal yang dapat diberangkatkan.',
                ]);
            }

            $penjualan = DB::table('penjualan')
                ->where('id_penjualan', $pengiriman->id_penjualan)
                ->where('id_cabang', $idCabang)
                ->whereNull('deleted_at')
                ->lockForUpdate()
                ->first();

            if (! $penjualan) {
                throw ValidationException::withMessages([
                    'id_penjualan' => 'Penjualan sumber pengiriman pengganti tidak ditemukan pada cabang aktif.',
                ]);
            }

            $detail = DB::table('pengiriman_detail')
                ->where('id_pengiriman', $id)
                ->whereNull('deleted_at')
                ->lockForUpdate()
                ->get();

            if ($detail->isEmpty()) {
                throw ValidationException::withMessages([
                    'detail' => 'Pengiriman pengganti tidak memiliki detail aktif.',
                ]);
            }

            foreach ($detail as $index => $item) {
                $detailJual = DB::table('penjualan_detail')
                    ->where('id_penjualan_detail', $item->id_penjualan_detail)
                    ->where('id_penjualan', $penjualan->id_penjualan)
                    ->whereNull('deleted_at')
                    ->lockForUpdate()
                    ->first();

                if (! $detailJual) {
                    throw ValidationException::withMessages([
                        "detail.{$index}.id_penjualan_detail" => 'Detail penjualan sumber barang pengganti tidak ditemukan.',
                    ]);
                }

                $jumlahDasar = (float) $item->jumlah_dasar_dikirim;
                if ($jumlahDasar <= 0) {
                    throw ValidationException::withMessages([
                        "detail.{$index}.jumlah_dikirim" => 'Jumlah dasar barang pengganti harus lebih dari nol.',
                    ]);
                }

                $barang = $layanan->barangSatuan((int) $item->id_barang_satuan);
                $sudahDicatat = DB::table('mutasi_stok')
                    ->where('jenis_mutasi', self::JENIS_MUTASI_SKEMA)
                    ->where('jenis_dokumen', self::DOKUMEN_PENGGANTI_KELUAR)
                    ->where('id_dokumen', $id)
                    ->where('id_barang', $barang->id_barang)
                    ->where('id_lokasi_gudang', $detailJual->id_lokasi_gudang)
                    ->exists();

                if ($sudahDicatat) {
                    continue;
                }

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
                    $jumlahDasar,
                    $hpp,
                    self::JENIS_MUTASI_SKEMA,
                    self::DOKUMEN_PENGGANTI_KELUAR,
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

        $audit->catat(
            $request,
            'PENJUALAN',
            'UBAH',
            'pengiriman',
            $id,
            'Memberangkatkan barang pengganti retur dan mengurangi stok secara atomik menggunakan jenis mutasi yang sesuai skema paten.'
        );

        return back()->with('berhasil', 'Barang pengganti sedang dalam perjalanan dan stok telah diperbarui.');
    }

    public function gagalPengiriman(Request $request, int $id, AuditAktivitas $audit): RedirectResponse
    {
        $idCabang = $this->idCabangOperasional($request);
        $pengirimanAwal = $this->pengirimanPengganti($idCabang, $id);

        if (! $pengirimanAwal) {
            return parent::gagalPengiriman($request, $id, $audit);
        }

        $this->pastikanAksesOperasional($request, 'PENGIRIMAN_KELOLA');
        $persediaan = app(LayananPersediaan::class);
        $layanan = app(LayananPenjualan::class);

        DB::transaction(function () use ($request, $id, $idCabang, $persediaan, $layanan): void {
            $pengiriman = $this->pengirimanPengganti($idCabang, $id, true);

            if (! $pengiriman || ! in_array($pengiriman->status_pengiriman, ['DIJADWALKAN', 'DALAM_PERJALANAN'], true)) {
                throw ValidationException::withMessages([
                    'status_pengiriman' => 'Status pengiriman pengganti tidak dapat ditandai gagal.',
                ]);
            }

            if ($pengiriman->status_pengiriman === 'DALAM_PERJALANAN') {
                $penjualan = DB::table('penjualan')
                    ->where('id_penjualan', $pengiriman->id_penjualan)
                    ->where('id_cabang', $idCabang)
                    ->whereNull('deleted_at')
                    ->lockForUpdate()
                    ->first();

                if (! $penjualan) {
                    throw ValidationException::withMessages([
                        'id_penjualan' => 'Penjualan sumber pengiriman pengganti tidak ditemukan pada cabang aktif.',
                    ]);
                }

                $detail = DB::table('pengiriman_detail')
                    ->where('id_pengiriman', $id)
                    ->whereNull('deleted_at')
                    ->lockForUpdate()
                    ->get();

                if ($detail->isEmpty()) {
                    throw ValidationException::withMessages([
                        'detail' => 'Pengiriman pengganti tidak memiliki detail aktif.',
                    ]);
                }

                foreach ($detail as $index => $item) {
                    $detailJual = DB::table('penjualan_detail')
                        ->where('id_penjualan_detail', $item->id_penjualan_detail)
                        ->where('id_penjualan', $penjualan->id_penjualan)
                        ->whereNull('deleted_at')
                        ->lockForUpdate()
                        ->first();

                    if (! $detailJual) {
                        throw ValidationException::withMessages([
                            "detail.{$index}.id_penjualan_detail" => 'Detail penjualan sumber barang pengganti tidak ditemukan.',
                        ]);
                    }

                    $barang = $layanan->barangSatuan((int) $item->id_barang_satuan);
                    $sudahDipulihkan = DB::table('mutasi_stok')
                        ->where('jenis_mutasi', self::JENIS_MUTASI_SKEMA)
                        ->where('jenis_dokumen', self::DOKUMEN_PENGGANTI_BATAL)
                        ->where('id_dokumen', $id)
                        ->where('id_barang', $barang->id_barang)
                        ->where('id_lokasi_gudang', $detailJual->id_lokasi_gudang)
                        ->exists();

                    if ($sudahDipulihkan) {
                        continue;
                    }

                    $mutasiKeluar = DB::table('mutasi_stok')
                        ->where('jenis_mutasi', self::JENIS_MUTASI_SKEMA)
                        ->where('jenis_dokumen', self::DOKUMEN_PENGGANTI_KELUAR)
                        ->where('id_dokumen', $id)
                        ->where('id_barang', $barang->id_barang)
                        ->where('id_lokasi_gudang', $detailJual->id_lokasi_gudang)
                        ->orderByDesc('id_mutasi_stok')
                        ->first();

                    if (! $mutasiKeluar) {
                        throw ValidationException::withMessages([
                            'status_pengiriman' => 'Mutasi stok keluar barang pengganti tidak ditemukan sehingga stok belum dapat dipulihkan.',
                        ]);
                    }

                    $persediaan->catatMutasi(
                        $idCabang,
                        (int) $penjualan->id_gudang,
                        (int) $detailJual->id_lokasi_gudang,
                        (int) $barang->id_barang,
                        (float) $item->jumlah_dasar_dikirim,
                        0,
                        (float) $mutasiKeluar->harga_pokok,
                        self::JENIS_MUTASI_SKEMA,
                        self::DOKUMEN_PENGGANTI_BATAL,
                        $id,
                        $pengiriman->nomor_pengiriman,
                        'Pengembalian stok karena pengiriman barang pengganti gagal',
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

        $audit->catat(
            $request,
            'PENJUALAN',
            'UBAH',
            'pengiriman',
            $id,
            'Menandai pengiriman pengganti gagal dan memulihkan stok secara idempoten bila barang sudah diberangkatkan.'
        );

        return back()->with('berhasil', 'Pengiriman pengganti ditandai gagal. Stok telah dipulihkan bila sebelumnya sudah keluar.');
    }

    private function pengirimanPengganti(int $idCabang, int $idPengiriman, bool $kunci = false): ?object
    {
        $query = DB::table('pengiriman as p')
            ->join('retur_penjualan as r', function ($join): void {
                $join->on('r.id_cabang', '=', 'p.id_cabang')
                    ->whereRaw("p.keterangan = CONCAT('[PENGGANTI_RETUR:', r.id_retur_penjualan, ']')");
            })
            ->where('p.id_pengiriman', $idPengiriman)
            ->where('p.id_cabang', $idCabang)
            ->where('r.cara_pengembalian_dana', 'PENGGANTI_BARANG')
            ->whereNull('p.deleted_at')
            ->whereNull('r.deleted_at')
            ->select('p.*', 'r.id_retur_penjualan');

        if ($kunci) {
            $query->lockForUpdate();
        }

        return $query->first();
    }

    private function idCabangOperasional(Request $request): int
    {
        $idCabang = (int) $request->session()->get('id_cabang_aktif');

        if ($idCabang <= 0) {
            abort(403, 'Cabang aktif belum dipilih.');
        }

        return $idCabang;
    }

    private function pastikanAksesOperasional(Request $request, string $kode): void
    {
        abort_unless($request->user()?->memilikiHakAkses($kode, $this->idCabangOperasional($request)), 403);
    }
}
