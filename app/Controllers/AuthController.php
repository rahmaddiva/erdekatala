<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;
use App\Models\UserModel;
class AuthController extends BaseController
{

    protected $userModel;
    protected $session;

    public function __construct()
    {
        $this->userModel = new UserModel();
        $this->session = session();
    }

    public function index()
    {
        return view('auth/login');
    }

    public function profile()
    {
        $data = [
            'title' => 'Profile User',
            'user' => $this->userModel->find($this->session->get('id_user'))
        ];
        return view('auth/profile', $data);
    }

    public function update_profile()
    {
        $id = $this->session->get('id_user');
        $validation = \Config\Services::validation();
        $validation->setRules([
            'nama_lengkap' => 'required',
            'username' => 'required'
        ]);

        if (!$validation->withRequest($this->request)->run()) {
            return redirect()->back()->withInput()->with('error', implode(' ', $validation->getErrors()));
        }

        $username = $this->request->getPost('username');
        $nama = $this->request->getPost('nama_lengkap');

        // check username uniqueness (except current user)
        $existing = $this->userModel->where('username', $username)->where('id_user !=', $id)->first();
        if ($existing) {
            return redirect()->back()->withInput()->with('error', 'Username sudah digunakan oleh pengguna lain');
        }

        $this->userModel->update($id, [
            'username' => $username,
            'nama_lengkap' => $nama
        ]);

        // update session values
        $this->session->set('username', $username);
        $this->session->set('nama_lengkap', $nama);

        return redirect()->to('/profile')->with('success', 'Profil berhasil diperbarui');
    }

    public function change_password()
    {
        $id = $this->session->get('id_user');
        $validation = \Config\Services::validation();
        $validation->setRules([
            'current_password' => 'required',
            'new_password' => 'required|min_length[6]',
            'new_password_confirm' => 'required|matches[new_password]'
        ]);

        if (!$validation->withRequest($this->request)->run()) {
            return redirect()->back()->withInput()->with('error', implode(' ', $validation->getErrors()));
        }

        $current = $this->request->getPost('current_password');
        $new = $this->request->getPost('new_password');

        $user = $this->userModel->find($id);
        if (!$user || !password_verify($current, $user['password'])) {
            return redirect()->back()->with('error', 'Password lama tidak sesuai');
        }

        $hash = password_hash($new, PASSWORD_DEFAULT);
        $this->userModel->update($id, ['password' => $hash]);

        return redirect()->to('/profile')->with('success', 'Password berhasil diubah');
    }

    public function proses_login()
    {
        // validasi input login
        $validation = \Config\Services::validation();
        $validation->setRules([
            'username' => 'required',
            'password' => 'required'
        ]);

        if (!$validation->withRequest($this->request)->run()) {
            return redirect()->back()->withInput()->with('errors', $validation->getErrors());
        }

        $username = $this->request->getPost('username');
        $password = $this->request->getPost('password');
        $user = $this->userModel->where('username', $username)->first();
        if ($user && password_verify($password, $user['password'])) {
            // set session
            $this->session->set('id_user', $user['id_user']);
            $this->session->set('id_kecamatan', $user['id_kecamatan']);
            $this->session->set('nama_lengkap', $user['nama_lengkap']);
            $this->session->set('id_desa', $user['id_desa']);
            $this->session->set('username', $user['username']);
            $this->session->set('role', $user['role']);
            $this->session->set('logged_in', true);

            return redirect()->to('/dashboard');
        } else {
            return redirect()->back()->withInput()->with('error', 'Username atau password salah');
        }
    }

    public function logout()
    {
        // session destroy
        $this->session->destroy();
        return redirect()->to('/login');
    }
}
