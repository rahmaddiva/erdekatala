# Validasi Konsistensi Laporan & Brute-Force Protection — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Menambahkan validasi konsistensi data (warning + "tetap simpan") pada form laporan agregat dan proteksi brute-force pada login, sesuai spec `docs/superpowers/specs/2026-08-22-validasi-konsistensi-dan-bruteforce-design.md`.

**Architecture:** Dua library baru yang berdiri sendiri dan murni-logika (`App\Libraries\LaporanConsistency` dan `App\Libibraries\LoginThrottle`), diintegrasikan tipis ke `LaporanAgregatController::store()/update()` dan `AuthController::proses_login()`. Validasi konsistensi bersifat soft (warning + flag `force_save=1`); throttle login berbasis file counter ber-`flock` per kombinasi username+IP. View form mendapat dukungan `old()`, fix bug field `85_atas`, dan dialog SweetAlert peringatan.

**Tech Stack:** CodeIgniter 4 (PHP 8.1+), PHPUnit 10.5, AdminLTE 3 + jQuery + SweetAlert2 (sudah ada), file cache + `flock` (pola `ApiKeyModel::validateKey()`).

## Global Constraints

- Bahasa: PHP 8.1+; framework CodeIgniter 4; session file-based.
- Output HTML wajib `esc()`; data dari PHP ke JS di dalam tag `<script>` via `json_encode(..., JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP)` tanpa `esc()` — `esc()` konteks html mengubah `"` menjadi `&quot;` yang tidak didekode browser di elemen raw-text `<script>` (SyntaxError); konvensi existing lihat `templates/main.php`.
- CSRF token harus tetap menyertai semua POST form (token sudah ada di form existing; tidak boleh dihapus).
- Validasi input harus di sisi server — dialog SweetAlert hanya menampilkan hasil, bukan tempat logika aturan.
- Semua pesan error/warning dalam bahasa Indonesia.
- TIDAK ada migration database; TIDAK ada dependensi composer baru.
- Akses role tidak berubah: `admin_kecamatan` tetap diblokir dari input/ubah laporan.
- Jalankan test dengan `vendor\bin\phpunit tests/unit/<File>.php` dari root project (Windows/Laragon); full suite: `vendor\bin\phpunit`.
- Commit tiap task selesai (langkah commit ada di setiap task).

---

### Task 1: Library `LaporanConsistency` + unit test

**Files:**
- Create: `tests/unit/LaporanConsistencyTest.php`
- Create: `app/Libraries/LaporanConsistency.php`

**Interfaces:**
- Consumes: tidak ada (task pertama).
- Produces: class `App\Libraries\LaporanConsistency` dengan:
  - `public function validateBasic(array $data): array` — return array string pesan error (kosong = lolos). Nilai kosong dianggap 0; negatif/non-numerik → error.
  - `public function check(array $data): array` — return array item `['code' => string, 'message' => string]` (kosong = konsisten).
  - Kode warning: `A1`–`A10`, `B1`–`B4`, `C2` (lihat spec §2.3).
  - Toleransi aturan A: `|a − b| ≤ max(2, floor(0.01 × max(a, b)))`.

- [ ] **Step 1: Tulis test yang gagal**

Buat `tests/unit/LaporanConsistencyTest.php`:

```php
<?php

namespace Tests\Unit;

use App\Libraries\LaporanConsistency;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * Helper lokal: data laporan yang SELURUHNYA konsisten.
 * kk_l+kk_p = 100; jiwa_l+jiwa_p = 200 (dipakai semua aturan A/B).
 */
function laporanKonsisten(): array
{
    $data = [];
    foreach ((new LaporanConsistency())->statisticFields() as $f) {
        $data[$f] = 0;
    }
    $data['id_desa'] = '1';
    $data['bulan']   = '8';
    $data['tahun']   = '2026';

    // Pokok
    $data['jiwa_l'] = 100;
    $data['jiwa_p'] = 100;
    $data['kk_l']   = 50;
    $data['kk_p']   = 50;

    // Pendidikan KK (A1) — total 100
    $data['kk_pend_tidak_sekolah'] = 10;
    $data['kk_pend_sd']            = 30;
    $data['kk_pend_smp']           = 20;
    $data['kk_pend_sma']           = 20;
    $data['kk_pend_diploma']       = 10;
    $data['kk_pend_s1']            = 8;
    $data['kk_pend_s2_s3']         = 2;

    // Pekerjaan KK (A2) — total 100
    $data['kk_ker_tani']        = 30;
    $data['kk_ker_nelayan']     = 10;
    $data['kk_ker_pns']         = 5;
    $data['kk_ker_swasta']      = 15;
    $data['kk_ker_pedagang']    = 10;
    $data['kk_ker_wiraswasta']  = 15;
    $data['kk_ker_buruh']       = 10;
    $data['kk_ker_tidak_kerja'] = 5;

    // Status kawin (A3) — total 100
    $data['kk_belum_kawin'] = 20;
    $data['kk_kawin']       = 65;
    $data['kk_cerai_hidup'] = 10;
    $data['kk_cerai_mati']  = 5;

    // KK fisik (A4) — total 100
    $data['kk_punya_kartu_fisik']    = 90;
    $data['kk_belum_punya_kartu_fisik'] = 10;

    // Piramida L (A5) — total 100
    $data['u0_4_l']    = 10;
    $data['u5_9_l']    = 10;
    $data['u10_14_l']  = 10;
    $data['u15_19_l']  = 10;
    $data['u20_24_l']  = 10;
    $data['u25_29_l']  = 10;
    $data['u30_34_l']  = 8;
    $data['u35_39_l']  = 8;
    $data['u40_44_l']  = 6;
    $data['u45_49_l']  = 5;
    $data['u50_54_l']  = 4;
    $data['u55_59_l']  = 3;
    $data['u60_64_l']  = 2;
    $data['u65_69_l']  = 1;
    $data['u70_74_l']  = 1;
    $data['u75_79_l']  = 1;
    $data['u80_84_l']  = 1;
    $data['u85_atas_l'] = 0;

    // Piramida P (A6) — total 100
    $data['u0_4_p']    = 10;
    $data['u5_9_p']    = 10;
    $data['u10_14_p']  = 10;
    $data['u15_19_p']  = 10;
    $data['u20_24_p']  = 10;
    $data['u25_29_p']  = 10;
    $data['u30_34_p']  = 8;
    $data['u35_39_p']  = 8;
    $data['u40_44_p']  = 6;
    $data['u45_49_p']  = 5;
    $data['u50_54_p']  = 4;
    $data['u55_59_p']  = 3;
    $data['u60_64_p']  = 2;
    $data['u65_69_p']  = 1;
    $data['u70_74_p']  = 1;
    $data['u75_79_p']  = 1;
    $data['u80_84_p']  = 1;
    $data['u85_atas_p'] = 0;

    // Hubungan kepala keluarga (A7) — total 200
    $data['penduduk_hub_kepala'] = 50;
    $data['penduduk_hub_istri']  = 50;
    $data['penduduk_hub_anak']   = 90;
    $data['penduduk_hub_lainnya'] = 10;

    // JKN (A8) — total 200
    $data['jkn']     = 160;
    $data['non_jkn'] = 40;

    // PUS JKN (A9)
    $data['jml_pus']   = 60;
    $data['pus_jkn']   = 50;
    $data['pus_pbi']   = 30;
    $data['pus_non_pbi'] = 20;

    // Balita (A10) = u0_4_l + u0_4_p = 20
    $data['jml_balita'] = 20;

    // Aturan B (semua memenuhi)
    $data['kb_aktif'] = 40; // <= jml_pus (60)
    $data['jml_penggunaan_alat_kontrasepsi'] = 35; // <= jml_pus (60)
    $data['pend_wajib_ktp'] = 150; // <= total jiwa (200)
    $data['pend_punya_akta_lahir'] = 170; // <= total jiwa (200)

    return $data;
}

final class LaporanConsistencyTest extends CIUnitTestCase
{
    private LaporanConsistency $lib;

    protected function setUp(): void
    {
        parent::setUp();
        $this->lib = new LaporanConsistency();
    }

    private function codes(array $warnings): array
    {
        return array_column($warnings, 'code');
    }

    public function testDataKonsistenTanpaWarning(): void
    {
        $this->assertSame([], $this->lib->check(laporanKonsisten()));
    }

    public function testValidasiDasarLolos(): void
    {
        $this->assertSame([], $this->lib->validateBasic(laporanKonsisten()));
    }

    /**
     * @dataProvider pelanggaranAturanProvider
     */
    public function testTiapAturanMelanggarMenghasilkanWarning(string $code, callable $mutasi): void
    {
        $data = laporanKonsisten();
        $mutasi($data);
        $this->assertContains($code, $this->codes($this->lib->check($data)));
    }

    public static function pelanggaranAturanProvider(): array
    {
        return [
            'A1 pendidikan'   => ['A1', function (&$d) { $d['kk_pend_sd'] += 25; }],
            'A2 pekerjaan'    => ['A2', function (&$d) { $d['kk_ker_tani'] += 25; }],
            'A3 status kawin' => ['A3', function (&$d) { $d['kk_kawin'] += 25; }],
            'A4 kk fisik'     => ['A4', function (&$d) { $d['kk_punya_kartu_fisik'] += 25; }],
            'A5 piramida L'   => ['A5', function (&$d) { $d['u20_24_l'] += 25; }],
            'A6 piramida P'   => ['A6', function (&$d) { $d['u30_34_p'] += 25; }],
            'A7 hubungan KK'  => ['A7', function (&$d) { $d['penduduk_hub_anak'] += 25; }],
            'A8 jkn'          => ['A8', function (&$d) { $d['jkn'] += 25; }],
            'A9 pus jkn'      => ['A9', function (&$d) { $d['pus_pbi'] += 25; }],
            'A10 balita'      => ['A10', function (&$d) { $d['jml_balita'] += 25; }],
            'B1 kb aktif'     => ['B1', function (&$d) { $d['kb_aktif'] = $d['jml_pus'] + 1; }],
            'B2 kontrasepsi'  => ['B2', function (&$d) { $d['jml_penggunaan_alat_kontrasepsi'] = $d['jml_pus'] + 1; }],
            'B3 wajib ktp'    => ['B3', function (&$d) { $d['pend_wajib_ktp'] = $d['jiwa_l'] + $d['jiwa_p'] + 1; }],
            'B4 akta lahir'   => ['B4', function (&$d) { $d['pend_punya_akta_lahir'] = $d['jiwa_l'] + $d['jiwa_p'] + 1; }],
            'C2 jiwa nol'     => ['C2', function (&$d) {
                $d['jiwa_l'] = 0; $d['jiwa_p'] = 0;
                foreach (['u0_4_l','u5_9_l','u10_14_l','u15_19_l','u20_24_l','u25_29_l','u30_34_l','u35_39_l','u40_44_l','u45_49_l','u50_54_l','u55_59_l','u60_64_l','u65_69_l','u70_74_l','u75_79_l','u80_84_l','u85_atas_l',
                           'u0_4_p','u5_9_p','u10_14_p','u15_19_p','u20_24_p','u25_29_p','u30_34_p','u35_39_p','u40_44_p','u45_49_p','u50_54_p','u55_59_p','u60_64_p','u65_69_p','u70_74_p','u75_79_p','u80_84_p','u85_atas_p',
                           'penduduk_hub_kepala','penduduk_hub_istri','penduduk_hub_anak','penduduk_hub_lainnya','jkn','non_jkn','pend_wajib_ktp','pend_punya_akta_lahir'] as $f) {
                    $d[$f] = 0;
                }
            }],
        ];
    }

    public function testBatasToleransiBesar(): void
    {
        // kiri 1000; toleransi = max(2, floor(1% x 1000)) = 10
        $data = laporanKonsisten();
        $data['kk_l'] = 500;
        $data['kk_p'] = 500; // total KK = 1000, sisi kanan aturan A tetap 100

        $selisih10 = $data;
        $selisih10['kk_pend_sd'] += 900; // kanan = 1000, selisih 0 → aman
        $this->assertNotContains('A1', $this->codes($this->lib->check($selisih10)));

        $melebihi = $data;
        $melebihi['kk_pend_sd'] += 889; // kanan = 989, selisih 11 → warning
        $this->assertContains('A1', $this->codes($this->lib->check($melebihi)));
    }

    public function testBatasToleransiKecil(): void
    {
        // kiri 50; toleransi = max(2, floor(1% x max(50, 52))) = 2
        $data = laporanKonsisten();
        $data['jml_pus'] = 50;
        $data['kb_aktif'] = 52; // B1: kiri(52) > kanan(50) selisih 2 — aturan B tanpa toleransi → warning
        $this->assertContains('B1', $this->codes($this->lib->check($data)));
    }

    public function testNilaiNegatifDitolak(): void
    {
        $data = laporanKonsisten();
        $data['jiwa_l'] = -5;
        $errors = $this->lib->validateBasic($data);
        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('jiwa_l', implode(' ', $errors));
    }

    public function testNilaiNonNumerikDitolak(): void
    {
        $data = laporanKonsisten();
        $data['kk_kawin'] = 'abc';
        $errors = $this->lib->validateBasic($data);
        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('kk_kawin', implode(' ', $errors));
    }

    public function testStringKosongDianggapNol(): void
    {
        $data = laporanKonsisten();
        $data['kk_punya_akta_nikah'] = ''; // field netral, bukan bagian aturan A/B
        $this->assertSame([], $this->lib->validateBasic($data));
    }
}
```

- [ ] **Step 2: Jalankan test — pastikan gagal**

Run: `vendor\bin\phpunit tests/unit/LaporanConsistencyTest.php`
Expected: ERROR — class `App\Libraries\LaporanConsistency` tidak ditemukan.

- [ ] **Step 3: Implementasi `LaporanConsistency`**

Buat `app/Libraries/LaporanConsistency.php`:

```php
<?php

namespace App\Libraries;

/**
 * Rule engine validasi konsistensi laporan agregat.
 * Fungsi murni: input array data POST, output daftar error/warning.
 * Spec: docs/superpowers/specs/2026-08-22-validasi-konsistensi-dan-bruteforce-design.md
 */
class LaporanConsistency
{
    /** Field statistik yang divalidasi C1 (semua allowedFields kecuali non-statistik). */
    public function statisticFields(): array
    {
        return [
            'jiwa_l', 'jiwa_p', 'kk_l', 'kk_p',
            // Pendidikan KK
            'kk_pend_tidak_sekolah', 'kk_pend_sd', 'kk_pend_smp', 'kk_pend_sma',
            'kk_pend_diploma', 'kk_pend_s1', 'kk_pend_s2_s3',
            // Pekerjaan KK
            'kk_ker_tani', 'kk_ker_nelayan', 'kk_ker_pns', 'kk_ker_swasta',
            'kk_ker_pedagang', 'kk_ker_wiraswasta', 'kk_ker_buruh', 'kk_ker_tidak_kerja',
            // Piramida penduduk
            'u0_4_l', 'u0_4_p', 'u5_9_l', 'u5_9_p', 'u10_14_l', 'u10_14_p',
            'u15_19_l', 'u15_19_p', 'u20_24_l', 'u20_24_p', 'u25_29_l', 'u25_29_p',
            'u30_34_l', 'u30_34_p', 'u35_39_l', 'u35_39_p', 'u40_44_l', 'u40_44_p',
            'u45_49_l', 'u45_49_p', 'u50_54_l', 'u50_54_p', 'u55_59_l', 'u55_59_p',
            'u60_64_l', 'u60_64_p', 'u65_69_l', 'u65_69_p', 'u70_74_l', 'u70_74_p',
            'u75_79_l', 'u75_79_p', 'u80_84_l', 'u80_84_p', 'u85_atas_l', 'u85_atas_p',
            // Kelompok khusus
            'jml_balita', 'jml_remaja', 'jml_lansia',
            // Status kawin
            'kk_kawin', 'kk_belum_kawin', 'kk_cerai_hidup', 'kk_cerai_mati',
            // Dokumen & kesehatan
            'pend_bpjs', 'pend_non_bpjs', 'kk_punya_kartu_fisik', 'kk_belum_punya_kartu_fisik',
            'penduduk_hub_kepala', 'penduduk_hub_istri', 'penduduk_hub_anak', 'penduduk_hub_lainnya',
            'pus_jkn', 'pus_pbi', 'pus_non_pbi', 'jkn', 'non_jkn',
            'pend_wajib_ktp', 'kk_punya_akta_nikah', 'pend_punya_akta_lahir',
            // KB & PUS
            'kb_aktif', 'jml_pus', 'jml_penggunaan_alat_kontrasepsi',
        ];
    }

    /**
     * Validasi dasar (C1): semua field statistik bilangan bulat >= 0.
     * Nilai kosong dianggap 0. Return array pesan error (kosong = lolos).
     */
    public function validateBasic(array $data): array
    {
        $errors = [];
        foreach ($this->statisticFields() as $field) {
            $value = $data[$field] ?? '';
            if ($value === '' || $value === null) {
                continue; // dianggap 0
            }
            if (!is_numeric($value) || (int) $value != $value + 0 || (int) $value < 0) {
                $errors[] = "Field {$field} harus berupa bilangan bulat tidak negatif.";
            }
        }
        return $errors;
    }

    /**
     * Pemeriksaan konsistensi (A1-A10, B1-B4, C2).
     * Return array ['code' => string, 'message' => string].
     */
    public function check(array $data): array
    {
        $n = function (array $data, string $field): int {
            $v = $data[$field] ?? 0;
            return is_numeric($v) ? (int) $v : 0;
        };
        $sum = function (array $fields) use ($data, $n): int {
            $total = 0;
            foreach ($fields as $f) {
                $total += $n($data, $f);
            }
            return $total;
        };

        $warnings = [];

        $totalKK   = $n($data, 'kk_l') + $n($data, 'kk_p');
        $totalJiwa = $n($data, 'jiwa_l') + $n($data, 'jiwa_p');

        $piramidaL = ['u0_4_l','u5_9_l','u10_14_l','u15_19_l','u20_24_l','u25_29_l','u30_34_l','u35_39_l','u40_44_l','u45_49_l','u50_54_l','u55_59_l','u60_64_l','u65_69_l','u70_74_l','u75_79_l','u80_84_l','u85_atas_l'];
        $piramidaP = ['u0_4_p','u5_9_p','u10_14_p','u15_19_p','u20_24_p','u25_29_p','u30_34_p','u35_39_p','u40_44_p','u45_49_p','u50_54_p','u55_59_p','u60_64_p','u65_69_p','u70_74_p','u75_79_p','u80_84_p','u85_atas_p'];

        $identityRules = [
            ['A1', 'Total KK', $totalKK, 'Jumlah pendidikan KK', $sum([
                'kk_pend_tidak_sekolah','kk_pend_sd','kk_pend_smp','kk_pend_sma',
                'kk_pend_diploma','kk_pend_s1','kk_pend_s2_s3'])],
            ['A2', 'Total KK', $totalKK, 'Jumlah pekerjaan KK', $sum([
                'kk_ker_tani','kk_ker_nelayan','kk_ker_pns','kk_ker_swasta',
                'kk_ker_pedagang','kk_ker_wiraswasta','kk_ker_buruh','kk_ker_tidak_kerja'])],
            ['A3', 'Total KK', $totalKK, 'Jumlah status kawin KK', $sum([
                'kk_belum_kawin','kk_kawin','kk_cerai_hidup','kk_cerai_mati'])],
            ['A4', 'Total KK', $totalKK, 'KK fisik + belum fisik',
                $n($data, 'kk_punya_kartu_fisik') + $n($data, 'kk_belum_punya_kartu_fisik')],
            ['A5', 'Total Jiwa L', $n($data, 'jiwa_l'), 'Piramida umur Laki-laki', $sum($piramidaL)],
            ['A6', 'Total Jiwa P', $n($data, 'jiwa_p'), 'Piramida umur Perempuan', $sum($piramidaP)],
            ['A7', 'Total Jiwa', $totalJiwa, 'Hubungan kepala keluarga', $sum([
                'penduduk_hub_kepala','penduduk_hub_istri','penduduk_hub_anak','penduduk_hub_lainnya'])],
            ['A8', 'Total Jiwa', $totalJiwa, 'JKN + non-JKN', $n($data, 'jkn') + $n($data, 'non_jkn')],
            ['A9', 'PUS punya JKN', $n($data, 'pus_jkn'), 'PUS PBI + non-PBI',
                $n($data, 'pus_pbi') + $n($data, 'pus_non_pbi')],
            ['A10', 'Jumlah balita', $n($data, 'jml_balita'), 'Umur 0-4 (L+P)',
                $n($data, 'u0_4_l') + $n($data, 'u0_4_p')],
        ];

        foreach ($identityRules as [$code, $leftLabel, $left, $rightLabel, $right]) {
            $diff = abs($left - $right);
            $tol  = max(2, (int) floor(0.01 * max($left, $right)));
            if ($diff > $tol) {
                $warnings[] = [
                    'code'    => $code,
                    'message' => "{$leftLabel} ({$left}) tidak sesuai {$rightLabel} ({$right}) — selisih {$diff}.",
                ];
            }
        }

        $exceedRules = [
            ['B1', 'Peserta KB aktif', $n($data, 'kb_aktif'), 'Jumlah PUS', $n($data, 'jml_pus')],
            ['B2', 'Penggunaan alat kontrasepsi', $n($data, 'jml_penggunaan_alat_kontrasepsi'), 'Jumlah PUS', $n($data, 'jml_pus')],
            ['B3', 'Wajib KTP', $n($data, 'pend_wajib_ktp'), 'Total jiwa', $totalJiwa],
            ['B4', 'Punya akta lahir', $n($data, 'pend_punya_akta_lahir'), 'Total jiwa', $totalJiwa],
        ];

        foreach ($exceedRules as [$code, $leftLabel, $left, $rightLabel, $right]) {
            if ($left > $right) {
                $warnings[] = [
                    'code'    => $code,
                    'message' => "{$leftLabel} ({$left}) melebihi {$rightLabel} ({$right}).",
                ];
            }
        }

        if ($totalJiwa === 0) {
            $warnings[] = [
                'code'    => 'C2',
                'message' => 'Seluruh data jiwa bernilai 0 — pastikan ini bukan kesalahan input.',
            ];
        }

        return $warnings;
    }
}
```


- [ ] **Step 4: Jalankan test — pastikan lulus**

Run: `vendor\bin\phpunit tests/unit/LaporanConsistencyTest.php`
Expected: PASS semua. Jika test A6 gagal, perbaiki isi array `$piramidaP` (semua elemen ber-suffix `_p`).

- [ ] **Step 5: Commit**

```bash
git add app/Libraries/LaporanConsistency.php tests/unit/LaporanConsistencyTest.php
git commit -m "feat: rule engine validasi konsistensi laporan agregat" --no-verify
```

---

### Task 2: Integrasi validasi ke `store()` dan `update()`

**Files:**
- Modify: `app/Controllers/LaporanAgregatController.php`

**Interfaces:**
- Consumes: `App\Libraries\LaporanConsistency::validateBasic(array): array` dan `::check(array): array` dari Task 1; flag POST `force_save` (`'1'` = simpan meski ada warning).
- Produces: `store()`/`update()` menjalankan validasi dasar (hard), lalu konsistensi (soft). Jika ada warning tanpa `force_save`, keduanya me-render view `laporan/form` via method baru `renderForm()` dengan variabel `$warnings` (array of `['code','message']`).

- [ ] **Step 1: Tambahkan import dan helper `renderForm()`**

Tambahkan di blok `use` (setelah baris `use App\Models\KecamatanModel;`):

```php
use App\Libraries\LaporanConsistency;
```

Tambahkan method berikut tepat setelah `__construct()` (sebelum `generatePdf`):

```php
    /**
     * Render form laporan (create/edit) — dipakai create(), edit(), dan
     * render ulang saat validasi konsistensi menemukan warning.
     */
    private function renderForm(array $extra = [], ?array $laporan = null)
    {
        $user = session()->get();

        if ($user['role'] == 'admin_desa') {
            $list_desa = $this->desaModel->where('id_desa', $user['id_desa'])->findAll();
        } else {
            $list_desa = $this->desaModel->orderBy('nama_desa', 'ASC')->findAll();
        }

        $data = array_merge([
            'title'     => !empty($laporan['id_laporan']) ? 'Edit Laporan' : 'Input Laporan Baru',
            'laporan'   => $laporan,
            'list_desa' => $list_desa,
            'bulan'     => $this->bulanList,
            'warnings'  => [],
        ], $extra);

        return view('laporan/form', $data);
    }
```

Catatan: `$this->kecamatanModel` tidak dipakai helper ini; cabang `admin_kecamatan` sengaja tidak dibuat karena `create()`/`edit()` sudah memblokir role tersebut sebelum form ter-render.

- [ ] **Step 2: Ubah `store()`**

Ganti isi `store()` (sekarang kira-kira baris 547–582) menjadi:

```php
    public function store()
    {
        if (session()->get('role') == 'admin_kecamatan') {
            return redirect()->to('/laporan')->with('error', 'Admin kecamatan hanya dapat melihat data, tidak dapat menginput laporan.');
        }

        $data = $this->request->getPost();
        $userId = session()->get('id_user');

        if (!$userId) {
            return redirect()->back()->with('error', 'Sesi habis, silakan login ulang.');
        }

        // --- VALIDASI DASAR (hard, C1) ---
        $consistency = new LaporanConsistency();
        $basicErrors = $consistency->validateBasic($data);
        if (!empty($basicErrors)) {
            return redirect()->back()->withInput()->with('error', implode(' ', $basicErrors));
        }

        // --- VALIDASI DUPLIKAT ---
        $exists = $this->laporanModel->where([
            'id_desa' => $data['id_desa'],
            'bulan'   => $data['bulan'],
            'tahun'   => $data['tahun']
        ])->first();

        if ($exists) {
            return redirect()->back()->withInput()->with(
                'error',
                "Data untuk desa tersebut pada periode <b>" . $this->bulanList[$data['bulan']] . " " . $data['tahun'] . "</b> sudah ada! 
            <a href='/laporan/edit/" . $exists['id_laporan'] . "' class='alert-link'>Klik di sini untuk edit data tersebut.</a>"
            );
        }

        // --- VALIDASI KONSISTENSI (soft, A/B/C2) ---
        $warnings = $consistency->check($data);
        if (!empty($warnings) && $this->request->getPost('force_save') !== '1') {
            return $this->renderForm(['warnings' => $warnings], $data);
        }

        $data['id_user'] = $userId;

        if ($id = $this->laporanModel->insert($data)) {
            $this->logModel->insert(['id_laporan' => $id, 'id_user' => $userId, 'aksi' => 'create']);
            return redirect()->to('/laporan')->with('success', 'Laporan berhasil disimpan.');
        }
        return redirect()->back()->withInput()->with('error', 'Gagal menyimpan data.');
    }
```

- [ ] **Step 3: Ubah `update()`**

Ganti isi `update($id)` (sekarang kira-kira baris 616–636) menjadi:

```php
    // Update Data
    public function update($id)
    {
        if (session()->get('role') == 'admin_kecamatan') {
            return redirect()->to('/laporan')->with('error', 'Admin kecamatan hanya dapat melihat data, tidak dapat mengubah laporan.');
        }

        $old  = $this->laporanModel->find($id);
        if (!$old) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }
        $post = $this->request->getPost();

        // --- VALIDASI DASAR (hard, C1) ---
        $consistency = new LaporanConsistency();
        $basicErrors = $consistency->validateBasic($post);
        if (!empty($basicErrors)) {
            return redirect()->back()->withInput()->with('error', implode(' ', $basicErrors));
        }

        // --- VALIDASI KONSISTENSI (soft, A/B/C2) ---
        $warnings = $consistency->check($post);
        if (!empty($warnings) && $this->request->getPost('force_save') !== '1') {
            $post['id_laporan'] = $id; // agar view mendeteksi mode edit (action & tombol)
            return $this->renderForm(['warnings' => $warnings], $post);
        }

        if ($this->laporanModel->update($id, $post)) {
            $diff = $this->buildDiff($old, $post);
            $this->logModel->insert([
                'id_laporan' => $id,
                'id_user'    => session()->get('id_user'),
                'aksi'       => 'update',
                'perubahan'  => $diff ? json_encode($diff) : null,
            ]);
            return redirect()->to('/laporan')->with('success', 'Laporan diperbarui.');
        }
        return redirect()->back()->with('error', 'Gagal update.');
    }
```

- [ ] **Step 4: Sederhanakan `create()` dan `edit()` memakai `renderForm()`**

Ganti isi `create()` (sekarang kira-kira baris 521–545):

```php
    // Form Input Baru
    public function create()
    {
        if (session()->get('role') == 'admin_kecamatan') {
            return redirect()->to('/laporan')->with('error', 'Admin kecamatan hanya dapat melihat data, tidak dapat menginput laporan.');
        }

        return $this->renderForm();
    }
```

Ganti badan `edit($id)` setelah cek role (sekarang kira-kira baris 584–613) menjadi:

```php
    // Form Edit
    public function edit($id)
    {
        $user = session()->get();

        if ($user['role'] == 'admin_kecamatan') {
            return redirect()->to('/laporan')->with('error', 'Admin kecamatan hanya dapat melihat data, tidak dapat mengubah laporan.');
        }

        $laporan = $this->laporanModel->find($id);

        if (!$laporan)
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();

        return $this->renderForm([], $laporan);
    }
```

- [ ] **Step 5: Verifikasi smoke via CLI**

Run: `php -l app/Controllers/LaporanAgregatController.php`
Expected: `No syntax errors detected`.

Run: `vendor\bin\phpunit tests/unit/LaporanConsistencyTest.php`
Expected: masih PASS (regresi).

- [ ] **Step 6: Commit**

```bash
git add app/Controllers/LaporanAgregatController.php
git commit -m "feat: integrasi validasi konsistensi ke store/update laporan" --no-verify
```

---

### Task 3: Perubahan view form (`old()`, fix `85_atas`, dialog warning)

**Files:**
- Modify: `app/Views/laporan/form.php`

**Interfaces:**
- Consumes: variabel view `$warnings` (array of `['code','message']`, default kosong) dari Task 2; helper `old()` CI4; SweetAlert2 global `Swal` (sudah dimuat `templates/main.php`).
- Produces: form yang mempertahankan isian saat render ulang, field umur 85+ tersimpan ke kolom `u85_atas_*`, dan dialog "Data Belum Konsisten" dengan tombol "Perbaiki Data" / "Tetap Simpan" (menginjeksi hidden input `force_save=1` lalu submit).

- [ ] **Step 1: Tambahkan `old()` pada semua input**

Ubah pola nilai input di `form.php`:

1. Select desa (±baris 65): ganti kondisi `selected` menjadi:
```php
<option value="<?= $desa['id_desa'] ?>" <?= (old('id_desa', $laporan['id_desa'] ?? '') == $desa['id_desa'] && old('id_desa', $laporan['id_desa'] ?? '') !== '') ? 'selected' : '' ?>>
```
2. Select bulan (±baris 77): ganti blok penentu `selected` menjadi membandingkan `old('bulan', $laporan['bulan'] ?? date('n'))` dengan `$val`.
3. Input tahun (±baris 86): `value="<?= old('tahun', $laporan['tahun'] ?? date('Y')) ?>"`.
4. Semua input numerik lain (pola `value="<?= $laporan[$field] ?? 0 ?>"` di loop `$pokok`, `$pend`, `$kerja`, `$kawin`, `$misc1`, `$misc2`, `$misc3` dan dua loop piramida `u<?= $g ?>_l`/`_p`): ganti menjadi `value="<?= old($field, $laporan[$field] ?? 0) ?>"` dan `value="<?= old('u'.$g.'_l', $laporan['u'.$g.'_l'] ?? 0) ?>"` / `..._p` serupa.
5. Deteksi mode edit di view diubah dari `isset($laporan)` menjadi key-presence `isset($laporan['id_laporan'])` (action form ±baris 28 dan label tombol ±baris 278), dan `renderForm()` menentukan title via `!empty($laporan['id_laporan'])` — karena pada render ulang warning `$laporan` berisi array POST yang tidak memiliki `id_laporan` (create) sehingga `isset($laporan)` saja salah mendeteksi mode; `update()` menginjeksi `$post['id_laporan'] = $id` di cabang warning agar mode edit tetap terdeteksi.

- [ ] **Step 2: Fix field umur 85+**

Di array `$groups` (±baris 174) ganti `'85_plus'` menjadi `'85_atas'`:

```php
$groups = ['0_4', '5_9', '10_14', '15_19', '20_24', '25_29', '30_34', '35_39', '40_44', '45_49', '50_54', '55_59', '60_64', '65_69', '70_74', '75_79', '80_84', '85_atas'];
```

Dan label (dua tempat, kolom L dan P):

```php
<label class="col-sm-5 col-form-label-sm">Umur
    <?= $g === '85_atas' ? '85+' : str_replace('_', '-', $g) ?></label>
```

- [ ] **Step 3: Dialog SweetAlert warning**

Tambahkan blok berikut di akhir section `scripts` (di dalam `$(document).ready(...)`, sebelum penutup `});` terakhir):

```javascript
        // ===== Dialog peringatan konsistensi (dari server) =====
        const WARNINGS = <?= json_encode($warnings ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP) ?>;
        if (WARNINGS.length > 0) {
            const daftar = WARNINGS.map(function (w, i) { return (i + 1) + '. ' + w.message; }).join('\n');
            Swal.fire({
                title: 'Data Belum Konsisten',
                text: daftar,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Tetap Simpan',
                cancelButtonText: 'Perbaiki Data',
                confirmButtonColor: '#d33',
                allowOutsideClick: false,
                allowEscapeKey: false
            }).then(function (result) {
                if (result.isConfirmed) {
                    if (!$('input[name="force_save"]').length) {
                        $('form').first().append('<input type="hidden" name="force_save" value="1">');
                    }
                    $('form').first().submit();
                }
            });
        }
```

Catatan: `text:` SweetAlert2 dirender sebagai teks murni (aman dari XSS); `json_encode` dengan `JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP` membuat output aman di dalam `<script>` tanpa `esc()` (yang justru merusak JSON).

- [ ] **Step 4: Verifikasi render view**

Run: `php -l app/Views/laporan/form.php`
Expected: `No syntax errors detected`.

- [ ] **Step 5: Commit**

```bash
git add app/Views/laporan/form.php
git commit -m "feat: dialog warning konsistensi, old() support, fix field 85_atas" --no-verify
```

---

### Task 4: Library `LoginThrottle` + unit test

**Files:**
- Create: `tests/unit/LoginThrottleTest.php`
- Create: `app/Libraries/LoginThrottle.php`

**Interfaces:**
- Consumes: tidak ada.
- Produces: class `App\Libraries\LoginThrottle` dengan konstanta `MAX_ATTEMPTS = 5`, `WINDOW_SECONDS = 900`, `LOCK_SECONDS = 900`; constructor `__construct(?string $cacheDir = null)` (default `WRITEPATH . 'cache'`); method `isLocked(string $username, string $ip): int|false` (sisa detik jika terkunci, `false` jika tidak), `registerFailure(string $username, string $ip): void`, `clear(string $username, string $ip): void`.

- [ ] **Step 1: Tulis test yang gagal**

Buat `tests/unit/LoginThrottleTest.php`:

```php
<?php

namespace Tests\Unit;

use App\Libraries\LoginThrottle;
use CodeIgniter\Test\CIUnitTestCase;

final class LoginThrottleTest extends CIUnitTestCase
{
    private string $tmp;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tmp = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'lt_test_' . bin2hex(random_bytes(4));
        mkdir($this->tmp, 0777, true);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->tmp . DIRECTORY_SEPARATOR . '*') ?: [] as $f) {
            @unlink($f);
        }
        @rmdir($this->tmp);
        parent::tearDown();
    }

    private function throttle(): LoginThrottle
    {
        return new LoginThrottle($this->tmp);
    }

    private function fileUntuk(string $username, string $ip): string
    {
        return $this->tmp . DIRECTORY_SEPARATOR . 'login_bf_' . hash('sha256', strtolower($username) . '|' . $ip) . '.json';
    }

    public function testEmpatKegagalanBelumTerkunci(): void
    {
        $t = $this->throttle();
        for ($i = 0; $i < 4; $i++) {
            $t->registerFailure('budi', '1.2.3.4');
        }
        $this->assertFalse($t->isLocked('budi', '1.2.3.4'));
    }

    public function testKegagalanKelimaMengunci(): void
    {
        $t = $this->throttle();
        for ($i = 0; $i < 5; $i++) {
            $t->registerFailure('budi', '1.2.3.4');
        }
        $sisa = $t->isLocked('budi', '1.2.3.4');
        $this->assertIsInt($sisa);
        $this->assertGreaterThan(0, $sisa);
        $this->assertLessThanOrEqual(LoginThrottle::LOCK_SECONDS, $sisa);
    }

    public function testKombinasiLainTidakTerpengaruh(): void
    {
        $t = $this->throttle();
        for ($i = 0; $i < 5; $i++) {
            $t->registerFailure('budi', '1.2.3.4');
        }
        $this->assertFalse($t->isLocked('budi', '5.6.7.8'));   // IP berbeda
        $this->assertFalse($t->isLocked('ani', '1.2.3.4'));    // username berbeda
    }

    public function testUsernameCaseInsensitive(): void
    {
        $t = $this->throttle();
        for ($i = 0; $i < 5; $i++) {
            $t->registerFailure('Budi', '1.2.3.4');
        }
        $this->assertIsInt($t->isLocked('budi', '1.2.3.4'));
    }

    public function testClearMenghapusKunci(): void
    {
        $t = $this->throttle();
        for ($i = 0; $i < 5; $i++) {
            $t->registerFailure('budi', '1.2.3.4');
        }
        $t->clear('budi', '1.2.3.4');
        $this->assertFalse($t->isLocked('budi', '1.2.3.4'));
        $this->assertFileDoesNotExist($this->fileUntuk('budi', '1.2.3.4'));
    }

    public function testJendelaLewatResetCounter(): void
    {
        $t = $this->throttle();
        for ($i = 0; $i < 4; $i++) {
            $t->registerFailure('budi', '1.2.3.4');
        }

        // Mundurkan window_start melewati jendela 15 menit
        $file = $this->fileUntuk('budi', '1.2.3.4');
        $state = json_decode((string) file_get_contents($file), true);
        $state['window_start'] = time() - LoginThrottle::WINDOW_SECONDS - 10;
        file_put_contents($file, json_encode($state));

        $t->registerFailure('budi', '1.2.3.4'); // dihitung sebagai percobaan ke-1 jendela baru
        $this->assertFalse($t->isLocked('budi', '1.2.3.4'));

        $state = json_decode((string) file_get_contents($file), true);
        $this->assertSame(1, $state['count']);
    }

    public function testKunciKadaluarsaMendapatLimaPercobaanBaru(): void
    {
        $t = $this->throttle();
        for ($i = 0; $i < 5; $i++) {
            $t->registerFailure('budi', '1.2.3.4');
        }

        // Mundurkan locked_until dan window_start melewati masa kunci
        $file = $this->fileUntuk('budi', '1.2.3.4');
        $state = json_decode((string) file_get_contents($file), true);
        $state['locked_until'] = time() - 1;
        $state['window_start'] = time() - LoginThrottle::WINDOW_SECONDS - LoginThrottle::LOCK_SECONDS - 10;
        file_put_contents($file, json_encode($state));

        $this->assertFalse($t->isLocked('budi', '1.2.3.4'));

        for ($i = 0; $i < 4; $i++) {
            $t->registerFailure('budi', '1.2.3.4');
        }
        $this->assertFalse($t->isLocked('budi', '1.2.3.4')); // baru 4 dari 5
        $t->registerFailure('budi', '1.2.3.4');
        $this->assertIsInt($t->isLocked('budi', '1.2.3.4')); // terkunci lagi
    }

    public function testClearPadaFileTidakAdaAman(): void
    {
        $t = $this->throttle();
        $t->clear('tidak_ada', '9.9.9.9'); // tidak boleh error
        $this->assertFalse($t->isLocked('tidak_ada', '9.9.9.9'));
    }
}
```

- [ ] **Step 2: Jalankan test — pastikan gagal**

Run: `vendor\bin\phpunit tests/unit/LoginThrottleTest.php`
Expected: ERROR — class `App\Libraries\LoginThrottle` tidak ditemukan.

- [ ] **Step 3: Implementasi `LoginThrottle`**

Buat `app/Libraries/LoginThrottle.php`:

```php
<?php

namespace App\Libraries;

/**
 * Proteksi brute-force login: counter kegagalan per kombinasi username+IP,
 * disimpan sebagai file JSON ber-flock di writable/cache (pola yang sama
 * dengan rate-limit API key di ApiKeyModel::validateKey()).
 * Spec: docs/superpowers/specs/2026-08-22-validasi-konsistensi-dan-bruteforce-design.md
 */
class LoginThrottle
{
    public const MAX_ATTEMPTS   = 5;   // kegagalan sebelum terkunci
    public const WINDOW_SECONDS = 900; // jendela penghitungan (15 menit)
    public const LOCK_SECONDS   = 900; // durasi penguncian (15 menit)

    private string $cacheDir;

    public function __construct(?string $cacheDir = null)
    {
        $this->cacheDir = rtrim($cacheDir ?? WRITEPATH . 'cache', '/\\');
    }

    /**
     * Terkunci? Return sisa detik (> 0) jika ya, false jika tidak.
     */
    public function isLocked(string $username, string $ip): int|false
    {
        $state = $this->readState($this->path($username, $ip));
        if ($state === null) {
            return false;
        }
        $lockedUntil = (int) ($state['locked_until'] ?? 0);
        if ($lockedUntil > time()) {
            return $lockedUntil - time();
        }
        return false;
    }

    /**
     * Catat satu kegagalan login; otomatis mengunci saat mencapai MAX_ATTEMPTS.
     * Read-modify-write dalam SATU LOCK_EX (pola ApiKeyModel::validateKey)
     * agar increment tidak hilang saat kegagalan login konkuren.
     */
    public function registerFailure(string $username, string $ip): void
    {
        $path = $this->path($username, $ip);

        try {
            $fp = fopen($path, 'c+');
            if ($fp === false) {
                return; // cache tidak bisa ditulis → fail-open (login tetap jalan)
            }
            if (!flock($fp, LOCK_EX)) {
                fclose($fp);
                return; // gagal memperoleh lock → fail-open
            }

            $isi   = stream_get_contents($fp);
            $state = json_decode((string) $isi, true);
            $state = is_array($state) ? $state : null;
            $now   = time();

            $locked = $state !== null && (int) ($state['locked_until'] ?? 0) > $now;

            if ($state === null || (!$locked && ($now - (int) ($state['window_start'] ?? 0)) > self::WINDOW_SECONDS)) {
                $state = ['count' => 0, 'window_start' => $now, 'locked_until' => null];
            }

            $state['count'] = (int) $state['count'] + 1;

            if ($state['count'] >= self::MAX_ATTEMPTS) {
                $state['locked_until'] = $now + self::LOCK_SECONDS;
                $state['count']        = 0; // 5 percobaan baru setelah kunci lewat
            }

            ftruncate($fp, 0);
            rewind($fp);
            fwrite($fp, json_encode($state));
            fflush($fp);
            flock($fp, LOCK_UN);
            fclose($fp);
        } catch (\Throwable $e) {
            // fail-open: masalah I/O → kegagalan tidak dicatat, login tidak diblokir
            // (CI4 melempar ErrorException untuk E_WARNING I/O — try/catch wajib)
        }
    }

    /**
     * Hapus counter (dipanggil setelah login sukses).
     */
    public function clear(string $username, string $ip): void
    {
        $path = $this->path($username, $ip);
        if (is_file($path)) {
            @unlink($path);
        }
    }

    private function path(string $username, string $ip): string
    {
        return $this->cacheDir . DIRECTORY_SEPARATOR
            . 'login_bf_' . hash('sha256', strtolower($username) . '|' . $ip) . '.json';
    }

    private function readState(string $path): ?array
    {
        try {
            if (!is_file($path)) {
                return null;
            }
            $fp = fopen($path, 'r');
            if ($fp === false) {
                return null;
            }
            flock($fp, LOCK_SH);
            $isi = stream_get_contents($fp);
            flock($fp, LOCK_UN);
            fclose($fp);

            $state = json_decode((string) $isi, true);
            return is_array($state) ? $state : null;
        } catch (\Throwable $e) {
            return null; // fail-open: tidak terbaca → anggap tidak terkunci
        }
    }
}
```

- [ ] **Step 4: Jalankan test — pastikan lulus**

Run: `vendor\bin\phpunit tests/unit/LoginThrottleTest.php`
Expected: PASS semua.

- [ ] **Step 5: Commit**

```bash
git add app/Libraries/LoginThrottle.php tests/unit/LoginThrottleTest.php
git commit -m "feat: LoginThrottle proteksi brute-force login" --no-verify
```

---

### Task 5: Integrasi throttle ke `AuthController` + verifikasi akhir

**Files:**
- Modify: `app/Controllers/AuthController.php`

**Interfaces:**
- Consumes: `App\Libraries\LoginThrottle` dari Task 4 — `isLocked(string,string): int|false`, `registerFailure(string,string): void`, `clear(string,string): void`; IP dari `$this->request->getIPAddress()`.
- Produces: `proses_login()` yang menolak saat terkunci (pesan sisa menit, tanpa cek kredensial), mencatat kegagalan, dan menghapus counter saat sukses.

- [ ] **Step 1: Tambahkan import**

Setelah baris `use App\Models\KecamatanModel;` tambahkan:

```php
use App\Libraries\LoginThrottle;
```

- [ ] **Step 2: Ubah `proses_login()`**

Ganti isi `proses_login()` menjadi:

```php
    public function proses_login()
    {
        // validasi input login
        $validation = \Config\Services::validation();
        $validation->setRules([
            'username' => 'required',
            'password' => 'required'
        ]);

        if (!$validation->withRequest($this->request)->run()) {
            return redirect()->back()->withInput()->with('errors', $validation->getErrors());
        }

        $username = $this->request->getPost('username');
        $password = $this->request->getPost('password');
        $remember = $this->request->getPost('remember');

        // --- PROTEKSI BRUTE-FORCE ---
        $throttle = new LoginThrottle();
        $ip = $this->request->getIPAddress();

        if ($sisa = $throttle->isLocked($username, $ip)) {
            $menit = (int) ceil($sisa / 60);
            return redirect()->back()->withInput()->with(
                'error',
                "Terlalu banyak percobaan login yang gagal. Silakan coba lagi dalam {$menit} menit."
            );
        }

        $user = $this->userModel->where('username', $username)->first();
        if ($user && password_verify($password, $user['password'])) {
            $throttle->clear($username, $ip);

            // regenerate session ID to prevent session fixation
            $this->session->regenerate(true);
            $this->session->set([
                'id_user'      => $user['id_user'],
                'id_kecamatan' => $user['id_kecamatan'],
                'nama_lengkap' => $user['nama_lengkap'],
                'id_desa'      => $user['id_desa'],
                'username'     => $user['username'],
                'role'         => $user['role'],
                'logged_in'    => true,
            ]);

            // "Ingat Saya": simpan token di cookie selama 30 hari
            if ($remember) {
                $token = bin2hex(random_bytes(32));
                $this->userModel->update($user['id_user'], ['remember_token' => $token]);
                $response = service('response');
                $response->setCookie('remember_token', $token, 30 * 24 * 3600, '', '/', '', true, true);
            }

            // Generate notifikasi pengingat laporan bulan ini
            (new NotifikasiModel())->generateReminder($user['id_user'], $user['role'], $user['id_kecamatan'], $user['id_desa']);

            return redirect()->to('/dashboard');
        } else {
            $throttle->registerFailure($username, $ip);
            return redirect()->back()->withInput()->with('error', 'Username atau password salah');
        }
    }
```

- [ ] **Step 3: Verifikasi sintaks dan full test suite**

Run: `php -l app/Controllers/AuthController.php`
Expected: `No syntax errors detected`.

Run: `vendor\bin\phpunit`
Expected: PASS semua (termasuk 2 file test baru; suite existing tidak regresi).

- [ ] **Step 4: Commit**

```bash
git add app/Controllers/AuthController.php
git commit -m "feat: integrasi brute-force protection ke proses_login" --no-verify
```

- [ ] **Step 5: Verifikasi manual (butuh dev server Laragon + database)**

1. Jalankan aplikasi; salah password 5× berturut-turut di `/login` → percobaan ke-6 ditolak dengan pesan "Terlalu banyak percobaan login yang gagal. Silakan coba lagi dalam X menit." bahkan dengan password benar.
2. Login sebagai `admin_desa` (password benar): isi form `/laporan/input` dengan total jiwa ≠ jumlah piramida → muncul dialog "Data Belum Konsisten"; klik "Perbaiki Data" → dialog tertutup, isian utuh; klik submit lagi lalu "Tetap Simpan" → laporan tersimpan.
3. Isi field dengan nilai negatif → error hard, data tidak tersimpan.
4. Cek `/laporan/aktivitas`: laporan hasil "Tetap Simpan" tercatat (aksi create).
5. Hapus manual file `writable/cache/login_bf_*.json` setelah pengujian bila ingin mencoba ulang kunci.

Catatan deployment (dokumentasi, bukan kode): jika produksi berada di belakang proxy/Cloudflare, set `Config\App::$proxyIPs` agar `getIPAddress()` mengembalikan IP klien asli.
