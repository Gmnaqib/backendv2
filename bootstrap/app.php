<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function ($middleware) {
        $middleware->alias([
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
        ]);

        // API-only: tidak ada route 'login' untuk dituju. Tanpa ini middleware
        // auth melempar RouteNotFoundException dan permintaan tanpa token
        // dijawab 500, bukan 401 seperti yang ditangani di withExceptions.
        $middleware->redirectGuestsTo(fn () => null);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->renderable(function (\Throwable $e, $request) {
            if ($request->is('api/*')) {
                if ($e instanceof \Illuminate\Http\Exceptions\PostTooLargeException) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Ukuran file terlalu besar. Maksimal pengunggahan tidak boleh melebihi batas server.',
                    ], 413);
                }

                if ($e instanceof \Illuminate\Auth\AuthenticationException) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Anda belum login atau sesi telah berakhir.'
                    ], 401);
                }

                if ($e instanceof \Illuminate\Auth\Access\AuthorizationException || $e instanceof \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Anda tidak memiliki akses untuk melakukan tindakan ini.'
                    ], 403);
                }

                if ($e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Data tidak ditemukan.'
                    ], 404);
                }

                if ($e instanceof \Symfony\Component\HttpKernel\Exception\NotFoundHttpException) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Alamat atau rute tidak ditemukan.'
                    ], 404);
                }

                if ($e instanceof \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Metode permintaan tidak didukung.'
                    ], 405);
                }

                if ($e instanceof \Illuminate\Validation\ValidationException) {
                    return response()->json([
                        'success' => false,
                        'message' => $e->getMessage(),
                        'errors' => $e->errors()
                    ], 422);
                }

                if ($e instanceof \Illuminate\Database\QueryException || $e instanceof \PDOException) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Terjadi kesalahan pada database sistem. Silakan coba lagi.'
                    ], 500);
                }

                return response()->json([
                    'success' => false,
                    'message' => 'Terjadi kesalahan pada server. Silakan hubungi administrator atau coba lagi nanti.'
                ], 500);
            }
        });
    })->create();
