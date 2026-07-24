<?php

namespace App\Http\Requests\Penjualan;

use Illuminate\Foundation\Http\FormRequest;

class SimpanPenawaranFinalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_pelanggan' => ['nullable', 'integer'],
            'tanggal_penawaran' => ['required', 'date'],
            'berlaku_sampai' => ['nullable', 'date', 'after_or_equal:tanggal_penawaran'],
            'alamat_penagihan' => ['nullable', 'string', 'max:1000'],
            'alamat_pengiriman' => ['nullable', 'string', 'max:1000'],
            'biaya_pengiriman' => ['nullable', 'numeric', 'min:0'],
            'syarat_ketentuan' => ['nullable', 'string', 'max:5000'],
            'keterangan' => ['nullable', 'string', 'max:2000'],
            'detail' => ['required', 'array', 'min:1', 'max:200'],
            'detail.*.id_barang_satuan' => ['required', 'integer'],
            'detail.*.id_tarif_pajak' => ['nullable', 'integer'],
            'detail.*.jumlah' => ['required', 'numeric', 'gt:0'],
            'detail.*.harga_satuan' => ['required', 'numeric', 'min:0'],
            'detail.*.potongan_persen' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'detail.*.keterangan' => ['nullable', 'string', 'max:1000'],
        ];
    }
}