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
                    <a href="<?= base_url('kecamatan/create') ?>" class="btn btn-primary btn-sm shadow-sm">
                        <i class="fas fa-plus-circle mr-1"></i> Tambah Kecamatan
                    </a>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-12">

                    <?php if (session()->getFlashdata('success')): ?>
                        <div class="alert alert-success alert-dismissible">
                            <button type="button" class="close" data-dismiss="alert">&times;</button>
                            <i class="fas fa-check-circle mr-1"></i> <?= session()->getFlashdata('success') ?>
                        </div>
                    <?php endif; ?>
                    <?php if (session()->getFlashdata('error')): ?>
                        <div class="alert alert-danger alert-dismissible">
                            <button type="button" class="close" data-dismiss="alert">&times;</button>
                            <i class="fas fa-exclamation-circle mr-1"></i> <?= session()->getFlashdata('error') ?>
                        </div>
                    <?php endif; ?>

                    <div class="card card-outline card-primary shadow-sm">
                        <div class="card-header border-0">
                            <h3 class="card-title font-weight-bold text-muted">Daftar Kecamatan</h3>
                            <div class="card-tools">
                                <span class="badge badge-info"><?= count($kecamatan) ?> kecamatan</span>
                            </div>
                        </div>

                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover table-striped m-0">
                                    <thead class="thead-light">
                                        <tr>
                                            <th class="text-center" style="width: 50px">No</th>
                                            <th>Nama Kecamatan</th>
                                            <th>Kode</th>
                                            <th>Slug URL</th>
                                            <th class="text-center">Halaman Publik</th>
                                            <th class="text-center" style="width: 180px">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($kecamatan as $index => $k):
                                            $slug = $k['slug'] ?: strtolower(str_replace(' ', '-', $k['nama_kecamatan']));
                                        ?>
                                            <tr>
                                                <td class="text-center align-middle"><?= $index + 1 ?></td>
                                                <td class="align-middle font-weight-bold text-dark">
                                                    <?= esc($k['nama_kecamatan']) ?>
                                                    <?php if (!empty($k['nama_camat'])): ?>
                                                        <br><small class="text-muted"><i class="fas fa-user-tie mr-1"></i><?= esc($k['nama_camat']) ?></small>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="align-middle"><?= esc($k['kode_kecamatan'] ?: '-') ?></td>
                                                <td class="align-middle">
                                                    <code><?= esc($slug) ?></code>
                                                </td>
                                                <td class="text-center align-middle">
                                                    <?php if ($k['is_public']): ?>
                                                        <span class="badge badge-success"><i class="fas fa-eye mr-1"></i> Publik</span>
                                                    <?php else: ?>
                                                        <span class="badge badge-secondary"><i class="fas fa-eye-slash mr-1"></i> Sembunyi</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-center align-middle">
                                                    <a href="<?= base_url('kecamatan/edit/' . $k['id_kecamatan']) ?>"
                                                        class="btn btn-warning btn-sm btn-flat" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="<?= base_url($slug) ?>" target="_blank"
                                                        class="btn btn-info btn-sm btn-flat" title="Lihat Halaman Publik">
                                                        <i class="fas fa-external-link-alt"></i>
                                                    </a>
                                                    <form action="<?= base_url('kecamatan/delete/' . $k['id_kecamatan']) ?>"
                                                        method="post" class="d-inline" onsubmit="return confirm('Hapus kecamatan <?= esc($k['nama_kecamatan']) ?>? Semua data terkait (desa, laporan) juga akan terhapus.')">
                                                        <button type="submit" class="btn btn-danger btn-sm btn-flat" title="Hapus">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </section>
</div>

<?= $this->endSection() ?>
