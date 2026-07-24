<?php

namespace App\Http\Requests\Laporan;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class FilterLaporanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'tanggal_awal' => $this->input('tanggal_awal') ?: now()->startOfMonth()->toDateString(),
            'tanggal_akhir' => $this->input('tanggal_akhir') ?: now()->toDateString(),
            'jenis_laporan' => $this->input('jenis_laporan') ?: 'penjualan',
            'pencarian' => trim((string) $this->input('pencarian', '')),
        ]);
    }

    public function rules(): array
    {
        return [
            'tanggal_awal' => ['required', 'date_format:Y-m-d'],
            'tanggal_akhir' => ['required', 'date_format:Y-m-d', 'after_or_equal:tanggal_awal'],
            'jenis_laporan' => [
                'required',
                Rule::in(['penjualan', 'pembelian', 'persediaan', 'hutang', 'piutang', 'kas']),
            ],
            'pencarian' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $awal = CarbonImmutable::createFromFormat('Y-m-d', $this->string('tanggal_awal')->toString());
                $akhir = CarbonImmutable::createFromFormat('Y-m-d', $this->string('tanggal_akhir')->toString());

                if ($awal->diffInDays($akhir) > 366) {
                    $validator->errors()->add('tanggal_akhir', 'Rentang laporan maksimal 366 hari.');
                }
            },
        ];
    }

    public function messages(): array
    {
        return [
            'tanggal_awal.required' => 'Tanggal awal wajib diisi.',
            'tanggal_awal.date_format' => 'Format tanggal awal harus YYYY-MM-DD.',
            'tanggal_akhir.required' => 'Tanggal akhir wajib diisi.',
            'tanggal_akhir.date_format' => 'Format tanggal akhir harus YYYY-MM-DD.',
            'tanggal_akhir.after_or_equal' => 'Tanggal akhir tidak boleh lebih awal dari tanggal awal.',
            'jenis_laporan.in' => 'Jenis laporan tidak valid.',
        ];
    }
}
