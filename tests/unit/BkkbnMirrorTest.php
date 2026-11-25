<?php

namespace Tests\Unit;

use App\Libraries\BkkbnMirrorService;
use CodeIgniter\Test\CIUnitTestCase;
use ReflectionClass;

/**
 * Regresi untuk perbaikan cache BKKBN 2026-11-25:
 * - laporan YanKB/Dallap butuh timeout 90 dtk di CLI (JasperReports 35–50 dtk);
 *   timeout lama 15 dtk membuat warmup selalu gagal.
 * - Pesan fallback cache harus menyebut tanggal cache yang sebenarnya.
 */
final class BkkbnMirrorTest extends CIUnitTestCase
{
    private ReflectionClass $ref;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ref = new ReflectionClass(BkkbnMirrorService::class);
    }

    private function panggil(string $method, array $args = [])
    {
        $m = $this->ref->getMethod($method);
        $m->setAccessible(true);

        return $m->invoke($this->ref->newInstance(), ...$args);
    }

    public function testTimeoutLaporanCliMinimalSembilanPuluhDetik(): void
    {
        // PHPUnit berjalan di CLI, jadi jalur CLI yang diuji.
        $this->assertSame(90, $this->panggil('laporanTimeout'));
    }

    public function testCacheTimestampMemakaiMtimeFile(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'bkkbn_');
        file_put_contents($file, 'x');
        touch($file, strtotime('2026-11-20 19:13:27'));

        try {
            $this->assertSame('20 Nov 2026, 19:13', $this->panggil('cacheTimestamp', [$file]));
        } finally {
            @unlink($file);
        }
    }

    public function testCacheTimestampFileHilangTidakMelemparError(): void
    {
        $hasil = $this->panggil('cacheTimestamp', [sys_get_temp_dir() . '/tidak-ada-' . uniqid() . '.json']);

        $this->assertMatchesRegularExpression('/^\d{2} [A-Z][a-z]{2} \d{4}, \d{2}:\d{2}$/', $hasil);
    }
}
