<?php

namespace App\Http\Controllers;

use App\Http\Requests\Penjualan\SimpanPengirimanDiperkuatRequest;
use App\Http\Requests\Penjualan\SimpanReturPenjualanDiperkuatRequest;
use App\Services\AuditAktivitas;
use App\Services\LayananKeuangan;
use App\Services\LayananPenjualan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PenjualanDiperkuatController extends PenjualanController
{
    public function simpanPengiriman(Request $request, LayananPenjualan $layanan, AuditAktivitas $audit): RedirectResponse
    {
        $this->pastikanAksesDiperkuat($request, 'PENGIRIMAN_KELOLA');
        $idCabang = $this->idCabangDiperkuat($request);
        $data = app(SimpanPengirimanDiperkuatRequest::class)->validated();

        if (empty($data['id_penjualan']) && empty($data['id_pesanan_penjualan'])) {
            throw ValidationException::withMessages([
                'id_penjualan' => 'Pengiriman harus terkait penjualan atau pesanan.',
            ]);
        }

        $id = DB::transaction(function () use ($request, $data, $idCabang, $layanan): int {
            $penjualan = $this->penjualanUntukPengiriman($idCabang, $data['id_penjualan'] ?? null);
            $pesanan = $this->pesananUntukPengiriman($idCabang, $data['id_pesanan_penjualan'] ?? null);

            if ($penjualan && $pesanan && (int) $penjualan->id_pesanan_penjualan !== (int) $pesanan->id_pesanan_penjualan) {
                throw ValidationException::withMessages([
                    'id_pesanan_penjualan' => 'Pesanan yang dipilih bukan sumber dari transaksi penjualan tersebut.',
                ]);
            }

            $this->pastikanArmadaDanPengemudi($idCabang, $data);
            $rincian = $this->rincianPengiriman($data['detail'], $penjualan, $pesanan, $layanan);

            $idPengiriman = (int) DB::table('pengiriman')->insertGetId([
                'id_cabang' => $idCabang,
                'id_pesanan_penjualan' => $pesanan?->id_pesanan_penjualan,
                'id_penjualan' => $penjualan?->id_penjualan,
                'id_armada' => $data['id_armada'] ?? null,
                'id_pegawai_pengemudi' => $data['id_pegawai_pengemudi'] ?? null,
                'nomor_pengiriman' => $layanan->nomorBerikutnya($idCabang, 'PENGIRIMAN', 'DO', $data['tanggal_pengiriman']),
                'tanggal_pengiriman' => $data['tanggal_pengiriman'],
                'tanggal_rencana_tiba' => $data['tanggal_rencana_tiba'] ?? null,
                'status_pengiriman' => 'DRAF',
                'nama_penerima' => $data['nama_penerima'] ?? null,
                'telepon_penerima' => $data['telepon_penerima'] ?? null,
                'alamat_pengiriman' => $data['alamat_pengiriman'],
                'garis_lintang' => $data['garis_lintang'] ?? null,
                'garis_bujur' => $data['garis_bujur'] ?? null,
                'biaya_pengiriman' => $data['biaya_pengiriman'] ?? 0,
                'keterangan' => $data['keterangan'] ?? null,
                'created_at' => now(),
                'created_by' => $request->user()->id_pengguna,
            ]);

            foreach ($rincian as $item) {
                DB::table('pengiriman_detail')->insert([
                    'id_pengiriman' => $idPengiriman,
                    'id_pesanan_penjualan_detail' => $item['detailPesanan']?->id_pesanan_penjualan_detail,
                    'id_penjualan_detail' => $item['detailPenjualan']?->id_penjualan_detail,
                    'id_barang_satuan' => $item['barangSatuan']->id_barang_satuan,
                    'nilai_konversi' => $item['barangSatuan']->nilai_konversi,
                    'jumlah_dikirim' => $item['baris']['jumlah_dikirim'],
                    'jumlah_dasar_dikirim' => $item['jumlahDasar'],
                    'jumlah_diterima' => 0,
                    'jumlah_dasar_diterima' => 0,
                    'keterangan' => $item['baris']['keterangan'] ?? null,
                    'created_at' => now(),
                    'created_by' => $request->user()->id_pengguna,
                ]);
            }

            return $idPengiriman;
        });

        $audit->catat($request, 'PENJUALAN', 'TAMBAH', 'pengiriman', $id, 'Membuat draf pengiriman dengan validasi sumber dokumen dan cabang.');

        return back()->with('berhasil', 'Pengiriman berhasil dibuat sebagai draf. Relasi dokumen dan cabang telah diverifikasi.');
    }

    public function simpanRetur(Request $request, LayananPenjualan $layanan, AuditAktivitas $audit): RedirectResponse
    {
        $this->pastikanAksesDiperkuat($request, 'RETUR_PENJUALAN_KELOLA');
        $idCabang = $this->idCabangDiperkuat($request);
        $data = app(SimpanReturPenjualanDiperkuatRequest::class)->validated();

        $id = DB::transaction(function () use ($request, $data, $idCabang, $layanan): int {
            $penjualan = DB::table('penjualan')
                ->where('id_penjualan', $data['id_penjualan'])
                ->where('id_cabang', $idCabang)
                ->whereIn('status_penjualan', ['DISETUJUI', 'SEBAGIAN_DIBAYAR', 'LUNAS'])
                ->whereNull('deleted_at')
                ->lockForUpdate()
                ->first();

            if (! $penjualan) {
                throw ValidationException::withMessages([
                    'id_penjualan' => 'Penjualan tidak valid, berasal dari cabang lain, atau belum disetujui.',
                ]);
            }

            $gudangValid = DB::table('gudang')
                ->where('id_gudang', $data['id_gudang'])
                ->where('id_cabang', $idCabang)
                ->where('status_aktif', 1)
                ->whereNull('deleted_at')
                ->exists();

            if (! $gudangValid) {
                throw ValidationException::withMessages(['id_gudang' => 'Gudang retur tidak valid untuk cabang aktif.']);
            }

            if (in_array($data['cara_pengembalian_dana'], ['TUNAI', 'TRANSFER'], true)) {
                $this->pastikanKasBank($idCabang, (int) $data['id_kas_bank']);
            }

            $rincian = [];
            $totalRetur = 0.0;

            foreach ($data['detail'] as $index => $baris) {
                $detailPenjualan = DB::table('penjualan_detail')
                    ->where('id_penjualan_detail', $baris['id_penjualan_detail'])
                    ->where('id_penjualan', $penjualan->id_penjualan)
                    ->whereNull('deleted_at')
                    ->lockForUpdate()
                    ->first();

                if (! $detailPenjualan) {
                    throw ValidationException::withMessages([
                        "detail.{$index}.id_penjualan_detail" => 'Detail retur bukan bagian dari transaksi penjualan yang dipilih.',
                    ]);
                }

                if ((int) $detailPenjualan->id_barang_satuan !== (int) $baris['id_barang_satuan']) {
                    throw ValidationException::withMessages([
                        "detail.{$index}.id_barang_satuan" => 'Barang pada detail retur tidak sama dengan barang sumber penjualan.',
                    ]);
                }

                $barangSatuan = $layanan->barangSatuan((int) $detailPenjualan->id_barang_satuan);
                $jumlah = (float) $baris['jumlah'];
                $jumlahSumber = (float) $detailPenjualan->jumlah;

                if ($jumlahSumber <= 0) {
                    throw ValidationException::withMessages([
                        "detail.{$index}.jumlah" => 'Jumlah pada detail penjualan sumber tidak valid.',
                    ]);
                }

                $jumlahDasar = $layanan->jumlahDasar($barangSatuan, $jumlah, "detail.{$index}.jumlah");
                $layanan->pastikanGudangLokasi($idCabang, (int) $data['id_gudang'], (int) $baris['id_lokasi_gudang']);

                $sudahRetur = (float) DB::table('retur_penjualan_detail as d')
                    ->join('retur_penjualan as h', 'h.id_retur_penjualan', '=', 'd.id_retur_penjualan')
                    ->where('d.id_penjualan_detail', $detailPenjualan->id_penjualan_detail)
                    ->whereNotIn('h.status_retur', ['DIBATALKAN'])
                    ->whereNull('h.deleted_at')
                    ->whereNull('d.deleted_at')
                    ->sum('d.jumlah');

                if ($sudahRetur + $jumlah > $jumlahSumber + 0.0001) {
                    throw ValidationException::withMessages([
                        "detail.{$index}.jumlah" => 'Jumlah retur melebihi jumlah yang pernah dijual.',
                    ]);
                }

                $totalBarisSumber = (float) $detailPenjualan->total_baris;
                $totalBaris = round($totalBarisSumber * ($jumlah / $jumlahSumber), 2);
                $hargaSatuan = round($totalBaris / $jumlah, 2);
                $totalRetur += $totalBaris;

                $rincian[] = compact(
                    'baris',
                    'barangSatuan',
                    'detailPenjualan',
                    'jumlahDasar',
                    'hargaSatuan',
                    'totalBaris'
                );
            }

            $totalRetur = round($totalRetur, 2);

            if ($data['cara_pengembalian_dana'] === 'POTONG_PIUTANG') {
                $piutang = DB::table('piutang_pelanggan')
                    ->where('id_cabang', $idCabang)
                    ->where('id_penjualan', $penjualan->id_penjualan)
                    ->whereNull('deleted_at')
                    ->lockForUpdate()
                    ->first();

                if (! $piutang) {
                    throw ValidationException::withMessages([
                        'cara_pengembalian_dana' => 'Penjualan ini tidak memiliki piutang aktif yang dapat dipotong.',
                    ]);
                }

                if ($totalRetur > (float) $piutang->sisa_piutang + 0.009) {
                    throw ValidationException::withMessages([
                        'detail' => 'Nilai retur melebihi sisa piutang pelanggan.',
                    ]);
                }
            }

            $idRetur = (int) DB::table('retur_penjualan')->insertGetId([
                'id_cabang' => $idCabang,
                'id_pelanggan' => $penjualan->id_pelanggan,
                'id_penjualan' => $penjualan->id_penjualan,
                'id_gudang' => $data['id_gudang'],
                'nomor_retur' => $layanan->nomorBerikutnya($idCabang, 'RETUR_PENJUALAN', 'SR', $data['tanggal_retur']),
                'tanggal_retur' => $data['tanggal_retur'],
                'alasan_retur' => $data['alasan_retur'],
                'cara_pengembalian_dana' => $data['cara_pengembalian_dana'],
                'id_kas_bank' => $data['cara_pengembalian_dana'] === 'POTONG_PIUTANG' ? null : $data['id_kas_bank'],
                'status_retur' => 'DRAF',
                'total_retur' => $totalRetur,
                'keterangan' => $data['keterangan'] ?? null,
                'created_at' => now(),
                'created_by' => $request->user()->id_pengguna,
            ]);

            foreach ($rincian as $item) {
                DB::table('retur_penjualan_detail')->insert([
                    'id_retur_penjualan' => $idRetur,
                    'id_penjualan_detail' => $item['detailPenjualan']->id_penjualan_detail,
                    'id_barang_satuan' => $item['detailPenjualan']->id_barang_satuan,
                    'id_lokasi_gudang' => $item['baris']['id_lokasi_gudang'],
                    'nilai_konversi' => $item['barangSatuan']->nilai_konversi,
                    'jumlah' => $item['baris']['jumlah'],
                    'jumlah_dasar' => $item['jumlahDasar'],
                    'harga_satuan' => $item['hargaSatuan'],
                    'total_baris' => $item['totalBaris'],
                    'nomor_lot' => $item['baris']['nomor_lot'] ?? null,
                    'tanggal_kedaluwarsa' => $item['baris']['tanggal_kedaluwarsa'] ?? null,
                    'kondisi_barang' => $item['baris']['kondisi_barang'],
                    'bisa_dijual_kembali' => $item['baris']['bisa_dijual_kembali'],
                    'keterangan' => $item['baris']['keterangan'] ?? null,
                    'created_at' => now(),
                    'created_by' => $request->user()->id_pengguna,
                ]);
            }

            return $idRetur;
        });

        $audit->catat($request, 'PENJUALAN', 'TAMBAH', 'retur_penjualan', $id, 'Membuat draf retur dengan nilai yang dihitung dari detail penjualan sumber.');

        return back()->with('berhasil', 'Retur berhasil dibuat. Nilai retur dihitung dari transaksi penjualan sumber dan tidak menggunakan harga kiriman browser.');
    }

    public function terimaRetur(Request $request, int $id, LayananPenjualan $layanan, AuditAktivitas $audit): RedirectResponse
    {
        $this->pastikanAksesDiperkuat($request, 'RETUR_PENJUALAN_TERIMA');
        $idCabang = $this->idCabangDiperkuat($request);
        $idTransaksiKas = null;

        DB::transaction(function () use ($request, $id, $idCabang, $layanan, &$idTransaksiKas): void {
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
                throw ValidationException::withMessages([
                    'status_retur' => 'Hanya retur DISETUJUI yang dapat diterima.',
                ]);
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

            if ($retur->cara_pengembalian_dana === 'POTONG_PIUTANG') {
                $piutang = DB::table('piutang_pelanggan')
                    ->where('id_cabang', $idCabang)
                    ->where('id_penjualan', $retur->id_penjualan)
                    ->whereNull('deleted_at')
                    ->lockForUpdate()
                    ->first();

                if (! $piutang) {
                    throw ValidationException::withMessages([
                        'cara_pengembalian_dana' => 'Piutang penjualan tidak ditemukan pada cabang aktif.',
                    ]);
                }

                if ((float) $retur->total_retur > (float) $piutang->sisa_piutang + 0.009) {
                    throw ValidationException::withMessages([
                        'total_retur' => 'Nilai retur melebihi sisa piutang.',
                    ]);
                }

                DB::table('piutang_pelanggan')
                    ->where('id_piutang_pelanggan', $piutang->id_piutang_pelanggan)
                    ->update([
                        'nilai_retur' => round((float) $piutang->nilai_retur + (float) $retur->total_retur, 2),
                        'updated_at' => now(),
                        'updated_by' => $request->user()->id_pengguna,
                    ]);

                $layanan->perbaruiPiutang((int) $piutang->id_piutang_pelanggan, (int) $request->user()->id_pengguna);
            } elseif (in_array($retur->cara_pengembalian_dana, ['TUNAI', 'TRANSFER'], true)) {
                if (! $retur->id_kas_bank) {
                    throw ValidationException::withMessages([
                        'id_kas_bank' => 'Kas atau bank pengembalian dana belum ditentukan.',
                    ]);
                }

                $keuangan = app(LayananKeuangan::class);
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
            } else {
                throw ValidationException::withMessages([
                    'cara_pengembalian_dana' => 'Metode pengembalian dana belum memiliki alur operasional yang aman.',
                ]);
            }

            DB::table('retur_penjualan')->where('id_retur_penjualan', $id)->update([
                'status_retur' => 'DITERIMA',
                'updated_at' => now(),
                'updated_by' => $request->user()->id_pengguna,
            ]);
        });

        $keteranganAudit = $idTransaksiKas
            ? 'Menerima retur, menambah stok, dan membuat draf transaksi kas/bank #'.$idTransaksiKas.'.'
            : 'Menerima retur, menambah stok, dan memperbarui piutang.';
        $audit->catat($request, 'PENJUALAN', 'UBAH', 'retur_penjualan', $id, $keteranganAudit);

        $pesan = $idTransaksiKas
            ? 'Retur diterima. Draf pengeluaran kas/bank telah dibuat dan wajib disetujui bagian keuangan sebelum retur diselesaikan.'
            : 'Retur diterima, stok diperbarui, dan piutang telah dipotong.';

        return back()->with('berhasil', $pesan);
    }

    public function selesaikanRetur(Request $request, int $id, AuditAktivitas $audit): RedirectResponse
    {
        $this->pastikanAksesDiperkuat($request, 'RETUR_PENJUALAN_KELOLA');
        $idCabang = $this->idCabangDiperkuat($request);

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
                throw ValidationException::withMessages([
                    'status_retur' => 'Hanya retur DITERIMA yang dapat diselesaikan.',
                ]);
            }

            if (in_array($retur->cara_pengembalian_dana, ['TUNAI', 'TRANSFER'], true)) {
                $transaksiDisetujui = DB::table('transaksi_kas')
                    ->where('id_cabang', $idCabang)
                    ->where('sumber_transaksi', 'RETUR_PENJUALAN')
                    ->where('id_sumber', $retur->id_retur_penjualan)
                    ->where('status_transaksi', 'DISETUJUI')
                    ->whereNull('deleted_at')
                    ->exists();

                if (! $transaksiDisetujui) {
                    throw ValidationException::withMessages([
                        'status_retur' => 'Pengeluaran kas/bank untuk retur belum disetujui bagian keuangan.',
                    ]);
                }
            }

            DB::table('retur_penjualan')->where('id_retur_penjualan', $id)->update([
                'status_retur' => 'SELESAI',
                'updated_at' => now(),
                'updated_by' => $request->user()->id_pengguna,
            ]);
        });

        $audit->catat($request, 'PENJUALAN', 'UBAH', 'retur_penjualan', $id, 'Menyelesaikan retur setelah kewajiban pengembalian dana diverifikasi.');

        return back()->with('berhasil', 'Retur penjualan berhasil diselesaikan.');
    }

    private function penjualanUntukPengiriman(int $idCabang, mixed $idPenjualan): ?object
    {
        if (! $idPenjualan) {
            return null;
        }

        $penjualan = DB::table('penjualan')
            ->where('id_penjualan', $idPenjualan)
            ->where('id_cabang', $idCabang)
            ->whereIn('status_penjualan', ['DISETUJUI', 'SEBAGIAN_DIBAYAR', 'LUNAS'])
            ->whereNull('deleted_at')
            ->lockForUpdate()
            ->first();

        if (! $penjualan) {
            throw ValidationException::withMessages([
                'id_penjualan' => 'Penjualan tidak valid, berasal dari cabang lain, atau belum disetujui.',
            ]);
        }

        return $penjualan;
    }

    private function pesananUntukPengiriman(int $idCabang, mixed $idPesanan): ?object
    {
        if (! $idPesanan) {
            return null;
        }

        $pesanan = DB::table('pesanan_penjualan')
            ->where('id_pesanan_penjualan', $idPesanan)
            ->where('id_cabang', $idCabang)
            ->whereNotIn('status_pesanan', ['DRAF', 'DIBATALKAN', 'SELESAI'])
            ->whereNull('deleted_at')
            ->lockForUpdate()
            ->first();

        if (! $pesanan) {
            throw ValidationException::withMessages([
                'id_pesanan_penjualan' => 'Pesanan tidak valid, berasal dari cabang lain, belum disetujui, atau sudah selesai.',
            ]);
        }

        return $pesanan;
    }

    private function pastikanArmadaDanPengemudi(int $idCabang, array $data): void
    {
        if (! empty($data['id_armada'])) {
            $valid = DB::table('armada')
                ->where('id_armada', $data['id_armada'])
                ->where('id_cabang', $idCabang)
                ->where('status_aktif', 1)
                ->whereNull('deleted_at')
                ->exists();

            if (! $valid) {
                throw ValidationException::withMessages([
                    'id_armada' => 'Armada tidak valid untuk cabang aktif.',
                ]);
            }
        }

        if (! empty($data['id_pegawai_pengemudi'])) {
            $valid = DB::table('pegawai')
                ->where('id_pegawai', $data['id_pegawai_pengemudi'])
                ->where('id_cabang', $idCabang)
                ->where('status_aktif', 1)
                ->whereNull('deleted_at')
                ->exists();

            if (! $valid) {
                throw ValidationException::withMessages([
                    'id_pegawai_pengemudi' => 'Pengemudi tidak valid untuk cabang aktif.',
                ]);
            }
        }
    }

    private function rincianPengiriman(array $detail, ?object $penjualan, ?object $pesanan, LayananPenjualan $layanan): array
    {
        $rincian = [];

        foreach ($detail as $index => $baris) {
            $detailPenjualan = null;
            $detailPesanan = null;

            if ($penjualan) {
                if (empty($baris['id_penjualan_detail'])) {
                    throw ValidationException::withMessages([
                        "detail.{$index}.id_penjualan_detail" => 'Detail penjualan wajib dipilih karena header penjualan digunakan.',
                    ]);
                }

                $detailPenjualan = DB::table('penjualan_detail')
                    ->where('id_penjualan_detail', $baris['id_penjualan_detail'])
                    ->where('id_penjualan', $penjualan->id_penjualan)
                    ->whereNull('deleted_at')
                    ->lockForUpdate()
                    ->first();

                if (! $detailPenjualan) {
                    throw ValidationException::withMessages([
                        "detail.{$index}.id_penjualan_detail" => 'Detail penjualan tidak berasal dari transaksi penjualan yang dipilih.',
                    ]);
                }
            } elseif (! empty($baris['id_penjualan_detail'])) {
                throw ValidationException::withMessages([
                    "detail.{$index}.id_penjualan_detail" => 'Detail penjualan tidak boleh diisi tanpa header penjualan.',
                ]);
            }

            if ($pesanan) {
                if (empty($baris['id_pesanan_penjualan_detail'])) {
                    throw ValidationException::withMessages([
                        "detail.{$index}.id_pesanan_penjualan_detail" => 'Detail pesanan wajib dipilih karena header pesanan digunakan.',
                    ]);
                }

                $detailPesanan = DB::table('pesanan_penjualan_detail')
                    ->where('id_pesanan_penjualan_detail', $baris['id_pesanan_penjualan_detail'])
                    ->where('id_pesanan_penjualan', $pesanan->id_pesanan_penjualan)
                    ->whereNull('deleted_at')
                    ->lockForUpdate()
                    ->first();

                if (! $detailPesanan) {
                    throw ValidationException::withMessages([
                        "detail.{$index}.id_pesanan_penjualan_detail" => 'Detail pesanan tidak berasal dari pesanan yang dipilih.',
                    ]);
                }
            } elseif (! empty($baris['id_pesanan_penjualan_detail'])) {
                throw ValidationException::withMessages([
                    "detail.{$index}.id_pesanan_penjualan_detail" => 'Detail pesanan tidak boleh diisi tanpa header pesanan.',
                ]);
            }

            $idBarangSatuan = (int) ($detailPenjualan?->id_barang_satuan ?? $detailPesanan?->id_barang_satuan ?? 0);

            if ($idBarangSatuan <= 0 || (int) $baris['id_barang_satuan'] !== $idBarangSatuan) {
                throw ValidationException::withMessages([
                    "detail.{$index}.id_barang_satuan" => 'Barang tidak sesuai dengan detail dokumen sumber.',
                ]);
            }

            if ($detailPenjualan && $detailPesanan) {
                if ((int) $detailPenjualan->id_barang_satuan !== (int) $detailPesanan->id_barang_satuan) {
                    throw ValidationException::withMessages([
                        "detail.{$index}.id_barang_satuan" => 'Barang pada detail penjualan dan detail pesanan tidak sama.',
                    ]);
                }

                if (! $detailPenjualan->id_pesanan_penjualan_detail || (int) $detailPenjualan->id_pesanan_penjualan_detail !== (int) $detailPesanan->id_pesanan_penjualan_detail) {
                    throw ValidationException::withMessages([
                        "detail.{$index}.id_pesanan_penjualan_detail" => 'Detail pesanan bukan sumber dari detail penjualan yang dipilih.',
                    ]);
                }
            }

            $jumlah = (float) $baris['jumlah_dikirim'];

            if ($detailPenjualan) {
                $this->pastikanSisaPengiriman(
                    'id_penjualan_detail',
                    (int) $detailPenjualan->id_penjualan_detail,
                    (float) $detailPenjualan->jumlah,
                    $jumlah,
                    "detail.{$index}.jumlah_dikirim",
                    'penjualan'
                );
            }

            if ($detailPesanan) {
                $this->pastikanSisaPengiriman(
                    'id_pesanan_penjualan_detail',
                    (int) $detailPesanan->id_pesanan_penjualan_detail,
                    (float) $detailPesanan->jumlah,
                    $jumlah,
                    "detail.{$index}.jumlah_dikirim",
                    'pesanan'
                );
            }

            $barangSatuan = $layanan->barangSatuan($idBarangSatuan);
            $jumlahDasar = $layanan->jumlahDasar($barangSatuan, $jumlah, "detail.{$index}.jumlah_dikirim");
            $rincian[] = compact('baris', 'detailPenjualan', 'detailPesanan', 'barangSatuan', 'jumlahDasar');
        }

        return $rincian;
    }

    private function pastikanSisaPengiriman(
        string $kolomSumber,
        int $idSumber,
        float $jumlahSumber,
        float $jumlahBaru,
        string $atribut,
        string $namaSumber
    ): void {
        $sudahDikirim = (float) DB::table('pengiriman_detail as d')
            ->join('pengiriman as h', 'h.id_pengiriman', '=', 'd.id_pengiriman')
            ->where("d.{$kolomSumber}", $idSumber)
            ->whereNotIn('h.status_pengiriman', ['GAGAL', 'DIBATALKAN'])
            ->whereNull('h.deleted_at')
            ->whereNull('d.deleted_at')
            ->sum('d.jumlah_dikirim');

        if ($sudahDikirim + $jumlahBaru > $jumlahSumber + 0.0001) {
            throw ValidationException::withMessages([
                $atribut => "Jumlah pengiriman melebihi sisa jumlah {$namaSumber}.",
            ]);
        }
    }

    private function pastikanKasBank(int $idCabang, int $idKasBank): object
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

    private function idCabangDiperkuat(Request $request): int
    {
        $idCabang = (int) $request->session()->get('id_cabang_aktif');

        if ($idCabang <= 0) {
            abort(403, 'Cabang aktif belum dipilih.');
        }

        return $idCabang;
    }

    private function pastikanAksesDiperkuat(Request $request, string $kode): void
    {
        abort_unless(
            $request->user()?->memilikiHakAkses($kode, $this->idCabangDiperkuat($request)),
            403
        );
    }
}
