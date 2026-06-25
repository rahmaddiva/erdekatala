<!DOCTYPE html>
<html>
<head>
    <title><?= $title ?></title>
    <style>
        @page { margin: 1.2cm; }
        body { font-family: 'Helvetica', sans-serif; font-size: 7pt; color: #333; line-height: 1.2; }
        .header { text-align: center; margin-bottom: 12px; border-bottom: 2px solid #000; padding-bottom: 5px; }
        .section-title {
            font-weight: bold; font-size: 8.5pt; margin: 12px 0 4px 0;
            background: #e9ecef; padding: 3px 5px; border-left: 3px solid #333;
        }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; margin-bottom: 12px; }
        th, td { border: 1px solid #999; padding: 2px 3px; text-align: center; word-wrap: break-word; }
        th { background-color: #e9ecef; font-weight: bold; }
        .text-left { text-align: left; padding-left: 4px; }
        .page-break { page-break-before: always; }
        .footer { margin-top: 10px; font-size: 6pt; text-align: right; color: #666; }
    </style>
</head>
<body>

<?php
// $sections adalah array_flip dari pilihan user, cek dengan isset($sections['key'])
// Jika tidak ada $sections (export cepat), tampilkan semua
$allSections = ['pokok','pendidikan','pekerjaan','piramida','kawin','jkn','dokumen','kb'];
if (empty($sections)) {
    $sections = array_flip($allSections);
}
$isFirst = true;
?>

<?php if (isset($sections['pokok']) || isset($sections['pendidikan']) || isset($sections['pekerjaan'])): ?>
    <?php if (!$isFirst): ?><div class="page-break"></div><?php endif; $isFirst = false; ?>
    <div class="header">
        <h2 style="margin:0;">LAPORAN AGREGAT KEPENDUDUKAN</h2>
        <p style="margin:4px 0;">Dicetak: <?= $tanggal ?> &nbsp;|&nbsp; Oleh: <?= esc($user['nama_lengkap']) ?> (<?= esc($user['role']) ?>)</p>
    </div>
<?php endif; ?>

<?php if (isset($sections['pokok'])): ?>
    <div class="section-title">A. DATA POKOK &mdash; JIWA &amp; KEPALA KELUARGA</div>
    <table>
        <thead>
            <tr>
                <th width="18">No</th>
                <th width="80">Wilayah (RT / Dusun)</th>
                <th colspan="2">Jiwa</th>
                <th colspan="2">KK</th>
                <th width="40">Total Jiwa</th>
                <th width="40">Total KK</th>
            </tr>
            <tr>
                <th></th><th></th>
                <th>L</th><th>P</th>
                <th>L</th><th>P</th>
                <th></th><th></th>
            </tr>
        </thead>
        <tbody>
            <?php $i = 1; foreach ($laporan as $r): ?>
            <tr>
                <td><?= $i++ ?></td>
                <td class="text-left">RT <?= $r['no_rt'] ?> &mdash; <?= esc($r['nama_dusun']) ?></td>
                <td><?= $r['jiwa_l'] ?></td>
                <td><?= $r['jiwa_p'] ?></td>
                <td><?= $r['kk_l'] ?></td>
                <td><?= $r['kk_p'] ?></td>
                <td><?= $r['jiwa_l'] + $r['jiwa_p'] ?></td>
                <td><?= $r['kk_l'] + $r['kk_p'] ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<?php if (isset($sections['pendidikan'])): ?>
    <div class="section-title">B. TINGKAT PENDIDIKAN KEPALA KELUARGA</div>
    <table>
        <thead>
            <tr>
                <th width="18">No</th>
                <th width="80">Wilayah</th>
                <th>T.Sek</th><th>SD</th><th>SMP</th><th>SMA</th>
                <th>Diploma</th><th>S1</th><th>S2/S3</th>
            </tr>
        </thead>
        <tbody>
            <?php $i = 1; foreach ($laporan as $r): ?>
            <tr>
                <td><?= $i++ ?></td>
                <td class="text-left">RT <?= $r['no_rt'] ?> &mdash; <?= esc($r['nama_dusun']) ?></td>
                <td><?= $r['kk_pend_tidak_sekolah'] ?></td>
                <td><?= $r['kk_pend_sd'] ?></td>
                <td><?= $r['kk_pend_smp'] ?></td>
                <td><?= $r['kk_pend_sma'] ?></td>
                <td><?= $r['kk_pend_diploma'] ?></td>
                <td><?= $r['kk_pend_s1'] ?></td>
                <td><?= $r['kk_pend_s2_s3'] ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<?php if (isset($sections['pekerjaan'])): ?>
    <div class="section-title">C. JENIS PEKERJAAN KEPALA KELUARGA</div>
    <table>
        <thead>
            <tr>
                <th width="18">No</th>
                <th width="80">Wilayah</th>
                <th>Tani</th><th>Nelayan</th><th>PNS</th><th>Swasta</th>
                <th>Pedagang</th><th>Wiraswasta</th><th>Buruh</th><th>Tdk Kerja</th>
            </tr>
        </thead>
        <tbody>
            <?php $i = 1; foreach ($laporan as $r): ?>
            <tr>
                <td><?= $i++ ?></td>
                <td class="text-left">RT <?= $r['no_rt'] ?> &mdash; <?= esc($r['nama_dusun']) ?></td>
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
<?php endif; ?>

<?php if (isset($sections['piramida'])): ?>
    <?php if (!$isFirst): ?><div class="page-break"></div><?php endif; $isFirst = false; ?>
    <div class="header">
        <h2 style="margin:0;">LAPORAN AGREGAT KEPENDUDUKAN</h2>
        <p style="margin:4px 0;">D. PIRAMIDA PENDUDUK &mdash; DISTRIBUSI KELOMPOK UMUR</p>
    </div>
    <table style="font-size: 5.5pt;">
        <thead>
            <tr>
                <th rowspan="2" width="14">No</th>
                <th rowspan="2" width="40">Wilayah</th>
                <?php
                $ageGroups = ['0-4','5-9','10-14','15-19','20-24','25-29','30-34','35-39',
                              '40-44','45-49','50-54','55-59','60-64','65-69','70-74','75-79','80-84','85+'];
                foreach ($ageGroups as $ag): ?>
                    <th colspan="2"><?= $ag ?></th>
                <?php endforeach; ?>
            </tr>
            <tr>
                <?php for ($k = 0; $k < 18; $k++): ?>
                    <th>L</th><th>P</th>
                <?php endfor; ?>
            </tr>
        </thead>
        <tbody>
            <?php
            $fields = ['u0_4','u5_9','u10_14','u15_19','u20_24','u25_29','u30_34','u35_39',
                       'u40_44','u45_49','u50_54','u55_59','u60_64','u65_69','u70_74','u75_79','u80_84','u85_atas'];
            $i = 1;
            foreach ($laporan as $r): ?>
            <tr>
                <td><?= $i++ ?></td>
                <td class="text-left">RT<?= $r['no_rt'] ?></td>
                <?php foreach ($fields as $f): ?>
                    <td><?= $r[$f.'_l'] ?></td>
                    <td><?= $r[$f.'_p'] ?></td>
                <?php endforeach; ?>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<?php if (isset($sections['kawin']) || isset($sections['jkn']) || isset($sections['dokumen']) || isset($sections['kb'])): ?>
    <?php if (!$isFirst): ?><div class="page-break"></div><?php endif; $isFirst = false; ?>
    <div class="header">
        <h2 style="margin:0;">LAPORAN AGREGAT KEPENDUDUKAN</h2>
        <p style="margin:4px 0;">Data Sosial, Kesehatan &amp; Administrasi Kependudukan</p>
    </div>
<?php endif; ?>

<?php if (isset($sections['kawin'])): ?>
    <div class="section-title">E. STATUS PERKAWINAN KEPALA KELUARGA</div>
    <table>
        <thead>
            <tr>
                <th width="18">No</th>
                <th width="90">Wilayah</th>
                <th>Belum Kawin</th><th>Kawin</th><th>Cerai Hidup</th><th>Cerai Mati</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            <?php $i = 1; foreach ($laporan as $r): ?>
            <tr>
                <td><?= $i++ ?></td>
                <td class="text-left">RT <?= $r['no_rt'] ?> &mdash; <?= esc($r['nama_dusun']) ?></td>
                <td><?= $r['kk_belum_kawin'] ?></td>
                <td><?= $r['kk_kawin'] ?></td>
                <td><?= $r['kk_cerai_hidup'] ?></td>
                <td><?= $r['kk_cerai_mati'] ?></td>
                <td><?= $r['kk_belum_kawin'] + $r['kk_kawin'] + $r['kk_cerai_hidup'] + $r['kk_cerai_mati'] ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<?php if (isset($sections['jkn'])): ?>
    <div class="section-title">F. JAMINAN KESEHATAN NASIONAL (JKN / BPJS)</div>
    <table>
        <thead>
            <tr>
                <th width="18">No</th>
                <th width="90">Wilayah</th>
                <th>BPJS PBI</th><th>BPJS Non-PBI</th><th>Tidak Ber-JKN</th>
                <th>Total BPJS</th>
            </tr>
        </thead>
        <tbody>
            <?php $i = 1; foreach ($laporan as $r): ?>
            <tr>
                <td><?= $i++ ?></td>
                <td class="text-left">RT <?= $r['no_rt'] ?> &mdash; <?= esc($r['nama_dusun']) ?></td>
                <td><?= $r['pus_pbi'] ?></td>
                <td><?= $r['pus_non_pbi'] ?></td>
                <td><?= $r['non_jkn'] ?></td>
                <td><?= $r['pus_pbi'] + $r['pus_non_pbi'] ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<?php if (isset($sections['dokumen'])): ?>
    <div class="section-title">G. DOKUMEN ADMINISTRASI KEPENDUDUKAN</div>
    <table>
        <thead>
            <tr>
                <th width="18">No</th>
                <th width="90">Wilayah</th>
                <th>Wajib KTP</th><th>Punya Akta Lahir</th>
                <th>Punya Akta Nikah</th><th>KK Fisik</th><th>Blm KK Fisik</th>
            </tr>
        </thead>
        <tbody>
            <?php $i = 1; foreach ($laporan as $r): ?>
            <tr>
                <td><?= $i++ ?></td>
                <td class="text-left">RT <?= $r['no_rt'] ?> &mdash; <?= esc($r['nama_dusun']) ?></td>
                <td><?= $r['pend_wajib_ktp'] ?></td>
                <td><?= $r['pend_punya_akta_lahir'] ?></td>
                <td><?= $r['kk_punya_akta_nikah'] ?></td>
                <td><?= $r['kk_punya_kartu_fisik'] ?></td>
                <td><?= $r['kk_belum_punya_kartu_fisik'] ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<?php if (isset($sections['kb'])): ?>
    <div class="section-title">H. KELUARGA BERENCANA &amp; KELOMPOK RENTAN</div>
    <table>
        <thead>
            <tr>
                <th width="18">No</th>
                <th width="90">Wilayah</th>
                <th>Balita</th><th>Remaja</th><th>Lansia</th>
                <th>Jml PUS</th><th>KB Aktif</th><th>Alat Kontrasepsi</th>
            </tr>
        </thead>
        <tbody>
            <?php $i = 1; foreach ($laporan as $r): ?>
            <tr>
                <td><?= $i++ ?></td>
                <td class="text-left">RT <?= $r['no_rt'] ?> &mdash; <?= esc($r['nama_dusun']) ?></td>
                <td><?= $r['jml_balita'] ?></td>
                <td><?= $r['jml_remaja'] ?></td>
                <td><?= $r['jml_lansia'] ?></td>
                <td><?= $r['jml_pus'] ?></td>
                <td><?= $r['kb_aktif'] ?></td>
                <td><?= $r['jml_penggunaan_alat_kontrasepsi'] ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<div class="footer">Dicetak pada: <?= date('d/m/Y H:i:s') ?></div>

</body>
</html>
