<?php

namespace App\Commands;

use App\Libraries\BkkbnMirrorService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Panaskan cache mirror BKKBN (SIGA) supaya pengunjung landing page tidak
 * pernah disuguhi data basi.
 *
 * Alasan: cache KRS diambil on-demand saat pengguna klik ▶ pada kecamatan.
 * Kecamatan yang lama tidak dibuka akan menyimpan cache berbulan-bulan, dan
 * bila fetch ulang kebetulan kena gelombang gagal gateway (endpoint ini
 * flaky: terukur 60-75% sukses, gagal = 0 byte tanpa respons), pengguna
 * melihat banner "data tersimpan per <tanggal lama>".
 *
 * Perintah ini menarik ulang seluruh cache kabupaten + 11 kecamatan.
 *
 * Pemakaian:
 *   php spark bkkbn:warmup                    # KRS kabupaten + 11 kecamatan
 *   php spark bkkbn:warmup --only krs         # hanya KRS
 *   php spark bkkbn:warmup --only yankb       # hanya laporan YanKB
 *   php spark bkkbn:warmup --only dallap      # hanya matriks Dallap
 *   php spark bkkbn:warmup --bulan 9          # bulan laporan YanKB/Dallap
 *
 * Catatan: parser CLI CodeIgniter hanya mengenal opsi bergaya `--nama nilai`
 * (dipisah spasi). Bentuk `--nama=nilai` TIDAK terbaca dan akan diabaikan
 * diam-diam. Kedua bentuk tetap ditangani di bawah agar tidak menyesatkan.
 */
class BkkbnWarmup extends BaseCommand
{
    protected $group       = 'Erdekatala';
    protected $name        = 'bkkbn:warmup';
    protected $description = 'Panaskan cache mirror BKKBN (KRS + YanKB + Dallap) agar landing page tidak menampilkan data basi.';
    protected $usage       = 'bkkbn:warmup [--only krs|yankb|dallap|all] [--bulan 1..12] [--tahun YYYY]';

    /** Jeda antar-request (mikrodetik) — sopan terhadap gateway, tidak memicu rate limit. */
    private const JEDA_US = 250_000;

    public function run(array $params)
    {
        $tahun = (int) ($this->opsi('tahun') ?: 2026);
        $bulan = (int) ($this->opsi('bulan') ?: 8);
        $only  = (string) ($this->opsi('only') ?: 'all');

        if ($bulan < 1 || $bulan > 12) {
            CLI::error('Bulan harus 1-12.');
            return EXIT_ERROR;
        }
        if (!in_array($only, ['all', 'krs', 'yankb', 'dallap'], true)) {
            CLI::error('--only harus salah satu dari: all, krs, yankb, dallap');
            return EXIT_ERROR;
        }

        $svc = new BkkbnMirrorService();

        // 1. Hapus penanda throttle supaya fetch benar-benar dicoba.
        //    Tanpa ini, kegagalan < 10 menit terakhir membuat semua request
        //    langsung dilayani cache basi dan warm-up tidak ada gunanya.
        $throttle = glob(WRITEPATH . 'cache/krs_attempt_*.txt') ?: [];
        foreach ($throttle as $f) {
            @unlink($f);
        }
        $mtimeAsli = $this->rewindTime($tahun, $only, $bulan);

        $hasil = [];

        if ($only === 'all' || $only === 'krs') {
            $hasil[] = $this->warmKrs($svc, $tahun);
        }
        if ($only === 'all' || $only === 'yankb') {
            $hasil[] = $this->warmYankb($svc, $tahun, $bulan);
        }
        if ($only === 'all' || $only === 'dallap') {
            $hasil[] = $this->warmDallap($svc, $tahun, $bulan);
        }

        $dipulihkan = $this->pulihkanMtime($mtimeAsli);
        if ($dipulihkan > 0) {
            CLI::newLine();
            CLI::write('  ' . $dipulihkan . ' cache gagal di-refresh; mtime asli dikembalikan agar umurnya tidak menyesatkan.', 'light_gray');
        }

        return $this->ringkas($hasil, $only, $tahun, $bulan);
    }

    /**
     * Tarik KRS kabupaten + semua kecamatan.
     * Sengaja memakai endpoint per-kecamatan (bukan hanya kabupaten) supaya
     * cache drill-down desa ikut segar — itulah yang paling sering basi.
     */
    private function warmKrs(BkkbnMirrorService $svc, int $tahun): array
    {
        CLI::write('KRS — Monitoring VERVAL', 'yellow');

        // Daftar diambil dari trait, bukan disalin — supaya tidak ada daftar
        // kedua yang bisa basi bila id BKKBN berubah.
        $kecamatan = $svc->kecamatanMap();

        $ok = 0;
        $gagal = [];
        $t0 = microtime(true);

        $kab = $svc->fetchMonitoringKrs($tahun, null);
        $kabOk = empty($kab['error']) && !empty($kab['rows']);
        $this->baris('kabupaten', 'semua', $kabOk, count($kab['rows'] ?? []), $kab['error'] ?? null);
        $kabOk ? $ok++ : $gagal[] = 'kabupaten';
        usleep(self::JEDA_US);

        foreach ($kecamatan as $nama => $id) {
            $r = $svc->fetchMonitoringKrs($tahun, $id);
            $berhasil = empty($r['error']) && !empty($r['rows']);
            $this->baris('kecamatan', $nama, $berhasil, count($r['rows'] ?? []), $r['error'] ?? null);
            $berhasil ? $ok++ : $gagal[] = $nama;
            usleep(self::JEDA_US);
        }

        $total = 1 + count($kecamatan);

        return [
            'nama'   => 'KRS',
            'ok'     => $ok,
            'total'  => $total,
            'gagal'  => $gagal,
            'detik'  => round(microtime(true) - $t0, 1),
        ];
    }

    /** Tarik laporan YanKB (Tabel1 + Tabel21) untuk bulan terpilih. */
    private function warmYankb(BkkbnMirrorService $svc, int $tahun, int $bulan): array
    {
        CLI::write('YanKB — Cakupan & Pelayanan KB', 'yellow');

        $ok = 0;
        $gagal = [];
        $t0 = microtime(true);
        $reports = ['Tabel1' => 'Cakupan Laporan Tempat Pelayanan KB', 'Tabel21' => 'Jumlah Pelayanan Peserta KB Baru'];

        foreach ($reports as $name => $label) {
            $r = $svc->fetchYankbReport($name, $tahun, $bulan);
            $berhasil = empty($r['error']) && !empty($r['html']);
            $this->baris('YanKB', $label, $berhasil, $berhasil ? strlen($r['html']) : 0, $r['error'] ?? null, 'byte');
            $berhasil ? $ok++ : $gagal[] = $name;
            usleep(self::JEDA_US);
        }

        return ['nama' => 'YanKB', 'ok' => $ok, 'total' => count($reports), 'gagal' => $gagal, 'detik' => round(microtime(true) - $t0, 1)];
    }

    /** Tarik matriks Dallap level kabupaten (8 laporan sekaligus). */
    private function warmDallap(BkkbnMirrorService $svc, int $tahun, int $bulan): array
    {
        CLI::write('Dallap — Rekap Bangga Kencana', 'yellow');
        $t0 = microtime(true);

        $r = $svc->fetchDallapMatrix($tahun, $bulan);
        $rows = count($r['rows'] ?? []);
        $berhasil = $rows > 0;
        // Laporan Dallap tahunan tidak memakai bulan, jadi error parsial itu wajar —
        // yang penting ada baris terbentuk.
        $this->baris('Dallap', 'matriks kabupaten', $berhasil, $rows, $r['error'] ?? null, 'baris');

        return [
            'nama'  => 'Dallap',
            'ok'    => $berhasil ? 1 : 0,
            'total' => 1,
            'gagal' => $berhasil ? [] : ['matriks kabupaten'],
            'detik' => round(microtime(true) - $t0, 1),
        ];
    }

    /** Satu baris hasil warm-up. */
    private function baris(string $kelompok, string $nama, bool $ok, int $jumlah, ?string $error, string $satuan = 'baris'): void
    {
        $label = str_pad($kelompok, 10) . str_pad(mb_substr($nama, 0, 34), 36);
        if ($ok) {
            CLI::write('  ' . $label . CLI::color("OK  {$jumlah} {$satuan}", 'green'));
            return;
        }
        $ket = $error ? mb_substr($error, 0, 60) : 'tidak ada data';
        CLI::write('  ' . $label . CLI::color('GAGAL', 'red') . '  ' . CLI::color($ket, 'light_gray'));
    }

    /**
     * Baca opsi CLI secara toleran terhadap dua gaya penulisan:
     *   --only krs        (didukung CodeIgniter)   -> terbaca
     *   --only=krs        (umum tapi TIDAK didukung) -> ditangani manual di sini
     *
     * Tanpa ini, `--only=krs` diabaikan diam-diam dan perintah berjalan
     * dengan cakupan penuh — perilaku yang menyesatkan.
     */
    private function opsi(string $nama): ?string
    {
        $nilai = CLI::getOption($nama);
        if ($nilai !== null && $nilai !== true) {
            return (string) $nilai;
        }

        // Bentuk `--nama=nilai`, termasuk bila CI4 menelannya jadi satu segmen.
        foreach (array_merge($_SERVER['argv'] ?? [], CLI::getSegments()) as $arg) {
            if (str_starts_with($arg, '--' . $nama . '=')) {
                return substr($arg, strlen($nama) + 3);
            }
        }

        // Flag tanpa nilai (mis. --all) dianggap string kosong, bukan null.
        return ($nilai === true) ? '' : null;
    }

    /**
     * Set mtime seluruh cache KRS ke 0 supaya TTL 30 menit dianggap kedaluwarsa
     * dan fetch benar-benar dijalankan. Kalau tidak, warm-up hanya akan
     * membaca cache (yang basi) tanpa menarik data baru.
     */
    private function rewindTime(int $tahun, string $only, int $bulan): array
    {
        // HANYA file yang benar-benar akan di-refresh oleh perintah ini.
        // Sengaja sempit: menyentuh file lain (mis. Dallap per-kecamatan yang
        // tak pernah di-refresh di sini) akan meninggalkannya bertimestamp 1970
        // bila fetch gagal — umurnya jadi menyesatkan dan tidak bisa dipulihkan.
        $files = [];
        if ($only === 'all' || $only === 'krs') {
            $files = array_merge($files, glob(WRITEPATH . 'cache/krs_monitoring_' . $tahun . '*.json') ?: []);
        }
        if ($only === 'all' || $only === 'yankb') {
            foreach (['Tabel1', 'Tabel21'] as $r) {
                $files[] = WRITEPATH . 'cache/yankb_' . $r . '_' . $tahun . '_' . str_pad((string) $bulan, 2, '0', STR_PAD_LEFT) . '.html';
            }
        }
        if ($only === 'all' || $only === 'dallap') {
            // Level kabupaten saja; definisi diambil dari service.
            foreach (BkkbnMirrorService::dallapReportDefs() as $nama => $def) {
                $suffix = $def['monthly'] ? '_' . str_pad((string) $bulan, 2, '0', STR_PAD_LEFT) : '';
                $files[] = WRITEPATH . 'cache/dallap_' . $nama . '_' . $tahun . $suffix . '.html';
            }
        }

        $asli = [];
        foreach ($files as $f) {
            if (!is_file($f)) {
                continue;   // belum ada cache: tidak ada yang perlu disentuh
            }
            $asli[$f] = (int) filemtime($f);
            @touch($f, 1);
        }

        return $asli;
    }

    /**
     * Kembalikan mtime asli untuk cache yang GAGAL di-refresh.
     *
     * rewindTime() menandai mtime=1 agar TTL 30 menit dianggap kedaluwarsa.
     * Bila fetch-nya lalu gagal, file tetap bertimestamp 1970: isinya masih
     * benar (fallback basi) tapi umurnya menyesatkan dan TTL selamanya
     * dianggap lewat. Mtime asli dikembalikan supaya umur tetap jujur.
     */
    private function pulihkanMtime(array $asli): int
    {
        clearstatcache();
        $dipulihkan = 0;
        foreach ($asli as $f => $mtime) {
            if (!is_file($f)) {
                continue;
            }
            if (filemtime($f) <= 1) {
                @touch($f, $mtime);
                $dipulihkan++;
            }
        }
        return $dipulihkan;
    }

    /** Tabel ringkasan + exit code (non-zero bila ada yang gagal, agar bisa dipakai cron/monitoring). */
    private function ringkas(array $hasil, string $only, int $tahun, int $bulan): int
    {
        CLI::newLine();
        $rows = [];
        $okTotal = 0;
        $semua = 0;
        foreach ($hasil as $h) {
            $rows[] = [$h['nama'], $h['ok'] . ' / ' . $h['total'], $h['detik'] . 's', $h['gagal'] ? implode(', ', $h['gagal']) : '—'];
            $okTotal += $h['ok'];
            $semua   += $h['total'];
        }
        CLI::table($rows, ['Bagian', 'Berhasil', 'Durasi', 'Gagal']);

        $konteks = 'tahun ' . $tahun;
        if ($only === 'yankb' || $only === 'dallap' || $only === 'all') {
            $konteks .= ', bulan ' . $bulan;
        }
        CLI::write('Ringkasan: ' . $okTotal . '/' . $semua . ' berhasil (' . $konteks . ')');

        if ($okTotal === $semua) {
            CLI::write('Cache BKKBN sudah segar.', 'green');
            return EXIT_SUCCESS;
        }

        CLI::newLine();
        CLI::write('Sebagian gagal — gateway BKKBN sedang flaky (gagal = 0 byte tanpa respons).', 'yellow');
        CLI::write('Jalankan ulang perintah yang sama; data yang sudah berhasil tidak perlu diulang.', 'light_gray');
        return EXIT_ERROR;
    }
}
