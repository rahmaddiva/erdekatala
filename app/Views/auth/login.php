<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SIKADA TALA | Log in</title>

    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <link rel="stylesheet" href="<?= base_url('assets/plugins/fontawesome-free/css/all.min.css'); ?>">
    <link rel="stylesheet" href="<?= base_url('assets/plugins/icheck-bootstrap/icheck-bootstrap.min.css'); ?>">
    <link rel="stylesheet" href="<?= base_url('assets/dist/css/adminlte.min.css'); ?>">
    <link rel="icon" type="image/png" href="<?= base_url('assets/dist/img/SikadaIreng.png') ?>">

    <style>
        .cursor-follower {
            position: fixed;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            border: 2px solid rgba(192, 57, 43, 0.6);
            pointer-events: none;
            z-index: 9999;
            top: 0;
            left: 0;
            opacity: 0;
        }
        .cursor-follower.active { opacity: 1; }
    </style>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Source Sans Pro', sans-serif;
            min-height: 100vh;
            display: flex;
        }

        /* Panel kiri */
        .login-left {
            width: 55%;
            background: linear-gradient(150deg, #c0392b 0%, #7b241c 100%);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 60px 50px;
            color: #fff;
            position: relative;
            overflow: hidden;
        }

        .login-left::before {
            content: '';
            position: absolute;
            width: 400px;
            height: 400px;
            border-radius: 50%;
            background: rgba(255,255,255,0.05);
            top: -100px;
            right: -100px;
        }

        .login-left::after {
            content: '';
            position: absolute;
            width: 300px;
            height: 300px;
            border-radius: 50%;
            background: rgba(255,255,255,0.05);
            bottom: -80px;
            left: -80px;
        }

        .login-left .logo-wrap {
            margin-bottom: 36px;
            position: relative;
            z-index: 1;
        }

        .login-left .logo-wrap img {
            width: 220px;
            filter: brightness(0) invert(1);
        }

        .login-left .tagline {
            font-size: 1.05rem;
            opacity: 0.9;
            text-align: center;
            line-height: 1.7;
            max-width: 380px;
            position: relative;
            z-index: 1;
            margin-bottom: 40px;
        }

        .login-left .feature-list {
            list-style: none;
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 380px;
        }

        .login-left .feature-list li {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 0.92rem;
            opacity: 0.9;
            padding: 8px 0;
            border-bottom: 1px solid rgba(255,255,255,0.12);
        }

        .login-left .feature-list li:last-child {
            border-bottom: none;
        }

        .login-left .feature-list li i {
            font-size: 1rem;
            opacity: 0.8;
            width: 20px;
            text-align: center;
        }

        .login-left .instansi {
            margin-top: 40px;
            font-size: 0.8rem;
            opacity: 0.65;
            text-align: center;
            position: relative;
            z-index: 1;
            line-height: 1.6;
        }

        /* Panel kanan */
        .login-right {
            width: 45%;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 60px 40px;
            background: #1a1a2e;
        }

        .login-form-wrap {
            width: 100%;
            max-width: 360px;
        }

        .login-form-wrap h2 {
            color: #fff;
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 6px;
        }

        .login-form-wrap .sub {
            color: #aaa;
            font-size: 0.875rem;
            margin-bottom: 30px;
        }

        .login-form-wrap .form-control {
            background: #262640;
            border: 1px solid #3a3a5c;
            color: #fff;
            border-radius: 6px;
        }

        .login-form-wrap .form-control:focus {
            background: #2e2e50;
            border-color: #c0392b;
            color: #fff;
            box-shadow: 0 0 0 0.2rem rgba(192,57,43,0.25);
        }

        .login-form-wrap .form-control::placeholder {
            color: #666;
        }

        .login-form-wrap .input-group-text {
            background: #262640;
            border: 1px solid #3a3a5c;
            color: #888;
        }

        .login-form-wrap .btn-masuk {
            background: #c0392b;
            border: none;
            border-radius: 6px;
            padding: 10px;
            font-size: 0.95rem;
            font-weight: 600;
            letter-spacing: 0.5px;
            transition: background 0.2s;
        }

        .login-form-wrap .btn-masuk:hover {
            background: #a93226;
        }

        .login-form-wrap label {
            color: #aaa;
            font-size: 0.875rem;
        }

        /* Responsive */
        @media (max-width: 768px) {
            body { flex-direction: column; }
            .login-left {
                width: 100%;
                padding: 40px 30px;
            }
            .login-left .feature-list { display: none; }
            .login-right {
                width: 100%;
                padding: 40px 24px;
            }
        }
    </style>
</head>

<body>

    <!-- Panel Kiri: Info Sistem -->
    <div class="login-left">
        <div class="logo-wrap">
            <img src="<?= base_url('assets/dist/img/Sikadaputih.png') ?>" alt="Sikada Tala">
        </div>

        <p class="tagline">
            Sistem Informasi Kependudukan Agregat<br>
            Kabupaten Tanah Laut
        </p>

        <ul class="feature-list">
            <li><i class="fas fa-map-marker-alt"></i> Pendataan hingga tingkat Desa</li>
            <li><i class="fas fa-chart-bar"></i> Visualisasi statistik kependudukan</li>
            <li><i class="fas fa-file-export"></i> Ekspor laporan Excel &amp; PDF</li>
            <li><i class="fas fa-plug"></i> Akses data via Public API</li>
        </ul>

        <p class="instansi">
            DP3AP2KB Kabupaten Tanah Laut<br>
            Kalimantan Selatan
        </p>
    </div>

    <!-- Panel Kanan: Form Login -->
    <div class="login-right">
        <div class="login-form-wrap">
            <h2>Selamat Datang</h2>
            <p class="sub">Masuk untuk melanjutkan ke sistem</p>

            <?php if (session()->getFlashdata('error')): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= session()->getFlashdata('error') ?>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            <?php endif; ?>

            <form action="<?= base_url('proses_login') ?>" method="post">
                <?= csrf_field() ?>

                <div class="input-group mb-3">
                    <input type="text" name="username" class="form-control" placeholder="Username"
                        required autofocus value="<?= esc(old('username')) ?>">
                    <div class="input-group-append">
                        <div class="input-group-text">
                            <span class="fas fa-user"></span>
                        </div>
                    </div>
                </div>

                <div class="input-group mb-4">
                    <input type="password" name="password" class="form-control" placeholder="Password" required>
                    <div class="input-group-append">
                        <div class="input-group-text">
                            <span class="fas fa-lock"></span>
                        </div>
                    </div>
                </div>

                <div class="row align-items-center mb-3">
                    <div class="col-7">
                        <div class="icheck-danger">
                            <input type="checkbox" name="remember" id="remember" value="1">
                            <label for="remember">Ingat Saya</label>
                        </div>
                    </div>
                    <div class="col-5">
                        <button type="submit" class="btn btn-danger btn-block btn-masuk">Masuk</button>
                    </div>
                </div>

            </form>
        </div>
    </div>

    <script src="<?= base_url('assets/plugins/jquery/jquery.min.js') ?>"></script>
    <script src="<?= base_url('assets/plugins/bootstrap/js/bootstrap.bundle.min.js') ?>"></script>
    <script src="<?= base_url('assets/dist/js/adminlte.min.js') ?>"></script>
    <div class="cursor-follower" id="cursorFollower"></div>
    <script>
        (function() {
            var f = document.getElementById('cursorFollower');
            var mx = 0, my = 0, fx = 0, fy = 0;
            var speed = 0.08;
            document.addEventListener('mousemove', function(e) {
                mx = e.clientX;
                my = e.clientY;
                if (!f.classList.contains('active')) f.classList.add('active');
            });
            document.addEventListener('mouseleave', function() {
                f.classList.remove('active');
            });
            (function loop() {
                fx += (mx - fx) * speed;
                fy += (my - fy) * speed;
                f.style.left = (fx - 10) + 'px';
                f.style.top = (fy - 10) + 'px';
                requestAnimationFrame(loop);
            })();
        })();
    </script>
</body>

</html>