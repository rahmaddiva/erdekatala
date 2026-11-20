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
                        <?php
                        $targetScope = 'desa';
                        $targetLabel = $user['role'];
                        foreach ($roles as $r) {
                            if ($r['nama_role'] === $user['role']) {
                                $targetScope = $r['scope'];
                                $targetLabel = $r['label'];
                                break;
                            }
                        }
                        ?>
                        <?php if (isset($is_kecamatan) && $is_kecamatan): ?>
                        <div class="form-group">
                            <label>Role</label>
                            <input type="text" class="form-control" value="<?= esc($targetLabel) ?>" disabled>
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
                            <?php if ($targetScope === 'desa'): ?>
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
                            <?php endif; ?>
                        <?php else: ?>
                        <div class="form-group">
                            <label>Role</label>
                            <select name="role" id="sel_role" class="form-control">
                                <?php foreach ($roles as $r): ?>
                                <option value="<?= esc($r['nama_role']) ?>" data-scope="<?= esc($r['scope']) ?>"
                                    <?= $user['role'] === $r['nama_role'] ? 'selected' : '' ?>>
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
                                <option value="<?= $k['id_kecamatan'] ?>" <?= $k['id_kecamatan'] == $user['id_kecamatan'] ? 'selected' : '' ?>>
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
                                <option value="<?= $d['id_desa'] ?>" <?= $d['id_desa'] == $user['id_desa'] ? 'selected' : '' ?>>
                                    <?= esc($d['nama_desa']) ?>
                                </option>
                                <?php endforeach; ?>
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