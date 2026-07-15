<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * ponytail: adds missing indexes for common query patterns
 * - (tahun, bulan) on laporan_agregat — used by DataTables filter & export
 * - remember_token on users — used by LoggedIn filter on every request
 */
class AddPerformanceIndexes extends Migration
{
    public function up()
    {
        // Composite index for period-based queries
        $this->forge->addKey(['tahun', 'bulan'], false, false, 'idx_laporan_tahun_bulan');
        // Can't use addKey after table creation, use raw SQL
        $this->db->query('CREATE INDEX idx_laporan_tahun_bulan ON laporan_agregat (tahun, bulan)');
        $this->db->query('CREATE INDEX idx_users_remember_token ON users (remember_token)');
    }

    public function down()
    {
        $this->db->query('DROP INDEX idx_laporan_tahun_bulan ON laporan_agregat');
        $this->db->query('DROP INDEX idx_users_remember_token ON users');
    }
}
