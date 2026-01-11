<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateUsers extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id_user' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'username' => ['type' => 'VARCHAR', 'constraint' => '100', 'unique' => true],
            'password' => ['type' => 'VARCHAR', 'constraint' => '255'],
            'nama_lengkap' => ['type' => 'VARCHAR', 'constraint' => '255'],
            'role' => ['type' => 'ENUM', 'constraint' => ['admin_dinas', 'admin_kecamatan', 'admin_desa']],
            'id_kecamatan' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'id_desa' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id_user', true);
        $this->forge->addForeignKey('id_kecamatan', 'kecamatan', 'id_kecamatan', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('id_desa', 'm_desa', 'id_desa', 'SET NULL', 'CASCADE');
        $this->forge->createTable('users');
    }

    public function down()
    {
        $this->forge->dropTable('users');
    }
}