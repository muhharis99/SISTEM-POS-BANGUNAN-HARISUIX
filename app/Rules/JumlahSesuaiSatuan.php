<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\DB;

class JumlahSesuaiSatuan implements ValidationRule
{
    public function __construct(private readonly int $idSatuan) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_numeric($value)) {
            $fail('Nilai :attribute harus berupa angka.');

            return;
        }

        $jumlahDesimal = DB::table('satuan')
            ->where('id_satuan', $this->idSatuan)
            ->whereNull('deleted_at')
            ->value('jumlah_desimal');

        if ($jumlahDesimal === null) {
            $fail('Satuan untuk :attribute tidak valid.');

            return;
        }

        $teks = rtrim(rtrim(number_format((float) $value, 12, '.', ''), '0'), '.');
        $bagian = explode('.', $teks, 2);
        $jumlahAktual = isset($bagian[1]) ? strlen($bagian[1]) : 0;

        if ($jumlahAktual > (int) $jumlahDesimal) {
            $fail('Nilai :attribute maksimal boleh memiliki '.(int) $jumlahDesimal.' angka di belakang koma sesuai pengaturan satuan.');
        }
    }
}
