<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ERDEKATALA |
        <?= $title ?>
    </title>

    <link rel="icon" type="image/png" href="<?= base_url('assets/') ?>dist/img/erdekataladark.png">

    <!-- Google Font: Source Sans Pro -->
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="<?= base_url('assets/') ?>plugins/fontawesome-free/css/all.min.css">
    <!-- overlayScrollbars -->
    <link rel="stylesheet" href="<?= base_url('assets/') ?>plugins/overlayScrollbars/css/OverlayScrollbars.min.css">
    <!-- Theme style -->
    <link rel="stylesheet" href="<?= base_url('assets/') ?>dist/css/adminlte.min.css">
    <!-- DataTables -->
    <link rel="stylesheet" href="<?= base_url('assets/') ?>plugins/datatables-bs4/css/dataTables.bootstrap4.min.css">
    <!-- Select2 -->
    <link rel="stylesheet" href="<?= base_url('assets/') ?>plugins/select2/css/select2.min.css">
    <link rel="stylesheet" href="<?= base_url('assets/') ?>plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css">
    <link rel="stylesheet" href="<?= base_url('assets/') ?>plugins/sweetalert2-theme-bootstrap-4/bootstrap-4.min.css">
    <style>
        /* ============================================================
           LIGHT MODE OVERRIDES
           Default AdminLTE dark-mode diaktifkan via JS berdasarkan
           preferensi yang disimpan di localStorage.
        ============================================================ */
        body.dark-mode {
            --lte-bg:        #1e2a38;
            --lte-card-bg:   #243040;
            --lte-text:      #e2e8f0;
            --lte-border:    #2d3748;
            --lte-muted:     #94a3b8;
        }

        body:not(.dark-mode) {
            --lte-bg:        #f4f6f9;
            --lte-card-bg:   #ffffff;
            --lte-text:      #212529;
            --lte-border:    #dee2e6;
            --lte-muted:     #6c757d;
        }

        /* Wrapper & content area */
        body:not(.dark-mode) .content-wrapper,
        body:not(.dark-mode) .main-footer {
            background-color: #f4f6f9;
            color: #212529;
        }

        /* Cards */
        body:not(.dark-mode) .card {
            background-color: #ffffff;
            border-color: #dee2e6;
            color: #212529;
        }
        body:not(.dark-mode) .card-header {
            background-color: #f8f9fa;
            border-bottom-color: #dee2e6;
            color: #212529;
        }
        body:not(.dark-mode) .card-body {
            color: #212529;
        }
        body:not(.dark-mode) .card-footer {
            background-color: #f8f9fa;
            border-top-color: #dee2e6;
        }

        /* Info boxes */
        body:not(.dark-mode) .info-box {
            background-color: #ffffff;
            color: #212529;
            box-shadow: 0 1px 3px rgba(0,0,0,.12);
        }
        body:not(.dark-mode) .info-box-text,
        body:not(.dark-mode) .info-box-number {
            color: #212529;
        }

        /* Tables */
        body:not(.dark-mode) .table {
            color: #212529;
        }
        body:not(.dark-mode) .table thead th {
            background-color: #f8f9fa;
            border-color: #dee2e6;
            color: #212529;
        }
        body:not(.dark-mode) .table-striped tbody tr:nth-of-type(odd) {
            background-color: rgba(0,0,0,.03);
        }
        body:not(.dark-mode) .table-hover tbody tr:hover {
            background-color: rgba(0,0,0,.05);
        }
        body:not(.dark-mode) .table-bordered,
        body:not(.dark-mode) .table-bordered td,
        body:not(.dark-mode) .table-bordered th {
            border-color: #dee2e6;
        }
        body:not(.dark-mode) .table-danger {
            background-color: #fff5f5 !important;
        }

        /* Navbar */
        body:not(.dark-mode) .main-header.navbar {
            background-color: #ffffff;
            border-bottom: 1px solid #dee2e6;
        }
        body:not(.dark-mode) .main-header .nav-link,
        body:not(.dark-mode) .main-header .nav-link i {
            color: #495057 !important;
        }
        body:not(.dark-mode) .main-header .nav-link:hover {
            color: #007bff !important;
        }

        /* Sidebar */
        body:not(.dark-mode) .main-sidebar {
            background-color: #343a40;
        }

        /* Dropdown menus */
        body:not(.dark-mode) .dropdown-menu {
            background-color: #ffffff;
            border-color: #dee2e6;
        }
        body:not(.dark-mode) .dropdown-item {
            color: #212529;
        }
        body:not(.dark-mode) .dropdown-item:hover {
            background-color: #f8f9fa;
        }
        body:not(.dark-mode) .dropdown-header {
            color: #6c757d;
        }

        /* Forms */
        body:not(.dark-mode) .form-control {
            background-color: #ffffff;
            border-color: #ced4da;
            color: #212529;
        }
        body:not(.dark-mode) .form-control:focus {
            border-color: #80bdff;
            background-color: #ffffff;
            color: #212529;
        }
        body:not(.dark-mode) .input-group-text {
            background-color: #e9ecef;
            border-color: #ced4da;
            color: #495057;
        }

        /* Select2 */
        body:not(.dark-mode) .select2-container--bootstrap4 .select2-selection {
            background-color: #ffffff;
            border-color: #ced4da;
            color: #212529;
        }
        body:not(.dark-mode) .select2-dropdown {
            background-color: #ffffff;
            border-color: #ced4da;
        }
        body:not(.dark-mode) .select2-results__option {
            color: #212529;
        }
        body:not(.dark-mode) .select2-results__option--highlighted {
            background-color: #007bff;
            color: #fff;
        }

        /* Modal */
        body:not(.dark-mode) .modal-content {
            background-color: #ffffff;
            color: #212529;
        }
        body:not(.dark-mode) .modal-header {
            border-bottom-color: #dee2e6;
        }
        body:not(.dark-mode) .modal-footer {
            border-top-color: #dee2e6;
        }

        /* Breadcrumb */
        body:not(.dark-mode) .breadcrumb {
            background-color: transparent;
        }
        body:not(.dark-mode) .breadcrumb-item a {
            color: #007bff;
        }
        body:not(.dark-mode) .breadcrumb-item.active {
            color: #6c757d;
        }

        /* Text utilities */
        body:not(.dark-mode) .text-muted {
            color: #6c757d !important;
        }
        body:not(.dark-mode) h1, body:not(.dark-mode) h2,
        body:not(.dark-mode) h3, body:not(.dark-mode) h4,
        body:not(.dark-mode) h5, body:not(.dark-mode) h6 {
            color: #212529;
        }

        /* Accordion / collapse card headers */
        body:not(.dark-mode) .card-header[data-toggle="collapse"] {
            background-color: #f8f9fa;
        }
        body:not(.dark-mode) .card-header[data-toggle="collapse"]:hover {
            background-color: #e9ecef;
        }

        /* Content header */
        body:not(.dark-mode) .content-header h1 {
            color: #212529;
        }

        /* Nav header in sidebar (tetap dark) */
        body:not(.dark-mode) .nav-sidebar .nav-header {
            color: #c2c7d0;
        }

        /* DataTables */
        body:not(.dark-mode) .dataTables_wrapper .dataTables_length,
        body:not(.dark-mode) .dataTables_wrapper .dataTables_filter,
        body:not(.dark-mode) .dataTables_wrapper .dataTables_info,
        body:not(.dark-mode) .dataTables_wrapper .dataTables_paginate {
            color: #212529;
        }
        body:not(.dark-mode) .page-link {
            background-color: #fff;
            border-color: #dee2e6;
            color: #007bff;
        }
        body:not(.dark-mode) .page-item.active .page-link {
            background-color: #007bff;
            border-color: #007bff;
        }

        /* Toggle button style */
        #themeToggle i {
            font-size: 1rem;
            transition: transform 0.3s ease;
        }
        body.dark-mode #themeToggle i {
            color: #f6c90e;
        }
        body:not(.dark-mode) #themeToggle i {
            color: #495057;
        }
    </style>

    <style>
        /* Preloader styles */
        #preloader {
            position: fixed;
            inset: 0;
            background: #0b0f14;
            background: linear-gradient(180deg, #0b0f14 0%, rgba(11, 15, 20, 0.95) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            z-index: 2000;
            color: #fff;
        }

        .preloader-logo {
            width: 120px;
            height: auto;
            border-radius: 12px;
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.5);
            transform-origin: center;
            animation: preloader-bounce 1.6s infinite;
        }

        .preloader-spinner {
            border: 4px solid rgba(255, 255, 255, 0.08);
            border-top-color: #ffffff;
            border-radius: 50%;
            width: 36px;
            height: 36px;
            margin-top: 16px;
            animation: spin 1s linear infinite;
        }

        .preloader-text {
            margin-top: 10px;
            font-size: 14px;
            opacity: 0.85;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        @keyframes preloader-bounce {

            0%,
            100% {
                transform: translateY(0)
            }

            50% {
                transform: translateY(-6px)
            }
        }

        /* hide preloader after loaded */
        body.preloader-done #preloader {
            display: none;
        }
    </style>
    <style>
        /* Styling agar input pencarian di header tidak terlalu besar */
        .search-row input {
            width: 100%;
            font-size: 12px;
            padding: 5px;
        }

        /* Mengatur loading spinner agar di tengah */
        div.dataTables_wrapper div.dataTables_processing {
            top: 10% !important;
            background: rgba(255, 255, 255, 0.8);
            border: 1px solid #ddd;
        }
    </style>
</head>

<body class="hold-transition sidebar-mini layout-fixed layout-navbar-fixed layout-footer-fixed">
    <div class="wrapper">

        <!-- Preloader -->
        <div id="preloader" aria-hidden="true">
            <img class="preloader-logo" src="<?= base_url('assets/') ?>dist/img/erdekatala.png" alt="ERDEKATALA Logo">
            <div class="preloader-spinner" role="status" aria-label="Loading"></div>
            <div class="preloader-text">Memuat…</div>
        </div>

        <?= $this->include('templates/navbar'); ?>

        <?= $this->include('templates/sidebar'); ?>

        <?= $this->renderSection('content'); ?>

        <?= $this->include('templates/footer'); ?>
    </div>
    <!-- ./wrapper -->

    <!-- REQUIRED SCRIPTS -->


    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="<?= base_url('assets/') ?>plugins/jquery/jquery.min.js"></script>
    <!-- Bootstrap -->
    <script src="<?= base_url('assets/') ?>plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
    <!-- overlayScrollbars -->
    <script src="<?= base_url('assets/') ?>plugins/overlayScrollbars/js/jquery.overlayScrollbars.min.js"></script>
    <!-- AdminLTE App -->
    <script src="<?= base_url('assets/') ?>dist/js/adminlte.js"></script>

    <!-- Select2 -->
    <script src="<?= base_url('assets/') ?>plugins/select2/js/select2.full.min.js"></script>
    <script src="<?= base_url('assets/') ?>plugins/sweetalert2/sweetalert2.min.js"></script>

    <!-- AdminLTE dashboard demo (This is only for demo purposes) -->
    <script src="<?= base_url('assets/') ?>dist/js/pages/dashboard2.js"></script>

    <!-- DataTables  & Plugins -->
    <script src="<?= base_url('assets/') ?>plugins/datatables/jquery.dataTables.min.js"></script>
    <script src="<?= base_url('assets/') ?>plugins/datatables-bs4/js/dataTables.bootstrap4.min.js"></script>
    <script src="<?= base_url('assets/') ?>plugins/datatables-responsive/js/dataTables.responsive.min.js"></script>
    <script src="<?= base_url('assets/') ?>plugins/datatables-responsive/js/responsive.bootstrap4.min.js"></script>
    <script src="<?= base_url('assets/') ?>plugins/datatables-buttons/js/dataTables.buttons.min.js"></script>
    <script src="<?= base_url('assets/') ?>plugins/datatables-buttons/js/buttons.bootstrap4.min.js"></script>
    <script src="<?= base_url('assets/') ?>plugins/jszip/jszip.min.js"></script>
    <script src="<?= base_url('assets/') ?>plugins/pdfmake/pdfmake.min.js"></script>
    <script src="<?= base_url('assets/') ?>plugins/pdfmake/vfs_fonts.js"></script>
    <script src="<?= base_url('assets/') ?>plugins/datatables-buttons/js/buttons.html5.min.js"></script>
    <script src="<?= base_url('assets/') ?>plugins/datatables-buttons/js/buttons.print.min.js"></script>
    <script src="<?= base_url('assets/') ?>plugins/datatables-buttons/js/buttons.colVis.min.js"></script>
    <script>
        $(document).ready(function () {
            $('#example2').DataTable({
                "paging": true,
                "lengthChange": true,
                "searching": true,
                "ordering": true,
                "info": true,
                "autoWidth": false,
                "responsive": true,
                "language": {
                    "search": "Cari Desa:",
                    "lengthMenu": "Tampilkan _MENU_ data",
                    "zeroRecords": "Data tidak ditemukan",
                    "info": "Menampilkan _START_ s/d _END_ dari _TOTAL_ data",
                    "paginate": {
                        "next": "Berikutnya",
                        "previous": "Sebelumnya"
                    }
                },
                // Mengatur tata letak agar search bar dan length menu memiliki padding yang pas
                "dom": "<'row px-3 pt-3'<'col-sm-6'l><'col-sm-6 text-right'f>>" +
                    "<'row'<'col-sm-12'tr>>" +
                    "<'row px-3 pb-3'<'col-sm-5'i><'col-sm-7'p>>",
                // mengatur tata letak paginasi di kanan bawah
            });
            $(document).ready(function () {
                // Inisialisasi DataTables
                $('#tableLaporan').DataTable({
                    "responsive": true,
                    "lengthChange": true,
                    "autoWidth": false,
                    "buttons": ["copy", "csv", "excel", "pdf", "print", "colvis"],
                    "language": {
                        "url": "//cdn.datatables.net/plug-ins/1.10.24/i18n/Indonesian.json" // Terjemahan Indo
                    },
                    // Mengatur tata letak agar search bar dan length menu memiliki padding yang pas
                    "dom": "<'row px-3 pt-3'<'col-sm-6'l><'col-sm-6 text-right'f>>" +
                        "<'row'<'col-sm-12'tr>>" +
                        "<'row px-3 pb-3'<'col-sm-5'i><'col-sm-7'p>>",
                    // mengatur tata letak paginasi di kanan bawah

                }).buttons().container().appendTo('#tableLaporan_wrapper .col-md-6:eq(0)');
            });
            $("#example1").DataTable({
                "paging": true,
                "lengthChange": true,
                "searching": true,
                "ordering": true,
                "info": true,
                "autoWidth": false,
                "responsive": true,
                "buttons": ["copy", "csv", "excel", "pdf", "print", "colvis"],
                "language": {
                    "search": "Cari:",
                    "lengthMenu": "Tampilkan _MENU_ data",
                    "zeroRecords": "Data tidak ditemukan",
                    "info": "Menampilkan _START_ s/d _END_ dari _TOTAL_ data",
                    "paginate": {
                        "next": "Berikutnya",
                        "previous": "Sebelumnya"
                    }
                },
                "dom": "<'row px-3 pt-3'<'col-sm-6'l><'col-sm-6 text-right'f>>" +
                    "<'row'<'col-sm-12'tr>>" +
                    "<'row px-3 pb-3'<'col-sm-5'i><'col-sm-7 text-right'p>>"
            }).buttons().container().appendTo('#example1_wrapper .col-sm-6:eq(0)');
        });
        $('.select2').select2()

        //Initialize Select2 Elements
        $('.select2bs4').select2({
            theme: 'bootstrap4'
        })

        $(function () {
            // Inisialisasi Toast AdminLTE
            var Toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true
            });

            // Deteksi Flashdata Error dari CodeIgniter
            <?php if (session()->getFlashdata('error')): ?>
                Toast.fire({
                    icon: 'error',
                    title: <?= json_encode(session()->getFlashdata('error')) ?>
                });
            <?php endif; ?>

            // Deteksi Flashdata Success dari CodeIgniter
            <?php if (session()->getFlashdata('success')): ?>
                Toast.fire({
                    icon: 'success',
                    title: <?= json_encode(session()->getFlashdata('success')) ?>
                });
            <?php endif; ?>

            // Konfirmasi logout menggunakan SweetAlert2
            $('.logout-btn').on('click', function (e) {
                e.preventDefault();
                var url = $(this).data('logout-url');
                Swal.fire({
                    title: 'Konfirmasi Logout',
                    text: 'Apakah Anda yakin ingin keluar?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, keluar',
                    cancelButtonText: 'Batal',
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = url;
                    }
                });
            });
        });

    </script>

    <script>
        // ============================================================
        // DARK / LIGHT MODE TOGGLE
        // ============================================================
        (function () {
            // Terapkan tema sesegera mungkin sebelum render untuk hindari flash
            var saved = localStorage.getItem('erdekatala_theme') || 'dark';
            if (saved === 'dark') {
                document.body.classList.add('dark-mode');
            } else {
                document.body.classList.remove('dark-mode');
            }
        })();

        $(document).ready(function () {
            function applyTheme(mode) {
                var $body = $('body');
                var $icon = $('#themeIcon');

                if (mode === 'dark') {
                    $body.addClass('dark-mode');
                    $icon.removeClass('fa-moon').addClass('fa-sun');
                    localStorage.setItem('erdekatala_theme', 'dark');
                } else {
                    $body.removeClass('dark-mode');
                    $icon.removeClass('fa-sun').addClass('fa-moon');
                    localStorage.setItem('erdekatala_theme', 'light');
                }
            }

            // Inisialisasi ikon sesuai mode tersimpan
            var currentTheme = localStorage.getItem('erdekatala_theme') || 'dark';
            applyTheme(currentTheme);

            // Klik toggle
            $('#themeToggle').on('click', function () {
                var isDark = $('body').hasClass('dark-mode');
                applyTheme(isDark ? 'light' : 'dark');
            });
        });
    </script>

    <script>
        // Preloader: fade out when page fully loaded
        $(window).on('load', function () {
            // small delay for smoother effect
            setTimeout(function () {
                $('#preloader').fadeOut(400, function () {
                    $('body').addClass('preloader-done');
                });
            }, 250);
            // Fallback: remove after 5s in case load event doesn't fire
            setTimeout(function () {
                $('#preloader').fadeOut(200, function () {
                    $('body').addClass('preloader-done');
                });
            }, 5000);
        });
    </script>



</body>

</html>