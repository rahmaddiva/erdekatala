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
// ===== Label SKOP dari HASIL RESOLUSI filter wilayah =====
// Sebelumnya dipakai string mentah ?kecamatan= / ?desa= sehingga slug yang
// tidak bisa di-resolve tetap menulis skop "Desa" padahal angkanya level
// kecamatan. Sekarang: $id_desa_filter / $id_kec_filter (hasil lookup DB).
$idKecFilter    = $id_kec_filter ?? null;
$idDesaFilter   = $id_desa_filter ?? null;
$filterKecNama  = $filter_kec_nama ?? null;
$filterDesaNama = $filter_desa_nama ?? null;
$kecUnresolved  = !empty($kec_unresolved);
$desaUnresolved = !empty($desa_unresolved);
// Slug URL (case-insensitive) untuk mencocokkan nilai <option selected>.
$filterKecSlugLower  = $filter_kec_slug !== null ? strtolower((string) $filter_kec_slug) : null;
$filterDesaSlugLower = $filter_desa_slug !== null ? strtolower((string) $filter_desa_slug) : null;
// Slug efektif kecamatan terpilih: dari kecamatan hasil RESOLUSI (bila ada),
// kalau tidak dari ?kecamatan= mentah. Dipakai peta supaya highlight poligon
// konsisten dengan scope data (mis. ?desa= tanpa ?kecamatan=, atau ?kecamatan=
// yang gagal di-resolve tapi desa-nya ketemu).
$effectiveKecSlug = $filterKecNama
    ? strtolower(str_replace(' ', '-', $filterKecNama))
    : ($filterKecSlugLower ?: null);

if ($idDesaFilter) {
    $pendudukKey = 'Desa';
    $scopeDetail = ($filterDesaNama ? 'Desa ' . $filterDesaNama : 'desa terpilih')
        . ($filterKecNama ? ', Kec. ' . $filterKecNama : '');
} elseif ($idKecFilter) {
    $pendudukKey = 'Kecamatan';
    $scopeDetail = $filterKecNama ? 'Kec. ' . $filterKecNama : 'kecamatan terpilih';
} else {
    $pendudukKey = 'Kabupaten';
    $scopeDetail = 'seluruh kabupaten';
}

// Mode sumber data profil (dihitung sekali di atas — dipakai hero, note-strip,
// dan badge Bagian 1): 'laporan' | 'dukcapil' | 'campuran'
$profilMode = $profilMeta['mode'] ?? 'dukcapil';
$profilSumberFilter = $profilMeta['sumber_filter'] ?? 'dukcapil';

// ===== Provenance Kelompok A (KIA & Akta Cerai) =====
// Dua indikator ini SELALU dari snapshot Dukcapil (PublicController baris
// ~185-221); laporan desa tidak punya kolomnya. Karena itu mereka tidak boleh
// berdiri di bawah badge "Laporan Desa" tanpa penanda sendiri.
$dokumenSumber   = $profilMeta['dokumen_sumber'] ?? [];
$kelompokAAktif  = !empty($profilMeta['kelompok_a_aktif']) && isset($dokumen['KIA'], $dokumen['Akta Cerai']);
$kelompokALabels = ['KIA', 'Akta Cerai'];
// "Campuran internal" = chart dokumen tidak seragam sumbernya, sehingga butuh
// catatan: mode utama laporan/desa tapi KIA & Akta Cerai tetap Dukcapil.
$dokumenCampuran = $kelompokAAktif && $profilMode !== 'dukcapil';

// Label sumber RESMI Dukcapil (DKB) — dari controller (ProfilDukcapilModel::
// SUMBER_LABEL_DKB). Fallback literal menjaga view tetap benar bila meta kosong.
$dkbLabel = $profilMeta['sumber_label_dkb']
    ?? \App\Models\ProfilDukcapilModel::SUMBER_LABEL_DKB;

// Semester Romawi (1→I, 2→II) untuk pill periode saat mode Dukcapil.
$romawiSemester = static fn(int $s): string => [1 => 'I', 2 => 'II'][$s] ?? (string) $s;

// Helper untuk format angka
$fmt = function($n) { return number_format((int)$n, 0, ',', '.'); };
$pendTotal = array_sum($pendidikan);
$pekTotal  = array_sum($pekerjaan);
$kawinTotal = array_sum($status_kawin);
$jknTotal   = array_sum($jkn_bpjs);
$dokTotal   = array_sum($dokumen);

/* ===== Indikator turunan (dihitung dari piramida usia & KK) =====
 * Rumus rasio ketergantungan mengikuti BPS: (0–14 + 65+) / 15–64 × 100.
 * Catatan: $piramidaL/$piramidaP adalah 18 kelompok umur 5-tahunan —
 * indeks 0..2 = 0–14 th, 3..12 = 15–64 th, 13..17 = 65+ th. */
$produktif = $usia014 = $usia65 = null;
if (isset($piramidaL, $piramidaP) && count($piramidaL) >= 18 && count($piramidaP) >= 18) {
    $produktif = $usia014 = $usia65 = 0;
    foreach ($piramidaL as $i => $v) {
        $n = (int) $v + (int) ($piramidaP[$i] ?? 0);
        if ($i <= 2)      { $usia014  += $n; }   // 0-4, 5-9, 10-14
        elseif ($i <= 12) { $produktif += $n; }   // 15-19 … 60-64
        else              { $usia65   += $n; }   // 65-69 … 85+
    }
}
$sexRatio      = $jiwaP > 0 ? $jiwaL / $jiwaP * 100 : null;
$depRatio      = ($produktif !== null && $produktif > 0) ? ($usia014 + $usia65) / $produktif * 100 : null;
$produktifPct  = ($produktif !== null && $jiwaTotal > 0) ? $produktif / $jiwaTotal * 100 : null;
$jiwaPerKk     = $kkTotal > 0 ? $jiwaTotal / $kkTotal : null;
$kkFisikVal    = (int) ($dokumen['KK Fisik'] ?? 0);
$kkFisikPct    = $kkTotal > 0 ? $kkFisikVal / $kkTotal * 100 : null;
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
// YanKB: bulan terpilih (1-12) + nama bulan Indonesia
$yankbBulan  = (int) ($yankbBulan ?? 8);
$yankbTahun  = (int) ($yankbTahun ?? 2026);
$bulanNama = [
    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
    5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
    9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
];
$yankbBulanNama = $bulanNama[$yankbBulan] ?? 'Agustus';

// ===== Cakupan tab BKKBN (YanKB & Dallap) — mengikuti filter kecamatan =====
// Saat ?kecamatan= aktif, baris YanKB/Dallap sudah disaring ke 1 kecamatan di
// controller; label/crumb di sini harus menyebut cakupan itu (bukan "seluruh
// kabupaten"). $bkkbnMeta dikirim controller.
$bkkbnMeta     = $bkkbnMeta ?? [];
$bkkbnIsKec    = !empty($bkkbnMeta['is_kecamatan']);
$bkkbnKecNama  = $bkkbnMeta['kec_nama'] ?? $filterKecNama;
$bkkbnScope    = $bkkbnMeta['kec_label'] ?? ($bkkbnIsKec ? 'Kecamatan ' . $bkkbnKecNama : 'Kabupaten Tanah Laut');

/* ===== Deteksi "bulan belum ada pelaporan" (opsional UX) =====
 * BKKBN tetap mengembalikan laporan berstruktur penuh untuk bulan depan,
 * hanya saja semua nilainya 0 (kecamatan belum lapor). Tandai supaya user
 * tidak mengira datanya rusak/kosong. */
$dallapMonthlyCols = ['Tabel3A', 'Tabel10', 'Tabel11']; // kolom Dallap yang mengikuti filter bulan
$dallapAllZero = static function (array $rows, array $cols) use ($dallapMonthlyCols): bool {
    $cols = $cols ?: $dallapMonthlyCols;
    $hasAny = false;
    foreach ($rows as $row) {
        foreach ($cols as $rn) {
            $v = $row['qty'][$rn] ?? null;
            if ($v !== null && (int) $v !== 0) { $hasAny = true; break 2; }
        }
    }
    return !$hasAny;
};
$dallapZeroBadge = static function (bool $allZero, int $bulan, string $label = 'Dallap BKKBN') use ($bulanNama): string {
    return $allZero
        ? '<div class="zero-report-badge"><i class="bi bi-hourglass-split me-1"></i>'
          . 'Belum ada pelaporan ' . esc($label) . ' untuk bulan '
          . htmlspecialchars($bulanNama[$bulan] ?? (string) $bulan) . ' — tabel menampilkan angka 0 dari sumber SIGA, bukan data hilang.'
          . '</div>'
        : '';
};
// CSS badge — tampil saat laporan bulan depan masih kosong
?>
<style>
.zero-report-badge {
    display: flex; align-items: flex-start; gap: .5rem;
    margin: 0 0 .75rem; padding: .55rem .9rem;
    font-size: .82rem; line-height: 1.5;
    background: rgba(0, 91, 172, 0.06);
    border: 1px solid rgba(0, 91, 172, 0.25);
    border-left: 3px solid var(--primary);
    border-radius: 6px; color: var(--ink-soft, #103a5c);
}
.zero-report-badge i { flex-shrink: 0; margin-top: .1rem; color: var(--primary); }
</style>
<?php
// Render tabel YanKB gaya krs-tree dari data terstruktur (fallback iframe jika parse gagal)
$renderYankbTable = static function (array $data): string {
    if (empty($data['headers']) || empty($data['rows'])) {
        return '';
    }
    $seps = $data['seps'] ?? [];
    $h = '<div class="krs-scroll"><table class="krs-tree krs-yankb">';
    $h .= '<thead>';
    foreach ($data['headers'] as $ri => $row) {
        $h .= '<tr' . ($ri === 0 ? ' class="krs-grp"' : '') . '>';
        foreach ($row as $cell) {
            $attr = '';
            if (($cell['rs'] ?? 1) > 1) $attr .= ' rowspan="' . (int) $cell['rs'] . '"';
            if (($cell['cs'] ?? 1) > 1) $attr .= ' colspan="' . (int) $cell['cs'] . '"';
            $h .= '<th' . $attr . '>' . esc($cell['t']) . '</th>';
        }
        $h .= '</tr>';
    }
    $h .= '</thead><tbody>';
    foreach ($data['rows'] as $row) {
        $h .= '<tr' . (!empty($row['total']) ? ' class="krs-kab"' : '') . '>';
        foreach ($row['cells'] as $i => $cell) {
            $col = $i + 1;
            $cls = ($col === 1) ? 'kode' : (($col === 2) ? 'wilayah' : 'n');
            if (!empty($row['total']) && $col === 1) $cls = 'wilayah';
            if (in_array($col, $seps, true)) $cls .= ' sep';
            $csAttr = (($cell['cs'] ?? 1) > 1) ? ' colspan="' . (int) $cell['cs'] . '"' : '';
            $h .= '<td class="' . trim($cls) . '"' . $csAttr . '>' . esc($cell['t']) . '</td>';
        }
        $h .= '</tr>';
    }
    $h .= '</tbody></table></div>';
    return $h;
};
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Erdekatala - Sistem Informasi Kependudukan Kabupaten Tanah Laut</title>
    <meta name="description" content="Portal data kependudukan tingkat Desa-Kecamatan di Kabupaten Tanah Laut, Kalimantan Selatan.">
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

    <!-- ZingChart (CDN resmi, sama dengan dashboard) -->
    <script src="https://cdn.zingchart.com/zingchart.min.js"></script>

    <!-- Leaflet (peta sebaran, file lokal di public/leaflet) -->
    <link rel="stylesheet" href="<?= base_url('leaflet/leaflet.css') ?>">

    <style>
        :root {
            --primary:   #005bac;
            --primary-d: #004a8c;
            --primary-l: #8ec7f0;
            --ink:       #0a2a4a;
            --ink-soft:  #1e3a5c;
            --paper:     #ffffff;
            --paper-2:   #eef3f8;
            --paper-3:   #e2eaf2;
            --muted:     #5f6b7a;
            --border:    #d5dfee;
            --accent-l:  #2d6cdf;
            --accent-p:  #d6336c;
            --good:      #2f9e44;
            --shadow-sm: 0 1px 2px rgba(15,25,35,.04), 0 1px 1px rgba(15,25,35,.03);
            --shadow-md: 0 4px 16px rgba(15,25,35,.06), 0 2px 4px rgba(15,25,35,.04);
            --shadow-lg: 0 24px 48px -12px rgba(15,25,35,.14);
        }

        * { box-sizing: border-box; }

        /* Fokus keyboard: cincin 3px agar selalu terlihat (a11y: 2-4px).
           Tidak menimpa kontrol yang sudah punya gaya fokus sendiri
           (mis. .filter-bar select memakai box-shadow). */
        a:focus-visible,
        button:focus-visible,
        select:focus-visible,
        input:focus-visible,
        summary:focus-visible,
        [tabindex]:focus-visible {
            outline: 3px solid var(--primary);
            outline-offset: 2px;
        }

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
            display: inline-flex;
            align-items: center;
            min-height: 44px;
            font-weight: 600;
            color: var(--ink-soft);
            font-size: 0.95rem;
        }
        .navbar-sikada .nav-link-sikada:hover { color: var(--primary); }
                .btn-login {
            background: var(--ink);
            color: #fff;
            display: inline-flex;
            align-items: center;
            min-height: 44px;
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
            background: var(--paper);
            border-bottom: 1px solid var(--border);
            padding: 3rem 0 2rem;
        }
        /* Hormati preferensi pengguna: matikan transisi/animasi dekoratif. */
        @media (prefers-reduced-motion: reduce) {
            .stat-card, .stat-card:hover { transition: none; transform: none; }
            .stat-card .meter > span { transition: none; }
            .summary-chev { transition: none; }
            .group-summary { transition: none; }
            .btn-login:hover, .filter-bar .btn-filter:hover { transform: none; }
        }

        .hero-top {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 2rem;
            flex-wrap: wrap;
        }
        .hero-copy { max-width: 680px; }
        .hero .eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.76rem;
            font-weight: 600;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            color: var(--primary);
            margin-bottom: 0.75rem;
        }
        .hero .eyebrow::before {
            content: "";
            width: 28px;
            height: 2px;
            background: var(--primary);
        }
        .hero h1 {
            font-family: 'Poppins', sans-serif;
            font-size: clamp(1.9rem, 3.4vw, 2.5rem);
            line-height: 1.15;
            color: var(--ink);
            margin-bottom: 0.6rem;
            font-weight: 700;
            letter-spacing: -0.01em;
        }
        .hero p.lead {
            font-size: 1rem;
            line-height: 1.6;
            color: var(--muted);
            max-width: 60ch;
            margin: 0;
        }
        .hero-status {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            padding-top: 0.4rem;
        }
        .status-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.4rem 0.8rem;
            border-radius: 999px;
            font-size: 0.78rem;
            font-weight: 600;
            background: var(--paper-2);
            color: var(--ink-soft);
            border: 1px solid var(--border);
            white-space: nowrap;
        }
        .status-pill i { color: var(--primary); font-size: 0.9rem; }
        .status-pill.muted i { color: var(--muted); }

        /* ===== FILTER BAR ===== */
        .filter-bar {
            background: var(--paper);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 1.25rem 1.5rem;
            margin-top: 1.75rem;
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
            box-shadow: 0 0 0 3px rgba(0,91,172,.35);
            background: var(--paper);
        }
                .filter-bar .btn-filter {
            background: var(--primary);
            color: #fff;
            border: none;
            display: inline-flex;
            align-items: center;
            min-height: 44px;
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

        /* ===== GRUP COLLAPSE (bagian 3: data pihak ketiga) ===== */
        .group-collapse { display: block; }
        /* summary dipakai sebagai section-head: buang marker bawaan browser */
        .group-summary {
            list-style: none;
            cursor: pointer;
            user-select: none;
            margin-bottom: 0;
            transition: color .15s ease;
        }
        .group-summary::-webkit-details-marker { display: none; }
        .group-summary::marker { content: ""; }
        .group-summary:hover h2 { color: var(--primary); }
        .group-summary:focus-visible {
            outline: 3px solid var(--primary);
            outline-offset: 4px;
            border-radius: 4px;
        }
        .summary-cta {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            margin-left: 0.75rem;
            padding: 0.45rem 0.85rem;
            min-height: 34px;
            border: 1px solid var(--border);
            border-radius: 999px;
            background: var(--paper);
            color: var(--primary);
            font-size: 0.76rem;
            font-weight: 700;
            letter-spacing: 0.02em;
            white-space: nowrap;
        }
        .summary-chev { transition: transform .2s ease; font-size: 0.85rem; }
        .cta-close { display: none; }
        .group-collapse[open] .cta-open { display: none; }
        .group-collapse[open] .cta-close { display: inline; }
        .group-collapse[open] .summary-chev { transform: rotate(180deg); }
        /* jarak ke konten saat terbuka (section-head margin direset di atas) */
        .group-collapse[open] .group-body { margin-top: 1.5rem; }
        .group-filter {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            flex-wrap: wrap;
            margin-bottom: 1rem;
        }
        .group-filter label {
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: var(--muted);
            margin: 0;
        }
        /* Select dalam group-filter (Sumber data, Bulan BKKBN) — konsisten
           dengan tema: permukaan kartu, border halus, aksen primary saat fokus. */
        .group-filter select.form-select {
            width: auto;
            min-width: 15rem;
            padding: 0.55rem 2.4rem 0.55rem 0.85rem;
            border: 1px solid var(--border);
            border-radius: 8px;
            background-color: var(--paper-2);
            color: var(--ink);
            font-family: inherit;
            font-size: 0.88rem;
            font-weight: 500;
            line-height: 1.4;
            cursor: pointer;
            appearance: none;
            -webkit-appearance: none;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%236b7280' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 0.8rem center;
            transition: border-color .15s ease, box-shadow .15s ease, background-color .15s ease;
        }
        .group-filter select.form-select:hover {
            border-color: var(--primary);
        }
        .group-filter select.form-select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(0, 91, 172, 0.18);
        }
        .group-filter select.form-select:focus-visible {
            outline: none;
        }
        .section-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            flex-wrap: wrap;
            margin-bottom: 1.5rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid var(--border);
        }
        .section-head h2 {
            font-size: 1.35rem;
            margin: 0;
            font-weight: 700;
            letter-spacing: -0.01em;
            display: flex;
            align-items: center;
            gap: 0.6rem;
        }
        /* nomor urut grup bagian (1/2/3) */
        .section-head .num {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 26px;
            height: 26px;
            flex-shrink: 0;
            border-radius: 50%;
            background: var(--primary);
            color: #fff;
            font-size: 0.8rem;
            font-weight: 700;
        }
        /* penanda sumber data pihak ketiga */
        .section-head .tag {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            font-size: 0.68rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            padding: 0.22rem 0.55rem;
            border-radius: 4px;
            background: var(--paper-3);
            color: var(--ink-soft);
        }
        /* badge sumber: Dukcapil = data resmi instansi, warna tegas */
        .section-head .tag.src-dukcapil {
            background: #e7f0fb;
            color: #0b4a8f;
            border: 1px solid #bcd6f2;
        }
        .section-head .tag.src-dukcapil i { color: #0b4a8f; }
        /* badge sumber: laporan desa = data input internal */
        .section-head .tag.src-desa {
            background: var(--paper-3);
            color: var(--ink-soft);
            border: 1px solid var(--border);
        }
        .section-head .tag + strong { margin-left: 0.5rem; }
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
        .stat-grid.cols-5 { grid-template-columns: repeat(5, 1fr); }
        @media (max-width: 992px) { .stat-grid { grid-template-columns: repeat(2, 1fr); } }
        @media (max-width: 576px) { .stat-grid { grid-template-columns: 1fr; } }
        @media (max-width: 992px) { .stat-grid.cols-5 { grid-template-columns: repeat(2, 1fr); } }
        @media (max-width: 576px) { .stat-grid.cols-5 { grid-template-columns: 1fr; } }

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
        /* satuan kecil yang mengikuti angka utama (mis. "/ 135 desa", "%") */
        .stat-card .value .unit {
            font-size: 0.95rem;
            font-weight: 500;
            color: var(--muted);
            margin-left: 0.3rem;
            letter-spacing: 0;
        }
        /* bar progres tipis untuk kartu berbasis persentase */
        .stat-card .meter {
            height: 4px;
            border-radius: 999px;
            background: var(--paper-3);
            overflow: hidden;
            margin: 0 0 0.5rem;
        }
        .stat-card .meter > span {
            display: block;
            height: 100%;
            border-radius: 999px;
            background: var(--good);
            transition: width .4s ease;
        }
        .stat-card .meter.pink > span { background: var(--accent-p); }
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

        /* ===== NOTE STRIP (catatan konteks data) ===== */
        .note-strip {
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            background: var(--paper);
            border: 1px solid var(--border);
            border-left: 3px solid var(--primary);
            border-radius: 8px;
            padding: 0.9rem 1.1rem;
            margin-top: 1.5rem;
        }
        .note-strip > i {
            color: var(--primary);
            font-size: 1.05rem;
            flex-shrink: 0;
            line-height: 1.5;
        }
        .note-strip p {
            margin: 0;
            font-size: 0.88rem;
            line-height: 1.6;
            color: var(--muted);
        }
        .note-strip strong { color: var(--ink-soft); font-weight: 600; }

        /* ===== FILTER WARN (slug wilayah tidak bisa di-resolve) ===== */
        .filter-warn {
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            background: #fff7e6;
            border: 1px solid #f2d69a;
            border-left: 3px solid #d99a1a;
            border-radius: 8px;
            padding: 0.8rem 1.1rem;
            margin-top: 1.75rem;
        }
        .filter-warn > i { color: #d99a1a; font-size: 1.05rem; flex-shrink: 0; line-height: 1.5; }
        .filter-warn p { margin: 0; font-size: 0.86rem; line-height: 1.6; color: #6b4c10; }
        .filter-warn strong { color: #4a330a; font-weight: 700; }

        /* ===== PENANDA SUMBER PER-INDIKATOR (chart Dokumen Kependudukan) ===== */
        .src-note {
            display: flex;
            align-items: flex-start;
            gap: 0.5rem;
            margin: 0.5rem 0 0;
            padding: 0.5rem 0.7rem;
            background: var(--paper-3);
            border: 1px dashed var(--border);
            border-radius: 6px;
            font-size: 0.76rem;
            line-height: 1.55;
            color: var(--muted);
        }
        .src-note > i { color: var(--primary); font-size: 0.9rem; flex-shrink: 0; line-height: 1.5; }
        .src-note p { margin: 0; }
        .src-note strong { color: var(--ink-soft); font-weight: 600; }
        /* label sumber RESMI (DKB) — teks persis dari ProfilDukcapilModel::SUMBER_LABEL_DKB */
        .src-official {
            display: inline-block;
            margin-top: 0.35rem;
            font-size: 0.78rem;
            font-weight: 600;
            line-height: 1.5;
            color: #0b4a8f;
        }
        /* chip sumber mini: dipakai di tabel & legenda chart */
        .src-chip {
            display: inline-block;
            font-size: 0.62rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            padding: 0.1rem 0.35rem;
            border-radius: 3px;
            margin-left: 0.35rem;
            vertical-align: middle;
            white-space: nowrap;
        }
        .src-chip.dukcapil { background: #e7f0fb; color: #0b4a8f; border: 1px solid #bcd6f2; }
        .src-chip.desa     { background: var(--paper-3); color: var(--ink-soft); border: 1px solid var(--border); }

        /* ===== HINT di sebelah dropdown "Sumber data" ===== */
        .group-filter .sumber-hint {
            display: inline-flex;
            align-items: flex-start;
            gap: 0.35rem;
            margin-left: 0.6rem;
            font-size: 0.76rem;
            line-height: 1.5;
            color: var(--muted);
            max-width: 46rem;
        }
        .group-filter .sumber-hint > i { color: var(--primary); line-height: 1.5; }
        .group-filter .sumber-hint strong { color: var(--ink-soft); font-weight: 600; }

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
        footer.sikada-footer a:hover { color: var(--primary-l); }
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
            font-weight: 600; font-size: 0.9rem;
            padding: 0.85rem 1.1rem; border-radius: 0;
            white-space: nowrap; transition: all .2s ease;
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
        #monitoring-bkkbn .tab-pane { padding-top: .5rem; }
        #monitoring-bkkbn .krs-shell { margin-bottom: .75rem; }
        #monitoring-bkkbn .krs-toolbar { padding: .6rem .9rem; gap: .5rem; }
        #monitoring-bkkbn .krs-toolbar h3 { font-size: .9rem; }
        #monitoring-bkkbn .krs-hint { padding: .45rem .9rem; }

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
            background: rgba(0,91,172,.03) !important;
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
        /* Bagian navigasi (bukan grup data): tanpa border bawah, bobot lebih ringan
           agar tidak terbaca sebagai "grup nomor 4". */
        .nav-block .section-head { border-bottom: none; padding-bottom: 0; }
        .nav-block .section-head h2 { font-size: 1.15rem; }
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
            background: linear-gradient(135deg, #103a5c 0%, #243556 100%);
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
            background: #e8eef6; color: #103a5c;
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
            text-align: left; font-weight: 600; color: #103a5c;
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
        /* pemisah tebal antar grup metrik (sesuai PDF Rekap; body: col 1=nama, 2–24=metrik) */
        table.krs-tree tbody td:nth-child(3),
        table.krs-tree tbody td:nth-child(5),
        table.krs-tree tbody td:nth-child(8),
        table.krs-tree tbody td:nth-child(11),
        table.krs-tree tbody td:nth-child(14),
        table.krs-tree tbody td:nth-child(17),
        table.krs-tree tbody td:nth-child(20),
        table.krs-tree tbody td:nth-child(23) {
            border-right: 2px solid #9aabc4;
        }
        /* nth-child = indeks sel dalam tr-nya masing-masing (bukan kolom fisik):
           r2: child3=c3, child5=c5, child6=PUS c8, child7=c11, child8=c14, child9=c17, child10=c20, child13=c23
           r3: child1=c8, child3=c11, child5=c14, child7=c17, child10=c20 (c3/c5/c23 sudah dibawa sel rowspan3 dari r2)
           r4: child3=c8, child5=c11, child7=c14, child9=c17 (c20 tak punya sel di r4, dibawa rowspan PRI) */
        table.krs-tree thead tr:nth-child(2) th:nth-child(3),
        table.krs-tree thead tr:nth-child(2) th:nth-child(5),
        table.krs-tree thead tr:nth-child(2) th:nth-child(6),
        table.krs-tree thead tr:nth-child(2) th:nth-child(7),
        table.krs-tree thead tr:nth-child(2) th:nth-child(8),
        table.krs-tree thead tr:nth-child(2) th:nth-child(9),
        table.krs-tree thead tr:nth-child(2) th:nth-child(10),
        table.krs-tree thead tr:nth-child(2) th:nth-child(13),
        table.krs-tree thead tr:nth-child(3) th:nth-child(1),
        table.krs-tree thead tr:nth-child(3) th:nth-child(3),
        table.krs-tree thead tr:nth-child(3) th:nth-child(5),
        table.krs-tree thead tr:nth-child(3) th:nth-child(7),
        table.krs-tree thead tr:nth-child(3) th:nth-child(10),
        table.krs-tree thead tr:nth-child(4) th:nth-child(3),
        table.krs-tree thead tr:nth-child(4) th:nth-child(5),
        table.krs-tree thead tr:nth-child(4) th:nth-child(7),
        table.krs-tree thead tr:nth-child(4) th:nth-child(9),
        table.krs-tree thead tr:nth-child(5) th:nth-child(3),
        table.krs-tree thead tr:nth-child(5) th:nth-child(5),
        table.krs-tree thead tr:nth-child(5) th:nth-child(8),
        table.krs-tree thead tr:nth-child(5) th:nth-child(11),
        table.krs-tree thead tr:nth-child(5) th:nth-child(14),
        table.krs-tree thead tr:nth-child(5) th:nth-child(17),
        table.krs-tree thead tr:nth-child(5) th:nth-child(20),
        table.krs-tree thead tr:nth-child(5) th:nth-child(23) {
            border-right: 2px solid #9aabc4;
        }
        /* sticky 5 baris header KRS (top = akumulasi tinggi baris, diukur dari render) */
        table#krs-tree thead tr:nth-child(2) th { top: 29px; }
        table#krs-tree thead tr:nth-child(3) th { top: 58px; z-index: 2; }
        table#krs-tree thead tr:nth-child(4) th { top: 87px; z-index: 2; }
        table#krs-tree thead tr.krs-idx th {
            top: 116px; z-index: 2;
            background: #d9edf9; color: #0f6c94;
            padding: .15rem .25rem; font-size: .55rem;
        }
        table#krs-tree thead tr.krs-idx th:first-child { background: #d9edf9; }
        /* baris super (colspan lebar) tidak ikut sticky-left agar tidak menutupi kolom */
        table#krs-tree thead tr:first-child th:first-child { position: static; }
        .krs-toggle {
            display: inline-flex; align-items: center; justify-content: center;
            width: 1.35rem; height: 1.35rem; margin-right: .4rem;
            border: 1px solid #c5d0e0; border-radius: 4px;
            background: #fff; color: #103a5c; cursor: pointer;
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
        /* ===== KRS 24 kolom: padatkan agar muat container + kunci header kolom-1 ===== */
        table#krs-tree { min-width: 0; width: 100%; font-size: .68rem; }
        table#krs-tree thead th { padding: .4rem .25rem; font-size: .6rem; }
        table#krs-tree tbody td { padding: .25rem .3rem; }
        table#krs-tree tbody td.wilayah { min-width: 150px; max-width: 170px; }
        table#krs-tree thead th:first-child {
            position: sticky; left: 0; z-index: 6;
            background: #d4deec;
        }
        table#krs-tree thead tr:nth-child(2) th:first-child { background: #e8eef6; }
        /* ===== REKAP BANGGA KENCANA: 8 kolom saja — fit container, tanpa scroll horizontal ===== */
        table#dallap-tree { min-width: 0; }
        /* ===== PUS & KB AKTIF: 4 kolom, isi lebar penuh, angka tidak terlipat ===== */
        table#puskb-tree { width: 100%; min-width: 0; }
        table#puskb-tree thead th { white-space: nowrap; }
        table#puskb-tree tbody td.wilayah { min-width: 0; max-width: none; }
        table#puskb-tree tbody td.n { min-width: 130px; }

		/* ===== YAN-KB REPORT (iframe srcdoc, mirror SIGA) ===== */
		.yankb-frame { overflow-x: auto; }
		.yankb-frame iframe {
		    display: block; width: 100%; min-width: 1008px; height: 70vh;
		    border: 0; background: #fff;
		}
		/* ===== YAN-KB NATIVE TABLE (gaya krs-tree, fallback iframe) ===== */
\t\ttable.krs-tree.krs-yankb { min-width: 960px; font-size: .72rem; }
		table.krs-tree.krs-yankb thead th { white-space: nowrap; }
		table.krs-tree.krs-yankb thead tr:nth-child(3) th { top: 4rem; z-index: 2; }
		table.krs-tree.krs-yankb thead tr:nth-child(4) th { top: 6rem; z-index: 2; }
\t\ttable.krs-tree.krs-yankb tbody td { padding: .3rem .35rem; }
		table.krs-tree.krs-yankb tbody td.n { text-align: right; }
		table.krs-tree.krs-yankb tbody td.sep { border-right: 2px solid #9aabc4; }
		table.krs-tree.krs-yankb tbody td.wilayah { min-width: 160px; max-width: 220px; }

		@media (max-width: 768px) {
            .hero { padding: 3rem 0 2.5rem; }
            .hero-meta { gap: 1rem; }
            .section-head { flex-direction: column; align-items: flex-start; }
            .stat-grid { gap: 0.75rem; }
        }

        /* ===== PETA SEBARAN (Leaflet) ===== */
        .map-shell {
            background: var(--paper);
            border: 1px solid var(--border);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: var(--shadow-md);
        }
        #map-kecamatan {
            height: 500px;
            width: 100%;
            position: relative;
            z-index: 0;
            background: var(--paper-2);
        }
        .map-indicator-bar {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: .5rem;
            padding: .8rem 1.1rem;
            background: var(--paper);
            border-bottom: 1px solid var(--border);
        }
        .map-indicator-bar label {
            margin: 0;
            font-size: .82rem;
            font-weight: 600;
            color: var(--ink-soft);
        }
        .map-indicator-bar select {
            font-family: inherit;
            font-size: .82rem;
            color: var(--ink);
            background: var(--paper-2);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: .4rem 2.1rem .4rem .75rem;
            min-width: 230px;
            cursor: pointer;
            outline: none;
            transition: border-color .15s ease;
        }
        .map-indicator-bar select:hover,
        .map-indicator-bar select:focus { border-color: var(--primary); }
        .map-indicator-bar select:focus { box-shadow: 0 0 0 3px rgba(0,91,172,.35); }
        .map-indicator-bar .lg-label-ind {
            margin-left: auto;
            font-size: .78rem;
            color: var(--muted);
            font-style: italic;
        }
        .leaflet-container {
            background: var(--paper-2);
            font-family: inherit;
        }
        .map-shell .map-legend {
            display: flex;
            flex-wrap: wrap;
            gap: 1.25rem;
            align-items: center;
            padding: .8rem 1.1rem;
            background: var(--paper);
            border-top: 1px solid var(--border);
            font-size: .8rem;
            color: var(--ink-soft);
        }
        .map-shell .map-legend .dot {
            display: inline-block;
            width: 11px; height: 11px;
            border-radius: 50%;
            margin-right: .4rem;
            vertical-align: middle;
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
            color: var(--ink);
            font-variant-numeric: tabular-nums;
        }
        .map-shell .map-legend .lg-sep {
            width: 1px; height: 16px;
            background: var(--border);
        }
        .map-popup-kec { font-family: 'Poppins', sans-serif; }
        .map-popup-kec .pk-name {
            font-weight: 700; color: var(--ink); font-size: .95rem;
            margin-bottom: .35rem;
        }
        .map-popup-kec .pk-row {
            display: flex; justify-content: space-between; gap: 1rem;
            font-size: .82rem; color: var(--ink-soft);
            padding: .12rem 0;
        }
        .map-popup-kec .pk-row b { color: var(--ink); font-variant-numeric: tabular-nums; }
        .map-popup-kec .pk-link {
            display: block; margin-top: .45rem;
            font-size: .8rem; font-weight: 600; color: var(--primary);
        }
        .map-popup-kec .pk-link:hover { color: var(--primary-d); }
        .leaflet-popup-content a { color: var(--primary); }
        .map-placeholder {
            height: 500px;
            display: flex; align-items: center; justify-content: center;
            background: var(--paper-2);
            color: var(--muted);
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
                <img src="<?= base_url('assets/dist/img/erdekatala.png') ?>" alt="Erdekatala" class="brand-mark">
                <span>
                    Erdekatala
                    <small>Kabupaten Tanah Laut</small>
                </span>
            </a>
            <div class="nav-right d-none d-md-flex">
                <a href="<?= site_url('/api/docs') ?>" class="nav-link-sikada"><i class="bi bi-code-slash me-1"></i>API</a>
                <button class="nav-theme-btn" type="button" aria-label="Ganti mode gelap/terang" title="Mode gelap/terang">
                    <svg class="ic-sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>
                    <svg class="ic-moon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
                </button>
                <a href="<?= site_url('/login') ?>" class="btn-login"><i class="bi bi-box-arrow-in-right me-1"></i>Masuk</a>
            </div>
            <a href="<?= site_url('/login') ?>" class="btn-login d-md-none"><i class="bi bi-box-arrow-in-right"></i></a>
        </div>
    </nav>

    <!-- ===== HERO / HEADER DATA ===== -->
    <header class="hero">
        <div class="container">
            <div class="hero-top">
                <div class="hero-copy">
                    <span class="eyebrow">Portal Data Kependudukan</span>
                    <h1>Rumah Data Kependudukan Tanah Laut</h1>
                    <p class="lead">Erdekatala (<em>Elektronik Rumah Data Kependudukan Tanah Laut</em>) — data agregat jiwa, kartu keluarga, pendidikan, pekerjaan, piramida penduduk, serta cakupan JKN/BPJS dan KB-PUS tingkat desa.</p>
                </div>
                <div class="hero-status">
                    <span class="status-pill">
                        <i class="bi bi-database-check"></i>
                        <?php if ($profilMode === 'dukcapil'): ?>
                            Dukcapil · DKB Semester <?= esc($romawiSemester((int) ($profilMeta['semester'] ?? 1))) ?> <?= (int) ($profilMeta['tahun'] ?? 2026) ?>
                        <?php elseif ($latestPeriod): ?>
                            Data terbaru <?= esc($bulanNama[$latestPeriod['bulan']] ?? '-') ?> <?= (int) $latestPeriod['tahun'] ?>
                        <?php else: ?>
                            Belum ada data
                        <?php endif; ?>
                    </span>
                    <span class="status-pill muted"<?= in_array($profilMode, ['dukcapil', 'campuran'], true) ? ' title="' . esc($dkbLabel) . '"' : '' ?>>
                        <i class="bi bi-clock-history"></i>Sumber: <?= esc($profilMode === 'dukcapil'
                            ? 'Dukcapil'
                            : ($profilMode === 'campuran'
                                ? 'Dukcapil + laporan desa'
                                : 'laporan desa')) ?>
                    </span>
                </div>
            </div>

            <!-- Filter wilayah -->
            <form method="GET" action="<?= site_url('/') ?>" class="filter-bar">
                <div class="field">
                    <label for="sel-kecamatan">Kecamatan</label>
                    <select name="kecamatan" id="sel-kecamatan" onchange="loadDesaBySlug(this.value); document.getElementById('sel-desa').value=''; this.form.submit()">
                        <option value="">— Semua Kecamatan —</option>
                        <?php foreach ($list_kecamatan as $k):
                            $slug_k = strtolower(str_replace(' ', '-', $k['nama_kecamatan']));
                            // Terpilih bila slug cocok, ATAU bila ?desa= di-resolve global
                            // (kecamatan di-derive dari desa) sehingga $filter_kec_slug kosong.
                            $kecSelected = ($filterKecSlugLower === $slug_k)
                                || ($idKecFilter && $filterKecNama && $filterKecNama === $k['nama_kecamatan']);
                        ?>
                            <option value="<?= esc($slug_k) ?>" <?= $kecSelected ? 'selected' : '' ?>>
                                <?= esc($k['nama_kecamatan']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field">
                    <label for="sel-desa">Desa</label>
                    <select name="desa" id="sel-desa" onchange="this.form.submit()" <?= empty($idKecFilter) ? 'disabled' : '' ?>>
                        <option value="">— Semua Desa —</option>
                        <?php foreach ($list_desa as $d):
                            $slug_d = strtolower(str_replace(' ', '-', $d['nama_desa']));
                        ?>
                            <option value="<?= esc($slug_d) ?>" <?= $filterDesaSlugLower === $slug_d ? 'selected' : '' ?>>
                                <?= esc($d['nama_desa']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn-filter"><i class="bi bi-funnel me-1"></i>Terapkan</button>
                <a href="<?= site_url('/') ?>" class="btn-reset"><i class="bi bi-arrow-counterclockwise me-1"></i>Reset</a>
                <?php // Pertahankan filter non-wilayah saat wilayah diganti (kalau tidak,
                      // memilih kecamatan akan menghapus ?sumber= & ?bulan= diam-diam).
                      // Default (dukcapil) ≡ tanpa ?sumber= → URL tetap bersih; hanya
                      // pilihan eksplisit desa/campuran yang dipertahankan. ?>
                <?php if (in_array($profilSumberFilter, ['desa', 'campuran'], true)): ?>
                    <input type="hidden" name="sumber" value="<?= esc($profilSumberFilter) ?>">
                <?php endif; ?>
                <?php if (!empty($yankbBulan)): ?>
                    <input type="hidden" name="bulan" value="<?= (int) $yankbBulan ?>">
                <?php endif; ?>
            </form>
        </div>
    </header>

    <main class="container">
        <?php if ($kecUnresolved || $desaUnresolved): ?>
            <!-- Filter wilayah tidak bisa di-resolve → beri umpan balik + skop yang sebenarnya -->
            <div class="filter-warn">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <p>
                    <?php if ($kecUnresolved): ?>
                        Kecamatan &ldquo;<?= esc((string) $filter_kec_slug) ?>&rdquo; tidak dikenali atau tidak dipublikasikan.
                        <?php if ($idDesaFilter): ?>
                            Menampilkan data <strong>Desa <?= esc((string) $filterDesaNama) ?></strong> (filter desa tetap diterapkan, kecamatan diabaikan).
                        <?php else: ?>
                            Menampilkan data <strong>seluruh kabupaten</strong>.
                        <?php endif; ?>
                    <?php else: ?>
                        <?php if ($idKecFilter): ?>
                            Desa &ldquo;<?= esc((string) $filter_desa_slug) ?>&rdquo; tidak ditemukan<?= $filterKecNama ? ' di Kecamatan ' . esc($filterKecNama) : '' ?>.
                            Menampilkan data <strong>tingkat kecamatan</strong> (seluruh desa di kecamatan ini).
                        <?php else: ?>
                            Desa &ldquo;<?= esc((string) $filter_desa_slug) ?>&rdquo; tidak ditemukan.
                            Menampilkan data <strong>seluruh kabupaten</strong>.
                        <?php endif; ?>
                    <?php endif; ?>
                </p>
            </div>
        <?php endif; ?>

        <?php if ($jiwaTotal === 0 && $kkTotal === 0): ?>
            <div class="empty-state">
                <i class="bi bi-inboxes"></i>
                <h3>Belum ada data laporan</h3>
                <p>Tidak ada laporan pada wilayah yang dipilih. Silakan pilih kecamatan/desa lain atau hubungi admin desa untuk melakukan input laporan.</p>
            </div>
        <?php else: ?>

        <!-- ===== RINGKASAN CAKUPAN ===== -->
        <section class="block" style="padding-top: 2rem;">
            <div class="section-head">
                <h2>Ringkasan Data</h2>
                <span class="crumb">Skop: <strong><?= esc($pendudukKey) ?></strong> - <?= esc($scopeDetail) ?></span>
            </div>
            <div class="stat-grid">
                <?php
                    $cov          = $coverage ?? ['total_kecamatan'=>0,'total_desa'=>0,'kecamatan_lapor'=>0,'desa_lapor'=>0];
                    $desaTotal    = (int) $cov['total_desa'];
                    $desaLapor    = (int) $cov['desa_lapor'];
                    $kecTotal     = (int) $cov['total_kecamatan'];
                    $kecLapor     = (int) $cov['kecamatan_lapor'];
                    $desaPct      = $desaTotal > 0 ? round($desaLapor / $desaTotal * 100) : 0;
                    $pendudukLbl  = $pendudukKey === 'Desa' ? 'Desa ini' : 'Wilayah ini';
                    $jknPunya     = (int)($jkn_bpjs['PBI'] ?? 0) + (int)($jkn_bpjs['Non PBI'] ?? 0);
                    $jknPct       = $jiwaTotal > 0 ? round($jknPunya / $jiwaTotal * 100) : 0;
                ?>
                <div class="stat-card">
                    <i class="bi bi-check2-circle icon"></i>
                    <div class="label">Cakupan Laporan</div>
                    <div class="value"><?= $desaLapor ?><span class="unit">/ <?= $desaTotal ?> desa</span></div>
                    <div class="meter" role="progressbar" aria-valuenow="<?= $desaPct ?>" aria-valuemin="0" aria-valuemax="100" aria-label="Cakupan laporan desa"><span style="width: <?= $desaPct ?>%"></span></div>
                    <div class="sub"><?= $kecLapor ?> dari <?= $kecTotal ?> kecamatan melaporkan</div>
                </div>
                <div class="stat-card">
                    <i class="bi bi-people-fill icon"></i>
                    <div class="label">Total Jiwa</div>
                    <div class="value"><?= number_format($jiwaTotal, 0, ',', '.') ?></div>
                    <div class="sub"><b><?= number_format($jiwaL, 0, ',', '.') ?> L</b> · <i><?= number_format($jiwaP, 0, ',', '.') ?> P</i></div>
                </div>
                <div class="stat-card">
                    <i class="bi bi-house-heart-fill icon"></i>
                    <div class="label">Kartu Keluarga</div>
                    <div class="value"><?= number_format($kkTotal, 0, ',', '.') ?></div>
                    <div class="sub">Rata-rata <b><?= $kkTotal > 0 ? round($jiwaTotal / $kkTotal, 1) : 0 ?></b> jiwa/KK</div>
                </div>
                <div class="stat-card">
                    <i class="bi bi-shield-check icon"></i>
                    <div class="label">Cakupan JKN/BPJS</div>
                    <div class="value"><?= $jknPct ?><span class="unit">%</span></div>
                    <div class="meter pink" role="progressbar" aria-valuenow="<?= $jknPct ?>" aria-valuemin="0" aria-valuemax="100" aria-label="Cakupan JKN BPJS"><span style="width: <?= $jknPct ?>%"></span></div>
                    <div class="sub"><?= number_format($jknPunya, 0, ',', '.') ?> dari <?= number_format($jiwaTotal, 0, ',', '.') ?> jiwa</div>
                </div>
            </div>

            <!-- Catatan konteks data -->
            <div class="note-strip">
                <i class="bi bi-info-circle-fill"></i>
                <p>
                    <?php if ($profilMode === 'dukcapil'): ?>
                        Data <?= esc($pendudukLbl) ?> bersumber dari snapshot Dukcapil Kab. Tanah Laut.
                        <span class="src-official"><?= esc($dkbLabel) ?></span>
                    <?php elseif ($profilMode === 'campuran'): ?>
                        Data <?= esc($pendudukLbl) ?> gabungan: laporan desa + snapshot Dukcapil untuk desa yang belum lapor.
                        <span class="src-official"><?= esc($dkbLabel) ?></span>
                    <?php else: ?>
                        Data <?= esc($pendudukLbl) ?> diturunkan dari laporan desa yang sudah diinput admin desa.
                    <?php endif; ?>
                    <?php if ($dokumenCampuran): ?>
                        <strong>KIA</strong> &amp; <strong>Akta Cerai</strong> dikecualikan: keduanya bersumber dari snapshot
                        Dukcapil Kab. Tanah Laut, karena laporan desa tidak memuatnya.
                        <span class="src-official"><?= esc($dkbLabel) ?></span>
                    <?php endif; ?>
                    Filter wilayah di atas menyesuaikan <strong>Ringkasan Data, Indikator Turunan, Profil Kependudukan, dan Peta Sebaran Spasial</strong>.
                    Peta memakai sumber angka yang sama dengan kartu profil (<?= esc($map_meta['sumber_label'] ?? 'snapshot Dukcapil') ?>).
                    Tabel Monitoring BKKBN (KRS/YanKB/Dallap) berasal dari sumber pihak ketiga (SIGA) dan mengikuti filter kecamatan, dengan cakupan tersendiri — lihat keterangan di bagian itu.
                    <?php if (!empty($latestPeriod) && $profilMode !== 'dukcapil'): ?>
                        <?php
                            $lpDesa   = (int) ($latestPeriod['desa_lapor'] ?? 0);
                            $lpTotal  = (int) ($latestPeriod['desa_total'] ?? 0);
                            $lpCampur = $lpTotal > $lpDesa;
                        ?>
                        Periode laporan terbaru: <strong><?= esc($bulanNama[$latestPeriod['bulan']] ?? '-') ?> <?= (int) $latestPeriod['tahun'] ?></strong><?php if ($lpCampur): ?> (<?= $lpDesa ?> dari <?= $lpTotal ?> desa; sisanya memakai laporan terakhirnya)<?php endif; ?>.
                    <?php endif; ?>
                </p>
            </div>
        </section>

        <!-- ===== INDIKATOR TURUNAN ===== -->
        <section class="block" style="padding-top: 0;">
            <div class="section-head">
                <h2>Indikator Turunan</h2>
                <span class="crumb">Dihitung dari piramida usia &amp; jumlah KK · <strong><?= esc($pendudukKey) ?></strong></span>
            </div>
            <div class="stat-grid cols-5">
                <div class="stat-card alt-blue">
                    <i class="bi bi-gender-ambiguous icon"></i>
                    <div class="label">Rasio Jenis Kelamin</div>
                    <div class="value"><?= $sexRatio !== null ? number_format($sexRatio, 1, ',', '.') : '—' ?></div>
                    <div class="sub">laki-laki per 100 perempuan</div>
                </div>
                <div class="stat-card alt-pink">
                    <i class="bi bi-diagram-3 icon"></i>
                    <div class="label">Rasio Ketergantungan</div>
                    <div class="value"><?= $depRatio !== null ? number_format($depRatio, 1, ',', '.') : '—' ?><span class="unit">%</span></div>
                    <div class="sub">usia non-produktif per 100 usia produktif</div>
                </div>
                <div class="stat-card alt-green">
                    <i class="bi bi-briefcase icon"></i>
                    <div class="label">Usia Produktif</div>
                    <div class="value"><?= $produktifPct !== null ? number_format($produktifPct, 1, ',', '.') : '—' ?><span class="unit">%</span></div>
                    <div class="sub"><?= $produktif !== null ? $fmt($produktif) . ' jiwa (15–64 th)' : 'data usia belum tersedia' ?></div>
                </div>
                <div class="stat-card">
                    <i class="bi bi-people icon"></i>
                    <div class="label">Rata-rata Jiwa / KK</div>
                    <div class="value"><?= $jiwaPerKk !== null ? number_format($jiwaPerKk, 2, ',', '.') : '—' ?></div>
                    <div class="sub"><?= $fmt($jiwaTotal) ?> jiwa / <?= $fmt($kkTotal) ?> KK</div>
                </div>
                <div class="stat-card alt-blue">
                    <i class="bi bi-house-check icon"></i>
                    <div class="label">Cakupan KK Fisik</div>
                    <div class="value"><?= $kkFisikPct !== null ? number_format($kkFisikPct, 1, ',', '.') : '—' ?><span class="unit">%</span></div>
                    <div class="meter" role="progressbar" aria-valuenow="<?= $kkFisikPct !== null ? (int) round($kkFisikPct) : 0 ?>" aria-valuemin="0" aria-valuemax="100" aria-label="Cakupan kepemilikan kartu keluarga"><span style="width: <?= $kkFisikPct !== null ? min(100, max(0, $kkFisikPct)) : 0 ?>%"></span></div>
                    <div class="sub"><?= $fmt($kkFisikVal) ?> dari <?= $fmt($kkTotal) ?> KK</div>
                </div>
            </div>
            <p style="font-size:.78rem;color:var(--muted);margin:-1.5rem 0 0;">
                Rasio ketergantungan = (usia 0–14 + 65 tahun ke atas) ÷ usia 15–64 × 100.
                Usia produktif = penduduk 15–64 tahun. Angka dihitung dari piramida umur dan jumlah KK pada wilayah yang dipilih.
            </p>
        </section>

        <!-- ===== BAGIAN 1: PROFIL KEPENDUDUKAN ===== -->
        <section class="block" style="padding-top: 0;">
            <div class="section-head">
                <h2><span class="num">1</span>Profil Kependudukan</h2>
                <span class="crumb">
                    <?php
                    // Sumber data Bagian 1 — dinamis dari profilMeta (Desain B).
                    // $profilMode sudah dihitung di atas file (dipakai juga hero & note-strip).
                    $sumberProfil = $profilMode;
                    $sumberBadge  = [
                        'desa'     => ['label' => 'Laporan Desa',             'icon' => 'bi-clipboard-check'],
                        'laporan'  => ['label' => 'Laporan Desa',             'icon' => 'bi-clipboard-check'],
                        'dukcapil' => ['label' => 'Dukcapil Kab. Tanah Laut', 'icon' => 'bi-building-check'],
                        'campuran' => ['label' => 'Dukcapil + Laporan Desa',  'icon' => 'bi-layers-half'],
                    ][$sumberProfil] ?? ['label' => 'Laporan Desa', 'icon' => 'bi-clipboard-check'];
                    ?>
                    <span class="tag src-<?= $sumberProfil === 'dukcapil' ? 'dukcapil' : ($sumberProfil === 'campuran' ? 'dukcapil' : 'desa') ?>">
                        <i class="bi <?= esc($sumberBadge['icon']) ?>"></i><?= esc($sumberBadge['label']) ?>
                    </span>
                    <?php if ($sumberProfil === 'campuran'): ?>
                        <span class="tag src-desa" style="margin-left:.35rem;">
                            <i class="bi bi-info-circle"></i><?= (int) ($profilMeta['laporan_count'] ?? 0) ?> desa lapor · <?= (int) ($profilMeta['dukcapil_count'] ?? 0) ?> Dukcapil
                        </span>
                    <?php endif; ?>
                    <strong><?= esc($pendudukKey) ?></strong>
                </span>
            </div>
            <div class="group-filter" style="margin-top:.5rem;">
                <label for="sumber-data">Sumber data</label>
                <select id="sumber-data" class="form-select" style="width:auto;" aria-label="Filter sumber data profil kependudukan">
                    <?php
                    $sumberNow = $profilSumberFilter; // 'dukcapil' (default) | 'desa' | 'campuran'
                    // Berapa desa di scope yang punya laporan desa vs hanya Dukcapil —
                    // untuk keterangan opsi "Campuran".
                    $scopeLaporan  = (int) ($profilMeta['laporan_count_scope'] ?? 0);
                    $scopeDukcapil = (int) ($profilMeta['dukcapil_count_scope'] ?? 0);
                    $scopeTotal    = (int) ($profilMeta['total_scope'] ?? 0);
                    ?>
                    <option value="dukcapil" <?= $sumberNow === 'dukcapil' ? 'selected' : '' ?>>Hanya Dukcapil (default)</option>
                    <option value="desa"     <?= $sumberNow === 'desa'     ? 'selected' : '' ?>>Hanya Laporan Desa</option>
                    <option value="campuran" <?= $sumberNow === 'campuran' ? 'selected' : '' ?>>Campuran — Laporan Desa + Dukcapil<?= $scopeTotal ? ' (' . $scopeLaporan . ' desa lapor · ' . $scopeDukcapil . ' Dukcapil)' : '' ?></option>
                </select>
                <?php if ($sumberNow === 'dukcapil'): ?>
                    <span class="sumber-hint">
                        <i class="bi bi-info-circle"></i>
                        Data profil memakai <strong>snapshot resmi Dukcapil</strong> (<?= esc($dkbLabel) ?>).
                        <?php if ($kelompokAAktif): ?>KIA &amp; Akta Cerai memang selalu dari Dukcapil.<?php endif; ?>
                    </span>
                <?php elseif ($sumberNow === 'campuran'): ?>
                    <span class="sumber-hint">
                        <i class="bi bi-info-circle"></i>
                        Mode campuran memakai laporan desa untuk desa yang sudah lapor
                        (<?= $scopeLaporan ?> desa) dan snapshot Dukcapil untuk sisanya (<?= $scopeDukcapil ?> desa).
                    </span>
                <?php endif; ?>
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
                                        <p>Kepemilikan KTP-el, akta lahir, akta nikah, KK fisik, KIA &amp; akta cerai</p>
                                    </div>
                                    <span class="badge-nde"><?= count($dokumen) ?> jenis</span>
                                </div>
                                <div class="chart-wrap"><div id="zc-dokumen" class="zc zc-tall"></div></div>
                                <?php if ($kelompokAAktif): ?>
                                    <!-- Penanda provenance: KIA & Akta Cerai bukan dari sumber mode utama -->
                                    <div class="src-note">
                                        <i class="bi bi-tags-fill"></i>
                                        <p>
                                            <?php if ($profilMode === 'dukcapil'): ?>
                                                <strong>Semua <?= count($dokumen) ?> indikator</strong> bersumber dari snapshot
                                                <strong>Dukcapil Kab. Tanah Laut</strong>.
                                            <?php else: ?>
                                                <strong>KIA</strong> &amp; <strong>Akta Cerai</strong> bersumber dari snapshot
                                                <strong>Dukcapil Kab. Tanah Laut</strong>,
                                                bukan dari <?= $profilMode === 'campuran' ? 'gabungan' : 'laporan desa' ?> seperti
                                                KTP-el, Akta Lahir, Akta Nikah &amp; KK Fisik. Laporan desa tidak memuat kedua indikator ini.
                                            <?php endif; ?>
                                            <span class="src-official"><?= esc($dkbLabel) ?></span>
                                        </p>
                                    </div>
                                <?php endif; ?>
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
                                <?php foreach ($dokumen as $label => $val):
                                    $dokSrc = $dokumenSumber[$label] ?? 'utama';
                                    $dokSrcIsDukcapil = $dokSrc === 'dukcapil';
                                    $dokSrcLabel = $dokSrcIsDukcapil
                                        ? 'Dukcapil'
                                        : ($profilMode === 'dukcapil' ? 'Dukcapil' : ($profilMode === 'campuran' ? 'laporan/Dukcapil' : 'laporan desa'));
                                    // Untuk indikator bersumber Dukcapil, tooltip chip memuat
                                    // label sumber RESMI (DKB) — teks panjang tidak muat di chip.
                                    $dokSrcTitle = $dokSrcIsDukcapil ? $dkbLabel : 'Sumber data indikator ini';
                                ?>
                                <tr>
                                    <td class="dt-cat">Dokumen</td>
                                    <td class="dt-sub"><?= esc($label) ?><span class="src-chip <?= $dokSrcIsDukcapil ? 'dukcapil' : 'desa' ?>" title="<?= esc($dokSrcTitle) ?>"><?= esc($dokSrcLabel) ?></span></td>
                                    <td class="dt-num"><?= $fmt($val) ?></td>
                                    <td class="dt-num dt-pct"><?= $jiwaTotal > 0 ? number_format($val / $jiwaTotal * 100, 1, ',', '.') . '%' : '—' ?></td>
                                </tr>
                                <?php endforeach; ?>
                                <tr>
                                    <td class="dt-cat">KB &amp; PUS · Laporan Desa</td>
                                    <td class="dt-sub">KB Aktif</td>
                                    <td class="dt-num"><?= $fmt($kbAktif) ?></td>
                                    <td class="dt-num dt-pct"><?= $pus > 0 ? number_format($kbAktif / $pus * 100, 1, ',', '.') . '%' : '—' ?></td>
                                </tr>
                                <tr>
                                    <td class="dt-cat">KB &amp; PUS · Laporan Desa</td>
                                    <td class="dt-sub">Jumlah PUS</td>
                                    <td class="dt-num"><?= $fmt($pus) ?></td>
                                    <td class="dt-num dt-pct">—</td>
                                </tr>
                                <tr>
                                    <td class="dt-cat">KB &amp; PUS · Laporan Desa</td>
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

        <?php $mapIndicatorGroups = include APPPATH . 'Views/public/partials/map_indicators.php'; ?>

        <!-- ===== BAGIAN 2: SEBARAN SPASIAL (Leaflet) ===== -->
        <section class="block" style="padding-top: 0;" id="peta-sebaran">
            <div class="section-head">
                <h2><span class="num">2</span>Sebaran Spasial</h2>
                <span class="crumb">
                    <?php
                        $mapSumberTag = [
                            'desa'     => ['cls' => 'src-desa',     'icon' => 'bi-clipboard-check', 'label' => 'Laporan Desa'],
                            'dukcapil' => ['cls' => 'src-dukcapil', 'icon' => 'bi-building-check',  'label' => 'Dukcapil Kab. Tanah Laut'],
                            'campuran' => ['cls' => 'src-dukcapil', 'icon' => 'bi-layers-half',     'label' => 'Dukcapil + Laporan Desa'],
                        ][$map_meta['sumber'] ?? 'dukcapil'] ?? ['cls' => 'src-dukcapil', 'icon' => 'bi-building-check', 'label' => 'Dukcapil Kab. Tanah Laut'];
                    ?>
                    <span class="tag <?= esc($mapSumberTag['cls']) ?>"><i class="bi <?= esc($mapSumberTag['icon']) ?>"></i><?= esc($mapSumberTag['label']) ?></span>
                    <strong><?= count($map_kecamatan) ?></strong> kecamatan · Indikator per kecamatan
                </span>
            </div>
            <div class="note-strip" style="margin-top:.75rem;">
                <i class="bi bi-map-fill"></i>
                <p>
                    <?php if (($map_meta['sumber'] ?? 'dukcapil') === 'desa'): ?>
                        Peta menampilkan <strong><?= count($map_kecamatan) ?> kecamatan</strong> dengan angka dari
                        <strong>laporan desa</strong> (mengikuti filter <em>Sumber data</em>).
                    <?php elseif (($map_meta['sumber'] ?? 'dukcapil') === 'campuran'): ?>
                        Peta menampilkan <strong><?= count($map_kecamatan) ?> kecamatan</strong> dengan angka <strong>gabungan</strong>
                        laporan desa + snapshot Dukcapil (mengikuti filter <em>Sumber data</em>).
                    <?php else: ?>
                        Peta menampilkan <strong><?= count($map_kecamatan) ?> kecamatan</strong> dengan angka dari
                        <strong>snapshot Dukcapil</strong> (sumber yang sama dengan kartu profil).
                    <?php endif; ?>
                    <?php if ($idKecFilter): ?>
                        Peta dibatasi pada <strong>Kecamatan <?= esc((string) $filterKecNama) ?></strong><?= $idDesaFilter ? ' — desa ' . esc((string) $filterDesaNama) . ' disorot' : '' ?>, mengikuti filter wilayah.
                    <?php endif; ?>
                </p>
            </div>
            <div class="map-shell">
                <div class="map-indicator-bar">
                    <label for="ind-kec-select">Indikator:</label>
                    <select id="ind-kec-select">
                        <?php foreach ($mapIndicatorGroups as $grup): ?>
                        <optgroup label="<?= esc($grup['group']) ?>">
                            <?php foreach ($grup['items'] as $it): ?>
                            <option value="<?= esc($it['field']) ?>"><?= esc($it['label']) ?></option>
                            <?php endforeach; ?>
                        </optgroup>
                        <?php endforeach; ?>
                    </select>
                    <span class="lg-label-ind" id="lg-indicator-name"></span>
                </div>
                <div id="map-kecamatan"></div>
            <div class="map-legend" id="map-legend-kec">
                <span class="lg-item"><span class="swatch" style="background:#2d6cdf;"></span>Kecamatan terpilih</span>
                <span class="lg-item"><span class="swatch" style="background:#d5dfee;"></span>Tidak ada data</span>
                <span class="lg-sep"></span>
                <span class="lg-item lg-ind-label"></span>
                <span class="lg-item" id="lg-0"><span class="swatch"></span><b></b></span>
                <span class="lg-item" id="lg-1"><span class="swatch"></span><b></b></span>
                <span class="lg-item" id="lg-2"><span class="swatch"></span><b></b></span>
                <span class="lg-item" id="lg-3"><span class="swatch"></span><b></b></span>
                <span class="lg-item" id="lg-4"><span class="swatch"></span><b></b></span>
                <span class="ms-auto text-muted">Klik poligon untuk detail</span>
            </div>
            </div>
        </section>

        <!-- ===== BAGIAN 3: MONITORING BKKBN (mirror SIGA) ===== -->
        <details class="block group-collapse" style="padding-top: 0;" id="monitoring-bkkbn">
            <summary class="section-head group-summary">
                <h2>
                    <span class="num">3</span>Monitoring BKKBN
                    <span class="tag"><i class="bi bi-box-arrow-up-right"></i>Data pihak ketiga</span>
                </h2>
                <span class="crumb">
                    SIGA BKKBN · Tahun <?= $yankbTahun ?> · <?= esc($bkkbnScope) ?>
                    <span class="summary-cta">
                        <span class="cta-open">Tampilkan data</span>
                        <span class="cta-close">Sembunyikan data</span>
                        <i class="bi bi-chevron-down summary-chev" aria-hidden="true"></i>
                    </span>
                </span>
            </summary>
            <div class="group-body">
            <?php if ($idKecFilter): ?>
            <div class="note-strip" style="margin:0 0 .75rem;">
                <i class="bi bi-info-circle-fill"></i>
                <p>
                    Data BKKBN bersumber dari SIGA (pihak ketiga). Tab <strong>KRS</strong>, <strong>Cakupan YanKB</strong>,
                    <strong>Pelayanan KB Baru</strong>, dan <strong>Dallap/PUS &amp; KB Aktif</strong> kini
                    <strong>mengikuti filter kecamatan</strong> — menampilkan <strong>Kecamatan <?= esc((string) $filterKecNama) ?></strong> saja.
                    Tab YanKB &amp; Dallap tetap <strong>tidak difilter per desa</strong> (cakupan per kecamatan).
                </p>
            </div>
            <?php endif; ?>
            <div class="group-filter">
                <label for="yankb-bulan">Bulan laporan</label>
                <select id="yankb-bulan" class="form-select" style="width:auto;" aria-label="Filter bulan laporan YanKB">
                    <?php foreach ($bulanNama as $b => $nama): ?>
                    <option value="<?= $b ?>" <?= $b === $yankbBulan ? 'selected' : '' ?>><?= $nama ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <ul class="nav nav-tabs sikada-tabs" id="sigaTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-krs" type="button" role="tab" aria-selected="true">
                        <i class="bi bi-diagram-3-fill me-1"></i>Monitoring VERVAL KRS
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-yankb" type="button" role="tab" aria-selected="false">
                        <i class="bi bi-clipboard-data-fill me-1"></i>Cakupan Laporan Tempat Pelayanan KB
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-yankb21" type="button" role="tab" aria-selected="false">
                        <i class="bi bi-people-fill me-1"></i>Jumlah Pelayanan Peserta KB Baru
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-dallap" type="button" role="tab" aria-selected="false">
                        <i class="bi bi-grid-3x3-gap-fill me-1"></i>Rekap Program Bangga Kencana
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-puskb" type="button" role="tab" aria-selected="false">
                        <i class="bi bi-heart-pulse-fill me-1"></i>PUS &amp; KB Aktif (SIGA)
                    </button>
                </li>

            </ul>
            <div class="tab-content">
            <div class="tab-pane fade show active" id="tab-krs" role="tabpanel">

            <?php if (!empty($krsError)): ?>
                <?php $krsHasData = !empty($krsKecamatan); ?>
                <div class="alert <?= $krsHasData ? 'alert-info' : 'alert-warning' ?> py-2 px-3 mb-3" style="font-size:.9rem;">
                    <i class="bi bi-<?= $krsHasData ? 'info-circle' : 'exclamation-triangle' ?> me-1"></i><?= esc($krsError) ?>
                </div>
            <?php endif; ?>

            <?php if (empty($krsKecamatan)): ?>
                <p class="text-muted">Data Monitoring KRS belum tersedia.</p>
            <?php else:
                $renderKrsCells = function (array $r) use ($krsFmt): string {
                    // Urutan & label kolom mengikuti "Monitoring Verval KRS (Rekap).pdf"
                    return
                        '<td class="n">' . $krsFmt($r['kelurahanAda'] ?? 0) . '</td>' .
                        '<td class="n">' . $krsFmt($r['kelurahanTarget'] ?? 0) . '</td>' .
                        '<td class="n">' . $krsFmt($r['kelurahanVerval'] ?? 0) . '</td>' .
                        '<td class="n">' . number_format((float) ($r['cakupanKelurahanVerval'] ?? 0), 2, '.', '') . '</td>' .
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
                        '<td class="n">' . $krsFmt($r['sasaranPrioritas'] ?? 0) . '</td>' .
                        '<td class="n">' . $krsFmt($r['prioritasTerverval'] ?? 0) . '</td>' .
                        '<td class="n">' . $krsFmt($r['adaBaruLainTerverval'] ?? 0) . '</td>' .
                        '<td class="n">' . $krsFmt($r['totalVervalAda'] ?? 0) . '</td>' .
                        '<td class="n">' . $krsFmt($r['totalVervalBaru'] ?? 0) . '</td>' .
                        // (22) TOTAL: field `total` dari BKKBN selalu "0"/absen (PDF Rekap cetak null di kolom ini) → tampilkan '-' tanpa informasi
                        '<td class="n">' . (($r['total'] ?? null) === null || $r['total'] === '' || (int) str_replace([',', '.'], '', (string) $r['total']) === 0 ? '-' : $krsFmt($r['total'])) . '</td>' .
                        '<td class="n">' . $krsFmt($r['keluargaSasaranCatin'] ?? 0) . '</td>';
                };
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
                            <!-- Header 5 baris, mengikuti "Monitoring Verval KRS (Rekap).pdf" -->
                            <tr class="krs-grp">
                                <th colspan="17">Jumlah Sasaran KRS (Baseline)</th>
                                <th colspan="7">Jumlah Sasaran KRS Verval</th>
                            </tr>
                            <tr>
                                <th rowspan="3">Nama Kecamatan</th>
                                <th rowspan="3">Jumlah Desa/Kelurahan (ada)</th>
                                <th rowspan="3">Jumlah Desa/Kelurahan (Target)</th>
                                <th rowspan="3">Jumlah Desa / Kelurahan Telah di Verval</th>
                                <th rowspan="3">Cakupan Desa / Kelurahan Telah di Verval</th>
                                <th colspan="3">PUS</th>
                                <th colspan="3">PUS Hamil / Ibu Hamil</th>
                                <th colspan="3">keluarga Punya Anak 0 - 23 Bulan</th>
                                <th colspan="3">Kelurga Punya Anak 24 - 59 Bulan</th>
                                <th colspan="3">TOTAL SASARAN PRIORITAS</th>
                                <th rowspan="3">Jumlah Kelurga Ada</th>
                                <th rowspan="3">Jumlah Keluarga Baru</th>
                                <th rowspan="3">TOTAL</th>
                                <th rowspan="3">Keluarga Sasaran Catin</th>
                            </tr>
                            <tr>
                                <th colspan="3">Verval</th>
                                <th rowspan="2">Sasaran</th>
                                <th colspan="2">Verval</th>
                                <th rowspan="2">Sasaran</th>
                                <th colspan="2">Verval</th>
                                <th rowspan="2">Sasaran</th>
                                <th colspan="2">Verval</th>
                                <th rowspan="2">Sasaran Prioritas</th>
                                <th rowspan="2">Prioritas Terverval</th>
                                <th rowspan="2">Selain Ada / Baru Terverval</th>
                            </tr>
                            <tr>
                                <th>Ada</th><th>Baru</th><th>Jumlah</th>
                                <th>Ada</th><th>Baru</th>
                                <th>Ada</th><th>Baru</th>
                                <th>Ada</th><th>Baru</th>
                            </tr>
                            <tr class="krs-idx">
                                <th>(1)</th><th>(2)</th><th>(3)</th><th>(4)</th><th>(4/3*100)</th>
                                <th>(5)</th><th>(6)</th><th>(7)</th><th>(8)</th><th>(9)</th><th>(10)</th>
                                <th>(11)</th><th>(12)</th><th>(13)</th><th>(14)</th><th>(15)</th><th>(16)</th>
                                <th>(17)</th><th>(18)</th><th>(19)</th><th>(20)</th><th>(21)</th><th>(22)</th><th>(23)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($krsKab): ?>
                            <tr class="krs-kab" data-level="kab">
                                <td class="wilayah">
                                    <button type="button" class="krs-toggle open" data-role="kab" aria-label="Toggle kabupaten">▼</button>
                                    <?= esc($krsKab['namaDaerah'] ?? 'TANAH LAUT') ?>
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
                    Sumber data: SIGA BKKBN (live, cache 30 menit). Kolom & nomor (1)–(23) mengikuti format laporan Monitoring Verval KRS (Rekap).
                </div>
            </div>
            <?php endif; ?>

            </div><!-- /tab-krs -->

			<!-- ===== TAB 2: CAKUPAN LAPORAN TEMPAT PELAYANAN KB ===== -->
			<div class="tab-pane fade" id="tab-yankb" role="tabpanel">
			    <?php if (!empty($yankbError)): ?>
			        <div class="alert alert-warning py-2 px-3 mb-3" style="font-size:.9rem;">
			            <i class="bi bi-exclamation-triangle me-1"></i><?= esc($yankbError) ?>
			        </div>
			    <?php endif; ?>

			    <?php if (empty($yankbData['rows']) && ($bkkbnIsKec || empty($yankbHtml))): ?>
			        <p class="text-muted">Data Cakupan Laporan Tempat Pelayanan KB<?= $bkkbnIsKec ? ' untuk ' . esc($bkkbnScope) : '' ?> belum tersedia.</p>
			    <?php else: ?>
			        <div class="krs-shell">
			            <div class="krs-toolbar">
			                <div>
			                    <h3 style="color:#ffffff;">Cakupan Laporan Tempat Pelayanan KB — <?= esc($bkkbnScope) ?></h3>
			                    <div class="meta">SIGA BKKBN · Bulan <?= $yankbBulanNama ?> <?= $yankbTahun ?><?php if (!empty($yankbUpdatedAt)): ?> · update <?= esc($yankbUpdatedAt) ?><?php endif; ?></div>
			                </div>
			                <span class="badge-live">LIVE MIRROR</span>
			            </div>
			            <?php
			            // YanKB Tabel1: kolom ke-3 dst = angka; baris non-total saja
			            $yankbZero = true;
			            foreach (($yankbData['rows'] ?? []) as $r) {
			                if (!empty($r['total'])) continue;
			                foreach (array_slice($r['cells'] ?? [], 2) as $c) {
			                    if (preg_match('/^\d+([.,]\d+)?$/', trim($c['t'] ?? '')) && (float) str_replace(',', '.', str_replace('.', '', trim($c['t'] ?? '0'))) > 0) {
			                        $yankbZero = false; break 2;
			                    }
			                }
			            }
			            ?>
			            <?= $dallapZeroBadge($yankbZero, $yankbBulan, 'Cakupan Laporan Tempat Pelayanan KB') ?>
			<?php if (!empty($yankbData['rows'])): ?>
			    <?= $renderYankbTable($yankbData) ?>
			<?php else: ?>
			            <div class="yankb-frame">
			                <iframe srcdoc="<?= esc($yankbHtml, 'attr') ?>" title="Cakupan Laporan Tempat Pelayanan KB" loading="lazy"></iframe>
			            </div>
			<?php endif; ?>
			            <div class="krs-hint">
			                Sumber data: SIGA BKKBN (live, cache 30 menit). Laporan YanKB bulanan per kecamatan.
			            </div>
			        </div>
			    <?php endif; ?>
			</div><!-- /tab-yankb -->

			<!-- ===== TAB 3: JUMLAH PELAYANAN PESERTA KB BARU (Tabel21) ===== -->
			<div class="tab-pane fade" id="tab-yankb21" role="tabpanel">
			    <?php if (!empty($yankb21Error)): ?>
			        <div class="alert alert-warning py-2 px-3 mb-3" style="font-size:.9rem;">
			            <i class="bi bi-exclamation-triangle me-1"></i><?= esc($yankb21Error) ?>
			        </div>
			    <?php endif; ?>

			    <?php if (empty($yankb21Data['rows']) && ($bkkbnIsKec || empty($yankb21Html))): ?>
			        <p class="text-muted">Data Jumlah Pelayanan Peserta KB Baru<?= $bkkbnIsKec ? ' untuk ' . esc($bkkbnScope) : '' ?> belum tersedia.</p>
			    <?php else: ?>
			        <div class="krs-shell">
			            <div class="krs-toolbar">
			                <div>
			                    <h3 style="color:#ffffff;">Jumlah Pelayanan Peserta KB Baru — <?= esc($bkkbnScope) ?></h3>
			                    <div class="meta">SIGA BKKBN · Bulan <?= $yankbBulanNama ?> <?= $yankbTahun ?><?php if (!empty($yankb21UpdatedAt)): ?> · update <?= esc($yankb21UpdatedAt) ?><?php endif; ?></div>
			                </div>
			                <span class="badge-live">LIVE MIRROR</span>
			            </div>
			            <?php
			            // YanKB Tabel21 (kumulatif "S.D BULAN"): kolom ke-3 dst = angka
			            $yankb21Zero = true;
			            foreach (($yankb21Data['rows'] ?? []) as $r) {
			                if (!empty($r['total'])) continue;
			                foreach (array_slice($r['cells'] ?? [], 2) as $c) {
			                    if (preg_match('/^\d+([.,]\d+)?$/', trim($c['t'] ?? '')) && (float) str_replace(',', '.', str_replace('.', '', trim($c['t'] ?? '0'))) > 0) {
			                        $yankb21Zero = false; break 2;
			                    }
			                }
			            }
			            ?>
			            <?= $dallapZeroBadge($yankb21Zero, $yankbBulan, 'Pelayanan Peserta KB Baru') ?>
			<?php if (!empty($yankb21Data['rows'])): ?>
			    <?= $renderYankbTable($yankb21Data) ?>
			<?php else: ?>
			            <div class="yankb-frame">
			                <iframe srcdoc="<?= esc($yankb21Html, 'attr') ?>" title="Jumlah Pelayanan Peserta KB Baru" loading="lazy"></iframe>
			            </div>
			<?php endif; ?>
			            <div class="krs-hint">
			                Sumber data: SIGA BKKBN (live, cache 30 menit). Laporan YanKB bulanan per kecamatan.
			            </div>
			        </div>
			    <?php endif; ?>
			</div><!-- /tab-yankb21 -->

			<!-- ===== TAB 4: REKAP PROGRAM BANGGA KENCANA (Dallap) ===== -->
			<div class="tab-pane fade" id="tab-dallap" role="tabpanel">
			    <?php
			    $dallapDefs = $dallapDefs ?? [];
			    $dallapRows = $dallapRows ?? [];
			    $dallapHasData = !empty($dallapDefs) && !empty($dallapRows);
			    // slug per kecamatan dari list_kecamatan (DB), dinormalisasi sama
			    // seperti resolveBkkbnKecamatanId(): 'Bati-Bati' (DB) == 'BATI BATI' (BKKBN)
			    $dallapNorm = static function (string $s): string {
			        $s = strtoupper(trim(str_replace(['_', '-'], ' ', $s)));
			        return preg_replace('/\s+/', ' ', $s) ?? $s;
			    };
			    $dallapSlug = [];
			    foreach (($list_kecamatan ?? []) as $k) {
			        $dallapSlug[$dallapNorm($k['nama_kecamatan'])] = $k['slug']
			            ?: strtolower(str_replace(' ', '-', $k['nama_kecamatan']));
			    }
			    if (!empty($dallapError)):
			    ?>
			        <div class="alert <?= $dallapHasData ? 'alert-info' : 'alert-warning' ?> py-2 px-3 mb-3" style="font-size:.9rem;">
			            <i class="bi bi-<?= $dallapHasData ? 'info-circle' : 'exclamation-triangle' ?> me-1"></i><?= esc($dallapError) ?>
			        </div>
			    <?php endif; ?>

			    <?php if (!$dallapHasData): ?>
			        <p class="text-muted">Data Rekap Program Bangga Kencana<?= $bkkbnIsKec ? ' untuk ' . esc($bkkbnScope) : '' ?> belum tersedia.</p>
			    <?php else: ?>
			        <?= $dallapZeroBadge($dallapAllZero($dallapRows, []), $yankbBulan, 'Rekap Program Bangga Kencana') ?>
			        <div class="krs-shell">
			            <div class="krs-toolbar">
			                <div>
			                    <h3 style="color:#ffffff;">Rekap Program Bangga Kencana — <?= esc($bkkbnScope) ?></h3>
			                    <div class="meta">SIGA BKKBN · Tahun <?= $dallapTahun ?? 2026 ?><?php if (!empty($dallapUpdatedAt)): ?> · update <?= esc($dallapUpdatedAt) ?><?php endif; ?> · klik ▶ untuk lihat rincian per desa</div>
			                </div>
			                <span class="badge-live">LIVE MIRROR</span>
			            </div>
			            <div class="krs-scroll"><table class="krs-tree" id="dallap-tree">
			                <thead>
			                    <tr>
			                        <th>No</th>
			                        <th>Nama Kecamatan</th>
			                        <?php foreach ($dallapDefs as $reportName => $def): ?>
			                            <th><?= esc($def['label']) ?></th>
			                        <?php endforeach; ?>
			                    </tr>
			                </thead>
			                <tbody>
			                    <?php $no = 1; $dallapTotals = array_fill_keys(array_keys($dallapDefs), 0); ?>
			                    <?php foreach ($dallapRows as $row): ?>
		                    <?php $rName = $dallapNorm($row['nama']); $rSlug = $dallapSlug[$rName] ?? ''; ?>
			                    <tr class="dallap-kec" id="dlp-<?= esc($rSlug ?: $no) ?>" data-parent="<?= esc($rSlug ?: $no) ?>" data-slug="<?= esc($rSlug) ?>">
			                        <td class="kode"><?= $no++ ?></td>
			                        <td class="wilayah">
			                            <?php if ($rSlug): ?>
			                            <button type="button" class="krs-toggle" data-role="dlp" data-slug="<?= esc($rSlug) ?>" aria-label="Expand desa">▶</button>
			                            <?php else: ?>
			                            <span class="krs-toggle leaf">·</span>
			                            <?php endif; ?>
			                            <?= esc(ucwords(strtolower($row['nama']))) ?>
			                        </td>
			                        <?php foreach ($dallapDefs as $reportName => $def): ?>
			                            <?php $v = $row['qty'][$reportName] ?? null; ?>
			                            <?php if ($v !== null) $dallapTotals[$reportName] += $v; ?>
			                            <td class="n"><?= $v === null ? '<span class="text-muted">-</span>' : number_format($v, 0, ',', '.') ?></td>
			                        <?php endforeach; ?>
			                    </tr>
			                    <?php endforeach; ?>
			                    <tr class="krs-kab">
			                        <td class="wilayah" colspan="2">JUMLAH TOTAL</td>
			                        <?php foreach ($dallapDefs as $reportName => $def): ?>
			                            <td class="n"><?= number_format($dallapTotals[$reportName], 0, ',', '.') ?></td>
			                        <?php endforeach; ?>
			                    </tr>
			                </tbody>
			            </table></div>
			            <div class="krs-hint">
                Sumber data: SIGA BKKBN (live, cache 30 menit). Kolom: PKB = penyuluh KB, UPPKA = kelompok UPPKA, BKB = kelompok BKB (bulanan, mengikuti filter bulan), BKL/BKR = kelompok BKL/BKR, PIK Remaja = PIK-R, PUS (SIGA) = jumlah PUS, KB Aktif (SIGA) = peserta KB aktif (keduanya bulanan, mengikuti filter bulan). Angka = jumlah per kecamatan; klik ▶ untuk rincian per desa. Data "KB &amp; PUS · Laporan Desa" pada tabel di atas berasal dari input admin desa, bukan dari SIGA.
			            </div>
			        </div>
			    <?php endif; ?>
			</div><!-- /tab-dallap -->

			<!-- ===== TAB 5: PUS & KB AKTIF (SIGA Dallap bulanan) ===== -->
			<div class="tab-pane fade" id="tab-puskb" role="tabpanel">
			    <?php
			    $pkCols = ['Tabel10' => 'PUS', 'Tabel11' => 'KB Aktif'];
			    $pkHasData = !empty($dallapRows) && (isset($dallapDefs['Tabel10']) || isset($dallapDefs['Tabel11']));
			    if (!empty($dallapError)):
			    ?>
			        <div class="alert <?= $pkHasData ? 'alert-info' : 'alert-warning' ?> py-2 px-3 mb-3" style="font-size:.9rem;">
			            <i class="bi bi-<?= $pkHasData ? 'info-circle' : 'exclamation-triangle' ?> me-1"></i><?= esc($dallapError) ?>
			        </div>
			    <?php endif; ?>

			    <?php if (!$pkHasData): ?>
			        <p class="text-muted">Data PUS &amp; KB Aktif (SIGA) belum tersedia.</p>
			    <?php else: ?>
			        <div class="krs-shell">
			            <div class="krs-toolbar">
			                <div>
			                    <h3 style="color:#ffffff;">PUS &amp; KB Aktif — <?= esc($bkkbnScope) ?></h3>
			                    <div class="meta">SIGA BKKBN · Dallap Tabel10/11 · Bulan <?= $yankbBulanNama ?> <?= $dallapTahun ?? 2026 ?><?php if (!empty($dallapUpdatedAt)): ?> · update <?= esc($dallapUpdatedAt) ?><?php endif; ?> · klik ▶ untuk rincian per desa</div>
			                </div>
			                <span class="badge-live">LIVE MIRROR</span>
			            </div>
			            <?= $dallapZeroBadge($dallapAllZero($dallapRows, ['Tabel10', 'Tabel11']), $yankbBulan, 'PUS &amp; KB Aktif') ?>
			            <div class="krs-scroll"><table class="krs-tree" id="puskb-tree">
			                <thead>
			                    <tr>
			                        <th>No</th>
			                        <th>Kecamatan / Desa</th>
			                        <th>Jumlah PUS</th>
			                        <th>Peserta KB Aktif</th>
			                    </tr>
			                </thead>
			                <tbody>
			                    <?php $pkNo = 1; $pkTot = ['Tabel10' => 0, 'Tabel11' => 0]; ?>
			                    <?php foreach ($dallapRows as $row): ?>
			                    <?php $pkName = $dallapNorm($row['nama']); $pkSlug = $dallapSlug[$pkName] ?? ''; ?>
			                    <tr class="dallap-kec" id="pkb-<?= esc($pkSlug ?: $pkNo) ?>" data-parent="<?= esc($pkSlug ?: $pkNo) ?>" data-slug="<?= esc($pkSlug) ?>">
			                        <td class="kode"><?= $pkNo++ ?></td>
			                        <td class="wilayah">
			                            <?php if ($pkSlug): ?>
			                            <button type="button" class="krs-toggle" data-role="pkb" data-slug="<?= esc($pkSlug) ?>" aria-label="Expand desa">▶</button>
			                            <?php else: ?>
			                            <span class="krs-toggle leaf">·</span>
			                            <?php endif; ?>
			                            <?= esc(ucwords(strtolower($row['nama']))) ?>
			                        </td>
			                        <?php foreach ($pkCols as $rn => $lbl): ?>
			                            <?php $pv = $row['qty'][$rn] ?? null; ?>
			                            <?php if ($pv !== null) $pkTot[$rn] += $pv; ?>
			                            <td class="n"><?= $pv === null ? '<span class="text-muted">-</span>' : number_format($pv, 0, ',', '.') ?></td>
			                        <?php endforeach; ?>
			                    </tr>
			                    <?php endforeach; ?>
			                    <tr class="krs-kab">
			                        <td class="wilayah" colspan="2">JUMLAH TOTAL</td>
			                        <td class="n"><?= number_format($pkTot['Tabel10'], 0, ',', '.') ?></td>
			                        <td class="n"><?= number_format($pkTot['Tabel11'], 0, ',', '.') ?></td>
			                    </tr>
			                </tbody>
			            </table></div>
			            <div class="krs-hint">
			                Sumber data: SIGA BKKBN Dallap Tabel10 (PUS) &amp; Tabel11 (KB Aktif), bulanan mengikuti filter bulan. Berbeda dengan "KB &amp; PUS · Laporan Desa" yang berasal dari input admin desa.
			            </div>
			        </div>
			    <?php endif; ?>
			</div><!-- /tab-puskb -->
            </div><!-- /tab-content -->
            </div><!-- /group-body -->
        </details>

        <!-- ===== JELAJAHI KECAMATAN (navigasi, bukan grup data) ===== -->
        <section class="block nav-block" style="padding-top: 1.5rem;">
            <div class="section-head">
                <h2>Jelajahi Kecamatan</h2>
                <span class="crumb"><strong><?= count($list_kecamatan) ?></strong> kecamatan di Kabupaten Tanah Laut</span>
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
                    <img src="<?= base_url('assets/dist/img/erdekatala.png') ?>" alt="Erdekatala" class="brand-mark">
                    <h5>Erdekatala</h5>
                    <p style="font-size: 0.88rem; color: rgba(255,255,255,.55);">
                        Singkatan dari Elektronik Rumah Data Kependudukan Tanah Laut — sistem informasi kependudukan berbasis Desa-Kecamatan untuk Pemerintah Kabupaten Tanah Laut, Kalimantan Selatan.
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
                        Data "Laporan Desa" (termasuk KB &amp; PUS) diperbarui oleh admin desa setiap periode pelaporan; data berlabel "SIGA" adalah cerminan resmi BKKBN (cache 30 menit).
                    </p>
                </div>
            </div>
            <div class="footer-bottom">
                <span>&copy; <?= date('Y') ?> Pemerintah Kabupaten Tanah Laut.</span>
                <span>Elektronik Rumah Data Kependudukan Tanah Laut</span>
            </div>
        </div>
    </footer>

    <!-- ===== ZINGCHART CONFIGS ===== -->
    <script>
        // ponytail: window-scope so DT ready() block can call these
        const rendered = new Set();
        let renderGender, renderPiramida, renderSosio, renderKesehatan;

        document.addEventListener("DOMContentLoaded", function () {
        // Palette konsisten — sadar dark-mode (dibaca ulang tiap render).
        const isDarkChart = () => document.body.classList.contains('dark-mode');
        const chartTheme = () => isDarkChart()
            ? { ink: "#e8eef6", muted: "#8fa1b8", border: "#33475f" }
            : { ink: "#0a2a4a", muted: "#6b7280", border: "#d5dfee" };
        const C = {
            primary: "#005bac",
            get ink()    { return chartTheme().ink; },
            blue:    "#2d6cdf",
            pink:    "#d6336c",
            green:   "#2f9e44",
            amber:   "#f59e0b",
            get muted()  { return chartTheme().muted; },
            paper:   "#eef3f8",
            get border() { return chartTheme().border; }
        };
        const PALETTE7 = ["#005bac","#2d6cdf","#2f9e44","#d6336c","#8b5cf6","#f59e0b","#0d9488"];
        const GUIDE = { get "line-color"() { return C.border; }, "line-style": "dotted" };
        const AX = { get "font-color"() { return C.ink; }, "font-size": 11 };
        const TIP = { borderRadius: 4, padding: 8, get "background-color"() { return C.ink; }, get "font-color"() { return isDarkChart() ? "#0a1730" : "#fff"; } };
        // Warna bar chart Dokumen: biru Dukcapil (sumber berbeda) vs primer (sumber mode utama)
        const dokColor = (t) => t === 'C_DUKCAPIL' ? C.blue : C.primary;

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
        const dokLabels    = <?= json_encode(array_map(
            fn($l) => $l . (($dokumenSumber[$l] ?? 'utama') === 'dukcapil' ? ' · Dukcapil' : ''),
            array_keys($dokumen)
        ), JSON_UNESCAPED_UNICODE) ?>;
        const dokValues    = <?= json_encode(array_map('intval', array_values($dokumen))) ?>;
        // Warna per-bar mengikuti sumber indikator: biru = Dukcapil, primer = sumber mode utama
        const dokColors    = <?= json_encode(array_map(
            fn($l) => (($dokumenSumber[$l] ?? 'utama') === 'dukcapil' ? 'C_DUKCAPIL' : 'C_UTAMA'),
            array_keys($dokumen)
        )) ?>;

        // ============ TAB POKOK — render langsung (tab aktif) ============
        renderGender = function () {
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
        };
        renderGender();

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
                        styles: dokColors.map(t => ({ "background-color": dokColor(t) }))
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
    <script src="<?= base_url('leaflet/leaflet.js') ?>"></script>
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

        // Render ulang chart yang tampil saat mode gelap/terang berubah (palet dibaca ulang via getter).
        document.addEventListener('erdekatala:theme', function () {
            var pane = document.querySelector('.tab-pane.active');
            var key = pane ? pane.id.replace('tab-', '') : 'pokok';
            rendered.delete('piramida'); rendered.delete('sosio'); rendered.delete('kesehatan');
            if (key === 'piramida' && typeof renderPiramida === 'function') { renderPiramida(); rendered.add('piramida'); }
            else if (key === 'sosio' && typeof renderSosio === 'function') { renderSosio(); rendered.add('sosio'); }
            else if (key === 'kesehatan' && typeof renderKesehatan === 'function') { renderKesehatan(); rendered.add('kesehatan'); }
            else if (typeof renderGender === 'function') { renderGender(); }
        });

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
            // Urutan kolom sama dengan renderKrsCells (format PDF Rekap)
            return [
                r.kelurahanAda, r.kelurahanTarget, r.kelurahanVerval,
                Number(r.cakupanKelurahanVerval ?? 0).toFixed(2),
                r.pusVervalAda, r.pusVervalBaru, r.pusVervalAdaBaru,
                r.pusHamilSasaran, r.pushamilVervalAda, r.pushamilVervalBaru,
                r.badutaSasaran, r.badutaVervalAda, r.badutaVervalBaru,
                r.balitaSasaran, r.balitaVervalAda, r.balitaVervalBaru,
                r.sasaranPrioritas, r.prioritasTerverval, r.adaBaruLainTerverval,
                r.totalVervalAda, r.totalVervalBaru, r.total, r.keluargaSasaranCatin
            ].map((v, i) => {
                if (i === 3) return `<td class="n">${v}</td>`;
                if (i === 21) return `<td class="n">${(+String(v ?? '').replace(/[.,]/g, '')) ? krsFmt(v) : '-'}</td>`;
                const val = (v === null || v === undefined || v === '') ? '-' : krsFmt(v);
                return `<td class="n">${val}</td>`;
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

        // ===== Dallap: expand desa per kecamatan (lazy-load via AJAX) =====
        const dallapDesaUrl = '<?= site_url('getDallapDesa') ?>';
        const dallapLoaded = new Set();

        document.querySelectorAll('#dallap-tree .krs-toggle[data-role="dlp"]').forEach(btn => {
            btn.addEventListener('click', async function () {
                const slug = this.dataset.slug;
                const parentRow = this.closest('tr');
                if (!parentRow || !slug) return;

                const parentId = parentRow.id;
                const open = this.classList.contains('open');
                if (open) {
                    document.querySelectorAll(`#dallap-tree tr[data-parent="${parentId}"]`).forEach(tr => tr.remove());
                    this.classList.remove('open');
                    this.textContent = '▶';
                    return;
                }

                this.classList.add('open');
                this.textContent = '▼';

                const loadTr = document.createElement('tr');
                loadTr.className = 'krs-loading';
                loadTr.dataset.parent = parentId;
                loadTr.innerHTML = `<td class="wilayah" colspan="8"><i class="bi bi-arrow-repeat"></i> Memuat desa...</td>`;
                parentRow.after(loadTr);

                try {
                    const res = await fetch(dallapDesaUrl + '/' + encodeURIComponent(slug), { headers: { 'Accept': 'application/json' } });
                    const json = await res.json();
                    loadTr.remove();
                    if (!res.ok || !json || json.error) {
                        const errTr = document.createElement('tr');
                        errTr.className = 'krs-loading';
                        errTr.dataset.parent = parentId;
                        errTr.innerHTML = `<td class="wilayah" colspan="8">${(json && json.error) || 'Gagal memuat data desa.'}</td>`;
                        parentRow.after(errTr);
                        this.classList.remove('open');
                        this.textContent = '▶';
                        return;
                    }

                    const defs = json.defs || {};
                    let anchor = parentRow;
                    (json.rows || []).forEach((r, di) => {
                        const tr = document.createElement('tr');
                        tr.className = 'dallap-desa';
                        tr.dataset.parent = parentId;
                        let cells = `<td class="kode">${di + 1}</td><td class="wilayah"><span class="krs-toggle leaf">·</span>${(r.nama || '-').replace(/</g, '&lt;')}</td>`;
                        Object.keys(defs).forEach(rn => {
                            const v = (r.qty && r.qty[rn] !== undefined && r.qty[rn] !== null) ? r.qty[rn] : null;
                            cells += `<td class="n">${v === null ? '-' : Number(v).toLocaleString('id-ID')}</td>`;
                        });
                        tr.innerHTML = cells;
                        anchor.after(tr);
                        anchor = tr;
                    });

                    const totTr = document.createElement('tr');
                    totTr.className = 'krs-kab';
                    totTr.dataset.parent = parentId;
                    let totCells = `<td class="wilayah" colspan="2">JUMLAH TOTAL</td>`;
                    Object.keys(defs).forEach(rn => {
                        let s = 0;
                        (json.rows || []).forEach(r => { const v = (r.qty && r.qty[rn]); if (v !== null && v !== undefined) s += v; });
                        totCells += `<td class="n">${s.toLocaleString('id-ID')}</td>`;
                    });
                    totTr.innerHTML = totCells;
                    anchor.after(totTr);
                } catch (e) {
                    loadTr.remove();
                    const errTr = document.createElement('tr');
                    errTr.className = 'krs-loading';
                    errTr.dataset.parent = parentId;
                    errTr.innerHTML = `<td class="wilayah" colspan="8">Gagal memuat data desa.</td>`;
                    parentRow.after(errTr);
                    this.classList.remove('open');
                    this.textContent = '▶';
                }
            });
        });

        // ===== PUS & KB Aktif: expand desa per kecamatan (reuse getDallapDesa, 2 kolom) =====
        const pkbCols = ['Tabel10', 'Tabel11'];
        const pkbBulan = new URLSearchParams(window.location.search).get('bulan') || '<?= (int)($yankbBulan ?? 8) ?>';
        document.querySelectorAll('#puskb-tree .krs-toggle[data-role="pkb"]').forEach(btn => {
            btn.addEventListener('click', async function () {
                const slug = this.dataset.slug;
                const parentRow = this.closest('tr');
                if (!parentRow || !slug) return;

                const parentId = parentRow.id;
                const open = this.classList.contains('open');
                if (open) {
                    document.querySelectorAll(`#puskb-tree tr[data-parent="${parentId}"]`).forEach(tr => tr.remove());
                    this.classList.remove('open');
                    this.textContent = '▶';
                    return;
                }

                this.classList.add('open');
                this.textContent = '▼';

                const loadTr = document.createElement('tr');
                loadTr.className = 'krs-loading';
                loadTr.dataset.parent = parentId;
                loadTr.innerHTML = `<td class="wilayah" colspan="4"><i class="bi bi-arrow-repeat"></i> Memuat desa...</td>`;
                parentRow.after(loadTr);

                try {
                    const res = await fetch(dallapDesaUrl + '/' + encodeURIComponent(slug) + '?bulan=' + encodeURIComponent(pkbBulan), { headers: { 'Accept': 'application/json' } });
                    const json = await res.json();
                    loadTr.remove();
                    if (!res.ok || !json || json.error) {
                        const errTr = document.createElement('tr');
                        errTr.className = 'krs-loading';
                        errTr.dataset.parent = parentId;
                        errTr.innerHTML = `<td class="wilayah" colspan="4">${(json && json.error) || 'Gagal memuat data desa.'}</td>`;
                        parentRow.after(errTr);
                        this.classList.remove('open');
                        this.textContent = '▶';
                        return;
                    }

                    let anchor = parentRow;
                    (json.rows || []).forEach((r, di) => {
                        const tr = document.createElement('tr');
                        tr.className = 'dallap-desa';
                        tr.dataset.parent = parentId;
                        let cells = `<td class="kode">${di + 1}</td><td class="wilayah"><span class="krs-toggle leaf">·</span>${(r.nama || '-').replace(/</g, '&lt;')}</td>`;
                        pkbCols.forEach(rn => {
                            const v = (r.qty && r.qty[rn] !== undefined && r.qty[rn] !== null) ? r.qty[rn] : null;
                            cells += `<td class="n">${v === null ? '-' : Number(v).toLocaleString('id-ID')}</td>`;
                        });
                        tr.innerHTML = cells;
                        anchor.after(tr);
                        anchor = tr;
                    });

                    const totTr = document.createElement('tr');
                    totTr.className = 'krs-kab';
                    totTr.dataset.parent = parentId;
                    let totCells = `<td class="wilayah" colspan="2">JUMLAH TOTAL</td>`;
                    pkbCols.forEach(rn => {
                        let s = 0;
                        (json.rows || []).forEach(r => { const v = (r.qty && r.qty[rn]); if (v !== null && v !== undefined) s += v; });
                        totCells += `<td class="n">${s.toLocaleString('id-ID')}</td>`;
                    });
                    totTr.innerHTML = totCells;
                    anchor.after(totTr);
                } catch (e) {
                    loadTr.remove();
                    const errTr = document.createElement('tr');
                    errTr.className = 'krs-loading';
                    errTr.dataset.parent = parentId;
                    errTr.innerHTML = `<td class="wilayah" colspan="4">Gagal memuat data desa.</td>`;
                    parentRow.after(errTr);
                    this.classList.remove('open');
                    this.textContent = '▶';
                }
            });
        });

        // ===== Filter bulan laporan YanKB (Tabel1/Tabel21) =====
        const yankbBulanSel = document.getElementById('yankb-bulan');
        if (yankbBulanSel) {
            yankbBulanSel.addEventListener('change', function () {
                const url = new URL(window.location.href);
                url.searchParams.set('bulan', this.value);
                window.location.href = url.toString();
            });
        }

        // ===== Filter sumber data profil kependudukan (Bagian 1) =====
        const sumberSel = document.getElementById('sumber-data');
        if (sumberSel) {
            sumberSel.addEventListener('change', function () {
                const url = new URL(window.location.href);
                if (this.value === 'dukcapil') {
                    url.searchParams.delete('sumber'); // default (Dukcapil), URL bersih
                } else {
                    url.searchParams.set('sumber', this.value);
                }
                window.location.href = url.toString();
            });
        }

        // ===== Sticky header 4-baris tabel YanKB (hitungan dinamis, tab tersembunyi) =====
        const fixYankbSticky = () => {
            document.querySelectorAll('table.krs-yankb').forEach(tbl => {
                let acc = 0;
                tbl.querySelectorAll('thead tr').forEach(tr => {
                    tr.querySelectorAll('th').forEach(th => th.style.top = acc + 'px');
                    acc += tr.offsetHeight;
                });
            });
        };
        // Tab toggle: hitung ulang saat tab ditampilkan (height=0 saat hidden)
        document.querySelectorAll('#monitoring-bkkbn button[data-bs-toggle="tab"]').forEach(btn => {
            btn.addEventListener('shown.bs.tab', fixYankbSticky);
        });
        // Panggil sekali untuk tab aktif (default tab-krs)
        setTimeout(fixYankbSticky, 100);
    });
    </script>
    <script>
        // ===== PETA SEBARAN KEPENDUDUKAN (Leaflet) — tematik multi-indikator =====
        // Data polygon + statistik dimuat dari endpoint publik GET /api/v1/map/kecamatan
        // (single source of truth; join statistik dilakukan di server).
        (function () {
            const mapEl = document.getElementById('map-kecamatan');
            if (!mapEl) return;

            // Metadata kecamatan dari server (nama/slug/titik) + nilai indikator
            // dari SUMBER AKTIF (konsisten dengan kartu profil). Nilai dari sini
            // MENIMPA properti endpoint /api/v1/map/* yang selalu berbasis laporan
            // desa, sehingga peta tidak pernah menampilkan angka laporan desa saat
            // kartu menampilkan Dukcapil. Peta juga hanya memuat wilayah dalam scope.
            const data = <?= json_encode($map_kecamatan) ?>;
            const mapDesaData = <?= json_encode($map_desa) ?>;
            const filterKec = <?= json_encode($effectiveKecSlug) ?>;
            const filterDesa = <?= json_encode($filter_desa_slug) ?>;

            // Mapping SENTRAL label Indonesia -> field snake_case (partials/map_indicators.php)
            const INDICATOR_GROUPS = <?= json_encode($mapIndicatorGroups) ?>;
            const INDICATOR_LABEL = {};
            INDICATOR_GROUPS.forEach(function (g) {
                g.items.forEach(function (it) { INDICATOR_LABEL[it.field] = it.label; });
            });

            const API_MAP_KEC = <?= json_encode(base_url('api/v1/map/kecamatan')) ?>;
            const API_MAP_DESA = <?= json_encode(base_url('api/v1/map/desa')) ?>;
            const GEO_KAB = <?= json_encode(base_url('geojson/kabupaten.geojson')) ?>;
            const BASE_URL = <?= json_encode(site_url('/')) ?>;

            if (typeof L === 'undefined' || !data.length) {
                if (typeof L === 'undefined') {
                    mapEl.className = 'map-placeholder';
                    mapEl.innerHTML = '<div class="text-center"><i class="bi bi-map" style="font-size:2rem;display:block;margin-bottom:.5rem;"></i>Peta tidak dapat dimuat (library Leaflet gagal dimuat).</div>';
                    return;
                }
                mapEl.className = 'map-placeholder';
                mapEl.innerHTML = '<div class="text-center"><i class="bi bi-map" style="font-size:2rem;display:block;margin-bottom:.5rem;"></i>Belum ada data koordinat wilayah.</div>';
                return;
            }

            const fmt = n => Number(n || 0).toLocaleString('id-ID');
            const escHtml = s => String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
                return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
            });
            const RAMP = ['#e3eef9', '#c2d8ee', '#99bce0', '#4a8ac8', '#005bac'];
            const COLOR_ACTIVE = '#2d6cdf';
            const COLOR_NODATA = '#d5dfee';

            // Kecamatan yang difilter (slug)
            const activeKec = filterKec
                ? data.find(k => k.slug === filterKec) || null
                : null;

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
            const indicatorSelect = document.getElementById('ind-kec-select');
            let activeField = (indicatorSelect && indicatorSelect.value) || 'total_jiwa';
            if (indicatorSelect) indicatorSelect.value = activeField;

            // ===== State layer & feature =====
            let map;
            let kecGeo = null, desaGeo = null, kabGeo = null;
            let kecFeatures = [];   // feature kecamatan hasil merge endpoint + metadata
            let desaFeatures = [];  // feature desa hasil merge endpoint (saat filter aktif)
            let kecBucket = makeBucketFn([0]);
            let desaBucket = makeBucketFn([0]);

            const byKode = new Map(data.map(k => [k.kode, k]));
            const activeDesaSlug = filterDesa ? String(filterDesa).toLowerCase() : '';
            const desaSlugify = name => String(name).toLowerCase().replace(/\s+/g, '-');

            function numVal(props, field) {
                const v = Number((props || {})[field]);
                return isFinite(v) ? v : 0;
            }

            // Gabung properti endpoint (indikator snake_case) dengan metadata
            // kecamatan + nilai dari sumber aktif (byKode). Feature di luar scope
            // filter (kecamatan lain) di-skip → peta hanya menampilkan scope.
            function mergeKecMeta(feature) {
                const p = feature.properties || {};
                const k = byKode.get(String(p.kd_kecamatan));
                if (!k) return null; // di luar scope (endpoint mengirim seluruh kabupaten)
                const props = Object.assign({}, p, k); // nilai sumber aktif menimpa endpoint
                props.kode = String(p.kd_kecamatan);
                props.nama = k.nama;
                props.slug = k.slug;
                props.hasData = true;
                props.isActive = !!(activeKec && k.kode === activeKec.kode);
                props.bucket = 0;
                return { type: 'Feature', geometry: feature.geometry, properties: props };
            }

            // Peta per-desa dari sumber aktif (byDesa). Di luar scope → null.
            const byDesa = new Map(mapDesaData.map(d => [d.kd_kecamatan + '.' + d.kd_kelurahan, d]));
            function mergeDesaMeta(feature) {
                const p = feature.properties || {};
                const kode = String(p.kd_kecamatan) + '.' + String(p.kd_kelurahan);
                const d = byDesa.get(kode);
                if (!d) return null; // di luar scope
                const props = Object.assign({}, p, d); // nilai sumber aktif menimpa endpoint
                props.kode = kode;
                props.nama = p.nm_kelurahan || d.nama || '';
                props.slug = desaSlugify(props.nama);
                props.hasData = true;
                props.isActive = !!(activeDesaSlug && props.slug === activeDesaSlug);
                props.bucket = 0;
                return { type: 'Feature', geometry: feature.geometry, properties: props };
            }

            // Hitung ulang bucket sesuai indikator aktif, restyle layer, perbarui legenda
            function recalcAndRestyle() {
                if (kecFeatures.length) {
                    kecBucket = makeBucketFn(kecFeatures.map(function (f) { return numVal(f.properties, activeField); }));
                    kecFeatures.forEach(function (f) {
                        f.properties.bucket = f.properties.hasData ? kecBucket.fn(numVal(f.properties, activeField)) : 0;
                    });
                    if (kecGeo) kecGeo.eachLayer(function (l) { l.setStyle(kecStyle(l.feature)); });
                }
                if (desaFeatures.length) {
                    desaBucket = makeBucketFn(desaFeatures.map(function (f) { return numVal(f.properties, activeField); }));
                    desaFeatures.forEach(function (f) {
                        f.properties.bucket = f.properties.hasData ? desaBucket.fn(numVal(f.properties, activeField)) : 0;
                    });
                    if (desaGeo) desaGeo.eachLayer(function (l) { l.setStyle(desaStyle(l.feature)); });
                }
                updateLegend(kecBucket, INDICATOR_LABEL[activeField] || activeField);
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
            function kecStyle(feature) {
                return {
                    fillColor: baseFill(feature.properties || {}),
                    fillOpacity: 0.85,
                    color: '#ffffff',
                    weight: 1.2,
                    opacity: 1
                };
            }
            function desaStyle(feature) {
                return {
                    fillColor: baseFill(feature.properties || {}),
                    fillOpacity: 0.85,
                    color: '#ffffff',
                    weight: 0.8,
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

            // Handler interaksi poligon kecamatan (hover + popup klik)
            function onEachKec(feature, layer) {
                layer.bindPopup(function (l) { return kecPopupHtml(l.feature.properties); }, { className: 'map-popup-kec', closeButton: false, maxWidth: 300 });
                layer.on({
                    mouseover: function (e) {
                        e.target.setStyle(hoverStyle(feature));
                        mapEl.style.cursor = 'pointer';
                    },
                    mouseout: function (e) {
                        if (kecGeo) kecGeo.resetStyle(e.target);
                        mapEl.style.cursor = '';
                    }
                });
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
                map = L.map('map-kecamatan', {
                    scrollWheelZoom: false,
                    zoomControl: false,
                    minZoom: 6,
                    maxZoom: 18
                });
                // View sementara sebelum fitBounds data GeoJSON siap
                map.setView([-3.85, 114.75], 8);
                L.control.zoom({ position: 'topright' }).addTo(map);
                map.attributionControl.setPrefix('');
            } catch (err) {
                showMapError('Peta tidak dapat diinisialisasi (' + ((err && err.message) || 'kesalahan tak terduga') + '). Coba muat ulang halaman atau gunakan browser lain.');
                return;
            }

            const tiles = L.tileLayer('https://basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}.png<?= $cartoApiKey ? '?key=' . $cartoApiKey : '' ?>', {
                maxZoom: 19,
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors &copy; <a href="https://www.carto.com/attributions">CARTO</a>'
            }).addTo(map);

            // Kegagalan tile basemap dicatat, bukan diam-diam
            let mapTileErr = 0;
            tiles.on('tileerror', function () {
                mapTileErr++;
                if (mapTileErr === 3) {
                    const legend = document.getElementById('map-legend-kec');
                    if (legend && !legend.querySelector('.tile-note')) {
                        const note = document.createElement('span');
                        note.className = 'text-muted tile-note';
                        note.style.fontSize = '.75rem';
                        note.innerHTML = ' &middot; <i class="bi bi-exclamation-triangle"></i> Peta dasar gagal dimuat &mdash; poligon data tetap tampil';
                        legend.appendChild(note);
                    }
                }
            });

            // Popup: nama wilayah + nilai indikator aktif + link detail
            function pkRow(label, value) {
                return '<div class="pk-row"><span>' + escHtml(label) + '</span><b>' + fmt(value) + '</b></div>';
            }
            function activeIndicatorRow(props) {
                return pkRow(INDICATOR_LABEL[activeField] || activeField, numVal(props, activeField));
            }

            function kecPopupHtml(k) {
                let html = '<div class="pk-name">' + escHtml(k.nama || '—') + '</div>';
                html += activeIndicatorRow(k);
                ['total_jiwa', 'total_kk'].forEach(function (fld) {
                    if (fld !== activeField) html += pkRow(INDICATOR_LABEL[fld] || fld, numVal(k, fld));
                });
                html += '<div class="pk-row"><span>Jiwa L / P</span><b>' + fmt(numVal(k, 'jiwa_l')) + ' / ' + fmt(numVal(k, 'jiwa_p')) + '</b></div>';
                if (k.slug) {
                    html += '<a class="pk-link" href="' + BASE_URL + escHtml(k.slug) + '">Lihat detail kecamatan →</a>';
                }
                return html;
            }

            function desaPopupHtml(d) {
                let html = '<div class="pk-name">' + escHtml(d.nama || '—') + '</div>';
                html += activeIndicatorRow(d);
                ['total_jiwa', 'total_kk'].forEach(function (fld) {
                    if (fld !== activeField) html += pkRow(INDICATOR_LABEL[fld] || fld, numVal(d, fld));
                });
                html += '<div class="pk-row"><span>Jiwa L / P</span><b>' + fmt(numVal(d, 'jiwa_l')) + ' / ' + fmt(numVal(d, 'jiwa_p')) + '</b></div>';
                if (activeKec && activeKec.slug && d.slug) {
                    html += '<a class="pk-link" href="' + BASE_URL + escHtml(activeKec.slug) + '?desa=' + encodeURIComponent(d.slug) + '">Lihat detail desa →</a>';
                }
                return html;
            }

            function showMapError(msg) {
                if (map) { try { map.remove(); } catch (e) { /* noop */ } map = null; }
                mapEl.className = 'map-placeholder';
                mapEl.innerHTML = '<div class="text-center"><i class="bi bi-map" style="font-size:2rem;display:block;margin-bottom:.5rem;"></i>' + escHtml(msg) + '</div>';
            }

            // Garis batas kabupaten selalu di atas (meniru urutan layer MapLibre lama)
            function raiseKab() {
                if (kabGeo) { kabGeo.eachLayer(function (l) { l.bringToFront(); }); }
            }

            // Overlay poligon desa saat filter kecamatan aktif (dari endpoint map/desa)
            function loadDesa(kec) {
                fetch(API_MAP_DESA + '?id_kecamatan=' + encodeURIComponent(kec.id_kecamatan))
                    .then(r => { if (!r.ok) throw new Error('HTTP ' + r.status); return r.json(); })
                    .then(fc => {
                        const features = (fc.features || []).map(mergeDesaMeta).filter(Boolean);
                        if (!features.length) return;
                        desaFeatures = features;
                        desaBucket = makeBucketFn(desaFeatures.map(function (f) { return numVal(f.properties, activeField); }));
                        desaFeatures.forEach(function (f) {
                            f.properties.bucket = f.properties.hasData ? desaBucket.fn(numVal(f.properties, activeField)) : 0;
                        });
                        if (desaGeo) { map.removeLayer(desaGeo); }
                        desaGeo = L.geoJSON({ type: 'FeatureCollection', features: desaFeatures }, {
                            style: desaStyle,
                            onEachFeature: onEachDesa
                        }).addTo(map);
                        raiseKab();
                    })
                    .catch(() => { /* peta kecamatan tetap tampil */ });
            }

            // ===== Muat FeatureCollection kecamatan dari endpoint publik =====
            fetch(API_MAP_KEC)
                .then(r => { if (!r.ok) throw new Error('HTTP ' + r.status); return r.json(); })
                .then(fc => {
                    if (!fc || !Array.isArray(fc.features) || !fc.features.length) {
                        showMapError('Data peta belum tersedia.');
                        return;
                    }
                    kecFeatures = fc.features.map(mergeKecMeta).filter(Boolean);

                    kecGeo = L.geoJSON({ type: 'FeatureCollection', features: kecFeatures }, {
                        style: kecStyle,
                        onEachFeature: onEachKec
                    }).addTo(map);

                    // Hitung choropleth + legenda sesuai indikator aktif
                    recalcAndRestyle();

                    if (activeKec) {
                        let activeLayer = null;
                        kecGeo.eachLayer(function (l) {
                            if (l.feature && l.feature.properties.kode === activeKec.kode) activeLayer = l;
                        });
                        if (activeLayer) {
                            map.fitBounds(activeLayer.getBounds(), { padding: [60, 60] });
                        } else {
                            // Fallback ke titik lat/lng kecamatan (urutan Leaflet: [lat, lng])
                            map.flyTo([activeKec.lat || -3.8, activeKec.lng || 114.75], 9);
                        }
                        loadDesa(activeKec);
                    } else if (kecGeo.getLayers().length) {
                        map.fitBounds(kecGeo.getBounds(), { padding: [40, 40] });
                    }
                    raiseKab();
                })
                .catch(err => showMapError('Peta tidak dapat dimuat (' + ((err && err.message) || 'kesalahan jaringan') + ').'));

            // Garis batas kabupaten (konteks; gagal dimuat tidak menghentikan peta)
            fetch(GEO_KAB)
                .then(r => { if (!r.ok) throw new Error('HTTP ' + r.status); return r.json(); })
                .then(fc => {
                    const features = fc.features || [];
                    if (!features.length) return;
                    kabGeo = L.geoJSON(fc, {
                        style: { color: '#5b6472', weight: 1.6, opacity: 0.9, dashArray: '8,4', fill: false },
                        interactive: false
                    }).addTo(map);
                })
                .catch(function () { /* opsional */ });

            // ===== Legenda (warna + rentang nilai, mengikuti indikator aktif) =====
            function updateLegend(bucket, label) {
                const indLabel = document.querySelector('#map-legend-kec .lg-ind-label');
                if (indLabel) indLabel.textContent = label;
                const barLabel = document.getElementById('lg-indicator-name');
                if (barLabel) barLabel.textContent = 'Menampilkan: ' + label;
                RAMP.forEach(function (color, i) {
                    const el = document.getElementById('lg-' + i);
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
            // via bindPopup + event layer di onEachKec/onEachDesa.
        })();
    </script>
    <script>
        // Load desa berdasarkan slug kecamatan (untuk chained select)
        function loadDesaBySlug(slugKecamatan) {
            var selectDesa = document.getElementById('sel-desa');
            selectDesa.innerHTML = '<option value="">— Semua Desa —</option>';
            
            if (!slugKecamatan) {
                selectDesa.disabled = true;
                return;
            }
            
            selectDesa.disabled = false;
            fetch('<?= site_url('/getDesaByKecamatanSlug/') ?>' + slugKecamatan)
                .then(res => res.json())
                .then(data => {
                    data.forEach(function(desa) {
                        var opt = document.createElement('option');
                        opt.value = desa.slug;
                        opt.textContent = desa.nama_desa;
                        selectDesa.appendChild(opt);
                    });
                })
                .catch(err => console.error('Error loading desa:', err));
        }
    </script>
    <?= $this->setVar('noButton', true)->include('templates/theme_toggle') ?>
</body>

</html>
