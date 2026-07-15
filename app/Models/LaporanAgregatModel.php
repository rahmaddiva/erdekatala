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
     * Menghasilkan data untuk tabel rekapitulasi per desa
     */
    public function getSummaryPerDesa($id_kecamatan)
    {
        return $this->select('m_desa.id_desa, m_desa.nama_desa,
                          SUM(jiwa_l + jiwa_p) as total_jiwa,
                          SUM(kk_l + kk_p) as total_kk,
                          SUM(jml_balita) as total_balita,
                          SUM(jml_pus) as total_pus,
                          SUM(jiwa_l) as total_jiwa_l,
                          SUM(jiwa_p) as total_jiwa_p'
        )
            ->join('m_desa', 'm_desa.id_desa = laporan_agregat.id_desa')
            ->where('m_desa.id_kecamatan', $id_kecamatan)
            ->groupBy('m_desa.id_desa')
            ->findAll();
    }

    public function getSummaryPerKecamatan()
    {
        return $this->select('kecamatan.nama_kecamatan,
                          SUM(jiwa_l + jiwa_p) as total_jiwa,
                          SUM(kk_l + kk_p) as total_kk,
                          SUM(jml_balita) as total_balita,
                          SUM(jml_pus) as total_pus,
                          SUM(jiwa_l) as total_jiwa_l,
                          SUM(jiwa_p) as total_jiwa_p'
        )
            ->join('m_desa', 'm_desa.id_desa = laporan_agregat.id_desa')
            ->join('kecamatan', 'kecamatan.id_kecamatan = m_desa.id_kecamatan')
            ->groupBy('kecamatan.id_kecamatan')
            ->findAll();
    }

    /**
     * Aggregasi semua statistik langsung di SQL (menggantikan findAll + PHP loop).
     * Filter opsional: id_kecamatan, id_desa.
     * Return: single row associative array dengan semua SUM.
     */
    public function getAggregatedStats(?int $id_kecamatan = null, ?int $id_desa = null): array
    {
        $builder = $this->builder();
        $builder->select('
            SUM(jiwa_l) as jiwa_l, SUM(jiwa_p) as jiwa_p,
            SUM(kk_l) as kk_l, SUM(kk_p) as kk_p,
            SUM(jml_balita) as jml_balita, SUM(jml_remaja) as jml_remaja,
            SUM(jml_lansia) as jml_lansia, SUM(jml_pus) as jml_pus,
            SUM(kb_aktif) as kb_aktif, SUM(jml_penggunaan_alat_kontrasepsi) as jml_penggunaan_alat_kontrasepsi,
            SUM(pend_bpjs) as pend_bpjs,
            SUM(kk_pend_tidak_sekolah) as kk_pend_tidak_sekolah, SUM(kk_pend_sd) as kk_pend_sd,
            SUM(kk_pend_smp) as kk_pend_smp, SUM(kk_pend_sma) as kk_pend_sma,
            SUM(kk_pend_diploma) as kk_pend_diploma, SUM(kk_pend_s1) as kk_pend_s1,
            SUM(kk_pend_s2_s3) as kk_pend_s2_s3,
            SUM(kk_ker_tani) as kk_ker_tani, SUM(kk_ker_nelayan) as kk_ker_nelayan,
            SUM(kk_ker_pns) as kk_ker_pns, SUM(kk_ker_swasta) as kk_ker_swasta,
            SUM(kk_ker_pedagang) as kk_ker_pedagang, SUM(kk_ker_wiraswasta) as kk_ker_wiraswasta,
            SUM(kk_ker_buruh) as kk_ker_buruh, SUM(kk_ker_tidak_kerja) as kk_ker_tidak_kerja,
            SUM(kk_kawin) as kk_kawin, SUM(kk_belum_kawin) as kk_belum_kawin,
            SUM(kk_cerai_hidup) as kk_cerai_hidup, SUM(kk_cerai_mati) as kk_cerai_mati,
            SUM(pend_wajib_ktp) as pend_wajib_ktp, SUM(pend_punya_akta_lahir) as pend_punya_akta_lahir,
            SUM(kk_punya_akta_nikah) as kk_punya_akta_nikah, SUM(kk_punya_kartu_fisik) as kk_punya_kartu_fisik,
            SUM(kk_belum_punya_kartu_fisik) as kk_belum_punya_kartu_fisik,
            SUM(pus_pbi) as pus_pbi, SUM(pus_non_pbi) as pus_non_pbi, SUM(non_jkn) as non_jkn,
            SUM(u0_4_l) as u0_4_l, SUM(u0_4_p) as u0_4_p,
            SUM(u5_9_l) as u5_9_l, SUM(u5_9_p) as u5_9_p,
            SUM(u10_14_l) as u10_14_l, SUM(u10_14_p) as u10_14_p,
            SUM(u15_19_l) as u15_19_l, SUM(u15_19_p) as u15_19_p,
            SUM(u20_24_l) as u20_24_l, SUM(u20_24_p) as u20_24_p,
            SUM(u25_29_l) as u25_29_l, SUM(u25_29_p) as u25_29_p,
            SUM(u30_34_l) as u30_34_l, SUM(u30_34_p) as u30_34_p,
            SUM(u35_39_l) as u35_39_l, SUM(u35_39_p) as u35_39_p,
            SUM(u40_44_l) as u40_44_l, SUM(u40_44_p) as u40_44_p,
            SUM(u45_49_l) as u45_49_l, SUM(u45_49_p) as u45_49_p,
            SUM(u50_54_l) as u50_54_l, SUM(u50_54_p) as u50_54_p,
            SUM(u55_59_l) as u55_59_l, SUM(u55_59_p) as u55_59_p,
            SUM(u60_64_l) as u60_64_l, SUM(u60_64_p) as u60_64_p,
            SUM(u65_69_l) as u65_69_l, SUM(u65_69_p) as u65_69_p,
            SUM(u70_74_l) as u70_74_l, SUM(u70_74_p) as u70_74_p,
            SUM(u75_79_l) as u75_79_l, SUM(u75_79_p) as u75_79_p,
            SUM(u80_84_l) as u80_84_l, SUM(u80_84_p) as u80_84_p,
            SUM(u85_atas_l) as u85_atas_l, SUM(u85_atas_p) as u85_atas_p
        ');

        if ($id_desa) {
            $builder->where('laporan_agregat.id_desa', $id_desa);
        } elseif ($id_kecamatan) {
            $builder->join('m_desa', 'm_desa.id_desa = laporan_agregat.id_desa');
            $builder->where('m_desa.id_kecamatan', $id_kecamatan);
        }

        $row = $builder->get()->getRowArray();

        // Return zeros jika tidak ada data
        if ($row === null || $row['jiwa_l'] === null) {
            return array_fill_keys(array_keys($this->getAggregatedStatsKeys()), 0);
        }

        // Cast semua ke int
        return array_map('intval', $row);
    }

    /**
     * Helper: daftar key yang direturn getAggregatedStats
     */
    private function getAggregatedStatsKeys(): array
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
}
