<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AlterLaporanAgregatIdRtToIdDesa extends Migration
{
    public function up()
    {
        // ponytail: idempotent — tabel mungkin sudah dimigrasi manual
        $fields = array_column($this->db->query('SHOW COLUMNS FROM laporan_agregat')->getResultArray(), 'Field');

        if (in_array('id_rt', $fields)) {
            $keys = array_column($this->db->query("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_NAME='laporan_agregat' AND COLUMN_NAME='id_rt' AND TABLE_SCHEMA=DATABASE()")->getResultArray(), 'CONSTRAINT_NAME');
            foreach ($keys as $k) {
                if ($k !== 'PRIMARY') $this->db->query("ALTER TABLE laporan_agregat DROP FOREIGN KEY `$k`");
            }
            $this->db->query('ALTER TABLE laporan_agregat DROP COLUMN id_rt');
        }

        if (!in_array('id_desa', $fields)) {
            $this->db->query('ALTER TABLE laporan_agregat ADD COLUMN id_desa INT UNSIGNED NOT NULL AFTER id_user');
            $this->db->query('ALTER TABLE laporan_agregat ADD CONSTRAINT laporan_agregat_desa_fk FOREIGN KEY (id_desa) REFERENCES m_desa(id_desa) ON DELETE CASCADE');
            $this->db->query('ALTER TABLE laporan_agregat ADD UNIQUE KEY unique_desa_periode (id_desa, bulan, tahun)');
        }
    }

    public function down()
    {
        $this->db->query('ALTER TABLE laporan_agregat DROP FOREIGN KEY laporan_agregat_desa_fk');
        $this->db->query('ALTER TABLE laporan_agregat DROP INDEX unique_desa_periode');
        $this->db->query('ALTER TABLE laporan_agregat DROP COLUMN id_desa');
        $this->db->query('ALTER TABLE laporan_agregat ADD COLUMN id_rt INT UNSIGNED NOT NULL AFTER id_user');
        $this->db->query('ALTER TABLE laporan_agregat ADD CONSTRAINT laporan_agregat_ibfk_1 FOREIGN KEY (id_rt) REFERENCES m_rt(id_rt) ON DELETE CASCADE');
    }
}
