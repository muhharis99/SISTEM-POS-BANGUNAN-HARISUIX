# Fase 4 — Status

## Checkpoint

- Fase 1: lulus.
- Fase 2: lulus dan merged ke `main`.
- Fase 3: lulus dan merged ke `main`.
- Fase 4: implementasi Persediaan dan Mutasi Stok berlangsung pada branch `fase-4-persediaan` melalui Draft PR #5.
- Fase 5: belum dimulai.

## Cakupan implementasi

- saldo dan mutasi stok;
- stok awal dan persetujuan;
- transfer antar-gudang dan antar-lokasi dengan proses kirim/terima;
- stok opname dan penyesuaian otomatis;
- penyesuaian manual tambah/kurang;
- stok rusak dan stok tersedia;
- kartu stok dan laporan saldo;
- konversi satuan dan validasi desimal dinamis;
- penguncian saldo dan nomor dokumen;
- audit aktivitas, permission, isolasi cabang, serta UI UBold lokal.

## Pengaman

- Tidak ada perubahan skema paten.
- Tidak ada tabel atau kolom tambahan di luar tabel internal `migrations`.
- Tidak ada CDN eksternal; UBold dan Nunito tetap lokal.
- Backup wajib dibuat sebelum migration dan sebelum setup/testing Fase 4.
- Regression test Fase 1, Fase 2, dan Fase 3 tetap dijalankan.
- Draft PR #5 tidak boleh di-merge atau diubah menjadi ready sebelum pemilik menyatakan eksplisit `Fase 4 lulus`.
- Auto-merge dilarang.
- Fase 5 tidak boleh dimulai tanpa instruksi terpisah.

## Dokumen

- `docs/FASE-4-RINGKASAN-IMPLEMENTASI.md`
- `docs/FASE-4-CHECKLIST-PENGUJIAN-MANUAL.md`
