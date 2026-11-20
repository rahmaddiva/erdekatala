<?php

namespace App\Database\Migrations;

use App\Libraries\Permissions;
use CodeIgniter\Database\Migration;

class CreateRolesAndPermissions extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'nama_role'   => ['type' => 'VARCHAR', 'constraint' => 50],
            'label'       => ['type' => 'VARCHAR', 'constraint' => 100],
            'scope'       => ['type' => 'ENUM', 'constraint' => ['dinas', 'kecamatan', 'desa'], 'default' => 'desa'],
            'permissions' => ['type' => 'TEXT', 'null' => true],
            'is_system'   => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'created_at'  => ['type' => 'DATETIME', 'null' => true],
            'updated_at'  => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('nama_role', true);
        $this->forge->createTable('roles', true);

        // Role kustom membutuhkan kolom role yang tidak lagi ENUM.
        $this->db->query('ALTER TABLE ' . $this->db->prefixTable('users') . ' MODIFY role VARCHAR(50) NOT NULL');

        $labels = [
            'admin_dinas'     => 'Administrator Dinas',
            'admin_kecamatan' => 'Administrator Kecamatan',
            'admin_desa'      => 'Administrator Desa',
        ];
        $scopes = [
            'admin_dinas'     => 'dinas',
            'admin_kecamatan' => 'kecamatan',
            'admin_desa'      => 'desa',
        ];

        $now  = date('Y-m-d H:i:s');
        $rows = [];
        foreach (Permissions::defaults() as $name => $perms) {
            $rows[] = [
                'nama_role'   => $name,
                'label'       => $labels[$name],
                'scope'       => $scopes[$name],
                'permissions' => json_encode(array_values($perms)),
                'is_system'   => 1,
                'created_at'  => $now,
                'updated_at'  => $now,
            ];
        }
        $this->db->table('roles')->insertBatch($rows);
    }

    public function down()
    {
        $this->db->query(
            "UPDATE " . $this->db->prefixTable('users') . " SET role = 'admin_desa' "
            . "WHERE role NOT IN ('admin_dinas','admin_kecamatan','admin_desa')"
        );
        $this->db->query(
            'ALTER TABLE ' . $this->db->prefixTable('users')
            . " MODIFY role ENUM('admin_dinas','admin_kecamatan','admin_desa') NOT NULL"
        );
        $this->forge->dropTable('roles', true);
    }
}
