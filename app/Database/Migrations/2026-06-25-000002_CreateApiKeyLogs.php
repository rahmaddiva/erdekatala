<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateApiKeyLogs extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'constraint'     => 20,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'api_key_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'endpoint' => [
                'type'       => 'VARCHAR',
                'constraint' => '100',
            ],
            'ip_address' => [
                'type'       => 'VARCHAR',
                'constraint' => '45',
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey(['api_key_id', 'created_at']); // index untuk rate limit query
        $this->forge->addForeignKey('api_key_id', 'api_keys', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('api_key_logs');
    }

    public function down()
    {
        $this->forge->dropTable('api_key_logs');
    }
}
