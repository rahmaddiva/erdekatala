<?= $this->extend('templates/main') ?>
<?= $this->section('content') ?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0"><?= $title ?></h1>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">

            <div class="card card-outline card-primary shadow-sm mb-4">
                <div class="card-body">
                    <form action="/dashboard" method="get">
                        <div class="row align-items-end">
                            <?php if (session()->get('role') == 'admin_dinas'): ?>
                                <div class="col-md-3">
                                    <label>Pilih Kecamatan:</label>
                                    <select name="id_kecamatan" id="filter_kecamatan" class="form-control select2bs4">
                                        <option value="">-- Semua Kecamatan --</option>
                                        <?php foreach ($list_kecamatan as $k): ?>
                                            <option value="<?= $k['id_kecamatan'] ?>" <?= ($filter_kec == $k['id_kecamatan']) ? 'selected' : '' ?>>
                                                <?= $k['nama_kecamatan'] ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            <?php endif; ?>

                            <div class="col-md-3">
                                <label>Pilih Desa:</label>
                                <select name="id_desa" id="filter_desa" class="form-control select2bs4">
                                    <option value="">-- Semua Desa --</option>
                                    <?php foreach ($list_desa as $d): ?>
                                        <option value="<?= $d['id_desa'] ?>" <?= ($filter_desa == $d['id_desa']) ? 'selected' : '' ?>>
                                            <?= $d['nama_desa'] ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary btn-block">
                                    <i class="fas fa-filter"></i> Filter
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <div class="card card-outline card-info">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-table mr-1"></i>
                        Ringkasan Data <?= ($filter_kec) ? 'Per Desa' : 'Per Kecamatan' ?>
                    </h3>
                </div>
                <div class="card-body p-0">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Wilayah</th>
                                <th class="text-center">Total KK</th>
                                <th class="text-center">Total Jiwa</th>
                                <th class="text-center">L</th>
                                <th class="text-center">P</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($data_summary as $s): ?>
                                <tr>
                                    <td>
                                        <?= $s['nama_kecamatan'] ?>
                                    </td>
                                    <td class="text-center">
                                        <?= number_format($s['total_kk']) ?>
                                    </td>
                                    <td class="text-center">
                                        <?= number_format($s['total_jiwa']) ?>
                                    </td>
                                    <td class="text-center">
                                        <?= number_format($s['total_jiwa_l']) ?>
                                    </td>
                                    <td class="text-center">
                                        <?= number_format($s['total_jiwa_p']) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="row">
                <div class="col-12 col-sm-6 col-md-3">
                    <div class="info-box shadow-sm">
                        <span class="info-box-icon bg-primary"><i class="fas fa-users"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Total Jiwa</span>
                            <span
                                class="info-box-number"><?= number_format($totals['jiwa_l'] + $totals['jiwa_p']) ?></span>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-md-3">
                    <div class="info-box shadow-sm">
                        <span class="info-box-icon bg-success"><i class="fas fa-home"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Total KK</span>
                            <span class="info-box-number"><?= number_format($totals['kk_l'] + $totals['kk_p']) ?></span>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-md-3">
                    <div class="info-box shadow-sm">
                        <span class="info-box-icon bg-warning"><i class="fas fa-baby"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Total Balita</span>
                            <span class="info-box-number"><?= number_format($totals['balita']) ?></span>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-md-3">
                    <div class="info-box shadow-sm">
                        <span class="info-box-icon bg-danger"><i class="fas fa-heart"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Total PUS</span>
                            <span class="info-box-number"><?= number_format($totals['pus']) ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card card-info">
                <div class="card-header">
                    <h3 class="card-title">Tingkat Pendidikan</h3>
                </div>
                <div class="card-body">
                    <div id="chartPendidikan"></div>
                </div>
            </div>

            <div class="card card-success">
                <div class="card-header">
                    <h3 class="card-title">Jenis Pekerjaan</h3>
                </div>
                <div class="card-body">
                    <div id="chartPekerjaan"></div>
                </div>
            </div>

            <div class="card card-danger">
                <div class="card-header">
                    <h3 class="card-title">Piramida Penduduk</h3>
                </div>
                <div class="card-body">
                    <div id="chartPiramida"></div>
                </div>
            </div>

            <div class="card card-warning">
                <div class="card-header">
                    <h3 class="card-title">Status Perkawinan</h3>
                </div>
                <div class="card-body">
                    <div id="chartKawin"></div>
                </div>
            </div>



            <?php if (session()->get('role') == 'admin_kecamatan'): ?>
                <div class="row">
                    <div class="col-12">
                        <div class="card card-outline card-secondary">
                            <div class="card-header">
                                <h3 class="card-title">Rekapitulasi Data Per Desa</h3>
                            </div>
                            <div class="card-body p-0">
                                <table id="example1" class="table table-striped table-valign-middle">
                                    <thead>
                                        <tr>
                                            <th>Nama Desa</th>
                                            <th>Total Jiwa</th>
                                            <th>Total KK</th>
                                            <th>Laki-Laki</th>
                                            <th>Perempuan</th>
                                            <th>Total Balita</th>
                                            <th>Total PUS</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($data_summary_desa as $summary): ?>
                                            <tr>
                                                <td>
                                                    <?= $summary['nama_desa'] ?>
                                                </td>
                                                <td>
                                                    <?= number_format($summary['total_jiwa']) ?>
                                                </td>
                                                <td>
                                                    <?= number_format($summary['total_kk']) ?>
                                                </td>
                                                <td>
                                                    <?= number_format($summary['total_jiwa_l']) ?>
                                                </td>
                                                <td>
                                                    <?= number_format($summary['total_jiwa_p']) ?>
                                                </td>

                                                <td>
                                                    <?= number_format($summary['total_balita']) ?>
                                                </td>
                                                <td>
                                                    <?= number_format($summary['total_pus']) ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

        </div>
    </section>
</div>
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
    document.addEventListener("DOMContentLoaded", function () {
        // Pengaturan warna teks untuk Dark Mode
        const darkModeTextColor = '#ffffff';
        const labelStyle = {
            colors: darkModeTextColor,
            fontSize: '12px',
            fontWeight: 600,
            fontFamily: 'Source Sans Pro, sans-serif'
        };

        // Konfigurasi Umum Chart
        const commonOptions = {
            chart: {
                foreColor: darkModeTextColor, // Mengubah semua teks chart menjadi putih
                toolbar: { show: false }
            },
            theme: {
                mode: 'dark' // Mengaktifkan mode gelap bawaan ApexCharts
            }
        };

        // 1. Chart Pendidikan (Donut)
        new ApexCharts(document.querySelector("#chartPendidikan"), {
            ...commonOptions,
            series: <?= json_encode(array_values($pendidikan)) ?>,
            chart: { ...commonOptions.chart, type: 'donut', height: 350 },
            labels: <?= json_encode(array_keys($pendidikan)) ?>,
            stroke: { show: false }, // Menghilangkan garis pinggir agar lebih clean
            plotOptions: {
                pie: {
                    donut: {
                        labels: {
                            show: true,
                            name: { color: darkModeTextColor },
                            value: { color: darkModeTextColor },
                            total: { show: true, label: 'Total', color: darkModeTextColor }
                        }
                    }
                }
            },
            legend: { position: 'bottom', labels: { colors: darkModeTextColor } }
        }).render();

        // 2. Chart Pekerjaan (Bar Horizontal)
        new ApexCharts(document.querySelector("#chartPekerjaan"), {
            ...commonOptions,
            series: [{ name: 'Jiwa', data: <?= json_encode(array_values($pekerjaan)) ?> }],
            chart: { ...commonOptions.chart, type: 'bar', height: 350 },
            plotOptions: {
                bar: {
                    horizontal: true,
                    dataLabels: { position: 'top' }
                }
            },
            dataLabels: {
                enabled: true,
                offsetX: 30,
                style: { colors: [darkModeTextColor] }
            },
            xaxis: {
                categories: <?= json_encode(array_keys($pekerjaan)) ?>,
                labels: { style: labelStyle }
            },
            yaxis: { labels: { style: labelStyle } },
            grid: { borderColor: '#444' } // Warna garis grid lebih halus di dark mode
        }).render();

        // 3. Piramida Penduduk
        const maleData = <?= json_encode($piramidaL) ?>;
        const femaleData = <?= json_encode($piramidaP) ?>;
        const negativeMale = maleData.map(val => -Math.abs(val));

        new ApexCharts(document.querySelector("#chartPiramida"), {
            ...commonOptions,
            series: [
                { name: 'Laki-laki', data: negativeMale },
                { name: 'Perempuan', data: femaleData }
            ],
            chart: { ...commonOptions.chart, type: 'bar', height: 450, stacked: true },
            colors: ['#30befd', '#ff5b5b'], // Warna biru & merah yang lebih neon agar kontras
            plotOptions: { bar: { horizontal: true, barHeight: '85%' } },
            xaxis: {
                categories: <?= json_encode($ageLabels) ?>,
                labels: {
                    formatter: (val) => Math.abs(val),
                    style: labelStyle
                }
            },
            yaxis: { labels: { style: labelStyle } },
            legend: { position: 'top', labels: { colors: darkModeTextColor } },
            grid: { borderColor: '#444' }
        }).render();

        // 4. Status Perkawinan (Radial Bar)
        new ApexCharts(document.querySelector("#chartKawin"), {
            ...commonOptions,
            series: <?= json_encode(array_values($status_kawin)) ?>,
            chart: { ...commonOptions.chart, height: 350, type: 'radialBar' },
            plotOptions: {
                radialBar: {
                    track: { background: '#333' }, // Warna lintasan yang gelap
                    dataLabels: {
                        name: { color: darkModeTextColor },
                        value: { color: darkModeTextColor },
                        total: { show: true, label: 'Total', color: darkModeTextColor }
                    }
                }
            },
            labels: <?= json_encode(array_keys($status_kawin)) ?>,
        }).render();
    });
</script>
<?= $this->endSection() ?>