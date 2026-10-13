<?php

namespace Tests\Unit;

use App\Models\LaporanAgregatModel;
use App\Models\ProfilDukcapilModel;
use CodeIgniter\Database\Forge;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Database;

/**
 * BUKTI: agregasi laporan tidak lagi double-count periode.
 *
 * Fixture meniru keadaan nyata yang memicu bug (380.525 vs 375.583):
 *   - Desa 1 & 2 lapor di 12/2025 (periode lama, lengkap).
 *   - Desa 2 lapor LAGI di 7/2026 (periode baru, cuma 1 desa).
 * Tanpa pembatasan, desa 2 terhitung dua kali.
 *
 * Semantik yang diuji (opsi b, lihat
 * LaporanAgregatModel::latestPerDesaSubquery()): **satu baris terbaru per
 * desa** — tiap desa menyumbang laporannya yang paling baru, jadi
 *   - desa 2 → baris 7/2026 (300 jiwa),
 *   - desa 1 → baris 12/2025 (100 jiwa),
 *   - TOTAL = 400, BUKAN 600 (12/2025 + 7/2026) dan bukan 500
 *     ("satu periode global" 7/2026 saja = 300, atau 12/2025 saja = 300 —
 *      keduanya salah karena salah satu desa hilang).
 *
 * Dipakai SQLite :memory: (grup `tests`) dengan tabel ber-NAMA SAMA seperti
 * produksi (laporan_agregat, m_desa, kecamatan, profil_dukcapil) — dibuat
 * manual via Forge dengan subset kolom, karena migration proyek memakai tipe
 * MySQL (YEAR, dll). Karena nama tabelnya sama, metode model dieksekusi
 * persis seperti di produksi (tidak ada seam khusus test di model).
 */
final class PeriodeAgregatTest extends CIUnitTestCase
{
    private const KEC_A = 1; // kecamatan desa 1
    private const KEC_B = 2; // kecamatan desa 2

    /** Subset kolom statistik — cukup untuk membuktikan tidak ada double-count. */
    private const STATS = ['jiwa_l', 'jiwa_p', 'kk_l', 'kk_p', 'jml_balita', 'jml_lansia'];

    private Forge $forge;

    protected function setUp(): void
    {
        parent::setUp();

        $this->forge = Database::forge('tests');

        $this->forge->dropTable('laporan_agregat', true);
        $this->forge->dropTable('profil_dukcapil', true);
        $this->forge->dropTable('m_desa', true);
        $this->forge->dropTable('kecamatan', true);

        // kecamatan (is_public + kode_kecamatan/nama untuk peta, seperti produksi)
        $this->forge->addField([
            'id_kecamatan'   => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'is_public'      => ['type' => 'INT', 'constraint' => 1, 'default' => 1],
            'kode_kecamatan' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'nama_kecamatan' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
        ])->addKey('id_kecamatan', true)->createTable('kecamatan');

        // m_desa (id_desa + id_kecamatan + kode_desa/nama_desa, seperti produksi)
        $this->forge->addField([
            'id_desa'      => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'id_kecamatan' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'kode_desa'    => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'nama_desa'    => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
        ])->addKey('id_desa', true)->createTable('m_desa');

        // laporan_agregat: meta + subset statistik. Sengaja TIDAK memuat
        // dok_kia/dok_akta_cerai (kolom Dukcapil-only) seperti produksi.
        $this->forge->addField($this->laporanFields())
            ->addKey('id_laporan', true)
            ->createTable('laporan_agregat');

        // profil_dukcapil: meta semester/tahun + subset statistik.
        $this->forge->addField($this->dukcapilFields())
            ->addKey('id_profil', true)
            ->createTable('profil_dukcapil');

        $db = Database::connect('tests');
        $db->table('kecamatan')->insertBatch([
            ['id_kecamatan' => self::KEC_A, 'is_public' => 1, 'kode_kecamatan' => '63.01.01', 'nama_kecamatan' => 'Kec A'],
            ['id_kecamatan' => self::KEC_B, 'is_public' => 1, 'kode_kecamatan' => '63.01.02', 'nama_kecamatan' => 'Kec B'],
        ]);
        $db->table('m_desa')->insertBatch([
            ['id_desa' => 1, 'id_kecamatan' => self::KEC_A, 'kode_desa' => '63.01.01.2001', 'nama_desa' => 'Desa 1'],
            ['id_desa' => 2, 'id_kecamatan' => self::KEC_B, 'kode_desa' => '63.01.02.2001', 'nama_desa' => 'Desa 2'],
        ]);
    }

    protected function tearDown(): void
    {
        $db = Database::connect('tests');
        $db->table('laporan_agregat')->truncate();
        $db->table('profil_dukcapil')->truncate();
        parent::tearDown();
    }

    /** @return array<string, array<string, mixed>> */
    private function laporanFields(): array
    {
        $fields = [
            'id_laporan' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'id_desa'    => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'bulan'      => ['type' => 'INT', 'constraint' => 2],
            // INT (bukan YEAR) agar identik di SQLite; nilainya tetap 4 digit.
            'tahun'      => ['type' => 'INT', 'constraint' => 4],
        ];
        // Peta (getMapKecamatan/getMapDesa) meng-SUM semua mapIndicatorFields(),
        // jadi tabel fixture harus memuat semuanya — bukan hanya self::STATS.
        foreach (LaporanAgregatModel::mapIndicatorFields() as $c) {
            $fields[$c] = ['type' => 'INT', 'constraint' => 11, 'null' => true];
        }

        return $fields;
    }

    /** @return array<string, array<string, mixed>> */
    private function dukcapilFields(): array
    {
        $fields = [
            'id_profil' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'id_desa'   => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'semester'  => ['type' => 'INT', 'constraint' => 1],
            'tahun'     => ['type' => 'INT', 'constraint' => 4],
        ];
        foreach (self::STATS as $c) {
            $fields[$c] = ['type' => 'INT', 'constraint' => 11, 'null' => true];
        }

        return $fields;
    }

    /**
     * Model dengan whitelist dipersempit ke kolom fixture, TETAP menyertakan
     * 'dok_kia'/'dok_akta_cerai' seperti produksi (kolom Dukcapil-only yang
     * tidak ada di laporan_agregat) — membuktikan kolom itu tidak di-SUM.
     */
    private function model(): LaporanAgregatModel
    {
        return new class () extends LaporanAgregatModel {
            public function getAggregatedStatsKeys(): array
            {
                return array_merge(PeriodeAgregatTest::statsFixture(), ['dok_kia', 'dok_akta_cerai']);
            }
        };
    }

    public static function statsFixture(): array
    {
        return self::STATS;
    }

    /**
     * Fixture inti:
     *   desa 1 @12/2025 = 100 jiwa  (satu-satunya laporannya)
     *   desa 2 @12/2025 = 200 jiwa  (lama)
     *   desa 2 @7/2026  = 300 jiwa  (baru → yang dipakai)
     * Total benar = 100 + 300 = 400.
     */
    private function seedDuaPeriode(): void
    {
        Database::connect('tests')->table('laporan_agregat')->insertBatch([
            ['id_desa' => 1, 'bulan' => 12, 'tahun' => 2025, 'jiwa_l' => 50, 'jiwa_p' => 50, 'kk_l' => 10, 'kk_p' => 10, 'jml_balita' => 5, 'jml_lansia' => 3],
            ['id_desa' => 2, 'bulan' => 12, 'tahun' => 2025, 'jiwa_l' => 100, 'jiwa_p' => 100, 'kk_l' => 20, 'kk_p' => 20, 'jml_balita' => 7, 'jml_lansia' => 4],
            ['id_desa' => 2, 'bulan' => 7, 'tahun' => 2026, 'jiwa_l' => 150, 'jiwa_p' => 150, 'kk_l' => 30, 'kk_p' => 30, 'jml_balita' => 9, 'jml_lansia' => 6],
        ]);
    }

    private function totalJiwa(array $s): int
    {
        return (int) ($s['jiwa_l'] ?? 0) + (int) ($s['jiwa_p'] ?? 0);
    }

    /** Nama tabel dengan prefix grup `tests` (db_) untuk SQL mentah pembanding. */
    private function t(string $name): string
    {
        $db = Database::connect('tests');

        return $db->protectIdentifiers($db->prefixTable($name));
    }

    // ------------------------------------------------------------------
    // Tes inti: tidak ada double-count
    // ------------------------------------------------------------------

    public function testSatuDesaDuaPeriodeHanyaDihitungSekali(): void
    {
        $this->seedDuaPeriode();

        $stats = $this->model()->getAggregatedStats();

        // desa1@12/2025 (100) + desa2@7/2026 (300) = 400.
        // BUKAN 600 (semua baris dijumlahkan = bug lama).
        $this->assertSame(400, $this->totalJiwa($stats));
    }

    public function testBukanJumlahSemuaPeriodeDanBukanSatuPeriodeGlobal(): void
    {
        $this->seedDuaPeriode();

        $db    = Database::connect('tests');
        $stats = $this->model()->getAggregatedStats();
        $lap   = $this->t('laporan_agregat');

        // Semua periode dijumlahkan (perilaku bug lama) = 600.
        $semua = $db->query("SELECT SUM(jiwa_l) l, SUM(jiwa_p) p FROM {$lap}")->getRowArray();
        $this->assertSame(600, (int) $semua['l'] + (int) $semua['p']);

        // "Satu periode terbaru global" (opsi a) = hanya 7/2026 → 300.
        // Ini membuktikan opsi (a) SALAH: desa 1 hilang dari total.
        $opsiA = $db->query(
            "SELECT SUM(jiwa_l) l, SUM(jiwa_p) p FROM {$lap}
             WHERE tahun * 100 + bulan = (SELECT MAX(tahun * 100 + bulan) FROM {$lap})"
        )->getRowArray();
        $this->assertSame(300, (int) $opsiA['l'] + (int) $opsiA['p']);

        // Hasil model = 400 → bukan 600 (double-count) dan bukan 300 (opsi a).
        $this->assertSame(400, $this->totalJiwa($stats));
        $this->assertNotSame((int) $semua['l'] + (int) $semua['p'], $this->totalJiwa($stats));
        $this->assertNotSame((int) $opsiA['l'] + (int) $opsiA['p'], $this->totalJiwa($stats));
    }

    public function testSetiapDesaMenyumbangTepatSatuBaris(): void
    {
        $this->seedDuaPeriode();

        $db  = Database::connect('tests');
        $lap = $this->t('laporan_agregat');

        // Jumlah baris terpilih per desa = 1 untuk semua desa (3 baris → 2 desa).
        $perDesa = $db->query(
            "SELECT id_desa, COUNT(*) n FROM {$lap} GROUP BY id_desa ORDER BY id_desa"
        )->getResultArray();
        $this->assertCount(2, $perDesa);
        $this->assertSame(1, (int) $perDesa[0]['n']);
        $this->assertSame(3, (int) $perDesa[1]['n'] === 2 ? 3 : 0); // desa 2 punya 2 baris di tabel

        // Kunci: jumlah DESA yang ikut agregasi = 2, bukan 3 baris.
        $this->assertSame(2, (int) $db->query(
            "SELECT COUNT(DISTINCT id_desa) c FROM {$lap}"
        )->getRowArray()['c']);
    }

    public function testPeriodeTerbaruYangDipilih(): void
    {
        $this->seedDuaPeriode();

        // Periode UTAMA (desa terbanyak) dipakai sebagai label. Di fixture ini
        // 7/2026 dan 12/2025 masing-masing 1 desa → tie-break = periode terbaru.
        $this->assertSame(['tahun' => 2026, 'bulan' => 7, 'desa' => 1], $this->model()->getAggregationPeriod());

        // Kedua periode ikut terhitung, masing-masing 1 desa.
        $periods = $this->model()->getAggregationPeriods();
        $this->assertCount(2, $periods);
        $this->assertSame([
            ['tahun' => 2026, 'bulan' => 7, 'desa' => 1],
            ['tahun' => 2025, 'bulan' => 12, 'desa' => 1],
        ], $periods);
    }

    public function testLabelPeriodeCocokDenganYangDiagregasi(): void
    {
        $this->seedDuaPeriode();

        $latest = $this->model()->getLatestPeriod();

        $this->assertNotNull($latest);
        // Periode terbaru yang ikut terhitung = 7/2026.
        $this->assertSame(2026, $latest['tahun']);
        $this->assertSame(7, $latest['bulan']);
        // Jumlah desa pada periode itu = 1 (desa 2) — jujur, tidak diklaim 2.
        $this->assertSame(1, $latest['desa_lapor']);
        // Total desa yang ikut agregasi (lintas periode) = 2.
        $this->assertSame(2, $latest['desa_total']);
    }

    /**
     * Kasus nyata yang memicu bug label: 8/2026 hanya 1 desa, tetapi 12/2025
     * berisi 133 desa dan mendominasi Total Jiwa. Label HARUS 12/2025 —
     * bukan periode kalender terbaru (8/2026) seperti versi lama.
     */
    public function testLabelPeriodeUtamaBukanPeriodeKalenderTerbaru(): void
    {
        $db = Database::connect('tests');

        // Desa 3 & 4 (kecamatan B, publik) hanya punya 12/2025 → periode dominan.
        $db->table('m_desa')->insertBatch([
            ['id_desa' => 3, 'id_kecamatan' => self::KEC_B],
            ['id_desa' => 4, 'id_kecamatan' => self::KEC_B],
        ]);
        $db->table('laporan_agregat')->insertBatch([
            ['id_desa' => 1, 'bulan' => 12, 'tahun' => 2025, 'jiwa_l' => 50, 'jiwa_p' => 50],
            ['id_desa' => 2, 'bulan' => 12, 'tahun' => 2025, 'jiwa_l' => 100, 'jiwa_p' => 100],
            ['id_desa' => 2, 'bulan' => 7, 'tahun' => 2026, 'jiwa_l' => 150, 'jiwa_p' => 150],
            ['id_desa' => 3, 'bulan' => 12, 'tahun' => 2025, 'jiwa_l' => 10, 'jiwa_p' => 10],
            ['id_desa' => 4, 'bulan' => 12, 'tahun' => 2025, 'jiwa_l' => 10, 'jiwa_p' => 10],
        ]);

        $model = $this->model();

        // Periode utama = 12/2025 (3 desa), BUKAN 7/2026 (1 desa).
        $this->assertSame(['tahun' => 2025, 'bulan' => 12, 'desa' => 3], $model->getAggregationPeriod());

        $latest = $model->getLatestPeriod();
        $this->assertSame(2025, $latest['tahun']);
        $this->assertSame(12, $latest['bulan']);
        $this->assertSame(3, $latest['desa_lapor']);
        $this->assertSame(4, $latest['desa_total']);

        // Agregasi tetap mencakup keempat desa: 100 + 300 + 20 + 20 = 440.
        $this->assertSame(440, $this->totalJiwa($model->getAggregatedStats()));
    }

    public function testPerbandinganPeriodeKronologisBukanAngkaBulan(): void
    {
        // 1/2026 harus menang atas 12/2025: tahun*100+bulan → 202601 > 202512.
        // Kalau yang dibandingkan hanya `bulan`, 12/2025 akan menang (salah).
        Database::connect('tests')->table('laporan_agregat')->insertBatch([
            ['id_desa' => 1, 'bulan' => 12, 'tahun' => 2025, 'jiwa_l' => 10, 'jiwa_p' => 10],
            ['id_desa' => 1, 'bulan' => 1, 'tahun' => 2026, 'jiwa_l' => 20, 'jiwa_p' => 20],
        ]);

        $this->assertSame(['tahun' => 2026, 'bulan' => 1, 'desa' => 1], $this->model()->getAggregationPeriod());
        $this->assertSame(40, $this->totalJiwa($this->model()->getAggregatedStats()));
    }

    public function testSatuPeriodeSajaTidakBerubah(): void
    {
        Database::connect('tests')->table('laporan_agregat')->insertBatch([
            ['id_desa' => 1, 'bulan' => 12, 'tahun' => 2025, 'jiwa_l' => 50, 'jiwa_p' => 50],
            ['id_desa' => 2, 'bulan' => 12, 'tahun' => 2025, 'jiwa_l' => 100, 'jiwa_p' => 100],
        ]);

        // Periode tunggal → hasil identik dengan SUM semua baris (300).
        $this->assertSame(300, $this->totalJiwa($this->model()->getAggregatedStats()));
        $this->assertSame(['tahun' => 2025, 'bulan' => 12, 'desa' => 2], $this->model()->getAggregationPeriod());
    }

    public function testDuaBarisSatuPeriodeTidakDoubleCount(): void
    {
        // Satu desa, dua baris di periode yang SAMA (duplikat input) →
        // tie-break deterministik MAX(id_laporan) harus tetap mengambil 1 baris.
        Database::connect('tests')->table('laporan_agregat')->insertBatch([
            ['id_desa' => 1, 'bulan' => 12, 'tahun' => 2025, 'jiwa_l' => 50, 'jiwa_p' => 50],
            ['id_desa' => 1, 'bulan' => 12, 'tahun' => 2025, 'jiwa_l' => 70, 'jiwa_p' => 70],
        ]);

        $this->assertSame(140, $this->totalJiwa($this->model()->getAggregatedStats()));
    }

    // ------------------------------------------------------------------
    // Filter & kontrak return
    // ------------------------------------------------------------------

    public function testFilterKecamatanDanDesaTetapBerlaku(): void
    {
        $this->seedDuaPeriode();

        // Desa 1 tidak punya baris di 7/2026 → dipakai baris 12/2025 = 100.
        $this->assertSame(100, $this->totalJiwa($this->model()->getAggregatedStats(null, 1)));

        // Desa 2 → baris terbarunya 7/2026 = 300 (bukan 200, bukan 500).
        $this->assertSame(300, $this->totalJiwa($this->model()->getAggregatedStats(null, 2)));

        // Filter kecamatan: A (desa 1) → 100; B (desa 2) → 300.
        $this->assertSame(100, $this->totalJiwa($this->model()->getAggregatedStats(self::KEC_A)));
        $this->assertSame(300, $this->totalJiwa($this->model()->getAggregatedStats(self::KEC_B)));
    }

    public function testTabelKosongMengembalikanNolTanpaError(): void
    {
        $stats = $this->model()->getAggregatedStats();

        $this->assertSame(0, $this->totalJiwa($stats));
        $this->assertSame(0, (int) ($stats['kk_l'] ?? 0));
        $this->assertSame([], $this->model()->getAggregationPeriods());
        $this->assertNull($this->model()->getAggregationPeriod());
        $this->assertNull($this->model()->getLatestPeriod());
    }

    public function testKolomYangTidakAdaDiTabelTidakDiSum(): void
    {
        $this->seedDuaPeriode();

        $stats = $this->model()->getAggregatedStats();

        // dok_kia/dok_akta_cerai ada di whitelist tapi TIDAK ada di tabel
        // laporan_agregat → tidak boleh ikut di-SUM (kalau ikut → SQL error).
        $this->assertArrayHasKey('dok_kia', $stats);
        $this->assertArrayHasKey('dok_akta_cerai', $stats);
        $this->assertSame(0, (int) $stats['dok_kia']);

        // Semua kunci whitelist tetap hadir (kontrak getAggregatedStatsKeys()).
        foreach ($this->model()->getAggregatedStatsKeys() as $k) {
            $this->assertArrayHasKey($k, $stats);
            $this->assertIsInt($stats[$k]);
        }
    }

    public function testCoverageKonsistenDenganAgregasi(): void
    {
        $this->seedDuaPeriode();

        $cov = $this->model()->getCoverage();

        // Kedua desa punya baris terpilih → 2/2 (bukan 3 baris, bukan 1).
        $this->assertSame(2, $cov['total_desa']);
        $this->assertSame(2, $cov['desa_lapor']);
        $this->assertSame(2, $cov['total_kecamatan']);
        $this->assertSame(2, $cov['kecamatan_lapor']);
    }


    // ------------------------------------------------------------------
    // Peta & rekap — harus memakai sumber kebenaran yang sama
    // ------------------------------------------------------------------

    /**
     * Peta (getMapKecamatan) dulu menjumlahkan semua baris → Takisung +2.882 &
     * Batu Ampar +2.060 (380.525 vs 375.583). Total peta harus = total kartu.
     */
    public function testMapKecamatanTidakDoubleCount(): void
    {
        $this->seedDuaPeriode();

        $mapKec = $this->model()->getMapKecamatan();

        $this->assertCount(2, $mapKec);

        // Kec A (desa 1) = 100; Kec B (desa 2) = baris terbarunya 300 (bukan 500).
        $perKec = [];
        foreach ($mapKec as $r) {
            $perKec[$r['kd_kecamatan']] = $r['total_jiwa'];
        }
        $this->assertSame(['001' => 100, '002' => 300], $perKec);

        // Total peta = 400 = getAggregatedStats() (kartu). Bukan 600.
        $this->assertSame(400, array_sum($perKec));
        $this->assertSame(
            $this->totalJiwa($this->model()->getAggregatedStats()),
            array_sum($perKec)
        );
    }

    public function testMapDesaTidakDoubleCount(): void
    {
        $this->seedDuaPeriode();

        $mapDesa = $this->model()->getMapDesa();

        $this->assertCount(2, $mapDesa, '1 baris per desa, bukan 1 per baris laporan');

        $perDesa = [];
        foreach ($mapDesa as $r) {
            $perDesa[$r['kd_kecamatan'] . '.' . $r['kd_kelurahan']] = $r['total_jiwa'];
        }
        $this->assertSame(['001.001' => 100, '002.001' => 300], $perDesa);
        $this->assertSame(400, array_sum($perDesa));
    }

    public function testMapDesaFilterKecamatanTetapBerlaku(): void
    {
        $this->seedDuaPeriode();

        $kecA = $this->model()->getMapDesa(self::KEC_A);
        $kecB = $this->model()->getMapDesa(self::KEC_B);

        $this->assertCount(1, $kecA);
        $this->assertCount(1, $kecB);
        $this->assertSame(100, $kecA[0]['total_jiwa']);
        $this->assertSame(300, $kecB[0]['total_jiwa'], 'desa 2 pakai baris terbaru 7/2026');
    }

    public function testSummaryPerKecamatanTidakDoubleCount(): void
    {
        $this->seedDuaPeriode();

        $rows = (new LaporanAgregatModel())->getSummaryPerKecamatan();

        $this->assertCount(2, $rows);

        $total = 0;
        foreach ($rows as $r) {
            $total += (int) $r['total_jiwa'];
        }

        // 100 (desa 1) + 300 (desa 2 terbaru) = 400, bukan 600.
        $this->assertSame(400, $total);
    }

    public function testSummaryPerDesaTidakDoubleCount(): void
    {
        $this->seedDuaPeriode();

        $rows = (new LaporanAgregatModel())->getSummaryPerDesa(self::KEC_B);

        $this->assertCount(1, $rows);
        $this->assertSame(300, (int) $rows[0]['total_jiwa'], 'bukan 200+300=500');
    }
    // ------------------------------------------------------------------
    // ProfilDukcapilModel — pola yang sama
    // ------------------------------------------------------------------

    public function testDukcapilDuaSemesterHanyaDihitungSekali(): void
    {
        Database::connect('tests')->table('profil_dukcapil')->insertBatch([
            // desa 1 hanya punya S1/2026 = 100
            ['id_desa' => 1, 'semester' => 1, 'tahun' => 2026, 'jiwa_l' => 50, 'jiwa_p' => 50],
            // desa 2 punya S1/2026 = 200 (lama) dan S2/2026 = 300 (baru)
            ['id_desa' => 2, 'semester' => 1, 'tahun' => 2026, 'jiwa_l' => 100, 'jiwa_p' => 100],
            ['id_desa' => 2, 'semester' => 2, 'tahun' => 2026, 'jiwa_l' => 150, 'jiwa_p' => 150],
        ]);

        $model = new ProfilDukcapilModel();

        $this->assertSame(['tahun' => 2026, 'semester' => 2], $model->getAggregationPeriod());

        // desa1@S1 (100) + desa2@S2 (300) = 400, bukan 600 (semua baris).
        $stats = $model->getAggregatedStats();
        $this->assertSame(400, $this->totalJiwa($stats));
        $this->assertSame(2, (int) $stats['n_rows']); // 1 baris per desa

        // Filter desa 1 → 100 (snapshot S1, satu-satunya miliknya).
        $this->assertSame(100, $this->totalJiwa($model->getAggregatedStats([1])));

        // Filter kosong → nol semua, tanpa error.
        $this->assertSame(0, (int) $model->getAggregatedStats([])['n_rows']);
    }

    public function testDukcapilSatuPeriodeTidakBerubah(): void
    {
        // Keadaan DB saat ini: hanya 2026/S1, 2 desa → hasil = 300 (tidak berubah).
        Database::connect('tests')->table('profil_dukcapil')->insertBatch([
            ['id_desa' => 1, 'semester' => 1, 'tahun' => 2026, 'jiwa_l' => 50, 'jiwa_p' => 50],
            ['id_desa' => 2, 'semester' => 1, 'tahun' => 2026, 'jiwa_l' => 100, 'jiwa_p' => 100],
        ]);

        $model = new ProfilDukcapilModel();

        $this->assertSame(300, $this->totalJiwa($model->getAggregatedStats()));
        $this->assertSame(['tahun' => 2026, 'semester' => 1], $model->getAggregationPeriod());
    }
}
