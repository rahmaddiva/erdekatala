<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ERDEKATALA |
        <?= $title ?>
    </title>

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
</head>

<body class="hold-transition dark-mode sidebar-mini layout-fixed layout-navbar-fixed layout-footer-fixed">
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
                    title: '<?= session()->getFlashdata('error') ?>'
                });
            <?php endif; ?>

            // Deteksi Flashdata Success dari CodeIgniter
            <?php if (session()->getFlashdata('success')): ?>
                Toast.fire({
                    icon: 'success',
                    title: '<?= session()->getFlashdata('success') ?>'
                });
            <?php endif; ?>
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