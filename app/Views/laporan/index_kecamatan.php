<?= $this->extend('templates/main') ?>
<?= $this->section('content') ?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1><i class="fas fa-file-alt mr-2"></i><?= esc($title) ?></h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
                        <li class="breadcrumb-item active">Riwayat Laporan</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">

            <!-- Filter Periode -->
            <div class="card card-outline card-primary shadow-sm mb-4">
                <div class="card-body py-2">
                    <form method="get" action="/laporan" class="form-inline">
                        <label class="mr-2"><i class="fas fa-calendar-alt mr-1"></i> Periode:</label>
                        <select name="bulan" class="form-control form-control-sm mr-2 select2bs4">
                            <?php foreach ($bulanList as $num => $nama): ?>
                                <option value="<?= $num ?>" <?= $filterBulan == $num ? 'selected' : '' ?>>
                                    <?= $nama ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <select name="tahun" class="form-control form-control-sm mr-2">
                            <?php for ($y = date('Y'); $y >= date('Y') - 5; $y--): ?>
                                <option value="<?= $y ?>" <?= $filterTahun == $y ? 'selected' : '' ?>><?= $y ?></option>
                            <?php endfor; ?>
                        </select>
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="fas fa-filter mr-1"></i> Terapkan
                        </button>
                        <div class="ml-auto">
                            <div class="btn-group">
                                <a href="/laporan/export-options" class="btn btn-info btn-sm">
                                    <i class="fas fa-sliders-h mr-1"></i> Cetak Kustom
                                </a>
                                <button type="button" class="btn btn-success btn-sm dropdown-toggle" data-toggle="dropdown">
                                    <i class="fas fa-file-export mr-1"></i> Export Semua
                                </button>
                                <div class="dropdown-menu dropdown-menu-right">
                                    <a class="dropdown-item"
                                        href="/laporan/export/excel?bulan=<?= $filterBulan ?>&tahun=<?= $filterTahun ?>">
                                        <i class="fas fa-file-excel text-success mr-2"></i> Excel (.xlsx)
                                    </a>
                                    <a class="dropdown-item"
                                        href="/laporan/export/pdf?bulan=<?= $filterBulan ?>&tahun=<?= $filterTahun ?>">
                                        <i class="fas fa-file-pdf text-danger mr-2"></i> PDF (.pdf)
                                    </a>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Summary strip -->
            <?php
                $totalDesa      = count($desaStats);
                $desaLengkap    = count(array_filter($desaStats, fn($d) => $d['sudah_lapor'] > 0));
                $totalSudahSemua = array_sum(array_column($desaStats, 'sudah_lapor'));
                $persenSemua    = $totalDesa > 0 ? round($desaLengkap / $totalDesa * 100) : 0;
            ?>
            <div class="row mb-4">
                <div class="col-md-3 col-6">
                    <div class="info-box shadow-sm">
                        <span class="info-box-icon bg-gradient-primary">
                            <i class="fas fa-map-marker-alt"></i>
                        </span>
                        <div class="info-box-content">
                            <span class="info-box-text">Total Desa</span>
                            <span class="info-box-number"><?= $totalDesa ?></span>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="info-box shadow-sm">
                        <span class="info-box-icon bg-gradient-success">
                            <i class="fas fa-check-double"></i>
                        </span>
                        <div class="info-box-content">
                            <span class="info-box-text">Desa Lengkap</span>
                            <span class="info-box-number"><?= $desaLengkap ?> / <?= $totalDesa ?></span>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="info-box shadow-sm">
                        <span class="info-box-icon bg-gradient-info">
                            <i class="fas fa-map-marker-alt"></i>
                        </span>
                        <div class="info-box-content">
                            <span class="info-box-text">Desa Sudah Lapor</span>
                            <span class="info-box-number"><?= $totalSudahSemua ?> / <?= $totalDesa ?></span>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="info-box shadow-sm">
                        <span class="info-box-icon <?= $persenSemua == 100 ? 'bg-gradient-success' : ($persenSemua >= 50 ? 'bg-gradient-warning' : 'bg-gradient-danger') ?>">
                            <i class="fas fa-percent"></i>
                        </span>
                        <div class="info-box-content">
                            <span class="info-box-text">Capaian Keseluruhan</span>
                            <span class="info-box-number"><?= $persenSemua ?>%</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card Grid per Desa -->
            <div class="row">
                <?php foreach ($desaStats as $stat): ?>
                    <?php
                        $desa     = $stat['desa'];
                        $persen   = $stat['persen'];
                        $colorClass = $persen == 100 ? 'success' : ($persen >= 50 ? 'warning' : 'danger');
                        $cardBorder = $persen == 100 ? 'card-success' : ($persen >= 50 ? 'card-warning' : 'card-danger');
                    ?>
                    <div class="col-md-4 col-sm-6">
                        <div class="card card-outline <?= $cardBorder ?> shadow-sm mb-3">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-map-marker-alt mr-2 text-muted"></i>
                                    <?= esc($desa['nama_desa']) ?>
                                </h5>
                                <div class="card-tools">
                                    <?php if ($persen == 100): ?>
                                        <span class="badge badge-success"><i class="fas fa-check mr-1"></i>Lengkap</span>
                                    <?php elseif ($stat['sudah_lapor'] == 0): ?>
                                        <span class="badge badge-danger">Belum Ada</span>
                                    <?php else: ?>
                                        <span class="badge badge-warning"><?= $persen ?>%</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="card-body pb-2">
                                <!-- Progress bar -->
                                <div class="progress mb-2" style="height: 8px;">
                                    <div class="progress-bar bg-<?= $colorClass ?>"
                                        style="width: <?= $persen ?>%"
                                        title="<?= $persen ?>% desa sudah lapor">
                                    </div>
                                </div>

                                <!-- Statistik laporan desa -->
                                <div class="row text-center mb-2">
                                    <div class="col-6">
                                        <div class="text-success" style="font-size:11px;">Sudah Lapor</div>
                                        <strong class="text-success"><?= $stat['sudah_lapor'] ?></strong>
                                    </div>
                                    <div class="col-6">
                                        <div class="text-danger" style="font-size:11px;">Belum Lapor</div>
                                        <strong class="text-danger"><?= $stat['belum_lapor'] ?></strong>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer p-2 text-right">
                                <button type="button"
                                    class="btn btn-sm btn-outline-secondary btn-detail-desa"
                                    data-id="<?= $desa['id_desa'] ?>"
                                    data-nama="<?= esc($desa['nama_desa']) ?>"
                                    data-toggle="modal" data-target="#modalDetailDesa">
                                    <i class="fas fa-list mr-1"></i> Lihat Detail
                                </button>
                                <a href="/laporan?id_desa=<?= $desa['id_desa'] ?>&bulan=<?= $filterBulan ?>&tahun=<?= $filterTahun ?>"
                                    class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-table mr-1"></i> Tabel Lengkap
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

        </div>
    </section>
</div>

<!-- Modal Detail Laporan per Desa -->
<div class="modal fade" id="modalDetailDesa" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-map-marker-alt mr-2"></i>
                    Detail Laporan - <span id="modalDesaNama"></span>
                </h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="modalDesaContent">
                    <div class="text-center py-4">
                        <i class="fas fa-spinner fa-spin fa-2x"></i>
                        <p class="mt-2">Memuat data...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    $('.btn-detail-desa').on('click', function () {
        const idDesa = $(this).data('id');
        const namaDesa = $(this).data('nama');
        const bulan = <?= $filterBulan ?>;
        const tahun = <?= $filterTahun ?>;

        $('#modalDesaNama').text(namaDesa);
        $('#modalDesaContent').html('<div class="text-center py-4"><i class="fas fa-spinner fa-spin fa-2x"></i><p class="mt-2">Memuat data...</p></div>');

        $.get('<?= base_url('laporan/detailDesa') ?>/' + idDesa + '?bulan=' + bulan + '&tahun=' + tahun, function (res) {
            if (res.status === 'success') {
                function esc(str) {
                    return $('<span>').text(str).html();
                }

                let html = '<table class="table table-sm table-bordered">';
                html += '<thead class="bg-light"><tr><th class="text-center">Status</th><th class="text-center">Jiwa</th><th class="text-center">KK</th><th class="text-center">Aksi</th></tr></thead><tbody>';

                res.data.forEach(function (row) {
                    const statusBadge = row.id_laporan
                        ? '<span class="badge badge-success"><i class="fas fa-check mr-1"></i>Sudah Lapor</span>'
                        : '<span class="badge badge-danger"><i class="fas fa-times mr-1"></i>Belum Lapor</span>';

                    const jiwa = row.id_laporan ? (parseInt(row.jiwa_l) + parseInt(row.jiwa_p)).toLocaleString('id-ID') : '-';
                    const kk   = row.id_laporan ? (parseInt(row.kk_l)   + parseInt(row.kk_p)).toLocaleString('id-ID')   : '-';

                    const idLaporan = parseInt(row.id_laporan) || 0;
                    const aksi = idLaporan
                        ? `<a href="/laporan/edit/${idLaporan}" class="btn btn-xs btn-warning" title="Edit"><i class="fas fa-edit"></i></a>`
                        : `<a href="/laporan/input" class="btn btn-xs btn-primary" title="Input"><i class="fas fa-plus"></i></a>`;

                    html += `<tr class="${idLaporan ? '' : 'table-danger'}">`;
                    html += `<td class="text-center">${statusBadge}</td>`;
                    html += `<td class="text-center">${jiwa}</td>`;
                    html += `<td class="text-center">${kk}</td>`;
                    html += `<td class="text-center">${aksi}</td>`;
                    html += '</tr>';
                });

                html += '</tbody></table>';
                $('#modalDesaContent').html(html);
            } else {
                $('#modalDesaContent').html('<p class="text-danger">Gagal memuat data.</p>');
            }
        }).fail(function () {
            $('#modalDesaContent').html('<p class="text-danger">Gagal memuat data.</p>');
        });
    });
});
</script>

<?= $this->endSection() ?>
