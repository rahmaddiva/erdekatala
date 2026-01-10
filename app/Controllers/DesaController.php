<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;
use App\Models\DesaModel;
class DesaController extends BaseController
{

    protected $desaModel;

    public function __construct()
    {
        $this->desaModel = new DesaModel();
    }

    public function index()
    {
        $data = [
            'title' => 'Data Desa',
            'desa' => $this->desaModel->findAll()
        ];

        return view('desa/index', $data);
    }

    public function store()
    {
        // validasi inputan 
        $validation = \Config\Services::validation();
        $validation->setRules([
            'nama_desa' => 'required|min_length[3]|max_length[100]',
        ]);
        if (!$validation->withRequest($this->request)->run()) {
            return redirect()->back()->withInput()->with('errors', $validation->getErrors());
        }

        $this->desaModel->insert([
            'nama_desa' => $this->request->getPost('nama_desa'),
            'kode_desa' => $this->request->getPost('kode_desa'),
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        return redirect()->to('/desa')->with('success', 'Data desa berhasil ditambahkan.');
    }

    public function update($id)
    {
        // validasi inputan 
        $validation = \Config\Services::validation();
        $validation->setRules([
            'nama_desa' => 'required|min_length[3]|max_length[100]',
        ]);
        if (!$validation->withRequest($this->request)->run()) {
            return redirect()->back()->withInput()->with('errors', $validation->getErrors());
        }

        $this->desaModel->update($id, [
            'nama_desa' => $this->request->getPost('nama_desa'),
            'kode_desa' => $this->request->getPost('kode_desa'),
        ]);

        return redirect()->to('/desa')->with('success', 'Data desa berhasil diupdate.');
    }

    public function delete($id)
    {
        $this->desaModel->delete($id);
        return redirect()->to('/desa')->with('success', 'Data desa berhasil dihapus.');
    }
}
