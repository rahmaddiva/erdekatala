<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run()
    {
        $db = \Config\Database::connect();

        // 1. Admin Dinas (Global)
        $db->table('users')->insert([
            'username' => 'admin_dinas',
            'password' => password_hash('password123', PASSWORD_DEFAULT),
            'nama_lengkap' => 'Admin Kabupaten Tanah Laut',
            'role' => 'admin_dinas',
            'id_kecamatan' => null,
            'id_desa' => null
        ]);

        // 2. Admin Kecamatan (Loop semua kecamatan)
        $kecamatans = $db->table('kecamatan')->get()->getResultArray();
        foreach ($kecamatans as $kec) {
            $username = 'admin_kec_' . strtolower(str_replace(' ', '', $kec['nama_kecamatan']));
            $db->table('users')->insert([
                'username' => $username,
                'password' => password_hash('kecamatan123', PASSWORD_DEFAULT),
                'nama_lengkap' => 'Admin Kec. ' . $kec['nama_kecamatan'],
                'role' => 'admin_kecamatan',
                'id_kecamatan' => $kec['id_kecamatan'], // Terikat ke Kecamatan ini
                'id_desa' => null
            ]);
        }

        // 3. Admin Desa (Loop semua desa)
        $desas = $db->table('m_desa')->get()->getResultArray();
        foreach ($desas as $desa) {
            $username = 'admin_desa_' . strtolower(str_replace(' ', '', $desa['nama_desa']));
            $db->table('users')->insert([
                'username' => $username,
                'password' => password_hash('desa123', PASSWORD_DEFAULT),
                'nama_lengkap' => 'Admin Desa ' . $desa['nama_desa'],
                'role' => 'admin_desa',
                'id_kecamatan' => $desa['id_kecamatan'],
                'id_desa' => $desa['id_desa']
            ]);
        }
    }
}