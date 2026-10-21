<?php

namespace App\Http\Controllers\API;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\BarangPengajuanLainnyaResource;
use App\Models\BarangPengajuanLainnya;
use App\Support\PengajuanCache;
use Illuminate\Http\Request;

class BarangPengajuanLainnyaController extends Controller
{
    public function approve(Request $request, BarangPengajuanLainnya $barangPengajuanLainnya)
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

        $barangPengajuanLainnya->update($validated);

        PengajuanCache::flush();

        return ApiResponse::success(
            new BarangPengajuanLainnyaResource($barangPengajuanLainnya),
            'Barang manual berhasil di approve'
        );
    }
}
