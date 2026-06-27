<?php

namespace App\Controllers;

use App\Models\LaporanAgregatModel;
use App\Models\KecamatanModel;
use App\Models\DesaModel;
use CodeIgniter\Exceptions\PageNotFoundException;

class PublicController extends BaseController
{
    protected $laporanModel;
    protected $kecamatanModel;
    protected $desaModel;


    public function __construct()
    {
        $this->laporanModel = new LaporanAgregatModel();
        $this->kecamatanModel = new KecamatanModel();
        $this->desaModel = new DesaModel();
    }

    /**
     * Landing page publik - dapat diakses tanpa login
     */
    public function landingpage()
    {
        $id_kecamatan_filter = $this->request->getGet('id_kecamatan');
        $id_desa_filter      = $this->request->getGet('id_desa');

        $list_kecamatan = $this->kecamatanModel->findAll();

        $list_desa = [];
        if ($id_kecamatan_filter) {
            $list_desa = $this->desaModel->where('id_kecamatan', $id_kecamatan_filter)->findAll();
        }

        $builder = $this->laporanModel->select('laporan_agregat.*, m_desa.nama_desa, kecamatan.nama_kecamatan')
            ->join('m_rt', 'm_rt.id_rt = laporan_agregat.id_rt')
            ->join('m_dusun', 'm_dusun.id_dusun = m_rt.id_dusun')
            ->join('m_desa', 'm_desa.id_desa = m_dusun.id_desa')
            ->join('kecamatan', 'kecamatan.id_kecamatan = m_desa.id_kecamatan');

        if ($id_desa_filter) {
            $builder->where('m_desa.id_desa', $id_desa_filter);
        } elseif ($id_kecamatan_filter) {
            $builder->where('kecamatan.id_kecamatan', $id_kecamatan_filter);
        }

        $allLaporan = $builder->findAll();

        [$totals, $pendidikan, $pekerjaan, $jkn_bpjs, $status_kawin, $dokumen, $ageLabels, $piramidaL, $piramidaP] =
            $this->aggregateLaporan($allLaporan);

        $data = [
            'list_kecamatan' => $list_kecamatan,
            'list_desa'      => $list_desa,
            'filter_kec'     => $id_kecamatan_filter,
            'filter_desa'    => $id_desa_filter,
            'totals'         => $totals,
            'pendidikan'     => $pendidikan,
            'pekerjaan'      => $pekerjaan,
            'jkn_bpjs'       => $jkn_bpjs,
            'status_kawin'   => $status_kawin,
            'dokumen'        => $dokumen,
            'ageLabels'      => $ageLabels,
            'piramidaL'      => $piramidaL,
            'piramidaP'      => $piramidaP,
        ];

        return view('public/landingpage', $data);
    }

    /**
     * Halaman per-kecamatan dengan URL slug-based: /kurau, /pelaihari, dst.
     * Mendukung filter desa via ?desa=slug
     */
    public function kecamatan($slug)
    {
        $all_kecamatan = $this->kecamatanModel->orderBy('nama_kecamatan', 'ASC')->findAll();

        $kec = null;
        foreach ($all_kecamatan as $k) {
            if ($this->slugify($k['nama_kecamatan']) === strtolower($slug)) {
                $kec = $k;
                break;
            }
        }

        if ($kec === null) {
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

        $builder = $this->laporanModel->select('laporan_agregat.*, m_desa.nama_desa, m_desa.id_desa, kecamatan.nama_kecamatan')
            ->join('m_rt', 'm_rt.id_rt = laporan_agregat.id_rt')
            ->join('m_dusun', 'm_dusun.id_dusun = m_rt.id_dusun')
            ->join('m_desa', 'm_desa.id_desa = m_dusun.id_desa')
            ->join('kecamatan', 'kecamatan.id_kecamatan = m_desa.id_kecamatan')
            ->where('kecamatan.id_kecamatan', $kec['id_kecamatan']);

        if ($selected_desa) {
            $builder->where('m_desa.id_desa', $selected_desa['id_desa']);
        }

        $allLaporan = $builder->findAll();

        [$totals, $pendidikan, $pekerjaan, $jkn_bpjs, $status_kawin, $dokumen, $ageLabels, $piramidaL, $piramidaP] =
            $this->aggregateLaporan($allLaporan);

        $desa_stats = $this->getDesaStats($kec['id_kecamatan']);

        $pendudukKey = $selected_desa
            ? 'Desa ' . $selected_desa['nama_desa']
            : 'Kecamatan ' . $kec['nama_kecamatan'];

        $data = [
            'kecamatan'      => $kec,
            'selected_desa'  => $selected_desa,
            'list_desa'      => $list_desa,
            'desa_stats'     => $desa_stats,
            'all_kecamatan'  => $all_kecamatan,
            'pendudukKey'    => $pendudukKey,
            'totals'         => $totals,
            'pendidikan'     => $pendidikan,
            'pekerjaan'      => $pekerjaan,
            'jkn_bpjs'       => $jkn_bpjs,
            'status_kawin'   => $status_kawin,
            'dokumen'        => $dokumen,
            'ageLabels'      => $ageLabels,
            'piramidaL'      => $piramidaL,
            'piramidaP'      => $piramidaP,
        ];

        return view('public/kecamatan', $data);
    }

    /**
     * AJAX endpoint untuk mendapatkan desa berdasarkan kecamatan
     */
    public function getDesaByKecamatan($id_kecamatan)
    {
        $desa = $this->desaModel->where('id_kecamatan', $id_kecamatan)->findAll();
        return $this->response->setJSON($desa);
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
     * Agregasi data laporan untuk chart.
     * Mengembalikan array: [totals, pendidikan, pekerjaan, jkn_bpjs, status_kawin, dokumen, ageLabels, piramidaL, piramidaP]
     */
    private function aggregateLaporan($allLaporan)
    {
        $totals = [
            'jiwa_l' => 0,
            'jiwa_p' => 0,
            'kk_l'   => 0,
            'kk_p'   => 0,
            'balita' => 0,
            'pus'    => 0,
        ];

        $pendidikan = [
            'Tidak Sekolah' => 0,
            'SD'            => 0,
            'SMP'           => 0,
            'SMA'           => 0,
            'Diploma'       => 0,
            'S1'            => 0,
            'S2/S3'         => 0,
        ];

        $pekerjaan = [
            'Petani'        => 0,
            'Nelayan'       => 0,
            'PNS'           => 0,
            'Swasta'        => 0,
            'Pedagang'      => 0,
            'Wiraswasta'    => 0,
            'Buruh'         => 0,
            'Tidak Bekerja' => 0,
        ];

        $jkn_bpjs = [
            'PBI'         => 0,
            'Non PBI'     => 0,
            'Tidak Punya' => 0,
        ];

        $status_kawin = [
            'Kawin'        => 0,
            'Belum Kawin'  => 0,
            'Cerai Hidup'  => 0,
            'Cerai Mati'   => 0,
        ];

        $dokumen = [
            'KTP-el'      => 0,
            'Akta Lahir'  => 0,
            'Akta Nikah'  => 0,
            'KK Fisik'    => 0,
        ];

        $ageLabels = [
            '0-4', '5-9', '10-14', '15-19', '20-24', '25-29',
            '30-34', '35-39', '40-44', '45-49', '50-54', '55-59',
            '60-64', '65-69', '70-74', '75-79', '80-84', '85+',
        ];
        $piramidaL = array_fill(0, 18, 0);
        $piramidaP = array_fill(0, 18, 0);

        $piramidaFields = [
            ['u0_4_l',    'u0_4_p'],
            ['u5_9_l',    'u5_9_p'],
            ['u10_14_l',  'u10_14_p'],
            ['u15_19_l',  'u15_19_p'],
            ['u20_24_l',  'u20_24_p'],
            ['u25_29_l',  'u25_29_p'],
            ['u30_34_l',  'u30_34_p'],
            ['u35_39_l',  'u35_39_p'],
            ['u40_44_l',  'u40_44_p'],
            ['u45_49_l',  'u45_49_p'],
            ['u50_54_l',  'u50_54_p'],
            ['u55_59_l',  'u55_59_p'],
            ['u60_64_l',  'u60_64_p'],
            ['u65_69_l',  'u65_69_p'],
            ['u70_74_l',  'u70_74_p'],
            ['u75_79_l',  'u75_79_p'],
            ['u80_84_l',  'u80_84_p'],
            ['u85_atas_l', 'u85_atas_p'],
        ];

        foreach ($allLaporan as $l) {
            $totals['jiwa_l'] += $l['jiwa_l'] ?? 0;
            $totals['jiwa_p'] += $l['jiwa_p'] ?? 0;
            $totals['kk_l']   += $l['kk_l']   ?? 0;
            $totals['kk_p']   += $l['kk_p']   ?? 0;
            $totals['balita'] += $l['jml_balita'] ?? 0;
            $totals['pus']    += $l['jml_pus']    ?? 0;

            $pendidikan['Tidak Sekolah'] += $l['kk_pend_tidak_sekolah'] ?? 0;
            $pendidikan['SD']            += $l['kk_pend_sd']            ?? 0;
            $pendidikan['SMP']           += $l['kk_pend_smp']           ?? 0;
            $pendidikan['SMA']           += $l['kk_pend_sma']           ?? 0;
            $pendidikan['Diploma']       += $l['kk_pend_diploma']       ?? 0;
            $pendidikan['S1']            += $l['kk_pend_s1']            ?? 0;
            $pendidikan['S2/S3']         += $l['kk_pend_s2_s3']         ?? 0;

            $pekerjaan['Petani']        += $l['kk_ker_tani']        ?? 0;
            $pekerjaan['Nelayan']       += $l['kk_ker_nelayan']     ?? 0;
            $pekerjaan['PNS']           += $l['kk_ker_pns']         ?? 0;
            $pekerjaan['Swasta']        += $l['kk_ker_swasta']      ?? 0;
            $pekerjaan['Pedagang']      += $l['kk_ker_pedagang']    ?? 0;
            $pekerjaan['Wiraswasta']    += $l['kk_ker_wiraswasta']  ?? 0;
            $pekerjaan['Buruh']         += $l['kk_ker_buruh']       ?? 0;
            $pekerjaan['Tidak Bekerja'] += $l['kk_ker_tidak_kerja'] ?? 0;

            $jkn_bpjs['PBI']         += $l['pus_pbi']     ?? 0;
            $jkn_bpjs['Non PBI']     += $l['pus_non_pbi'] ?? 0;
            $jkn_bpjs['Tidak Punya'] += $l['non_jkn']     ?? 0;

            $status_kawin['Kawin']       += $l['kk_kawin']       ?? 0;
            $status_kawin['Belum Kawin'] += $l['kk_belum_kawin'] ?? 0;
            $status_kawin['Cerai Hidup'] += $l['kk_cerai_hidup'] ?? 0;
            $status_kawin['Cerai Mati']  += $l['kk_cerai_mati']  ?? 0;

            $dokumen['KTP-el']      += $l['pend_wajib_ktp']       ?? 0;
            $dokumen['Akta Lahir']  += $l['pend_punya_akta_lahir'] ?? 0;
            $dokumen['Akta Nikah']  += $l['kk_punya_akta_nikah']  ?? 0;
            $dokumen['KK Fisik']    += $l['kk_punya_kartu_fisik'] ?? 0;

            foreach ($piramidaFields as $idx => $fields) {
                $piramidaL[$idx] += $l[$fields[0]] ?? 0;
                $piramidaP[$idx] += $l[$fields[1]] ?? 0;
            }
        }

        return [$totals, $pendidikan, $pekerjaan, $jkn_bpjs, $status_kawin, $dokumen, $ageLabels, $piramidaL, $piramidaP];
    }

    /**
     * Statistik per-desa untuk tabel di halaman kecamatan.
     * Mengembalikan: id_desa, nama_desa, total_jiwa_l, total_jiwa_p, total_kk, rt_count
     */
    private function getDesaStats($id_kecamatan)
    {
        $builder = $this->laporanModel->builder();
        $builder->select(
            'm_desa.id_desa, m_desa.nama_desa,
             SUM(laporan_agregat.jiwa_l) AS total_jiwa_l,
             SUM(laporan_agregat.jiwa_p) AS total_jiwa_p,
             SUM(laporan_agregat.kk_l + laporan_agregat.kk_p) AS total_kk,
             COUNT(laporan_agregat.id_laporan) AS rt_count'
        );
        $builder->join('m_rt',     'm_rt.id_rt = laporan_agregat.id_rt');
        $builder->join('m_dusun',  'm_dusun.id_dusun = m_rt.id_dusun');
        $builder->join('m_desa',   'm_desa.id_desa = m_dusun.id_desa');
        $builder->where('m_desa.id_kecamatan', $id_kecamatan);
        $builder->groupBy('m_desa.id_desa');
        $builder->orderBy('m_desa.nama_desa', 'ASC');

        return $builder->get()->getResultArray();
    }
}
