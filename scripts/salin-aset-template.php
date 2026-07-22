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

$lokasiCssAplikasi = $tujuan.'/css/app.min.css';
$lokasiFontNormal = $tujuan.'/fonts/nunito/Nunito-Variable.woff2';
$lokasiFontItalic = $tujuan.'/fonts/nunito/Nunito-Italic-Variable.woff2';

foreach ([$lokasiCssAplikasi, $lokasiFontNormal, $lokasiFontItalic] as $berkasWajib) {
    if (! is_file($berkasWajib)) {
        fwrite(STDERR, "Berkas wajib asset lokal tidak ditemukan: {$berkasWajib}\n");
        exit(1);
    }
}

$blokImportRusak = '@import url(../../../../css2);'
    .'@import url(../../../../css2-1);'
    .'@import url(../../../../css2-2);'
    .'@import url(../../../../css2-3);'
    .'@import url(../../../../css2-4);'
    .'@import url(../../../../css2-5);'
    .'@import url(../../../../css2-6);'
    .'@import url(../../../../css2-7);'
    .'@import url(../../../../css2-6);'
    .'@import url(../../../../css2-8);';

$blokFontLokal = '@font-face{font-family:"Nunito";font-style:normal;font-weight:300 700;font-display:swap;'
    .'src:url("../fonts/nunito/Nunito-Variable.woff2") format("woff2")}'
    .'@font-face{font-family:"Nunito";font-style:italic;font-weight:300 700;font-display:swap;'
    .'src:url("../fonts/nunito/Nunito-Italic-Variable.woff2") format("woff2")}';

$isiCss = file_get_contents($lokasiCssAplikasi);

if ($isiCss === false) {
    fwrite(STDERR, "Gagal membaca CSS aplikasi: {$lokasiCssAplikasi}\n");
    exit(1);
}

$jumlahPenggantian = 0;
$isiCssDenganFontLokal = str_replace(
    $blokImportRusak,
    $blokFontLokal,
    $isiCss,
    $jumlahPenggantian
);

if ($jumlahPenggantian !== 1) {
    fwrite(
        STDERR,
        "Blok import css2* harus ditemukan tepat satu kali. Ditemukan: {$jumlahPenggantian}.\n"
    );
    exit(1);
}

if (file_put_contents($lokasiCssAplikasi, $isiCssDenganFontLokal) === false) {
    fwrite(STDERR, "Gagal menulis CSS aplikasi dengan font lokal.\n");
    exit(1);
}

$hashCss = hash_file('sha256', $lokasiCssAplikasi);

if ($hashCss === false) {
    fwrite(STDERR, "Gagal menghitung checksum CSS aplikasi setelah penggantian font.\n");
    exit(1);
}

$manifest['css/app.min.css'] = [
    'sha256' => $hashCss,
    'ukuran_byte' => filesize($lokasiCssAplikasi),
];

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
    'perubahan_terkontrol' => [
        'css/app.min.css' => 'Import css2* yang 404 diganti dua @font-face Nunito variable lokal.',
    ],
    'dibuat_pada' => date(DATE_ATOM),
    'berkas' => $manifest,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

if ($isiManifest === false || file_put_contents($lokasiManifest, $isiManifest."\n") === false) {
    fwrite(STDERR, "Gagal menulis manifest aset.\n");
    exit(1);
}

fwrite(STDOUT, "Aset UBold berhasil disalin tanpa pembaruan vendor.\n");
fwrite(STDOUT, "Import css2* berhasil diganti dengan Nunito variable font lokal.\n");
fwrite(STDOUT, "Jumlah berkas: {$jumlahBerkas}\n");
fwrite(STDOUT, "Manifest SHA-256: {$lokasiManifest}\n");
