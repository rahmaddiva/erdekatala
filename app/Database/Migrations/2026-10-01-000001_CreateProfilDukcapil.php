<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Snapshot profil kependudukan resmi Dukcapil per desa.
 * Baseline "data pasti & valid" — operator desa tetap input laporan bulanan
 * di laporan_agregat; landing page memakai profil_dukcapil sebagai fallback
 * untuk desa yang belum lapor (Desain B).
 */
class CreateProfilDukcapil extends Migration
{
    public function up()
    {
        // Daftar kolom statistik identik dengan laporan_agregat
        // (disalin dari 2026-01-11-052202_CreateLaporanAgregat.php:20-101).
        $statsFields = [
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
            'kb_aktif', 'jml_pus', 'jml_penggunaan_alat_kontrasepsi',
        ];

        $fields = [
            'id_profil' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'id_desa'   => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'semester'  => ['type' => 'INT', 'constraint' => 1], // 1 | 2
            'tahun'     => ['type' => 'YEAR'],
            'imported_at' => ['type' => 'DATETIME', 'null' => true],
            'imported_by' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
        ];
        foreach ($statsFields as $f) {
            $fields[$f] = ['type' => 'INT', 'constraint' => 11, 'default' => 0];
        }

        $this->forge->addField($fields);
        $this->forge->addKey('id_profil', true);
        $this->forge->addUniqueKey(['id_desa', 'tahun', 'semester']);
        $this->forge->addForeignKey('id_desa', 'm_desa', 'id_desa', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('imported_by', 'users', 'id_user', 'SET NULL', 'CASCADE');
        $this->forge->createTable('profil_dukcapil');
    }

    public function down()
    {
        $this->forge->dropTable('profil_dukcapil');
    }
}
