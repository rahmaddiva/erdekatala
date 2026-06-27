<?php
// Normalisasi data untuk tampilan
$jiwaL     = (int)($totals['jiwa_l'] ?? 0);
$jiwaP     = (int)($totals['jiwa_p'] ?? 0);
$jiwaTotal = $jiwaL + $jiwaP;
$kkL       = (int)($totals['kk_l'] ?? 0);
$kkP       = (int)($totals['kk_p'] ?? 0);
$kkTotal   = $kkL + $kkP;
$balita    = (int)($totals['balita'] ?? 0);
$pus       = (int)($totals['pus'] ?? 0);

$kecNama   = $kecamatan['nama_kecamatan'] ?? '';
$kecSlug   = strtolower(str_replace(' ', '-', $kecNama));

$hasData = $jiwaTotal > 0;

// Helper slug untuk view
$slugify = function($name) {
    return strtolower(str_replace(' ', '-', $name));
};
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kecamatan <?= esc($kecNama) ?> — Sikada Tala</title>
    <meta name="description" content="Data agregat kependudukan Kecamatan <?= esc($kecNama) ?>, Kabupaten Tanah Laut, Kalimantan Selatan.">

    <!-- Font: Poppins -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Vendor CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">

    <!-- ZingChart -->
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

        h1, h2, h3, h4, h5 { font-family: 'Poppins', sans-serif; color: var(--ink); letter-spacing: -0.01em; }
        a { color: var(--primary); text-decoration: none; }
        a:hover { color: var(--primary-d); }
        .container { max-width: 1240px; }

        /* ===== NAVBAR ===== */
        .navbar-sikada {
            background: var(--paper);
            border-bottom: 1px solid var(--border);
            padding: 0.85rem 0;
            position: sticky; top: 0; z-index: 1000;
            box-shadow: var(--shadow-sm);
        }
        .navbar-sikada .brand {
            display: flex; align-items: center; gap: 0.65rem;
            font-family: 'Poppins', sans-serif; font-weight: 700; font-size: 1.35rem;
            color: var(--ink); text-decoration: none;
        }
        .navbar-sikada .brand-mark {
            width: 38px; height: 38px;
            background: var(--primary); color: #fff;
            border-radius: 8px; display: grid; place-items: center;
            font-family: 'Poppins', sans-serif; font-weight: 700; font-size: 1.05rem;
            box-shadow: 0 2px 8px rgba(221,72,20,.35);
        }
        .navbar-sikada .brand small {
            display: block; font-weight: 400; font-size: 0.72rem;
            color: var(--muted); letter-spacing: 0.04em; text-transform: uppercase;
        }
        .navbar-sikada .nav-right { display: flex; align-items: center; gap: 1.5rem; }
        .navbar-sikada .nav-link-sikada { font-weight: 600; color: var(--ink-soft); font-size: 0.95rem; }
        .navbar-sikada .nav-link-sikada:hover { color: var(--primary); }
        .btn-login {
            background: var(--ink); color: #fff; padding: 0.55rem 1.25rem;
            border-radius: 6px; font-weight: 600; font-size: 0.92rem;
            border: none; transition: all .2s ease;
        }
        .btn-login:hover { background: var(--primary); color: #fff; }

        /* ===== PAGE HEADER ===== */
        .page-hero {
            background: var(--ink);
            color: #fff;
            padding: 3rem 0 2.5rem;
            position: relative;
            overflow: hidden;
        }
        .page-hero::before {
            content: ""; position: absolute; top: 0; right: 0;
            width: 55%; height: 100%;
            background:
                radial-gradient(circle at 80% 20%, rgba(221,72,20,.22), transparent 55%),
                radial-gradient(circle at 95% 75%, rgba(45,108,223,.15), transparent 50%);
            pointer-events: none;
        }
        .page-hero::after {
            content: ""; position: absolute; bottom: -1px; left: 0;
            width: 100%; height: 48px;
            background: var(--paper-2);
            clip-path: polygon(0 100%, 100% 100%, 100% 0, 0 100%);
        }
        .breadcrumb-sikada {
            display: flex; align-items: center; gap: 0.5rem;
            font-size: 0.82rem; color: rgba(255,255,255,.55);
            margin-bottom: 0.85rem; position: relative; z-index: 1;
        }
        .breadcrumb-sikada a { color: rgba(255,255,255,.55); }
        .breadcrumb-sikada a:hover { color: var(--primary); }
        .breadcrumb-sikada .sep { opacity: 0.4; }
        .breadcrumb-sikada .current { color: #fff; font-weight: 600; }
        .page-hero h1 {
            color: #fff; font-size: 2.4rem; font-weight: 700;
            margin-bottom: 0.5rem; position: relative; z-index: 1;
        }
        .page-hero h1 em { font-style: italic; color: var(--primary); }
        .page-hero .lead {
            font-size: 1.05rem; color: rgba(255,255,255,.7);
            max-width: 600px; font-weight: 300;
            position: relative; z-index: 1;
        }

        /* ===== DESA BADGE (when desa is selected) ===== */
        .desa-badge {
            display: inline-flex; align-items: center; gap: 0.6rem;
            background: var(--primary); color: #fff;
            padding: 0.5rem 1rem; border-radius: 8px;
            font-size: 0.95rem; font-weight: 600;
            margin-top: 1rem;
            box-shadow: 0 4px 14px rgba(221,72,20,.25);
            position: relative; z-index: 1;
        }
        .desa-badge a {
            color: rgba(255,255,255,.85); font-size: 0.82rem;
            font-weight: 400; text-decoration: underline;
        }
        .desa-badge a:hover { color: #fff; }

        /* ===== SECTION SHELL ===== */
        section.block { padding: 3rem 0; }
        .section-head {
            display: flex; align-items: baseline; justify-content: space-between;
            gap: 1rem; margin-bottom: 1.75rem;
            padding-bottom: 0.85rem; border-bottom: 2px solid var(--ink);
        }
        .section-head h2 { font-size: 1.6rem; margin: 0; font-weight: 700; }
        .section-head .crumb { font-size: 0.82rem; color: var(--muted); font-weight: 600; letter-spacing: 0.04em; }
        .section-head .crumb strong { color: var(--primary); }

        /* ===== STAT CARDS ===== */
        .stat-grid {
            display: grid; grid-template-columns: repeat(4, 1fr);
            gap: 1rem; margin-bottom: 2.5rem;
        }
        @media (max-width: 992px) { .stat-grid { grid-template-columns: repeat(2, 1fr); } }
        @media (max-width: 576px) { .stat-grid { grid-template-columns: 1fr; } }
        .stat-card {
            background: var(--paper); border: 1px solid var(--border);
            border-left: 4px solid var(--primary); border-radius: 8px;
            padding: 1.25rem 1.4rem; position: relative;
            transition: transform .2s ease, box-shadow .2s ease;
        }
        .stat-card:hover { transform: translateY(-3px); box-shadow: var(--shadow-md); }
        .stat-card .icon { position: absolute; top: 1rem; right: 1.1rem; font-size: 1.4rem; color: var(--paper-3); }
        .stat-card .label {
            font-size: 0.74rem; font-weight: 700; text-transform: uppercase;
            letter-spacing: 0.08em; color: var(--muted); margin-bottom: 0.4rem;
        }
        .stat-card .value {
            font-family: 'Poppins', sans-serif; font-size: 2.1rem; font-weight: 700;
            color: var(--ink); line-height: 1; margin-bottom: 0.35rem;
        }
        .stat-card .sub { font-size: 0.82rem; color: var(--muted); }

        /* ===== INFO STRIP ===== */
        .info-strip {
            background: var(--ink); color: #fff; border-radius: 10px;
            padding: 1.5rem 1.75rem; display: flex; align-items: center;
            gap: 1.25rem; margin: 2rem 0;
        }
        .info-strip .info-icon { font-size: 1.8rem; color: var(--primary); flex-shrink: 0; }
        .info-strip h4 { color: #fff; margin: 0 0 0.2rem; font-size: 1.05rem; }
        .info-strip p { margin: 0; color: rgba(255,255,255,.7); font-size: 0.9rem; }

        /* ===== CHART CARD ===== */
        .chart-card {
            background: var(--paper); border: 1px solid var(--border);
            border-radius: 10px; padding: 1.5rem; box-shadow: var(--shadow-sm);
        }
        .chart-head {
            display: flex; justify-content: space-between; align-items: flex-start;
            gap: 1rem; margin-bottom: 1rem; padding-bottom: 1rem;
            border-bottom: 1px solid var(--border);
        }
        .chart-head h3 { font-size: 1.05rem; margin: 0 0 0.2rem; font-weight: 700; }
        .chart-head p { font-size: 0.84rem; color: var(--muted); margin: 0; }
        .badge-nde {
            font-size: 0.7rem; font-weight: 700; text-transform: uppercase;
            letter-spacing: 0.06em; padding: 0.3rem 0.6rem;
            background: var(--paper-3); color: var(--ink-soft);
            border-radius: 4px; white-space: nowrap;
        }
        .chart-wrap { width: 100%; }
        .zc { width: 100%; min-height: 260px; }
        .zc-pyramid { min-height: 450px; }
        .zc-tall { min-height: 300px; }

        /* ===== DESA TABLE ===== */
        .desa-section {
            background: var(--paper); border: 1px solid var(--border);
            border-radius: 10px; overflow: hidden; box-shadow: var(--shadow-sm);
        }
        .desa-search-bar {
            padding: 1rem 1.25rem; border-bottom: 1px solid var(--border);
            background: var(--paper-2);
        }
        .desa-search-bar input {
            width: 100%; padding: 0.6rem 0.95rem; border: 1px solid var(--border);
            border-radius: 6px; background: var(--paper);
            font-family: inherit; font-size: 0.92rem; color: var(--ink);
        }
        .desa-search-bar input:focus {
            outline: none; border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(221,72,20,.12);
        }
        .table-desa { width: 100%; border-collapse: collapse; }
        .table-desa thead th {
            font-size: 0.72rem; font-weight: 700; text-transform: uppercase;
            letter-spacing: 0.06em; color: var(--muted);
            padding: 0.8rem 1rem; background: var(--paper-2);
            border-bottom: 1px solid var(--border); text-align: left;
        }
        .table-desa thead th.num { text-align: right; }
        .table-desa tbody td {
            padding: 0.85rem 1rem; border-bottom: 1px solid var(--border);
            font-size: 0.92rem; vertical-align: middle;
        }
        .table-desa tbody td.num { text-align: right; font-family: 'Poppins', sans-serif; font-weight: 600; }
        .table-desa tbody tr { cursor: pointer; transition: background .15s ease; }
        .table-desa tbody tr:hover { background: var(--paper-2); }
        .table-desa tbody tr:last-child td { border-bottom: none; }
        .table-desa .desa-link { color: var(--ink); font-weight: 600; }
        .table-desa .desa-link i { color: var(--primary); margin-right: 0.35rem; }
        .table-desa .rt-pill {
            font-size: 0.78rem; padding: 0.15rem 0.5rem;
            background: var(--paper-3); color: var(--ink-soft);
            border-radius: 10px; font-weight: 600;
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
        .kec-card.active {
            border-color: var(--primary); border-width: 2px;
            background: rgba(221,72,20,.05); color: var(--primary);
        }
        .kec-card i { font-size: 1.5rem; margin-bottom: 0.5rem; color: var(--paper-3); }
        .kec-card:hover i, .kec-card.active i { color: var(--primary); }

        /* ===== FOOTER ===== */
        footer.sikada-footer {
            background: var(--ink); color: rgba(255,255,255,.65);
            padding: 3rem 0 1.5rem; margin-top: 3rem;
        }
        footer.sikada-footer h5 { color: #fff; font-size: 1rem; margin-bottom: 1rem; }
        footer.sikada-footer a { color: rgba(255,255,255,.65); }
        footer.sikada-footer a:hover { color: var(--primary); }
        footer.sikada-footer .footer-bottom {
            border-top: 1px solid rgba(255,255,255,.1); padding-top: 1.25rem;
            margin-top: 2rem; font-size: 0.82rem; color: rgba(255,255,255,.45);
            display: flex; justify-content: space-between; flex-wrap: wrap; gap: 0.5rem;
        }
        footer.sikada-footer .brand-mark {
            width: 32px; height: 32px; background: var(--primary); color: #fff;
            border-radius: 6px; display: inline-grid; place-items: center;
            font-family: 'Poppins', sans-serif; font-weight: 700; font-size: 0.85rem;
            margin-bottom: 0.75rem;
        }

        /* ===== EMPTY STATE ===== */
        .empty-state {
            text-align: center; padding: 3rem 1rem; color: var(--muted);
        }
        .empty-state i { font-size: 2.5rem; color: var(--paper-3); margin-bottom: 0.75rem; display: block; }

        @media (max-width: 768px) {
            .page-hero { padding: 2.5rem 0 2rem; }
            .page-hero h1 { font-size: 1.8rem; }
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
                <span>Sikada Tala <small>Kabupaten Tanah Laut</small></span>
            </a>
            <div class="nav-right d-none d-md-flex">
                <a href="<?= site_url('/api/docs') ?>" class="nav-link-sikada"><i class="bi bi-code-slash me-1"></i>API</a>
                <a href="<?= site_url('/login') ?>" class="btn-login"><i class="bi bi-box-arrow-in-right me-1"></i>Masuk</a>
            </div>
            <a href="<?= site_url('/login') ?>" class="btn-login d-md-none"><i class="bi bi-box-arrow-in-right"></i></a>
        </div>
    </nav>

    <!-- ===== PAGE HERO ===== -->
    <header class="page-hero">
        <div class="container position-relative">
            <div class="breadcrumb-sikada">
                <a href="<?= site_url('/') ?>">Beranda</a>
                <span class="sep">/</span>
                <span>Kecamatan</span>
                <span class="sep">/</span>
                <span class="current"><?= esc($kecNama) ?></span>
                <?php if ($selected_desa): ?>
                    <span class="sep">/</span>
                    <span class="current"><?= esc($selected_desa['nama_desa']) ?></span>
                <?php endif; ?>
            </div>
            <h1>Kecamatan <em><?= esc($kecNama) ?></em></h1>
            <p class="lead">Portal data agregat kependudukan tingkat RT di Kecamatan <?= esc($kecNama) ?>, Kabupaten Tanah Laut.</p>
            <?php if ($selected_desa): ?>
                <div class="desa-badge">
                    <i class="bi bi-geo-alt-fill"></i>
                    Desa <?= esc($selected_desa['nama_desa']) ?>
                    <a href="<?= site_url('/' . $kecSlug) ?>">← Lihat semua desa</a>
                </div>
            <?php endif; ?>
        </div>
    </header>

    <main class="container">

        <?php if (!$selected_desa && !empty($desa_stats)): ?>
        <!-- ===== DESA TABLE ===== -->
        <section class="block" style="padding-bottom: 1.5rem;">
            <div class="section-head">
                <h2>Desa di Kecamatan <?= esc($kecNama) ?></h2>
                <span class="crumb">Klik desa untuk melihat detail</span>
            </div>
            <div class="desa-section">
                <div class="desa-search-bar">
                    <input type="text" id="desaSearch" placeholder="Cari nama desa..." autocomplete="off">
                </div>
                <div style="overflow-x: auto;">
                    <table class="table-desa" id="tableDesa">
                        <thead>
                            <tr>
                                <th>Nama Desa</th>
                                <th class="num">Total Jiwa</th>
                                <th class="num">Total KK</th>
                                <th class="num">RT Terlapor</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($desa_stats as $ds): ?>
                            <tr onclick="window.location.href='<?= site_url('/' . $kecSlug . '?desa=' . $slugify($ds['nama_desa'])) ?>'">
                                <td>
                                    <span class="desa-link">
                                        <i class="bi bi-geo-alt"></i><?= esc($ds['nama_desa']) ?>
                                    </span>
                                </td>
                                <td class="num"><?= number_format((int)$ds['total_jiwa_l'] + (int)$ds['total_jiwa_p'], 0, ',', '.') ?></td>
                                <td class="num"><?= number_format((int)$ds['total_kk'], 0, ',', '.') ?></td>
                                <td class="num"><span class="rt-pill"><?= (int)$ds['rt_count'] ?> RT</span></td>
                                <td class="num"><i class="bi bi-chevron-right text-muted"></i></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
        <?php endif; ?>

        <?php if ($hasData): ?>

        <!-- ===== STAT CARDS + INFO STRIP ===== -->
        <section class="block" style="padding-top: 1.5rem;">
            <div class="stat-grid">
                <div class="stat-card">
                    <i class="bi bi-people-fill icon"></i>
                    <div class="label">Total Jiwa</div>
                    <div class="value"><?= number_format($jiwaTotal, 0, ',', '.') ?></div>
                    <div class="sub"><?= number_format($jiwaL, 0, ',', '.') ?> L · <?= number_format($jiwaP, 0, ',', '.') ?> P</div>
                </div>
                <div class="stat-card">
                    <i class="bi bi-house-door-fill icon"></i>
                    <div class="label">Kartu Keluarga</div>
                    <div class="value"><?= number_format($kkTotal, 0, ',', '.') ?></div>
                    <div class="sub"><?= number_format($kkL, 0, ',', '.') ?> L · <?= number_format($kkP, 0, ',', '.') ?> P</div>
                </div>
                <div class="stat-card">
                    <i class="bi bi-balloon-fill icon"></i>
                    <div class="label">Balita</div>
                    <div class="value"><?= number_format($balita, 0, ',', '.') ?></div>
                    <div class="sub">Usia 0-5 tahun</div>
                </div>
                <div class="stat-card">
                    <i class="bi bi-heart-pulse-fill icon"></i>
                    <div class="label">PUS</div>
                    <div class="value"><?= number_format($pus, 0, ',', '.') ?></div>
                    <div class="sub">Pasangan Usia Subur</div>
                </div>
            </div>

            <div class="info-strip">
                <i class="bi bi-info-circle-fill info-icon"></i>
                <div>
                    <h4>Skop data: <strong><?= esc($pendudukKey) ?></strong></h4>
                    <p>Data diturunkan dari laporan agregat RT yang diinput oleh admin desa. Klik desa pada tabel di atas untuk mempersempit lingk data.</p>
                </div>
            </div>
        </section>

        <!-- ===== PIRAMIDA PENDUDUK ===== -->
        <section class="block" style="padding-top: 0;">
            <div class="section-head">
                <h2>Piramida Penduduk</h2>
                <span class="crumb">Sebaran umur · <strong><?= esc($pendudukKey) ?></strong></span>
            </div>
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
        </section>

        <!-- ===== PENDIDIKAN + JKN ===== -->
        <section class="block" style="padding-top: 1.5rem;">
            <div class="section-head">
                <h2>Pendidikan &amp; Kesehatan</h2>
                <span class="crumb">Tingkat pendidikan KK · cakupan JKN</span>
            </div>
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
                                <h3>Cakupan JKN / BPJS</h3>
                                <p>Peserta PBI, non-PBI, dan tidak memiliki JKN</p>
                            </div>
                            <span class="badge-nde">3 kategori</span>
                        </div>
                        <div class="chart-wrap"><div id="zc-jkn" class="zc zc-tall"></div></div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ===== PEKERJAAN ===== -->
        <section class="block" style="padding-top: 1.5rem;">
            <div class="section-head">
                <h2>Pekerjaan</h2>
                <span class="crumb">Bidang pekerjaan KK</span>
            </div>
            <div class="chart-card">
                <div class="chart-head">
                    <div>
                        <h3>Distribusi Pekerjaan Kepala Keluarga</h3>
                        <p>Sebaran bidang pekerjaan pada Kepala Keluarga</p>
                    </div>
                    <span class="badge-nde">8 jenis</span>
                </div>
                <div class="chart-wrap"><div id="zc-pekerjaan" class="zc zc-tall"></div></div>
            </div>
        </section>

        <!-- ===== GENDER ===== -->
        <section class="block" style="padding-top: 1.5rem; padding-bottom: 1rem;">
            <div class="section-head">
                <h2>Komposisi Gender</h2>
                <span class="crumb">Jiwa &amp; KK per jenis kelamin</span>
            </div>
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
        </section>

        <!-- ===== STATUS KAWIN + DOKUMEN ===== -->
        <section class="block" style="padding-top: 1.5rem;">
            <div class="section-head">
                <h2>Status Perkawinan &amp; Dokumen</h2>
                <span class="crumb">Status KK · kepemilikan dokumen kependudukan</span>
            </div>
            <div class="row g-4">
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
        </section>

        <?php else: ?>

        <section class="block">
            <div class="empty-state">
                <i class="bi bi-inbox"></i>
                <h3>Belum ada data laporan</h3>
                <p>Belum ada laporan agregat RT untuk <?= esc($pendudukKey) ?>.</p>
            </div>
        </section>

        <?php endif; ?>

        <!-- ===== JELAJAHI KECAMATAN ===== -->
        <section class="block" style="padding-top: 1.5rem;">
            <div class="section-head">
                <h2>Jelajahi Kecamatan Lain</h2>
                <span class="crumb">11 kecamatan di Kabupaten Tanah Laut</span>
            </div>
            <div class="kec-grid">
                <?php foreach ($all_kecamatan as $k): 
                    $k_slug = $slugify($k['nama_kecamatan']);
                    $is_active = ($k['id_kecamatan'] == $kecamatan['id_kecamatan']);
                ?>
                <a href="<?= site_url('/' . $k_slug) ?>" class="kec-card <?= $is_active ? 'active' : '' ?>">
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
        const C = {
            primary: "#dd4814", ink: "#0f1923", blue: "#2d6cdf", pink: "#d6336c",
            green: "#2f9e44", muted: "#6b7280", paper: "#f7f5f1", border: "#e5e1d8",
            amber: "#f59e0b"
        };
        const PALETTE7 = ["#dd4814","#2d6cdf","#2f9e44","#d6336c","#8b5cf6","#f59e0b","#0d9488"];
        const GUIDE = { "line-color": C.border, "line-style": "dotted" };
        const AX = { "font-color": C.ink, "font-size": 11 };
        const TIP = { borderRadius: 4, padding: 8, "background-color": C.ink, "font-color": "#fff" };

        // ===== 1. PIRAMIDA PENDUDUK =====
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
                    format: "%v",
                    item: Object.assign({}, AX),
                    guide: GUIDE,
                    tick: { "line-color": C.border }
                },
                series: [
                    { text: "Laki-laki",  values: piramidaLneg, "background-color": C.blue },
                    { text: "Perempuan", values: piramidaP,    "background-color": C.pink }
                ]
            }
        });

        // ===== 2. PENDIDIKAN (bar) =====
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
                    tooltip: Object.assign({ text: "%scale-key-text: <strong>%v</strong> KK" }, TIP),
                    "value-box": { text: "%v", "font-color": C.ink, "font-size": 10, placement: "top" },
                    animation: { effect: 2, speed: 500 }
                },
                plotarea: { margin: "dynamic 45 45 50" },
                "scale-x": {
                    values: pendLabels,
                    item: Object.assign({ "font-size": 10 }, AX),
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

        // ===== 3. JKN / BPJS (donut) =====
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

        // ===== 4. PEKERJAAN (hbar) =====
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

        // ===== 5. GENDER (grouped bar) =====
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

        // ===== 6. STATUS PERKAWINAN (donut) =====
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
                    layout: "x4", position: "50% 92%",
                    "background-color": "none", "border-width": 0,
                    marker: { "border-radius": 3 },
                    item: Object.assign({}, AX)
                },
                series: kawinLabels.map((label, i) => ({
                    text: label,
                    values: [kawinValues[i]],
                    "background-color": kawinColors[i]
                }))
            }
        });

        // ===== 7. DOKUMEN KEPENDUDUKAN (hbar) =====
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
                    tooltip: Object.assign({ text: "%scale-key-text: <strong>%v</strong> jiwa" }, TIP),
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

        // ===== DESA TABLE SEARCH =====
        const searchInput = document.getElementById('desaSearch');
        if (searchInput) {
            searchInput.addEventListener('input', function() {
                const q = this.value.toLowerCase();
                document.querySelectorAll('#tableDesa tbody tr').forEach(function(row) {
                    const text = row.textContent.toLowerCase();
                    row.style.display = text.includes(q) ? '' : 'none';
                });
            });
        }
    </script>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
