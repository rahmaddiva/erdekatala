<?php

namespace Tests\Unit;

use App\Libraries\LoginThrottle;
use CodeIgniter\Test\CIUnitTestCase;

final class LoginThrottleTest extends CIUnitTestCase
{
    private string $tmp;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tmp = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'lt_test_' . bin2hex(random_bytes(4));
        mkdir($this->tmp, 0777, true);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->tmp . DIRECTORY_SEPARATOR . '*') ?: [] as $f) {
            @unlink($f);
        }
        @rmdir($this->tmp);
        parent::tearDown();
    }

    private function throttle(): LoginThrottle
    {
        return new LoginThrottle($this->tmp);
    }

    private function fileUntuk(string $username, string $ip): string
    {
        return $this->tmp . DIRECTORY_SEPARATOR . 'login_bf_' . hash('sha256', strtolower($username) . '|' . $ip) . '.json';
    }

    public function testEmpatKegagalanBelumTerkunci(): void
    {
        $t = $this->throttle();
        for ($i = 0; $i < 4; $i++) {
            $t->registerFailure('budi', '1.2.3.4');
        }
        $this->assertFalse($t->isLocked('budi', '1.2.3.4'));
    }

    public function testKegagalanKelimaMengunci(): void
    {
        $t = $this->throttle();
        for ($i = 0; $i < 5; $i++) {
            $t->registerFailure('budi', '1.2.3.4');
        }
        $sisa = $t->isLocked('budi', '1.2.3.4');
        $this->assertIsInt($sisa);
        $this->assertGreaterThan(0, $sisa);
        $this->assertLessThanOrEqual(LoginThrottle::LOCK_SECONDS, $sisa);
    }

    public function testKombinasiLainTidakTerpengaruh(): void
    {
        $t = $this->throttle();
        for ($i = 0; $i < 5; $i++) {
            $t->registerFailure('budi', '1.2.3.4');
        }
        $this->assertFalse($t->isLocked('budi', '5.6.7.8'));   // IP berbeda
        $this->assertFalse($t->isLocked('ani', '1.2.3.4'));    // username berbeda
    }

    public function testUsernameCaseInsensitive(): void
    {
        $t = $this->throttle();
        for ($i = 0; $i < 5; $i++) {
            $t->registerFailure('Budi', '1.2.3.4');
        }
        $this->assertIsInt($t->isLocked('budi', '1.2.3.4'));
    }

    public function testClearMenghapusKunci(): void
    {
        $t = $this->throttle();
        for ($i = 0; $i < 5; $i++) {
            $t->registerFailure('budi', '1.2.3.4');
        }
        $t->clear('budi', '1.2.3.4');
        $this->assertFalse($t->isLocked('budi', '1.2.3.4'));
        $this->assertFileDoesNotExist($this->fileUntuk('budi', '1.2.3.4'));
    }

    public function testJendelaLewatResetCounter(): void
    {
        $t = $this->throttle();
        for ($i = 0; $i < 4; $i++) {
            $t->registerFailure('budi', '1.2.3.4');
        }

        // Mundurkan window_start melewati jendela 15 menit
        $file = $this->fileUntuk('budi', '1.2.3.4');
        $state = json_decode((string) file_get_contents($file), true);
        $state['window_start'] = time() - LoginThrottle::WINDOW_SECONDS - 10;
        file_put_contents($file, json_encode($state));

        $t->registerFailure('budi', '1.2.3.4'); // dihitung sebagai percobaan ke-1 jendela baru
        $this->assertFalse($t->isLocked('budi', '1.2.3.4'));

        $state = json_decode((string) file_get_contents($file), true);
        $this->assertSame(1, $state['count']);
    }

    public function testKunciKadaluarsaMendapatLimaPercobaanBaru(): void
    {
        $t = $this->throttle();
        for ($i = 0; $i < 5; $i++) {
            $t->registerFailure('budi', '1.2.3.4');
        }

        // Mundurkan locked_until dan window_start melewati masa kunci
        $file = $this->fileUntuk('budi', '1.2.3.4');
        $state = json_decode((string) file_get_contents($file), true);
        $state['locked_until'] = time() - 1;
        $state['window_start'] = time() - LoginThrottle::WINDOW_SECONDS - LoginThrottle::LOCK_SECONDS - 10;
        file_put_contents($file, json_encode($state));

        $this->assertFalse($t->isLocked('budi', '1.2.3.4'));

        for ($i = 0; $i < 4; $i++) {
            $t->registerFailure('budi', '1.2.3.4');
        }
        $this->assertFalse($t->isLocked('budi', '1.2.3.4')); // baru 4 dari 5
        $t->registerFailure('budi', '1.2.3.4');
        $this->assertIsInt($t->isLocked('budi', '1.2.3.4')); // terkunci lagi
    }

    public function testClearPadaFileTidakAdaAman(): void
    {
        $t = $this->throttle();
        $t->clear('tidak_ada', '9.9.9.9'); // tidak boleh error
        $this->assertFalse($t->isLocked('tidak_ada', '9.9.9.9'));
    }
}
