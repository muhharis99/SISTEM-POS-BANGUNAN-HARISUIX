SET NAMES utf8mb4;
SET time_zone = '+07:00';

CREATE DATABASE IF NOT EXISTS sistem_informasi_toko_bangunan
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE sistem_informasi_toko_bangunan;

-- =========================================================
-- 1. CABANG, PEGAWAI, PENGGUNA, DAN HAK AKSES
-- =========================================================

CREATE TABLE IF NOT EXISTS cabang (
    id_cabang BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    kode_cabang VARCHAR(20) NOT NULL,
    nama_cabang VARCHAR(150) NOT NULL,
    alamat TEXT NOT NULL,
    provinsi VARCHAR(100) NULL,
    kabupaten_kota VARCHAR(100) NULL,
    kecamatan VARCHAR(100) NULL,
    kelurahan VARCHAR(100) NULL,
    kode_pos VARCHAR(10) NULL,
    telepon VARCHAR(30) NULL,
    nomor_whatsapp VARCHAR(30) NULL,
    surel VARCHAR(150) NULL,
    status_aktif TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by BIGINT UNSIGNED NULL,
    updated_at DATETIME NULL,
    updated_by BIGINT UNSIGNED NULL,
    deleted_at DATETIME NULL,
    deleted_by BIGINT UNSIGNED NULL,
    UNIQUE KEY uk_cabang_kode (kode_cabang),
    KEY idx_cabang_nama (nama_cabang),
    KEY idx_cabang_status_aktif (status_aktif)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS pegawai (
    id_pegawai BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_cabang BIGINT UNSIGNED NOT NULL,
    kode_pegawai VARCHAR(30) NOT NULL,
    nama_pegawai VARCHAR(150) NOT NULL,
    jenis_kelamin ENUM('LAKI_LAKI','PEREMPUAN') NULL,
    tempat_lahir VARCHAR(100) NULL,
    tanggal_lahir DATE NULL,
    alamat TEXT NULL,
    telepon VARCHAR(30) NULL,
    nomor_whatsapp VARCHAR(30) NULL,
    surel VARCHAR(150) NULL,
    jabatan VARCHAR(100) NULL,
    tanggal_masuk DATE NULL,
    tanggal_keluar DATE NULL,
    status_pegawai ENUM('TETAP','KONTRAK','HARIAN','MAGANG','NONAKTIF') NOT NULL DEFAULT 'TETAP',
    status_aktif TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by BIGINT UNSIGNED NULL,
    updated_at DATETIME NULL,
    updated_by BIGINT UNSIGNED NULL,
    deleted_at DATETIME NULL,
    deleted_by BIGINT UNSIGNED NULL,
    UNIQUE KEY uk_pegawai_kode (kode_pegawai),
    KEY idx_pegawai_cabang (id_cabang),
    KEY idx_pegawai_nama (nama_pegawai),
    CONSTRAINT fk_pegawai_cabang
        FOREIGN KEY (id_cabang) REFERENCES cabang(id_cabang)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS pengguna (
    id_pengguna BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_pegawai BIGINT UNSIGNED NULL,
    nama_pengguna VARCHAR(100) NOT NULL,
    kata_sandi VARCHAR(255) NOT NULL,
    nama_tampilan VARCHAR(150) NOT NULL,
    surel VARCHAR(150) NULL,
    telepon VARCHAR(30) NULL,
    status_aktif TINYINT(1) NOT NULL DEFAULT 1,
    terakhir_masuk DATETIME NULL,
    percobaan_masuk SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    dikunci_sampai DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by BIGINT UNSIGNED NULL,
    updated_at DATETIME NULL,
    updated_by BIGINT UNSIGNED NULL,
    deleted_at DATETIME NULL,
    deleted_by BIGINT UNSIGNED NULL,
    UNIQUE KEY uk_pengguna_nama_pengguna (nama_pengguna),
    UNIQUE KEY uk_pengguna_pegawai (id_pegawai),
    CONSTRAINT fk_pengguna_pegawai
        FOREIGN KEY (id_pegawai) REFERENCES pegawai(id_pegawai)
        ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS peran (
    id_peran BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    kode_peran VARCHAR(50) NOT NULL,
    nama_peran VARCHAR(100) NOT NULL,
    keterangan TEXT NULL,
    status_aktif TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by BIGINT UNSIGNED NULL,
    updated_at DATETIME NULL,
    updated_by BIGINT UNSIGNED NULL,
    deleted_at DATETIME NULL,
    deleted_by BIGINT UNSIGNED NULL,
    UNIQUE KEY uk_peran_kode (kode_peran)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS hak_akses (
    id_hak_akses BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    kode_hak_akses VARCHAR(100) NOT NULL,
    nama_hak_akses VARCHAR(150) NOT NULL,
    nama_modul VARCHAR(100) NOT NULL,
    keterangan TEXT NULL,
    status_aktif TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by BIGINT UNSIGNED NULL,
    updated_at DATETIME NULL,
    updated_by BIGINT UNSIGNED NULL,
    deleted_at DATETIME NULL,
    deleted_by BIGINT UNSIGNED NULL,
    UNIQUE KEY uk_hak_akses_kode (kode_hak_akses),
    KEY idx_hak_akses_modul (nama_modul)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS pengguna_peran (
    id_pengguna_peran BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_pengguna BIGINT UNSIGNED NOT NULL,
    id_peran BIGINT UNSIGNED NOT NULL,
    id_cabang BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by BIGINT UNSIGNED NULL,
    deleted_at DATETIME NULL,
    deleted_by BIGINT UNSIGNED NULL,
    UNIQUE KEY uk_pengguna_peran_cabang (id_pengguna, id_peran, id_cabang),
    KEY idx_pengguna_peran_peran (id_peran),
    KEY idx_pengguna_peran_cabang (id_cabang),
    CONSTRAINT fk_pengguna_peran_pengguna
        FOREIGN KEY (id_pengguna) REFERENCES pengguna(id_pengguna),
    CONSTRAINT fk_pengguna_peran_peran
        FOREIGN KEY (id_peran) REFERENCES peran(id_peran),
    CONSTRAINT fk_pengguna_peran_cabang
        FOREIGN KEY (id_cabang) REFERENCES cabang(id_cabang)
        ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS peran_hak_akses (
    id_peran_hak_akses BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_peran BIGINT UNSIGNED NOT NULL,
    id_hak_akses BIGINT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by BIGINT UNSIGNED NULL,
    deleted_at DATETIME NULL,
    deleted_by BIGINT UNSIGNED NULL,
    UNIQUE KEY uk_peran_hak_akses (id_peran, id_hak_akses),
    KEY idx_peran_hak_akses_hak (id_hak_akses),
    CONSTRAINT fk_peran_hak_akses_peran
        FOREIGN KEY (id_peran) REFERENCES peran(id_peran),
    CONSTRAINT fk_peran_hak_akses_hak
        FOREIGN KEY (id_hak_akses) REFERENCES hak_akses(id_hak_akses)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS pengaturan_aplikasi (
    id_pengaturan_aplikasi BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_cabang BIGINT UNSIGNED NULL,
    kunci_pengaturan VARCHAR(150) NOT NULL,
    nilai_pengaturan LONGTEXT NULL,
    jenis_nilai ENUM('TEKS','ANGKA','DESIMAL','TANGGAL','WAKTU','BENAR_SALAH','DAFTAR') NOT NULL DEFAULT 'TEKS',
    keterangan TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by BIGINT UNSIGNED NULL,
    updated_at DATETIME NULL,
    updated_by BIGINT UNSIGNED NULL,
    deleted_at DATETIME NULL,
    deleted_by BIGINT UNSIGNED NULL,
    UNIQUE KEY uk_pengaturan_cabang_kunci (id_cabang, kunci_pengaturan),
    CONSTRAINT fk_pengaturan_aplikasi_cabang
        FOREIGN KEY (id_cabang) REFERENCES cabang(id_cabang)
        ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS nomor_dokumen (
    id_nomor_dokumen BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_cabang BIGINT UNSIGNED NOT NULL,
    jenis_dokumen VARCHAR(100) NOT NULL,
    awalan VARCHAR(30) NOT NULL,
    tahun SMALLINT UNSIGNED NOT NULL,
    bulan TINYINT UNSIGNED NOT NULL DEFAULT 0,
    nomor_terakhir BIGINT UNSIGNED NOT NULL DEFAULT 0,
    jumlah_digit TINYINT UNSIGNED NOT NULL DEFAULT 5,
    pola_nomor VARCHAR(150) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by BIGINT UNSIGNED NULL,
    updated_at DATETIME NULL,
    updated_by BIGINT UNSIGNED NULL,
    UNIQUE KEY uk_nomor_dokumen (id_cabang, jenis_dokumen, tahun, bulan),
    CONSTRAINT fk_nomor_dokumen_cabang
        FOREIGN KEY (id_cabang) REFERENCES cabang(id_cabang)
) ENGINE=InnoDB;

-- =========================================================
-- 2. MASTER BARANG, PELANGGAN, PEMASOK, GUDANG, DAN KAS
-- =========================================================

CREATE TABLE IF NOT EXISTS kategori_barang (
    id_kategori_barang BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_kategori_induk BIGINT UNSIGNED NULL,
    kode_kategori VARCHAR(30) NOT NULL,
    nama_kategori VARCHAR(150) NOT NULL,
    keterangan TEXT NULL,
    urutan_tampil INT UNSIGNED NOT NULL DEFAULT 0,
    status_aktif TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by BIGINT UNSIGNED NULL,
    updated_at DATETIME NULL,
    updated_by BIGINT UNSIGNED NULL,
    deleted_at DATETIME NULL,
    deleted_by BIGINT UNSIGNED NULL,
    UNIQUE KEY uk_kategori_barang_kode (kode_kategori),
    KEY idx_kategori_barang_induk (id_kategori_induk),
    KEY idx_kategori_barang_nama (nama_kategori),
    CONSTRAINT fk_kategori_barang_induk
        FOREIGN KEY (id_kategori_induk) REFERENCES kategori_barang(id_kategori_barang)
        ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS merek_barang (
    id_merek_barang BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    kode_merek VARCHAR(30) NOT NULL,
    nama_merek VARCHAR(150) NOT NULL,
    keterangan TEXT NULL,
    status_aktif TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by BIGINT UNSIGNED NULL,
    updated_at DATETIME NULL,
    updated_by BIGINT UNSIGNED NULL,
    deleted_at DATETIME NULL,
    deleted_by BIGINT UNSIGNED NULL,
    UNIQUE KEY uk_merek_barang_kode (kode_merek),
    KEY idx_merek_barang_nama (nama_merek)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS satuan (
    id_satuan BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    kode_satuan VARCHAR(20) NOT NULL,
    nama_satuan VARCHAR(100) NOT NULL,
    jumlah_desimal TINYINT UNSIGNED NOT NULL DEFAULT 0,
    status_aktif TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by BIGINT UNSIGNED NULL,
    updated_at DATETIME NULL,
    updated_by BIGINT UNSIGNED NULL,
    deleted_at DATETIME NULL,
    deleted_by BIGINT UNSIGNED NULL,
    UNIQUE KEY uk_satuan_kode (kode_satuan),
    UNIQUE KEY uk_satuan_nama (nama_satuan)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS barang (
    id_barang BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_kategori_barang BIGINT UNSIGNED NOT NULL,
    id_merek_barang BIGINT UNSIGNED NULL,
    id_satuan_dasar BIGINT UNSIGNED NOT NULL,
    kode_barang VARCHAR(50) NOT NULL,
    nama_barang VARCHAR(200) NOT NULL,
    jenis_barang ENUM('BARANG','JASA') NOT NULL DEFAULT 'BARANG',
    spesifikasi TEXT NULL,
    warna VARCHAR(100) NULL,
    ukuran VARCHAR(100) NULL,
    berat_kilogram DECIMAL(18,3) NOT NULL DEFAULT 0,
    panjang_sentimeter DECIMAL(18,3) NOT NULL DEFAULT 0,
    lebar_sentimeter DECIMAL(18,3) NOT NULL DEFAULT 0,
    tinggi_sentimeter DECIMAL(18,3) NOT NULL DEFAULT 0,
    stok_minimum DECIMAL(18,3) NOT NULL DEFAULT 0,
    stok_maksimum DECIMAL(18,3) NOT NULL DEFAULT 0,
    metode_persediaan ENUM('RATA_RATA','MASUK_PERTAMA_KELUAR_PERTAMA') NOT NULL DEFAULT 'RATA_RATA',
    bisa_dibeli TINYINT(1) NOT NULL DEFAULT 1,
    bisa_dijual TINYINT(1) NOT NULL DEFAULT 1,
    wajib_nomor_lot TINYINT(1) NOT NULL DEFAULT 0,
    wajib_tanggal_kedaluwarsa TINYINT(1) NOT NULL DEFAULT 0,
    status_aktif TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by BIGINT UNSIGNED NULL,
    updated_at DATETIME NULL,
    updated_by BIGINT UNSIGNED NULL,
    deleted_at DATETIME NULL,
    deleted_by BIGINT UNSIGNED NULL,
    UNIQUE KEY uk_barang_kode (kode_barang),
    KEY idx_barang_nama (nama_barang),
    KEY idx_barang_kategori (id_kategori_barang),
    KEY idx_barang_merek (id_merek_barang),
    KEY idx_barang_satuan_dasar (id_satuan_dasar),
    CONSTRAINT fk_barang_kategori
        FOREIGN KEY (id_kategori_barang) REFERENCES kategori_barang(id_kategori_barang),
    CONSTRAINT fk_barang_merek
        FOREIGN KEY (id_merek_barang) REFERENCES merek_barang(id_merek_barang)
        ON DELETE SET NULL,
    CONSTRAINT fk_barang_satuan_dasar
        FOREIGN KEY (id_satuan_dasar) REFERENCES satuan(id_satuan)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS barang_satuan (
    id_barang_satuan BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_barang BIGINT UNSIGNED NOT NULL,
    id_satuan BIGINT UNSIGNED NOT NULL,
    kode_batang VARCHAR(100) NULL,
    nilai_konversi DECIMAL(18,6) NOT NULL DEFAULT 1,
    harga_beli_acuan DECIMAL(18,2) NOT NULL DEFAULT 0,
    harga_jual_acuan DECIMAL(18,2) NOT NULL DEFAULT 0,
    satuan_utama_pembelian TINYINT(1) NOT NULL DEFAULT 0,
    satuan_utama_penjualan TINYINT(1) NOT NULL DEFAULT 0,
    status_aktif TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by BIGINT UNSIGNED NULL,
    updated_at DATETIME NULL,
    updated_by BIGINT UNSIGNED NULL,
    deleted_at DATETIME NULL,
    deleted_by BIGINT UNSIGNED NULL,
    UNIQUE KEY uk_barang_satuan (id_barang, id_satuan),
    UNIQUE KEY uk_barang_satuan_kode_batang (kode_batang),
    KEY idx_barang_satuan_satuan (id_satuan),
    CONSTRAINT fk_barang_satuan_barang
        FOREIGN KEY (id_barang) REFERENCES barang(id_barang),
    CONSTRAINT fk_barang_satuan_satuan
        FOREIGN KEY (id_satuan) REFERENCES satuan(id_satuan)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS jenis_pelanggan (
    id_jenis_pelanggan BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    kode_jenis_pelanggan VARCHAR(30) NOT NULL,
    nama_jenis_pelanggan VARCHAR(100) NOT NULL,
    potongan_persen_bawaan DECIMAL(8,4) NOT NULL DEFAULT 0,
    batas_piutang_bawaan DECIMAL(18,2) NOT NULL DEFAULT 0,
    lama_jatuh_tempo_bawaan SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    status_aktif TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by BIGINT UNSIGNED NULL,
    updated_at DATETIME NULL,
    updated_by BIGINT UNSIGNED NULL,
    deleted_at DATETIME NULL,
    deleted_by BIGINT UNSIGNED NULL,
    UNIQUE KEY uk_jenis_pelanggan_kode (kode_jenis_pelanggan)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS pelanggan (
    id_pelanggan BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_jenis_pelanggan BIGINT UNSIGNED NOT NULL,
    kode_pelanggan VARCHAR(40) NOT NULL,
    nama_pelanggan VARCHAR(200) NOT NULL,
    jenis_identitas ENUM('KTP','SIM','PASPOR','LAINNYA') NULL,
    nomor_identitas VARCHAR(100) NULL,
    nomor_pokok_wajib_pajak VARCHAR(40) NULL,
    telepon VARCHAR(30) NULL,
    nomor_whatsapp VARCHAR(30) NULL,
    surel VARCHAR(150) NULL,
    alamat_utama TEXT NULL,
    provinsi VARCHAR(100) NULL,
    kabupaten_kota VARCHAR(100) NULL,
    kecamatan VARCHAR(100) NULL,
    kelurahan VARCHAR(100) NULL,
    kode_pos VARCHAR(10) NULL,
    nama_kontak VARCHAR(150) NULL,
    batas_piutang DECIMAL(18,2) NOT NULL DEFAULT 0,
    lama_jatuh_tempo SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    potongan_persen DECIMAL(8,4) NOT NULL DEFAULT 0,
    status_aktif TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by BIGINT UNSIGNED NULL,
    updated_at DATETIME NULL,
    updated_by BIGINT UNSIGNED NULL,
    deleted_at DATETIME NULL,
    deleted_by BIGINT UNSIGNED NULL,
    UNIQUE KEY uk_pelanggan_kode (kode_pelanggan),
    KEY idx_pelanggan_nama (nama_pelanggan),
    KEY idx_pelanggan_jenis (id_jenis_pelanggan),
    KEY idx_pelanggan_whatsapp (nomor_whatsapp),
    CONSTRAINT fk_pelanggan_jenis
        FOREIGN KEY (id_jenis_pelanggan) REFERENCES jenis_pelanggan(id_jenis_pelanggan)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS alamat_pelanggan (
    id_alamat_pelanggan BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_pelanggan BIGINT UNSIGNED NOT NULL,
    nama_alamat VARCHAR(100) NOT NULL,
    nama_penerima VARCHAR(150) NULL,
    telepon_penerima VARCHAR(30) NULL,
    alamat TEXT NOT NULL,
    provinsi VARCHAR(100) NULL,
    kabupaten_kota VARCHAR(100) NULL,
    kecamatan VARCHAR(100) NULL,
    kelurahan VARCHAR(100) NULL,
    kode_pos VARCHAR(10) NULL,
    garis_lintang DECIMAL(10,7) NULL,
    garis_bujur DECIMAL(10,7) NULL,
    alamat_utama TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by BIGINT UNSIGNED NULL,
    updated_at DATETIME NULL,
    updated_by BIGINT UNSIGNED NULL,
    deleted_at DATETIME NULL,
    deleted_by BIGINT UNSIGNED NULL,
    KEY idx_alamat_pelanggan_pelanggan (id_pelanggan),
    CONSTRAINT fk_alamat_pelanggan_pelanggan
        FOREIGN KEY (id_pelanggan) REFERENCES pelanggan(id_pelanggan)
        ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS pemasok (
    id_pemasok BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    kode_pemasok VARCHAR(40) NOT NULL,
    nama_pemasok VARCHAR(200) NOT NULL,
    nomor_pokok_wajib_pajak VARCHAR(40) NULL,
    nama_kontak VARCHAR(150) NULL,
    telepon VARCHAR(30) NULL,
    nomor_whatsapp VARCHAR(30) NULL,
    surel VARCHAR(150) NULL,
    alamat TEXT NULL,
    provinsi VARCHAR(100) NULL,
    kabupaten_kota VARCHAR(100) NULL,
    kecamatan VARCHAR(100) NULL,
    kelurahan VARCHAR(100) NULL,
    kode_pos VARCHAR(10) NULL,
    nama_bank VARCHAR(100) NULL,
    nomor_rekening VARCHAR(100) NULL,
    nama_pemilik_rekening VARCHAR(150) NULL,
    batas_hutang DECIMAL(18,2) NOT NULL DEFAULT 0,
    lama_jatuh_tempo SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    status_aktif TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by BIGINT UNSIGNED NULL,
    updated_at DATETIME NULL,
    updated_by BIGINT UNSIGNED NULL,
    deleted_at DATETIME NULL,
    deleted_by BIGINT UNSIGNED NULL,
    UNIQUE KEY uk_pemasok_kode (kode_pemasok),
    KEY idx_pemasok_nama (nama_pemasok)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS gudang (
    id_gudang BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_cabang BIGINT UNSIGNED NOT NULL,
    kode_gudang VARCHAR(30) NOT NULL,
    nama_gudang VARCHAR(150) NOT NULL,
    jenis_gudang ENUM('UTAMA','TOKO','TRANSIT','RUSAK','RETUR') NOT NULL DEFAULT 'UTAMA',
    alamat TEXT NULL,
    nama_penanggung_jawab VARCHAR(150) NULL,
    telepon VARCHAR(30) NULL,
    status_aktif TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by BIGINT UNSIGNED NULL,
    updated_at DATETIME NULL,
    updated_by BIGINT UNSIGNED NULL,
    deleted_at DATETIME NULL,
    deleted_by BIGINT UNSIGNED NULL,
    UNIQUE KEY uk_gudang_cabang_kode (id_cabang, kode_gudang),
    KEY idx_gudang_cabang (id_cabang),
    CONSTRAINT fk_gudang_cabang
        FOREIGN KEY (id_cabang) REFERENCES cabang(id_cabang)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS lokasi_gudang (
    id_lokasi_gudang BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_gudang BIGINT UNSIGNED NOT NULL,
    id_lokasi_induk BIGINT UNSIGNED NULL,
    kode_lokasi VARCHAR(50) NOT NULL,
    nama_lokasi VARCHAR(150) NOT NULL,
    jenis_lokasi ENUM('ZONA','RAK','BARIS','TINGKAT','AREA_UMUM') NOT NULL DEFAULT 'AREA_UMUM',
    keterangan TEXT NULL,
    status_aktif TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by BIGINT UNSIGNED NULL,
    updated_at DATETIME NULL,
    updated_by BIGINT UNSIGNED NULL,
    deleted_at DATETIME NULL,
    deleted_by BIGINT UNSIGNED NULL,
    UNIQUE KEY uk_lokasi_gudang_kode (id_gudang, kode_lokasi),
    KEY idx_lokasi_gudang_induk (id_lokasi_induk),
    CONSTRAINT fk_lokasi_gudang_gudang
        FOREIGN KEY (id_gudang) REFERENCES gudang(id_gudang),
    CONSTRAINT fk_lokasi_gudang_induk
        FOREIGN KEY (id_lokasi_induk) REFERENCES lokasi_gudang(id_lokasi_gudang)
        ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS kas_bank (
    id_kas_bank BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_cabang BIGINT UNSIGNED NOT NULL,
    kode_kas_bank VARCHAR(30) NOT NULL,
    nama_kas_bank VARCHAR(150) NOT NULL,
    jenis_kas_bank ENUM('KAS','BANK') NOT NULL,
    nama_bank VARCHAR(100) NULL,
    nomor_rekening VARCHAR(100) NULL,
    nama_pemilik_rekening VARCHAR(150) NULL,
    saldo_awal DECIMAL(18,2) NOT NULL DEFAULT 0,
    tanggal_saldo_awal DATE NULL,
    status_aktif TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by BIGINT UNSIGNED NULL,
    updated_at DATETIME NULL,
    updated_by BIGINT UNSIGNED NULL,
    deleted_at DATETIME NULL,
    deleted_by BIGINT UNSIGNED NULL,
    UNIQUE KEY uk_kas_bank_cabang_kode (id_cabang, kode_kas_bank),
    KEY idx_kas_bank_cabang (id_cabang),
    CONSTRAINT fk_kas_bank_cabang
        FOREIGN KEY (id_cabang) REFERENCES cabang(id_cabang)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS metode_pembayaran (
    id_metode_pembayaran BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    kode_metode_pembayaran VARCHAR(30) NOT NULL,
    nama_metode_pembayaran VARCHAR(100) NOT NULL,
    kelompok_pembayaran ENUM('TUNAI','TRANSFER','KARTU','DOMPET_DIGITAL','TEMPO','LAINNYA') NOT NULL,
    biaya_persen DECIMAL(8,4) NOT NULL DEFAULT 0,
    biaya_tetap DECIMAL(18,2) NOT NULL DEFAULT 0,
    status_aktif TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by BIGINT UNSIGNED NULL,
    updated_at DATETIME NULL,
    updated_by BIGINT UNSIGNED NULL,
    deleted_at DATETIME NULL,
    deleted_by BIGINT UNSIGNED NULL,
    UNIQUE KEY uk_metode_pembayaran_kode (kode_metode_pembayaran)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS kategori_biaya (
    id_kategori_biaya BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_kategori_induk BIGINT UNSIGNED NULL,
    kode_kategori_biaya VARCHAR(30) NOT NULL,
    nama_kategori_biaya VARCHAR(150) NOT NULL,
    keterangan TEXT NULL,
    status_aktif TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by BIGINT UNSIGNED NULL,
    updated_at DATETIME NULL,
    updated_by BIGINT UNSIGNED NULL,
    deleted_at DATETIME NULL,
    deleted_by BIGINT UNSIGNED NULL,
    UNIQUE KEY uk_kategori_biaya_kode (kode_kategori_biaya),
    KEY idx_kategori_biaya_induk (id_kategori_induk),
    CONSTRAINT fk_kategori_biaya_induk
        FOREIGN KEY (id_kategori_induk) REFERENCES kategori_biaya(id_kategori_biaya)
        ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS armada (
    id_armada BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_cabang BIGINT UNSIGNED NOT NULL,
    nomor_polisi VARCHAR(30) NOT NULL,
    jenis_kendaraan VARCHAR(100) NOT NULL,
    merek_kendaraan VARCHAR(100) NULL,
    tipe_kendaraan VARCHAR(100) NULL,
    tahun_kendaraan SMALLINT UNSIGNED NULL,
    kapasitas_kilogram DECIMAL(18,3) NOT NULL DEFAULT 0,
    kapasitas_meter_kubik DECIMAL(18,3) NOT NULL DEFAULT 0,
    status_kendaraan ENUM('TERSEDIA','DIGUNAKAN','PERAWATAN','NONAKTIF') NOT NULL DEFAULT 'TERSEDIA',
    status_aktif TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by BIGINT UNSIGNED NULL,
    updated_at DATETIME NULL,
    updated_by BIGINT UNSIGNED NULL,
    deleted_at DATETIME NULL,
    deleted_by BIGINT UNSIGNED NULL,
    UNIQUE KEY uk_armada_nomor_polisi (nomor_polisi),
    KEY idx_armada_cabang (id_cabang),
    CONSTRAINT fk_armada_cabang
        FOREIGN KEY (id_cabang) REFERENCES cabang(id_cabang)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS tarif_pajak (
    id_tarif_pajak BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    kode_tarif_pajak VARCHAR(30) NOT NULL,
    nama_tarif_pajak VARCHAR(100) NOT NULL,
    persen_pajak DECIMAL(8,4) NOT NULL DEFAULT 0,
    jenis_pajak ENUM('MASUKAN','KELUARAN','KEDUANYA') NOT NULL DEFAULT 'KEDUANYA',
    status_aktif TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by BIGINT UNSIGNED NULL,
    updated_at DATETIME NULL,
    updated_by BIGINT UNSIGNED NULL,
    deleted_at DATETIME NULL,
    deleted_by BIGINT UNSIGNED NULL,
    UNIQUE KEY uk_tarif_pajak_kode (kode_tarif_pajak)
) ENGINE=InnoDB;

-- =========================================================
-- 3. DAFTAR HARGA
-- =========================================================

CREATE TABLE IF NOT EXISTS daftar_harga (
    id_daftar_harga BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_cabang BIGINT UNSIGNED NOT NULL,
    id_jenis_pelanggan BIGINT UNSIGNED NULL,
    kode_daftar_harga VARCHAR(30) NOT NULL,
    nama_daftar_harga VARCHAR(150) NOT NULL,
    tanggal_mulai DATE NOT NULL,
    tanggal_selesai DATE NULL,
    prioritas SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    status_aktif TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by BIGINT UNSIGNED NULL,
    updated_at DATETIME NULL,
    updated_by BIGINT UNSIGNED NULL,
    deleted_at DATETIME NULL,
    deleted_by BIGINT UNSIGNED NULL,
    UNIQUE KEY uk_daftar_harga_cabang_kode (id_cabang, kode_daftar_harga),
    KEY idx_daftar_harga_jenis_pelanggan (id_jenis_pelanggan),
    KEY idx_daftar_harga_masa_berlaku (tanggal_mulai, tanggal_selesai),
    CONSTRAINT fk_daftar_harga_cabang
        FOREIGN KEY (id_cabang) REFERENCES cabang(id_cabang),
    CONSTRAINT fk_daftar_harga_jenis_pelanggan
        FOREIGN KEY (id_jenis_pelanggan) REFERENCES jenis_pelanggan(id_jenis_pelanggan)
        ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS daftar_harga_detail (
    id_daftar_harga_detail BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_daftar_harga BIGINT UNSIGNED NOT NULL,
    id_barang_satuan BIGINT UNSIGNED NOT NULL,
    jumlah_minimum DECIMAL(18,3) NOT NULL DEFAULT 1,
    harga_jual DECIMAL(18,2) NOT NULL DEFAULT 0,
    potongan_persen DECIMAL(8,4) NOT NULL DEFAULT 0,
    status_aktif TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by BIGINT UNSIGNED NULL,
    updated_at DATETIME NULL,
    updated_by BIGINT UNSIGNED NULL,
    deleted_at DATETIME NULL,
    deleted_by BIGINT UNSIGNED NULL,
    UNIQUE KEY uk_daftar_harga_detail (id_daftar_harga, id_barang_satuan, jumlah_minimum),
    KEY idx_daftar_harga_detail_barang_satuan (id_barang_satuan),
    CONSTRAINT fk_daftar_harga_detail_daftar
        FOREIGN KEY (id_daftar_harga) REFERENCES daftar_harga(id_daftar_harga)
        ON DELETE CASCADE,
    CONSTRAINT fk_daftar_harga_detail_barang_satuan
        FOREIGN KEY (id_barang_satuan) REFERENCES barang_satuan(id_barang_satuan)
) ENGINE=InnoDB;

-- =========================================================
-- 4. PERSEDIAAN DAN MUTASI STOK
-- =========================================================

CREATE TABLE IF NOT EXISTS saldo_stok (
    id_saldo_stok BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_gudang BIGINT UNSIGNED NOT NULL,
    id_lokasi_gudang BIGINT UNSIGNED NOT NULL,
    id_barang BIGINT UNSIGNED NOT NULL,
    jumlah_stok DECIMAL(18,3) NOT NULL DEFAULT 0,
    jumlah_dipesan DECIMAL(18,3) NOT NULL DEFAULT 0,
    jumlah_rusak DECIMAL(18,3) NOT NULL DEFAULT 0,
    harga_pokok_rata_rata DECIMAL(18,4) NOT NULL DEFAULT 0,
    harga_beli_terakhir DECIMAL(18,4) NOT NULL DEFAULT 0,
    tanggal_mutasi_terakhir DATETIME NULL,
    updated_at DATETIME NULL,
    updated_by BIGINT UNSIGNED NULL,
    UNIQUE KEY uk_saldo_stok (id_gudang, id_lokasi_gudang, id_barang),
    KEY idx_saldo_stok_barang (id_barang),
    KEY idx_saldo_stok_lokasi (id_lokasi_gudang),
    CONSTRAINT fk_saldo_stok_gudang
        FOREIGN KEY (id_gudang) REFERENCES gudang(id_gudang),
    CONSTRAINT fk_saldo_stok_lokasi
        FOREIGN KEY (id_lokasi_gudang) REFERENCES lokasi_gudang(id_lokasi_gudang),
    CONSTRAINT fk_saldo_stok_barang
        FOREIGN KEY (id_barang) REFERENCES barang(id_barang)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS mutasi_stok (
    id_mutasi_stok BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_cabang BIGINT UNSIGNED NOT NULL,
    id_gudang BIGINT UNSIGNED NOT NULL,
    id_lokasi_gudang BIGINT UNSIGNED NOT NULL,
    id_barang BIGINT UNSIGNED NOT NULL,
    tanggal_mutasi DATETIME NOT NULL,
    jenis_mutasi ENUM(
        'STOK_AWAL','PEMBELIAN','RETUR_PEMBELIAN','PENJUALAN','RETUR_PENJUALAN',
        'TRANSFER_MASUK','TRANSFER_KELUAR','PENYESUAIAN_MASUK','PENYESUAIAN_KELUAR',
        'STOK_OPNAME','LAINNYA'
    ) NOT NULL,
    jenis_dokumen VARCHAR(100) NOT NULL,
    id_dokumen BIGINT UNSIGNED NULL,
    nomor_dokumen VARCHAR(100) NULL,
    jumlah_masuk DECIMAL(18,3) NOT NULL DEFAULT 0,
    jumlah_keluar DECIMAL(18,3) NOT NULL DEFAULT 0,
    harga_pokok DECIMAL(18,4) NOT NULL DEFAULT 0,
    nilai_mutasi DECIMAL(18,2) NOT NULL DEFAULT 0,
    saldo_setelah DECIMAL(18,3) NOT NULL DEFAULT 0,
    keterangan TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by BIGINT UNSIGNED NULL,
    KEY idx_mutasi_stok_tanggal (tanggal_mutasi),
    KEY idx_mutasi_stok_barang (id_barang),
    KEY idx_mutasi_stok_gudang (id_gudang, id_lokasi_gudang),
    KEY idx_mutasi_stok_dokumen (jenis_dokumen, id_dokumen),
    CONSTRAINT fk_mutasi_stok_cabang
        FOREIGN KEY (id_cabang) REFERENCES cabang(id_cabang),
    CONSTRAINT fk_mutasi_stok_gudang
        FOREIGN KEY (id_gudang) REFERENCES gudang(id_gudang),
    CONSTRAINT fk_mutasi_stok_lokasi
        FOREIGN KEY (id_lokasi_gudang) REFERENCES lokasi_gudang(id_lokasi_gudang),
    CONSTRAINT fk_mutasi_stok_barang
        FOREIGN KEY (id_barang) REFERENCES barang(id_barang)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS stok_awal (
    id_stok_awal BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_cabang BIGINT UNSIGNED NOT NULL,
    id_gudang BIGINT UNSIGNED NOT NULL,
    nomor_stok_awal VARCHAR(50) NOT NULL,
    tanggal_stok_awal DATE NOT NULL,
    status_stok_awal ENUM('DRAF','DISETUJUI','DIBATALKAN') NOT NULL DEFAULT 'DRAF',
    keterangan TEXT NULL,
    id_pengguna_penyetuju BIGINT UNSIGNED NULL,
    tanggal_disetujui DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by BIGINT UNSIGNED NULL,
    updated_at DATETIME NULL,
    updated_by BIGINT UNSIGNED NULL,
    deleted_at DATETIME NULL,
    deleted_by BIGINT UNSIGNED NULL,
    UNIQUE KEY uk_stok_awal_nomor (id_cabang, nomor_stok_awal),
    KEY idx_stok_awal_tanggal (tanggal_stok_awal),
    CONSTRAINT fk_stok_awal_cabang
        FOREIGN KEY (id_cabang) REFERENCES cabang(id_cabang),
    CONSTRAINT fk_stok_awal_gudang
        FOREIGN KEY (id_gudang) REFERENCES gudang(id_gudang),
    CONSTRAINT fk_stok_awal_penyetuju
        FOREIGN KEY (id_pengguna_penyetuju) REFERENCES pengguna(id_pengguna)
        ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS stok_awal_detail (
    id_stok_awal_detail BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_stok_awal BIGINT UNSIGNED NOT NULL,
    id_barang_satuan BIGINT UNSIGNED NOT NULL,
    id_lokasi_gudang BIGINT UNSIGNED NOT NULL,
    nilai_konversi DECIMAL(18,6) NOT NULL DEFAULT 1,
    jumlah DECIMAL(18,3) NOT NULL DEFAULT 0,
    jumlah_dasar DECIMAL(18,3) NOT NULL DEFAULT 0,
    harga_pokok DECIMAL(18,4) NOT NULL DEFAULT 0,
    total_nilai DECIMAL(18,2) NOT NULL DEFAULT 0,
    nomor_lot VARCHAR(100) NULL,
    tanggal_kedaluwarsa DATE NULL,
    keterangan TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by BIGINT UNSIGNED NULL,
    updated_at DATETIME NULL,
    updated_by BIGINT UNSIGNED NULL,
    deleted_at DATETIME NULL,
    deleted_by BIGINT UNSIGNED NULL,
    UNIQUE KEY uk_stok_awal_detail (id_stok_awal, id_barang_satuan, id_lokasi_gudang, nomor_lot),
    KEY idx_stok_awal_detail_barang_satuan (id_barang_satuan),
    CONSTRAINT fk_stok_awal_detail_header
        FOREIGN KEY (id_stok_awal) REFERENCES stok_awal(id_stok_awal)
        ON DELETE CASCADE,
    CONSTRAINT fk_stok_awal_detail_barang_satuan
        FOREIGN KEY (id_barang_satuan) REFERENCES barang_satuan(id_barang_satuan),
    CONSTRAINT fk_stok_awal_detail_lokasi
        FOREIGN KEY (id_lokasi_gudang) REFERENCES lokasi_gudang(id_lokasi_gudang)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS transfer_stok (
    id_transfer_stok BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_cabang BIGINT UNSIGNED NOT NULL,
    id_gudang_asal BIGINT UNSIGNED NOT NULL,
    id_gudang_tujuan BIGINT UNSIGNED NOT NULL,
    nomor_transfer VARCHAR(50) NOT NULL,
    tanggal_transfer DATE NOT NULL,
    tanggal_kebutuhan DATE NULL,
    status_transfer ENUM('DRAF','DISETUJUI','DIKIRIM','DITERIMA','DIBATALKAN') NOT NULL DEFAULT 'DRAF',
    id_pengguna_peminta BIGINT UNSIGNED NULL,
    id_pengguna_penyetuju BIGINT UNSIGNED NULL,
    id_pengguna_pengirim BIGINT UNSIGNED NULL,
    id_pengguna_penerima BIGINT UNSIGNED NULL,
    tanggal_disetujui DATETIME NULL,
    tanggal_dikirim DATETIME NULL,
    tanggal_diterima DATETIME NULL,
    keterangan TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by BIGINT UNSIGNED NULL,
    updated_at DATETIME NULL,
    updated_by BIGINT UNSIGNED NULL,
    deleted_at DATETIME NULL,
    deleted_by BIGINT UNSIGNED NULL,
    UNIQUE KEY uk_transfer_stok_nomor (id_cabang, nomor_transfer),
    KEY idx_transfer_stok_tanggal (tanggal_transfer),
    KEY idx_transfer_stok_asal (id_gudang_asal),
    KEY idx_transfer_stok_tujuan (id_gudang_tujuan),
    CONSTRAINT fk_transfer_stok_cabang
        FOREIGN KEY (id_cabang) REFERENCES cabang(id_cabang),
    CONSTRAINT fk_transfer_stok_gudang_asal
        FOREIGN KEY (id_gudang_asal) REFERENCES gudang(id_gudang),
    CONSTRAINT fk_transfer_stok_gudang_tujuan
        FOREIGN KEY (id_gudang_tujuan) REFERENCES gudang(id_gudang),
    CONSTRAINT fk_transfer_stok_peminta
        FOREIGN KEY (id_pengguna_peminta) REFERENCES pengguna(id_pengguna)
        ON DELETE SET NULL,
    CONSTRAINT fk_transfer_stok_penyetuju
        FOREIGN KEY (id_pengguna_penyetuju) REFERENCES pengguna(id_pengguna)
        ON DELETE SET NULL,
    CONSTRAINT fk_transfer_stok_pengirim
        FOREIGN KEY (id_pengguna_pengirim) REFERENCES pengguna(id_pengguna)
        ON DELETE SET NULL,
    CONSTRAINT fk_transfer_stok_penerima
        FOREIGN KEY (id_pengguna_penerima) REFERENCES pengguna(id_pengguna)
        ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS transfer_stok_detail (
    id_transfer_stok_detail BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_transfer_stok BIGINT UNSIGNED NOT NULL,
    id_barang_satuan BIGINT UNSIGNED NOT NULL,
    id_lokasi_asal BIGINT UNSIGNED NOT NULL,
    id_lokasi_tujuan BIGINT UNSIGNED NOT NULL,
    nilai_konversi DECIMAL(18,6) NOT NULL DEFAULT 1,
    jumlah_diminta DECIMAL(18,3) NOT NULL DEFAULT 0,
    jumlah_dikirim DECIMAL(18,3) NOT NULL DEFAULT 0,
    jumlah_diterima DECIMAL(18,3) NOT NULL DEFAULT 0,
    jumlah_dasar_dikirim DECIMAL(18,3) NOT NULL DEFAULT 0,
    jumlah_dasar_diterima DECIMAL(18,3) NOT NULL DEFAULT 0,
    harga_pokok DECIMAL(18,4) NOT NULL DEFAULT 0,
    nomor_lot VARCHAR(100) NULL,
    tanggal_kedaluwarsa DATE NULL,
    keterangan TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by BIGINT UNSIGNED NULL,
    updated_at DATETIME NULL,
    updated_by BIGINT UNSIGNED NULL,
    deleted_at DATETIME NULL,
    deleted_by BIGINT UNSIGNED NULL,
    KEY idx_transfer_stok_detail_header (id_transfer_stok),
    KEY idx_transfer_stok_detail_barang_satuan (id_barang_satuan),
    CONSTRAINT fk_transfer_stok_detail_header
        FOREIGN KEY (id_transfer_stok) REFERENCES transfer_stok(id_transfer_stok)
        ON DELETE CASCADE,
    CONSTRAINT fk_transfer_stok_detail_barang_satuan
        FOREIGN KEY (id_barang_satuan) REFERENCES barang_satuan(id_barang_satuan),
    CONSTRAINT fk_transfer_stok_detail_lokasi_asal
        FOREIGN KEY (id_lokasi_asal) REFERENCES lokasi_gudang(id_lokasi_gudang),
    CONSTRAINT fk_transfer_stok_detail_lokasi_tujuan
        FOREIGN KEY (id_lokasi_tujuan) REFERENCES lokasi_gudang(id_lokasi_gudang)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS stok_opname (
    id_stok_opname BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_cabang BIGINT UNSIGNED NOT NULL,
    id_gudang BIGINT UNSIGNED NOT NULL,
    nomor_stok_opname VARCHAR(50) NOT NULL,
    tanggal_stok_opname DATE NOT NULL,
    status_stok_opname ENUM('DRAF','PROSES','SELESAI','DISETUJUI','DIBATALKAN') NOT NULL DEFAULT 'DRAF',
    id_pengguna_penanggung_jawab BIGINT UNSIGNED NULL,
    id_pengguna_penyetuju BIGINT UNSIGNED NULL,
    tanggal_disetujui DATETIME NULL,
    keterangan TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by BIGINT UNSIGNED NULL,
    updated_at DATETIME NULL,
    updated_by BIGINT UNSIGNED NULL,
    deleted_at DATETIME NULL,
    deleted_by BIGINT UNSIGNED NULL,
    UNIQUE KEY uk_stok_opname_nomor (id_cabang, nomor_stok_opname),
    KEY idx_stok_opname_tanggal (tanggal_stok_opname),
    CONSTRAINT fk_stok_opname_cabang
        FOREIGN KEY (id_cabang) REFERENCES cabang(id_cabang),
    CONSTRAINT fk_stok_opname_gudang
        FOREIGN KEY (id_gudang) REFERENCES gudang(id_gudang),
    CONSTRAINT fk_stok_opname_penanggung_jawab
        FOREIGN KEY (id_pengguna_penanggung_jawab) REFERENCES pengguna(id_pengguna)
        ON DELETE SET NULL,
    CONSTRAINT fk_stok_opname_penyetuju
        FOREIGN KEY (id_pengguna_penyetuju) REFERENCES pengguna(id_pengguna)
        ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS stok_opname_detail (
    id_stok_opname_detail BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_stok_opname BIGINT UNSIGNED NOT NULL,
    id_barang BIGINT UNSIGNED NOT NULL,
    id_lokasi_gudang BIGINT UNSIGNED NOT NULL,
    jumlah_sistem DECIMAL(18,3) NOT NULL DEFAULT 0,
    jumlah_fisik DECIMAL(18,3) NOT NULL DEFAULT 0,
    jumlah_selisih DECIMAL(18,3) NOT NULL DEFAULT 0,
    harga_pokok DECIMAL(18,4) NOT NULL DEFAULT 0,
    nilai_selisih DECIMAL(18,2) NOT NULL DEFAULT 0,
    nomor_lot VARCHAR(100) NULL,
    tanggal_kedaluwarsa DATE NULL,
    keterangan TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by BIGINT UNSIGNED NULL,
    updated_at DATETIME NULL,
    updated_by BIGINT UNSIGNED NULL,
    deleted_at DATETIME NULL,
    deleted_by BIGINT UNSIGNED NULL,
    UNIQUE KEY uk_stok_opname_detail (id_stok_opname, id_barang, id_lokasi_gudang, nomor_lot),
    KEY idx_stok_opname_detail_barang (id_barang),
    CONSTRAINT fk_stok_opname_detail_header
        FOREIGN KEY (id_stok_opname) REFERENCES stok_opname(id_stok_opname)
        ON DELETE CASCADE,
    CONSTRAINT fk_stok_opname_detail_barang
        FOREIGN KEY (id_barang) REFERENCES barang(id_barang),
    CONSTRAINT fk_stok_opname_detail_lokasi
        FOREIGN KEY (id_lokasi_gudang) REFERENCES lokasi_gudang(id_lokasi_gudang)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS penyesuaian_stok (
    id_penyesuaian_stok BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_cabang BIGINT UNSIGNED NOT NULL,
    id_gudang BIGINT UNSIGNED NOT NULL,
    id_stok_opname BIGINT UNSIGNED NULL,
    nomor_penyesuaian VARCHAR(50) NOT NULL,
    tanggal_penyesuaian DATE NOT NULL,
    alasan_penyesuaian VARCHAR(255) NOT NULL,
    status_penyesuaian ENUM('DRAF','DISETUJUI','DIBATALKAN') NOT NULL DEFAULT 'DRAF',
    id_pengguna_penyetuju BIGINT UNSIGNED NULL,
    tanggal_disetujui DATETIME NULL,
    total_nilai DECIMAL(18,2) NOT NULL DEFAULT 0,
    keterangan TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by BIGINT UNSIGNED NULL,
    updated_at DATETIME NULL,
    updated_by BIGINT UNSIGNED NULL,
    deleted_at DATETIME NULL,
    deleted_by BIGINT UNSIGNED NULL,
    UNIQUE KEY uk_penyesuaian_stok_nomor (id_cabang, nomor_penyesuaian),
    KEY idx_penyesuaian_stok_tanggal (tanggal_penyesuaian),
    CONSTRAINT fk_penyesuaian_stok_cabang
        FOREIGN KEY (id_cabang) REFERENCES cabang(id_cabang),
    CONSTRAINT fk_penyesuaian_stok_gudang
        FOREIGN KEY (id_gudang) REFERENCES gudang(id_gudang),
    CONSTRAINT fk_penyesuaian_stok_opname
        FOREIGN KEY (id_stok_opname) REFERENCES stok_opname(id_stok_opname)
        ON DELETE SET NULL,
    CONSTRAINT fk_penyesuaian_stok_penyetuju
        FOREIGN KEY (id_pengguna_penyetuju) REFERENCES pengguna(id_pengguna)
        ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS penyesuaian_stok_detail (
    id_penyesuaian_stok_detail BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_penyesuaian_stok BIGINT UNSIGNED NOT NULL,
    id_barang BIGINT UNSIGNED NOT NULL,
    id_lokasi_gudang BIGINT UNSIGNED NOT NULL,
    jenis_penyesuaian ENUM('TAMBAH','KURANG') NOT NULL,
    jumlah_dasar DECIMAL(18,3) NOT NULL DEFAULT 0,
    harga_pokok DECIMAL(18,4) NOT NULL DEFAULT 0,
    total_nilai DECIMAL(18,2) NOT NULL DEFAULT 0,
    nomor_lot VARCHAR(100) NULL,
    tanggal_kedaluwarsa DATE NULL,
    keterangan TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by BIGINT UNSIGNED NULL,
    updated_at DATETIME NULL,
    updated_by BIGINT UNSIGNED NULL,
    deleted_at DATETIME NULL,
    deleted_by BIGINT UNSIGNED NULL,
    KEY idx_penyesuaian_stok_detail_header (id_penyesuaian_stok),
    KEY idx_penyesuaian_stok_detail_barang (id_barang),
    CONSTRAINT fk_penyesuaian_stok_detail_header
        FOREIGN KEY (id_penyesuaian_stok) REFERENCES penyesuaian_stok(id_penyesuaian_stok)
        ON DELETE CASCADE,
    CONSTRAINT fk_penyesuaian_stok_detail_barang
        FOREIGN KEY (id_barang) REFERENCES barang(id_barang),
    CONSTRAINT fk_penyesuaian_stok_detail_lokasi
        FOREIGN KEY (id_lokasi_gudang) REFERENCES lokasi_gudang(id_lokasi_gudang)
) ENGINE=InnoDB;

-- =========================================================
-- 5. PEMBELIAN DAN HUTANG PEMASOK
-- =========================================================

CREATE TABLE IF NOT EXISTS permintaan_pembelian (
    id_permintaan_pembelian BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_cabang BIGINT UNSIGNED NOT NULL,
    nomor_permintaan VARCHAR(50) NOT NULL,
    tanggal_permintaan DATE NOT NULL,
    tanggal_kebutuhan DATE NULL,
    tingkat_kepentingan ENUM('RENDAH','NORMAL','TINGGI','MENDESAK') NOT NULL DEFAULT 'NORMAL',
    status_permintaan ENUM('DRAF','DIAJUKAN','DISETUJUI','DITOLAK','DIPROSES','SELESAI','DIBATALKAN') NOT NULL DEFAULT 'DRAF',
    id_pengguna_peminta BIGINT UNSIGNED NULL,
    id_pengguna_penyetuju BIGINT UNSIGNED NULL,
    tanggal_disetujui DATETIME NULL,
    keterangan TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by BIGINT UNSIGNED NULL,
    updated_at DATETIME NULL,
    updated_by BIGINT UNSIGNED NULL,
    deleted_at DATETIME NULL,
    deleted_by BIGINT UNSIGNED NULL,
    UNIQUE KEY uk_permintaan_pembelian_nomor (id_cabang, nomor_permintaan),
    KEY idx_permintaan_pembelian_tanggal (tanggal_permintaan),
    CONSTRAINT fk_permintaan_pembelian_cabang
        FOREIGN KEY (id_cabang) REFERENCES cabang(id_cabang),
    CONSTRAINT fk_permintaan_pembelian_peminta
        FOREIGN KEY (id_pengguna_peminta) REFERENCES pengguna(id_pengguna)
        ON DELETE SET NULL,
    CONSTRAINT fk_permintaan_pembelian_penyetuju
        FOREIGN KEY (id_pengguna_penyetuju) REFERENCES pengguna(id_pengguna)
        ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS permintaan_pembelian_detail (
    id_permintaan_pembelian_detail BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_permintaan_pembelian BIGINT UNSIGNED NOT NULL,
    id_barang_satuan BIGINT UNSIGNED NOT NULL,
    nilai_konversi DECIMAL(18,6) NOT NULL DEFAULT 1,
    jumlah_diminta DECIMAL(18,3) NOT NULL DEFAULT 0,
    jumlah_dasar_diminta DECIMAL(18,3) NOT NULL DEFAULT 0,
    jumlah_dipesan DECIMAL(18,3) NOT NULL DEFAULT 0,
    perkiraan_harga DECIMAL(18,2) NOT NULL DEFAULT 0,
    keterangan TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by BIGINT UNSIGNED NULL,
    updated_at DATETIME NULL,
    updated_by BIGINT UNSIGNED NULL,
    deleted_at DATETIME NULL,
    deleted_by BIGINT UNSIGNED NULL,
    KEY idx_permintaan_pembelian_detail_header (id_permintaan_pembelian),
    KEY idx_permintaan_pembelian_detail_barang_satuan (id_barang_satuan),
    CONSTRAINT fk_permintaan_pembelian_detail_header
        FOREIGN KEY (id_permintaan_pembelian) REFERENCES permintaan_pembelian(id_permintaan_pembelian)
        ON DELETE CASCADE,
    CONSTRAINT fk_permintaan_pembelian_detail_barang_satuan
        FOREIGN KEY (id_barang_satuan) REFERENCES barang_satuan(id_barang_satuan)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS pesanan_pembelian (
    id_pesanan_pembelian BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_cabang BIGINT UNSIGNED NOT NULL,
    id_pemasok BIGINT UNSIGNED NOT NULL,
    nomor_pesanan VARCHAR(50) NOT NULL,
    tanggal_pesanan DATE NOT NULL,
    tanggal_perkiraan_tiba DATE NULL,
    status_pesanan ENUM('DRAF','DIAJUKAN','DISETUJUI','DIKIRIM_PEMASOK','DITERIMA_SEBAGIAN','DITERIMA','SELESAI','DIBATALKAN') NOT NULL DEFAULT 'DRAF',
    cara_pembayaran ENUM('TUNAI','TEMPO') NOT NULL DEFAULT 'TUNAI',
    lama_jatuh_tempo SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    alamat_pengiriman TEXT NULL,
    total_kotor DECIMAL(18,2) NOT NULL DEFAULT 0,
    total_potongan DECIMAL(18,2) NOT NULL DEFAULT 0,
    total_pajak DECIMAL(18,2) NOT NULL DEFAULT 0,
    biaya_pengiriman DECIMAL(18,2) NOT NULL DEFAULT 0,
    biaya_lain DECIMAL(18,2) NOT NULL DEFAULT 0,
    total_bersih DECIMAL(18,2) NOT NULL DEFAULT 0,
    id_pengguna_penyetuju BIGINT UNSIGNED NULL,
    tanggal_disetujui DATETIME NULL,
    keterangan TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by BIGINT UNSIGNED NULL,
    updated_at DATETIME NULL,
    updated_by BIGINT UNSIGNED NULL,
    deleted_at DATETIME NULL,
    deleted_by BIGINT UNSIGNED NULL,
    UNIQUE KEY uk_pesanan_pembelian_nomor (id_cabang, nomor_pesanan),
    KEY idx_pesanan_pembelian_pemasok (id_pemasok),
    KEY idx_pesanan_pembelian_tanggal (tanggal_pesanan),
    KEY idx_pesanan_pembelian_status (status_pesanan),
    CONSTRAINT fk_pesanan_pembelian_cabang
        FOREIGN KEY (id_cabang) REFERENCES cabang(id_cabang),
    CONSTRAINT fk_pesanan_pembelian_pemasok
        FOREIGN KEY (id_pemasok) REFERENCES pemasok(id_pemasok),
    CONSTRAINT fk_pesanan_pembelian_penyetuju
        FOREIGN KEY (id_pengguna_penyetuju) REFERENCES pengguna(id_pengguna)
        ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS pesanan_pembelian_detail (
    id_pesanan_pembelian_detail BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_pesanan_pembelian BIGINT UNSIGNED NOT NULL,
    id_permintaan_pembelian_detail BIGINT UNSIGNED NULL,
    id_barang_satuan BIGINT UNSIGNED NOT NULL,
    id_tarif_pajak BIGINT UNSIGNED NULL,
    nilai_konversi DECIMAL(18,6) NOT NULL DEFAULT 1,
    jumlah DECIMAL(18,3) NOT NULL DEFAULT 0,
    jumlah_dasar DECIMAL(18,3) NOT NULL DEFAULT 0,
    harga_satuan DECIMAL(18,2) NOT NULL DEFAULT 0,
    potongan_persen DECIMAL(8,4) NOT NULL DEFAULT 0,
    potongan_nilai DECIMAL(18,2) NOT NULL DEFAULT 0,
    pajak_persen DECIMAL(8,4) NOT NULL DEFAULT 0,
    pajak_nilai DECIMAL(18,2) NOT NULL DEFAULT 0,
    total_baris DECIMAL(18,2) NOT NULL DEFAULT 0,
    jumlah_diterima DECIMAL(18,3) NOT NULL DEFAULT 0,
    jumlah_difakturkan DECIMAL(18,3) NOT NULL DEFAULT 0,
    keterangan TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by BIGINT UNSIGNED NULL,
    updated_at DATETIME NULL,
    updated_by BIGINT UNSIGNED NULL,
    deleted_at DATETIME NULL,
    deleted_by BIGINT UNSIGNED NULL,
    KEY idx_pesanan_pembelian_detail_header (id_pesanan_pembelian),
    KEY idx_pesanan_pembelian_detail_permintaan (id_permintaan_pembelian_detail),
    KEY idx_pesanan_pembelian_detail_barang_satuan (id_barang_satuan),
    CONSTRAINT fk_pesanan_pembelian_detail_header
        FOREIGN KEY (id_pesanan_pembelian) REFERENCES pesanan_pembelian(id_pesanan_pembelian)
        ON DELETE CASCADE,
    CONSTRAINT fk_pesanan_pembelian_detail_permintaan
        FOREIGN KEY (id_permintaan_pembelian_detail) REFERENCES permintaan_pembelian_detail(id_permintaan_pembelian_detail)
        ON DELETE SET NULL,
    CONSTRAINT fk_pesanan_pembelian_detail_barang_satuan
        FOREIGN KEY (id_barang_satuan) REFERENCES barang_satuan(id_barang_satuan),
    CONSTRAINT fk_pesanan_pembelian_detail_pajak
        FOREIGN KEY (id_tarif_pajak) REFERENCES tarif_pajak(id_tarif_pajak)
        ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS penerimaan_barang (
    id_penerimaan_barang BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_cabang BIGINT UNSIGNED NOT NULL,
    id_gudang BIGINT UNSIGNED NOT NULL,
    id_pemasok BIGINT UNSIGNED NOT NULL,
    id_pesanan_pembelian BIGINT UNSIGNED NULL,
    nomor_penerimaan VARCHAR(50) NOT NULL,
    tanggal_penerimaan DATE NOT NULL,
    nomor_surat_jalan VARCHAR(100) NULL,
    tanggal_surat_jalan DATE NULL,
    status_penerimaan ENUM('DRAF','DITERIMA','DIBATALKAN') NOT NULL DEFAULT 'DRAF',
    id_pengguna_penerima BIGINT UNSIGNED NULL,
    keterangan TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by BIGINT UNSIGNED NULL,
    updated_at DATETIME NULL,
    updated_by BIGINT UNSIGNED NULL,
    deleted_at DATETIME NULL,
    deleted_by BIGINT UNSIGNED NULL,
    UNIQUE KEY uk_penerimaan_barang_nomor (id_cabang, nomor_penerimaan),
    KEY idx_penerimaan_barang_pemasok (id_pemasok),
    KEY idx_penerimaan_barang_pesanan (id_pesanan_pembelian),
    KEY idx_penerimaan_barang_tanggal (tanggal_penerimaan),
    CONSTRAINT fk_penerimaan_barang_cabang
        FOREIGN KEY (id_cabang) REFERENCES cabang(id_cabang),
    CONSTRAINT fk_penerimaan_barang_gudang
        FOREIGN KEY (id_gudang) REFERENCES gudang(id_gudang),
    CONSTRAINT fk_penerimaan_barang_pemasok
        FOREIGN KEY (id_pemasok) REFERENCES pemasok(id_pemasok),
    CONSTRAINT fk_penerimaan_barang_pesanan
        FOREIGN KEY (id_pesanan_pembelian) REFERENCES pesanan_pembelian(id_pesanan_pembelian)
        ON DELETE SET NULL,
    CONSTRAINT fk_penerimaan_barang_penerima
        FOREIGN KEY (id_pengguna_penerima) REFERENCES pengguna(id_pengguna)
        ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS penerimaan_barang_detail (
    id_penerimaan_barang_detail BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_penerimaan_barang BIGINT UNSIGNED NOT NULL,
    id_pesanan_pembelian_detail BIGINT UNSIGNED NULL,
    id_barang_satuan BIGINT UNSIGNED NOT NULL,
    id_lokasi_gudang BIGINT UNSIGNED NOT NULL,
    nilai_konversi DECIMAL(18,6) NOT NULL DEFAULT 1,
    jumlah_diterima DECIMAL(18,3) NOT NULL DEFAULT 0,
    jumlah_dasar_diterima DECIMAL(18,3) NOT NULL DEFAULT 0,
    jumlah_ditolak DECIMAL(18,3) NOT NULL DEFAULT 0,
    harga_pokok DECIMAL(18,4) NOT NULL DEFAULT 0,
    nomor_lot VARCHAR(100) NULL,
    tanggal_produksi DATE NULL,
    tanggal_kedaluwarsa DATE NULL,
    keterangan TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by BIGINT UNSIGNED NULL,
    updated_at DATETIME NULL,
    updated_by BIGINT UNSIGNED NULL,
    deleted_at DATETIME NULL,
    deleted_by BIGINT UNSIGNED NULL,
    KEY idx_penerimaan_barang_detail_header (id_penerimaan_barang),
    KEY idx_penerimaan_barang_detail_pesanan (id_pesanan_pembelian_detail),
    KEY idx_penerimaan_barang_detail_barang_satuan (id_barang_satuan),
    CONSTRAINT fk_penerimaan_barang_detail_header
        FOREIGN KEY (id_penerimaan_barang) REFERENCES penerimaan_barang(id_penerimaan_barang)
        ON DELETE CASCADE,
    CONSTRAINT fk_penerimaan_barang_detail_pesanan
        FOREIGN KEY (id_pesanan_pembelian_detail) REFERENCES pesanan_pembelian_detail(id_pesanan_pembelian_detail)
        ON DELETE SET NULL,
    CONSTRAINT fk_penerimaan_barang_detail_barang_satuan
        FOREIGN KEY (id_barang_satuan) REFERENCES barang_satuan(id_barang_satuan),
    CONSTRAINT fk_penerimaan_barang_detail_lokasi
        FOREIGN KEY (id_lokasi_gudang) REFERENCES lokasi_gudang(id_lokasi_gudang)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS faktur_pembelian (
    id_faktur_pembelian BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_cabang BIGINT UNSIGNED NOT NULL,
    id_pemasok BIGINT UNSIGNED NOT NULL,
    id_pesanan_pembelian BIGINT UNSIGNED NULL,
    id_penerimaan_barang BIGINT UNSIGNED NULL,
    nomor_faktur_internal VARCHAR(50) NOT NULL,
    nomor_faktur_pemasok VARCHAR(100) NOT NULL,
    tanggal_faktur DATE NOT NULL,
    tanggal_jatuh_tempo DATE NULL,
    cara_pembayaran ENUM('TUNAI','TEMPO') NOT NULL DEFAULT 'TUNAI',
    status_faktur ENUM('DRAF','DISETUJUI','SEBAGIAN_DIBAYAR','LUNAS','DIBATALKAN') NOT NULL DEFAULT 'DRAF',
    total_kotor DECIMAL(18,2) NOT NULL DEFAULT 0,
    total_potongan DECIMAL(18,2) NOT NULL DEFAULT 0,
    total_pajak DECIMAL(18,2) NOT NULL DEFAULT 0,
    biaya_pengiriman DECIMAL(18,2) NOT NULL DEFAULT 0,
    biaya_lain DECIMAL(18,2) NOT NULL DEFAULT 0,
    pembulatan DECIMAL(18,2) NOT NULL DEFAULT 0,
    total_bersih DECIMAL(18,2) NOT NULL DEFAULT 0,
    total_dibayar DECIMAL(18,2) NOT NULL DEFAULT 0,
    sisa_hutang DECIMAL(18,2) NOT NULL DEFAULT 0,
    id_pengguna_penyetuju BIGINT UNSIGNED NULL,
    tanggal_disetujui DATETIME NULL,
    keterangan TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by BIGINT UNSIGNED NULL,
    updated_at DATETIME NULL,
    updated_by BIGINT UNSIGNED NULL,
    deleted_at DATETIME NULL,
    deleted_by BIGINT UNSIGNED NULL,
    UNIQUE KEY uk_faktur_pembelian_internal (id_cabang, nomor_faktur_internal),
    UNIQUE KEY uk_faktur_pembelian_pemasok (id_pemasok, nomor_faktur_pemasok),
    KEY idx_faktur_pembelian_tanggal (tanggal_faktur),
    KEY idx_faktur_pembelian_jatuh_tempo (tanggal_jatuh_tempo),
    KEY idx_faktur_pembelian_status (status_faktur),
    CONSTRAINT fk_faktur_pembelian_cabang
        FOREIGN KEY (id_cabang) REFERENCES cabang(id_cabang),
    CONSTRAINT fk_faktur_pembelian_pemasok
        FOREIGN KEY (id_pemasok) REFERENCES pemasok(id_pemasok),
    CONSTRAINT fk_faktur_pembelian_pesanan
        FOREIGN KEY (id_pesanan_pembelian) REFERENCES pesanan_pembelian(id_pesanan_pembelian)
        ON DELETE SET NULL,
    CONSTRAINT fk_faktur_pembelian_penerimaan
        FOREIGN KEY (id_penerimaan_barang) REFERENCES penerimaan_barang(id_penerimaan_barang)
        ON DELETE SET NULL,
    CONSTRAINT fk_faktur_pembelian_penyetuju
        FOREIGN KEY (id_pengguna_penyetuju) REFERENCES pengguna(id_pengguna)
        ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS faktur_pembelian_detail (
    id_faktur_pembelian_detail BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_faktur_pembelian BIGINT UNSIGNED NOT NULL,
    id_penerimaan_barang_detail BIGINT UNSIGNED NULL,
    id_barang_satuan BIGINT UNSIGNED NOT NULL,
    id_tarif_pajak BIGINT UNSIGNED NULL,
    nilai_konversi DECIMAL(18,6) NOT NULL DEFAULT 1,
    jumlah DECIMAL(18,3) NOT NULL DEFAULT 0,
    jumlah_dasar DECIMAL(18,3) NOT NULL DEFAULT 0,
    harga_satuan DECIMAL(18,2) NOT NULL DEFAULT 0,
    potongan_persen DECIMAL(8,4) NOT NULL DEFAULT 0,
    potongan_nilai DECIMAL(18,2) NOT NULL DEFAULT 0,
    pajak_persen DECIMAL(8,4) NOT NULL DEFAULT 0,
    pajak_nilai DECIMAL(18,2) NOT NULL DEFAULT 0,
    total_baris DECIMAL(18,2) NOT NULL DEFAULT 0,
    keterangan TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by BIGINT UNSIGNED NULL,
    updated_at DATETIME NULL,
    updated_by BIGINT UNSIGNED NULL,
    deleted_at DATETIME NULL,
    deleted_by BIGINT UNSIGNED NULL,
    KEY idx_faktur_pembelian_detail_header (id_faktur_pembelian),
    KEY idx_faktur_pembelian_detail_penerimaan (id_penerimaan_barang_detail),
    KEY idx_faktur_pembelian_detail_barang_satuan (id_barang_satuan),
    CONSTRAINT fk_faktur_pembelian_detail_header
        FOREIGN KEY (id_faktur_pembelian) REFERENCES faktur_pembelian(id_faktur_pembelian)
        ON DELETE CASCADE,
    CONSTRAINT fk_faktur_pembelian_detail_penerimaan
        FOREIGN KEY (id_penerimaan_barang_detail) REFERENCES penerimaan_barang_detail(id_penerimaan_barang_detail)
        ON DELETE SET NULL,
    CONSTRAINT fk_faktur_pembelian_detail_barang_satuan
        FOREIGN KEY (id_barang_satuan) REFERENCES barang_satuan(id_barang_satuan),
    CONSTRAINT fk_faktur_pembelian_detail_pajak
        FOREIGN KEY (id_tarif_pajak) REFERENCES tarif_pajak(id_tarif_pajak)
        ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS hutang_pemasok (
    id_hutang_pemasok BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_cabang BIGINT UNSIGNED NOT NULL,
    id_pemasok BIGINT UNSIGNED NOT NULL,
    id_faktur_pembelian BIGINT UNSIGNED NOT NULL,
    tanggal_hutang DATE NOT NULL,
    tanggal_jatuh_tempo DATE NULL,
    nilai_awal DECIMAL(18,2) NOT NULL DEFAULT 0,
    nilai_pembayaran DECIMAL(18,2) NOT NULL DEFAULT 0,
    nilai_retur DECIMAL(18,2) NOT NULL DEFAULT 0,
    nilai_penyesuaian DECIMAL(18,2) NOT NULL DEFAULT 0,
    sisa_hutang DECIMAL(18,2) NOT NULL DEFAULT 0,
    status_hutang ENUM('BELUM_LUNAS','SEBAGIAN','LUNAS','DIBATALKAN') NOT NULL DEFAULT 'BELUM_LUNAS',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by BIGINT UNSIGNED NULL,
    updated_at DATETIME NULL,
    updated_by BIGINT UNSIGNED NULL,
    UNIQUE KEY uk_hutang_pemasok_faktur (id_faktur_pembelian),
    KEY idx_hutang_pemasok_pemasok (id_pemasok),
    KEY idx_hutang_pemasok_jatuh_tempo (tanggal_jatuh_tempo),
    CONSTRAINT fk_hutang_pemasok_cabang
        FOREIGN KEY (id_cabang) REFERENCES cabang(id_cabang),
    CONSTRAINT fk_hutang_pemasok_pemasok
        FOREIGN KEY (id_pemasok) REFERENCES pemasok(id_pemasok),
    CONSTRAINT fk_hutang_pemasok_faktur
        FOREIGN KEY (id_faktur_pembelian) REFERENCES faktur_pembelian(id_faktur_pembelian)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS pembayaran_hutang (
    id_pembayaran_hutang BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_cabang BIGINT UNSIGNED NOT NULL,
    id_pemasok BIGINT UNSIGNED NOT NULL,
    id_kas_bank BIGINT UNSIGNED NOT NULL,
    id_metode_pembayaran BIGINT UNSIGNED NOT NULL,
    nomor_pembayaran VARCHAR(50) NOT NULL,
    tanggal_pembayaran DATE NOT NULL,
    nomor_bukti VARCHAR(100) NULL,
    total_pembayaran DECIMAL(18,2) NOT NULL DEFAULT 0,
    biaya_pembayaran DECIMAL(18,2) NOT NULL DEFAULT 0,
    status_pembayaran ENUM('DRAF','DISETUJUI','DIBATALKAN') NOT NULL DEFAULT 'DRAF',
    id_pengguna_penyetuju BIGINT UNSIGNED NULL,
    tanggal_disetujui DATETIME NULL,
    keterangan TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by BIGINT UNSIGNED NULL,
    updated_at DATETIME NULL,
    updated_by BIGINT UNSIGNED NULL,
    deleted_at DATETIME NULL,
    deleted_by BIGINT UNSIGNED NULL,
    UNIQUE KEY uk_pembayaran_hutang_nomor (id_cabang, nomor_pembayaran),
    KEY idx_pembayaran_hutang_pemasok (id_pemasok),
    KEY idx_pembayaran_hutang_tanggal (tanggal_pembayaran),
    CONSTRAINT fk_pembayaran_hutang_cabang
        FOREIGN KEY (id_cabang) REFERENCES cabang(id_cabang),
    CONSTRAINT fk_pembayaran_hutang_pemasok
        FOREIGN KEY (id_pemasok) REFERENCES pemasok(id_pemasok),
    CONSTRAINT fk_pembayaran_hutang_kas_bank
        FOREIGN KEY (id_kas_bank) REFERENCES kas_bank(id_kas_bank),
    CONSTRAINT fk_pembayaran_hutang_metode
        FOREIGN KEY (id_metode_pembayaran) REFERENCES metode_pembayaran(id_metode_pembayaran),
    CONSTRAINT fk_pembayaran_hutang_penyetuju
        FOREIGN KEY (id_pengguna_penyetuju) REFERENCES pengguna(id_pengguna)
        ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS pembayaran_hutang_detail (
    id_pembayaran_hutang_detail BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_pembayaran_hutang BIGINT UNSIGNED NOT NULL,
    id_hutang_pemasok BIGINT UNSIGNED NOT NULL,
    nilai_dialokasikan DECIMAL(18,2) NOT NULL DEFAULT 0,
    potongan_pembayaran DECIMAL(18,2) NOT NULL DEFAULT 0,
    keterangan TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by BIGINT UNSIGNED NULL,
    UNIQUE KEY uk_pembayaran_hutang_detail (id_pembayaran_hutang, id_hutang_pemasok),
    KEY idx_pembayaran_hutang_detail_hutang (id_hutang_pemasok),
    CONSTRAINT fk_pembayaran_hutang_detail_header
        FOREIGN KEY (id_pembayaran_hutang) REFERENCES pembayaran_hutang(id_pembayaran_hutang)
        ON DELETE CASCADE,
    CONSTRAINT fk_pembayaran_hutang_detail_hutang
        FOREIGN KEY (id_hutang_pemasok) REFERENCES hutang_pemasok(id_hutang_pemasok)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS retur_pembelian (
    id_retur_pembelian BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_cabang BIGINT UNSIGNED NOT NULL,
    id_pemasok BIGINT UNSIGNED NOT NULL,
    id_faktur_pembelian BIGINT UNSIGNED NULL,
    id_gudang BIGINT UNSIGNED NOT NULL,
    nomor_retur VARCHAR(50) NOT NULL,
    tanggal_retur DATE NOT NULL,
    alasan_retur TEXT NOT NULL,
    cara_pengembalian_dana ENUM('POTONG_HUTANG','TUNAI','TRANSFER','PENGGANTI_BARANG') NOT NULL DEFAULT 'POTONG_HUTANG',
    id_kas_bank BIGINT UNSIGNED NULL,
    status_retur ENUM('DRAF','DISETUJUI','DIKIRIM','SELESAI','DIBATALKAN') NOT NULL DEFAULT 'DRAF',
    total_retur DECIMAL(18,2) NOT NULL DEFAULT 0,
    id_pengguna_penyetuju BIGINT UNSIGNED NULL,
    tanggal_disetujui DATETIME NULL,
    keterangan TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by BIGINT UNSIGNED NULL,
    updated_at DATETIME NULL,
    updated_by BIGINT UNSIGNED NULL,
    deleted_at DATETIME NULL,
    deleted_by BIGINT UNSIGNED NULL,
    UNIQUE KEY uk_retur_pembelian_nomor (id_cabang, nomor_retur),
    KEY idx_retur_pembelian_pemasok (id_pemasok),
    KEY idx_retur_pembelian_faktur (id_faktur_pembelian),
    KEY idx_retur_pembelian_tanggal (tanggal_retur),
    CONSTRAINT fk_retur_pembelian_cabang
        FOREIGN KEY (id_cabang) REFERENCES cabang(id_cabang),
    CONSTRAINT fk_retur_pembelian_pemasok
        FOREIGN KEY (id_pemasok) REFERENCES pemasok(id_pemasok),
    CONSTRAINT fk_retur_pembelian_faktur
        FOREIGN KEY (id_faktur_pembelian) REFERENCES faktur_pembelian(id_faktur_pembelian)
        ON DELETE SET NULL,
    CONSTRAINT fk_retur_pembelian_gudang
        FOREIGN KEY (id_gudang) REFERENCES gudang(id_gudang),
    CONSTRAINT fk_retur_pembelian_kas_bank
        FOREIGN KEY (id_kas_bank) REFERENCES kas_bank(id_kas_bank)
        ON DELETE SET NULL,
    CONSTRAINT fk_retur_pembelian_penyetuju
        FOREIGN KEY (id_pengguna_penyetuju) REFERENCES pengguna(id_pengguna)
        ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS retur_pembelian_detail (
    id_retur_pembelian_detail BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_retur_pembelian BIGINT UNSIGNED NOT NULL,
    id_faktur_pembelian_detail BIGINT UNSIGNED NULL,
    id_barang_satuan BIGINT UNSIGNED NOT NULL,
    id_lokasi_gudang BIGINT UNSIGNED NOT NULL,
    nilai_konversi DECIMAL(18,6) NOT NULL DEFAULT 1,
    jumlah DECIMAL(18,3) NOT NULL DEFAULT 0,
    jumlah_dasar DECIMAL(18,3) NOT NULL DEFAULT 0,
    harga_satuan DECIMAL(18,2) NOT NULL DEFAULT 0,
    total_baris DECIMAL(18,2) NOT NULL DEFAULT 0,
    nomor_lot VARCHAR(100) NULL,
    tanggal_kedaluwarsa DATE NULL,
    kondisi_barang ENUM('BAIK','RUSAK','SALAH_KIRIM','KEDALUWARSA','LAINNYA') NOT NULL DEFAULT 'BAIK',
    keterangan TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by BIGINT UNSIGNED NULL,
    updated_at DATETIME NULL,
    updated_by BIGINT UNSIGNED NULL,
    deleted_at DATETIME NULL,
    deleted_by BIGINT UNSIGNED NULL,
    KEY idx_retur_pembelian_detail_header (id_retur_pembelian),
    KEY idx_retur_pembelian_detail_faktur (id_faktur_pembelian_detail),
    KEY idx_retur_pembelian_detail_barang_satuan (id_barang_satuan),
    CONSTRAINT fk_retur_pembelian_detail_header
        FOREIGN KEY (id_retur_pembelian) REFERENCES retur_pembelian(id_retur_pembelian)
        ON DELETE CASCADE,
    CONSTRAINT fk_retur_pembelian_detail_faktur
        FOREIGN KEY (id_faktur_pembelian_detail) REFERENCES faktur_pembelian_detail(id_faktur_pembelian_detail)
        ON DELETE SET NULL,
    CONSTRAINT fk_retur_pembelian_detail_barang_satuan
        FOREIGN KEY (id_barang_satuan) REFERENCES barang_satuan(id_barang_satuan),
    CONSTRAINT fk_retur_pembelian_detail_lokasi
        FOREIGN KEY (id_lokasi_gudang) REFERENCES lokasi_gudang(id_lokasi_gudang)
) ENGINE=InnoDB;

-- =========================================================
-- 6. PENAWARAN, PESANAN, PENJUALAN, PIUTANG, DAN PENGIRIMAN
-- =========================================================

CREATE TABLE IF NOT EXISTS penawaran_penjualan (
    id_penawaran_penjualan BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_cabang BIGINT UNSIGNED NOT NULL,
    id_pelanggan BIGINT UNSIGNED NULL,
    nomor_penawaran VARCHAR(50) NOT NULL,
    tanggal_penawaran DATE NOT NULL,
    berlaku_sampai DATE NULL,
    status_penawaran ENUM('DRAF','DIKIRIM','DISETUJUI_PELANGGAN','DITOLAK','KEDALUWARSA','MENJADI_PESANAN','DIBATALKAN') NOT NULL DEFAULT 'DRAF',
    alamat_penagihan TEXT NULL,
    alamat_pengiriman TEXT NULL,
    total_kotor DECIMAL(18,2) NOT NULL DEFAULT 0,
    total_potongan DECIMAL(18,2) NOT NULL DEFAULT 0,
    total_pajak DECIMAL(18,2) NOT NULL DEFAULT 0,
    biaya_pengiriman DECIMAL(18,2) NOT NULL DEFAULT 0,
    total_bersih DECIMAL(18,2) NOT NULL DEFAULT 0,
    syarat_ketentuan TEXT NULL,
    keterangan TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by BIGINT UNSIGNED NULL,
    updated_at DATETIME NULL,
    updated_by BIGINT UNSIGNED NULL,
    deleted_at DATETIME NULL,
    deleted_by BIGINT UNSIGNED NULL,
    UNIQUE KEY uk_penawaran_penjualan_nomor (id_cabang, nomor_penawaran),
    KEY idx_penawaran_penjualan_pelanggan (id_pelanggan),
    KEY idx_penawaran_penjualan_tanggal (tanggal_penawaran),
    CONSTRAINT fk_penawaran_penjualan_cabang
        FOREIGN KEY (id_cabang) REFERENCES cabang(id_cabang),
    CONSTRAINT fk_penawaran_penjualan_pelanggan
        FOREIGN KEY (id_pelanggan) REFERENCES pelanggan(id_pelanggan)
        ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS penawaran_penjualan_detail (
    id_penawaran_penjualan_detail BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_penawaran_penjualan BIGINT UNSIGNED NOT NULL,
    id_barang_satuan BIGINT UNSIGNED NOT NULL,
    id_tarif_pajak BIGINT UNSIGNED NULL,
    nilai_konversi DECIMAL(18,6) NOT NULL DEFAULT 1,
    jumlah DECIMAL(18,3) NOT NULL DEFAULT 0,
    jumlah_dasar DECIMAL(18,3) NOT NULL DEFAULT 0,
    harga_satuan DECIMAL(18,2) NOT NULL DEFAULT 0,
    potongan_persen DECIMAL(8,4) NOT NULL DEFAULT 0,
    potongan_nilai DECIMAL(18,2) NOT NULL DEFAULT 0,
    pajak_persen DECIMAL(8,4) NOT NULL DEFAULT 0,
    pajak_nilai DECIMAL(18,2) NOT NULL DEFAULT 0,
    total_baris DECIMAL(18,2) NOT NULL DEFAULT 0,
    keterangan TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by BIGINT UNSIGNED NULL,
    updated_at DATETIME NULL,
    updated_by BIGINT UNSIGNED NULL,
    deleted_at DATETIME NULL,
    deleted_by BIGINT UNSIGNED NULL,
    KEY idx_penawaran_penjualan_detail_header (id_penawaran_penjualan),
    KEY idx_penawaran_penjualan_detail_barang_satuan (id_barang_satuan),
    CONSTRAINT fk_penawaran_penjualan_detail_header
        FOREIGN KEY (id_penawaran_penjualan) REFERENCES penawaran_penjualan(id_penawaran_penjualan)
        ON DELETE CASCADE,
    CONSTRAINT fk_penawaran_penjualan_detail_barang_satuan
        FOREIGN KEY (id_barang_satuan) REFERENCES barang_satuan(id_barang_satuan),
    CONSTRAINT fk_penawaran_penjualan_detail_pajak
        FOREIGN KEY (id_tarif_pajak) REFERENCES tarif_pajak(id_tarif_pajak)
        ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS pesanan_penjualan (
    id_pesanan_penjualan BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_cabang BIGINT UNSIGNED NOT NULL,
    id_pelanggan BIGINT UNSIGNED NOT NULL,
    id_penawaran_penjualan BIGINT UNSIGNED NULL,
    id_daftar_harga BIGINT UNSIGNED NULL,
    nomor_pesanan VARCHAR(50) NOT NULL,
    nomor_pesanan_pelanggan VARCHAR(100) NULL,
    tanggal_pesanan DATE NOT NULL,
    tanggal_rencana_pengiriman DATE NULL,
    sumber_pesanan ENUM('TOKO','TELEPON','WHATSAPP','SUREL','WEBSITE','TENAGA_PENJUAL','LAINNYA') NOT NULL DEFAULT 'TOKO',
    status_pesanan ENUM('DRAF','DISETUJUI','DIPROSES','SIAP_DIKIRIM','DIKIRIM_SEBAGIAN','DIKIRIM','SELESAI','DIBATALKAN') NOT NULL DEFAULT 'DRAF',
    cara_pembayaran ENUM('TUNAI','TEMPO') NOT NULL DEFAULT 'TUNAI',
    lama_jatuh_tempo SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    alamat_penagihan TEXT NULL,
    alamat_pengiriman TEXT NULL,
    nama_penerima VARCHAR(150) NULL,
    telepon_penerima VARCHAR(30) NULL,
    total_kotor DECIMAL(18,2) NOT NULL DEFAULT 0,
    total_potongan DECIMAL(18,2) NOT NULL DEFAULT 0,
    total_pajak DECIMAL(18,2) NOT NULL DEFAULT 0,
    biaya_pengiriman DECIMAL(18,2) NOT NULL DEFAULT 0,
    biaya_lain DECIMAL(18,2) NOT NULL DEFAULT 0,
    total_bersih DECIMAL(18,2) NOT NULL DEFAULT 0,
    uang_muka DECIMAL(18,2) NOT NULL DEFAULT 0,
    id_pegawai_penjual BIGINT UNSIGNED NULL,
    id_pengguna_penyetuju BIGINT UNSIGNED NULL,
    tanggal_disetujui DATETIME NULL,
    keterangan TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by BIGINT UNSIGNED NULL,
    updated_at DATETIME NULL,
    updated_by BIGINT UNSIGNED NULL,
    deleted_at DATETIME NULL,
    deleted_by BIGINT UNSIGNED NULL,
    UNIQUE KEY uk_pesanan_penjualan_nomor (id_cabang, nomor_pesanan),
    KEY idx_pesanan_penjualan_pelanggan (id_pelanggan),
    KEY idx_pesanan_penjualan_tanggal (tanggal_pesanan),
    KEY idx_pesanan_penjualan_status (status_pesanan),
    CONSTRAINT fk_pesanan_penjualan_cabang
        FOREIGN KEY (id_cabang) REFERENCES cabang(id_cabang),
    CONSTRAINT fk_pesanan_penjualan_pelanggan
        FOREIGN KEY (id_pelanggan) REFERENCES pelanggan(id_pelanggan),
    CONSTRAINT fk_pesanan_penjualan_penawaran
        FOREIGN KEY (id_penawaran_penjualan) REFERENCES penawaran_penjualan(id_penawaran_penjualan)
        ON DELETE SET NULL,
    CONSTRAINT fk_pesanan_penjualan_daftar_harga
        FOREIGN KEY (id_daftar_harga) REFERENCES daftar_harga(id_daftar_harga)
        ON DELETE SET NULL,
    CONSTRAINT fk_pesanan_penjualan_pegawai
        FOREIGN KEY (id_pegawai_penjual) REFERENCES pegawai(id_pegawai)
        ON DELETE SET NULL,
    CONSTRAINT fk_pesanan_penjualan_penyetuju
        FOREIGN KEY (id_pengguna_penyetuju) REFERENCES pengguna(id_pengguna)
        ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS pesanan_penjualan_detail (
    id_pesanan_penjualan_detail BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_pesanan_penjualan BIGINT UNSIGNED NOT NULL,
    id_penawaran_penjualan_detail BIGINT UNSIGNED NULL,
    id_barang_satuan BIGINT UNSIGNED NOT NULL,
    id_tarif_pajak BIGINT UNSIGNED NULL,
    nilai_konversi DECIMAL(18,6) NOT NULL DEFAULT 1,
    jumlah DECIMAL(18,3) NOT NULL DEFAULT 0,
    jumlah_dasar DECIMAL(18,3) NOT NULL DEFAULT 0,
    harga_satuan DECIMAL(18,2) NOT NULL DEFAULT 0,
    potongan_persen DECIMAL(8,4) NOT NULL DEFAULT 0,
    potongan_nilai DECIMAL(18,2) NOT NULL DEFAULT 0,
    pajak_persen DECIMAL(8,4) NOT NULL DEFAULT 0,
    pajak_nilai DECIMAL(18,2) NOT NULL DEFAULT 0,
    total_baris DECIMAL(18,2) NOT NULL DEFAULT 0,
    jumlah_dikirim DECIMAL(18,3) NOT NULL DEFAULT 0,
    jumlah_difakturkan DECIMAL(18,3) NOT NULL DEFAULT 0,
    keterangan TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by BIGINT UNSIGNED NULL,
    updated_at DATETIME NULL,
    updated_by BIGINT UNSIGNED NULL,
    deleted_at DATETIME NULL,
    deleted_by BIGINT UNSIGNED NULL,
    KEY idx_pesanan_penjualan_detail_header (id_pesanan_penjualan),
    KEY idx_pesanan_penjualan_detail_penawaran (id_penawaran_penjualan_detail),
    KEY idx_pesanan_penjualan_detail_barang_satuan (id_barang_satuan),
    CONSTRAINT fk_pesanan_penjualan_detail_header
        FOREIGN KEY (id_pesanan_penjualan) REFERENCES pesanan_penjualan(id_pesanan_penjualan)
        ON DELETE CASCADE,
    CONSTRAINT fk_pesanan_penjualan_detail_penawaran
        FOREIGN KEY (id_penawaran_penjualan_detail) REFERENCES penawaran_penjualan_detail(id_penawaran_penjualan_detail)
        ON DELETE SET NULL,
    CONSTRAINT fk_pesanan_penjualan_detail_barang_satuan
        FOREIGN KEY (id_barang_satuan) REFERENCES barang_satuan(id_barang_satuan),
    CONSTRAINT fk_pesanan_penjualan_detail_pajak
        FOREIGN KEY (id_tarif_pajak) REFERENCES tarif_pajak(id_tarif_pajak)
        ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS penjualan (
    id_penjualan BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_cabang BIGINT UNSIGNED NOT NULL,
    id_gudang BIGINT UNSIGNED NOT NULL,
    id_pelanggan BIGINT UNSIGNED NULL,
    id_pesanan_penjualan BIGINT UNSIGNED NULL,
    id_daftar_harga BIGINT UNSIGNED NULL,
    id_kas_bank BIGINT UNSIGNED NULL,
    id_metode_pembayaran BIGINT UNSIGNED NULL,
    nomor_penjualan VARCHAR(50) NOT NULL,
    tanggal_penjualan DATETIME NOT NULL,
    tanggal_jatuh_tempo DATE NULL,
    jenis_penjualan ENUM('TUNAI','TEMPO') NOT NULL DEFAULT 'TUNAI',
    status_penjualan ENUM('DRAF','DISETUJUI','SEBAGIAN_DIBAYAR','LUNAS','DIBATALKAN') NOT NULL DEFAULT 'DRAF',
    status_pengiriman ENUM('BELUM_DIKIRIM','SEBAGIAN','DIKIRIM','DIAMBIL_SENDIRI') NOT NULL DEFAULT 'BELUM_DIKIRIM',
    total_kotor DECIMAL(18,2) NOT NULL DEFAULT 0,
    total_potongan DECIMAL(18,2) NOT NULL DEFAULT 0,
    total_pajak DECIMAL(18,2) NOT NULL DEFAULT 0,
    biaya_pengiriman DECIMAL(18,2) NOT NULL DEFAULT 0,
    biaya_lain DECIMAL(18,2) NOT NULL DEFAULT 0,
    pembulatan DECIMAL(18,2) NOT NULL DEFAULT 0,
    total_bersih DECIMAL(18,2) NOT NULL DEFAULT 0,
    total_dibayar DECIMAL(18,2) NOT NULL DEFAULT 0,
    uang_kembali DECIMAL(18,2) NOT NULL DEFAULT 0,
    sisa_piutang DECIMAL(18,2) NOT NULL DEFAULT 0,
    id_pegawai_penjual BIGINT UNSIGNED NULL,
    id_pengguna_penyetuju BIGINT UNSIGNED NULL,
    tanggal_disetujui DATETIME NULL,
    keterangan TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by BIGINT UNSIGNED NULL,
    updated_at DATETIME NULL,
    updated_by BIGINT UNSIGNED NULL,
    deleted_at DATETIME NULL,
    deleted_by BIGINT UNSIGNED NULL,
    UNIQUE KEY uk_penjualan_nomor (id_cabang, nomor_penjualan),
    KEY idx_penjualan_pelanggan (id_pelanggan),
    KEY idx_penjualan_tanggal (tanggal_penjualan),
    KEY idx_penjualan_jatuh_tempo (tanggal_jatuh_tempo),
    KEY idx_penjualan_status (status_penjualan),
    CONSTRAINT fk_penjualan_cabang
        FOREIGN KEY (id_cabang) REFERENCES cabang(id_cabang),
    CONSTRAINT fk_penjualan_gudang
        FOREIGN KEY (id_gudang) REFERENCES gudang(id_gudang),
    CONSTRAINT fk_penjualan_pelanggan
        FOREIGN KEY (id_pelanggan) REFERENCES pelanggan(id_pelanggan)
        ON DELETE SET NULL,
    CONSTRAINT fk_penjualan_pesanan
        FOREIGN KEY (id_pesanan_penjualan) REFERENCES pesanan_penjualan(id_pesanan_penjualan)
        ON DELETE SET NULL,
    CONSTRAINT fk_penjualan_daftar_harga
        FOREIGN KEY (id_daftar_harga) REFERENCES daftar_harga(id_daftar_harga)
        ON DELETE SET NULL,
    CONSTRAINT fk_penjualan_kas_bank
        FOREIGN KEY (id_kas_bank) REFERENCES kas_bank(id_kas_bank)
        ON DELETE SET NULL,
    CONSTRAINT fk_penjualan_metode_pembayaran
        FOREIGN KEY (id_metode_pembayaran) REFERENCES metode_pembayaran(id_metode_pembayaran)
        ON DELETE SET NULL,
    CONSTRAINT fk_penjualan_pegawai
        FOREIGN KEY (id_pegawai_penjual) REFERENCES pegawai(id_pegawai)
        ON DELETE SET NULL,
    CONSTRAINT fk_penjualan_penyetuju
        FOREIGN KEY (id_pengguna_penyetuju) REFERENCES pengguna(id_pengguna)
        ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS penjualan_detail (
    id_penjualan_detail BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_penjualan BIGINT UNSIGNED NOT NULL,
    id_pesanan_penjualan_detail BIGINT UNSIGNED NULL,
    id_barang_satuan BIGINT UNSIGNED NOT NULL,
    id_lokasi_gudang BIGINT UNSIGNED NOT NULL,
    id_tarif_pajak BIGINT UNSIGNED NULL,
    nilai_konversi DECIMAL(18,6) NOT NULL DEFAULT 1,
    jumlah DECIMAL(18,3) NOT NULL DEFAULT 0,
    jumlah_dasar DECIMAL(18,3) NOT NULL DEFAULT 0,
    harga_satuan DECIMAL(18,2) NOT NULL DEFAULT 0,
    potongan_persen DECIMAL(8,4) NOT NULL DEFAULT 0,
    potongan_nilai DECIMAL(18,2) NOT NULL DEFAULT 0,
    pajak_persen DECIMAL(8,4) NOT NULL DEFAULT 0,
    pajak_nilai DECIMAL(18,2) NOT NULL DEFAULT 0,
    total_baris DECIMAL(18,2) NOT NULL DEFAULT 0,
    harga_pokok DECIMAL(18,4) NOT NULL DEFAULT 0,
    total_harga_pokok DECIMAL(18,2) NOT NULL DEFAULT 0,
    laba_kotor DECIMAL(18,2) NOT NULL DEFAULT 0,
    nomor_lot VARCHAR(100) NULL,
    tanggal_kedaluwarsa DATE NULL,
    keterangan TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by BIGINT UNSIGNED NULL,
    updated_at DATETIME NULL,
    updated_by BIGINT UNSIGNED NULL,
    deleted_at DATETIME NULL,
    deleted_by BIGINT UNSIGNED NULL,
    KEY idx_penjualan_detail_header (id_penjualan),
    KEY idx_penjualan_detail_pesanan (id_pesanan_penjualan_detail),
    KEY idx_penjualan_detail_barang_satuan (id_barang_satuan),
    KEY idx_penjualan_detail_lokasi (id_lokasi_gudang),
    CONSTRAINT fk_penjualan_detail_header
        FOREIGN KEY (id_penjualan) REFERENCES penjualan(id_penjualan)
        ON DELETE CASCADE,
    CONSTRAINT fk_penjualan_detail_pesanan
        FOREIGN KEY (id_pesanan_penjualan_detail) REFERENCES pesanan_penjualan_detail(id_pesanan_penjualan_detail)
        ON DELETE SET NULL,
    CONSTRAINT fk_penjualan_detail_barang_satuan
        FOREIGN KEY (id_barang_satuan) REFERENCES barang_satuan(id_barang_satuan),
    CONSTRAINT fk_penjualan_detail_lokasi
        FOREIGN KEY (id_lokasi_gudang) REFERENCES lokasi_gudang(id_lokasi_gudang),
    CONSTRAINT fk_penjualan_detail_pajak
        FOREIGN KEY (id_tarif_pajak) REFERENCES tarif_pajak(id_tarif_pajak)
        ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS piutang_pelanggan (
    id_piutang_pelanggan BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_cabang BIGINT UNSIGNED NOT NULL,
    id_pelanggan BIGINT UNSIGNED NOT NULL,
    id_penjualan BIGINT UNSIGNED NOT NULL,
    tanggal_piutang DATE NOT NULL,
    tanggal_jatuh_tempo DATE NULL,
    nilai_awal DECIMAL(18,2) NOT NULL DEFAULT 0,
    nilai_pembayaran DECIMAL(18,2) NOT NULL DEFAULT 0,
    nilai_retur DECIMAL(18,2) NOT NULL DEFAULT 0,
    nilai_penyesuaian DECIMAL(18,2) NOT NULL DEFAULT 0,
    sisa_piutang DECIMAL(18,2) NOT NULL DEFAULT 0,
    status_piutang ENUM('BELUM_LUNAS','SEBAGIAN','LUNAS','DIBATALKAN') NOT NULL DEFAULT 'BELUM_LUNAS',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by BIGINT UNSIGNED NULL,
    updated_at DATETIME NULL,
    updated_by BIGINT UNSIGNED NULL,
    UNIQUE KEY uk_piutang_pelanggan_penjualan (id_penjualan),
    KEY idx_piutang_pelanggan_pelanggan (id_pelanggan),
    KEY idx_piutang_pelanggan_jatuh_tempo (tanggal_jatuh_tempo),
    CONSTRAINT fk_piutang_pelanggan_cabang
        FOREIGN KEY (id_cabang) REFERENCES cabang(id_cabang),
    CONSTRAINT fk_piutang_pelanggan_pelanggan
        FOREIGN KEY (id_pelanggan) REFERENCES pelanggan(id_pelanggan),
    CONSTRAINT fk_piutang_pelanggan_penjualan
        FOREIGN KEY (id_penjualan) REFERENCES penjualan(id_penjualan)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS pembayaran_piutang (
    id_pembayaran_piutang BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_cabang BIGINT UNSIGNED NOT NULL,
    id_pelanggan BIGINT UNSIGNED NOT NULL,
    id_kas_bank BIGINT UNSIGNED NOT NULL,
    id_metode_pembayaran BIGINT UNSIGNED NOT NULL,
    nomor_pembayaran VARCHAR(50) NOT NULL,
    tanggal_pembayaran DATE NOT NULL,
    nomor_bukti VARCHAR(100) NULL,
    total_pembayaran DECIMAL(18,2) NOT NULL DEFAULT 0,
    biaya_pembayaran DECIMAL(18,2) NOT NULL DEFAULT 0,
    status_pembayaran ENUM('DRAF','DISETUJUI','DIBATALKAN') NOT NULL DEFAULT 'DRAF',
    id_pengguna_penyetuju BIGINT UNSIGNED NULL,
    tanggal_disetujui DATETIME NULL,
    keterangan TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by BIGINT UNSIGNED NULL,
    updated_at DATETIME NULL,
    updated_by BIGINT UNSIGNED NULL,
    deleted_at DATETIME NULL,
    deleted_by BIGINT UNSIGNED NULL,
    UNIQUE KEY uk_pembayaran_piutang_nomor (id_cabang, nomor_pembayaran),
    KEY idx_pembayaran_piutang_pelanggan (id_pelanggan),
    KEY idx_pembayaran_piutang_tanggal (tanggal_pembayaran),
    CONSTRAINT fk_pembayaran_piutang_cabang
        FOREIGN KEY (id_cabang) REFERENCES cabang(id_cabang),
    CONSTRAINT fk_pembayaran_piutang_pelanggan
        FOREIGN KEY (id_pelanggan) REFERENCES pelanggan(id_pelanggan),
    CONSTRAINT fk_pembayaran_piutang_kas_bank
        FOREIGN KEY (id_kas_bank) REFERENCES kas_bank(id_kas_bank),
    CONSTRAINT fk_pembayaran_piutang_metode
        FOREIGN KEY (id_metode_pembayaran) REFERENCES metode_pembayaran(id_metode_pembayaran),
    CONSTRAINT fk_pembayaran_piutang_penyetuju
        FOREIGN KEY (id_pengguna_penyetuju) REFERENCES pengguna(id_pengguna)
        ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS pembayaran_piutang_detail (
    id_pembayaran_piutang_detail BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_pembayaran_piutang BIGINT UNSIGNED NOT NULL,
    id_piutang_pelanggan BIGINT UNSIGNED NOT NULL,
    nilai_dialokasikan DECIMAL(18,2) NOT NULL DEFAULT 0,
    potongan_pembayaran DECIMAL(18,2) NOT NULL DEFAULT 0,
    keterangan TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by BIGINT UNSIGNED NULL,
    UNIQUE KEY uk_pembayaran_piutang_detail (id_pembayaran_piutang, id_piutang_pelanggan),
    KEY idx_pembayaran_piutang_detail_piutang (id_piutang_pelanggan),
    CONSTRAINT fk_pembayaran_piutang_detail_header
        FOREIGN KEY (id_pembayaran_piutang) REFERENCES pembayaran_piutang(id_pembayaran_piutang)
        ON DELETE CASCADE,
    CONSTRAINT fk_pembayaran_piutang_detail_piutang
        FOREIGN KEY (id_piutang_pelanggan) REFERENCES piutang_pelanggan(id_piutang_pelanggan)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS pengiriman (
    id_pengiriman BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_cabang BIGINT UNSIGNED NOT NULL,
    id_pesanan_penjualan BIGINT UNSIGNED NULL,
    id_penjualan BIGINT UNSIGNED NULL,
    id_armada BIGINT UNSIGNED NULL,
    id_pegawai_pengemudi BIGINT UNSIGNED NULL,
    nomor_pengiriman VARCHAR(50) NOT NULL,
    tanggal_pengiriman DATE NOT NULL,
    tanggal_rencana_tiba DATETIME NULL,
    tanggal_berangkat DATETIME NULL,
    tanggal_tiba DATETIME NULL,
    status_pengiriman ENUM('DRAF','DIJADWALKAN','DALAM_PERJALANAN','DITERIMA','GAGAL','DIBATALKAN') NOT NULL DEFAULT 'DRAF',
    nama_penerima VARCHAR(150) NULL,
    telepon_penerima VARCHAR(30) NULL,
    alamat_pengiriman TEXT NOT NULL,
    garis_lintang DECIMAL(10,7) NULL,
    garis_bujur DECIMAL(10,7) NULL,
    biaya_pengiriman DECIMAL(18,2) NOT NULL DEFAULT 0,
    bukti_penerimaan VARCHAR(255) NULL,
    catatan_penerima TEXT NULL,
    keterangan TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by BIGINT UNSIGNED NULL,
    updated_at DATETIME NULL,
    updated_by BIGINT UNSIGNED NULL,
    deleted_at DATETIME NULL,
    deleted_by BIGINT UNSIGNED NULL,
    UNIQUE KEY uk_pengiriman_nomor (id_cabang, nomor_pengiriman),
    KEY idx_pengiriman_tanggal (tanggal_pengiriman),
    KEY idx_pengiriman_status (status_pengiriman),
    KEY idx_pengiriman_penjualan (id_penjualan),
    CONSTRAINT fk_pengiriman_cabang
        FOREIGN KEY (id_cabang) REFERENCES cabang(id_cabang),
    CONSTRAINT fk_pengiriman_pesanan
        FOREIGN KEY (id_pesanan_penjualan) REFERENCES pesanan_penjualan(id_pesanan_penjualan)
        ON DELETE SET NULL,
    CONSTRAINT fk_pengiriman_penjualan
        FOREIGN KEY (id_penjualan) REFERENCES penjualan(id_penjualan)
        ON DELETE SET NULL,
    CONSTRAINT fk_pengiriman_armada
        FOREIGN KEY (id_armada) REFERENCES armada(id_armada)
        ON DELETE SET NULL,
    CONSTRAINT fk_pengiriman_pengemudi
        FOREIGN KEY (id_pegawai_pengemudi) REFERENCES pegawai(id_pegawai)
        ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS pengiriman_detail (
    id_pengiriman_detail BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_pengiriman BIGINT UNSIGNED NOT NULL,
    id_pesanan_penjualan_detail BIGINT UNSIGNED NULL,
    id_penjualan_detail BIGINT UNSIGNED NULL,
    id_barang_satuan BIGINT UNSIGNED NOT NULL,
    nilai_konversi DECIMAL(18,6) NOT NULL DEFAULT 1,
    jumlah_dikirim DECIMAL(18,3) NOT NULL DEFAULT 0,
    jumlah_dasar_dikirim DECIMAL(18,3) NOT NULL DEFAULT 0,
    jumlah_diterima DECIMAL(18,3) NOT NULL DEFAULT 0,
    jumlah_dasar_diterima DECIMAL(18,3) NOT NULL DEFAULT 0,
    keterangan TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by BIGINT UNSIGNED NULL,
    updated_at DATETIME NULL,
    updated_by BIGINT UNSIGNED NULL,
    deleted_at DATETIME NULL,
    deleted_by BIGINT UNSIGNED NULL,
    KEY idx_pengiriman_detail_header (id_pengiriman),
    KEY idx_pengiriman_detail_barang_satuan (id_barang_satuan),
    CONSTRAINT fk_pengiriman_detail_header
        FOREIGN KEY (id_pengiriman) REFERENCES pengiriman(id_pengiriman)
        ON DELETE CASCADE,
    CONSTRAINT fk_pengiriman_detail_pesanan
        FOREIGN KEY (id_pesanan_penjualan_detail) REFERENCES pesanan_penjualan_detail(id_pesanan_penjualan_detail)
        ON DELETE SET NULL,
    CONSTRAINT fk_pengiriman_detail_penjualan
        FOREIGN KEY (id_penjualan_detail) REFERENCES penjualan_detail(id_penjualan_detail)
        ON DELETE SET NULL,
    CONSTRAINT fk_pengiriman_detail_barang_satuan
        FOREIGN KEY (id_barang_satuan) REFERENCES barang_satuan(id_barang_satuan)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS retur_penjualan (
    id_retur_penjualan BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_cabang BIGINT UNSIGNED NOT NULL,
    id_pelanggan BIGINT UNSIGNED NULL,
    id_penjualan BIGINT UNSIGNED NOT NULL,
    id_gudang BIGINT UNSIGNED NOT NULL,
    nomor_retur VARCHAR(50) NOT NULL,
    tanggal_retur DATE NOT NULL,
    alasan_retur TEXT NOT NULL,
    cara_pengembalian_dana ENUM('POTONG_PIUTANG','TUNAI','TRANSFER','PENGGANTI_BARANG') NOT NULL DEFAULT 'POTONG_PIUTANG',
    id_kas_bank BIGINT UNSIGNED NULL,
    status_retur ENUM('DRAF','DISETUJUI','DITERIMA','SELESAI','DIBATALKAN') NOT NULL DEFAULT 'DRAF',
    total_retur DECIMAL(18,2) NOT NULL DEFAULT 0,
    id_pengguna_penyetuju BIGINT UNSIGNED NULL,
    tanggal_disetujui DATETIME NULL,
    keterangan TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by BIGINT UNSIGNED NULL,
    updated_at DATETIME NULL,
    updated_by BIGINT UNSIGNED NULL,
    deleted_at DATETIME NULL,
    deleted_by BIGINT UNSIGNED NULL,
    UNIQUE KEY uk_retur_penjualan_nomor (id_cabang, nomor_retur),
    KEY idx_retur_penjualan_pelanggan (id_pelanggan),
    KEY idx_retur_penjualan_penjualan (id_penjualan),
    KEY idx_retur_penjualan_tanggal (tanggal_retur),
    CONSTRAINT fk_retur_penjualan_cabang
        FOREIGN KEY (id_cabang) REFERENCES cabang(id_cabang),
    CONSTRAINT fk_retur_penjualan_pelanggan
        FOREIGN KEY (id_pelanggan) REFERENCES pelanggan(id_pelanggan)
        ON DELETE SET NULL,
    CONSTRAINT fk_retur_penjualan_penjualan
        FOREIGN KEY (id_penjualan) REFERENCES penjualan(id_penjualan),
    CONSTRAINT fk_retur_penjualan_gudang
        FOREIGN KEY (id_gudang) REFERENCES gudang(id_gudang),
    CONSTRAINT fk_retur_penjualan_kas_bank
        FOREIGN KEY (id_kas_bank) REFERENCES kas_bank(id_kas_bank)
        ON DELETE SET NULL,
    CONSTRAINT fk_retur_penjualan_penyetuju
        FOREIGN KEY (id_pengguna_penyetuju) REFERENCES pengguna(id_pengguna)
        ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS retur_penjualan_detail (
    id_retur_penjualan_detail BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_retur_penjualan BIGINT UNSIGNED NOT NULL,
    id_penjualan_detail BIGINT UNSIGNED NOT NULL,
    id_barang_satuan BIGINT UNSIGNED NOT NULL,
    id_lokasi_gudang BIGINT UNSIGNED NOT NULL,
    nilai_konversi DECIMAL(18,6) NOT NULL DEFAULT 1,
    jumlah DECIMAL(18,3) NOT NULL DEFAULT 0,
    jumlah_dasar DECIMAL(18,3) NOT NULL DEFAULT 0,
    harga_satuan DECIMAL(18,2) NOT NULL DEFAULT 0,
    total_baris DECIMAL(18,2) NOT NULL DEFAULT 0,
    nomor_lot VARCHAR(100) NULL,
    tanggal_kedaluwarsa DATE NULL,
    kondisi_barang ENUM('BAIK','RUSAK','CACAT','SALAH_KIRIM','LAINNYA') NOT NULL DEFAULT 'BAIK',
    bisa_dijual_kembali TINYINT(1) NOT NULL DEFAULT 1,
    keterangan TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by BIGINT UNSIGNED NULL,
    updated_at DATETIME NULL,
    updated_by BIGINT UNSIGNED NULL,
    deleted_at DATETIME NULL,
    deleted_by BIGINT UNSIGNED NULL,
    KEY idx_retur_penjualan_detail_header (id_retur_penjualan),
    KEY idx_retur_penjualan_detail_penjualan (id_penjualan_detail),
    KEY idx_retur_penjualan_detail_barang_satuan (id_barang_satuan),
    CONSTRAINT fk_retur_penjualan_detail_header
        FOREIGN KEY (id_retur_penjualan) REFERENCES retur_penjualan(id_retur_penjualan)
        ON DELETE CASCADE,
    CONSTRAINT fk_retur_penjualan_detail_penjualan
        FOREIGN KEY (id_penjualan_detail) REFERENCES penjualan_detail(id_penjualan_detail),
    CONSTRAINT fk_retur_penjualan_detail_barang_satuan
        FOREIGN KEY (id_barang_satuan) REFERENCES barang_satuan(id_barang_satuan),
    CONSTRAINT fk_retur_penjualan_detail_lokasi
        FOREIGN KEY (id_lokasi_gudang) REFERENCES lokasi_gudang(id_lokasi_gudang)
) ENGINE=InnoDB;

-- =========================================================
-- 7. KAS, BANK, DAN AKUNTANSI
-- =========================================================

CREATE TABLE IF NOT EXISTS transaksi_kas (
    id_transaksi_kas BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_cabang BIGINT UNSIGNED NOT NULL,
    id_kas_bank BIGINT UNSIGNED NOT NULL,
    id_kas_bank_tujuan BIGINT UNSIGNED NULL,
    id_kategori_biaya BIGINT UNSIGNED NULL,
    nomor_transaksi VARCHAR(50) NOT NULL,
    tanggal_transaksi DATETIME NOT NULL,
    jenis_transaksi ENUM('MASUK','KELUAR','PINDAH') NOT NULL,
    sumber_transaksi VARCHAR(100) NULL,
    id_sumber BIGINT UNSIGNED NULL,
    nomor_sumber VARCHAR(100) NULL,
    nilai_transaksi DECIMAL(18,2) NOT NULL DEFAULT 0,
    keterangan TEXT NOT NULL,
    status_transaksi ENUM('DRAF','DISETUJUI','DIBATALKAN') NOT NULL DEFAULT 'DRAF',
    id_pengguna_penyetuju BIGINT UNSIGNED NULL,
    tanggal_disetujui DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by BIGINT UNSIGNED NULL,
    updated_at DATETIME NULL,
    updated_by BIGINT UNSIGNED NULL,
    deleted_at DATETIME NULL,
    deleted_by BIGINT UNSIGNED NULL,
    UNIQUE KEY uk_transaksi_kas_nomor (id_cabang, nomor_transaksi),
    KEY idx_transaksi_kas_tanggal (tanggal_transaksi),
    KEY idx_transaksi_kas_kas_bank (id_kas_bank),
    KEY idx_transaksi_kas_sumber (sumber_transaksi, id_sumber),
    CONSTRAINT fk_transaksi_kas_cabang
        FOREIGN KEY (id_cabang) REFERENCES cabang(id_cabang),
    CONSTRAINT fk_transaksi_kas_kas_bank
        FOREIGN KEY (id_kas_bank) REFERENCES kas_bank(id_kas_bank),
    CONSTRAINT fk_transaksi_kas_kas_bank_tujuan
        FOREIGN KEY (id_kas_bank_tujuan) REFERENCES kas_bank(id_kas_bank)
        ON DELETE SET NULL,
    CONSTRAINT fk_transaksi_kas_kategori_biaya
        FOREIGN KEY (id_kategori_biaya) REFERENCES kategori_biaya(id_kategori_biaya)
        ON DELETE SET NULL,
    CONSTRAINT fk_transaksi_kas_penyetuju
        FOREIGN KEY (id_pengguna_penyetuju) REFERENCES pengguna(id_pengguna)
        ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS akun_keuangan (
    id_akun_keuangan BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_akun_induk BIGINT UNSIGNED NULL,
    kode_akun VARCHAR(30) NOT NULL,
    nama_akun VARCHAR(150) NOT NULL,
    kelompok_akun ENUM('ASET','KEWAJIBAN','MODAL','PENDAPATAN','BEBAN') NOT NULL,
    saldo_normal ENUM('DEBET','KREDIT') NOT NULL,
    akun_rincian TINYINT(1) NOT NULL DEFAULT 1,
    status_aktif TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by BIGINT UNSIGNED NULL,
    updated_at DATETIME NULL,
    updated_by BIGINT UNSIGNED NULL,
    deleted_at DATETIME NULL,
    deleted_by BIGINT UNSIGNED NULL,
    UNIQUE KEY uk_akun_keuangan_kode (kode_akun),
    KEY idx_akun_keuangan_induk (id_akun_induk),
    CONSTRAINT fk_akun_keuangan_induk
        FOREIGN KEY (id_akun_induk) REFERENCES akun_keuangan(id_akun_keuangan)
        ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS pemetaan_akun (
    id_pemetaan_akun BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_cabang BIGINT UNSIGNED NULL,
    kunci_pemetaan VARCHAR(100) NOT NULL,
    id_akun_keuangan BIGINT UNSIGNED NOT NULL,
    keterangan TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by BIGINT UNSIGNED NULL,
    updated_at DATETIME NULL,
    updated_by BIGINT UNSIGNED NULL,
    UNIQUE KEY uk_pemetaan_akun (id_cabang, kunci_pemetaan),
    KEY idx_pemetaan_akun_akun (id_akun_keuangan),
    CONSTRAINT fk_pemetaan_akun_cabang
        FOREIGN KEY (id_cabang) REFERENCES cabang(id_cabang)
        ON DELETE CASCADE,
    CONSTRAINT fk_pemetaan_akun_akun
        FOREIGN KEY (id_akun_keuangan) REFERENCES akun_keuangan(id_akun_keuangan)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS jurnal_umum (
    id_jurnal_umum BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_cabang BIGINT UNSIGNED NOT NULL,
    nomor_jurnal VARCHAR(50) NOT NULL,
    tanggal_jurnal DATE NOT NULL,
    sumber_jurnal VARCHAR(100) NULL,
    id_sumber BIGINT UNSIGNED NULL,
    nomor_sumber VARCHAR(100) NULL,
    keterangan TEXT NOT NULL,
    status_jurnal ENUM('DRAF','DIPOSTING','DIBATALKAN') NOT NULL DEFAULT 'DRAF',
    id_pengguna_pemosting BIGINT UNSIGNED NULL,
    tanggal_diposting DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by BIGINT UNSIGNED NULL,
    updated_at DATETIME NULL,
    updated_by BIGINT UNSIGNED NULL,
    deleted_at DATETIME NULL,
    deleted_by BIGINT UNSIGNED NULL,
    UNIQUE KEY uk_jurnal_umum_nomor (id_cabang, nomor_jurnal),
    KEY idx_jurnal_umum_tanggal (tanggal_jurnal),
    KEY idx_jurnal_umum_sumber (sumber_jurnal, id_sumber),
    CONSTRAINT fk_jurnal_umum_cabang
        FOREIGN KEY (id_cabang) REFERENCES cabang(id_cabang),
    CONSTRAINT fk_jurnal_umum_pemosting
        FOREIGN KEY (id_pengguna_pemosting) REFERENCES pengguna(id_pengguna)
        ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS jurnal_umum_detail (
    id_jurnal_umum_detail BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_jurnal_umum BIGINT UNSIGNED NOT NULL,
    id_akun_keuangan BIGINT UNSIGNED NOT NULL,
    debet DECIMAL(18,2) NOT NULL DEFAULT 0,
    kredit DECIMAL(18,2) NOT NULL DEFAULT 0,
    keterangan TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by BIGINT UNSIGNED NULL,
    KEY idx_jurnal_umum_detail_header (id_jurnal_umum),
    KEY idx_jurnal_umum_detail_akun (id_akun_keuangan),
    CONSTRAINT fk_jurnal_umum_detail_header
        FOREIGN KEY (id_jurnal_umum) REFERENCES jurnal_umum(id_jurnal_umum)
        ON DELETE CASCADE,
    CONSTRAINT fk_jurnal_umum_detail_akun
        FOREIGN KEY (id_akun_keuangan) REFERENCES akun_keuangan(id_akun_keuangan)
) ENGINE=InnoDB;

-- =========================================================
-- 8. LAMPIRAN DAN CATATAN AKTIVITAS
-- =========================================================

CREATE TABLE IF NOT EXISTS lampiran_dokumen (
    id_lampiran_dokumen BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    jenis_dokumen VARCHAR(100) NOT NULL,
    id_dokumen BIGINT UNSIGNED NOT NULL,
    nama_berkas VARCHAR(255) NOT NULL,
    nama_berkas_asli VARCHAR(255) NOT NULL,
    lokasi_berkas VARCHAR(500) NOT NULL,
    jenis_berkas VARCHAR(100) NULL,
    ukuran_berkas BIGINT UNSIGNED NOT NULL DEFAULT 0,
    keterangan TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by BIGINT UNSIGNED NULL,
    deleted_at DATETIME NULL,
    deleted_by BIGINT UNSIGNED NULL,
    KEY idx_lampiran_dokumen (jenis_dokumen, id_dokumen)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS log_aktivitas (
    id_log_aktivitas BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_pengguna BIGINT UNSIGNED NULL,
    id_cabang BIGINT UNSIGNED NULL,
    tanggal_aktivitas DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    nama_modul VARCHAR(100) NOT NULL,
    jenis_aktivitas ENUM('MASUK','KELUAR','TAMBAH','UBAH','HAPUS','LIHAT','CETAK','UNDUH','SETUJUI','BATALKAN','LAINNYA') NOT NULL,
    nama_tabel VARCHAR(100) NULL,
    id_referensi BIGINT UNSIGNED NULL,
    keterangan TEXT NULL,
    data_sebelum LONGTEXT NULL,
    data_sesudah LONGTEXT NULL,
    alamat_ip VARCHAR(45) NULL,
    peramban TEXT NULL,
    KEY idx_log_aktivitas_pengguna (id_pengguna),
    KEY idx_log_aktivitas_cabang (id_cabang),
    KEY idx_log_aktivitas_tanggal (tanggal_aktivitas),
    KEY idx_log_aktivitas_referensi (nama_tabel, id_referensi),
    CONSTRAINT fk_log_aktivitas_pengguna
        FOREIGN KEY (id_pengguna) REFERENCES pengguna(id_pengguna)
        ON DELETE SET NULL,
    CONSTRAINT fk_log_aktivitas_cabang
        FOREIGN KEY (id_cabang) REFERENCES cabang(id_cabang)
        ON DELETE SET NULL
) ENGINE=InnoDB;

-- =========================================================
-- 9. DATA AWAL
-- =========================================================

INSERT IGNORE INTO peran (kode_peran, nama_peran, keterangan)
VALUES
    ('ADMINISTRATOR', 'Administrator', 'Akses penuh seluruh modul sistem'),
    ('PEMILIK', 'Pemilik', 'Pemantauan usaha, laporan, dan persetujuan'),
    ('KASIR', 'Kasir', 'Transaksi penjualan dan penerimaan pembayaran'),
    ('GUDANG', 'Petugas Gudang', 'Persediaan, penerimaan, transfer, dan stok opname'),
    ('PEMBELIAN', 'Petugas Pembelian', 'Permintaan, pesanan, dan faktur pembelian'),
    ('PENJUALAN', 'Petugas Penjualan', 'Penawaran, pesanan, penjualan, dan pelanggan'),
    ('KEUANGAN', 'Petugas Keuangan', 'Kas, bank, hutang, piutang, dan jurnal');

INSERT IGNORE INTO metode_pembayaran
    (kode_metode_pembayaran, nama_metode_pembayaran, kelompok_pembayaran)
VALUES
    ('TUNAI', 'Tunai', 'TUNAI'),
    ('TRANSFER_BANK', 'Transfer Bank', 'TRANSFER'),
    ('QRIS', 'QRIS', 'DOMPET_DIGITAL'),
    ('KARTU_DEBIT', 'Kartu Debit', 'KARTU'),
    ('KARTU_KREDIT', 'Kartu Kredit', 'KARTU'),
    ('TEMPO', 'Pembayaran Tempo', 'TEMPO');

INSERT IGNORE INTO jenis_pelanggan
    (kode_jenis_pelanggan, nama_jenis_pelanggan, potongan_persen_bawaan, batas_piutang_bawaan, lama_jatuh_tempo_bawaan)
VALUES
    ('UMUM', 'Pelanggan Umum', 0, 0, 0),
    ('TUKANG', 'Tukang', 0, 0, 0),
    ('KONTRAKTOR', 'Kontraktor', 0, 0, 0),
    ('TOKO', 'Toko atau Pengecer', 0, 0, 0);

INSERT IGNORE INTO satuan (kode_satuan, nama_satuan, jumlah_desimal)
VALUES
    ('BUAH', 'Buah', 0),
    ('BATANG', 'Batang', 0),
    ('LEMBAR', 'Lembar', 0),
    ('SAK', 'Sak', 0),
    ('DUS', 'Dus', 0),
    ('IKAT', 'Ikat', 0),
    ('KALENG', 'Kaleng', 0),
    ('KILOGRAM', 'Kilogram', 3),
    ('GRAM', 'Gram', 3),
    ('METER', 'Meter', 3),
    ('METER_PERSEGI', 'Meter Persegi', 3),
    ('METER_KUBIK', 'Meter Kubik', 3),
    ('LITER', 'Liter', 3),
    ('ROL', 'Rol', 0),
    ('SET', 'Set', 0);

-- =========================================================
-- 10. TAMPILAN DATA RINGKAS
-- =========================================================

CREATE OR REPLACE VIEW tampilan_stok_tersedia AS
SELECT
    ss.id_saldo_stok,
    g.id_cabang,
    ss.id_gudang,
    g.kode_gudang,
    g.nama_gudang,
    ss.id_lokasi_gudang,
    lg.kode_lokasi,
    lg.nama_lokasi,
    b.id_barang,
    b.kode_barang,
    b.nama_barang,
    s.kode_satuan AS satuan_dasar,
    ss.jumlah_stok,
    ss.jumlah_dipesan,
    ss.jumlah_rusak,
    (ss.jumlah_stok - ss.jumlah_dipesan - ss.jumlah_rusak) AS jumlah_tersedia,
    ss.harga_pokok_rata_rata,
    ss.harga_beli_terakhir,
    ss.tanggal_mutasi_terakhir
FROM saldo_stok ss
INNER JOIN gudang g
    ON g.id_gudang = ss.id_gudang
INNER JOIN lokasi_gudang lg
    ON lg.id_lokasi_gudang = ss.id_lokasi_gudang
INNER JOIN barang b
    ON b.id_barang = ss.id_barang
INNER JOIN satuan s
    ON s.id_satuan = b.id_satuan_dasar
WHERE b.deleted_at IS NULL
  AND g.deleted_at IS NULL
  AND lg.deleted_at IS NULL;

CREATE OR REPLACE VIEW tampilan_hutang_pemasok AS
SELECT
    hp.id_hutang_pemasok,
    hp.id_cabang,
    hp.id_pemasok,
    p.kode_pemasok,
    p.nama_pemasok,
    fp.nomor_faktur_internal,
    fp.nomor_faktur_pemasok,
    hp.tanggal_hutang,
    hp.tanggal_jatuh_tempo,
    hp.nilai_awal,
    hp.nilai_pembayaran,
    hp.nilai_retur,
    hp.nilai_penyesuaian,
    hp.sisa_hutang,
    hp.status_hutang,
    CASE
        WHEN hp.status_hutang <> 'LUNAS'
         AND hp.tanggal_jatuh_tempo IS NOT NULL
         AND hp.tanggal_jatuh_tempo < CURDATE()
        THEN DATEDIFF(CURDATE(), hp.tanggal_jatuh_tempo)
        ELSE 0
    END AS jumlah_hari_terlambat
FROM hutang_pemasok hp
INNER JOIN pemasok p
    ON p.id_pemasok = hp.id_pemasok
INNER JOIN faktur_pembelian fp
    ON fp.id_faktur_pembelian = hp.id_faktur_pembelian;

CREATE OR REPLACE VIEW tampilan_piutang_pelanggan AS
SELECT
    pp.id_piutang_pelanggan,
    pp.id_cabang,
    pp.id_pelanggan,
    p.kode_pelanggan,
    p.nama_pelanggan,
    pj.nomor_penjualan,
    pp.tanggal_piutang,
    pp.tanggal_jatuh_tempo,
    pp.nilai_awal,
    pp.nilai_pembayaran,
    pp.nilai_retur,
    pp.nilai_penyesuaian,
    pp.sisa_piutang,
    pp.status_piutang,
    CASE
        WHEN pp.status_piutang <> 'LUNAS'
         AND pp.tanggal_jatuh_tempo IS NOT NULL
         AND pp.tanggal_jatuh_tempo < CURDATE()
        THEN DATEDIFF(CURDATE(), pp.tanggal_jatuh_tempo)
        ELSE 0
    END AS jumlah_hari_terlambat
FROM piutang_pelanggan pp
INNER JOIN pelanggan p
    ON p.id_pelanggan = pp.id_pelanggan
INNER JOIN penjualan pj
    ON pj.id_penjualan = pp.id_penjualan;
