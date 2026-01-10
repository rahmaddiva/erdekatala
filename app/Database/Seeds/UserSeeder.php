<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run()
    {
        $db = \Config\Database::connect();
        $users = [];

        // 1. Tambahkan 2 Admin Dinas
        $users[] = [
            'username' => 'admin_dinas1',
            'password' => password_hash('password123', PASSWORD_DEFAULT),
            'nama_lengkap' => 'Administrator Dinas 1',
            'role' => 'admin_dinas',
            'id_desa' => null, // Admin dinas tidak terikat desa spesifik
            'created_at' => date('Y-m-d H:i:s'),
        ];

        $users[] = [
            'username' => 'admin_dinas2',
            'password' => password_hash('password123', PASSWORD_DEFAULT),
            'nama_lengkap' => 'Administrator Dinas 2',
            'role' => 'admin_dinas',
            'id_desa' => null,
            'created_at' => date('Y-m-d H:i:s'),
        ];

        // 2. Ambil semua data desa dari tabel m_desa
        $allDesa = $db->table('m_desa')->get()->getResultArray();

        foreach ($allDesa as $desa) {
            // Membuat username dari nama desa (contoh: "Desa Kurau" -> "admin_kurau")
            $cleanName = strtolower(str_replace(' ', '_', $desa['nama_desa']));

            $users[] = [
                'username' => 'admin_' . $cleanName,
                'password' => password_hash('desa123', PASSWORD_DEFAULT), // Password default
                'nama_lengkap' => 'Admin ' . $desa['nama_desa'],
                'role' => 'admin_desa',
                'id_desa' => $desa['id_desa'], // Relasi ke ID desa
                'created_at' => date('Y-m-d H:i:s'),
            ];
        }

        // 3. Insert Batch ke tabel users
        $db->table('users')->insertBatch($users);
    }
}