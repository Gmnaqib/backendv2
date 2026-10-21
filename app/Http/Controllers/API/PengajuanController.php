<?php

namespace App\Http\Controllers\API;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\PengajuanResource;
use App\Models\DaftarPengajuan;
use App\Models\Jabatan;
use App\Support\CacheGuard;
use App\Support\PengajuanCache;
use Illuminate\Http\Request;

class PengajuanController extends Controller
{
    private const RELASI = [
        'user.jabatan',
        'user.unit',
        'aktivasi.pengajuan',
        'barang.barang.vendor',
        'barangLainnya',
    ];

    public function index(Request $request)
    {
        $isAdmin = auth()->user()->role === 'admin';

        $data = PengajuanCache::remember('index', [
            'user'  => $isAdmin ? 'admin' : auth()->id(),
            'page'  => $request->integer('page', 1),
        ], function () use ($isAdmin) {
            $query = DaftarPengajuan::with(self::RELASI);

            if (! $isAdmin) {
                $query->where('user_id', auth()->id());
            }

            return CacheGuard::polos(
                PengajuanResource::collection($query->latest()->paginate(10))
            );
        });

        return ApiResponse::success($data, 'List pengajuan');
    }

    public function show($id)
    {
        $query = DaftarPengajuan::with(self::RELASI)->where('id', $id);

        
        if (auth()->user()->role !== 'admin') {
            $query->where('user_id', auth()->id());
        }

        $data = $query->firstOrFail();

        return ApiResponse::success(
            new PengajuanResource($data),
            'Detail pengajuan'
        );
    }

    public function my()
    {
        $data = PengajuanCache::remember('my', [
            'user' => auth()->id(),
        ], function () {
            return CacheGuard::polos(PengajuanResource::collection(
                DaftarPengajuan::with(self::RELASI)
                    ->where('user_id', auth()->id())
                    ->latest()
                    ->get()
            ));
        });

        return ApiResponse::success($data, 'Pengajuan saya');
    }

    public function histori(Request $request)
    {
        $aktivasi = $request->id_aktivasi;
        $jabatan = $request->jabatan_id;
        $bagian = $request->bagian_id;

        $data = PengajuanCache::remember('histori', [
            'aktivasi' => $aktivasi,
            'jabatan'  => $jabatan,
            'bagian'   => $bagian,
        ], function () use ($aktivasi, $jabatan, $bagian) {
            $query = DaftarPengajuan::with(self::RELASI);

            
            if ($aktivasi) {
                $query->where('id_aktivasi', $aktivasi);
            }

            
            if ($jabatan) {
                $query->whereHas('user.jabatan', function ($q) use ($jabatan) {
                    $q->where('id', $jabatan);
                });
            }

            
            if ($bagian) {
                $query->whereHas('user.unit', function ($q) use ($bagian) {
                    $q->where('id', $bagian);
                });
            }

            return CacheGuard::polos(
                PengajuanResource::collection($query->latest()->get())
            );
        });

        return ApiResponse::success($data, 'Histori pengajuan');
    }

    public function historiMy(Request $request)
    {
        $tahun = $request->tahun;

        $data = PengajuanCache::remember('histori-my', [
            'user'  => auth()->id(),
            'tahun' => $tahun,
        ], function () use ($tahun) {
            $query = DaftarPengajuan::with(self::RELASI)
                ->where('user_id', auth()->id());

            
            if ($tahun) {
                $query->whereYear('date', $tahun);
            }

            return CacheGuard::polos(
                PengajuanResource::collection($query->latest()->get())
            );
        });

        return ApiResponse::success($data, 'Histori pengajuan saya');
    }
}
