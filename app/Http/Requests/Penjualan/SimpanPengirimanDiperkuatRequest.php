<?php

namespace App\Http\Requests\Penjualan;

use Illuminate\Foundation\Http\FormRequest;

class SimpanPengirimanDiperkuatRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_pesanan_penjualan' => ['nullable', 'integer'],
            'id_penjualan' => ['nullable', 'integer'],
            'id_armada' => ['nullable', 'integer'],
            'id_pegawai_pengemudi' => ['nullable', 'integer'],
            'tanggal_pengiriman' => ['required', 'date'],
            'tanggal_rencana_tiba' => ['nullable', 'date', 'after_or_equal:tanggal_pengiriman'],
            'nama_penerima' => ['nullable', 'string', 'max:150'],
            'telepon_penerima' => ['nullable', 'string', 'max:30'],
            'alamat_pengiriman' => ['required', 'string'],
            'garis_lintang' => ['nullable', 'numeric', 'between:-90,90'],
            'garis_bujur' => ['nullable', 'numeric', 'between:-180,180'],
            'biaya_pengiriman' => ['nullable', 'numeric', 'min:0'],
            'keterangan' => ['nullable', 'string'],
            'detail' => ['required', 'array', 'min:1'],
            'detail.*.id_pesanan_penjualan_detail' => ['nullable', 'integer', 'distinct'],
            'detail.*.id_penjualan_detail' => ['nullable', 'integer', 'distinct'],
            'detail.*.id_barang_satuan' => ['required', 'integer'],
            'detail.*.jumlah_dikirim' => ['required', 'numeric', 'gt:0'],
            'detail.*.keterangan' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'tanggal_rencana_tiba.after_or_equal' => 'Tanggal rencana tiba tidak boleh sebelum tanggal pengiriman.',
            'detail.*.id_pesanan_penjualan_detail.distinct' => 'Detail pesanan yang sama tidak boleh dikirim dua kali dalam satu dokumen.',
            'detail.*.id_penjualan_detail.distinct' => 'Detail penjualan yang sama tidak boleh dikirim dua kali dalam satu dokumen.',
        ];
    }
}
