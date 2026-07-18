<?php

namespace App\Models;

use CodeIgniter\Model;

class LaporanLogModel extends Model
{
    protected $table         = 'laporan_log';
    protected $primaryKey    = 'id';
    protected $allowedFields = ['id_laporan', 'id_user', 'aksi', 'perubahan'];
    protected $useTimestamps = true;
    protected $updatedField  = ''; // ponytail: create-only, no updated_at needed
}
