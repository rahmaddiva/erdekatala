<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\LaporanAgregatModel;
use App\Models\KecamatanModel;
use App\Models\DesaModel;

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

        $kecamatanModel = new KecamatanModel();
        $desaModel = new DesaModel();

        // Tentukan scope filter berdasarkan role
        $scope_kec = null;
        $scope_desa = null;

        if ($user['role'] == 'admin_dinas') {
            $list_kecamatan = $kecamatanModel->findAll();
            if ($id_kecamatan_filter) {
                $list_desa = $desaModel->where('id_kecamatan', $id_kecamatan_filter)->findAll();
            }
            if ($id_desa_filter) {
                $scope_desa = (int) $id_desa_filter;
                $data_summary = $this->laporanModel->getSummaryPerDesa(
                    $desaModel->find($id_desa_filter)['id_kecamatan'] ?? 0
                );
            } elseif ($id_kecamatan_filter) {
                $scope_kec = (int) $id_kecamatan_filter;
                $data_summary = $this->laporanModel->getSummaryPerDesa($id_kecamatan_filter);
            } else {
                $data_summary = $this->laporanModel->getSummaryPerKecamatan();
            }
        } elseif ($user['role'] == 'admin_kecamatan') {
            $list_desa = $desaModel->where('id_kecamatan', $user['id_kecamatan'])->findAll();
            if ($id_desa_filter) {
                $scope_desa = (int) $id_desa_filter;
            } else {
                $scope_kec = (int) $user['id_kecamatan'];
                $data_summary = $this->laporanModel->getSummaryPerDesa($user['id_kecamatan']);
            }
        } else {
            // Admin Desa
            $scope_desa = (int) $user['id_desa'];
        }

        // Single SQL SUM query — no findAll() + PHP loop
        $stats = $this->laporanModel->getAggregatedStats($scope_kec, $scope_desa);
        $chart = LaporanAgregatModel::statsToChartData($stats);

        $data = [
            'title'          => 'Statistik Agregat Kependudukan',
            'grafik'         => $chart['grafik'],
            'list_kecamatan' => $list_kecamatan,
            'list_desa'      => $list_desa,
            'list_dusun'     => [], // ponytail: removed dusun/rt layers
            'list_rt'        => [],
            'filter_kec'     => $id_kecamatan_filter,
            'filter_desa'    => $id_desa_filter,
            'filter_dusun'   => null,
            'filter_rt'      => null,
            'data_summary'   => $data_summary,
            'pendidikan'     => $chart['pendidikan'],
            'pekerjaan'      => $chart['pekerjaan'],
            'status_kawin'   => $chart['status_kawin'],
            'ageLabels'      => $chart['ageLabels'],
            'piramidaL'      => $chart['piramidaL'],
            'piramidaP'      => $chart['piramidaP'],
            'totals'         => $chart['totals'],
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
