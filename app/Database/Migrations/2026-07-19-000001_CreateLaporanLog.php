<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateLaporanLog extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'          => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'id_laporan'  => ['type' => 'INT', 'unsigned' => true],
            'id_user'     => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'aksi'        => ['type' => 'ENUM', 'constraint' => ['create', 'update', 'delete']],
            'created_at'  => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('id_laporan');
        $this->forge->createTable('laporan_log');
    }

    public function down()
    {
        $this->forge->dropTable('laporan_log');
    }
}
