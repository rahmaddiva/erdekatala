<!-- Main Sidebar Container -->
<aside class="main-sidebar sidebar-dark-primary elevation-4">
    <!-- Brand Logo -->
    <a href="index3.html" class="brand-link">
        <img src="<?= base_url('assets/') ?>dist/img/erdekatala.png" alt="AdminLTE Logo" style="width: 160px;"
            class="brand-image" style="opacity: .8">
    </a>

    <!-- Sidebar -->
    <div class="sidebar">
        <!-- Sidebar user panel (optional) -->
        <div class="user-panel mt-3 pb-3 mb-3 d-flex">
            <div class="image">
                <img src="<?= base_url('assets/') ?>dist/img/user2-160x160.jpg" class="img-circle elevation-2"
                    alt="User Image">
            </div>
            <div class="info">
                <a href="#" class="d-block"><?= session()->get('nama_lengkap'); ?></a>
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

                <li class="nav-item">
                    <a href="<?= base_url('dashboard') ?>" class="nav-link active">
                        <i class="nav-icon fas fa-tachometer-alt"></i>
                        <p>Dashboard</p>
                    </a>
                </li>

                <li class="nav-header">MANAJEMEN WILAYAH</li>
                <li class="nav-item">
                    <a href="#" class="nav-link">
                        <i class="nav-icon fas fa-map-marked-alt"></i>
                        <p>Master Wilayah <i class="right fas fa-angle-left"></i></p>
                    </a>
                    <ul class="nav nav-treeview">
                        <?php if (session()->get('role') == 'admin_dinas'): ?>
                            <li class="nav-item">
                                <a href="<?= base_url('desa') ?>" class="nav-link">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Data Desa</p>
                                </a>
                            </li>
                        <?php endif; ?> 
                        <li class="nav-item">
                            <a href="<?= base_url('dusun') ?>" class="nav-link">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Data Dusun</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?= base_url('rt') ?>" class="nav-link">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Data RT</p>
                            </a>
                        </li>
                    </ul>
                </li>

                <li class="nav-header">DATA AGREGAT</li>
                <li class="nav-item">
                    <a href="<?= base_url('laporan/input') ?>" class="nav-link">
                        <i class="nav-icon fas fa-edit"></i>
                        <p>Input Laporan Baru</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?= base_url('laporan') ?>" class="nav-link">
                        <i class="nav-icon fas fa-file-invoice"></i>
                        <p>Riwayat Laporan</p>
                    </a>
                </li>

                <li class="nav-header">PENGATURAN</li>
                <li class="nav-item">
                    <a href="<?= base_url('users') ?>" class="nav-link">
                        <i class="nav-icon fas fa-user-cog"></i>
                        <p>Manajemen User</p>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="<?= base_url('auth/logout') ?>" class="nav-link text-danger">
                        <i class="nav-icon fas fa-sign-out-alt"></i>
                        <p>Logout</p>
                    </a>
                </li>

            </ul>
        </nav>
    </div>
    <!-- /.sidebar -->
</aside>