<?php

namespace App\Http\Requests\Keuangan;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SimpanAkunKeuanganRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_akun_induk' => ['nullable', 'integer'],
            'kode_akun' => ['required', 'string', 'max:30'],
            'nama_akun' => ['required', 'string', 'max:150'],
            'kelompok_akun' => ['required', Rule::in(['ASET', 'KEWAJIBAN', 'MODAL', 'PENDAPATAN', 'BEBAN'])],
            'saldo_normal' => ['required', Rule::in(['DEBET', 'KREDIT'])],
            'akun_rincian' => ['required', 'boolean'],
            'status_aktif' => ['required', 'boolean'],
        ];
    }
}
