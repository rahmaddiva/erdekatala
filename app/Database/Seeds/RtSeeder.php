<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class RtSeeder extends Seeder
{
    public function run()
    {
        $db = \Config\Database::connect();
        $allDusun = $db->table('m_dusun')->get()->getResultArray();
        $data = [];

        foreach ($allDusun as $dusun) {
            // Membuat 3 RT untuk setiap Dusun
            for ($i = 1; $i <= 3; $i++) {
                $data[] = [
                    'id_dusun' => $dusun['id_dusun'],
                    'no_rt' => str_pad($i, 3, '0', STR_PAD_LEFT), // Hasil: 001, 002, 003
                ];
            }
        }

        $db->table('m_rt')->insertBatch($data);
    }
}