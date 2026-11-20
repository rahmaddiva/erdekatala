<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\KecamatanModel;
use App\Libraries\SafeImageUpload;

class KecamatanController extends BaseController
{
    protected $kecamatanModel;
    protected $session;

    public function __construct()
    {
        $this->session = session();
        $this->kecamatanModel = new KecamatanModel();

        if (role_scope() !== 'dinas' || !can('kecamatan.manage')) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }
    }

    public function index()
    {
        $kecamatan = $this->kecamatanModel->orderBy('nama_kecamatan', 'ASC')->findAll();

        $data = [
            'title' => 'Data Kecamatan',
            'kecamatan' => $kecamatan,
        ];

        return view('kecamatan/index', $data);
    }

    public function create()
    {
        $data = [
            'title' => 'Tambah Kecamatan',
        ];

        return view('kecamatan/form', $data);
    }

    public function store()
    {
        $validation = \Config\Services::validation();
        $validation->setRules([
            'nama_kecamatan' => 'required|min_length[3]|max_length[100]',
            'kode_kecamatan' => 'permit_empty|max_length[20]',
            'slug'           => 'permit_empty|max_length[100]|is_unique[kecamatan.slug]',
            'foto'           => 'permit_empty|max_size[foto,2048]|ext_in[foto,jpg,jpeg,png,webp]',
        ]);

        if (!$validation->withRequest($this->request)->run()) {
            return redirect()->back()->withInput()->with('errors', $validation->getErrors());
        }

        $foto = $this->request->getFile('foto');
        $safeName = null;
        if ($foto && $foto->isValid() && !$foto->hasMoved()) {
            $safeName = SafeImageUpload::safeName($foto);
            if ($safeName === null) {
                return redirect()->back()->withInput()->with('error', 'Foto harus berupa gambar JPG, PNG, atau WebP yang valid.');
            }
        }

        $data = [
            'nama_kecamatan' => $this->request->getPost('nama_kecamatan'),
            'kode_kecamatan' => $this->request->getPost('kode_kecamatan'),
            'slug'           => $this->request->getPost('slug') ?: null,
            'page_title'     => $this->request->getPost('page_title') ?: null,
            'meta_description' => $this->request->getPost('meta_description') ?: null,
            'deskripsi'      => $this->request->getPost('deskripsi') ?: null,
            'nama_camat'     => $this->request->getPost('nama_camat') ?: null,
            'alamat_kantor'  => $this->request->getPost('alamat_kantor') ?: null,
            'telepon'        => $this->request->getPost('telepon') ?: null,
            'email'          => $this->request->getPost('email') ?: null,
            'jam_layanan'    => $this->request->getPost('jam_layanan') ?: null,
            'is_public'      => $this->request->getPost('is_public') ? 1 : 0,
        ];

        if ($safeName !== null) {
            $uploadDir = FCPATH . 'assets/uploads/kecamatan';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            if (!$foto->move($uploadDir, $safeName)) {
                return redirect()->back()->withInput()->with('error', 'Gagal menyimpan foto.');
            }
            $data['foto'] = 'assets/uploads/kecamatan/' . $safeName;
        }

        $this->kecamatanModel->insert($data);

        return redirect()->to('/kecamatan')->with('success', 'Data kecamatan berhasil ditambahkan.');
    }

    public function edit($id)
    {
        $kec = $this->kecamatanModel->find($id);
        if (!$kec) {
            return redirect()->to('/kecamatan')->with('error', 'Data kecamatan tidak ditemukan.');
        }

        $data = [
            'title' => 'Edit Kecamatan',
            'kec'   => $kec,
        ];

        return view('kecamatan/form', $data);
    }

    public function update($id)
    {
        $kec = $this->kecamatanModel->find($id);
        if (!$kec) {
            return redirect()->to('/kecamatan')->with('error', 'Data kecamatan tidak ditemukan.');
        }

        $slugRule = 'permit_empty|max_length[100]';
        $newSlug = $this->request->getPost('slug');
        if ($newSlug && $newSlug !== ($kec['slug'] ?? '')) {
            $slugRule .= '|is_unique[kecamatan.slug]';
        }

        $validation = \Config\Services::validation();
        $validation->setRules([
            'nama_kecamatan' => 'required|min_length[3]|max_length[100]',
            'kode_kecamatan' => 'permit_empty|max_length[20]',
            'slug'           => $slugRule,
            'foto'           => 'permit_empty|max_size[foto,2048]|ext_in[foto,jpg,jpeg,png,webp]',
        ]);

        if (!$validation->withRequest($this->request)->run()) {
            return redirect()->back()->withInput()->with('errors', $validation->getErrors());
        }

        $foto = $this->request->getFile('foto');
        $safeName = null;
        if ($foto && $foto->isValid() && !$foto->hasMoved()) {
            $safeName = SafeImageUpload::safeName($foto);
            if ($safeName === null) {
                return redirect()->back()->withInput()->with('error', 'Foto harus berupa gambar JPG, PNG, atau WebP yang valid.');
            }
        }

        $data = [
            'nama_kecamatan' => $this->request->getPost('nama_kecamatan'),
            'kode_kecamatan' => $this->request->getPost('kode_kecamatan'),
            'slug'           => $newSlug ?: null,
            'page_title'     => $this->request->getPost('page_title') ?: null,
            'meta_description' => $this->request->getPost('meta_description') ?: null,
            'deskripsi'      => $this->request->getPost('deskripsi') ?: null,
            'nama_camat'     => $this->request->getPost('nama_camat') ?: null,
            'alamat_kantor'  => $this->request->getPost('alamat_kantor') ?: null,
            'telepon'        => $this->request->getPost('telepon') ?: null,
            'email'          => $this->request->getPost('email') ?: null,
            'jam_layanan'    => $this->request->getPost('jam_layanan') ?: null,
            'is_public'      => $this->request->getPost('is_public') ? 1 : 0,
        ];

        if ($safeName !== null) {
            $uploadDir = FCPATH . 'assets/uploads/kecamatan';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            if (!$foto->move($uploadDir, $safeName)) {
                return redirect()->back()->withInput()->with('error', 'Gagal menyimpan foto.');
            }

            if (!empty($kec['foto']) && file_exists(FCPATH . $kec['foto'])) {
                unlink(FCPATH . $kec['foto']);
            }

            $data['foto'] = 'assets/uploads/kecamatan/' . $safeName;
        }

        $this->kecamatanModel->update($id, $data);

        return redirect()->to('/kecamatan')->with('success', 'Data kecamatan berhasil diupdate.');
    }

    public function delete($id)
    {
        $kec = $this->kecamatanModel->find($id);
        if (!$kec) {
            return redirect()->to('/kecamatan')->with('error', 'Data kecamatan tidak ditemukan.');
        }

        if (!empty($kec['foto']) && file_exists(FCPATH . $kec['foto'])) {
            unlink(FCPATH . $kec['foto']);
        }

        $this->kecamatanModel->delete($id);

        return redirect()->to('/kecamatan')->with('success', 'Data kecamatan berhasil dihapus.');
    }
}
