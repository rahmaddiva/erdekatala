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
        width: 38px;
        height: 38px;
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
