<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Vendor;
use App\Http\Requests\Vendor\StoreVendorRequest;
use App\Http\Requests\Vendor\UpdateVendorRequest;
use App\Http\Resources\VendorResource;
use App\Helpers\ApiResponse;

class VendorController extends Controller
{
    public function index()
    {
        $vendors = Vendor::latest()->paginate(10);

        return ApiResponse::success(
            VendorResource::collection($vendors),
            'List vendor'
        );
    }

    public function rekapVendor(\Illuminate\Http\Request $request)
    {
        if ($request->has('id_aktivasi')) {
            $aktivasiIds = [$request->id_aktivasi];
        } else {
            $aktivasiIds = \App\Models\AktivasiPengajuan::whereDate('aktif_mulai', '<=', now())
                ->whereDate('aktif_selesai', '>=', now())
                ->pluck('id');
        }

        $vendors = Vendor::with(['barang' => function ($query) use ($aktivasiIds) {
            $filter = function ($q) use ($aktivasiIds) {
                $q->whereHas('daftarPengajuan', function ($dp) use ($aktivasiIds) {
                      $dp->whereIn('id_aktivasi', $aktivasiIds);
                  });
            };

            $query->withSum(['barangPengajuan' => $filter], 'jumlah_disetujui');
            $query->withSum(['barangPengajuan' => $filter], 'jumlah');
        }])->get();

        $vendors->each(function ($vendor) {
            if ($vendor->relationLoaded('barang')) {
                $vendor->setRelation('barang', $vendor->barang->filter(function ($barang) {
                    $jumlah = $barang->barang_pengajuan_sum_jumlah_disetujui ?? $barang->barang_pengajuan_sum_jumlah ?? 0;
                    return $jumlah > 0;
                })->values());
            }
        });

        $vendors = $vendors->filter(function ($vendor) {
            return $vendor->barang->count() > 0;
        })->values();

        return ApiResponse::success(
            VendorResource::collection($vendors),
            'List rekap vendor beserta barang'
        );
    }

    public function store(StoreVendorRequest $request)
    {
        $vendor = Vendor::create($request->validated());

        return ApiResponse::success(
            new VendorResource($vendor),
            'Vendor berhasil dibuat'
        );
    }

    public function show(Vendor $vendor)
    {
        return ApiResponse::success(
            new VendorResource($vendor),
            'Detail vendor'
        );
    }

    public function update(UpdateVendorRequest $request, Vendor $vendor)
    {
        $vendor->update($request->validated());

        return ApiResponse::success(
            new VendorResource($vendor),
            'Vendor berhasil diupdate'
        );
    }

    public function destroy(Vendor $vendor)
    {
        if ($vendor->barang()->exists()) {
            return ApiResponse::error(
                'Vendor tidak bisa dihapus karena masih digunakan barang',
                422
            );
        }

        $vendor->delete();

        return ApiResponse::success(
            null,
            'Vendor berhasil dihapus'
        );
    }
}
