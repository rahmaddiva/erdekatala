<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;
use App\Models\RtModel;
use App\Models\DusunModel;

class RtController extends BaseController
{

    protected $rtModel;
    protected $dusunModel;

    public function __construct()
    {
        $this->rtModel = new RtModel();
        $this->dusunModel = new DusunModel();
    }

    public function index()
    {
        $data = [
            'title' => 'Data RT',
            'rt' => $this->rtModel->findAll(),
            'dusun' => $this->dusunModel->findAll(),
        ];

        return view('rt/index', $data);
    }

    public function store()
    {
        $namaRt = $this->request->getPost('nama_rt');
        $idDusun = $this->request->getPost('id_dusun');

        $this->rtModel->insert([
            'nama_rt' => $namaRt,
            'id_dusun' => $idDusun,
        ]);

        return redirect()->to('/rt')->with('success', 'RT berhasil ditambahkan.');
    }

    public function update($id)
    {
        $namaRt = $this->request->getPost('nama_rt');
        $idDusun = $this->request->getPost('id_dusun');

        $this->rtModel->update($id, [
            'nama_rt' => $namaRt,
            'id_dusun' => $idDusun,
        ]);

        return redirect()->to('/rt')->with('success', 'RT berhasil diperbarui.');
    }

    public function delete($id)
    {
        $this->rtModel->delete($id);
        return redirect()->to('/rt')->with('success', 'RT berhasil dihapus.');
    }


}
