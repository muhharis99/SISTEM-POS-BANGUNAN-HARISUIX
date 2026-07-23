<?php

namespace App\Http\Requests\Keuangan;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SimpanTransaksiKasRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_kas_bank' => ['required', 'integer'],
            'id_kas_bank_tujuan' => ['nullable', 'integer', 'different:id_kas_bank'],
            'id_kategori_biaya' => ['nullable', 'integer'],
            'tanggal_transaksi' => ['required', 'date'],
            'jenis_transaksi' => ['required', Rule::in(['MASUK', 'KELUAR', 'PINDAH'])],
            'sumber_transaksi' => ['nullable', 'string', 'max:100'],
            'id_sumber' => ['nullable', 'integer'],
            'nomor_sumber' => ['nullable', 'string', 'max:100'],
            'nilai_transaksi' => ['required', 'numeric', 'gt:0'],
            'keterangan' => ['required', 'string'],
        ];
    }
}
