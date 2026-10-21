<?php

namespace App\Http\Requests\DaftarPengajuan;

use Illuminate\Foundation\Http\FormRequest;

class StoreFullDaftarPengajuanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation()
    {
        $barang = $this->input('barang', []);
        $barangLainnya = $this->input('barang_lainnya', []);

        $this->merge([
            'barang' => is_string($barang) ? json_decode($barang, true) : $barang,
            'barang_lainnya' => is_string($barangLainnya) ? json_decode($barangLainnya, true) : $barangLainnya,
        ]);
    }

    
    protected function passedValidation()
    {
        $barang = $this->input('barang', []);
        $barangLainnya = $this->input('barang_lainnya', []);

        if (is_array($barang)) {
            foreach ($barang as &$item) {
                if (isset($item['jumlah'])) {
                    $item['jumlah'] = (int) $item['jumlah'];
                }
            }
            unset($item);
        }

        if (is_array($barangLainnya)) {
            foreach ($barangLainnya as &$item) {
                if (isset($item['jumlah'])) {
                    $item['jumlah'] = (int) $item['jumlah'];
                }
            }
            unset($item);
        }

        $this->merge([
            'barang' => $barang,
            'barang_lainnya' => $barangLainnya,
        ]);
    }

    public function rules(): array
    {
        return [
            'tipe' => ['required', 'in:tahunan,ujian,kelas'],

            'semester' => [
                'nullable',
                'in:ganjil,genap,tahunan',
            ],

            'ujian' => [
                'nullable',
                'in:uts,uas,Default,default',
            ],

            'id_aktivasi' => [
                'nullable',
                'integer',
                'exists:aktivasi_pengajuan,id',
            ],

            'date' => [
                'nullable',
                'date',
            ],

            'surat_pengajuan' => [
                'nullable',
                'file',
                'mimes:pdf',
                'max:2048',
            ],

            'barang' => [
                'nullable',
                'array',
            ],

            'barang.*.id_barang' => [
                'required',
                'exists:barang,id',
            ],

            'barang.*.jumlah' => [
                'required',
                'numeric',
                'integer',
                'min:1',
                'max:2147483647',
            ],

            'barang_lainnya' => [
                'nullable',
                'array',
                'prohibited_unless:tipe,tahunan',
            ],

            'barang_lainnya.*.nama' => [
                'required',
                'string',
                'max:255',
            ],

            'barang_lainnya.*.jumlah' => [
                'required',
                'numeric',
                'integer',
                'min:1',
                'max:2147483647',
            ],

            'barang_lainnya.*.kategori' => [
                'required',
                'in:habis_pakai,tidak_habis_pakai',
            ],

            'barang_lainnya.*.satuan' => [
                'required',
                'string',
                'max:100',
            ],

            'barang_lainnya.*.alasan' => [
                'nullable',
                'string',
            ],

            'barang_lainnya.*.catatan' => [
                'nullable',
                'string',
            ],

            'barang_lainnya.*.note' => [
                'nullable',
                'string',
            ],

            'barang_lainnya.*.bukti_foto' => [
                'nullable',
                'file',
                'mimes:jpg,jpeg,png',
                'max:2048',
            ],
        ];
    }

    public function messages(): array
    {
        return [

            'barang.*.id_barang.required' => 'Barang wajib dipilih.',
            'barang.*.id_barang.exists' => 'Barang tidak ditemukan.',
            'barang.*.jumlah.min' => 'Jumlah minimal 1.',
            'barang.*.jumlah.max' => 'Jumlah maksimal 2.147.483.647.',
            'barang.*.jumlah.integer' => 'Jumlah harus berupa bilangan bulat.',
            'barang.*.jumlah.numeric' => 'Jumlah harus berupa angka yang valid.',

            'barang_lainnya.prohibited_unless' => 'Pengajuan lainnya hanya tersedia untuk ATK Tahunan.',

            'barang_lainnya.*.jumlah.min' => 'Jumlah minimal 1.',
            'barang_lainnya.*.jumlah.max' => 'Jumlah maksimal 2.147.483.647.',
            'barang_lainnya.*.jumlah.integer' => 'Jumlah harus berupa bilangan bulat.',
            'barang_lainnya.*.jumlah.numeric' => 'Jumlah harus berupa angka yang valid.',

            'barang_lainnya.*.kategori.in' => 'Kategori harus habis pakai atau tidak habis pakai.',

            'surat_pengajuan.file' => 'Surat pengajuan harus berupa file.',
            'surat_pengajuan.mimes' => 'Surat pengajuan harus berformat PDF.',
            'surat_pengajuan.max' => 'Ukuran surat pengajuan maksimal 2MB.',

            'barang_lainnya.*.bukti_foto.file' => 'Bukti foto harus berupa file.',
            'barang_lainnya.*.bukti_foto.mimes' => 'Bukti foto harus berformat JPG, JPEG, atau PNG.',
            'barang_lainnya.*.bukti_foto.max' => 'Ukuran bukti foto maksimal 2MB.',
        ];
    }
}
