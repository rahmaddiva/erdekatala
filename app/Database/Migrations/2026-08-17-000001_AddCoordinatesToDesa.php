<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddCoordinatesToDesa extends Migration
{
    public function up()
    {
        $this->forge->addColumn('m_desa', [
            'latitude'  => ['type' => 'DECIMAL', 'constraint' => '10,7', 'null' => true, 'after' => 'kode_desa'],
            'longitude' => ['type' => 'DECIMAL', 'constraint' => '10,7', 'null' => true, 'after' => 'latitude'],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('m_desa', ['latitude', 'longitude']);
    }
}
