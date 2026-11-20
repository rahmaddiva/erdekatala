<?php
helper('url');
$loggedIn = false;
try {
    $loggedIn = (bool) service('session')->get('logged_in');
} catch (\Throwable $e) {
    $loggedIn = false;
}
$path = '/' . ltrim(uri_string(), '/');
$homeUrl = base_url('/');
$actionUrl = $loggedIn ? base_url('dashboard') : base_url('login');
$actionLabel = $loggedIn ? 'Buka Dashboard' : 'Masuk ke Aplikasi';
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta name="description" content="Halaman tidak ditemukan — Erdekatala.">
    <title>404 — Halaman Tidak Ditemukan | Erdekatala</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700&family=Lexend:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= base_url('assets/plugins/fontawesome-free/css/all.min.css') ?>">
    <link rel="icon" type="image/png" href="<?= base_url('assets/dist/img/erdekatala.png') ?>">

    <style>
        :root {
            --ink:       #0a2a4a;
            --ink-2:     #103a5c;
            --primary:   #005bac;
            --primary-d: #004a8c;
            --primary-l: #3a96dd;
            --paper:     #ffffff;
            --paper-2:   #eef3f8;
            --line:      #d5dfee;
            --muted:     #5f6b7a;
            --soft:      #f6f9fc;
            --shadow:    0 24px 60px rgba(8, 32, 58, .14);
            --font-brand: 'Poppins', sans-serif;
            --font-body:  'Lexend', sans-serif;
        }

        html.dark {
            --ink:       #eaf2fb;
            --ink-2:     #c6d3e6;
            --primary:   #3a96dd;
            --primary-d: #6db4e8;
            --primary-l: #6db4e8;
            --paper:     #0b1526;
            --paper-2:   #0a1220;
            --line:      #26364f;
            --muted:     #9fb0c6;
            --soft:      #0f1c30;
            --shadow:    0 24px 60px rgba(0, 0, 0, .45);
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        html { color-scheme: light; }
        html.dark { color-scheme: dark; }

        body {
            min-height: 100vh;
            min-height: 100dvh;
            background:
                radial-gradient(circle at 1px 1px, rgba(0, 91, 172, .10) 1px, transparent 0) 0 0 / 26px 26px,
                radial-gradient(1100px 620px at 85% -10%, rgba(58, 150, 221, .18), transparent 60%),
                radial-gradient(900px 540px at -10% 110%, rgba(0, 91, 172, .10), transparent 55%),
                var(--paper-2);
            color: var(--ink);
            font-family: var(--font-body);
            -webkit-font-smoothing: antialiased;
        }

        .shell {
            width: min(1120px, calc(100% - 40px));
            min-height: 100vh;
            min-height: 100dvh;
            margin: 0 auto;
            padding: 28px 0 36px;
            display: flex;
            flex-direction: column;
        }

        .brand {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            width: fit-content;
            border-radius: 12px;
            padding: 6px 8px;
            transition: background .18s ease;
        }
        .brand:hover { background: rgba(0, 91, 172, .06); }
        .brand img { height: 42px; width: auto; display: block; border-radius: 8px; }
        html.dark .brand img { background: #fff; padding: 3px 6px; }
        .brand-text { display: flex; flex-direction: column; line-height: 1.15; }
        .brand-name { font-family: var(--font-brand); font-weight: 700; font-size: 1rem; color: var(--ink); letter-spacing: .01em; }
        .brand-sub  { font-size: .68rem; color: var(--muted); letter-spacing: .06em; text-transform: uppercase; }

        .main {
            flex: 1;
            display: grid;
            grid-template-columns: minmax(0, 1.05fr) minmax(0, .95fr);
            align-items: center;
            gap: clamp(24px, 5vw, 72px);
            padding: clamp(28px, 6vh, 72px) 0 24px;
        }

        .eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            width: fit-content;
            padding: 7px 12px;
            border-radius: 999px;
            border: 1px solid rgba(0, 91, 172, .22);
            background: rgba(0, 91, 172, .06);
            color: var(--primary-d);
            font-family: var(--font-brand);
            font-size: .72rem;
            font-weight: 600;
            letter-spacing: .12em;
            text-transform: uppercase;
        }
        html.dark .eyebrow { border-color: rgba(109, 180, 232, .28); background: rgba(58, 150, 221, .10); }

        .code {
            font-family: var(--font-brand);
            font-size: clamp(6.5rem, 17vw, 11.5rem);
            font-weight: 700;
            line-height: .82;
            letter-spacing: -.07em;
            margin: 18px 0 10px;
            color: var(--primary);
            background: linear-gradient(135deg, var(--primary-d), var(--primary) 45%, var(--primary-l));
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            filter: drop-shadow(0 18px 30px rgba(0, 91, 172, .18));
        }

        h1 {
            font-family: var(--font-brand);
            font-size: clamp(1.55rem, 3vw, 2.15rem);
            line-height: 1.2;
            letter-spacing: -.02em;
            color: var(--ink);
            margin-bottom: 12px;
        }

        .lead {
            color: var(--muted);
            font-size: clamp(.94rem, 1.25vw, 1.03rem);
            line-height: 1.75;
            max-width: 54ch;
        }

        .path {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            max-width: 100%;
            margin-top: 20px;
            padding: 8px 12px;
            border: 1px dashed var(--line);
            border-radius: 10px;
            background: var(--paper);
            color: var(--muted);
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
            font-size: .78rem;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .path i { color: var(--primary); flex: 0 0 auto; }

        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 28px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 9px;
            min-height: 46px;
            padding: 0 20px;
            border-radius: 12px;
            border: 1px solid transparent;
            font-family: var(--font-brand);
            font-size: .88rem;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: transform .16s ease, box-shadow .16s ease, background .16s ease, border-color .16s ease;
        }
        .btn:focus-visible { outline: 3px solid var(--primary-l); outline-offset: 3px; }
        .btn-primary {
            color: #fff;
            background: linear-gradient(135deg, var(--primary-d), var(--primary));
            box-shadow: 0 12px 26px rgba(0, 91, 172, .24);
        }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 16px 32px rgba(0, 91, 172, .30); }
        .btn-ghost {
            color: var(--ink);
            background: var(--paper);
            border-color: var(--line);
        }
        .btn-ghost:hover { transform: translateY(-2px); border-color: var(--primary-l); }

        .hint {
            margin-top: 18px;
            color: var(--muted);
            font-size: .78rem;
            line-height: 1.6;
        }
        .hint strong { color: var(--ink-2); font-weight: 500; }

        /* ==== Ilustrasi peta ==== */
        .art {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 360px;
        }
        .art::before {
            content: "";
            position: absolute;
            inset: 8% 4%;
            border-radius: 32px;
            background:
                radial-gradient(circle at 30% 25%, rgba(58, 150, 221, .22), transparent 45%),
                radial-gradient(circle at 75% 70%, rgba(0, 91, 172, .16), transparent 50%),
                var(--soft);
            border: 1px solid var(--line);
            box-shadow: var(--shadow);
            transform: rotate(-2deg);
        }
        .art svg {
            position: relative;
            width: min(100%, 430px);
            height: auto;
            filter: drop-shadow(0 20px 30px rgba(8, 32, 58, .12));
        }
        .pin {
            animation: float 3.8s ease-in-out infinite;
            transform-origin: 210px 150px;
        }
        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50%      { transform: translateY(-9px); }
        }

        .footer {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            padding-top: 18px;
            border-top: 1px solid var(--line);
            color: var(--muted);
            font-size: .74rem;
        }
        .footer a { color: var(--primary-d); text-decoration: none; }
        .footer a:hover { text-decoration: underline; }

        @media (max-width: 860px) {
            .main { grid-template-columns: 1fr; padding-top: 20px; gap: 8px; }
            .art { order: -1; min-height: 260px; }
            .art::before { inset: 4% 10%; }
            .art svg { width: min(100%, 330px); }
            .code { margin-top: 14px; }
            .footer { justify-content: center; text-align: center; }
        }

        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { animation: none !important; transition: none !important; }
        }
    </style>

    <script>
        (function () {
            try {
                if (localStorage.getItem('sikada_theme') === 'dark') {
                    document.documentElement.classList.add('dark');
                }
            } catch (e) { /* localStorage bisa diblokir */ }
        })();
    </script>
</head>

<body>
    <div class="shell">
        <a class="brand" href="<?= esc($homeUrl) ?>" aria-label="Erdekatala — Beranda">
            <img src="<?= base_url('assets/dist/img/erdekatala.png') ?>" alt="Logo Erdekatala">
            <span class="brand-text">
                <span class="brand-name">Erdekatala</span>
                <span class="brand-sub">Kabupaten Tanah Laut</span>
            </span>
        </a>

        <main class="main">
            <section>
                <span class="eyebrow"><i class="fas fa-map-signs" aria-hidden="true"></i> Error 404</span>
                <div class="code" aria-hidden="true">404</div>
                <h1>Halaman yang Anda cari tidak ditemukan.</h1>
                <p class="lead">
                    Alamat ini mungkin salah ketik, sudah dipindahkan, atau tidak lagi tersedia.
                    Silakan kembali ke beranda untuk menjelajahi data kependudukan Kabupaten Tanah Laut.
                </p>

                <div class="path" title="<?= esc($path) ?>">
                    <i class="fas fa-link" aria-hidden="true"></i>
                    <span><?= esc($path) ?></span>
                </div>

                <div class="actions">
                    <a class="btn btn-primary" href="<?= esc($homeUrl) ?>">
                        <i class="fas fa-house" aria-hidden="true"></i> Kembali ke Beranda
                    </a>
                    <a class="btn btn-ghost" href="<?= esc($actionUrl) ?>">
                        <i class="fas <?= $loggedIn ? 'fa-tachometer-alt' : 'fa-sign-in-alt' ?>" aria-hidden="true"></i>
                        <?= esc($actionLabel) ?>
                    </a>
                </div>

                <p class="hint">
                    Butuh bantuan? Hubungi <strong>admin Erdekatala</strong> atau kembali ke
                    <a href="<?= esc($homeUrl) ?>" style="color:var(--primary-d)">halaman utama</a>.
                </p>
            </section>

            <div class="art" aria-hidden="true">
                <svg viewBox="0 0 420 360" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <rect x="54" y="46" width="312" height="246" rx="26" fill="var(--paper)" stroke="var(--line)" stroke-width="2"/>
                    <path d="M86 96c34-22 61-6 92 4s55 6 86-8 58-12 76 2" stroke="var(--primary-l)" stroke-opacity=".45" stroke-width="2" stroke-linecap="round"/>
                    <path d="M82 134c40-14 61 8 96 8s62-16 91-6 54 6 76-6" stroke="var(--primary-l)" stroke-opacity=".28" stroke-width="2" stroke-linecap="round"/>
                    <path d="M86 176c30 10 57-8 88-2s50 14 79 6 49-18 74-10" stroke="var(--primary-l)" stroke-opacity=".35" stroke-width="2" stroke-linecap="round"/>
                    <path d="M88 218c33 14 58-10 92-4s55 16 86 4 51-20 70-8" stroke="var(--primary-l)" stroke-opacity=".22" stroke-width="2" stroke-linecap="round"/>
                    <path d="M96 256c28 8 54-6 82 0s48 12 76 4 44-12 62-4" stroke="var(--primary-l)" stroke-opacity=".30" stroke-width="2" stroke-linecap="round"/>
                    <g opacity=".55">
                        <circle cx="118" cy="82" r="3" fill="var(--primary)"/>
                        <circle cx="146" cy="82" r="3" fill="var(--primary)"/>
                        <circle cx="174" cy="82" r="3" fill="var(--primary)"/>
                        <circle cx="118" cy="110" r="3" fill="var(--primary)"/>
                        <circle cx="146" cy="110" r="3" fill="var(--primary)"/>
                        <circle cx="174" cy="110" r="3" fill="var(--primary)"/>
                        <circle cx="118" cy="138" r="3" fill="var(--primary)"/>
                        <circle cx="146" cy="138" r="3" fill="var(--primary)"/>
                        <circle cx="174" cy="138" r="3" fill="var(--primary)"/>
                    </g>
                    <path class="pin" d="M232 74c-30 0-54 23-54 52 0 39 54 92 54 92s54-53 54-92c0-29-24-52-54-52Z" fill="var(--primary)" opacity=".14"/>
                    <path class="pin" d="M232 86c-24 0-43 18-43 40 0 30 43 73 43 73s43-43 43-73c0-22-19-40-43-40Z" fill="var(--paper)" stroke="var(--primary)" stroke-width="5"/>
                    <circle class="pin" cx="232" cy="126" r="14" fill="var(--primary)"/>
                    <path d="M318 258c-18 14-36 20-54 18" stroke="var(--primary)" stroke-width="2" stroke-dasharray="5 7" stroke-linecap="round" opacity=".55"/>
                    <circle cx="326" cy="250" r="10" fill="var(--soft)" stroke="var(--line)" stroke-width="2"/>
                    <circle cx="326" cy="250" r="3" fill="var(--primary)"/>
                </svg>
            </div>
        </main>

        <footer class="footer">
            <span>© <?= date('Y') ?> Erdekatala — Elektronik Rumah Data Kependudukan Tanah Laut</span>
            <span><a href="<?= esc($homeUrl) ?>">Beranda</a> · <a href="<?= esc($actionUrl) ?>"><?= esc($actionLabel) ?></a></span>
        </footer>
    </div>
</body>

</html>
