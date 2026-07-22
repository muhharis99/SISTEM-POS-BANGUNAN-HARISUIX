<?php

declare(strict_types=1);

$akarProyek = dirname(__DIR__);
$sumber = $akarProyek.'/template_admin/assets';
$tujuan = $akarProyek.'/public/assets/admin';
$lokasiManifest = $akarProyek.'/docs/manifests/aset-ubold-sha256.json';

if (! is_dir($sumber)) {
    fwrite(STDERR, "Folder sumber tidak ditemukan: {$sumber}\n");
    exit(1);
}

if (! is_dir($tujuan) && ! mkdir($tujuan, 0775, true) && ! is_dir($tujuan)) {
    fwrite(STDERR, "Gagal membuat folder tujuan: {$tujuan}\n");
    exit(1);
}

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($sumber, FilesystemIterator::SKIP_DOTS),
    RecursiveIteratorIterator::SELF_FIRST
);

$jumlahBerkas = 0;
$manifest = [];

foreach ($iterator as $item) {
    $pathRelatif = str_replace('\\', '/', substr($item->getPathname(), strlen($sumber) + 1));
    $pathTujuan = $tujuan.'/'.$pathRelatif;

    if ($item->isDir()) {
        if (! is_dir($pathTujuan) && ! mkdir($pathTujuan, 0775, true) && ! is_dir($pathTujuan)) {
            fwrite(STDERR, "Gagal membuat folder: {$pathTujuan}\n");
            exit(1);
        }

        continue;
    }

    $folderTujuan = dirname($pathTujuan);

    if (! is_dir($folderTujuan) && ! mkdir($folderTujuan, 0775, true) && ! is_dir($folderTujuan)) {
        fwrite(STDERR, "Gagal membuat folder: {$folderTujuan}\n");
        exit(1);
    }

    if (! copy($item->getPathname(), $pathTujuan)) {
        fwrite(STDERR, "Gagal menyalin berkas: {$pathRelatif}\n");
        exit(1);
    }

    $hash = hash_file('sha256', $pathTujuan);

    if ($hash === false) {
        fwrite(STDERR, "Gagal menghitung checksum: {$pathRelatif}\n");
        exit(1);
    }

    $manifest[$pathRelatif] = [
        'sha256' => $hash,
        'ukuran_byte' => filesize($pathTujuan),
    ];
    $jumlahBerkas++;
}

ksort($manifest);

$folderManifest = dirname($lokasiManifest);

if (! is_dir($folderManifest) && ! mkdir($folderManifest, 0775, true) && ! is_dir($folderManifest)) {
    fwrite(STDERR, "Gagal membuat folder manifest: {$folderManifest}\n");
    exit(1);
}

$isiManifest = json_encode([
    'sumber' => 'template_admin/assets',
    'tujuan' => 'public/assets/admin',
    'algoritma' => 'sha256',
    'jumlah_berkas' => $jumlahBerkas,
    'dibuat_pada' => date(DATE_ATOM),
    'berkas' => $manifest,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

if ($isiManifest === false || file_put_contents($lokasiManifest, $isiManifest."\n") === false) {
    fwrite(STDERR, "Gagal menulis manifest aset.\n");
    exit(1);
}

fwrite(STDOUT, "Aset UBold berhasil disalin tanpa pembaruan vendor.\n");
fwrite(STDOUT, "Jumlah berkas: {$jumlahBerkas}\n");
fwrite(STDOUT, "Manifest SHA-256: {$lokasiManifest}\n");
