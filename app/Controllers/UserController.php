<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;
use App\Models\UserModel;

class UserController extends BaseController
{
    protected $userModel;
    protected $session;

    public function __construct()
    {
        $this->userModel = new UserModel();
        $this->session = session();

        // cek role jika bukan admin_dinas maka redirect ke dashboard
        if ($this->session->get('role') != 'admin_dinas') {
            header('Location: ' . base_url('dashboard'));
            exit();
        }

    }

    public function index()
    {
        $data = [
            'title' => 'Manajemen Pengguna',
            'users' => $this->userModel->findAll()
        ];
        return view('user/index', $data);
    }

    public function create()
    {
        $data = [
            'title' => 'Tambah Pengguna'
        ];
        return view('user/create', $data);
    }

    public function store()
    {
        $validation = \Config\Services::validation();
        $validation->setRules([
            'username' => 'required|is_unique[users.username]',
            'nama_lengkap' => 'required',
            'password' => 'required|min_length[6]',
            'password_confirm' => 'required|matches[password]'
        ]);

        if (!$validation->withRequest($this->request)->run()) {
            return redirect()->back()->withInput()->with('error', implode(' ', $validation->getErrors()));
        }

        $data = [
            'username' => $this->request->getPost('username'),
            'nama_lengkap' => $this->request->getPost('nama_lengkap'),
            'password' => password_hash($this->request->getPost('password'), PASSWORD_DEFAULT),
            'role' => $this->request->getPost('role') ?? 'user'
        ];

        $this->userModel->insert($data);
        return redirect()->to('/users')->with('success', 'Pengguna berhasil dibuat');
    }

    public function edit($id = null)
    {
        $user = $this->userModel->find($id);
        if (!$user) {
            return redirect()->to('/users')->with('error', 'Pengguna tidak ditemukan');
        }
        $data = [
            'title' => 'Edit Pengguna',
            'user' => $user
        ];
        return view('user/edit', $data);
    }

    public function update($id = null)
    {
        $user = $this->userModel->find($id);
        if (!$user) {
            return redirect()->to('/users')->with('error', 'Pengguna tidak ditemukan');
        }

        $validation = \Config\Services::validation();
        $validation->setRules([
            'username' => 'required',
            'nama_lengkap' => 'required'
        ]);

        if (!$validation->withRequest($this->request)->run()) {
            return redirect()->back()->withInput()->with('error', implode(' ', $validation->getErrors()));
        }

        $username = $this->request->getPost('username');
        // check uniqueness except current
        $existing = $this->userModel->where('username', $username)->where('id_user !=', $id)->first();
        if ($existing) {
            return redirect()->back()->withInput()->with('error', 'Username sudah digunakan oleh pengguna lain');
        }

        $update = [
            'username' => $username,
            'nama_lengkap' => $this->request->getPost('nama_lengkap'),
            'role' => $this->request->getPost('role') ?? $user['role']
        ];

        $newPass = $this->request->getPost('password');
        $confirm = $this->request->getPost('password_confirm');
        if (!empty($newPass)) {
            if (strlen($newPass) < 6) {
                return redirect()->back()->withInput()->with('error', 'Password baru minimal 6 karakter');
            }
            if ($newPass !== $confirm) {
                return redirect()->back()->withInput()->with('error', 'Konfirmasi password tidak cocok');
            }
            $update['password'] = password_hash($newPass, PASSWORD_DEFAULT);
        }

        $this->userModel->update($id, $update);
        return redirect()->to('/users')->with('success', 'Pengguna berhasil diperbarui');
    }

    public function delete($id = null)
    {
        $user = $this->userModel->find($id);
        if (!$user) {
            return redirect()->to('/users')->with('error', 'Pengguna tidak ditemukan');
        }
        $this->userModel->delete($id);
        return redirect()->to('/users')->with('success', 'Pengguna berhasil dihapus');
    }
}
