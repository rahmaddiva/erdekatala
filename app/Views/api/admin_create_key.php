<?= $this->extend('templates/main') ?>
<?= $this->section('content') ?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0 font-weight-bold"><?= esc($title) ?></h1>
                </div>
                <div class="col-sm-6 text-right">
                    <a href="<?= base_url('apikeys') ?>" class="btn btn-secondary">
                        <i class="fas fa-arrow-left mr-1"></i> Kembali
                    </a>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            <div class="row justify-content-center">
                <div class="col-md-7">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-key mr-2"></i>Form Buat API Key</h3>
                        </div>
                        <div class="card-body">

                            <?php if (!empty($errors)): ?>
                                <div class="alert alert-danger alert-dismissible">
                                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                                    <ul class="mb-0 pl-3">
                                        <?php foreach ($errors as $e): ?>
                                            <li><?= esc($e) ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>

                            <form method="POST" action="<?= base_url('apikeys/store') ?>">
                                <?= csrf_field() ?>

                                <div class="form-group">
                                    <label>Nama Pemilik</label>
                                    <input type="text" name="owner_name" class="form-control"
                                           placeholder="Nama lengkap pemilik key"
                                           value="<?= esc($old['owner_name'] ?? '') ?>">
                                </div>

                                <div class="form-group">
                                    <label>Nama / Label <span class="text-danger">*</span></label>
                                    <input type="text" name="label" class="form-control"
                                           placeholder="Misal: Website Desa Kurau"
                                           value="<?= esc($old['label'] ?? '') ?>" required>
                                    <small class="text-muted">Nama pengingat untuk key ini</small>
                                </div>

                                <div class="form-group">
                                    <label>Email Pemilik <span class="text-danger">*</span></label>
                                    <input type="email" name="owner_email" class="form-control"
                                           placeholder="email@contoh.com"
                                           value="<?= esc($old['owner_email'] ?? '') ?>" required>
                                </div>

                                <div class="form-group">
                                    <label>Limit Request per Hari <span class="text-danger">*</span></label>
                                    <input type="number" name="rate_limit" class="form-control"
                                           min="1" max="100000"
                                           value="<?= esc($old['rate_limit'] ?? '1000') ?>" required>
                                    <small class="text-muted">Default: 1000 request/hari</small>
                                </div>

                                <div class="form-group mb-0">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-key mr-1"></i> Buat API Key
                                    </button>
                                    <a href="<?= base_url('apikeys') ?>" class="btn btn-secondary ml-2">Batal</a>
                                </div>
                            </form>

                        </div>
                    </div>

                    <div class="callout callout-info">
                        <h5><i class="fas fa-info-circle mr-1"></i> Informasi</h5>
                        <p class="mb-0">API key yang dibuat akan ditampilkan di halaman daftar. Key hanya bisa dilihat nilainya melalui flash message setelah dibuat. Pastikan menyalin dan menyerahkan key ke pemiliknya segera.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<?= $this->endSection() ?>
