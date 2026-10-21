<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Throwable;
use __PHP_Incomplete_Class;

class CacheGuard
{
    public const BATAS_BYTE = 1048576;

    /**
     * Nilai dari cache bisa kembali sebagai objek cacat kalau payload-nya
     * pernah terpotong saat disimpan. unserialize() tidak melempar apa pun
     * untuk kasus itu, jadi kerusakannya baru meledak saat objeknya dipakai.
     */
    public static function utuh($nilai): bool
    {
        if ($nilai instanceof __PHP_Incomplete_Class) {
            return false;
        }

        if ($nilai instanceof Collection) {
            foreach ($nilai as $item) {
                if ($item instanceof __PHP_Incomplete_Class) {
                    return false;
                }

                break;
            }
        }

        return true;
    }

    /**
     * Payload besar berisiko terpotong oleh kolom cache atau batas paket
     * database, dan hasilnya justru rusak saat dibaca kembali. Lebih baik
     * tidak disimpan sama sekali.
     */
    public static function layakSimpan($nilai): bool
    {
        try {
            return strlen(serialize($nilai)) <= self::batasByte();
        } catch (Throwable $e) {
            return false;
        }
    }

    /**
     * Batas 1 MB cocok untuk store database, yang memotong payload besar.
     * Redis tidak punya batas itu, jadi batasnya bisa dinaikkan lewat
     * CACHE_MAX_PAYLOAD_BYTES supaya endpoint besar ikut ter-cache.
     */
    /**
     * config('cache.serializable_classes') bernilai false, sehingga setiap
     * store membaca ulang objek sebagai __PHP_Incomplete_Class. Model dan
     * resource harus diubah jadi array biasa sebelum masuk cache, kalau tidak
     * nilainya selalu ditolak saat dibaca dan cache tidak pernah terpakai.
     *
     * Lewat JSON supaya hasilnya persis sama dengan respons API yang dikirim.
     */
    public static function polos($nilai): mixed
    {
        return json_decode(
            json_encode($nilai, JSON_THROW_ON_ERROR),
            true,
            512,
            JSON_THROW_ON_ERROR
        );
    }

    public static function batasByte(): int
    {
        try {
            $batas = (int) config('cache.max_payload_bytes', self::BATAS_BYTE);
        } catch (Throwable $e) {
            $batas = self::BATAS_BYTE;
        }

        return $batas > 0 ? $batas : self::BATAS_BYTE;
    }
}
