# AGENTS.md — Erdekatala Project

Dokumen ini menjelaskan konteks lengkap project Erdekatala untuk model AI atau agent yang bekerja di repository ini.

---

## Gambaran Umum

**Erdekatala** adalah sistem informasi kependudukan berbasis web untuk pemerintah daerah Kabupaten Tanah Laut, Kalimantan Selatan. Aplikasi ini digunakan untuk pencatatan, pelaporan, dan visualisasi data agregat kependudukan tingkat desa, mulai dari data jiwa, KK, pendidikan, pekerjaan, kesehatan (JKN/BPJS), hingga KB dan PUS.

Nama "Erdekatala" berasal dari singkatan Elektronik Rumah Data Kependudukan Tanah Laut.

---

## Tech Stack

|Layer|Teknologi|
|---|---|
|Framework|CodeIgniter 4 (PHP 8.1+)|
|Database|MySQL, driver MySQLi|
|Frontend|AdminLTE 3 (dark mode), Bootstrap 4, jQuery|
|Chart|ZingChart v2.9.16|
|DataTable|DataTables (server-side)|
|Export|dompdf (PDF), phpspreadsheet (Excel)|
|Dev Server|Laragon (localhost)|
|Session|CI4 file-based session|

---

## Struktur Direktori Penting

```
erdekatala/
├── app/
│   ├── Config/
│   │   ├── Routes.php          # Semua route aplikasi
│   │   └── Filters.php         # Filter autentikasi + pengecualian
│   ├── Controllers/
│   │   ├── AuthController.php          # Login, logout, profile, remember me
│   │   ├── DashboardController.php     # Dashboard statistik + AJAX dropdown
│   │   ├── KecamatanController.php     # CRUD kecamatan + profil publik (admin_dinas only)
│   │   ├── DesaController.php          # CRUD desa (admin_dinas only)
│   │   ├── DusunController.php         # CRUD dusun (admin_dinas only)
│   │   ├── RtController.php            # CRUD RT (admin_dinas only)
│   │   ├── UserController.php          # CRUD user (admin_dinas; admin_kecamatan lihat saja)
│   │   ├── LaporanAgregatController.php # Laporan: index/create/edit/delete/export/riwayat/aktivitas
│   │   ├── DataTabelController.php     # Halaman Data Tabel semua laporan (per role)
│   │   ├── NotifikasiController.php    # Bell notifikasi (list/read/read_all/delete)
│   │   ├── PublicController.php        # Landing page, halaman per kecamatan, KRS BKKBN
│   │   ├── ApiController.php           # Endpoint data v1
│   │   ├── ApiDocsController.php       # Swagger UI + form registrasi + guide
│   │   └── ApiKeyAdminController.php   # Manajemen key oleh admin_dinas
│   ├── Models/
│   │   ├── KecamatanModel.php     # Tabel: kecamatan
│   │   ├── DesaModel.php          # Tabel: m_desa
│   │   ├── DusunModel.php         # Tabel: m_dusun
│   │   ├── RtModel.php            # Tabel: m_rt
│   │   ├── UserModel.php          # Tabel: users
│   │   ├── LaporanAgregatModel.php # Tabel: laporan_agregat (~80 kolom)
│   │   ├── LaporanLogModel.php    # Tabel: laporan_log (audit log)
│   │   ├── NotifikasiModel.php    # Tabel: notifikasi (+ generateReminder)
│   │   └── ApiKeyModel.php        # Tabel: api_keys (+ rate limit)
│   ├── Filters/
│   │   ├── LoggedIn.php           # Guard: session logged_in + auto-login remember_token
│   │   └── ApiKeyAuth.php         # Validasi API key, cek limit, increment counter
│   ├── Database/
│   │   ├── Migrations/            # 16 migration file (lihat daftar di bawah)
│   │   └── Seeds/
│   │       ├── DatabaseSeeder.php       # Entry point: panggil semua seeder
│   │       ├── WilayahSeeder.php        # 11 kecamatan + desa Tanah Laut
│   │       ├── DusunSeeder.php          # 2 dusun per desa
│   │       ├── RtSeeder.php             # 3 RT per dusun
│   │       ├── UserSeeder.php           # admin_dinas, admin_kecamatan, admin_desa
│   │       └── LaporanAgregatSeeder.php # Data dummy laporan per desa
│   └── Views/
│       ├── templates/           # main.php, navbar, sidebar, footer
│       ├── auth/                # login.php (remember me), profile.php
│       ├── dashboard/           # index.php (ZingChart)
│       ├── laporan/
│       │   ├── index.php            # Datatable (admin_dinas)
│       │   ├── index_desa.php       # Daftar laporan per bulan (admin_desa)
│       │   ├── index_kecamatan.php  # Card grid per desa (admin_kecamatan)
│       │   ├── form.php             # Form input/edit laporan
│       │   ├── export_pdf.php       # Template PDF dompdf
│       │   └── aktivitas.php        # Log aktivitas perubahan
│       ├── data_tabel/          # index.php — tabel semua laporan (per role)
│       ├── notifikasi/partials/ # list.php — dropdown bell notifikasi
│       ├── kecamatan/           # index.php, form.php — CRUD + profil publik
│       ├── desa/, dusun/, rt/   # CRUD views
│       ├── user/                # CRUD views user
│       ├── api/                 # docs.php (Swagger), guide.php, register.php, admin_keys.php, admin_create_key.php
│       └── public/              # landingpage.php, kecamatan.php
└── .claude/
    ├── agents/php-pro.md        # Agent spec untuk PHP 8.3+
    └── skills/code-reviewer/    # Script code review Python
```

---

## Skema Database

### Hierarki Wilayah

```
kecamatan (id_kecamatan, nama_kecamatan, kode_kecamatan, slug, ...profil publik)
  └── m_desa (id_desa, id_kecamatan, nama_desa, kode_desa)
        ├── m_dusun (id_dusun, id_desa, nama_dusun)
        │     └── m_rt (id_rt, id_dusun, no_rt)   # master wilayah; bukan unit lapor
        └── laporan_agregat (id_laporan, id_desa, id_user, bulan, tahun, ...~80 kolom statistik)
            # unique (id_desa, bulan, tahun) — unit lapor = desa
```

### Tabel `kecamatan`

Selain kolom dasar, tabel ini memiliki kolom profil untuk halaman publik per kecamatan:

|Kolom|Keterangan|
|---|---|
|id_kecamatan|PK|
|nama_kecamatan, kode_kecamatan|-|
|slug|Unique, untuk URL publik (`/kurau`, `/pelaihari`, dst)|
|page_title, meta_description|SEO halaman publik|
|deskripsi, foto|Konten profil|
|nama_camat, alamat_kantor, telepon, email, jam_layanan|Kontak layanan|
|is_public|TINYINT default 1; 0 = halaman publik disembunyikan (404)|

### Tabel `users`

|Kolom|Keterangan|
|---|---|
|id_user|PK|
|username|Unique|
|password|bcrypt via password_hash()|
|remember_token|VARCHAR(64) nullable, untuk fitur "Ingat Saya" (cookie 30 hari)|
|nama_lengkap|-|
|role|ENUM: admin_dinas, admin_kecamatan, admin_desa|
|id_kecamatan|FK nullable (untuk admin_kecamatan)|
|id_desa|FK nullable (untuk admin_desa)|

### Tabel `laporan_agregat` (kolom utama)

Setiap record mewakili laporan satu desa untuk satu periode bulan/tahun. Kolom statistik mencakup:
- Jiwa & KK per jenis kelamin
- Pendidikan KK (7 tingkat)
- Pekerjaan KK (8 jenis)
- Piramida penduduk per 5 tahun (0-4 s/d 85+), L dan P
- Kelompok khusus: balita, remaja, lansia
- Status perkawinan (4 kategori)
- Dokumen kependudukan: KTP-el, akta lahir, akta nikah, KK fisik
- JKN/BPJS: PBI, non-PBI, non-JKN
- KB & PUS

Setiap record mewakili laporan satu desa untuk satu periode bulan/tahun — satu desa boleh punya banyak periode (riwayat). **Agregasi hanya memakai 1 baris terbaru per desa**; lihat butir #20 di "Hal Penting".
Catatan: kolom FK unit lapor adalah `id_desa` (bukan `id_rt` — sudah dimigrasi). Ada index `(tahun, bulan)` untuk filter DataTables & export.

### Tabel `notifikasi`

Bell notifikasi per user (dibuat saat login, bukan cron).

|Kolom|Keterangan|
|---|---|
|id|PK|
|id_user|Penerima|
|judul, pesan|Isi notifikasi|
|url|Link tujuan (mis. `/laporan/input`)|
|jenis|`pengingat_laporan`, `rekap_kecamatan`, `rekap_dinas`|
|periode|Format `YYYY-MM`|
|is_read|TINYINT 0/1|
|created_at|DATETIME|

Unique key `(id_user, jenis, periode)` — tidak dobel untuk periode yang sama.

### Tabel `laporan_log`

Audit log perubahan laporan.

|Kolom|Keterangan|
|---|---|
|id|PK|
|id_laporan|Laporan terkait|
|id_user|Nullable (pelaku)|
|aksi|ENUM: create, update, delete|
|perubahan|TEXT nullable — detail field yang berubah (untuk update)|
|created_at|DATETIME|

### Tabel `profil_dukcapil`

Snapshot profil kependudukan resmi Dukcapil per desa (semester/tahun). Baseline "data pasti & valid" untuk landing page — laporan desa tetap di `laporan_agregat` dan menimpa baseline per desa (Desain B: desa yang sudah lapor → angka laporan; belum lapor → fallback snapshot Dukcapil). Angka sumber: data Dukcapil semester 2/2025 yang sama dengan `LaporanAgregatSeeder` (method `getKecamatanData()`/`mapKecamatanData()`/`distribute()` kini `public` dan direuse `ProfilDukcapilSeeder`).

|Kolom|Keterangan|
|---|---|
|id_profil|PK|
|id_desa|FK → `m_desa.id_desa` (ON DELETE CASCADE)|
|semester|1 \| 2|
|tahun|YEAR|
|imported_at|DATETIME nullable|
|imported_by|FK nullable → `users.id_user` (ON DELETE SET NULL)|
|...stats|~82 kolom statistik, identik `laporan_agregat` **plus** 2 kolom khusus Dukcapil|
|dok_kia|KARTU IDENTITAS ANAK (0-16 th), basis PENDUDUK. Migration `2026-10-09-000001`|
|dok_akta_cerai|AKTA PERCERAIAN, basis PENDUDUK. Migration `2026-10-09-000001`|

Unique key `(id_desa, tahun, semester)`. Landing page memakai `ProfilDukcapilModel::getLatestPerDesa()` + `getAggregatedStats($ids)`; filter sumber via `?sumber=desa|dukcapil` di `/` (default: campuran otomatis — laporan desa menimpa Dukcapil per desa, tanpa double-count).

**Atribusi sumber data RESMI** untuk tabel ini (baku, jangan ubah ejaannya — termasuk tanda bintang di depan): `* Sumber data adalah Data Konsolidasi Bersih (DKB) Semester I Tahun 2026`. Dihasilkan oleh `DukcapilImport::labelSumber($semester, $tahun)` (semester Romawi, tahun mengikuti periode impor) — pakai helper itu, bukan teks hardcode baru. Di workbook sumber, label ini ada di 7 sheet; sheet `KEPEMILIKAN DOK` terlabel "DKB pertanggal 30092026". Lihat butir #17.

---

## Sistem Role & Akses

|Role|Scope Data|Akses Khusus|
|---|---|---|
|`admin_dinas`|Semua kecamatan|CRUD kecamatan/desa/dusun/rt, CRUD user, kelola API key, lihat semua laporan + aktivitas|
|`admin_kecamatan`|Kecamatannya saja|Lihat laporan + aktivitas kecamatannya, lihat daftar akun admin desa (read-only)|
|`admin_desa`|Desanya saja|Input laporan desanya, lihat laporan desanya|

Sesi menyimpan: `id_user`, `id_kecamatan`, `id_desa`, `role`, `nama_lengkap`, `username`, `logged_in`.

Semua route kecuali `/`, `/login`, `/proses_login`, `/public/*`, `/getDesaByKecamatan*`, `assets/*`, `api/*`, dan halaman slug kecamatan diproteksi filter `LoggedIn` (lihat `Filters.php`).

### Fitur Remember Me

- Checkbox "Ingat Saya" di halaman login (`name="remember"`).
- Saat login: simpan `remember_token` (64 hex) di tabel `users` + cookie `remember_token` 30 hari (httpOnly, secure).
- Filter `LoggedIn` melakukan auto-login via cookie jika session kosong.
- Dihapus saat logout.

### Notifikasi Pengingat Laporan

- Dibuat di `AuthController::proses_login()` via `NotifikasiModel::generateReminder()` — bukan cron/jadwal.
- `admin_desa`: notifikasi "belum lapor" bulan berjalan jika desanya belum input.
- `admin_kecamatan`/`admin_dinas`: notifikasi rekap jumlah desa yang belum lapor di wilayahnya.
- Idempotent per `(id_user, jenis, periode)`.

---

## Routes Lengkap

```
# Public
GET  /                              PublicController::landingpage         (?kecamatan=slug&desa=slug)
GET  /public/landingpage            PublicController::landingpage
GET  /getDesaByKecamatan/:id        PublicController::getDesaByKecamatan
GET  /getDesaByKecamatanSlug/:slug  PublicController::getDesaByKecamatanSlug
GET  /public/krs-desa/:id           PublicController::getKrsDesa          (AJAX, id BKKBN)
GET  /:segment                      PublicController::kecamatan           (slug kecamatan, DI AKHIR)

# Auth
GET  /login                         AuthController::index
POST /proses_login                  AuthController::proses_login          (+ remember me, + notifikasi)
GET  /logout                        AuthController::logout
GET  /profile                       AuthController::profile
POST /profile/update                AuthController::update_profile
POST /profile/password              AuthController::change_password

# Dashboard
GET  /dashboard                     DashboardController::index
GET  /dashboard/getDesaByKecamatan/:id
GET  /dashboard/getDusunByDesa/:id
GET  /dashboard/getRtByDusun/:id

# Kecamatan (admin_dinas only)
GET  /kecamatan                     KecamatanController::index
GET  /kecamatan/create              KecamatanController::create
POST /kecamatan/store               KecamatanController::store
GET  /kecamatan/edit/:id            KecamatanController::edit
POST /kecamatan/update/:id          KecamatanController::update
POST /kecamatan/delete/:id          KecamatanController::delete

# Master Wilayah (admin_dinas only)
GET/POST /desa/*                    DesaController
GET/POST /dusun/*                   DusunController
GET/POST /rt/*                      RtController

# Laporan Agregat
GET  /laporan                       LaporanAgregatController::index
POST /laporan                       LaporanAgregatController::index  (AJAX DataTables)
GET  /laporan/input                 LaporanAgregatController::create
POST /laporan/store                 LaporanAgregatController::store
GET  /laporan/edit/:id              LaporanAgregatController::edit
POST /laporan/update/:id            LaporanAgregatController::update
POST /laporan/delete/:id            LaporanAgregatController::delete
GET  /laporan/export-options        LaporanAgregatController::exportOptions  (GET+POST)
GET  /laporan/export/:format        LaporanAgregatController::export  (excel|pdf)
GET  /laporan/getPreviousData       LaporanAgregatController::getPreviousData
GET  /laporan/riwayat/:id           LaporanAgregatController::getRiwayat  (AJAX log perubahan)
GET  /laporan/aktivitas             LaporanAgregatController::aktivitas  (admin_kecamatan & admin_dinas)
GET  /laporan/detailDesa/:id        LaporanAgregatController::detailDesa  (AJAX)

# Data Tabel
GET  /data-tabel                    DataTabelController::index  (?bulan=&tahun=)

# User Management
GET/POST /users/*                   UserController  (admin_dinas CRUD; admin_kecamatan lihat daftar)

# API Publik (filter apikeyauth pada /api/v1/*)
GET  /api/docs                      ApiDocsController::index  (Swagger UI, tanpa auth)
GET  /api/guide                     ApiDocsController::guide  (panduan integrasi, tanpa auth)
GET  /api/register                  ApiDocsController::register  (tanpa auth)
POST /api/register                  ApiDocsController::store
GET  /api/v1/info                   ApiController::info
GET  /api/v1/kecamatan              ApiController::kecamatan
GET  /api/v1/desa                   ApiController::desa
GET  /api/v1/laporan                ApiController::laporan
GET  /api/v1/laporan/rekap/kecamatan ApiController::rekapKecamatan
GET  /api/v1/laporan/rekap/desa     ApiController::rekapDesa

# Manajemen API key (admin_dinas only)
GET  /apikeys                       ApiKeyAdminController::index
GET  /apikeys/create                ApiKeyAdminController::create
POST /apikeys/store                 ApiKeyAdminController::store
POST /apikeys/revoke/:id            ApiKeyAdminController::revoke
POST /apikeys/activate/:id          ApiKeyAdminController::activate
POST /apikeys/delete/:id            ApiKeyAdminController::delete

# Notifikasi Bell
GET  /notifikasi/list               NotifikasiController::list
POST /notifikasi/read/:id           NotifikasiController::read
POST /notifikasi/read_all           NotifikasiController::read_all
POST /notifikasi/delete/:id         NotifikasiController::delete
```

---

## Alur Bisnis Utama

### 1. Input Laporan

1. Admin desa login, buka `/laporan/input`
2. Pilih desa (otomatis untuk admin_desa), bulan, tahun lalu isi semua field statistik
3. Validasi duplikat: satu desa tidak boleh input 2x untuk periode yang sama (`id_desa` + bulan + tahun)
4. Ada fitur "salin data bulan lalu" via AJAX `GET /laporan/getPreviousData?id_desa=`
5. Data disimpan ke `laporan_agregat` + dicatat ke `laporan_log` (aksi `create`)

### 2. Monitoring & Review

- **admin_desa** buka `/laporan`: daftar laporan desanya per tahun/bulan
- **admin_kecamatan** buka `/laporan`: card grid per desa + progress bar capaian, modal detail desa via AJAX
- **admin_dinas** buka `/laporan`: datatable server-side dengan filter kecamatan/desa
- **Aktivitas Perubahan** (`/laporan/aktivitas`, admin_kecamatan & admin_dinas): log seluruh aksi create/update/delete
- **Riwayat per laporan** (`/laporan/riwayat/:id`, AJAX): detail perubahan satu laporan

### 3. Dashboard Statistik

- Filter berjenjang: kecamatan → desa (chained dropdown AJAX)
- 7 chart ZingChart: piramida penduduk, pendidikan, status kawin, pekerjaan, gender, dokumen adminduk, JKN/BPJS
- Tabel ringkasan per wilayah (per kecamatan/desa tergantung filter)

### 4. Data Tabel

- `/data-tabel`: tabel semua kolom statistik + nama wilayah, dibatasi sesuai role
- Filter bulan/tahun via query string; daftar tahun dari data terbaru

### 5. Export

- Format: Excel (.xlsx via phpspreadsheet) atau PDF (A4 landscape via dompdf)
- Alur: `/laporan/export-options` (pilih kecamatan/desa/bulan/tahun) → `/laporan/export/:format`

### 6. Notifikasi Pengingat

- Dibuat saat login (lihat `NotifikasiModel::generateReminder`)
- Bell di navbar menampilkan jumlah unread; bisa ditandai dibaca / dihapus

### 7. Landing Page & Halaman Kecamatan Publik

- `/` : statistik agregat kabupaten + filter `?kecamatan=slug&desa=slug`, chart, dan tree **Monitoring KRS BKKBN**
- `/:segment` (mis. `/kurau`): halaman profil + statistik per kecamatan, filter `?desa=slug`, tabel statistik per desa
- **KRS BKKBN**: data diambil live dari API `siga-api-gateway.bkkbn.go.id` (idProvinsi=22, idKabupaten=322, tahun di-hardcode), cache file 30 menit di `writable/cache/`, map id BKKBN per kecamatan di-hardcode di `PublicController::bkkbnKecamatanMap()`. Drill-down desa via `/public/krs-desa/:id`.

#### Struktur landing page (`app/Views/public/landingpage.php`)

Halaman disusun jadi **3 grup bernomor** + 1 bagian navigasi. Nomor urut (`.section-head .num`) menandai grup data; bagian navigasi sengaja tanpa nomor dan berbobot lebih ringan (`.nav-block`).

| Urutan | Grup | Isi | Sumber data |
|---|---|---|---|
| — | Ringkasan Data | 4 kartu cakupan (desa lapor, jiwa, KK, JKN/BPJS) + `.note-strip` | `laporan_agregat` |
| 1 | Profil Kependudukan | tab: Data Pokok · Sosio Ekonomi · Piramida · Kesehatan & Dokumen | `laporan_agregat` (badge `.tag.src-desa`) |
| 2 | Sebaran Spasial | peta Leaflet tematik multi-indikator | `laporan_agregat` + `public/geojson/*` |
| 3 | Monitoring BKKBN | tab: VERVAL KRS · Cakupan YanKB · KB Baru · Dallap · PUS & KB | API SIGA BKKBN (badge `.tag` "Data pihak ketiga") |
| — | Jelajahi Kecamatan | grid kartu link ke `/{slug}` | `kecamatan` |

Catatan penting saat mengubah halaman ini:

1. **Bagian 3 pakai `<details class="group-collapse">`** — tertutup secara default agar tabel lebar BKKBN (24 kolom, `rowspan`/`colspan`) tidak membebani tampilan awal. Ringkasan memakai `.group-summary` sebagai `section-head`; kontennya di dalam `.group-body`.
2. **Badge & label sumber data Bagian 1** kini **dinamis** dari `$profilMeta` (dikirim `PublicController::landingpage()`). `$profilMode` dihitung sekali di atas view dan dipakai hero, note-strip, dan badge: `'laporan'` (semua desa lapor) \| `'dukcapil'` (tidak ada yang lapor) \| `'campuran'` (gabungan per desa). Filter manual via `?sumber=desa|dukcapil` — dropdown "Sumber data" di Bagian 1 mengubah param ini. `ProfilDukcapilSeeder` mengisi snapshot Dukcapil semester 2/2025 (reuse angka `LaporanAgregatSeeder`, method-nya kini `public`).
3. **ID tab tidak boleh berubah** — JS ZingChart/DataTables mengaitkan lewat `data-bs-target="#tab-*"` dan `shown.bs.tab` pada `#dataTabs`/`#sigaTabs`. Semua `data-bs-target` harus punya pasangan `id` dan sebaliknya.
4. **Token `--muted` (`#5f6b7a`)** dipilih agar lolos WCAG AA (≥4.5:1) di atas `--paper` **dan** `--paper-2`. Mencerahkannya akan menggagalkan kontras pada `.section-head .crumb`, `.group-filter label`, dan `.krs-hint`.
5. **Gaya komponen baru untuk dark mode** ditambahkan di `app/Views/templates/theme_toggle.php` (`.status-pill`, `.note-strip`, `.zero-report-badge`, `.summary-cta`, `.tag`, `.stat-card .icon`). Menambah komponen baru di landing page berarti menambah aturan `body.dark-mode` di sana juga.
6. **Target sentuh minimum 44px** untuk semua kontrol (`.nav-link-sikada`, `.btn-login`, `.nav-theme-btn`, `.btn-filter`). `:focus-visible` global 3px ada di blok dasar CSS landing page.
7. **`baseURL` auto-detect** diimplementasikan di `Config\App::__construct()` (lihat catatan #13). Sebelum ini `$baseURL` masih hardcode `http://localhost:8080/` sehingga seluruh `base_url()`/`site_url()` cross-origin — termasuk fetch peta & API yang diblokir browser.

---

## Konvensi Kode

- **Namespace**: `App\Controllers`, `App\Models`, `App\Filters`, `App\Database\Seeds`
- **Model**: semua extends `CodeIgniter\Model`, wajib ada `$table`, `$primaryKey`, `$allowedFields`
- **Controller**: semua extends `BaseController`, route ditulis manual di `Routes.php`
- **View**: extends `templates/main`, pakai `$this->section('content')` dan `$this->endSection()`
- **Validasi**: menggunakan `\Config\Services::validation()` di controller
- **Password**: selalu `password_hash()` saat simpan, `password_verify()` saat cek
- **Output HTML**: gunakan `esc()` untuk mencegah XSS
- **AJAX response**: `$this->response->setJSON($data)`
- **Tabel Monitoring BKKBN** (landing page, `app/Views/public/landingpage.php`): standar rapat — shell `.krs-shell` + tabel `.krs-tree`; gap tab→shell 8px (`#monitoring-bkkbn .tab-pane{padding-top:.5rem}`), toolbar `.6rem/.9rem` judul `.9rem`, hint `.45rem/.9rem`; sel tabel font `.68–.72rem` padding `.25–.3rem/.3–.35rem`; tabel lebar isi penuh (`width:100%; min-width:0`), header angka `nowrap` + kolom angka `min-width:130px`; kolom wilayah kunci sticky kiri (thead `th:first-child` + tbody `td.wilayah`); scope override per-id tabel (`#krs-tree`, `#dallap-tree`, `#puskb-tree`, `.krs-yankb`) — jangan ubah `.krs-tree` umum

---

## Migrations (urutan rilis)

```
2026-01-11-052017_CreateKecamatan
2026-01-11-052035_CreateDesa
2026-01-11-052058_CreateDusunAndRt
2026-01-11-052138_CreateUsers
2026-01-11-052202_CreateLaporanAgregat
2026-06-25-081545_CreateApiKeysTable
2026-06-25-000001_CreateApiKeys
2026-06-25-000002_CreateApiKeyLogs
2026-06-27-000001_AddOwnerFieldsToApiKeys
2026-06-28-000001_AddProfileFieldsToKecamatan
2026-07-02-000001_AddRememberTokenToUsers
2026-07-12-000001_AlterLaporanAgregatIdRtToIdDesa
2026-07-15-000001_AddPerformanceIndexes
2026-07-19-000001_CreateLaporanLog
2026-07-19-000002_AddPerubahanToLaporanLog
2026-08-02-000001_CreateNotifikasi
2026-10-01-000001_CreateProfilDukcapil
2026-10-09-000001_AddDokumenToProfilDukcapil
```

---

## Seeder & Data Dummy

Jalankan ulang semua data dummy:

```bash
php spark db:seed DatabaseSeeder
```

Urutan seeder: `WilayahSeeder` → `DesaKoordinatSeeder` → `DusunSeeder` → `RtSeeder` → `UserSeeder` → `ProfilDukcapilSeeder` → `LaporanAgregatSeeder`

**Laporan desa bersumber dari Dukcapil (2026-10-10).** `LaporanAgregatSeeder` kini **MIRROR per-desa** dari snapshot `profil_dukcapil` (1 baris terbaru per desa: urut `tahun DESC, semester DESC, id_profil DESC`) — bukan lagi distribusi sintetis angka kecamatan. Semua kolom statistik bersama disalin **apa adanya** (tanpa hitung ulang turunan) dan **tanpa** `dok_kia`/`dok_akta_cerai` (kolom khusus Dukcapil). Daftar kolom bersama dihitung **dinamis** dari irisan skema kedua tabel. Karena itu `ProfilDukcapilSeeder` **dipindah ke SEBELUM** `LaporanAgregatSeeder`: pada seed fresh, snapshot sudah terisi saat laporan di-seed.

- **Periode laporan = MIRROR snapshot** (asumsi): semester 1 → `bulan=6` (Juni), semester 2 → `bulan=12` (Desember), `tahun` mengikuti. Lihat `docs/superpowers/specs/2026-10-10-seeder-laporan-dari-dukcapil.md`.
- Desa tanpa snapshot Dukcapil **dilewati** (tidak ditebak).
- Bila `profil_dukcapil` **KOSONG**, seeder **jatuh ke perilaku LAMA** (distribusi sintetis SM2/2025) dan mencetak peringatan — supaya `php spark db:seed DatabaseSeeder` pada DB kosong tetap jalan. Method `getKecamatanData()`/`mapKecamatanData()`/`distribute()` tetap `public` (dipakai `ProfilDukcapilSeeder`).
- **Interaksi yang harus jujur diketahui:** `ProfilDukcapilSeeder` menghasilkan data **SINTETIS** (snapshot semester 2/2025, diturunkan dari `LaporanAgregatSeeder::getKecamatanData()` lalu didistribusikan proporsional per desa) — bukan data Dukcapil resmi, dan ia melakukan `truncate()` pada `profil_dukcapil`. Jadi seed penuh (`php spark db:seed DatabaseSeeder`) menghasilkan laporan **berbasis Dukcapil SINTETIS** (periode 12/2025). Untuk data **resmi**, jalankan `php spark dukcapil:import --commit` (lihat catatan #17 & #18) **lalu** `php spark db:seed LaporanAgregatSeeder` (periode 6/2026).

Akun default setelah seeding:

|Role|Username|Password|
|---|---|---|
|admin_dinas|admin_dinas|password123|
|admin_kecamatan|admin_kec_kurau|kecamatan123|
|admin_desa|admin_desa_kurau|desa123|

---

## Hal Penting yang Harus Diperhatikan

1. **Jangan gunakan route `/laporan/create`** -- route yang benar adalah `/laporan/input` (method controller tetap `create()`)
2. **Model `LaporanAgregatModel`** memiliki ~80 `$allowedFields` -- saat menambah kolom baru, pastikan ditambahkan di sini juga
3. **AJAX DataTables** di `laporan/index.php` menggunakan POST, bukan GET -- pastikan CSRF token disertakan
4. **Role check di controller**: `KecamatanController`, `DesaController`, `DusunController`, `RtController` memblokir non-`admin_dinas` di `__construct()`. `UserController` mengizinkan `admin_dinas` + `admin_kecamatan` (kecamatan hanya lihat daftar akun admin desa; CRUD tetap `_blockNonDinas()`). `ApiKeyAdminController` hanya `admin_dinas`
5. **Tampilan laporan berbeda per role** -- `index_desa.php`, `index_kecamatan.php`, `index.php` masing-masing untuk role berbeda
6. **ZingChart** (bukan ApexCharts) digunakan untuk semua chart di dashboard
7. **Kolom tabel**: `m_rt.no_rt` (bukan `nomor_rt`), `m_desa` (bukan `desa`), `m_dusun` (bukan `dusun`)
8. **Unit lapor = desa**: `laporan_agregat.id_desa` (bukan `id_rt`), unique `(id_desa, bulan, tahun)`
9. **Route dinamis `/(:segment)`** (slug kecamatan) harus tetap DI AKHIR `Routes.php` agar tidak bentrok dengan route lain
10. **Notifikasi pengingat dibuat saat login**, bukan cron — jangan cari scheduler
11. **Monitoring KRS BKKBN** bergantung pada API eksternal: id provinsi/kabupaten & tahun di-hardcode, map id BKKBN per kecamatan di-hardcode, cache 30 menit. Jika API mati, fallback ke cache lama dengan flag error
12. **Filter `loggedin`** mengecualikan slug kecamatan secara eksplisit (`takisung*`, `pelaihari*`, dst) — jika menambah kecamatan, tambahkan juga prefix-nya di `Filters.php`
13. **baseURL otomatis dideteksi** dari host request di `Config\App::__construct()` — mendukung localhost ↔ produksi tanpa mengubah `.env`/`App::$baseURL`. Jangan hardcode domain di view; selalu pakai `site_url()`/`base_url()`. Skema HTTPS diambil dari `X-Forwarded-Proto` (proxy/Cloudflare) → `HTTPS` → `REQUEST_SCHEME`. Sub-folder didukung dari direktori `SCRIPT_NAME`. Deteksi dilewati (pakai nilai config) bila `app.baseURL` diset eksplisit di `.env` atau saat dijalankan dari CLI (`php spark`)
14. **Timezone aplikasi = `Asia/Makassar` (WITA)** di `App::$appTimezone`, di-hardcode (bukan lewat `.env`). CI4 memanggil `date_default_timezone_set($appTimezone)` saat boot, jadi seluruh `date()`/`time()`/`strtotime()` di 78 titik pemakaian konsisten WITA dan tidak bergantung `date.timezone` di php.ini server. Sebelumnya `UTC` — pesan "data tersimpan per …" di landing page jadi 8 jam lebih awal dari kenyataan.
15. **Cache BKKBN disegarkan dengan `php spark bkkbn:warmup`** — bukan cron, jalankan manual atau jadwalkan sendiri. Cache KRS diambil on-demand saat pengguna klik ▶, jadi kecamatan yang jarang dibuka bisa menyimpan cache berbulan-bulan dan memunculkan banner "data tersimpan per …". Opsi: `--only krs|yankb|dallap|all`, `--bulan 1..12`, `--tahun YYYY`. Exit code non-zero bila ada yang gagal.
16. **Parser opsi CLI CodeIgniter hanya mendukung `--nama nilai` (spasi)**; bentuk `--nama=nilai` ditelan diam-diam. Command `bkkbn:warmup` menangani keduanya lewat helper `opsi()`, tapi command baru sebaiknya ikut menangani ini atau pakai sintaks spasi di dokumentasinya.
17. **`php spark dukcapil:import`** — impor snapshot Dukcapil dari workbook Excel ke `profil_dukcapil`. **Default = DRY-RUN**: tanpa `--commit` tidak ada baris yang ditulis, hanya laporan rencana (periode lama yang akan dihapus + 135 baris periode target yang akan disisipkan). Opsi: `--file <path>` (default `ROOTPATH . 'DATA KEPENDUDUKAN KAB TALA SM I 2026.xlsx'`), `--tahun <YYYY>` (default 2026), `--semester <1|2>` (default 1), `--commit`. Sintaks resminya **spasi** (`--tahun 2026`) mengikuti batasan parser opsi CI4 di butir #16, meski helper `opsi()` di command juga menerima `--nama=nilai`. Bersifat **REPLACE**, bukan append: saat `--commit`, periode lama dihapus dulu dengan `DELETE FROM` (bukan `TRUNCATE` — di MySQL itu DDL ber-implicit commit, tidak bisa di-rollback) lalu disisipkan 135 baris periode target, semuanya dalam satu transaksi; keadaan akhir = hanya ada satu periode. Command menjalankan hard-assert V1–V7 (total jiwa 378.190, KK 132.030, join 135/135 desa) dan **membatalkan tanpa menulis** bila ada yang gagal, lalu verifikasi pasca-commit (COUNT=135, hanya periode target). Rencana & asumsi A1–A8: `docs/superpowers/plans/2026-10-09-import-dukcapil-sm1-2026.md`. **Atribusi sumber data RESMI** yang dipakai aplikasi (baku — jangan ubah ejaannya, termasuk tanda bintang di depan): `* Sumber data adalah Data Konsolidasi Bersih (DKB) Semester I Tahun 2026`. Sumber kebenarannya SATU helper: `DukcapilImport::labelSumber(int $semester, int $tahun)` (`public static`) — dipakai command pada ringkasan dry-run **dan** ringkasan pasca-commit (blok `SUMBER DATA RESMI`); semester ditulis **angka Romawi** dan tahun mengikuti opsi `--semester`/`--tahun`, sedangkan kata-katanya tetap persis. Pakai helper ini, bukan teks hardcode baru, di mana pun label perlu ditampilkan. Catatan workbook: label di atas ada di **7 sheet**; sheet `KEPEMILIKAN DOK` terlabel **"DKB pertanggal 30092026"** (beda potongan — dasar asumsi A5).
18. **`profil_dukcapil` praktis snapshot SATU periode** — sejak 2026-10-09 `ProfilDukcapilModel::getAggregatedStats()` menjumlahkan **1 baris terbaru per desa** (bukan semua baris), jadi periode ganda tidak lagi double-count. Meski begitu tetap **jangan menambah periode baru tanpa menghapus yang lama** (label semester/tahun di note-strip masih diambil dari `orderBy('tahun DESC, semester DESC')->first()`), dan **jangan re-seed di lingkungan produksi**: `php spark db:seed DatabaseSeeder` memanggil `ProfilDukcapilSeeder` yang melakukan `truncate()` pada `profil_dukcapil` → MENGHAPUS data resm…
19. **Method `BkkbnMirror` sengaja `private`** (bukan `protected`/`public`). Untuk memakainya di luar controller, bungkus lewat `App\Libraries\BkkbnMirrorService` yang mengekspos API publik sempit — jangan ubah visibility di trait. Tiga method fetch di trait bernama `fetch*Internal` karena nama publiknya dipakai wrapper service.
20. **Agregasi laporan memakai "1 baris terbaru per desa", BUKAN "1 periode terbaru global"** (diperbaiki 2026-10-09; lihat `docs/superpowers/specs/2026-10-09-fix-double-count-periode.md`). `laporan_agregat` bisa berisi banyak periode sekaligus, dan `SUM` tanpa filter periode membuat desa ber-periode ganda terhitung dua kali — **dulu** (data sintetis SM2/2025) Total Jiwa 380.525, nilai benar 375.583 (selisih +4.942 dari Ranggang & Batu Ampar). **Sejak 2026-10-10** `LaporanAgregatSeeder` MIRROR snapshot Dukcapil (lihat "Seeder & Data Dummy"), jadi data di DB dev kini **135 baris @ 6/2026** dengan Total Jiwa **378.190** — angka 375.583 di bawah adalah keadaan **historis** (sebelum seeder diubah). Aturan yang berlaku sekarang:
    - **Satu sumber kebenaran**: `LaporanAgregatModel::latestPerDesaSubquery()` memilih 1 baris per desa — urutan `(tahun*100 + bulan) DESC`, tie-break `MAX(id_laporan)` di dalam periode terpilih (deterministik; tidak bergantung urutan insert/fisik/mesin DB). `ProfilDukcapilModel` punya padanannya (`tahun*10 + semester`, tie-break `MAX(id_profil)`).
    - `getAggregatedStats()`, `getCoverage()`, `getLatestPeriod()`, `getMapKecamatan()`, `getMapDesa()`, `getSummaryPerKecamatan()`, dan `getSummaryPerDesa()` **semuanya turunan subquery itu** — jangan menulis `SUM(...)`/`COUNT(...)` atas `laporan_agregat` langsung, dan jangan menduplikasi logika periode: pakai subquery/method yang sudah ada supaya angka kartu, peta, API, dan label tidak pernah saling bertentangan. Kalau menambah agregasi baru, ikutkan subquery ini.
    - Kenapa bukan "1 periode terbaru global" (`MAX(tahun*100+bulan)`): terukur di DB ini hanya **2.060** karena periode terbaru (8/2026) cuma berisi 1 desa → 133 desa hilang dari total kabupaten. Opsi per-desa terpilih justru memberi 375.583 tanpa desa yang hilang, dan otomatis sama dengan opsi global saat semua desa sudah lapor di periode terbaru.
    - **Label periode = periode UTAMA** (desa terbanyak), bukan periode kalender terbaru: `getLatestPeriod()` mengembalikan `desa_lapor` (desa pada periode itu) **dan** `desa_total` (desa lintas periode). Sejak 2026-10-10 (MIRROR Dukcapil) data dev = **6/2026, desa_lapor=135, desa_total=135** (satu periode, tidak ada desa hilang). Jangan mengklaim label itu sebagai "satu bulan" bila `desa_total > desa_lapor`.
    - **Peta & API ikut tunduk pada aturan ini** (diperbaiki menyusul temuan "peta 380.525 vs kartu 375.583"): `getMapKecamatan()`/`getMapDesa()` dipakai `PublicController::buildMapKecamatan()` dan endpoint publik `/api/v1/map/kecamatan` & `/api/v1/map/desa`; `getSummaryPerKecamatan()`/`getSummaryPerDesa()` dipakai dashboard & `ApiController`. Semuanya kini memakai subquery yang sama.
    - **Cara verifikasi cepat**: kesetaraan total `total_jiwa` antara GeoJSON peta dan kartu "Total Jiwa" di landing page **hanya berlaku bila sumbernya sama** — bandingkan `?sumber=desa` dengan `?sumber=desa`, atau `?sumber=dukcapil` dengan `?sumber=dukcapil`. **Sejak 2026-10-10** laporan desa MIRROR Dukcapil, jadi `?sumber=desa` ↔ **378.190** **sama dengan** `?sumber=dukcapil` (default) ↔ **378.190** (DKB Dukcapil SM I/2026) — kesetaraan lintas sumber ini sekarang WAJAR. Selisih terhadap angka dari sumber lain (mis. angka historis 375.583) adalah tanda data sudah berbeda, BUKAN tanda agregasi salah; yang berarti bug adalah selisih ≠ 0 **di dalam sumber yang sama** (mis. `/api/v1/map/kecamatan?sumber=dukcapil` vs kartu landing page default, atau `/api/v1/map/kecamatan` vs `/api/v1/map/desa` dengan `?sumber=` yang sama).
    - **Sumber data bersifat eksplisit (2026-10-09)**: **default sumber data aplikasi kini Dukcapil** (snapshot `profil_dukcapil`, DKB Semester I 2026 → Total Jiwa 378.190); laporan desa (`laporan_agregat`) dipakai bila `?sumber=desa` dipilih. Parameter `?sumber=desa|dukcapil|campuran` (default `dukcapil`) berlaku di landing page `/`, halaman per-kecamatan `/{slug}`, **dan endpoint peta `/api/v1/map/kecamatan` & `/api/v1/map/desa`**. **Sejak 2026-10-10** `laporan_agregat` MIRROR snapshot Dukcapil, jadi `?sumber=desa` dan `?sumber=dukcapil` kini memberi Total Jiwa yang **sama** (378.190) — bukan lagi 375.583 vs 378.190. Angka 375.583 adalah keadaan historis (data laporan sintetis SM2/2025) sebelum seeder diubah; 380.525 yang lama tetap salah (double-count periode).
21. **`vendor/bin/phpunit` RUSAK di Laragon ini** — jalankan `php vendor/phpunit/phpunit/phpunit tests/unit`. Grup DB test = `tests` (SQLite `:memory:`, prefix `db_`), jadi SQL mentah di model/perintah WAJIB memakai prefix tabel (`$this->db->prefixTable()` / `protectIdentifiers`), bukan nama tabel hardcode.

---

## Public API

### Overview

API publik read-only berbasis API key. Data yang diekspos adalah semua data agregat kependudukan.

- Base URL: `/api/v1/`
- Auth: `Authorization: Bearer <api_key>` atau query param `?api_key=<key>` — **kecuali endpoint peta `/api/v1/map/*` yang bersifat publik tanpa API key**
- Rate limit: 1000 request/hari per key (reset tengah malam)
- Response format: JSON (`application/geo+json` untuk endpoint peta)
- CSRF tidak diterapkan pada `/api/v1/*` (lihat `Filters.php`)

### Tabel `api_keys`

Skema aktual DB (diverifikasi via `INFORMATION_SCHEMA.COLUMNS`, 2026-08-22). Tabel dibuat oleh migration `2026-06-25-000001_CreateApiKeys` + `2026-06-27-000001_AddOwnerFieldsToApiKeys`. Migration `2026-06-25-081545_CreateApiKeysTable` (yang mendefinisikan kolom `key_value`, `label`, `status`, dst) TIDAK pernah diterapkan karena tabel sudah ada — `createTable('api_keys', true)` di sana bersifat if-not-exists sehingga dilewati. Dokumentasi lama (key_value/label/daily_limit/requests_today/status) tidak sesuai kenyataan.

|Kolom|Tipe|Keterangan|
|---|---|---|
|id|INT UNSIGNED AUTO_INCREMENT|PK|
|name|VARCHAR(100) NOT NULL|Nama aplikasi/project|
|owner_name|VARCHAR(100) NULL|Nama pemohon (registrasi publik)|
|owner_org|VARCHAR(150) NULL|Instansi/organisasi pemohon|
|email|VARCHAR(150) NOT NULL|Email kontak pemohon|
|api_key|VARCHAR(64) NOT NULL, UNIQUE|**Hash SHA-256** dari key plaintext — bukan plaintext (lihat catatan di bawah)|
|is_active|TINYINT(1) NOT NULL DEFAULT 1|1=aktif, 0=nonaktif|
|rate_limit|INT NOT NULL DEFAULT 1000|Maks request per hari|
|created_by|INT UNSIGNED NULL|FK → `users.id_user` (ON DELETE SET NULL); NULL=self-service registrasi publik, terisi jika dibuat admin|
|last_used_at|DATETIME NULL|Waktu terakhir key dipakai (`ApiKeyModel::incrementUsage`)|
|created_at|DATETIME NULL|Timestamp dibuat|
|updated_at|DATETIME NULL|Timestamp diperbarui|

**Penyimpanan hash SHA-256:** kolom `api_key` menyimpan **hash SHA-256** (`hash('sha256', $plaintext)` via `ApiKeyModel::hashKey()`), sehingga key plaintext **tidak bisa dibaca ulang dari DB**. Plaintext (64-char hex dari `ApiKeyModel::generateKey()` = `bin2hex(random_bytes(32))`) hanya ditampilkan sekali — di halaman sukses registrasi (`register_success.php`) atau saat admin membuat key manual. Filter `ApiKeyAuth` mencocokkan key dari request dengan meng-hash-nya lalu lookup kolom `api_key`. Untuk testing endpoint ber-key, daftarkan key baru via `POST /api/register` (perlu CSRF token) — jangan coba ambil key dari DB.

**Rate limit tanpa kolom counter:** tidak ada kolom `requests_today`/`last_reset_date`/`total_requests`. Counter harian disimpan sebagai file `writable/cache/api_rl_<md5hash>_<YYYYMMDD>.txt` dengan `flock` atomic di `ApiKeyModel::validateKey()`; reset harian otomatis dari nama file berbasis tanggal. Log request tercatat di tabel terpisah `api_key_logs` (migration `2026-06-25-000002_CreateApiKeyLogs`).

### Endpoint API

```
GET /api/docs                        Swagger UI (tanpa auth)
GET /api/guide                       Panduan integrasi (tanpa auth)
GET /api/register                    Form pendaftaran API key (tanpa auth)
POST /api/register                   Proses pendaftaran (tanpa auth)

GET /api/v1/info                     Info API dan daftar endpoint
GET /api/v1/kecamatan                Daftar semua kecamatan
GET /api/v1/desa                     Daftar desa (?id_kecamatan= opsional)
GET /api/v1/laporan                  Data laporan agregat per desa (paginasi, filter lengkap)
GET /api/v1/laporan/rekap/kecamatan  Rekapitulasi total per kecamatan
GET /api/v1/laporan/rekap/desa       Rekapitulasi per desa (?id_kecamatan= wajib)

GET /api/v1/map/kecamatan            GeoJSON FeatureCollection per kecamatan + indikator statistik gabungan (PUBLIK, tanpa API key)
GET /api/v1/map/desa                 GeoJSON FeatureCollection per desa + indikator statistik gabungan (?id_kecamatan= opsional; PUBLIK, tanpa API key)

GET /apikeys                         Admin: daftar semua key (admin_dinas only)
GET /apikeys/create                  Admin: form buat key manual
POST /apikeys/store                  Admin: simpan key manual
POST /apikeys/revoke/:id             Admin: nonaktifkan key
POST /apikeys/activate/:id           Admin: aktifkan kembali key
POST /apikeys/delete/:id             Admin: hapus key permanen
```

### File Terkait

|File|Fungsi|
|---|---|
|`app/Controllers/ApiController.php`|Endpoint data v1|
|`app/Controllers/ApiDocsController.php`|Swagger UI + form registrasi + guide|
|`app/Controllers/ApiKeyAdminController.php`|Manajemen key oleh admin_dinas|
|`app/Models/ApiKeyModel.php`|Model + validasi key + rate limit|
|`app/Filters/ApiKeyAuth.php`|Filter CI4 -- validasi key, cek limit, increment counter|
|`app/Views/api/docs.php`|Swagger UI (standalone, tanpa AdminLTE)|
|`app/Views/api/guide.php`|Panduan integrasi (standalone)|
|`app/Views/api/register.php`|Form pendaftaran key publik|
|`app/Views/api/register_success.php`|Halaman tampil key setelah daftar|
|`app/Views/api/admin_keys.php`|Tabel manajemen key untuk admin_dinas|
|`app/Views/api/admin_create_key.php`|Form buat key manual oleh admin_dinas|

### Catatan Penting

- Route `/api/*` dikecualikan dari filter `loggedin` di `Filters.php`
- Filter `apikeyauth` hanya diterapkan pada grup `/api/v1/*` — **kecuali** `/api/v1/map/*`: endpoint peta (`GET /api/v1/map/kecamatan` & `GET /api/v1/map/desa`) bersifat **publik tanpa API key** dan mengembalikan FeatureCollection GeoJSON (`application/geo+json`) dengan indikator statistik gabungan dari `public/geojson/*` + agregasi `LaporanAgregatModel::getMapKecamatan()`/`getMapDesa()`
- Halaman `/api/docs`, `/api/guide`, dan `/api/register` tidak perlu auth apapun
- Key di-generate dengan `bin2hex(random_bytes(32))` -- 64 karakter hex; yang disimpan di kolom `api_keys.api_key` adalah **hash SHA-256**-nya (plaintext tidak bisa dibaca ulang dari DB)
- Rate limit harian memakai file counter atomic (`writable/cache/api_rl_<md5hash>_<YYYYMMDD>.txt` + `flock`) di `ApiKeyModel::validateKey()` — reset harian otomatis dari nama file berbasis tanggal (bukan kolom `requests_today`)
