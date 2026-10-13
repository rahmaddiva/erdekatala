<?php

namespace App\Libraries;

/**
 * Pemetaan 1 baris Excel Dukcapil -> 1 baris tabel `profil_dukcapil`.
 *
 * SENG DIBUAT MURNI: tidak menyentuh database, tidak membaca file, tidak
 * memanggil service CodeIgniter. Semua metode publik menerima array dan
 * mengembalikan array, sehingga bisa di-unit-test dan diaudit tanpa fixture
 * berat (lihat tests/unit/DukcapilMappingTest.php).
 *
 * Sumber data : DATA KEPENDUDUKAN KAB TALA SM I 2026.xlsx (8 sheet, 135 desa).
 * Dasar hukum : docs/superpowers/specs/2026-10-09-integrasi-dukcapil-sm1-2026-design.md
 *               (Opsi C — tanpa migration) & rencana
 *               docs/superpowers/plans/2026-10-09-import-dukcapil-sm1-2026.md.
 *
 * ASUMSI YANG DIPAKAI (harus dikonfirmasi ke Dukcapil sebelum dipakai resmi):
 *   A1  Header usia `65-99` = `65-69` (dugaan typo). Bukti: Sigma 16 grup usia
 *       per desa == JML JIWA persis (135/135, selisih 0). Bila `65-99` berarti
 *       65-99 tahun, grup 70-74 dan 75+ terhitung ganda dan total mustahil sama.
 *   A2  `75+` dipecah 50% / 30% / 20% -> u75_79 / u80_84 / u85_atas. Rasio ini
 *       diambil SAMA PERSIS dengan LaporanAgregatSeeder::mapKecamatanData().
 *   A3  Usia Excel gabungan L+P; dipecah dengan rasio gender desa
 *       r = jiwa_l / (jiwa_l + jiwa_p). Sigma dijaga agar tetap == jiwa_l/jiwa_p.
 *   A4  Pendidikan (10 tingkat) & pekerjaan (11 jenis) berbasis JIWA di Excel,
 *       tetapi kolom DB `kk_pend_*`/`kk_ker_*` berbasis KK -> diskalakan dengan
 *       f = kk_total / jiwa_total. HASILNYA BUKAN ANGKA RESMI PER-KK.
 *   A5  Dokumen: KTP-el -> pend_wajib_ktp; KK -> kk_punya_kartu_fisik
 *       (+ lawannya = kk_total - nilai); Akta Lahir 0-17 -> pend_punya_akta_lahir;
 *       Akta Perkawinan -> kk_punya_akta_nikah. KIA (0-16) -> dok_kia dan
 *       Akta Perceraian -> dok_akta_cerai (Kelompok A: kini DISIMPAN, basis
 *       penduduk, butuh kolom baru lewat migration 2026-10-09-000001).
 *   A6  jml_balita / jml_remaja / jml_lansia dihitung dari piramida hasil A1-A3.
 *   A7  Kolom tanpa sumber Excel diisi ESTIMASI (port formula seeder) — lihat
 *       estimasiTanpaSumber(). Semua ditandai agar tidak dibaca sebagai data resmi.
 */
class DukcapilMapping
{
    /** Penanda khusus: nilai usia `75+` harus dipecah jadi 3 golongan (A2). */
    public const PECAH_75 = '@pecah75plus';

    /** 18 golongan usia DB (nama dasar, tanpa akhiran _l / _p). */
    public const KOLOM_USIA = [
        'u0_4', 'u5_9', 'u10_14', 'u15_19', 'u20_24', 'u25_29',
        'u30_34', 'u35_39', 'u40_44', 'u45_49', 'u50_54', 'u55_59',
        'u60_64', 'u65_69', 'u70_74', 'u75_79', 'u80_84', 'u85_atas',
    ];

    /**
     * Label usia Excel -> kolom DB.
     * Catatan A1: `65-99` dipetakan ke `u65_69`. `65-69` juga diterima untuk
     * berjaga-jaga bila file revisi sudah memperbaiki typonya.
     */
    public const USIA_MAP = [
        '00-04' => 'u0_4',
        '05-09' => 'u5_9',
        '10-14' => 'u10_14',
        '15-19' => 'u15_19',
        '20-24' => 'u20_24',
        '25-29' => 'u25_29',
        '30-34' => 'u30_34',
        '35-39' => 'u35_39',
        '40-44' => 'u40_44',
        '45-49' => 'u45_49',
        '50-54' => 'u50_54',
        '55-59' => 'u55_59',
        '60-64' => 'u60_64',
        '65-99' => 'u65_69', // A1 — typo di file asli
        '65-69' => 'u65_69',
        '70-74' => 'u70_74',
        '75+'   => self::PECAH_75,
    ];

    /** 10 tingkat pendidikan Excel (basis JIWA) -> 7 kolom DB (basis KK). */
    public const PENDIDIKAN_MAP = [
        'kk_pend_tidak_sekolah' => ['tidak_blm_sekolah'],
        'kk_pend_sd'            => ['belum_tamat_sd', 'tamat_sd'],
        'kk_pend_smp'           => ['sltp'],
        'kk_pend_sma'           => ['slta'],
        'kk_pend_diploma'       => ['diploma_1_2', 'akademi_d3'],
        'kk_pend_s1'            => ['s1_d4'],
        'kk_pend_s2_s3'         => ['s2', 's3'],
    ];

    /**
     * 11 jenis pekerjaan Excel (basis JIWA) -> 8 kolom DB (basis KK).
     * Bobot < 1.0 = satu sel Excel dibagi ke beberapa kolom DB.
     *
     * Keputusan judgement (tidak ada pemetaan bersih dari Excel):
     *  - TENAGA PENGAJAR  -> kk_ker_pns        : pengajar negeri dominan ASN;
     *                                            DB tidak punya kategori pendidik.
     *  - TENAGA KESEHATAN -> kk_ker_pns        : nakes publik dominan ASN.
     *  - AGAMA & KEPERCAYAAN, PELAJAR/MAHASISWA, PENSIUNAN
     *                     -> kk_ker_tidak_kerja: bukan angkatan kerja; jumlahnya
     *                                            kecil sehingga efeknya minor.
     *  - WIRASWASTA       -> swasta 40% / pedagang 30% / wiraswasta 30%:
     *                        mengikuti pembeda yang sudah dipakai
     *                        LaporanAgregatSeeder (emp[3] 40/30/30).
     *  - LAINNYA          -> buruh 40% / tidak_kerja 60%: idem
     *                        (`lain_buruh = 0.4 x lain` di seeder).
     */
    public const PEKERJAAN_MAP = [
        'kk_ker_tidak_kerja' => [
            ['belum_tidak_bekerja', 1.0],
            ['agama_kepercayaan', 1.0],
            ['pelajar_mahasiswa', 1.0],
            ['pensiunan', 1.0],
            ['lainnya', 0.6],
        ],
        'kk_ker_pns' => [
            ['aparatur_pejabat_negara', 1.0],
            ['tenaga_pengajar', 1.0],
            ['tenaga_kesehatan', 1.0],
        ],
        'kk_ker_tani'       => [['pertanian_peternakan', 1.0]],
        'kk_ker_nelayan'    => [['nelayan', 1.0]],
        'kk_ker_swasta'     => [['wiraswasta', 0.4]],
        'kk_ker_pedagang'   => [['wiraswasta', 0.3]],
        'kk_ker_wiraswasta' => [['wiraswasta', 0.3]],
        'kk_ker_buruh'      => [['lainnya', 0.4]],
    ];

    /** Rasio status kawin ESTIMASI (A7) — diturunkan dari total SM2/2025. */
    private const RASIO_KAWIN        = 1.273284; // 166.683 / 130.908
    private const RASIO_BELUM_KAWIN  = 1.395591; // 182.694 / 130.908
    private const RASIO_CERAI_HIDUP  = 0.066742; //   8.737 / 130.908
    private const RASIO_CERAI_MATI   = 0.133445; //  17.469 / 130.908

    /**
     * Kolom yang bersumber dari Excel (langsung atau lewat transformasi
     * terdokumentasi A1-A6).
     */
    public function kolomResmi(): array
    {
        $usia = [];
        foreach (self::KOLOM_USIA as $b) {
            $usia[] = $b . '_l';
            $usia[] = $b . '_p';
        }

        return array_merge(
            ['jiwa_l', 'jiwa_p', 'kk_l', 'kk_p'],
            $usia,
            array_keys(self::PENDIDIKAN_MAP),
            array_keys(self::PEKERJAAN_MAP),
            [
                'pend_wajib_ktp', 'kk_punya_kartu_fisik', 'kk_belum_punya_kartu_fisik',
                'pend_punya_akta_lahir', 'kk_punya_akta_nikah',
                'dok_kia', 'dok_akta_cerai',
                'jml_balita', 'jml_remaja', 'jml_lansia',
            ]
        );
    }

    /** Kolom yang TIDAK punya sumber Excel — diisi estimasi (A7). */
    public function kolomEstimasi(): array
    {
        return [
            'kk_kawin', 'kk_belum_kawin', 'kk_cerai_hidup', 'kk_cerai_mati',
            'pend_bpjs', 'pend_non_bpjs',
            'pus_jkn', 'pus_pbi', 'pus_non_pbi', 'jkn', 'non_jkn',
            'penduduk_hub_kepala', 'penduduk_hub_istri', 'penduduk_hub_anak', 'penduduk_hub_lainnya',
            'kb_aktif', 'jml_pus', 'jml_penggunaan_alat_kontrasepsi',
        ];
    }

    /**
     * Pecah 1 angka gabungan (L+P) menjadi [laki, perempuan] memakai rasio
     * gender desa (A3).
     *
     * @return array{0:int,1:int}
     */
    public function pecahGender(int $nilai, float $rasioLaki): array
    {
        if ($nilai <= 0) {
            return [0, 0];
        }

        $r = max(0.0, min(1.0, $rasioLaki));
        $l = (int) round($nilai * $r);
        $l = max(0, min($nilai, $l));

        return [$l, $nilai - $l];
    }

    /**
     * Pecah satu angka `75+` menjadi [75-79, 80-84, 85+] dengan rasio
     * 50% / 30% / 20% (A2 — sama dengan LaporanAgregatSeeder::mapKecamatanData()).
     * Sisa (bukan pembulatan ketiga) diberikan ke 85+ supaya total selalu pas.
     *
     * @return array{0:int,1:int,2:int}
     */
    public function pecah75plus(int $nilai): array
    {
        if ($nilai <= 0) {
            return [0, 0, 0];
        }

        $u75_79 = (int) round($nilai * 0.5);
        $u80_84 = (int) round($nilai * 0.3);
        $u85    = $nilai - $u75_79 - $u80_84;

        if ($u85 < 0) {
            $u85 = 0; // hanya mungkin bila $nilai == 0, sudah ditangani di atas
        }

        return [$u75_79, $u80_84, $u85];
    }

    /**
     * Sebar 16 grup usia gabungan Excel menjadi 36 kolom piramida DB (18 x L/P).
     *
     * A1: `65-99` -> `u65_69`.
     * A2: `75+` dipecah dulu per gender, lalu 50/30/20.
     * A3: pemecahan L/P memakai rasio gender desa; SELISIH PEMBULATAN dikoreksi
     *     agar Sigma(u*_l) == jiwa_l dan Sigma(u*_p) == jiwa_p.
     *
     * @param array $usia Label Excel => nilai (16 key)
     * @return array 36 kolom `u*_l` / `u*_p`
     */
    public function sebarPiramida(array $usia, int $jiwaL, int $jiwaP): array
    {
        $hasil = [];
        foreach (self::KOLOM_USIA as $b) {
            $hasil[$b] = [0, 0]; // [laki, perempuan]
        }

        $rasio = ($jiwaL + $jiwaP) > 0 ? $jiwaL / ($jiwaL + $jiwaP) : 0.5;

        foreach ($usia as $label => $nilai) {
            $v = (int) $nilai;
            if ($v <= 0) {
                continue;
            }

            $tujuan = self::USIA_MAP[$this->normalisasiLabel((string) $label)] ?? null;
            if ($tujuan === null) {
                continue; // label tidak dikenal -> diabaikan, tidak merusak total lain
            }

            if ($tujuan === self::PECAH_75) {
                [$l, $p]   = $this->pecahGender($v, $rasio);
                [$la, $lb, $lc] = $this->pecah75plus($l);
                [$pa, $pb, $pc] = $this->pecah75plus($p);

                $hasil['u75_79'][0]  += $la;
                $hasil['u75_79'][1]  += $pa;
                $hasil['u80_84'][0]  += $lb;
                $hasil['u80_84'][1]  += $pb;
                $hasil['u85_atas'][0] += $lc;
                $hasil['u85_atas'][1] += $pc;
                continue;
            }

            [$l, $p] = $this->pecahGender($v, $rasio);
            $hasil[$tujuan][0] += $l;
            $hasil[$tujuan][1] += $p;
        }

        // --- Koreksi pembulatan A3 -------------------------------------------
        // round() per grup bisa membuat Sigma(L) bergeser 1-2 dari jiwa_l.
        // Selisih dialokasikan ±1 ke golongan terbesar (geser L<->P dalam golongan
        // yang sama, jadi total per golongan tidak berubah).
        $sumL = 0;
        foreach (self::KOLOM_USIA as $b) {
            $sumL += $hasil[$b][0];
        }
        $selisih = $jiwaL - $sumL;

        if ($selisih !== 0) {
            $urutan = self::KOLOM_USIA;
            usort($urutan, static fn($a, $b) => ($hasil[$b][0] + $hasil[$b][1]) <=> ($hasil[$a][0] + $hasil[$a][1]));

            $arah = $selisih > 0 ? 1 : -1;
            $sisa = abs($selisih);
            $n    = count($urutan);

            for ($i = 0; $i < $n * 4 && $sisa > 0; $i++) {
                $k = $urutan[$i % $n];
                if ($arah > 0) {
                    if ($hasil[$k][1] > 0) {
                        $hasil[$k][0]++;
                        $hasil[$k][1]--;
                        $sisa--;
                    }
                } elseif ($hasil[$k][0] > 0) {
                    $hasil[$k][0]--;
                    $hasil[$k][1]++;
                    $sisa--;
                }
            }
        }

        $out = [];
        foreach (self::KOLOM_USIA as $b) {
            $out[$b . '_l'] = $hasil[$b][0];
            $out[$b . '_p'] = $hasil[$b][1];
        }

        return $out;
    }

    /**
     * A4 — pendidikan basis JIWA (10 tingkat) -> basis KK (7 kolom).
     * Hasil dijamin: Sigma(kk_pend_*) == kk_total.
     */
    public function skalaPendidikan(array $pendidikan, int $kkTotal, int $jiwaTotal): array
    {
        $raw = [];
        foreach (self::PENDIDIKAN_MAP as $kolom => $sumber) {
            $jum = 0;
            foreach ($sumber as $k) {
                $jum += (int) ($pendidikan[$k] ?? 0);
            }
            $raw[$kolom] = $jum;
        }

        return $this->skalaDanRapikan($raw, $kkTotal, $jiwaTotal);
    }

    /**
     * A4 — pekerjaan basis JIWA (11 jenis) -> basis KK (8 kolom).
     * Hasil dijamin: Sigma(kk_ker_*) == kk_total.
     */
    public function skalaPekerjaan(array $pekerjaan, int $kkTotal, int $jiwaTotal): array
    {
        $raw = [];
        foreach (self::PEKERJAAN_MAP as $kolom => $komponen) {
            $jum = 0.0;
            foreach ($komponen as [$kunci, $bobot]) {
                $jum += ((int) ($pekerjaan[$kunci] ?? 0)) * $bobot;
            }
            $raw[$kolom] = $jum;
        }

        return $this->skalaDanRapikan($raw, $kkTotal, $jiwaTotal);
    }

    /**
     * A5 — dokumen. SEMUA 6 kolom sheet KEPEMILIKAN DOK kini dipakai, termasuk
     * KIA (0-16) -> `dok_kia` dan Akta Perceraian -> `dok_akta_cerai`
     * (Kelompok A — dulu dibuang karena butuh kolom baru; kini ada lewat
     * migration 2026-10-09-000001). Basis keduanya PENDUDUK (bukan KK), jadi
     * tidak ikut diskalakan dengan f = kk_total / jiwa_total seperti A4.
     *
     * @return array{pend_wajib_ktp:int, kk_punya_kartu_fisik:int, kk_belum_punya_kartu_fisik:int, pend_punya_akta_lahir:int, kk_punya_akta_nikah:int, dok_kia:int, dok_akta_cerai:int}
     */
    public function petakDokumen(array $dokumen, int $kkTotal): array
    {
        $kartuKeluarga = (int) ($dokumen['kartu_keluarga'] ?? 0);

        // Diklem ke kk_total agar `punya + belum punya == kk_total` selalu benar.
        $punya = max(0, min($kartuKeluarga, $kkTotal));

        return [
            'pend_wajib_ktp'               => (int) ($dokumen['ktp_el_rekam'] ?? 0),
            'kk_punya_kartu_fisik'         => $punya,
            'kk_belum_punya_kartu_fisik'   => max(0, $kkTotal - $punya),
            'pend_punya_akta_lahir'        => (int) ($dokumen['akta_lahir_0_17'] ?? 0),
            'kk_punya_akta_nikah'          => (int) ($dokumen['akta_perkawinan'] ?? 0),
            // Kelompok A: 2 indikator dokumen yang dulu dibuang (basis penduduk).
            'dok_kia'                      => (int) ($dokumen['kia_0_16'] ?? 0),
            'dok_akta_cerai'               => (int) ($dokumen['akta_perceraian'] ?? 0),
        ];
    }

    /**
     * A6 — kelompok khusus, dihitung dari piramida hasil pemetaan (bukan dari
     * Excel langsung). Formula identik dengan ProfilDukcapilSeeder baris 65-73.
     *
     * @return array{jml_balita:int, jml_remaja:int, jml_lansia:int}
     */
    public function hitungKelompokKhusus(array $baris): array
    {
        $lanjut = [
            'u60_64', 'u65_69', 'u70_74', 'u75_79', 'u80_84', 'u85_atas',
        ];

        $balita = (int) ($baris['u0_4_l'] ?? 0) + (int) ($baris['u0_4_p'] ?? 0);

        $remaja = (int) ($baris['u10_14_l'] ?? 0) + (int) ($baris['u10_14_p'] ?? 0)
                + (int) ($baris['u15_19_l'] ?? 0) + (int) ($baris['u15_19_p'] ?? 0);

        $lansia = 0;
        foreach ($lanjut as $b) {
            $lansia += (int) ($baris[$b . '_l'] ?? 0) + (int) ($baris[$b . '_p'] ?? 0);
        }

        return [
            'jml_balita' => $balita,
            'jml_remaja' => $remaja,
            'jml_lansia' => $lansia,
        ];
    }

    /**
     * A7 — ESTIMASI. Kolom yang tidak punya sumber Excel diisi dengan formula
     * yang SAMA dengan yang dipakai ProfilDukcapilSeeder hari ini, supaya landing
     * page tidak drop. Status kawin memakai rasio proporsional deterministik
     * (bukan distribute() acak) agar reprodusibel.
     *
     * SEMUA NILAI DI SINI ADALAH ESTIMASI — BUKAN DATA RESMI DUKCAPIL.
     */
    public function estimasiTanpaSumber(int $kkTotal, int $jiwaTotal): array
    {
        $out = [];

        // Status kawin — rasio dari SM2/2025 (kk_total 130.908).
        $out['kk_kawin']        = (int) round($kkTotal * self::RASIO_KAWIN);
        $out['kk_belum_kawin']  = (int) round($kkTotal * self::RASIO_BELUM_KAWIN);
        $out['kk_cerai_hidup']  = (int) round($kkTotal * self::RASIO_CERAI_HIDUP);
        $out['kk_cerai_mati']   = (int) round($kkTotal * self::RASIO_CERAI_MATI);

        // JKN / BPJS
        $out['jkn']           = (int) round($jiwaTotal * 0.80);
        $out['non_jkn']       = max(0, $jiwaTotal - $out['jkn']);
        $out['pend_bpjs']     = $out['jkn'];
        $out['pend_non_bpjs'] = $out['non_jkn'];

        // PUS & PBI
        $out['pus_pbi']     = (int) round($jiwaTotal * 0.25);
        $out['pus_non_pbi'] = max(0, $out['jkn'] - $out['pus_pbi']);
        $out['pus_jkn']     = $out['pus_pbi'] + $out['pus_non_pbi'];

        // Hubungan dengan kepala keluarga
        $out['penduduk_hub_kepala']  = $kkTotal;
        $out['penduduk_hub_istri']   = (int) round($kkTotal * 0.85);
        $out['penduduk_hub_anak']    = (int) round($jiwaTotal * 0.30);
        $out['penduduk_hub_lainnya'] = max(0, $jiwaTotal
            - $out['penduduk_hub_kepala']
            - $out['penduduk_hub_istri']
            - $out['penduduk_hub_anak']);

        // KB & PUS
        $out['jml_pus']                       = (int) round($out['kk_kawin'] * 0.85);
        $out['kb_aktif']                      = (int) round($out['jml_pus'] * 0.60);
        $out['jml_penggunaan_alat_kontrasepsi'] = $out['kb_aktif'];

        return $out;
    }

    /**
     * Entry point: 1 baris Excel desa -> array kolom `profil_dukcapil`
     * (kolom statistik + semester + tahun; `id_desa`/`imported_at`/`imported_by`
     * ditambahkan pemanggil karena butuh DB).
     *
     * Bentuk $baris:
     *   [
     *     'kode' => '63.01.01.2001', 'nama' => 'BENUA LAWAS',
     *     'kk_total' => 801, 'kk_l' => 616, 'kk_p' => 185,
     *     'jiwa_total' => 2285, 'jiwa_l' => 1153, 'jiwa_p' => 1132,
     *     'usia' => ['00-04' => 136, ..., '65-99' => 71, '70-74' => 45, '75+' => 48],
     *     'pendidikan' => ['tidak_blm_sekolah' => 787, ... 10 key],
     *     'pekerjaan' => ['belum_tidak_bekerja' => 841, ... 11 key],
     *     'dokumen' => ['ktp_el_rekam' => 1633, ... 6 key],
     *   ]
     */
    public function petakSatuDesa(array $baris, int $semester = 1, int $tahun = 2026): array
    {
        $kkTotal   = (int) ($baris['kk_total'] ?? 0);
        $jiwaTotal = (int) ($baris['jiwa_total'] ?? 0);
        $jiwaL     = (int) ($baris['jiwa_l'] ?? 0);
        $jiwaP     = (int) ($baris['jiwa_p'] ?? 0);

        $out = [
            'kk_l'   => (int) ($baris['kk_l'] ?? 0),
            'kk_p'   => (int) ($baris['kk_p'] ?? 0),
            'jiwa_l' => $jiwaL,
            'jiwa_p' => $jiwaP,
        ];

        // A1-A3: piramida (36 kolom)
        $out += $this->sebarPiramida((array) ($baris['usia'] ?? []), $jiwaL, $jiwaP);

        // A4: pendidikan & pekerjaan diskalakan ke basis KK
        $out += $this->skalaPendidikan((array) ($baris['pendidikan'] ?? []), $kkTotal, $jiwaTotal);
        $out += $this->skalaPekerjaan((array) ($baris['pekerjaan'] ?? []), $kkTotal, $jiwaTotal);

        // A5: dokumen
        $out += $this->petakDokumen((array) ($baris['dokumen'] ?? []), $kkTotal);

        // A6: kelompok khusus (butuh piramida yang sudah jadi)
        $out += $this->hitungKelompokKhusus($out);

        // A7: estimasi
        $out += $this->estimasiTanpaSumber($kkTotal, $jiwaTotal);

        $out['semester'] = $semester;
        $out['tahun']    = $tahun;

        return $out;
    }

    /** Normalisasi label Excel: uppercase, spasi dirapatkan, trim. */
    private function normalisasiLabel(string $label): string
    {
        return strtoupper(trim((string) preg_replace('/\s+/', ' ', $label)));
    }

    /**
     * Skala basis jiwa -> basis KK, lalu rapikan pembulatan sehingga
     * Sigma(kolom) == kk_total (syarat A4).
     *
     * @param array<string,float|int> $raw Nilai per kolom (basis jiwa)
     */
    private function skalaDanRapikan(array $raw, int $kkTotal, int $jiwaTotal): array
    {
        $out = [];
        foreach ($raw as $k => $v) {
            $out[$k] = 0;
        }

        if ($kkTotal <= 0) {
            return $out;
        }

        $f = $jiwaTotal > 0 ? ($kkTotal / $jiwaTotal) : 0.0;
        foreach ($raw as $k => $v) {
            $out[$k] = max(0, (int) round(((float) $v) * $f));
        }

        $selisih = $kkTotal - array_sum($out);
        if ($selisih === 0) {
            return $out;
        }

        // Selisih dialokasikan ±1 ke kolom dengan nilai mentah terbesar.
        $urutan = array_keys($raw);
        usort($urutan, static fn($a, $b) => ((float) $raw[$b]) <=> ((float) $raw[$a]));

        $arah = $selisih > 0 ? 1 : -1;
        $sisa = abs($selisih);
        $n    = count($urutan);

        for ($i = 0; $i < $n * 4 && $sisa > 0; $i++) {
            $k = $urutan[$i % $n];
            if ($arah > 0) {
                $out[$k]++;
                $sisa--;
            } elseif ($out[$k] > 0) {
                $out[$k]--;
                $sisa--;
            }
        }

        return $out;
    }
}
