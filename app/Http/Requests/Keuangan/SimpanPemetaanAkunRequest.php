<?php

namespace App\Http\Requests\Keuangan;

use Illuminate\Foundation\Http\FormRequest;

class SimpanPemetaanAkunRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'kunci_pemetaan' => ['required', 'string', 'max:100'],
            'id_akun_keuangan' => ['required', 'integer'],
            'keterangan' => ['nullable', 'string'],
        ];
    }
}
