<?php

namespace App\Controllers;

use App\Models\ApiKeyModel;

class ApiKeyAdminController extends BaseController
{
    private ApiKeyModel $model;

    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
        $this->model = new ApiKeyModel();

        if (session()->get('role') !== 'admin_dinas') {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }
    }

    // GET /apikeys
    public function index()
    {
        $allKeys = $this->model->orderBy('created_at', 'DESC')->findAll();
        $publikCount = 0;
        foreach ($allKeys as $k) {
            if ($k['created_by'] === null) {
                $publikCount++;
            }
        }

        return view('api/admin_keys', [
            'title'       => 'Manajemen API Key',
            'apikeys'     => $allKeys,
            'publikCount' => $publikCount,
        ]);
    }

    // GET /apikeys/create
    public function create()
    {
        return view('api/admin_create_key', [
            'title'  => 'Buat API Key Baru',
            'errors' => [],
            'old'    => [],
        ]);
    }

    // POST /apikeys/store
    public function store()
    {
        $rules = [
            'label'      => 'required|min_length[3]|max_length[100]',
            'owner_email'=> 'required|valid_email|max_length[150]',
            'rate_limit' => 'required|integer|greater_than[0]',
        ];

        if (!$this->validate($rules)) {
            return view('api/admin_create_key', [
                'title'  => 'Buat API Key Baru',
                'errors' => $this->validator->getErrors(),
                'old'    => $this->request->getPost(),
            ]);
        }

        $keyValue = $this->model->generateKey();
        $keyHash  = $this->model->hashKey($keyValue);

        $this->model->insert([
            'api_key'    => $keyHash,
            'name'       => $this->request->getPost('label'),
            'owner_name' => $this->request->getPost('owner_name'),
            'email'      => $this->request->getPost('owner_email'),
            'rate_limit' => $this->request->getPost('rate_limit'),
            'is_active'  => 1,
            'created_by' => session()->get('id_user'),
        ]);

        return redirect()->to('/apikeys')->with('success', 'API key berhasil dibuat. Key: ' . $keyValue);
    }

    // GET /apikeys/revoke/:id
    public function revoke(int $id)
    {
        $this->model->update($id, ['is_active' => 0]);
        return redirect()->to('/apikeys')->with('success', 'API key berhasil dinonaktifkan.');
    }

    // GET /apikeys/activate/:id
    public function activate(int $id)
    {
        $this->model->update($id, ['is_active' => 1]);
        return redirect()->to('/apikeys')->with('success', 'API key berhasil diaktifkan kembali.');
    }

    // GET /apikeys/delete/:id
    public function delete(int $id)
    {
        $this->model->delete($id);
        return redirect()->to('/apikeys')->with('success', 'API key berhasil dihapus.');
    }
}
