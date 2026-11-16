<?php

namespace Tests\Unit;

use App\Models\LaporanAgregatModel;
use PHPUnit\Framework\TestCase;

/**
 * Kelompok khusus (balita/remaja/lansia/PUS) dihitung dari piramida umur:
 *   balita 0–4, remaja 10–19, lansia 60+, PUS perempuan 15–49.
 */
final class KelompokUmurTest extends TestCase
{
    private function baris(): array
    {
        // Nilai khas: tiap prefix umur punya l & p berbeda agar tertukar ketahuan.
        $s = [];
        $bands = ['u0_4', 'u5_9', 'u10_14', 'u15_19', 'u20_24', 'u25_29', 'u30_34',
                  'u35_39', 'u40_44', 'u45_49', 'u50_54', 'u55_59', 'u60_64',
                  'u65_69', 'u70_74', 'u75_79', 'u80_84', 'u85_atas'];
        foreach ($bands as $i => $b) {
            $s[$b . '_l'] = 100 + $i;
            $s[$b . '_p'] = 200 + $i;
        }

        return $s;
    }

    public function testBalitaRemajaLansiaDariPiramida(): void
    {
        $k = LaporanAgregatModel::hitungKelompokUmur($this->baris());

        $this->assertSame(100, $k['balita']['l']);
        $this->assertSame(200, $k['balita']['p']);
        $this->assertSame(300, $k['balita']['total'], 'balita = u0_4 L+P');

        // 10-14 (102+202) + 15-19 (103+203)
        $this->assertSame(610, $k['remaja']['total'], 'remaja = 10-19, bukan 10-24');

        // 60+ = 12..17 (60-64 .. 85+)
        $lansia = 0;
        for ($i = 12; $i <= 17; $i++) {
            $lansia += (100 + $i) + (200 + $i);
        }
        $this->assertSame($lansia, $k['lansia']['total'], 'lansia = 60 tahun ke atas');
    }

    public function testPusHanyaPerempuan15Sampai49(): void
    {
        $k = LaporanAgregatModel::hitungKelompokUmur($this->baris());

        $this->assertSame(0, $k['pus']['l'], 'PUS tidak memakai penduduk laki-laki');

        $pus = 0;
        for ($i = 3; $i <= 9; $i++) { // u15_19 .. u45_49
            $pus += 200 + $i;
        }
        $this->assertSame($pus, $k['pus']['p']);
        $this->assertSame($pus, $k['pus']['total']);

        // Batas: u10_14 (i=2) & u50_54 (i=10) TIDAK ikut.
        $this->assertSame(1442, $k['pus']['total']);
    }

    public function testExprSqlPusHanyaKolomPerempuan(): void
    {
        $expr = LaporanAgregatModel::kelompokUmurSumExpr('jml_pus');

        $this->assertSame(
            'SUM(lp.u15_19_p + lp.u20_24_p + lp.u25_29_p + lp.u30_34_p'
            . ' + lp.u35_39_p + lp.u40_44_p + lp.u45_49_p)',
            $expr
        );
        $this->assertStringNotContainsString('_l', (string) $expr);

        $this->assertSame('SUM(lp.u0_4_l + lp.u0_4_p)', LaporanAgregatModel::kelompokUmurSumExpr('jml_balita'));
        $this->assertNull(LaporanAgregatModel::kelompokUmurSumExpr('jiwa_l'));
    }

    public function testExprBucketRasioKetergantungan(): void
    {
        $this->assertSame(
            'SUM(lp.u0_4_l + lp.u0_4_p + lp.u5_9_l + lp.u5_9_p + lp.u10_14_l + lp.u10_14_p)',
            LaporanAgregatModel::kelompokUmurSumExpr('jml_anak_0_14')
        );

        $produktif = (string) LaporanAgregatModel::kelompokUmurSumExpr('jml_produktif');
        $this->assertStringContainsString('lp.u15_19_l', $produktif);
        $this->assertStringContainsString('lp.u60_64_p', $produktif, '15-64 inklusif');
        $this->assertStringNotContainsString('u65_69', $produktif);
        $this->assertStringNotContainsString('u10_14', $produktif);

        $lansia65 = (string) LaporanAgregatModel::kelompokUmurSumExpr('jml_usia_65_plus');
        $this->assertStringContainsString('lp.u65_69_l', $lansia65);
        $this->assertStringContainsString('lp.u85_atas_p', $lansia65);
        $this->assertStringNotContainsString('u60_64', $lansia65);
    }
}
