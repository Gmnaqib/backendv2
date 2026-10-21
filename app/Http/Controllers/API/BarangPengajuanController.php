<?php

namespace App\Http\Controllers\API;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\BarangPengajuanResource;
use App\Models\BarangPengajuan;
use App\Support\PengajuanCache;
use Illuminate\Http\Request;

class BarangPengajuanController extends Controller
{
    public function approve(Request $request, BarangPengajuan $barangPengajuan)
    {
        $validated = $request->validate([

            'jumlah_disetujui' => [
                'required',
                'integer',
                'min:0',
            ],

            'status' => [
                'required',
                'integer',
                'in:0,1,2',
            ],
        ]);

        $barangPengajuan->update([

            'jumlah_disetujui' => $validated['jumlah_disetujui'],

            'status' => $validated['status'],

        ]);

        PengajuanCache::flush();

        return ApiResponse::success(
            new BarangPengajuanResource($barangPengajuan->load('barang'))
        );
    }
}
