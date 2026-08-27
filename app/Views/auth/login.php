<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Masuk ke Erdekatala — Sistem Informasi Kependudukan Agregat Kabupaten Tanah Laut.">
    <title>Masuk — Erdekatala</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&family=Lexend:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= base_url('assets/plugins/fontawesome-free/css/all.min.css'); ?>">
    <link rel="icon" type="image/png" href="<?= base_url('assets/dist/img/erdekatala.png') ?>">
    <link rel="shortcut icon" href="<?= base_url('favicon.ico') ?>">

    <style>
        /* ============================================================
           ERDEKATALA — LOGIN (redesign)
           Design system: design-system/erdekatala/MASTER.md
                          + pages/login.md (override)
           Brand tokens selaras dengan landing page publik.
           ============================================================ */
        :root {
            --ink:        #0f1923;
            --ink-2:      #1a2744;
            --primary:    #dd4814;
            --primary-d:  #b0380f;
            --primary-dd: #96300c;
            --paper:      #ffffff;
            --paper-2:    #f7f5f1;
            --line:       #e5e1d8;
            --muted:      #6b7280;
            --danger-bg:   #fdf0eb;
            --danger-line: #f2c4b0;
            --danger-ink:  #8f2d0a;
            --font-brand: 'Poppins', sans-serif;
            --font-body:  'Lexend', sans-serif;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        html { color-scheme: light; }

        body {
            font-family: var(--font-body);
            background: var(--paper);
            color: var(--ink);
            -webkit-font-smoothing: antialiased;
        }

        .shell {
            display: grid;
            grid-template-columns: 1fr;
            min-height: 100vh;
            min-height: 100dvh;
        }

        /* ================= BRAND PANEL (kiri, desktop) ================= */
        .brand-panel {
            display: none;
            position: relative;
            overflow: hidden;
            background: linear-gradient(160deg, #0f1923 0%, #14212e 55%, #1a2744 100%);
            color: #fff;
        }

        /* pola titik halus (nuansa peta/GIS) */
        .brand-panel::before {
            content: "";
            position: absolute;
            inset: 0;
            background-image: radial-gradient(rgba(255, 255, 255, 0.055) 1px, transparent 1px);
            background-size: 26px 26px;
            pointer-events: none;
        }

        /* pendar hangat di sudut bawah */
        .brand-panel::after {
            content: "";
            position: absolute;
            width: 640px;
            height: 460px;
            left: -140px;
            bottom: -160px;
            background: radial-gradient(closest-side, rgba(221, 72, 20, 0.28), transparent 72%);
            pointer-events: none;
        }

        .brand-inner {
            position: relative;
            z-index: 1;
            height: 100%;
            display: flex;
            flex-direction: column;
            padding: 48px 56px 40px;
            max-width: 620px;
        }

        .brand-head {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .brand-logo {
            width: 52px;
            height: 52px;
            flex-shrink: 0;
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.96);
            display: grid;
            place-items: center;
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.25);
        }

        .brand-logo img { width: 40px; height: auto; display: block; }

        .brand-name {
            display: block;
            font-family: var(--font-brand);
            font-weight: 700;
            font-size: 1.25rem;
            letter-spacing: -0.01em;
            line-height: 1.2;
        }

        .brand-sub {
            display: block;
            font-size: 0.68rem;
            font-weight: 400;
            letter-spacing: 0.16em;
            text-transform: uppercase;
            color: rgba(255, 255, 255, 0.55);
            margin-top: 3px;
        }

        .brand-title {
            font-family: var(--font-brand);
            font-weight: 700;
            font-size: clamp(1.7rem, 2.5vw, 2.3rem);
            line-height: 1.28;
            letter-spacing: -0.015em;
            margin: auto 0 0;
            padding-top: 56px;
        }

        .brand-title em {
            font-style: normal;
            color: #f0824f;
        }

        .brand-desc {
            margin-top: 16px;
            font-size: 0.92rem;
            font-weight: 300;
            line-height: 1.75;
            color: rgba(255, 255, 255, 0.72);
            max-width: 46ch;
        }

        .brand-points {
            list-style: none;
            margin: 28px 0 0;
            display: grid;
            gap: 14px;
        }

        .brand-points li {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 0.86rem;
            color: rgba(255, 255, 255, 0.82);
        }

        .brand-points li i {
            width: 34px;
            height: 34px;
            flex-shrink: 0;
            display: grid;
            place-items: center;
            border-radius: 9px;
            background: rgba(221, 72, 20, 0.18);
            border: 1px solid rgba(221, 72, 20, 0.35);
            color: #f0824f;
            font-size: 0.8rem;
        }

        .brand-foot {
            margin-top: 44px;
            padding-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.09);
            font-size: 0.72rem;
            letter-spacing: 0.04em;
            color: rgba(255, 255, 255, 0.45);
        }

        /* ================= FORM PANEL (kanan) ================= */
        .form-panel {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            min-height: 100dvh;
            background: var(--paper);
        }

        /* header brand ringkas — hanya mobile */
        .brand-mobile {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            padding: 28px 24px 0;
        }

        .brand-mobile .brand-logo {
            width: 46px;
            height: 46px;
            border-radius: 11px;
            box-shadow: 0 4px 14px rgba(15, 25, 35, 0.12);
        }

        .brand-mobile .brand-logo img { width: 34px; }

        .brand-mobile .bm-name {
            font-family: var(--font-brand);
            font-weight: 700;
            font-size: 1.1rem;
            color: var(--ink);
            line-height: 1.2;
        }

        .brand-mobile .bm-sub {
            font-size: 0.62rem;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            color: var(--muted);
            margin-top: 2px;
        }

        .form-center {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 24px;
        }

        .form-wrap {
            width: 100%;
            max-width: 400px;
            animation: rise 0.45s cubic-bezier(0.22, 1, 0.36, 1) both;
        }

        @keyframes rise {
            from { opacity: 0; transform: translateY(14px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .form-wrap h2 {
            font-family: var(--font-brand);
            font-weight: 700;
            font-size: 1.55rem;
            letter-spacing: -0.015em;
            color: var(--ink);
        }

        .form-wrap .sub {
            margin-top: 8px;
            margin-bottom: 28px;
            font-size: 0.88rem;
            font-weight: 300;
            line-height: 1.6;
            color: var(--muted);
        }

        /* ---------- Alert error ---------- */
        .alert {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            background: var(--danger-bg);
            border: 1px solid var(--danger-line);
            border-radius: 10px;
            padding: 12px 14px;
            margin-bottom: 20px;
            font-size: 0.84rem;
            line-height: 1.55;
            color: var(--danger-ink);
        }

        .alert > i { margin-top: 2px; font-size: 0.9rem; flex-shrink: 0; }
        .alert ul { margin: 0; padding-left: 16px; }

        /* ---------- Field ---------- */
        .field { margin-bottom: 18px; }

        .field label {
            display: block;
            font-size: 0.8rem;
            font-weight: 500;
            color: var(--ink);
            margin-bottom: 7px;
        }

        .control { position: relative; }

        .control > .lead-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #9aa3ae;
            font-size: 0.85rem;
            pointer-events: none;
            transition: color 0.18s ease;
        }

        .control input {
            width: 100%;
            height: 48px;
            padding: 0 46px;
            border: 1px solid var(--line);
            border-radius: 10px;
            background: var(--paper);
            font-family: var(--font-body);
            font-size: 0.95rem;
            color: var(--ink);
            transition: border-color 0.18s ease, box-shadow 0.18s ease;
        }

        .control input::placeholder { color: #a8afb9; font-weight: 300; }
        .control input:hover { border-color: #d3ccc0; }

        .control input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3.5px rgba(221, 72, 20, 0.16);
        }

        .control input:focus ~ .lead-icon,
        .control input:focus-within ~ .lead-icon { color: var(--primary-d); }

        .control input.is-invalid {
            border-color: var(--danger-line);
            background: #fffbf9;
        }

        /* toggle lihat/sembunyikan password */
        .toggle-pass {
            position: absolute;
            right: 2px;
            top: 50%;
            transform: translateY(-50%);
            width: 44px;
            height: 44px;
            display: grid;
            place-items: center;
            border: 0;
            border-radius: 8px;
            background: transparent;
            color: #8b93a0;
            font-size: 0.85rem;
            cursor: pointer;
            transition: background 0.15s ease, color 0.15s ease;
        }

        .toggle-pass:hover { background: var(--paper-2); color: var(--ink); }

        .toggle-pass:focus-visible {
            outline: 2px solid var(--primary);
            outline-offset: 2px;
        }

        /* ---------- Remember + tombol ---------- */
        .remember-row {
            display: flex;
            align-items: center;
            gap: 8px;
            margin: 2px 0 24px;
        }

        .remember-row input[type="checkbox"] {
            width: 16px;
            height: 16px;
            accent-color: var(--primary-d);
            cursor: pointer;
        }

        .remember-row label {
            font-size: 0.84rem;
            color: #4b5563;
            cursor: pointer;
            user-select: none;
        }

        .btn-submit {
            width: 100%;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            border: 0;
            border-radius: 10px;
            background: var(--primary-d);
            color: #fff;
            font-family: var(--font-brand);
            font-weight: 600;
            font-size: 0.96rem;
            letter-spacing: 0.02em;
            cursor: pointer;
            box-shadow: 0 10px 22px -8px rgba(176, 56, 15, 0.5);
            transition: background 0.2s ease, box-shadow 0.2s ease, transform 0.12s ease;
        }

        .btn-submit:hover {
            background: #c2410f;
            box-shadow: 0 14px 26px -8px rgba(176, 56, 15, 0.55);
        }

        .btn-submit:active { background: var(--primary-dd); transform: translateY(1px); }

        .btn-submit:focus-visible {
            outline: 2px solid var(--primary);
            outline-offset: 3px;
        }

        .btn-submit:disabled { opacity: 0.75; cursor: not-allowed; }

        .btn-submit .spinner { display: none; font-size: 0.95rem; }
        .btn-submit.is-loading .spinner { display: inline-block; }

        /* ---------- Tautan & footer ---------- */
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-top: 26px;
            font-size: 0.8rem;
            font-weight: 400;
            color: var(--muted);
            text-decoration: none;
            transition: color 0.15s ease;
        }

        .back-link i { font-size: 0.72rem; }
        .back-link:hover { color: var(--primary-d); }

        .back-link:focus-visible {
            outline: 2px solid var(--primary);
            outline-offset: 3px;
            border-radius: 4px;
        }

        .form-foot {
            padding: 0 24px 22px;
            text-align: center;
            font-size: 0.7rem;
            letter-spacing: 0.03em;
            color: #9aa3ae;
        }

        /* ================= RESPONSIVE ================= */
        @media (min-width: 992px) {
            .shell { grid-template-columns: minmax(430px, 46%) 1fr; }
            .brand-panel { display: block; }
            .brand-mobile { display: none; }
            .form-panel { min-height: 0; }
            .form-center { padding: 48px; }
        }

        @media (max-width: 380px) {
            .form-center { padding: 32px 18px; }
            .form-wrap h2 { font-size: 1.35rem; }
        }

        /* aksesibilitas: hormati preferensi reduksi gerak */
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                animation: none !important;
                transition: none !important;
            }
        }
    </style>
</head>

<body>
    <?php
    $flashError  = session()->getFlashdata('error');
    $flashErrors = session()->getFlashdata('errors'); // array error validasi
    $oldUsername = old('username', '');
    ?>

    <div class="shell">

        <!-- ============ PANEL BRAND (desktop) ============ -->
        <aside class="brand-panel">
            <div class="brand-inner">
                <div class="brand-head">
                    <span class="brand-logo">
                        <img src="<?= base_url('assets/dist/img/erdekatala.png') ?>" alt="Logo Erdekatala" width="40" height="36">
                    </span>
                    <div>
                        <span class="brand-name">Erdekatala</span>
                        <span class="brand-sub">Kabupaten Tanah Laut</span>
                    </div>
                </div>

                <h1 class="brand-title">Sistem Informasi<br>Kependudukan <em>Agregat</em></h1>
                <p class="brand-desc">
                    Satu pintu untuk mencatat, memantau, dan melaporkan data agregat
                    kependudukan desa se-Kabupaten Tanah Laut.
                </p>

                <ul class="brand-points">
                    <li><i class="fas fa-database" aria-hidden="true"></i> Data agregat per desa, dusun, hingga RT</li>
                    <li><i class="fas fa-chart-line" aria-hidden="true"></i> Monitoring &amp; riwayat laporan bulanan</li>
                    <li><i class="fas fa-map-marked-alt" aria-hidden="true"></i> Visualisasi peta sebaran wilayah</li>
                </ul>

                <div class="brand-foot">
                    DP3AP2KB Kabupaten Tanah Laut &middot; Kalimantan Selatan
                </div>
            </div>
        </aside>

        <!-- ============ PANEL FORM ============ -->
        <main class="form-panel">

            <!-- brand ringkas untuk layar kecil -->
            <div class="brand-mobile">
                <span class="brand-logo">
                    <img src="<?= base_url('assets/dist/img/erdekatala.png') ?>" alt="Logo Erdekatala" width="34" height="31">
                </span>
                <div>
                    <div class="bm-name">Erdekatala</div>
                    <div class="bm-sub">Kabupaten Tanah Laut</div>
                </div>
            </div>

            <div class="form-center">
                <div class="form-wrap">
                    <h2>Selamat datang kembali</h2>
                    <p class="sub">Masuk untuk melanjutkan ke dasbor Erdekatala.</p>

                    <?php if ($flashError): ?>
                        <div class="alert" role="alert">
                            <i class="fas fa-exclamation-circle" aria-hidden="true"></i>
                            <div><?= esc($flashError) ?></div>
                        </div>
                    <?php endif; ?>

                    <?php if (is_array($flashErrors) && count($flashErrors)): ?>
                        <div class="alert" role="alert">
                            <i class="fas fa-exclamation-circle" aria-hidden="true"></i>
                            <div>
                                <?php foreach ($flashErrors as $err): ?>
                                    <?= esc($err) ?><br>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <form id="loginForm" action="<?= base_url('proses_login') ?>" method="post">
                        <?= csrf_field() ?>

                        <div class="field">
                            <label for="username">Username</label>
                            <div class="control">
                                <input type="text" id="username" name="username"
                                    placeholder="Masukkan username Anda" autocomplete="username" required
                                    <?= $oldUsername ? '' : 'autofocus' ?> value="<?= esc($oldUsername) ?>">
                                <i class="fas fa-user lead-icon" aria-hidden="true"></i>
                            </div>
                        </div>

                        <div class="field">
                            <label for="password">Password</label>
                            <div class="control">
                                <input type="password" id="password" name="password"
                                    placeholder="Masukkan password Anda" autocomplete="current-password" required
                                    <?= $oldUsername ? 'autofocus' : '' ?>>
                                <i class="fas fa-lock lead-icon" aria-hidden="true"></i>
                                <button type="button" class="toggle-pass" id="togglePassword"
                                    aria-label="Tampilkan password" aria-pressed="false">
                                    <i class="fas fa-eye" aria-hidden="true"></i>
                                </button>
                            </div>
                        </div>

                        <div class="remember-row">
                            <input type="checkbox" name="remember" id="remember" value="1">
                            <label for="remember">Ingat saya</label>
                        </div>

                        <button type="submit" class="btn-submit" id="btnSubmit">
                            <i class="fas fa-circle-notch fa-spin spinner" aria-hidden="true"></i>
                            <span class="btn-label">Masuk</span>
                        </button>
                    </form>

                    <a class="back-link" href="<?= base_url('/') ?>">
                        <i class="fas fa-arrow-left" aria-hidden="true"></i>
                        Kembali ke beranda publik
                    </a>
                </div>
            </div>

            <footer class="form-foot">
                &copy; <?= date('Y') ?> DP3AP2KB Kabupaten Tanah Laut
            </footer>
        </main>
    </div>

    <script>
        (function () {
            'use strict';

            // Toggle tampil/sembunyikan password
            var toggle = document.getElementById('togglePassword');
            var pass   = document.getElementById('password');

            if (toggle && pass) {
                toggle.addEventListener('click', function () {
                    var show = pass.type === 'password';
                    pass.type = show ? 'text' : 'password';

                    var icon = toggle.querySelector('i');
                    icon.classList.toggle('fa-eye', !show);
                    icon.classList.toggle('fa-eye-slash', show);

                    toggle.setAttribute('aria-label', show ? 'Sembunyikan password' : 'Tampilkan password');
                    toggle.setAttribute('aria-pressed', String(show));
                    pass.focus();
                });
            }

            // Umpan balik submit: tombol loading saat form diproses
            var form = document.getElementById('loginForm');
            var btn  = document.getElementById('btnSubmit');

            if (form && btn) {
                form.addEventListener('submit', function () {
                    btn.classList.add('is-loading');
                    btn.disabled = true;
                    btn.querySelector('.btn-label').textContent = 'Memproses\u2026';
                });
            }
        })();
    </script>
</body>

</html>
