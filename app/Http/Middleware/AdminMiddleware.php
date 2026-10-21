<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AdminMiddleware
{
    public function handle($request, Closure $next)
    {

        if (!auth()->check()) {

            return response()->json([
                'success' => false,
                'message' => 'unauthenticated'
            ], 401);
        }

        if (auth()->user()->role !== 'admin') {

            return response()->json([
                'success' => false,
                'message' => 'akses ditolak'
            ], 403);
        }

        return $next($request);
    }
}
