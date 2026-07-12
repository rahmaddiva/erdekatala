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

            <!-- Tombol Aksi Atas -->
            <div class="row mb-3">
                <div class="col-12 d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-muted">
                            <i class="fas fa-map-marker-alt mr-1"></i>
                            Laporan Desa: <strong><?= esc(session()->get('nama_desa') ?? '') ?></strong>
                        </span>
                    </div>
                    <div class="btn-group">
                        <a href="/laporan/input" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus mr-1"></i> Input Laporan Baru
                        </a>
                        <a href="/laporan/export-options" class="btn btn-info btn-sm">
                            <i class="fas fa-sliders-h mr-1"></i> Cetak Kustom
                        </a>
                        <button type="button" class="btn btn-success btn-sm dropdown-toggle" data-toggle="dropdown">
                            <i class="fas fa-file-export mr-1"></i> Export Semua
                        </button>
                        <div class="dropdown-menu dropdown-menu-right">
                            <a class="dropdown-item" href="/laporan/export/excel">
                                <i class="fas fa-file-excel text-success mr-2"></i> Excel (.xlsx)
                            </a>
                            <a class="dropdown-item" href="/laporan/export/pdf">
                                <i class="fas fa-file-pdf text-danger mr-2"></i> PDF (.pdf)
                            </a>
                        </div>
                    </div>
                </div>
            </div>

                <!-- Filter Periode -->
            <div class="card card-outline card-primary shadow-sm mb-4">
                <div class="card-body py-2">
                    <form method="get" action="/laporan" class="form-inline">
                        <label class="mr-2"><i class="fas fa-calendar-alt mr-1"></i> Periode:</label>
                        <select name="bulan" class="form-control form-control-sm mr-2">
                            <option value="">-- Semua Bulan --</option>
                            <?php foreach ($bulanList as $num => $nama): ?>
                                <option value="<?= $num ?>" <?= $filterBulan == $num ? 'selected' : '' ?>>
                                    <?= $nama ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <select name="tahun" class="form-control form-control-sm mr-2">
                            <option value="">-- Semua Tahun --</option>
                            <?php for ($y = date('Y'); $y >= date('Y') - 5; $y--): ?>
                                <option value="<?= $y ?>" <?= $filterTahun == $y ? 'selected' : '' ?>><?= $y ?></option>
                            <?php endfor; ?>
                        </select>
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="fas fa-filter mr-1"></i> Terapkan
                        </button>
                        <?php if (!empty($filterBulan) || !empty($filterTahun)): ?>
                            <a href="/laporan" class="btn btn-secondary btn-sm ml-2">
                                <i class="fas fa-times mr-1"></i> Reset
                            </a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>

            <?php if (empty($grouped)): ?>
                <div class="card shadow-sm">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                        <p class="text-muted mb-3">Belum ada laporan yang diinput.</p>
                        <a href="/laporan/input" class="btn btn-primary">
                            <i class="fas fa-plus mr-1"></i> Input Laporan Pertama
                        </a>
                    </div>
                </div>
            <?php else: ?>

                <?php foreach ($grouped as $tahun => $bulanData): ?>
                    <!-- Header Tahun -->
                    <div class="d-flex align-items-center mb-2 mt-4">
                        <div class="bg-primary rounded px-3 py-1 mr-3">
                            <strong class="text-white"><?= $tahun ?></strong>
                        </div>
                        <hr class="flex-grow-1 m-0" style="border-color: #444;">
                    </div>

                    <?php foreach ($bulanData as $bulan => $laporanList): ?>
                        <?php
                            $namaBulan   = $bulanList[$bulan] ?? $bulan;
                            $laporan     = $laporanList[0] ?? null; // satu laporan per desa per bulan
                            $sudahLapor  = !empty($laporan);
                            $persen      = $sudahLapor ? 100 : 0;
                            $accordionId = 'acc-' . $tahun . '-' . $bulan;
                            $collapseId  = 'col-' . $tahun . '-' . $bulan;
                            $isLatest    = ($tahun == array_key_first($grouped) && $bulan == array_key_first($bulanData));
                        ?>

                        <div class="card shadow-sm mb-2" id="<?= $accordionId ?>">
                            <div class="card-header p-0" style="cursor:pointer;"
                                data-toggle="collapse" data-target="#<?= $collapseId ?>"
                                aria-expanded="<?= $isLatest ? 'true' : 'false' ?>">
                                <div class="d-flex align-items-center px-3 py-2">
                                    <!-- Icon bulan -->
                                    <div class="mr-3">
                                        <span class="badge badge-pill
                                            <?= $persen == 100 ? 'badge-success' : ($persen >= 50 ? 'badge-warning' : 'badge-danger') ?>"
                                            style="font-size:13px; padding: 6px 12px;">
                                            <?= $namaBulan ?>
                                        </span>
                                    </div>

                                    <!-- Info ringkas -->
                                    <div class="flex-grow-1">
                                        <div class="d-flex align-items-center">
                                            <?php if ($sudahLapor): ?>
                                                <small class="text-muted mr-3">
                                                    <i class="fas fa-check-circle text-success mr-1"></i>
                                                    Sudah lapor
                                                </small>
                                            <?php else: ?>
                                                <small class="text-muted">
                                                    <i class="fas fa-times-circle text-danger mr-1"></i>
                                                    Belum lapor
                                                </small>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <!-- Persen & chevron -->
                                    <div class="ml-3 text-right">
                                        <strong class="<?= $persen == 100 ? 'text-success' : 'text-warning' ?>">
                                            <?= $persen ?>%
                                        </strong>
                                        <i class="fas fa-chevron-<?= $isLatest ? 'up' : 'down' ?> ml-2 text-muted"></i>
                                    </div>
                                </div>
                            </div>

                            <div id="<?= $collapseId ?>" class="collapse <?= $isLatest ? 'show' : '' ?>">
                                <div class="card-body p-0">
                                    <table class="table table-sm table-hover m-0">
                                        <thead class="bg-light">
                                            <tr>
                                                <th class="pl-3 text-center">Status</th>
                                                <th class="text-center">Jiwa</th>
                                                <th class="text-center">KK</th>
                                                <th class="text-center">Jiwa L</th>
                                                <th class="text-center">Jiwa P</th>
                                                <th class="text-center">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if ($sudahLapor): ?>
                                                <tr>
                                                    <td class="text-center">
                                                        <span class="badge badge-success"><i class="fas fa-check mr-1"></i>Sudah Lapor</span>
                                                    </td>
                                                    <td class="text-center">
                                                        <span class="badge badge-info">
                                                            <?= number_format($laporan['jiwa_l'] + $laporan['jiwa_p']) ?>
                                                        </span>
                                                    </td>
                                                    <td class="text-center">
                                                        <span class="badge badge-success">
                                                            <?= number_format($laporan['kk_l'] + $laporan['kk_p']) ?>
                                                        </span>
                                                    </td>
                                                    <td class="text-center text-info"><?= number_format($laporan['jiwa_l']) ?></td>
                                                    <td class="text-center text-danger"><?= number_format($laporan['jiwa_p']) ?></td>
                                                    <td class="text-center">
                                                        <a href="/laporan/edit/<?= $laporan['id_laporan'] ?>"
                                                            class="btn btn-xs btn-warning" title="Edit">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <button type="button" class="btn btn-xs btn-danger"
                                                            onclick="confirmDelete(<?= $laporan['id_laporan'] ?>)" title="Hapus">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                            <?php else: ?>
                                                <tr class="table-danger">
                                                    <td class="text-center">
                                                        <span class="badge badge-danger"><i class="fas fa-times mr-1"></i>Belum Lapor</span>
                                                    </td>
                                                    <td colspan="4" class="text-center text-muted">-</td>
                                                    <td class="text-center">
                                                        <a href="/laporan/input" class="btn btn-xs btn-primary" title="Input">
                                                            <i class="fas fa-plus"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                    <?php endforeach; ?>
                <?php endforeach; ?>

            <?php endif; ?>
        </div>
    </section>
</div>

<script>
function confirmDelete(id) {
    if (confirm('Hapus data laporan ini?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '/laporan/delete/' + id;
        const csrf = document.createElement('input');
        csrf.type = 'hidden';
        csrf.name = '<?= csrf_token() ?>';
        csrf.value = '<?= csrf_hash() ?>';
        form.appendChild(csrf);
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

<?= $this->endSection() ?>
