<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\LaporanAgregatModel;
use App\Models\RtModel;

class LaporanAgregatController extends BaseController
{
    protected $laporanModel;
    protected $rtModel;
    protected $bulanList;

    public function __construct()
    {
        $this->laporanModel = new LaporanAgregatModel();
        $this->rtModel = new RtModel();
        // Tambahkan helper daftar bulan agar konsisten
        $this->bulanList = [
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember'
        ];
    }

    // Tampilkan Daftar Laporan
    public function index()
    {
        $user = session()->get();
        if ($user['role'] == 'admin_desa') {
            $data['laporan'] = $this->laporanModel->getRekapByDesa($user['id_desa']);
        } elseif ($user['role'] == 'admin_kecamatan') {
            $data['laporan'] = $this->laporanModel->getRekapByKecamatan($user['id_kecamatan']);
        } else {
            $data['laporan'] = $this->laporanModel->findAll();
        }

        $data['title'] = "Daftar Laporan Agregat";
        return view('laporan/index', $data);
    }

    // Form Input Baru
    public function create()
    {
        $user = session()->get();

        $data = [
            'title' => 'Input Laporan Baru',
            'list_rt' => ($user['role'] == 'admin_desa')
                ? $this->rtModel->select('m_rt.*, m_dusun.nama_dusun')->join('m_dusun', 'm_dusun.id_dusun = m_rt.id_dusun')->where('m_dusun.id_desa', $user['id_desa'])->findAll()
                : $this->rtModel->findAll(),
            'bulan' => $this->bulanList // Mengirim array berindeks angka
        ];
        return view('laporan/form', $data);
    }

    public function store()
    {
        $data = $this->request->getPost();
        $userId = session()->get('id_user');

        if (!$userId) {
            return redirect()->back()->with('error', 'Sesi habis, silakan login ulang.');
        }

        // --- VALIDASI DUPLIKAT ---
        $exists = $this->laporanModel->where([
            'id_rt' => $data['id_rt'],
            'bulan' => $data['bulan'],
            'tahun' => $data['tahun']
        ])->first();

        if ($exists) {
            return redirect()->back()->withInput()->with(
                'error',
                "Data untuk RT tersebut pada periode <b>" . $this->bulanList[$data['bulan']] . " " . $data['tahun'] . "</b> sudah ada! 
            <a href='/laporan/edit/" . $exists['id_laporan'] . "' class='alert-link'>Klik di sini untuk edit data tersebut.</a>"
            );
        }

        $data['id_user'] = $userId;

        if ($this->laporanModel->insert($data)) {
            return redirect()->to('/laporan')->with('success', 'Laporan berhasil disimpan.');
        }
        return redirect()->back()->withInput()->with('error', 'Gagal menyimpan data.');
    }
    // Form Edit
    public function edit($id)
    {
        $data = [
            'title' => 'Edit Laporan',
            'laporan' => $this->laporanModel->find($id),
            'list_rt' => (new RtModel())->getRtWithDusun(),
            'bulan' => ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember']
        ];
        return view('laporan/form', $data);
    }

    // Update Data
    public function update($id)
    {
        if ($this->laporanModel->update($id, $this->request->getPost())) {
            return redirect()->to('/laporan')->with('success', 'Laporan diperbarui.');
        }
        return redirect()->back()->with('error', 'Gagal update.');
    }

    // Hapus Data
    public function delete($id)
    {
        $this->laporanModel->delete($id);
        return redirect()->to('/laporan')->with('success', 'Laporan dihapus.');
    }
}