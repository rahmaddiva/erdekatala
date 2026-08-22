# AGENTS.md — Erdekatala Project

Dokumen ini menjelaskan konteks lengkap project Erdekatala untuk model AI atau agent yang bekerja di repository ini.

---

## Gambaran Umum

**Erdekatala** adalah sistem informasi kependudukan berbasis web untuk pemerintah daerah Kabupaten Tanah Laut, Kalimantan Selatan. Aplikasi ini digunakan untuk pencatatan, pelaporan, dan visualisasi data agregat kependudukan tingkat desa, mulai dari data jiwa, KK, pendidikan, pekerjaan, kesehatan (JKN/BPJS), hingga KB dan PUS.

Nama "Erdekatala" berasal dari singkatan RT-Desa-Kecamatan-Tanah Laut-Agregat.

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
- **Flash message**: `->with('success', '...')` atau `->with('error', '...')`, ditampilkan via SweetAlert2 toast di `templates/main.php`

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
```

---

## Seeder & Data Dummy

Jalankan ulang semua data dummy:

```bash
php spark db:seed DatabaseSeeder
```

Urutan seeder: `WilayahSeeder` → `DusunSeeder` → `RtSeeder` → `UserSeeder` → `LaporanAgregatSeeder`

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
13. **baseURL otomatis dideteksi** dari host request di `Config\App::__construct()` — mendukung localhost ↔ produksi tanpa mengubah `.env`/`App::$baseURL`. Jangan hardcode domain di view; selalu pakai `site_url()`/`base_url()`. Skema HTTPS diambil dari `X-Forwarded-Proto` (proxy/Cloudflare) → `HTTPS` → `REQUEST_SCHEME`

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
