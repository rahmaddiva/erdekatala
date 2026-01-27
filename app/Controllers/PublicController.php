<?php

namespace App\Controllers;

use App\Models\LaporanAgregatModel;
use App\Models\KecamatanModel;
use App\Models\DesaModel;
class PublicController extends BaseController
{
    protected $laporanModel;
    protected $kecamatanModel;
    protected $desaModel;


    public function __construct()
    {
        $this->laporanModel = new LaporanAgregatModel();
        $this->kecamatanModel = new KecamatanModel();
        $this->desaModel = new DesaModel();
    }

    /**
     * Landing page publik - dapat diakses tanpa login
     */
    public function landingpage()
    {
        // Ambil filter dari query string
        $id_kecamatan_filter = $this->request->getGet('id_kecamatan');
        $id_desa_filter = $this->request->getGet('id_desa');

        // Ambil semua kecamatan untuk dropdown
        $list_kecamatan = $this->kecamatanModel->findAll();

        // Ambil desa berdasarkan kecamatan yang dipilih
        $list_desa = [];
        if ($id_kecamatan_filter) {
            $list_desa = $this->desaModel->where('id_kecamatan', $id_kecamatan_filter)->findAll();
        }

        // Query data laporan berdasarkan filter
        $builder = $this->laporanModel->select('laporan_agregat.*, m_desa.nama_desa, kecamatan.nama_kecamatan')
            ->join('m_rt', 'm_rt.id_rt = laporan_agregat.id_rt')
            ->join('m_dusun', 'm_dusun.id_dusun = m_rt.id_dusun')
            ->join('m_desa', 'm_desa.id_desa = m_dusun.id_desa')
            ->join('kecamatan', 'kecamatan.id_kecamatan = m_desa.id_kecamatan');

        // Apply filter
        if ($id_desa_filter) {
            $builder->where('m_desa.id_desa', $id_desa_filter);
        } elseif ($id_kecamatan_filter) {
            $builder->where('kecamatan.id_kecamatan', $id_kecamatan_filter);
        }

        $allLaporan = $builder->findAll();

        // Inisialisasi data untuk chart
        $totals = [
            'jiwa_l' => 0,
            'jiwa_p' => 0,
            'kk_l' => 0,
            'kk_p' => 0,
            'balita' => 0,
            'pus' => 0,
        ];

        $pendidikan = [
            'Tidak Sekolah' => 0,
            'SD' => 0,
            'SMP' => 0,
            'SMA' => 0,
            'Diploma' => 0,
            'S1' => 0,
            'S2/S3' => 0
        ];

        $pekerjaan = [
            'Petani' => 0,
            'Nelayan' => 0,
            'PNS' => 0,
            'Swasta' => 0,
            'Pedagang' => 0,
            'Wiraswasta' => 0,
            'Buruh' => 0,
            'Tidak Bekerja' => 0
        ];

        $jkn_bpjs = [
            'PBI' => 0,
            'Non PBI' => 0,
            'Tidak Punya' => 0
        ];

        // Piramida Penduduk
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

        // Aggregate data
        foreach ($allLaporan as $l) {
            $totals['jiwa_l'] += $l['jiwa_l'] ?? 0;
            $totals['jiwa_p'] += $l['jiwa_p'] ?? 0;
            $totals['kk_l'] += $l['kk_l'] ?? 0;
            $totals['kk_p'] += $l['kk_p'] ?? 0;
            $totals['balita'] += $l['jml_balita'] ?? 0;
            $totals['pus'] += $l['jml_pus'] ?? 0;

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

            $jkn_bpjs['PBI'] += $l['pus_pbi'] ?? 0;
            $jkn_bpjs['Non PBI'] += $l['pus_non_pbi'] ?? 0;
            $jkn_bpjs['Tidak Punya'] += $l['non_jkn'] ?? 0;

            foreach ($piramidaFields as $idx => $fields) {
                $piramidaL[$idx] += $l[$fields[0]] ?? 0;
                $piramidaP[$idx] += $l[$fields[1]] ?? 0;
            }
        }

        $data = [
            'list_kecamatan' => $list_kecamatan,
            'list_desa' => $list_desa,
            'filter_kec' => $id_kecamatan_filter,
            'filter_desa' => $id_desa_filter,
            'totals' => $totals,
            'pendidikan' => $pendidikan,
            'pekerjaan' => $pekerjaan,
            'jkn_bpjs' => $jkn_bpjs,
            'ageLabels' => $ageLabels,
            'piramidaL' => $piramidaL,
            'piramidaP' => $piramidaP
        ];

        return view('public/landingpage', $data);
    }

    // AJAX endpoint untuk mendapatkan desa berdasarkan kecamatan
    public function getDesaByKecamatan($id_kecamatan)
    {
        $desa = $this->desaModel->where('id_kecamatan', $id_kecamatan)->findAll();
        return $this->response->setJSON($desa);
    }
}
