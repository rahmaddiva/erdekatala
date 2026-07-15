<?= $this->extend('templates/main') ?>
<?= $this->section('content') ?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1><i class="fas fa-table mr-2"></i>Data Tabel Laporan Agregat</h1>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">

            <!-- Filter Bulan & Tahun -->
            <div class="card card-outline card-secondary shadow mb-3">
                <div class="card-body py-2">
                    <form method="GET" action="" class="form-inline flex-wrap" style="gap: 0.5rem;">
                        <div class="form-group mr-2">
                            <label class="mr-1"><i class="fas fa-calendar-alt mr-1"></i>Bulan</label>
                            <select name="bulan" class="form-control form-control-sm">
                                <option value="">-- Semua --</option>
                                <?php foreach ($namaBulan as $num => $nama): ?>
                                    <option value="<?= $num ?>" <?= (string)$filterBulan === (string)$num ? 'selected' : '' ?>>
                                        <?= $nama ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group mr-2">
                            <label class="mr-1"><i class="fas fa-calendar mr-1"></i>Tahun</label>
                            <select name="tahun" class="form-control form-control-sm">
                                <option value="">-- Semua --</option>
                                <?php foreach ($tahunList as $t): ?>
                                    <option value="<?= esc($t) ?>" <?= (string)$filterTahun === (string)$t ? 'selected' : '' ?>>
                                        <?= esc($t) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm mr-1">
                            <i class="fas fa-filter mr-1"></i>Terapkan
                        </button>
                        <a href="?" class="btn btn-secondary btn-sm">
                            <i class="fas fa-times mr-1"></i>Reset
                        </a>
                        <?php if ($filterBulan || $filterTahun): ?>
                            <span class="badge badge-info ml-2 align-self-center">
                                Filter aktif:
                                <?= $filterBulan ? $namaBulan[$filterBulan] : '' ?>
                                <?= $filterBulan && $filterTahun ? ' / ' : '' ?>
                                <?= $filterTahun ? esc($filterTahun) : '' ?>
                            </span>
                        <?php endif; ?>
                    </form>
                </div>
            </div>

<?php
// ponytail: define groups inline, no config class
$groups = [
    'identitas'  => ['label' => 'Identitas Wilayah & Periode',   'icon' => 'fa-map-marker-alt', 'cols' => [
        'nama_kecamatan' => 'Kecamatan', 'nama_desa' => 'Desa',
        'bulan' => 'Bulan', 'tahun' => 'Tahun',
    ]],
    'jiwa'       => ['label' => 'Jiwa & KK',                     'icon' => 'fa-users', 'cols' => [
        'jiwa_l' => 'Jiwa L', 'jiwa_p' => 'Jiwa P',
        'kk_l'   => 'KK L',   'kk_p'   => 'KK P',
    ]],
    'pendidikan' => ['label' => 'Pendidikan',                     'icon' => 'fa-graduation-cap', 'cols' => [
        'kk_pend_tidak_sekolah' => 'Tdk Sekolah', 'kk_pend_sd' => 'SD', 'kk_pend_smp' => 'SMP',
        'kk_pend_sma' => 'SMA', 'kk_pend_diploma' => 'Diploma', 'kk_pend_s1' => 'S1', 'kk_pend_s2_s3' => 'S2/S3',
    ]],
    'pekerjaan'  => ['label' => 'Pekerjaan',                      'icon' => 'fa-briefcase', 'cols' => [
        'kk_ker_tani' => 'Tani', 'kk_ker_nelayan' => 'Nelayan', 'kk_ker_pns' => 'PNS',
        'kk_ker_swasta' => 'Swasta', 'kk_ker_pedagang' => 'Pedagang',
        'kk_ker_wiraswasta' => 'Wiraswasta', 'kk_ker_buruh' => 'Buruh', 'kk_ker_tidak_kerja' => 'Tdk Kerja',
    ]],
    'piramida'   => ['label' => 'Piramida Penduduk',              'icon' => 'fa-chart-bar', 'cols' => [
        'u0_4_l' => '0-4 L',   'u0_4_p' => '0-4 P',
        'u5_9_l' => '5-9 L',   'u5_9_p' => '5-9 P',
        'u10_14_l' => '10-14 L','u10_14_p' => '10-14 P',
        'u15_19_l' => '15-19 L','u15_19_p' => '15-19 P',
        'u20_24_l' => '20-24 L','u20_24_p' => '20-24 P',
        'u25_29_l' => '25-29 L','u25_29_p' => '25-29 P',
        'u30_34_l' => '30-34 L','u30_34_p' => '30-34 P',
        'u35_39_l' => '35-39 L','u35_39_p' => '35-39 P',
        'u40_44_l' => '40-44 L','u40_44_p' => '40-44 P',
        'u45_49_l' => '45-49 L','u45_49_p' => '45-49 P',
        'u50_54_l' => '50-54 L','u50_54_p' => '50-54 P',
        'u55_59_l' => '55-59 L','u55_59_p' => '55-59 P',
        'u60_64_l' => '60-64 L','u60_64_p' => '60-64 P',
        'u65_69_l' => '65-69 L','u65_69_p' => '65-69 P',
        'u70_74_l' => '70-74 L','u70_74_p' => '70-74 P',
        'u75_79_l' => '75-79 L','u75_79_p' => '75-79 P',
        'u80_84_l' => '80-84 L','u80_84_p' => '80-84 P',
        'u85_atas_l' => '85+ L','u85_atas_p' => '85+ P',
        'jml_balita' => 'Balita','jml_remaja' => 'Remaja','jml_lansia' => 'Lansia',
    ]],
    'kawin'      => ['label' => 'Status Perkawinan',              'icon' => 'fa-heart', 'cols' => [
        'kk_kawin' => 'Kawin', 'kk_belum_kawin' => 'Blm Kawin',
        'kk_cerai_hidup' => 'Cerai Hidup', 'kk_cerai_mati' => 'Cerai Mati',
    ]],
    'kesehatan'  => ['label' => 'Kesehatan & Dokumen',            'icon' => 'fa-heartbeat', 'cols' => [
        'pend_bpjs' => 'BPJS', 'pend_non_bpjs' => 'Non BPJS',
        'kk_punya_kartu_fisik' => 'Punya Kartu', 'kk_belum_punya_kartu_fisik' => 'Blm Punya Kartu',
        'penduduk_hub_kepala' => 'Hub.Kepala', 'penduduk_hub_istri' => 'Hub.Istri',
        'penduduk_hub_anak' => 'Hub.Anak', 'penduduk_hub_lainnya' => 'Hub.Lainnya',
        'pus_jkn' => 'PUS JKN', 'pus_pbi' => 'PUS PBI', 'pus_non_pbi' => 'PUS Non-PBI',
        'jkn' => 'JKN', 'non_jkn' => 'Non JKN',
        'pend_wajib_ktp' => 'Wajib KTP', 'kk_punya_akta_nikah' => 'Akta Nikah', 'pend_punya_akta_lahir' => 'Akta Lahir',
    ]],
    'kb'         => ['label' => 'KB & PUS',                       'icon' => 'fa-child', 'cols' => [
        'kb_aktif' => 'KB Aktif', 'jml_pus' => 'Jml PUS', 'jml_penggunaan_alat_kontrasepsi' => 'Alat Kontrasepsi',
    ]],
];
?>

            <div id="accordion-laporan">
            <?php foreach ($groups as $gid => $g): ?>
                <div class="card card-outline card-primary shadow mb-2">
                    <div class="card-header p-0">
                        <h2 class="mb-0">
                            <button class="btn btn-link btn-block text-left px-3 py-2 collapsed"
                                    type="button" data-toggle="collapse"
                                    data-target="#grp-<?= $gid ?>" aria-expanded="false">
                                <i class="fas <?= $g['icon'] ?> mr-2 text-primary"></i>
                                <?= $g['label'] ?>
                                <span class="badge badge-secondary ml-2"><?= count($g['cols']) ?> kolom</span>
                            </button>
                        </h2>
                    </div>
                    <div id="grp-<?= $gid ?>" class="collapse" data-parent="#accordion-laporan">
                        <div class="card-body p-2">
                            <div class="table-responsive">
                                <table id="dt-<?= $gid ?>" class="table table-bordered table-striped table-sm" style="white-space:nowrap;">
                                    <thead class="thead-dark">
                                        <tr>
                                            <?php if ($gid !== 'identitas'): ?>
                                                <th>Kecamatan</th><th>Desa</th><th>Bulan</th><th>Tahun</th>
                                            <?php endif; ?>
                                            <?php foreach ($g['cols'] as $lbl): ?>
                                                <th><?= $lbl ?></th>
                                            <?php endforeach; ?>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($laporan as $row): ?>
                                        <tr>
                                            <?php if ($gid !== 'identitas'): ?>
                                                <td><?= esc($row['nama_kecamatan']) ?></td>
                                                <td><?= esc($row['nama_desa']) ?></td>
                                                <td><?= esc($namaBulan[$row['bulan']] ?? $row['bulan']) ?></td>
                                                <td><?= esc($row['tahun']) ?></td>
                                            <?php endif; ?>
                                            <?php foreach (array_keys($g['cols']) as $col): ?>
                                                <td><?= esc($col === 'bulan' ? ($namaBulan[$row[$col]] ?? $row[$col]) : ($row[$col] ?? '')) ?></td>
                                            <?php endforeach; ?>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            </div>

        </div>
    </section>
</div>

<script>
$(document).ready(function () {
    const cfg = {
        responsive: true, pageLength: 25,
        language: {
            search: "Cari:", lengthMenu: "Tampilkan _MENU_",
            info: "_START_-_END_ dari _TOTAL_",
            paginate: { previous: "Prev", next: "Next" },
            zeroRecords: "Tidak ada data"
        }
    };
    // ponytail: init DataTable on accordion open, not on load — avoids rendering hidden tables
    $('[data-toggle="collapse"]').on('click', function () {
        const tid = '#dt-' + $(this).data('target').replace('#grp-', '');
        if (!$.fn.DataTable.isDataTable(tid)) {
            $(tid).DataTable(tid.includes('piramida') ? Object.assign({}, cfg, { scrollX: true }) : cfg);
        } else {
            $(tid).DataTable().columns.adjust();
        }
    });
});
</script>

<?= $this->endSection() ?>
