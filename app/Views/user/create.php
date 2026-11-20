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
                    <h3 class="card-title">Form Tambah Pengguna</h3>
                </div>
                <form method="post" action="/users/store">
                    <?= csrf_field() ?>
                    <div class="card-body">
                        <div class="form-group">
                            <label>Nama Lengkap</label>
                            <input type="text" name="nama_lengkap" class="form-control"
                                value="<?= old('nama_lengkap') ?>">
                        </div>
                        <div class="form-group">
                            <label>Username</label>
                            <input type="text" name="username" class="form-control" value="<?= old('username') ?>">
                        </div>
                        <div class="form-group">
                            <label>Password</label>
                            <input type="password" name="password" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Konfirmasi Password</label>
                            <input type="password" name="password_confirm" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Role</label>
                            <select name="role" id="sel_role" class="form-control">
                                <?php foreach ($roles as $r): ?>
                                <option value="<?= esc($r['nama_role']) ?>" data-scope="<?= esc($r['scope']) ?>"
                                    <?= old('role', 'admin_desa') === $r['nama_role'] ? 'selected' : '' ?>>
                                    <?= esc($r['label']) ?> — <?= esc($r['scope']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group scope-kecamatan">
                            <label>Kecamatan</label>
                            <select name="id_kecamatan" class="form-control">
                                <option value="">-- Pilih Kecamatan --</option>
                                <?php foreach ($kecamatan_list as $k): ?>
                                <option value="<?= $k['id_kecamatan'] ?>" <?= old('id_kecamatan') == $k['id_kecamatan'] ? 'selected' : '' ?>>
                                    <?= esc($k['nama_kecamatan']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group scope-desa">
                            <label>Desa</label>
                            <select name="id_desa" class="form-control">
                                <option value="">-- Pilih Desa --</option>
                                <?php foreach ($desa_list as $d): ?>
                                <option value="<?= $d['id_desa'] ?>" <?= old('id_desa') == $d['id_desa'] ? 'selected' : '' ?>>
                                    <?= esc($d['nama_desa']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="card-footer">
                        <a href="/users" class="btn btn-secondary">Batal</a>
                        <button type="submit" class="btn btn-success">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </section>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    $(function () {
        function syncScope() {
            var scope = $('#sel_role option:selected').data('scope');
            $('.scope-kecamatan').toggle(scope === 'kecamatan');
            $('.scope-desa').toggle(scope === 'desa');
        }
        $('#sel_role').on('change', syncScope);
        syncScope();
    });
</script>
<?= $this->endSection() ?>