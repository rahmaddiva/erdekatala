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
        <!-- Notifikasi -->
        <li class="nav-item dropdown">
            <a class="nav-link" data-toggle="dropdown" href="#" id="notifBell">
                <i class="far fa-bell"></i>
                <span class="badge badge-warning navbar-badge" id="notifBadge" style="display:none;">0</span>
            </a>
            <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right notif-dropdown" id="notifDropdown"></div>
        </li>
        <!-- Dark/Light Mode Toggle -->
        <li class="nav-item">
            <button id="themeToggle" class="btn nav-link border-0 px-3" title="Ganti tema" style="background:none;">
                <i id="themeIcon" class="fas fa-sun"></i>
            </button>
        </li>
        <!-- user -->
        <li class="nav-item dropdown">
            <a class="nav-link" data-toggle="dropdown" href="#">
                <i class="fas fa-user-circle">
                    <?= esc(session()->get('username')) ?>
                </i>
            </a>
            <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
                <span class="dropdown-item dropdown-header"><?= esc(session()->get('nama_lengkap')); ?></span>
                <div class="dropdown-divider"></div>
                <a href="/profile" class="dropdown-item">
                    <i class="fas fa-user mr-2"></i> Profile
                </a>
                <div class="dropdown-divider"></div>
                <form action="<?= base_url('logout'); ?>" method="post" class="m-0">
                    <?= csrf_field() ?>
                    <button type="button" class="dropdown-item logout-btn">
                        <i class="fas fa-sign-out-alt mr-2"></i> Logout
                    </button>
                </form>
            </div>
        </li>

    </ul>
</nav>
<!-- /.navbar -->