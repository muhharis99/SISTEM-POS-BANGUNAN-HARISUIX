<?php

namespace App\Http\Requests\Penjualan;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SimpanPenjualanFinalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_gudang' => ['required', 'integer'],
            'id_pelanggan' => ['nullable', 'integer'],
            'id_pesanan_penjualan' => ['nullable', 'integer'],
            'id_daftar_harga' => ['nullable', 'integer'],
            'id_kas_bank' => ['nullable', 'integer'],
            'id_metode_pembayaran' => ['nullable', 'integer'],
            'tanggal_penjualan' => ['required', 'date'],
            'tanggal_jatuh_tempo' => ['nullable', 'date', 'after_or_equal:tanggal_penjualan'],
            'jenis_penjualan' => ['required', Rule::in(['TUNAI', 'TEMPO'])],
            'status_pengiriman' => ['nullable', Rule::in(['BELUM_DIKIRIM', 'DIAMBIL_SENDIRI'])],
            'biaya_pengiriman' => ['nullable', 'numeric', 'min:0'],
            'biaya_lain' => ['nullable', 'numeric', 'min:0'],
            'pembulatan' => ['nullable', 'numeric'],
            'total_dibayar' => ['nullable', 'numeric', 'min:0'],
            'keterangan' => ['nullable', 'string', 'max:2000'],
            'detail' => ['required', 'array', 'min:1', 'max:200'],
            'detail.*.id_pesanan_penjualan_detail' => ['nullable', 'integer', 'distinct'],
            'detail.*.id_barang_satuan' => ['required', 'integer'],
            'detail.*.id_lokasi_gudang' => ['required', 'integer'],
            'detail.*.id_tarif_pajak' => ['nullable', 'integer'],
            'detail.*.jumlah' => ['required', 'numeric', 'gt:0'],
            'detail.*.harga_satuan' => ['required', 'numeric', 'min:0'],
            'detail.*.potongan_persen' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'detail.*.nomor_lot' => ['nullable', 'string', 'max:100'],
            'detail.*.tanggal_kedaluwarsa' => ['nullable', 'date'],
            'detail.*.keterangan' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $jenis = (string) $this->input('jenis_penjualan');

            if ($jenis === 'TEMPO' && ! $this->filled('id_pelanggan')) {
                $validator->errors()->add('id_pelanggan', 'Penjualan tempo wajib memiliki pelanggan.');
            }

            if ($jenis === 'TEMPO' && ! $this->filled('tanggal_jatuh_tempo')) {
                $validator->errors()->add('tanggal_jatuh_tempo', 'Tanggal jatuh tempo wajib diisi untuk penjualan tempo.');
            }

            if ($jenis === 'TUNAI' && $this->filled('tanggal_jatuh_tempo')) {
                $validator->errors()->add('tanggal_jatuh_tempo', 'Tanggal jatuh tempo tidak digunakan untuk penjualan tunai.');
            }
        });
    }
}