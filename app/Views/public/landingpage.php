<?php
// Normalisasi data untuk tampilan
$jiwaL       = (int)($totals['jiwa_l'] ?? 0);
$jiwaP       = (int)($totals['jiwa_p'] ?? 0);
$jiwaTotal   = $jiwaL + $jiwaP;
$kkL         = (int)($totals['kk_l'] ?? 0);
$kkP         = (int)($totals['kk_p'] ?? 0);
$kkTotal     = $kkL + $kkP;
$balita      = (int)($totals['balita'] ?? 0);
$remaja      = (int)($totals['remaja'] ?? 0);
$lansia      = (int)($totals['lansia'] ?? 0);
$pus         = (int)($totals['pus'] ?? 0);
$kbAktif     = (int)($totals['kb_aktif'] ?? 0);
$alatKontra  = (int)($totals['alat_kontrasepsi'] ?? 0);
$pendudukKey = !empty($filter_kec) ? (esc($filter_desa ? 'Desa' : 'Kecamatan')) : 'Kabupaten';

// Helper untuk format angka
$fmt = function($n) { return number_format((int)$n, 0, ',', '.'); };
$pendTotal = array_sum($pendidikan);
$pekTotal  = array_sum($pekerjaan);
$kawinTotal = array_sum($status_kawin);
$jknTotal   = array_sum($jkn_bpjs);
$dokTotal   = array_sum($dokumen);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sikada Tala — Sistem Informasi Kependudukan Kabupaten Tanah Laut</title>
    <meta name="description" content="Portal data agregat kependudukan tingkat RT-RW-Desa-Kecamatan di Kabupaten Tanah Laut, Kalimantan Selatan.">

    <!-- Font: Poppins -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Vendor CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">

    <!-- DataTables CSS (Bootstrap 5 + Responsive) -->
    <link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css" rel="stylesheet">

    <!-- ZingChart (CDN resmi, sama dengan dashboard) -->
    <script src="https://cdn.zingchart.com/zingchart.min.js"></script>

    <style>
        :root {
            --primary:   #dd4814;
            --primary-d: #b0380f;
            --ink:       #0f1923;
            --ink-soft:  #1e2d3a;
            --paper:     #ffffff;
            --paper-2:   #f7f5f1;
            --paper-3:   #efece6;
            --muted:     #6b7280;
            --border:    #e5e1d8;
            --accent-l:  #2d6cdf;
            --accent-p:  #d6336c;
            --good:      #2f9e44;
            --shadow-sm: 0 1px 2px rgba(15,25,35,.04), 0 1px 1px rgba(15,25,35,.03);
            --shadow-md: 0 4px 16px rgba(15,25,35,.06), 0 2px 4px rgba(15,25,35,.04);
            --shadow-lg: 0 24px 48px -12px rgba(15,25,35,.14);
        }

        * { box-sizing: border-box; }

        body {
            font-family: 'Poppins', sans-serif;
            background: var(--paper-2);
            color: var(--ink);
            margin: 0;
            line-height: 1.55;
            -webkit-font-smoothing: antialiased;
        }

        h1, h2, h3, h4, h5, .display {
            font-family: 'Poppins', sans-serif;
            color: var(--ink);
            letter-spacing: -0.01em;
        }

        a { color: var(--primary); text-decoration: none; }
        a:hover { color: var(--primary-d); }

        .container { max-width: 1240px; }

        /* ===== NAVBAR ===== */
        .navbar-sikada {
            background: var(--paper);
            border-bottom: 1px solid var(--border);
            padding: 0.85rem 0;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: var(--shadow-sm);
        }
        .navbar-sikada .brand {
            display: flex;
            align-items: center;
            gap: 0.65rem;
            font-family: 'Poppins', sans-serif;
            font-weight: 700;
            font-size: 1.35rem;
            color: var(--ink);
            text-decoration: none;
        }
        .navbar-sikada .brand-mark {
            width: 38px;
            height: 38px;
            background: var(--primary);
            color: #fff;
            border-radius: 8px;
            display: grid;
            place-items: center;
            font-family: 'Poppins', sans-serif;
            font-weight: 700;
            font-size: 1.05rem;
            box-shadow: 0 2px 8px rgba(221,72,20,.35);
        }
        .navbar-sikada .brand small {
            display: block;
            font-family: 'Poppins', sans-serif;
            font-weight: 400;
            font-size: 0.72rem;
            color: var(--muted);
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }
        .navbar-sikada .nav-right {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }
        .navbar-sikada .nav-link-sikada {
            font-weight: 600;
            color: var(--ink-soft);
            font-size: 0.95rem;
        }
        .navbar-sikada .nav-link-sikada:hover { color: var(--primary); }
        .btn-login {
            background: var(--ink);
            color: #fff;
            padding: 0.55rem 1.25rem;
            border-radius: 6px;
            font-weight: 600;
            font-size: 0.92rem;
            border: none;
            transition: all .2s ease;
        }
        .btn-login:hover { background: var(--primary); color: #fff; transform: translateY(-1px); }

        /* ===== HERO ===== */
        .hero {
            background: var(--ink);
            color: #fff;
            padding: 4.5rem 0 3rem;
            position: relative;
            overflow: hidden;
        }
        .hero::before {
            content: "";
            position: absolute;
            top: 0; right: 0;
            width: 55%;
            height: 100%;
            background:
                radial-gradient(circle at 80% 20%, rgba(221,72,20,.22), transparent 55%),
                radial-gradient(circle at 95% 75%, rgba(45,108,223,.15), transparent 50%);
            pointer-events: none;
        }
        .hero::after {
            content: "";
            position: absolute;
            bottom: -1px; left: 0;
            width: 100%;
            height: 64px;
            background: var(--paper-2);
            clip-path: polygon(0 100%, 100% 100%, 100% 0, 0 100%);
        }
        .hero .eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.78rem;
            font-weight: 600;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            color: var(--primary);
            margin-bottom: 1rem;
        }
        .hero .eyebrow::before {
            content: "";
            width: 28px;
            height: 2px;
            background: var(--primary);
        }
        .hero h1 {
            font-size: clamp(2rem, 4.2vw, 3.1rem);
            line-height: 1.1;
            color: #fff;
            margin-bottom: 1rem;
            font-weight: 700;
        }
        .hero h1 em {
            font-style: italic;
            color: var(--primary);
        }
        .hero p.lead {
            font-size: 1.12rem;
            color: rgba(255,255,255,.78);
            max-width: 640px;
            font-weight: 300;
        }
        .hero-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 1.75rem;
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid rgba(255,255,255,.12);
        }
        .hero-meta .item {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }
        .hero-meta .item .v {
            font-family: 'Poppins', sans-serif;
            font-size: 1.6rem;
            font-weight: 700;
            color: #fff;
            line-height: 1;
        }
        .hero-meta .item .l {
            font-size: 0.76rem;
            color: rgba(255,255,255,.6);
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        /* ===== FILTER BAR ===== */
        .filter-bar {
            background: var(--paper);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 1.25rem 1.5rem;
            margin-top: -2rem;
            position: relative;
            z-index: 5;
            box-shadow: var(--shadow-lg);
            display: flex;
            flex-wrap: wrap;
            align-items: flex-end;
            gap: 1rem;
        }
        .filter-bar .field { flex: 1 1 220px; min-width: 200px; }
        .filter-bar label {
            display: block;
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: var(--muted);
            margin-bottom: 0.35rem;
        }
        .filter-bar select,
        .filter-bar input {
            width: 100%;
            padding: 0.6rem 0.85rem;
            border: 1px solid var(--border);
            border-radius: 6px;
            background: var(--paper-2);
            font-family: inherit;
            font-size: 0.95rem;
            color: var(--ink);
            transition: border-color .15s ease, box-shadow .15s ease;
        }
        .filter-bar select:focus,
        .filter-bar input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(221,72,20,.12);
            background: var(--paper);
        }
        .filter-bar .btn-filter {
            background: var(--primary);
            color: #fff;
            border: none;
            padding: 0.6rem 1.5rem;
            border-radius: 6px;
            font-weight: 600;
            font-size: 0.92rem;
            transition: all .2s ease;
            height: fit-content;
        }
        .filter-bar .btn-filter:hover { background: var(--primary-d); transform: translateY(-1px); }
        .filter-bar .btn-reset {
            background: transparent;
            color: var(--muted);
            border: 1px solid var(--border);
            padding: 0.6rem 1rem;
            border-radius: 6px;
            font-weight: 600;
            font-size: 0.92rem;
            height: fit-content;
        }
        .filter-bar .btn-reset:hover { color: var(--ink); border-color: var(--ink); }

        /* ===== SECTION SHELL ===== */
        section.block { padding: 3rem 0; }
        .section-head {
            display: flex;
            align-items: baseline;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: 1.75rem;
            padding-bottom: 0.85rem;
            border-bottom: 2px solid var(--ink);
        }
        .section-head h2 {
            font-size: 1.6rem;
            margin: 0;
            font-weight: 700;
        }
        .section-head .crumb {
            font-size: 0.82rem;
            color: var(--muted);
            font-weight: 600;
            letter-spacing: 0.04em;
        }
        .section-head .crumb strong { color: var(--primary); }

        /* ===== STAT CARDS ===== */
        .stat-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1rem;
            margin-bottom: 2.5rem;
        }
        @media (max-width: 992px) { .stat-grid { grid-template-columns: repeat(2, 1fr); } }
        @media (max-width: 576px) { .stat-grid { grid-template-columns: 1fr; } }

        .stat-card {
            background: var(--paper);
            border: 1px solid var(--border);
            border-left: 4px solid var(--primary);
            border-radius: 8px;
            padding: 1.25rem 1.4rem;
            position: relative;
            transition: transform .2s ease, box-shadow .2s ease;
        }
        .stat-card:hover { transform: translateY(-3px); box-shadow: var(--shadow-md); }
        .stat-card .icon {
            position: absolute;
            top: 1rem;
            right: 1.1rem;
            font-size: 1.4rem;
            color: var(--paper-3);
        }
        .stat-card .label {
            font-size: 0.74rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: var(--muted);
            margin-bottom: 0.4rem;
        }
        .stat-card .value {
            font-family: 'Poppins', sans-serif;
            font-size: 2.1rem;
            font-weight: 700;
            color: var(--ink);
            line-height: 1;
            margin-bottom: 0.35rem;
        }
        .stat-card .sub {
            font-size: 0.82rem;
            color: var(--muted);
        }
        .stat-card .sub b { color: var(--accent-l); }
        .stat-card .sub i { color: var(--accent-p); font-style: normal; }
        .stat-card.alt-blue { border-left-color: var(--accent-l); }
        .stat-card.alt-pink { border-left-color: var(--accent-p); }
        .stat-card.alt-green { border-left-color: var(--good); }
        .stat-card.alt-blue .icon { color: #dbe7fb; }
        .stat-card.alt-pink .icon { color: #fbdcec; }
        .stat-card.alt-green .icon { color: #d4f0da; }

        /* ===== CHART CARD ===== */
        .chart-card {
            background: var(--paper);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 1.5rem 1.6rem 1.75rem;
            box-shadow: var(--shadow-sm);
        }
        .chart-card .chart-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: 1rem;
            padding-bottom: 0.85rem;
            border-bottom: 1px dashed var(--border);
        }
        .chart-card .chart-head h3 {
            font-size: 1.12rem;
            margin: 0 0 0.2rem;
            font-weight: 700;
        }
        .chart-card .chart-head p {
            font-size: 0.8rem;
            color: var(--muted);
            margin: 0;
        }
        .chart-card .chart-head .badge-nde {
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            padding: 0.3rem 0.6rem;
            background: var(--paper-3);
            color: var(--ink-soft);
            border-radius: 4px;
            white-space: nowrap;
        }
        .chart-wrap { width: 100%; }
        .zc { width: 100%; min-height: 260px; }
        .zc-pyramid { min-height: 450px; }
        .zc-tall { min-height: 300px; }

        /* ===== LEGEND ===== */
        .legend-inline {
            display: flex;
            gap: 1.25rem;
            flex-wrap: wrap;
            margin-top: 0.5rem;
            font-size: 0.82rem;
            color: var(--ink-soft);
        }
        .legend-inline .dot {
            display: inline-block;
            width: 10px; height: 10px;
            border-radius: 2px;
            margin-right: 0.4rem;
            vertical-align: middle;
        }

        /* ===== INFO STRIP ===== */
        .info-strip {
            background: var(--ink);
            color: #fff;
            border-radius: 10px;
            padding: 1.5rem 1.75rem;
            display: flex;
            align-items: center;
            gap: 1.25rem;
            margin: 2rem 0;
        }
        .info-strip .info-icon {
            font-size: 1.8rem;
            color: var(--primary);
            flex-shrink: 0;
        }
        .info-strip h4 { color: #fff; margin: 0 0 0.2rem; font-size: 1.05rem; }
        .info-strip p { margin: 0; color: rgba(255,255,255,.7); font-size: 0.9rem; }

        /* ===== FOOTER ===== */
        footer.sikada-footer {
            background: var(--ink);
            color: rgba(255,255,255,.65);
            padding: 3rem 0 1.5rem;
            margin-top: 3rem;
        }
        footer.sikada-footer h5 {
            color: #fff;
            font-size: 1rem;
            margin-bottom: 1rem;
        }
        footer.sikada-footer a { color: rgba(255,255,255,.65); }
        footer.sikada-footer a:hover { color: var(--primary); }
        footer.sikada-footer .footer-bottom {
            border-top: 1px solid rgba(255,255,255,.1);
            padding-top: 1.25rem;
            margin-top: 2rem;
            font-size: 0.82rem;
            color: rgba(255,255,255,.45);
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 0.5rem;
        }
        footer.sikada-footer .brand-mark {
            width: 32px; height: 32px;
            background: var(--primary);
            color: #fff;
            border-radius: 6px;
            display: inline-grid;
            place-items: center;
            font-family: 'Poppins', sans-serif;
            font-weight: 700;
            font-size: 0.85rem;
            margin-bottom: 0.75rem;
        }

        /* ===== EMPTY STATE ===== */
        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
            color: var(--muted);
        }
        .empty-state i { font-size: 2.5rem; color: var(--paper-3); margin-bottom: 0.75rem; display: block; }

        /* ===== TABS ===== */
        .sikada-tabs {
            border-bottom: 2px solid var(--border);
            gap: 0.25rem;
            flex-wrap: nowrap;
            overflow-x: auto;
            scrollbar-width: thin;
        }
        .sikada-tabs .nav-link {
            border: none;
            border-bottom: 3px solid transparent;
            color: var(--muted);
            font-weight: 600;
            font-size: 0.9rem;
            padding: 0.85rem 1.1rem;
            border-radius: 0;
            white-space: nowrap;
            transition: all .2s ease;
        }
        .sikada-tabs .nav-link:hover {
            color: var(--primary);
            border-bottom-color: var(--paper-3);
        }
        .sikada-tabs .nav-link.active {
            color: var(--primary);
            border-bottom-color: var(--primary);
            background: transparent;
        }
        .tab-pane { padding-top: 1.5rem; }

        /* ===== DATA TABLE ===== */
        .dt-wrap {
            background: var(--paper);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 1.25rem 1.5rem 0.5rem;
            box-shadow: var(--shadow-sm);
            margin-top: 1.5rem;
        }
        .dt-wrap .dt-head {
            display: flex; justify-content: space-between; align-items: center;
            margin-bottom: 0.75rem;
        }
        .dt-wrap .dt-head h3 {
            font-size: 1rem; font-weight: 700; color: var(--ink); margin: 0;
        }
        .dt-wrap .dt-head .crumb {
            font-size: 0.8rem; color: var(--muted); font-weight: 600;
        }
        table.dataTable {
            border-collapse: separate !important;
            border-spacing: 0;
        }
        table.dataTable thead th {
            background: var(--paper-2);
            border-bottom: 1px solid var(--border) !important;
            font-size: 0.78rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--muted);
            padding: 0.7rem 1rem;
        }
        table.dataTable tbody td {
            border-bottom: 1px solid var(--border) !important;
            font-size: 0.9rem;
            color: var(--ink);
            padding: 0.6rem 1rem;
        }
        table.dataTable tbody tr:hover td {
            background: rgba(221,72,20,.03) !important;
        }
        .dt-num { text-align: right; font-variant-numeric: tabular-nums; font-weight: 600; }
        .dt-total { font-weight: 700; color: var(--primary); }
        .dt-cat { font-weight: 600; color: var(--ink); }
        .dt-sub { color: var(--ink-soft); }
        .dt-pct { color: var(--muted); font-size: 0.82rem; }
        div.dataTables_wrapper div.dataTables_filter input {
            border: 1px solid var(--border);
            border-radius: 6px;
            padding: 0.35rem 0.7rem;
            font-size: 0.85rem;
        }

        /* ===== JELAJAHI KECAMATAN ===== */
        .kec-grid {
            display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
            gap: 0.85rem; margin-top: 1.5rem;
        }
        .kec-card {
            display: flex; flex-direction: column; align-items: center;
            justify-content: center; text-align: center;
            background: var(--paper); border: 1px solid var(--border);
            border-radius: 8px; padding: 1.25rem 0.75rem;
            color: var(--ink); font-weight: 600; font-size: 0.9rem;
            transition: all .2s ease; text-decoration: none;
        }
        .kec-card:hover {
            border-color: var(--primary); color: var(--primary);
            transform: translateY(-3px); box-shadow: var(--shadow-md);
        }
        .kec-card i { font-size: 1.5rem; margin-bottom: 0.5rem; color: var(--paper-3); }
        .kec-card:hover i { color: var(--primary); }

        @media (max-width: 768px) {
            .hero { padding: 3rem 0 2.5rem; }
            .hero-meta { gap: 1rem; }
            .section-head { flex-direction: column; align-items: flex-start; }
            .stat-grid { gap: 0.75rem; }
        }
    </style>
</head>

<body>

    <!-- ===== NAVBAR ===== -->
    <nav class="navbar-sikada">
        <div class="container d-flex align-items-center justify-content-between">
            <a href="<?= site_url('/') ?>" class="brand">
                <span class="brand-mark">S</span>
                <span>
                    Sikada Tala
                    <small>Kabupaten Tanah Laut</small>
                </span>
            </a>
            <div class="nav-right d-none d-md-flex">
                <a href="<?= site_url('/api/docs') ?>" class="nav-link-sikada"><i class="bi bi-code-slash me-1"></i>API</a>
                <a href="<?= site_url('/login') ?>" class="btn-login"><i class="bi bi-box-arrow-in-right me-1"></i>Masuk</a>
            </div>
            <a href="<?= site_url('/login') ?>" class="btn-login d-md-none"><i class="bi bi-box-arrow-in-right"></i></a>
        </div>
    </nav>

    <!-- ===== HERO ===== -->
    <header class="hero">
        <div class="container position-relative">
            <span class="eyebrow">Portal Data Kependudukan</span>
            <h1>Sikada Tala — <em>Agregat Data</em><br>tingkat RT di Kabupaten Tanah Laut</h1>
            <p class="lead">Pemantauan terpadu data jiwa, kartu keluarga, pendidikan, pekerjaan, piramida penduduk, serta cakupan JKN/BPJS dan KB-PUS dari hulu ke hilir, langsung dari lapangan.</p>
            <div class="hero-meta">
                <div class="item">
                    <span class="v">11</span>
                    <span class="l">Kecamatan</span>
                </div>
                <div class="item">
                    <span class="v">RT-RW</span>
                    <span class="l">Unit Terkecil</span>
                </div>
                <div class="item">
                    <span class="v"><?= number_format($jiwaTotal, 0, ',', '.') ?></span>
                    <span class="l">Total Jiwa tercatat</span>
                </div>
                <div class="item">
                    <span class="v"><?= number_format($kkTotal, 0, ',', '.') ?></span>
                    <span class="l">Kartu Keluarga</span>
                </div>
            </div>
        </div>
    </header>

    <main class="container">

        <!-- ===== FILTER BAR ===== -->
        <form method="GET" action="<?= site_url('/') ?>" class="filter-bar">
            <div class="field">
                <label for="id_kecamatan">Kecamatan</label>
                <select name="id_kecamatan" id="id_kecamatan" onchange="document.getElementById('form-desa').value=''; this.form.submit()">
                    <option value="">— Semua Kecamatan —</option>
                    <?php foreach ($list_kecamatan as $k): ?>
                        <option value="<?= esc($k['id_kecamatan']) ?>" <?= (string)$filter_kec === (string)$k['id_kecamatan'] ? 'selected' : '' ?>>
                            <?= esc($k['nama_kecamatan']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label for="id_desa">Desa</label>
                <select name="id_desa" id="form-desa" onchange="this.form.submit()" <?= empty($filter_kec) ? 'disabled' : '' ?>>
                    <option value="">— Semua Desa —</option>
                    <?php foreach ($list_desa as $d): ?>
                        <option value="<?= esc($d['id_desa']) ?>" <?= (string)$filter_desa === (string)$d['id_desa'] ? 'selected' : '' ?>>
                            <?= esc($d['nama_desa']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn-filter"><i class="bi bi-funnel me-1"></i>Terapkan</button>
            <a href="<?= site_url('/') ?>" class="btn-reset"><i class="bi bi-arrow-counterclockwise me-1"></i>Reset</a>
        </form>

        <?php if ($jiwaTotal === 0 && $kkTotal === 0): ?>
            <div class="empty-state">
                <i class="bi bi-inboxes"></i>
                <h3>Belum ada data laporan</h3>
                <p>Tidak ada laporan agregat pada wilayah yang dipilih. Silakan pilih kecamatan/desa lain atau hubungi admin desa untuk melakukan input laporan.</p>
            </div>
        <?php else: ?>

        <!-- ===== STAT CARDS ===== -->
        <section class="block" style="padding-top: 2rem;">
            <div class="stat-grid">
                <div class="stat-card">
                    <i class="bi bi-people-fill icon"></i>
                    <div class="label">Total Jiwa</div>
                    <div class="value"><?= number_format($jiwaTotal, 0, ',', '.') ?></div>
                    <div class="sub"><b><?= number_format($jiwaL, 0, ',', '.') ?> L</b> · <i><?= number_format($jiwaP, 0, ',', '.') ?> P</i></div>
                </div>
                <div class="stat-card alt-blue">
                    <i class="bi bi-house-heart-fill icon"></i>
                    <div class="label">Kartu Keluarga</div>
                    <div class="value"><?= number_format($kkTotal, 0, ',', '.') ?></div>
                    <div class="sub"><b><?= number_format($kkL, 0, ',', '.') ?> L</b> · <i><?= number_format($kkP, 0, ',', '.') ?> P</i></div>
                </div>
                <div class="stat-card alt-pink">
                    <i class="bi bi-baby icon"></i>
                    <div class="label">Balita</div>
                    <div class="value"><?= number_format($balita, 0, ',', '.') ?></div>
                    <div class="sub">Usia 0–5 tahun</div>
                </div>
                <div class="stat-card alt-green">
                    <i class="bi bi-heart-pulse-fill icon"></i>
                    <div class="label">PUS</div>
                    <div class="value"><?= number_format($pus, 0, ',', '.') ?></div>
                    <div class="sub">Pasangan Usia Subur</div>
                </div>
            </div>

            <!-- Info strip -->
            <div class="info-strip">
                <i class="bi bi-info-circle-fill info-icon"></i>
                <div>
                    <h4>Skop data saat ini: <?= esc($pendudukKey) ?><?= !empty($filter_kec) ? ' — ' . esc($filter_desa ? 'Desa terpilih' : 'Kecamatan terpilih') : ' (seluruh Kabupaten)' ?></h4>
                    <p>Data diturunkan dari laporan agregat RT yang telah diinput oleh admin desa. Filter menyesuaikan tampilan seluruh chart di bawah.</p>
                </div>
            </div>
        </section>

        <!-- ===== TABS: CHART + DATATABLE ===== -->
        <section class="block" style="padding-top: 0;">
            <ul class="nav nav-tabs sikada-tabs" id="dataTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-pokok" type="button" role="tab" aria-selected="true">
                        <i class="bi bi-people-fill me-1"></i>Data Pokok
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-sosio" type="button" role="tab" aria-selected="false">
                        <i class="bi bi-briefcase-fill me-1"></i>Sosio Ekonomi
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-piramida" type="button" role="tab" aria-selected="false">
                        <i class="bi bi-graph-up me-1"></i>Piramida Penduduk
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-kesehatan" type="button" role="tab" aria-selected="false">
                        <i class="bi bi-heart-pulse-fill me-1"></i>Kesehatan &amp; Dokumen
                    </button>
                </li>
            </ul>

            <div class="tab-content">

                <!-- ===== TAB 1: DATA POKOK ===== -->
                <div class="tab-pane fade show active" id="tab-pokok" role="tabpanel">
                    <div class="chart-card">
                        <div class="chart-head">
                            <div>
                                <h3>Perbandingan Laki-laki vs Perempuan</h3>
                                <p>Total jiwa dan total KK berdasarkan jenis kelamin</p>
                            </div>
                            <span class="badge-nde">2 indikator</span>
                        </div>
                        <div class="chart-wrap"><div id="zc-gender" class="zc"></div></div>
                    </div>
                    <div class="dt-wrap">
                        <div class="dt-head">
                            <h3>Tabel Data Pokok</h3>
                            <span class="crumb">Jiwa · KK · Kelompok khusus · PUS</span>
                        </div>
                        <table id="dt-pokok" class="table table-hover" style="width:100%">
                            <thead>
                                <tr>
                                    <th>Kategori</th>
                                    <th class="dt-num">Laki-laki</th>
                                    <th class="dt-num">Perempuan</th>
                                    <th class="dt-num">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td class="dt-cat">Jiwa</td>
                                    <td class="dt-num"><?= $fmt($jiwaL) ?></td>
                                    <td class="dt-num"><?= $fmt($jiwaP) ?></td>
                                    <td class="dt-num dt-total"><?= $fmt($jiwaTotal) ?></td>
                                </tr>
                                <tr>
                                    <td class="dt-cat">Kartu Keluarga</td>
                                    <td class="dt-num"><?= $fmt($kkL) ?></td>
                                    <td class="dt-num"><?= $fmt($kkP) ?></td>
                                    <td class="dt-num dt-total"><?= $fmt($kkTotal) ?></td>
                                </tr>
                                <tr>
                                    <td class="dt-cat">Balita</td>
                                    <td class="dt-num">—</td>
                                    <td class="dt-num">—</td>
                                    <td class="dt-num dt-total"><?= $fmt($balita) ?></td>
                                </tr>
                                <tr>
                                    <td class="dt-cat">Remaja</td>
                                    <td class="dt-num">—</td>
                                    <td class="dt-num">—</td>
                                    <td class="dt-num dt-total"><?= $fmt($remaja) ?></td>
                                </tr>
                                <tr>
                                    <td class="dt-cat">Lansia</td>
                                    <td class="dt-num">—</td>
                                    <td class="dt-num">—</td>
                                    <td class="dt-num dt-total"><?= $fmt($lansia) ?></td>
                                </tr>
                                <tr>
                                    <td class="dt-cat">PUS</td>
                                    <td class="dt-num">—</td>
                                    <td class="dt-num">—</td>
                                    <td class="dt-num dt-total"><?= $fmt($pus) ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- ===== TAB 2: SOSIO EKONOMI ===== -->
                <div class="tab-pane fade" id="tab-sosio" role="tabpanel">
                    <div class="row g-4">
                        <div class="col-lg-6">
                            <div class="chart-card">
                                <div class="chart-head">
                                    <div>
                                        <h3>Pendidikan Kepala Keluarga</h3>
                                        <p>Distribusi tingkat pendidikan pada Kepala Keluarga</p>
                                    </div>
                                    <span class="badge-nde">7 tingkat</span>
                                </div>
                                <div class="chart-wrap"><div id="zc-pendidikan" class="zc zc-tall"></div></div>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="chart-card">
                                <div class="chart-head">
                                    <div>
                                        <h3>Status Perkawinan KK</h3>
                                        <p>Distribusi status perkawinan pada Kepala Keluarga</p>
                                    </div>
                                    <span class="badge-nde">4 kategori</span>
                                </div>
                                <div class="chart-wrap"><div id="zc-kawin" class="zc zc-tall"></div></div>
                            </div>
                        </div>
                    </div>
                    <div class="chart-card" style="margin-top: 1.5rem;">
                        <div class="chart-head">
                            <div>
                                <h3>Distribusi Pekerjaan Kepala Keluarga</h3>
                                <p>Sebaran lapangan pekerjaan utama pada Kepala Keluarga</p>
                            </div>
                            <span class="badge-nde">8 jenis</span>
                        </div>
                        <div class="chart-wrap"><div id="zc-pekerjaan" class="zc zc-tall"></div></div>
                    </div>
                    <div class="dt-wrap">
                        <div class="dt-head">
                            <h3>Tabel Sosio Ekonomi</h3>
                            <span class="crumb">Pendidikan · Pekerjaan · Status Perkawinan</span>
                        </div>
                        <table id="dt-sosio" class="table table-hover" style="width:100%">
                            <thead>
                                <tr>
                                    <th>Kategori</th>
                                    <th>Sub-kategori</th>
                                    <th class="dt-num">Jumlah</th>
                                    <th class="dt-num">Persentase</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pendidikan as $label => $val): ?>
                                <tr>
                                    <td class="dt-cat">Pendidikan KK</td>
                                    <td class="dt-sub"><?= esc($label) ?></td>
                                    <td class="dt-num"><?= $fmt($val) ?></td>
                                    <td class="dt-num dt-pct"><?= $pendTotal > 0 ? number_format($val / $pendTotal * 100, 1, ',', '.') . '%' : '—' ?></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php foreach ($pekerjaan as $label => $val): ?>
                                <tr>
                                    <td class="dt-cat">Pekerjaan KK</td>
                                    <td class="dt-sub"><?= esc($label) ?></td>
                                    <td class="dt-num"><?= $fmt($val) ?></td>
                                    <td class="dt-num dt-pct"><?= $pekTotal > 0 ? number_format($val / $pekTotal * 100, 1, ',', '.') . '%' : '—' ?></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php foreach ($status_kawin as $label => $val): ?>
                                <tr>
                                    <td class="dt-cat">Status Perkawinan</td>
                                    <td class="dt-sub"><?= esc($label) ?></td>
                                    <td class="dt-num"><?= $fmt($val) ?></td>
                                    <td class="dt-num dt-pct"><?= $kawinTotal > 0 ? number_format($val / $kawinTotal * 100, 1, ',', '.') . '%' : '—' ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- ===== TAB 3: PIRAMIDA PENDUDUK ===== -->
                <div class="tab-pane fade" id="tab-piramida" role="tabpanel">
                    <div class="chart-card">
                        <div class="chart-head">
                            <div>
                                <h3>Distribusi Penduduk per Kelompok Umur</h3>
                                <p>Batang kiri = Laki-laki, batang kanan = Perempuan (5 tahunan)</p>
                            </div>
                            <span class="badge-nde">18 kelompok</span>
                        </div>
                        <div class="chart-wrap"><div id="zc-piramida" class="zc zc-pyramid"></div></div>
                    </div>
                    <div class="dt-wrap">
                        <div class="dt-head">
                            <h3>Tabel Piramida Penduduk</h3>
                            <span class="crumb">18 kelompok umur · L vs P</span>
                        </div>
                        <table id="dt-piramida" class="table table-hover" style="width:100%">
                            <thead>
                                <tr>
                                    <th>Kelompok Umur</th>
                                    <th class="dt-num">Laki-laki</th>
                                    <th class="dt-num">Perempuan</th>
                                    <th class="dt-num">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($ageLabels as $i => $label):
                                    $pl = (int)$piramidaL[$i];
                                    $pp = (int)$piramidaP[$i];
                                ?>
                                <tr>
                                    <td class="dt-cat"><?= esc($label) ?></td>
                                    <td class="dt-num"><?= $fmt($pl) ?></td>
                                    <td class="dt-num"><?= $fmt($pp) ?></td>
                                    <td class="dt-num dt-total"><?= $fmt($pl + $pp) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- ===== TAB 4: KESEHATAN & DOKUMEN ===== -->
                <div class="tab-pane fade" id="tab-kesehatan" role="tabpanel">
                    <div class="row g-4">
                        <div class="col-lg-6">
                            <div class="chart-card">
                                <div class="chart-head">
                                    <div>
                                        <h3>Cakupan JKN / BPJS</h3>
                                        <p>Peserta PBI, non-PBI, dan tidak memiliki JKN</p>
                                    </div>
                                    <span class="badge-nde">3 kategori</span>
                                </div>
                                <div class="chart-wrap"><div id="zc-jkn" class="zc zc-tall"></div></div>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="chart-card">
                                <div class="chart-head">
                                    <div>
                                        <h3>Dokumen Kependudukan</h3>
                                        <p>Kepemilikan KTP-el, akta lahir, akta nikah, KK fisik</p>
                                    </div>
                                    <span class="badge-nde">4 jenis</span>
                                </div>
                                <div class="chart-wrap"><div id="zc-dokumen" class="zc zc-tall"></div></div>
                            </div>
                        </div>
                    </div>
                    <div class="dt-wrap">
                        <div class="dt-head">
                            <h3>Tabel Kesehatan &amp; Dokumen</h3>
                            <span class="crumb">JKN/BPJS · Dokumen Kependudukan · KB &amp; PUS</span>
                        </div>
                        <table id="dt-kesehatan" class="table table-hover" style="width:100%">
                            <thead>
                                <tr>
                                    <th>Kategori</th>
                                    <th>Sub-kategori</th>
                                    <th class="dt-num">Jumlah</th>
                                    <th class="dt-num">Persentase</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($jkn_bpjs as $label => $val): ?>
                                <tr>
                                    <td class="dt-cat">JKN/BPJS</td>
                                    <td class="dt-sub"><?= esc($label) ?></td>
                                    <td class="dt-num"><?= $fmt($val) ?></td>
                                    <td class="dt-num dt-pct"><?= $jknTotal > 0 ? number_format($val / $jknTotal * 100, 1, ',', '.') . '%' : '—' ?></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php foreach ($dokumen as $label => $val): ?>
                                <tr>
                                    <td class="dt-cat">Dokumen</td>
                                    <td class="dt-sub"><?= esc($label) ?></td>
                                    <td class="dt-num"><?= $fmt($val) ?></td>
                                    <td class="dt-num dt-pct"><?= $jiwaTotal > 0 ? number_format($val / $jiwaTotal * 100, 1, ',', '.') . '%' : '—' ?></td>
                                </tr>
                                <?php endforeach; ?>
                                <tr>
                                    <td class="dt-cat">KB &amp; PUS</td>
                                    <td class="dt-sub">KB Aktif</td>
                                    <td class="dt-num"><?= $fmt($kbAktif) ?></td>
                                    <td class="dt-num dt-pct"><?= $pus > 0 ? number_format($kbAktif / $pus * 100, 1, ',', '.') . '%' : '—' ?></td>
                                </tr>
                                <tr>
                                    <td class="dt-cat">KB &amp; PUS</td>
                                    <td class="dt-sub">Jumlah PUS</td>
                                    <td class="dt-num"><?= $fmt($pus) ?></td>
                                    <td class="dt-num dt-pct">—</td>
                                </tr>
                                <tr>
                                    <td class="dt-cat">KB &amp; PUS</td>
                                    <td class="dt-sub">Penggunaan Alat Kontrasepsi</td>
                                    <td class="dt-num"><?= $fmt($alatKontra) ?></td>
                                    <td class="dt-num dt-pct"><?= $pus > 0 ? number_format($alatKontra / $pus * 100, 1, ',', '.') . '%' : '—' ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </section>

        <?php endif; ?>

        <!-- ===== JELAJAHI KECAMATAN ===== -->
        <section class="block" style="padding-top: 1.5rem;">
            <div class="section-head">
                <h2>Jelajahi Kecamatan</h2>
                <span class="crumb">11 kecamatan di Kabupaten Tanah Laut</span>
            </div>
            <div class="kec-grid">
                <?php foreach ($list_kecamatan as $k): 
                    $k_slug = $k['slug'] ?: strtolower(str_replace(' ', '-', $k['nama_kecamatan']));
                ?>
                <a href="<?= site_url('/' . $k_slug) ?>" class="kec-card">
                    <i class="bi bi-geo-fill"></i>
                    <?= esc($k['nama_kecamatan']) ?>
                </a>
                <?php endforeach; ?>
            </div>
        </section>

    </main>

    <!-- ===== FOOTER ===== -->
    <footer class="sikada-footer">
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-5">
                    <span class="brand-mark">S</span>
                    <h5>Sikada Tala</h5>
                    <p style="font-size: 0.88rem; color: rgba(255,255,255,.55);">
                        Sistem informasi kependudukan agregat berbasis RT-RW-Desa-Kecamatan untuk Pemerintah Kabupaten Tanah Laut, Kalimantan Selatan.
                    </p>
                </div>
                <div class="col-lg-3 col-6">
                    <h5>Navigasi</h5>
                    <ul class="list-unstyled" style="font-size: 0.9rem; line-height: 2;">
                        <li><a href="<?= site_url('/') ?>">Beranda</a></li>
                        <li><a href="<?= site_url('/login') ?>">Masuk Sistem</a></li>
                        <li><a href="<?= site_url('/api/docs') ?>">Dokumentasi API</a></li>
                        <li><a href="<?= site_url('/api/register') ?>">Daftar API Key</a></li>
                    </ul>
                </div>
                <div class="col-lg-4 col-6">
                    <h5>Tentang Data</h5>
                    <p style="font-size: 0.88rem; color: rgba(255,255,255,.55);">
                        Data pada portal ini bersifat agregat dan diperbarui oleh admin desa setiap periode pelaporan. Untuk data mikro per individu, silakan hubungi Dinas Kependudukan dan Pencatatan Sipil.
                    </p>
                </div>
            </div>
            <div class="footer-bottom">
                <span>&copy; <?= date('Y') ?> Pemerintah Kabupaten Tanah Laut — Dinas Kependudukan &amp; Pencatatan Sipil.</span>
                <span>RT-RW-Desa-Kecamatan-Laporan-Agregat</span>
            </div>
        </div>
    </footer>

    <!-- ===== ZINGCHART CONFIGS ===== -->
    <script>
        document.addEventListener("DOMContentLoaded", function () {
        // Palette konsisten
        const C = {
            primary: "#dd4814",
            ink:     "#0f1923",
            blue:    "#2d6cdf",
            pink:    "#d6336c",
            green:   "#2f9e44",
            muted:   "#6b7280",
            paper:   "#f7f5f1",
            border:  "#e5e1d8"
        };
        const PALETTE7 = ["#dd4814","#2d6cdf","#2f9e44","#d6336c","#8b5cf6","#f59e0b","#0d9488"];
        const GUIDE = { "line-color": C.border, "line-style": "dotted" };
        const AX = { "font-color": C.ink, "font-size": 11 };
        const TIP = { borderRadius: 4, padding: 8, "background-color": C.ink, "font-color": "#fff" };

        // ============ 1. PIRAMIDA PENDUDUK (hbar, L negatif kiri / P positif kanan) ============
        const ageLabels  = <?= json_encode($ageLabels) ?>;
        const piramidaL  = <?= json_encode(array_map('intval', $piramidaL)) ?>;
        const piramidaP  = <?= json_encode(array_map('intval', $piramidaP)) ?>;
        const piramidaLneg = piramidaL.map(v => -1 * v);

        zingchart.render({
            id: "zc-piramida",
            height: 450,
            width: "100%",
            data: {
                type: "hbar",
                "background-color": "transparent",
                stacked: true,
                legend: {
                    layout: "x2", position: "4% 2%",
                    "background-color": "none", "border-width": 0,
                    marker: { "border-radius": 3 },
                    item: Object.assign({}, AX)
                },
                plot: {
                    "bar-width": "82%",
                    "border-radius": 3,
                    tooltip: Object.assign({ text: "%t — Umur %scale-key-text: %node-value orang" }, TIP),
                    animation: { effect: 2, speed: 600 }
                },
                plotarea: { margin: "dynamic 45 dynamic 70" },
                "scale-x": {
                    values: ageLabels,
                    item: Object.assign({}, AX),
                    guide: GUIDE,
                    tick: { "line-color": C.border }
                },
                "scale-y": {
                    label: { text: "Jumlah Penduduk", "font-color": C.muted },
                    item: Object.assign({}, AX),
                    guide: GUIDE,
                    tick: { "line-color": C.border },
                    format: "%v"
                },
                series: [
                    { text: "Laki-laki",  values: piramidaLneg, "background-color": C.blue, "legend-marker": { "background-color": C.blue } },
                    { text: "Perempuan", values: piramidaP,    "background-color": C.pink, "legend-marker": { "background-color": C.pink } }
                ]
            }
        });

        // ============ 2. PENDIDIKAN (bar vertikal + label nilai) ============
        const pendLabels = <?= json_encode(array_keys($pendidikan)) ?>;
        const pendValues = <?= json_encode(array_map('intval', array_values($pendidikan))) ?>;

        zingchart.render({
            id: "zc-pendidikan",
            height: 300,
            width: "100%",
            data: {
                type: "bar",
                "background-color": "transparent",
                plot: {
                    "bar-width": "55%",
                    "border-radius": 4,
                    tooltip: Object.assign({ text: "%k: <strong>%v</strong> KK" }, TIP),
                    "value-box": { text: "%v", "font-color": C.ink, "font-size": 10, placement: "top" },
                    animation: { effect: 2, speed: 500 }
                },
                plotarea: { margin: "dynamic 40 50 45" },
                "scale-x": {
                    values: pendLabels,
                    item: Object.assign({ angle: -20, "offset-y": -3 }, AX),
                    guide: GUIDE,
                    tick: { "line-color": C.border }
                },
                "scale-y": {
                    format: "%v",
                    item: Object.assign({}, AX),
                    guide: GUIDE,
                    tick: { "line-color": C.border }
                },
                series: [{
                    text: "KK",
                    values: pendValues,
                    styles: PALETTE7.map(c => ({ "background-color": c }))
                }]
            }
        });

        // ============ 3. JKN / BPJS (donut) ============
        const jknLabels = <?= json_encode(array_keys($jkn_bpjs)) ?>;
        const jknValues = <?= json_encode(array_map('intval', array_values($jkn_bpjs))) ?>;

        zingchart.render({
            id: "zc-jkn",
            height: 300,
            width: "100%",
            data: {
                type: "pie",
                "background-color": "transparent",
                plot: {
                    "border-radius": 6,
                    "slice": 60,
                    tooltip: Object.assign({ text: "%t: <strong>%v</strong> (%npv%)" }, TIP),
                    "value-box": {
                        text: "%npv%", "font-color": C.ink, "font-size": 11,
                        placement: "out", "font-weight": "bold"
                    },
                    animation: { effect: 2, speed: 600 }
                },
                plotarea: { margin: "10 10 45 10" },
                legend: {
                    layout: "x3", position: "50% 92%",
                    "background-color": "none", "border-width": 0,
                    marker: { "border-radius": 3 },
                    item: Object.assign({}, AX)
                },
                series: [
                    { text: jknLabels[0], values: [jknValues[0]], "background-color": C.primary },
                    { text: jknLabels[1], values: [jknValues[1]], "background-color": C.blue },
                    { text: jknLabels[2], values: [jknValues[2]], "background-color": C.muted }
                ]
            }
        });

        // ============ 4. PEKERJAAN (hbar horizontal + label nilai) ============
        const pkrLabels = <?= json_encode(array_keys($pekerjaan)) ?>;
        const pkrValues = <?= json_encode(array_map('intval', array_values($pekerjaan))) ?>;

        zingchart.render({
            id: "zc-pekerjaan",
            height: 320,
            width: "100%",
            data: {
                type: "hbar",
                "background-color": "transparent",
                plot: {
                    "bar-width": "60%",
                    "border-radius": 4,
                    tooltip: Object.assign({ text: "%scale-key-text: <strong>%v</strong> KK" }, TIP),
                    "value-box": { text: "%v", "font-color": C.ink, "font-size": 10, placement: "top-out" },
                    animation: { effect: 2, speed: 500 }
                },
                plotarea: { margin: "dynamic 50 dynamic 60" },
                "scale-x": {
                    values: pkrLabels,
                    item: Object.assign({}, AX),
                    guide: GUIDE,
                    tick: { "line-color": C.border }
                },
                "scale-y": {
                    format: "%v",
                    item: Object.assign({}, AX),
                    guide: GUIDE,
                    tick: { "line-color": C.border }
                },
                series: [{
                    text: "KK",
                    values: pkrValues,
                    "background-color": C.primary
                }]
            }
        });

        // ============ 5. GENDER (grouped bar + label nilai) ============
        zingchart.render({
            id: "zc-gender",
            height: 260,
            width: "100%",
            data: {
                type: "bar",
                "background-color": "transparent",
                plot: {
                    "bar-width": "45%",
                    "border-radius": 4,
                    tooltip: Object.assign({ text: "%t · %k: <strong>%v</strong>" }, TIP),
                    "value-box": { text: "%v", "font-color": C.ink, "font-size": 10, placement: "top" },
                    animation: { effect: 2, speed: 500 }
                },
                plotarea: { margin: "dynamic 45 45 50" },
                "scale-x": {
                    values: ["Total Jiwa", "Total KK"],
                    item: Object.assign({ "font-size": 12 }, AX),
                    guide: GUIDE,
                    tick: { "line-color": C.border }
                },
                "scale-y": {
                    format: "%v",
                    item: Object.assign({}, AX),
                    guide: GUIDE,
                    tick: { "line-color": C.border }
                },
                legend: {
                    layout: "x2", position: "85% 4%",
                    "background-color": "none", "border-width": 0,
                    marker: { "border-radius": 3 },
                    item: Object.assign({}, AX)
                },
                series: [
                    { text: "Laki-laki",  values: [<?= (int)$jiwaL ?>, <?= (int)$kkL ?>], "background-color": C.blue },
                    { text: "Perempuan", values: [<?= (int)$jiwaP ?>, <?= (int)$kkP ?>], "background-color": C.pink }
                ]
            }
        });

        // ============ 6. STATUS PERKAWINAN (donut) ============
        const kawinLabels = <?= json_encode(array_keys($status_kawin)) ?>;
        const kawinValues = <?= json_encode(array_map('intval', array_values($status_kawin))) ?>;
        const kawinColors = [C.green, C.blue, C.amber, C.primary];

        zingchart.render({
            id: "zc-kawin",
            height: 300,
            width: "100%",
            data: {
                type: "pie",
                "background-color": "transparent",
                plot: {
                    "border-radius": 6,
                    "slice": 60,
                    tooltip: Object.assign({ text: "%t: <strong>%v</strong> KK (%npv%)" }, TIP),
                    "value-box": {
                        text: "%npv%", "font-color": C.ink, "font-size": 11,
                        placement: "out", "font-weight": "bold"
                    },
                    animation: { effect: 2, speed: 600 }
                },
                plotarea: { margin: "10 10 45 10" },
                legend: {
                    layout: "x2", position: "50% 92%",
                    "background-color": "none", "border-width": 0,
                    marker: { "border-radius": 3 },
                    item: Object.assign({}, AX)
                },
                series: kawinValues.map((v, i) => ({
                    text: kawinLabels[i], values: [v],
                    "background-color": kawinColors[i % kawinColors.length]
                }))
            }
        });

        // ============ 7. DOKUMEN ADMINDUK (hbar) ============
        const dokLabels = <?= json_encode(array_keys($dokumen)) ?>;
        const dokValues = <?= json_encode(array_map('intval', array_values($dokumen))) ?>;

        zingchart.render({
            id: "zc-dokumen",
            height: 300,
            width: "100%",
            data: {
                type: "hbar",
                "background-color": "transparent",
                plot: {
                    "bar-width": "60%",
                    "border-radius": 4,
                    tooltip: Object.assign({ text: "%scale-key-text: <strong>%v</strong>" }, TIP),
                    "value-box": { text: "%v", "font-color": C.ink, "font-size": 10, placement: "top-out" },
                    animation: { effect: 2, speed: 500 }
                },
                plotarea: { margin: "dynamic 50 dynamic 60" },
                "scale-x": {
                    values: dokLabels,
                    item: Object.assign({}, AX),
                    guide: GUIDE,
                    tick: { "line-color": C.border }
                },
                "scale-y": {
                    format: "%v",
                    item: Object.assign({}, AX),
                    guide: GUIDE,
                    tick: { "line-color": C.border }
                },
                series: [{
                    text: "Pemilik Dokumen",
                    values: dokValues,
                    "background-color": C.primary
                }]
            }
        });
        });
    </script>

    <!-- jQuery + DataTables + Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const DT_LANG = {
            sEmptyTable: "Tidak ada data tersedia",
            sInfo: "",
            sInfoEmpty: "",
            sInfoFiltered: "",
            sInfoPostFix: "",
            sInfoThousands: ".",
            sLengthMenu: "",
            sLoadingRecords: "Memuat...",
            sProcessing: "Memproses...",
            sSearch: "Cari:",
            sZeroRecords: "Data tidak ditemukan",
            oPaginate: { sFirst: "Awal", sLast: "Akhir", sNext: "Berikut", sPrevious: "Sebelumnya" },
            oAria: { sSortAscending: ": urutkan naik", sSortDescending: ": urutkan turun" }
        };
        const dts = {};
        dts.pokok = $('#dt-pokok').DataTable({
            responsive: true, paging: false, searching: true, info: false,
            language: DT_LANG,
            columnDefs: [
                { targets: [1, 2, 3], className: 'text-end dt-num' },
                { targets: 0, className: 'dt-cat' }
            ]
        });
        dts.sosio = $('#dt-sosio').DataTable({
            responsive: true, paging: false, searching: true, info: false,
            language: DT_LANG,
            columnDefs: [
                { targets: [2, 3], className: 'text-end dt-num' },
                { targets: [0], className: 'dt-cat' }
            ]
        });
        dts.piramida = $('#dt-piramida').DataTable({
            responsive: true, paging: false, searching: true, info: false,
            language: DT_LANG,
            columnDefs: [
                { targets: [1, 2, 3], className: 'text-end dt-num' },
                { targets: 0, className: 'dt-cat' }
            ]
        });
        dts.kesehatan = $('#dt-kesehatan').DataTable({
            responsive: true, paging: false, searching: true, info: false,
            language: DT_LANG,
            columnDefs: [
                { targets: [2, 3], className: 'text-end dt-num' },
                { targets: [0], className: 'dt-cat' }
            ]
        });
        const tabEl = document.getElementById('dataTabs');
        if (tabEl) {
            tabEl.addEventListener('shown.bs.tab', function (e) {
                const targetId = e.target.getAttribute('data-bs-target');
                const key = targetId.replace('#tab-', '');
                if (dts[key]) {
                    dts[key].columns.adjust();
                    if (dts[key].responsive) dts[key].responsive.rebuild();
                }
                const charts = { pokok: 'zc-gender', sosio: null, piramida: 'zc-piramida', kesehatan: null };
                if (key === 'sosio') {
                    ['zc-pendidikan', 'zc-kawin', 'zc-pekerjaan'].forEach(function (id) {
                        try { zingchart.exec(id, 'resize'); } catch (err) {}
                    });
                } else if (key === 'kesehatan') {
                    ['zc-jkn', 'zc-dokumen'].forEach(function (id) {
                        try { zingchart.exec(id, 'resize'); } catch (err) {}
                    });
                } else if (charts[key]) {
                    try { zingchart.exec(charts[key], 'resize'); } catch (err) {}
                }
            });
        }
    });
    </script>
</body>

</html>
