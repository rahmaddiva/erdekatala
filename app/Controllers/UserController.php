<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;
use App\Models\UserModel;
use App\Models\DesaModel;

class UserController extends BaseController
{
    protected $userModel;
    protected $session;

    public function __construct()
    {
        $this->userModel = new UserModel();
        $this->session = session();

        // admin_dinas dan admin_kecamatan boleh akses; admin_desa redirect
        if (!in_array($this->session->get('role'), ['admin_dinas', 'admin_kecamatan'], true)) {
            header('Location: ' . base_url('dashboard'));
            exit();
        }
    }

    private function _blockNonDinas()
    {
        if ($this->session->get('role') !== 'admin_dinas') {
            header('Location: ' . base_url('dashboard'));
            exit();
        }
    }

    public function index()
    {
        $isKecamatan = $this->session->get('role') === 'admin_kecamatan';

        if ($isKecamatan) {
            $users = $this->userModel->select('users.*, m_desa.nama_desa')
                ->join('m_desa', 'm_desa.id_desa = users.id_desa', 'left')
                ->where('users.role', 'admin_desa')
                ->where('users.id_kecamatan', $this->session->get('id_kecamatan'))
                ->findAll();
            $title = 'Akun Admin Desa';
        } else {
            $users = $this->userModel->findAll();
            $title = 'Manajemen Pengguna';
        }

        $data = [
            'title' => $title,
            'users' => $users,
            'is_kecamatan' => $isKecamatan
        ];
        return view('user/index', $data);
    }

    public function create()
    {
        $this->_blockNonDinas();
        $data = [
            'title' => 'Tambah Pengguna'
        ];
        return view('user/create', $data);
    }

    public function store()
    {
        $this->_blockNonDinas();
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

    private function _canManageUser($user)
    {
        // admin_kecamatan hanya boleh kelola akun admin_desa di kecamatannya
        if ($this->session->get('role') === 'admin_kecamatan') {
            return $user['role'] === 'admin_desa'
                && (int) $user['id_kecamatan'] === (int) $this->session->get('id_kecamatan');
        }
        return true;
    }

    private function _isKecamatan()
    {
        return $this->session->get('role') === 'admin_kecamatan';
    }

    public function edit($id = null)
    {
        $user = $this->userModel->find($id);
        if (!$user) {
            return redirect()->to('/users')->with('error', 'Pengguna tidak ditemukan');
        }
        if (!$this->_canManageUser($user)) {
            return redirect()->to('/users')->with('error', 'Anda tidak memiliki akses ke pengguna tersebut');
        }

        $data = [
            'title' => 'Edit Pengguna',
            'user' => $user,
            'is_kecamatan' => $this->_isKecamatan()
        ];

        if ($this->_isKecamatan()) {
            $data['desa_list'] = (new DesaModel())
                ->where('id_kecamatan', $this->session->get('id_kecamatan'))
                ->orderBy('nama_desa', 'ASC')
                ->findAll();
        }

        return view('user/edit', $data);
    }

    public function update($id = null)
    {
        $user = $this->userModel->find($id);
        if (!$user) {
            return redirect()->to('/users')->with('error', 'Pengguna tidak ditemukan');
        }
        if (!$this->_canManageUser($user)) {
            return redirect()->to('/users')->with('error', 'Anda tidak memiliki akses ke pengguna tersebut');
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
        ];

        if ($this->_isKecamatan()) {
            // admin_kecamatan tidak bisa ubah role; hanya nama, username, desa, password
            $desaId = $this->request->getPost('id_desa');
            $desaValid = (new DesaModel())
                ->where('id_desa', $desaId)
                ->where('id_kecamatan', $this->session->get('id_kecamatan'))
                ->first();
            if (!$desaValid) {
                return redirect()->back()->withInput()->with('error', 'Desa tidak valid untuk kecamatan Anda');
            }
            $update['id_desa'] = $desaId;
        } else {
            $update['role'] = $this->request->getPost('role') ?? $user['role'];
        }

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
        if (!$this->_canManageUser($user)) {
            return redirect()->to('/users')->with('error', 'Anda tidak memiliki akses ke pengguna tersebut');
        }
        $this->userModel->delete($id);
        return redirect()->to('/users')->with('success', 'Pengguna berhasil dihapus');
    }
}
