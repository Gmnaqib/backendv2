<?php

namespace App\Support;

use Closure;
use Illuminate\Support\Facades\Cache;
use Throwable;
use App\Support\CacheGuard;

class PengajuanCache
{
    private const VERSION_KEY = 'pengajuan:version';

    private const TTL = 300;

    public static function remember(string $nama, array $konteks, Closure $callback)
    {
        $kunci = null;

        try {
            $kunci = self::kunci($nama, $konteks);

            $tersimpan = Cache::get($kunci);

            if ($tersimpan !== null && CacheGuard::utuh($tersimpan)) {
                return $tersimpan;
            }
        } catch (Throwable $e) {
            $kunci = null;
        }

        $nilai = $callback();

        if ($kunci !== null && CacheGuard::layakSimpan($nilai)) {
            try {
                Cache::put($kunci, $nilai, self::TTL);
            } catch (Throwable $e) {
                // cache gagal disimpan, data tetap dikembalikan apa adanya
            }
        }

        return $nilai;
    }

    public static function flush(): void
    {
        try {
            if (Cache::has(self::VERSION_KEY)) {
                Cache::increment(self::VERSION_KEY);

                return;
            }

            Cache::forever(self::VERSION_KEY, 2);
        } catch (Throwable $e) {
            // kegagalan cache tidak boleh menggagalkan penyimpanan data
        }
    }

    private static function versi(): int
    {
        return (int) Cache::get(self::VERSION_KEY, 1);
    }

    private static function kunci(string $nama, array $konteks): string
    {
        $konteks = array_filter($konteks, fn ($v) => $v !== null && $v !== '');

        ksort($konteks);

        return sprintf(
            'pengajuan:v%d:%s:%s',
            self::versi(),
            $nama,
            md5(json_encode($konteks))
        );
    }
}
