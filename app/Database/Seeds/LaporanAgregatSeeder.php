<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class LaporanAgregatSeeder extends Seeder
{
    public function run()
    {
        $db = \Config\Database::connect();

        // 1. Ambil semua RT dan User Admin Desa
        $allRT = $db->table('m_rt')->get()->getResultArray();
        $adminDesa = $db->table('users')->where('role', 'admin_desa')->get()->getResultArray();

        if (empty($allRT) || empty($adminDesa)) {
            echo "Error: Pastikan tabel m_rt dan users sudah terisi sebelum menjalankan seeder ini.\n";
            return;
        }

        $data = [];
        $bulan = date('n'); // Bulan sekarang (1-12)
        $tahun = 2026;      // Tahun sesuai periode aplikasi

        foreach ($allRT as $rt) {
            // Cari user yang bertugas di desa tempat RT ini berada
            // (Asumsi: m_rt join ke m_dusun join ke m_desa)
            $desaOfRt = $db->table('m_rt')
                ->select('m_dusun.id_desa')
                ->join('m_dusun', 'm_dusun.id_dusun = m_rt.id_dusun')
                ->where('m_rt.id_rt', $rt['id_rt'])
                ->get()->getRow();

            $user = $db->table('users')
                ->where('id_desa', $desaOfRt->id_desa)
                ->get()->getRow();

            $id_user = $user ? $user->id_user : $adminDesa[0]['id_user'];

            // Angka Dasar Populasi per RT (Misal: 50 - 150 jiwa)
            $jiwaL = rand(30, 70);
            $jiwaP = rand(30, 70);
            $totalJiwa = $jiwaL + $jiwaP;

            $data[] = [
                'id_rt' => $rt['id_rt'],
                'id_user' => $id_user,
                'bulan' => $bulan,
                'tahun' => $tahun,

                // SHEET 1 & 2: Populasi & KK
                'jiwa_l' => $jiwaL,
                'jiwa_p' => $jiwaP,
                'kk_l' => rand(20, 30),
                'kk_p' => rand(2, 8),

                // SHEET 3: Pendidikan (Total harus mendekati KK)
                'kk_pend_tidak_sekolah' => rand(1, 3),
                'kk_pend_sd' => rand(5, 10),
                'kk_pend_smp' => rand(5, 8),
                'kk_pend_sma' => rand(5, 10),
                'kk_pend_diploma' => rand(0, 2),
                'kk_pend_s1' => rand(1, 4),
                'kk_pend_s2_s3' => rand(0, 1),

                // SHEET 4: Pekerjaan
                'kk_ker_tani' => rand(10, 20),
                'kk_ker_nelayan' => rand(0, 5),
                'kk_ker_pns' => rand(1, 3),
                'kk_ker_swasta' => rand(5, 10),
                'kk_ker_pedagang' => rand(2, 5),
                'kk_ker_wiraswasta' => rand(2, 5),
                'kk_ker_buruh' => rand(5, 10),
                'kk_ker_tidak_kerja' => rand(1, 5),

                // SHEET 5: Piramida Penduduk (Distribusi Umur)
                'u0_4_l' => rand(2, 5),
                'u0_4_p' => rand(2, 5),
                'u5_9_l' => rand(2, 5),
                'u5_9_p' => rand(2, 5),
                'u10_14_l' => rand(3, 6),
                'u10_14_p' => rand(3, 6),
                'u15_19_l' => rand(3, 6),
                'u15_19_p' => rand(3, 6),
                'u20_24_l' => rand(4, 7),
                'u20_24_p' => rand(4, 7),
                'u25_29_l' => rand(4, 7),
                'u25_29_p' => rand(4, 7),
                'u30_34_l' => rand(4, 7),
                'u30_34_p' => rand(4, 7),
                'u35_39_l' => rand(3, 6),
                'u35_39_p' => rand(3, 6),
                'u40_44_l' => rand(3, 6),
                'u40_44_p' => rand(3, 6),
                'u45_49_l' => rand(2, 5),
                'u45_49_p' => rand(2, 5),
                'u50_54_l' => rand(2, 5),
                'u50_54_p' => rand(2, 5),
                'u55_59_l' => rand(1, 4),
                'u55_59_p' => rand(1, 4),
                'u60_64_l' => rand(1, 3),
                'u60_64_p' => rand(1, 3),
                'u65_69_l' => rand(1, 2),
                'u65_69_p' => rand(1, 2),
                'u70_74_l' => rand(0, 2),
                'u70_74_p' => rand(0, 2),
                'u75_79_l' => rand(0, 1),
                'u75_79_p' => rand(0, 1),
                'u80_84_l' => rand(0, 1),
                'u80_84_p' => rand(0, 1),
                'u85_atas_l' => rand(0, 1),
                'u85_atas_p' => rand(0, 1),

                // SHEET 6: Kelompok Khusus
                'jml_balita' => rand(5, 10),
                'jml_remaja' => rand(10, 15),
                'jml_lansia' => rand(5, 8),

                // SHEET 7: Status Perkawinan
                'kk_kawin' => rand(15, 25),
                'kk_belum_kawin' => rand(5, 10),
                'kk_cerai_hidup' => rand(1, 3),
                'kk_cerai_mati' => rand(1, 2),

                // SHEET 8+: Kesehatan & Dokumen
                'pend_bpjs' => round($totalJiwa * 0.8), // 80% punya BPJS
                'pend_non_bpjs' => round($totalJiwa * 0.2),
                'pend_wajib_ktp' => round($totalJiwa * 0.7),
                'kk_punya_kartu_fisik' => rand(20, 30),
                'kk_belum_punya_kartu_fisik' => rand(1, 5),
                'penduduk_hub_kepala' => $totalJiwa - rand(5, 10),
                'penduduk_hub_istri' => rand(10, 15),
                'penduduk_hub_anak' => rand(15, 25),
                'penduduk_hub_lainnya' => rand(1, 5),
                'pus_jkn' => round($totalJiwa * 0.75),
                'pus_pbi' => round($totalJiwa * 0.15),
                'pus_non_pbi' => round($totalJiwa * 0.10),
                'jkn' => round($totalJiwa * 0.8),
                'non_jkn' => round($totalJiwa * 0.2),
                'kk_punya_akta_nikah' => rand(20, 30),
                'pend_punya_akta_lahir' => $totalJiwa - rand(0, 5),
                'kb_aktif' => rand(15, 25),
                'jml_pus' => rand(20, 30),
                'jml_penggunaan_alat_kontrasepsi' => rand(15, 25),
            ];
        }

        // Simpan ke database
        // kosongkan tabel terlebih dahulu untuk menghindari duplikasi
        $db->table('laporan_agregat')->truncate();
        $db->table('laporan_agregat')->insertBatch($data);
    }
}