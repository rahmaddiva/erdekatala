<?php

namespace App\Controllers;

use App\Models\LaporanAgregatModel;
use App\Models\KecamatanModel;
use App\Models\DesaModel;
use CodeIgniter\Exceptions\PageNotFoundException;

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
        $id_kecamatan_filter = $this->request->getGet('id_kecamatan');
        $id_desa_filter      = $this->request->getGet('id_desa');

        $list_kecamatan = $this->kecamatanModel->where('is_public', 1)->findAll();

        $list_desa = [];
        if ($id_kecamatan_filter) {
            $list_desa = $this->desaModel->where('id_kecamatan', $id_kecamatan_filter)->findAll();
        }

        // Single SQL SUM query — no findAll() + PHP loop
        $stats = $this->laporanModel->getAggregatedStats(
            $id_kecamatan_filter ? (int) $id_kecamatan_filter : null,
            $id_desa_filter ? (int) $id_desa_filter : null
        );
        $chart = LaporanAgregatModel::statsToChartData($stats);

        // KRS tree: kab total + 11 kecamatan (desa di-load via AJAX expand)
        $krs = $this->buildKrsTree(2026, $id_kecamatan_filter ? (int) $id_kecamatan_filter : null);

        $data = [
            'list_kecamatan' => $list_kecamatan,
            'list_desa'      => $list_desa,
            'filter_kec'     => $id_kecamatan_filter,
            'filter_desa'    => $id_desa_filter,
            'totals'         => $chart['totals'],
            'pendidikan'     => $chart['pendidikan'],
            'pekerjaan'      => $chart['pekerjaan'],
            'jkn_bpjs'       => $chart['jkn_bpjs'],
            'status_kawin'   => $chart['status_kawin'],
            'dokumen'        => $chart['dokumen'],
            'ageLabels'      => $chart['ageLabels'],
            'piramidaL'      => $chart['piramidaL'],
            'piramidaP'      => $chart['piramidaP'],
            'krsKab'         => $krs['kab'],
            'krsKecamatan'   => $krs['kecamatan'],
            'krsUpdatedAt'   => $krs['updated_at'],
            'krsError'       => $krs['error'],
            'krsFilterKec'   => $krs['filter_kec_name'],
        ];

        return view('public/landingpage', $data);
    }

    /**
     * AJAX: daftar desa KRS untuk 1 kecamatan BKKBN.
     */
    public function getKrsDesa($idKecamatanBkkbn)
    {
        $id = (int) $idKecamatanBkkbn;
        if ($id <= 0 || !in_array($id, array_values($this->bkkbnKecamatanMap()), true)) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'idKecamatan tidak valid', 'rows' => []]);
        }
        $krs = $this->fetchMonitoringKrs(2026, $id);
        return $this->response->setJSON([
            'rows'       => $krs['rows'],
            'updated_at' => $krs['updated_at'],
            'error'      => $krs['error'],
        ]);
    }

    /** // ponytail: hardcoded BKKBN ids, ganti DB column jika berubah */
    private function bkkbnKecamatanMap(): array
    {
        return [
            'TAKISUNG'      => 6157,
            'JORONG'        => 5721,
            'PELAIHARI'     => 5847,
            'KURAU'         => 5846,
            'BATI BATI'     => 5845,
            'PANYIPATAN'    => 5844,
            'KINTAP'        => 5720,
            'TAMBANG ULANG' => 5712,
            'BATU AMPAR'    => 5843,
            'BAJUIN'        => 6625,
            'BUMI MAKMUR'   => 6624,
        ];
    }

    private function resolveBkkbnKecamatanId(string $nama): ?int
    {
        $key = strtoupper(trim(str_replace(['_', '-'], ' ', $nama)));
        $key = preg_replace('/\s+/', ' ', $key) ?? $key;
        return $this->bkkbnKecamatanMap()[$key] ?? null;
    }

    /**
     * Bangun tree KRS: baris kab (sum) + list kecamatan + id BKKBN untuk expand.
     * Jika filter kecamatan lokal → hanya 1 kecamatan (auto-expand via UI).
     */
    private function buildKrsTree(int $tahun, ?int $idKecamatanLokal = null): array
    {
        $raw = $this->fetchMonitoringKrs($tahun, null);
        $rows = $raw['rows'] ?? [];
        $filterName = null;

        if ($idKecamatanLokal) {
            $kecRow = $this->kecamatanModel->find($idKecamatanLokal);
            $filterName = $kecRow['nama_kecamatan'] ?? null;
            if ($filterName) {
                $target = strtoupper(trim(str_replace(['_', '-'], ' ', $filterName)));
                $target = preg_replace('/\s+/', ' ', $target) ?? $target;
                $rows = array_values(array_filter($rows, function ($r) use ($target) {
                    $n = strtoupper(trim(str_replace(['_', '-'], ' ', $r['namaDaerah'] ?? '')));
                    $n = preg_replace('/\s+/', ' ', $n) ?? $n;
                    return $n === $target;
                }));
            }
        }

        $kecamatan = [];
        foreach ($rows as $r) {
            $nama = $r['namaDaerah'] ?? '';
            $r['bkkbn_id'] = $this->resolveBkkbnKecamatanId($nama);
            $kecamatan[] = $r;
        }

        return [
            'kab'             => $this->sumKrsRows($kecamatan, 'TANAH LAUT'),
            'kecamatan'       => $kecamatan,
            'updated_at'      => $raw['updated_at'] ?? null,
            'error'           => $raw['error'] ?? null,
            'filter_kec_name' => $filterName,
        ];
    }

    /** Sum numerik field KRS (string ber-koma) jadi 1 baris kab. */
    private function sumKrsRows(array $rows, string $nama): array
    {
        $numKeys = [
            'kelurahanAda', 'kelurahanTarget', 'kelurahanVerval',
            'pusSasaran', 'pusVervalAda', 'pusVervalBaru', 'pusVervalAdaBaru',
            'pusHamilSasaran', 'pushamilVervalAda', 'pushamilVervalBaru',
            'badutaSasaran', 'badutaVervalAda', 'badutaVervalBaru',
            'balitaSasaran', 'balitaVervalAda', 'balitaVervalBaru',
            'keluargaSasaranCatin', 'sasaranPrioritas', 'prioritasTerverval',
            'adaBaruLainTerverval', 'totalSasaran', 'totalVervalAdaBaru',
            'totalVervalAda', 'totalVervalBaru', 'totalRekap',
        ];
        $sum = array_fill_keys($numKeys, 0);
        foreach ($rows as $r) {
            foreach ($numKeys as $k) {
                $sum[$k] += (int) str_replace([',', '.'], '', (string) ($r[$k] ?? 0));
            }
        }
        // format kembali seperti API (dengan koma ribuan US)
        $out = ['namaDaerah' => $nama, 'kategoriLaporan' => 'Kabupaten', 'kode' => '322'];
        foreach ($sum as $k => $v) {
            $out[$k] = number_format($v, 0, '.', ',');
        }
        $out['cakupanKelurahanVerval'] = ($sum['kelurahanAda'] > 0)
            ? round($sum['kelurahanVerval'] / $sum['kelurahanAda'] * 100)
            : 0;
        $out['persenTarget'] = ($sum['totalSasaran'] > 0)
            ? (string) round($sum['totalVervalAdaBaru'] / $sum['totalSasaran'] * 100)
            : '0';
        return $out;
    }

    /**
     * Mirror live Monitoring KRS dari API BKKBN (Tanah Laut).
     * Cache file 30 menit. idKecamatan BKKBN opsional → drill-down desa.
     */
    private function fetchMonitoringKrs(int $tahun = 2026, ?int $idKecamatanBkkbn = null): array
    {
        $cacheKey  = $tahun . ($idKecamatanBkkbn ? '_kec' . $idKecamatanBkkbn : '_kab');
        $cacheFile = WRITEPATH . 'cache/krs_monitoring_' . $cacheKey . '.json';
        $ttl = 1800; // 30 menit

        if (is_file($cacheFile) && (time() - filemtime($cacheFile)) < $ttl) {
            $cached = json_decode(file_get_contents($cacheFile), true);
            if (is_array($cached) && isset($cached['rows'])) {
                return $cached;
            }
        }

        $url = 'https://siga-api-gateway.bkkbn.go.id/modul-stunting-sigabaru/siga/stunting/getMonitoringKrs'
            . '?tahun=' . $tahun . '&idProvinsi=22&idKabupaten=322';
        if ($idKecamatanBkkbn) {
            $url .= '&idKecamatan=' . $idKecamatanBkkbn;
        }

        $raw = $this->httpGet($url);
        if ($raw === null) {
            if (is_file($cacheFile)) {
                $cached = json_decode(file_get_contents($cacheFile), true);
                if (is_array($cached) && isset($cached['rows'])) {
                    $cached['error'] = 'Data cache (API tidak terjangkau).';
                    return $cached;
                }
            }
            return ['rows' => [], 'updated_at' => null, 'error' => 'Gagal mengambil data KRS dari BKKBN.'];
        }

        $json = json_decode($raw, true);
        $rows = $json['data'] ?? [];
        if (!is_array($rows)) {
            $rows = [];
        }

        $payload = [
            'rows'       => $rows,
            'updated_at' => date('Y-m-d H:i:s'),
            'error'      => null,
        ];

        if (!is_dir(dirname($cacheFile))) {
            @mkdir(dirname($cacheFile), 0755, true);
        }
        @file_put_contents($cacheFile, json_encode($payload));

        return $payload;
    }

    /** GET remote URL — curl dulu, fallback file_get_contents. */
    private function httpGet(string $url): ?string
    {
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 8,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTPHEADER     => ['Accept: application/json'],
                CURLOPT_SSL_VERIFYPEER => true,
            ]);
            $body = curl_exec($ch);
            $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if ($body !== false && $code >= 200 && $code < 300) {
                return $body;
            }
        }

        $ctx = stream_context_create([
            'http' => [
                'method'  => 'GET',
                'timeout' => 8,
                'header'  => "Accept: application/json\r\n",
            ],
            'ssl' => [
                'verify_peer'      => true,
                'verify_peer_name' => true,
            ],
        ]);
        $body = @file_get_contents($url, false, $ctx);
        return $body === false ? null : $body;
    }

    /**
     * Halaman per-kecamatan dengan URL slug-based: /kurau, /pelaihari, dst.
     * Mendukung filter desa via ?desa=slug
     */
    public function kecamatan($slug)
    {
        $all_kecamatan = $this->kecamatanModel->orderBy('nama_kecamatan', 'ASC')->findAll();

        $kec = null;
        $slugLower = strtolower($slug);
        foreach ($all_kecamatan as $k) {
            $kSlug = $k['slug'] ?: $this->slugify($k['nama_kecamatan']);
            if ($kSlug === $slugLower) {
                $kec = $k;
                break;
            }
        }

        if ($kec === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        if (!$kec['is_public']) {
            throw PageNotFoundException::forPageNotFound();
        }

        $list_desa = $this->desaModel
            ->where('id_kecamatan', $kec['id_kecamatan'])
            ->orderBy('nama_desa', 'ASC')
            ->findAll();

        $desa_slug   = $this->request->getGet('desa');
        $selected_desa = null;

        if ($desa_slug) {
            foreach ($list_desa as $d) {
                if ($this->slugify($d['nama_desa']) === strtolower($desa_slug)) {
                    $selected_desa = $d;
                    break;
                }
            }
        }

        // Single SQL SUM query — no findAll() + PHP loop
        $stats = $this->laporanModel->getAggregatedStats(
            (int) $kec['id_kecamatan'],
            $selected_desa ? (int) $selected_desa['id_desa'] : null
        );
        $chart = LaporanAgregatModel::statsToChartData($stats);

        $desa_stats = $this->getDesaStats($kec['id_kecamatan']);

        $pendudukKey = $selected_desa
            ? 'Desa ' . $selected_desa['nama_desa']
            : 'Kecamatan ' . $kec['nama_kecamatan'];

        $public_kecamatan = array_filter($all_kecamatan, fn($k) => $k['is_public']);

        $data = [
            'kecamatan'         => $kec,
            'selected_desa'     => $selected_desa,
            'list_desa'         => $list_desa,
            'desa_stats'        => $desa_stats,
            'all_kecamatan'     => array_values($public_kecamatan),
            'pendudukKey'    => $pendudukKey,
            'totals'         => $chart['totals'],
            'pendidikan'     => $chart['pendidikan'],
            'pekerjaan'      => $chart['pekerjaan'],
            'jkn_bpjs'       => $chart['jkn_bpjs'],
            'status_kawin'   => $chart['status_kawin'],
            'dokumen'        => $chart['dokumen'],
            'ageLabels'      => $chart['ageLabels'],
            'piramidaL'      => $chart['piramidaL'],
            'piramidaP'      => $chart['piramidaP'],
        ];

        return view('public/kecamatan', $data);
    }

    /**
     * AJAX endpoint untuk mendapatkan desa berdasarkan kecamatan
     */
    public function getDesaByKecamatan($id_kecamatan)
    {
        $desa = $this->desaModel->where('id_kecamatan', $id_kecamatan)->findAll();
        return $this->response->setJSON($desa);
    }

    // ===================================================
    //  PRIVATE HELPERS
    // ===================================================

    /**
     * Ubah nama menjadi slug URL: "Tambang Ulang" → "tambang-ulang"
     */
    private function slugify($name)
    {
        return strtolower(str_replace(' ', '-', $name));
    }

    /**
     * Statistik per-desa untuk tabel di halaman kecamatan.
     * Mengembalikan: id_desa, nama_desa, total_jiwa_l, total_jiwa_p, total_kk, rt_count
     */
    private function getDesaStats($id_kecamatan)
    {
        $builder = $this->laporanModel->builder();
        $builder->select(
            'm_desa.id_desa, m_desa.nama_desa,
             SUM(laporan_agregat.jiwa_l) AS total_jiwa_l,
             SUM(laporan_agregat.jiwa_p) AS total_jiwa_p,
             SUM(laporan_agregat.kk_l + laporan_agregat.kk_p) AS total_kk,
             COUNT(laporan_agregat.id_laporan) AS rt_count'
        );
        $builder->join('m_desa', 'm_desa.id_desa = laporan_agregat.id_desa');
        $builder->where('m_desa.id_kecamatan', $id_kecamatan);
        $builder->groupBy('m_desa.id_desa');
        $builder->orderBy('m_desa.nama_desa', 'ASC');

        return $builder->get()->getResultArray();
    }
}
