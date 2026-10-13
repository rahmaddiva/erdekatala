<?php

namespace App\Models;

use CodeIgniter\Model;

class LaporanAgregatModel extends Model
{
    protected $table = 'laporan_agregat';
    protected $primaryKey = 'id_laporan';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;

    // Semua kolom yang boleh diisi secara massal (mass assignment)
    protected $allowedFields = [
        'id_desa',
        'id_user',
        'bulan',
        'tahun',

        // SHEET 1 & 2
        'jiwa_l',
        'jiwa_p',
        'kk_l',
        'kk_p',

        // SHEET 3: Pendidikan
        'kk_pend_tidak_sekolah',
        'kk_pend_sd',
        'kk_pend_smp',
        'kk_pend_sma',
        'kk_pend_diploma',
        'kk_pend_s1',
        'kk_pend_s2_s3',

        // SHEET 4: Pekerjaan
        'kk_ker_tani',
        'kk_ker_nelayan',
        'kk_ker_pns',
        'kk_ker_swasta',
        'kk_ker_pedagang',
        'kk_ker_wiraswasta',
        'kk_ker_buruh',
        'kk_ker_tidak_kerja',

        // SHEET 5: Piramida Penduduk
        'u0_4_l',
        'u0_4_p',
        'u5_9_l',
        'u5_9_p',
        'u10_14_l',
        'u10_14_p',
        'u15_19_l',
        'u15_19_p',
        'u20_24_l',
        'u20_24_p',
        'u25_29_l',
        'u25_29_p',
        'u30_34_l',
        'u30_34_p',
        'u35_39_l',
        'u35_39_p',
        'u40_44_l',
        'u40_44_p',
        'u45_49_l',
        'u45_49_p',
        'u50_54_l',
        'u50_54_p',
        'u55_59_l',
        'u55_59_p',
        'u60_64_l',
        'u60_64_p',
        'u65_69_l',
        'u65_69_p',
        'u70_74_l',
        'u70_74_p',
        'u75_79_l',
        'u75_79_p',
        'u80_84_l',
        'u80_84_p',
        'u85_atas_l',
        'u85_atas_p',

        // SHEET 6: Kelompok Umur Khusus
        'jml_balita',
        'jml_remaja',
        'jml_lansia',

        // SHEET 7: Status Perkawinan
        'kk_kawin',
        'kk_belum_kawin',
        'kk_cerai_hidup',
        'kk_cerai_mati',

        // SHEET 8 - 15: Dokumen & Kesehatan
        'pend_bpjs',
        'pend_non_bpjs',
        'kk_punya_kartu_fisik',
        'kk_belum_punya_kartu_fisik',
        'penduduk_hub_kepala',
        'penduduk_hub_istri',
        'penduduk_hub_anak',
        'penduduk_hub_lainnya',
        'pus_jkn',
        'pus_pbi',
        'pus_non_pbi',
        'jkn',
        'non_jkn',
        'pend_wajib_ktp',
        'kk_punya_akta_nikah',
        'pend_punya_akta_lahir',

        // SHEET 16 - 20: KB & PUS
        'kb_aktif',
        'jml_pus',
        'jml_penggunaan_alat_kontrasepsi',
    ];

    /**
     * Ambil semua laporan berdasarkan Desa
     */
    public function getRekapByDesa($id_desa)
    {
        return $this->select('laporan_agregat.*')
            ->where('laporan_agregat.id_desa', $id_desa)
            ->findAll();
    }

    public function getRekapByKecamatan($id_kecamatan, $id_desa = null)
    {
        $builder = $this->select('laporan_agregat.*, m_desa.nama_desa')
            ->join('m_desa', 'm_desa.id_desa = laporan_agregat.id_desa')
            ->where('m_desa.id_kecamatan', $id_kecamatan);

        if ($id_desa) {
            $builder->where('laporan_agregat.id_desa', $id_desa);
        }

        return $builder->findAll();
    }

    /**
     * Menghasilkan data untuk tabel rekapitulasi per desa.
     *
     * PERBAIKAN double-count: sumber baris = latestPerDesaSubquery() (1 baris
     * terbaru per desa), sama dengan getAggregatedStats(). Pola bug ini identik
     * dengan getSummaryPerKecamatan() — desa ber-periode ganda terhitung dua
     * kali (DashboardController & ApiController memakai method ini).
     */
    public function getSummaryPerDesa($id_kecamatan)
    {
        $sub = $this->latestPerDesaSubquery();

        return $this->db->query(
            "SELECT m_desa.id_desa, m_desa.nama_desa,
                    SUM(lp.jiwa_l + lp.jiwa_p) as total_jiwa,
                    SUM(lp.kk_l + lp.kk_p)     as total_kk,
                    SUM(lp.jml_balita)         as total_balita,
                    SUM(lp.jml_pus)            as total_pus,
                    SUM(lp.jiwa_l)             as total_jiwa_l,
                    SUM(lp.jiwa_p)             as total_jiwa_p
             FROM {$sub} lp
             JOIN {$this->tableName('m_desa')} m_desa ON m_desa.id_desa = lp.id_desa
             WHERE m_desa.id_kecamatan = ?
             GROUP BY m_desa.id_desa",
            [$id_kecamatan]
        )->getResultArray();
    }

    /**
     * Rekap per kecamatan — dipakai PublicController::buildMapKecamatan().
     *
     * PERBAIKAN double-count: sumber baris = latestPerDesaSubquery() (1 baris
     * terbaru per desa), sumber kebenaran yang SAMA dengan getAggregatedStats().
     * Sebelumnya menjumlahkan semua baris laporan_agregat → desa yang punya dua
     * periode terhitung dua kali (Takisung +2.882, Batu Ampar +2.060).
     */
    public function getSummaryPerKecamatan()
    {
        $sub = $this->latestPerDesaSubquery();

        return $this->db->query(
            "SELECT kecamatan.nama_kecamatan,
                    SUM(lp.jiwa_l + lp.jiwa_p) as total_jiwa,
                    SUM(lp.kk_l + lp.kk_p)     as total_kk,
                    SUM(lp.jml_balita)         as total_balita,
                    SUM(lp.jml_pus)            as total_pus,
                    SUM(lp.jiwa_l)             as total_jiwa_l,
                    SUM(lp.jiwa_p)             as total_jiwa_p
             FROM {$sub} lp
             JOIN {$this->tableName('m_desa')} m_desa ON m_desa.id_desa = lp.id_desa
             JOIN {$this->tableName('kecamatan')} kecamatan ON kecamatan.id_kecamatan = m_desa.id_kecamatan
             GROUP BY kecamatan.id_kecamatan"
        )->getResultArray();
    }

    /**
     * Subquery: SATU baris terpilih per desa — dasar bersama semua agregasi.
     *
     * Latar belakang bug: `laporan_agregat` bisa berisi lebih dari satu periode
     * (kini: 135 baris 12/2025 + 1 baris 7/2026 + 1 baris 8/2026). SUM tanpa
     * filter periode membuat desa yang punya dua periode terhitung DUA KALI:
     * 380.525 padahal nilai satu periode = 375.583 (selisih +4.942).
     *
     * DUA opsi yang dipertimbangkan:
     *   (a) satu periode terbaru global: `WHERE tahun*100+bulan = MAX(...)`.
     *       Deterministik & mudah dijelaskan, TETAPI di data ini hanya berisi
     *       1 desa (8/2026) → Total Jiwa jatuh ke 2.060: 133 desa tiba-tiba
     *       hilang dari total. Terlalu merusak untuk "kabupaten".
     *   (b) satu baris terbaru PER DESA: tiap desa menyumbang laporannya yang
     *       paling baru. Terukur = 375.583 (135 desa, tepat nilai satu periode
     *       12/2025 karena 133 desa memang hanya punya periode itu).
     *
     * DIPILIH: (b). Alasan:
     *   - Kriteria utama terpenuhi persis: **setiap desa menyumbang tepat SATU
     *     periode** → tidak ada lagi double-count, apa pun isi tabelnya.
     *   - Tidak ada desa yang hilang: agregat selalu mencakup semua desa yang
     *     pernah lapor, sehingga cakupan (135/135) konsisten dengan Total Jiwa.
     *     Opsi (a) justru memunculkan ketidak-konsistenan baru itu.
     *   - Deterministik: urutan dipilih dengan (tahun*100 + bulan) DESC, lalu
     *     `MAX(id_laporan)` sebagai tie-break bila satu desa punya dua baris
     *     dalam periode yang sama — hasilnya tidak bergantung pada urutan
     *     insert, urutan fisik tabel, atau mesin DB.
     *   - Opsi (a) adalah kasus khusus (b) saat SEMUA desa sudah lapor di
     *     periode terbaru; jadi (b) otomatis menyatu ke (a) saat data lengkap.
     * Trade-off yang disadari: total bisa memadukan potret dari bulan berbeda
     * (133 desa @12/2025 + 1 @7/2026 + 1 @8/2026). Karena itu label periode
     * TIDAK diklaim sebagai "satu bulan"; getLatestPeriod() melaporkan periode
     * terbaru yang ikut terhitung + `desa_lapor` = jumlah desa yang memang
     * berasal dari periode itu (lihat getAggregationPeriods()).
     *
     * Catatan skema: `tahun` YEAR & `bulan` 1..12 → `tahun*100 + bulan`
     * monotonic terhadap kronologi (202512 < 202607 < 202608).
     *
     * @return string SQL subquery (sudah ber-prefix & ter-escape), alias `lp`.
     */
    private function latestPerDesaSubquery(): string
    {
        $t = $this->tableName('laporan_agregat');

        // Dua tingkat supaya tie-break benar-benar memilih baris DALAM periode
        // terbaru (bukan id terbesar secara global):
        //   mp = periode terbaru per desa
        //   lp = id_laporan terbesar di antara baris periode itu → 1 baris/desa
        // Pemanggil WAJIB memberi alias sendiri, mis. `FROM {$sub} lp`.
        return "(
            SELECT {$t}.*
            FROM {$t}
            JOIN (
                SELECT it.id_desa, MAX(it.id_laporan) AS id_laporan
                FROM {$t} it
                JOIN (
                    SELECT id_desa, MAX(tahun * 100 + bulan) AS periode
                    FROM {$t}
                    GROUP BY id_desa
                ) mp ON mp.id_desa = it.id_desa
                     AND mp.periode = (it.tahun * 100 + it.bulan)
                GROUP BY it.id_desa
            ) lpf ON lpf.id_laporan = {$t}.id_laporan
        )";
    }

    /**
     * Periode-periode yang benar-benar ikut terhitung dalam agregasi, beserta
     * jumlah desa dari masing-masing periode.
     *
     * Urutan: **periode dengan desa terbanyak lebih dulu**, baru periode
     * terbaru sebagai tie-break. Urutan ini yang dipakai getLatestPeriod()
     * untuk LABEL periode, supaya label tidak lagi bertentangan dengan angka
     * yang ditampilkan: sebelumnya label menampilkan 8/2026 (1 desa) padahal
     * Total Jiwa berasal dari 135 desa yang sebagian besar @12/2025.
     * Deterministik: tidak ada dua periode dengan (desa, tahun, bulan) sama.
     *
     * @return list<array{tahun:int,bulan:int,desa:int}>
     */
    public function getAggregationPeriods(): array
    {
        $sub = $this->latestPerDesaSubquery();

        $rows = $this->db->query(
            "SELECT lp.tahun, lp.bulan, COUNT(DISTINCT lp.id_desa) AS desa
             FROM {$sub} lp
             GROUP BY lp.tahun, lp.bulan
             ORDER BY desa DESC, (lp.tahun * 100 + lp.bulan) DESC",
        )->getResultArray();

        return array_map(static fn(array $r): array => [
            'tahun' => (int) $r['tahun'],
            'bulan' => (int) $r['bulan'],
            'desa'  => (int) $r['desa'],
        ], $rows);
    }

    /**
     * Periode agregasi UTAMA: periode yang mewakili desa terbanyak di antara
     * baris terpilih (lihat latestPerDesaSubquery()); tie-break = terbaru.
     * Inilah periode yang dipakai sebagai label, karena periode inilah yang
     * paling mewakili angka agregat yang ditampilkan.
     *
     * @return array{tahun:int,bulan:int,desa:int}|null null bila tidak ada data.
     */
    public function getAggregationPeriod(): ?array
    {
        $semua = $this->getAggregationPeriods();

        return $semua === [] ? null : $semua[0];
    }

    /**
     * Nama tabel siap-pakai untuk raw SQL: prefix konfigurasi (DBPrefix) ikut
     * diterapkan lalu identifier-nya di-escape. Dipakai getCoverage() dan
     * getLatestPeriod() yang masih berbentuk raw SQL.
     */
    private function tableName(string $name): string
    {
        return $this->db->protectIdentifiers($this->db->prefixTable($name));
    }

    /**
     * Cakupan pelaporan: berapa kecamatan/desa yang sudah punya laporan vs total wilayah publik.
     * Dipakai kartu "cakupan" di landing page — satu query, LEFT JOIN + COUNT(DISTINCT CASE).
     * Filter opsional: kalau diisi, cakupan dibatasi ke wilayah itu (kecamatan/desa terpilih).
     *
     * "Sudah lapor" = punya baris TERPILIH menurut latestPerDesaSubquery(),
     * persis definisi yang dipakai getAggregatedStats(). Jadi kartu cakupan
     * (n/m desa) selalu konsisten dengan angka Total Jiwa di sebelahnya.
     */
    public function getCoverage(?int $id_kecamatan = null, ?int $id_desa = null): array
    {
        $params  = [];
        $scope   = 'k.is_public = 1';
        $joinLap = 'la.id_desa = d.id_desa';

        if ($id_kecamatan) {
            $scope .= ' AND d.id_kecamatan = ?';
            $params[] = $id_kecamatan;
        }
        if ($id_desa) {
            $scope .= ' AND d.id_desa = ?';
            $params[] = $id_desa;
        }

        $row = $this->db->query(
            "SELECT
                COUNT(DISTINCT d.id_kecamatan) AS total_kecamatan,
                COUNT(DISTINCT d.id_desa)      AS total_desa,
                COUNT(DISTINCT CASE WHEN la.id_laporan IS NOT NULL THEN d.id_kecamatan END) AS kecamatan_lapor,
                COUNT(DISTINCT CASE WHEN la.id_laporan IS NOT NULL THEN d.id_desa END)      AS desa_lapor
             FROM {$this->tableName('m_desa')} d
             JOIN {$this->tableName('kecamatan')} k ON k.id_kecamatan = d.id_kecamatan
             LEFT JOIN {$this->latestPerDesaSubquery()} la ON {$joinLap}
             WHERE {$scope}",
            $params
        )->getRowArray();

        return [
            'total_kecamatan' => (int) ($row['total_kecamatan'] ?? 0),
            'total_desa'      => (int) ($row['total_desa'] ?? 0),
            'kecamatan_lapor' => (int) ($row['kecamatan_lapor'] ?? 0),
            'desa_lapor'      => (int) ($row['desa_lapor'] ?? 0),
        ];
    }

    /**
     * Periode laporan terbaru beserta jumlah desa yang sudah masuk di periode itu.
     * Dipakai sebagai penanda "Periode laporan terbaru: <bulan> <tahun>" di landing page.
     *
     * PERBAIKAN: definisi diselaraskan dengan getAggregatedStats(). Periode
     * diambil dari baris yang BENAR-BENAR ikut agregasi, dan dipilih periode
     * UTAMA (getAggregationPeriod() = desa terbanyak), bukan sekadar periode
     * kalender terbaru. `desa_lapor` dihitung pada periode itu. Versi lama
     * memakai `ORDER BY tahun DESC, bulan DESC LIMIT 1` → menampilkan 8/2026
     * yang hanya berisi 1 desa, padahal Total Jiwa berasal dari 135 desa yang
     * sebagian besar @12/2025.
     *
     * `desa_total` juga dikembalikan (jumlah desa lintas periode yang
     * diagregasi) supaya selisihnya bisa dijelaskan bila ada.
     *
     * @return array{tahun:int,bulan:int,desa_lapor:int,desa_total:int}|null
     */
    public function getLatestPeriod(): ?array
    {
        $semua = $this->getAggregationPeriods();
        if ($semua === []) {
            return null;
        }

        // Periode utama = desa terbanyak (deterministik, lihat getAggregationPeriods()).
        $utama     = $semua[0];
        $desaTotal = array_sum(array_column($semua, 'desa'));

        // Hitung desa_lapor HANYA pada desa publik & periode itu, konsisten
        // dengan getCoverage() yang memakai filter k.is_public = 1.
        $row = $this->db->query(
            "SELECT COUNT(DISTINCT la.id_desa) AS desa_lapor
             FROM {$this->latestPerDesaSubquery()} la
             JOIN {$this->tableName('m_desa')} d  ON d.id_desa = la.id_desa
             JOIN {$this->tableName('kecamatan')} k ON k.id_kecamatan = d.id_kecamatan
             WHERE k.is_public = 1 AND la.tahun = ? AND la.bulan = ?",
            [$utama['tahun'], $utama['bulan']]
        )->getRowArray();

        return [
            'tahun'      => $utama['tahun'],
            'bulan'      => $utama['bulan'],
            'desa_lapor' => (int) ($row['desa_lapor'] ?? 0),
            'desa_total' => $desaTotal,
        ];
    }

    /**
     * Agregasi semua statistik langsung di SQL (menggantikan findAll + PHP loop).
     * Filter opsional: id_kecamatan, id_desa.
     * Return: single row associative array dengan semua SUM.
     *
     * PERBAIKAN double-count: SUM dijalankan atas latestPerDesaSubquery() —
     * SATU baris terpilih per desa (laporan terbaru tiap desa), bukan atas
     * semua baris. Akibatnya setiap desa menyumbang tepat satu periode:
     * 380.525 → 375.583 (selisih +4.942 dari 2 desa yang terhitung dua kali).
     * Lihat latestPerDesaSubquery() untuk alasan pemilihan opsi (b) ini
     * dibanding opsi (a) "satu periode terbaru global".
     *
     * Kompatibilitas: tanda tangan & kunci return tetap sama seperti sebelum
     * perbaikan (daftar kunci = getAggregatedStatsKeys(), semua int).
     */
    public function getAggregatedStats(?int $id_kecamatan = null, ?int $id_desa = null): array
    {
        $keys = $this->getAggregatedStatsKeys();
        // $keys adalah LIST nama kolom → dipakai langsung sebagai kunci hasil
        // (array_keys() di sini akan menghasilkan 0..N, bukan nama kolom).
        $zero = array_fill_keys($keys, 0);

        // Kolom yang benar-benar ada di tabel: mencegah SUM() atas kolom yang
        // hanya ada di profil_dukcapil (dok_kia, dok_akta_cerai) → DatabaseException.
        $dbCols  = array_flip($this->db->getFieldNames($this->table));
        $sumCols = array_values(array_filter($keys, static fn($c) => isset($dbCols[$c])));
        if ($sumCols === []) {
            return $zero;
        }

        $select = [];
        foreach ($sumCols as $c) {
            // Kolom di-escape; namanya dari whitelist getAggregatedStatsKeys(),
            // bukan dari input pengguna.
            $esc      = $this->db->protectIdentifiers($c);
            $select[] = "SUM({$esc}) as {$esc}";
        }

        // KUNCI PERBAIKAN: sumber baris = 1 baris terpilih per desa (alias lp).
        $sub = $this->latestPerDesaSubquery();
        $sql = 'SELECT ' . implode(', ', $select) . " FROM {$sub} lp";
        $params = [];

        if ($id_desa) {
            $sql .= ' WHERE lp.id_desa = ?';
            $params[] = $id_desa;
        } elseif ($id_kecamatan) {
            // Join ke m_desa di LUAR subquery: filter wilayah tetap berlaku
            // pada desa, sementara pemilihan baris per desa sudah selesai.
            $sql .= ' JOIN ' . $this->tableName('m_desa') . ' d ON d.id_desa = lp.id_desa'
                  . ' WHERE d.id_kecamatan = ?';
            $params[] = $id_kecamatan;
        }

        $row = $this->db->query($sql, $params)->getRowArray();

        // SUM atas 0 baris → semua NULL; kembalikan nol, bukan NULL.
        if ($row === null || ($row[$sumCols[0]] ?? null) === null) {
            return $zero;
        }

        // Cast ke int untuk kolom yang ada; sisanya (Dukcapil-only) tetap 0.
        $out = $zero;
        foreach ($sumCols as $c) {
            $out[$c] = (int) ($row[$c] ?? 0);
        }

        return $out;
    }

    /**
     * Daftar field indikator yang diagregasi untuk peta (snake_case, selain
     * kunci join & field turunan total_kk/total_jiwa). Konsisten dgn library/map.md.
     */
    public static function mapIndicatorFields(): array
    {
        return [
            // Jiwa & KK dasar
            'jiwa_l', 'jiwa_p', 'kk_l', 'kk_p',
            // Kelompok umur
            'jml_balita', 'jml_remaja', 'jml_lansia',
            // Pendidikan (7)
            'kk_pend_tidak_sekolah', 'kk_pend_sd', 'kk_pend_smp', 'kk_pend_sma',
            'kk_pend_diploma', 'kk_pend_s1', 'kk_pend_s2_s3',
            // Pekerjaan (8)
            'kk_ker_tani', 'kk_ker_nelayan', 'kk_ker_pns', 'kk_ker_swasta',
            'kk_ker_pedagang', 'kk_ker_wiraswasta', 'kk_ker_buruh', 'kk_ker_tidak_kerja',
            // Status kawin (4)
            'kk_kawin', 'kk_belum_kawin', 'kk_cerai_hidup', 'kk_cerai_mati',
            // Dokumen (5)
            'pend_wajib_ktp', 'pend_punya_akta_lahir', 'kk_punya_akta_nikah',
            'kk_punya_kartu_fisik', 'kk_belum_punya_kartu_fisik',
            // JKN/BPJS (7)
            'pend_bpjs', 'pend_non_bpjs', 'pus_jkn', 'pus_pbi', 'pus_non_pbi', 'jkn', 'non_jkn',
            // KB & PUS
            'kb_aktif', 'jml_pus', 'jml_penggunaan_alat_kontrasepsi',
        ];
    }

    /**
     * Agregasi per kecamatan untuk peta (1 query GROUP BY, bukan loop N query).
     * Kunci join ke GeoJSON: kd_kecamatan (3 digit via PublicController::kodeKecamatan3).
     * Mengembalikan 1 baris per kecamatan + semua field indikator (SUM, int).
     *
     * PERBAIKAN double-count: sumber baris = latestPerDesaSubquery() (1 baris
     * terbaru per desa), sumber kebenaran yang SAMA dengan getAggregatedStats().
     * Sebelumnya menjumlahkan semua baris → Takisung +2.882 & Batu Ampar +2.060
     * (peta 380.525 vs kartu 375.583). Kunci join (kd_kecamatan) tetap sama.
     */
    public function getMapKecamatan(): array
    {
        $fields = self::mapIndicatorFields();
        $select = ['kecamatan.id_kecamatan', 'kecamatan.kode_kecamatan'];
        foreach ($fields as $f) {
            // Kolom di-escape; namanya dari whitelist mapIndicatorFields().
            $esc      = $this->db->protectIdentifiers($f);
            $select[] = "SUM(lp.{$esc}) AS {$esc}";
        }

        $sub = $this->latestPerDesaSubquery();

        $rows = $this->db->query(
            'SELECT ' . implode(', ', $select) . "
             FROM {$sub} lp
             JOIN {$this->tableName('m_desa')} m_desa ON m_desa.id_desa = lp.id_desa
             JOIN {$this->tableName('kecamatan')} kecamatan ON kecamatan.id_kecamatan = m_desa.id_kecamatan
             GROUP BY kecamatan.id_kecamatan, kecamatan.kode_kecamatan"
        )->getResultArray();

        return array_map(function (array $r) {
            return $this->normalizeMapRow($r, $this->kode3($r['kode_kecamatan'] ?? ''));
        }, $rows);
    }

    /**
     * Agregasi per desa untuk peta (1 query GROUP BY, bukan loop N query).
     * Kunci join ke GeoJSON: kd_kecamatan + kd_kelurahan (dipecah dari m_desa.kode_desa).
     * @param int|null $idKecamatan Bila diset, batasi ke kecamatan tsb.
     *
     * PERBAIKAN double-count: sumber baris = latestPerDesaSubquery() (1 baris
     * terbaru per desa), sumber kebenaran yang SAMA dengan getAggregatedStats().
     * Filter ?id_kecamatan= dan kunci join (kd_kecamatan/kd_kelurahan) tetap sama.
     */
    public function getMapDesa(?int $idKecamatan = null): array
    {
        $fields = self::mapIndicatorFields();
        $select = ['m_desa.id_desa', 'm_desa.kode_desa', 'kecamatan.kode_kecamatan'];
        foreach ($fields as $f) {
            // Kolom di-escape; namanya dari whitelist mapIndicatorFields().
            $esc      = $this->db->protectIdentifiers($f);
            $select[] = "SUM(lp.{$esc}) AS {$esc}";
        }

        $sub     = $this->latestPerDesaSubquery();
        $sql     = 'SELECT ' . implode(', ', $select) . "
             FROM {$sub} lp
             JOIN {$this->tableName('m_desa')} m_desa ON m_desa.id_desa = lp.id_desa
             JOIN {$this->tableName('kecamatan')} kecamatan ON kecamatan.id_kecamatan = m_desa.id_kecamatan";
        $params  = [];

        if ($idKecamatan !== null) {
            $sql .= ' WHERE m_desa.id_kecamatan = ?';
            $params[] = $idKecamatan;
        }

        $sql .= ' GROUP BY m_desa.id_desa, m_desa.kode_desa, kecamatan.kode_kecamatan';

        $rows = $this->db->query($sql, $params)->getResultArray();

        return array_map(function (array $r) {
            $kdKecamatan = $this->kode3($r['kode_kecamatan'] ?? '');
            $kdKelurahan = $this->kodeDesa3($r['kode_desa'] ?? '');
            return $this->normalizeMapRow($r, $kdKecamatan, $kdKelurahan);
        }, $rows);
    }

    /**
     * Ubah "63.01.01" -> "001" (3 digit), konsisten dgn PublicController::kodeKecamatan3.
     */
    private function kode3(string $kode): string
    {
        $parts = explode('.', $kode);
        return str_pad((string) (end($parts) ?: ''), 3, '0', STR_PAD_LEFT);
    }

    /**
     * Pecah m_desa.kode_desa "63.01.01.2001" -> "001" (3 digit terakhir bagian desa),
     * konsisten dgn getDesaStats() public kunci {"kec"."desa"}.
     */
    private function kodeDesa3(string $kode): string
    {
        $parts = explode('.', $kode);
        return substr((string) ($parts[3] ?? ''), -3);
    }

    /**
     * Normalisasi satu baris agregat: buang kolom join mentah, tambah kunci join
     * + total_kk/total_jiwa, cast semua indikator ke int (wilayah tanpa data -> 0).
     */
    private function normalizeMapRow(array $row, string $kdKecamatan, ?string $kdKelurahan = null): array
    {
        $out = ['kd_kecamatan' => $kdKecamatan];
        if ($kdKelurahan !== null) {
            $out['kd_kelurahan'] = $kdKelurahan;
        }

        foreach (self::mapIndicatorFields() as $f) {
            $out[$f] = (int) ($row[$f] ?? 0);
        }

        $out['total_jiwa'] = $out['jiwa_l'] + $out['jiwa_p'];
        $out['total_kk']   = $out['kk_l'] + $out['kk_p'];

        return $out;
    }

    /**
     * Helper: daftar key yang direturn getAggregatedStats
     */
    public function getAggregatedStatsKeys(): array
    {
        return [
            'jiwa_l', 'jiwa_p', 'kk_l', 'kk_p', 'jml_balita', 'jml_remaja', 'jml_lansia',
            'jml_pus', 'kb_aktif', 'jml_penggunaan_alat_kontrasepsi', 'pend_bpjs',
            'kk_pend_tidak_sekolah', 'kk_pend_sd', 'kk_pend_smp', 'kk_pend_sma',
            'kk_pend_diploma', 'kk_pend_s1', 'kk_pend_s2_s3',
            'kk_ker_tani', 'kk_ker_nelayan', 'kk_ker_pns', 'kk_ker_swasta',
            'kk_ker_pedagang', 'kk_ker_wiraswasta', 'kk_ker_buruh', 'kk_ker_tidak_kerja',
            'kk_kawin', 'kk_belum_kawin', 'kk_cerai_hidup', 'kk_cerai_mati',
            'pend_wajib_ktp', 'pend_punya_akta_lahir', 'kk_punya_akta_nikah',
            'kk_punya_kartu_fisik', 'kk_belum_punya_kartu_fisik',
            // Dukcapil-only (Kelompok A) — laporan_agregat tidak punya kolom ini,
            // jadi selalu 0 dari sisi laporan; ada di daftar agar ikut di-SUM
            // saat landing page menggabungkan profil_dukcapil + laporan_agregat.
            'dok_kia', 'dok_akta_cerai',
            'pus_pbi', 'pus_non_pbi', 'non_jkn',
            'u0_4_l', 'u0_4_p', 'u5_9_l', 'u5_9_p', 'u10_14_l', 'u10_14_p',
            'u15_19_l', 'u15_19_p', 'u20_24_l', 'u20_24_p', 'u25_29_l', 'u25_29_p',
            'u30_34_l', 'u30_34_p', 'u35_39_l', 'u35_39_p', 'u40_44_l', 'u40_44_p',
            'u45_49_l', 'u45_49_p', 'u50_54_l', 'u50_54_p', 'u55_59_l', 'u55_59_p',
            'u60_64_l', 'u60_64_p', 'u65_69_l', 'u65_69_p', 'u70_74_l', 'u70_74_p',
            'u75_79_l', 'u75_79_p', 'u80_84_l', 'u80_84_p', 'u85_atas_l', 'u85_atas_p',
        ];
    }

    /**
     * Helper: extract chart arrays dari single aggregated row
     */
    public static function statsToChartData(array $s): array
    {
        $totals = [
            'jiwa_l' => $s['jiwa_l'], 'jiwa_p' => $s['jiwa_p'],
            'kk_l' => $s['kk_l'], 'kk_p' => $s['kk_p'],
            'balita' => $s['jml_balita'], 'remaja' => $s['jml_remaja'],
            'lansia' => $s['jml_lansia'], 'pus' => $s['jml_pus'],
            'kb_aktif' => $s['kb_aktif'], 'alat_kontrasepsi' => $s['jml_penggunaan_alat_kontrasepsi'],
            'bpjs' => $s['pend_bpjs'],
        ];

        $pendidikan = [
            'Tidak Sekolah' => $s['kk_pend_tidak_sekolah'], 'SD' => $s['kk_pend_sd'],
            'SMP' => $s['kk_pend_smp'], 'SMA' => $s['kk_pend_sma'],
            'Diploma' => $s['kk_pend_diploma'], 'S1' => $s['kk_pend_s1'], 'S2/S3' => $s['kk_pend_s2_s3'],
        ];

        $pekerjaan = [
            'Petani' => $s['kk_ker_tani'], 'Nelayan' => $s['kk_ker_nelayan'],
            'PNS' => $s['kk_ker_pns'], 'Swasta' => $s['kk_ker_swasta'],
            'Pedagang' => $s['kk_ker_pedagang'], 'Wiraswasta' => $s['kk_ker_wiraswasta'],
            'Buruh' => $s['kk_ker_buruh'], 'Tidak Bekerja' => $s['kk_ker_tidak_kerja'],
        ];

        $status_kawin = [
            'Kawin' => $s['kk_kawin'], 'Belum Kawin' => $s['kk_belum_kawin'],
            'Cerai Hidup' => $s['kk_cerai_hidup'], 'Cerai Mati' => $s['kk_cerai_mati'],
        ];

        $dokumen = [
            'KTP-el' => $s['pend_wajib_ktp'], 'Akta Lahir' => $s['pend_punya_akta_lahir'],
            'Akta Nikah' => $s['kk_punya_akta_nikah'], 'KK Fisik' => $s['kk_punya_kartu_fisik'],
            // Dukcapil-only (Kelompok A). 0 saat data murni dari laporan desa.
            'KIA' => (int) ($s['dok_kia'] ?? 0),
            'Akta Cerai' => (int) ($s['dok_akta_cerai'] ?? 0),
        ];

        $jkn_bpjs = [
            'PBI' => $s['pus_pbi'], 'Non PBI' => $s['pus_non_pbi'], 'Tidak Punya' => $s['non_jkn'],
        ];

        $grafik = [
            'gender' => ['jiwa_l' => $s['jiwa_l'], 'jiwa_p' => $s['jiwa_p'], 'kk_l' => $s['kk_l'], 'kk_p' => $s['kk_p']],
            'dokumen' => [
                'ktp_elektronik' => $s['pend_wajib_ktp'], 'akta_lahir' => $s['pend_punya_akta_lahir'],
                'akta_nikah' => $s['kk_punya_akta_nikah'], 'kk_fisik' => $s['kk_punya_kartu_fisik'],
                'kk_non_fisik' => $s['kk_belum_punya_kartu_fisik'],
            ],
            'bpjs' => ['pbi' => $s['pus_pbi'], 'non_pbi' => $s['pus_non_pbi'], 'non_jkn' => $s['non_jkn']],
        ];

        $ageLabels = ['0-4','5-9','10-14','15-19','20-24','25-29','30-34','35-39',
                      '40-44','45-49','50-54','55-59','60-64','65-69','70-74','75-79','80-84','85+'];

        $piramidaL = [
            $s['u0_4_l'], $s['u5_9_l'], $s['u10_14_l'], $s['u15_19_l'],
            $s['u20_24_l'], $s['u25_29_l'], $s['u30_34_l'], $s['u35_39_l'],
            $s['u40_44_l'], $s['u45_49_l'], $s['u50_54_l'], $s['u55_59_l'],
            $s['u60_64_l'], $s['u65_69_l'], $s['u70_74_l'], $s['u75_79_l'],
            $s['u80_84_l'], $s['u85_atas_l'],
        ];

        $piramidaP = [
            $s['u0_4_p'], $s['u5_9_p'], $s['u10_14_p'], $s['u15_19_p'],
            $s['u20_24_p'], $s['u25_29_p'], $s['u30_34_p'], $s['u35_39_p'],
            $s['u40_44_p'], $s['u45_49_p'], $s['u50_54_p'], $s['u55_59_p'],
            $s['u60_64_p'], $s['u65_69_p'], $s['u70_74_p'], $s['u75_79_p'],
            $s['u80_84_p'], $s['u85_atas_p'],
        ];

        return compact('totals', 'pendidikan', 'pekerjaan', 'status_kawin', 'dokumen', 'jkn_bpjs', 'grafik', 'ageLabels', 'piramidaL', 'piramidaP');
    }

    /**
     * Status kelengkapan lapor per desa untuk periode bulan/tahun.
     * Return: list desa + flag sudah_lapor, id_laporan, jiwa, kk.
     */
    public function getStatusLaporDesa(int $bulan, int $tahun, ?int $id_kecamatan = null): array
    {
        $db = \Config\Database::connect();
        $builder = $db->table('m_desa d')
            ->select('d.id_desa, d.nama_desa, d.id_kecamatan, k.nama_kecamatan,
                      la.id_laporan, la.jiwa_l, la.jiwa_p, la.kk_l, la.kk_p')
            ->join('kecamatan k', 'k.id_kecamatan = d.id_kecamatan')
            ->join(
                'laporan_agregat la',
                "la.id_desa = d.id_desa AND la.bulan = {$bulan} AND la.tahun = {$tahun}",
                'left'
            )
            ->orderBy('k.nama_kecamatan', 'ASC')
            ->orderBy('d.nama_desa', 'ASC');

        if ($id_kecamatan) {
            $builder->where('d.id_kecamatan', $id_kecamatan);
        }

        $rows = $builder->get()->getResultArray();
        foreach ($rows as &$r) {
            $r['sudah_lapor'] = !empty($r['id_laporan']);
        }
        return $rows;
    }

    /**
     * Deteksi anomali: lonjakan jiwa total > threshold% dibanding periode sebelumnya.
     * Hanya bandingkan desa yang punya data di kedua periode.
     */
    public function getAnomaliJiwa(int $bulan, int $tahun, float $thresholdPct = 20.0, ?int $id_kecamatan = null): array
    {
        $prevBulan = $bulan > 1 ? $bulan - 1 : 12;
        $prevTahun = $bulan > 1 ? $tahun : $tahun - 1;

        $db = \Config\Database::connect();
        $sql = "
            SELECT d.id_desa, d.nama_desa, k.nama_kecamatan,
                   (curr.jiwa_l + curr.jiwa_p) AS jiwa_sekarang,
                   (prev.jiwa_l + prev.jiwa_p) AS jiwa_sebelum,
                   curr.id_laporan
            FROM laporan_agregat curr
            JOIN laporan_agregat prev
              ON prev.id_desa = curr.id_desa
             AND prev.bulan = ? AND prev.tahun = ?
            JOIN m_desa d ON d.id_desa = curr.id_desa
            JOIN kecamatan k ON k.id_kecamatan = d.id_kecamatan
            WHERE curr.bulan = ? AND curr.tahun = ?
              AND (prev.jiwa_l + prev.jiwa_p) > 0
        ";
        $params = [$prevBulan, $prevTahun, $bulan, $tahun];

        if ($id_kecamatan) {
            $sql .= " AND d.id_kecamatan = ?";
            $params[] = $id_kecamatan;
        }

        $rows = $db->query($sql, $params)->getResultArray();
        $anomali = [];
        foreach ($rows as $r) {
            $sebelum = (int) $r['jiwa_sebelum'];
            $sekarang = (int) $r['jiwa_sekarang'];
            $pct = (($sekarang - $sebelum) / $sebelum) * 100;
            if (abs($pct) >= $thresholdPct) {
                $r['persen_perubahan'] = round($pct, 1);
                $r['selisih'] = $sekarang - $sebelum;
                $anomali[] = $r;
            }
        }

        usort($anomali, fn($a, $b) => abs($b['persen_perubahan']) <=> abs($a['persen_perubahan']));
        return $anomali;
    }
}
