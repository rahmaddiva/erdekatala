<?= $this->extend('templates/main') ?>
<?= $this->section('content') ?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0 font-weight-bold"><?= $title ?></h1>
                </div>
                <div class="col-sm-6 text-right">
                    <a href="<?= base_url('apikeys/create') ?>" class="btn btn-primary">
                        <i class="fas fa-plus mr-1"></i> Buat API Key Baru
                    </a>
                    <a href="<?= base_url('api/docs') ?>" target="_blank" class="btn btn-info ml-1">
                        <i class="fas fa-book mr-1"></i> Dokumentasi API
                    </a>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">

            <?php if (session()->getFlashdata('success')): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="fas fa-check-circle mr-2"></i><?= session()->getFlashdata('success') ?>
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                </div>
            <?php endif; ?>

            <!-- Stat Cards -->
            <div class="row mb-3">
                            <?php
                $total    = count($apikeys);
                $active   = count(array_filter($apikeys, fn($k) => $k['is_active'] == 1));
                $revoked  = $total - $active;
                $totalReq = 0; // kolom tidak ada di tabel
                ?>
                <div class="col-sm-3">
                    <div class="info-box">
                        <span class="info-box-icon bg-info"><i class="fas fa-key"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Total Key</span>
                            <span class="info-box-number"><?= $total ?></span>
                        </div>
                    </div>
                </div>
                <div class="col-sm-3">
                    <div class="info-box">
                        <span class="info-box-icon bg-success"><i class="fas fa-check-circle"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Aktif</span>
                            <span class="info-box-number"><?= $active ?></span>
                        </div>
                    </div>
                </div>
                <div class="col-sm-3">
                    <div class="info-box">
                        <span class="info-box-icon bg-secondary"><i class="fas fa-ban"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Revoked</span>
                            <span class="info-box-number"><?= $revoked ?></span>
                        </div>
                    </div>
                </div>
                <div class="col-sm-3">
                    <div class="info-box">
                        <span class="info-box-icon bg-warning"><i class="fas fa-chart-line"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Total Requests</span>
                            <span class="info-box-number"><?= number_format($totalReq) ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabel -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-list mr-2"></i>Daftar API Key Terdaftar</h3>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th width="40">No</th>
                                    <th>Nama / Label</th>
                                    <th>Email</th>
                                    <th class="text-center">Rate Limit/Hari</th>
                                    <th>Terakhir Digunakan</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-center">Dibuat</th>
                                    <th class="text-center" width="130">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($apikeys)): ?>
                                    <tr>
                                        <td colspan="8" class="text-center py-5 text-muted">
                                            <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                                            Belum ada API key terdaftar.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php $no = 1; foreach ($apikeys as $k): ?>
                                    <tr>
                                        <td class="text-center"><?= $no++ ?></td>
                                        <td><strong><?= esc($k['name']) ?></strong></td>
                                        <td><small><?= esc($k['email']) ?></small></td>
                                        <td class="text-center"><?= number_format($k['rate_limit']) ?></td>
                                        <td>
                                            <small class="text-muted">
                                                <?= $k['last_used_at'] ? date('d M Y H:i', strtotime($k['last_used_at'])) : '-' ?>
                                            </small>
                                        </td>
                                        <td class="text-center">
                                            <?php if ($k['is_active'] == 1): ?>
                                                <span class="badge badge-success"><i class="fas fa-check mr-1"></i>Aktif</span>
                                            <?php else: ?>
                                                <span class="badge badge-secondary"><i class="fas fa-ban mr-1"></i>Nonaktif</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <small><?= $k['created_at'] ? date('d M Y', strtotime($k['created_at'])) : '-' ?></small>
                                        </td>
                                        <td class="text-center">
                                            <?php if ($k['is_active'] == 1): ?>
                                                <a href="<?= base_url('apikeys/revoke/' . $k['id']) ?>"
                                                   class="btn btn-warning btn-xs"
                                                   onclick="return confirm('Nonaktifkan key ini?')">
                                                    <i class="fas fa-ban"></i>
                                                </a>
                                            <?php else: ?>
                                                <a href="<?= base_url('apikeys/activate/' . $k['id']) ?>"
                                                   class="btn btn-success btn-xs">
                                                    <i class="fas fa-check"></i>
                                                </a>
                                            <?php endif; ?>
                                            <a href="<?= base_url('apikeys/delete/' . $k['id']) ?>"
                                               class="btn btn-danger btn-xs ml-1"
                                               onclick="return confirm('Hapus permanen key ini?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </section>
</div>

<?= $this->endSection() ?>
