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

            <div class="row">
                <div class="col-md-6">
                    <div class="card card-outline card-success">
                        <div class="card-header">
                            <h3 class="card-title">Pendidikan KK (Horizontal Bar)</h3>
                        </div>
                        <div class="card-body"><canvas id="barPendidikan" height="150"></canvas></div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card card-outline card-primary">
                        <div class="card-header">
                            <h3 class="card-title">Pekerjaan KK (Doughnut)</h3>
                        </div>
                        <div class="card-body"><canvas id="doughnutPekerjaan" height="150"></canvas></div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-7">
                    <div class="card card-outline card-info">
                        <div class="card-header">
                            <h3 class="card-title">Piramida Penduduk</h3>
                        </div>
                        <div class="card-body"><canvas id="piramidaChart" height="180"></canvas></div>
                    </div>
                </div>
                <div class="col-md-5">
                    <div class="card card-outline card-warning">
                        <div class="card-header">
                            <h3 class="card-title">Status Perkawinan (Polar Area)</h3>
                        </div>
                        <div class="card-body"><canvas id="polarKawin" height="180"></canvas></div>
                    </div>
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

<script>
    document.addEventListener('DOMContentLoaded', function () {

        // 1. Pendidikan (Horizontal Bar)
        new Chart(document.getElementById('barPendidikan'), {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_keys($pendidikan)) ?>,
                datasets: [{
                    label: 'Jumlah KK',
                    backgroundColor: '#28a745',
                    data: <?= json_encode(array_values($pendidikan)) ?>
                }]
            },
            options: { indexAxis: 'y' }
        });

        // 2. Pekerjaan (Doughnut)
        new Chart(document.getElementById('doughnutPekerjaan'), {
            type: 'doughnut',
            data: {
                labels: <?= json_encode(array_keys($pekerjaan)) ?>,
                datasets: [{
                    data: <?= json_encode(array_values($pekerjaan)) ?>,
                    backgroundColor: ['#007bff', '#dc3545', '#ffc107', '#28a745', '#17a2b8', '#20c997', '#e83e8c', '#6c757d']
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'right'
                    }
                }
            }
        });

        // 3. Piramida Penduduk
        new Chart(document.getElementById('piramidaChart'), {
            type: 'bar',
            data: {
                labels: <?= json_encode($ageLabels) ?>,
                datasets: [
                    {
                        label: 'Laki-laki',
                        backgroundColor: '#007bff',
                        data: <?= json_encode($piramidaL) ?>
                    },
                    {
                        label: 'Perempuan',
                        backgroundColor: '#dc3545',
                        data: <?= json_encode($piramidaP) ?>
                    }
                ]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                scales: {
                    x: {
                        beginAtZero: true,
                        stacked: false
                    }
                }
            }
        });

        // 4. Status Kawin (Polar Area)
        new Chart(document.getElementById('polarKawin'), {
            type: 'polarArea',
            data: {
                labels: <?= json_encode(array_keys($status_kawin)) ?>,
                datasets: [{
                    data: <?= json_encode(array_values($status_kawin)) ?>,
                    backgroundColor: ['#007bff', '#ffc107', '#dc3545', '#6c757d']
                }]
            },
            options: {
                responsive: true
            }
        });
    });
</script>

<?= $this->endSection() ?>