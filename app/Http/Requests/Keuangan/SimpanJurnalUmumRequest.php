<?php

namespace App\Http\Requests\Keuangan;

use Illuminate\Foundation\Http\FormRequest;

class SimpanJurnalUmumRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tanggal_jurnal' => ['required', 'date'],
            'sumber_jurnal' => ['nullable', 'string', 'max:100'],
            'id_sumber' => ['nullable', 'integer'],
            'nomor_sumber' => ['nullable', 'string', 'max:100'],
            'keterangan' => ['required', 'string'],
            'detail' => ['required', 'array', 'min:2'],
            'detail.*.id_akun_keuangan' => ['required', 'integer'],
            'detail.*.debet' => ['nullable', 'numeric', 'min:0'],
            'detail.*.kredit' => ['nullable', 'numeric', 'min:0'],
            'detail.*.keterangan' => ['nullable', 'string'],
        ];
    }
}
