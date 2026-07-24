<?php

namespace App\Http\Requests\Penjualan;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SimpanPesananFinalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_pelanggan' => ['required', 'integer'],
            'id_daftar_harga' => ['nullable', 'integer'],
            'nomor_pesanan_pelanggan' => ['nullable', 'string', 'max:100'],
            'tanggal_pesanan' => ['required', 'date'],
            'tanggal_rencana_pengiriman' => ['nullable', 'date', 'after_or_equal:tanggal_pesanan'],
            'sumber_pesanan' => ['required', Rule::in(['TOKO', 'TELEPON', 'WHATSAPP', 'SUREL', 'WEBSITE', 'TENAGA_PENJUAL', 'LAINNYA'])],
            'cara_pembayaran' => ['required', Rule::in(['TUNAI', 'TEMPO'])],
            'lama_jatuh_tempo' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'alamat_penagihan' => ['nullable', 'string', 'max:1000'],
            'alamat_pengiriman' => ['nullable', 'string', 'max:1000'],
            'nama_penerima' => ['nullable', 'string', 'max:150'],
            'telepon_penerima' => ['nullable', 'string', 'max:30'],
            'biaya_pengiriman' => ['nullable', 'numeric', 'min:0'],
            'biaya_lain' => ['nullable', 'numeric', 'min:0'],
            'uang_muka' => ['nullable', 'numeric', 'min:0'],
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

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->input('cara_pembayaran') === 'TUNAI' && (int) $this->input('lama_jatuh_tempo', 0) > 0) {
                $validator->errors()->add('lama_jatuh_tempo', 'Jatuh tempo hanya digunakan untuk pesanan TEMPO.');
            }
        });
    }
}