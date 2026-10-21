<?php

use App\Http\Controllers\API\AktivasiController;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\BarangController;
use App\Http\Controllers\API\BarangPengajuanController;
use App\Http\Controllers\API\BarangPengajuanLainnyaController;
use App\Http\Controllers\API\CetakBerkasController;
use App\Http\Controllers\API\DaftarPengajuanController;
use App\Http\Controllers\API\JabatanController;
use App\Http\Controllers\API\PengajuanController;
use App\Http\Controllers\API\PengumumanController;
use App\Http\Controllers\API\SwaggerTestController;
use App\Http\Controllers\API\TipePengajuanController;
use App\Http\Controllers\API\UnitTypeController;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\VendorController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);

Route::get('swagger-test', [SwaggerTestController::class, 'index']);

Route::middleware('auth:sanctum')->group(function () {

    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::post('/profile', [UserController::class, 'updateProfile']);

    Route::get('/periode-aktif', [AktivasiController::class, 'current']);

    Route::apiResource('barang', BarangController::class);

    Route::apiResource('pengumuman', PengumumanController::class);

    Route::apiResource('aktivasi-pengajuan', AktivasiController::class);

    Route::post(
        'daftar-pengajuan/full',
        [DaftarPengajuanController::class, 'storeFull']
    );

    Route::apiResource('daftar-pengajuan', DaftarPengajuanController::class)
        ->except(['store']);

    Route::get('my-pengajuan', [PengajuanController::class, 'my']);

    Route::get('/histori', [PengajuanController::class, 'histori']);
    Route::get('/histori-pengajuan/my', [PengajuanController::class, 'historiMy']);
});

Route::middleware(['auth:sanctum', 'admin'])->group(function () {

    Route::apiResource('users', UserController::class);
    Route::post('users/sync', [UserController::class, 'syncFromApi']);
    Route::get('dropdown', [UserController::class, 'dropdown']);
    Route::get('rekap-vendor', [VendorController::class, 'rekapVendor']);
    Route::apiResource('vendors', VendorController::class);

    Route::patch(
        'aktivasi-pengajuan/{aktivasiPengajuan}/activate',
        [AktivasiController::class, 'activate']
    );

    Route::get(
        '/aktivasi-pengajuan/{id}/summary',
        [AktivasiController::class, 'summary']
    );

    Route::get(
        'pengajuan',
        [TipePengajuanController::class, 'index']
    );

    Route::apiResource(
        'jabatans',
        JabatanController::class
    );

    Route::apiResource(
        'unit-types',
        UnitTypeController::class
    );

    Route::get(
        'admin/daftar-pengajuan',
        [DaftarPengajuanController::class, 'adminIndex']
    );

    Route::get(
        'admin/aktivasi-pengajuan',
        [DaftarPengajuanController::class, 'adminAktivasi']
    );

    Route::patch(
        'admin/barang-pengajuan/{barangPengajuan}/approve',
        [BarangPengajuanController::class, 'approve']
    );

    Route::patch(
        'admin/barang-pengajuan-lainnya/{barangPengajuanLainnya}/approve',
        [BarangPengajuanLainnyaController::class, 'approve']
    );

    Route::get(
        '/admin/cetak-berkas',
        [CetakBerkasController::class, 'index']
    );
});
