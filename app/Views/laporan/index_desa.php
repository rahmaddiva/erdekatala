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
                            <i class="fas fa-home mr-1"></i>
                            Total RT terdaftar: <strong><?= $totalRt ?> RT</strong>
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
                            $namaBulan  = $bulanList[$bulan] ?? $bulan;
                            $rtSudah    = array_column($laporanList, 'id_rt');
                            $jmlSudah   = count($laporanList);
                            $jmlBelum   = $totalRt - $jmlSudah;
                            $persen     = $totalRt > 0 ? round($jmlSudah / $totalRt * 100) : 0;
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
                                            <small class="text-muted mr-3">
                                                <i class="fas fa-check-circle text-success mr-1"></i>
                                                <?= $jmlSudah ?> RT sudah lapor
                                            </small>
                                            <?php if ($jmlBelum > 0): ?>
                                                <small class="text-muted">
                                                    <i class="fas fa-times-circle text-danger mr-1"></i>
                                                    <?= $jmlBelum ?> RT belum lapor
                                                </small>
                                            <?php endif; ?>
                                        </div>
                                        <!-- Progress bar -->
                                        <div class="progress mt-1" style="height:5px; max-width:250px;">
                                            <div class="progress-bar
                                                <?= $persen == 100 ? 'bg-success' : ($persen >= 50 ? 'bg-warning' : 'bg-danger') ?>"
                                                style="width:<?= $persen ?>%"></div>
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
                                                <th class="pl-3">Dusun</th>
                                                <th>RT</th>
                                                <th class="text-center">Jiwa</th>
                                                <th class="text-center">KK</th>
                                                <th class="text-center">Jiwa L</th>
                                                <th class="text-center">Jiwa P</th>
                                                <th class="text-center">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            // Kelompokkan per dusun
                                            $perDusun = [];
                                            foreach ($laporanList as $l) {
                                                $perDusun[$l['nama_dusun']][] = $l;
                                            }
                                            ksort($perDusun);
                                            ?>
                                            <?php foreach ($perDusun as $namaDusun => $rows): ?>
                                                <?php foreach ($rows as $idx => $row): ?>
                                                    <tr>
                                                        <?php if ($idx === 0): ?>
                                                            <td rowspan="<?= count($rows) ?>" class="pl-3 align-middle">
                                                                <i class="fas fa-map-marker-alt text-muted mr-1"></i>
                                                                <strong><?= esc($namaDusun) ?></strong>
                                                            </td>
                                                        <?php endif; ?>
                                                        <td>
                                                            <span class="badge badge-secondary">RT <?= esc($row['no_rt']) ?></span>
                                                        </td>
                                                        <td class="text-center">
                                                            <span class="badge badge-info">
                                                                <?= number_format($row['jiwa_l'] + $row['jiwa_p']) ?>
                                                            </span>
                                                        </td>
                                                        <td class="text-center">
                                                            <span class="badge badge-success">
                                                                <?= number_format($row['kk_l'] + $row['kk_p']) ?>
                                                            </span>
                                                        </td>
                                                        <td class="text-center text-info"><?= number_format($row['jiwa_l']) ?></td>
                                                        <td class="text-center text-danger"><?= number_format($row['jiwa_p']) ?></td>
                                                        <td class="text-center">
                                                            <a href="/laporan/edit/<?= $row['id_laporan'] ?>"
                                                                class="btn btn-xs btn-warning" title="Edit">
                                                                <i class="fas fa-edit"></i>
                                                            </a>
                                                            <button type="button" class="btn btn-xs btn-danger"
                                                                onclick="confirmDelete(<?= $row['id_laporan'] ?>)" title="Hapus">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php endforeach; ?>

                                            <!-- RT yang belum lapor -->
                                            <?php
                                            $rtBelum = array_filter($allRt, fn($rt) => !in_array($rt['id_rt'], $rtSudah));
                                            foreach ($rtBelum as $rt):
                                            ?>
                                                <tr class="table-danger">
                                                    <td class="pl-3 text-muted">
                                                        <i class="fas fa-map-marker-alt mr-1"></i>
                                                        <?= esc($rt['nama_dusun']) ?>
                                                    </td>
                                                    <td>
                                                        <span class="badge badge-secondary">RT <?= esc($rt['no_rt']) ?></span>
                                                    </td>
                                                    <td colspan="4" class="text-center text-danger">
                                                        <i class="fas fa-exclamation-circle mr-1"></i>
                                                        <small>Belum lapor</small>
                                                    </td>
                                                    <td class="text-center">
                                                        <a href="/laporan/input" class="btn btn-xs btn-outline-primary" title="Input sekarang">
                                                            <i class="fas fa-plus"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                        <?php
                                        $totalJiwa = array_sum(array_map(fn($l) => $l['jiwa_l'] + $l['jiwa_p'], $laporanList));
                                        $totalKK   = array_sum(array_map(fn($l) => $l['kk_l'] + $l['kk_p'], $laporanList));
                                        ?>
                                        <tfoot class="bg-light">
                                            <tr>
                                                <td colspan="2" class="pl-3"><strong>Total Bulan Ini</strong></td>
                                                <td class="text-center"><strong><?= number_format($totalJiwa) ?></strong></td>
                                                <td class="text-center"><strong><?= number_format($totalKK) ?></strong></td>
                                                <td colspan="3"></td>
                                            </tr>
                                        </tfoot>
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
