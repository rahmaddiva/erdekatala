<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;
use App\Models\UserModel;
use App\Models\NotifikasiModel;
use App\Models\KecamatanModel;
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

        if ($this->session->get('role') === 'admin_kecamatan') {
            $data['kec'] = (new KecamatanModel())->find($this->session->get('id_kecamatan'));
        }

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

    public function updateKecamatanProfile()
    {
        if ($this->session->get('role') !== 'admin_kecamatan') {
            return redirect()->to('/dashboard')->with('error', 'Anda tidak memiliki akses');
        }

        $idKec = $this->session->get('id_kecamatan');
        $kec = (new KecamatanModel())->find($idKec);
        if (!$kec) {
            return redirect()->to('/profile')->with('error', 'Data kecamatan tidak ditemukan');
        }

        $slugRule = 'permit_empty|max_length[100]';
        $newSlug = $this->request->getPost('slug');
        if ($newSlug && $newSlug !== ($kec['slug'] ?? '')) {
            $slugRule .= '|is_unique[kecamatan.slug]';
        }

        $validation = \Config\Services::validation();
        $validation->setRules([
            'slug'           => $slugRule,
            'foto'           => 'permit_empty|max_size[foto,2048]|ext_in[foto,jpg,jpeg,png,webp]',
        ]);

        if (!$validation->withRequest($this->request)->run()) {
            return redirect()->back()->withInput()->with('errors', $validation->getErrors());
        }

        $data = [
            'slug'           => $newSlug ?: null,
            'page_title'     => $this->request->getPost('page_title') ?: null,
            'meta_description' => $this->request->getPost('meta_description') ?: null,
            'deskripsi'      => $this->request->getPost('deskripsi') ?: null,
            'nama_camat'     => $this->request->getPost('nama_camat') ?: null,
            'alamat_kantor'  => $this->request->getPost('alamat_kantor') ?: null,
            'telepon'        => $this->request->getPost('telepon') ?: null,
            'email'          => $this->request->getPost('email') ?: null,
            'jam_layanan'    => $this->request->getPost('jam_layanan') ?: null,
        ];

        $foto = $this->request->getFile('foto');
        if ($foto && $foto->isValid() && !$foto->hasMoved()) {
            $uploadDir = FCPATH . 'assets/uploads/kecamatan';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            if (!empty($kec['foto']) && file_exists(FCPATH . $kec['foto'])) {
                unlink(FCPATH . $kec['foto']);
            }

            $newName = $foto->getRandomName();
            $foto->move($uploadDir, $newName);
            $data['foto'] = 'assets/uploads/kecamatan/' . $newName;
        }

        (new KecamatanModel())->update($idKec, $data);

        return redirect()->to('/profile')->with('success', 'Data kecamatan berhasil diperbarui');
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
        $remember = $this->request->getPost('remember');
        $user = $this->userModel->where('username', $username)->first();
        if ($user && password_verify($password, $user['password'])) {
            // regenerate session ID to prevent session fixation
            $this->session->regenerate(true);
            $this->session->set([
                'id_user'      => $user['id_user'],
                'id_kecamatan' => $user['id_kecamatan'],
                'nama_lengkap' => $user['nama_lengkap'],
                'id_desa'      => $user['id_desa'],
                'username'     => $user['username'],
                'role'         => $user['role'],
                'logged_in'    => true,
            ]);

            // "Ingat Saya": simpan token di cookie selama 30 hari
            if ($remember) {
                $token = bin2hex(random_bytes(32));
                $this->userModel->update($user['id_user'], ['remember_token' => $token]);
                $response = service('response');
                $response->setCookie('remember_token', $token, 30 * 24 * 3600, '', '/', '', true, true);
            }

            // Generate notifikasi pengingat laporan bulan ini
            (new NotifikasiModel())->generateReminder($user['id_user'], $user['role'], $user['id_kecamatan'], $user['id_desa']);

            return redirect()->to('/dashboard');
        } else {
            return redirect()->back()->withInput()->with('error', 'Username atau password salah');
        }
    }

    public function logout()
    {
        // Hapus remember_token dari DB dan cookie
        $id = $this->session->get('id_user');
        if ($id) {
            $this->userModel->update($id, ['remember_token' => null]);
        }
        service('response')->deleteCookie('remember_token');

        $this->session->destroy();
        return redirect()->to('/login');
    }
}
