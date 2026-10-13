<?php /* Partial toggle dark/light untuk halaman standalone (publik, login, API).
 * Pakai: $this->setVar('variant', 'bg')->include('templates/theme_toggle')
 * (argumen ke-2 include() = $options, BUKAN data view — selalu via setVar)
 * variant 'surface': --paper = permukaan (landing, kecamatan, login)
 * variant 'bg':      --paper = latar halaman (docs, guide, register, register_success)
 * noButton=true:     tanpa tombol floating (halaman bernavbar, tombol di .nav-right)
 */ ?>
<?php $ttVariant = $variant ?? 'surface'; ?>
<style>
    body.dark-mode {
        --ink: #e8eef6;
        --ink-soft: #c6d3e6;
        --ink-2: #c6d3e6;
        --muted: #8fa1b8;
        --border: #26364f;
        --line: #26364f;
        --primary: #3a96dd;
        --primary-d: #6db4e8;
        <?php if ($ttVariant === 'bg'): ?>
        --paper: #0b1526;
        --paper-2: #132239;
        <?php else: ?>
        --paper: #132239;
        --paper-2: #0b1526;
        <?php endif; ?>
        --paper-3: #1b2c47;
        color-scheme: dark;
    }
    /* Blok yang memang gelap di light mode — kunci tetap gelap. */
    body.dark-mode .hero,
    body.dark-mode .page-hero,
    body.dark-mode footer.sikada-footer,
    body.dark-mode .info-strip,
    body.dark-mode .api-header,
    body.dark-mode .guide-header,
    body.dark-mode .api-footer,
    body.dark-mode .guide-footer {
        background: #0a1730 !important;
    }
    body.dark-mode .btn-login { background: #3a96dd !important; }
    /* Bagian baru landing: hero terang, pill status, strip catatan, kartu angka. */
    body.dark-mode .status-pill { background: #0b1526 !important; border-color: #26364f !important; color: #c6d3e6 !important; }
    body.dark-mode .status-pill i { color: #6db4e8 !important; }
    body.dark-mode .note-strip { background: #0b1526 !important; border-color: #26364f !important; border-left-color: #3a96dd !important; }
    body.dark-mode .zero-report-badge { background: #0b1526 !important; border-color: #26364f !important; border-left-color: #3a96dd !important; color: #c6d3e6 !important; }
    body.dark-mode .zero-report-badge i { color: #6db4e8 !important; }
    /* Peringatan filter wilayah yang tidak bisa di-resolve. */
    body.dark-mode .filter-warn { background: #3a2d10 !important; border-color: #5c4718 !important; border-left-color: #d99a1a !important; }
    body.dark-mode .filter-warn > i { color: #f0b37e !important; }
    body.dark-mode .filter-warn p { color: #f2e2c0 !important; }
    body.dark-mode .filter-warn strong { color: #ffe6b0 !important; }
    /* Penanda sumber per-indikator (chart & tabel Dokumen Kependudukan). */
    body.dark-mode .src-note { background: #0b1526 !important; border-color: #26364f !important; }
    body.dark-mode .src-note > i { color: #6db4e8 !important; }
    body.dark-mode .src-note p { color: #c6d3e6 !important; }
    body.dark-mode .src-note strong { color: #e8eef6 !important; }
    /* Label sumber RESMI (DKB). */
    body.dark-mode .src-official { color: #9ec7f0 !important; }
    body.dark-mode .src-chip.dukcapil { background: #12314f !important; color: #9ec7f0 !important; border-color: #24547f !important; }
    body.dark-mode .src-chip.desa { background: #1b2c47 !important; color: #c6d3e6 !important; border-color: #26364f !important; }
    body.dark-mode .group-filter .sumber-hint { color: #8fa1b8 !important; }
    body.dark-mode .group-filter .sumber-hint > i { color: #6db4e8 !important; }
    body.dark-mode .group-filter .sumber-hint strong { color: #e8eef6 !important; }
    body.dark-mode .group-filter select.form-select {
        background-color: #132239 !important;
        color: #e8eef6 !important;
        border-color: #26364f !important;
        background-image: url("data:image/svg+xml;charset=UTF-8,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%238fa1b8' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E") !important;
    }
    body.dark-mode .group-filter select.form-select:hover { border-color: #3a96dd !important; }
    body.dark-mode .group-filter select.form-select:focus {
        border-color: #3a96dd !important;
        box-shadow: 0 0 0 3px rgba(58, 150, 221, 0.25) !important;
    }
    body.dark-mode .group-filter select.form-select option {
        background-color: #132239 !important;
        color: #e8eef6 !important;
    }
    body.dark-mode .note-strip p { color: #c6d3e6 !important; }
    body.dark-mode .note-strip strong { color: #e8eef6 !important; }
    body.dark-mode .summary-cta { background: #0b1526 !important; color: #6db4e8 !important; border-color: #26364f !important; }
    body.dark-mode .section-head .tag { background: #1b2c47 !important; color: #c6d3e6 !important; border-color: #26364f !important; }
    body.dark-mode .section-head .tag.src-dukcapil { background: #12314f !important; color: #9ec7f0 !important; border-color: #24547f !important; }
    body.dark-mode .section-head .tag.src-dukcapil i { color: #9ec7f0 !important; }
    body.dark-mode .stat-card .meter { background: #26364f !important; }
    /* Ikon watermark kartu: di light mode pakai --paper-3, terlalu gelap di dark mode. */
    body.dark-mode .stat-card .icon { color: #2b4062 !important; }
    body.dark-mode .group-filter label { color: #8fa1b8 !important; }
    /* Sel tabel & komponen yang hardcode putih/navy di light mode. */
    body.dark-mode table.krs-tree tbody td,
    body.dark-mode table.krs-tree tr.krs-desa td,
    body.dark-mode table.krs-tree tr.krs-desa td.wilayah,
    body.dark-mode table.dataTable tbody td {
        background: #132239 !important;
        color: #dbe6f5 !important;
        border-color: #26364f !important;
    }
    body.dark-mode table.krs-tree thead th { background: #0a1730 !important; color: #dbe6f5 !important; }
    body.dark-mode table.krs-tree tbody tr:hover td,
    body.dark-mode table.krs-tree tbody tr:hover td.wilayah,
    body.dark-mode table.krs-tree tr.krs-desa:hover td,
    body.dark-mode table.krs-tree tr.krs-desa:hover td.wilayah,
    body.dark-mode table.krs-tree tr.krs-kec:hover td.wilayah,
    body.dark-mode table.dataTable tbody tr:hover td { background: #1b2c47 !important; }
    body.dark-mode table.dataTable thead th { color: #8fa1b8 !important; border-color: #26364f !important; }
    body.dark-mode table.dataTable { border-color: #26364f !important; }
    body.dark-mode .dataTables_wrapper .dataTables_length,
    body.dark-mode .dataTables_wrapper .dataTables_filter,
    body.dark-mode .dataTables_wrapper .dataTables_info,
    body.dark-mode .dataTables_wrapper .dataTables_paginate { color: #c6d3e6 !important; }
    body.dark-mode .dataTables_wrapper .dataTables_filter input,
    body.dark-mode .dataTables_wrapper .dataTables_length select,
    body.dark-mode .filter-bar select, body.dark-mode .filter-bar input,
    body.dark-mode .krs-toolbar input, body.dark-mode .krs-toolbar select {
        background: #0b1526 !important; color: #e8eef6 !important; border-color: #26364f !important;
    }
    body.dark-mode .krs-toggle, body.dark-mode .kec-card i { color: #c6d3e6 !important; }
    body.dark-mode .map-shell, body.dark-mode .map-shell .map-legend { background: #132239 !important; color: #c6d3e6 !important; }
    body.dark-mode .map-shell .lg-val, body.dark-mode .map-shell .map-legend b { color: #e8eef6 !important; }
    body.dark-mode .leaflet-popup-content-wrapper, body.dark-mode .leaflet-popup-tip { background: #132239 !important; color: #e8eef6 !important; }
    body.dark-mode .map-popup-desa .pk-name, body.dark-mode .map-popup-desa .pk-row b { color: #e8eef6 !important; }
    body.dark-mode .map-popup-desa .pk-row { color: #c6d3e6 !important; }
    /* Halaman login: label & ikon yang hardcode abu di light mode. */
    body.dark-mode .remember-row label { color: #c6d3e6 !important; }
    body.dark-mode .form-foot { color: #8fa1b8 !important; }
    body.dark-mode .control > .lead-icon, body.dark-mode .toggle-pass { color: #8fa1b8 !important; }
    body.dark-mode .control input::placeholder { color: #7d8ea6 !important; }
    /* Halaman API standalone (docs/guide/register): teks hardcode di CSS pihak ketiga
       (swagger-ui.css, highlight.js github, bootstrap) tetap navy gelap — timpa agar
       kontras di atas background gelap dari variabel --paper/--ink. */
    body.dark-mode .swagger-ui { color: #e8eef6; }
    body.dark-mode .swagger-ui .info .title,
    body.dark-mode .swagger-ui .info p,
    body.dark-mode .swagger-ui .info li,
    body.dark-mode .swagger-ui .opblock-description-wrapper p,
    body.dark-mode .swagger-ui .opblock-summary-description,
    body.dark-mode .swagger-ui .opblock-summary,
    body.dark-mode .swagger-ui .parameters-col_description,
    body.dark-mode .swagger-ui .parameters-col_name,
    body.dark-mode .swagger-ui .response-col_description,
    body.dark-mode .swagger-ui .response-col_description p,
    body.dark-mode .swagger-ui .response-col_status,
    body.dark-mode .swagger-ui table thead tr th,
    body.dark-mode .swagger-ui table thead tr td,
    body.dark-mode .swagger-ui table tbody tr td { color: #dbe6f5 !important; }
    body.dark-mode .swagger-ui .opblock-tag,
    body.dark-mode .swagger-ui .opblock .opblock-section-header h4,
    body.dark-mode .swagger-ui .responses-inner h4,
    body.dark-mode .swagger-ui .responses-inner h5,
    body.dark-mode .swagger-ui section.models h4,
    body.dark-mode .swagger-ui section.models.is-open h4,
    body.dark-mode .swagger-ui .model-title,
    body.dark-mode .swagger-ui .model { color: #e8eef6 !important; }
    body.dark-mode .swagger-ui .prop-type { color: #6db4e8 !important; }
    body.dark-mode .swagger-ui .prop-format { color: #8fa1b8 !important; }
    body.dark-mode .swagger-ui .opblock.opblock-get { background: rgba(58,150,221,.08); }
    body.dark-mode .swagger-ui .opblock .opblock-section-header { background: #132239; }
    body.dark-mode .swagger-ui .opblock-body pre { background: #0a1730; }
    body.dark-mode .swagger-ui .opblock-body pre code { color: #e8eef6 !important; }
    body.dark-mode .swagger-ui input[type=text],
    body.dark-mode .swagger-ui select,
    body.dark-mode .swagger-ui textarea { background: #0b1526; color: #e8eef6; border-color: #26364f; }
    /* Halaman guide: highlight.js tema "github" hardcode teks gelap di latar terang. */
    body.dark-mode pre code.hljs { background: #0a1730; color: #e8eef6; }
    body.dark-mode pre code.hljs .hljs-string { color: #8be28f; }
    body.dark-mode pre code.hljs .hljs-keyword,
    body.dark-mode pre code.hljs .hljs-built_in { color: #6db4e8; }
    body.dark-mode pre code.hljs .hljs-comment { color: #8fa1b8; }
    body.dark-mode pre code.hljs .hljs-number,
    body.dark-mode pre code.hljs .hljs-literal { color: #f0b37e; }
    body.dark-mode pre code.hljs .hljs-title,
    body.dark-mode pre code.hljs .hljs-function { color: #c792ea; }
    body.dark-mode pre code.hljs .hljs-attr,
    body.dark-mode pre code.hljs .hljs-attribute { color: #82aaff; }
    /* Halaman guide: teks Bootstrap hardcode yang ikut terbalik. */
    body.dark-mode .sidebar .nav-link { color: #c6d3e6 !important; }
    body.dark-mode .sidebar .nav-link:hover,
    body.dark-mode .sidebar .nav-link.active { color: #6db4e8 !important; }
    body.dark-mode .table td,
    body.dark-mode .table th { color: #dbe6f5 !important; border-color: #26364f !important; }
    body.dark-mode .table thead th { background: #0a1730 !important; color: #8fa1b8 !important; }
    body.dark-mode .step-card .card-header h5 { color: #e8eef6 !important; }
    body.dark-mode .base-url-box,
    body.dark-mode .key-box { background: #0b1526 !important; color: #dbe6f5 !important; border-color: #26364f !important; }
    body.dark-mode .alert-info,
    body.dark-mode .alert-warning { background: #12314f !important; color: #dbe6f5 !important; border-color: #24547f !important; }
    body.dark-mode code:not(pre code) { background: #1b2c47 !important; color: #6db4e8 !important; }
    body.dark-mode .nav-lang .nav-link { color: #6db4e8 !important; }
    body.dark-mode .nav-lang .nav-link.active { background: #3a96dd !important; color: #fff !important; }
    body.dark-mode .guide-header .btn-light { background: #132239 !important; color: #e8eef6 !important; border: 1px solid #26364f !important; }
    body.dark-mode .guide-header .btn-light:hover { background: #1b2c47 !important; }
    /* Halaman register & register_success. */
    body.dark-mode .register-card,
    body.dark-mode .success-card { background: #132239 !important; border-color: #26364f !important; }
    body.dark-mode .register-card .form-control { background: #0b1526 !important; color: #e8eef6 !important; border-color: #26364f !important; }
    body.dark-mode .register-card .form-control:focus { background: #0b1526 !important; color: #e8eef6 !important; border-color: #3a96dd !important; }
    body.dark-mode .form-control::placeholder { color: #7d8ea6 !important; }
    body.dark-mode .register-card label,
    body.dark-mode .register-card small { color: #dbe6f5 !important; }
    body.dark-mode .info-box { background: #0b1526 !important; color: #dbe6f5 !important; border-color: #26364f !important; }
    body.dark-mode .key-value { color: #6db4e8 !important; }
    body.dark-mode .key-label { color: #8fa1b8 !important; }
    body.dark-mode .sub,
    body.dark-mode .back-link { color: #8fa1b8 !important; }
    body.dark-mode .back-link:hover { color: #6db4e8 !important; }
    body.dark-mode .usage-box { background: #0b1526 !important; color: #dbe6f5 !important; border-color: #26364f !important; }
    body.dark-mode .usage-box strong { color: #e8eef6 !important; }
    body.dark-mode .alert-danger { background: #12314f !important; color: #dbe6f5 !important; border-color: #24547f !important; }
    /* Transisi hanya saat toggle (class dilepas 450ms setelah klik). */
    body.theme-anim, body.theme-anim *, body.theme-anim *::before, body.theme-anim *::after {
        transition: background-color .35s ease, color .35s ease, border-color .35s ease, box-shadow .35s ease !important;
    }
    /* Tombol floating — hanya untuk halaman tanpa navbar (login, API). */
    #themeToggleBtn {
        position: fixed;
        right: 18px;
        bottom: 18px;
        z-index: 9999;
        width: 44px;
        height: 44px;
        border-radius: 50%;
        border: 1px solid var(--border);
        background: var(--paper-2);
        color: var(--ink);
        display: grid;
        place-items: center;
        cursor: pointer;
        box-shadow: 0 4px 14px rgba(0, 0, 0, .25);
    }
    #themeToggleBtn:hover { transform: scale(1.06); }
    #themeToggleBtn svg { width: 20px; height: 20px; }
    /* Tombol navbar — ghost sejajar link API & tombol Masuk. */
    .nav-theme-btn {
        display: inline-grid;
        place-items: center;
        width: 44px;
        height: 44px;
        border-radius: 8px;
        border: 1px solid var(--border);
        background: transparent;
        color: var(--ink-soft);
        cursor: pointer;
        transition: color .15s ease, border-color .15s ease, background .15s ease;
    }
    .nav-theme-btn:hover { color: var(--primary); border-color: var(--primary); }
    .nav-theme-btn svg { width: 18px; height: 18px; }
</style>
<?php if (!isset($noButton) || !$noButton): ?>
<button id="themeToggleBtn" type="button" aria-label="Ganti mode gelap/terang" title="Mode gelap/terang">
    <svg class="ic-sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>
    <svg class="ic-moon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
</button>
<?php endif; ?>
<script>
(function () {
    var KEY = 'erdekatala_theme';
    function paint(mode) {
        document.body.classList.toggle('dark-mode', mode === 'dark');
        document.querySelectorAll('#themeToggleBtn, .nav-theme-btn').forEach(function (b) {
            var sun = b.querySelector('.ic-sun'), moon = b.querySelector('.ic-moon');
            if (sun) sun.style.display = mode === 'dark' ? '' : 'none';
            if (moon) moon.style.display = mode === 'dark' ? 'none' : '';
        });
    }
    var cur = null;
    try { cur = localStorage.getItem(KEY); } catch (e) {}
    paint(cur === 'dark' ? 'dark' : 'light');
    document.querySelectorAll('#themeToggleBtn, .nav-theme-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var next = document.body.classList.contains('dark-mode') ? 'light' : 'dark';
            document.body.classList.add('theme-anim');
            try { localStorage.setItem(KEY, next); } catch (e) {}
            paint(next);
            try { document.dispatchEvent(new CustomEvent('erdekatala:theme', { detail: next })); } catch (e) {}
            setTimeout(function () { document.body.classList.remove('theme-anim'); }, 450);
        });
    });
})();
</script>
