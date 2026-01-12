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
        'id_rt',
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
     * Digunakan oleh: Admin Desa
     */
    public function getRekapByDesa($id_desa)
    {
        return $this->select('laporan_agregat.*, m_rt.no_rt, m_dusun.nama_dusun')
            ->join('m_rt', 'm_rt.id_rt = laporan_agregat.id_rt')
            ->join('m_dusun', 'm_dusun.id_dusun = m_rt.id_dusun')
            ->where('m_dusun.id_desa', $id_desa)
            ->findAll();
    }
    /**
     * Ambil semua laporan berdasarkan Kecamatan
     * Digunakan oleh: Admin Kecamatan
     */
    public function getRekapByKecamatan($id_kecamatan, $id_desa = null)
    {
        $builder = $this->select('laporan_agregat.*, m_desa.nama_desa, m_dusun.nama_dusun , m_rt.no_rt')
            ->join('m_rt', 'm_rt.id_rt = laporan_agregat.id_rt')
            ->join('m_dusun', 'm_dusun.id_dusun = m_rt.id_dusun')
            ->join('m_desa', 'm_desa.id_desa = m_dusun.id_desa')
            ->where('m_desa.id_kecamatan', $id_kecamatan);

        // Jika admin memilih desa tertentu
        if ($id_desa) {
            $builder->where('m_desa.id_desa', $id_desa);
        }

        return $builder->findAll();
    }

    /**
     * Menghasilkan data untuk tabel rekapitulasi per desa
     */
    public function getSummaryPerDesa($id_kecamatan)
    {
        return $this->select('m_desa.nama_desa, 
                          SUM(jiwa_l + jiwa_p) as total_jiwa, 
                          SUM(kk_l + kk_p) as total_kk,
                          SUM(jml_balita) as total_balita,
                          SUM(jml_pus) as total_pus,
                          SUM(jiwa_l) as total_jiwa_l, 
                          SUM(jiwa_p) as total_jiwa_p'
        )
            ->join('m_rt', 'm_rt.id_rt = laporan_agregat.id_rt')
            ->join('m_dusun', 'm_dusun.id_dusun = m_rt.id_dusun')
            ->join('m_desa', 'm_desa.id_desa = m_dusun.id_desa')
            ->where('m_desa.id_kecamatan', $id_kecamatan)
            ->groupBy('m_desa.id_desa')
            ->findAll();
    }

    public function getSummaryPerDusun($id_desa)
    {
        return $this->select('m_dusun.nama_dusun, 
                          SUM(jiwa_l + jiwa_p) as total_jiwa, 
                          SUM(kk_l + kk_p) as total_kk,
                          SUM(jml_balita) as total_balita,
                          SUM(jml_pus) as total_pus,
                          SUM(jiwa_l) as total_jiwa_l, 
                          SUM(jiwa_p) as total_jiwa_p'
        )
            ->join('m_rt', 'm_rt.id_rt = laporan_agregat.id_rt')
            ->join('m_dusun', 'm_dusun.id_dusun = m_rt.id_dusun')
            ->where('m_dusun.id_desa', $id_desa)
            ->groupBy('m_dusun.id_dusun')
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
            ->join('m_rt', 'm_rt.id_rt = laporan_agregat.id_rt')
            ->join('m_dusun', 'm_dusun.id_dusun = m_rt.id_dusun')
            ->join('m_desa', 'm_desa.id_desa = m_dusun.id_desa')
            ->join('kecamatan', 'kecamatan.id_kecamatan = m_desa.id_kecamatan')
            ->groupBy('kecamatan.id_kecamatan')
            ->findAll();
    }
}