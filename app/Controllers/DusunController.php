<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;
use App\Models\DusunModel;

class DusunController extends BaseController
{

    protected $dusunModel;

    public function __construct()
    {
        $this->dusunModel = new DusunModel();
    }

    public function index()
    {
        $data = [
            'title' => 'Data Dusun',
            'dusun' => $this->dusunModel->getDusunByDesa(session()->get('id_desa')),
        ];

        return view('dusun/index', $data);
    }

    public function store()
    {
        $namaDusun = $this->request->getPost('nama_dusun');
        $idDesa = session()->get('id_desa');

        $this->dusunModel->insert([
            'nama_dusun' => $namaDusun,
            'id_desa' => $idDesa,
        ]);

        return redirect()->to('/dusun')->with('success', 'Dusun berhasil ditambahkan.');
    }

    public function update($id)
    {
        $namaDusun = $this->request->getPost('nama_dusun');

        $this->dusunModel->update($id, [
            'nama_dusun' => $namaDusun,
        ]);

        return redirect()->to('/dusun')->with('success', 'Dusun berhasil diperbarui.');
    }

    public function delete($id)
    {
        $this->dusunModel->delete($id);
        return redirect()->to('/dusun')->with('success', 'Dusun berhasil dihapus.');
    }
}
