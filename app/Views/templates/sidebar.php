<!-- Main Sidebar Container -->
<aside class="main-sidebar sidebar-dark-primary elevation-4">
    <!-- Brand Logo -->
    <a href="<?= base_url('dashboard') ?>" class="brand-link">
        <img src="<?= base_url('assets/') ?>dist/img/erdekatala.png" alt="Erdekatala Logo" style="width: 160px;"
            class="brand-image" style="opacity: .8">
    </a>

    <!-- Sidebar -->
    <div class="sidebar">
        <?php
        $s1    = service('uri')->getSegment(1);
        $s2    = service('uri')->getSegment(2);
        $scope = role_scope();
        ?>
        <!-- Sidebar user panel (optional) -->
        <div class="user-panel mt-3 pb-3 mb-3 d-flex">
            <div class="image">
                <img src="<?= base_url('assets/') ?>dist/img/avatar5.png" class="img-circle elevation-2"
                    alt="User Image">
            </div>
            <div class="info">
                <a href="#" class="d-block"><?= esc(session()->get('nama_lengkap')); ?></a>
            </div>
        </div>

        <!-- SidebarSearch Form -->
        <div class="form-inline">
            <div class="input-group" data-widget="sidebar-search">
                <input class="form-control form-control-sidebar" type="search" placeholder="Search" aria-label="Search">
                <div class="input-group-append">
                    <button class="btn btn-sidebar">
                        <i class="fas fa-search fa-fw"></i>
                    </button>
                </div>
            </div>
        </div>

        <nav class="mt-2">
            <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">

                <?php if (can('dashboard.view')): ?>
                <li class="nav-item">
                    <a href="<?= base_url('dashboard') ?>" class="nav-link <?= ($s1 == 'dashboard') ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-tachometer-alt"></i>
                        <p>Dashboard</p>
                    </a>
                </li>
                <?php endif; ?>

                <?php $showWilayah = can('kecamatan.manage') || can('desa.manage') || can('dusun.manage') || can('rt.manage'); ?>
                <?php if ($showWilayah): ?>
                    <li class="nav-header">MANAJEMEN WILAYAH</li>
                    <li class="nav-item <?= (in_array($s1, ['kecamatan', 'desa', 'dusun', 'rt']) ? 'menu-open' : '') ?>">
                        <a href="#" class="nav-link <?= (in_array($s1, ['kecamatan', 'desa', 'dusun', 'rt']) ? 'active' : '') ?>">
                            <i class="nav-icon fas fa-map-marked-alt"></i>
                            <p>Master Wilayah <i class="right fas fa-angle-left"></i></p>
                        </a>
                        <ul class="nav nav-treeview">
                            <?php if (can('kecamatan.manage')): ?>
                                <li class="nav-item">
                                    <a href="<?= base_url('kecamatan') ?>" class="nav-link <?= ($s1 == 'kecamatan') ? 'active' : '' ?>">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Data Kecamatan</p>
                                    </a>
                                </li>
                            <?php endif; ?>
                            <?php if (can('desa.manage')): ?>
                                <li class="nav-item">
                                    <a href="<?= base_url('desa') ?>" class="nav-link <?= ($s1 == 'desa') ? 'active' : '' ?>">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Data Desa</p>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </li>
                <?php endif; ?>

                <?php if (can('laporan.view') || can('laporan.input') || can('laporan.aktivitas')): ?>
                <li class="nav-header">DATA AGREGAT</li>
                <?php if (can('laporan.input')): ?>
                <li class="nav-item">
                    <a href="<?= base_url('laporan/input') ?>"
                        class="nav-link <?= ($s1 == 'laporan' && $s2 == 'input') ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-edit"></i>
                        <p>Input Laporan Baru</p>
                    </a>
                </li>
                <?php endif; ?>
                <?php if (can('laporan.view')): ?>
                <li class="nav-item">
                    <a href="<?= base_url('laporan') ?>"
                        class="nav-link <?= ($s1 == 'laporan' && $s2 != 'input' && $s2 != 'aktivitas') ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-file-invoice"></i>
                        <p>Riwayat Laporan</p>
                    </a>
                </li>
                <?php endif; ?>
                <?php if (can('laporan.aktivitas')): ?>
                <li class="nav-item">
                    <a href="<?= base_url('laporan/aktivitas') ?>"
                        class="nav-link <?= ($s1 == 'laporan' && $s2 == 'aktivitas') ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-history"></i>
                        <p>Aktivitas Perubahan</p>
                    </a>
                </li>
                <?php endif; ?>
                <?php endif; ?>

                <?php if (can('data_tabel.view')): ?>
                <li class="nav-header">DATA TABEL</li>
                <li class="nav-item">
                    <a href="<?= base_url('data-tabel') ?>" class="nav-link <?= ($s1 == 'data-tabel') ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-table"></i>
                        <p>Data Tabel</p>
                    </a>
                </li>
                <?php endif; ?>

                <?php if (can('users.view') || (can('roles.manage') && $scope === 'dinas')): ?>
                    <li class="nav-header">PENGATURAN</li>
                    <?php if (can('users.view')): ?>
                    <li class="nav-item">
                        <a href="<?= base_url('users') ?>" class="nav-link <?= ($s1 == 'users') ? 'active' : '' ?>">
                            <i class="nav-icon fas fa-user-cog"></i>
                            <p><?= $scope === 'kecamatan' ? 'Akun Pengguna Kecamatan' : 'Manajemen User' ?></p>
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php if (can('roles.manage') && $scope === 'dinas'): ?>
                    <li class="nav-item">
                        <a href="<?= base_url('roles') ?>" class="nav-link <?= ($s1 == 'roles') ? 'active' : '' ?>">
                            <i class="nav-icon fas fa-user-shield"></i>
                            <p>Manajemen Role</p>
                        </a>
                    </li>
                    <?php endif; ?>
                <?php endif; ?>

                <?php if (can('apikeys.manage')): ?>
                    <li class="nav-header">API PUBLIK</li>
                    <li class="nav-item <?= ($s1 == 'apikeys') ? 'menu-open' : '' ?>">
                        <a href="#" class="nav-link <?= ($s1 == 'apikeys') ? 'active' : '' ?>">
                            <i class="nav-icon fas fa-key"></i>
                            <p>API Key <i class="right fas fa-angle-left"></i></p>
                        </a>
                        <ul class="nav nav-treeview">
                            <li class="nav-item">
                                <a href="<?= base_url('apikeys') ?>" class="nav-link <?= ($s1 == 'apikeys' && $s2 != 'docs') ? 'active' : '' ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Manajemen API Key</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?= base_url('api/guide') ?>" class="nav-link" target="_blank">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Panduan Integrasi <i class="fas fa-external-link-alt fa-xs ml-1"></i></p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?= base_url('api/docs') ?>" class="nav-link" target="_blank">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Dokumentasi API <i class="fas fa-external-link-alt fa-xs ml-1"></i></p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?= base_url('api/register') ?>" class="nav-link" target="_blank">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Form Registrasi Key <i class="fas fa-external-link-alt fa-xs ml-1"></i></p>
                                </a>
                            </li>
                        </ul>
                    </li>
                <?php endif; ?>

                <li class="nav-item">
                    <form action="<?= base_url('logout') ?>" method="post">
                        <?= csrf_field() ?>
                        <button type="submit" class="nav-link text-danger border-0 bg-transparent w-100 text-left">
                            <i class="nav-icon fas fa-sign-out-alt"></i>
                            <p>Logout</p>
                        </button>
                    </form>
                </li>

            </ul>
        </nav>
    </div>
    <!-- /.sidebar -->
</aside>
