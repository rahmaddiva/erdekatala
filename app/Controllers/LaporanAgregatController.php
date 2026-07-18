<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\LaporanAgregatModel;
use App\Models\LaporanLogModel;
use App\Models\DesaModel;
use App\Models\KecamatanModel;
use Dompdf\Dompdf;
use Dompdf\Options;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class LaporanAgregatController extends BaseController
{
    protected $laporanModel;
    protected $desaModel;
    protected $bulanList;
    protected $kecamatanModel;
    protected $logModel;

    /**
     * Helper untuk membuat header otomatis, menebalkan font,
     * dan mengatur lebar kolom otomatis.
     */

    public function __construct()
    {
        $this->laporanModel = new LaporanAgregatModel();
        $this->desaModel    = new DesaModel();
        $this->logModel     = new LaporanLogModel();
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
            $headers = ['No', 'Kecamatan', 'Desa', 'Bulan', 'Tahun', 'Jiwa L', 'Jiwa P', 'KK L', 'KK P'];
            $this->writeHeader($sheet, $headers);
            $rowIdx = 2;
            foreach ($laporan as $row) {
                $sheet->fromArray([
                    $rowIdx - 1, $row['nama_kecamatan'], $row['nama_desa'],
                    $this->bulanList[$row['bulan']] ?? $row['bulan'], $row['tahun'],
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
                    $rowIdx - 1, $row['nama_desa'],
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
                    $rowIdx - 1, $row['nama_desa'],
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
                $r = [$rowIdx - 1, $row['nama_desa']];
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
                    $rowIdx - 1, $row['nama_desa'],
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
                    $rowIdx - 1, $row['nama_desa'],
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
                    $rowIdx - 1, $row['nama_desa'],
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
                    $rowIdx - 1, $row['nama_desa'],
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

            $builder = $this->laporanModel->select('laporan_agregat.id_laporan, laporan_agregat.id_desa, laporan_agregat.bulan, laporan_agregat.tahun, laporan_agregat.jiwa_l, laporan_agregat.jiwa_p, laporan_agregat.kk_l, laporan_agregat.kk_p, m_desa.nama_desa, kecamatan.nama_kecamatan')
                ->join('m_desa', 'm_desa.id_desa = laporan_agregat.id_desa')
                ->join('kecamatan', 'kecamatan.id_kecamatan = m_desa.id_kecamatan');

            if ($user['role'] == 'admin_dinas') {
                // Jika Desa dipilih, filter berdasarkan Desa
                if (!empty($id_desa_filter)) {
                    $builder->where('laporan_agregat.id_desa', $id_desa_filter);
                }
                // Jika hanya Kecamatan yang dipilih
                elseif (!empty($id_kecamatan_filter)) {
                    $builder->where('m_desa.id_kecamatan', $id_kecamatan_filter);
                }
            } elseif ($user['role'] == 'admin_kecamatan') {
                $builder->where('m_desa.id_kecamatan', $user['id_kecamatan']);
                if (!empty($id_desa_filter)) {
                    $builder->where('laporan_agregat.id_desa', $id_desa_filter);
                }
            } elseif ($user['role'] == 'admin_desa') {
                $builder->where('laporan_agregat.id_desa', $user['id_desa']);
            }

            // ponytail: scoped count — counts only what this role can see
            $totalData = (clone $builder)->countAllResults(false);

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

            // Filter bulan/tahun eksak dari dropdown
            if (!empty($bulan)) {
                $builder->where('laporan_agregat.bulan', (int)$bulan);
            }
            if (!empty($tahun)) {
                $builder->where('laporan_agregat.tahun', (int)$tahun);
            }

            // Original Search Filter (untuk backward compatibility)
            if ($search) {
                $bulanNomor = null;
                foreach ($this->bulanList as $num => $nama) {
                    if (stripos($nama, $search) !== false) {
                        $bulanNomor = $num;
                        break;
                    }
                }

                $builder->groupStart()
                    ->like('m_desa.nama_desa', $search)
                    ->orLike('laporan_agregat.tahun', $search);

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
                $editBtn    = '<a href="' . base_url('laporan/edit/' . $row['id_laporan']) . '" class="btn btn-sm btn-warning" title="Edit"><i class="fas fa-edit"></i></a>';
                $riwayatBtn = '<button type="button" class="btn btn-sm btn-info btn-riwayat" data-id="' . $row['id_laporan'] . '" title="Riwayat"><i class="fas fa-history"></i></button>';
                $deleteBtn  = '<button type="button" class="btn btn-sm btn-danger" onclick="confirmDelete(' . $row['id_laporan'] . ')" title="Hapus"><i class="fas fa-trash"></i></button>';

                $rowArray = [];
                $rowArray[] = $no++;
                $rowArray[] = '<strong>' . ($this->bulanList[$row['bulan']] ?? $row['bulan']) . '</strong> ' . $row['tahun'];

                // Kolom Desa (selalu tampil untuk admin kecamatan dan dinas)
                if (in_array($user['role'], ['admin_kecamatan', 'admin_dinas'])) {
                    $rowArray[] = '<span class="badge badge-secondary">' . esc($row['nama_desa']) . '</span>';
                }

                $rowArray[] = '<span class="badge badge-info">' . number_format($row['jiwa_l'] + $row['jiwa_p']) . ' Jiwa</span>';
                $rowArray[] = '<span class="badge badge-success">' . number_format($row['kk_l'] + $row['kk_p']) . ' KK</span>';
                $rowArray[] = $editBtn . ' ' . $riwayatBtn . ' ' . $deleteBtn;

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

        // --- Tampilan khusus admin_desa: accordion timeline per bulan ---
        if ($user['role'] == 'admin_desa') {
            $filterBulan = $this->request->getGet('bulan');
            $filterTahun = $this->request->getGet('tahun');

            $query = $this->laporanModel
                ->where('laporan_agregat.id_desa', $user['id_desa']);

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

            $allRt = []; // ponytail: tidak lagi digunakan, laporan per desa bukan per RT

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
                'totalRt'     => 0,
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

            // Single query: laporan count per desa untuk periode ini
            $db = \Config\Database::connect();
            $statsRows = $db->table('m_desa d')
                ->select('d.id_desa, COUNT(DISTINCT la.id_laporan) as sudah_lapor')
                ->join('laporan_agregat la', "la.id_desa = d.id_desa AND la.bulan = {$filterBulan} AND la.tahun = {$filterTahun}", 'left')
                ->where('d.id_kecamatan', $user['id_kecamatan'])
                ->groupBy('d.id_desa')
                ->get()->getResultArray();
            $statsMap = array_column($statsRows, null, 'id_desa');

            $desaStats = [];
            foreach ($listDesa as $desa) {
                $s = $statsMap[$desa['id_desa']] ?? ['sudah_lapor' => 0];
                $sudah = (int)$s['sudah_lapor'];
                $desaStats[] = [
                    'desa'        => $desa,
                    'total_rt'    => 0,
                    'sudah_lapor' => $sudah,
                    'belum_lapor' => $sudah > 0 ? 0 : 1,
                    'persen'      => $sudah > 0 ? 100 : 0,
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
        $id_desa = $this->request->getGet('id_desa');
        $bulan   = (int) $this->request->getGet('bulan');
        $tahun   = (int) $this->request->getGet('tahun');

        // Hitung periode tepat sebelumnya
        $lastData = null;
        if ($bulan && $tahun) {
            $prevBulan = $bulan > 1 ? $bulan - 1 : 12;
            $prevTahun = $bulan > 1 ? $tahun : $tahun - 1;

            $lastData = $this->laporanModel
                ->where('id_desa', $id_desa)
                ->where('bulan', $prevBulan)
                ->where('tahun', $prevTahun)
                ->first();
        }

        // Fallback: ambil record terbaru (backward-compat tombol manual)
        if (!$lastData) {
            $lastData = $this->laporanModel
                ->where('id_desa', $id_desa)
                ->orderBy('tahun', 'DESC')
                ->orderBy('bulan', 'DESC')
                ->first();
        }

        if ($lastData) {
            // Jangan kirim field identitas/timestamp
            foreach (['id_laporan', 'id_desa', 'bulan', 'tahun', 'id_user', 'created_at', 'updated_at'] as $key) {
                unset($lastData[$key]);
            }
            return $this->response->setJSON(['status' => 'success', 'data' => $lastData]);
        }

        return $this->response->setJSON([
            'status'  => 'error',
            'message' => 'Tidak ditemukan data sebelumnya untuk desa ini.'
        ]);
    }

    // Form Input Baru
    public function create()
    {
        $user = session()->get();

        if ($user['role'] == 'admin_kecamatan') {
            return redirect()->to('/laporan')->with('error', 'Admin kecamatan hanya dapat melihat data, tidak dapat menginput laporan.');
        }

        // Ambil daftar desa berdasarkan Role
        if ($user['role'] == 'admin_desa') {
            $list_desa = $this->desaModel->where('id_desa', $user['id_desa'])->findAll();
        } elseif ($user['role'] == 'admin_kecamatan') {
            $list_desa = $this->desaModel->where('id_kecamatan', $user['id_kecamatan'])->orderBy('nama_desa', 'ASC')->findAll();
        } else {
            $list_desa = $this->desaModel->orderBy('nama_desa', 'ASC')->findAll();
        }

        $data = [
            'title'     => 'Input Laporan Baru',
            'list_desa' => $list_desa,
            'bulan'     => $this->bulanList
        ];

        return view('laporan/form', $data);
    }

    public function store()
    {
        if (session()->get('role') == 'admin_kecamatan') {
            return redirect()->to('/laporan')->with('error', 'Admin kecamatan hanya dapat melihat data, tidak dapat menginput laporan.');
        }

        $data = $this->request->getPost();
        $userId = session()->get('id_user');

        if (!$userId) {
            return redirect()->back()->with('error', 'Sesi habis, silakan login ulang.');
        }

        // --- VALIDASI DUPLIKAT ---
        $exists = $this->laporanModel->where([
            'id_desa' => $data['id_desa'],
            'bulan'   => $data['bulan'],
            'tahun'   => $data['tahun']
        ])->first();

        if ($exists) {
            return redirect()->back()->withInput()->with(
                'error',
                "Data untuk desa tersebut pada periode <b>" . $this->bulanList[$data['bulan']] . " " . $data['tahun'] . "</b> sudah ada! 
            <a href='/laporan/edit/" . $exists['id_laporan'] . "' class='alert-link'>Klik di sini untuk edit data tersebut.</a>"
            );
        }

        $data['id_user'] = $userId;

        if ($id = $this->laporanModel->insert($data)) {
            $this->logModel->insert(['id_laporan' => $id, 'id_user' => $userId, 'aksi' => 'create']);
            return redirect()->to('/laporan')->with('success', 'Laporan berhasil disimpan.');
        }
        return redirect()->back()->withInput()->with('error', 'Gagal menyimpan data.');
    }
    // Form Edit
    public function edit($id)
    {
        $user = session()->get();

        if ($user['role'] == 'admin_kecamatan') {
            return redirect()->to('/laporan')->with('error', 'Admin kecamatan hanya dapat melihat data, tidak dapat mengubah laporan.');
        }

        $laporan = $this->laporanModel->find($id);

        if (!$laporan)
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();

        // Ambil list desa berdasarkan role
        if ($user['role'] == 'admin_desa') {
            $list_desa = $this->desaModel->where('id_desa', $user['id_desa'])->findAll();
        } elseif ($user['role'] == 'admin_kecamatan') {
            $list_desa = $this->desaModel->where('id_kecamatan', $user['id_kecamatan'])->orderBy('nama_desa', 'ASC')->findAll();
        } else {
            $list_desa = $this->desaModel->orderBy('nama_desa', 'ASC')->findAll();
        }

        $data = [
            'title'     => 'Edit Laporan',
            'laporan'   => $laporan,
            'list_desa' => $list_desa,
            'bulan'     => $this->bulanList
        ];
        return view('laporan/form', $data);
    }

    // Update Data
    public function update($id)
    {
        if (session()->get('role') == 'admin_kecamatan') {
            return redirect()->to('/laporan')->with('error', 'Admin kecamatan hanya dapat melihat data, tidak dapat mengubah laporan.');
        }

        $old  = $this->laporanModel->find($id);
        $post = $this->request->getPost();

        if ($this->laporanModel->update($id, $post)) {
            $diff = $this->buildDiff($old, $post);
            $this->logModel->insert([
                'id_laporan' => $id,
                'id_user'    => session()->get('id_user'),
                'aksi'       => 'update',
                'perubahan'  => $diff ? json_encode($diff) : null,
            ]);
            return redirect()->to('/laporan')->with('success', 'Laporan diperbarui.');
        }
        return redirect()->back()->with('error', 'Gagal update.');
    }

    // Hapus Data
    public function delete($id)
    {
        if (session()->get('role') == 'admin_kecamatan') {
            return redirect()->to('/laporan')->with('error', 'Admin kecamatan hanya dapat melihat data, tidak dapat menghapus laporan.');
        }

        $this->logModel->insert(['id_laporan' => $id, 'id_user' => session()->get('id_user'), 'aksi' => 'delete']);
        $this->laporanModel->delete($id);
        return redirect()->to('/laporan')->with('success', 'Laporan dihapus.');
    }

    // Riwayat perubahan sebuah laporan (AJAX)
    public function getRiwayat($id)
    {
        $logs = $this->logModel
            ->select('laporan_log.aksi, laporan_log.perubahan, laporan_log.created_at, users.nama_lengkap, users.role')
            ->join('users', 'users.id_user = laporan_log.id_user', 'left')
            ->where('id_laporan', $id)
            ->orderBy('laporan_log.created_at', 'DESC')
            ->findAll();

        foreach ($logs as &$log) {
            $log['perubahan'] = $log['perubahan'] ? json_decode($log['perubahan'], true) : null;
        }

        return $this->response->setJSON(['status' => 'success', 'data' => $logs]);
    }

    /**
     * Feed aktivitas perubahan laporan se-wilayah.
     * admin_kecamatan → desa di kecamatannya; admin_dinas → semua.
     */
    public function aktivitas()
    {
        $user  = session()->get();
        $role  = $user['role'] ?? '';

        if (!in_array($role, ['admin_kecamatan', 'admin_dinas'], true)) {
            return redirect()->to('/laporan')->with('error', 'Akses ditolak.');
        }

        $id_desa = $this->request->getGet('id_desa');
        $aksi    = $this->request->getGet('aksi');
        $bulan   = $this->request->getGet('bulan');
        $tahun   = $this->request->getGet('tahun');

        $builder = $this->logModel
            ->select('laporan_log.*, users.nama_lengkap, users.role,
                      laporan_agregat.bulan, laporan_agregat.tahun,
                      m_desa.nama_desa, m_desa.id_desa, kecamatan.nama_kecamatan')
            ->join('users', 'users.id_user = laporan_log.id_user', 'left')
            ->join('laporan_agregat', 'laporan_agregat.id_laporan = laporan_log.id_laporan', 'left')
            ->join('m_desa', 'm_desa.id_desa = laporan_agregat.id_desa', 'left')
            ->join('kecamatan', 'kecamatan.id_kecamatan = m_desa.id_kecamatan', 'left');

        if ($role === 'admin_kecamatan') {
            $builder->where('m_desa.id_kecamatan', $user['id_kecamatan']);
        }

        if ($id_desa) {
            $builder->where('m_desa.id_desa', $id_desa);
        }
        if ($aksi && in_array($aksi, ['create', 'update', 'delete'], true)) {
            $builder->where('laporan_log.aksi', $aksi);
        }
        if ($bulan) {
            $builder->where('laporan_agregat.bulan', $bulan);
        }
        if ($tahun) {
            $builder->where('laporan_agregat.tahun', $tahun);
        }

        $logs = $builder->orderBy('laporan_log.created_at', 'DESC')->findAll(100);

        foreach ($logs as &$log) {
            $log['perubahan'] = $log['perubahan'] ? json_decode($log['perubahan'], true) : null;
        }

        // daftar desa untuk filter
        if ($role === 'admin_kecamatan') {
            $list_desa = $this->desaModel->where('id_kecamatan', $user['id_kecamatan'])->orderBy('nama_desa', 'ASC')->findAll();
        } else {
            $list_desa = $this->desaModel->orderBy('nama_desa', 'ASC')->findAll();
        }

        return view('laporan/aktivitas', [
            'title'      => 'Aktivitas Perubahan Laporan',
            'logs'       => $logs,
            'list_desa'  => $list_desa,
            'bulanList'  => $this->bulanList,
            'filter'     => [
                'id_desa' => $id_desa,
                'aksi'    => $aksi,
                'bulan'   => $bulan,
                'tahun'   => $tahun,
            ],
        ]);
    }

    /** Bandingkan data lama vs baru, return array field yang berubah. */
    private function buildDiff(?array $old, array $new): array
    {
        if (!$old) {
            return [];
        }

        $skip = ['id_laporan', 'id_desa', 'id_user', 'bulan', 'tahun', 'created_at', 'updated_at', 'csrf_test_name'];
        $diff = [];

        foreach ($new as $field => $after) {
            if (in_array($field, $skip, true) || !array_key_exists($field, $old)) {
                continue;
            }
            $before = $old[$field];
            // bandingkan sebagai string agar 0 == "0" tidak dianggap berubah
            if ((string) $before !== (string) $after) {
                $diff[] = [
                    'field' => $field,
                    'label' => $this->fieldLabel($field),
                    'lama'  => $before,
                    'baru'  => $after,
                ];
            }
        }

        return $diff;
    }

    private function fieldLabel(string $field): string
    {
        static $map = [
            'jiwa_l' => 'Jiwa Laki-laki', 'jiwa_p' => 'Jiwa Perempuan',
            'kk_l' => 'KK Laki-laki', 'kk_p' => 'KK Perempuan',
            'kk_pend_tidak_sekolah' => 'Pendidikan: Tidak Sekolah', 'kk_pend_sd' => 'Pendidikan: SD',
            'kk_pend_smp' => 'Pendidikan: SMP', 'kk_pend_sma' => 'Pendidikan: SMA',
            'kk_pend_diploma' => 'Pendidikan: Diploma', 'kk_pend_s1' => 'Pendidikan: S1',
            'kk_pend_s2_s3' => 'Pendidikan: S2/S3',
            'kk_ker_tani' => 'Pekerjaan: Tani', 'kk_ker_nelayan' => 'Pekerjaan: Nelayan',
            'kk_ker_pns' => 'Pekerjaan: PNS', 'kk_ker_swasta' => 'Pekerjaan: Swasta',
            'kk_ker_pedagang' => 'Pekerjaan: Pedagang', 'kk_ker_wiraswasta' => 'Pekerjaan: Wiraswasta',
            'kk_ker_buruh' => 'Pekerjaan: Buruh', 'kk_ker_tidak_kerja' => 'Pekerjaan: Tidak Kerja',
            'jml_balita' => 'Jml Balita', 'jml_remaja' => 'Jml Remaja', 'jml_lansia' => 'Jml Lansia',
            'kk_kawin' => 'Status Kawin', 'kk_belum_kawin' => 'Belum Kawin',
            'kk_cerai_hidup' => 'Cerai Hidup', 'kk_cerai_mati' => 'Cerai Mati',
            'pend_bpjs' => 'Punya BPJS', 'pend_non_bpjs' => 'Non BPJS',
            'jkn' => 'JKN', 'non_jkn' => 'Non JKN',
            'pend_wajib_ktp' => 'Wajib KTP', 'pend_punya_akta_lahir' => 'Punya Akta Lahir',
            'kk_punya_akta_nikah' => 'Punya Akta Nikah',
            'kk_punya_kartu_fisik' => 'KK Punya Kartu Fisik', 'kk_belum_punya_kartu_fisik' => 'KK Belum Kartu Fisik',
            'kb_aktif' => 'KB Aktif', 'jml_pus' => 'Jml PUS',
            'jml_penggunaan_alat_kontrasepsi' => 'Alat Kontrasepsi',
            'pus_jkn' => 'PUS JKN', 'pus_pbi' => 'PUS PBI', 'pus_non_pbi' => 'PUS Non PBI',
            'penduduk_hub_kepala' => 'Hub: Kepala', 'penduduk_hub_istri' => 'Hub: Istri',
            'penduduk_hub_anak' => 'Hub: Anak', 'penduduk_hub_lainnya' => 'Hub: Lainnya',
        ];

        if (isset($map[$field])) {
            return $map[$field];
        }
        // piramida umur: u0_4_l → Umur 0-4 L
        if (preg_match('/^u(\d+)_(\d+|atas)_(l|p)$/', $field, $m)) {
            $range = $m[2] === 'atas' ? $m[1] . '+' : $m[1] . '-' . $m[2];
            return 'Umur ' . $range . ' ' . ($m[3] === 'l' ? 'L' : 'P');
        }
        return $field;
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
                ->select('laporan_agregat.*, m_desa.nama_desa, kecamatan.nama_kecamatan')
                ->join('m_desa',     'm_desa.id_desa = laporan_agregat.id_desa')
                ->join('kecamatan',  'kecamatan.id_kecamatan = m_desa.id_kecamatan');

            if ($user['role'] == 'admin_dinas') {
                if (!empty($id_kecamatan)) $builder->where('kecamatan.id_kecamatan', $id_kecamatan);
                if (!empty($id_desa))      $builder->where('laporan_agregat.id_desa', $id_desa);
            } elseif ($user['role'] == 'admin_kecamatan') {
                $builder->where('kecamatan.id_kecamatan', $user['id_kecamatan']);
                if (!empty($id_desa)) $builder->where('laporan_agregat.id_desa', $id_desa);
            } else {
                $builder->where('laporan_agregat.id_desa', $user['id_desa']);
            }

            if ($bulan) $builder->where('laporan_agregat.bulan', $bulan);
            if ($tahun) $builder->where('laporan_agregat.tahun', $tahun);

            // ponytail: hard limit mencegah memory blow-up pada export besar
            $dataLaporan = $builder->orderBy('laporan_agregat.tahun', 'DESC')
                ->orderBy('laporan_agregat.bulan', 'DESC')
                ->findAll(5000);

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

        $builder = $this->laporanModel->select('laporan_agregat.*, m_desa.nama_desa, kecamatan.nama_kecamatan')
            ->join('m_desa',    'm_desa.id_desa = laporan_agregat.id_desa')
            ->join('kecamatan', 'kecamatan.id_kecamatan = m_desa.id_kecamatan');

        if ($user['role'] == 'admin_dinas') {
            if (!empty($id_kecamatan)) $builder->where('kecamatan.id_kecamatan', $id_kecamatan);
            if (!empty($id_desa))      $builder->where('laporan_agregat.id_desa', $id_desa);
        } elseif ($user['role'] == 'admin_kecamatan') {
            $builder->where('kecamatan.id_kecamatan', $user['id_kecamatan']);
            if (!empty($id_desa)) $builder->where('laporan_agregat.id_desa', $id_desa);
        } else {
            $builder->where('laporan_agregat.id_desa', $user['id_desa']);
        }

        if ($bulan) $builder->where('laporan_agregat.bulan', $bulan);
        if ($tahun) $builder->where('laporan_agregat.tahun', $tahun);

        // ponytail: hard limit mencegah memory blow-up pada export besar
        $dataLaporan = $builder->orderBy('laporan_agregat.tahun', 'DESC')
            ->orderBy('laporan_agregat.bulan', 'DESC')
            ->findAll(5000);

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

        $desaModel = new \App\Models\DesaModel();
        $desa = $desaModel->find($id_desa);

        // Ambil laporan desa ini untuk periode ini
        $laporan = $this->laporanModel
            ->where('id_desa', $id_desa)
            ->where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->first();

        $data = [[
            'id_desa'    => $id_desa,
            'nama_desa'  => $desa['nama_desa'] ?? '-',
            'id_laporan' => $laporan['id_laporan'] ?? null,
            'jiwa_l'     => $laporan['jiwa_l'] ?? 0,
            'jiwa_p'     => $laporan['jiwa_p'] ?? 0,
            'kk_l'       => $laporan['kk_l'] ?? 0,
            'kk_p'       => $laporan['kk_p'] ?? 0,
        ]];

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