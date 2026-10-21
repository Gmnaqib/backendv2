<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Barang;
use App\Http\Requests\Barang\StoreBarangRequest;
use App\Http\Requests\Barang\UpdateBarangRequest;
use App\Http\Resources\BarangResource;
use App\Helpers\ApiResponse;
use App\Support\PengajuanCache;

class BarangController extends Controller
{
    public function index()
    {
        $barang = Barang::query()
            ->with('vendor:id,nama')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();

        return ApiResponse::success(
            BarangResource::collection($barang)
        );
    }

    public function store(StoreBarangRequest $request)
    {
        $barang = Barang::create($request->validated());

        PengajuanCache::flush();

        return ApiResponse::success(
            new BarangResource($barang->load('vendor'))
        );
    }

    public function show(Barang $barang)
    {
        return ApiResponse::success(
            new BarangResource($barang->load('vendor'))
        );
    }

    public function update(UpdateBarangRequest $request, Barang $barang)
    {
        $barang->update($request->validated());

        PengajuanCache::flush();

        return ApiResponse::success(
            new BarangResource($barang->load('vendor'))
        );
    }

    public function destroy(Barang $barang)
    {
        $barang->delete();

        PengajuanCache::flush();

        return ApiResponse::success(null, 'deleted');
    }
}
