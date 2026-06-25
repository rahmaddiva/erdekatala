<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Erdekatala API Docs</title>
    <link rel="stylesheet" href="https://unpkg.com/swagger-ui-dist@5.17.14/swagger-ui.css">
    <style>
        body { margin: 0; background: #f4f6f9; font-family: sans-serif; }

        .api-header {
            background: linear-gradient(135deg, #1a73e8 0%, #0d47a1 100%);
            color: #fff;
            padding: 18px 40px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 2px 6px rgba(0,0,0,0.15);
        }
        .api-header h1 { margin: 0; font-size: 1.35rem; }
        .api-header p  { margin: 3px 0 0; font-size: 0.83rem; opacity: 0.85; }
        .api-header .btn-group a {
            display: inline-block; margin-left: 10px;
            padding: 7px 16px; border-radius: 4px;
            text-decoration: none; font-size: 0.85rem; font-weight: 600;
        }
        .btn-white  { background: #fff; color: #1a73e8; }
        .btn-outline { border: 1px solid rgba(255,255,255,0.7); color: #fff; }
        .btn-outline:hover { background: rgba(255,255,255,0.15); }

        /* Swagger UI light overrides */
        .swagger-ui { background: #f4f6f9; }
        .swagger-ui .topbar { display: none; }
        .swagger-ui .info { background: #fff; border-radius: 8px; padding: 20px 24px; margin: 20px; box-shadow: 0 1px 4px rgba(0,0,0,0.08); }
        .swagger-ui .info .title { color: #1a73e8; }
        .swagger-ui .scheme-container { background: #fff; box-shadow: 0 1px 4px rgba(0,0,0,0.08); padding: 12px 20px; }
        .swagger-ui .opblock-tag { color: #1a237e; font-weight: 700; border-bottom: 2px solid #e8eaf6; }
        .swagger-ui .opblock.opblock-get .opblock-summary-method { background: #1a73e8; }
        .swagger-ui .opblock.opblock-get { border-color: #1a73e8; background: rgba(26,115,232,0.04); }
        .swagger-ui .btn.authorize { border-color: #1a73e8; color: #1a73e8; }
        .swagger-ui .btn.authorize svg { fill: #1a73e8; }
        .swagger-ui section.models { background: #fff; border-radius: 8px; margin: 0 20px 20px; box-shadow: 0 1px 4px rgba(0,0,0,0.08); }
    </style>
</head>
<body>

<div class="api-header">
    <div>
        <h1>Erdekatala Public API</h1>
        <p>Data Agregat Kependudukan Kabupaten Tanah Laut, Kalimantan Selatan</p>
    </div>
    <div class="btn-group">
        <a href="/api/guide" class="btn-white mr-1">Panduan Integrasi</a>
        <a href="/api/register" class="btn-white">Dapatkan API Key</a>
        <a href="/" class="btn-outline">Kembali ke Website</a>
    </div>
</div>

<div id="swagger-ui"></div>

<script src="https://unpkg.com/swagger-ui-dist@5.17.14/swagger-ui-bundle.js"></script>
<script>
    const spec = {
        openapi: "3.0.3",
        info: {
            title: "Erdekatala Public API",
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
            const key = localStorage.getItem('erdekatala_api_key');
            if (key && !req.headers['Authorization']) {
                req.headers['Authorization'] = 'Bearer ' + key;
            }
            return req;
        }
    });
</script>
</body>
</html>
