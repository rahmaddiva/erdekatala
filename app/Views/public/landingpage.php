<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title>ERDEKATALA - Statistik Kependudukan</title>
    <meta name="description" content="Sistem Informasi Statistik Kependudukan Tanah Laut">
    <meta name="keywords" content="kependudukan, statistik, tanah laut">

    <!-- Fonts -->
    <link href="https://fonts.googleapis.com" rel="preconnect">
    <link href="https://fonts.gstatic.com" rel="preconnect" crossorigin>
    <!-- icon -->
    <link rel="icon" type="image/x-icon" href="/assets/dist/img/erdekatala.png">
    <link
        href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&family=Poppins:wght@300;400;600;700&display=swap"
        rel="stylesheet">

    <!-- Vendor CSS Files -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">

    <!-- ApexCharts -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/apexcharts@3.45.1/dist/apexcharts.css">

    <style>
        :root {
            --primary-color: #dd4814;
            --secondary-color: #2c3e50;
            --accent-color: #3498db;
            --dark-bg: #1a1a1a;
            --light-bg: #f8f9fa;
        }

        body {
            font-family: 'Roboto', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #e8f0f7 100%);
            min-height: 100vh;
        }

        .navbar {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 0.75rem 0;
        }

        .navbar-brand {
            font-family: 'Poppins', sans-serif;
            font-weight: 700;
            font-size: 1.5rem;
            color: var(--primary-color) !important;
        }

        .hero-section {
            padding: 80px 0 40px;
            color: white;
            text-align: center;
            background: #e8f0f7 margin-bottom: 2rem;
            margin-top: 70px;
        }

        .hero-section h1 {
            font-family: 'Poppins', sans-serif;
            font-weight: 700;
            font-size: 3rem;
            margin-bottom: 1rem;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
        }

        .hero-section p {
            font-size: 1.2rem;
            opacity: 0.9;
        }

        .filter-card {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            margin-bottom: 2.5rem;
        }

        .stats-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        }

        .stats-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            margin-bottom: 1rem;
        }

        .stats-number {
            font-size: 2.5rem;
            font-weight: 700;
            font-family: 'Poppins', sans-serif;
        }

        .stats-label {
            color: #6c757d;
            font-size: 0.95rem;
        }

        .chart-card {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }

        .chart-title {
            font-family: 'Poppins', sans-serif;
            font-weight: 600;
            font-size: 1.3rem;
            margin-bottom: 1.5rem;
            color: var(--secondary-color);
        }

        footer {
            background: var(--secondary-color);
            color: white;
            padding: 2rem 0;
            margin-top: 4rem;
        }

        .btn-filter {
            background: linear-gradient(135deg, var(--primary-color), #e67e22);
            border: none;
            color: white;
            padding: 0.75rem 2rem;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-filter:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(221, 72, 20, 0.4);
            color: white;
        }

        .form-select,
        .form-control {
            border: 2px solid #e9ecef;
            border-radius: 8px;
            padding: 0.75rem;
            transition: all 0.3s ease;
        }

        .form-select:focus,
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(221, 72, 20, 0.25);
        }

        .navbar-nav .nav-link {
            font-weight: 500;
            margin: 0 0.5rem;
            transition: all 0.3s ease;
            color: #2c3e50 !important;
            border-radius: 6px;
            padding: 0.5rem 1rem !important;
        }

        .navbar-nav .nav-link:hover {
            background-color: rgba(221, 72, 20, 0.1);
            color: var(--primary-color) !important;
            transform: translateY(-2px);
        }

        .navbar-nav .nav-link.active {
            background-color: var(--primary-color);
            color: white !important;
        }

        .navbar-brand img {
            transition: transform 0.3s ease;
        }

        .navbar-brand:hover img {
            transform: scale(1.05);
        }

        @media (max-width: 768px) {
            .hero-section h1 {
                font-size: 2rem;
            }

            .stats-number {
                font-size: 1.8rem;
            }
        }
    </style>
</head>

<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light fixed-top">
        <div class="container">
            <a class="navbar-brand fw-bold" href="/">
                <img src="/assets/dist/img/erdekataladark.png" alt="ERDEKATALA" width="150" height="50"
                    class="d-inline-block align-text-top">
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
                aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item">
                        <a class="nav-link" href="#statistik">
                            <i class="bi bi-graph-up"></i> Statistik
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link btn btn-sm"
                            style="background: linear-gradient(135deg, var(--primary-color), #e67e22); color: white; border: none; font-weight: 600;"
                            href="/login">
                            <i class="bi bi-box-arrow-in-right"></i> Login
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <section>
        <div class="hero-section">
            <div class="container">
                <img src="/assets/dist/img/erdekataladark.png" alt="ERDEKATALA" width="200" height="70"
                    class="d-inline-block align-text-top mb-3">
            </div>
        </div>
    </section>

    <!-- Main Content -->
    <div class="container pb-5" style="padding-top: 2rem;">
        <!-- Filter Section -->
        <div class="filter-card">
            <h4 class="mb-4"><i class="bi bi-funnel-fill text-primary"></i> Filter Wilayah</h4>
            <form method="get" action="/" id="filterForm">
                <div class="row g-3">
                    <div class="col-md-5">
                        <label class="form-label fw-bold">Kecamatan</label>
                        <select name="id_kecamatan" id="filter_kecamatan" class="form-select">
                            <option value="">-- Semua Kecamatan --</option>
                            <?php foreach ($list_kecamatan as $k): ?>
                                <option value="<?= $k['id_kecamatan'] ?>" <?= ($filter_kec == $k['id_kecamatan']) ? 'selected' : '' ?>>
                                    <?= esc($k['nama_kecamatan']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label fw-bold">Desa</label>
                        <select name="id_desa" id="filter_desa" class="form-select">
                            <option value="">-- Semua Desa --</option>
                            <?php foreach ($list_desa as $d): ?>
                                <option value="<?= $d['id_desa'] ?>" <?= ($filter_desa == $d['id_desa']) ? 'selected' : '' ?>>
                                    <?= esc($d['nama_desa']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-filter w-100">
                            <i class="bi bi-search"></i> Tampilkan
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Statistics Cards -->
        <div class="row" id="statistik">
            <div class="col-md-3 col-sm-6">
                <div class="stats-card">
                    <div class="stats-icon" style="background: linear-gradient(135deg, #667eea, #764ba2);">
                        <i class="bi bi-people-fill text-white"></i>
                    </div>
                    <div class="stats-number text-primary">
                        <?= number_format($totals['jiwa_l'] + $totals['jiwa_p']) ?>
                    </div>
                    <div class="stats-label">Total Penduduk</div>
                    <small class="text-muted">
                        <i class="bi bi-gender-male text-info"></i> <?= number_format($totals['jiwa_l']) ?> |
                        <i class="bi bi-gender-female text-danger"></i> <?= number_format($totals['jiwa_p']) ?>
                    </small>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="stats-card">
                    <div class="stats-icon" style="background: linear-gradient(135deg, #f093fb, #f5576c);">
                        <i class="bi bi-house-fill text-white"></i>
                    </div>
                    <div class="stats-number text-success">
                        <?= number_format($totals['kk_l'] + $totals['kk_p']) ?>
                    </div>
                    <div class="stats-label">Kepala Keluarga</div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="stats-card">
                    <div class="stats-icon" style="background: linear-gradient(135deg, #4facfe, #00f2fe);">
                        <i class="bi bi-emoji-smile-fill text-white"></i>
                    </div>
                    <div class="stats-number text-warning">
                        <?= number_format($totals['balita']) ?>
                    </div>
                    <div class="stats-label">Balita (0-5 Tahun)</div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="stats-card">
                    <div class="stats-icon" style="background: linear-gradient(135deg, #fa709a, #fee140);">
                        <i class="bi bi-heart-fill text-white"></i>
                    </div>
                    <div class="stats-number text-danger">
                        <?= number_format($totals['pus']) ?>
                    </div>
                    <div class="stats-label">Pasangan Usia Subur</div>
                </div>
            </div>
        </div>

        <!-- Charts Section -->
        <div class="row">
            <!-- Pendidikan Chart -->
            <div class="col-lg-6">
                <div class="chart-card">
                    <h5 class="chart-title">
                        <i class="bi bi-mortarboard-fill text-info"></i> Tingkat Pendidikan
                    </h5>
                    <div id="chartPendidikan"></div>
                </div>
            </div>

            <!-- JKN/BPJS Chart -->
            <div class="col-lg-6">
                <div class="chart-card">
                    <h5 class="chart-title">
                        <i class="bi bi-heart-pulse-fill text-danger"></i> Kepesertaan JKN/BPJS
                    </h5>
                    <div id="chartJKN"></div>
                </div>
            </div>

            <!-- Pekerjaan Chart -->
            <div class="col-lg-12">
                <div class="chart-card">
                    <h5 class="chart-title">
                        <i class="bi bi-briefcase-fill text-success"></i> Jenis Pekerjaan
                    </h5>
                    <div id="chartPekerjaan"></div>
                </div>
            </div>

            <!-- Gender Chart -->
            <div class="col-lg-12">
                <div class="chart-card">
                    <h5 class="chart-title">
                        <i class="bi bi-gender-ambiguous text-primary"></i> Komposisi Jenis Kelamin
                    </h5>
                    <div id="chartGender"></div>
                </div>
            </div>

            <!-- Piramida Penduduk Chart -->
            <div class="col-lg-12">
                <div class="chart-card">
                    <h5 class="chart-title">
                        <i class="bi bi-diagram-3-fill text-success"></i> Piramida Penduduk
                    </h5>
                    <div id="chartPiramida"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer>
        <div class="container text-center">
            <p class="mb-0">&copy; <?= date('Y') ?> ERDEKATALA - DP3AP2KB Kabupaten Tanah Laut</p>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/apexcharts@3.45.1/dist/apexcharts.min.js"></script>

    <script>
        // Chained Dropdown Logic
        $('#filter_kecamatan').change(function () {
            const idKecamatan = $(this).val();
            $('#filter_desa').html('<option value="">Memuat...</option>');

            if (idKecamatan) {
                $.get('/getDesaByKecamatan/' + idKecamatan, function (data) {
                    let html = '<option value="">-- Semua Desa --</option>';
                    data.forEach(function (desa) {
                        html += `<option value="${desa.id_desa}">${desa.nama_desa}</option>`;
                    });
                    $('#filter_desa').html(html);
                });
            } else {
                $('#filter_desa').html('<option value="">-- Semua Desa --</option>');
            }
        });

        // ApexCharts Configuration
        const chartOptions = {
            chart: {
                fontFamily: 'Roboto, sans-serif',
                toolbar: {
                    show: true,
                    tools: {
                        download: true,
                        selection: true,
                        zoom: true,
                        zoomin: true,
                        zoomout: true,
                        pan: true,
                        reset: true
                    },
                    export: {
                        csv: {
                            filename: 'data.csv'
                        },
                        svg: {
                            filename: 'chart.svg'
                        },
                        png: {
                            filename: 'chart.png'
                        }
                    }
                },
                animations: { enabled: true, speed: 800 }
            },
            theme: { mode: 'light' },
            dataLabels: {
                enabled: true,
                style: { fontSize: '12px', fontWeight: 600 }
            }
        };

        // Chart Pendidikan (Donut)
        new ApexCharts(document.querySelector("#chartPendidikan"), {
            ...chartOptions,
            series: <?= json_encode(array_values($pendidikan)) ?>,
            chart: { ...chartOptions.chart, type: 'donut', height: 350 },
            labels: <?= json_encode(array_keys($pendidikan)) ?>,
            colors: ['#6366f1', '#8b5cf6', '#ec4899', '#f59e0b', '#10b981', '#3b82f6', '#06b6d4'],
            legend: { position: 'bottom' },
            plotOptions: {
                pie: {
                    donut: {
                        size: '65%',
                        labels: {
                            show: true,
                            total: {
                                show: true,
                                label: 'Total',
                                formatter: (w) => w.globals.seriesTotals.reduce((a, b) => a + b, 0).toLocaleString()
                            }
                        }
                    }
                }
            }
        }).render();

        // Chart JKN/BPJS (Donut)
        new ApexCharts(document.querySelector("#chartJKN"), {
            ...chartOptions,
            series: <?= json_encode(array_values($jkn_bpjs)) ?>,
            chart: { ...chartOptions.chart, type: 'donut', height: 350 },
            labels: <?= json_encode(array_keys($jkn_bpjs)) ?>,
            colors: ['#06b6d4', '#14b8a6', '#f59e0b'],
            legend: { position: 'bottom' },
            plotOptions: {
                pie: {
                    donut: {
                        size: '65%',
                        labels: {
                            show: true,
                            total: {
                                show: true,
                                label: 'Total',
                                formatter: (w) => w.globals.seriesTotals.reduce((a, b) => a + b, 0).toLocaleString()
                            }
                        }
                    }
                }
            }
        }).render();

        // Chart Pekerjaan (Bar)
        new ApexCharts(document.querySelector("#chartPekerjaan"), {
            ...chartOptions,
            series: [{
                name: 'Jumlah Penduduk',
                data: <?= json_encode(array_values($pekerjaan)) ?>
            }],
            chart: { ...chartOptions.chart, type: 'bar', height: 400 },
            plotOptions: {
                bar: {
                    horizontal: true,
                    borderRadius: 8,
                    dataLabels: { position: 'top' }
                }
            },
            colors: ['#10b981'],
            xaxis: {
                categories: <?= json_encode(array_keys($pekerjaan)) ?>,
                labels: { formatter: (val) => val.toLocaleString() }
            },
            yaxis: { labels: { style: { fontSize: '13px' } } }
        }).render();

        // Chart Gender (Bar)
        new ApexCharts(document.querySelector("#chartGender"), {
            ...chartOptions,
            series: [
                { name: 'Laki-laki', data: [<?= $totals['jiwa_l'] ?>, <?= $totals['kk_l'] ?>] },
                { name: 'Perempuan', data: [<?= $totals['jiwa_p'] ?>, <?= $totals['kk_p'] ?>] }
            ],
            chart: { ...chartOptions.chart, type: 'bar', height: 350 },
            plotOptions: {
                bar: {
                    horizontal: false,
                    columnWidth: '55%',
                    borderRadius: 8
                }
            },
            colors: ['#3b82f6', '#ec4899'],
            xaxis: {
                categories: ['Jumlah Jiwa', 'Jumlah KK']
            },
            yaxis: {
                labels: { formatter: (val) => val.toLocaleString() }
            },
            legend: { position: 'top' }
        }).render();

        // Chart Piramida Penduduk (Horizontal Bar)
        new ApexCharts(document.querySelector("#chartPiramida"), {
            ...chartOptions,
            dataLabels: {
                enabled: false
            },
            series: [
                {
                    name: 'Laki-laki',
                    data: <?= json_encode(array_map(function ($v) {
                        return -$v;
                    }, $piramidaL)) ?>
                },
                {
                    name: 'Perempuan',
                    data: <?= json_encode($piramidaP) ?>
                }
            ],
            chart: { ...chartOptions.chart, type: 'bar', height: 450 },
            plotOptions: {
                bar: {
                    horizontal: true,
                    borderRadius: 4,
                    dataLabels: { position: 'top' }
                }
            },
            colors: ['#3b82f6', '#ec4899'],
            xaxis: {
                categories: <?= json_encode($ageLabels) ?>,
                labels: {
                    formatter: (val) => Math.abs(val).toLocaleString()
                }
            },
            yaxis: {
                labels: { style: { fontSize: '12px' } }
            },
            legend: { position: 'top', horizontalAlign: 'center' },
            tooltip: {
                shared: true,
                intersect: false,
                theme: 'light',
                custom: function ({ series, seriesIndex, dataPointIndex, w }) {
                    const category = w.globals.labels[dataPointIndex];
                    const value1 = Math.abs(Math.round(series[0][dataPointIndex]));
                    const value2 = Math.round(series[1][dataPointIndex]);
                    const total = value1 + value2;
                    return `
                        <div style="padding: 12px; background: white; border: 1px solid #e5e7eb; border-radius: 6px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                            <div style="font-weight: 600; color: #1f2937; margin-bottom: 8px;">Kelompok Usia: ${category} tahun</div>
                            <div style="display: flex; justify-content: space-between; gap: 16px; margin-bottom: 6px;">
                                <span style="color: #3b82f6;"><strong>Laki-laki:</strong></span>
                                <span style="color: #3b82f6; font-weight: 600;">${value1.toLocaleString()}</span>
                            </div>
                            <div style="display: flex; justify-content: space-between; gap: 16px; margin-bottom: 6px;">
                                <span style="color: #ec4899;"><strong>Perempuan:</strong></span>
                                <span style="color: #ec4899; font-weight: 600;">${value2.toLocaleString()}</span>
                            </div>
                            <div style="border-top: 1px solid #e5e7eb; padding-top: 6px; color: #6b7280; font-size: 12px;">
                                <strong>Total: ${total.toLocaleString()}</strong>
                            </div>
                        </div>
                    `;
                }
            }
        }).render();
    </script>
</body>

</html>