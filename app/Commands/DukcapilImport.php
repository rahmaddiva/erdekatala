<?php

namespace App\Commands;

use App\Libraries\DukcapilMapping;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Impor data Dukcapil Semester I 2026 ke tabel `profil_dukcapil`.
 *
 * MODE REPLACE, bukan append. Saat `--commit`, tabel dikosongkan dengan
 * `DELETE FROM` (DML transaksional — BUKAN `TRUNCATE`, yang di MySQL memicu
 * implicit commit dan tidak bisa di-rollback), lalu diisi TEPAT 135 baris
 * periode target; keadaan akhir = hanya ada (tahun, semester) target.
 *
 * Kenapa replace, bukan "biarkan SM2/2025 sebagai riwayat":
 * `PublicController::landingpage()` mengambil ANGKA dari
 * `ProfilDukcapilModel::getAggregatedStats()` (baris ~110 & ~123), yang
 * menjalankan SUM(kolom) atas SEMUA baris milik id_desa tersebut TANPA filter
 * periode. `getLatestPerDesa()` / `orderBy('tahun DESC, semester DESC')->first()`
 * HANYA dipakai untuk label semester/tahun di note-strip (baris ~132), bukan
 * untuk angka. Kalau baris 2025/2 dibiarkan berdampingan dengan 2026/1, landing
 * page menjumlahkan keduanya (≈753.773 jiwa) sementara labelnya tetap
 * "Semester I 2026" — silent wrong-data. Karena tabel ini praktis snapshot satu
 * periode, replace adalah bentuk yang benar dan sesuai keputusan
 * "SM1/2026 menggantikan SM2/2025".
 *
 * Tanpa `--commit` = DRY-RUN: tidak menulis apa pun, tetapi melaporkan rencana
 * (berapa baris & periode yang akan dihapus, berapa yang akan disisipkan).
 *
 * Pemakaian (parser opsi CI4 hanya paham bentuk spasi):
 *   php spark dukcapil:import                                  # dry-run
 *   php spark dukcapil:import --commit                         # replace ke DB
 *   php spark dukcapil:import --file "path\ke\file.xlsx"       # workbook lain
 *   php spark dukcapil:import --tahun 2026 --semester 1
     *
     * Atribusi sumber data RESMI (baku, jangan diubah — lihat AGENTS.md butir #17):
     *   * Sumber data adalah Data Konsolidasi Bersih (DKB) Semester I Tahun 2026
     * Dicetak di ringkasan (dry-run & --commit) lewat `labelSumber($semester, $tahun)`;
     * semester ditulis Romawi dan tahun mengikuti opsi `--semester`/`--tahun`.
     *
     * Rencana & asumsi A1-A8:
     * docs/superpowers/plans/2026-10-09-import-dukcapil-sm1-2026.md
     */
class DukcapilImport extends BaseCommand
{
    protected $group       = 'Erdekatala';
    protected $name        = 'dukcapil:import';
    protected $description = 'Impor DATA KEPENDUDUKAN KAB TALA *.xlsx ke profil_dukcapil (REPLACE; dry-run tanpa --commit).';
    protected $usage       = 'dukcapil:import [--file <path>] [--tahun <YYYY>] [--semester <1|2>] [--commit]';

    /** Nama sheet yang wajib ada (urutan sesuai file asli). */
    private const SHEET = [
        'kk'        => 'JML KK',
        'kk_gender' => 'JML KK PER JENIS KELAMIN',
        'jiwa'      => 'JML JIWA',
        'jiwa_gender' => 'JML JIWA PER JENIS KELAMIN',
        'usia'      => 'JML JIWA PER USIA',
        'pendidikan' => 'JML JIWA PER PENDIDIKAN',
        'pekerjaan' => 'JML JIWA PER PEKERJAAN',
        'dokumen'   => 'KEPEMILIKAN DOK',
    ];

    /** HARD-ASSERT — harus cocok persis atau impor dibatalkan (exit code 1). */
    private const ASSERT_DESA = 135;
    private const ASSERT_KEC  = 11;
    private const ASSERT_KK   = 132030;
    private const ASSERT_JIWA = 378190;

    /** Kolom data mulai di D untuk ke-8 sheet (A=NO, B=KODE, C=DESA). */
    private const KOLOM_MULAI = 'D';

    /** Wadah nilai per sheet: null = langsung di baris; string = sub-array. */
    private const WADAH = [
        'kk'          => null,
        'kk_gender'   => null,
        'jiwa'        => null,
        'jiwa_gender' => null,
        'usia'        => 'usia',
        'pendidikan'  => 'pendidikan',
        'pekerjaan'   => 'pekerjaan',
        'dokumen'     => 'dokumen',
    ];


    /** Nama kolom data per sheet, urut sesuai kolom Excel. */
    private const KEY = [
        'kk'         => ['kk_total'],
        'kk_gender'  => ['kk_l', 'kk_p', 'kk_total'],
        'jiwa'       => ['jiwa_total'],
        'jiwa_gender' => ['jiwa_l', 'jiwa_p', 'jiwa_total'],
        'usia'       => null,       // diambil dari header
        'pendidikan' => [
            'tidak_blm_sekolah', 'belum_tamat_sd', 'tamat_sd', 'sltp', 'slta',
            'diploma_1_2', 'akademi_d3', 's1_d4', 's2', 's3',
        ],
        'pekerjaan'  => [
            'belum_tidak_bekerja', 'aparatur_pejabat_negara', 'tenaga_pengajar',
            'wiraswasta', 'pertanian_peternakan', 'nelayan', 'agama_kepercayaan',
            'pelajar_mahasiswa', 'tenaga_kesehatan', 'pensiunan', 'lainnya',
        ],
        'dokumen'    => [
            'ktp_el_rekam', 'kartu_keluarga', 'akta_lahir_0_17',
            'kia_0_16', 'akta_perkawinan', 'akta_perceraian',
        ],
    ];
    private DukcapilMapping $map;

    /** @var array<string> Kode Excel tanpa pasangan di m_desa. */
    private array $tanpaPasangan = [];

    /** @var array<string> Kode Excel yang namanya beda dengan m_desa. */
    private array $namaBeda = [];

    /** Semester -> angka Romawi (label resmi memakai Romawi: "Semester I"). */
    private const ROMAWI = [1 => 'I', 2 => 'II'];

    /**
     * Label atribusi sumber data RESMI Dukcapil — satu sumber kebenaran untuk
     * seluruh aplikasi (lihat AGENTS.md butir #17). Kata-katanya BAKU dan tidak
     * boleh diubah; hanya semester (Romawi) & tahun yang mengikuti opsi
     * `--semester`/`--tahun`. Default (1/2026) menghasilkan persis:
     * "* Sumber data adalah Data Konsolidasi Bersih (DKB) Semester I Tahun 2026".
     */
    public static function labelSumber(int $semester, int $tahun): string
    {
        return '* Sumber data adalah ' . self::sumberSingkat($semester, $tahun);
    }

    /** Bentuk ringkas label sumber: "Data Konsolidasi Bersih (DKB) Semester I Tahun 2026". */
    private static function sumberSingkat(int $semester, int $tahun): string
    {
        return 'Data Konsolidasi Bersih (DKB) Semester '
            . (self::ROMAWI[$semester] ?? (string) $semester)
            . ' Tahun ' . $tahun;
    }

    public function run(array $params)
    {
        $this->map = new DukcapilMapping();

        $file     = (string) ($this->opsi('file') ?: (ROOTPATH . 'DATA KEPENDUDUKAN KAB TALA SM I 2026.xlsx'));
        $tahun    = (int) ($this->opsi('tahun') ?: 2026);
        $semester = (int) ($this->opsi('semester') ?: 1);
        $commit   = $this->opsi('commit') !== null; // flag tanpa nilai

        if (!in_array($semester, [1, 2], true)) {
            CLI::error('--semester harus 1 atau 2.');
            return EXIT_ERROR;
        }
        if ($tahun < 2000 || $tahun > 2100) {
            CLI::error('--tahun tidak masuk akal: ' . $tahun);
            return EXIT_ERROR;
        }
        if (!is_file($file)) {
            CLI::error('File tidak ditemukan: ' . $file);
            return EXIT_ERROR;
        }

        CLI::write('IMPOR DATA DUKCAPIL — ' . CLI::color(($commit ? 'MODE COMMIT' : 'MODE DRY-RUN'), $commit ? 'red' : 'yellow'));
        CLI::write('  File     : ' . $file);
        CLI::write('  Target   : semester ' . $semester . ' / tahun ' . $tahun);
        CLI::write('  Sumber   : ' . self::sumberSingkat($semester, $tahun));
        CLI::newLine();

        // --- 1. Baca 8 sheet -------------------------------------------------
        try {
            $baris = $this->bacaWorkbook($file);
        } catch (\Throwable $e) {
            CLI::error('Gagal membaca workbook: ' . $e->getMessage());
            return EXIT_ERROR;
        }

        // --- 2. Hard-assert V1..V5 ------------------------------------------
        $error = $this->validasi($baris);
        if ($error !== []) {
            CLI::newLine();
            foreach ($error as $e) {
                CLI::error('  [GAGAL] ' . $e);
            }
            CLI::newLine();
            CLI::write('  Tidak ada baris ditulis. Impor dibatalkan.', 'light_red');
            return EXIT_ERROR;
        }

        // --- 3. Join m_desa (V6) --------------------------------------------
        $pasangan = $this->joinDesa(array_keys($baris));
        if ($this->tanpaPasangan !== [] || count($pasangan) !== self::ASSERT_DESA) {
            CLI::error('  [GAGAL] Join m_desa tidak penuh: ' . count($pasangan) . '/' . self::ASSERT_DESA
                . '; tanpa pasangan: ' . (empty($this->tanpaPasangan) ? '-' : implode(', ', $this->tanpaPasangan)));
            CLI::write('  Tidak ada baris ditulis. Impor dibatalkan.', 'light_red');
            return EXIT_ERROR;
        }

        // --- 4. Petakan 135 desa (V7 per baris) -----------------------------
        $rows   = [];
        $gagalV7 = [];
        foreach ($pasangan as $kode => $desa) {
            $petakan = $this->map->petakSatuDesa($baris[$kode], $semester, $tahun);

            // Laporkan desa yang namanya beda antara Excel dan m_desa (join
            // tetap by kode, jadi perbedaan ini hanya perlu diteruskan ke admin).
            $namaExcel = strtoupper(trim((string) ($baris[$kode]['nama'] ?? '')));
            $namaDb    = strtoupper(trim((string) $desa['nama_desa']));
            if ($namaExcel !== '' && $namaExcel !== $namaDb) {
                $this->namaBeda[] = $kode . ': Excel "' . $namaExcel . '" vs DB "' . $namaDb . '"';
            }

            $masalah = $this->cekKonsistensiBaris($petakan, $baris[$kode]);
            if ($masalah !== []) {
                $gagalV7[] = $kode . ' (' . $desa['nama_desa'] . '): ' . implode('; ', $masalah);
            }

            $rows[$kode] = array_merge([
                'id_desa'     => (int) $desa['id_desa'],
                'imported_at' => date('Y-m-d H:i:s'),
                'imported_by' => null,
            ], $petakan);
        }

        if ($gagalV7 !== []) {
            CLI::error('  [GAGAL] Konsistensi pasca-pemetaan (V7) — ' . count($gagalV7) . ' desa:');
            foreach (array_slice($gagalV7, 0, 10) as $g) {
                CLI::write('    - ' . $g);
            }
            CLI::write('  Tidak ada baris ditulis. Impor dibatalkan.', 'light_red');
            return EXIT_ERROR;
        }

        // --- 5. Ringkasan ---------------------------------------------------
        $this->ringkas($baris, $rows, $pasangan, $commit, $semester, $tahun);

        // --- 6. Tulis (hanya bila --commit) ---------------------------------
        if (!$commit) {
            CLI::newLine();
            CLI::write('  DRY-RUN — tidak ada baris diubah. Tambahkan --commit untuk REPLACE isi tabel.', 'yellow');
            return EXIT_SUCCESS;
        }

        return $this->tulis($rows, $semester, $tahun);
    }

    /**
     * Baca 8 sheet -> [kode_desa => barisExcel].
     * Pemindaian berdasar POLA KODE di kolom B, bukan nomor baris absolut, supaya
     * tidak patah bila Dukcapil menyisipkan baris di revisi berikutnya.
     */
    private function bacaWorkbook(string $file): array
    {
        $ss = IOFactory::load($file);

        foreach (self::SHEET as $nama) {
            if ($ss->getSheetByName($nama) === null) {
                throw new \RuntimeException('Sheet tidak ditemukan: ' . $nama);
            }
        }

        $gabung = [];

        foreach (self::SHEET as $kunci => $nama) {
            $sh = $ss->getSheetByName($nama);
            $maxKolom = $sh->getHighestColumn();

            foreach ($this->pindai($sh, $maxKolom) as $kode => $sel) {
                if (!isset($gabung[$kode])) {
                    $gabung[$kode] = [
                        'kode' => $kode, 'nama' => $sel['nama'],
                        'kecamatan' => $sel['kecamatan'],
                        'kk_total' => null, 'kk_l' => null, 'kk_p' => null,
                        'jiwa_total' => null, 'jiwa_l' => null, 'jiwa_p' => null,
                        'usia' => [], 'pendidikan' => [], 'pekerjaan' => [], 'dokumen' => [],
                    ];
                }
                if ($gabung[$kode]['nama'] === '' && $sel['nama'] !== '') {
                    $gabung[$kode]['nama'] = $sel['nama'];
                }
                $this->serap($gabung[$kode], $kunci, $sel['nilai']);
            }
        }

        ksort($gabung);

        return $gabung;
    }

    /**
     * Pindai satu sheet: kelompokkan baris per kecamatan (banner
     * `KECAMATAN xxx`), lalu kembalikan baris DESA saja dengan nilai kolom
     * data (mulai kolom D) plus label header untuk sheet usia.
     */
    private function pindai(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sh, string $maxKolom): array
    {
        $out        = [];
        $kecamatan  = '';
        $header     = [];
        $tinggi     = $sh->getHighestRow();
        $awal       = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString(self::KOLOM_MULAI);
        $akhir      = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($maxKolom);

        for ($r = 1; $r <= $tinggi; $r++) {
            $a = $this->teks($sh, 'A', $r);
            $b = $this->teks($sh, 'B', $r);

            if (str_starts_with($a, 'KECAMATAN')) {
                $kecamatan = trim(substr($a, strlen('KECAMATAN')));
                $header    = [];
                continue;
            }

            if ($a === 'NO' && $b === 'KODE') {
                // Per kolom: sub-header (baris r+1) bila terisi, bila kosong
                // (sel merged seperti `JML`) jatuh ke baris header utama.
                $header = $this->bacaHeader($sh, $r, $r + 1, $awal, $akhir);
                continue;
            }

            if (preg_match('/^63\.\d{2}\.\d{2}\.\d{4}$/', $b) !== 1) {
                continue; // total kecamatan / kosong / catatan kaki
            }

            $nilai = [];
            for ($c = $awal; $c <= $akhir; $c++) {
                $idx = $c - $awal;
                $v   = $sh->getCell(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($c) . $r)->getCalculatedValue();
                if (is_numeric($v)) {
                    $v = (int) round((float) $v);
                } else {
                    $v = ($v === null || $v === '') ? 0 : (int) 0;
                }
                if (!isset($header[$idx])) {
                    continue;
                }
                $nilai[$header[$idx]] = $v;
            }

            // Kolom tanpa header (spasi ekor, dsb) dibuang; yang penting nilai
            // terpetakan semua. Simpan juga daftar nilai berurutan untuk sheet usia.
            $out[$b] = [
                'nama'      => $this->teks($sh, 'C', $r),
                'kecamatan' => $kecamatan,
                'nilai'     => $nilai,
                'urutan'    => array_values($nilai),
            ];
        }

        return $out;
    }

    /**
     * Baca label header kolom data (D..max).
     * Untuk tiap kolom dipakai baris sub-header bila terisi, bila kosong
     * (sel merged seperti `JML`) jatuh ke baris header utama.
     */
    private function bacaHeader($sh, int $barisUtama, int $barisSub, int $awal, int $akhir): array
    {
        $out = [];
        for ($c = $awal; $c <= $akhir; $c++) {
            $kol   = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($c);
            $label = $this->teks($sh, $kol, $barisSub);
            if ($label === '') {
                $label = $this->teks($sh, $kol, $barisUtama);
            }
            if ($label !== '') {
                $out[$c - $awal] = $label;
            }
        }

        return $out;
    }

    /**
     * Serap nilai satu sheet ke baris gabungan desa.
     * Pemetaan POSISIONAL: urutan KEY[$kunci] harus sama dengan urutan kolom
     * Excel (kolom D dan seterusnya). Aman terhadap variasi ejaan header.
     *
     * Sheet usia memakai label Excel apa adanya sebagai key (`65-99` ikut
     * terbawa; A1 menangani pemetaannya di DukcapilMapping::USIA_MAP).
     */
    private function serap(array &$baris, string $kunci, array $nilai): void
    {
        if ($kunci === 'usia') {
            foreach ($nilai as $label => $v) {
                $baris['usia'][$label] = $v;
            }
            return;
        }

        $urutan     = array_values($nilai);
        $terpetakan = [];
        foreach (self::KEY[$kunci] as $i => $k) {
            $terpetakan[$k] = $urutan[$i] ?? 0;
        }

        $wadah = self::WADAH[$kunci];
        if ($wadah === null) {
            foreach ($terpetakan as $k => $v) {
                $baris[$k] = $v;
            }
            return;
        }

        foreach ($terpetakan as $k => $v) {
            $baris[$wadah][$k] = $v;
        }
    }

    /** V1..V5 — kembalikan daftar pesan error (kosong = lolos). */
    private function validasi(array $baris): array
    {
        $e = [];

        $n = count($baris);
        if ($n !== self::ASSERT_DESA) {
            $e[] = "V1 jumlah desa: $n (harus " . self::ASSERT_DESA . ')';
        }

        $kec = [];
        $sumKK = 0;
        $sumJiwa = 0;

        foreach ($baris as $kode => $b) {
            $kec[$b['kecamatan']] = true;
            $sumKK   += (int) $b['kk_total'];
            $sumJiwa += (int) $b['jiwa_total'];

            // V5 — konsistensi internal per desa
            if ((int) $b['kk_l'] + (int) $b['kk_p'] !== (int) $b['kk_total']) {
                $e[] = "V5 $kode: kk_l+kk_p (" . ((int) $b['kk_l'] + (int) $b['kk_p']) . ') != kk_total (' . $b['kk_total'] . ')';
            }
            if ((int) $b['jiwa_l'] + (int) $b['jiwa_p'] !== (int) $b['jiwa_total']) {
                $e[] = "V5 $kode: jiwa_l+jiwa_p (" . ((int) $b['jiwa_l'] + (int) $b['jiwa_p']) . ') != jiwa_total (' . $b['jiwa_total'] . ')';
            }

            $sUsia = array_sum(array_map('intval', $b['usia']));
            if ($sUsia !== (int) $b['jiwa_total']) {
                $e[] = "V5 $kode: Sigma usia ($sUsia) != jiwa_total ({$b['jiwa_total']})";
            }
            $sPend = array_sum(array_map('intval', $b['pendidikan']));
            if ($sPend !== (int) $b['jiwa_total']) {
                $e[] = "V5 $kode: Sigma pendidikan ($sPend) != jiwa_total ({$b['jiwa_total']})";
            }
            $sKer = array_sum(array_map('intval', $b['pekerjaan']));
            if ($sKer !== (int) $b['jiwa_total']) {
                $e[] = "V5 $kode: Sigma pekerjaan ($sKer) != jiwa_total ({$b['jiwa_total']})";
            }
        }

        if (count($kec) !== self::ASSERT_KEC) {
            $e[] = 'V4 jumlah kecamatan: ' . count($kec) . ' (harus ' . self::ASSERT_KEC . ')';
        }
        if ($sumKK !== self::ASSERT_KK) {
            $e[] = 'V2 total KK kabupaten: ' . number_format($sumKK) . ' (harus ' . number_format(self::ASSERT_KK) . ')';
        }
        if ($sumJiwa !== self::ASSERT_JIWA) {
            $e[] = 'V3 total jiwa kabupaten: ' . number_format($sumJiwa) . ' (harus ' . number_format(self::ASSERT_JIWA) . ')';
        }

        return $e;
    }

    /** V6 — cocokkan kode Excel dengan m_desa.kode_desa. */
    private function joinDesa(array $kode): array
    {
        $db   = \Config\Database::connect();
        $rows = $db->table('m_desa')
            ->select('m_desa.id_desa, m_desa.nama_desa, m_desa.kode_desa, kecamatan.nama_kecamatan')
            ->join('kecamatan', 'kecamatan.id_kecamatan = m_desa.id_kecamatan')
            ->get()->getResultArray();

        $byKode = [];
        foreach ($rows as $r) {
            $byKode[trim((string) $r['kode_desa'])] = $r;
        }

        $pasangan = [];
        foreach ($kode as $k) {
            if (!isset($byKode[$k])) {
                $this->tanpaPasangan[] = $k;
                continue;
            }
            $pasangan[$k] = $byKode[$k];
        }

        return $pasangan;
    }

    /** V7 — konsistensi pasca-pemetaan untuk satu desa. */
    private function cekKonsistensiBaris(array $p, array $excel): array
    {
        $e = [];

        $sumPir = 0;
        foreach (DukcapilMapping::KOLOM_USIA as $b) {
            $sumPir += (int) $p[$b . '_l'] + (int) $p[$b . '_p'];
        }
        $target = (int) $p['jiwa_l'] + (int) $p['jiwa_p'];
        if ($sumPir !== $target) {
            $e[] = "piramida $sumPir != jiwa $target";
        }

        $sumPend = 0;
        foreach (array_keys(DukcapilMapping::PENDIDIKAN_MAP) as $c) {
            $sumPend += (int) $p[$c];
        }
        $kk = (int) $p['kk_l'] + (int) $p['kk_p'];
        if ($sumPend !== $kk) {
            $e[] = "pendidikan $sumPend != kk_total $kk";
        }

        $sumKer = 0;
        foreach (array_keys(DukcapilMapping::PEKERJAAN_MAP) as $c) {
            $sumKer += (int) $p[$c];
        }
        if ($sumKer !== $kk) {
            $e[] = "pekerjaan $sumKer != kk_total $kk";
        }

        return $e;
    }

    /** Tabel per kecamatan + total + ringkasan resmi vs estimasi. */
    private function ringkas(array $baris, array $rows, array $pasangan, bool $commit, int $semester, int $tahun): void
    {
        // Agregat per kecamatan dari baris HASIL PEMETAAN (yang akan ditulis).
        // $rows sudah berkey kode desa, jadi tidak ada risiko salah urut.
        $perKec = [];
        foreach ($rows as $kode => $r) {
            $kec  = $baris[$kode]['kecamatan'] ?: '(tanpa kecamatan)';
            if (!isset($perKec[$kec])) {
                $perKec[$kec] = ['desa' => 0, 'kk' => 0, 'jiwa' => 0, 'kk_excel' => 0, 'jiwa_excel' => 0];
            }
            $perKec[$kec]['desa']++;
            $perKec[$kec]['kk']   += (int) $r['kk_l'] + (int) $r['kk_p'];
            $perKec[$kec]['jiwa'] += (int) $r['jiwa_l'] + (int) $r['jiwa_p'];
        }
        // Total Excel per kecamatan (dari baris TOTAL kecamatan tidak dipakai;
        // dihitung dari jumlah desa agar perbandingannya apples-to-apples).
        foreach ($baris as $b) {
            $kec = $b['kecamatan'] ?: '(tanpa kecamatan)';
            if (isset($perKec[$kec])) {
                $perKec[$kec]['kk_excel']   += (int) $b['kk_total'];
                $perKec[$kec]['jiwa_excel'] += (int) $b['jiwa_total'];
            }
        }

        $tabel = [];
        $tKK = $tJiwa = $tDesa = 0;
        foreach ($perKec as $kec => $v) {
            $tabel[] = [
                $kec,
                (string) $v['desa'],
                number_format($v['kk']),
                number_format($v['kk_excel']),
                (string) ($v['kk'] - $v['kk_excel']),
                number_format($v['jiwa']),
                number_format($v['jiwa_excel']),
                (string) ($v['jiwa'] - $v['jiwa_excel']),
            ];
            $tDesa += $v['desa'];
            $tKK   += $v['kk'];
            $tJiwa += $v['jiwa'];
        }
        $tabel[] = [
            'TOTAL KABUPATEN', (string) $tDesa,
            number_format($tKK), number_format(self::ASSERT_KK), (string) ($tKK - self::ASSERT_KK),
            number_format($tJiwa), number_format(self::ASSERT_JIWA), (string) ($tJiwa - self::ASSERT_JIWA),
        ];

        CLI::write(CLI::color('  RINGKASAN PER KECAMATAN', 'green'));
        CLI::table($tabel, ['Kecamatan', 'Desa', 'KK (impor)', 'KK (Excel)', 'Δ', 'Jiwa (impor)', 'Jiwa (Excel)', 'Δ']);
        CLI::newLine();

        // Hard-assert (sudah lolos; dicetak sebagai bukti)
        CLI::write(CLI::color('  HARD-ASSERT', 'green'));
        CLI::write('    [OK] V1 jumlah desa        : ' . count($baris) . ' == ' . self::ASSERT_DESA);
        CLI::write('    [OK] V2 total KK kabupaten : ' . number_format($tKK) . ' == ' . number_format(self::ASSERT_KK));
        CLI::write('    [OK] V3 total jiwa kabupaten: ' . number_format($tJiwa) . ' == ' . number_format(self::ASSERT_JIWA));
        CLI::write('    [OK] V4 jumlah kecamatan   : ' . count($perKec) . ' == ' . self::ASSERT_KEC);
        CLI::write('    [OK] V5 konsistensi internal per desa: 135/135 (Sigma usia = Sigma pend = Sigma ker = jiwa; L+P = total)');
        CLI::write('    [OK] V6 join m_desa.kode_desa       : ' . count($pasangan) . '/' . self::ASSERT_DESA . ' cocok');
        CLI::write('    [OK] V7 konsistensi pasca-pemetaan  : 135/135 (piramida = jiwa; Sigma pend = Sigma ker = kk_total)');
        CLI::newLine();

        // Ringkasan kolom resmi vs estimasi (daftar dibungkus biar terbaca).
        $resmi    = $this->map->kolomResmi();
        $estimasi = $this->map->kolomEstimasi();
        CLI::write(CLI::color('  SUMBER KOLOM', 'green'));
        CLI::write('    [DATA EXCEL] ' . count($resmi) . ' kolom (langsung atau lewat transformasi A1-A6):');
        foreach (array_chunk($resmi, 6) as $potong) {
            CLI::write('      ' . implode(', ', $potong));
        }
        CLI::newLine();
        CLI::write(CLI::color('    [ESTIMASI] ' . count($estimasi) . ' kolom — TIDAK ada di Excel, port formula seeder (A7):', 'yellow'));
        foreach (array_chunk($estimasi, 6) as $potong) {
            CLI::write('      ' . implode(', ', $potong));
        }
        CLI::newLine();

        // Total indikator dokumen (6 kolom Excel — semuanya kini disimpan)
        $dok = ['ktp_el_rekam' => 0, 'kartu_keluarga' => 0, 'akta_lahir_0_17' => 0, 'kia_0_16' => 0, 'akta_perkawinan' => 0, 'akta_perceraian' => 0];
        foreach ($baris as $b) {
            foreach ($dok as $k => $_) {
                $dok[$k] += (int) ($b['dokumen'][$k] ?? 0);
            }
        }
        CLI::write(CLI::color('  KEPEMILIKAN DOKUMEN (total kabupaten)', 'green'));
        CLI::write('    KTP-el terekam -> pend_wajib_ktp       : ' . number_format($dok['ktp_el_rekam']));
        CLI::write('    Kartu Keluarga -> kk_punya_kartu_fisik : ' . number_format($dok['kartu_keluarga']));
        CLI::write('    Akta Lahir 0-17 -> pend_punya_akta_lahir: ' . number_format($dok['akta_lahir_0_17']));
        CLI::write('    Akta Perkawinan -> kk_punya_akta_nikah : ' . number_format($dok['akta_perkawinan']));
        CLI::write('    KIA 0-16        -> dok_kia             : ' . number_format($dok['kia_0_16']) . ' (Kelompok A — kini disimpan)');
        CLI::write('    Akta Cerai      -> dok_akta_cerai      : ' . number_format($dok['akta_perceraian']) . ' (Kelompok A — kini disimpan)');
        CLI::newLine();

        // Peringatan
        CLI::write(CLI::color('  ASUMSI & CATATAN', 'yellow'));
        CLI::write('    A1  Header usia `65-99` dipetakan ke u65_69 (typo; belum konfirmasi Dukcapil).');
        CLI::write('    A2  `75+` dipecah 50%/30%/20% ke u75_79 / u80_84 / u85_atas.');
        CLI::write('    A3  Usia Excel gabungan L+P; dipecah dengan rasio gender desa (bukan data).');
        CLI::write('    A4  Pendidikan & pekerjaan basis JIWA diskalakan ke basis KK — BUKAN angka resmi per-KK.');
        CLI::write('    A5  Sheet KEPEMILIKAN DOK potong 30-09-2026; 7 sheet lain Semester I 2026 (30-06).');
        CLI::write('        Rasio cakupan dokumen karena itu tidak se-perioda dengan angka jiwa/KK.');
        CLI::write('    A6  jml_balita / jml_remaja / jml_lansia dihitung dari piramida hasil pemetaan.');
        CLI::write('    A7  ' . count($estimasi) . ' kolom ESTIMASI (JKN/BPJS, KB/PUS, status kawin, hubungan KK).');
        CLI::write('    A8  REPLACE: periode lama dihapus total, hanya periode ' . $semester . '/' . $tahun . ' tersisa.');
        CLI::write('        (getAggregatedStats() menjumlahkan SEMUA baris tanpa filter periode →');
        CLI::write('         periode ganda akan double-count, bukan riwayat.)');
        if ($this->namaBeda !== []) {
            CLI::write('    Nama desa beda antara Excel dan m_desa (join tetap by KODE):');
            foreach ($this->namaBeda as $nb) {
                CLI::write('      - ' . $nb);
            }
            CLI::newLine();
        }
        CLI::newLine();
        CLI::write(CLI::color('  SUMBER DATA RESMI', 'green'));
        CLI::write('    ' . self::labelSumber($semester, $tahun));
        CLI::write('    (Lihat A5: sheet KEPEMILIKAN DOK di workbook terlabel "DKB pertanggal 30092026" —');
        CLI::write('     beda potongan; 7 sheet lain memakai label resmi di atas.)');
        CLI::newLine();

        CLI::write('  Baris siap ditulis: ' . count($rows) . ' — target (id_desa, semester=' . $semester . ', tahun=' . $tahun . ')');

        // Rencana REPLACE: berapa baris & periode yang akan dihapus.
        $db           = \Config\Database::connect();
        $sebelum      = $db->table('profil_dukcapil')
            ->select('tahun, semester, COUNT(*) as c')
            ->groupBy(['tahun', 'semester'])
            ->get()->getResultArray();
        $totalSebelum = (int) $db->table('profil_dukcapil')->countAllResults();

        CLI::write(CLI::color('  RENCANA PENULISAN (replace)', 'green'));
        CLI::write('    Mode       : ' . ($commit ? 'COMMIT — akan menulis' : 'DRY-RUN — tidak menulis apa pun'));
        CLI::write('    Dihapus    : ' . $totalSebelum . ' baris (' . $this->ringkasPeriode($sebelum) . ')');
        CLI::write('    Disisipkan : ' . count($rows) . ' baris periode ' . $semester . '/' . $tahun);
        CLI::write('    Keadaan akhir: tepat ' . count($rows) . ' baris, hanya periode ' . $semester . '/' . $tahun
            . ' → tidak ada double-count.');
        if (!$commit) {
            CLI::write('    ' . CLI::color('Catatan: SM2/2025 TIDAK dipertahankan. Baseline lama sudah tercapture di', 'yellow'));
            CLI::write('    ' . CLI::color('scratch/baseline_dukcapil.json dan bisa diregenerasi via ProfilDukcapilSeeder.', 'yellow'));
        }
    }

    /**
     * REPLACE (bukan append): kosongkan tabel dengan DELETE FROM (bukan TRUNCATE),
     * lalu sisipkan 135 baris periode target. Semua dalam satu transaksi, dan
     * DELETE FROM benar-benar transaksional → gagal = rollback penuh.
     *
     * Kenapa DELETE FROM, bukan truncate()/emptyTable():
     * Di MySQL `TRUNCATE TABLE` adalah DDL yang memicu implicit commit, sehingga
     * TIDAK bisa di-rollback. Bila insertBatch() gagal setelah truncate, tabel
     * tertinggal kosong tanpa pemulihan otomatis. Terukur di DB ini:
     * `DELETE FROM` + transRollback() mengembalikan 135 baris, sedangkan
     * `TRUNCATE` + transRollback() menyisakan 0 baris.
     * Catatan: `$builder->delete()` tanpa where TIDAK bisa dipakai — CI4
     * melempar DatabaseException ("Deletes are not allowed unless they contain a
     * where or like clause"). Karena itu dipakai raw query, tetapi tetap dengan
     * nama tabel dari $this->db->DBPrefix agar aman terhadap prefix.
     *
     * Kenapa harus replace, bukan "tinggalkan SM2/2025 sbg riwayat":
     * PublicController::landingpage() mengambil ANGKA dari
     * ProfilDukcapilModel::getAggregatedStats(), yang menjalankan SUM(kolom)
     * atas SEMUA baris id_desa tanpa filter periode. getLatestPerDesa()/
     * orderBy(...)->first() hanya dipakai untuk LABEL semester/tahun.
     * Jadi baris 2025/2 yang dibiarkan akan ikut ter-SUM → double-count
     * (≈753.773 jiwa) dengan label tetap "Semester I 2026". Tabel ini praktis
     * snapshot satu periode, jadi replace adalah bentuk yang benar.
     */
    private function tulis(array $rows, int $semester, int $tahun): int
    {
        $db = \Config\Database::connect();

        // Keadaan SEBELUM — dipakai untuk laporan & verifikasi.
        $sebelum = $db->table('profil_dukcapil')
            ->select('tahun, semester, COUNT(*) as c')
            ->groupBy(['tahun', 'semester'])
            ->get()->getResultArray();

        $db->transStart();
        $db->query('DELETE FROM ' . $db->protectIdentifiers('profil_dukcapil'));
        $db->table('profil_dukcapil')->insertBatch($rows);
        $db->transComplete();

        if ($db->transStatus() === false) {
            CLI::error('Transaksi gagal; tabel tidak berubah (DELETE FROM bersifat transaksional → rollback utuh).');
            return EXIT_ERROR;
        }

        // --- Verifikasi pasca-commit (wajib) --------------------------------
        $total = (int) $db->table('profil_dukcapil')->countAllResults();

        $periode = $db->table('profil_dukcapil')
            ->select('tahun, semester, COUNT(*) as c')
            ->groupBy(['tahun', 'semester'])
            ->get()->getResultArray();

        $sum = $db->table('profil_dukcapil')
            ->select('SUM(jiwa_l + jiwa_p) as jiwa, SUM(kk_l + kk_p) as kk')
            ->get()->getRowArray();
        $jiwa = (int) ($sum['jiwa'] ?? 0);
        $kk   = (int) ($sum['kk'] ?? 0);

        CLI::newLine();
        CLI::write(CLI::color('  HASIL COMMIT (replace)', 'green'));
        CLI::write('    Dihapus   : ' . $this->ringkasPeriode($sebelum));
        CLI::write('    Disisipkan: ' . count($rows) . ' baris periode ' . $semester . '/' . $tahun);
        CLI::newLine();
        CLI::write('    COUNT(*)              = ' . $total . '   (harus ' . self::ASSERT_DESA . ')');
        CLI::write('    Periode tersisa       = ' . $this->ringkasPeriode($periode));
        CLI::write('    SUM(jiwa_l + jiwa_p)  = ' . number_format($jiwa) . '   (harus ' . number_format(self::ASSERT_JIWA) . ')');
        CLI::write('    SUM(kk_l + kk_p)      = ' . number_format($kk) . '   (harus ' . number_format(self::ASSERT_KK) . ')');

        $hanyaTarget = count($periode) === 1
            && (int) $periode[0]['tahun'] === $tahun
            && (int) $periode[0]['semester'] === $semester;

        $ok = ($total === self::ASSERT_DESA) && $hanyaTarget
            && $jiwa === self::ASSERT_JIWA && $kk === self::ASSERT_KK;

        if (!$ok) {
            CLI::newLine();
            CLI::error('  Verifikasi pasca-commit GAGAL — periksa tabel sebelum dipakai.');
            return EXIT_ERROR;
        }

        CLI::newLine();
        CLI::write('  Verifikasi pasca-commit LOLOS — tidak ada double-count periode.', 'green');
        CLI::newLine();
        CLI::write(CLI::color('  SUMBER DATA RESMI', 'green'));
        CLI::write('    ' . self::labelSumber($semester, $tahun));

        return EXIT_SUCCESS;
    }

    /** Ringkas daftar periode jadi "135 baris 2025/2; 135 baris 2026/1". */
    private function ringkasPeriode(array $rows): string
    {
        if ($rows === []) {
            return '(kosong)';
        }

        $out = [];
        foreach ($rows as $r) {
            $out[] = ((int) $r['c']) . ' baris ' . ((int) $r['semester']) . '/' . ((int) $r['tahun']);
        }

        return implode('; ', $out);
    }

    /** Teks sel yang sudah dirapikan (trim + spasi tunggal). */
    private function teks($sh, string $kolom, int $baris): string
    {
        $v = $sh->getCell($kolom . $baris)->getCalculatedValue();

        return trim((string) preg_replace('/\s+/', ' ', is_scalar($v) ? (string) $v : ''));
    }

    /**
     * Baca opsi CLI secara toleran terhadap dua gaya penulisan — port dari
     * BkkbnWarmup::opsi(). Parser CI4 hanya mengenal `--nama nilai` (spasi);
     * bentuk `--nama=nilai` ditangani manual agar tidak diabaikan diam-diam.
     *
     * @return string|null null = opsi tidak diberikan; '' = flag tanpa nilai.
     */
    private function opsi(string $nama): ?string
    {
        $nilai = CLI::getOption($nama);
        if ($nilai !== null && $nilai !== true) {
            return (string) $nilai;
        }

        foreach (array_merge($_SERVER['argv'] ?? [], CLI::getSegments()) as $arg) {
            if (str_starts_with($arg, '--' . $nama . '=')) {
                return substr($arg, strlen($nama) + 3);
            }
        }

        return ($nilai === true) ? '' : null;
    }
}
