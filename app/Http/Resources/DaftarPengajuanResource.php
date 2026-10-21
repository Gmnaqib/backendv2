<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class DaftarPengajuanResource extends JsonResource
{
    public function toArray($request)
    {
        return [

            'id' => $this->id,

            'user' => [
                'id' => $this->user?->id,
                'nama' => $this->user?->nama ?? $this->user?->username,
                'nip' => $this->user?->nip,
                'jabatan' => $this->user?->jabatan?->nama,
                'unit' => $this->user?->unit?->nama,
            ],

            'id_aktivasi' => $this->id_aktivasi,
            'aktivasi_pengajuan' => $this->aktivasi?->tipe,
            'aktivasi_pengajuan_id' => $this->id_aktivasi,
            'tahun_akademik' => $this->aktivasi?->tahun_akademik,

            'aktivasi' => $this->aktivasi ? [
                'id' => $this->aktivasi->id,
                'tipe' => $this->aktivasi->tipe,
                'tahun_akademik' => $this->aktivasi->tahun_akademik,
                'aktif_mulai' => $this->aktivasi->aktif_mulai,
                'aktif_selesai' => $this->aktivasi->aktif_selesai,
                'nama_periode' => $this->buildNamaPeriode(),
                'pengajuan' => $this->aktivasi->pengajuan ? [
                    'id' => $this->aktivasi->pengajuan->id,
                    'tipe' => $this->aktivasi->pengajuan->tipe,
                    'semester' => $this->aktivasi->pengajuan->semester,
                    'ujian' => $this->aktivasi->pengajuan->ujian,
                ] : null,
            ] : null,

            'surat_pengajuan' => $this->surat_pengajuan,

            'date' => $this->date,

            'created_at' => $this->created_at,

            'barang' => BarangPengajuanResource::collection(
                $this->whenLoaded('barang')
            ),

            'barang_lainnya' => BarangPengajuanLainnyaResource::collection(
                $this->whenLoaded('barangLainnya')
            ),

        ];
    }

    private function buildNamaPeriode(): string
    {
        $aktivasi = $this->aktivasi;
        if (!$aktivasi) {
            return '';
        }

        $parts = ['Periode'];

        $tipePengajuan = $aktivasi->pengajuan->tipe ?? '';

        $tipeLabels = [
            'tahunan' => 'Tahunan',
            'ujian'   => 'Ujian',
            'kelas'   => 'Kelas',
            'nonrutin' => 'Non Rutin',
        ];

        if (isset($tipeLabels[$tipePengajuan])) {
            $parts[] = $tipeLabels[$tipePengajuan];
        }

        $ujian = $aktivasi->pengajuan->ujian ?? null;
        if ($ujian && strtolower($ujian) !== 'default') {
            $parts[] = strtoupper($ujian);
        }

        if ($aktivasi->tahun_akademik) {
            $parts[] = $aktivasi->tahun_akademik;
        }

        $semester = $aktivasi->pengajuan->semester ?? null;
        if ($semester && !in_array(strtolower($semester), ['tahunan', 'default', ''])) {
            $parts[] = ucfirst($semester);
        }

        return implode(' ', $parts);
    }
}
