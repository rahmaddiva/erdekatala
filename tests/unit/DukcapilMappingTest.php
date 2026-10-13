<?php

namespace Tests\Unit;

use App\Libraries\DukcapilMapping;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * Unit test fungsi MURNI pemetaan Excel Dukcapil -> profil_dukcapil.
 * Tidak menyentuh database & tidak membaca file — semua fixture dibuat di sini.
 *
 * Kasus yang diuji (sesuai rencana
 * docs/superpowers/plans/2026-10-09-import-dukcapil-sm1-2026.md §6 T6):
 *   (a) Sigma piramida == jiwa_l + jiwa_p
 *   (b) Sigma kk_pend_* == kk_total
 *   (c) Sigma kk_ker_*  == kk_total
 *   (d) label `65-99` -> u65_69
 *   (e) `75+` terpecah ke u75_79 / u80_84 / u85_atas
 *   (f) kk_belum_punya_kartu_fisik == kk_total - kartu_keluarga
 *   (g) jml_balita / jml_remaja / jml_lansia dari piramida
 *
 * Jalankan: vendor\bin\phpunit tests/unit/DukcapilMappingTest.php
 */
final class DukcapilMappingTest extends CIUnitTestCase
{
    private DukcapilMapping $map;

    protected function setUp(): void
    {
        parent::setUp();
        $this->map = new DukcapilMapping();
    }

    // -----------------------------------------------------------------------
    // Helper fixture
    // -----------------------------------------------------------------------

    /**
     * Satu baris Excel sintetis. Angka sengaja "tidak rapi" (ada ganjil, ada 0)
     * supaya benar-benar menguji koreksi pembulatan.
     *
     * kk_total = 100, jiwa_total = 280, jiwa_l = 140, jiwa_p = 140 (rasio 0.5).
     * Sigma usia = Sigma pendidikan = Sigma pekerjaan = 280 (seperti file asli).
     */
    private function barisSintetis(): array
    {
        $usia = [
            '00-04' => 20, '05-09' => 25, '10-14' => 27, '15-19' => 21,
            '20-24' => 18, '25-29' => 17, '30-34' => 16, '35-39' => 15,
            '40-44' => 14, '45-49' => 13, '50-54' => 12, '55-59' => 11,
            '60-64' => 10, '65-99' => 9,  '70-74' => 7,  '75+'   => 45,
        ];

        $pendidikan = [
            'tidak_blm_sekolah' => 60, 'belum_tamat_sd' => 30, 'tamat_sd' => 70,
            'sltp' => 40, 'slta' => 35, 'diploma_1_2' => 3, 'akademi_d3' => 5,
            's1_d4' => 30, 's2' => 4, 's3' => 3,
        ];

        $pekerjaan = [
            'belum_tidak_bekerja' => 70, 'aparatur_pejabat_negara' => 10,
            'tenaga_pengajar' => 8, 'wiraswasta' => 50, 'pertanian_peternakan' => 60,
            'nelayan' => 5, 'agama_kepercayaan' => 2, 'pelajar_mahasiswa' => 30,
            'tenaga_kesehatan' => 3, 'pensiunan' => 4, 'lainnya' => 38,
        ];

        $dokumen = [
            'ktp_el_rekam' => 200, 'kartu_keluarga' => 93, 'akta_lahir_0_17' => 60,
            'kia_0_16' => 40, 'akta_perkawinan' => 70, 'akta_perceraian' => 3,
        ];

        return [
            'kode' => '63.01.01.2001', 'nama' => 'DESA CONTOH',
            'kk_total' => 100, 'kk_l' => 62, 'kk_p' => 38,
            'jiwa_total' => 280, 'jiwa_l' => 140, 'jiwa_p' => 140,
            'usia' => $usia, 'pendidikan' => $pendidikan,
            'pekerjaan' => $pekerjaan, 'dokumen' => $dokumen,
        ];
    }

    /** Total semua kolom piramida (36 kolom). */
    private function totalPiramida(array $r): int
    {
        $t = 0;
        foreach (DukcapilMapping::KOLOM_USIA as $b) {
            $t += (int) $r[$b . '_l'] + (int) $r[$b . '_p'];
        }

        return $t;
    }

    // -----------------------------------------------------------------------
    // (a) Sigma piramida == jiwa_l + jiwa_p
    // -----------------------------------------------------------------------

    public function testTotalPiramidaSamaDenganJiwa(): void
    {
        $r = $this->map->petakSatuDesa($this->barisSintetis());

        $this->assertSame(
            (int) $r['jiwa_l'] + (int) $r['jiwa_p'],
            $this->totalPiramida($r),
            'Sigma 36 kolom piramida harus == jiwa_l + jiwa_p'
        );
    }

    public function testPiramidaTerpecahBenarPerGender(): void
    {
        // Rasio gender tidak simetris (L=180, P=120 -> r = 0.6) untuk memastikan
        // pemecahan L/P mengikuti rasio desa, bukan dibagi dua rata.
        $b = $this->barisSintetis();
        $b['jiwa_l'] = 168;
        $b['jiwa_p'] = 112;
        $b['jiwa_total'] = 280;

        $r = $this->map->petakSatuDesa($b);

        $sumL = 0;
        $sumP = 0;
        foreach (DukcapilMapping::KOLOM_USIA as $g) {
            $sumL += (int) $r[$g . '_l'];
            $sumP += (int) $r[$g . '_p'];
        }

        $this->assertSame(168, $sumL, 'Sigma u*_l harus == jiwa_l');
        $this->assertSame(112, $sumP, 'Sigma u*_p harus == jiwa_p');
        $this->assertSame(280, $this->totalPiramida($r));
        $this->assertGreaterThan($sumP, $sumL, 'rasio 0.6 -> laki-laki harus lebih banyak');
    }

    public function testPiramidaKasusEkstremNol(): void
    {
        $b = $this->barisSintetis();
        foreach ($b['usia'] as $k => $_) {
            $b['usia'][$k] = 0;
        }
        $b['jiwa_total'] = 0;
        $b['jiwa_l']     = 0;
        $b['jiwa_p']     = 0;
        $b['kk_total']   = 0;
        $b['kk_l']       = 0;
        $b['kk_p']       = 0;
        foreach ($b['pendidikan'] as $k => $_) {
            $b['pendidikan'][$k] = 0;
        }
        foreach ($b['pekerjaan'] as $k => $_) {
            $b['pekerjaan'][$k] = 0;
        }

        $r = $this->map->petakSatuDesa($b);

        $this->assertSame(0, $this->totalPiramida($r), 'desa kosong -> piramida nol semua');
        foreach (DukcapilMapping::KOLOM_USIA as $g) {
            $this->assertSame(0, (int) $r[$g . '_l']);
            $this->assertSame(0, (int) $r[$g . '_p']);
        }
    }

    public function testPiramidaTidakAdaNilaiNegatif(): void
    {
        // Semua nilai usia sangat kecil sehingga koreksi pembulatan berpotensi
        // menghasilkan angka negatif bila tidak dijaga.
        $b = $this->barisSintetis();
        $b['jiwa_l'] = 279;
        $b['jiwa_p'] = 1;
        $b['jiwa_total'] = 280;

        $r = $this->map->petakSatuDesa($b);

        foreach (DukcapilMapping::KOLOM_USIA as $g) {
            $this->assertGreaterThanOrEqual(0, (int) $r[$g . '_l']);
            $this->assertGreaterThanOrEqual(0, (int) $r[$g . '_p']);
        }
        $this->assertSame(280, $this->totalPiramida($r));
    }

    // -----------------------------------------------------------------------
    // (b) Sigma pendidikan == kk_total
    // -----------------------------------------------------------------------

    public function testTotalPendidikanSamaDenganKk(): void
    {
        $b = $this->barisSintetis();
        $r = $this->map->petakSatuDesa($b);

        $kk   = (int) $r['kk_l'] + (int) $r['kk_p'];
        $sum  = 0;
        foreach (array_keys(DukcapilMapping::PENDIDIKAN_MAP) as $c) {
            $sum += (int) $r[$c];
        }

        $this->assertSame($kk, $sum, 'Sigma kk_pend_* harus == kk_total');
    }

    public function testTotalPendidikanSamaUntukBerbagaiSkala(): void
    {
        //uji pada kk_total yang bukan kelipatan "rapi" supaya pembulatan benar-benar diuji
        foreach ([7, 13, 99, 100, 801, 1484] as $kkTotal) {
            $b = $this->barisSintetis();
            $b['kk_total'] = $kkTotal;
            $b['kk_l']     = (int) round($kkTotal * 0.7);
            $b['kk_p']     = $kkTotal - $b['kk_l'];

            $r = $this->map->petakSatuDesa($b);

            $sum = 0;
            foreach (array_keys(DukcapilMapping::PENDIDIKAN_MAP) as $c) {
                $sum += (int) $r[$c];
            }
            $this->assertSame($kkTotal, $sum, "Sigma kk_pend_* harus == $kkTotal");
        }
    }

    public function testPendidikanMenggabungkanTingkatTanpaKolom(): void
    {
        // Hanya `belum_tamat_sd` & `tamat_sd` yang diisi -> keduanya harus
        // bergabung ke kk_pend_sd, kolom lain nol.
        $b = $this->barisSintetis();
        foreach ($b['pendidikan'] as $k => $_) {
            $b['pendidikan'][$k] = 0;
        }
        $b['pendidikan']['belum_tamat_sd'] = 140;
        $b['pendidikan']['tamat_sd']       = 140;

        $r = $this->map->petakSatuDesa($b);

        $this->assertSame(100, (int) $r['kk_pend_sd'], 'belum_tamat_sd + tamat_sd -> kk_pend_sd');
        $this->assertSame(0, (int) $r['kk_pend_tidak_sekolah']);
        $this->assertSame(0, (int) $r['kk_pend_smp']);
    }

    // -----------------------------------------------------------------------
    // (c) Sigma pekerjaan == kk_total
    // -----------------------------------------------------------------------

    public function testTotalPekerjaanSamaDenganKk(): void
    {
        $b = $this->barisSintetis();
        $r = $this->map->petakSatuDesa($b);

        $kk  = (int) $r['kk_l'] + (int) $r['kk_p'];
        $sum = 0;
        foreach (array_keys(DukcapilMapping::PEKERJAAN_MAP) as $c) {
            $sum += (int) $r[$c];
        }

        $this->assertSame($kk, $sum, 'Sigma kk_ker_* harus == kk_total');
    }

    public function testTotalPekerjaanSamaUntukBerbagaiSkala(): void
    {
        foreach ([7, 13, 99, 100, 801, 1484] as $kkTotal) {
            $b = $this->barisSintetis();
            $b['kk_total'] = $kkTotal;
            $b['kk_l']     = (int) round($kkTotal * 0.7);
            $b['kk_p']     = $kkTotal - $b['kk_l'];

            $r = $this->map->petakSatuDesa($b);

            $sum = 0;
            foreach (array_keys(DukcapilMapping::PEKERJAAN_MAP) as $c) {
                $sum += (int) $r[$c];
            }
            $this->assertSame($kkTotal, $sum, "Sigma kk_ker_* harus == $kkTotal");
        }
    }

    public function testPekerjaanMemakaiDelapanKolomDb(): void
    {
        $b = $this->barisSintetis();
        $r = $this->map->petakSatuDesa($b);

        $kolom = [
            'kk_ker_tani', 'kk_ker_nelayan', 'kk_ker_pns', 'kk_ker_swasta',
            'kk_ker_pedagang', 'kk_ker_wiraswasta', 'kk_ker_buruh', 'kk_ker_tidak_kerja',
        ];
        foreach ($kolom as $c) {
            $this->assertArrayHasKey($c, $r, "kolom $c harus ada");
        }

        // Pemetaan harus menghasilkan tepat 8 kolom DB (dari 11 jenis Excel).
        // Urutan konstanta tidak bermakna, jadi dibandingkan sebagai himpunan.
        $aktual = array_keys(DukcapilMapping::PEKERJAAN_MAP);
        sort($kolom);
        sort($aktual);
        $this->assertSame($kolom, $aktual, 'pemetaan harus menghasilkan tepat 8 kolom kk_ker_*');

        // Semua kolom harus berupa integer >= 0, bukan string/null.
        foreach ($kolom as $c) {
            $this->assertIsInt($r[$c], "kolom $c harus integer");
            $this->assertGreaterThanOrEqual(0, $r[$c], "kolom $c tidak boleh negatif");
        }
    }

    // -----------------------------------------------------------------------
    // (d) `65-99` -> u65_69
    // -----------------------------------------------------------------------

    public function testLabel65SembilanPuluhSembilanMasukKeU65_69(): void
    {
        $b = $this->barisSintetis();
        foreach ($b['usia'] as $k => $_) {
            $b['usia'][$k] = 0;
        }
        // A1: satu-satunya nilai usia ada di label `65-99`.
        $b['usia']['65-99'] = 100;
        $b['jiwa_total'] = 100;
        $b['jiwa_l']     = 50;
        $b['jiwa_p']     = 50;

        $r = $this->map->petakSatuDesa($b);

        $this->assertSame(50, (int) $r['u65_69_l'], 'A1: `65-99` -> u65_69_l');
        $this->assertSame(50, (int) $r['u65_69_p'], 'A1: `65-99` -> u65_69_p');
        $this->assertSame(0, (int) $r['u70_74_l'] + (int) $r['u70_74_p'], '70-74 tidak boleh terisi');
        $this->assertSame(100, $this->totalPiramida($r));
    }

    public function testLabel65EnamPuluhSembilanJugaDiterima(): void
    {
        // Bila file revisi sudah memperbaiki typo, pemetaan tetap sama.
        $b = $this->barisSintetis();
        foreach ($b['usia'] as $k => $_) {
            $b['usia'][$k] = 0;
        }
        $b['usia']['65-69'] = 40;
        $b['jiwa_total'] = 40;
        $b['jiwa_l']     = 20;
        $b['jiwa_p']     = 20;

        $r = $this->map->petakSatuDesa($b);

        $this->assertSame(40, (int) $r['u65_69_l'] + (int) $r['u65_69_p']);
    }

    // -----------------------------------------------------------------------
    // (e) `75+` terpecah ke 3 golongan
    // -----------------------------------------------------------------------

    public function testPecah75PlusTigaGolongan(): void
    {
        $b = $this->barisSintetis();
        foreach ($b['usia'] as $k => $_) {
            $b['usia'][$k] = 0;
        }
        $b['usia']['75+'] = 100;
        $b['jiwa_total'] = 100;
        $b['jiwa_l']     = 60;
        $b['jiwa_p']     = 40;

        $r = $this->map->petakSatuDesa($b);

        // A2: rasio 50 / 30 / 20 dijumlahkan per gender -> 60/36/24.
        $this->assertSame(30, (int) $r['u75_79_l']);
        $this->assertSame(18, (int) $r['u80_84_l']);
        $this->assertSame(12, (int) $r['u85_atas_l']);
        $this->assertSame(20, (int) $r['u75_79_p']);
        $this->assertSame(12, (int) $r['u80_84_p']);
        $this->assertSame(8, (int) $r['u85_atas_p']);

        // Total per golongan harus 50/30/20 dari 100.
        $this->assertSame(50, (int) $r['u75_79_l'] + (int) $r['u75_79_p']);
        $this->assertSame(30, (int) $r['u80_84_l'] + (int) $r['u80_84_p']);
        $this->assertSame(20, (int) $r['u85_atas_l'] + (int) $r['u85_atas_p']);
        $this->assertSame(100, $this->totalPiramida($r));
    }

    public function testPecah75PlusTidakKehilanganAngka(): void
    {
        // Angka ganjil: pemecahan tidak boleh kehilangan / menambah 1 pun.
        foreach ([0, 1, 3, 7, 45, 789] as $v) {
            $b = $this->barisSintetis();
            foreach ($b['usia'] as $k => $_) {
                $b['usia'][$k] = 0;
            }
            $b['usia']['75+'] = $v;
            $b['jiwa_total'] = $v;
            $b['jiwa_l']     = (int) ceil($v / 2);
            $b['jiwa_p']     = $v - $b['jiwa_l'];

            $r = $this->map->petakSatuDesa($b);

            $this->assertSame($v, $this->totalPiramida($r), "75+ = $v tidak boleh berubah totalnya");
        }
    }

    public function testPecah75PlusRasioLimaTigaDua(): void
    {
        $this->assertSame([50, 30, 20], $this->map->pecah75plus(100));
        $this->assertSame([5, 3, 2], $this->map->pecah75plus(10));
        $this->assertSame([0, 0, 0], $this->map->pecah75plus(0));
    }

    // -----------------------------------------------------------------------
    // (f) kk_belum_punya_kartu_fisik
    // -----------------------------------------------------------------------

    public function testBelumPunyaKartuFisikAdalahSisaKk(): void
    {
        $b = $this->barisSintetis(); // kk_total 100, kartu_keluarga 93
        $r = $this->map->petakSatuDesa($b);

        $kk = (int) $r['kk_l'] + (int) $r['kk_p'];

        $this->assertSame(93, (int) $r['kk_punya_kartu_fisik'], 'kartu_keluarga -> kk_punya_kartu_fisik');
        $this->assertSame(7, (int) $r['kk_belum_punya_kartu_fisik'], 'sisanya = kk_total - kartu_keluarga');
        $this->assertSame($kk, (int) $r['kk_punya_kartu_fisik'] + (int) $r['kk_belum_punya_kartu_fisik']);
    }

    public function testBelumPunyaKartuFisikTidakNegatifBilaDokumenLebihBesar(): void
    {
        // Data anomali: kartu_keluarga lebih besar dari kk_total.
        $r = $this->map->petakDokumen(['kartu_keluarga' => 150], 100);

        $this->assertSame(100, (int) $r['kk_punya_kartu_fisik'], 'harus diklem ke kk_total');
        $this->assertSame(0, (int) $r['kk_belum_punya_kartu_fisik'], 'tidak boleh negatif');
    }

    public function testDokumenKiaDanAktaCeraiDisimpan(): void
    {
        // Kelompok A: KIA (0-16) -> dok_kia dan Akta Perceraian -> dok_akta_cerai
        // kini DISIMPAN (kolom baru lewat migration 2026-10-09-000001).
        $b = $this->barisSintetis();
        $b['dokumen']['kia_0_16']       = 999;
        $b['dokumen']['akta_perceraian'] = 888;

        $r = $this->map->petakSatuDesa($b);

        $this->assertSame(200, (int) $r['pend_wajib_ktp'], 'KTP-el -> pend_wajib_ktp');
        $this->assertSame(60, (int) $r['pend_punya_akta_lahir'], 'akta lahir 0-17 -> pend_punya_akta_lahir');
        $this->assertSame(70, (int) $r['kk_punya_akta_nikah'], 'akta perkawinan -> kk_punya_akta_nikah');
        $this->assertSame(999, (int) $r['dok_kia'], 'KIA 0-16 -> dok_kia');
        $this->assertSame(888, (int) $r['dok_akta_cerai'], 'akta perceraian -> dok_akta_cerai');
    }

    public function testDokumenKiaDanAktaCeraiMasukKolomResmi(): void
    {
        $resmi = $this->map->kolomResmi();
        $this->assertContains('dok_kia', $resmi);
        $this->assertContains('dok_akta_cerai', $resmi);
        // Tidak boleh dianggap estimasi (punya sumber Excel).
        $this->assertNotContains('dok_kia', $this->map->kolomEstimasi());
        $this->assertNotContains('dok_akta_cerai', $this->map->kolomEstimasi());
    }

    // -----------------------------------------------------------------------
    // (g) jml_balita / jml_remaja / jml_lansia
    // -----------------------------------------------------------------------

    public function testKelompokKhususDariPiramida(): void
    {
        $b = $this->barisSintetis();
        $r = $this->map->petakSatuDesa($b);

        $balitaDiharapkan = (int) $r['u0_4_l'] + (int) $r['u0_4_p'];
        $remajaDiharapkan = (int) $r['u10_14_l'] + (int) $r['u10_14_p']
                          + (int) $r['u15_19_l'] + (int) $r['u15_19_p'];

        $lansiaDiharapkan = 0;
        foreach (['u60_64', 'u65_69', 'u70_74', 'u75_79', 'u80_84', 'u85_atas'] as $g) {
            $lansiaDiharapkan += (int) $r[$g . '_l'] + (int) $r[$g . '_p'];
        }

        $this->assertSame($balitaDiharapkan, (int) $r['jml_balita'], 'balita = u0_4_l + u0_4_p');
        $this->assertSame($remajaDiharapkan, (int) $r['jml_remaja'], 'remaja = u10_14 + u15_19');
        $this->assertSame($lansiaDiharapkan, (int) $r['jml_lansia'], 'lansia = u60_64 .. u85_atas');
    }

    public function testKelompokKhususAngkaEksak(): void
    {
        // Semua usia dipusatkan di 3 golongan supaya angkanya bisa dicek pasti.
        $b = $this->barisSintetis();
        foreach ($b['usia'] as $k => $_) {
            $b['usia'][$k] = 0;
        }
        $b['usia']['00-04'] = 40; // balita
        $b['usia']['10-14'] = 30; // remaja
        $b['usia']['15-19'] = 20; // remaja
        $b['usia']['60-64'] = 10; // lansia
        $b['jiwa_total'] = 100;
        $b['jiwa_l']     = 50;
        $b['jiwa_p']     = 50;

        $r = $this->map->petakSatuDesa($b);

        $this->assertSame(40, (int) $r['jml_balita']);
        $this->assertSame(50, (int) $r['jml_remaja']);
        $this->assertSame(10, (int) $r['jml_lansia']);
    }

    public function testKelompokKhususKonsistenDenganFungsiMandiri(): void
    {
        $b = $this->barisSintetis();
        $r = $this->map->petakSatuDesa($b);

        $langsung = $this->map->hitungKelompokKhusus($r);

        $this->assertSame((int) $r['jml_balita'], $langsung['jml_balita']);
        $this->assertSame((int) $r['jml_remaja'], $langsung['jml_remaja']);
        $this->assertSame((int) $r['jml_lansia'], $langsung['jml_lansia']);
    }

    // -----------------------------------------------------------------------
    // Penataan kolom & estimasi
    // -----------------------------------------------------------------------

    public function testSemuaKolomTabelTerisi(): void
    {
        // allowedFields profil_dukcapil (tanpa meta) harus semuanya ada di
        // hasil pemetaan — kalau ada yang hilang, insert akan memakai default 0
        // tanpa ketahuan.
        $model   = new \App\Models\ProfilDukcapilModel();
        $meta    = ['id_desa', 'semester', 'tahun', 'imported_at', 'imported_by'];
        $dibutuh = array_values(array_diff($model->allowedFields ?? [], $meta));

        $r = $this->map->petakSatuDesa($this->barisSintetis());

        $hilang = array_values(array_diff($dibutuh, array_keys($r)));
        $this->assertSame([], $hilang, 'kolom tabel yang belum terpetakan: ' . implode(', ', $hilang));
    }

    public function testDaftarKolomResmiDanEstimasiTidakTumpangTindih(): void
    {
        $resmi    = $this->map->kolomResmi();
        $estimasi = $this->map->kolomEstimasi();

        $this->assertSame([], array_intersect($resmi, $estimasi), 'resmi & estimasi tidak boleh tumpang tindih');
        $this->assertCount(65, $resmi);
        $this->assertCount(18, $estimasi);
        $this->assertSame(83, count($resmi) + count($estimasi), '65 resmi + 18 estimasi = 83 kolom statistik');
    }

    public function testEstimasiMengikutiFormulaSeeder(): void
    {
        $kk   = 1000;
        $jiwa = 3000;

        $e = $this->map->estimasiTanpaSumber($kk, $jiwa);

        // Port persis dari ProfilDukcapilSeeder baris 74-95.
        $this->assertSame((int) round($jiwa * 0.80), $e['jkn']);
        $this->assertSame($jiwa - $e['jkn'], $e['non_jkn']);
        $this->assertSame($e['jkn'], $e['pend_bpjs']);
        $this->assertSame($e['non_jkn'], $e['pend_non_bpjs']);
        $this->assertSame((int) round($jiwa * 0.25), $e['pus_pbi']);
        $this->assertSame($e['jkn'] - $e['pus_pbi'], $e['pus_non_pbi']);
        $this->assertSame($e['pus_pbi'] + $e['pus_non_pbi'], $e['pus_jkn']);
        $this->assertSame($kk, $e['penduduk_hub_kepala']);
        $this->assertSame((int) round($kk * 0.85), $e['penduduk_hub_istri']);
        $this->assertSame((int) round($jiwa * 0.30), $e['penduduk_hub_anak']);
        $this->assertSame((int) round($e['kk_kawin'] * 0.85), $e['jml_pus']);
        $this->assertSame((int) round($e['jml_pus'] * 0.60), $e['kb_aktif']);
        $this->assertSame($e['kb_aktif'], $e['jml_penggunaan_alat_kontrasepsi']);
    }

    public function testSemesterDanTahunIkutMasuk(): void
    {
        $r = $this->map->petakSatuDesa($this->barisSintetis(), 2, 2027);

        $this->assertSame(2, $r['semester']);
        $this->assertSame(2027, $r['tahun']);
    }

    public function testPecahGenderBatasRasio(): void
    {
        $this->assertSame([10, 0], $this->map->pecahGender(10, 1.0), 'semua laki-laki');
        $this->assertSame([0, 10], $this->map->pecahGender(10, 0.0), 'semua perempuan');
        $this->assertSame([5, 5], $this->map->pecahGender(10, 0.5), 'rasio 50:50');
        $this->assertSame([0, 0], $this->map->pecahGender(0, 0.5), 'nilai nol');
        $this->assertSame([10, 0], $this->map->pecahGender(10, 5.0), 'rasio di luar rentang diklem');
    }
}
