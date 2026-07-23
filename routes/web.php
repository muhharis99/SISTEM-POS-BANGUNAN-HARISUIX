<?php

use App\Http\Controllers\AuditController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\BarangController;
use App\Http\Controllers\CabangAktifController;
use App\Http\Controllers\DaftarHargaController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GudangController;
use App\Http\Controllers\HutangPemasokController;
use App\Http\Controllers\MasterDataController;
use App\Http\Controllers\PelangganController;
use App\Http\Controllers\PembelianController;
use App\Http\Controllers\PenggunaController;
use App\Http\Controllers\PenjualanController;
use App\Http\Controllers\PenyesuaianStokController;
use App\Http\Controllers\PeranController;
use App\Http\Controllers\PersediaanController;
use App\Http\Controllers\PiutangPelangganController;
use App\Http\Controllers\ProfilController;
use App\Http\Controllers\StokAwalController;
use App\Http\Controllers\StokOpnameController;
use App\Http\Controllers\TransferStokController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::middleware('guest')->group(function (): void {
    Route::get('/masuk', [LoginController::class, 'tampilkan'])->name('masuk');
    Route::post('/masuk', [LoginController::class, 'masuk'])->middleware('throttle:10,1')->name('masuk.proses');
});

Route::middleware(['auth', 'cabang.aktif'])->group(function (): void {
    Route::post('/keluar', [LoginController::class, 'keluar'])->name('keluar');

    Route::get('/dashboard', [DashboardController::class, 'index'])->middleware('hak.akses:DASHBOARD_LIHAT')->name('dashboard');
    Route::post('/cabang-aktif', [CabangAktifController::class, 'ubah'])->middleware('hak.akses:CABANG_PILIH')->name('cabang-aktif.ubah');
    Route::get('/profil', [ProfilController::class, 'tampilkan'])->name('profil');
    Route::put('/profil/kata-sandi', [ProfilController::class, 'ubahKataSandi'])->middleware('hak.akses:PROFIL_UBAH_KATA_SANDI')->name('profil.kata-sandi');

    Route::prefix('pengguna')->name('pengguna.')->group(function (): void {
        Route::get('/', [PenggunaController::class, 'index'])->middleware('hak.akses:PENGGUNA_LIHAT')->name('index');
        Route::post('/', [PenggunaController::class, 'simpan'])->middleware('hak.akses:PENGGUNA_BUAT')->name('simpan');
        Route::put('/{pengguna}', [PenggunaController::class, 'ubah'])->middleware('hak.akses:PENGGUNA_UBAH')->name('ubah');
        Route::patch('/{pengguna}/status', [PenggunaController::class, 'ubahStatus'])->middleware('hak.akses:PENGGUNA_UBAH_STATUS')->name('status');
        Route::put('/{pengguna}/kata-sandi', [PenggunaController::class, 'resetKataSandi'])->middleware('hak.akses:PENGGUNA_RESET_KATA_SANDI')->name('kata-sandi');
    });

    Route::prefix('peran')->name('peran.')->group(function (): void {
        Route::get('/', [PeranController::class, 'index'])->middleware('hak.akses:PERAN_LIHAT')->name('index');
        Route::post('/', [PeranController::class, 'simpan'])->middleware('hak.akses:PERAN_BUAT')->name('simpan');
        Route::put('/{peran}', [PeranController::class, 'ubah'])->middleware('hak.akses:PERAN_UBAH')->name('ubah');
        Route::patch('/{peran}/status', [PeranController::class, 'ubahStatus'])->middleware('hak.akses:PERAN_UBAH_STATUS')->name('status');
    });

    Route::prefix('master/{slug}')->name('master.')->group(function (): void {
        Route::get('/', [MasterDataController::class, 'index'])->name('index');
        Route::post('/', [MasterDataController::class, 'simpan'])->name('simpan');
        Route::put('/{id}', [MasterDataController::class, 'ubah'])->whereNumber('id')->name('ubah');
        Route::patch('/{id}/status', [MasterDataController::class, 'ubahStatus'])->whereNumber('id')->name('status');
    });

    Route::prefix('barang')->name('barang.')->group(function (): void {
        Route::get('/', [BarangController::class, 'index'])->middleware('hak.akses:MASTER_BARANG_LIHAT')->name('index');
        Route::post('/', [BarangController::class, 'simpan'])->middleware('hak.akses:MASTER_BARANG_KELOLA')->name('simpan');
        Route::put('/{id}', [BarangController::class, 'ubah'])->whereNumber('id')->middleware('hak.akses:MASTER_BARANG_KELOLA')->name('ubah');
        Route::patch('/{id}/status', [BarangController::class, 'ubahStatus'])->whereNumber('id')->middleware('hak.akses:MASTER_BARANG_KELOLA')->name('status');
    });

    Route::prefix('pelanggan')->name('pelanggan.')->group(function (): void {
        Route::get('/', [PelangganController::class, 'index'])->middleware('hak.akses:MASTER_PELANGGAN_LIHAT')->name('index');
        Route::post('/', [PelangganController::class, 'simpan'])->middleware('hak.akses:MASTER_PELANGGAN_KELOLA')->name('simpan');
        Route::put('/{id}', [PelangganController::class, 'ubah'])->whereNumber('id')->middleware('hak.akses:MASTER_PELANGGAN_KELOLA')->name('ubah');
        Route::patch('/{id}/status', [PelangganController::class, 'ubahStatus'])->whereNumber('id')->middleware('hak.akses:MASTER_PELANGGAN_KELOLA')->name('status');
    });

    Route::prefix('gudang')->name('gudang.')->group(function (): void {
        Route::get('/', [GudangController::class, 'index'])->middleware('hak.akses:MASTER_GUDANG_LIHAT')->name('index');
        Route::post('/', [GudangController::class, 'simpan'])->middleware('hak.akses:MASTER_GUDANG_KELOLA')->name('simpan');
        Route::put('/{id}', [GudangController::class, 'ubah'])->whereNumber('id')->middleware('hak.akses:MASTER_GUDANG_KELOLA')->name('ubah');
        Route::patch('/{id}/status', [GudangController::class, 'ubahStatus'])->whereNumber('id')->middleware('hak.akses:MASTER_GUDANG_KELOLA')->name('status');
        Route::post('/{idGudang}/lokasi', [GudangController::class, 'simpanLokasi'])->whereNumber('idGudang')->middleware('hak.akses:MASTER_GUDANG_KELOLA')->name('lokasi.simpan');
        Route::put('/{idGudang}/lokasi/{idLokasi}', [GudangController::class, 'ubahLokasi'])->whereNumber(['idGudang', 'idLokasi'])->middleware('hak.akses:MASTER_GUDANG_KELOLA')->name('lokasi.ubah');
        Route::patch('/{idGudang}/lokasi/{idLokasi}/status', [GudangController::class, 'ubahStatusLokasi'])->whereNumber(['idGudang', 'idLokasi'])->middleware('hak.akses:MASTER_GUDANG_KELOLA')->name('lokasi.status');
    });

    Route::prefix('daftar-harga')->name('daftar-harga.')->group(function (): void {
        Route::get('/', [DaftarHargaController::class, 'index'])->middleware('hak.akses:DAFTAR_HARGA_LIHAT')->name('index');
        Route::post('/', [DaftarHargaController::class, 'simpan'])->middleware('hak.akses:DAFTAR_HARGA_KELOLA')->name('simpan');
        Route::put('/{id}', [DaftarHargaController::class, 'ubah'])->whereNumber('id')->middleware('hak.akses:DAFTAR_HARGA_KELOLA')->name('ubah');
        Route::patch('/{id}/status', [DaftarHargaController::class, 'ubahStatus'])->whereNumber('id')->middleware('hak.akses:DAFTAR_HARGA_KELOLA')->name('status');
    });

    Route::prefix('persediaan')->name('persediaan.')->group(function (): void {
        Route::get('/', [PersediaanController::class, 'index'])->middleware('hak.akses:PERSEDIAAN_LIHAT')->name('index');
        Route::get('/kartu/{idBarang}', [PersediaanController::class, 'kartu'])->whereNumber('idBarang')->middleware('hak.akses:LAPORAN_STOK_LIHAT')->name('kartu');
    });

    Route::prefix('stok-awal')->name('stok-awal.')->group(function (): void {
        Route::get('/', [StokAwalController::class, 'index'])->middleware('hak.akses:STOK_AWAL_KELOLA')->name('index');
        Route::post('/', [StokAwalController::class, 'simpan'])->middleware('hak.akses:STOK_AWAL_KELOLA')->name('simpan');
        Route::put('/{id}', [StokAwalController::class, 'ubah'])->whereNumber('id')->middleware('hak.akses:STOK_AWAL_KELOLA')->name('ubah');
        Route::patch('/{id}/setujui', [StokAwalController::class, 'setujui'])->whereNumber('id')->middleware('hak.akses:STOK_AWAL_SETUJUI')->name('setujui');
        Route::patch('/{id}/batalkan', [StokAwalController::class, 'batalkan'])->whereNumber('id')->middleware('hak.akses:STOK_AWAL_KELOLA')->name('batalkan');
    });

    Route::prefix('transfer-stok')->name('transfer-stok.')->group(function (): void {
        Route::get('/', [TransferStokController::class, 'index'])->middleware('hak.akses:TRANSFER_STOK_KELOLA')->name('index');
        Route::post('/', [TransferStokController::class, 'simpan'])->middleware('hak.akses:TRANSFER_STOK_KELOLA')->name('simpan');
        Route::put('/{id}', [TransferStokController::class, 'ubah'])->whereNumber('id')->middleware('hak.akses:TRANSFER_STOK_KELOLA')->name('ubah');
        Route::patch('/{id}/setujui', [TransferStokController::class, 'setujui'])->whereNumber('id')->middleware('hak.akses:TRANSFER_STOK_SETUJUI')->name('setujui');
        Route::patch('/{id}/kirim', [TransferStokController::class, 'kirim'])->whereNumber('id')->middleware('hak.akses:TRANSFER_STOK_KIRIM')->name('kirim');
        Route::patch('/{id}/terima', [TransferStokController::class, 'terima'])->whereNumber('id')->middleware('hak.akses:TRANSFER_STOK_TERIMA')->name('terima');
        Route::patch('/{id}/batalkan', [TransferStokController::class, 'batalkan'])->whereNumber('id')->middleware('hak.akses:TRANSFER_STOK_KELOLA')->name('batalkan');
    });

    Route::prefix('stok-opname')->name('stok-opname.')->group(function (): void {
        Route::get('/', [StokOpnameController::class, 'index'])->middleware('hak.akses:STOK_OPNAME_KELOLA')->name('index');
        Route::post('/', [StokOpnameController::class, 'simpan'])->middleware('hak.akses:STOK_OPNAME_KELOLA')->name('simpan');
        Route::put('/{id}', [StokOpnameController::class, 'ubah'])->whereNumber('id')->middleware('hak.akses:STOK_OPNAME_KELOLA')->name('ubah');
        Route::patch('/{id}/mulai', [StokOpnameController::class, 'mulai'])->whereNumber('id')->middleware('hak.akses:STOK_OPNAME_KELOLA')->name('mulai');
        Route::patch('/{id}/selesai', [StokOpnameController::class, 'selesai'])->whereNumber('id')->middleware('hak.akses:STOK_OPNAME_KELOLA')->name('selesai');
        Route::patch('/{id}/setujui', [StokOpnameController::class, 'setujui'])->whereNumber('id')->middleware('hak.akses:STOK_OPNAME_SETUJUI')->name('setujui');
        Route::patch('/{id}/batalkan', [StokOpnameController::class, 'batalkan'])->whereNumber('id')->middleware('hak.akses:STOK_OPNAME_KELOLA')->name('batalkan');
    });

    Route::prefix('penyesuaian-stok')->name('penyesuaian-stok.')->group(function (): void {
        Route::get('/', [PenyesuaianStokController::class, 'index'])->middleware('hak.akses:PENYESUAIAN_STOK_KELOLA')->name('index');
        Route::post('/', [PenyesuaianStokController::class, 'simpan'])->middleware('hak.akses:PENYESUAIAN_STOK_KELOLA')->name('simpan');
        Route::put('/{id}', [PenyesuaianStokController::class, 'ubah'])->whereNumber('id')->middleware('hak.akses:PENYESUAIAN_STOK_KELOLA')->name('ubah');
        Route::patch('/{id}/setujui', [PenyesuaianStokController::class, 'setujui'])->whereNumber('id')->middleware('hak.akses:PENYESUAIAN_STOK_SETUJUI')->name('setujui');
        Route::patch('/{id}/batalkan', [PenyesuaianStokController::class, 'batalkan'])->whereNumber('id')->middleware('hak.akses:PENYESUAIAN_STOK_KELOLA')->name('batalkan');
    });

    Route::prefix('pembelian')->name('pembelian.')->group(function (): void {
        Route::get('/', [PembelianController::class, 'index'])->middleware('hak.akses:PEMBELIAN_LIHAT')->name('index');

        Route::post('/permintaan', [PembelianController::class, 'simpanPermintaan'])->middleware('hak.akses:PERMINTAAN_PEMBELIAN_KELOLA')->name('permintaan.simpan');
        Route::patch('/permintaan/{id}/ajukan', [PembelianController::class, 'ajukanPermintaan'])->whereNumber('id')->middleware('hak.akses:PERMINTAAN_PEMBELIAN_KELOLA')->name('permintaan.ajukan');
        Route::patch('/permintaan/{id}/setujui', [PembelianController::class, 'setujuiPermintaan'])->whereNumber('id')->middleware('hak.akses:PERMINTAAN_PEMBELIAN_SETUJUI')->name('permintaan.setujui');
        Route::patch('/permintaan/{id}/tolak', [PembelianController::class, 'tolakPermintaan'])->whereNumber('id')->middleware('hak.akses:PERMINTAAN_PEMBELIAN_SETUJUI')->name('permintaan.tolak');
        Route::patch('/permintaan/{id}/batalkan', [PembelianController::class, 'batalkanPermintaan'])->whereNumber('id')->middleware('hak.akses:PERMINTAAN_PEMBELIAN_KELOLA')->name('permintaan.batalkan');

        Route::post('/pesanan', [PembelianController::class, 'simpanPesanan'])->middleware('hak.akses:PESANAN_PEMBELIAN_KELOLA')->name('pesanan.simpan');
        Route::patch('/pesanan/{id}/ajukan', [PembelianController::class, 'ajukanPesanan'])->whereNumber('id')->middleware('hak.akses:PESANAN_PEMBELIAN_KELOLA')->name('pesanan.ajukan');
        Route::patch('/pesanan/{id}/setujui', [PembelianController::class, 'setujuiPesanan'])->whereNumber('id')->middleware('hak.akses:PESANAN_PEMBELIAN_SETUJUI')->name('pesanan.setujui');
        Route::patch('/pesanan/{id}/batalkan', [PembelianController::class, 'batalkanPesanan'])->whereNumber('id')->middleware('hak.akses:PESANAN_PEMBELIAN_KELOLA')->name('pesanan.batalkan');

        Route::post('/penerimaan', [PembelianController::class, 'simpanPenerimaan'])->middleware('hak.akses:PENERIMAAN_BARANG_KELOLA')->name('penerimaan.simpan');
        Route::patch('/penerimaan/{id}/terima', [PembelianController::class, 'terimaPenerimaan'])->whereNumber('id')->middleware('hak.akses:PENERIMAAN_BARANG_TERIMA')->name('penerimaan.terima');
        Route::patch('/penerimaan/{id}/batalkan', [PembelianController::class, 'batalkanPenerimaan'])->whereNumber('id')->middleware('hak.akses:PENERIMAAN_BARANG_KELOLA')->name('penerimaan.batalkan');

        Route::post('/faktur', [PembelianController::class, 'simpanFaktur'])->middleware('hak.akses:FAKTUR_PEMBELIAN_KELOLA')->name('faktur.simpan');
        Route::patch('/faktur/{id}/setujui', [PembelianController::class, 'setujuiFaktur'])->whereNumber('id')->middleware('hak.akses:FAKTUR_PEMBELIAN_SETUJUI')->name('faktur.setujui');
        Route::patch('/faktur/{id}/batalkan', [PembelianController::class, 'batalkanFaktur'])->whereNumber('id')->middleware('hak.akses:FAKTUR_PEMBELIAN_KELOLA')->name('faktur.batalkan');

        Route::post('/retur', [PembelianController::class, 'simpanRetur'])->middleware('hak.akses:RETUR_PEMBELIAN_KELOLA')->name('retur.simpan');
        Route::patch('/retur/{id}/setujui', [PembelianController::class, 'setujuiRetur'])->whereNumber('id')->middleware('hak.akses:RETUR_PEMBELIAN_SETUJUI')->name('retur.setujui');
        Route::patch('/retur/{id}/kirim', [PembelianController::class, 'kirimRetur'])->whereNumber('id')->middleware('hak.akses:RETUR_PEMBELIAN_KIRIM')->name('retur.kirim');
        Route::patch('/retur/{id}/selesai', [PembelianController::class, 'selesaikanRetur'])->whereNumber('id')->middleware('hak.akses:RETUR_PEMBELIAN_KELOLA')->name('retur.selesai');
        Route::patch('/retur/{id}/batalkan', [PembelianController::class, 'batalkanRetur'])->whereNumber('id')->middleware('hak.akses:RETUR_PEMBELIAN_KELOLA')->name('retur.batalkan');
    });

    Route::prefix('hutang-pemasok')->name('hutang-pemasok.')->group(function (): void {
        Route::get('/', [HutangPemasokController::class, 'index'])->middleware('hak.akses:HUTANG_PEMASOK_LIHAT')->name('index');
        Route::post('/', [HutangPemasokController::class, 'simpan'])->middleware('hak.akses:PEMBAYARAN_HUTANG_KELOLA')->name('simpan');
        Route::patch('/{id}/setujui', [HutangPemasokController::class, 'setujui'])->whereNumber('id')->middleware('hak.akses:PEMBAYARAN_HUTANG_SETUJUI')->name('setujui');
        Route::patch('/{id}/batalkan', [HutangPemasokController::class, 'batalkan'])->whereNumber('id')->middleware('hak.akses:PEMBAYARAN_HUTANG_KELOLA')->name('batalkan');
    });

    Route::prefix('penjualan')->name('penjualan.')->group(function (): void {
        Route::get('/', [PenjualanController::class, 'index'])->middleware('hak.akses:PENJUALAN_LIHAT')->name('index');

        Route::post('/penawaran', [PenjualanController::class, 'simpanPenawaran'])->middleware('hak.akses:PENAWARAN_PENJUALAN_KELOLA')->name('penawaran.simpan');
        Route::patch('/penawaran/{id}/kirim', [PenjualanController::class, 'kirimPenawaran'])->whereNumber('id')->middleware('hak.akses:PENAWARAN_PENJUALAN_KELOLA')->name('penawaran.kirim');
        Route::patch('/penawaran/{id}/terima', [PenjualanController::class, 'terimaPenawaran'])->whereNumber('id')->middleware('hak.akses:PENAWARAN_PENJUALAN_KELOLA')->name('penawaran.terima');
        Route::patch('/penawaran/{id}/tolak', [PenjualanController::class, 'tolakPenawaran'])->whereNumber('id')->middleware('hak.akses:PENAWARAN_PENJUALAN_KELOLA')->name('penawaran.tolak');
        Route::patch('/penawaran/{id}/kedaluwarsa', [PenjualanController::class, 'kedaluwarsaPenawaran'])->whereNumber('id')->middleware('hak.akses:PENAWARAN_PENJUALAN_KELOLA')->name('penawaran.kedaluwarsa');
        Route::patch('/penawaran/{id}/batalkan', [PenjualanController::class, 'batalkanPenawaran'])->whereNumber('id')->middleware('hak.akses:PENAWARAN_PENJUALAN_KELOLA')->name('penawaran.batalkan');
        Route::post('/penawaran/{id}/jadikan-pesanan', [PenjualanController::class, 'jadikanPesanan'])->whereNumber('id')->middleware('hak.akses:PESANAN_PENJUALAN_KELOLA')->name('penawaran.jadikan-pesanan');

        Route::post('/pesanan', [PenjualanController::class, 'simpanPesanan'])->middleware('hak.akses:PESANAN_PENJUALAN_KELOLA')->name('pesanan.simpan');
        Route::patch('/pesanan/{id}/setujui', [PenjualanController::class, 'setujuiPesanan'])->whereNumber('id')->middleware('hak.akses:PESANAN_PENJUALAN_SETUJUI')->name('pesanan.setujui');
        Route::patch('/pesanan/{id}/batalkan', [PenjualanController::class, 'batalkanPesanan'])->whereNumber('id')->middleware('hak.akses:PESANAN_PENJUALAN_KELOLA')->name('pesanan.batalkan');

        Route::post('/transaksi', [PenjualanController::class, 'simpanPenjualan'])->middleware('hak.akses:TRANSAKSI_PENJUALAN_KELOLA')->name('transaksi.simpan');
        Route::patch('/transaksi/{id}/setujui', [PenjualanController::class, 'setujuiPenjualan'])->whereNumber('id')->middleware('hak.akses:TRANSAKSI_PENJUALAN_SETUJUI')->name('transaksi.setujui');
        Route::patch('/transaksi/{id}/batalkan', [PenjualanController::class, 'batalkanPenjualan'])->whereNumber('id')->middleware('hak.akses:TRANSAKSI_PENJUALAN_KELOLA')->name('transaksi.batalkan');

        Route::post('/pengiriman', [PenjualanController::class, 'simpanPengiriman'])->middleware('hak.akses:PENGIRIMAN_KELOLA')->name('pengiriman.simpan');
        Route::patch('/pengiriman/{id}/jadwalkan', [PenjualanController::class, 'jadwalkanPengiriman'])->whereNumber('id')->middleware('hak.akses:PENGIRIMAN_JADWALKAN')->name('pengiriman.jadwalkan');
        Route::patch('/pengiriman/{id}/berangkat', [PenjualanController::class, 'berangkatkanPengiriman'])->whereNumber('id')->middleware('hak.akses:PENGIRIMAN_KIRIM')->name('pengiriman.berangkat');
        Route::patch('/pengiriman/{id}/terima', [PenjualanController::class, 'terimaPengiriman'])->whereNumber('id')->middleware('hak.akses:PENGIRIMAN_TERIMA')->name('pengiriman.terima');
        Route::patch('/pengiriman/{id}/gagal', [PenjualanController::class, 'gagalPengiriman'])->whereNumber('id')->middleware('hak.akses:PENGIRIMAN_KELOLA')->name('pengiriman.gagal');
        Route::patch('/pengiriman/{id}/batalkan', [PenjualanController::class, 'batalkanPengiriman'])->whereNumber('id')->middleware('hak.akses:PENGIRIMAN_KELOLA')->name('pengiriman.batalkan');

        Route::post('/retur', [PenjualanController::class, 'simpanRetur'])->middleware('hak.akses:RETUR_PENJUALAN_KELOLA')->name('retur.simpan');
        Route::patch('/retur/{id}/setujui', [PenjualanController::class, 'setujuiRetur'])->whereNumber('id')->middleware('hak.akses:RETUR_PENJUALAN_SETUJUI')->name('retur.setujui');
        Route::patch('/retur/{id}/terima', [PenjualanController::class, 'terimaRetur'])->whereNumber('id')->middleware('hak.akses:RETUR_PENJUALAN_TERIMA')->name('retur.terima');
        Route::patch('/retur/{id}/selesai', [PenjualanController::class, 'selesaikanRetur'])->whereNumber('id')->middleware('hak.akses:RETUR_PENJUALAN_KELOLA')->name('retur.selesai');
        Route::patch('/retur/{id}/batalkan', [PenjualanController::class, 'batalkanRetur'])->whereNumber('id')->middleware('hak.akses:RETUR_PENJUALAN_KELOLA')->name('retur.batalkan');
    });

    Route::prefix('piutang-pelanggan')->name('piutang-pelanggan.')->group(function (): void {
        Route::get('/', [PiutangPelangganController::class, 'index'])->middleware('hak.akses:PIUTANG_PELANGGAN_LIHAT')->name('index');
        Route::post('/', [PiutangPelangganController::class, 'simpan'])->middleware('hak.akses:PEMBAYARAN_PIUTANG_KELOLA')->name('simpan');
        Route::patch('/{id}/setujui', [PiutangPelangganController::class, 'setujui'])->whereNumber('id')->middleware('hak.akses:PEMBAYARAN_PIUTANG_SETUJUI')->name('setujui');
        Route::patch('/{id}/batalkan', [PiutangPelangganController::class, 'batalkan'])->whereNumber('id')->middleware('hak.akses:PEMBAYARAN_PIUTANG_KELOLA')->name('batalkan');
    });

    Route::get('/audit', [AuditController::class, 'index'])->middleware('hak.akses:AUDIT_LIHAT')->name('audit.index');
});
