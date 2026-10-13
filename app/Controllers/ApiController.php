<?php

namespace App\Controllers;

use App\Models\ApiKeyModel;
use App\Models\KecamatanModel;
use App\Models\DesaModel;
use App\Models\LaporanAgregatModel;
use App\Models\ProfilDukcapilModel;

class ApiController extends BaseController
{
    use BkkbnMirror;
    private ApiKeyModel $apiKeyModel;
    private LaporanAgregatModel $laporanModel;
    private ProfilDukcapilModel $profilDukcapilModel;
    private KecamatanModel $kecamatanModel;
    private DesaModel $desaModel;

    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
        $this->apiKeyModel         = new ApiKeyModel();
        $this->laporanModel        = new LaporanAgregatModel();
        $this->profilDukcapilModel = new ProfilDukcapilModel();
        $this->kecamatanModel      = new KecamatanModel();
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
                'GET /api/v1/map/kecamatan'           => 'GeoJSON FeatureCollection per kecamatan + indikator statistik gabungan (filter: ?sumber=desa|dukcapil|campuran, default dukcapil; PUBLIK, tanpa API key)',
                'GET /api/v1/map/desa'                => 'GeoJSON FeatureCollection per desa + indikator statistik gabungan (filter: ?id_kecamatan=, ?sumber=desa|dukcapil|campuran, default dukcapil; PUBLIK, tanpa API key)',
                'GET /api/v1/bkkbn/krs'               => 'Cermin Monitoring KRS BKKBN: rows per kecamatan + baris kab (filter: ?tahun=; sumber: BKKBN, cache 30 menit)',
                'GET /api/v1/bkkbn/dallap'            => 'Cermin Rekap Bangga Kencana BKKBN: matriks per kecamatan (filter: ?tahun=, ?bulan=; sumber: BKKBN, cache 30 menit)',
                'GET /api/v1/bkkbn/yankb'             => 'Cermin YanKB BKKBN Tabel1/Tabel21: tabel terstruktur per kecamatan (wajib: ?report=; filter: ?tahun=, ?bulan=; sumber: BKKBN, cache 30 menit)',
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

    // GET /api/v1/map/kecamatan?sumber= — FeatureCollection GeoJSON + statistik (publik tanpa key)
    public function mapKecamatan()
    {
        $sumber = $this->resolveSumber();

        $geojson = $this->readGeojson(FCPATH . 'geojson/kecamatan.geojson');
        if ($geojson === null) {
            return $this->json(['status' => 'error', 'message' => 'File peta kecamatan tidak dapat dibaca.'], 500);
        }

        // Baris per-desa sesuai sumber aktif, lalu agregasikan ke kecamatan.
        // Untuk 'desa' & 'dukcapil' ini setara dengan getMapKecamatan() model
        // masing-masing; untuk 'campuran' penggabungan per-desa WAJIB agar
        // semantik "laporan menimpa Dukcapil" tetap benar tanpa double-count.
        $aggr = $this->aggregateMapToKecamatan(
            $this->mapRowsPerDesa(null, $sumber)
        );

        foreach ($geojson['features'] as &$feature) {
            $kd = (string) ($feature['properties']['kd_kecamatan'] ?? '');
            $feature['properties'] = array_merge(
                $feature['properties'],
                $this->indicatorDefaults(isset($aggr[$kd]) ? $aggr[$kd] : [])
            );
        }
        unset($feature);

        // Foreign member di level FeatureCollection: konsumen tahu asal angka.
        $geojson['sumber']       = $sumber;
        $geojson['sumber_label'] = $this->sumberLabel($sumber);

        return $this->geojson($geojson);
    }

    // GET /api/v1/map/desa?id_kecamatan=&sumber= — FeatureCollection GeoJSON + statistik (publik tanpa key)
    public function mapDesa()
    {
        $sumber = $this->resolveSumber();

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

        // Baris per-desa sesuai sumber aktif (kunci "kec.desa").
        $aggr = $this->mapRowsPerDesa($id_kecamatan, $sumber);

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

        $payload = [
            'type'         => 'FeatureCollection',
            'features'     => $features,
            'sumber'       => $sumber,
            'sumber_label' => $this->sumberLabel($sumber),
        ];
        return $this->geojson($payload);
    }

    /**
     * Resolusi parameter ?sumber= untuk endpoint peta publik.
     *
     * Nilai dikenal: 'desa' | 'dukcapil' | 'campuran'. Tanpa param ATAU nilai
     * tak dikenal → 'dukcapil' (DEFAULT). Ini SENGAJA menyamai perilaku landing
     * page (PublicController): nilai tak dikenal diperlakukan sebagai default
     * Dukcapil, bukan 422 — supaya API publik dan halaman publik konsisten dan
     * tidak ada klien lama yang tiba-tiba gagal hanya karena typo query param.
     */
    private function resolveSumber(): string
    {
        $raw = strtolower(trim((string) ($this->request->getGet('sumber') ?? '')));
        return in_array($raw, ['desa', 'dukcapil', 'campuran'], true) ? $raw : 'dukcapil';
    }

    /**
     * Label sumber untuk foreign member `sumber_label` di payload GeoJSON.
     * Untuk dukcapil memakai satu sumber kebenaran ProfilDukcapilModel::sumberLabelDkb().
     * Untuk desa/campuran memakai label yang sesuai (sejajar landing page).
     */
    private function sumberLabel(string $sumber): string
    {
        switch ($sumber) {
            case 'desa':
                return 'Laporan desa (laporan_agregat, 1 baris terbaru per desa)';
            case 'campuran':
                return 'Gabungan: laporan desa bila ada, Dukcapil bila desa belum lapor';
            case 'dukcapil':
            default:
                return ProfilDukcapilModel::sumberLabelDkb();
        }
    }

    /**
     * Baris indikator peta per DESA sesuai SUMBER aktif (kunci "kec.desa").
     * Duplikasi kecil yang DISENGAJA dari PublicController::mapRowsPerDesa()
     * (tidak refactor lintas controller — lihat catatan tugas) agar semantik
     * sumber identik dengan landing page:
     *  - desa     : laporan desa (LaporanAgregatModel::getMapDesa)
     *  - dukcapil : snapshot Dukcapil (ProfilDukcapilModel::getMapDesa)
     *  - campuran : Dukcapil sebagai baseline, ditimpa laporan desa per desa
     *
     * @return array<string,array<string,int|string>> kunci "kec.desa" => row
     */
    private function mapRowsPerDesa(?int $idKecamatan, string $sumber): array
    {
        $key = static fn(array $r): string => $r['kd_kecamatan'] . '.' . $r['kd_kelurahan'];

        if ($sumber === 'desa') {
            $out = [];
            foreach ($this->laporanModel->getMapDesa($idKecamatan) as $r) {
                $out[$key($r)] = $r;
            }
            return $out;
        }

        // dukcapil (default) & campuran sama-sama mulai dari snapshot Dukcapil.
        $out = [];
        foreach ($this->profilDukcapilModel->getMapDesa($idKecamatan) as $r) {
            $out[$key($r)] = $r;
        }

        if ($sumber === 'campuran') {
            foreach ($this->laporanModel->getMapDesa($idKecamatan) as $r) {
                $out[$key($r)] = $r; // laporan desa menimpa Dukcapil per desa
            }
        }

        return $out;
    }

    /**
     * Jumlahkan baris indikator per-desa menjadi baris per-kecamatan (kunci
     * kd_kecamatan 3 digit), lalu tambah turunan total_jiwa/total_kk.
     * Duplikasi kecil yang DISENGAJA dari PublicController::aggregateMapToKecamatan().
     *
     * @param array<string,array<string,int|string>> $desaRows
     * @return array<string,array<string,int|string>> kd_kecamatan => row
     */
    private function aggregateMapToKecamatan(array $desaRows): array
    {
        $fields = LaporanAgregatModel::mapIndicatorFields();
        $out    = [];

        foreach ($desaRows as $r) {
            $kode = (string) ($r['kd_kecamatan'] ?? '');
            if ($kode === '') {
                continue;
            }
            if (!isset($out[$kode])) {
                $out[$kode] = ['kd_kecamatan' => $kode];
                foreach ($fields as $f) {
                    $out[$kode][$f] = 0;
                }
            }
            foreach ($fields as $f) {
                $out[$kode][$f] += (int) ($r[$f] ?? 0);
            }
        }

        foreach ($out as &$row) {
            $row['total_jiwa'] = (int) $row['jiwa_l'] + (int) $row['jiwa_p'];
            $row['total_kk']   = (int) $row['kk_l'] + (int) $row['kk_p'];
        }
        unset($row);

        return $out;
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
    // GET /api/v1/bkkbn/krs?tahun= — cermin Monitoring KRS BKKBN (sumber: BKKBN, cache 30 menit)
    public function bkkbnKrs()
    {
        $tahun = filter_var($this->request->getGet('tahun') ?? 2026, FILTER_VALIDATE_INT, ['options' => ['min_range' => 2000, 'max_range' => 2100]]);
        if (!$tahun) {
            return $this->json(['status' => 'error', 'message' => 'Parameter tahun harus berupa angka 2000-2100.'], 422);
        }
        $raw = $this->fetchMonitoringKrsInternal($tahun, null);
        return $this->json([
            'status'     => 'ok',
            'sumber'     => 'BKKBN (SIGA gateway), cache 30 menit — bukan data Erdekatala',
            'tahun'      => $tahun,
            'updated_at' => $raw['updated_at'],
            'error'      => $raw['error'],
            'data'       => $raw['rows'],
        ]);
    }

    // GET /api/v1/bkkbn/dallap?tahun=&bulan= — cermin Rekap Bangga Kencana BKKBN (sumber: BKKBN, cache 30 menit)
    public function bkkbnDallap()
    {
        $tahun = filter_var($this->request->getGet('tahun') ?? 2026, FILTER_VALIDATE_INT, ['options' => ['min_range' => 2000, 'max_range' => 2100]]);
        $bulan = filter_var($this->request->getGet('bulan') ?? 8, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 12]]);
        if (!$tahun || !$bulan) {
            return $this->json(['status' => 'error', 'message' => 'Parameter tahun (2000-2100) dan bulan (1-12) harus valid.'], 422);
        }
        $m = $this->fetchDallapMatrixInternal($tahun, $bulan);
        return $this->json([
            'status'     => 'ok',
            'sumber'     => 'BKKBN (SIGA gateway), cache 30 menit — bukan data Erdekatala',
            'tahun'      => $tahun,
            'bulan'      => $bulan,
            'updated_at' => $m['updated_at'],
            'error'      => $m['error'],
            'defs'       => $m['defs'],
            'data'       => $m['rows'],
        ]);
    }

    // GET /api/v1/bkkbn/yankb?report=&tahun=&bulan= — cermin YanKB BKKBN (sumber: BKKBN, cache 30 menit)
    public function bkkbnYankb()
    {
        $report = strtoupper(trim((string) ($this->request->getGet('report') ?? '')));
        if (!in_array($report, ['TABEL1', 'TABEL21'], true)) {
            return $this->json(['status' => 'error', 'message' => 'Parameter report wajib: Tabel1 atau Tabel21.'], 422);
        }
        $reportName = $report === 'TABEL1' ? 'Tabel1' : 'Tabel21';
        $tahun = filter_var($this->request->getGet('tahun') ?? 2026, FILTER_VALIDATE_INT, ['options' => ['min_range' => 2000, 'max_range' => 2100]]);
        $bulan = filter_var($this->request->getGet('bulan') ?? 8, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 12]]);
        if (!$tahun || !$bulan) {
            return $this->json(['status' => 'error', 'message' => 'Parameter tahun (2000-2100) dan bulan (1-12) harus valid.'], 422);
        }
        $r = $this->fetchYankbReportInternal($reportName, $tahun, $bulan);
        return $this->json([
            'status'     => 'ok',
            'sumber'     => 'BKKBN (SIGA gateway), cache 30 menit — bukan data Erdekatala',
            'report'     => $reportName,
            'tahun'      => $tahun,
            'bulan'      => $bulan,
            'updated_at' => $r['updated_at'],
            'error'      => $r['error'],
            'data'       => $this->parseYankbHtml($r['html'] ?? ''),
        ]);
    }
}
