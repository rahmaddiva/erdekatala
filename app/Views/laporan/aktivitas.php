<?= $this->extend('templates/main') ?>
<?= $this->section('content') ?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1><i class="fas fa-history mr-2"></i><?= esc($title) ?></h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
                        <li class="breadcrumb-item active">Aktivitas Perubahan</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">

            <div class="card card-outline card-primary shadow-sm mb-3">
                <div class="card-body py-2">
                    <form method="get" action="/laporan/aktivitas" class="form-inline flex-wrap">
                        <label class="mr-2 mb-1"><i class="fas fa-filter mr-1"></i>Filter:</label>

                        <select name="id_desa" class="form-control form-control-sm mr-2 mb-1">
                            <option value="">-- Semua Desa --</option>
                            <?php foreach ($list_desa as $d): ?>
                                <option value="<?= $d['id_desa'] ?>" <?= ($filter['id_desa'] == $d['id_desa']) ? 'selected' : '' ?>>
                                    <?= esc($d['nama_desa']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <select name="aksi" class="form-control form-control-sm mr-2 mb-1">
                            <option value="">-- Semua Aksi --</option>
                            <option value="create" <?= $filter['aksi'] === 'create' ? 'selected' : '' ?>>Dibuat</option>
                            <option value="update" <?= $filter['aksi'] === 'update' ? 'selected' : '' ?>>Diperbarui</option>
                            <option value="delete" <?= $filter['aksi'] === 'delete' ? 'selected' : '' ?>>Dihapus</option>
                        </select>

                        <select name="bulan" class="form-control form-control-sm mr-2 mb-1">
                            <option value="">-- Semua Bulan --</option>
                            <?php foreach ($bulanList as $num => $nama): ?>
                                <option value="<?= $num ?>" <?= $filter['bulan'] == $num ? 'selected' : '' ?>><?= $nama ?></option>
                            <?php endforeach; ?>
                        </select>

                        <select name="tahun" class="form-control form-control-sm mr-2 mb-1">
                            <option value="">-- Semua Tahun --</option>
                            <?php for ($y = date('Y'); $y >= date('Y') - 5; $y--): ?>
                                <option value="<?= $y ?>" <?= $filter['tahun'] == $y ? 'selected' : '' ?>><?= $y ?></option>
                            <?php endfor; ?>
                        </select>

                        <button type="submit" class="btn btn-primary btn-sm mb-1 mr-1">
                            <i class="fas fa-search mr-1"></i> Terapkan
                        </button>
                        <a href="/laporan/aktivitas" class="btn btn-secondary btn-sm mb-1">Reset</a>
                    </form>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-stream mr-1"></i>
                        Log Perubahan
                        <span class="badge badge-secondary ml-1"><?= count($logs) ?></span>
                    </h3>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($logs)): ?>
                        <div class="text-center text-muted py-5">
                            <i class="fas fa-inbox fa-3x mb-3 d-block"></i>
                            Belum ada aktivitas perubahan.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover table-sm mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th style="width:140px">Waktu</th>
                                        <th>Desa</th>
                                        <th>Periode</th>
                                        <th>Aksi</th>
                                        <th>Oleh</th>
                                        <th>Detail Perubahan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $aksiLabel = ['create' => 'Dibuat', 'update' => 'Diperbarui', 'delete' => 'Dihapus'];
                                    $aksiColor = ['create' => 'success', 'update' => 'warning', 'delete' => 'danger'];
                                    foreach ($logs as $log):
                                        $aksi = $log['aksi'] ?? '';
                                    ?>
                                        <tr>
                                            <td>
                                                <small><?= !empty($log['created_at']) ? date('d-m-Y H:i', strtotime($log['created_at'])) : '-' ?></small>
                                            </td>
                                            <td>
                                                <span class="badge badge-secondary"><?= esc($log['nama_desa'] ?? '-') ?></span>
                                            </td>
                                            <td>
                                                <?php if (!empty($log['bulan']) && !empty($log['tahun'])): ?>
                                                    <strong><?= esc($bulanList[$log['bulan']] ?? $log['bulan']) ?></strong>
                                                    <?= esc($log['tahun']) ?>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge badge-<?= $aksiColor[$aksi] ?? 'secondary' ?>">
                                                    <?= esc($aksiLabel[$aksi] ?? $aksi) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <strong><?= esc($log['nama_lengkap'] ?? 'Sistem') ?></strong>
                                                <br><small class="text-muted"><?= esc($log['role'] ?? '-') ?></small>
                                            </td>
                                            <td>
                                                <?php if (!empty($log['perubahan']) && is_array($log['perubahan'])): ?>
                                                    <button type="button"
                                                        class="btn btn-xs btn-outline-info btn-detail-diff"
                                                        data-diff='<?= esc(json_encode($log['perubahan']), 'attr') ?>'>
                                                        <i class="fas fa-list mr-1"></i>
                                                        <?= count($log['perubahan']) ?> field berubah
                                                    </button>
                                                <?php elseif ($aksi === 'update'): ?>
                                                    <small class="text-muted">Tidak ada field berubah</small>
                                                <?php elseif ($aksi === 'create'): ?>
                                                    <small class="text-muted">Laporan baru dibuat</small>
                                                <?php elseif ($aksi === 'delete'): ?>
                                                    <small class="text-muted">Laporan dihapus</small>
                                                <?php else: ?>
                                                    <small class="text-muted">-</small>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="card-footer text-muted">
                            <small>Menampilkan maksimal 100 aktivitas terbaru.</small>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </section>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<div class="modal fade" id="modalDiff" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-exchange-alt mr-1"></i> Detail Perubahan Field</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body" id="modalDiffContent"></div>
        </div>
    </div>
</div>
<script>
$(document).on('click', '.btn-detail-diff', function () {
    var diff = $(this).data('diff');
    if (typeof diff === 'string') {
        try { diff = JSON.parse(diff); } catch (e) { diff = []; }
    }
    if (!diff || !diff.length) {
        $('#modalDiffContent').html('<p class="text-muted text-center">Tidak ada detail.</p>');
    } else {
        var html = '<table class="table table-sm table-bordered mb-0"><thead class="bg-light"><tr>';
        html += '<th>Field</th><th class="text-right">Lama</th><th class="text-right">Baru</th></tr></thead><tbody>';
        diff.forEach(function (p) {
            html += '<tr><td>' + (p.label || p.field) + '</td>';
            html += '<td class="text-right text-danger">' + p.lama + '</td>';
            html += '<td class="text-right text-success"><strong>' + p.baru + '</strong></td></tr>';
        });
        html += '</tbody></table>';
        $('#modalDiffContent').html(html);
    }
    $('#modalDiff').modal('show');
});
</script>
<?= $this->endSection() ?>
