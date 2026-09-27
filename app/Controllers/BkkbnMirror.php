<?php

namespace App\Controllers;

/**
 * Cermin live API BKKBN (SIGA gateway) + cache file 30 menit.
 * Dipakai PublicController (halaman publik) dan ApiController (endpoint /api/v1/bkkbn/*).
 * Data BUKAN milik Erdekatala — selalu labeli sumber di konsumen.
 *
 * Method di trait ini sengaja `private`. Untuk memakainya dari luar
 * controller (mis. spark command), bungkus lewat App\Libraries\BkkbnMirrorService —
 * jangan ubah visibility di sini agar API-nya tidak melebar.
 *
 * Catatan penamaan: tiga method fetch*Internal dipakai sebagai implementasi
 * oleh BkkbnMirrorService (yang mengekspos nama publik tanpa suffix).
 */
trait BkkbnMirror
{

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
     * Mirror live Monitoring KRS dari API BKKBN (Tanah Laut).
     * Cache file 30 menit. idKecamatan BKKBN opsional → drill-down desa.
     */
    private function fetchMonitoringKrsInternal(int $tahun = 2026, ?int $idKecamatanBkkbn = null): array
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
            . '?tahun=' . $tahun . '&periode=2&idProvinsi=22&idKabupaten=322';
        if ($idKecamatanBkkbn) {
            $url .= '&idKecamatan=' . $idKecamatanBkkbn;
        }

        // Throttle retry saat API down: skip fetch sinkron bila baru gagal < 10 menit lalu, langsung sajikan cache basi.
        $attemptFile = WRITEPATH . 'cache/krs_attempt_' . $cacheKey . '.txt';
        $throttled   = is_file($attemptFile) && (time() - filemtime($attemptFile)) < 600 && is_file($cacheFile);
        $raw         = $throttled ? null : $this->httpGet($url);
        if ($raw === null) {
            if (!$throttled) {
                @touch($attemptFile);
                log_message('warning', 'KRS BKKBN tidak terjangkau: {url}', ['url' => $url]);
            }
            if (is_file($cacheFile)) {
                $cached = json_decode(file_get_contents($cacheFile), true);
                if (is_array($cached) && isset($cached['rows'])) {
                    $asOf = strtotime((string) ($cached['updated_at'] ?? '')) ?: filemtime($cacheFile);
                    $cached['error'] = 'Menampilkan data tersimpan per ' . date('d M Y, H:i', $asOf)
                        . ' — sumber data BKKBN sementara tidak terjangkau.';
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

        // Jangan timpa cache bagus dengan respons kosong.
        if ($rows === []) {
            @touch($attemptFile);
            if (is_file($cacheFile)) {
                $cached = json_decode(file_get_contents($cacheFile), true);
                if (is_array($cached) && !empty($cached['rows'])) {
                    $cached['error'] = 'Data BKKBN terbaru kosong — menampilkan data tersimpan.';
                    return $cached;
                }
            }
            return ['rows' => [], 'updated_at' => null, 'error' => 'Data KRS dari BKKBN kosong.'];
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
        @unlink($attemptFile); // sukses: retry berikutnya langsung fetch

        return $payload;
    }


    /**
     * Mirror laporan YanKB bulanan per kecamatan dari SIGA BKKBN (JasperReports HTML).
     * $reportName: 'Tabel1' = Cakupan Laporan Tempat Pelayanan KB,
     *              'Tabel21' = Jumlah Pelayanan Peserta KB Baru per Metode Kontrasepsi.
     * Cache file 30 menit per reportName. Bulan 1-12, tahun 4 digit.
     */
    private function fetchYankbReportInternal(string $reportName, int $tahun = 2026, int $bulan = 8): array
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


    /** Definis laporan Dallap: reportName => [label, kolom jumlah (1-based), kategori, bulanan?]. */
    private function dallapReports(): array
    {
        return [
            'Tabel2A'  => ['label' => 'PKB',        'qtyCol' => 3, 'category' => 'Dallap-Tahunan', 'monthly' => false],
            'Tabel11A' => ['label' => 'UPPKA',      'qtyCol' => 4, 'category' => 'Dallap-Tahunan', 'monthly' => false],
            'Tabel3A'  => ['label' => 'BKB',        'qtyCol' => 3, 'category' => 'Dallap-Bulanan', 'monthly' => true],
            'Tabel10A' => ['label' => 'BKL',        'qtyCol' => 4, 'category' => 'Dallap-Tahunan', 'monthly' => false],
            'Tabel9B'  => ['label' => 'BKR',        'qtyCol' => 3, 'category' => 'Dallap-Tahunan', 'monthly' => false],
            'Tabel12A' => ['label' => 'PIK Remaja', 'qtyCol' => 3, 'category' => 'Dallap-Tahunan', 'monthly' => false],
            'Tabel10'  => ['label' => 'PUS (SIGA)',      'qtyCol' => 3, 'category' => 'Dallap-Bulanan', 'monthly' => true],
            'Tabel11'  => ['label' => 'KB Aktif (SIGA)', 'qtyCol' => 3, 'category' => 'Dallap-Bulanan', 'monthly' => true],
        ];
    }


    /**
     * Matriks Rekap Bangga Kencana: 6 laporan Dallap level kecamatan (default),
     * atau per-desa jika $idKecamatanBkkbn diberikan. Cache HTML 30 menit per
     * (report, level, bulan). Return ['defs','rows','updated_at','error'];
     * rows = [['nama'=>..., 'kode'=>..., 'qty'=>[reportName=>int|null]]].
     */
    private function fetchDallapMatrixInternal(int $tahun = 2026, int $bulan = 8, ?int $idKecamatanBkkbn = null, ?string $namaKecamatan = null): array
    {
        $defs = $this->dallapReports();
        $qty = [];
        $kode = [];
        $errors = [];
        $updated = null;

        foreach ($defs as $reportName => $def) {
            $r = $this->fetchDallapReport($reportName, $def, $tahun, $bulan, $idKecamatanBkkbn);
            if ($r['updated_at'] !== null && ($updated === null || $r['updated_at'] > $updated)) {
                $updated = $r['updated_at'];
            }
            if ($r['error'] !== null) {
                $errors[] = $def['label'] . ': ' . $r['error'];
            }
            $parsed = $this->parseDallapRows($r['html'] ?? '', (int) $def['qtyCol'], $idKecamatanBkkbn !== null ? 'desa' : 'kec');
            foreach ($parsed['rows'] as $nama => $p) {
                $qty[$nama][$reportName] = $p['qty'];
                if (!isset($kode[$nama])) {
                    $kode[$nama] = $p['kode'];
                }
            }
        }

        $rows = [];
        foreach (array_keys($qty) as $nama) {
            $row = ['nama' => $nama, 'kode' => $kode[$nama] ?? null, 'qty' => []];
            foreach ($defs as $reportName => $def) {
                $row['qty'][$reportName] = $qty[$nama][$reportName] ?? null;
            }
            $rows[] = $row;
        }
        usort($rows, fn($a, $b) => ((int) ($a['kode'] ?? 99)) <=> ((int) ($b['kode'] ?? 99)));

        return [
            'defs'        => $defs,
            'rows'        => $rows,
            'updated_at'  => $updated,
            'error'       => $errors ? implode(' ', $errors) : null,
            'nama_kecamatan' => $namaKecamatan,
        ];
    }


    /** Fetch 1 laporan Dallap level kabupaten/kecamatan/desa; return ['html','updated_at','error']. */
    private function fetchDallapReport(string $reportName, array $def, int $tahun, int $bulan, ?int $idKecamatanBkkbn = null): array
    {
        $suffix = $def['monthly'] ? '_' . str_pad((string) $bulan, 2, '0', STR_PAD_LEFT) : '';
        $suffix .= $idKecamatanBkkbn ? '_kec' . $idKecamatanBkkbn : '';
        $cacheFile = WRITEPATH . 'cache/dallap_' . $reportName . '_' . $tahun . $suffix . '.html';
        $ttl = 1800; // 30 menit

        if (is_file($cacheFile) && (time() - filemtime($cacheFile)) < $ttl) {
            $cached = file_get_contents($cacheFile);
            if ($cached !== false && $cached !== '') {
                return ['html' => $cached, 'updated_at' => date('Y-m-d H:i:s', filemtime($cacheFile)), 'error' => null];
            }
        }

        $filter = ['tahun' => (string) $tahun, 'provinsiId' => '22', 'kabupatenId' => '322',
            'reportName' => $reportName, 'reportType' => 'html', 'reportCategory' => $def['category']];
        if ($def['monthly']) {
            $filter['bulan'] = $bulan;
        }
        if ($idKecamatanBkkbn !== null) {
            $filter['kecamatanId'] = (string) $idKecamatanBkkbn;
        }
        $url = 'https://siga-api-gateway.bkkbn.go.id/report-siga-dallap-ocp/viewReport'
            . '?filterPencarian=' . urlencode(json_encode($filter));

        $raw = $this->httpGet($url);
        if ($raw === null || trim($raw) === '') {
            if (is_file($cacheFile)) {
                $cached = file_get_contents($cacheFile);
                if ($cached !== false && $cached !== '') {
                    return ['html' => $cached, 'updated_at' => date('Y-m-d H:i:s', filemtime($cacheFile)), 'error' => 'Data cache (API tidak terjangkau).'];
                }
            }
            return ['html' => '', 'updated_at' => null, 'error' => 'Gagal mengambil data ' . $def['label'] . ' dari BKKBN.'];
        }

        if (!is_dir(dirname($cacheFile))) {
            @mkdir(dirname($cacheFile), 0755, true);
        }
        @file_put_contents($cacheFile, $raw);

        return ['html' => $raw, 'updated_at' => date('Y-m-d H:i:s'), 'error' => null];
    }


    /**
     * Parse kolom jumlah (1-based) per baris wilayah dari HTML JasperReports Dallap.
     * Level 'kec': baris = kecamatan (kode 01-11, header KECAMATAN).
     * Level 'desa': baris = desa (kode 4 digit, header KELURAHAN).
     * Return ['rows'=>[NAMA=>['kode'=>int,'qty'=>int]], 'nama_kecamatan'=>?string].
     * Baris JUMLAH TOTAL dilewati (jumlah dihitung ulang client-side).
     */
    private function parseDallapRows(string $html, int $qtyCol, string $level = 'kec'): array
    {
        $html = trim($html);
        $out = ['rows' => [], 'nama_kecamatan' => null];
        if ($html === '') {
            return $out;
        }
        $dom = new \DOMDocument();
        \libxml_use_internal_errors(true);
        $dom->loadHTML($html);
        \libxml_clear_errors();
        $xp = new \DOMXPath($dom);

        $trs = $xp->query('//table[contains(@class,"jrPage")]/tr');
        if (!$trs || $trs->length === 0) {
            $trs = $xp->query('//table//tr');
        }
        if (!$trs || $trs->length === 0) {
            return $out;
        }

        // Level desa: judul "Kec : NAMA" dipakai sebagai nama kecamatan
        if ($level === 'desa') {
            foreach ($xp->query('//span | //td') as $s) {
                $t = trim(preg_replace('/\s+/', ' ', $s->textContent));
                if (preg_match('/^Kec\s*:\s*(.+)$/', $t, $m)) {
                    $out['nama_kecamatan'] = trim($m[1]);
                    break;
                }
            }
        }

        $codeRe = $level === 'desa' ? '/^\d{4}$/' : '/^\d{1,3}$/';
        foreach ($trs as $tr) {
            $cells = [];
            foreach ($xp->query('./td', $tr) as $td) {
                $cells[] = trim(preg_replace('/\s+/', ' ', $td->textContent));
            }
            // Buang spacer kosong di tepi (khas JasperReports) seperti parseYankbHtml
            while ($cells && reset($cells) === '') {
                array_shift($cells);
            }
            while ($cells && end($cells) === '') {
                array_pop($cells);
            }
            if (count($cells) < $qtyCol) {
                continue;
            }
            $kode = $cells[0];
            $nama = strtoupper(trim($cells[1] ?? ''));
            if (!preg_match($codeRe, (string) $kode) || $nama === '' || is_numeric($nama) || stripos($nama, 'total') !== false) {
                continue;
            }
            $qty = (int) str_replace([',', '.', ' '], '', (string) ($cells[$qtyCol - 1] ?? 0));
            $out['rows'][$nama] = ['kode' => (int) $kode, 'qty' => $qty];
        }
        return $out;
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
            $err  = curl_error($ch) ?: '-';
            curl_close($ch);
            if ($body !== false && $code >= 200 && $code < 300) {
                return $body;
            }
            log_message('debug', 'httpGet gagal: {url} (HTTP {code}, curl: {err})', ['url' => $url, 'code' => $code, 'err' => $err]);
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
        if ($body === false) {
            log_message('debug', 'httpGet fallback gagal: {url}', ['url' => $url]);
            return null;
        }
        return $body;
    }

}