<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\LaporanAgregatModel;

class DashboardController extends BaseController
{
    protected $laporanModel;

    public function __construct()
    {
        $this->laporanModel = new LaporanAgregatModel();
    }

    public function index()
    {
        $user = session()->get();
        $id_desa_filter = $this->request->getGet('id_desa'); // Ambil filter dari URL
        $data_summary_desa = [];
        $list_desa = [];

        if ($user['role'] == 'admin_kecamatan') {
            // Ambil list desa untuk dropdown filter
            $desaModel = new \App\Models\DesaModel();
            $list_desa = $desaModel->where('id_kecamatan', $user['id_kecamatan'])->findAll();

            // Ambil data laporan (dengan filter jika ada)
            $allLaporan = $this->laporanModel->getRekapByKecamatan($user['id_kecamatan'], $id_desa_filter);

            // Ambil ringkasan per desa untuk tabel
            $data_summary_desa = $this->laporanModel->getSummaryPerDesa($user['id_kecamatan']);

        } elseif ($user['role'] == 'admin_desa') {
            $allLaporan = $this->laporanModel->getRekapByDesa($user['id_desa']);
        } else {
            $allLaporan = $this->laporanModel->findAll();
        }

        // 1. Inisialisasi Data Ringkasan (Info Boxes)
        $totals = [
            'jiwa_l' => 0,
            'jiwa_p' => 0,
            'kk_l' => 0,
            'kk_p' => 0,
            'balita' => 0,
            'pus' => 0,
            'bpjs' => 0
        ];

        // 2. Data Grouping untuk Charts
        $pendidikan = [
            'Tdk Sekolah' => 0,
            'SD' => 0,
            'SMP' => 0,
            'SMA' => 0,
            'Diploma' => 0,
            'S1' => 0,
            'S2/S3' => 0
        ];

        $pekerjaan = [
            'Tani' => 0,
            'Nelayan' => 0,
            'PNS' => 0,
            'Swasta' => 0,
            'Pedagang' => 0,
            'Wiraswasta' => 0,
            'Buruh' => 0,
            'Tidak Kerja' => 0
        ];

        $status_kawin = [
            'Kawin' => 0,
            'Belum Kawin' => 0,
            'Cerai Hidup' => 0,
            'Cerai Mati' => 0
        ];

        // Piramida Penduduk - lengkap dengan semua kelompok umur
        $ageLabels = [
            '0-4',
            '5-9',
            '10-14',
            '15-19',
            '20-24',
            '25-29',
            '30-34',
            '35-39',
            '40-44',
            '45-49',
            '50-54',
            '55-59',
            '60-64',
            '65-69',
            '70-74',
            '75-79',
            '80-84',
            '85+'
        ];
        $piramidaL = array_fill(0, 18, 0);
        $piramidaP = array_fill(0, 18, 0);

        // Mapping field piramida
        $piramidaFields = [
            ['u0_4_l', 'u0_4_p'],
            ['u5_9_l', 'u5_9_p'],
            ['u10_14_l', 'u10_14_p'],
            ['u15_19_l', 'u15_19_p'],
            ['u20_24_l', 'u20_24_p'],
            ['u25_29_l', 'u25_29_p'],
            ['u30_34_l', 'u30_34_p'],
            ['u35_39_l', 'u35_39_p'],
            ['u40_44_l', 'u40_44_p'],
            ['u45_49_l', 'u45_49_p'],
            ['u50_54_l', 'u50_54_p'],
            ['u55_59_l', 'u55_59_p'],
            ['u60_64_l', 'u60_64_p'],
            ['u65_69_l', 'u65_69_p'],
            ['u70_74_l', 'u70_74_p'],
            ['u75_79_l', 'u75_79_p'],
            ['u80_84_l', 'u80_84_p'],
            ['u85_atas_l', 'u85_atas_p']
        ];

        foreach ($allLaporan as $l) {
            // Totals - gunakan field yang benar
            $totals['jiwa_l'] += $l['jiwa_l'] ?? 0;
            $totals['jiwa_p'] += $l['jiwa_p'] ?? 0;
            $totals['kk_l'] += $l['kk_l'] ?? 0;
            $totals['kk_p'] += $l['kk_p'] ?? 0;
            $totals['balita'] += $l['jml_balita'] ?? 0;
            $totals['pus'] += $l['jml_pus'] ?? 0;
            $totals['bpjs'] += $l['pend_bpjs'] ?? 0;

            // Pendidikan
            $pendidikan['Tdk Sekolah'] += $l['kk_pend_tidak_sekolah'] ?? 0;
            $pendidikan['SD'] += $l['kk_pend_sd'] ?? 0;
            $pendidikan['SMP'] += $l['kk_pend_smp'] ?? 0;
            $pendidikan['SMA'] += $l['kk_pend_sma'] ?? 0;
            $pendidikan['Diploma'] += $l['kk_pend_diploma'] ?? 0;
            $pendidikan['S1'] += $l['kk_pend_s1'] ?? 0;
            $pendidikan['S2/S3'] += $l['kk_pend_s2_s3'] ?? 0;

            // Pekerjaan
            $pekerjaan['Tani'] += $l['kk_ker_tani'] ?? 0;
            $pekerjaan['Nelayan'] += $l['kk_ker_nelayan'] ?? 0;
            $pekerjaan['PNS'] += $l['kk_ker_pns'] ?? 0;
            $pekerjaan['Swasta'] += $l['kk_ker_swasta'] ?? 0;
            $pekerjaan['Pedagang'] += $l['kk_ker_pedagang'] ?? 0;
            $pekerjaan['Wiraswasta'] += $l['kk_ker_wiraswasta'] ?? 0;
            $pekerjaan['Buruh'] += $l['kk_ker_buruh'] ?? 0;
            $pekerjaan['Tidak Kerja'] += $l['kk_ker_tidak_kerja'] ?? 0;

            // Status Perkawinan
            $status_kawin['Kawin'] += $l['kk_kawin'] ?? 0;
            $status_kawin['Belum Kawin'] += $l['kk_belum_kawin'] ?? 0;
            $status_kawin['Cerai Hidup'] += $l['kk_cerai_hidup'] ?? 0;
            $status_kawin['Cerai Mati'] += $l['kk_cerai_mati'] ?? 0;

            // Piramida Penduduk
            foreach ($piramidaFields as $idx => $fields) {
                $piramidaL[$idx] += $l[$fields[0]] ?? 0;
                $piramidaP[$idx] += $l[$fields[1]] ?? 0;
            }
        }

        $data = [
            'title' => 'Dashboard Erdekatala',
            'totals' => $totals,
            'pendidikan' => $pendidikan,
            'pekerjaan' => $pekerjaan,
            'status_kawin' => $status_kawin,
            'ageLabels' => $ageLabels,
            'piramidaL' => $piramidaL,
            'piramidaP' => $piramidaP,
            'list_desa' => $list_desa,         // Untuk dropdown
            'selected_desa' => $id_desa_filter,    // Desa yang sedang dipilih
            'data_summary_desa' => $data_summary_desa  // Untuk tabel rekap
        ];

        return view('dashboard/index', $data);
    }
}