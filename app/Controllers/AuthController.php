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
            $this->session->set('user_id', $user['id_user']);
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
