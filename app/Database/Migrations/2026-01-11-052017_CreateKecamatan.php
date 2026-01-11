<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateKecamatan extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id_kecamatan' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'nama_kecamatan' => ['type' => 'VARCHAR', 'constraint' => '100'],
            'kode_kecamatan' => ['type' => 'VARCHAR', 'constraint' => '20'],
        ]);
        $this->forge->addKey('id_kecamatan', true);
        $this->forge->createTable('kecamatan');
    }

    public function down()
    {
        $this->forge->dropTable('kecamatan');
    }
}