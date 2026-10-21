<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use Illuminate\Support\Facades\Auth;
use App\Helpers\ApiResponse;

class AuthController extends Controller
{
    public function login(LoginRequest $request)
    {
        if (!Auth::attempt($request->validated())) {
            return ApiResponse::error('Username atau password salah.', 401);
        }

        $user = Auth::user()->load(['jabatan', 'unit']);

        $token = $user->createToken('auth_token')->plainTextToken;

        return ApiResponse::success([
            'token' => $token,
            'user' => $user
        ]);
    }

    public function me()
    {
        return ApiResponse::success(
            auth()->user()->load(['jabatan', 'unit'])
        );
    }

    public function logout()
    {
        auth()->user()->currentAccessToken()->delete();

        return ApiResponse::success(null, 'logout berhasil');
    }
}
