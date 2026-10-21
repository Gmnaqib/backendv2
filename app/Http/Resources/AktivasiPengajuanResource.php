<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AktivasiPengajuanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [

            'id' => $this->id,

            'pengajuan' => [
                'id' => $this->pengajuan->id,
                'tipe' => $this->pengajuan->tipe,
                'semester' => $this->pengajuan->semester,
                'ujian' => $this->pengajuan->ujian,
            ],

            'aktif_mulai' => $this->aktif_mulai,
            'aktif_selesai' => $this->aktif_selesai,

            'tipe' => $this->tipe,

            'tahun_akademik' => $this->tahun_akademik,

            'nama_periode' => $this->buildNamaPeriode(),

            'created_at' => $this->created_at
        ];
    }

    private function buildNamaPeriode(): string
    {
        $parts = ['Periode'];

        $tipePengajuan = $this->pengajuan->tipe ?? '';

        $tipeLabels = [
            'tahunan' => 'Tahunan',
            'ujian'   => 'Ujian',
            'kelas'   => 'Kelas',
            'nonrutin' => 'Non Rutin',
        ];

        if (isset($tipeLabels[$tipePengajuan])) {
            $parts[] = $tipeLabels[$tipePengajuan];
        }

        $ujian = $this->pengajuan->ujian ?? null;
        if ($ujian && strtolower($ujian) !== 'default') {
            $parts[] = strtoupper($ujian);
        }

        if ($this->tahun_akademik) {
            $parts[] = $this->tahun_akademik;
        }

        $semester = $this->pengajuan->semester ?? null;
        if ($semester && !in_array(strtolower($semester), ['tahunan', 'default', ''])) {
            $parts[] = ucfirst($semester);
        }

        return implode(' ', $parts);
    }
}
