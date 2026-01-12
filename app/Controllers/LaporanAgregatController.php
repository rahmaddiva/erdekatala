<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\LaporanAgregatModel;
use App\Models\RtModel;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class LaporanAgregatController extends BaseController
{
    protected $laporanModel;
    protected $rtModel;
    protected $bulanList;

    // Tambahkan ini di dalam class LaporanAgregatController

    /**
     * Helper untuk membuat header otomatis, menebalkan font, 
     * dan mengatur lebar kolom otomatis.
     */

    public function __construct()
    {
        $this->laporanModel = new LaporanAgregatModel();
        $this->rtModel = new RtModel();
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

            // Total semua data (sebelum difilter)
            $totalData = $this->laporanModel->countAll();

            // Builder dasar dengan Join
            $builder = $this->laporanModel->select('laporan_agregat.*, m_desa.nama_desa, m_dusun.nama_dusun, m_rt.no_rt')
                ->join('m_rt', 'm_rt.id_rt = laporan_agregat.id_rt')
                ->join('m_dusun', 'm_dusun.id_dusun = m_rt.id_dusun')
                ->join('m_desa', 'm_desa.id_desa = m_dusun.id_desa');

            // Filter Berdasarkan Hak Akses (Role)
            if ($user['role'] == 'admin_desa') {
                $builder->where('m_desa.id_desa', $user['id_desa']);
            } elseif ($user['role'] == 'admin_kecamatan') {
                $builder->where('m_desa.id_kecamatan', $user['id_kecamatan']);
                // Tambahan filter dropdown desa jika dipilih
                if ($id_desa_filter) {
                    $builder->where('m_desa.id_desa', $id_desa_filter);
                }
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
        }

        $data = [
            'title' => 'Riwayat Laporan Agregat',
            'list_desa' => $list_desa,
            'filter_desa' => $this->request->getVar('id_desa') ?? null,
        ];

        return view('laporan/index', $data);
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
            $list_rt = $this->rtModel->findAll();
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

    public function export()
    {
        $user = session()->get();
        $laporan = $this->laporanModel->getRekapByDesa($user['id_desa']);

        if (!$laporan)
            return redirect()->back()->with('error', 'Data tidak tersedia.');

        $spreadsheet = new Spreadsheet();
        $bulanNama = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

        // Definisi Style Border
        $styleBorder = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['argb' => 'FF000000'],
                ],
            ],
        ];

        // --- SHEET 1: SOSIAL EKONOMI ---
        $sheet1 = $spreadsheet->getActiveSheet();
        $sheet1->setTitle('Sosial Ekonomi');
        $h1 = ['No', 'RT', 'Bulan', 'Tahun', 'Jiwa L', 'Jiwa P', 'KK L', 'KK P', 'P_Tdk_Sekolah', 'P_SD', 'P_SMP', 'P_SMA', 'P_Diploma', 'P_S1', 'P_S2_S3', 'K_Tani', 'K_Nelayan', 'K_PNS', 'K_Swasta', 'K_Pedagang', 'K_Wiraswasta', 'K_Buruh', 'K_Tdk_Kerja', 'S_Kawin', 'S_Belum_Kawin', 'S_Cerai_Hidup', 'S_Cerai_Mati'];
        $this->writeHeader($sheet1, $h1);

        foreach ($laporan as $i => $row) {
            $rowIdx = $i + 2;
            $data = [$i + 1, $row['no_rt'], $bulanNama[$row['bulan']], $row['tahun'], $row['jiwa_l'], $row['jiwa_p'], $row['kk_l'], $row['kk_p'], $row['kk_pend_tidak_sekolah'], $row['kk_pend_sd'], $row['kk_pend_smp'], $row['kk_pend_sma'], $row['kk_pend_diploma'], $row['kk_pend_s1'], $row['kk_pend_s2_s3'], $row['kk_ker_tani'], $row['kk_ker_nelayan'], $row['kk_ker_pns'], $row['kk_ker_swasta'], $row['kk_ker_pedagang'], $row['kk_ker_wiraswasta'], $row['kk_ker_buruh'], $row['kk_ker_tidak_kerja'], $row['kk_kawin'], $row['kk_belum_kawin'], $row['kk_cerai_hidup'], $row['kk_cerai_mati']];
            $sheet1->fromArray($data, NULL, 'A' . $rowIdx);
        }
        // Terapkan Border Sheet 1
        $sheet1->getStyle('A1:AA' . (count($laporan) + 1))->applyFromArray($styleBorder);


        // --- SHEET 2: DEMOGRAFI ---
        $sheet2 = $spreadsheet->createSheet();
        $sheet2->setTitle('Demografi & Piramida');
        $groups = ['0_4', '5_9', '10_14', '15_19', '20_24', '25_29', '30_34', '35_39', '40_44', '45_49', '50_54', '55_59', '60_64', '65_69', '70_74', '75_79', '80_84', '85_atas'];
        $h2 = ['No', 'RT', 'Periode'];
        foreach ($groups as $g) {
            $h2[] = "L $g";
            $h2[] = "P $g";
        }
        array_push($h2, 'Balita', 'Remaja', 'Lansia');
        $this->writeHeader($sheet2, $h2);

        foreach ($laporan as $i => $row) {
            $rowIdx = $i + 2;
            $rowPira = [$i + 1, $row['no_rt'], $bulanNama[$row['bulan']] . ' ' . $row['tahun']];
            foreach ($groups as $g) {
                $rowPira[] = $row["u{$g}_l"];
                $rowPira[] = $row["u{$g}_p"];
            }
            array_push($rowPira, $row['jml_balita'], $row['jml_remaja'], $row['jml_lansia']);
            $sheet2->fromArray($rowPira, NULL, 'A' . $rowIdx);
        }
        // Terapkan Border Sheet 2 (Kolom A sampai AV)
        $sheet2->getStyle('A1:AV' . (count($laporan) + 1))->applyFromArray($styleBorder);


        // --- SHEET 3: KESEHATAN & DOKUMEN ---
        $sheet3 = $spreadsheet->createSheet();
        $sheet3->setTitle('Kesehatan & Dokumen');
        $h3 = ['No', 'RT', 'BPJS', 'Non-BPJS', 'KK_Fisik', 'KK_Belum_Fisik', 'H_Kepala', 'H_Istri', 'H_Anak', 'H_Lainnya', 'PUS_JKN', 'PUS_PBI', 'PUS_Non_PBI', 'JKN', 'Non_JKN', 'Wajib_KTP', 'Akta_Nikah', 'Akta_Lahir', 'KB_Aktif', 'Jml_PUS', 'Alat_Kontrasepsi'];
        $this->writeHeader($sheet3, $h3);

        foreach ($laporan as $i => $row) {
            $rowIdx = $i + 2;
            $data3 = [$i + 1, $row['no_rt'], $row['pend_bpjs'], $row['pend_non_bpjs'], $row['kk_punya_kartu_fisik'], $row['kk_belum_punya_kartu_fisik'], $row['penduduk_hub_kepala'], $row['penduduk_hub_istri'], $row['penduduk_hub_anak'], $row['penduduk_hub_lainnya'], $row['pus_jkn'], $row['pus_pbi'], $row['pus_non_pbi'], $row['jkn'], $row['non_jkn'], $row['pend_wajib_ktp'], $row['kk_punya_akta_nikah'], $row['pend_punya_akta_lahir'], $row['kb_aktif'], $row['jml_pus'], $row['jml_penggunaan_alat_kontrasepsi']];
            $sheet3->fromArray($data3, NULL, 'A' . $rowIdx);
        }
        // Terapkan Border Sheet 3 (Kolom A sampai U)
        $sheet3->getStyle('A1:U' . (count($laporan) + 1))->applyFromArray($styleBorder);

        // Proses Download
        $filename = "Export_Agregat_Lengkap_" . date('Ymd_His') . ".xlsx";
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit();
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