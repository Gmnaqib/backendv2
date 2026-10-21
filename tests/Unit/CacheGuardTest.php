<?php

namespace Tests\Unit;

use App\Support\CacheGuard;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;

class CacheGuardTest extends TestCase
{
    private function objekCacat()
    {
        return @unserialize('O:19:"KelasYangTidakAda00":0:{}');
    }

    public function test_objek_cacat_ditolak(): void
    {
        $this->assertFalse(CacheGuard::utuh($this->objekCacat()));
    }

    public function test_koleksi_berisi_objek_cacat_ditolak(): void
    {
        $koleksi = new Collection([$this->objekCacat()]);

        $this->assertFalse(CacheGuard::utuh($koleksi));
    }

    public function test_nilai_wajar_diterima(): void
    {
        $this->assertTrue(CacheGuard::utuh(new Collection([1, 2, 3])));
        $this->assertTrue(CacheGuard::utuh(['a' => 1]));
        $this->assertTrue(CacheGuard::utuh('teks'));
    }

    public function test_payload_kelewat_besar_tidak_disimpan(): void
    {
        $besar = str_repeat('x', CacheGuard::BATAS_BYTE + 1);

        $this->assertFalse(CacheGuard::layakSimpan($besar));
        $this->assertTrue(CacheGuard::layakSimpan('kecil'));
    }
}
