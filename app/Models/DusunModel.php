<?php

namespace App\Models;

use CodeIgniter\Model;

class DusunModel extends Model
{
    protected $table = 'm_dusun';
    protected $primaryKey = 'id_dusun';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $protectFields = true;
    protected $allowedFields = [
        'id_desa',
        'nama_dusun',
    ];


}
