<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class LaporanAgregatSeeder extends Seeder
{
    private function getKecamatanData(): array
    {
        return [
        'Takisung' => [
            'pop_l' => 17633, 'pop_p' => 17109,
            'kk_l' => 9614, 'kk_p' => 2581, 'kk_total' => 12195,
            'age_l' => [1168, 1481, 1515, 1413, 1474, 1306, 1264, 1232, 1362, 1377, 1166, 936, 690, 578, 354, 317],
            'age_p' => [1036, 1358, 1502, 1353, 1367, 1225, 1276, 1224, 1326, 1360, 1080, 905, 797, 524, 334, 442],
            'edu' => [10288, 3531, 10919, 5101, 3881, 103, 190, 713, 15, 1],
            'emp' => [12173, 389, 103, 4836, 4350, 2095, 13, 2425, 21, 76, 8261],
            'mar' => [15012, 17119, 793, 1818],
        ],
        'Jorong' => [
            'pop_l' => 18999, 'pop_p' => 18123,
            'kk_l' => 10208, 'kk_p' => 2474, 'kk_total' => 12682,
            'age_l' => [1407, 1728, 1826, 1629, 1645, 1462, 1339, 1455, 1470, 1418, 1186, 862, 644, 403, 267, 258],
            'age_p' => [1294, 1702, 1731, 1508, 1480, 1415, 1353, 1521, 1507, 1285, 1064, 805, 584, 403, 213, 258],
            'edu' => [11712, 4049, 9275, 5605, 5364, 98, 236, 759, 24, 0],
            'emp' => [13317, 348, 147, 8043, 2386, 836, 17, 3267, 30, 44, 8687],
            'mar' => [17005, 17883, 812, 1422],
        ],
        'Pelaihari' => [
            'pop_l' => 42348, 'pop_p' => 41879,
            'kk_l' => 22811, 'kk_p' => 6384, 'kk_total' => 29195,
            'age_l' => [2861, 3685, 3780, 3586, 3574, 3246, 3172, 3022, 3161, 3053, 2696, 2308, 1677, 1239, 652, 636],
            'age_p' => [2658, 3527, 3524, 3371, 3314, 3239, 3228, 3249, 3277, 3077, 2709, 2257, 1716, 1214, 685, 834],
            'edu' => [23683, 7886, 15738, 12050, 16992, 413, 1836, 5242, 384, 3],
            'emp' => [26647, 4274, 543, 19620, 6505, 45, 45, 9657, 286, 806, 15799],
            'mar' => [37680, 40106, 2408, 4033],
        ],
        'Kurau' => [
            'pop_l' => 7528, 'pop_p' => 7389,
            'kk_l' => 4061, 'kk_p' => 1313, 'kk_total' => 5374,
            'age_l' => [542, 631, 639, 602, 677, 574, 570, 548, 573, 514, 542, 398, 291, 228, 98, 101],
            'age_p' => [470, 602, 643, 575, 644, 508, 556, 573, 519, 557, 491, 418, 272, 247, 129, 185],
            'edu' => [4435, 1376, 4917, 2053, 1666, 42, 102, 320, 6, 0],
            'emp' => [5238, 218, 40, 1939, 2931, 197, 2, 1270, 9, 42, 3031],
            'mar' => [6625, 7113, 307, 872],
        ],
        'Bati-Bati' => [
            'pop_l' => 24263, 'pop_p' => 23332,
            'kk_l' => 13020, 'kk_p' => 3608, 'kk_total' => 16628,
            'age_l' => [1540, 2087, 2189, 2016, 1971, 1896, 1870, 1903, 1889, 1890, 1541, 1253, 887, 639, 344, 348],
            'age_p' => [1416, 1950, 1975, 1918, 1871, 1745, 1825, 1834, 1995, 1816, 1489, 1243, 868, 607, 325, 455],
            'edu' => [14178, 4920, 12881, 7247, 6779, 142, 258, 1161, 29, 0],
            'emp' => [15791, 518, 177, 11591, 4258, 48, 20, 4766, 36, 116, 10274],
            'mar' => [21255, 23185, 1026, 2129],
        ],
        'Panyipatan' => [
            'pop_l' => 13545, 'pop_p' => 13279,
            'kk_l' => 7369, 'kk_p' => 2150, 'kk_total' => 9519,
            'age_l' => [949, 1172, 1224, 1039, 1120, 1058, 1015, 1007, 1042, 979, 905, 681, 501, 370, 234, 249],
            'age_p' => [865, 1049, 1106, 1052, 1052, 972, 997, 987, 1071, 951, 875, 733, 550, 430, 198, 391],
            'edu' => [8108, 3816, 8355, 3378, 2501, 53, 136, 466, 10, 1],
            'emp' => [8324, 238, 91, 2776, 6235, 698, 8, 2770, 10, 29, 5645],
            'mar' => [11398, 13470, 594, 1362],
        ],
        'Kintap' => [
            'pop_l' => 23422, 'pop_p' => 22325,
            'kk_l' => 12413, 'kk_p' => 2977, 'kk_total' => 15390,
            'age_l' => [1662, 2186, 2385, 2123, 1993, 1847, 1712, 1712, 1874, 1699, 1474, 1006, 705, 460, 274, 310],
            'age_p' => [1511, 2100, 2219, 1964, 1957, 1623, 1773, 1757, 1884, 1556, 1333, 894, 641, 468, 260, 385],
            'edu' => [14711, 4512, 13372, 6351, 5825, 84, 217, 662, 12, 1],
            'emp' => [17200, 303, 206, 10876, 2423, 615, 14, 3534, 43, 15, 10518],
            'mar' => [21188, 22062, 762, 1735],
        ],
        'Tambang Ulang' => [
            'pop_l' => 9976, 'pop_p' => 9635,
            'kk_l' => 5427, 'kk_p' => 1530, 'kk_total' => 6957,
            'age_l' => [698, 847, 887, 842, 808, 772, 756, 720, 794, 745, 614, 465, 374, 287, 186, 181],
            'age_p' => [649, 847, 800, 819, 812, 740, 703, 736, 780, 728, 572, 464, 354, 259, 159, 213],
            'edu' => [6153, 2044, 5499, 3067, 2371, 45, 111, 310, 10, 1],
            'emp' => [6532, 203, 53, 3982, 2724, 10, 10, 1876, 10, 30, 4181],
            'mar' => [8646, 9525, 518, 922],
        ],
        'Batu Ampar' => [
            'pop_l' => 15063, 'pop_p' => 14324,
            'kk_l' => 8483, 'kk_p' => 1786, 'kk_total' => 10269,
            'age_l' => [1071, 1260, 1296, 1252, 1212, 1166, 1035, 988, 1087, 1158, 1035, 795, 649, 432, 281, 346],
            'age_p' => [973, 1077, 1259, 1162, 1216, 1065, 1021, 1056, 1091, 1148, 930, 701, 573, 406, 273, 373],
            'edu' => [8960, 2633, 8049, 4776, 3953, 74, 217, 707, 17, 1],
            'emp' => [9826, 293, 123, 5314, 6172, 6, 11, 2209, 31, 39, 5363],
            'mar' => [12337, 15073, 642, 1335],
        ],
        'Bajuin' => [
            'pop_l' => 10552, 'pop_p' => 10124,
            'kk_l' => 5866, 'kk_p' => 1585, 'kk_total' => 7451,
            'age_l' => [738, 836, 942, 867, 892, 838, 777, 778, 798, 802, 694, 517, 354, 304, 201, 214],
            'age_p' => [658, 842, 872, 837, 866, 719, 791, 810, 788, 764, 629, 504, 360, 311, 155, 218],
            'edu' => [6636, 1962, 6223, 2931, 2399, 39, 152, 322, 9, 3],
            'emp' => [7353, 184, 64, 3847, 3436, 8, 5, 1373, 6, 41, 4359],
            'mar' => [8860, 10275, 597, 944],
        ],
        'Bumi Makmur' => [
            'pop_l' => 7430, 'pop_p' => 7305,
            'kk_l' => 3905, 'kk_p' => 1343, 'kk_total' => 5248,
            'age_l' => [510, 665, 631, 617, 634, 549, 563, 630, 546, 536, 461, 359, 303, 223, 103, 100],
            'age_p' => [470, 613, 631, 606, 591, 520, 522, 589, 508, 528, 438, 412, 343, 224, 106, 204],
            'edu' => [3666, 1789, 5196, 2169, 1586, 29, 49, 247, 4, 0],
            'emp' => [5141, 118, 21, 1582, 2888, 604, 7, 1347, 7, 14, 3006],
            'mar' => [6677, 6883, 278, 897],
        ],
        ];
    }

    private function distribute(int $total, int $numParts): array
    {
        if ($numParts <= 0) return [];
        if ($total <= 0) return array_fill(0, $numParts, 0);

        $base = $total / $numParts;
        $values = [];
        $sum = 0;

        for ($i = 0; $i < $numParts - 1; $i++) {
            $variance = 0.9 + (mt_rand(0, 2000) / 10000);
            $values[$i] = max(0, (int)round($base * $variance));
            $sum += $values[$i];
        }

        $values[$numParts - 1] = max(0, $total - $sum);
        return $values;
    }

    private function mapKecamatanData(array $kd): array
    {
        $u75_79_l = (int)round($kd['age_l'][15] * 0.5);
        $u80_84_l = (int)round($kd['age_l'][15] * 0.3);
        $u85_l = $kd['age_l'][15] - $u75_79_l - $u80_84_l;
        $u75_79_p = (int)round($kd['age_p'][15] * 0.5);
        $u80_84_p = (int)round($kd['age_p'][15] * 0.3);
        $u85_p = $kd['age_p'][15] - $u75_79_p - $u80_84_p;

        $wira = $kd['emp'][3];
        $wira_swasta = (int)round($wira * 0.4);
        $wira_pedagang = (int)round($wira * 0.3);
        $wira_wira = $wira - $wira_swasta - $wira_pedagang;
        $lain = $kd['emp'][10];
        $lain_buruh = (int)round($lain * 0.4);
        $lain_tidak = $lain - $lain_buruh;

        return [
            'jiwa_l' => $kd['pop_l'],
            'jiwa_p' => $kd['pop_p'],
            'kk_l' => $kd['kk_l'],
            'kk_p' => $kd['kk_p'],
            'u0_4_l' => $kd['age_l'][0], 'u0_4_p' => $kd['age_p'][0],
            'u5_9_l' => $kd['age_l'][1], 'u5_9_p' => $kd['age_p'][1],
            'u10_14_l' => $kd['age_l'][2], 'u10_14_p' => $kd['age_p'][2],
            'u15_19_l' => $kd['age_l'][3], 'u15_19_p' => $kd['age_p'][3],
            'u20_24_l' => $kd['age_l'][4], 'u20_24_p' => $kd['age_p'][4],
            'u25_29_l' => $kd['age_l'][5], 'u25_29_p' => $kd['age_p'][5],
            'u30_34_l' => $kd['age_l'][6], 'u30_34_p' => $kd['age_p'][6],
            'u35_39_l' => $kd['age_l'][7], 'u35_39_p' => $kd['age_p'][7],
            'u40_44_l' => $kd['age_l'][8], 'u40_44_p' => $kd['age_p'][8],
            'u45_49_l' => $kd['age_l'][9], 'u45_49_p' => $kd['age_p'][9],
            'u50_54_l' => $kd['age_l'][10], 'u50_54_p' => $kd['age_p'][10],
            'u55_59_l' => $kd['age_l'][11], 'u55_59_p' => $kd['age_p'][11],
            'u60_64_l' => $kd['age_l'][12], 'u60_64_p' => $kd['age_p'][12],
            'u65_69_l' => $kd['age_l'][13], 'u65_69_p' => $kd['age_p'][13],
            'u70_74_l' => $kd['age_l'][14], 'u70_74_p' => $kd['age_p'][14],
            'u75_79_l' => $u75_79_l, 'u75_79_p' => $u75_79_p,
            'u80_84_l' => $u80_84_l, 'u80_84_p' => $u80_84_p,
            'u85_atas_l' => $u85_l, 'u85_atas_p' => $u85_p,
            'kk_pend_tidak_sekolah' => $kd['edu'][0],
            'kk_pend_sd' => $kd['edu'][1] + $kd['edu'][2],
            'kk_pend_smp' => $kd['edu'][3],
            'kk_pend_sma' => $kd['edu'][4],
            'kk_pend_diploma' => $kd['edu'][5] + $kd['edu'][6],
            'kk_pend_s1' => $kd['edu'][7],
            'kk_pend_s2_s3' => $kd['edu'][8] + $kd['edu'][9],
            'kk_ker_tani' => $kd['emp'][4],
            'kk_ker_nelayan' => $kd['emp'][5],
            'kk_ker_pns' => $kd['emp'][1] + $kd['emp'][2] + $kd['emp'][8],
            'kk_ker_swasta' => $wira_swasta,
            'kk_ker_pedagang' => $wira_pedagang,
            'kk_ker_wiraswasta' => $wira_wira,
            'kk_ker_buruh' => $lain_buruh,
            'kk_ker_tidak_kerja' => $kd['emp'][0] + $kd['emp'][6] + $kd['emp'][7] + $kd['emp'][9] + $lain_tidak,
            'kk_kawin' => $kd['mar'][0],
            'kk_belum_kawin' => $kd['mar'][1],
            'kk_cerai_hidup' => $kd['mar'][2],
            'kk_cerai_mati' => $kd['mar'][3],
        ];
    }

    public function run()
    {
        $db = \Config\Database::connect();
        $adminDesa = $db->table('users')->where('role', 'admin_desa')->get()->getResultArray();

        if (empty($adminDesa)) {
            echo "Error: Pastikan tabel users sudah terisi sebelum menjalankan seeder ini.\n";
            return;
        }

        // Ambil semua desa beserta nama kecamatannya sekaligus — 1 query
        $allDesa = $db->table('m_desa')
            ->select('m_desa.id_desa, m_desa.nama_desa, kecamatan.nama_kecamatan')
            ->join('kecamatan', 'kecamatan.id_kecamatan = m_desa.id_kecamatan')
            ->get()->getResultArray();

        if (empty($allDesa)) {
            echo "Error: Pastikan tabel m_desa sudah terisi sebelum menjalankan seeder ini.\n";
            return;
        }

        $kecData = $this->getKecamatanData();
        $bulan   = 12;  // Semester II = Desember
        $tahun   = 2025;

        // Kelompokkan desa per kecamatan
        $desaByKec = [];
        foreach ($allDesa as $desa) {
            $desaByKec[$desa['nama_kecamatan']][] = $desa;
        }

        // Cache user per desa
        $userCache = [];
        foreach ($adminDesa as $u) {
            if ($u['id_desa']) $userCache[$u['id_desa']] = $u['id_user'];
        }
        $fallbackUser = $adminDesa[0]['id_user'];

        $data = [];

        foreach ($desaByKec as $kec => $desas) {
            if (!isset($kecData[$kec])) {
                echo "Warning: No data for kecamatan '$kec', skipping.\n";
                continue;
            }

            $kd      = $kecData[$kec];
            $numDesa = count($desas);
            $mapped  = $this->mapKecamatanData($kd);

            // Distribusi data kecamatan ke tiap desa secara proporsional
            $distributed = [];
            foreach ($mapped as $col => $total) {
                $distributed[$col] = $this->distribute($total, $numDesa);
            }

            foreach ($desas as $i => $desa) {
                $id_desa  = $desa['id_desa'];
                $id_user  = $userCache[$id_desa] ?? $fallbackUser;

                $vals = [];
                foreach ($distributed as $col => $arr) {
                    $vals[$col] = $arr[$i];
                }

                $totalJiwa = $vals['jiwa_l'] + $vals['jiwa_p'];
                $kkTotal   = $vals['kk_l'] + $vals['kk_p'];

                $vals['jml_balita'] = $vals['u0_4_l'] + $vals['u0_4_p'];
                $vals['jml_remaja'] = $vals['u10_14_l'] + $vals['u10_14_p']
                    + $vals['u15_19_l'] + $vals['u15_19_p'];
                $vals['jml_lansia'] = $vals['u60_64_l'] + $vals['u60_64_p']
                    + $vals['u65_69_l'] + $vals['u65_69_p']
                    + $vals['u70_74_l'] + $vals['u70_74_p']
                    + $vals['u75_79_l'] + $vals['u75_79_p']
                    + $vals['u80_84_l'] + $vals['u80_84_p']
                    + $vals['u85_atas_l'] + $vals['u85_atas_p'];

                $vals['jkn']          = (int)round($totalJiwa * 0.80);
                $vals['non_jkn']      = $totalJiwa - $vals['jkn'];
                $vals['pend_bpjs']    = $vals['jkn'];
                $vals['pend_non_bpjs'] = $vals['non_jkn'];
                $vals['pus_pbi']      = (int)round($totalJiwa * 0.25);
                $vals['pus_non_pbi']  = $vals['jkn'] - $vals['pus_pbi'];
                $vals['pus_jkn']      = $vals['pus_pbi'] + $vals['pus_non_pbi'];

                $vals['jml_pus']                     = (int)round($vals['kk_kawin'] * 0.85);
                $vals['kb_aktif']                    = (int)round($vals['jml_pus'] * 0.60);
                $vals['jml_penggunaan_alat_kontrasepsi'] = $vals['kb_aktif'];

                $vals['pend_wajib_ktp']          = (int)round($totalJiwa * 0.70);
                $vals['pend_punya_akta_lahir']   = (int)round($totalJiwa * 0.90);
                $vals['kk_punya_akta_nikah']     = (int)round($vals['kk_kawin'] * 0.70);
                $vals['kk_punya_kartu_fisik']    = (int)round($kkTotal * 0.95);
                $vals['kk_belum_punya_kartu_fisik'] = $kkTotal - $vals['kk_punya_kartu_fisik'];

                $vals['penduduk_hub_kepala']  = $kkTotal;
                $vals['penduduk_hub_istri']   = (int)round($kkTotal * 0.85);
                $vals['penduduk_hub_anak']    = (int)round($totalJiwa * 0.30);
                $vals['penduduk_hub_lainnya'] = max(0, $totalJiwa
                    - $vals['penduduk_hub_kepala']
                    - $vals['penduduk_hub_istri']
                    - $vals['penduduk_hub_anak']);

                $data[] = array_merge([
                    'id_desa' => $id_desa,
                    'id_user' => $id_user,
                    'bulan'   => $bulan,
                    'tahun'   => $tahun,
                ], $vals);
            }
        }

        $db->table('laporan_agregat')->truncate();
        $db->table('laporan_agregat')->insertBatch($data);
        echo "LaporanAgregatSeeder: " . count($data) . " records inserted.\n";
    }
}
