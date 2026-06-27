<?php

namespace App\Controllers;

use App\Models\ApiKeyModel;

class ApiDocsController extends BaseController
{
    // GET /api/docs  -- Swagger UI
    public function index()
    {
        return view('api/docs');
    }

    // GET /api/guide  -- Panduan integrasi
    public function guide()
    {
        return view('api/guide');
    }

    // GET /api/register  -- Form pendaftaran API key
    public function register()
    {
        return view('api/register', ['errors' => [], 'old' => []]);
    }

    // POST /api/register
    public function store()
    {
        $rules = [
            'owner_name'  => 'required|min_length[3]|max_length[100]',
            'owner_email' => 'required|valid_email|max_length[150]',
            'label'       => 'required|min_length[3]|max_length[100]',
            'owner_org'   => 'permit_empty|max_length[150]',
        ];

        if (!$this->validate($rules)) {
            return view('api/register', [
                'errors' => $this->validator->getErrors(),
                'old'    => $this->request->getPost(),
            ]);
        }

        $model    = new ApiKeyModel();
        $keyValue = $model->generateKey();
        $keyHash  = $model->hashKey($keyValue);

        $model->insert([
            'api_key'    => $keyHash,
            'name'       => $this->request->getPost('owner_name') . ' - ' . $this->request->getPost('label'),
            'email'      => $this->request->getPost('owner_email'),
            'is_active'  => 1,
            'rate_limit' => 1000,
        ]);

        return view('api/register_success', ['key_value' => $keyValue]);
    }
}
