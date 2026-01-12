<?= $this->extend('templates/main') ?>
<?= $this->section('content') ?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0"><i class="fas fa-chart-line mr-2"></i><?= $title ?></h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="#">Home</a></li>
                        <li class="breadcrumb-item active">Dashboard</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">

            <!-- Filter Card dengan Chained Dropdown -->
            <div class="card card-outline card-primary shadow-sm mb-4">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-filter mr-2"></i>Filter Wilayah</h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-tool" data-card-widget="collapse">
                            <i class="fas fa-minus"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <form action="/dashboard" method="get" id="filterForm">
                        <div class="row">
                            <?php if (session()->get('role') == 'admin_dinas'): ?>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label><i class="fas fa-map-marked-alt mr-1"></i>Kecamatan</label>
                                            <select name="id_kecamatan" id="filter_kecamatan" class="form-control select2bs4">
                                                <option value="">-- Semua Kecamatan --</option>
                                                <?php foreach ($list_kecamatan as $k): ?>
                                                        <option value="<?= $k['id_kecamatan'] ?>" <?= ($filter_kec == $k['id_kecamatan']) ? 'selected' : '' ?>>
                                                            <?= $k['nama_kecamatan'] ?>
                                                        </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                            <?php endif; ?>

                            <?php if (session()->get('role') == 'admin_dinas' || session()->get('role') == 'admin_kecamatan'): ?>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label><i class="fas fa-map-marker-alt mr-1"></i>Desa</label>
                                            <select name="id_desa" id="filter_desa" class="form-control select2bs4">
                                                <option value="">-- Semua Desa --</option>
                                                <?php foreach ($list_desa as $d): ?>
                                                        <option value="<?= $d['id_desa'] ?>" <?= ($filter_desa == $d['id_desa']) ? 'selected' : '' ?>>
                                                            <?= $d['nama_desa'] ?>
                                                        </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                            <?php endif; ?>

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label><i class="fas fa-home mr-1"></i>Dusun</label>
                                    <select name="id_dusun" id="filter_dusun" class="form-control select2bs4">
                                        <option value="">-- Semua Dusun --</option>
                                        <?php foreach ($list_dusun as $dus): ?>
                                                <option value="<?= $dus['id_dusun'] ?>" <?= ($filter_dusun == $dus['id_dusun']) ? 'selected' : '' ?>>
                                                    <?= $dus['nama_dusun'] ?>
                                                </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-2">
                                <div class="form-group">
                                    <label><i class="fas fa-users mr-1"></i>RT</label>
                                    <select name="id_rt" id="filter_rt" class="form-control select2bs4">
                                        <option value="">-- Semua RT --</option>
                                        <?php foreach ($list_rt as $rt): ?>
                                                <option value="<?= $rt['id_rt'] ?>" <?= ($filter_rt == $rt['id_rt']) ? 'selected' : '' ?>>
                                                    RT <?= $rt['no_rt'] ?>
                                                </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-1">
                                <div class="form-group">
                                    <label>&nbsp;</label>
                                    <button type="submit" class="btn btn-primary btn-block">
                                        <i class="fas fa-search"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Info Boxes - Statistik Utama -->
            <div class="row">
                <div class="col-12 col-sm-6 col-md-3">
                    <div class="info-box shadow-sm">
                        <span class="info-box-icon bg-gradient-primary elevation-1">
                            <i class="fas fa-users"></i>
                        </span>
                        <div class="info-box-content">
                            <span class="info-box-text">Total Penduduk</span>
                            <span class="info-box-number">
                                <?= number_format($totals['jiwa_l'] + $totals['jiwa_p']) ?>
                            </span>
                            <small class="text-muted">
                                <i class="fas fa-male text-info"></i> <?= number_format($totals['jiwa_l']) ?> | 
                                <i class="fas fa-female text-danger"></i> <?= number_format($totals['jiwa_p']) ?>
                            </small>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-sm-6 col-md-3">
                    <div class="info-box shadow-sm">
                        <span class="info-box-icon bg-gradient-success elevation-1">
                            <i class="fas fa-home"></i>
                        </span>
                        <div class="info-box-content">
                            <span class="info-box-text">Total Kepala Keluarga</span>
                            <span class="info-box-number">
                                <?= number_format($totals['kk_l'] + $totals['kk_p']) ?>
                            </span>
                            <small class="text-muted">
                                KK Laki: <?= number_format($totals['kk_l']) ?> | 
                                KK Perempuan: <?= number_format($totals['kk_p']) ?>
                            </small>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-sm-6 col-md-3">
                    <div class="info-box shadow-sm">
                        <span class="info-box-icon bg-gradient-warning elevation-1">
                            <i class="fas fa-baby"></i>
                        </span>
                        <div class="info-box-content">
                            <span class="info-box-text">Balita (0-5 Tahun)</span>
                            <span class="info-box-number">
                                <?= number_format($totals['balita']) ?>
                            </span>
                            <small class="text-muted">Anak usia 0-5 tahun</small>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-sm-6 col-md-3">
                    <div class="info-box shadow-sm">
                        <span class="info-box-icon bg-gradient-danger elevation-1">
                            <i class="fas fa-heart"></i>
                        </span>
                        <div class="info-box-content">
                            <span class="info-box-text">Pasangan Usia Subur</span>
                            <span class="info-box-number">
                                <?= number_format($totals['pus']) ?>
                            </span>
                            <small class="text-muted">PUS aktif</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabel Ringkasan per Wilayah -->
            <?php if (!empty($data_summary)): ?>
                <div class="card card-outline card-info shadow-sm">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-table mr-2"></i>
                            Ringkasan Data 
                            <?php if ($filter_desa): ?>
                                    Per Dusun
                            <?php elseif ($filter_kec): ?>
                                    Per Desa
                            <?php else: ?>
                                    Per Kecamatan
                            <?php endif; ?>
                        </h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover m-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th>Wilayah</th>
                                        <th class="text-center">Total KK</th>
                                        <th class="text-center">Total Jiwa</th>
                                        <th class="text-center">Laki-laki</th>
                                        <th class="text-center">Perempuan</th>
                                        <th class="text-center">Balita</th>
                                        <th class="text-center">PUS</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($data_summary as $s): ?>
                                            <tr>
                                                <td>
                                                    <strong>
                                                        <?= $s['nama_kecamatan'] ?? $s['nama_desa'] ?? $s['nama_dusun'] ?>
                                                    </strong>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge badge-success">
                                                        <?= number_format($s['total_kk']) ?>
                                                    </span>
                                                </td>
                                                <td class="text-center">
                                                    <strong><?= number_format($s['total_jiwa']) ?></strong>
                                                </td>
                                                <td class="text-center">
                                                    <i class="fas fa-male text-info"></i> 
                                                    <?= number_format($s['total_jiwa_l']) ?>
                                                </td>
                                                <td class="text-center">
                                                    <i class="fas fa-female text-danger"></i> 
                                                    <?= number_format($s['total_jiwa_p']) ?>
                                                </td>
                                                <td class="text-center">
                                                    <?= number_format($s['total_balita']) ?>
                                                </td>
                                                <td class="text-center">
                                                    <?= number_format($s['total_pus']) ?>
                                                </td>
                                            </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Grafik Statistik -->
            <div class="row">
                <!-- Piramida Penduduk -->
                <div class="col-md-12">
                    <div class="card card-danger shadow-sm">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-chart-bar mr-2"></i>Piramida Penduduk</h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                    <i class="fas fa-minus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div id="chartPiramida"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Tingkat Pendidikan -->
                <div class="col-md-6">
                    <div class="card card-info shadow-sm">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-graduation-cap mr-2"></i>Tingkat Pendidikan</h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                    <i class="fas fa-minus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div id="chartPendidikan"></div>
                        </div>
                    </div>
                </div>

                <!-- Status Perkawinan -->
                <div class="col-md-6">
                    <div class="card card-warning shadow-sm">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-ring mr-2"></i>Status Perkawinan</h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                    <i class="fas fa-minus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div id="chartKawin"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Jenis Pekerjaan -->
                <div class="col-md-12">
                    <div class="card card-success shadow-sm">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-briefcase mr-2"></i>Jenis Pekerjaan</h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                    <i class="fas fa-minus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div id="chartPekerjaan"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-8">
                    <div class="card card-outline card-primary shadow">
                        <div class="card-header">
                            <h3 class="card-title">Statistik Jiwa & Kepala Keluarga (Gender)</h3>
                        </div>
                        <div class="card-body">
                            <div id="chartGender"></div>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card card-outline card-success shadow">
                        <div class="card-header">
                            <h3 class="card-title">Capaian Dokumen Adminduk</h3>
                        </div>
                        <div class="card-body">
                            <div id="chartDokumen"></div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card card-outline card-info shadow">
                        <div class="card-header">
                            <h3 class="card-title">Distribusi JKN / BPJS</h3>
                        </div>
                        <div class="card-body">
                            <div id="chartBPJS"></div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </section>
</div>

<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
document.addEventListener("DOMContentLoaded", function () {
    // Konfigurasi tema gelap
    const darkModeTextColor = '#ffffff';
    const labelStyle = {
        colors: darkModeTextColor,
        fontSize: '12px',
        fontWeight: 600,
        fontFamily: 'Source Sans Pro, sans-serif'
    };

    const commonOptions = {
        chart: {
            foreColor: darkModeTextColor,
            toolbar: { show: true, tools: { download: true } },
            animations: { enabled: true, speed: 800 }
        },
        theme: { mode: 'dark' },
        grid: { borderColor: '#444', padding: { left: 10, right: 10 } }
    };

    // 1. Piramida Penduduk (Prioritas Utama)
    const maleData = <?= json_encode($piramidaL) ?>;
    const femaleData = <?= json_encode($piramidaP) ?>;
    const negativeMale = maleData.map(val => -Math.abs(val));

     // --- 1. Grafik Gender (Grouped Bar) ---
        var optGender = {
            series: [{
                name: 'Laki-laki',
                data: [<?= $grafik['gender']['jiwa_l'] ?>, <?= $grafik['gender']['kk_l'] ?>]
        }, {
            name: 'Perempuan',
            data: [<?= $grafik['gender']['jiwa_p'] ?>, <?= $grafik['gender']['kk_p'] ?>]
        }],
        chart: { type: 'bar', height: 350 },
        xaxis: { categories: ['Jumlah Jiwa', 'Jumlah KK'] },
        colors: ['#007bff', '#dc3545']
    };
    new ApexCharts(document.querySelector("#chartGender"), optGender).render();

    // --- 2. Grafik Dokumen (Radar atau Bar) ---
    var optDokumen = {
        series: [{
            name: 'Total Punya',
            data: [
                    <?= $grafik['dokumen']['ktp_elektronik'] ?>,
                    <?= $grafik['dokumen']['akta_lahir'] ?>,
                    <?= $grafik['dokumen']['akta_nikah'] ?>,
                    <?= $grafik['dokumen']['kk_fisik'] ?>
                ]
        }],
        chart: { type: 'bar', height: 350 },
        plotOptions: { bar: { horizontal: true } },
        xaxis: { categories: ['KTP-el', 'Akta Lahir', 'Akta Nikah', 'KK Fisik'] },
        colors: ['#28a745']
    };
    new ApexCharts(document.querySelector("#chartDokumen"), optDokumen).render();

    // --- 3. Grafik BPJS (Donut) ---
    var optBPJS = {
        series: [<?= $grafik['bpjs']['pbi'] ?>, <?= $grafik['bpjs']['non_pbi'] ?>, <?= $grafik['bpjs']['non_jkn'] ?>],
        chart: { type: 'donut', height: 350 },
        labels: ['PBI', 'Non PBI', 'Tidak Ada JKN'],
        colors: ['#17a2b8', '#20c997', '#ffc107']
    };
    new ApexCharts(document.querySelector("#chartBPJS"), optBPJS).render();

    new ApexCharts(document.querySelector("#chartPiramida"), {
        ...commonOptions,
        series: [
            { name: 'Laki-laki', data: negativeMale },
            { name: 'Perempuan', data: femaleData }
        ],
        chart: { 
            ...commonOptions.chart, 
            type: 'bar', 
            height: 500, 
            stacked: true 
        },
        colors: ['#3b82f6', '#ec4899'],
        plotOptions: { 
            bar: { 
                horizontal: true, 
                barHeight: '90%',
                borderRadius: 2
            } 
        },
        dataLabels: { 
            enabled: true,
            formatter: (val) => Math.abs(val),
            style: { fontSize: '10px', colors: ['#fff'] }
        },
        xaxis: {
            categories: <?= json_encode($ageLabels) ?>,
            labels: {
                formatter: (val) => Math.abs(Math.round(val)),
                style: labelStyle
            },
            title: { text: 'Jumlah Penduduk', style: { color: darkModeTextColor } }
        },
        yaxis: { 
            labels: { style: labelStyle },
            title: { text: 'Kelompok Umur', style: { color: darkModeTextColor } }
        },
        legend: { 
            position: 'top', 
            horizontalAlign: 'center',
            labels: { colors: darkModeTextColor },
            markers: { width: 12, height: 12 }
        },
        tooltip: {
            shared: false,
            x: { formatter: (val) => 'Umur: ' + val + ' tahun' },
            y: { formatter: (val) => Math.abs(val) + ' orang' }
        }
    }).render();

    // 2. Pendidikan (Donut Chart)
    new ApexCharts(document.querySelector("#chartPendidikan"), {
        ...commonOptions,
        series: <?= json_encode(array_values($pendidikan)) ?>,
        chart: { 
            ...commonOptions.chart, 
            type: 'donut', 
            height: 380 
        },
        labels: <?= json_encode(array_keys($pendidikan)) ?>,
        colors: ['#6366f1', '#8b5cf6', '#ec4899', '#f59e0b', '#10b981', '#3b82f6', '#06b6d4'],
        stroke: { show: false },
        plotOptions: {
            pie: {
                donut: {
                    size: '65%',
                    labels: {
                        show: true,
                        name: { 
                            color: darkModeTextColor,
                            fontSize: '16px',
                            fontWeight: 600
                        },
                        value: { 
                            color: darkModeTextColor,
                            fontSize: '24px',
                            fontWeight: 700,
                            formatter: (val) => val.toLocaleString()
                        },
                        total: { 
                            show: true, 
                            label: 'Total', 
                            color: darkModeTextColor,
                            fontSize: '14px',
                            fontWeight: 600,
                            formatter: (w) => w.globals.seriesTotals.reduce((a, b) => a + b, 0).toLocaleString()
                        }
                    }
                }
            }
        },
        legend: { 
            position: 'bottom',
            labels: { colors: darkModeTextColor },
            fontSize: '13px'
        },
        dataLabels: {
            enabled: true,
            formatter: (val, opts) => Math.round(val) + '%',
            style: { fontSize: '12px', fontWeight: 600 }
        }
    }).render();

    // 3. Status Perkawinan (Donut Chart)
    new ApexCharts(document.querySelector("#chartKawin"), {
        ...commonOptions,
        series: <?= json_encode(array_values($status_kawin)) ?>,
        chart: { 
            ...commonOptions.chart, 
            type: 'donut', 
            height: 380 
        },
        labels: <?= json_encode(array_keys($status_kawin)) ?>,
        colors: ['#10b981', '#3b82f6', '#f59e0b', '#ef4444'],
        stroke: { show: false },
        plotOptions: {
            pie: {
                donut: {
                    size: '65%',
                    labels: {
                        show: true,
                        name: { 
                            color: darkModeTextColor,
                            fontSize: '16px',
                            fontWeight: 600
                        },
                        value: { 
                            color: darkModeTextColor,
                            fontSize: '24px',
                            fontWeight: 700,
                            formatter: (val) => val.toLocaleString()
                        },
                        total: { 
                            show: true, 
                            label: 'Total', 
                            color: darkModeTextColor,
                            fontSize: '14px',
                            fontWeight: 600,
                            formatter: (w) => w.globals.seriesTotals.reduce((a, b) => a + b, 0).toLocaleString()
                        }
                    }
                }
            }
        },
        legend: { 
            position: 'bottom',
            labels: { colors: darkModeTextColor },
            fontSize: '13px'
        },
        dataLabels: {
            enabled: true,
            formatter: (val, opts) => Math.round(val) + '%',
            style: { fontSize: '12px', fontWeight: 600 }
        }
    }).render();

    // 4. Pekerjaan (Bar Chart)
    new ApexCharts(document.querySelector("#chartPekerjaan"), {
        ...commonOptions,
        series: [{ 
            name: 'Jumlah Penduduk', 
            data: <?= json_encode(array_values($pekerjaan)) ?> 
        }],
        chart: { 
            ...commonOptions.chart, 
            type: 'bar', 
            height: 400 
        },
        plotOptions: {
            bar: {
                horizontal: true,
                borderRadius: 4,
                dataLabels: { position: 'top' }
            }
        },
        colors: ['#10b981'],
        dataLabels: {
            enabled: true,
            offsetX: 30,
            style: { 
                colors: [darkModeTextColor],
                fontSize: '11px',
                fontWeight: 600
            },
            formatter: (val) => val.toLocaleString()
        },
        xaxis: {
            categories: <?= json_encode(array_keys($pekerjaan)) ?>,
            labels: { 
                style: labelStyle,
                formatter: (val) => val.toLocaleString()
            },
            title: { text: 'Jumlah Penduduk', style: { color: darkModeTextColor } }
        },
        yaxis: { 
            labels: { style: labelStyle },
            title: { text: 'Jenis Pekerjaan', style: { color: darkModeTextColor } }
        }
    }).render();

    // Chained Dropdown Logic
    <?php if (session()->get('role') == 'admin_dinas'): ?>
        $('#filter_kecamatan').change(function() {
            const idKecamatan = $(this).val();
            $('#filter_desa').html('<option value="">-- Semua Desa --</option>');
            $('#filter_dusun').html('<option value="">-- Semua Dusun --</option>');
            $('#filter_rt').html('<option value="">-- Semua RT --</option>');
        
            if (idKecamatan) {
                $.get('/dashboard/getDesaByKecamatan/' + idKecamatan, function(data) {
                    data.forEach(function(desa) {
                        $('#filter_desa').append(`<option value="${desa.id_desa}">${desa.nama_desa}</option>`);
                    });
                });
            }
        });
    <?php endif; ?>

    $('#filter_desa').change(function() {
        const idDesa = $(this).val();
        $('#filter_dusun').html('<option value="">-- Semua Dusun --</option>');
        $('#filter_rt').html('<option value="">-- Semua RT --</option>');
        
        if (idDesa) {
            $.get('/dashboard/getDusunByDesa/' + idDesa, function(data) {
                data.forEach(function(dusun) {
                    $('#filter_dusun').append(`<option value="${dusun.id_dusun}">${dusun.nama_dusun}</option>`);
                });
            });
        }
    });

    $('#filter_dusun').change(function() {
        const idDusun = $(this).val();
        $('#filter_rt').html('<option value="">-- Semua RT --</option>');
        
        if (idDusun) {
            $.get('/dashboard/getRtByDusun/' + idDusun, function(data) {
                data.forEach(function(rt) {
                    $('#filter_rt').append(`<option value="${rt.id_rt}">RT ${rt.no_rt}</option>`);
                });
            });
        }
    });
});
</script>

<?= $this->endSection() ?>