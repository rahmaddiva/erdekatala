<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        // 0. Bersihkan semua tabel agar ID reset dan tidak duplikat
        $this->db->query('SET FOREIGN_KEY_CHECKS=0;');
        $this->db->table('laporan_agregat')->truncate();
        $this->db->table('users')->truncate();
        $this->db->table('m_rt')->truncate();
        $this->db->table('m_dusun')->truncate();
        $this->db->table('m_desa')->truncate();
        $this->db->table('kecamatan')->truncate();
        $this->db->query('SET FOREIGN_KEY_CHECKS=1;');

        // 1. Seed wilayah (kecamatan & desa)
        $this->call('WilayahSeeder');

        // 2. Seed dusun (bergantung pada m_desa)
        $this->call('DusunSeeder');

        // 3. Seed RT (bergantung pada m_dusun)
        $this->call('RtSeeder');

        // 4. Seed users (bergantung pada kecamatan & desa)
        $this->call('UserSeeder');

        // 5. Seed laporan agregat (bergantung pada m_rt & users)
        $this->call('LaporanAgregatSeeder');
    }
}
