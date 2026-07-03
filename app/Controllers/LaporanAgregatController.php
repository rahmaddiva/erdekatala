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

    private function generatePdf($laporan, $user, array $sections = [])
    {
        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'Helvetica');
        $dompdf = new Dompdf($options);

        if (empty($sections)) {
            $sections = ['pokok', 'pendidikan', 'pekerjaan', 'piramida', 'kawin', 'jkn', 'dokumen', 'kb'];
        }

        $data = [
            'title'    => 'LAPORAN AGREGAT KEPENDUDUKAN',
            'laporan'  => $laporan,
            'user'     => $user,
            'tanggal'  => date('d F Y'),
            'sections' => array_flip($sections), // flip agar cek dengan isset() lebih cepat
        ];

        $html = view('laporan/export_pdf', $data);

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        $filename = "Laporan_Agregat_" . date('Ymd_His') . ".pdf";

        return $this->response
            ->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', 'inline; filename="' . $filename . '"')
            ->setBody($dompdf->output());
    }

    private function generateExcel($laporan, array $sections = [])
    {
        if (empty($sections)) {
            $sections = ['pokok', 'pendidikan', 'pekerjaan', 'piramida', 'kawin', 'jkn', 'dokumen', 'kb'];
        }
        $sec = array_flip($sections);

        $spreadsheet = new Spreadsheet();
        $sheetIdx    = 0;

        // Sheet: Data Pokok
        if (isset($sec['pokok'])) {
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Data Pokok');
            $headers = ['No', 'Kecamatan', 'Desa', 'Dusun', 'RT', 'Bulan', 'Tahun', 'Jiwa L', 'Jiwa P', 'KK L', 'KK P'];
            $this->writeHeader($sheet, $headers);
            $rowIdx = 2;
            foreach ($laporan as $row) {
                $sheet->fromArray([
                    $rowIdx - 1, $row['nama_kecamatan'], $row['nama_desa'], $row['nama_dusun'],
                    $row['no_rt'], $this->bulanList[$row['bulan']] ?? $row['bulan'], $row['tahun'],
                    $row['jiwa_l'], $row['jiwa_p'], $row['kk_l'], $row['kk_p']
                ], NULL, 'A' . $rowIdx++);
            }
            $sheetIdx++;
        }

        // Sheet: Pendidikan
        if (isset($sec['pendidikan'])) {
            $sheet = $sheetIdx === 0 ? $spreadsheet->getActiveSheet() : $spreadsheet->createSheet();
            $sheet->setTitle('Pendidikan KK');
            $this->writeHeader($sheet, ['No','Wilayah','T.Sekolah','SD','SMP','SMA','Diploma','S1','S2/S3']);
            $rowIdx = 2;
            foreach ($laporan as $row) {
                $sheet->fromArray([
                    $rowIdx - 1, 'RT'.$row['no_rt'].'-'.$row['nama_dusun'],
                    $row['kk_pend_tidak_sekolah'], $row['kk_pend_sd'], $row['kk_pend_smp'],
                    $row['kk_pend_sma'], $row['kk_pend_diploma'], $row['kk_pend_s1'], $row['kk_pend_s2_s3']
                ], NULL, 'A' . $rowIdx++);
            }
            $sheetIdx++;
        }

        // Sheet: Pekerjaan
        if (isset($sec['pekerjaan'])) {
            $sheet = $sheetIdx === 0 ? $spreadsheet->getActiveSheet() : $spreadsheet->createSheet();
            $sheet->setTitle('Pekerjaan KK');
            $this->writeHeader($sheet, ['No','Wilayah','Tani','Nelayan','PNS','Swasta','Pedagang','Wiraswasta','Buruh','Tdk Kerja']);
            $rowIdx = 2;
            foreach ($laporan as $row) {
                $sheet->fromArray([
                    $rowIdx - 1, 'RT'.$row['no_rt'].'-'.$row['nama_dusun'],
                    $row['kk_ker_tani'], $row['kk_ker_nelayan'], $row['kk_ker_pns'],
                    $row['kk_ker_swasta'], $row['kk_ker_pedagang'], $row['kk_ker_wiraswasta'],
                    $row['kk_ker_buruh'], $row['kk_ker_tidak_kerja']
                ], NULL, 'A' . $rowIdx++);
            }
            $sheetIdx++;
        }

        // Sheet: Piramida Penduduk
        if (isset($sec['piramida'])) {
            $sheet = $sheetIdx === 0 ? $spreadsheet->getActiveSheet() : $spreadsheet->createSheet();
            $sheet->setTitle('Piramida Penduduk');
            $headers = ['No','Wilayah'];
            $ageGroups = ['0-4','5-9','10-14','15-19','20-24','25-29','30-34','35-39','40-44','45-49','50-54','55-59','60-64','65-69','70-74','75-79','80-84','85+'];
            foreach ($ageGroups as $ag) { $headers[] = $ag.' L'; $headers[] = $ag.' P'; }
            $this->writeHeader($sheet, $headers);
            $rowIdx = 2;
            $fields = ['u0_4','u5_9','u10_14','u15_19','u20_24','u25_29','u30_34','u35_39','u40_44','u45_49','u50_54','u55_59','u60_64','u65_69','u70_74','u75_79','u80_84','u85_atas'];
            foreach ($laporan as $row) {
                $r = [$rowIdx - 1, 'RT'.$row['no_rt']];
                foreach ($fields as $f) { $r[] = $row[$f.'_l']; $r[] = $row[$f.'_p']; }
                $sheet->fromArray($r, NULL, 'A' . $rowIdx++);
            }
            $sheetIdx++;
        }

        // Sheet: Status Perkawinan
        if (isset($sec['kawin'])) {
            $sheet = $sheetIdx === 0 ? $spreadsheet->getActiveSheet() : $spreadsheet->createSheet();
            $sheet->setTitle('Status Perkawinan');
            $this->writeHeader($sheet, ['No','Wilayah','Belum Kawin','Kawin','Cerai Hidup','Cerai Mati']);
            $rowIdx = 2;
            foreach ($laporan as $row) {
                $sheet->fromArray([
                    $rowIdx - 1, 'RT'.$row['no_rt'].'-'.$row['nama_dusun'],
                    $row['kk_belum_kawin'], $row['kk_kawin'], $row['kk_cerai_hidup'], $row['kk_cerai_mati']
                ], NULL, 'A' . $rowIdx++);
            }
            $sheetIdx++;
        }

        // Sheet: JKN/BPJS
        if (isset($sec['jkn'])) {
            $sheet = $sheetIdx === 0 ? $spreadsheet->getActiveSheet() : $spreadsheet->createSheet();
            $sheet->setTitle('JKN-BPJS');
            $this->writeHeader($sheet, ['No','Wilayah','BPJS PBI','BPJS Non-PBI','Tidak Ber-JKN','Total BPJS']);
            $rowIdx = 2;
            foreach ($laporan as $row) {
                $sheet->fromArray([
                    $rowIdx - 1, 'RT'.$row['no_rt'].'-'.$row['nama_dusun'],
                    $row['pus_pbi'], $row['pus_non_pbi'], $row['non_jkn'],
                    $row['pus_pbi'] + $row['pus_non_pbi']
                ], NULL, 'A' . $rowIdx++);
            }
            $sheetIdx++;
        }

        // Sheet: Dokumen Adminduk
        if (isset($sec['dokumen'])) {
            $sheet = $sheetIdx === 0 ? $spreadsheet->getActiveSheet() : $spreadsheet->createSheet();
            $sheet->setTitle('Dokumen Adminduk');
            $this->writeHeader($sheet, ['No','Wilayah','Wajib KTP','Punya Akta Lahir','Punya Akta Nikah','KK Fisik','Blm KK Fisik']);
            $rowIdx = 2;
            foreach ($laporan as $row) {
                $sheet->fromArray([
                    $rowIdx - 1, 'RT'.$row['no_rt'].'-'.$row['nama_dusun'],
                    $row['pend_wajib_ktp'], $row['pend_punya_akta_lahir'],
                    $row['kk_punya_akta_nikah'], $row['kk_punya_kartu_fisik'], $row['kk_belum_punya_kartu_fisik']
                ], NULL, 'A' . $rowIdx++);
            }
            $sheetIdx++;
        }

        // Sheet: KB & PUS
        if (isset($sec['kb'])) {
            $sheet = $sheetIdx === 0 ? $spreadsheet->getActiveSheet() : $spreadsheet->createSheet();
            $sheet->setTitle('KB dan PUS');
            $this->writeHeader($sheet, ['No','Wilayah','Balita','Remaja','Lansia','Jml PUS','KB Aktif','Alat Kontrasepsi']);
            $rowIdx = 2;
            foreach ($laporan as $row) {
                $sheet->fromArray([
                    $rowIdx - 1, 'RT'.$row['no_rt'].'-'.$row['nama_dusun'],
                    $row['jml_balita'], $row['jml_remaja'], $row['jml_lansia'],
                    $row['jml_pus'], $row['kb_aktif'], $row['jml_penggunaan_alat_kontrasepsi']
                ], NULL, 'A' . $rowIdx++);
            }
        }

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
            $bulan = $this->request->getVar('bulan'); // Ambil input bulan
            $tahun = $this->request->getVar('tahun'); // Ambil input tahun

            // Multi-column search parameters
            $search_periode = $this->request->getVar('search_periode');
            $search_desa = $this->request->getVar('search_desa');
            $search_dusun = $this->request->getVar('search_dusun');
            $search_rt = $this->request->getVar('search_rt');

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

            // Multi-column Search Filter
            if ($search_periode) {
                // Coba konversi nama bulan ke nomor (case-insensitive)
                $bulanNomor = null;
                foreach ($this->bulanList as $num => $nama) {
                    if (stripos($nama, $search_periode) !== false) {
                        $bulanNomor = $num;
                        break;
                    }
                }

                $builder->groupStart();

                // Cari berdasarkan nama bulan jika ditemukan
                if ($bulanNomor) {
                    $builder->where('laporan_agregat.bulan', $bulanNomor);
                }

                // Atau cari berdasarkan tahun/angka
                $builder->orLike('laporan_agregat.tahun', $search_periode);
                $builder->orLike('laporan_agregat.bulan', $search_periode);

                $builder->groupEnd();
            }

            if ($search_desa) {
                $builder->like('m_desa.nama_desa', $search_desa);
            }

            if ($search_dusun) {
                $builder->like('m_dusun.nama_dusun', $search_dusun);
            }

            if ($search_rt) {
                $builder->like('m_rt.no_rt', $search_rt);
            }

            // Filter bulan/tahun eksak dari dropdown
            if (!empty($bulan)) {
                $builder->where('laporan_agregat.bulan', (int)$bulan);
            }
            if (!empty($tahun)) {
                $builder->where('laporan_agregat.tahun', (int)$tahun);
            }

            // Original Search Filter (untuk backward compatibility)
            if ($search) {
                // Coba konversi nama bulan ke nomor (case-insensitive)
                $bulanNomor = null;
                foreach ($this->bulanList as $num => $nama) {
                    if (stripos($nama, $search) !== false) {
                        $bulanNomor = $num;
                        break;
                    }
                }

                $builder->groupStart()
                    ->like('m_dusun.nama_dusun', $search)
                    ->orLike('laporan_agregat.tahun', $search)
                    ->orLike('m_rt.no_rt', $search)
                    ->orLike('nama_desa', $search);

                // Tambahkan pencarian bulan jika ditemukan nama bulan
                if ($bulanNomor) {
                    $builder->orWhere('laporan_agregat.bulan', $bulanNomor);
                }

                $builder->groupEnd();
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
        $desaModel  = new \App\Models\DesaModel();
        $dusunModel = new \App\Models\DusunModel();
        $rtModel    = new \App\Models\RtModel();

        // --- Tampilan khusus admin_desa: accordion timeline per bulan ---
        if ($user['role'] == 'admin_desa') {
            $filterBulan = $this->request->getGet('bulan');
            $filterTahun = $this->request->getGet('tahun');

            $query = $this->laporanModel
                ->select('laporan_agregat.*, m_rt.no_rt, m_dusun.nama_dusun')
                ->join('m_rt', 'm_rt.id_rt = laporan_agregat.id_rt')
                ->join('m_dusun', 'm_dusun.id_dusun = m_rt.id_dusun')
                ->where('m_dusun.id_desa', $user['id_desa']);

            if (!empty($filterBulan)) {
                $query->where('laporan_agregat.bulan', (int)$filterBulan);
            }
            if (!empty($filterTahun)) {
                $query->where('laporan_agregat.tahun', (int)$filterTahun);
            }

            $allLaporan = $query
                ->orderBy('laporan_agregat.tahun', 'DESC')
                ->orderBy('laporan_agregat.bulan', 'DESC')
                ->findAll();

            $allRt = $rtModel->select('m_rt.id_rt, m_rt.no_rt, m_dusun.nama_dusun')
                ->join('m_dusun', 'm_dusun.id_dusun = m_rt.id_dusun')
                ->where('m_dusun.id_desa', $user['id_desa'])
                ->findAll();

            $grouped = [];
            foreach ($allLaporan as $l) {
                $grouped[$l['tahun']][$l['bulan']][] = $l;
            }
            krsort($grouped);
            foreach ($grouped as $tahun => &$bulanData) {
                krsort($bulanData);
            }
            unset($bulanData);

            $data = [
                'title'       => 'Riwayat Laporan Agregat',
                'bulanList'   => $this->bulanList,
                'grouped'     => $grouped,
                'allRt'       => $allRt,
                'totalRt'     => count($allRt),
                'filterBulan' => $filterBulan,
                'filterTahun' => $filterTahun,
            ];
            return view('laporan/index_desa', $data);
        }

        // --- Tampilan khusus admin_kecamatan: card grid per desa ---
        if ($user['role'] == 'admin_kecamatan') {
            $filterBulan = $this->request->getGet('bulan') ?? date('n');
            $filterTahun = $this->request->getGet('tahun') ?? date('Y');

            $listDesa = $desaModel->where('id_kecamatan', $user['id_kecamatan'])->findAll();

            // Single query: RT count + laporan count per desa untuk periode ini
            $db = \Config\Database::connect();
            $statsRows = $db->table('m_desa d')
                ->select('d.id_desa, COUNT(DISTINCT r.id_rt) as total_rt, COUNT(DISTINCT la.id_laporan) as sudah_lapor')
                ->join('m_dusun du', 'du.id_desa = d.id_desa', 'left')
                ->join('m_rt r', 'r.id_dusun = du.id_dusun', 'left')
                ->join('laporan_agregat la', "la.id_rt = r.id_rt AND la.bulan = {$filterBulan} AND la.tahun = {$filterTahun}", 'left')
                ->where('d.id_kecamatan', $user['id_kecamatan'])
                ->groupBy('d.id_desa')
                ->get()->getResultArray();
            $statsMap = array_column($statsRows, null, 'id_desa');

            $desaStats = [];
            foreach ($listDesa as $desa) {
                $s = $statsMap[$desa['id_desa']] ?? ['total_rt' => 0, 'sudah_lapor' => 0];
                $total = (int)$s['total_rt'];
                $sudah = (int)$s['sudah_lapor'];
                $desaStats[] = [
                    'desa'        => $desa,
                    'total_rt'    => $total,
                    'sudah_lapor' => $sudah,
                    'belum_lapor' => $total - $sudah,
                    'persen'      => $total > 0 ? round($sudah / $total * 100) : 0,
                ];
            }

            $data = [
                'title'       => 'Riwayat Laporan Agregat',
                'bulanList'   => $this->bulanList,
                'desaStats'   => $desaStats,
                'filterBulan' => $filterBulan,
                'filterTahun' => $filterTahun,
                'list_desa'   => $listDesa,
            ];
            return view('laporan/index_kecamatan', $data);
        }

        // --- Tampilan default admin_dinas: datatable ---
        $data = [
            'title'          => 'Data Laporan Agregat',
            'bulanList'      => $this->bulanList,
            'list_kecamatan' => $this->kecamatanModel->findAll(),
            'list_desa'      => [],
        ];
        return view('laporan/index', $data);
    }


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
            $rtRow = $this->rtModel->select('m_rt.id_dusun, m_dusun.id_desa, m_desa.id_kecamatan')
                ->join('m_dusun', 'm_dusun.id_dusun = m_rt.id_dusun')
                ->join('m_desa', 'm_desa.id_desa = m_dusun.id_desa')
                ->where('m_rt.id_rt', $laporan['id_rt'])
                ->first();

            if (!$rtRow || $rtRow['id_kecamatan'] != $user['id_kecamatan']) {
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

    public function exportOptions()
    {
        $user    = session()->get();
        $desaModel = new \App\Models\DesaModel();

        if ($this->request->getMethod() === 'post') {
            $format   = $this->request->getPost('format') ?? 'pdf';
            $sections = $this->request->getPost('sections') ?? [];
            $id_kecamatan = $this->request->getPost('id_kecamatan');
            $id_desa      = $this->request->getPost('id_desa');
            $bulan        = $this->request->getPost('bulan');
            $tahun        = $this->request->getPost('tahun');

            $builder = $this->laporanModel
                ->select('laporan_agregat.*, m_desa.nama_desa, kecamatan.nama_kecamatan, m_rt.no_rt, m_dusun.nama_dusun')
                ->join('m_rt',       'm_rt.id_rt = laporan_agregat.id_rt')
                ->join('m_dusun',    'm_dusun.id_dusun = m_rt.id_dusun')
                ->join('m_desa',     'm_desa.id_desa = m_dusun.id_desa')
                ->join('kecamatan',  'kecamatan.id_kecamatan = m_desa.id_kecamatan');

            if ($user['role'] == 'admin_dinas') {
                if (!empty($id_kecamatan)) $builder->where('kecamatan.id_kecamatan', $id_kecamatan);
                if (!empty($id_desa))      $builder->where('m_desa.id_desa', $id_desa);
            } elseif ($user['role'] == 'admin_kecamatan') {
                $builder->where('kecamatan.id_kecamatan', $user['id_kecamatan']);
                if (!empty($id_desa)) $builder->where('m_desa.id_desa', $id_desa);
            } else {
                $builder->where('m_desa.id_desa', $user['id_desa']);
            }

            if ($bulan) $builder->where('laporan_agregat.bulan', $bulan);
            if ($tahun) $builder->where('laporan_agregat.tahun', $tahun);

            $dataLaporan = $builder->orderBy('laporan_agregat.tahun', 'DESC')
                ->orderBy('laporan_agregat.bulan', 'DESC')
                ->findAll();

            if (empty($dataLaporan)) {
                return redirect()->back()->with('error', 'Tidak ada data untuk periode yang dipilih.');
            }

            if ($format === 'pdf') {
                return $this->generatePdf($dataLaporan, $user, $sections);
            } else {
                return $this->generateExcel($dataLaporan, $sections);
            }
        }

        // GET: tampilkan form pilihan
        $data = [
            'title'          => 'Pilihan Cetak / Export',
            'bulanList'      => $this->bulanList,
            'role'           => $user['role'],
            'list_kecamatan' => ($user['role'] == 'admin_dinas') ? $this->kecamatanModel->findAll() : [],
            'list_desa'      => ($user['role'] == 'admin_kecamatan')
                ? $desaModel->where('id_kecamatan', $user['id_kecamatan'])->findAll()
                : [],
        ];
        return view('laporan/export_options', $data);
    }

    public function export($format = 'excel')
    {
        $user = session()->get();
        $id_kecamatan = $this->request->getGet('id_kecamatan');
        $id_desa      = $this->request->getGet('id_desa');
        $bulan        = $this->request->getGet('bulan');
        $tahun        = $this->request->getGet('tahun');

        $builder = $this->laporanModel->select('laporan_agregat.*, m_desa.nama_desa, kecamatan.nama_kecamatan, m_rt.no_rt, m_dusun.nama_dusun')
            ->join('m_rt',      'm_rt.id_rt = laporan_agregat.id_rt')
            ->join('m_dusun',   'm_dusun.id_dusun = m_rt.id_dusun')
            ->join('m_desa',    'm_desa.id_desa = m_dusun.id_desa')
            ->join('kecamatan', 'kecamatan.id_kecamatan = m_desa.id_kecamatan');

        if ($user['role'] == 'admin_dinas') {
            if (!empty($id_kecamatan)) $builder->where('kecamatan.id_kecamatan', $id_kecamatan);
            if (!empty($id_desa))      $builder->where('m_desa.id_desa', $id_desa);
        } elseif ($user['role'] == 'admin_kecamatan') {
            $builder->where('kecamatan.id_kecamatan', $user['id_kecamatan']);
            if (!empty($id_desa)) $builder->where('m_desa.id_desa', $id_desa);
        } else {
            $builder->where('m_desa.id_desa', $user['id_desa']);
        }

        if ($bulan) $builder->where('laporan_agregat.bulan', $bulan);
        if ($tahun) $builder->where('laporan_agregat.tahun', $tahun);

        $dataLaporan = $builder->orderBy('laporan_agregat.tahun', 'DESC')
            ->orderBy('laporan_agregat.bulan', 'DESC')
            ->findAll();

        if (empty($dataLaporan)) {
            return redirect()->back()->with('error', 'Tidak ada data untuk diexport.');
        }

        // Export cepat tanpa pilihan section = semua section
        $allSections = ['pokok', 'pendidikan', 'pekerjaan', 'piramida', 'kawin', 'jkn', 'dokumen', 'kb'];

        if ($format == 'pdf') {
            return $this->generatePdf($dataLaporan, $user, $allSections);
        } else {
            return $this->generateExcel($dataLaporan, $allSections);
        }
    }
    public function detailDesa($id_desa)
    {
        $bulan = $this->request->getGet('bulan') ?? date('n');
        $tahun = $this->request->getGet('tahun') ?? date('Y');

        $rtModel = new \App\Models\RtModel();

        // Ambil semua RT di desa ini beserta dusunnya
        $allRt = $rtModel->select('m_rt.id_rt, m_rt.no_rt, m_dusun.nama_dusun')
            ->join('m_dusun', 'm_dusun.id_dusun = m_rt.id_dusun')
            ->where('m_dusun.id_desa', $id_desa)
            ->orderBy('m_dusun.nama_dusun', 'ASC')
            ->orderBy('m_rt.no_rt', 'ASC')
            ->findAll();

        // Ambil laporan yang sudah ada untuk periode ini
        $rtIds = array_column($allRt, 'id_rt');
        $laporanAda = [];
        if (!empty($rtIds)) {
            $rows = $this->laporanModel
                ->select('id_laporan, id_rt, bulan, tahun, jiwa_l, jiwa_p, kk_l, kk_p')
                ->whereIn('id_rt', $rtIds)
                ->where('bulan', $bulan)
                ->where('tahun', $tahun)
                ->findAll();
            foreach ($rows as $r) {
                $laporanAda[$r['id_rt']] = $r;
            }
        }

        // Gabungkan data RT dengan status laporan
        $data = [];
        foreach ($allRt as $rt) {
            $laporan = $laporanAda[$rt['id_rt']] ?? null;
            $data[] = array_merge($rt, [
                'id_laporan' => $laporan['id_laporan'] ?? null,
                'jiwa_l'     => $laporan['jiwa_l'] ?? 0,
                'jiwa_p'     => $laporan['jiwa_p'] ?? 0,
                'kk_l'       => $laporan['kk_l'] ?? 0,
                'kk_p'       => $laporan['kk_p'] ?? 0,
            ]);
        }

        return $this->response->setJSON(['status' => 'success', 'data' => $data]);
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