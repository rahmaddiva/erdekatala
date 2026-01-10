<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class DusunSeeder extends Seeder
{
    public function run()
    {
        $db = \Config\Database::connect();
        $allDesa = $db->table('m_desa')->get()->getResultArray();
        $data = [];

        foreach ($allDesa as $desa) {
            // Membuat 2 Dusun untuk setiap desa secara otomatis
            for ($i = 1; $i <= 2; $i++) {
                $data[] = [
                    'id_desa' => $desa['id_desa'],
                    'nama_dusun' => 'Dusun ' . $i . ' ' . $desa['nama_desa'],
                ];
            }
        }

        $db->table('m_dusun')->insertBatch($data);
    }
}