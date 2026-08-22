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

    /**
     * Keluarkan payload GeoJSON murni dengan Content-Type application/geo+json.
     */
    private function geojson(array $payload, int $status = 200)
    {
        return $this->response
            ->setStatusCode($status)
            ->setBody(json_encode($payload, JSON_UNESCAPED_UNICODE))
            ->setContentType('application/geo+json');
    }

    // GET /api/v1/info
    public function info()
    {
        return $this->json([
            'status'      => 'ok',
            'api'         => 'Erdekatala Public API',
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
                'GET /api/v1/map/kecamatan'           => 'GeoJSON FeatureCollection per kecamatan + indikator statistik gabungan (PUBLIK, tanpa API key)',
                'GET /api/v1/map/desa'                => 'GeoJSON FeatureCollection per desa + indikator statistik gabungan (filter: ?id_kecamatan=; PUBLIK, tanpa API key)',
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

    // GET /api/v1/map/kecamatan — FeatureCollection GeoJSON + statistik (publik tanpa key)
    public function mapKecamatan()
    {
        $geojson = $this->readGeojson(FCPATH . 'geojson/kecamatan.geojson');
        if ($geojson === null) {
            return $this->json(['status' => 'error', 'message' => 'File peta kecamatan tidak dapat dibaca.'], 500);
        }

        $aggr = [];
        foreach ($this->laporanModel->getMapKecamatan() as $row) {
            $aggr[$row['kd_kecamatan']] = $row;
        }

        foreach ($geojson['features'] as &$feature) {
            $kd = (string) ($feature['properties']['kd_kecamatan'] ?? '');
            $feature['properties'] = array_merge(
                $feature['properties'],
                $this->indicatorDefaults(isset($aggr[$kd]) ? $aggr[$kd] : [])
            );
        }
        unset($feature);

        return $this->geojson($geojson);
    }

    // GET /api/v1/map/desa?id_kecamatan= — FeatureCollection GeoJSON + statistik (publik tanpa key)
    public function mapDesa()
    {
        $id_kecamatan = null;
        $rawId = $this->request->getGet('id_kecamatan');
        if ($rawId !== null) {
            $id_kecamatan = filter_var($rawId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            if ($id_kecamatan === false) {
                return $this->json(['status' => 'error', 'message' => 'Parameter id_kecamatan harus berupa angka positif.'], 422);
            }
        }

        $features = $this->loadDesaFeatures($id_kecamatan);
        if ($features === null) {
            return $this->json(['status' => 'error', 'message' => 'File peta desa tidak dapat dibaca.'], 500);
        }

        $aggr = [];
        foreach ($this->laporanModel->getMapDesa($id_kecamatan) as $row) {
            $aggr[$row['kd_kecamatan'] . '.' . $row['kd_kelurahan']] = $row;
        }

        foreach ($features as &$feature) {
            $kdKec = (string) ($feature['properties']['kd_kecamatan'] ?? '');
            $kdKel = (string) ($feature['properties']['kd_kelurahan'] ?? '');
            $key = $kdKec . '.' . $kdKel;
            $feature['properties'] = array_merge(
                $feature['properties'],
                $this->indicatorDefaults(isset($aggr[$key]) ? $aggr[$key] : [])
            );
        }
        unset($feature);

        $payload = ['type' => 'FeatureCollection', 'features' => $features];
        return $this->geojson($payload);
    }

    /**
     * Baca file geojson -> array (null bila gagal/JSON invalid).
     */
    private function readGeojson(string $path): ?array
    {
        if (!is_file($path) || !is_readable($path)) {
            return null;
        }
        $data = json_decode((string) file_get_contents($path), true);
        if (!is_array($data) || ($data['type'] ?? null) !== 'FeatureCollection' || !isset($data['features'])) {
            return null;
        }
        return $data;
    }

    /**
     * Muat fitur desa: 1 file per kecamatan (001..011), de-dup kd_kecamatan+kd_kelurahan.
     * Return null bila file rusak; [] bila file tidak ada (fitur kosong).
     */
    private function loadDesaFeatures(?int $idKecamatan): ?array
    {
        $files = [];
        if ($idKecamatan !== null) {
            $files[] = str_pad((string) $idKecamatan, 3, '0', STR_PAD_LEFT) . '.geojson';
        } else {
            $files = glob(FCPATH . 'geojson/desa/*.geojson') ?: [];
        }

        $features = [];
        $seen = [];
        foreach ($files as $file) {
            $fullPath = $idKecamatan !== null
                ? FCPATH . 'geojson/desa/' . $file
                : $file;
            $geojson = $this->readGeojson($fullPath);
            if ($geojson === null) {
                // File ada namun rusak -> error; file tidak ada -> skip (bisa fitur kosong).
                if (file_exists($fullPath)) {
                    return null;
                }
                continue;
            }
            foreach ($geojson['features'] as $feature) {
                $kdKec = (string) ($feature['properties']['kd_kecamatan'] ?? '');
                $kdKel = (string) ($feature['properties']['kd_kelurahan'] ?? '');
                $key = $kdKec . '.' . $kdKel;
                if ($key === '.' || isset($seen[$key])) {
                    continue;
                }
                $seen[$key] = true;
                $features[] = $feature;
            }
        }

        return $features;
    }

    /**
     * Beri nilai default 0 (int) untuk semua field indikator yang dipakai peta,
     * agar setiap feature punya kunci yang seragam walau tanpa laporan.
     */
    private function indicatorDefaults(array $row): array
    {
        $defaults = array_fill_keys(\App\Models\LaporanAgregatModel::mapIndicatorFields(), 0);
        $defaults['total_jiwa'] = 0;
        $defaults['total_kk']   = 0;
        return array_merge($defaults, $row);
    }
}
