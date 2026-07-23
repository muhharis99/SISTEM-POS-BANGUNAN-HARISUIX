<?php

namespace App\Http\Requests\Lampiran;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SimpanLampiranDokumenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $ekstensi = implode(',', config('lampiran.ekstensi_diizinkan', []));

        return [
            'jenis_dokumen' => [
                'required',
                'string',
                Rule::in(array_keys(config('lampiran.dokumen', []))),
            ],
            'id_dokumen' => ['required', 'integer', 'min:1'],
            'berkas' => [
                'required',
                'file',
                'mimes:'.$ekstensi,
                'max:'.(int) config('lampiran.maksimum_kilobita', 10240),
            ],
            'keterangan' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'jenis_dokumen' => 'jenis dokumen',
            'id_dokumen' => 'ID dokumen',
            'berkas' => 'berkas lampiran',
            'keterangan' => 'keterangan',
        ];
    }
}
