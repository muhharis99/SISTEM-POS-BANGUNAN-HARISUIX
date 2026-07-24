<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Nota {{ $penjualan->nomor_penjualan }}</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; background: #f3f4f6; color: #111827; font-family: Arial, Helvetica, sans-serif; font-size: 12px; }
        .nota { width: 80mm; min-height: 100vh; margin: 18px auto; padding: 8mm 6mm; background: #fff; box-shadow: 0 10px 30px rgba(15, 23, 42, .12); }
        .text-center { text-align: center; }
        .text-end { text-align: right; }
        .fw-bold { font-weight: 700; }
        .muted { color: #6b7280; }
        .judul { margin: 0 0 3px; font-size: 17px; }
        .garis { margin: 9px 0; border-top: 1px dashed #6b7280; }
        .baris { display: flex; justify-content: space-between; gap: 10px; margin-bottom: 3px; }
        .baris span:first-child { flex: 0 0 37%; }
        .baris span:last-child { flex: 1; text-align: right; overflow-wrap: anywhere; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 4px 0; vertical-align: top; }
        th { border-bottom: 1px solid #111827; font-size: 11px; }
        td { border-bottom: 1px dotted #d1d5db; }
        .barang { max-width: 35mm; }
        .ringkas { margin-top: 7px; }
        .ringkas .baris { margin-bottom: 4px; }
        .total { padding-top: 6px; border-top: 1px solid #111827; font-size: 14px; }
        .aksi { width: 80mm; margin: 0 auto 18px; display: flex; gap: 8px; justify-content: center; }
        button { cursor: pointer; border: 0; border-radius: 6px; padding: 9px 14px; color: #fff; background: #2563eb; font-weight: 700; }
        button.secondary { background: #4b5563; }
        @media print {
            @page { size: 80mm auto; margin: 0; }
            body { background: #fff; }
            .nota { width: 80mm; margin: 0; padding: 6mm 5mm; box-shadow: none; }
            .aksi { display: none !important; }
        }
    </style>
</head>
<body>
    @php
        $rupiah = fn ($nilai) => 'Rp '.number_format((float) $nilai, 0, ',', '.');
        $jumlah = fn ($nilai) => rtrim(rtrim(number_format((float) $nilai, 3, ',', '.'), '0'), ',');
    @endphp

    <main class="nota">
        <header class="text-center">
            <h1 class="judul">{{ $cabang->nama_cabang ?? 'Toko Bangunan' }}</h1>
            <div>{{ $cabang->alamat ?? '-' }}</div>
            @if (! empty($cabang->telepon) || ! empty($cabang->nomor_whatsapp))
                <div>Telp/WA: {{ $cabang->telepon ?: $cabang->nomor_whatsapp }}</div>
            @endif
        </header>

        <div class="garis"></div>

        <section>
            <div class="baris"><span>Nomor</span><span class="fw-bold">{{ $penjualan->nomor_penjualan }}</span></div>
            <div class="baris"><span>Tanggal</span><span>{{ date('d-m-Y H:i', strtotime($penjualan->tanggal_penjualan)) }}</span></div>
            <div class="baris"><span>Pelanggan</span><span>{{ $penjualan->nama_pelanggan ?: 'Pelanggan Umum' }}</span></div>
            @if ($penjualan->telepon_pelanggan)
                <div class="baris"><span>Telepon</span><span>{{ $penjualan->telepon_pelanggan }}</span></div>
            @endif
            <div class="baris"><span>Kasir</span><span>{{ $penjualan->nama_kasir ?: '-' }}</span></div>
            <div class="baris"><span>Pembayaran</span><span>{{ $penjualan->nama_metode_pembayaran ?: $penjualan->jenis_penjualan }}</span></div>
        </section>

        <div class="garis"></div>

        <table>
            <thead>
                <tr>
                    <th class="barang">Barang</th>
                    <th class="text-end">Jumlah</th>
                    <th class="text-end">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($detail as $item)
                    <tr>
                        <td class="barang">
                            <div class="fw-bold">{{ $item->nama_barang }}</div>
                            <div class="muted">{{ $item->kode_barang }} · {{ $rupiah($item->harga_satuan) }}</div>
                            @if ((float) $item->potongan_nilai > 0)
                                <div class="muted">Potongan {{ $rupiah($item->potongan_nilai) }}</div>
                            @endif
                        </td>
                        <td class="text-end">{{ $jumlah($item->jumlah) }} {{ $item->kode_satuan }}</td>
                        <td class="text-end fw-bold">{{ $rupiah($item->total_baris) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <section class="ringkas">
            <div class="baris"><span>Total kotor</span><span>{{ $rupiah($penjualan->total_kotor) }}</span></div>
            @if ((float) $penjualan->total_potongan > 0)
                <div class="baris"><span>Potongan</span><span>-{{ $rupiah($penjualan->total_potongan) }}</span></div>
            @endif
            @if ((float) $penjualan->total_pajak > 0)
                <div class="baris"><span>Pajak</span><span>{{ $rupiah($penjualan->total_pajak) }}</span></div>
            @endif
            @if ((float) $penjualan->biaya_pengiriman > 0)
                <div class="baris"><span>Pengiriman</span><span>{{ $rupiah($penjualan->biaya_pengiriman) }}</span></div>
            @endif
            @if ((float) $penjualan->biaya_lain > 0)
                <div class="baris"><span>Biaya lain</span><span>{{ $rupiah($penjualan->biaya_lain) }}</span></div>
            @endif
            @if ((float) $penjualan->pembulatan !== 0.0)
                <div class="baris"><span>Pembulatan</span><span>{{ $rupiah($penjualan->pembulatan) }}</span></div>
            @endif
            <div class="baris total"><span class="fw-bold">TOTAL</span><span class="fw-bold">{{ $rupiah($penjualan->total_bersih) }}</span></div>
            <div class="baris"><span>Dibayar</span><span>{{ $rupiah($penjualan->total_dibayar) }}</span></div>
            @if ((float) $penjualan->uang_kembali > 0)
                <div class="baris"><span>Kembali</span><span>{{ $rupiah($penjualan->uang_kembali) }}</span></div>
            @endif
            @if ((float) $penjualan->sisa_piutang > 0)
                <div class="baris"><span>Sisa piutang</span><span class="fw-bold">{{ $rupiah($penjualan->sisa_piutang) }}</span></div>
            @endif
        </section>

        <div class="garis"></div>

        <footer class="text-center">
            <div class="fw-bold">Terima kasih telah berbelanja.</div>
            <div class="muted">Simpan nota ini sebagai bukti transaksi.</div>
        </footer>
    </main>

    <div class="aksi">
        <button type="button" onclick="window.print()">Cetak Nota</button>
        <button type="button" class="secondary" onclick="window.close()">Tutup</button>
    </div>
</body>
</html>
