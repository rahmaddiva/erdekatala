<?php

namespace App\Controllers;

use App\Models\LaporanAgregatModel;
use App\Models\KecamatanModel;
use App\Models\DesaModel;
use CodeIgniter\Exceptions\PageNotFoundException;

class PublicController extends BaseController
{
    use BkkbnMirror;
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

        // Dallap: rekap program Bangga Kencana per kecamatan (matriks ringkas).
        // 5 laporan tahunan + BKB bulanan (mengikuti filter ?bulan=).
        $dallap = $this->fetchDallapMatrix(2026, $yankbBulan);

        // Cakupan pelaporan + periode terbaru (kartu status di landing page)
        $coverage     = $this->laporanModel->getCoverage($id_kecamatan_filter, $id_desa_filter);
        $latestPeriod = $this->laporanModel->getLatestPeriod();

        $data = [
            'list_kecamatan'  => $list_kecamatan,
            'list_desa'       => $list_desa,
            'filter_kec_slug' => $slug_kecamatan,
            'filter_desa_slug'=> $slug_desa,
            'coverage'        => $coverage,
            'latestPeriod'    => $latestPeriod,
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
            'dallapRows'      => $dallap['rows'],
            'dallapDefs'      => $dallap['defs'],
            'dallapUpdatedAt' => $dallap['updated_at'],
            'dallapError'     => $dallap['error'],
            'dallapTahun'     => 2026,
            'cartoApiKey'     => env('carto.apiKey', ''),
        ];

        return view('public/landingpage', $data);
    }

    /**
     * AJAX: matriks per-desa untuk 1 kecamatan (Dallap), dipanggil dari dropdown
     * di tab Rekap Program Bangga Kencana. $kecSlug = slug kecamatan.
     */
    public function getDallapDesa(string $kecSlug)
    {
        $namaKec = $this->kecamatanModel
            ->where('LOWER(REPLACE(nama_kecamatan, " ", "-"))', strtolower($kecSlug))
            ->first();
        if (!$namaKec) {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'kecamatan tidak dikenal', 'rows' => []]);
        }
        $bkkbnId = $this->resolveBkkbnKecamatanId($namaKec['nama_kecamatan']);
        if (!$bkkbnId) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'kecamatan tidak punya id BKKBN', 'rows' => []]);
        }
        $bulan = (int) ($this->request->getGet('bulan') ?? 8);
        if ($bulan < 1 || $bulan > 12) {
            $bulan = 8;
        }
        $m = $this->fetchDallapMatrix(2026, $bulan, $bkkbnId, $namaKec['nama_kecamatan']);
        return $this->response->setJSON([
            'nama_kecamatan' => $namaKec['nama_kecamatan'],
            'defs'           => $m['defs'],
            'rows'           => $m['rows'],
            'updated_at'     => $m['updated_at'],
            'error'          => $m['error'],
        ]);
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
