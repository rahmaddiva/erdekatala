<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Snapshot profil Dukcapil semester II / 2025 per desa.
 * Sumber angka = getKecamatanData() LaporanAgregatSeeder (data Dukcapil
 * semester 2/2025 yang dipakai user sebagai seeder awal). Di-distribusikan
 * proporsional ke tiap desa dengan cara yang SAMA dengan LaporanAgregatSeeder
 * sehingga total per kecamatan konsisten.
 */
class ProfilDukcapilSeeder extends Seeder
{
    public function run()
    {
        $db = \Config\Database::connect();

        $allDesa = $db->table('m_desa')
            ->select('m_desa.id_desa, m_desa.nama_desa, kecamatan.nama_kecamatan')
            ->join('kecamatan', 'kecamatan.id_kecamatan = m_desa.id_kecamatan')
            ->get()->getResultArray();

        if (empty($allDesa)) {
            echo "ProfilDukcapilSeeder: tabel m_desa kosong — jalankan WilayahSeeder dulu.\n";
            return;
        }

        // Reuse sumber angka dari LaporanAgregatSeeder (data Dukcapil sem 2/2025)
        // tanpa eksekusi run() — method sudah public.
        $lapSeed = new \App\Database\Seeds\LaporanAgregatSeeder(new \Config\Database());
        $kecData = $lapSeed->getKecamatanData();
        $dist    = null;

        $semester = 2;
        $tahun    = 2025;
        $now      = date('Y-m-d H:i:s');

        $desaByKec = [];
        foreach ($allDesa as $d) {
            $desaByKec[$d['nama_kecamatan']][] = $d;
        }

        $rows = [];
        foreach ($desaByKec as $kec => $desas) {
            if (!isset($kecData[$kec])) {
                echo "ProfilDukcapilSeeder: tidak ada data kecamatan '$kec', skip.\n";
                continue;
            }
            $numDesa = count($desas);
            $mapped  = $lapSeed->mapKecamatanData($kecData[$kec]);
            $dist    = [];
            foreach ($mapped as $col => $total) {
                $dist[$col] = $lapSeed->distribute($total, $numDesa);
            }
            foreach ($desas as $i => $desa) {
                $vals = [];
                foreach ($dist as $col => $arr) {
                    $vals[$col] = $arr[$i];
                }
                // Turunan yang sama dengan LaporanAgregatSeeder::run()
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
                $vals['jkn']           = (int) round($totalJiwa * 0.80);
                $vals['non_jkn']       = $totalJiwa - $vals['jkn'];
                $vals['pend_bpjs']     = $vals['jkn'];
                $vals['pend_non_bpjs'] = $vals['non_jkn'];
                $vals['pus_pbi']       = (int) round($totalJiwa * 0.25);
                $vals['pus_non_pbi']   = $vals['jkn'] - $vals['pus_pbi'];
                $vals['pus_jkn']       = $vals['pus_pbi'] + $vals['pus_non_pbi'];
                $vals['jml_pus']       = (int) round($vals['kk_kawin'] * 0.85);
                $vals['kb_aktif']      = (int) round($vals['jml_pus'] * 0.60);
                $vals['jml_penggunaan_alat_kontrasepsi'] = $vals['kb_aktif'];
                $vals['pend_wajib_ktp']       = (int) round($totalJiwa * 0.70);
                $vals['pend_punya_akta_lahir'] = (int) round($totalJiwa * 0.90);
                $vals['kk_punya_akta_nikah']   = (int) round($vals['kk_kawin'] * 0.70);
                $vals['kk_punya_kartu_fisik']  = (int) round($kkTotal * 0.95);
                $vals['kk_belum_punya_kartu_fisik'] = $kkTotal - $vals['kk_punya_kartu_fisik'];
                $vals['penduduk_hub_kepala']  = $kkTotal;
                $vals['penduduk_hub_istri']   = (int) round($kkTotal * 0.85);
                $vals['penduduk_hub_anak']    = (int) round($totalJiwa * 0.30);
                $vals['penduduk_hub_lainnya'] = max(0, $totalJiwa
                    - $vals['penduduk_hub_kepala']
                    - $vals['penduduk_hub_istri']
                    - $vals['penduduk_hub_anak']);

                $rows[] = array_merge([
                    'id_desa'     => $desa['id_desa'],
                    'semester'    => $semester,
                    'tahun'       => $tahun,
                    'imported_at' => $now,
                    'imported_by' => null,
                ], $vals);
            }
        }

        $db->table('profil_dukcapil')->truncate();
        if (!empty($rows)) {
            $db->table('profil_dukcapil')->insertBatch($rows);
        }
        echo "ProfilDukcapilSeeder: " . count($rows) . " snapshot Dukcapil (semester $semester/$tahun) inserted.\n";
    }
}
