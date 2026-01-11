<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateDesa extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id_desa' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'id_kecamatan' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'nama_desa' => ['type' => 'VARCHAR', 'constraint' => '100'],
            'kode_desa' => ['type' => 'VARCHAR', 'constraint' => '50'],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id_desa', true);
        $this->forge->addForeignKey('id_kecamatan', 'kecamatan', 'id_kecamatan', 'CASCADE', 'CASCADE');
        $this->forge->createTable('m_desa');
    }

    public function down()
    {
        $this->forge->dropTable('m_desa');
    }
}