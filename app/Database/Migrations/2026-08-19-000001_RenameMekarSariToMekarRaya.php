<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Rename desa "Mekar Sari" -> "Mekar Raya" (Kintap, kode 63.01.07.2013)
 * agar sinkron dengan nama resmi BPS (GeoJSON kelurahan).
 */
class RenameMekarSariToMekarRaya extends Migration
{
    public function up()
    {
        $this->db->table('m_desa')
            ->where('kode_desa', '63.01.07.2013')
            ->update(['nama_desa' => 'Mekar Raya']);
    }

    public function down()
    {
        $this->db->table('m_desa')
            ->where('kode_desa', '63.01.07.2013')
            ->update(['nama_desa' => 'Mekar Sari']);
    }
}
