<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\LaporanAgregatModel;
use App\Models\RtModel;
use App\Models\KecamatanModel;
use Dompdf\Dompdf;
use Dompdf\Options;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class LaporanAgregatController extends BaseController
{
    protected $laporanModel;
    protected $rtModel;
    protected $bulanList;

    protected $kecamatanModel;

    // Tambahkan ini di dalam class LaporanAgregatController

    /**
     * Helper untuk membuat header otomatis, menebalkan font, 
     * dan mengatur lebar kolom otomatis.
     */

    public function __construct()
    {
        $this->laporanModel = new LaporanAgregatModel();
        $this->rtModel = new RtModel();
        $this->kecamatanModel = new KecamatanModel();
        // Tambahkan helper daftar bulan agar konsisten
        $this->bulanList = [
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember'
        ];
    }

    private function generatePdf($laporan, $user)
    {
        // Konfigurasi Dompdf
        $options = new Options();
        $options->set('isRemoteEnabled', true);
        // Gunakan font standar Dompdf (Helvetica) untuk menghindari masalah jika Arial tidak tersedia
        $options->set('defaultFont', 'Helvetica');
        $dompdf = new Dompdf($options);

        $data = [
            'title' => 'LAPORAN AGREGAT KEPENDUDUKAN',
            'laporan' => $laporan,
            'user' => $user,
            'tanggal' => date('d F Y')
        ];

        $html = view('laporan/export_pdf', $data);

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');// Landscape karena kolom banyak
        // ... (proses render tetap sama) ...
        $dompdf->render();

        $filename = "Laporan_Agregat_" . date('Ymd_His') . ".pdf";

        // Kirim response agar dibuka di browser
        return $this->response
            ->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', 'inline; filename="' . $filename . '"')
            ->setBody($dompdf->output());
    }

    // Gunakan logika generateExcel yang sudah Anda miliki namun sesuaikan input $laporan-nya
    private function generateExcel($laporan)
    {
        $spreadsheet = new Spreadsheet();

        // -- Sheet 1: Data Pokok --
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Data Pokok');

        $headers = ['No', 'Kecamatan', 'Desa', 'Dusun', 'RT', 'Bulan', 'Tahun', 'Jiwa L', 'Jiwa P', 'KK L', 'KK P'];
        $this->writeHeader($sheet, $headers);

        $rowIdx = 2;
        foreach ($laporan as $row) {
            $sheet->fromArray([
                $rowIdx - 1,
                $row['nama_kecamatan'],
                $row['nama_desa'],
                $row['nama_dusun'],
                $row['no_rt'],
                $this->bulanList[$row['bulan']] ?? $row['bulan'],
                $row['tahun'],
                $row['jiwa_l'],
                $row['jiwa_p'],
                $row['kk_l'],
                $row['kk_p']
            ], NULL, 'A' . $rowIdx);
            $rowIdx++;
        }

        // ... (Anda bisa menambah sheet 2 & 3 sesuai kebutuhan data pendidikan/pekerjaan) ...

        $filename = "Export_Agregat_" . date('Ymd_His') . ".xlsx";
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit();
    }
    public function index()
    {
        $user = session()->get();

        // 1. Menangani Request Data via AJAX (Server-side)
        if ($this->request->isAJAX()) {
            $draw = $this->request->getVar('draw');
            $start = $this->request->getVar('start');
            $length = $this->request->getVar('length');
            $search = $this->request->getVar('search')['value'] ?? null;
            $id_desa_filter = $this->request->getVar('id_desa'); // Filter khusus admin kecamatan
            $id_kecamatan_filter = $this->request->getVar('id_kecamatan');
            $id_desa_filter = $this->request->getVar('id_desa');

            // Total semua data (sebelum 
            $totalData = $this->laporanModel->countAll();

            $builder = $this->laporanModel->select('laporan_agregat.*, m_desa.nama_desa, m_dusun.nama_dusun, m_rt.no_rt, kecamatan.nama_kecamatan')
                ->join('m_rt', 'm_rt.id_rt = laporan_agregat.id_rt')
                ->join('m_dusun', 'm_dusun.id_dusun = m_rt.id_dusun')
                ->join('m_desa', 'm_desa.id_desa = m_dusun.id_desa')
                ->join('kecamatan', 'kecamatan.id_kecamatan = m_desa.id_kecamatan');

            if ($user['role'] == 'admin_dinas') {
                // Jika Desa dipilih, filter berdasarkan Desa (Otomatis masuk dalam Kecamatan tersebut)
                if (!empty($id_desa_filter)) {
                    $builder->where('m_desa.id_desa', $id_desa_filter);
                }
                // Jika hanya Kecamatan yang dipilih
                elseif (!empty($id_kecamatan_filter)) {
                    $builder->where('m_desa.id_kecamatan', $id_kecamatan_filter);
                }
            } elseif ($user['role'] == 'admin_kecamatan') {
                $builder->where('m_desa.id_kecamatan', $user['id_kecamatan']);
                if (!empty($id_desa_filter)) {
                    $builder->where('m_desa.id_desa', $id_desa_filter);
                }
            } elseif ($user['role'] == 'admin_desa') {
                $builder->where('m_desa.id_desa', $user['id_desa']);

            }


            // Search Filter
            if ($search) {
                $builder->groupStart()
                    ->like('m_dusun.nama_dusun', $search)
                    ->orLike('laporan_agregat.tahun', $search)
                    ->orLike('m_desa.nama_desa', $search)
                    ->groupEnd();
            }

            // Clone builder untuk mendapatkan total yang difilter
            $builderFiltered = clone $builder;
            $totalFiltered = $builderFiltered->countAllResults(false);

            // Ambil data dengan limit & offset
            $laporan = $builder->orderBy('laporan_agregat.id_laporan', 'DESC')
                ->findAll($length, $start);

            // Mapping Data untuk JSON
            $data = [];
            $no = $start + 1;

            foreach ($laporan as $row) {
                // Button Aksi
                $editBtn = '<a href="' . base_url('laporan/edit/' . $row['id_laporan']) . '" class="btn btn-sm btn-warning" title="Edit"><i class="fas fa-edit"></i></a>';
                $deleteBtn = '<button type="button" class="btn btn-sm btn-danger" onclick="confirmDelete(' . $row['id_laporan'] . ')" title="Hapus"><i class="fas fa-trash"></i></button>';

                $rowArray = [];
                $rowArray[] = $no++;
                $rowArray[] = '<strong>' . ($this->bulanList[$row['bulan']] ?? $row['bulan']) . '</strong> ' . $row['tahun'];

                // Kolom Desa (Hanya untuk admin kecamatan)
                if ($user['role'] == 'admin_kecamatan') {
                    $rowArray[] = '<span class="badge badge-secondary">' . esc($row['nama_desa']) . '</span>';
                }

                $rowArray[] = esc($row['nama_dusun']);
                $rowArray[] = 'RT ' . esc($row['no_rt']);
                $rowArray[] = '<span class="badge badge-info">' . number_format($row['jiwa_l'] + $row['jiwa_p']) . ' Jiwa</span>';
                $rowArray[] = '<span class="badge badge-success">' . number_format($row['kk_l'] + $row['kk_p']) . ' KK</span>';
                $rowArray[] = $editBtn . ' ' . $deleteBtn;

                $data[] = $rowArray;
            }

            $output = [
                "draw" => $draw,
                "recordsTotal" => $totalData,
                "recordsFiltered" => $totalFiltered,
                "data" => $data,
            ];

            return $this->response->setJSON($output);
        }

        // 2. Menampilkan Halaman Utama (Request Biasa)
        $list_desa = [];
        if ($user['role'] == 'admin_kecamatan') {
            $desaModel = new \App\Models\DesaModel();
            $list_desa = $desaModel->where('id_kecamatan', $user['id_kecamatan'])->findAll();
        } elseif ($user['role'] == 'admin_dinas') {
            $desaModel = new \App\Models\DesaModel();
            $list_desa = $desaModel->findAll();
        }

        $data = [
            'title' => 'Data Laporan Agregat',
            'list_kecamatan' => ($user['role'] == 'admin_dinas') ? $this->kecamatanModel->findAll() : [],
            // Jika admin kecamatan, ambil desa di kecamatannya saja
            'list_desa' => ($user['role'] == 'admin_kecamatan') ? (new \App\Models\DesaModel())->where('id_kecamatan', $user['id_kecamatan'])->findAll() : []
        ];

        return view('laporan/index', $data);
    }

    // Tambahkan di dalam class LaporanAgregatController

    public function getPreviousData()
    {
        $id_rt = $this->request->getGet('id_rt');

        // Cari data terbaru berdasarkan RT tersebut
        $lastData = $this->laporanModel
            ->where('id_rt', $id_rt)
            ->orderBy('tahun', 'DESC')
            ->orderBy('bulan', 'DESC')
            ->first();

        if ($lastData) {
            return $this->response->setJSON([
                'status' => 'success',
                'data' => $lastData
            ]);
        }

        return $this->response->setJSON([
            'status' => 'error',
            'message' => 'Tidak ditemukan data sebelumnya untuk RT ini.'
        ]);
    }

    // Form Input Baru
    public function create()
    {
        $user = session()->get();

        // Ambil daftar RT berdasarkan Role
        if ($user['role'] == 'admin_desa') {
            $list_rt = $this->rtModel->select('m_rt.*, m_dusun.nama_dusun')
                ->join('m_dusun', 'm_dusun.id_dusun = m_rt.id_dusun')
                ->where('m_dusun.id_desa', $user['id_desa'])
                ->findAll();
        } elseif ($user['role'] == 'admin_kecamatan') {
            // Admin Kecamatan melihat SEMUA RT dari SEMUA DESA di kecamatannya
            $list_rt = $this->rtModel->select('m_rt.*, m_dusun.nama_dusun, m_desa.nama_desa')
                ->join('m_dusun', 'm_dusun.id_dusun = m_rt.id_dusun')
                ->join('m_desa', 'm_desa.id_desa = m_dusun.id_desa')
                ->where('m_desa.id_kecamatan', $user['id_kecamatan'])
                ->orderBy('m_desa.nama_desa', 'ASC')
                ->findAll();
        } else {
            // Admin Dinas melihat SEMUA RT
            $list_rt = $this->rtModel->select('m_rt.*, m_dusun.nama_dusun, m_desa.nama_desa')
                ->join('m_dusun', 'm_dusun.id_dusun = m_rt.id_dusun')
                ->join('m_desa', 'm_desa.id_desa = m_dusun.id_desa')
                ->orderBy('m_desa.nama_desa', 'ASC')
                ->findAll();
        }

        $data = [
            'title' => 'Input Laporan Baru',
            'list_rt' => $list_rt,
            'bulan' => $this->bulanList
        ];

        return view('laporan/form', $data);
    }

    public function store()
    {
        $data = $this->request->getPost();
        $userId = session()->get('id_user');

        if (!$userId) {
            return redirect()->back()->with('error', 'Sesi habis, silakan login ulang.');
        }

        // --- VALIDASI DUPLIKAT ---
        $exists = $this->laporanModel->where([
            'id_rt' => $data['id_rt'],
            'bulan' => $data['bulan'],
            'tahun' => $data['tahun']
        ])->first();

        if ($exists) {
            return redirect()->back()->withInput()->with(
                'error',
                "Data untuk RT tersebut pada periode <b>" . $this->bulanList[$data['bulan']] . " " . $data['tahun'] . "</b> sudah ada! 
            <a href='/laporan/edit/" . $exists['id_laporan'] . "' class='alert-link'>Klik di sini untuk edit data tersebut.</a>"
            );
        }

        $data['id_user'] = $userId;

        if ($this->laporanModel->insert($data)) {
            return redirect()->to('/laporan')->with('success', 'Laporan berhasil disimpan.');
        }
        return redirect()->back()->withInput()->with('error', 'Gagal menyimpan data.');
    }
    // Form Edit
    public function edit($id)
    {
        $user = session()->get();
        $laporan = $this->laporanModel->find($id);

        if (!$laporan)
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();

        // SECURITY CHECK: Pastikan Admin Kecamatan hanya bisa edit data di wilayahnya
        if ($user['role'] == 'admin_kecamatan') {
            $cekWilayah = $this->laporanModel->select('m_desa.id_kecamatan')
                ->join('m_rt', 'm_rt.id_rt = laporan_agregat.id_rt')
                ->join('m_dusun', 'm_dusun.id_dusun = m_rt.id_dusun')
                ->join('m_desa', 'm_desa.id_desa = m_dusun.id_desa')
                ->where('laporan_agregat.id_laporan', $id)
                ->first();

            if ($cekWilayah['id_kecamatan'] != $user['id_kecamatan']) {
                return redirect()->to('/laporan')->with('error', 'Anda tidak memiliki akses ke data di luar wilayah kecamatan Anda.');
            }
        }

        // Ambil list RT (Gunakan logika yang sama dengan create untuk scope wilayah)
        $list_rt = ($user['role'] == 'admin_kecamatan')
            ? $this->rtModel->select('m_rt.*, m_desa.nama_desa, m_dusun.nama_dusun , m_rt.no_rt')->join('m_dusun', 'm_dusun.id_dusun=m_rt.id_dusun')->join('m_desa', 'm_desa.id_desa=m_dusun.id_desa')->where('m_desa.id_kecamatan', $user['id_kecamatan'])->findAll()
            : $this->rtModel->getRtWithDusun();

        $data = [
            'title' => 'Edit Laporan',
            'laporan' => $laporan,
            'list_rt' => $list_rt,
            'bulan' => $this->bulanList
        ];
        return view('laporan/form', $data);
    }

    // Update Data
    public function update($id)
    {
        if ($this->laporanModel->update($id, $this->request->getPost())) {
            return redirect()->to('/laporan')->with('success', 'Laporan diperbarui.');
        }
        return redirect()->back()->with('error', 'Gagal update.');
    }

    // Hapus Data
    public function delete($id)
    {
        $this->laporanModel->delete($id);
        return redirect()->to('/laporan')->with('success', 'Laporan dihapus.');
    }

    public function export($format = 'excel')
    {
        $user = session()->get();
        $id_kecamatan = $this->request->getGet('id_kecamatan');
        $id_desa = $this->request->getGet('id_desa');

        // 1. Inisialisasi Builder dengan Join Lengkap
        $builder = $this->laporanModel->select('laporan_agregat.*, m_desa.nama_desa, kecamatan.nama_kecamatan, m_rt.no_rt, m_dusun.nama_dusun')
            ->join('m_rt', 'm_rt.id_rt = laporan_agregat.id_rt')
            ->join('m_dusun', 'm_dusun.id_dusun = m_rt.id_dusun')
            ->join('m_desa', 'm_desa.id_desa = m_dusun.id_desa')
            ->join('kecamatan', 'kecamatan.id_kecamatan = m_desa.id_kecamatan');

        // 2. Filter Berdasarkan Role & Parameter GET
        if ($user['role'] == 'admin_dinas') {
            if (!empty($id_kecamatan))
                $builder->where('kecamatan.id_kecamatan', $id_kecamatan);
            if (!empty($id_desa))
                $builder->where('m_desa.id_desa', $id_desa);
        } elseif ($user['role'] == 'admin_kecamatan') {
            $builder->where('kecamatan.id_kecamatan', $user['id_kecamatan']);
            if (!empty($id_desa))
                $builder->where('m_desa.id_desa', $id_desa);
        } else {
            // admin_desa
            $builder->where('m_desa.id_desa', $user['id_desa']);
        }

        $dataLaporan = $builder->orderBy('laporan_agregat.tahun', 'DESC')
            ->orderBy('laporan_agregat.bulan', 'DESC')
            ->findAll();

        if (empty($dataLaporan)) {
            return redirect()->back()->with('error', 'Tidak ada data untuk diexport.');
        }

        // 3. Eksekusi Berdasarkan Format
        if ($format == 'pdf') {
            return $this->generatePdf($dataLaporan, $user);
        } else {
            return $this->generateExcel($dataLaporan);
        }
    }
    private function writeHeader($sheet, $headers)
    {
        $column = 'A';
        foreach ($headers as $h) {
            $sheet->setCellValue($column . '1', $h);

            // Membuat teks tebal (Bold)
            $sheet->getStyle($column . '1')->getFont()->setBold(true);

            // Memberikan warna background abu-abu muda pada header
            $sheet->getStyle($column . '1')->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setARGB('F2F2F2');

            // Mengatur agar lebar kolom otomatis menyesuaikan teks
            $sheet->getColumnDimension($column)->setAutoSize(true);

            $column++;
        }
    }
}