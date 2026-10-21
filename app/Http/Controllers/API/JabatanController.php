<?php

namespace App\Http\Controllers\API;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Jabatan\StoreJabatanRequest;
use App\Http\Requests\Jabatan\UpdateJabatanRequest;
use App\Http\Resources\JabatanResource;
use App\Models\Jabatan;
use Illuminate\Http\Request;

class JabatanController extends Controller
{
    public function index()
    {
        return ApiResponse::success(
            JabatanResource::collection(
                Jabatan::all()
            )
        );
    }

    public function store(StoreJabatanRequest $request)
    {
        $data = Jabatan::create($request->validated());

        return ApiResponse::created(
            new JabatanResource($data)
        );
    }

    public function show(Jabatan $jabatan)
    {
        return ApiResponse::success(
            new JabatanResource($jabatan)
        );
    }

    public function update(UpdateJabatanRequest $request, Jabatan $jabatan)
    {
        $jabatan->update($request->validated());

        return ApiResponse::success(
            new JabatanResource($jabatan)
        );
    }

    public function destroy(Jabatan $jabatan)
    {
        $jabatan->delete();

        return ApiResponse::deleted();
    }
}
