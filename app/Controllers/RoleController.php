<?php

namespace App\Controllers;

use App\Libraries\Permissions;
use App\Models\RoleModel;
use App\Models\UserModel;
use CodeIgniter\Exceptions\PageNotFoundException;

class RoleController extends BaseController
{
    protected RoleModel $roles;

    public function __construct()
    {
        $this->roles = new RoleModel();

        // Hanya role sistem admin_dinas yang boleh mengubah definisi role.
        if (session()->get('role') !== 'admin_dinas') {
            throw PageNotFoundException::forPageNotFound();
        }
        if (role_scope() !== 'dinas' || !can('roles.manage')) {
            throw PageNotFoundException::forPageNotFound();
        }
    }

    public function index()
    {
        $roles = $this->roles->orderBy('is_system', 'DESC')->orderBy('label', 'ASC')->findAll();

        $counts = \Config\Database::connect()->table('users')
            ->select('role, COUNT(*) AS total')
            ->groupBy('role')
            ->get()->getResultArray();

        return view('role/index', [
            'title'      => 'Manajemen Role',
            'roles'      => $roles,
            'userCounts' => array_column($counts, 'total', 'role'),
        ]);
    }

    public function create()
    {
        return view('role/form', [
            'title'  => 'Tambah Role',
            'role'   => null,
            'groups' => Permissions::GROUPS,
            'checked' => [],
        ]);
    }

    public function store()
    {
        $rules = [
            'nama_role' => 'required|alpha_dash|min_length[3]|max_length[50]|is_unique[roles.nama_role]',
            'label'     => 'required|max_length[100]',
            'scope'     => 'required|in_list[dinas,kecamatan,desa]',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $this->roles->insert([
            'nama_role'   => strtolower($this->request->getPost('nama_role')),
            'label'       => $this->request->getPost('label'),
            'scope'       => $this->request->getPost('scope'),
            'permissions' => json_encode(Permissions::sanitize($this->request->getPost('permissions'))),
            'is_system'   => 0,
        ]);

        return redirect()->to('/roles')->with('success', 'Role berhasil dibuat.');
    }

    public function edit($nama)
    {
        $role = $this->roles->find($nama);
        if (!$role) {
            throw PageNotFoundException::forPageNotFound();
        }

        return view('role/form', [
            'title'   => 'Edit Role',
            'role'    => $role,
            'groups'  => Permissions::GROUPS,
            'checked' => json_decode($role['permissions'] ?? '[]', true) ?: [],
        ]);
    }

    public function update($nama)
    {
        $role = $this->roles->find($nama);
        if (!$role) {
            throw PageNotFoundException::forPageNotFound();
        }

        $rules = ['label' => 'required|max_length[100]'];
        if (!(bool) $role['is_system']) {
            $rules['scope'] = 'required|in_list[dinas,kecamatan,desa]';
        }

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $perms = Permissions::sanitize($this->request->getPost('permissions'));

        // Jangan sampai admin mengunci dirinya sendiri dari halaman role.
        if ($nama === session()->get('role') && !in_array('roles.manage', $perms, true)) {
            $perms[] = 'roles.manage';
        }

        $data = [
            'label'       => $this->request->getPost('label'),
            'permissions' => json_encode(array_values(array_unique($perms))),
        ];
        if (!(bool) $role['is_system']) {
            $data['scope'] = $this->request->getPost('scope');
        }

        $this->roles->update($nama, $data);

        return redirect()->to('/roles')->with('success', 'Role berhasil diperbarui.');
    }

    public function delete($nama)
    {
        $role = $this->roles->find($nama);
        if (!$role) {
            throw PageNotFoundException::forPageNotFound();
        }
        if ((bool) $role['is_system']) {
            return redirect()->back()->with('error', 'Role sistem tidak bisa dihapus.');
        }
        if ($nama === session()->get('role')) {
            return redirect()->back()->with('error', 'Anda tidak bisa menghapus role yang sedang dipakai.');
        }

        $used = (new UserModel())->where('role', $nama)->countAllResults();
        if ($used > 0) {
            return redirect()->back()->with('error', 'Role masih dipakai ' . $used . ' pengguna.');
        }

        $this->roles->delete($nama);

        return redirect()->to('/roles')->with('success', 'Role berhasil dihapus.');
    }
}
