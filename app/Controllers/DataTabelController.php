<?php

namespace App\Controllers;

use App\Models\LaporanAgregatModel;

class DataTabelController extends BaseController
{
    public function index()
    {
        $m      = new LaporanAgregatModel();
        $role   = session()->get('role');
        $bulan  = $this->request->getGet('bulan');
        $tahun  = $this->request->getGet('tahun');

        $namaBulan = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret',
            4 => 'April',   5 => 'Mei',       6 => 'Juni',
            7 => 'Juli',    8 => 'Agustus',   9 => 'September',
            10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];

        $base = $m->select('laporan_agregat.id_laporan, laporan_agregat.id_desa, laporan_agregat.bulan, laporan_agregat.tahun, laporan_agregat.jiwa_l, laporan_agregat.jiwa_p, laporan_agregat.kk_l, laporan_agregat.kk_p, laporan_agregat.jml_balita, laporan_agregat.jml_pus, m_desa.nama_desa, kecamatan.nama_kecamatan')
                   ->join('m_desa',    'm_desa.id_desa = laporan_agregat.id_desa')
                   ->join('kecamatan', 'kecamatan.id_kecamatan = m_desa.id_kecamatan');

        if ($role === 'admin_desa') {
            $base->where('laporan_agregat.id_desa', session()->get('id_desa'));
        } elseif ($role === 'admin_kecamatan') {
            $base->where('m_desa.id_kecamatan', session()->get('id_kecamatan'));
        }

        if ($bulan) $base->where('laporan_agregat.bulan', $bulan);
        if ($tahun) $base->where('laporan_agregat.tahun', $tahun);

        // ambil daftar tahun yang tersedia untuk dropdown
        $tahunList = (new LaporanAgregatModel())->select('tahun')->distinct()->orderBy('tahun', 'DESC')->findAll();

        return view('data_tabel/index', [
            'title'      => 'Data Tabel Laporan Agregat',
            'laporan'    => $base->orderBy('laporan_agregat.tahun', 'DESC')->orderBy('laporan_agregat.bulan', 'DESC')->findAll(1000),
            'namaBulan'  => $namaBulan,
            'tahunList'  => array_column($tahunList, 'tahun'),
            'filterBulan' => $bulan,
            'filterTahun' => $tahun,
        ]);
    }
}
