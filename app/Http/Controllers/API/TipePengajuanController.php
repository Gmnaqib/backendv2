<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Pengajuan;

class TipePengajuanController extends Controller
{

    public function index()
    {

        $data = Pengajuan::all();

        return response()->json([
            'success' => true,
            'message' => 'OK',
            'data' => $data
        ]);
    }
}