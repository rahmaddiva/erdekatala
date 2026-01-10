<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        // 0. Bersihkan data lama agar ID reset
        $this->db->query('SET FOREIGN_KEY_CHECKS=0;');
        $this->db->table('laporan_agregat')->truncate();
        $this->db->table('users')->truncate();
        $this->db->table('m_rt')->truncate();
        $this->db->table('m_dusun')->truncate();
        $this->db->table('m_desa')->truncate();
        $this->db->query('SET FOREIGN_KEY_CHECKS=1;');

        // 1. SEED MASTER DESA
        $this->db->table('m_desa')->insert([
            'nama_desa' => 'Kurau',
            'kode_desa' => '63.01.03.2001'
        ]);
        $idDesa = $this->db->insertID();

        // 2. SEED MASTER DUSUN
        $this->db->table('m_dusun')->insert(['id_desa' => $idDesa, 'nama_dusun' => 'Dusun I']);
        $idDusun = $this->db->insertID();

        // 3. SEED MASTER RT
        $this->db->table('m_rt')->insert(['id_dusun' => $idDusun, 'nomor_rt' => '001']);
        $idRt = $this->db->insertID();

        // 4. SEED USERS
        $this->db->table('users')->insert([
            'username' => 'adminkurau',
            'password' => password_hash('kurau123', PASSWORD_DEFAULT),
            'nama_lengkap' => 'Operator Desa Kurau',
            'role' => 'admin_desa',
            'id_desa' => $idDesa
        ]);
        $idUser = $this->db->insertID();

        // 5. SEED LAPORAN AGREGAT (Lengkap 20 Sheet)
        $data = [
            'id_rt' => $idRt,
            'id_user' => $idUser,
            'bulan' => 1,
            'tahun' => 2024,

            // SHEET 1 & 2: Jiwa & KK (Total Jiwa: 100, Total KK: 40)
            'jiwa_l' => 55,
            'jiwa_p' => 45,
            'kk_l' => 35,
            'kk_p' => 5,

            // SHEET 3: Pendidikan KK (Total: 40)
            'kk_pend_tidak_sekolah' => 2,
            'kk_pend_sd' => 10,
            'kk_pend_smp' => 8,
            'kk_pend_sma' => 15,
            'kk_pend_diploma' => 2,
            'kk_pend_s1' => 2,
            'kk_pend_s2_s3' => 1,

            // SHEET 4: Pekerjaan KK (Total: 40)
            'kk_ker_tani' => 15,
            'kk_ker_nelayan' => 5,
            'kk_ker_pns' => 3,
            'kk_ker_swasta' => 7,
            'kk_ker_pedagang' => 4,
            'kk_ker_wiraswasta' => 3,
            'kk_ker_buruh' => 2,
            'kk_ker_tidak_kerja' => 1,

            // SHEET 5: Piramida Penduduk (Total L: 55, Total P: 45 | Grand Total: 100)
            'u0_4_l' => 4,
            'u0_4_p' => 3,
            'u5_9_l' => 3,
            'u5_9_p' => 3,
            'u10_14_l' => 5,
            'u10_14_p' => 4,
            'u15_19_l' => 4,
            'u15_19_p' => 3,
            'u20_24_l' => 6,
            'u20_24_p' => 5,
            'u25_29_l' => 5,
            'u25_29_p' => 4,
            'u30_34_l' => 4,
            'u30_34_p' => 3,
            'u35_39_l' => 4,
            'u35_39_p' => 3,
            'u40_44_l' => 3,
            'u40_44_p' => 3,
            'u45_49_l' => 3,
            'u45_49_p' => 2,
            'u50_54_l' => 3,
            'u50_54_p' => 2,
            'u55_59_l' => 3,
            'u55_59_p' => 2,
            'u60_64_l' => 2,
            'u60_64_p' => 2,
            'u65_69_l' => 2,
            'u65_69_p' => 1,
            'u70_74_l' => 1,
            'u70_74_p' => 1,
            'u75_79_l' => 1,
            'u75_79_p' => 1,
            'u80_84_l' => 1,
            'u80_84_p' => 1,
            'u85_atas_l' => 1,
            'u85_atas_p' => 2,

            // SHEET 6: Kelompok Umur Khusus
            'jml_balita' => 7,
            'jml_remaja' => 15,
            'jml_lansia' => 10,

            // SHEET 7: KK Status Perkawinan (Total: 40)
            'kk_kawin' => 32,
            'kk_belum_kawin' => 2,
            'kk_cerai_hidup' => 3,
            'kk_cerai_mati' => 3,

            // SHEET 8 - 15: Dokumen & Kesehatan
            'pend_bpjs' => 85,
            'pend_non_bpjs' => 15,
            'kk_punya_kartu_fisik' => 38,
            'kk_belum_punya_kartu_fisik' => 2,
            'penduduk_hub_kepala' => 40,
            'penduduk_hub_istri' => 32,
            'penduduk_hub_anak' => 25,
            'penduduk_hub_lainnya' => 3,
            'pus_jkn' => 28,
            'pus_pbi' => 20,
            'pus_non_pbi' => 8,
            'jkn' => 30,
            'non_jkn' => 10,
            'pend_wajib_ktp' => 75,
            'kk_punya_akta_nikah' => 35,
            'pend_punya_akta_lahir' => 98,

            // SHEET 16 - 20: KB, PUS & BKB
            'kb_aktif' => 25,
            'jml_pus' => 32,
            'jml_penggunaan_alat_kontrasepsi' => 25,
        ];

        $this->db->table('laporan_agregat')->insert($data);
    }
}