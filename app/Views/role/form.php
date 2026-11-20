<?= $this->extend('templates/main') ?>
<?= $this->section('content') ?>

<?php $isEdit = $role !== null;
$errors = session('errors') ?? []; ?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0 font-weight-bold"><?= esc($title) ?></h1>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <?php foreach ($errors as $err): ?>
                        <div><?= esc($err) ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form method="post"
                action="<?= $isEdit ? base_url('roles/update/' . rawurlencode($role['nama_role'])) : base_url('roles/store') ?>">
                <?= csrf_field() ?>

                <div class="card card-outline card-primary shadow-sm">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Nama Role</label>
                                    <input type="text" name="nama_role" class="form-control"
                                        value="<?= esc(old('nama_role', $role['nama_role'] ?? '')) ?>"
                                        <?= $isEdit ? 'disabled' : 'required' ?>>
                                    <?php if ($isEdit): ?>
                                        <small class="text-muted">Nama role tidak bisa diubah.</small>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Label</label>
                                    <input type="text" name="label" class="form-control"
                                        value="<?= esc(old('label', $role['label'] ?? '')) ?>" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Scope Data</label>
                                    <select name="scope" class="form-control" <?= $isEdit && (bool) $role['is_system'] ? 'disabled' : '' ?>>
                                        <?php $scopeVal = old('scope', $role['scope'] ?? 'desa'); ?>
                                        <option value="dinas" <?= $scopeVal === 'dinas' ? 'selected' : '' ?>>Dinas — semua wilayah</option>
                                        <option value="kecamatan" <?= $scopeVal === 'kecamatan' ? 'selected' : '' ?>>Kecamatan — wilayah sendiri</option>
                                        <option value="desa" <?= $scopeVal === 'desa' ? 'selected' : '' ?>>Desa — desa sendiri</option>
                                    </select>
                                    <?php if ($isEdit && (bool) $role['is_system']): ?>
                                        <small class="text-muted">Scope role sistem terkunci.</small>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card card-outline card-success shadow-sm">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-check-square mr-2"></i>Permission per Fitur</h3>
                    </div>
                    <div class="card-body">
                        <?php foreach ($groups as $group => $items): ?>
                            <h6 class="text-uppercase text-muted font-weight-bold mt-3"><?= esc($group) ?></h6>
                            <div class="row">
                                <?php foreach ($items as $key => $label): ?>
                                    <div class="col-md-4">
                                        <div class="form-check mb-2">
                                            <input type="checkbox" class="form-check-input"
                                                id="perm_<?= esc($key) ?>" name="permissions[]"
                                                value="<?= esc($key) ?>"
                                                <?= in_array($key, $checked, true) ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="perm_<?= esc($key) ?>">
                                                <?= esc($label) ?>
                                                <small class="text-muted d-block"><code><?= esc($key) ?></code></small>
                                            </label>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="card-footer">
                        <a href="<?= base_url('roles') ?>" class="btn btn-secondary">Batal</a>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save mr-1"></i> Simpan
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </section>
</div>

<?= $this->endSection() ?>
