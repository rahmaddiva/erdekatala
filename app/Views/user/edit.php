<?= $this->extend('templates/main') ?>
<?= $this->section('content') ?>

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
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title">Form Edit Pengguna</h3>
                </div>
                <form method="post" action="/users/update/<?= $user['id_user'] ?>">
                    <?= csrf_field() ?>
                    <div class="card-body">
                        <?php if (isset($is_kecamatan) && $is_kecamatan): ?>
                        <div class="form-group">
                            <label>Role</label>
                            <input type="text" class="form-control" value="Admin Desa" disabled>
                        </div>
                        <?php endif; ?>
                        <div class="form-group">
                            <label>Nama Lengkap</label>
                            <input type="text" name="nama_lengkap" class="form-control"
                                value="<?= old('nama_lengkap', esc($user['nama_lengkap'])) ?>">
                        </div>
                        <div class="form-group">
                            <label>Username</label>
                            <input type="text" name="username" class="form-control"
                                value="<?= old('username', esc($user['username'])) ?>">
                        </div>
                        <div class="form-group">
                            <label>Ganti Password (kosongkan jika tidak ingin mengubah)</label>
                            <input type="password" name="password" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Konfirmasi Password</label>
                            <input type="password" name="password_confirm" class="form-control">
                        </div>
                        <?php if (isset($is_kecamatan) && $is_kecamatan): ?>
                        <div class="form-group">
                            <label>Desa</label>
                            <select name="id_desa" class="form-control">
                                <?php foreach ($desa_list as $d): ?>
                                <option value="<?= $d['id_desa'] ?>" <?= ($d['id_desa'] == $user['id_desa']) ? 'selected' : '' ?>>
                                    <?= esc($d['nama_desa']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php else: ?>
                        <div class="form-group">
                            <label>Role</label>
                            <select name="role" class="form-control">
                                <option value="admin_desa" <?= $user['role'] == 'admin_desa' ? 'selected' : '' ?>>Admin Desa</option>
                                <option value="admin_kecamatan" <?= $user['role'] == 'admin_kecamatan' ? 'selected' : '' ?>>Admin Kecamatan</option>
                                <option value="admin_dinas" <?= $user['role'] == 'admin_dinas' ? 'selected' : '' ?>>Admin Dinas</option>
                            </select>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="card-footer">
                        <a href="/users" class="btn btn-secondary">Batal</a>
                        <button type="submit" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </section>
</div>

<?= $this->endSection() ?>