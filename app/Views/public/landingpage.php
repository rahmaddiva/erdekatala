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
$krsKab        = $krsKab ?? null;
$krsKecamatan  = $krsKecamatan ?? [];
$krsUpdatedAt  = $krsUpdatedAt ?? null;
$krsError      = $krsError ?? null;
$krsFilterKec  = $krsFilterKec ?? null;
// API kirim angka string ber-koma ("5,360")
$krsNum = static function ($v): int {
    return (int) str_replace([',', '.'], '', (string) ($v ?? 0));
};
$krsFmt = static function ($v) use ($krsNum): string {
    return number_format($krsNum($v), 0, ',', '.');
};
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sikada Tala — Sistem Informasi Kependudukan Kabupaten Tanah Laut</title>
    <meta name="description" content="Portal data agregat kependudukan tingkat Desa-Kecamatan di Kabupaten Tanah Laut, Kalimantan Selatan.">
    <link rel="icon" type="image/png" href="<?= base_url('assets/dist/img/SikadaIreng.png') ?>">

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
        .cursor-follower {
            position: fixed;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            border: 2px solid rgba(221, 72, 20, 0.6);
            pointer-events: none;
            z-index: 9999;
            top: 0;
            left: 0;
            opacity: 0;
        }
        .cursor-follower.active { opacity: 1; }
    </style>
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
            height: 38px;
            width: auto;
            display: block;
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
            height: 32px;
            width: auto;
            display: block;
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

        /* ===== KRS TREE TABLE (mirip SIGA PDF) ===== */
        .krs-shell {
            background: var(--paper);
            border: 1px solid var(--border);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: var(--shadow-md);
        }
        .krs-toolbar {
            display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between;
            gap: .75rem; padding: .9rem 1.1rem;
            background: linear-gradient(135deg, #1a2744 0%, #243556 100%);
            color: #fff;
        }
        .krs-toolbar h3 {
            margin: 0; font-size: 1rem; font-weight: 700; letter-spacing: .02em;
        }
        .krs-toolbar .meta {
            font-size: .78rem; opacity: .8;
        }
        .krs-toolbar .badge-live {
            background: rgba(72, 199, 142, .2); color: #7dffb3;
            border: 1px solid rgba(72,199,142,.35);
            border-radius: 999px; padding: .2rem .65rem;
            font-size: .72rem; font-weight: 700; letter-spacing: .04em;
        }
        .krs-scroll { overflow-x: auto; max-height: 70vh; overflow-y: auto; }
        table.krs-tree {
            width: 100%; border-collapse: collapse;
            font-size: .78rem; min-width: 1400px;
            border: 1px solid #b8c4d6;
        }
        table.krs-tree thead th {
            position: sticky; top: 0; z-index: 3;
            background: #e8eef6; color: #1a2744;
            font-weight: 700; font-size: .68rem; text-transform: uppercase;
            letter-spacing: .03em; white-space: nowrap;
            padding: .55rem .45rem;
            border: 1px solid #b8c4d6;
            text-align: center; vertical-align: middle;
        }
        table.krs-tree thead tr.krs-grp th {
            background: #d4deec;
            font-size: .7rem; padding: .5rem .4rem;
            border: 1px solid #a8b6cc;
        }
        table.krs-tree thead tr:nth-child(2) th {
            top: 2rem; /* sticky under group header */
            z-index: 2;
            background: #eef3f9;
        }
        table.krs-tree tbody td {
            padding: .42rem .45rem;
            border: 1px solid #cfd8e6;
            white-space: nowrap; vertical-align: middle;
            font-variant-numeric: tabular-nums;
            background: #fff;
        }
        table.krs-tree tbody td.n { text-align: right; font-weight: 600; color: #243049; }
        table.krs-tree tbody td.wilayah {
            text-align: left; font-weight: 600; color: #1a2744;
            position: sticky; left: 0; z-index: 1;
            min-width: 220px; max-width: 280px;
            border-right: 2px solid #a8b6cc;
            background: #fff;
        }
        table.krs-tree tbody tr:hover td { background: #f5f8fc; }
        table.krs-tree tbody tr:hover td.wilayah { background: #f5f8fc; }
        table.krs-tree tr.krs-kab td { background: #ffe8dc; font-weight: 700; }
        table.krs-tree tr.krs-kab td.wilayah { background: #ffe8dc; color: var(--primary-d); border-right-color: #e0a88a; }
        table.krs-tree tr.krs-kab:hover td,
        table.krs-tree tr.krs-kab:hover td.wilayah { background: #ffdccb; }
        table.krs-tree tr.krs-kec td { background: #f4f7fb; }
        table.krs-tree tr.krs-kec td.wilayah { background: #f4f7fb; padding-left: 1.4rem; }
        table.krs-tree tr.krs-kec:hover td,
        table.krs-tree tr.krs-kec:hover td.wilayah { background: #eaf0f8; }
        table.krs-tree tr.krs-desa td { background: #fff; }
        table.krs-tree tr.krs-desa td.wilayah {
            background: #fff; padding-left: 2.6rem; font-weight: 500; color: #3a4660;
        }
        table.krs-tree tr.krs-desa:hover td,
        table.krs-tree tr.krs-desa:hover td.wilayah { background: #f8fafc; }
        table.krs-tree tr.krs-loading td {
            background: #fafbfd; color: var(--muted); font-style: italic; text-align: left;
            padding-left: 2.6rem; border: 1px solid #cfd8e6;
        }
        table.krs-tree .pct { color: var(--primary); font-weight: 700; }
        /* pemisah tebal antar grup metrik (body: col 1=wilayah, 2–24=metrik) */
        table.krs-tree tbody td:nth-child(5),
        table.krs-tree tbody td:nth-child(9),
        table.krs-tree tbody td:nth-child(12),
        table.krs-tree tbody td:nth-child(15),
        table.krs-tree tbody td:nth-child(18),
        table.krs-tree tbody td:nth-child(19),
        table.krs-tree tbody td:nth-child(21) {
            border-right: 2px solid #9aabc4;
        }
        table.krs-tree thead tr:nth-child(2) th:nth-child(4),
        table.krs-tree thead tr:nth-child(2) th:nth-child(8),
        table.krs-tree thead tr:nth-child(2) th:nth-child(11),
        table.krs-tree thead tr:nth-child(2) th:nth-child(14),
        table.krs-tree thead tr:nth-child(2) th:nth-child(17),
        table.krs-tree thead tr:nth-child(2) th:nth-child(18),
        table.krs-tree thead tr:nth-child(2) th:nth-child(20) {
            border-right: 2px solid #9aabc4;
        }
        .krs-toggle {
            display: inline-flex; align-items: center; justify-content: center;
            width: 1.35rem; height: 1.35rem; margin-right: .4rem;
            border: 1px solid #c5d0e0; border-radius: 4px;
            background: #fff; color: #1a2744; cursor: pointer;
            font-size: .7rem; line-height: 1; vertical-align: middle;
            transition: all .15s ease;
        }
        .krs-toggle:hover { border-color: var(--primary); color: var(--primary); }
        .krs-toggle.open { background: var(--primary); border-color: var(--primary); color: #fff; }
        .krs-toggle.leaf {
            visibility: hidden; pointer-events: none;
        }
        .krs-hint {
            padding: .65rem 1.1rem; font-size: .8rem; color: var(--muted);
            border-top: 1px solid var(--border); background: var(--paper-2);
        }

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
                <img src="<?= base_url('assets/dist/img/SikadaIreng.png') ?>" alt="Sikada Tala" class="brand-mark">
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
            <h1>Sikada Tala — <em>Agregat Data</em><br>tingkat Desa di Kabupaten Tanah Laut</h1>
            <p class="lead">Pemantauan terpadu data jiwa, kartu keluarga, pendidikan, pekerjaan, piramida penduduk, serta cakupan JKN/BPJS dan KB-PUS dari hulu ke hilir, langsung dari lapangan.</p>
            <div class="hero-meta">
                <div class="item">
                    <span class="v">11</span>
                    <span class="l">Kecamatan</span>
                </div>
                <div class="item">
                    <span class="v">Desa</span>
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
                    <p>Data diturunkan dari laporan agregat desa yang telah diinput oleh admin desa. Filter menyesuaikan tampilan seluruh chart di bawah.</p>
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

        <!-- ===== MONITORING VERVAL KRS (tree, mirip SIGA) ===== -->
        <section class="block" style="padding-top: 0;" id="monitoring-krs">
            <div class="section-head">
                <h2>Monitoring VERVAL KRS</h2>
                <span class="crumb">
                    SIGA BKKBN · Tahun 2026 · Tanah Laut
                    <?php if (!empty($krsFilterKec)): ?> · <?= esc($krsFilterKec) ?><?php endif; ?>
                    <?php if (!empty($krsUpdatedAt)): ?> · update <?= esc($krsUpdatedAt) ?><?php endif; ?>
                </span>
            </div>

            <?php if (!empty($krsError)): ?>
                <div class="alert alert-warning py-2 px-3 mb-3" style="font-size:.9rem;">
                    <i class="bi bi-exclamation-triangle me-1"></i><?= esc($krsError) ?>
                </div>
            <?php endif; ?>

            <?php if (empty($krsKecamatan)): ?>
                <p class="text-muted">Data Monitoring KRS belum tersedia.</p>
            <?php else:
                $renderKrsCells = function (array $r) use ($krsFmt, $krsNum): string {
                    $ph = $krsNum($r['pushamilVervalAda'] ?? 0) + $krsNum($r['pushamilVervalBaru'] ?? 0);
                    $bd = $krsNum($r['badutaVervalAda'] ?? 0) + $krsNum($r['badutaVervalBaru'] ?? 0);
                    $bl = $krsNum($r['balitaVervalAda'] ?? 0) + $krsNum($r['balitaVervalBaru'] ?? 0);
                    return
                        '<td class="n">' . $krsFmt($r['kelurahanAda'] ?? 0) . '</td>' .
                        '<td class="n">' . $krsFmt($r['kelurahanTarget'] ?? 0) . '</td>' .
                        '<td class="n">' . $krsFmt($r['kelurahanVerval'] ?? 0) . '</td>' .
                        '<td class="n">' . $krsFmt($r['cakupanKelurahanVerval'] ?? 0) . '%</td>' .
                        '<td class="n">' . $krsFmt($r['pusSasaran'] ?? 0) . '</td>' .
                        '<td class="n">' . $krsFmt($r['pusVervalAda'] ?? 0) . '</td>' .
                        '<td class="n">' . $krsFmt($r['pusVervalBaru'] ?? 0) . '</td>' .
                        '<td class="n">' . $krsFmt($r['pusVervalAdaBaru'] ?? 0) . '</td>' .
                        '<td class="n">' . $krsFmt($r['pusHamilSasaran'] ?? 0) . '</td>' .
                        '<td class="n">' . $krsFmt($r['pushamilVervalAda'] ?? 0) . '</td>' .
                        '<td class="n">' . $krsFmt($r['pushamilVervalBaru'] ?? 0) . '</td>' .
                        '<td class="n">' . $krsFmt($r['badutaSasaran'] ?? 0) . '</td>' .
                        '<td class="n">' . $krsFmt($r['badutaVervalAda'] ?? 0) . '</td>' .
                        '<td class="n">' . $krsFmt($r['badutaVervalBaru'] ?? 0) . '</td>' .
                        '<td class="n">' . $krsFmt($r['balitaSasaran'] ?? 0) . '</td>' .
                        '<td class="n">' . $krsFmt($r['balitaVervalAda'] ?? 0) . '</td>' .
                        '<td class="n">' . $krsFmt($r['balitaVervalBaru'] ?? 0) . '</td>' .
                        '<td class="n">' . $krsFmt($r['keluargaSasaranCatin'] ?? 0) . '</td>' .
                        '<td class="n">' . $krsFmt($r['sasaranPrioritas'] ?? 0) . '</td>' .
                        '<td class="n">' . $krsFmt($r['prioritasTerverval'] ?? 0) . '</td>' .
                        '<td class="n">' . $krsFmt($r['totalSasaran'] ?? 0) . '</td>' .
                        '<td class="n">' . $krsFmt($r['totalVervalAdaBaru'] ?? 0) . '</td>' .
                        '<td class="n pct">' . esc(($r['persenTarget'] ?? '0') . '%') . '</td>';
                };
                $colspan = 24; // wilayah + 23 metrik
            ?>
            <div class="krs-shell">
                <div class="krs-toolbar">
                    <div>
                        <h3 style="color:#ffffff;">Monitoring VERVAL KRS — Kabupaten Tanah Laut</h3>
                        <div class="meta">Provinsi Kalimantan Selatan · Tahun 2026 · klik ▶ untuk expand desa</div>
                    </div>
                    <span class="badge-live">LIVE MIRROR</span>
                </div>
                <div class="krs-scroll">
                    <table class="krs-tree" id="krs-tree">
                        <thead>
                            <tr class="krs-grp">
                                <th rowspan="2">Provinsi / Kab / Kec / Desa</th>
                                <th colspan="4">Desa / Kelurahan</th>
                                <th colspan="4">PUS</th>
                                <th colspan="3">PUS Hamil</th>
                                <th colspan="3">Baduta</th>
                                <th colspan="3">Balita</th>
                                <th colspan="1">Catin</th>
                                <th colspan="2">Prioritas</th>
                                <th colspan="3">Total</th>
                            </tr>
                            <tr>
                                <th>Ada</th><th>Target</th><th>Verval</th><th>Cakupan</th>
                                <th>Sasaran</th><th>Ada</th><th>Baru</th><th>Ada+Baru</th>
                                <th>Sasaran</th><th>Ada</th><th>Baru</th>
                                <th>Sasaran</th><th>Ada</th><th>Baru</th>
                                <th>Sasaran</th><th>Ada</th><th>Baru</th>
                                <th>Sasaran</th>
                                <th>Sasaran</th><th>Terverval</th>
                                <th>Sasaran</th><th>Verval</th><th>% Target</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($krsKab): ?>
                            <tr class="krs-kab" data-level="kab">
                                <td class="wilayah">
                                    <button type="button" class="krs-toggle open" data-role="kab" aria-label="Toggle kabupaten">▼</button>
                                    TANAH LAUT
                                </td>
                                <?= $renderKrsCells($krsKab) ?>
                            </tr>
                            <?php endif; ?>

                            <?php foreach ($krsKecamatan as $i => $kec):
                                $bkkbnId = (int) ($kec['bkkbn_id'] ?? 0);
                                $rowId = 'kec-' . $bkkbnId . '-' . $i;
                            ?>
                            <tr class="krs-kec" data-level="kec" data-bkkbn="<?= $bkkbnId ?>" data-row="<?= $rowId ?>" id="<?= $rowId ?>">
                                <td class="wilayah">
                                    <?php if ($bkkbnId): ?>
                                    <button type="button" class="krs-toggle" data-role="kec" data-bkkbn="<?= $bkkbnId ?>" data-parent="<?= $rowId ?>" aria-label="Expand desa">▶</button>
                                    <?php else: ?>
                                    <span class="krs-toggle leaf">·</span>
                                    <?php endif; ?>
                                    <?= esc($kec['namaDaerah'] ?? '-') ?>
                                </td>
                                <?= $renderKrsCells($kec) ?>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="krs-hint">
                    Sumber data: SIGA BKKBN (live, cache 30 menit). Struktur mirip portal Monitoring VERVAL KRS.
                </div>
            </div>
            <?php endif; ?>
        </section>

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
                    <img src="<?= base_url('assets/dist/img/Sikadaputih.png') ?>" alt="Sikada Tala" class="brand-mark">
                    <h5>Sikada Tala</h5>
                    <p style="font-size: 0.88rem; color: rgba(255,255,255,.55);">
                        Sistem informasi kependudukan agregat berbasis Desa-Kecamatan untuk Pemerintah Kabupaten Tanah Laut, Kalimantan Selatan.
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
                        Data pada portal ini bersifat agregat dan diperbarui oleh admin desa setiap periode pelaporan.
                    </p>
                </div>
            </div>
            <div class="footer-bottom">
                <span>&copy; <?= date('Y') ?> Pemerintah Kabupaten Tanah Laut.</span>
                <span>Desa-Kecamatan-Laporan-Agregat</span>
            </div>
        </div>
    </footer>

    <!-- ===== ZINGCHART CONFIGS ===== -->
    <script>
        // ponytail: window-scope so DT ready() block can call these
        const rendered = new Set();
        let renderPiramida, renderSosio, renderKesehatan;

        document.addEventListener("DOMContentLoaded", function () {
        // Palette konsisten
        const C = {
            primary: "#dd4814",
            ink:     "#0f1923",
            blue:    "#2d6cdf",
            pink:    "#d6336c",
            green:   "#2f9e44",
            amber:   "#f59e0b",
            muted:   "#6b7280",
            paper:   "#f7f5f1",
            border:  "#e5e1d8"
        };
        const PALETTE7 = ["#dd4814","#2d6cdf","#2f9e44","#d6336c","#8b5cf6","#f59e0b","#0d9488"];
        const GUIDE = { "line-color": C.border, "line-style": "dotted" };
        const AX = { "font-color": C.ink, "font-size": 11 };
        const TIP = { borderRadius: 4, padding: 8, "background-color": C.ink, "font-color": "#fff" };

        // Data untuk chart yang di-defer (disiapkan di sini agar tersedia saat tab dibuka)
        const ageLabels    = <?= json_encode($ageLabels) ?>;
        const piramidaL    = <?= json_encode(array_map('intval', $piramidaL)) ?>;
        const piramidaP    = <?= json_encode(array_map('intval', $piramidaP)) ?>;
        const piramidaLneg = piramidaL.map(v => -1 * v);
        const pendLabels   = <?= json_encode(array_keys($pendidikan)) ?>;
        const pendValues   = <?= json_encode(array_map('intval', array_values($pendidikan))) ?>;
        const jknLabels    = <?= json_encode(array_keys($jkn_bpjs)) ?>;
        const jknValues    = <?= json_encode(array_map('intval', array_values($jkn_bpjs))) ?>;
        const pkrLabels    = <?= json_encode(array_keys($pekerjaan)) ?>;
        const pkrValues    = <?= json_encode(array_map('intval', array_values($pekerjaan))) ?>;
        const kawinLabels  = <?= json_encode(array_keys($status_kawin)) ?>;
        const kawinValues  = <?= json_encode(array_map('intval', array_values($status_kawin))) ?>;
        const kawinColors  = [C.green, C.blue, C.amber, C.primary];
        const dokLabels    = <?= json_encode(array_keys($dokumen)) ?>;
        const dokValues    = <?= json_encode(array_map('intval', array_values($dokumen))) ?>;

        // ============ TAB POKOK — render langsung (tab aktif) ============
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

        // ============ LAZY RENDER: hanya saat tab pertama kali dibuka ============
        renderPiramida = function () {
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
        }

        renderSosio = function () {
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
        }

        renderKesehatan = function () {
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
        }
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
        // ponytail: Set tracks first-render per tab; resize on subsequent opens
        const tabEl = document.getElementById('dataTabs');
        if (tabEl) {
            tabEl.addEventListener('shown.bs.tab', function (e) {
                const key = e.target.getAttribute('data-bs-target').replace('#tab-', '');
                if (dts[key]) { dts[key].columns.adjust(); if (dts[key].responsive) dts[key].responsive.rebuild(); }
                if (key === 'piramida') {
                    if (!rendered.has('piramida')) { renderPiramida(); rendered.add('piramida'); }
                    else { try { zingchart.exec('zc-piramida', 'resize'); } catch(e){} }
                } else if (key === 'sosio') {
                    if (!rendered.has('sosio')) { renderSosio(); rendered.add('sosio'); }
                    else { ['zc-pendidikan','zc-kawin','zc-pekerjaan'].forEach(id => { try { zingchart.exec(id,'resize'); } catch(e){} }); }
                } else if (key === 'kesehatan') {
                    if (!rendered.has('kesehatan')) { renderKesehatan(); rendered.add('kesehatan'); }
                    else { ['zc-jkn','zc-dokumen'].forEach(id => { try { zingchart.exec(id,'resize'); } catch(e){} }); }
                } else if (key === 'pokok') {
                    try { zingchart.exec('zc-gender', 'resize'); } catch(e) {}
                }
            });
        }

        // ===== KRS tree expand (lazy-load desa) =====
        const krsDesaUrl = '<?= site_url('public/krs-desa') ?>';
        const krsLoaded = new Set();

        function krsNum(v) {
            return parseInt(String(v ?? 0).replace(/[.,]/g, ''), 10) || 0;
        }
        function krsFmt(v) {
            return krsNum(v).toLocaleString('id-ID');
        }
        function krsCells(r) {
            return [
                r.kelurahanAda, r.kelurahanTarget, r.kelurahanVerval,
                (r.cakupanKelurahanVerval ?? 0) + '%',
                r.pusSasaran, r.pusVervalAda, r.pusVervalBaru, r.pusVervalAdaBaru,
                r.pusHamilSasaran, r.pushamilVervalAda, r.pushamilVervalBaru,
                r.badutaSasaran, r.badutaVervalAda, r.badutaVervalBaru,
                r.balitaSasaran, r.balitaVervalAda, r.balitaVervalBaru,
                r.keluargaSasaranCatin, r.sasaranPrioritas, r.prioritasTerverval,
                r.totalSasaran, r.totalVervalAdaBaru, (r.persenTarget ?? '0') + '%'
            ].map((v, i) => {
                const cls = (i === 22) ? 'n pct' : 'n';
                const val = (typeof v === 'string' && v.includes('%')) ? v : krsFmt(v);
                return `<td class="${cls}">${val}</td>`;
            }).join('');
        }

        document.querySelectorAll('#krs-tree .krs-toggle[data-role="kec"]').forEach(btn => {
            btn.addEventListener('click', async function () {
                const bkkbn = this.dataset.bkkbn;
                const parentId = this.dataset.parent;
                const parentRow = document.getElementById(parentId);
                if (!parentRow || !bkkbn) return;

                const open = this.classList.contains('open');
                if (open) {
                    // collapse children
                    document.querySelectorAll(`tr[data-parent="${parentId}"]`).forEach(tr => tr.remove());
                    this.classList.remove('open');
                    this.textContent = '▶';
                    return;
                }

                this.classList.add('open');
                this.textContent = '▼';

                if (krsLoaded.has(parentId)) {
                    // re-fetch still fine; mark as loading only first time
                }

                // loading row
                const loadTr = document.createElement('tr');
                loadTr.className = 'krs-loading';
                loadTr.dataset.parent = parentId;
                loadTr.innerHTML = `<td class="wilayah" colspan="24"><i class="bi bi-arrow-repeat"></i> Memuat desa…</td>`;
                parentRow.after(loadTr);

                try {
                    const res = await fetch(krsDesaUrl + '/' + bkkbn, { headers: { 'Accept': 'application/json' } });
                    const json = await res.json();
                    loadTr.remove();
                    const rows = json.rows || [];
                    if (!rows.length) {
                        const empty = document.createElement('tr');
                        empty.className = 'krs-loading';
                        empty.dataset.parent = parentId;
                        empty.innerHTML = `<td class="wilayah" colspan="24">Tidak ada data desa.</td>`;
                        parentRow.after(empty);
                        return;
                    }
                    let anchor = parentRow;
                    rows.forEach(r => {
                        const tr = document.createElement('tr');
                        tr.className = 'krs-desa';
                        tr.dataset.parent = parentId;
                        tr.dataset.level = 'desa';
                        tr.innerHTML = `<td class="wilayah"><span class="krs-toggle leaf">·</span>${(r.namaDaerah || '-').replace(/</g,'&lt;')}</td>${krsCells(r)}`;
                        anchor.after(tr);
                        anchor = tr;
                    });
                    krsLoaded.add(parentId);
                } catch (e) {
                    loadTr.innerHTML = `<td class="wilayah" colspan="24">Gagal memuat data desa.</td>`;
                }
            });
        });

        // toggle kab: hide/show kecamatan rows
        const kabBtn = document.querySelector('#krs-tree .krs-toggle[data-role="kab"]');
        if (kabBtn) {
            kabBtn.addEventListener('click', function () {
                const open = this.classList.contains('open');
                const show = !open;
                this.classList.toggle('open', show);
                this.textContent = show ? '▼' : '▶';
                document.querySelectorAll('#krs-tree tr.krs-kec, #krs-tree tr.krs-desa, #krs-tree tr.krs-loading').forEach(tr => {
                    tr.style.display = show ? '' : 'none';
                });
            });
        }

        // auto-expand first kecamatan if filter aktif (1 row)
        <?php if (!empty($krsFilterKec) && count($krsKecamatan) === 1): ?>
        const autoBtn = document.querySelector('#krs-tree .krs-toggle[data-role="kec"]');
        if (autoBtn) autoBtn.click();
        <?php endif; ?>
    });
    </script>
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
