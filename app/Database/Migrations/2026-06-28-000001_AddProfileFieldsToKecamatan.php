<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddProfileFieldsToKecamatan extends Migration
{
    public function up()
    {
        $this->forge->addColumn('kecamatan', [
            'slug'             => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true, 'after' => 'kode_kecamatan'],
            'page_title'       => ['type' => 'VARCHAR', 'constraint' => 200, 'null' => true, 'after' => 'slug'],
            'meta_description' => ['type' => 'TEXT', 'null' => true, 'after' => 'page_title'],
            'deskripsi'        => ['type' => 'TEXT', 'null' => true, 'after' => 'meta_description'],
            'foto'             => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'after' => 'deskripsi'],
            'nama_camat'       => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true, 'after' => 'foto'],
            'alamat_kantor'    => ['type' => 'TEXT', 'null' => true, 'after' => 'nama_camat'],
            'telepon'          => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true, 'after' => 'alamat_kantor'],
            'email'            => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true, 'after' => 'telepon'],
            'jam_layanan'      => ['type' => 'VARCHAR', 'constraint' => 200, 'null' => true, 'after' => 'email'],
            'is_public'        => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1, 'after' => 'jam_layanan'],
        ]);

        $this->forge->addKey('slug', false, true, 'slug_kecamatan');
        $this->forge->processIndexes('kecamatan');
    }

    public function down()
    {
        $this->forge->dropColumn('kecamatan', [
            'slug', 'page_title', 'meta_description', 'deskripsi',
            'foto', 'nama_camat', 'alamat_kantor', 'telepon',
            'email', 'jam_layanan', 'is_public',
        ]);
    }
}
