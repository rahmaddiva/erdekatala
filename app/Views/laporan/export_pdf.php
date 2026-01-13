<!DOCTYPE html>
<html>

<head>
    <title><?= $title ?></title>
    <style>
        @page {
            margin: 1.5cm;
        }

        body {
            font-family: 'Helvetica', sans-serif;
            font-size: 7pt;
            color: #333;
            line-height: 1.2;
        }

        .header {
            text-align: center;
            margin-bottom: 15px;
            border-bottom: 2px solid #000;
            padding-bottom: 5px;
        }

        .section-title {
            font-weight: bold;
            font-size: 9pt;
            margin: 10px 0 5px 0;
            background: #f0f0f0;
            padding: 3px;
            border: 1px solid #ccc;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            margin-bottom: 20px;
        }

        th,
        td {
            border: 1px solid #000;
            padding: 2px;
            text-align: center;
            word-wrap: break-word;
        }

        th {
            background-color: #e9ecef;
            font-weight: bold;
        }

        .text-left {
            text-align: left;
            padding-left: 3px;
        }

        .page-break {
            page-break-before: always;
        }

        .bg-total {
            background-color: #fafafa;
            font-weight: bold;
        }
    </style>
</head>

<body>

    <div class="header">
        <h2 style="margin:0;">LAPORAN AGREGAT KEPENDUDUKAN (BAGIAN I)</h2>
        <p style="margin:5px 0;">Data Pokok, Pendidikan, dan Pekerjaan Kepala Keluarga</p>
    </div>

    <div class="section-title">A. DATA JIWA, KK, PENDIDIKAN, & PEKERJAAN</div>
    <table>
        <thead>
            <tr>
                <th rowspan="2" width="20">No</th>
                <th rowspan="2" width="70">Wilayah (RT/Dusun)</th>
                <th colspan="2">Jiwa</th>
                <th colspan="2">KK</th>
                <th colspan="7">Pendidikan (KK)</th>
                <th colspan="8">Pekerjaan (KK)</th>
            </tr>
            <tr>
                <th>L</th>
                <th>P</th>
                <th>L</th>
                <th>P</th>
                <th>T.Sek</th>
                <th>SD</th>
                <th>SMP</th>
                <th>SMA</th>
                <th>Dip</th>
                <th>S1</th>
                <th>S2/3</th>
                <th>Tani</th>
                <th>Nel</th>
                <th>PNS</th>
                <th>Sws</th>
                <th>Dag</th>
                <th>Wir</th>
                <th>Bur</th>
                <th>T.Ker</th>
            </tr>
        </thead>
        <tbody>
            <?php $i = 1;
            foreach ($laporan as $r): ?>
                <tr>
                    <td><?= $i++ ?></td>
                    <td class="text-left">RT<?= $r['no_rt'] ?> - <?= $r['nama_dusun'] ?></td>
                    <td><?= $r['jiwa_l'] ?></td>
                    <td><?= $r['jiwa_p'] ?></td>
                    <td><?= $r['kk_l'] ?></td>
                    <td><?= $r['kk_p'] ?></td>
                    <td><?= $r['kk_pend_tidak_sekolah'] ?></td>
                    <td><?= $r['kk_pend_sd'] ?></td>
                    <td><?= $r['kk_pend_smp'] ?></td>
                    <td><?= $r['kk_pend_sma'] ?></td>
                    <td><?= $r['kk_pend_diploma'] ?></td>
                    <td><?= $r['kk_pend_s1'] ?></td>
                    <td><?= $r['kk_pend_s2_s3'] ?></td>
                    <td><?= $r['kk_ker_tani'] ?></td>
                    <td><?= $r['kk_ker_nelayan'] ?></td>
                    <td><?= $r['kk_ker_pns'] ?></td>
                    <td><?= $r['kk_ker_swasta'] ?></td>
                    <td><?= $r['kk_ker_pedagang'] ?></td>
                    <td><?= $r['kk_ker_wiraswasta'] ?></td>
                    <td><?= $r['kk_ker_buruh'] ?></td>
                    <td><?= $r['kk_ker_tidak_kerja'] ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="page-break"></div>

    <div class="header">
        <h2 style="margin:0;">LAPORAN AGREGAT KEPENDUDUKAN (BAGIAN II)</h2>
        <p style="margin:5px 0;">Piramida Penduduk - Distribusi Kelompok Umur (L/P)</p>
    </div>

    <div class="section-title">B. KELOMPOK UMUR (JIWA)</div>
    <table style="font-size: 6pt;">
        <thead>
            <tr>
                <th rowspan="2" width="15">No</th>
                <th rowspan="2" width="45">Wilayah</th>
                <th colspan="2">0-4</th>
                <th colspan="2">5-9</th>
                <th colspan="2">10-14</th>
                <th colspan="2">15-19</th>
                <th colspan="2">20-24</th>
                <th colspan="2">25-29</th>
                <th colspan="2">30-34</th>
                <th colspan="2">35-39</th>
                <th colspan="2">40-44</th>
                <th colspan="2">45-49</th>
                <th colspan="2">50-54</th>
                <th colspan="2">55-59</th>
                <th colspan="2">60-64</th>
                <th colspan="2">65-69</th>
                <th colspan="2">70-74</th>
                <th colspan="2">75+</th>
            </tr>
            <tr>
                <?php for ($k = 0; $k < 16; $k++): ?>
                    <th>L</th>
                    <th>P</th> <?php endfor; ?>
            </tr>
        </thead>
        <tbody>
            <?php $i = 1;
            foreach ($laporan as $r): ?>
                <tr>
                    <td><?= $i++ ?></td>
                    <td class="text-left">RT<?= $r['no_rt'] ?></td>
                    <td><?= $r['u0_4_l'] ?></td>
                    <td><?= $r['u0_4_p'] ?></td>
                    <td><?= $r['u5_9_l'] ?></td>
                    <td><?= $r['u5_9_p'] ?></td>
                    <td><?= $r['u10_14_l'] ?></td>
                    <td><?= $r['u10_14_p'] ?></td>
                    <td><?= $r['u15_19_l'] ?></td>
                    <td><?= $r['u15_19_p'] ?></td>
                    <td><?= $r['u20_24_l'] ?></td>
                    <td><?= $r['u20_24_p'] ?></td>
                    <td><?= $r['u25_29_l'] ?></td>
                    <td><?= $r['u25_29_p'] ?></td>
                    <td><?= $r['u30_34_l'] ?></td>
                    <td><?= $r['u30_34_p'] ?></td>
                    <td><?= $r['u35_39_l'] ?></td>
                    <td><?= $r['u35_39_p'] ?></td>
                    <td><?= $r['u40_44_l'] ?></td>
                    <td><?= $r['u40_44_p'] ?></td>
                    <td><?= $r['u45_49_l'] ?></td>
                    <td><?= $r['u45_49_p'] ?></td>
                    <td><?= $r['u50_54_l'] ?></td>
                    <td><?= $r['u50_54_p'] ?></td>
                    <td><?= $r['u55_59_l'] ?></td>
                    <td><?= $r['u55_59_p'] ?></td>
                    <td><?= $r['u60_64_l'] ?></td>
                    <td><?= $r['u60_64_p'] ?></td>
                    <td><?= $r['u65_69_l'] ?></td>
                    <td><?= $r['u65_69_p'] ?></td>
                    <td><?= $r['u70_74_l'] ?></td>
                    <td><?= $r['u70_74_p'] ?></td>
                    <td><?= $r['u75_79_l'] ?></td>
                    <td><?= $r['u75_79_p'] ?></td>
                    <td><?= $r['u80_84_l'] ?></td>
                    <td><?= $r['u80_84_p'] ?></td>
                    <td><?= $r['u85_atas_l'] ?></td>
                    <td><?= $r['u85_atas_p'] ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="page-break"></div>

    <div class="header">
        <h2 style="margin:0;">LAPORAN AGREGAT KEPENDUDUKAN (BAGIAN III)</h2>
        <p style="margin:5px 0;">Agama, Status Perkawinan, Kesehatan, dan Data Sektoral Lainnya</p>
    </div>

    <div class="section-title">C. AGAMA & STATUS PERKAWINAN (KK)</div>
    <table>
        <thead>
            <tr>
                <th rowspan="2" width="20">No</th>
                <th rowspan="2" width="70">Wilayah</th>
                <th colspan="4">Status Perkawinan</th>
            </tr>
            <tr>
                < <th>B.Kaw</th>
                    <th>Kaw</th>
                    <th>C.Hid</th>
                    <th>C.Mat</th>
            </tr>
        </thead>
        <tbody>
            <?php $i = 1;
            foreach ($laporan as $r): ?>
                <tr>
                    <td><?= $i++ ?></td>
                    <td class="text-left">RT<?= $r['no_rt'] ?></td>
                    <td><?= $r['kk_belum_kawin'] ?></td>
                    <td><?= $r['kk_kawin'] ?></td>
                    <td><?= $r['kk_cerai_hidup'] ?></td>
                    <td><?= $r['kk_cerai_mati'] ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="section-title">D. KESEHATAN, KB, & ADMINISTRASI (MISC)</div>
    <table>
        <thead>
            <tr>
                <th rowspan="2" width="20">No</th>
                <th rowspan="2" width="70">Wilayah</th>
                <th colspan="3">Jaminan Kesehatan</th>
                <th colspan="2">Keluarga Berencana</th>
                <th colspan="5">Lain-lain</th>
            </tr>
            <tr>
                <th>PBI</th>
                <th>Non-PBI</th>
                <th>T.Punya</th>
                <th>KB Aktif</th>
                <th>Alat Kontra</th>
                <th>Balita</th>
                <th>PUS</th>
                <th>Wajib KTP</th>
                <th>Akta Nikah</th>
                <th>Akta Lahir</th>
            </tr>
        </thead>
        <tbody>
            <?php $i = 1;
            foreach ($laporan as $r): ?>
                <tr>
                    <td><?= $i++ ?></td>
                    <td class="text-left">RT<?= $r['no_rt'] ?></td>
                    <td><?= $r['pus_pbi'] ?></td>
                    <td><?= $r['pus_non_pbi'] ?></td>
                    <td><?= $r['non_jkn'] ?></td>
                    <td><?= $r['kb_aktif'] ?></td>
                    <td><?= $r['jml_penggunaan_alat_kontrasepsi'] ?></td>
                    <td><?= $r['jml_balita'] ?></td>
                    <td><?= $r['jml_pus'] ?></td>
                    <td><?= $r['pend_wajib_ktp'] ?></td>
                    <td><?= $r['kk_punya_akta_nikah'] ?></td>
                    <td><?= $r['pend_punya_akta_lahir'] ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div style="margin-top: 20px; font-size: 6pt; text-align: right;">
        Dicetak pada: <?= date('d/m/Y H:i:s') ?> | Halaman {PAGE_NUM} dari {PAGE_COUNT}
    </div>

</body>

</html>