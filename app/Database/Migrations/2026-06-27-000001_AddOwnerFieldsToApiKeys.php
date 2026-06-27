<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddOwnerFieldsToApiKeys extends Migration
{
    public function up()
    {
        $this->forge->addColumn('api_keys', [
            'owner_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
                'after'      => 'name',
                'comment'    => 'Nama pemohon (registrasi publik)',
            ],
            'owner_org' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => true,
                'after'      => 'owner_name',
                'comment'    => 'Instansi/organisasi pemohon',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('api_keys', 'owner_name');
        $this->forge->dropColumn('api_keys', 'owner_org');
    }
}
