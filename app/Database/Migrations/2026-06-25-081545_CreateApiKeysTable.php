<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateApiKeysTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'key_value' => [
                'type'       => 'VARCHAR',
                'constraint' => 64,
                'unique'     => true,
            ],
            'label' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'owner_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'owner_email' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
            ],
            'owner_org' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => true,
            ],
            'daily_limit' => [
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 1000,
            ],
            'requests_today' => [
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 0,
            ],
            'last_reset_date' => [
                'type'    => 'DATE',
                'null'    => true,
            ],
            'total_requests' => [
                'type'       => 'BIGINT',
                'constraint' => 20,
                'default'    => 0,
            ],
            'status' => [
                'type'       => 'ENUM',
                'constraint' => ['active', 'revoked'],
                'default'    => 'active',
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
        $this->forge->addKey('key_value');
        $this->forge->addKey('owner_email');
        $this->forge->createTable('api_keys', true);
    }

    public function down()
    {
        $this->forge->dropTable('api_keys', true);
    }
}
