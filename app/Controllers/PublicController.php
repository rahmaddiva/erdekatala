<?php

namespace App\Controllers;

use App\Models\LaporanAgregatModel;

class PublicController extends BaseController
{
    protected $laporanModel;

    public function __construct()
    {
        $this->laporanModel = new LaporanAgregatModel();
    }

    /**
     * Landing page publik - dapat diakses tanpa login
     */
    public function landingpage()
    {
        // Ambil data agregat per kecamatan
        $kecamatanModel = new \App\Models\KecamatanModel();

        $list_kecamatan = $kecamatanModel->findAll();
        $data_kecamatan = $this->laporanModel->getSummaryPerKecamatan();

        // Persiapan data untuk chart
        $chart_labels = [];
        $chart_total_jiwa = [];
        $chart_total_kk = [];
        $chart_total_balita = [];
        $chart_total_pus = [];

        foreach ($data_kecamatan as $item) {
            $chart_labels[] = $item['nama_kecamatan'];
            $chart_total_jiwa[] = $item['total_jiwa'] ?? 0;
            $chart_total_kk[] = $item['total_kk'] ?? 0;
            $chart_total_balita[] = $item['total_balita'] ?? 0;
            $chart_total_pus[] = $item['total_pus'] ?? 0;
        }

        // Hitung total keseluruhan
        $total_keseluruhan = [
            'total_jiwa' => 0,
            'total_kk' => 0,
            'total_balita' => 0,
            'total_pus' => 0,
            'total_jiwa_l' => 0,
            'total_jiwa_p' => 0
        ];

        foreach ($data_kecamatan as $item) {
            $total_keseluruhan['total_jiwa'] += $item['total_jiwa'] ?? 0;
            $total_keseluruhan['total_kk'] += $item['total_kk'] ?? 0;
            $total_keseluruhan['total_balita'] += $item['total_balita'] ?? 0;
            $total_keseluruhan['total_pus'] += $item['total_pus'] ?? 0;
            $total_keseluruhan['total_jiwa_l'] += $item['total_jiwa_l'] ?? 0;
            $total_keseluruhan['total_jiwa_p'] += $item['total_jiwa_p'] ?? 0;
        }

        $data = [
            'title' => 'Statistik Kependudukan Per Kecamatan',
            'list_kecamatan' => $list_kecamatan,
            'data_kecamatan' => $data_kecamatan,
            'chart_labels' => json_encode($chart_labels),
            'chart_total_jiwa' => json_encode($chart_total_jiwa),
            'chart_total_kk' => json_encode($chart_total_kk),
            'chart_total_balita' => json_encode($chart_total_balita),
            'chart_total_pus' => json_encode($chart_total_pus),
            'total_keseluruhan' => $total_keseluruhan
        ];

        return view('public/landingpage', $data);
    }
}
