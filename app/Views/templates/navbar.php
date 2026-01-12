<!-- Navbar -->
<nav class="main-header navbar navbar-expand navbar-dark">
    <!-- Left navbar links -->
    <ul class="navbar-nav">
        <li class="nav-item">
            <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
        </li>
        <li class="nav-item d-none d-sm-inline-block">
            <a href="index3.html" class="nav-link">Home</a>
        </li>
    </ul>

    <!-- Right navbar links -->
    <ul class="navbar-nav ml-auto">
        <!-- user -->
        <li class="nav-item dropdown">
            <a class="nav-link" data-toggle="dropdown" href="#">
                <i class="fas fa-user-circle">
                    <?= session()->get('username') ?>
                </i>
            </a>
            <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
                <span class="dropdown-item dropdown-header"><?= session()->get('nama_lengkap'); ?></span>
                <div class="dropdown-divider"></div>
                <a href="#" class="dropdown-item">
                    <i class="fas fa-user mr-2"></i> Profile
                </a>
                <div class="dropdown-divider"></div>
                <a href="#" class="dropdown-item logout-btn" data-logout-url="<?= base_url('logout'); ?>">
                    <i class="fas fa-sign-out-alt mr-2"></i> Logout
                </a>
            </div>
        </li>

    </ul>
</nav>
<!-- /.navbar -->