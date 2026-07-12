<?php

namespace App\Controllers;

use App\Models\ApiKeyModel;
use App\Models\KecamatanModel;
use App\Models\DesaModel;
use App\Models\LaporanAgregatModel;

class ApiController extends BaseController
{
    private ApiKeyModel $apiKeyModel;
    private LaporanAgregatModel $laporanModel;
    private KecamatanModel $kecamatanModel;
    private DesaModel $desaModel;

    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
        $this->apiKeyModel    = new ApiKeyModel();
        $this->laporanModel   = new LaporanAgregatModel();
        $this->kecamatanModel = new KecamatanModel();
        $this->desaModel      = new DesaModel();
    }

    private function json(array $data, int $status = 200)
    {
        return $this->response->setStatusCode($status)->setJSON($data);
    }

    // GET /api/v1/info
    public function info()
    {
        return $this->json([
            'status'      => 'ok',
            'api'         => 'Sikada Tala Public API',
            'version'     => 'v1',
            'description' => 'Data agregat kependudukan Kabupaten Tanah Laut, Kalimantan Selatan',
            'docs'        => base_url('api/docs'),
            'endpoints'   => [
                'GET /api/v1/info'                    => 'Informasi API',
                'GET /api/v1/kecamatan'               => 'Daftar semua kecamatan',
                'GET /api/v1/desa'                    => 'Daftar desa (filter: ?id_kecamatan=)',
                'GET /api/v1/laporan'                 => 'Data laporan agregat (filter: ?id_kecamatan=, ?id_desa=, ?bulan=, ?tahun=)',
                'GET /api/v1/laporan/rekap/kecamatan' => 'Rekapitulasi per kecamatan',
                'GET /api/v1/laporan/rekap/desa'      => 'Rekapitulasi per desa (wajib: ?id_kecamatan=)',
            ],
        ]);
    }

    // GET /api/v1/kecamatan
    public function kecamatan()
    {
        $data = $this->kecamatanModel->findAll();
        return $this->json([
            'status' => 'ok',
            'total'  => count($data),
            'data'   => $data,
        ]);
    }

    // GET /api/v1/desa?id_kecamatan=
    public function desa()
    {
        // Sanitasi: hanya integer positif
        $id_kecamatan = $this->request->getGet('id_kecamatan');
        if ($id_kecamatan !== null) {
            $id_kecamatan = filter_var($id_kecamatan, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            if ($id_kecamatan === false) {
                return $this->json(['status' => 'error', 'message' => 'Parameter id_kecamatan harus berupa angka positif.'], 422);
            }
        }

        $builder = $this->desaModel
            ->select('m_desa.*, kecamatan.nama_kecamatan')
            ->join('kecamatan', 'kecamatan.id_kecamatan = m_desa.id_kecamatan');

        if ($id_kecamatan) {
            $builder->where('m_desa.id_kecamatan', $id_kecamatan);
        }

        $data = $builder->findAll();
        return $this->json([
            'status' => 'ok',
            'total'  => count($data),
            'data'   => $data,
        ]);
    }

    // GET /api/v1/laporan?id_kecamatan=&id_desa=&bulan=&tahun=&page=&per_page=
    public function laporan()
    {
        // Sanitasi semua input parameter
        $id_kecamatan = filter_var($this->request->getGet('id_kecamatan'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: null;
        $id_desa      = filter_var($this->request->getGet('id_desa'),      FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: null;
        $bulan        = filter_var($this->request->getGet('bulan'),         FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 12]]) ?: null;
        $tahun        = filter_var($this->request->getGet('tahun'),         FILTER_VALIDATE_INT, ['options' => ['min_range' => 2000, 'max_range' => 2100]]) ?: null;
        $page         = max(1, (int) ($this->request->getGet('page') ?? 1));
        $perPage      = min(500, max(10, (int) ($this->request->getGet('per_page') ?? 100)));

        $builder = $this->laporanModel
            ->select('laporan_agregat.*, kecamatan.nama_kecamatan, m_desa.nama_desa')
            ->join('m_desa',    'm_desa.id_desa = laporan_agregat.id_desa')
            ->join('kecamatan', 'kecamatan.id_kecamatan = m_desa.id_kecamatan');

        if ($id_kecamatan) $builder->where('kecamatan.id_kecamatan', $id_kecamatan);
        if ($id_desa)      $builder->where('m_desa.id_desa', $id_desa);
        if ($bulan)        $builder->where('laporan_agregat.bulan', $bulan);
        if ($tahun)        $builder->where('laporan_agregat.tahun', $tahun);

        $total = $builder->countAllResults(false);
        $data  = $builder->orderBy('tahun', 'DESC')
                         ->orderBy('bulan', 'DESC')
                         ->paginate($perPage, 'default', $page);

        return $this->json([
            'status'   => 'ok',
            'total'    => $total,
            'page'     => $page,
            'per_page' => $perPage,
            'pages'    => (int) ceil($total / $perPage),
            'data'     => $data,
        ]);
    }

    // GET /api/v1/laporan/rekap/kecamatan
    public function rekapKecamatan()
    {
        $data = $this->laporanModel->getSummaryPerKecamatan();
        return $this->json([
            'status' => 'ok',
            'total'  => count($data),
            'data'   => $data,
        ]);
    }

    // GET /api/v1/laporan/rekap/desa?id_kecamatan=
    public function rekapDesa()
    {
        $id_kecamatan = filter_var($this->request->getGet('id_kecamatan'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if (!$id_kecamatan) {
            return $this->json(['status' => 'error', 'message' => 'Parameter id_kecamatan wajib diisi dan harus berupa angka positif.'], 422);
        }
        $data = $this->laporanModel->getSummaryPerDesa($id_kecamatan);
        return $this->json([
            'status' => 'ok',
            'total'  => count($data),
            'data'   => $data,
        ]);
    }
}
