<?php

namespace App\Models;

use CodeIgniter\Model;

class RoleModel extends Model
{
    protected $table            = 'roles';
    protected $primaryKey       = 'nama_role';
    protected $useAutoIncrement = false;
    protected $returnType       = 'array';
    protected $protectFields    = true;
    protected $allowedFields    = ['nama_role', 'label', 'scope', 'permissions', 'is_system'];
    protected $useTimestamps    = true;
    protected $createdField     = 'created_at';
    protected $updatedField     = 'updated_at';

    /** @return list<string> */
    public function permissionsOf(?string $name): array
    {
        if (!$name) {
            return [];
        }

        $row = $this->find($name);

        return $row ? (json_decode($row['permissions'] ?? '[]', true) ?: []) : [];
    }
}
