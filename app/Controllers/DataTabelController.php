<?php

namespace App\Controllers;

use App\Models\LaporanAgregatModel;

class DataTabelController extends BaseController
{
    public function index()
    {
        $m     = new LaporanAgregatModel();
        $role  = session()->get('role');
        $base  = $m->select('laporan_agregat.*, m_rt.no_rt, m_dusun.nama_dusun, m_desa.nama_desa, kecamatan.nama_kecamatan')
                    ->join('m_rt',      'm_rt.id_rt = laporan_agregat.id_rt')
                    ->join('m_dusun',   'm_dusun.id_dusun = m_rt.id_dusun')
                    ->join('m_desa',    'm_desa.id_desa = m_dusun.id_desa')
                    ->join('kecamatan', 'kecamatan.id_kecamatan = m_desa.id_kecamatan');

        // ponytail: scope by role using existing session values
        if ($role === 'admin_desa') {
            $base->where('m_dusun.id_desa', session()->get('id_desa'));
        } elseif ($role === 'admin_kecamatan') {
            $base->where('m_desa.id_kecamatan', session()->get('id_kecamatan'));
        }
        // admin_dinas: no filter, gets all

        return view('data_tabel/index', [
            'title'   => 'Data Tabel Laporan Agregat',
            'laporan' => $base->findAll(),
        ]);
    }
}
