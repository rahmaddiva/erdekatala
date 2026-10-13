<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Snapshot profil kependudukan resmi Dukcapil per desa (semester/tahun).
 * Berfungsi sebagai baseline "data pasti & valid" — landing page memakainya
 * sebagai fallback untuk desa yang belum lapor bulan berjalan (Desain B).
 */
class ProfilDukcapilModel extends Model
{
    protected $table      = 'profil_dukcapil';
    protected $primaryKey = 'id_profil';
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $useTimestamps  = false;

    /**
     * Label sumber data RESMI Dukcapil yang wajib ditampilkan pada seluruh
     * tampilan publik yang angkanya berasal dari snapshot ini (landing page `/`).
     *
     * Format persis sesuai permintaan user (tanda bintang di depan, angka
     * semester Romawi, kapitalisasi) sehingga teks dapat dikutip apa adanya.
     * Bila snapshot berganti, cukup ubah `$semester`/`$tahun` di sini — semua
     * titik tampilan ikut berubah (satu sumber kebenaran).
     */
    public const SUMBER_LABEL_DKB = '* Sumber data adalah Data Konsolidasi Bersih (DKB) Semester I Tahun 2026';

    /**
     * Bangun label DKB dengan semester Romawi + tahun dinamis.
     *
     * Dipertahankan agar pemakaian yang butuh periode tidak meng-hardcode tahun,
     * namun teks tetap identik dengan SUMBER_LABEL_DKB. Bila $semester/$tahun
     * null (tabel kosong), jatuh ke nilai literal.
     */
    public static function sumberLabelDkb(?int $semester = 1, ?int $tahun = 2026): string
    {
        if ($semester === null || $tahun === null) {
            return self::SUMBER_LABEL_DKB;
        }

        return sprintf(
            '* Sumber data adalah Data Konsolidasi Bersih (DKB) Semester %s Tahun %d',
            self::romawiSemester($semester),
            $tahun
        );
    }

    /** Konversi angka semester (1/2) ke Romawi (I/II). */
    private static function romawiSemester(int $semester): string
    {
        return [1 => 'I', 2 => 'II'][$semester] ?? (string) $semester;
    }

    protected $allowedFields = [
        'id_desa', 'semester', 'tahun', 'imported_at', 'imported_by',
        // Statistik — identik dengan LaporanAgregatModel
        'jiwa_l', 'jiwa_p', 'kk_l', 'kk_p',
        'kk_pend_tidak_sekolah', 'kk_pend_sd', 'kk_pend_smp', 'kk_pend_sma',
        'kk_pend_diploma', 'kk_pend_s1', 'kk_pend_s2_s3',
        'kk_ker_tani', 'kk_ker_nelayan', 'kk_ker_pns', 'kk_ker_swasta',
        'kk_ker_pedagang', 'kk_ker_wiraswasta', 'kk_ker_buruh', 'kk_ker_tidak_kerja',
        'u0_4_l', 'u0_4_p', 'u5_9_l', 'u5_9_p',
        'u10_14_l', 'u10_14_p', 'u15_19_l', 'u15_19_p',
        'u20_24_l', 'u20_24_p', 'u25_29_l', 'u25_29_p',
        'u30_34_l', 'u30_34_p', 'u35_39_l', 'u35_39_p',
        'u40_44_l', 'u40_44_p', 'u45_49_l', 'u45_49_p',
        'u50_54_l', 'u50_54_p', 'u55_59_l', 'u55_59_p',
        'u60_64_l', 'u60_64_p', 'u65_69_l', 'u65_69_p',
        'u70_74_l', 'u70_74_p', 'u75_79_l', 'u75_79_p',
        'u80_84_l', 'u80_84_p', 'u85_atas_l', 'u85_atas_p',
        'jml_balita', 'jml_remaja', 'jml_lansia',
        'kk_kawin', 'kk_belum_kawin', 'kk_cerai_hidup', 'kk_cerai_mati',
        'pend_bpjs', 'pend_non_bpjs',
        'kk_punya_kartu_fisik', 'kk_belum_punya_kartu_fisik',
        'penduduk_hub_kepala', 'penduduk_hub_istri', 'penduduk_hub_anak', 'penduduk_hub_lainnya',
        'pus_jkn', 'pus_pbi', 'pus_non_pbi', 'jkn', 'non_jkn',
        'pend_wajib_ktp', 'kk_punya_akta_nikah', 'pend_punya_akta_lahir',
        'dok_kia', 'dok_akta_cerai',
        'kb_aktif', 'jml_pus', 'jml_penggunaan_alat_kontrasepsi',
    ];

    /**
     * Ambil snapshot Dukcapil terbaru per desa (prioritas semester/tahun terbesar).
     * Return: [id_desa => rowArray] — kosong jika tabel kosong.
     */
    public function getLatestPerDesa(): array
    {
        $rows = $this->orderBy('tahun DESC, semester DESC')->findAll();
        $out = [];
        foreach ($rows as $r) {
            $id = (int) $r['id_desa'];
            if (!isset($out[$id])) {
                $out[$id] = $r; // sudah terurut desc, ambil pertama
            }
        }
        return $out;
    }

    /**
     * Nama tabel siap-pakai untuk raw SQL: prefix konfigurasi (DBPrefix)
     * diterapkan lalu identifier-nya di-escape.
     */
    private function tableName(string $name): string
    {
        return $this->db->protectIdentifiers($this->db->prefixTable($name));
    }

    /**
     * Subquery: SATU baris snapshot terbaru per desa — dasar bersama agregasi.
     *
     * Sama persis dengan semantik LaporanAgregatModel::latestPerDesaSubquery()
     * (opsi b: "satu baris terbaru per desa", bukan "satu periode global"):
     *   - setiap desa menyumbang tepat SATU periode → tidak ada double-count;
     *   - tidak ada desa yang hilang dari total;
     *   - deterministik: urutan (tahun*10 + semester) DESC, tie-break
     *     MAX(id_profil) di dalam periode terpilih.
     * Saat ini tabel hanya berisi 2026/S1 (135 baris) sehingga hasilnya identik
     * dengan SUM tanpa filter (378.190) — tetapi kebal terhadap periode ganda
     * di masa depan. Risiko ini sudah dicatat sebagai A8 di DukcapilImport.
     *
     * @return string SQL subquery (ber-prefix & ter-escape), alias `lp`.
     */
    private function latestPerDesaSubquery(): string
    {
        $t = $this->tableName('profil_dukcapil');

        return "(
            SELECT {$t}.*
            FROM {$t}
            JOIN (
                SELECT it.id_desa, MAX(it.id_profil) AS id_profil
                FROM {$t} it
                JOIN (
                    SELECT id_desa, MAX(tahun * 10 + semester) AS periode
                    FROM {$t}
                    GROUP BY id_desa
                ) mp ON mp.id_desa = it.id_desa
                     AND mp.periode = (it.tahun * 10 + it.semester)
                GROUP BY it.id_desa
            ) lpf ON lpf.id_profil = {$t}.id_profil
        )";
    }

    /**
     * Periode snapshot yang benar-benar ikut terhitung, beserta jumlah desa.
     * Diurutkan: periode terbaru dulu, lalu desa terbanyak (deterministik).
     *
     * @return list<array{tahun:int,semester:int,desa:int}>
     */
    public function getAggregationPeriods(): array
    {
        $sub = $this->latestPerDesaSubquery();

        $rows = $this->db->query(
            "SELECT lp.tahun, lp.semester, COUNT(DISTINCT lp.id_desa) AS desa
             FROM {$sub} lp
             GROUP BY lp.tahun, lp.semester
             ORDER BY (lp.tahun * 10 + lp.semester) DESC, desa DESC"
        )->getResultArray();

        return array_map(static fn(array $r): array => [
            'tahun'    => (int) $r['tahun'],
            'semester' => (int) $r['semester'],
            'desa'     => (int) $r['desa'],
        ], $rows);
    }

    /**
     * Periode agregasi utama (snapshot terbaru yang ikut terhitung).
     *
     * @return array{tahun:int,semester:int}|null null bila tabel kosong.
     */
    public function getAggregationPeriod(): ?array
    {
        $semua = $this->getAggregationPeriods();
        if ($semua === []) {
            return null;
        }

        return [
            'tahun'    => $semua[0]['tahun'],
            'semester' => $semua[0]['semester'],
        ];
    }

    /**
     * Agregasi snapshot Dukcapil (SUM semua kolom statistik) — format sama
     * dgn LaporanAgregatModel::getAggregatedStats(). $ids = id_desa filter.
     *
     * PERBAIKAN double-count: SUM dijalankan atas SATU baris terbaru per desa
     * (latestPerDesaSubquery()), bukan atas semua baris. Kalau nanti tabel
     * berisi lebih dari satu semester (mis. S1/2026 dan S2/2026), tanpa ini
     * setiap desa terhitung dua kali — persis bug laporan_agregat
     * (380.525 vs 375.583). Selain itu, kolom yang tidak ada di tabel tidak
     * ikut di-SUM (allowedFields bisa lebih maju dari skema).
     */
    public function getAggregatedStats(?array $ids = null): array
    {
        // Kolom statistik = allowedFields minus kolom meta, dibatasi ke kolom
        // yang benar-benar ada di tabel.
        $meta       = ['id_desa', 'semester', 'tahun', 'imported_at', 'imported_by'];
        $candidates = array_values(array_diff($this->allowedFields, $meta));
        $dbCols     = array_flip($this->db->getFieldNames($this->table));
        $stats      = array_values(array_filter($candidates, static fn($c) => isset($dbCols[$c])));
        $zero       = ['n_rows' => 0] + array_fill_keys($stats, 0);

        if ($ids === []) {
            // Filter kosong → tidak ada data
            return $zero;
        }

        $select = [];
        foreach ($stats as $c) {
            $esc      = $this->db->protectIdentifiers($c);
            $select[] = "SUM({$esc}) as {$esc}";
        }
        if ($select === []) {
            return $zero;
        }

        // KUNCI PERBAIKAN: sumber baris = 1 snapshot terpilih per desa (alias lp).
        $sql    = 'SELECT ' . implode(', ', $select) . ', COUNT(*) as n_rows'
                . ' FROM ' . $this->latestPerDesaSubquery() . ' lp';
        $params = [];

        if ($ids !== null) {
            // Placeholder dibuat manual: raw query CI4 tidak mengikat array
            // untuk klausa IN. Nilai tetap ter-bound (bukan disisipkan ke SQL).
            $ids = array_values(array_map('intval', $ids));
            if ($ids === []) {
                return $zero;
            }
            $sql .= ' WHERE lp.id_desa IN (' . implode(',', array_fill(0, count($ids), '?')) . ')';
            $params = $ids;
        }

        $row = $this->db->query($sql, $params)->getRowArray();
        if ($row === null || (int) $row['n_rows'] === 0) {
            return $zero;
        }

        $out = ['n_rows' => (int) $row['n_rows']];
        foreach ($stats as $c) {
            $out[$c] = (int) ($row[$c] ?? 0);
        }

        return $out;
    }

    /**
     * Agregasi snapshot Dukcapil per DESA untuk peta tematik — bentuk baris
     * IDENTIK LaporanAgregatModel::getMapDesa() (kunci join `kd_kecamatan`
     * [3 digit] + `kd_kelurahan` [3 digit], seluruh field indikator peta, plus
     * turunan `total_jiwa`/`total_kk`).
     *
     * Tujuan: peta landing page harus menampilkan angka dari SUMBER yang sama
     * dengan kartu profil. Karena default kini Dukcapil, peta memakai agregasi
     * ini (bukan laporan_agregat) agar tidak ada inkonsistensi kartu vs peta.
     *
     * Sumber baris = latestPerDesaSubquery() — SATU snapshot terbaru per desa,
     * sehingga tidak ada double-count dan tidak ada desa yang hilang. Logika
     * subquery TIDAK diubah; method ini hanya membungkusnya dengan GROUP BY.
     *
     * @param int|null $idKecamatan Bila diset, batasi ke kecamatan tsb.
     * @return list<array<string,int|string>>
     */
    public function getMapDesa(?int $idKecamatan = null): array
    {
        $fields = LaporanAgregatModel::mapIndicatorFields();
        $select = ['m_desa.id_desa', 'm_desa.kode_desa', 'kecamatan.kode_kecamatan'];
        foreach ($fields as $f) {
            // Kolom di-escape; namanya dari whitelist mapIndicatorFields().
            $esc      = $this->db->protectIdentifiers($f);
            $select[] = "SUM(lp.{$esc}) AS {$esc}";
        }

        $sub    = $this->latestPerDesaSubquery();
        $sql    = 'SELECT ' . implode(', ', $select) . "
             FROM {$sub} lp
             JOIN {$this->tableName('m_desa')} m_desa ON m_desa.id_desa = lp.id_desa
             JOIN {$this->tableName('kecamatan')} kecamatan ON kecamatan.id_kecamatan = m_desa.id_kecamatan";
        $params = [];

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
     * Agregasi snapshot Dukcapil per KECAMATAN untuk peta tematik — 1 query
     * GROUP BY, bentuk baris identik LaporanAgregatModel::getMapKecamatan().
     *
     * @param int|null $idKecamatan Bila diset, batasi ke kecamatan tsb.
     * @return list<array<string,int|string>>
     */
    public function getMapKecamatan(?int $idKecamatan = null): array
    {
        $fields = LaporanAgregatModel::mapIndicatorFields();
        $select = ['kecamatan.id_kecamatan', 'kecamatan.kode_kecamatan'];
        foreach ($fields as $f) {
            $esc      = $this->db->protectIdentifiers($f);
            $select[] = "SUM(lp.{$esc}) AS {$esc}";
        }

        $sub    = $this->latestPerDesaSubquery();
        $sql    = 'SELECT ' . implode(', ', $select) . "
             FROM {$sub} lp
             JOIN {$this->tableName('m_desa')} m_desa ON m_desa.id_desa = lp.id_desa
             JOIN {$this->tableName('kecamatan')} kecamatan ON kecamatan.id_kecamatan = m_desa.id_kecamatan";
        $params = [];

        if ($idKecamatan !== null) {
            $sql .= ' WHERE kecamatan.id_kecamatan = ?';
            $params[] = $idKecamatan;
        }

        $sql .= ' GROUP BY kecamatan.id_kecamatan, kecamatan.kode_kecamatan';

        $rows = $this->db->query($sql, $params)->getResultArray();

        return array_map(function (array $r) {
            return $this->normalizeMapRow($r, $this->kode3($r['kode_kecamatan'] ?? ''));
        }, $rows);
    }

    /**
     * Normalisasi satu baris agregat peta: buang kolom join mentah, tambah
     * kunci join + total_kk/total_jiwa, cast semua indikator ke int.
     * Salinan semantik LaporanAgregatModel::normalizeMapRow() (private di sana).
     */
    private function normalizeMapRow(array $row, string $kdKecamatan, ?string $kdKelurahan = null): array
    {
        $out = ['kd_kecamatan' => $kdKecamatan];
        if ($kdKelurahan !== null) {
            $out['kd_kelurahan'] = $kdKelurahan;
        }

        foreach (LaporanAgregatModel::mapIndicatorFields() as $f) {
            $out[$f] = (int) ($row[$f] ?? 0);
        }

        $out['total_jiwa'] = $out['jiwa_l'] + $out['jiwa_p'];
        $out['total_kk']   = $out['kk_l'] + $out['kk_p'];

        return $out;
    }

    /** "63.01.01" -> "001" (3 digit), konsisten dgn LaporanAgregatModel::kode3(). */
    private function kode3(string $kode): string
    {
        $parts = explode('.', $kode);
        return str_pad((string) (end($parts) ?: ''), 3, '0', STR_PAD_LEFT);
    }

    /** Pecah m_desa.kode_desa "63.01.01.2001" -> "001" (3 digit terakhir bagian desa). */
    private function kodeDesa3(string $kode): string
    {
        $parts = explode('.', $kode);
        return substr((string) ($parts[3] ?? ''), -3);
    }
}
