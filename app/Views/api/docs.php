<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sikada Tala API Docs</title>
    <link rel="stylesheet" href="https://unpkg.com/swagger-ui-dist@5.17.14/swagger-ui.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        :root{
            --ink:#0f1923; --ink-2:#1a2733;
            --primary:#dd4814; --primary-d:#b83a10;
            --paper:#f7f5f1; --paper-2:#ffffff;
            --line:#e5e1d8; --muted:#6b7280;
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
        .swagger-ui .opblock.opblock-get{border-color:var(--primary);background:rgba(221,72,20,0.04);}
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
        <img src="/assets/dist/img/Sikadaputih.png" alt="Sikada Tala" class="brand-logo">
        <div>
            <h1>Sikada Tala Public API</h1>
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
    Sikada Tala &middot; Sistem Informasi Kependudukan Kabupaten Tanah Laut, Kalimantan Selatan<br>
    <a href="/api/docs">Dokumentasi</a> &middot; <a href="/api/guide">Panduan</a> &middot; <a href="/api/register">Daftar API Key</a> &middot; <a href="/">Beranda</a>
</div>

<script src="https://unpkg.com/swagger-ui-dist@5.17.14/swagger-ui-bundle.js"></script>
<script>
    const spec = {
        openapi: "3.0.3",
        info: {
            title: "Sikada Tala Public API",
            version: "1.0.0",
            description: "API publik untuk mengakses data agregat kependudukan Kabupaten Tanah Laut, Kalimantan Selatan.\n\n**Cara Penggunaan:**\n1. [Daftarkan diri](/api/register) untuk mendapatkan API key gratis\n2. Gunakan API key sebagai Bearer token: `Authorization: Bearer <api_key>`\n3. Atau sebagai query param: `?api_key=<api_key>`\n\n**Rate Limit:** 1000 request per hari per API key. Header `X-RateLimit-Remaining` menunjukkan sisa kuota.",
            contact: {
                name: "DP3AP2KB Tanah Laut",
                url: window.location.origin
            },
            license: { name: "Open Data", url: "https://creativecommons.org/licenses/by/4.0/" }
        },
        servers: [{ url: window.location.origin, description: "Production Server" }],
        components: {
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
                    properties: {
                        id_kecamatan:   { type: "integer", example: 1 },
                        nama_kecamatan: { type: "string",  example: "Kurau" },
                        kode_kecamatan: { type: "string",  example: "6307010" }
                    }
                },
                Desa: {
                    type: "object",
                    properties: {
                        id_desa:        { type: "integer", example: 1 },
                        id_kecamatan:   { type: "integer", example: 1 },
                        nama_desa:      { type: "string",  example: "Kurau" },
                        kode_desa:      { type: "string",  example: "6307010001" },
                        nama_kecamatan: { type: "string",  example: "Kurau" }
                    }
                },
                LaporanAgregat: {
                    type: "object",
                    properties: {
                        id_laporan:     { type: "integer" },
                        bulan:          { type: "integer", example: 1, description: "1-12" },
                        tahun:          { type: "integer", example: 2025 },
                        no_rt:          { type: "string",  example: "001" },
                        nama_dusun:     { type: "string",  example: "Dusun Maju" },
                        nama_desa:      { type: "string",  example: "Kurau" },
                        nama_kecamatan: { type: "string",  example: "Kurau" },
                        jiwa_l:         { type: "integer", description: "Jumlah jiwa laki-laki" },
                        jiwa_p:         { type: "integer", description: "Jumlah jiwa perempuan" },
                        kk_l:           { type: "integer", description: "Kepala keluarga laki-laki" },
                        kk_p:           { type: "integer", description: "Kepala keluarga perempuan" },
                        jml_balita:     { type: "integer" },
                        jml_remaja:     { type: "integer" },
                        jml_lansia:     { type: "integer" },
                        kb_aktif:       { type: "integer" },
                        jml_pus:        { type: "integer" },
                        pus_pbi:        { type: "integer", description: "BPJS PBI" },
                        pus_non_pbi:    { type: "integer", description: "BPJS Non-PBI" },
                        non_jkn:        { type: "integer", description: "Tidak ber-JKN" }
                    }
                },
                RekapKecamatan: {
                    type: "object",
                    properties: {
                        nama_kecamatan: { type: "string",  example: "Kurau" },
                        total_jiwa:     { type: "integer" },
                        total_kk:       { type: "integer" },
                        total_balita:   { type: "integer" },
                        total_pus:      { type: "integer" },
                        total_jiwa_l:   { type: "integer" },
                        total_jiwa_p:   { type: "integer" }
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
                        "200": { description: "OK", content: { "application/json": { schema: { type: "object" } } } },
                        "401": { description: "API key tidak valid", content: { "application/json": { schema: { "$ref": "#/components/schemas/Error" } } } }
                    }
                }
            },
            "/api/v1/kecamatan": {
                get: {
                    tags: ["Wilayah"],
                    summary: "Daftar Kecamatan",
                    description: "Mengembalikan semua kecamatan di Kabupaten Tanah Laut.",
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
                        "401": { description: "Unauthorized" }
                    }
                }
            },
            "/api/v1/desa": {
                get: {
                    tags: ["Wilayah"],
                    summary: "Daftar Desa",
                    description: "Mengembalikan daftar desa. Bisa difilter per kecamatan.",
                    security: [{ BearerAuth: [] }],
                    parameters: [{
                        name: "id_kecamatan", in: "query", required: false,
                        description: "Filter berdasarkan ID kecamatan",
                        schema: { type: "integer" }
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
                        "401": { description: "Unauthorized" }
                    }
                }
            },
            "/api/v1/laporan": {
                get: {
                    tags: ["Laporan Agregat"],
                    summary: "Data Laporan Agregat",
                    description: "Mengambil data laporan agregat per RT. Mendukung filter dan paginasi.",
                    security: [{ BearerAuth: [] }],
                    parameters: [
                        { name: "id_kecamatan", in: "query", schema: { type: "integer" }, description: "Filter kecamatan" },
                        { name: "id_desa",      in: "query", schema: { type: "integer" }, description: "Filter desa" },
                        { name: "bulan",        in: "query", schema: { type: "integer", minimum: 1, maximum: 12 }, description: "Filter bulan (1-12)" },
                        { name: "tahun",        in: "query", schema: { type: "integer" }, description: "Filter tahun (misal: 2025)" },
                        { name: "page",         in: "query", schema: { type: "integer", default: 1 }, description: "Halaman" },
                        { name: "per_page",     in: "query", schema: { type: "integer", default: 100, maximum: 500 }, description: "Jumlah data per halaman (maks 500)" }
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
                        "401": { description: "Unauthorized" }
                    }
                }
            },
            "/api/v1/laporan/rekap/kecamatan": {
                get: {
                    tags: ["Rekapitulasi"],
                    summary: "Rekap per Kecamatan",
                    description: "Mengembalikan rekapitulasi total jiwa, KK, balita, dan PUS per kecamatan.",
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
                        "401": { description: "Unauthorized" }
                    }
                }
            },
            "/api/v1/laporan/rekap/desa": {
                get: {
                    tags: ["Rekapitulasi"],
                    summary: "Rekap per Desa",
                    description: "Rekapitulasi per desa dalam satu kecamatan.",
                    security: [{ BearerAuth: [] }],
                    parameters: [{
                        name: "id_kecamatan", in: "query", required: true,
                        description: "ID kecamatan (wajib)",
                        schema: { type: "integer" }
                    }],
                    responses: {
                        "200": { description: "OK" },
                        "401": { description: "Unauthorized" },
                        "422": { description: "Parameter id_kecamatan wajib diisi" }
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
</body>
</html>
