<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateDusunAndRt extends Migration
{
    public function up()
    {
        // Tabel Dusun
        $this->forge->addField([
            'id_dusun' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'id_desa' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'nama_dusun' => ['type' => 'VARCHAR', 'constraint' => '100'],
        ]);
        $this->forge->addKey('id_dusun', true);
        $this->forge->addForeignKey('id_desa', 'm_desa', 'id_desa', 'CASCADE', 'CASCADE');
        $this->forge->createTable('m_dusun');

        // Tabel RT
        $this->forge->addField([
            'id_rt' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'id_dusun' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'no_rt' => ['type' => 'VARCHAR', 'constraint' => '10'],
        ]);
        $this->forge->addKey('id_rt', true);
        $this->forge->addForeignKey('id_dusun', 'm_dusun', 'id_dusun', 'CASCADE', 'CASCADE');
        $this->forge->createTable('m_rt');
    }

    public function down()
    {
        $this->forge->dropTable('m_rt');
        $this->forge->dropTable('m_dusun');
    }
}