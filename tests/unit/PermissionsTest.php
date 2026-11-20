<?php

namespace Tests\Unit;

use App\Libraries\Permissions;
use CodeIgniter\Test\CIUnitTestCase;

final class PermissionsTest extends CIUnitTestCase
{
    public function testSemuaDefaultMemakaiKeyKatalog(): void
    {
        $all = Permissions::all();

        foreach (Permissions::defaults() as $role => $perms) {
            $this->assertNotEmpty($perms, "Role $role tidak punya permission default.");
            foreach ($perms as $perm) {
                $this->assertContains($perm, $all, "Permission '$perm' pada role $role tidak ada di katalog.");
            }
        }
    }

    public function testSanitizeMembuangKeyTidakDikenal(): void
    {
        $hasil = Permissions::sanitize(['laporan.view', 'tidak.ada', 'roles.manage']);

        $this->assertSame(['laporan.view', 'roles.manage'], $hasil);
        $this->assertSame([], Permissions::sanitize('bukan-array'));
    }

    public function testTidakAdaKeyDuplikatAntarGrup(): void
    {
        $keys = [];
        foreach (Permissions::GROUPS as $items) {
            foreach (array_keys($items) as $key) {
                $this->assertArrayNotHasKey($key, $keys, "Key '$key' terdaftar dua kali.");
                $keys[$key] = true;
            }
        }
    }
}
