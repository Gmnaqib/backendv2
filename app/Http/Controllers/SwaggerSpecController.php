<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\File;

class SwaggerSpecController extends Controller
{
    public function spec()
    {
        $file = base_path('docs/api-docs-v4.yaml');

        abort_if(! File::exists($file), 404, 'Dokumentasi API tidak ditemukan');

        return response(
            File::get($file),
            200,
            ['Content-Type' => 'application/yaml']
        );
    }

    public function ui()
    {
        $specUrl = url('/swagger/spec');

        return view('swagger-ui', [
            'title' => 'LOGISTIK UNIKOM API - v4',
            'specUrl' => $specUrl,
        ]);
    }
}
