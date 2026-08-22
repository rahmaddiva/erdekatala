<?php
// Normalisasi data untuk tampilan
$jiwaL      = (int)($totals['jiwa_l'] ?? 0);
$jiwaP      = (int)($totals['jiwa_p'] ?? 0);
$jiwaTotal  = $jiwaL + $jiwaP;
$kkL        = (int)($totals['kk_l'] ?? 0);
$kkP        = (int)($totals['kk_p'] ?? 0);
$kkTotal    = $kkL + $kkP;
$balita     = (int)($totals['balita'] ?? 0);
$remaja     = (int)($totals['remaja'] ?? 0);
$lansia     = (int)($totals['lansia'] ?? 0);
$pus        = (int)($totals['pus'] ?? 0);
$kbAktif    = (int)($totals['kb_aktif'] ?? 0);
$alatKontra = (int)($totals['alat_kontrasepsi'] ?? 0);

$kecNama   = $kecamatan['nama_kecamatan'] ?? '';
$kecSlug   = $kecamatan['slug'] ?: strtolower(str_replace(' ', '-', $kecNama));
$kecTitle  = $kecamatan['page_title'] ?: 'Kecamatan ' . $kecNama;
$kecMeta   = $kecamatan['meta_description'] ?: 'Data agregat kependudukan Kecamatan ' . $kecNama . ', Kabupaten Tanah Laut, Kalimantan Selatan.';
$pendudukKey = esc($kecNama);

// Kode 3 digit kecamatan untuk file GeoJSON (mis. "63.01.04" -> "004")
$kodeSeg = explode('.', (string) ($kecamatan['kode_kecamatan'] ?? ''));
$kecKode = str_pad((string) (end($kodeSeg) ?: ''), 3, '0', STR_PAD_LEFT);

$hasData = $jiwaTotal > 0;

$fmt = function($n) { return number_format((int)$n, 0, ',', '.'); };
$pendTotal  = array_sum($pendidikan);
$pekTotal   = array_sum($pekerjaan);
$kawinTotal = array_sum($status_kawin);
$jknTotal   = array_sum($jkn_bpjs);
$dokTotal   = array_sum($dokumen);

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
    <title><?= esc($kecTitle) ?> - Erdekatala</title>
    <meta name="description" content="<?= esc($kecMeta) ?>">
    <link rel="icon" type="image/png" href="<?= base_url('assets/dist/img/erdekatala.png') ?>">
    <link rel="shortcut icon" href="<?= base_url('favicon.ico') ?>">

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

    <!-- ZingChart -->
    <script src="https://cdn.zingchart.com/zingchart.min.js"></script>

    <!-- Leaflet (peta sebaran per desa, file lokal di public/leaflet) -->
    <link rel="stylesheet" href="<?= base_url('leaflet/leaflet.css') ?>">

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
            background: linear-gradient(135deg, var(--ink) 0%, #1a2e3e 100%);
            color: #fff;
            padding: 3.5rem 0 3rem;
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
        .stat-card .icon-circle {
            position: absolute; top: 1rem; right: 1.1rem;
            width: 38px; height: 38px; border-radius: 50%;
            display: grid; place-items: center; font-size: 1.15rem;
        }
        .stat-card .icon-circle.tone-primary { background: rgba(221,72,20,.12); color: var(--primary); }
        .stat-card .icon-circle.tone-blue    { background: rgba(45,108,223,.12); color: var(--accent-l); }
        .stat-card .icon-circle.tone-pink    { background: rgba(214,51,108,.12); color: var(--accent-p); }
        .stat-card .icon-circle.tone-green   { background: rgba(47,158,68,.12); color: var(--good); }
        .stat-card .icon-circle.tone-orange  { background: rgba(221,72,20,.12); color: var(--primary); }

        /* ===== PROFIL KECAMATAN ===== */
        .profil-card {
            background: var(--paper); border: 1px solid var(--border);
            border-radius: 10px; padding: 1.5rem 1.75rem; box-shadow: var(--shadow-sm);
        }
        .profil-card .profil-desc {
            font-size: 0.95rem; line-height: 1.7; color: var(--ink-soft); margin: 0 0 1.25rem;
        }
        .profil-grid {
            display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 0.9rem 1.5rem;
        }
        .profil-field {
            display: flex; align-items: flex-start; gap: 0.75rem;
            background: var(--paper-2); border: 1px solid var(--border);
            border-radius: 8px; padding: 0.75rem 0.9rem;
        }
        .profil-field.full { grid-column: 1 / -1; }
        .profil-field .pf-icon {
            width: 34px; height: 34px; border-radius: 50%; flex-shrink: 0;
            display: grid; place-items: center; margin-top: 0.15rem;
            background: rgba(221,72,20,.1); color: var(--primary);
        }
        .profil-field .pf-label {
            font-size: 0.7rem; font-weight: 700; text-transform: uppercase;
            letter-spacing: 0.06em; color: var(--muted); margin: 0 0 0.15rem;
        }
        .profil-field .pf-value {
            font-size: 0.93rem; font-weight: 600; color: var(--ink); margin: 0; line-height: 1.5;
        }
        .profil-photo {
            border-radius: 10px; overflow: hidden; box-shadow: var(--shadow-md); border: 1px solid var(--border);
        }
        .profil-photo img {
            width: 100%; aspect-ratio: 4/3; object-fit: cover; display: block;
        }
        .profil-photo-placeholder {
            border-radius: 10px; border: 1.5px dashed var(--border);
            background: linear-gradient(135deg, var(--paper), var(--paper-3));
            display: grid; place-items: center; min-height: 260px;
        }
        .profil-photo-placeholder i { font-size: 3rem; color: var(--paper-3); }
        .profil-photo-placeholder p { margin: 0.5rem 0 0; font-size: 0.85rem; color: var(--muted); }

        /* ===== INFO STRIP ===== */
        .info-strip {
            background: var(--ink); color: #fff; border-radius: 10px;
            padding: 1.5rem 1.75rem; display: flex; align-items: center;
            gap: 1.25rem; margin: 2rem 0;
        }
        .info-strip .info-icon {
            width: 46px; height: 46px; border-radius: 50%; flex-shrink: 0;
            display: grid; place-items: center; font-size: 1.3rem;
            background: rgba(255,255,255,.1); color: var(--primary);
        }
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

        /* ===== TABS + DATA TABLE ===== */
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

        /* ===== DESA TABLE ===== */
        .desa-section {
            background: var(--paper); border: 1px solid var(--border);
            border-radius: 10px; overflow: hidden; box-shadow: var(--shadow-sm);
        }
        .desa-search-bar {
            display: flex; align-items: center; gap: 0.5rem;
            padding: 1rem 1.25rem; border-bottom: 1px solid var(--border);
            background: var(--paper-2);
        }
        .desa-search-bar .search-icon {
            color: var(--muted); font-size: 1.1rem; flex-shrink: 0;
        }
        .desa-search-bar input {
            flex: 1; padding: 0.6rem 0.95rem; border: 1px solid var(--border);
            border-radius: 6px; background: var(--paper);
            font-family: inherit; font-size: 0.92rem; color: var(--ink);
        }
        .desa-search-bar input:focus {
            outline: none; border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(221,72,20,.12);
        }
        .table-desa { width: 100%; border-collapse: collapse; }
        .table-desa .thead-wrap { overflow-x: auto; }
        .table-desa thead th {
            position: sticky; top: 0; z-index: 1;
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
    <style>
        /* ===== PETA SEBARAN PER DESA (Leaflet) ===== */
        .map-shell {
            background: #fff;
            border: 1px solid var(--border, #e5e1d8);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 16px rgba(15,25,35,.06), 0 2px 4px rgba(15,25,35,.04);
        }
        #map-desa {
            height: 480px;
            width: 100%;
            position: relative;
            z-index: 0;
            background: #f7f5f1;
        }
        .map-indicator-bar {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: .5rem;
            padding: .8rem 1.1rem;
            background: var(--paper, #ffffff);
            border-bottom: 1px solid var(--border, #e5e1d8);
        }
        .map-indicator-bar label {
            margin: 0;
            font-size: .82rem;
            font-weight: 600;
            color: var(--ink-soft, #1e2d3a);
        }
        .map-indicator-bar select {
            font-family: inherit;
            font-size: .82rem;
            color: var(--ink, #0f1923);
            background: var(--paper-2, #f7f5f1);
            border: 1px solid var(--border, #e5e1d8);
            border-radius: 8px;
            padding: .4rem 2.1rem .4rem .75rem;
            min-width: 230px;
            cursor: pointer;
            outline: none;
            transition: border-color .15s ease;
        }
        .map-indicator-bar select:hover,
        .map-indicator-bar select:focus { border-color: var(--primary, #dd4814); }
        .map-indicator-bar .lg-label-ind {
            margin-left: auto;
            font-size: .78rem;
            color: var(--muted, #6b7280);
            font-style: italic;
        }
        .leaflet-container {
            background: #f7f5f1;
            font-family: inherit;
        }
        .map-shell .map-legend {
            display: flex;
            flex-wrap: wrap;
            gap: 1.25rem;
            align-items: center;
            padding: .8rem 1.1rem;
            background: #fff;
            border-top: 1px solid #e5e1d8;
            font-size: .8rem;
            color: #1e2d3a;
        }
        .map-shell .map-legend .swatch {
            display: inline-block;
            width: 13px; height: 13px;
            border-radius: 3px;
            margin-right: .35rem;
            vertical-align: middle;
            border: 1px solid rgba(15,25,35,.08);
        }
        .map-shell .map-legend .lg-item {
            display: inline-flex;
            align-items: center;
        }
        .map-shell .map-legend .lg-item b {
            font-weight: 600;
            color: #0f1923;
            font-variant-numeric: tabular-nums;
        }
        .map-shell .map-legend .lg-sep {
            width: 1px; height: 16px;
            background: #e5e1d8;
        }
        .map-popup-desa { font-family: 'Poppins', sans-serif; }
        .map-popup-desa .pk-name {
            font-weight: 700; color: #0f1923; font-size: .95rem;
            margin-bottom: .35rem;
        }
        .map-popup-desa .pk-row {
            display: flex; justify-content: space-between; gap: 1rem;
            font-size: .82rem; color: #1e2d3a;
            padding: .12rem 0;
        }
        .map-popup-desa .pk-row b { color: #0f1923; font-variant-numeric: tabular-nums; }
        .map-popup-desa .pk-link {
            display: block; margin-top: .45rem;
            font-size: .8rem; font-weight: 600; color: #dd4814;
        }
        .map-popup-desa .pk-link:hover { color: #b0380f; }
        .leaflet-popup-content a { color: #dd4814; }
        .map-placeholder {
            height: 480px;
            display: flex; align-items: center; justify-content: center;
            background: #f7f5f1;
            color: #6b7280;
            font-size: .95rem;
            border-radius: 12px;
        }
    </style>
</head>

<body>

    <!-- ===== NAVBAR ===== -->
    <nav class="navbar-sikada">
        <div class="container d-flex align-items-center justify-content-between">
            <a href="<?= site_url('/') ?>" class="brand">
                <span class="brand-mark">S</span>
                <span>Erdekatala <small>Kabupaten Tanah Laut</small></span>
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
            <h1><?= esc($kecTitle) ?></h1>
            <p class="lead">Portal data agregat kependudukan tingkat Desa di Kecamatan <?= esc($kecNama) ?>, Kabupaten Tanah Laut.</p>
            <?php if ($selected_desa): ?>
                <div class="desa-badge">
                    <i class="bi bi-geo-alt-fill"></i>
                    Desa <?= esc($selected_desa['nama_desa']) ?>
                    <a href="<?= site_url('/' . $kecSlug) ?>">← Lihat semua desa</a>
                </div>
            <?php endif; ?>
        </div>
    </header>

    <?php
    $hasProfile = !empty($kecamatan['deskripsi']) || !empty($kecamatan['foto'])
        || !empty($kecamatan['nama_camat']) || !empty($kecamatan['alamat_kantor'])
        || !empty($kecamatan['telepon']) || !empty($kecamatan['email'])
        || !empty($kecamatan['jam_layanan']);
    ?>
    <?php if ($hasProfile && !$selected_desa): ?>
    <!-- ===== PROFIL KECAMATAN ===== -->
    <section class="block" style="padding-top: 2rem;">
        <div class="section-head">
            <h2>Profil Kecamatan</h2>
            <span class="crumb">Informasi resmi kecamatan</span>
        </div>
        <div class="profil-grid-wrap" style="display: grid; grid-template-columns: minmax(0, 380px) 1fr; gap: 1.5rem; align-items: start;">
            <?php if (!empty($kecamatan['foto']) && file_exists(FCPATH . $kecamatan['foto'])): ?>
                <div class="profil-photo">
                    <img src="<?= base_url($kecamatan['foto']) ?>" alt="Foto Kecamatan <?= esc($kecNama) ?>">
                </div>
            <?php else: ?>
                <div class="profil-photo-placeholder">
                    <div style="text-align: center;">
                        <i class="bi bi-building"></i>
                        <p>Kantor Kecamatan <?= esc($kecNama) ?></p>
                    </div>
                </div>
            <?php endif; ?>
            <div class="profil-card">
                <?php if (!empty($kecamatan['deskripsi'])): ?>
                    <p class="profil-desc"><?= nl2br(esc($kecamatan['deskripsi'])) ?></p>
                <?php endif; ?>
                <div class="profil-grid">
                    <?php if (!empty($kecamatan['nama_camat'])): ?>
                    <div class="profil-field">
                        <div class="pf-icon"><i class="bi bi-person-badge"></i></div>
                        <div>
                            <p class="pf-label">Camat</p>
                            <p class="pf-value"><?= esc($kecamatan['nama_camat']) ?></p>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($kecamatan['jam_layanan'])): ?>
                    <div class="profil-field">
                        <div class="pf-icon"><i class="bi bi-clock"></i></div>
                        <div>
                            <p class="pf-label">Jam Layanan</p>
                            <p class="pf-value"><?= esc($kecamatan['jam_layanan']) ?></p>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($kecamatan['telepon'])): ?>
                    <div class="profil-field">
                        <div class="pf-icon"><i class="bi bi-telephone"></i></div>
                        <div>
                            <p class="pf-label">Telepon</p>
                            <p class="pf-value"><?= esc($kecamatan['telepon']) ?></p>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($kecamatan['email'])): ?>
                    <div class="profil-field">
                        <div class="pf-icon"><i class="bi bi-envelope"></i></div>
                        <div>
                            <p class="pf-label">Email</p>
                            <p class="pf-value"><?= esc($kecamatan['email']) ?></p>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($kecamatan['alamat_kantor'])): ?>
                    <div class="profil-field full">
                        <div class="pf-icon"><i class="bi bi-geo-alt"></i></div>
                        <div>
                            <p class="pf-label">Alamat Kantor</p>
                            <p class="pf-value"><?= nl2br(esc($kecamatan['alamat_kantor'])) ?></p>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <style>
        @media (max-width: 768px) {
            .profil-grid-wrap { grid-template-columns: 1fr !important; }
        }
        </style>
    </section>
    <?php endif; ?>

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
                    <i class="bi bi-search search-icon"></i>
                    <input type="text" id="desaSearch" placeholder="Cari nama desa..." autocomplete="off">
                </div>
                <div style="overflow-x: auto;">
                    <table class="table-desa" id="tableDesa">
                        <thead>
                            <tr>
                                <th>Nama Desa</th>
                                <th class="num">Total Jiwa</th>
                                <th class="num">Total KK</th>
                                <th class="num">Laporan</th>
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
                                <td class="num"><span class="rt-pill"><?= (int)$ds['rt_count'] ?> laporan</span></td>
                                <td class="num"><i class="bi bi-chevron-right text-muted"></i></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
        <?php endif; ?>

        <?php $mapIndicatorGroups = include APPPATH . 'Views/public/partials/map_indicators.php'; ?>

        <!-- ===== PETA SEBARAN PER DESA (Leaflet) ===== -->
        <section class="block" style="padding-bottom: 1.5rem;">
            <div class="section-head">
                <h2>Peta Sebaran Penduduk per Desa</h2>
                <span class="crumb">Indikator agregat per desa di Kecamatan <?= esc($kecNama) ?></span>
            </div>
            <div class="map-shell">
                <div class="map-indicator-bar">
                    <label for="ind-desa-select">Indikator:</label>
                    <select id="ind-desa-select">
                        <?php foreach ($mapIndicatorGroups as $grup): ?>
                        <optgroup label="<?= esc($grup['group']) ?>">
                            <?php foreach ($grup['items'] as $it): ?>
                            <option value="<?= esc($it['field']) ?>"><?= esc($it['label']) ?></option>
                            <?php endforeach; ?>
                        </optgroup>
                        <?php endforeach; ?>
                    </select>
                    <span class="lg-label-ind" id="lg-indicator-name-desa"></span>
                </div>
                <div id="map-desa"></div>
                <div class="map-legend" id="map-legend-desa">
                    <span class="lg-item"><span class="swatch" style="background:#2d6cdf;"></span>Desa terpilih</span>
                    <span class="lg-item"><span class="swatch" style="background:#e5e1d8;"></span>Tidak ada data</span>
                    <span class="lg-sep"></span>
                    <span class="lg-item lg-ind-label"></span>
                    <span class="lg-item" id="dlg-0"><span class="swatch"></span><b></b></span>
                    <span class="lg-item" id="dlg-1"><span class="swatch"></span><b></b></span>
                    <span class="lg-item" id="dlg-2"><span class="swatch"></span><b></b></span>
                    <span class="lg-item" id="dlg-3"><span class="swatch"></span><b></b></span>
                    <span class="lg-item" id="dlg-4"><span class="swatch"></span><b></b></span>
                    <span class="ms-auto text-muted">Klik poligon untuk detail</span>
                </div>
            </div>
        </section>

        <?php if ($hasData): ?>

        <!-- ===== STAT CARDS + INFO STRIP ===== -->
        <section class="block" style="padding-top: 1.5rem;">
            <div class="stat-grid">
                <div class="stat-card">
                    <div class="icon-circle tone-primary"><i class="bi bi-people-fill"></i></div>
                    <div class="label">Total Jiwa</div>
                    <div class="value"><?= number_format($jiwaTotal, 0, ',', '.') ?></div>
                    <div class="sub"><?= number_format($jiwaL, 0, ',', '.') ?> L · <?= number_format($jiwaP, 0, ',', '.') ?> P</div>
                </div>
                <div class="stat-card alt-blue">
                    <div class="icon-circle tone-blue"><i class="bi bi-house-door-fill"></i></div>
                    <div class="label">Kartu Keluarga</div>
                    <div class="value"><?= number_format($kkTotal, 0, ',', '.') ?></div>
                    <div class="sub"><?= number_format($kkL, 0, ',', '.') ?> L · <?= number_format($kkP, 0, ',', '.') ?> P</div>
                </div>
                <div class="stat-card alt-pink">
                    <div class="icon-circle tone-pink"><i class="bi bi-balloon-fill"></i></div>
                    <div class="label">Balita</div>
                    <div class="value"><?= number_format($balita, 0, ',', '.') ?></div>
                    <div class="sub">Usia 0-5 tahun</div>
                </div>
                <div class="stat-card alt-green">
                    <div class="icon-circle tone-green"><i class="bi bi-heart-pulse-fill"></i></div>
                    <div class="label">PUS</div>
                    <div class="value"><?= number_format($pus, 0, ',', '.') ?></div>
                    <div class="sub">Pasangan Usia Subur</div>
                </div>
            </div>

            <div class="info-strip">
                <i class="bi bi-info-circle-fill info-icon"></i>
                <div>
                    <h4>Skop data: <strong><?= esc($pendudukKey) ?></strong></h4>
                    <p>Data diturunkan dari laporan agregat desa yang diinput oleh admin desa. Klik desa pada tabel di atas untuk mempersempit lingkup data.</p>
                </div>
            </div>
        </section>

        <!-- ===== TABS: CHART + DATATABLE ===== -->
        <section class="block" style="padding-top: 0;">
            <div class="section-head">
                <h2>Data Agregat Kependudukan</h2>
                <span class="crumb">Sebaran data · <strong><?= esc($pendudukKey) ?></strong></span>
            </div>
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

        <?php else: ?>

        <section class="block">
            <div class="empty-state">
                <i class="bi bi-inbox"></i>
                <h3>Belum ada data laporan</h3>
                <p>Belum ada laporan agregat desa untuk <?= esc($pendudukKey) ?>.</p>
            </div>
        </section>

        <?php endif; ?>

        <!-- ===== JELAJAHI KECAMATAN ===== -->
        <section class="block" style="padding-top: 1.5rem;">
            <div class="section-head">
                <h2>Jelajahi Kecamatan Lain</h2>
                <span class="crumb"><?= count($all_kecamatan) ?> kecamatan di Kabupaten Tanah Laut</span>
            </div>
            <div class="kec-grid">
                <?php foreach ($all_kecamatan as $k): 
                    $k_slug = $k['slug'] ?: $slugify($k['nama_kecamatan']);
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
                    <h5>Erdekatala</h5>
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
        const C = {
            primary: "#dd4814", ink: "#0f1923", blue: "#2d6cdf", pink: "#d6336c",
            green: "#2f9e44", muted: "#6b7280", paper: "#f7f5f1", border: "#e5e1d8",
            amber: "#f59e0b"
        };
        const PALETTE7 = ["#dd4814","#2d6cdf","#2f9e44","#d6336c","#8b5cf6","#f59e0b","#0d9488"];
        const GUIDE = { "line-color": C.border, "line-style": "dotted" };
        const AX = { "font-color": C.ink, "font-size": 11 };
        const TIP = { borderRadius: 4, padding: 8, "background-color": C.ink, "font-color": "#fff" };

        // Data for deferred charts — declared here so render fns can close over them
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

        // ===== TAB POKOK — render immediately (active tab) =====
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

        // ===== LAZY RENDER FUNCTIONS =====
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
        };

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
                    series: [{ text: "KK", values: pendValues, styles: PALETTE7.map(c => ({ "background-color": c })) }]
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
                        "border-radius": 6, "slice": 60,
                        tooltip: Object.assign({ text: "%t: <strong>%v</strong> KK (%npv%)" }, TIP),
                        "value-box": { text: "%npv%", "font-color": C.ink, "font-size": 11, placement: "out", "font-weight": "bold" },
                        animation: { effect: 2, speed: 600 }
                    },
                    plotarea: { margin: "10 10 45 10" },
                    legend: { layout: "x4", position: "50% 92%", "background-color": "none", "border-width": 0, marker: { "border-radius": 3 }, item: Object.assign({}, AX) },
                    series: kawinLabels.map((label, i) => ({ text: label, values: [kawinValues[i]], "background-color": kawinColors[i] }))
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
                    "scale-x": { values: pkrLabels, item: Object.assign({}, AX), guide: GUIDE, tick: { "line-color": C.border } },
                    "scale-y": { format: "%v", item: Object.assign({}, AX), guide: GUIDE, tick: { "line-color": C.border } },
                    series: [{ text: "KK", values: pkrValues, "background-color": C.primary }]
                }
            });
        };

        renderKesehatan = function () {
            zingchart.render({
                id: "zc-jkn",
                height: 300,
                width: "100%",
                data: {
                    type: "pie",
                    "background-color": "transparent",
                    plot: {
                        "border-radius": 6, "slice": 60,
                        tooltip: Object.assign({ text: "%t: <strong>%v</strong> (%npv%)" }, TIP),
                        "value-box": { text: "%npv%", "font-color": C.ink, "font-size": 11, placement: "out", "font-weight": "bold" },
                        animation: { effect: 2, speed: 600 }
                    },
                    plotarea: { margin: "10 10 45 10" },
                    legend: { layout: "x3", position: "50% 92%", "background-color": "none", "border-width": 0, marker: { "border-radius": 3 }, item: Object.assign({}, AX) },
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
                        tooltip: Object.assign({ text: "%scale-key-text: <strong>%v</strong> jiwa" }, TIP),
                        "value-box": { text: "%v", "font-color": C.ink, "font-size": 10, placement: "top-out" },
                        animation: { effect: 2, speed: 500 }
                    },
                    plotarea: { margin: "dynamic 50 dynamic 60" },
                    "scale-x": { values: dokLabels, item: Object.assign({}, AX), guide: GUIDE, tick: { "line-color": C.border } },
                    "scale-y": { format: "%v", item: Object.assign({}, AX), guide: GUIDE, tick: { "line-color": C.border } },
                    series: [{ text: "Pemilik Dokumen", values: dokValues, "background-color": C.primary }]
                }
            });
        };

        // ===== DESA TABLE SEARCH =====
        const searchInput = document.getElementById('desaSearch');
        if (searchInput) {
            searchInput.addEventListener('input', function() {
                const q = this.value.toLowerCase();
                document.querySelectorAll('#tableDesa tbody tr').forEach(function(row) {
                    row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
                });
            });
        }
        });
    </script>

    <!-- Leaflet (peta sebaran per desa, file lokal di public/leaflet) -->
    <script src="<?= base_url('leaflet/leaflet.js') ?>"></script>

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
            sEmptyTable: "Tidak ada data tersedia", sInfo: "", sInfoEmpty: "", sInfoFiltered: "",
            sInfoPostFix: "", sInfoThousands: ".", sLengthMenu: "", sLoadingRecords: "Memuat...",
            sProcessing: "Memproses...", sSearch: "Cari:", sZeroRecords: "Data tidak ditemukan",
            oPaginate: { sFirst: "Awal", sLast: "Akhir", sNext: "Berikut", sPrevious: "Sebelumnya" },
            oAria: { sSortAscending: ": urutkan naik", sSortDescending: ": urutkan turun" }
        };
        const dts = {};
        dts.pokok = $('#dt-pokok').DataTable({
            responsive: true, paging: false, searching: true, info: false, language: DT_LANG,
            columnDefs: [{ targets: [1, 2, 3], className: 'text-end dt-num' }, { targets: 0, className: 'dt-cat' }]
        });
        dts.sosio = $('#dt-sosio').DataTable({
            responsive: true, paging: false, searching: true, info: false, language: DT_LANG,
            columnDefs: [{ targets: [2, 3], className: 'text-end dt-num' }, { targets: [0], className: 'dt-cat' }]
        });
        dts.piramida = $('#dt-piramida').DataTable({
            responsive: true, paging: false, searching: true, info: false, language: DT_LANG,
            columnDefs: [{ targets: [1, 2, 3], className: 'text-end dt-num' }, { targets: 0, className: 'dt-cat' }]
        });
        dts.kesehatan = $('#dt-kesehatan').DataTable({
            responsive: true, paging: false, searching: true, info: false, language: DT_LANG,
            columnDefs: [{ targets: [2, 3], className: 'text-end dt-num' }, { targets: [0], className: 'dt-cat' }]
        });
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
        });
    </script>
    <div class="cursor-follower" id="cursorFollower"></div>
    <script>
        // ===== PETA SEBARAN PER DESA (Leaflet) — tematik multi-indikator =====
        // Data polygon + statistik dimuat dari endpoint publik
        // GET /api/v1/map/desa?id_kecamatan=<id> (single source of truth;
        // join statistik dilakukan di server).
        (function () {
            const mapEl = document.getElementById('map-desa');
            if (!mapEl) return;

            const kecId = <?= json_encode((int) ($kecamatan['id_kecamatan'] ?? 0)) ?>;
            const kecSlug = <?= json_encode($kecSlug) ?>;
            const filterDesa = <?= json_encode($selected_desa ? $slugify($selected_desa['nama_desa']) : '') ?>;
            const API_MAP_DESA = <?= json_encode(base_url('api/v1/map/desa')) ?>;
            const BASE_URL = <?= json_encode(site_url('/')) ?>;

            // Mapping SENTRAL label Indonesia -> field snake_case (partials/map_indicators.php)
            const INDICATOR_GROUPS = <?= json_encode($mapIndicatorGroups) ?>;
            const INDICATOR_LABEL = {};
            INDICATOR_GROUPS.forEach(function (g) {
                g.items.forEach(function (it) { INDICATOR_LABEL[it.field] = it.label; });
            });

            if (typeof L === 'undefined') {
                mapEl.className = 'map-placeholder';
                mapEl.innerHTML = '<div class="text-center"><i class="bi bi-map" style="font-size:2rem;display:block;margin-bottom:.5rem;"></i>Peta tidak dapat dimuat (library Leaflet gagal dimuat).</div>';
                return;
            }

            const fmt = n => Number(n || 0).toLocaleString('id-ID');
            const escHtml = s => String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
                return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
            });
            const RAMP = ['#fdeae2', '#f8c9b3', '#f2a483', '#e86f3f', '#dd4814'];
            const COLOR_ACTIVE = '#2d6cdf';
            const COLOR_NODATA = '#e5e1d8';

            const desaSlugify = name => String(name).toLowerCase().replace(/\s+/g, '-');

            // Klasifikasi 5 kelas (kuantil sederhana); aman untuk data flat/semua 0
            function makeBucketFn(values) {
                const sorted = values.slice().sort((a, b) => a - b);
                const q = i => sorted.length ? sorted[Math.min(sorted.length - 1, Math.floor(sorted.length * i / 5))] : 0;
                const stops = [q(1), q(2), q(3), q(4), q(5)];
                return {
                    stops: stops,
                    flat: stops.every(function (s) { return s === stops[0]; }),
                    fn: function (n) {
                        for (let i = 0; i < stops.length; i++) {
                            if (n <= stops[i]) return i;
                        }
                        return stops.length - 1;
                    }
                };
            }

            // ===== Pemilih indikator =====
            const indicatorSelect = document.getElementById('ind-desa-select');
            let activeField = (indicatorSelect && indicatorSelect.value) || 'total_jiwa';
            if (indicatorSelect) indicatorSelect.value = activeField;

            // ===== State layer & feature =====
            let map;
            let desaGeo = null;
            let desaFeatures = [];   // feature desa hasil merge properti endpoint
            let desaBucket = makeBucketFn([0]);

            function numVal(props, field) {
                const v = Number((props || {})[field]);
                return isFinite(v) ? v : 0;
            }

            // Gabung properti endpoint (indikator snake_case) dengan metadata turunan
            function mergeDesa(feature) {
                const p = feature.properties || {};
                const nama = p.nm_kelurahan || '';
                const props = Object.assign({}, p);
                props.kode = String(p.kd_kecamatan) + '.' + String(p.kd_kelurahan);
                props.nama = nama;
                props.slug = desaSlugify(nama);
                props.hasData = true;
                props.isActive = !!(filterDesa && desaSlugify(nama) === filterDesa);
                props.bucket = 0;
                return { type: 'Feature', geometry: feature.geometry, properties: props };
            }

            // Hitung ulang bucket sesuai indikator aktif, restyle layer, perbarui legenda
            function recalcAndRestyle() {
                if (desaFeatures.length) {
                    desaBucket = makeBucketFn(desaFeatures.map(function (f) { return numVal(f.properties, activeField); }));
                    desaFeatures.forEach(function (f) {
                        f.properties.bucket = f.properties.hasData ? desaBucket.fn(numVal(f.properties, activeField)) : 0;
                    });
                    if (desaGeo) desaGeo.eachLayer(function (l) { l.setStyle(desaStyle(l.feature)); });
                }
                updateLegend(desaBucket, INDICATOR_LABEL[activeField] || activeField);
            }

            function applyIndicator(field) {
                if (!field || !INDICATOR_LABEL[field]) return;
                activeField = field;
                recalcAndRestyle();
            }

            if (indicatorSelect) {
                indicatorSelect.addEventListener('change', function () { applyIndicator(this.value); });
            }

            // ===== Style choropleth =====
            function baseFill(props) {
                return props.isActive ? COLOR_ACTIVE
                    : (props.hasData ? RAMP[props.bucket] : COLOR_NODATA);
            }
            function desaStyle(feature) {
                return {
                    fillColor: baseFill(feature.properties || {}),
                    fillOpacity: 0.85,
                    color: '#ffffff',
                    weight: 1,
                    opacity: 1
                };
            }
            // Hover: blend warna dasar 40% ke putih (ekuivalen overlay rgba(255,255,255,.40))
            function blendWhite(hex, amt) {
                const n = parseInt(hex.slice(1), 16);
                const mix = c => Math.round(c + (255 - c) * amt);
                return 'rgb(' + mix((n >> 16) & 255) + ',' + mix((n >> 8) & 255) + ',' + mix(n & 255) + ')';
            }
            function hoverStyle(feature) {
                return { fillColor: blendWhite(baseFill(feature.properties || {}), 0.4), fillOpacity: 0.85 };
            }

            // Handler interaksi poligon desa (hover + popup klik)
            function onEachDesa(feature, layer) {
                layer.bindPopup(function (l) { return desaPopupHtml(l.feature.properties); }, { className: 'map-popup-desa', closeButton: false, maxWidth: 300 });
                layer.on({
                    mouseover: function (e) {
                        e.target.setStyle(hoverStyle(feature));
                        mapEl.style.cursor = 'pointer';
                    },
                    mouseout: function (e) {
                        if (desaGeo) desaGeo.resetStyle(e.target);
                        mapEl.style.cursor = '';
                    }
                });
            }

            try {
                map = L.map('map-desa', {
                    scrollWheelZoom: false,
                    zoomControl: false,
                    minZoom: 6,
                    maxZoom: 18
                });
                // View sementara sebelum fitBounds data GeoJSON siap
                map.setView([-3.85, 114.75], 9);
                L.control.zoom({ position: 'topright' }).addTo(map);
                map.attributionControl.setPrefix('');
            } catch (err) {
                showMapError('Peta tidak dapat diinisialisasi (' + ((err && err.message) || 'kesalahan tak terduga') + '). Coba muat ulang halaman atau gunakan browser lain.');
                return;
            }

            const tiles = L.tileLayer('https://basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors &copy; <a href="https://www.carto.com/attributions">CARTO</a>'
            }).addTo(map);

            // Kegagalan tile basemap dicatat, bukan diam-diam
            let mapTileErr = 0;
            tiles.on('tileerror', function () {
                mapTileErr++;
                if (mapTileErr === 3) {
                    const legend = document.getElementById('map-legend-desa');
                    if (legend && !legend.querySelector('.tile-note')) {
                        const note = document.createElement('span');
                        note.className = 'text-muted tile-note';
                        note.style.fontSize = '.75rem';
                        note.innerHTML = ' &middot; <i class="bi bi-exclamation-triangle"></i> Peta dasar gagal dimuat &mdash; poligon data tetap tampil';
                        legend.appendChild(note);
                    }
                }
            });

            // Popup: nama desa + nilai indikator aktif + link detail
            function pkRow(label, value) {
                return '<div class="pk-row"><span>' + escHtml(label) + '</span><b>' + fmt(value) + '</b></div>';
            }
            function activeIndicatorRow(props) {
                return pkRow(INDICATOR_LABEL[activeField] || activeField, numVal(props, activeField));
            }

            function desaPopupHtml(d) {
                let html = '<div class="pk-name">' + escHtml(d.nama || '—') + '</div>';
                html += activeIndicatorRow(d);
                ['total_jiwa', 'total_kk'].forEach(function (fld) {
                    if (fld !== activeField) html += pkRow(INDICATOR_LABEL[fld] || fld, numVal(d, fld));
                });
                html += '<div class="pk-row"><span>Jiwa L / P</span><b>' + fmt(numVal(d, 'jiwa_l')) + ' / ' + fmt(numVal(d, 'jiwa_p')) + '</b></div>';
                if (kecSlug && d.slug) {
                    html += '<a class="pk-link" href="' + BASE_URL + escHtml(kecSlug) + '?desa=' + encodeURIComponent(d.slug) + '">Lihat detail desa →</a>';
                }
                return html;
            }

            function showMapError(msg) {
                if (map) { try { map.remove(); } catch (e) { /* noop */ } map = null; }
                mapEl.className = 'map-placeholder';
                mapEl.innerHTML = '<div class="text-center"><i class="bi bi-map" style="font-size:2rem;display:block;margin-bottom:.5rem;"></i>' + escHtml(msg) + '</div>';
            }

            // ===== Muat FeatureCollection desa dari endpoint publik =====
            fetch(API_MAP_DESA + '?id_kecamatan=' + encodeURIComponent(kecId))
                .then(r => { if (!r.ok) throw new Error('HTTP ' + r.status); return r.json(); })
                .then(fc => {
                    if (!fc || !Array.isArray(fc.features) || !fc.features.length) {
                        showMapError('Data peta belum tersedia.');
                        return;
                    }
                    desaFeatures = fc.features.map(mergeDesa);

                    desaGeo = L.geoJSON({ type: 'FeatureCollection', features: desaFeatures }, {
                        style: desaStyle,
                        onEachFeature: onEachDesa
                    }).addTo(map);

                    // Hitung choropleth + legenda sesuai indikator aktif
                    recalcAndRestyle();

                    let activeLayer = null;
                    desaGeo.eachLayer(function (l) {
                        if (l.feature && l.feature.properties.isActive) activeLayer = l;
                    });
                    if (activeLayer) {
                        map.fitBounds(activeLayer.getBounds(), { padding: [60, 60] });
                    } else if (desaGeo.getLayers().length) {
                        map.fitBounds(desaGeo.getBounds(), { padding: [40, 40] });
                    }
                })
                .catch(err => showMapError('Peta tidak dapat dimuat (' + ((err && err.message) || 'kesalahan jaringan') + ').'));

            // ===== Legenda (warna + rentang nilai, mengikuti indikator aktif) =====
            function updateLegend(bucket, label) {
                const indLabel = document.querySelector('#map-legend-desa .lg-ind-label');
                if (indLabel) indLabel.textContent = label;
                const barLabel = document.getElementById('lg-indicator-name-desa');
                if (barLabel) barLabel.textContent = 'Menampilkan: ' + label;
                RAMP.forEach(function (color, i) {
                    const el = document.getElementById('dlg-' + i);
                    if (!el) return;
                    const swatch = el.querySelector('.swatch');
                    const val = el.querySelector('b');
                    if (swatch) swatch.style.background = color;
                    if (!val) return;
                    if (bucket.flat) {
                        // Data seragam (mis. semua 0): hanya kelas pertama bermakna
                        val.textContent = i === 0 ? fmt(bucket.stops[0]) : '–';
                        return;
                    }
                    const stops = bucket.stops;
                    if (i > 0 && stops[i] === stops[i - 1]) {
                        val.textContent = '–'; // kelas kosong karena batas identik
                    } else {
                        const lo = i === 0 ? 0 : (stops[i - 1] + 1);
                        val.textContent = fmt(lo) + '–' + fmt(stops[i]);
                    }
                });
            }

            // Klik poligon (popup) dan hover highlight ditangani langsung
            // via bindPopup + event layer di onEachDesa.
        })();
    </script>
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