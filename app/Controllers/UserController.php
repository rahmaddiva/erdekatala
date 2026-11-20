<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;
use App\Models\UserModel;
use App\Models\DesaModel;
use App\Models\KecamatanModel;
use App\Models\RoleModel;

class UserController extends BaseController
{
    protected $userModel;
    protected $session;

    public function __construct()
    {
        $this->userModel = new UserModel();
        $this->session = session();
    }

    private function _blockNoView()
    {
        if (!can('users.view')) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }
    }

    private function _blockNoManage()
    {
        if (!can('users.manage')) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }
    }

    /** Role yang boleh diberikan oleh aktor saat ini. */
    private function assignableRoles(): array
    {
        $roles = (new RoleModel())->orderBy('label', 'ASC')->findAll();

        // Hanya admin_dinas (role sistem) yang boleh memberi role scope
        // kecamatan/dinas. Aktor lain hanya boleh membuat akun desa.
        if (session()->get('role') !== 'admin_dinas') {
            $roles = array_values(array_filter($roles, fn($r) => $r['scope'] === 'desa'));
        }

        return $roles;
    }

    public function index()
    {
        $this->_blockNoView();

        $scope = role_scope();
        $isKecamatan = $scope === 'kecamatan';

        $builder = $this->userModel->select('users.*, m_desa.nama_desa')
            ->join('m_desa', 'm_desa.id_desa = users.id_desa', 'left')
            ->join('roles', 'roles.nama_role = users.role', 'left');

        if ($scope === 'kecamatan') {
            $builder->where('users.id_kecamatan', $this->session->get('id_kecamatan'))
                ->where('roles.scope', 'desa');
            $title = 'Akun Pengguna Kecamatan';
        } elseif ($scope === 'dinas' && $this->session->get('role') !== 'admin_dinas') {
            // Role dinas kustom hanya melihat akun desa, bukan akun sistem/dinas.
            $builder->where('roles.scope', 'desa');
            $title = 'Akun Pengguna Desa';
        } elseif ($scope === 'desa') {
            $builder->where('users.id_user', $this->session->get('id_user'));
            $title = 'Akun Saya';
        } else {
            $title = 'Manajemen Pengguna';
        }

        $roles = (new RoleModel())->orderBy('label', 'ASC')->findAll();

        $data = [
            'title'        => $title,
            'users'        => $builder->findAll(),
            'is_kecamatan' => $isKecamatan,
            'role_labels'  => array_column($roles, 'label', 'nama_role'),
            'can_manage'   => can('users.manage'),
        ];
        return view('user/index', $data);
    }

    public function create()
    {
        $this->_blockNoManage();

        $data = [
            'title'          => 'Tambah Pengguna',
            'roles'          => $this->assignableRoles(),
            'kecamatan_list' => (new KecamatanModel())->orderBy('nama_kecamatan', 'ASC')->findAll(),
            'desa_list'      => (new DesaModel())->orderBy('nama_desa', 'ASC')->findAll(),
        ];
        return view('user/create', $data);
    }

    public function store()
    {
        $this->_blockNoManage();
        $validation = \Config\Services::validation();
        $validation->setRules([
            'username' => 'required|is_unique[users.username]',
            'nama_lengkap' => 'required',
            'password' => 'required|min_length[6]',
            'password_confirm' => 'required|matches[password]',
            'role' => 'required',
        ]);

        if (!$validation->withRequest($this->request)->run()) {
            return redirect()->back()->withInput()->with('error', implode(' ', $validation->getErrors()));
        }

        $role = $this->request->getPost('role');
        $scope = $this->resolveScope(
            $role,
            $this->request->getPost('id_kecamatan'),
            $this->request->getPost('id_desa')
        );
        if (is_string($scope)) {
            return redirect()->back()->withInput()->with('error', $scope);
        }

        $data = [
            'username' => $this->request->getPost('username'),
            'nama_lengkap' => $this->request->getPost('nama_lengkap'),
            'password' => password_hash($this->request->getPost('password'), PASSWORD_DEFAULT),
            'role' => $role,
        ] + $scope;

        $this->userModel->insert($data);
        return redirect()->to('/users')->with('success', 'Pengguna berhasil dibuat');
    }

    /**
     * Validasi & normalisasi scope wilayah sesuai role (dinamis).
     * Return ['id_kecamatan'=>?int,'id_desa'=>?int] atau string pesan error.
     */
    private function resolveScope(string $role, $idKecamatan, $idDesa): array|string
    {
        $actorScope = role_scope();

        if ($actorScope === 'desa') {
            return 'Anda tidak berhak mengelola pengguna.';
        }

        $roleRow = (new RoleModel())->find($role);
        if (!$roleRow) {
            return 'Role tidak valid.';
        }
        $targetScope = $roleRow['scope'];

        // Hanya admin_dinas yang boleh menetapkan role scope kecamatan/dinas.
        if (session()->get('role') !== 'admin_dinas' && $targetScope !== 'desa') {
            return 'Anda hanya dapat menetapkan role dengan scope desa.';
        }

        if ($targetScope === 'dinas') {
            return ['id_kecamatan' => null, 'id_desa' => null];
        }

        if ($targetScope === 'kecamatan') {
            $kec = $actorScope === 'kecamatan' ? (int) session('id_kecamatan') : (int) $idKecamatan;
            if (!$kec || !(new KecamatanModel())->find($kec)) {
                return 'Kecamatan wajib dipilih untuk role ini.';
            }
            return ['id_kecamatan' => $kec, 'id_desa' => null];
        }

        // Scope desa: id_kecamatan diturunkan dari desa.
        $desa = $idDesa ? (new DesaModel())->find((int) $idDesa) : null;
        if (!$desa) {
            return 'Desa wajib dipilih untuk role ini.';
        }
        if ($actorScope === 'kecamatan' && (int) $desa['id_kecamatan'] !== (int) session('id_kecamatan')) {
            return 'Desa tidak valid untuk kecamatan Anda.';
        }

        return [
            'id_kecamatan' => (int) $desa['id_kecamatan'],
            'id_desa'      => (int) $desa['id_desa'],
        ];
    }

    private function _canManageUser($user)
    {
        $actorRole = $this->session->get('role');
        $scope     = role_scope();

        if ($actorRole === 'admin_dinas') {
            return true;
        }
        if ($scope === 'desa') {
            return false;
        }

        // Aktor non-admin_dinas hanya boleh mengelola akun desa.
        $targetRole = (new RoleModel())->find($user['role']);
        if (($targetRole['scope'] ?? 'desa') !== 'desa') {
            return false;
        }

        if ($scope === 'kecamatan') {
            return (int) $user['id_kecamatan'] === (int) $this->session->get('id_kecamatan');
        }

        // Role dinas kustom: boleh mengelola akun desa lintas kecamatan.
        return true;
    }

    private function _isKecamatan()
    {
        return role_scope() === 'kecamatan';
    }

    public function edit($id = null)
    {
        $this->_blockNoManage();

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
            'roles' => $this->assignableRoles(),
            'is_kecamatan' => $this->_isKecamatan()
        ];

        if ($this->_isKecamatan()) {
            $data['desa_list'] = (new DesaModel())
                ->where('id_kecamatan', $this->session->get('id_kecamatan'))
                ->orderBy('nama_desa', 'ASC')
                ->findAll();
        } else {
            $data['kecamatan_list'] = (new KecamatanModel())->orderBy('nama_kecamatan', 'ASC')->findAll();
            $data['desa_list']      = (new DesaModel())->orderBy('nama_desa', 'ASC')->findAll();
        }

        return view('user/edit', $data);
    }

    public function update($id = null)
    {
        $this->_blockNoManage();

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
            // Aktor kecamatan tidak bisa ubah role; hanya scope desa yang bisa disesuaikan.
            $targetRole = (new RoleModel())->find($user['role']);
            if (($targetRole['scope'] ?? 'desa') === 'desa') {
                $desaId = $this->request->getPost('id_desa');
                $desaValid = (new DesaModel())
                    ->where('id_desa', $desaId)
                    ->where('id_kecamatan', $this->session->get('id_kecamatan'))
                    ->first();
                if (!$desaValid) {
                    return redirect()->back()->withInput()->with('error', 'Desa tidak valid untuk kecamatan Anda');
                }
                $update['id_desa']      = $desaId;
                $update['id_kecamatan'] = $this->session->get('id_kecamatan');
            }
        } else {
            $role = $this->request->getPost('role') ?? $user['role'];
            $scope = $this->resolveScope(
                $role,
                $this->request->getPost('id_kecamatan'),
                $this->request->getPost('id_desa')
            );
            if (is_string($scope)) {
                return redirect()->back()->withInput()->with('error', $scope);
            }
            $update['role'] = $role;
            $update += $scope;
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
        $this->_blockNoManage();

        $user = $this->userModel->find($id);
        if (!$user) {
            return redirect()->to('/users')->with('error', 'Pengguna tidak ditemukan');
        }
        if ((int) $id === (int) $this->session->get('id_user')) {
            return redirect()->to('/users')->with('error', 'Anda tidak bisa menghapus akun sendiri.');
        }
        if (!$this->_canManageUser($user)) {
            return redirect()->to('/users')->with('error', 'Anda tidak memiliki akses ke pengguna tersebut');
        }
        $this->userModel->delete($id);
        return redirect()->to('/users')->with('success', 'Pengguna berhasil dihapus');
    }
}
