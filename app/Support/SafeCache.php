<?php

namespace App\Support;

use Closure;
use Illuminate\Support\Facades\Cache;
use Throwable;

class SafeCache
{
    public static function remember(string $kunci, int $ttl, Closure $callback)
    {
        $bolehSimpan = true;

        try {
            $tersimpan = Cache::get($kunci);

            if ($tersimpan !== null && CacheGuard::utuh($tersimpan)) {
                return $tersimpan;
            }
        } catch (Throwable $e) {
            $bolehSimpan = false;
        }

        $nilai = $callback();

        if ($bolehSimpan && CacheGuard::layakSimpan($nilai)) {
            try {
                Cache::put($kunci, $nilai, $ttl);
            } catch (Throwable $e) {

            }
        }

        return $nilai;
    }

    public static function forget(string $kunci): void
    {
        try {
            Cache::forget($kunci);
        } catch (Throwable $e) {

        }
    }
}
