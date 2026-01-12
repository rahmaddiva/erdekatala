<?= $this->extend('templates/main') ?>
<?= $this->section('content') ?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0 font-weight-bold">
                        <?= $title ?>
                    </h1>
                </div>
                <div class="col-sm-6 text-right">
                    <a href="/users/create" class="btn btn-success">Tambah Pengguna</a>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            <div class="card">
                <div class="card-body">
                    <table class="table table-bordered" id="example2">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Username</th>
                                <th>Nama Lengkap</th>
                                <th>Role</th>
                                <th>Dibuat</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $i => $u): ?>
                                <tr>
                                    <td>
                                        <?= $i + 1 ?>
                                    </td>
                                    <td>
                                        <?= esc($u['username']) ?>
                                    </td>
                                    <td>
                                        <?= esc($u['nama_lengkap']) ?>
                                    </td>
                                    <td>
                                        <?= esc($u['role']) ?>
                                    </td>
                                    <td>
                                        <?= esc($u['created_at']) ?>
                                    </td>
                                    <td>
                                        <a href="/users/edit/<?= $u['id_user'] ?>" class="btn btn-sm btn-primary">Edit</a>
                                        <a href="#" data-url="/users/delete/<?= $u['id_user'] ?>"
                                            class="btn btn-sm btn-danger btn-delete-user">Hapus</a>
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

<script>
    $(function () {
        $('#usersTable').DataTable();

        $('.btn-delete-user').on('click', function (e) {
            e.preventDefault();
            var url = $(this).data('url');
            Swal.fire({
                title: 'Hapus Pengguna?',
                text: 'Data pengguna akan dihapus permanen.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya, hapus',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = url;
                }
            });
        });
    });
</script>

<?= $this->endSection() ?>