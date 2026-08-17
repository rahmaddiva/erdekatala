<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateNotifikasi extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'id_user'    => ['type' => 'INT', 'unsigned' => true],
            'judul'      => ['type' => 'VARCHAR', 'constraint' => 100],
            'pesan'      => ['type' => 'VARCHAR', 'constraint' => 255],
            'url'        => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'jenis'      => ['type' => 'VARCHAR', 'constraint' => 30],
            'periode'    => ['type' => 'VARCHAR', 'constraint' => 7],
            'is_read'    => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['id_user', 'jenis', 'periode']);
        $this->forge->createTable('notifikasi');
    }

    public function down()
    {
        $this->forge->dropTable('notifikasi');
    }
}
