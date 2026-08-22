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
