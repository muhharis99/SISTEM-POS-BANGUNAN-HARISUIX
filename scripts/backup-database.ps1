$ErrorActionPreference = 'Stop'

$AkarProyek = Split-Path -Parent $PSScriptRoot
$BerkasEnv = Join-Path $AkarProyek '.env'
$FolderBackup = Join-Path $AkarProyek 'backups\database'

if (-not (Test-Path $BerkasEnv)) {
    throw "Berkas .env tidak ditemukan: $BerkasEnv"
}

$Konfigurasi = @{}
Get-Content $BerkasEnv | ForEach-Object {
    $Baris = $_.Trim()

    if ($Baris -and -not $Baris.StartsWith('#') -and $Baris.Contains('=')) {
        $Bagian = $Baris.Split('=', 2)
        $Kunci = $Bagian[0].Trim()
        $Nilai = $Bagian[1].Trim().Trim('"')
        $Konfigurasi[$Kunci] = $Nilai
    }
}

$DbHost = if ($Konfigurasi['DB_HOST']) { $Konfigurasi['DB_HOST'] } else { '127.0.0.1' }
$DbPort = if ($Konfigurasi['DB_PORT']) { $Konfigurasi['DB_PORT'] } else { '3306' }
$DbDatabase = $Konfigurasi['DB_DATABASE']
$DbUsername = $Konfigurasi['DB_USERNAME']
$DbPassword = $Konfigurasi['DB_PASSWORD']

if (-not $DbDatabase -or -not $DbUsername) {
    throw 'DB_DATABASE dan DB_USERNAME wajib diisi di .env.'
}

$MySqlDump = Get-Command mysqldump -ErrorAction SilentlyContinue

if (-not $MySqlDump) {
    throw 'mysqldump belum tersedia di PATH. Tambahkan folder bin MySQL/Laragon ke PATH.'
}

New-Item -ItemType Directory -Path $FolderBackup -Force | Out-Null
$Waktu = Get-Date -Format 'yyyyMMdd_HHmmss'
$BerkasSql = Join-Path $FolderBackup "$($DbDatabase)_$($Waktu).sql"
$BerkasZip = "$BerkasSql.zip"

$env:MYSQL_PWD = $DbPassword

try {
    & $MySqlDump.Source `
        "--host=$DbHost" `
        "--port=$DbPort" `
        "--user=$DbUsername" `
        '--single-transaction' `
        '--routines' `
        '--triggers' `
        '--events' `
        '--hex-blob' `
        '--default-character-set=utf8mb4' `
        $DbDatabase | Out-File -FilePath $BerkasSql -Encoding utf8

    Compress-Archive -Path $BerkasSql -DestinationPath $BerkasZip -CompressionLevel Optimal -Force
    Remove-Item $BerkasSql -Force

    $Hash = Get-FileHash -Path $BerkasZip -Algorithm SHA256
    "$($Hash.Hash.ToLower())  $([System.IO.Path]::GetFileName($BerkasZip))" |
        Out-File -FilePath "$BerkasZip.sha256" -Encoding ascii
}
finally {
    Remove-Item Env:MYSQL_PWD -ErrorAction SilentlyContinue
}

Write-Host "Backup selesai: $BerkasZip"
Write-Host "Checksum     : $BerkasZip.sha256"
