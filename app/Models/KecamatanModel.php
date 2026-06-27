<?php

namespace App\Models;

use CodeIgniter\Model;

class KecamatanModel extends Model
{
    protected $table = 'kecamatan';
    protected $primaryKey = 'id_kecamatan';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $protectFields = true;
    protected $allowedFields = [
        'nama_kecamatan', 'kode_kecamatan',
        'slug', 'page_title', 'meta_description',
        'deskripsi', 'foto', 'nama_camat', 'alamat_kantor',
        'telepon', 'email', 'jam_layanan', 'is_public',
    ];

}
