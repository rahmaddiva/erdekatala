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
        $id_kecamatan_filter = $this->request->getGet('id_kecamatan');
        $id_desa_filter = $this->request->getGet('id_desa');

        $list_kecamatan = [];
        $list_desa = [];
        $data_summary = [];

        $kecamatanModel = new \App\Models\KecamatanModel();
        $desaModel = new \App\Models\DesaModel();

        if ($user['role'] == 'admin_dinas') {
            $list_kecamatan = $kecamatanModel->findAll();

            if ($id_kecamatan_filter) {
                $list_desa = $desaModel->where('id_kecamatan', $id_kecamatan_filter)->findAll();
            }

            if ($id_desa_filter) {
                $allLaporan = $this->laporanModel->getRekapByDesa($id_desa_filter);
                $data_summary = $this->laporanModel->getSummaryPerDesa(
                    $desaModel->find($id_desa_filter)['id_kecamatan'] ?? 0
                );
            } elseif ($id_kecamatan_filter) {
                $allLaporan = $this->laporanModel->getRekapByKecamatan($id_kecamatan_filter);
                $data_summary = $this->laporanModel->getSummaryPerDesa($id_kecamatan_filter);
            } else {
                $allLaporan = $this->laporanModel->findAll();
                $data_summary = $this->laporanModel->getSummaryPerKecamatan();
            }

        } elseif ($user['role'] == 'admin_kecamatan') {
            $list_desa = $desaModel->where('id_kecamatan', $user['id_kecamatan'])->findAll();

            if ($id_desa_filter) {
                $allLaporan = $this->laporanModel->getRekapByDesa($id_desa_filter);
            } else {
                $allLaporan = $this->laporanModel->getRekapByKecamatan($user['id_kecamatan']);
                $data_summary = $this->laporanModel->getSummaryPerDesa($user['id_kecamatan']);
            }
        } else {
            // Admin Desa
            $allLaporan = $this->laporanModel->getRekapByDesa($user['id_desa']);
        }

        // Inisialisasi Group Data
        $data_grafik = [
            'gender' => ['jiwa_l' => 0, 'jiwa_p' => 0, 'kk_l' => 0, 'kk_p' => 0],
            'dokumen' => [
                'ktp_elektronik' => 0,
                'akta_lahir' => 0,
                'akta_nikah' => 0,
                'kk_fisik' => 0,
                'kk_non_fisik' => 0
            ],
            'bpjs' => ['pbi' => 0, 'non_pbi' => 0, 'non_jkn' => 0],
            'pus_jkn' => ['jkn' => 0, 'non_jkn' => 0]
        ];

        foreach ($allLaporan as $l) {
            $data_grafik['gender']['jiwa_l'] += $l['jiwa_l'];
            $data_grafik['gender']['jiwa_p'] += $l['jiwa_p'];
            $data_grafik['gender']['kk_l'] += $l['kk_l'];
            $data_grafik['gender']['kk_p'] += $l['kk_p'];

            $data_grafik['dokumen']['ktp_elektronik'] += $l['pend_wajib_ktp'];
            $data_grafik['dokumen']['akta_lahir'] += $l['pend_punya_akta_lahir'];
            $data_grafik['dokumen']['akta_nikah'] += $l['kk_punya_akta_nikah'];
            $data_grafik['dokumen']['kk_fisik'] += $l['kk_punya_kartu_fisik'];
            $data_grafik['dokumen']['kk_non_fisik'] += $l['kk_belum_punya_kartu_fisik'];

            $data_grafik['bpjs']['pbi'] += $l['pus_pbi'];
            $data_grafik['bpjs']['non_pbi'] += $l['pus_non_pbi'];
            $data_grafik['bpjs']['non_jkn'] += $l['non_jkn'];
        }

        $totals = [
            'jiwa_l' => 0, 'jiwa_p' => 0,
            'kk_l' => 0, 'kk_p' => 0,
            'balita' => 0, 'pus' => 0, 'bpjs' => 0
        ];

        $pendidikan = [
            'Tidak Sekolah' => 0, 'SD' => 0, 'SMP' => 0, 'SMA' => 0,
            'Diploma' => 0, 'S1' => 0, 'S2/S3' => 0
        ];

        $pekerjaan = [
            'Petani' => 0, 'Nelayan' => 0, 'PNS' => 0, 'Swasta' => 0,
            'Pedagang' => 0, 'Wiraswasta' => 0, 'Buruh' => 0, 'Tidak Bekerja' => 0
        ];

        $status_kawin = [
            'Kawin' => 0, 'Belum Kawin' => 0, 'Cerai Hidup' => 0, 'Cerai Mati' => 0
        ];

        $ageLabels = ['0-4','5-9','10-14','15-19','20-24','25-29','30-34','35-39',
                      '40-44','45-49','50-54','55-59','60-64','65-69','70-74','75-79','80-84','85+'];
        $piramidaL = array_fill(0, 18, 0);
        $piramidaP = array_fill(0, 18, 0);

        $piramidaFields = [
            ['u0_4_l','u0_4_p'],['u5_9_l','u5_9_p'],['u10_14_l','u10_14_p'],
            ['u15_19_l','u15_19_p'],['u20_24_l','u20_24_p'],['u25_29_l','u25_29_p'],
            ['u30_34_l','u30_34_p'],['u35_39_l','u35_39_p'],['u40_44_l','u40_44_p'],
            ['u45_49_l','u45_49_p'],['u50_54_l','u50_54_p'],['u55_59_l','u55_59_p'],
            ['u60_64_l','u60_64_p'],['u65_69_l','u65_69_p'],['u70_74_l','u70_74_p'],
            ['u75_79_l','u75_79_p'],['u80_84_l','u80_84_p'],['u85_atas_l','u85_atas_p']
        ];

        foreach ($allLaporan as $l) {
            $totals['jiwa_l'] += $l['jiwa_l'] ?? 0;
            $totals['jiwa_p'] += $l['jiwa_p'] ?? 0;
            $totals['kk_l'] += $l['kk_l'] ?? 0;
            $totals['kk_p'] += $l['kk_p'] ?? 0;
            $totals['balita'] += $l['jml_balita'] ?? 0;
            $totals['pus'] += $l['jml_pus'] ?? 0;
            $totals['bpjs'] += $l['pend_bpjs'] ?? 0;

            $pendidikan['Tidak Sekolah'] += $l['kk_pend_tidak_sekolah'] ?? 0;
            $pendidikan['SD'] += $l['kk_pend_sd'] ?? 0;
            $pendidikan['SMP'] += $l['kk_pend_smp'] ?? 0;
            $pendidikan['SMA'] += $l['kk_pend_sma'] ?? 0;
            $pendidikan['Diploma'] += $l['kk_pend_diploma'] ?? 0;
            $pendidikan['S1'] += $l['kk_pend_s1'] ?? 0;
            $pendidikan['S2/S3'] += $l['kk_pend_s2_s3'] ?? 0;

            $pekerjaan['Petani'] += $l['kk_ker_tani'] ?? 0;
            $pekerjaan['Nelayan'] += $l['kk_ker_nelayan'] ?? 0;
            $pekerjaan['PNS'] += $l['kk_ker_pns'] ?? 0;
            $pekerjaan['Swasta'] += $l['kk_ker_swasta'] ?? 0;
            $pekerjaan['Pedagang'] += $l['kk_ker_pedagang'] ?? 0;
            $pekerjaan['Wiraswasta'] += $l['kk_ker_wiraswasta'] ?? 0;
            $pekerjaan['Buruh'] += $l['kk_ker_buruh'] ?? 0;
            $pekerjaan['Tidak Bekerja'] += $l['kk_ker_tidak_kerja'] ?? 0;

            $status_kawin['Kawin'] += $l['kk_kawin'] ?? 0;
            $status_kawin['Belum Kawin'] += $l['kk_belum_kawin'] ?? 0;
            $status_kawin['Cerai Hidup'] += $l['kk_cerai_hidup'] ?? 0;
            $status_kawin['Cerai Mati'] += $l['kk_cerai_mati'] ?? 0;

            foreach ($piramidaFields as $idx => $fields) {
                $piramidaL[$idx] += $l[$fields[0]] ?? 0;
                $piramidaP[$idx] += $l[$fields[1]] ?? 0;
            }
        }

        $data = [
            'title'          => 'Statistik Agregat Kependudukan',
            'grafik'         => $data_grafik,
            'list_kecamatan' => $list_kecamatan,
            'list_desa'      => $list_desa,
            'list_dusun'     => [], // ponytail: removed dusun/rt layers
            'list_rt'        => [],
            'filter_kec'     => $id_kecamatan_filter,
            'filter_desa'    => $id_desa_filter,
            'filter_dusun'   => null,
            'filter_rt'      => null,
            'data_summary'   => $data_summary,
            'pendidikan'     => $pendidikan,
            'pekerjaan'      => $pekerjaan,
            'status_kawin'   => $status_kawin,
            'ageLabels'      => $ageLabels,
            'piramidaL'      => $piramidaL,
            'piramidaP'      => $piramidaP,
            'totals'         => $totals
        ];

        return view('dashboard/index', $data);
    }

    // AJAX endpoint untuk chained dropdown
    public function getDesaByKecamatan($id_kecamatan)
    {
        $desaModel = new \App\Models\DesaModel();
        $desa = $desaModel->where('id_kecamatan', $id_kecamatan)->findAll();
        return $this->response->setJSON($desa);
    }

    // ponytail: getDusunByDesa & getRtByDusun kept for backward compat with other views
    public function getDusunByDesa($id_desa)
    {
        $dusunModel = new \App\Models\DusunModel();
        return $this->response->setJSON($dusunModel->where('id_desa', $id_desa)->findAll());
    }

    public function getRtByDusun($id_dusun)
    {
        $rtModel = new \App\Models\RtModel();
        return $this->response->setJSON($rtModel->where('id_dusun', $id_dusun)->findAll());
    }
}
