<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateApiKeys extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'name' => [
                'type' => 'VARCHAR',
                'constraint' => '100',
                'comment' => 'Nama aplikasi/project',
            ],
            'email' => [
                'type' => 'VARCHAR',
                'constraint' => '150',
                'comment' => 'Email kontak pemohon',
            ],
            'api_key' => [
                'type' => 'VARCHAR',
                'constraint' => '64',
                'unique' => true,
                'comment' => 'Format: erd_[random32hex]',
            ],
            'is_active' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 1,
                'comment' => '1=aktif, 0=nonaktif',
            ],
            'rate_limit' => [
                'type' => 'INT',
                'constraint' => 11,
                'default' => 1000,
                'comment' => 'Max request per hari',
            ],
            'created_by' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => true,
                'comment' => 'NULL=self-service, id_user=admin generate',
            ],
            'last_used_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('created_by', 'users', 'id_user', 'SET NULL', 'CASCADE');
        $this->forge->createTable('api_keys');
    }

    public function down()
    {
        $this->forge->dropTable('api_keys');
    }
}
