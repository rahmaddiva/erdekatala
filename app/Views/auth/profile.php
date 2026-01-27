<?= $this->extend('templates/main') ?>
<?= $this->section('content') ?>
<!-- Main content -->
<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0 font-weight-bold"><?= $title ?>
                    </h1>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-3">
                    <div class="card card-primary card-outline">
                        <div class="card-body box-profile">
                            <div class="text-center">
                                <img class="profile-user-img img-fluid img-circle"
                                    src="<?= base_url('assets/') ?>dist/img/avatar5.png" alt="User profile picture">
                            </div>

                            <h3 class="profile-username text-center"><?= esc($user['nama_lengkap']) ?></h3>

                            <p class="text-muted text-center"><?= esc($user['role'] ?? 'User') ?></p>

                            <ul class="list-group list-group-unbordered mb-3">
                                <li class="list-group-item">
                                    <b>Username</b> <a class="float-right"><?= esc($user['username']) ?></a>
                                </li>
                                <li class="list-group-item">
                                    <b>Member sejak</b> <a
                                        class="float-right"><?= date('d M Y', strtotime($user['created_at'] ?? date('Y-m-d'))) ?></a>
                                </li>
                            </ul>
                        </div>
                        <!-- /.card-body -->
                    </div>
                </div>

                <div class="col-md-9">
                    <div class="card">
                        <div class="card-header p-2">
                            <ul class="nav nav-pills">
                                <li class="nav-item"><a class="nav-link active" href="#settings"
                                        data-toggle="tab">Settings</a></li>
                            </ul>
                        </div><!-- /.card-header -->
                        <div class="card-body">

                            <?php if (session()->getFlashdata('error')): ?>
                                <div class="alert alert-danger alert-sm">
                                    <?= session()->getFlashdata('error') ?>
                                </div>
                            <?php endif; ?>

                            <?php if (session()->getFlashdata('success')): ?>
                                <div class="alert alert-success alert-sm">
                                    <?= session()->getFlashdata('success') ?>
                                </div>
                            <?php endif; ?>

                            <div class="tab-content">
                                <div class="active tab-pane" id="settings">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="card card-primary">
                                                <div class="card-header">
                                                    <h3 class="card-title">Edit Profil</h3>
                                                </div>
                                                <form class="form-horizontal" method="post" action="/profile/update">
                                                    <?= csrf_field() ?>
                                                    <div class="card-body">
                                                        <div class="form-group row">
                                                            <label for="inputNama"
                                                                class="col-sm-3 col-form-label">Nama</label>
                                                            <div class="col-sm-9">
                                                                <input type="text" name="nama_lengkap"
                                                                    class="form-control" id="inputNama"
                                                                    placeholder="Nama Lengkap"
                                                                    value="<?= old('nama_lengkap', esc($user['nama_lengkap'])) ?>">
                                                            </div>
                                                        </div>
                                                        <div class="form-group row">
                                                            <label for="inputUsername"
                                                                class="col-sm-3 col-form-label">Username</label>
                                                            <div class="col-sm-9">
                                                                <input type="text" name="username" class="form-control"
                                                                    id="inputUsername" placeholder="Username"
                                                                    value="<?= old('username', esc($user['username'])) ?>">
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="card-footer">
                                                        <button type="submit" class="btn btn-primary">Simpan
                                                            Perubahan</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="card card-warning">
                                                <div class="card-header">
                                                    <h3 class="card-title">Ganti Password</h3>
                                                </div>
                                                <form class="form-horizontal" method="post" action="/profile/password">
                                                    <?= csrf_field() ?>
                                                    <div class="card-body">
                                                        <div class="form-group row">
                                                            <label for="currentPassword"
                                                                class="col-sm-4 col-form-label">Password Lama</label>
                                                            <div class="col-sm-8">
                                                                <input type="password" name="current_password"
                                                                    class="form-control" id="currentPassword"
                                                                    placeholder="Password Lama">
                                                            </div>
                                                        </div>
                                                        <div class="form-group row">
                                                            <label for="newPassword"
                                                                class="col-sm-4 col-form-label">Password Baru</label>
                                                            <div class="col-sm-8">
                                                                <input type="password" name="new_password"
                                                                    class="form-control" id="newPassword"
                                                                    placeholder="Password Baru (min 6 karakter)">
                                                            </div>
                                                        </div>
                                                        <div class="form-group row">
                                                            <label for="confirmPassword"
                                                                class="col-sm-4 col-form-label">Konfirmasi</label>
                                                            <div class="col-sm-8">
                                                                <input type="password" name="new_password_confirm"
                                                                    class="form-control" id="confirmPassword"
                                                                    placeholder="Ulangi Password Baru">
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="card-footer">
                                                        <button type="submit" class="btn btn-warning">Ubah
                                                            Password</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div><!-- /.card-body -->
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- /.content -->
    <?= $this->endSection() ?>