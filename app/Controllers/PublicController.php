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
        $slug_kecamatan = $this->request->getGet('kecamatan');
        $slug_desa      = $this->request->getGet('desa');

        $id_kecamatan_filter = null;
        $id_desa_filter      = null;

        // Resolve slug kecamatan ke ID
        if ($slug_kecamatan) {
            $kecamatan = $this->kecamatanModel
                ->where('LOWER(REPLACE(nama_kecamatan, " ", "-"))', strtolower($slug_kecamatan))
                ->where('is_public', 1)
                ->first();
            if ($kecamatan) {
                $id_kecamatan_filter = $kecamatan['id_kecamatan'];
            }
        }

        // Resolve slug desa ke ID
        if ($slug_desa && $id_kecamatan_filter) {
            $desa = $this->desaModel
                ->where('LOWER(REPLACE(nama_desa, " ", "-"))', strtolower($slug_desa))
                ->where('id_kecamatan', $id_kecamatan_filter)
                ->first();
            if ($desa) {
                $id_desa_filter = $desa['id_desa'];
            }
        }

        $list_kecamatan = $this->kecamatanModel->where('is_public', 1)->findAll();

        $list_desa = [];
        if ($id_kecamatan_filter) {
            $list_desa = $this->desaModel->where('id_kecamatan', $id_kecamatan_filter)->findAll();
        }

        // Single SQL SUM query — no findAll() + PHP loop
        $stats = $this->laporanModel->getAggregatedStats(
            $id_kecamatan_filter,
            $id_desa_filter
        );
        $chart = LaporanAgregatModel::statsToChartData($stats);

        // KRS tree: kab total + 11 kecamatan (desa di-load via AJAX expand)
        $krs = $this->buildKrsTree(2026, $id_kecamatan_filter);

        // Cakupan Laporan Tempat Pelayanan KB (Tabel1) + Jumlah Pelayanan Peserta KB Baru (Tabel21)
        // Filter bulan via ?bulan=1..12 (default Agustus)
        $yankbBulan = (int) ($this->request->getGet('bulan') ?? 8);
        if ($yankbBulan < 1 || $yankbBulan > 12) {
            $yankbBulan = 8;
        }
        $yankb   = $this->fetchYankbReport('Tabel1', 2026, $yankbBulan);
        $yankb21 = $this->fetchYankbReport('Tabel21', 2026, $yankbBulan);

        $data = [
            'list_kecamatan'  => $list_kecamatan,
            'list_desa'       => $list_desa,
            'filter_kec_slug' => $slug_kecamatan,
            'filter_desa_slug'=> $slug_desa,
            'map_kecamatan'   => $this->buildMapKecamatan(),
            'desa_stats'      => $id_kecamatan_filter ? $this->getDesaStats($id_kecamatan_filter) : [],
            'totals'          => $chart['totals'],
            'pendidikan'      => $chart['pendidikan'],
            'pekerjaan'       => $chart['pekerjaan'],
            'jkn_bpjs'        => $chart['jkn_bpjs'],
            'status_kawin'    => $chart['status_kawin'],
            'dokumen'         => $chart['dokumen'],
            'ageLabels'       => $chart['ageLabels'],
            'piramidaL'       => $chart['piramidaL'],
            'piramidaP'       => $chart['piramidaP'],
            'krsKab'          => $krs['kab'],
            'krsKecamatan'    => $krs['kecamatan'],
            'krsUpdatedAt'    => $krs['updated_at'],
            'krsError'        => $krs['error'],
            'krsFilterKec'    => $krs['filter_kec_name'],
            'yankbHtml'       => $yankb['html'] ?? '',
            'yankbUpdatedAt'  => $yankb['updated_at'] ?? null,
            'yankbError'      => $yankb['error'] ?? null,
            'yankb21Html'     => $yankb21['html'] ?? '',
            'yankb21UpdatedAt'=> $yankb21['updated_at'] ?? null,
            'yankb21Error'    => $yankb21['error'] ?? null,
            'yankbData'       => $this->parseYankbHtml($yankb['html'] ?? ''),
            'yankb21Data'     => $this->parseYankbHtml($yankb21['html'] ?? ''),
            'yankbBulan'      => $yankbBulan,
            'yankbTahun'      => 2026,
            'cartoApiKey'     => env('carto.apiKey', ''),
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

    /**
     * Mirror laporan YanKB bulanan per kecamatan dari SIGA BKKBN (JasperReports HTML).
     * $reportName: 'Tabel1' = Cakupan Laporan Tempat Pelayanan KB,
     *              'Tabel21' = Jumlah Pelayanan Peserta KB Baru per Metode Kontrasepsi.
     * Cache file 30 menit per reportName. Bulan 1-12, tahun 4 digit.
     */
    private function fetchYankbReport(string $reportName, int $tahun = 2026, int $bulan = 8): array
    {
        $cacheKey  = $reportName . '_' . $tahun . '_' . str_pad((string) $bulan, 2, '0', STR_PAD_LEFT);
        $cacheFile = WRITEPATH . 'cache/yankb_' . $cacheKey . '.html';
        $ttl = 1800; // 30 menit

        if (is_file($cacheFile) && (time() - filemtime($cacheFile)) < $ttl) {
            $cached = file_get_contents($cacheFile);
            if ($cached !== false && $cached !== '') {
                return ['html' => $cached, 'updated_at' => date('Y-m-d H:i:s', filemtime($cacheFile)), 'error' => null];
            }
        }

        $filter = json_encode([
            'tahun'          => (string) $tahun,
            'bulan'          => $bulan,
            'provinsiId'     => '22',
            'kabupatenId'    => '322',
            'reportName'     => $reportName,
            'reportType'     => 'html',
            'reportCategory' => 'YanKB-Bulanan-Kec',
        ]);
        $url = 'https://siga-api-gateway.bkkbn.go.id/report-siga-yankb-ocp/viewReport'
            . '?filterPencarian=' . urlencode($filter);

        $raw = $this->httpGet($url);
        if ($raw === null || trim($raw) === '') {
            if (is_file($cacheFile)) {
                $cached = file_get_contents($cacheFile);
                if ($cached !== false && $cached !== '') {
                    return ['html' => $cached, 'updated_at' => date('Y-m-d H:i:s', filemtime($cacheFile)), 'error' => 'Data cache (API tidak terjangkau).'];
                }
            }
            return ['html' => '', 'updated_at' => null, 'error' => 'Gagal mengambil data Cakupan Laporan Tempat Pelayanan KB dari BKKBN.'];
        }

        if (!is_dir(dirname($cacheFile))) {
            @mkdir(dirname($cacheFile), 0755, true);
        }
        @file_put_contents($cacheFile, $raw);

        return ['html' => $raw, 'updated_at' => date('Y-m-d H:i:s'), 'error' => null];
    }

    /**
     * Parse HTML JasperReports YanKB menjadi struktur tabel terstruktur
     * (headers multi-row + data rows). Return [] jika tidak dikenali.
     */
    private function parseYankbHtml(string $html): array
    {
        $html = trim($html);
        if ($html === '') {
            return [];
        }

        $dom = new \DOMDocument();
        \libxml_use_internal_errors(true);
        $dom->loadHTML($html);
        \libxml_clear_errors();
        $xp = new \DOMXPath($dom);

        $trs = $xp->query('//table[contains(@class,"jrPage")]/tr');
        if (!$trs || $trs->length === 0) {
            return [];
        }

        // Kumpulkan semua baris sebagai array sel
        $allRows = [];
        foreach ($trs as $tr) {
            $cells = [];
            foreach ($xp->query('./td', $tr) as $td) {
                $text = trim(preg_replace('/\s+/', ' ', $td->textContent));
                $cells[] = [
                    't'  => $text,
                    'rs' => (int) ($td->getAttribute('rowspan') ?: 1),
                    'cs' => (int) ($td->getAttribute('colspan') ?: 1),
                ];
            }
            if ($cells) {
                $allRows[] = $cells;
            }
        }

        // Cari index baris 'KODE' → awal header
        $headerStart = -1;
        foreach ($allRows as $i => $row) {
            $merged = implode('', array_map(fn($c) => $c['t'], $row));
            $clean = preg_replace('/\s+/', '', $merged);
            if (strpos($clean, 'KODE') !== false && strpos($clean, 'KECAMATAN') !== false) {
                $headerStart = $i;
                break;
            }
        }
        if ($headerStart < 0) {
            return [];
        }

        // 4 baris header
        $headerRows = array_slice($allRows, $headerStart, 4);
        $dataRows   = array_slice($allRows, $headerStart + 4);

        // Buang sel kosong di tepi kiri/kanan (colspan 1, teks kosong)
        $stripEdges = static function (array &$rows): void {
            foreach ($rows as &$row) {
                while ($row && $row[0]['t'] === '' && $row[0]['cs'] === 1) {
                    array_shift($row);
                }
                while ($row && end($row)['t'] === '' && end($row)['cs'] === 1) {
                    array_pop($row);
                }
            }
            unset($row);
        };
        $stripEdges($headerRows);
        $stripEdges($dataRows);

        // Filter data rows: hanya baris dengan minimal 1 sel non-kosong
        $dataRows = array_values(array_filter($dataRows, fn($r) => implode('', array_column($r, 't')) !== ''));

        // Tandai baris total (terakhir, teks 'total')
        $totalIdx = -1;
        foreach ($dataRows as $i => $r) {
            $first = $r[0]['t'] ?? '';
            if (stripos($first, 'total') !== false) {
                $totalIdx = $i;
            }
        }

        // Bersihkan label Jasper yang menggabung kata
        $cleanLabel = static function (string $t): string {
            $t = str_replace(['YANGADA', 'YANGLAPOR', 'BIDANJEJARING'], ['YANG ADA', 'YANG LAPOR', 'BIDAN JEJARING'], $t);
            $t = preg_replace('/\(/', ' (', $t);
            return trim(preg_replace('/\s+/', ' ', $t));
        };

        // Bangun output
        $rows = [];
        foreach ($dataRows as $i => $r) {
            $cells = [];
            foreach ($r as $cell) {
                $cells[] = ['t' => $cleanLabel($cell['t']), 'cs' => $cell['cs']];
            }
            $rows[] = ['cells' => $cells, 'total' => ($i === $totalIdx)];
        }

        // Hitung posisi kolom separator grup dari header (grid expand rowspan/colspan)
        $seps = [];
        $grid = [];
        foreach ($headerRows as $ri => $row) {
            $c = 0;
            foreach ($row as $cell) {
                while (isset($grid[$ri][$c])) $c++;
                $endCol = $c + $cell['cs'];
                if ($cell['cs'] > 1) {
                    $seps[] = $endCol;
                }
                for ($dr = 0; $dr < $cell['rs']; $dr++) {
                    for ($dc = 0; $dc < $cell['cs']; $dc++) {
                        $grid[$ri + $dr][$c + $dc] = true;
                    }
                }
                $c = $endCol;
            }
        }
        $seps = array_values(array_unique($seps));
        sort($seps);

        // Bersihkan label header juga
        foreach ($headerRows as &$row) {
            foreach ($row as &$cell) {
                $cell['t'] = $cleanLabel($cell['t']);
            }
        }
        unset($row, $cell);

        return [
            'headers' => $headerRows,
            'rows'    => $rows,
            'seps'    => $seps,
        ];
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
            'cartoApiKey'    => env('carto.apiKey', ''),
        ];

        return view('public/kecamatan', $data);
    }

    /**
     * AJAX endpoint untuk mendapatkan desa berdasarkan kecamatan (ID, legacy)
     */
    public function getDesaByKecamatan($id_kecamatan)
    {
        $desa = $this->desaModel->where('id_kecamatan', $id_kecamatan)->findAll();
        return $this->response->setJSON($desa);
    }

    /**
     * AJAX endpoint untuk mendapatkan desa berdasarkan slug kecamatan
     * URL: /getDesaByKecamatanSlug/pelaihari
     */
    public function getDesaByKecamatanSlug($slug)
    {
        $kecamatan = $this->kecamatanModel
            ->where('LOWER(REPLACE(nama_kecamatan, " ", "-"))', strtolower($slug))
            ->where('is_public', 1)
            ->first();

        if (!$kecamatan) {
            return $this->response->setStatusCode(404)->setJSON([]);
        }

        $desa = $this->desaModel
            ->where('id_kecamatan', $kecamatan['id_kecamatan'])
            ->findAll();

        // Tambahkan slug ke setiap desa
        foreach ($desa as &$d) {
            $d['slug'] = strtolower(str_replace(' ', '-', $d['nama_desa']));
        }

        return $this->response->setJSON($desa);
    }

    /**
     * Data untuk peta sebaran: koordinat kecamatan (hardcode, sumber: peta.web.id)
     * + statistik agregat per kecamatan.
     */
    private function buildMapKecamatan(): array
    {
        $kecamatan = $this->kecamatanModel->where('is_public', 1)->findAll();

        // Koordinat fallback per kecamatan (peta.web.id) — dipakai bila poligon tak ada
        $coords = $this->kecamatanCoordinatesMap();

        // Statistik agregat per kecamatan
        $summaries = $this->laporanModel->getSummaryPerKecamatan();
        $stats = [];
        foreach ($summaries as $s) {
            $stats[$s['nama_kecamatan']] = $s;
        }

        $map = [];
        foreach ($kecamatan as $k) {
            $id = (int) $k['id_kecamatan'];
            $key = strtoupper(trim(str_replace(['_', '-'], ' ', $k['nama_kecamatan'])));
            $key = preg_replace('/\s+/', ' ', $key) ?? $key;
            $s = $stats[$k['nama_kecamatan']] ?? [];
            $slug = $k['slug'] ?: $this->slugify($k['nama_kecamatan']);
            $c = $coords[$key] ?? null;
            $map[] = [
                'kode'         => $this->kodeKecamatan3($k['kode_kecamatan']),
                'id_kecamatan' => $id,
                'nama'         => $k['nama_kecamatan'],
                'slug'         => $slug,
                'lat'          => $c['lat'] ?? null,
                'lng'          => $c['lng'] ?? null,
                'total_jiwa'   => (int) ($s['total_jiwa'] ?? 0),
                'total_jiwa_l' => (int) ($s['total_jiwa_l'] ?? 0),
                'total_jiwa_p' => (int) ($s['total_jiwa_p'] ?? 0),
                'total_kk'     => (int) ($s['total_kk'] ?? 0),
                'total_pus'    => (int) ($s['total_pus'] ?? 0),
            ];
        }

        return $map;
    }

    /**
     * "63.01.01" / "63.01.001" -> "001" (3 digit, kunci join dengan GeoJSON BPS)
     */
    private function kodeKecamatan3(string $kodeKecamatan): string
    {
        $parts = explode('.', $kodeKecamatan);
        $last = (string) (end($parts) ?: '');

        return str_pad($last, 3, '0', STR_PAD_LEFT);
    }

    /** // ponytail: koordinat kecamatan Tanah Laut, sumber https://peta.web.id/peta/kab/kabupaten-tanah-laut/ */
    private function kecamatanCoordinatesMap(): array
    {
        return [
            'BAJUIN'        => ['lat' => -3.7115033,  'lng' => 114.8804853],
            'BATI BATI'     => ['lat' => -3.565921,   'lng' => 114.7855271],
            'BATU AMPAR'    => ['lat' => -3.8215822,  'lng' => 114.9279547],
            'BUMI MAKMUR'   => ['lat' => -3.5617356,  'lng' => 114.607413],
            'JORONG'        => ['lat' => -3.9318403,  'lng' => 114.9754175],
            'KINTAP'        => ['lat' => -3.857227,   'lng' => 115.207901],
            'KURAU'         => ['lat' => -3.6442297,  'lng' => 114.6430427],
            'PANYIPATAN'    => ['lat' => -4.0351561,  'lng' => 114.6667939],
            'PELAIHARI'     => ['lat' => -3.799686,   'lng' => 114.746696],
            'TAKISUNG'      => ['lat' => -3.8676527,  'lng' => 114.6430427],
            'TAMBANG ULANG' => ['lat' => -3.6400722,  'lng' => 114.7855271],
        ];
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
            'm_desa.id_desa, m_desa.nama_desa, m_desa.kode_desa,
             SUM(laporan_agregat.jiwa_l) AS total_jiwa_l,
             SUM(laporan_agregat.jiwa_p) AS total_jiwa_p,
             SUM(laporan_agregat.kk_l + laporan_agregat.kk_p) AS total_kk,
             COUNT(laporan_agregat.id_laporan) AS rt_count'
        );
        $builder->join('m_desa', 'm_desa.id_desa = laporan_agregat.id_desa');
        $builder->where('m_desa.id_kecamatan', $id_kecamatan);
        $builder->groupBy('m_desa.id_desa');
        $builder->orderBy('m_desa.nama_desa', 'ASC');

        $rows = $builder->get()->getResultArray();

        // Tambah kunci join GeoJSON ("002.007") + total jiwa
        foreach ($rows as &$r) {
            $parts = explode('.', (string) ($r['kode_desa'] ?? ''));
            $kec = str_pad((string) ($parts[2] ?? ''), 3, '0', STR_PAD_LEFT);
            $desa = substr((string) ($parts[3] ?? ''), -3);
            $r['kode'] = $kec . '.' . $desa;
            $r['total_jiwa'] = (int) $r['total_jiwa_l'] + (int) $r['total_jiwa_p'];
        }
        unset($r);

        return $rows;
    }
}
