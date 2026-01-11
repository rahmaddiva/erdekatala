<?= $this->extend('templates/main') ?>
<?= $this->section('content') ?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0 font-weight-bold"><?= $title ?></h1>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-12">

                    <?php if (session()->getFlashdata('success')): ?>
                        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
                            <i class="icon fas fa-check-circle mr-2"></i> <?= session()->getFlashdata('success') ?>
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    <?php endif; ?>

                    <div class="card card-outline card-primary shadow-sm">
                        <div class="card-header border-0">
                            <h3 class="card-title font-weight-bold text-muted">Daftar Dusun</h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-primary btn-sm btn-flat shadow-sm"
                                    data-toggle="modal" data-target="#modalTambahDusun">
                                    <i class="fas fa-plus-circle mr-1"></i> Tambah Dusun
                                </button>
                            </div>
                        </div>

                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table id="example2" class="table table-hover table-striped m-0">
                                    <thead class="thead-light">
                                        <tr>
                                            <th class="text-center" style="width: 50px">No</th>
                                            <th>Nama Dusun</th>
                                            <th class="text-center" style="width: 150px">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($dusun as $index => $d): ?>
                                            <tr>
                                                <td class="text-center align-middle"><?= $index + 1 ?></td>
                                                <td class="align-middle font-weight-bold text-dark"><?= $d['nama_dusun'] ?>
                                                </td>
                                                <td class="text-center align-middle">
                                                    <div class="btn-group shadow-sm">
                                                        <button class="btn btn-warning btn-sm btn-flat" data-toggle="modal"
                                                            data-target="#editDusunModal<?= $d['id_dusun'] ?>"
                                                            title="Edit Data">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <a href="/dusun/delete/<?= $d['id_dusun'] ?>"
                                                            class="btn btn-danger btn-sm btn-flat"
                                                            onclick="return confirm('Apakah Anda yakin ingin menghapus dusun ini?')"
                                                            title="Hapus Data">
                                                            <i class="fas fa-trash"></i>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>

                                            <div class="modal fade" id="editDusunModal<?= $d['id_dusun'] ?>" tabindex="-1"
                                                role="dialog" aria-hidden="true">
                                                <div class="modal-dialog modal-dialog-centered" role="document">
                                                    <div class="modal-content">
                                                        <form action="/dusun/update/<?= $d['id_dusun'] ?>" method="post">
                                                            <?= csrf_field() ?>
                                                            <div class="modal-header bg-warning">
                                                                <h5 class="modal-title font-weight-bold">Update Data Dusun
                                                                </h5>
                                                                <button type="button" class="close" data-dismiss="modal"
                                                                    aria-label="Close">
                                                                    <span aria-hidden="true">&times;</span>
                                                                </button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <div class="form-group">
                                                                    <label class="font-weight-bold">Nama Dusun</label>
                                                                    <input type="text" name="nama_dusun"
                                                                        class="form-control" value="<?= $d['nama_dusun'] ?>"
                                                                        required>
                                                                </div>

                                                            </div>
                                                            <div class="modal-footer justify-content-between border-0">
                                                                <button type="button" class="btn btn-default btn-flat"
                                                                    data-dismiss="modal">Batal</button>
                                                                <button type="submit"
                                                                    class="btn btn-primary btn-flat">Simpan
                                                                    Perubahan</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="modal fade" id="modalTambahDusun" tabindex="-1" role="dialog" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered" role="document">
                            <div class="modal-content">
                                <form action="/dusun/store" method="post">
                                    <?= csrf_field() ?>
                                    <div class="modal-header bg-primary">
                                        <h5 class="modal-title font-weight-bold text-white">Tambah Data Dusun Baru</h5>
                                        <button type="button" class="close text-white" data-dismiss="modal"
                                            aria-label="Close">
                                            <span aria-hidden="true">&times;</span>
                                        </button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="form-group">
                                            <label class="font-weight-bold">Nama Dusun</label>
                                            <input type="text" name="nama_dusun" class="form-control"
                                                placeholder="Masukkan Nama Dusun" required>
                                        </div>

                                    </div>
                                    <div class="modal-footer justify-content-between border-0">
                                        <button type="button" class="btn btn-default btn-flat"
                                            data-dismiss="modal">Batal</button>
                                        <button type="submit" class="btn btn-primary btn-flat">Simpan Data</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </section>
</div>



<?= $this->endSection() ?>