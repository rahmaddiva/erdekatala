<?php

namespace App\Models;

use CodeIgniter\Model;

class DesaModel extends Model
{
    protected $table = 'm_desa';
    protected $primaryKey = 'id_desa';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $protectFields = true;
    protected $allowedFields = ['id_kecamatan', 'nama_desa', 'kode_desa', 'created_at'];

}
