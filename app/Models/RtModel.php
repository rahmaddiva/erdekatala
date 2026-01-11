<?php

namespace App\Models;

use CodeIgniter\Model;

class RtModel extends Model
{
    protected $table = 'm_rt';
    protected $primaryKey = 'id_rt';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $protectFields = true;
    protected $allowedFields = [
        'id_dusun',
        'no_rt',
    ];

    public function getRtWithDusun()
    {
        return $this->select('m_rt.*, m_dusun.nama_dusun')
            ->join('m_dusun', 'm_rt.id_dusun = m_dusun.id_dusun')
            ->where('m_dusun.id_desa', session()->get('id_desa'))
            ->findAll();
    }




}
