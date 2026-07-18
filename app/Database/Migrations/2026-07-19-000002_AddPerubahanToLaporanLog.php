<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPerubahanToLaporanLog extends Migration
{
    public function up()
    {
        $this->forge->addColumn('laporan_log', [
            'perubahan' => [
                'type' => 'TEXT',
                'null' => true,
                'after' => 'aksi',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('laporan_log', 'perubahan');
    }
}
