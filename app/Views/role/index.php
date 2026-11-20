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
                    <a href="<?= base_url('roles/create') ?>" class="btn btn-primary">
                        <i class="fas fa-plus mr-1"></i> Tambah Role
                    </a>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            <div class="card card-outline card-primary shadow-sm">
                <div class="card-body table-responsive p-0">
                    <table class="table table-hover table-striped mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th>Role</th>
                                <th>Label</th>
                                <th>Scope Data</th>
                                <th class="text-center">Pengguna</th>
                                <th class="text-center">Permission</th>
                                <th class="text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($roles as $r): ?>
                                <?php $perms = json_decode($r['permissions'] ?? '[]', true) ?: []; ?>
                                <tr>
                                    <td>
                                        <code><?= esc($r['nama_role']) ?></code>
                                        <?php if ((bool) $r['is_system']): ?>
                                            <span class="badge badge-secondary ml-1">sistem</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= esc($r['label']) ?></td>
                                    <td><span class="badge badge-info"><?= esc($r['scope']) ?></span></td>
                                    <td class="text-center"><?= (int) ($userCounts[$r['nama_role']] ?? 0) ?></td>
                                    <td class="text-center"><?= count($perms) ?> / <?= count(\App\Libraries\Permissions::all()) ?></td>
                                    <td class="text-right">
                                        <a href="<?= base_url('roles/edit/' . rawurlencode($r['nama_role'])) ?>"
                                            class="btn btn-sm btn-warning" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <?php if (!(bool) $r['is_system'] && $r['nama_role'] !== session()->get('role')): ?>
                                            <form action="<?= base_url('roles/delete/' . rawurlencode($r['nama_role'])) ?>"
                                                method="post" class="d-inline form-hapus-role">
                                                <?= csrf_field() ?>
                                                <button type="submit" class="btn btn-sm btn-danger" title="Hapus">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    $(function () {
        $('.form-hapus-role').on('submit', function (e) {
            e.preventDefault();
            var form = this;
            Swal.fire({
                title: 'Hapus Role?',
                text: 'Role akan dihapus permanen. Role yang masih dipakai tidak bisa dihapus.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya, hapus',
                cancelButtonText: 'Batal'
            }).then(function (result) {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        });
    });
</script>
<?= $this->endSection() ?>
