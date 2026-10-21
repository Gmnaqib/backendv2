<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;

class SwaggerTestController extends Controller
{
    public function index()
    {
        return response()->json([
            "message" => "swagger jalan"
        ]);
    }
}
