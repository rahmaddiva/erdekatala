<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Erdekatala API Docs</title>
    <link rel="stylesheet" href="https://unpkg.com/swagger-ui-dist@5.17.14/swagger-ui.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        .cursor-follower {
            position: fixed;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            border: 2px solid rgba(0, 91, 172, 0.6);
            pointer-events: none;
            z-index: 9999;
            top: 0;
            left: 0;
            opacity: 0;
        }
        .cursor-follower.active { opacity: 1; }
    </style>
    <style>
        :root{
            --ink:#0a2a4a; --ink-2:#103a5c;
            --primary:#005bac; --primary-d:#004a8c;
            --paper:#eef3f8; --paper-2:#ffffff;
            --line:#d5dfee; --muted:#6b7280;
        }
        *{box-sizing:border-box;}
        body{margin:0;background:var(--paper);font-family:"Poppins",sans-serif;color:var(--ink);}
        h1,h2,h3{font-family:"Poppins",sans-serif;}

        .api-header{
            background:var(--ink);color:#fff;padding:16px 40px;
            display:flex;align-items:center;justify-content:space-between;
            border-bottom:3px solid var(--primary);
            position:sticky;top:0;z-index:100;box-shadow:0 2px 12px rgba(0,0,0,0.25);
        }
        .api-header .brand{display:flex;align-items:center;gap:14px;}
        .api-header .brand-logo{
            height:40px;width:auto;flex-shrink:0;
        }
        .api-header h1{margin:0;font-size:1.3rem;font-weight:700;}
        .api-header p{margin:2px 0 0;font-size:0.83rem;opacity:0.7;font-weight:300;}
        .api-header .btn-group a{
            display:inline-block;margin-left:10px;padding:8px 18px;border-radius:6px;
            text-decoration:none;font-size:0.85rem;font-weight:600;transition:all .15s;
        }
        .btn-primary{background:var(--primary);color:#fff;}
        .btn-primary:hover{background:var(--primary-d);}
        .btn-outline{border:1px solid rgba(255,255,255,0.35);color:#fff;}
        .btn-outline:hover{background:rgba(255,255,255,0.1);border-color:rgba(255,255,255,0.6);}

        .api-footer{
            background:var(--ink);color:rgba(255,255,255,0.55);text-align:center;
            padding:22px 40px;font-size:0.83rem;border-top:3px solid var(--primary);line-height:1.7;
        }
        .api-footer a{color:var(--primary);text-decoration:none;font-weight:600;}
        .api-footer a:hover{text-decoration:underline;}

        .swagger-ui{background:var(--paper);font-family:"Source Sans 3",sans-serif;}
        .swagger-ui .topbar{display:none;}
        .swagger-ui .information-container{padding:20px 20px 0;}
        .swagger-ui .info{
            background:var(--paper-2);border-radius:10px;padding:24px 28px;
            box-shadow:0 1px 4px rgba(15,25,35,0.07);border:1px solid var(--line);
        }
        .swagger-ui .info .title{color:var(--ink);font-family:"Libre Baskerville",serif;}
        .swagger-ui .info .base-url{color:var(--muted);}
        .swagger-ui .scheme-container{
            background:var(--paper-2);box-shadow:0 1px 4px rgba(15,25,35,0.07);
            padding:14px 20px;border-bottom:1px solid var(--line);
        }
        .swagger-ui .opblock-tag{
            color:var(--ink);font-weight:700;border-bottom:2px solid var(--line);
            font-family:"Libre Baskerville",serif;
        }
        .swagger-ui .opblock.opblock-get .opblock-summary-method{background:var(--primary);}
        .swagger-ui .opblock.opblock-get{border-color:var(--primary);background:rgba(0,91,172,0.04);}
        .swagger-ui .opblock.opblock-get .opblock-summary{border-color:var(--line);}
        .swagger-ui .btn.authorize{border-color:var(--primary);color:var(--primary);}
        .swagger-ui .btn.authorize svg{fill:var(--primary);}
        .swagger-ui .btn.authorize.unlocked svg{fill:var(--primary);}
        .swagger-ui section.models{
            background:var(--paper-2);border-radius:10px;border:1px solid var(--line);
            box-shadow:0 1px 4px rgba(15,25,35,0.07);
        }
        .swagger-ui .model-box{background:var(--paper);}
        .swagger-ui .opblock .opblock-section-header{background:var(--paper);border-bottom:1px solid var(--line);}
        .swagger-ui .opblock-body pre{background:var(--ink);border-radius:6px;}
        .swagger-ui table thead tr td{color:var(--muted);font-weight:600;border-bottom:1px solid var(--line);}
        .swagger-ui .parameter__name{color:var(--ink);font-weight:600;}
        .swagger-ui .scheme-container .schemes-server-container label{color:var(--ink);font-weight:600;}
    </style>
</head>
<body>

<div class="api-header">
    <div class="brand">
        <img src="/assets/dist/img/erdekatala.png" alt="Erdekatala" class="brand-logo">
        <div>
            <h1>Erdekatala Public API</h1>
            <p>Data Agregat Kependudukan Kabupaten Tanah Laut, Kalimantan Selatan</p>
        </div>
    </div>
    <div class="btn-group">
        <a href="/api/guide" class="btn-outline">Panduan Integrasi</a>
        <a href="/api/register" class="btn-primary">Dapatkan API Key</a>
        <a href="/" class="btn-outline">Kembali ke Website</a>
    </div>
</div>

<div id="swagger-ui"></div>

<div class="api-footer">
    Erdekatala &middot; Sistem Informasi Kependudukan Kabupaten Tanah Laut, Kalimantan Selatan<br>
    <a href="/api/docs">Dokumentasi</a> &middot; <a href="/api/guide">Panduan</a> &middot; <a href="/api/register">Daftar API Key</a> &middot; <a href="/">Beranda</a>
</div>

<script src="https://unpkg.com/swagger-ui-dist@5.17.14/swagger-ui-bundle.js"></script>
<script>
    const spec = {
        openapi: "3.0.3",
        info: {
            title: "Erdekatala Public API",
            version: "1.1.0",
            description: "API publik untuk mengakses data agregat kependudukan Kabupaten Tanah Laut, Kalimantan Selatan.\n\n**Cara Penggunaan:**\n1. [Daftarkan diri](/api/register) untuk mendapatkan API key gratis\n2. Gunakan API key sebagai Bearer token: `Authorization: Bearer <api_key>`\n3. Atau sebagai query param: `?api_key=<api_key>`\n\n**Sumber data:** default `dukcapil` = snapshot resmi DKB Semester I 2026 (Total Jiwa 378.190). `?sumber=desa` memakai laporan desa; `?sumber=campuran` memakai laporan desa bila ada, Dukcapil bila desa belum lapor. Endpoint peta menerima parameter ini.\n\n**Agregasi:** endpoint rekap & peta memakai **satu baris terbaru per desa** (bukan `SUM` semua periode) agar tidak double-count. Endpoint `/api/v1/laporan` sebaliknya mengembalikan **baris mentah** per desa × periode.\n\n**Kelompok umur:** `balita` 0–4, `remaja` 10–19, `lansia` 60+, `PUS` perempuan 15–49 — diturunkan dari kolom piramida `u*`, bukan dari input desa.\n\n**Rate Limit:** 1000 request per hari per API key. Header respons: `X-RateLimit-Limit`, `X-RateLimit-Remaining`, `X-RateLimit-Reset`. Saat kuota habis → HTTP 429.",
            contact: {
                name: "DP3AP2KB Tanah Laut",
                url: window.location.origin
            },
            license: { name: "Open Data", url: "https://creativecommons.org/licenses/by/4.0/" }
        },
        tags: [
            { name: "Info", description: "Metadata API dan daftar endpoint." },
            { name: "Wilayah", description: "Daftar kecamatan dan desa." },
            { name: "Laporan Agregat", description: "Baris laporan mentah per desa dan periode." },
            { name: "Rekapitulasi", description: "Agregasi satu baris terbaru per desa (tanpa double-count)." },
            { name: "Peta GeoJSON", description: "Peta tematik publik — tanpa API key." },
            { name: "Cermin BKKBN", description: "Salinan data SIGA BKKBN (pihak ketiga, cache 30 menit)." }
        ],
        servers: [{ url: window.location.origin, description: "Production Server" }],
        components: {
            responses: {
                Unauthorized: {
                    description: "API key tidak ada, tidak valid, atau tidak aktif.",
                    content: { "application/json": { schema: { "$ref": "#/components/schemas/Error" } } }
                },
                RateLimited: {
                    description: "Kuota harian habis (reset tengah malam).",
                    content: { "application/json": { schema: {
                        type: "object",
                        properties: {
                            status:  { type: "string", example: "error" },
                            code:    { type: "integer", example: 429 },
                            message: { type: "string" },
                            limit:   { type: "integer", example: 1000 },
                            used:    { type: "integer", example: 1000 },
                            reset:   { type: "string", format: "date", example: "2026-11-17" }
                        }
                    }}}
                },
                ValidationError: {
                    description: "Parameter query tidak valid.",
                    content: { "application/json": { schema: {
                        type: "object",
                        properties: {
                            status:  { type: "string", example: "error" },
                            message: { type: "string", example: "Parameter id_kecamatan harus berupa angka positif." }
                        }
                    }}}
                }
            },
            securitySchemes: {
                BearerAuth: {
                    type: "http", scheme: "bearer",
                    description: "Masukkan API key Anda. Contoh: `Bearer xxxxxxxx`"
                },
                ApiKeyQuery: {
                    type: "apiKey", in: "query", name: "api_key",
                    description: "Masukkan API key sebagai query parameter"
                }
            },
            schemas: {
                Error: {
                    type: "object",
                    properties: {
                        status: { type: "string", example: "error" },
                        code:   { type: "integer", example: 401 },
                        message: { type: "string", example: "API key tidak valid." }
                    }
                },
                Kecamatan: {
                    type: "object",
                    description: "Baris tabel kecamatan. Endpoint mengembalikan seluruh kolomnya, termasuk profil publik (slug, deskripsi, kontak, is_public).",
                    properties: {
                        id_kecamatan:   { type: "integer", example: 4 },
                        nama_kecamatan: { type: "string",  example: "Kurau" },
                        kode_kecamatan: { type: "string",  example: "63.01.04", description: "Format BPS bertitik" },
                        slug:           { type: "string",  example: "kurau" },
                        is_public:      { type: "integer", example: 1, description: "0 = halaman publik disembunyikan" }
                    }
                },
                Desa: {
                    type: "object",
                    description: "Baris tabel m_desa (seluruh kolom, termasuk koordinat) + nama_kecamatan hasil join.",
                    properties: {
                        id_desa:        { type: "integer", example: 1 },
                        id_kecamatan:   { type: "integer", example: 4 },
                        nama_desa:      { type: "string",  example: "Kurau" },
                        kode_desa:      { type: "string",  example: "63.01.04.2001", description: "Format BPS bertitik" },
                        nama_kecamatan: { type: "string",  example: "Kurau" }
                    }
                },
                LaporanAgregat: {
                    type: "object",
                    description: "BARIS MENTAH laporan desa (satu baris per desa × bulan × tahun) — bukan hasil agregasi. Endpoint mengembalikan seluruh ~80 kolom statistik; contoh di bawah hanya kolom utama. `jml_*` di sini adalah nilai yang diinput/diimpor, bukan turunan piramida.",
                    properties: {
                        id_laporan:     { type: "integer" },
                        bulan:          { type: "integer", example: 6, description: "1-12" },
                        tahun:          { type: "integer", example: 2026 },
                        id_desa:        { type: "integer", example: 1 },
                        nama_desa:      { type: "string",  example: "Kurau" },
                        nama_kecamatan: { type: "string",  example: "Kurau" },
                        jiwa_l:         { type: "integer", description: "Jumlah jiwa laki-laki" },
                        jiwa_p:         { type: "integer", description: "Jumlah jiwa perempuan" },
                        kk_l:           { type: "integer", description: "Kepala keluarga laki-laki" },
                        kk_p:           { type: "integer", description: "Kepala keluarga perempuan" },
                        jml_balita:     { type: "integer", description: "Nilai input desa; endpoint rekap/peta menghitung 0–4 dari kolom umur" },
                        jml_remaja:     { type: "integer", description: "Nilai input desa; definisi baku 10–19" },
                        jml_lansia:     { type: "integer", description: "Nilai input desa; definisi baku 60+" },
                        kb_aktif:       { type: "integer" },
                        jml_pus:        { type: "integer", description: "Nilai input desa; endpoint rekap/peta memakai PUS = perempuan 15–49" },
                        pus_pbi:        { type: "integer", description: "BPJS PBI" },
                        pus_non_pbi:    { type: "integer", description: "BPJS Non-PBI" },
                        non_jkn:        { type: "integer", description: "Tidak ber-JKN" }
                    }
                },
                RekapKecamatan: {
                    type: "object",
                    description: "Agregasi satu baris terbaru per desa. `total_balita` = 0–4 dan `total_pus` = perempuan 15–49, keduanya diturunkan dari kolom piramida umur.",
                    properties: {
                        nama_kecamatan: { type: "string",  example: "Kurau" },
                        total_jiwa:     { type: "integer", example: 14995 },
                        total_kk:       { type: "integer", example: 5407 },
                        total_balita:   { type: "integer", example: 957 },
                        total_pus:      { type: "integer", example: 3967 },
                        total_jiwa_l:   { type: "integer", example: 7573 },
                        total_jiwa_p:   { type: "integer", example: 7422 }
                    }
                },
                RekapDesa: {
                    type: "object",
                    description: "Agregasi satu baris terbaru per desa dalam satu kecamatan. Semantik balita/PUS sama dengan RekapKecamatan.",
                    properties: {
                        id_desa:      { type: "integer", example: 1 },
                        nama_desa:    { type: "string",  example: "Kurau" },
                        total_jiwa:   { type: "integer" },
                        total_kk:     { type: "integer" },
                        total_balita: { type: "integer" },
                        total_pus:    { type: "integer" },
                        total_jiwa_l: { type: "integer" },
                        total_jiwa_p: { type: "integer" }
                    }
                },
                BkkbnMirror: {
                    type: "object",
                    description: "Cermin SIGA BKKBN (pihak ketiga, cache 30 menit). `error` bernilai null saat sukses; bila gateway BKKBN gagal, pesan error diisi sambil tetap mengembalikan cache lama. Angka BUKAN data Erdekatala — sertakan label sumber.",
                    properties: {
                        status:     { type: "string", example: "ok" },
                        sumber:     { type: "string", example: "BKKBN (SIGA gateway), cache 30 menit — bukan data Erdekatala" },
                        tahun:      { type: "integer", example: 2026 },
                        bulan:      { type: "integer", nullable: true, example: 8 },
                        report:     { type: "string", nullable: true, example: "Tabel1" },
                        updated_at: { type: "string", nullable: true, description: "Waktu cache terakhir diperbarui." },
                        error:      { type: "string", nullable: true, example: null },
                        defs:       { type: "object", description: "Definisi kolom (khusus endpoint dallap)." },
                        data: {
                            type: "array",
                            items: { type: "object" },
                            description: "Baris per kecamatan; endpoint krs menambahkan baris kabupaten."
                        }
                    }
                },
                GeojsonFeatureCollection: {
                    type: "object",
                    description: "FeatureCollection GeoJSON standar (RFC 7946) + foreign member `sumber` dan `sumber_label` di level koleksi. Setiap feature membawa properti wilayah asli (kd_kecamatan, nm_kecamatan / kd_kelurahan, nm_kelurahan, nama_desa untuk peta desa) ditambah ±40 field indikator statistik gabungan (satu baris terbaru per desa): jiwa, KK, kelompok umur (termasuk `jml_anak_0_14`, `jml_produktif` 15–64, `jml_usia_65_plus`, `jml_balita`, `jml_remaja`, `jml_lansia`, `jml_pus`), pendidikan, pekerjaan, status kawin, dokumen adminduk, JKN/BPJS, serta KB & PUS. Wilayah tanpa data tetap memiliki semua field indikator dengan nilai 0.",
                    properties: {
                        type: { type: "string", example: "FeatureCollection" },
                        sumber: { type: "string", enum: ["desa", "dukcapil", "campuran"], example: "dukcapil" },
                        sumber_label: { type: "string", example: "* Sumber data adalah Data Konsolidasi Bersih (DKB) Semester I Tahun 2026" },
                        features: {
                            type: "array",
                            items: {
                                type: "object",
                                properties: {
                                    type: { type: "string", example: "Feature" },
                                    geometry: { type: "object", description: "Polygon/MultiPolygon geometri wilayah" },
                                    properties: {
                                        type: "object",
                                        description: "Properti wilayah + indikator. Contoh kunci: kd_kecamatan, nm_kecamatan, total_jiwa, total_kk, jiwa_l, jiwa_p, jml_anak_0_14, jml_produktif, jml_usia_65_plus, jml_balita, jml_remaja, jml_lansia, jml_pus, kb_aktif, kk_pend_sd, kk_ker_tani, pend_bpjs, dok_kia. Peta desa juga memuat `nama_desa`.",
                                        example: {
                                            kd_kecamatan: "004",
                                            nm_kecamatan: "KURAU",
                                            nama_desa: "Kurau",
                                            total_jiwa: 14995,
                                            total_kk: 5407,
                                            jml_anak_0_14: 3494,
                                            jml_produktif: 10484,
                                            jml_usia_65_plus: 1017,
                                            jml_balita: 957,
                                            jml_remaja: 2492,
                                            jml_lansia: 1593,
                                            jml_pus: 3967,
                                            kb_aktif: 3512,
                                            kk_pend_sd: 900,
                                            pend_bpjs: 11996
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        },
        security: [{ BearerAuth: [] }, { ApiKeyQuery: [] }],
        paths: {
            "/api/v1/info": {
                get: {
                    tags: ["Info"],
                    summary: "Informasi API",
                    description: "Menampilkan informasi umum API dan daftar endpoint tersedia.",
                    security: [{ BearerAuth: [] }],
                    responses: {
                        "200": {
                            description: "OK",
                            content: { "application/json": { schema: {
                                type: "object",
                                properties: {
                                    status:      { type: "string", example: "ok" },
                                    api:         { type: "string", example: "Erdekatala Public API" },
                                    version:     { type: "string", example: "v1" },
                                    description: { type: "string" },
                                    docs:        { type: "string", format: "uri" },
                                    endpoints:   { type: "object", description: "Peta endpoint → deskripsi singkat." }
                                }
                            }}}
                        },
                        "401": { "$ref": "#/components/responses/Unauthorized" },
                        "429": { "$ref": "#/components/responses/RateLimited" }
                    }
                }
            },
            "/api/v1/kecamatan": {
                get: {
                    tags: ["Wilayah"],
                    summary: "Daftar Kecamatan",
                    description: "Mengembalikan semua kecamatan di Kabupaten Tanah Laut beserta seluruh kolomnya (termasuk slug/profil publik).",
                    security: [{ BearerAuth: [] }],
                    responses: {
                        "200": {
                            description: "OK",
                            content: { "application/json": { schema: {
                                type: "object",
                                properties: {
                                    status: { type: "string" },
                                    total:  { type: "integer" },
                                    data:   { type: "array", items: { "$ref": "#/components/schemas/Kecamatan" } }
                                }
                            }}}
                        },
                        "401": { "$ref": "#/components/responses/Unauthorized" },
                        "429": { "$ref": "#/components/responses/RateLimited" }
                    }
                }
            },
            "/api/v1/desa": {
                get: {
                    tags: ["Wilayah"],
                    summary: "Daftar Desa",
                    description: "Mengembalikan daftar desa (seluruh kolom `m_desa` + `nama_kecamatan`). Bisa difilter per kecamatan.",
                    security: [{ BearerAuth: [] }],
                    parameters: [{
                        name: "id_kecamatan", in: "query", required: false,
                        description: "Filter berdasarkan ID kecamatan (integer positif)",
                        schema: { type: "integer", minimum: 1 }
                    }],
                    responses: {
                        "200": {
                            description: "OK",
                            content: { "application/json": { schema: {
                                type: "object",
                                properties: {
                                    status: { type: "string" },
                                    total:  { type: "integer" },
                                    data:   { type: "array", items: { "$ref": "#/components/schemas/Desa" } }
                                }
                            }}}
                        },
                        "401": { "$ref": "#/components/responses/Unauthorized" },
                        "429": { "$ref": "#/components/responses/RateLimited" }
                    }
                }
            },
            "/api/v1/laporan": {
                get: {
                    tags: ["Laporan Agregat"],
                    summary: "Data Laporan Agregat",
                    description: "Mengambil **baris mentah** laporan desa (satu baris per desa × bulan × tahun), diurutkan periode terbaru. Bukan hasil agregasi — desa bisa punya banyak periode. Gunakan endpoint rekap/peta untuk angka 'satu baris terbaru per desa'. Filter tidak valid diabaikan (dianggap tidak difilter).",
                    security: [{ BearerAuth: [] }],
                    parameters: [
                        { name: "id_kecamatan", in: "query", schema: { type: "integer", minimum: 1 }, description: "Filter kecamatan" },
                        { name: "id_desa",      in: "query", schema: { type: "integer", minimum: 1 }, description: "Filter desa" },
                        { name: "bulan",        in: "query", schema: { type: "integer", minimum: 1, maximum: 12 }, description: "Filter bulan (1-12)" },
                        { name: "tahun",        in: "query", schema: { type: "integer", minimum: 2000, maximum: 2100 }, description: "Filter tahun (2000-2100)" },
                        { name: "page",         in: "query", schema: { type: "integer", minimum: 1, default: 1 }, description: "Halaman" },
                        { name: "per_page",     in: "query", schema: { type: "integer", minimum: 10, maximum: 500, default: 100 }, description: "Jumlah data per halaman (10-500)" }
                    ],
                    responses: {
                        "200": {
                            description: "OK",
                            content: { "application/json": { schema: {
                                type: "object",
                                properties: {
                                    status:   { type: "string" },
                                    total:    { type: "integer" },
                                    page:     { type: "integer" },
                                    per_page: { type: "integer" },
                                    pages:    { type: "integer" },
                                    data:     { type: "array", items: { "$ref": "#/components/schemas/LaporanAgregat" } }
                                }
                            }}}
                        },
                        "401": { "$ref": "#/components/responses/Unauthorized" },
                        "429": { "$ref": "#/components/responses/RateLimited" }
                    }
                }
            },
            "/api/v1/laporan/rekap/kecamatan": {
                get: {
                    tags: ["Rekapitulasi"],
                    summary: "Rekap per Kecamatan",
                    description: "Rekapitulasi per kecamatan memakai **satu baris terbaru per desa** (tanpa double-count periode). `total_balita` dihitung dari umur 0–4 dan `total_pus` dari perempuan 15–49 (kolom piramida, bukan `jml_*` input desa).",
                    security: [{ BearerAuth: [] }],
                    responses: {
                        "200": {
                            description: "OK",
                            content: { "application/json": { schema: {
                                type: "object",
                                properties: {
                                    status: { type: "string" },
                                    total:  { type: "integer" },
                                    data:   { type: "array", items: { "$ref": "#/components/schemas/RekapKecamatan" } }
                                }
                            }}}
                        },
                        "401": { "$ref": "#/components/responses/Unauthorized" },
                        "429": { "$ref": "#/components/responses/RateLimited" }
                    }
                }
            },
            "/api/v1/laporan/rekap/desa": {
                get: {
                    tags: ["Rekapitulasi"],
                    summary: "Rekap per Desa",
                    description: "Rekapitulasi per desa dalam satu kecamatan, memakai **satu baris terbaru per desa**. Semantik balita/PUS sama dengan rekap kecamatan.",
                    security: [{ BearerAuth: [] }],
                    parameters: [{
                        name: "id_kecamatan", in: "query", required: true,
                        description: "ID kecamatan (wajib, integer positif)",
                        schema: { type: "integer", minimum: 1 }
                    }],
                    responses: {
                        "200": {
                            description: "OK",
                            content: { "application/json": { schema: {
                                type: "object",
                                properties: {
                                    status: { type: "string" },
                                    total:  { type: "integer" },
                                    data:   { type: "array", items: { "$ref": "#/components/schemas/RekapDesa" } }
                                }
                            }}}
                        },
                        "401": { "$ref": "#/components/responses/Unauthorized" },
                        "429": { "$ref": "#/components/responses/RateLimited" },
                        "422": { "$ref": "#/components/responses/ValidationError" }
                    }
                }
            },
            "/api/v1/map/kecamatan": {
                get: {
                    tags: ["Peta GeoJSON"],
                    summary: "GeoJSON Peta Kecamatan + Statistik",
                    description: "**PUBLIK — tanpa API key.**\n\nMengembalikan FeatureCollection GeoJSON batas seluruh kecamatan dengan indikator statistik gabungan (satu baris terbaru per desa) di `properties` tiap feature, plus foreign member `sumber` & `sumber_label` di level koleksi. Indikator mencakup jiwa/KK, kelompok umur (`jml_anak_0_14`, `jml_produktif`, `jml_usia_65_plus`, `jml_balita`, `jml_remaja`, `jml_lansia`, `jml_pus`), pendidikan, pekerjaan, status kawin, dokumen adminduk, JKN/BPJS, KB & PUS. Wilayah tanpa data tetap tampil dengan indikator 0. Cocok untuk Leaflet `L.geoJSON()`.",
                    security: [],
                    parameters: [{
                        name: "sumber", in: "query", required: false,
                        description: "Sumber angka: `dukcapil` (default, snapshot DKB Semester I 2026), `desa` (laporan desa), atau `campuran` (laporan desa menimpa Dukcapil per desa). Nilai tak dikenal → default.",
                        schema: { type: "string", enum: ["desa", "dukcapil", "campuran"], default: "dukcapil" }
                    }],
                    responses: {
                        "200": {
                            description: "FeatureCollection GeoJSON (11 fitur kecamatan)",
                            content: { "application/geo+json": { schema: { "$ref": "#/components/schemas/GeojsonFeatureCollection" } } }
                        },
                        "500": { description: "File peta tidak dapat dibaca", content: { "application/json": { schema: { "$ref": "#/components/schemas/Error" } } } }
                    }
                }
            },
            "/api/v1/map/desa": {
                get: {
                    tags: ["Peta GeoJSON"],
                    summary: "GeoJSON Peta Desa + Statistik",
                    description: "**PUBLIK — tanpa API key.**\n\nFeatureCollection GeoJSON batas desa + indikator statistik gabungan (satu baris terbaru per desa) di `properties`, termasuk `nama_desa`; foreign member `sumber` & `sumber_label` ada di level koleksi. Tanpa filter: semua desa. Dengan `?id_kecamatan=`: hanya desa di kecamatan itu. Kecamatan valid tanpa data → features dengan indikator 0; file peta tidak ada → `features: []`.",
                    security: [],
                    parameters: [
                        {
                            name: "id_kecamatan", in: "query", required: false,
                            description: "Filter berdasarkan ID kecamatan (integer positif, opsional)",
                            schema: { type: "integer", minimum: 1 }
                        },
                        {
                            name: "sumber", in: "query", required: false,
                            description: "Sumber angka: `dukcapil` (default), `desa`, atau `campuran`. Nilai tak dikenal → default.",
                            schema: { type: "string", enum: ["desa", "dukcapil", "campuran"], default: "dukcapil" }
                        }
                    ],
                    responses: {
                        "200": {
                            description: "FeatureCollection GeoJSON per desa",
                            content: { "application/geo+json": { schema: { "$ref": "#/components/schemas/GeojsonFeatureCollection" } } }
                        },
                        "422": { "$ref": "#/components/responses/ValidationError" },
                        "500": { description: "File peta tidak dapat dibaca", content: { "application/json": { schema: { "$ref": "#/components/schemas/Error" } } } }
                    }
                }
            },
            "/api/v1/bkkbn/krs": {
                get: {
                    tags: ["Cermin BKKBN"],
                    summary: "Monitoring KRS BKKBN",
                    description: "Cermin live Monitoring KRS dari SIGA gateway BKKBN (cache 30 menit). **Bukan data Erdekatala** — selalu tampilkan label sumber ke konsumen. Mengembalikan `rows` per kecamatan + baris kabupaten.",
                    security: [{ BearerAuth: [] }],
                    parameters: [{
                        name: "tahun", in: "query", required: false,
                        description: "Tahun (2000-2100, default 2026)",
                        schema: { type: "integer", minimum: 2000, maximum: 2100, default: 2026 }
                    }],
                    responses: {
                        "200": {
                            description: "OK — `data` berisi baris per kecamatan + baris kabupaten.",
                            content: { "application/json": { schema: { "$ref": "#/components/schemas/BkkbnMirror" } } }
                        },
                        "401": { "$ref": "#/components/responses/Unauthorized" },
                        "429": { "$ref": "#/components/responses/RateLimited" },
                        "422": { "$ref": "#/components/responses/ValidationError" }
                    }
                }
            },
            "/api/v1/bkkbn/dallap": {
                get: {
                    tags: ["Cermin BKKBN"],
                    summary: "Rekap Bangga Kencana BKKBN",
                    description: "Cermin live matriks Rekap Program Bangga Kencana (8 laporan Dallap: PKB, UPPKA, BKB, BKL, BKR, PIK Remaja, PUS, KB Aktif) per kecamatan dari SIGA gateway BKKBN (cache 30 menit). **Bukan data Erdekatala**.",
                    security: [{ BearerAuth: [] }],
                    parameters: [
                        { name: "tahun", in: "query", schema: { type: "integer", minimum: 2000, maximum: 2100, default: 2026 }, description: "Tahun (2000-2100)" },
                        { name: "bulan", in: "query", schema: { type: "integer", minimum: 1, maximum: 12, default: 8 }, description: "Bulan (1-12)" }
                    ],
                    responses: {
                        "200": {
                            description: "OK — menyertakan `defs` (definisi kolom) dan `data` (baris per kecamatan).",
                            content: { "application/json": { schema: { "$ref": "#/components/schemas/BkkbnMirror" } } }
                        },
                        "401": { "$ref": "#/components/responses/Unauthorized" },
                        "429": { "$ref": "#/components/responses/RateLimited" },
                        "422": { "$ref": "#/components/responses/ValidationError" }
                    }
                }
            },
            "/api/v1/bkkbn/yankb": {
                get: {
                    tags: ["Cermin BKKBN"],
                    summary: "YanKB BKKBN Tabel1/Tabel21",
                    description: "Cermin live laporan YanKB (Tabel1 = Cakupan Laporan Tempat Pelayanan KB, Tabel21 = Pelayanan Peserta KB Baru per Metode Kontrasepsi) per kecamatan dari SIGA gateway BKKBN (cache 30 menit). **Bukan data Erdekatala**.",
                    security: [{ BearerAuth: [] }],
                    parameters: [
                        { name: "report", in: "query", required: true, schema: { type: "string", enum: ["Tabel1", "Tabel21"] }, description: "Nama laporan (wajib, case-insensitive): Tabel1 = cakupan tempat pelayanan KB; Tabel21 = peserta KB baru per metode" },
                        { name: "tahun", in: "query", schema: { type: "integer", minimum: 2000, maximum: 2100, default: 2026 }, description: "Tahun (2000-2100)" },
                        { name: "bulan", in: "query", schema: { type: "integer", minimum: 1, maximum: 12, default: 8 }, description: "Bulan (1-12)" }
                    ],
                    responses: {
                        "200": {
                            description: "OK — `data` berisi tabel terstruktur hasil parse HTML SIGA.",
                            content: { "application/json": { schema: { "$ref": "#/components/schemas/BkkbnMirror" } } }
                        },
                        "401": { "$ref": "#/components/responses/Unauthorized" },
                        "429": { "$ref": "#/components/responses/RateLimited" },
                        "422": { "$ref": "#/components/responses/ValidationError" }
                    }
                }
            }
        }
    };

    SwaggerUIBundle({
        spec,
        dom_id: '#swagger-ui',
        deepLinking: true,
        presets: [SwaggerUIBundle.presets.apis, SwaggerUIBundle.SwaggerUIStandalonePreset],
        layout: "BaseLayout",
        tryItOutEnabled: true,
        requestInterceptor: (req) => {
            // Auto-inject api_key from localStorage if present
            const key = localStorage.getItem('sikada_api_key');
            if (key && !req.headers['Authorization']) {
                req.headers['Authorization'] = 'Bearer ' + key;
            }
            return req;
        }
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
    <?= $this->setVar('variant', 'bg')->include('templates/theme_toggle') ?>
</body>
</html>
