<?php

namespace App\Http\Requests\Penjualan;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SimpanReturPenjualanDiperkuatRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_penjualan' => ['required', 'integer'],
            'id_gudang' => ['required', 'integer'],
            'tanggal_retur' => ['required', 'date'],
            'alasan_retur' => ['required', 'string'],
            'cara_pengembalian_dana' => ['required', Rule::in(['POTONG_PIUTANG', 'TUNAI', 'TRANSFER'])],
            'id_kas_bank' => [
                Rule::requiredIf(fn (): bool => in_array($this->input('cara_pengembalian_dana'), ['TUNAI', 'TRANSFER'], true)),
                'nullable',
                'integer',
            ],
            'keterangan' => ['nullable', 'string'],
            'detail' => ['required', 'array', 'min:1'],
            'detail.*.id_penjualan_detail' => ['required', 'integer', 'distinct'],
            'detail.*.id_barang_satuan' => ['required', 'integer'],
            'detail.*.id_lokasi_gudang' => ['required', 'integer'],
            'detail.*.jumlah' => ['required', 'numeric', 'gt:0'],
            'detail.*.harga_satuan' => ['nullable', 'numeric', 'min:0'],
            'detail.*.nomor_lot' => ['nullable', 'string', 'max:100'],
            'detail.*.tanggal_kedaluwarsa' => ['nullable', 'date'],
            'detail.*.kondisi_barang' => ['required', Rule::in(['BAIK', 'RUSAK', 'CACAT', 'SALAH_KIRIM', 'LAINNYA'])],
            'detail.*.bisa_dijual_kembali' => ['required', 'boolean'],
            'detail.*.keterangan' => ['nullable', 'string'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            foreach ((array) $this->input('detail', []) as $index => $baris) {
                $kondisi = (string) ($baris['kondisi_barang'] ?? '');
                $bisaDijual = filter_var($baris['bisa_dijual_kembali'] ?? false, FILTER_VALIDATE_BOOL);

                if ($kondisi !== 'BAIK' && $bisaDijual) {
                    $validator->errors()->add(
                        "detail.{$index}.bisa_dijual_kembali",
                        'Barang rusak, cacat, salah kirim, atau kondisi lainnya tidak boleh otomatis ditandai layak dijual kembali.'
                    );
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'cara_pengembalian_dana.in' => 'Metode pengganti barang belum didukung karena belum memiliki alur pengiriman pengganti yang dapat diaudit.',
            'id_kas_bank.required' => 'Kas atau bank wajib dipilih untuk pengembalian dana tunai atau transfer.',
            'detail.*.id_penjualan_detail.distinct' => 'Detail penjualan yang sama tidak boleh diretur dua kali dalam satu dokumen.',
        ];
    }
}
