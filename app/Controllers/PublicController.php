<?php

namespace App\Controllers;

use App\Models\LaporanAgregatModel;
use App\Models\KecamatanModel;
use App\Models\DesaModel;
use App\Models\ProfilDukcapilModel;
use CodeIgniter\Exceptions\PageNotFoundException;

class PublicController extends BaseController
{
    use BkkbnMirror;
    protected $laporanModel;
    protected $kecamatanModel;
    protected $desaModel;
    protected $profilDukcapilModel;


    public function __construct()
    {
        $this->laporanModel = new LaporanAgregatModel();
        $this->kecamatanModel = new KecamatanModel();
        $this->desaModel = new DesaModel();
        $this->profilDukcapilModel = new ProfilDukcapilModel();
    }

    /**
     * Landing page publik - dapat diakses tanpa login
     */
    public function landingpage()
    {
        $slug_kecamatan = $this->request->getGet('kecamatan');
        $slug_desa      = $this->request->getGet('desa');

        $id_kecamatan_filter = null;
        $id_desa_filter      = null;
        // Hasil resolusi slug → dikirim ke view supaya label SKOP ditulis dari
        // hasil resolusi, bukan dari string ?kecamatan= / ?desa= mentah.
        $filter_kec_nama  = null;   // nama kecamatan hasil resolusi (null = tidak ada/gagal)
        $filter_desa_nama = null;   // nama desa hasil resolusi (null = tidak ada/gagal)
        $kec_unresolved   = false;  // ?kecamatan= ada tapi tidak bisa di-resolve
        $desa_unresolved  = false;  // ?desa= ada tapi tidak bisa di-resolve

        // Resolve slug kecamatan ke ID
        if ($slug_kecamatan) {
            $kecamatan = $this->kecamatanModel
                ->where('LOWER(REPLACE(nama_kecamatan, " ", "-"))', strtolower($slug_kecamatan))
                ->where('is_public', 1)
                ->first();
            if ($kecamatan) {
                $id_kecamatan_filter = $kecamatan['id_kecamatan'];
                $filter_kec_nama     = $kecamatan['nama_kecamatan'];
            } else {
                $kec_unresolved = true;
            }
        }

        // Resolve slug desa ke ID.
        // Tiga keadaan:
        //  1) ?kecamatan= valid  → desa wajib berada di kecamatan itu (kalau tidak → desa_unresolved).
        //  2) ?kecamatan= TIDAK ada sama sekali → desa di-resolve GLOBAL by slug lalu
        //     kecamatan di-derive dari desa. Sebelumnya kasus ini mengabaikan ?desa=
        //     diam-diam (angka tetap seluruh kabupaten) — lihat laporan audit.
        //  3) ?kecamatan= ada tapi gagal di-resolve → desa tidak bisa di-scope;
        //     cukup andalkan peringatan kecamatan (tidak menambah peringatan desa).
        if ($slug_desa) {
            if ($id_kecamatan_filter) {
                $desa = $this->desaModel
                    ->where('LOWER(REPLACE(nama_desa, " ", "-"))', strtolower($slug_desa))
                    ->where('id_kecamatan', $id_kecamatan_filter)
                    ->first();
                if ($desa) {
                    $id_desa_filter   = $desa['id_desa'];
                    $filter_desa_nama = $desa['nama_desa'];
                } else {
                    $desa_unresolved = true;
                }
            } else {
                // Kecamatan tidak ada ATAU gagal di-resolve → coba resolve desa
                // GLOBAL by slug lalu derive kecamatan dari desa. Sebelumnya
                // kasus ini mengabaikan ?desa= diam-diam.
                $desa = $this->desaModel
                    ->where('LOWER(REPLACE(nama_desa, " ", "-"))', strtolower($slug_desa))
                    ->first();
                if ($desa) {
                    $id_desa_filter   = $desa['id_desa'];
                    $filter_desa_nama = $desa['nama_desa'];
                    // Derive kecamatan dari desa supaya dropdown kecamatan,
                    // daftar desa, dan label SKOP konsisten dengan scope desa.
                    $id_kecamatan_filter = (int) $desa['id_kecamatan'];
                    $kecRow = $this->kecamatanModel->find($id_kecamatan_filter);
                    if ($kecRow && (int) ($kecRow['is_public'] ?? 0) === 1) {
                        $filter_kec_nama = $kecRow['nama_kecamatan'];
                    }
                } else {
                    $desa_unresolved = true;
                }
            }
        }

        $list_kecamatan = $this->kecamatanModel->where('is_public', 1)->findAll();

        $list_desa = [];
        if ($id_kecamatan_filter) {
            $list_desa = $this->desaModel->where('id_kecamatan', $id_kecamatan_filter)->findAll();
        }

        // ===== Agregasi profil kependudukan (Desain B: Dukcapil = baseline,
        // laporan desa = lapisan di atasnya) =====
        // Filter sumber via ?sumber=desa|dukcapil|campuran.
        // KEPUTUSAN USER (2026-10-09): DEFAULT = DUKCAPIL. Tanpa ?sumber=,
        // landing page menampilkan snapshot Dukcapil (DKB SM I/2026) — bukan
        // "campuran otomatis" dan bukan laporan desa.
        //   (kosong)→ dukcapil (DEFAULT; snapshot resmi DKB)
        //   desa    → hanya laporan desa (perilaku lama, tetap didukung)
        //   dukcapil→ hanya snapshot Dukcapil (eksplisit)
        //   campuran→ gabungan: laporan desa menimpa baseline Dukcapil per desa
        //             (kemampuan lama dipertahankan sebagai pilihan eksplisit)
        $sumberRaw = strtolower(trim((string) ($this->request->getGet('sumber') ?? '')));
        if (in_array($sumberRaw, ['desa', 'dukcapil', 'campuran'], true)) {
            $sumberFilter = $sumberRaw;
        } else {
            // Tidak diberikan (atau nilai tak dikenal) → default Dukcapil.
            $sumberFilter = 'dukcapil';
        }

        // Desa yang relevan utk scope filter (kab penuh / 1 kecamatan / 1 desa)
        $desaScope = [];
        if ($id_desa_filter) {
            $desaScope = [$id_desa_filter];
        } elseif ($id_kecamatan_filter) {
            $desaScope = array_map(
                fn($d) => (int) $d['id_desa'],
                $this->desaModel->where('id_kecamatan', $id_kecamatan_filter)->findAll()
            );
        } else {
            $desaScope = array_map(fn($d) => (int) $d['id_desa'], $list_kecamatan ? $this->desaModel->findAll() : []);
        }

        // Desa yang sudah lapor (punya baris di laporan_agregat, scope ini)
        $desaLaporIds = [];
        if ($desaScope) {
            $lapRows = $this->laporanModel->builder()
                ->select('id_desa')
                ->whereIn('id_desa', $desaScope)
                ->groupBy('id_desa')
                ->get()->getResultArray();
            $desaLaporIds = array_map(fn($r) => (int) $r['id_desa'], $lapRows);
        }

        $statsDukcapil = ['n_rows' => 0];
        $statsLaporan  = [];
        $semesterDukcapil = null;
        $tahunDukcapil    = null;

        if ($sumberFilter === 'dukcapil') {
            // Semua desa di scope pakai snapshot Dukcapil (abaikan laporan desa)
            $statsDukcapil = $this->profilDukcapilModel->getAggregatedStats($desaScope ?: []);
            $desaLaporIds = []; // badge murni dukcapil
        } elseif ($sumberFilter === 'desa') {
            // Perilaku lama: hanya laporan desa
            $statsLaporan = $this->laporanModel->getAggregatedStats(
                $id_kecamatan_filter,
                $id_desa_filter
            );
        } else {
            // Campuran: laporan desa menimpa baseline Dukcapil per desa.
            // Dukcapil hanya dihitung utk desa yang BELUM lapor → tidak ada
            // double-count walau desa punya banyak baris laporan.
            $dukcapilIds = array_values(array_diff($desaScope, $desaLaporIds));
            $statsDukcapil = $this->profilDukcapilModel->getAggregatedStats($dukcapilIds ?: []);
            $statsLaporan = $this->laporanModel->getAggregatedStats(
                $id_kecamatan_filter,
                $id_desa_filter
            );
        }

        // Semester/tahun snapshot Dukcapil terbaru (untuk teks note-strip)
        if ((int) ($statsDukcapil['n_rows'] ?? 0) > 0) {
            $latestSnap = $this->profilDukcapilModel->orderBy('tahun DESC, semester DESC')->first();
            if ($latestSnap) {
                $semesterDukcapil = (int) $latestSnap['semester'];
                $tahunDukcapil    = (int) $latestSnap['tahun'];
            }
        }

        // Gabungkan per kolom statistik
        $keys = $this->laporanModel->getAggregatedStatsKeys();
        $stats = [];
        foreach ($keys as $k) {
            $stats[$k] = (int) ($statsLaporan[$k] ?? 0) + (int) ($statsDukcapil[$k] ?? 0);
        }

        // Meta sumber data utk badge di landing page
        $dukcapilCount = $sumberFilter === 'dukcapil'
            ? count($desaScope)
            : ($sumberFilter === 'desa' ? 0 : count(array_diff($desaScope, $desaLaporIds)));
        $laporanCount = $sumberFilter === 'dukcapil'
            ? 0
            : ($sumberFilter === 'desa' ? count($desaScope) : count($desaLaporIds));
        $profilMeta = [
            'sumber_filter'  => $sumberFilter,   // desa | dukcapil | campuran
            'dukcapil_count' => $dukcapilCount,
            'laporan_count'  => $laporanCount,
            'total_scope'    => count($desaScope),
            'semester'       => $semesterDukcapil ?? null,
            'tahun'          => $tahunDukcapil ?? null,
            // Mode = sumber aktif apa adanya. Default kini 'dukcapil' sehingga
            // badge/label tidak lagi salah menyebut "laporan desa" saat faktanya
            // Dukcapil. View memetakan 'desa' → label "Laporan Desa".
            'mode'           => $sumberFilter,
            // Berapa desa di scope yang punya laporan desa vs hanya Dukcapil —
            // dipakai untuk keterangan opsi "Campuran" di dropdown.
            'laporan_count_scope'   => count($desaLaporIds),
            'dukcapil_count_scope'  => count(array_diff($desaScope, $desaLaporIds)),
        ];
        $chart = LaporanAgregatModel::statsToChartData($stats);

        // Kelompok A: KIA & Akta Cerai HANYA ada di snapshot Dukcapil (laporan
        // desa tidak punya kolom ini). Karena "laporan menimpa Dukcapil" tidak
        // berlaku untuk indikator yang tidak dilaporkan desa, angkanya diambil
        // dari SELURUH scope Dukcapil — bukan hanya desa yang belum lapor —
        // supaya tidak hilang di mode campuran (default, ketika semua desa sudah
        // lapor). Sembunyikan hanya bila mode eksplisit murni laporan desa.
        // Provenance Kelompok A: penanda sumber per-indikator untuk chart/tabel
        // Dokumen Kependudukan. KIA & Akta Cerai SELALU dari Dukcapil sehingga
        // tidak boleh berada di bawah badge "Laporan Desa" tanpa keterangan.
        // KTP-el / Akta Lahir / Akta Nikah / KK Fisik mengikuti mode utama.
        $dokumenSumber = [];
        foreach (array_keys($chart['dokumen']) as $dokLabel) {
            $dokumenSumber[$dokLabel] = ($sumberFilter === 'dukcapil')
                ? 'dukcapil'
                : (in_array($dokLabel, ['KIA', 'Akta Cerai'], true) ? 'dukcapil' : 'utama');
        }
        $kelompokA = ['KIA', 'Akta Cerai'];
        $kelompokAAktif = false;
        if ($sumberFilter === 'desa') {
            unset($chart['dokumen']['KIA'], $chart['dokumen']['Akta Cerai']);
        } else {
            $dokScope = $this->profilDukcapilModel->getAggregatedStats($desaScope);
            $chart['dokumen']['KIA']        = (int) ($dokScope['dok_kia'] ?? 0);
            $chart['dokumen']['Akta Cerai'] = (int) ($dokScope['dok_akta_cerai'] ?? 0);
            $kelompokAAktif = true;
        }
        // Semester/tahun snapshot Dukcapil untuk catatan Kelompok A. Di mode
        // laporan/campuran statsDukcapil bisa kosong (n_rows=0) sehingga
        // $semesterDukcapil null — padahal KIA/Akta Cerai tetap dari Dukcapil.
        $kelompokASemester = $semesterDukcapil;
        $kelompokATahun    = $tahunDukcapil;
        if ($kelompokAAktif && ($kelompokASemester === null || $kelompokATahun === null)) {
            $snapA = $this->profilDukcapilModel->orderBy('tahun DESC, semester DESC')->first();
            if ($snapA) {
                $kelompokASemester = (int) $snapA['semester'];
                $kelompokATahun    = (int) $snapA['tahun'];
            }
        }
        $profilMeta['dokumen_sumber']     = $dokumenSumber;
        $profilMeta['kelompok_a']         = $kelompokA;
        $profilMeta['kelompok_a_aktif']   = $kelompokAAktif;
        $profilMeta['kelompok_a_semester'] = $kelompokASemester;
        $profilMeta['kelompok_a_tahun']    = $kelompokATahun;
        // Label sumber RESMI Dukcapil (DKB) — satu sumber kebenaran, dipakai
        // seluruh tampilan publik yang angkanya dari snapshot ini. Teks persis
        // SUMBER_LABEL_DKB (semester Romawi + tahun dinamis dari snapshot).
        $profilMeta['sumber_label_dkb'] = ProfilDukcapilModel::sumberLabelDkb(
            $kelompokASemester,
            $kelompokATahun
        );
        // KRS tree: kab total + 11 kecamatan (desa di-load via AJAX expand)
        $krs = $this->buildKrsTree(2026, $id_kecamatan_filter);

        // Cakupan Laporan Tempat Pelayanan KB (Tabel1) + Jumlah Pelayanan Peserta KB Baru (Tabel21)
        // Filter bulan via ?bulan=1..12 (default Agustus)
        $yankbBulan = (int) ($this->request->getGet('bulan') ?? 8);
        if ($yankbBulan < 1 || $yankbBulan > 12) {
            $yankbBulan = 8;
        }
        $yankb   = $this->fetchYankbReportInternal('Tabel1', 2026, $yankbBulan);
        $yankb21 = $this->fetchYankbReportInternal('Tabel21', 2026, $yankbBulan);

        // Dallap: rekap program Bangga Kencana per kecamatan (matriks ringkas).
        // 5 laporan tahunan + BKB bulanan (mengikuti filter ?bulan=).
        $dallap = $this->fetchDallapMatrixInternal(2026, $yankbBulan);

        // ===== Tab BKKBN (YanKB & Dallap) mengikuti filter kecamatan =====
        // Keputusan (2026-10-09): FILTER DI PHP atas respons level kabupaten,
        // BUKAN lewat parameter `kecamatanId` API. Alasannya (lihat
        // docs/superpowers/specs/2026-10-09-riset-bkkbn-filter-kecamatan.md):
        // mengirim `kecamatanId` menurunkan LEVEL baris (YanKB Tabel1 → faskes,
        // Tabel21 & Dallap → desa), sedangkan tab ini butuh 1 baris KECAMATAN.
        // Selain itu parseYankbHtml() mengembalikan [] untuk respons per-kecamatan
        // (header jadi 'KODE / NAMA FASKES', tanpa 'KECAMATAN'). Filter di PHP
        // memakai cache kabupaten yang sudah ada → 0 fetch tambahan, konsisten
        // dengan cara KRS difilter (buildKrsTree()).
        // Normalisasi nama HARUS identik resolveBkkbnKecamatanId() agar
        // 'Bati-Bati' (DB) cocok dengan 'BATI BATI' (SIGA/BKKBN).
        $normKec = static function (string $s): string {
            $s = strtoupper(trim(str_replace(['_', '-'], ' ', $s)));
            return preg_replace('/\s+/', ' ', $s) ?? $s;
        };
        $targetKec = $filter_kec_nama !== null ? $normKec($filter_kec_nama) : null;

        // Dallap: rows = [['nama'=>NAMA KECAMATAN, 'kode'=>.., 'qty'=>[..]]].
        // Saring ke 1 kecamatan; total dihitung ulang oleh view dari rows.
        if ($targetKec !== null && !empty($dallap['rows'])) {
            $dallap['rows'] = array_values(array_filter(
                $dallap['rows'],
                static fn($r) => $normKec((string) ($r['nama'] ?? '')) === $targetKec
            ));
        }

        // YanKB: rows[i]['cells'][1]['t'] = NAMA KECAMATAN (kode 2 digit di
        // cells[0], urut alfabet — BUKAN id BKKBN).
        // Baris 'JUMLAH TOTAL' pada respons SIGA = total SELURUH KABUPATEN; kalau
        // dibiarkan, ia menyesatkan (total 79 di bawah judul "Kecamatan Bajuin").
        // Maka total di-RECOMPUTE ke scope tersaring: angka baris kecamatan
        // ditempel ke template baris total (struktur colspan/kolom dipertahankan).
        // Untuk 1 kecamatan (kasus ini) hasilnya = angka kecamatan itu sendiri;
        // kolom persen tidak dijumlahkan (tidak relevan untuk 1 baris).
        // Bila tak ada kecamatan yang cocok → rows kosong (view menampilkan pesan
        // jujur, bukan iframe seluruh kabupaten di bawah judul kecamatan).
        $saringYankb = static function (array $parsed, ?string $target) use ($normKec): array {
            if ($target === null || empty($parsed['rows'])) {
                return $parsed;
            }
            $kecRows = [];
            $totalRows = [];
            foreach ($parsed['rows'] as $r) {
                if (!empty($r['total'])) {
                    $totalRows[] = $r;
                    continue;
                }
                if ($normKec((string) ($r['cells'][1]['t'] ?? '')) === $target) {
                    $kecRows[] = $r;
                }
            }
            if ($kecRows === []) {
                $parsed['rows'] = [];
                return $parsed;
            }
            // Recompute total scope: template total (cells[1:] = kolom angka),
            // sumber angka = baris kecamatan (cells[2:] = kolom angka).
            if (count($kecRows[0]['cells']) === count($totalRows[0]['cells'] ?? []) + 1) {
                foreach ($totalRows as &$tpl) {
                    for ($j = 1; $j < count($tpl['cells']); $j++) {
                        $v = $kecRows[0]['cells'][$j + 1]['t'] ?? null;
                        if ($v !== null) {
                            $tpl['cells'][$j]['t'] = $v;
                        }
                    }
                }
                unset($tpl);
            } else {
                // Struktur tak terduga → jangan tampilkan total yang salah.
                $totalRows = [];
            }
            $parsed['rows'] = array_merge($kecRows, $totalRows);
            return $parsed;
        };

        // Cakupan tab BKKBN utk label view (kecamatan bila filter aktif).
        $bkkbnScope = [
            'is_kecamatan'   => $targetKec !== null,
            'kec_nama'       => $filter_kec_nama,
            'kec_label'      => $targetKec !== null ? 'Kecamatan ' . $filter_kec_nama : 'Kabupaten Tanah Laut',
            'dallap_rows'    => count($dallap['rows']),
        ];

        // Cakupan pelaporan + periode terbaru (kartu status di landing page)
        $coverage     = $this->laporanModel->getCoverage($id_kecamatan_filter, $id_desa_filter);
        $latestPeriod = $this->laporanModel->getLatestPeriod();

        // Peta sebaran: ikut filter kecamatan + konsisten dengan sumber aktif.
        $mapKecamatan = $this->buildMapKecamatan($id_kecamatan_filter, $sumberFilter);
        $mapDesa      = $this->buildMapDesa($id_kecamatan_filter, $sumberFilter);
        $mapSumberLabel = $sumberFilter === 'desa'
            ? 'laporan desa'
            : ($sumberFilter === 'campuran' ? 'gabungan laporan desa + Dukcapil' : 'snapshot Dukcapil');
        $mapMeta = [
            'sumber'        => $sumberFilter,
            'sumber_label'  => $mapSumberLabel,
            'n_kecamatan'   => count($mapKecamatan),
            'n_desa'        => count($mapDesa),
            'scope_kec'     => $filter_kec_nama,
            'scope_desa'    => $filter_desa_nama,
        ];

        $data = [
            'list_kecamatan'  => $list_kecamatan,
            'list_desa'       => $list_desa,
            'filter_kec_slug' => $slug_kecamatan,
            'filter_desa_slug'=> $slug_desa,
            'filter_kec_nama' => $filter_kec_nama,
            'filter_desa_nama'=> $filter_desa_nama,
            'kec_unresolved'  => $kec_unresolved,
            'desa_unresolved' => $desa_unresolved,
            'id_kec_filter'   => $id_kecamatan_filter,
            'id_desa_filter'  => $id_desa_filter,
            'coverage'        => $coverage,
            'latestPeriod'    => $latestPeriod,
            'map_kecamatan'   => $mapKecamatan,
            'map_desa'        => $mapDesa,
            'map_meta'        => $mapMeta,
            'desa_stats'      => $id_kecamatan_filter ? $this->getDesaStats($id_kecamatan_filter) : [],
            'totals'          => $chart['totals'],
            'pendidikan'      => $chart['pendidikan'],
            'pekerjaan'       => $chart['pekerjaan'],
            'jkn_bpjs'        => $chart['jkn_bpjs'],
            'status_kawin'    => $chart['status_kawin'],
            'dokumen'         => $chart['dokumen'],
            'ageLabels'       => $chart['ageLabels'],
            'piramidaL'       => $chart['piramidaL'],
            'piramidaP'       => $chart['piramidaP'],
            'krsKab'          => $krs['kab'],
            'krsKecamatan'    => $krs['kecamatan'],
            'krsUpdatedAt'    => $krs['updated_at'],
            'krsError'        => $krs['error'],
            'krsFilterKec'    => $krs['filter_kec_name'],
            'yankbHtml'       => $yankb['html'] ?? '',
            'yankbUpdatedAt'  => $yankb['updated_at'] ?? null,
            'yankbError'      => $yankb['error'] ?? null,
            'yankb21Html'     => $yankb21['html'] ?? '',
            'yankb21UpdatedAt'=> $yankb21['updated_at'] ?? null,
            'yankb21Error'    => $yankb21['error'] ?? null,
            'yankbData'       => $saringYankb($this->parseYankbHtml($yankb['html'] ?? ''), $targetKec),
            'yankb21Data'     => $saringYankb($this->parseYankbHtml($yankb21['html'] ?? ''), $targetKec),
            'yankbBulan'      => $yankbBulan,
            'yankbTahun'      => 2026,
            'dallapRows'      => $dallap['rows'],
            'dallapDefs'      => $dallap['defs'],
            'dallapUpdatedAt' => $dallap['updated_at'],
            'dallapError'     => $dallap['error'],
            'dallapTahun'     => 2026,
            'bkkbnMeta'       => $bkkbnScope,
            'cartoApiKey'     => env('carto.apiKey', ''),
            'profilMeta'      => $profilMeta ?? null,
        ];

        return view('public/landingpage', $data);
    }

    /**
     * AJAX: matriks per-desa untuk 1 kecamatan (Dallap), dipanggil dari dropdown
     * di tab Rekap Program Bangga Kencana. $kecSlug = slug kecamatan.
     */
    public function getDallapDesa(string $kecSlug)
    {
        $namaKec = $this->kecamatanModel
            ->where('LOWER(REPLACE(nama_kecamatan, " ", "-"))', strtolower($kecSlug))
            ->first();
        if (!$namaKec) {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'kecamatan tidak dikenal', 'rows' => []]);
        }
        $bkkbnId = $this->resolveBkkbnKecamatanId($namaKec['nama_kecamatan']);
        if (!$bkkbnId) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'kecamatan tidak punya id BKKBN', 'rows' => []]);
        }
        $bulan = (int) ($this->request->getGet('bulan') ?? 8);
        if ($bulan < 1 || $bulan > 12) {
            $bulan = 8;
        }
        $m = $this->fetchDallapMatrixInternal(2026, $bulan, $bkkbnId, $namaKec['nama_kecamatan']);
        return $this->response->setJSON([
            'nama_kecamatan' => $namaKec['nama_kecamatan'],
            'defs'           => $m['defs'],
            'rows'           => $m['rows'],
            'updated_at'     => $m['updated_at'],
            'error'          => $m['error'],
        ]);
    }

    /**
     * AJAX: daftar desa KRS untuk 1 kecamatan BKKBN.
     */
    public function getKrsDesa($idKecamatanBkkbn)
    {
        $id = (int) $idKecamatanBkkbn;
        if ($id <= 0 || !in_array($id, array_values($this->bkkbnKecamatanMap()), true)) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'idKecamatan tidak valid', 'rows' => []]);
        }
        $krs = $this->fetchMonitoringKrsInternal(2026, $id);
        return $this->response->setJSON([
            'rows'       => $krs['rows'],
            'updated_at' => $krs['updated_at'],
            'error'      => $krs['error'],
        ]);
    }

    /**
     * Bangun tree KRS: baris kab (sum) + list kecamatan + id BKKBN untuk expand.
     * Jika filter kecamatan lokal → hanya 1 kecamatan (auto-expand via UI).
     */
    private function buildKrsTree(int $tahun, ?int $idKecamatanLokal = null): array
    {
        $raw = $this->fetchMonitoringKrsInternal($tahun, null);
        $rows = $raw['rows'] ?? [];
        $filterName = null;

        if ($idKecamatanLokal) {
            $kecRow = $this->kecamatanModel->find($idKecamatanLokal);
            $filterName = $kecRow['nama_kecamatan'] ?? null;
            if ($filterName) {
                $target = strtoupper(trim(str_replace(['_', '-'], ' ', $filterName)));
                $target = preg_replace('/\s+/', ' ', $target) ?? $target;
                $rows = array_values(array_filter($rows, function ($r) use ($target) {
                    $n = strtoupper(trim(str_replace(['_', '-'], ' ', $r['namaDaerah'] ?? '')));
                    $n = preg_replace('/\s+/', ' ', $n) ?? $n;
                    return $n === $target;
                }));
            }
        }

        $kecamatan = [];
        foreach ($rows as $r) {
            $nama = $r['namaDaerah'] ?? '';
            $r['bkkbn_id'] = $this->resolveBkkbnKecamatanId($nama);
            $kecamatan[] = $r;
        }

        return [
            // Saat filter kecamatan aktif, baris ini hanya berisi 1 kecamatan —
            // label "TANAH LAUT" akan menyesatkan (angkanya bukan total kabupaten).
            'kab'             => $this->sumKrsRows(
                $kecamatan,
                $filterName ? 'TANAH LAUT — ' . $filterName : 'TANAH LAUT'
            ),
            'kecamatan'       => $kecamatan,
            'updated_at'      => $raw['updated_at'] ?? null,
            'error'           => $raw['error'] ?? null,
            'filter_kec_name' => $filterName,
        ];
    }

    /** Sum numerik field KRS (string ber-koma) jadi 1 baris kab. */
    private function sumKrsRows(array $rows, string $nama): array
    {
        $numKeys = [
            'kelurahanAda', 'kelurahanTarget', 'kelurahanVerval',
            'pusSasaran', 'pusVervalAda', 'pusVervalBaru', 'pusVervalAdaBaru',
            'pusHamilSasaran', 'pushamilVervalAda', 'pushamilVervalBaru',
            'badutaSasaran', 'badutaVervalAda', 'badutaVervalBaru',
            'balitaSasaran', 'balitaVervalAda', 'balitaVervalBaru',
            'keluargaSasaranCatin', 'sasaranPrioritas', 'prioritasTerverval',
            'adaBaruLainTerverval', 'totalSasaran', 'totalVervalAdaBaru',
            'totalVervalAda', 'totalVervalBaru', 'totalRekap',
        ];
        $sum = array_fill_keys($numKeys, 0);
        foreach ($rows as $r) {
            foreach ($numKeys as $k) {
                $sum[$k] += (int) str_replace([',', '.'], '', (string) ($r[$k] ?? 0));
            }
        }
        // format kembali seperti API (dengan koma ribuan US)
        $out = ['namaDaerah' => $nama, 'kategoriLaporan' => 'Kabupaten', 'kode' => '322'];
        foreach ($sum as $k => $v) {
            $out[$k] = number_format($v, 0, '.', ',');
        }
        $out['cakupanKelurahanVerval'] = ($sum['kelurahanAda'] > 0)
            ? round($sum['kelurahanVerval'] / $sum['kelurahanAda'] * 100)
            : 0;
        $out['persenTarget'] = ($sum['totalSasaran'] > 0)
            ? (string) round($sum['totalVervalAdaBaru'] / $sum['totalSasaran'] * 100)
            : '0';
        return $out;
    }

    /**
     * Halaman per-kecamatan dengan URL slug-based: /kurau, /pelaihari, dst.
     * Mendukung filter desa via ?desa=slug dan filter sumber data via
     * ?sumber=desa|dukcapil|campuran (DEFAULT = dukcapil, konsisten landing page).
     */
    public function kecamatan($slug)
    {
        $all_kecamatan = $this->kecamatanModel->orderBy('nama_kecamatan', 'ASC')->findAll();

        $kec = null;
        $slugLower = strtolower($slug);
        foreach ($all_kecamatan as $k) {
            $kSlug = $k['slug'] ?: $this->slugify($k['nama_kecamatan']);
            if ($kSlug === $slugLower) {
                $kec = $k;
                break;
            }
        }

        if ($kec === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        if (!$kec['is_public']) {
            throw PageNotFoundException::forPageNotFound();
        }

        $list_desa = $this->desaModel
            ->where('id_kecamatan', $kec['id_kecamatan'])
            ->orderBy('nama_desa', 'ASC')
            ->findAll();

        $desa_slug   = $this->request->getGet('desa');
        $selected_desa = null;

        if ($desa_slug) {
            foreach ($list_desa as $d) {
                if ($this->slugify($d['nama_desa']) === strtolower($desa_slug)) {
                    $selected_desa = $d;
                    break;
                }
            }
        }

        // ===== Filter sumber data (konsisten landing page) =====
        //   (kosong) / nilai tak dikenal → dukcapil (DEFAULT; snapshot resmi DKB)
        //   desa     → hanya laporan desa (perilaku lama)
        //   dukcapil → hanya snapshot Dukcapil (eksplisit)
        //   campuran → per desa: laporan desa bila ada, kalau tidak Dukcapil
        $sumberRaw = strtolower(trim((string) ($this->request->getGet('sumber') ?? '')));
        $sumberFilter = in_array($sumberRaw, ['desa', 'dukcapil', 'campuran'], true)
            ? $sumberRaw
            : 'dukcapil';

        $idKecamatan = (int) $kec['id_kecamatan'];

        // Desa dalam scope: 1 desa bila ?desa= dipilih, seluruh desa kecamatan bila tidak.
        $idDesaFilter = $selected_desa ? (int) $selected_desa['id_desa'] : null;
        $desaScope = $idDesaFilter !== null
            ? [$idDesaFilter]
            : array_map(fn($d) => (int) $d['id_desa'], $list_desa);

        // Desa yang sudah lapor (punya baris laporan_agregat) di scope ini.
        $desaLaporIds = $this->desaIdsWithLaporan($desaScope);

        // Agregasi profil sesuai sumber aktif. Pola sama dengan landingpage():
        // mode dukcapil = snapshot saja; desa = laporan saja; campuran = jumlah
        // keduanya (Dukcapil hanya untuk desa yang belum lapor → tanpa double-count).
        $statsDukcapil = ['n_rows' => 0];
        $statsLaporan  = [];

        if ($sumberFilter === 'dukcapil') {
            $statsDukcapil = $this->profilDukcapilModel->getAggregatedStats($desaScope ?: []);
        } elseif ($sumberFilter === 'desa') {
            $statsLaporan = $this->laporanModel->getAggregatedStats($idKecamatan, $idDesaFilter);
        } else {
            $dukcapilIds   = array_values(array_diff($desaScope, $desaLaporIds));
            $statsDukcapil = $this->profilDukcapilModel->getAggregatedStats($dukcapilIds ?: []);
            $statsLaporan  = $this->laporanModel->getAggregatedStats($idKecamatan, $idDesaFilter);
        }

        $keys  = $this->laporanModel->getAggregatedStatsKeys();
        $stats = [];
        foreach ($keys as $k) {
            $stats[$k] = (int) ($statsLaporan[$k] ?? 0) + (int) ($statsDukcapil[$k] ?? 0);
        }

        $chart = LaporanAgregatModel::statsToChartData($stats);

        // ===== KIA & Akta Cerai (Kelompok A) =====
        // Hanya ada di snapshot Dukcapil; laporan desa tidak punya kolomnya.
        // Kini Dukcapil tersedia di halaman kecamatan → tampilkan bila sumbernya
        // menyertakan Dukcapil; sembunyikan hanya saat mode eksplisit murni desa
        // (persis seperti landing page). Nilai diambil dari scope desa/desa terpilih.
        $dokumenSumber = [];
        foreach (array_keys($chart['dokumen']) as $dokLabel) {
            $dokumenSumber[$dokLabel] = ($sumberFilter === 'dukcapil')
                ? 'dukcapil'
                : (in_array($dokLabel, ['KIA', 'Akta Cerai'], true) ? 'dukcapil' : 'utama');
        }
        $kelompokAAktif = false;
        $semesterDukcapil = null;
        $tahunDukcapil    = null;
        if ($sumberFilter === 'desa') {
            unset($chart['dokumen']['KIA'], $chart['dokumen']['Akta Cerai']);
        } else {
            $dokScope = $this->profilDukcapilModel->getAggregatedStats($desaScope);
            $chart['dokumen']['KIA']        = (int) ($dokScope['dok_kia'] ?? 0);
            $chart['dokumen']['Akta Cerai'] = (int) ($dokScope['dok_akta_cerai'] ?? 0);
            $kelompokAAktif = true;
            $snapA = $this->profilDukcapilModel->orderBy('tahun DESC, semester DESC')->first();
            if ($snapA) {
                $semesterDukcapil = (int) $snapA['semester'];
                $tahunDukcapil    = (int) $snapA['tahun'];
            }
        }

        // Meta sumber untuk badge/label view — mode apa adanya, jadi label jujur.
        $dukcapilCount = $sumberFilter === 'dukcapil'
            ? count($desaScope)
            : ($sumberFilter === 'desa' ? 0 : count(array_diff($desaScope, $desaLaporIds)));
        $laporanCount = $sumberFilter === 'dukcapil'
            ? 0
            : ($sumberFilter === 'desa' ? count($desaScope) : count($desaLaporIds));
        $profilMeta = [
            'sumber_filter'         => $sumberFilter,
            'mode'                  => $sumberFilter,
            'dukcapil_count'        => $dukcapilCount,
            'laporan_count'         => $laporanCount,
            'total_scope'           => count($desaScope),
            'laporan_count_scope'   => count($desaLaporIds),
            'dukcapil_count_scope'  => count(array_diff($desaScope, $desaLaporIds)),
            'semester'              => $semesterDukcapil,
            'tahun'                 => $tahunDukcapil,
            'dokumen_sumber'        => $dokumenSumber,
            'kelompok_a'            => ['KIA', 'Akta Cerai'],
            'kelompok_a_aktif'      => $kelompokAAktif,
            'kelompok_a_semester'   => $semesterDukcapil,
            'kelompok_a_tahun'      => $tahunDukcapil,
            // Label sumber RESMI Dukcapil (DKB) — satu sumber kebenaran.
            'sumber_label_dkb'      => ProfilDukcapilModel::sumberLabelDkb(
                $semesterDukcapil,
                $tahunDukcapil
            ),
        ];

        // Tabel per desa mengikuti sumber aktif (dukcapil → snapshot; campuran →
        // overlay laporan desa di atas snapshot per desa).
        $desa_stats = $this->getDesaStats($idKecamatan, $sumberFilter);

        $pendudukKey = $selected_desa
            ? 'Desa ' . $selected_desa['nama_desa']
            : 'Kecamatan ' . $kec['nama_kecamatan'];

        $public_kecamatan = array_filter($all_kecamatan, fn($k) => $k['is_public']);

        $data = [
            'kecamatan'         => $kec,
            'selected_desa'     => $selected_desa,
            'list_desa'         => $list_desa,
            'desa_stats'        => $desa_stats,
            'all_kecamatan'     => array_values($public_kecamatan),
            'pendudukKey'    => $pendudukKey,
            'totals'         => $chart['totals'],
            'pendidikan'     => $chart['pendidikan'],
            'pekerjaan'      => $chart['pekerjaan'],
            'jkn_bpjs'       => $chart['jkn_bpjs'],
            'status_kawin'   => $chart['status_kawin'],
            'dokumen'        => $chart['dokumen'],
            'ageLabels'      => $chart['ageLabels'],
            'piramidaL'      => $chart['piramidaL'],
            'piramidaP'      => $chart['piramidaP'],
            'sumberFilter'   => $sumberFilter,
            'profilMeta'     => $profilMeta,
            'cartoApiKey'    => env('carto.apiKey', ''),
        ];

        return view('public/kecamatan', $data);
    }

    /**
     * AJAX endpoint untuk mendapatkan desa berdasarkan kecamatan (ID, legacy)
     */
    public function getDesaByKecamatan($id_kecamatan)
    {
        $desa = $this->desaModel->where('id_kecamatan', $id_kecamatan)->findAll();
        return $this->response->setJSON($desa);
    }

    /**
     * AJAX endpoint untuk mendapatkan desa berdasarkan slug kecamatan
     * URL: /getDesaByKecamatanSlug/pelaihari
     */
    public function getDesaByKecamatanSlug($slug)
    {
        $kecamatan = $this->kecamatanModel
            ->where('LOWER(REPLACE(nama_kecamatan, " ", "-"))', strtolower($slug))
            ->where('is_public', 1)
            ->first();

        if (!$kecamatan) {
            return $this->response->setStatusCode(404)->setJSON([]);
        }

        $desa = $this->desaModel
            ->where('id_kecamatan', $kecamatan['id_kecamatan'])
            ->findAll();

        // Tambahkan slug ke setiap desa
        foreach ($desa as &$d) {
            $d['slug'] = strtolower(str_replace(' ', '-', $d['nama_desa']));
        }

        return $this->response->setJSON($desa);
    }

    /**
     * Data untuk peta sebaran: koordinat kecamatan (hardcode, sumber: peta.web.id)
     * + statistik agregat per kecamatan.
     *
     * KEPUTUSAN USER (2026-10-09):
     *  - Peta IKUT filter kecamatan: saat $idKecamatanFilter diisi, hanya
     *    kecamatan itu yang dikembalikan (peta fokus/terbatas ke kecamatan itu).
     *  - Angka peta KONSISTEN dengan ?sumber= aktif. Default kini Dukcapil →
     *    peta memakai snapshot Dukcapil, bukan laporan desa (mencegah peta
     *    menampilkan angka laporan desa saat kartu menampilkan Dukcapil).
     *
     * Nilai indikator (41 field + total_jiwa/total_kk) ikut dikirim supaya JS
     * dapat menimpakan nilai dari sumber aktif ke properti GeoJSON endpoint
     * (endpoint /api/v1/map/kecamatan bersumber laporan desa dan berada di luar
     * batas file yang boleh diubah pada turn ini).
     *
     * @param int|null $idKecamatanFilter id_kecamatan hasil resolusi (null = kabupaten)
     * @param string   $sumberFilter      'desa' | 'dukcapil' | 'campuran'
     */
    private function buildMapKecamatan(?int $idKecamatanFilter = null, string $sumberFilter = 'dukcapil'): array
    {
        $kecamatan = $this->kecamatanModel->where('is_public', 1)->findAll();

        // Koordinat fallback per kecamatan (peta.web.id) — dipakai bila poligon tak ada
        $coords = $this->kecamatanCoordinatesMap();

        // Statistik per kecamatan sesuai SUMBER aktif (konsisten dgn kartu profil).
        // Diagregasi dari baris per-desa agar semantik "laporan menimpa Dukcapil
        // per desa" pada mode campuran tetap benar (tanpa double-count).
        $agg = $this->aggregateMapToKecamatan(
            $this->mapRowsPerDesa($idKecamatanFilter, $sumberFilter)
        );

        $fields = LaporanAgregatModel::mapIndicatorFields();

        $map = [];
        foreach ($kecamatan as $k) {
            $id   = (int) $k['id_kecamatan'];
            // Scope filter kecamatan: lewati kecamatan di luar scope.
            if ($idKecamatanFilter !== null && $id !== $idKecamatanFilter) {
                continue;
            }
            $key  = strtoupper(trim(str_replace(['_', '-'], ' ', $k['nama_kecamatan'])));
            $key  = preg_replace('/\s+/', ' ', $key) ?? $key;
            $kode = $this->kodeKecamatan3($k['kode_kecamatan']);
            $s    = $agg[$kode] ?? [];
            $slug = $k['slug'] ?: $this->slugify($k['nama_kecamatan']);
            $c    = $coords[$key] ?? null;

            $row = [
                'kode'         => $kode,
                'id_kecamatan' => $id,
                'nama'         => $k['nama_kecamatan'],
                'slug'         => $slug,
                'lat'          => $c['lat'] ?? null,
                'lng'          => $c['lng'] ?? null,
            ];
            // Seluruh indikator peta + turunan total_jiwa/total_kk (sumber aktif).
            foreach ($fields as $f) {
                $row[$f] = (int) ($s[$f] ?? 0);
            }
            $row['total_jiwa'] = (int) ($s['total_jiwa'] ?? 0);
            $row['total_kk']   = (int) ($s['total_kk'] ?? 0);
            $map[] = $row;
        }

        return $map;
    }

    /**
     * Baris indikator peta per DESA sesuai SUMBER aktif (kunci "kec.desa").
     *  - dukcapil : snapshot Dukcapil (semua desa scope)
     *  - desa     : laporan desa (hanya desa yang lapor)
     *  - campuran : Dukcapil sebagai baseline, ditimpa laporan desa per desa
     *               (persis semantik kartu profil: laporan menimpa Dukcapil).
     *
     * @return array<string,array<string,int|string>>
     */
    private function mapRowsPerDesa(?int $idKecamatanFilter, string $sumberFilter): array
    {
        $key = static fn(array $r): string => $r['kd_kecamatan'] . '.' . $r['kd_kelurahan'];

        if ($sumberFilter === 'desa') {
            $out = [];
            foreach ($this->laporanModel->getMapDesa($idKecamatanFilter) as $r) {
                $out[$key($r)] = $r;
            }
            return $out;
        }

        // dukcapil (default) & campuran sama-sama mulai dari snapshot Dukcapil.
        $out = [];
        foreach ($this->profilDukcapilModel->getMapDesa($idKecamatanFilter) as $r) {
            $out[$key($r)] = $r;
        }

        if ($sumberFilter === 'campuran') {
            foreach ($this->laporanModel->getMapDesa($idKecamatanFilter) as $r) {
                $out[$key($r)] = $r; // laporan menimpa Dukcapil per desa
            }
        }

        return $out;
    }

    /**
     * Jumlahkan baris indikator per-desa menjadi baris per-kecamatan.
     * Kunci: kd_kecamatan (3 digit). Menambah total_jiwa/total_kk turunan.
     *
     * @param array<string,array<string,int|string>> $desaRows
     * @return array<string,array<string,int|string>> kd_kecamatan => row
     */
    private function aggregateMapToKecamatan(array $desaRows): array
    {
        $fields = LaporanAgregatModel::mapIndicatorFields();
        $out    = [];

        foreach ($desaRows as $r) {
            $kode = (string) ($r['kd_kecamatan'] ?? '');
            if ($kode === '') {
                continue;
            }
            if (!isset($out[$kode])) {
                $out[$kode] = ['kd_kecamatan' => $kode];
                foreach ($fields as $f) {
                    $out[$kode][$f] = 0;
                }
            }
            foreach ($fields as $f) {
                $out[$kode][$f] += (int) ($r[$f] ?? 0);
            }
        }

        foreach ($out as &$row) {
            $row['total_jiwa'] = (int) $row['jiwa_l'] + (int) $row['jiwa_p'];
            $row['total_kk']   = (int) $row['kk_l'] + (int) $row['kk_p'];
        }
        unset($row);

        return $out;
    }

    /**
     * Baris indikator peta per DESA untuk overlay poligon desa (saat filter
     * kecamatan aktif), dari SUMBER aktif — konsisten dgn kecamatan & kartu.
     * Bentuk baris = keluaran ProfilDukcapilModel/LaporanAgregatModel::getMapDesa().
     *
     * @return list<array<string,int|string>>
     */
    private function buildMapDesa(?int $idKecamatanFilter, string $sumberFilter): array
    {
        if ($idKecamatanFilter === null) {
            return [];
        }
        return array_values($this->mapRowsPerDesa($idKecamatanFilter, $sumberFilter));
    }

    /**
     * "63.01.01" / "63.01.001" -> "001" (3 digit, kunci join dengan GeoJSON BPS)
     */
    private function kodeKecamatan3(string $kodeKecamatan): string
    {
        $parts = explode('.', $kodeKecamatan);
        $last = (string) (end($parts) ?: '');

        return str_pad($last, 3, '0', STR_PAD_LEFT);
    }

    /** // ponytail: koordinat kecamatan Tanah Laut, sumber https://peta.web.id/peta/kab/kabupaten-tanah-laut/ */
    private function kecamatanCoordinatesMap(): array
    {
        return [
            'BAJUIN'        => ['lat' => -3.7115033,  'lng' => 114.8804853],
            'BATI BATI'     => ['lat' => -3.565921,   'lng' => 114.7855271],
            'BATU AMPAR'    => ['lat' => -3.8215822,  'lng' => 114.9279547],
            'BUMI MAKMUR'   => ['lat' => -3.5617356,  'lng' => 114.607413],
            'JORONG'        => ['lat' => -3.9318403,  'lng' => 114.9754175],
            'KINTAP'        => ['lat' => -3.857227,   'lng' => 115.207901],
            'KURAU'         => ['lat' => -3.6442297,  'lng' => 114.6430427],
            'PANYIPATAN'    => ['lat' => -4.0351561,  'lng' => 114.6667939],
            'PELAIHARI'     => ['lat' => -3.799686,   'lng' => 114.746696],
            'TAKISUNG'      => ['lat' => -3.8676527,  'lng' => 114.6430427],
            'TAMBANG ULANG' => ['lat' => -3.6400722,  'lng' => 114.7855271],
        ];
    }

    // ===================================================
    //  PRIVATE HELPERS
    // ===================================================

    /**
     * Ubah nama menjadi slug URL: "Tambang Ulang" → "tambang-ulang"
     */
    private function slugify($name)
    {
        return strtolower(str_replace(' ', '-', $name));
    }

    /**
     * Daftar id_desa (dari $ids) yang sudah punya baris di laporan_agregat.
     * Dipakai mode campuran: desa yang belum lapor memakai snapshot Dukcapil.
     *
     * @param list<int> $ids
     * @return list<int>
     */
    private function desaIdsWithLaporan(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        $rows = $this->laporanModel->builder()
            ->select('id_desa')
            ->whereIn('id_desa', $ids)
            ->groupBy('id_desa')
            ->get()->getResultArray();

        return array_map(static fn($r) => (int) $r['id_desa'], $rows);
    }

    /**
     * Statistik per-desa untuk tabel di halaman kecamatan.
     * Mengembalikan: id_desa, nama_desa, total_jiwa_l, total_jiwa_p, total_kk, rt_count
     *
     * @param string $sumber 'desa' (laporan desa) | 'dukcapil' (snapshot) |
     *                       'campuran' (per desa: laporan desa bila ada, kalau
     *                       tidak snapshot Dukcapil → konsisten dgn total kartu).
     */
    private function getDesaStats($id_kecamatan, string $sumber = 'desa')
    {
        if ($sumber === 'dukcapil') {
            return $this->getDesaStatsDukcapil($id_kecamatan);
        }
        if ($sumber !== 'campuran') {
            return $this->getDesaStatsLaporan($id_kecamatan);
        }

        // Campuran: overlay laporan desa di atas snapshot Dukcapil per desa.
        $lap = [];
        foreach ($this->getDesaStatsLaporan($id_kecamatan) as $r) {
            $lap[(int) $r['id_desa']] = $r;
        }
        $duk = [];
        foreach ($this->getDesaStatsDukcapil($id_kecamatan) as $r) {
            $duk[(int) $r['id_desa']] = $r;
        }

        $out = [];
        foreach ($this->desaModel->where('id_kecamatan', $id_kecamatan)
                     ->orderBy('nama_desa', 'ASC')->findAll() as $d) {
            $id = (int) $d['id_desa'];
            if (isset($lap[$id])) {
                $out[] = $lap[$id];        // laporan desa menimpa Dukcapil
            } elseif (isset($duk[$id])) {
                $out[] = $duk[$id];        // desa belum lapor → snapshot
            }
        }

        return $out;
    }

    /**
     * Statistik per-desa dari laporan_agregat (perilaku lama).
     */
    private function getDesaStatsLaporan($id_kecamatan)
    {
        $builder = $this->laporanModel->builder();
        $builder->select(
            'm_desa.id_desa, m_desa.nama_desa, m_desa.kode_desa,
             SUM(laporan_agregat.jiwa_l) AS total_jiwa_l,
             SUM(laporan_agregat.jiwa_p) AS total_jiwa_p,
             SUM(laporan_agregat.kk_l + laporan_agregat.kk_p) AS total_kk,
             COUNT(laporan_agregat.id_laporan) AS rt_count'
        );
        $builder->join('m_desa', 'm_desa.id_desa = laporan_agregat.id_desa');
        $builder->where('m_desa.id_kecamatan', $id_kecamatan);
        $builder->groupBy('m_desa.id_desa');
        $builder->orderBy('m_desa.nama_desa', 'ASC');

        $rows = $builder->get()->getResultArray();

        // Tambah kunci join GeoJSON ("002.007") + total jiwa
        foreach ($rows as &$r) {
            $parts = explode('.', (string) ($r['kode_desa'] ?? ''));
            $kec = str_pad((string) ($parts[2] ?? ''), 3, '0', STR_PAD_LEFT);
            $desa = substr((string) ($parts[3] ?? ''), -3);
            $r['kode'] = $kec . '.' . $desa;
            $r['total_jiwa'] = (int) $r['total_jiwa_l'] + (int) $r['total_jiwa_p'];
            $r['sumber'] = 'desa';
        }
        unset($r);

        return $rows;
    }

    /**
     * Statistik per-desa dari snapshot Dukcapil (1 baris terbaru per desa).
     * Bentuk baris SAMA dengan getDesaStats() mode laporan sehingga view dapat
     * memakai tabel yang sama; `rt_count` = jumlah snapshot (bukan laporan) dan
     * ditandai lewat kolom `sumber` agar view menulis keterangan jujur.
     */
    private function getDesaStatsDukcapil($id_kecamatan)
    {
        $builder = $this->profilDukcapilModel->builder();
        $builder->select(
            'm_desa.id_desa, m_desa.nama_desa, m_desa.kode_desa,
             SUM(profil_dukcapil.jiwa_l) AS total_jiwa_l,
             SUM(profil_dukcapil.jiwa_p) AS total_jiwa_p,
             SUM(profil_dukcapil.kk_l + profil_dukcapil.kk_p) AS total_kk,
             COUNT(profil_dukcapil.id_profil) AS rt_count'
        );
        $builder->join('m_desa', 'm_desa.id_desa = profil_dukcapil.id_desa');
        $builder->where('m_desa.id_kecamatan', $id_kecamatan);
        $builder->groupBy('m_desa.id_desa');
        $builder->orderBy('m_desa.nama_desa', 'ASC');

        $rows = $builder->get()->getResultArray();

        foreach ($rows as &$r) {
            $parts = explode('.', (string) ($r['kode_desa'] ?? ''));
            $kec = str_pad((string) ($parts[2] ?? ''), 3, '0', STR_PAD_LEFT);
            $desa = substr((string) ($parts[3] ?? ''), -3);
            $r['kode'] = $kec . '.' . $desa;
            $r['total_jiwa'] = (int) $r['total_jiwa_l'] + (int) $r['total_jiwa_p'];
            $r['sumber'] = 'dukcapil';
        }
        unset($r);

        return $rows;
    }
}
