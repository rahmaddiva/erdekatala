# AGENTS.md — Erdekatala Project

Dokumen ini menjelaskan konteks lengkap project Erdekatala untuk model AI atau agent yang bekerja di repository ini.

---

## Gambaran Umum

**Erdekatala** adalah sistem informasi kependudukan berbasis web untuk pemerintah daerah Kabupaten Tanah Laut, Kalimantan Selatan. Aplikasi ini digunakan untuk pencatatan, pelaporan, dan visualisasi data agregat kependudukan tingkat RT, mulai dari data jiwa, KK, pendidikan, pekerjaan, kesehatan (JKN/BPJS), hingga KB dan PUS.

Nama "Erdekatala" berasal dari singkatan RT-RW-Desa-Kecamatan-Laporan-Agregat.

---

## Tech Stack

| Layer | Teknologi |
|---|---|
| Framework | CodeIgniter 4 (PHP 8.1+) |
| Database | MySQL, driver MySQLi |
| Frontend | AdminLTE 3 (dark mode), Bootstrap 4, jQuery |
| Chart | ZingChart v2.9.16 |
| DataTable | DataTables (server-side) |
| Export | dompdf (PDF), phpspreadsheet (Excel) |
| Dev Server | Laragon (localhost) |
| Session | CI4 file-based session |

---

## Struktur Direktori Penting

```
erdekatala/
├── app/
│   ├── Config/
│   │   ├── Routes.php          # Semua route aplikasi
│   │   └── Filters.php         # Filter autentikasi
│   ├── Controllers/
│   │   ├── AuthController.php          # Login, logout, profile
│   │   ├── DashboardController.php     # Dashboard statistik + AJAX dropdown
│   │   ├── DesaController.php          # CRUD desa (admin_dinas only)
│   │   ├── DusunController.php         # CRUD dusun
│   │   ├── RtController.php            # CRUD RT
│   │   ├── UserController.php          # CRUD user (admin_dinas only)
│   │   ├── LaporanAgregatController.php # Laporan: index/create/edit/delete/export
│   │   └── PublicController.php        # Landing page publik
│   ├── Models/
│   │   ├── KecamatanModel.php   # Tabel: kecamatan
│   │   ├── DesaModel.php        # Tabel: m_desa
│   │   ├── DusunModel.php       # Tabel: m_dusun
│   │   ├── RtModel.php          # Tabel: m_rt
│   │   ├── UserModel.php        # Tabel: users
│   │   └── LaporanAgregatModel.php # Tabel: laporan_agregat (~80 kolom)
│   ├── Filters/
│   │   └── LoggedIn.php         # Guard: cek session logged_in
│   ├── Database/
│   │   ├── Migrations/          # 5 migration file
│   │   └── Seeds/
│   │       ├── DatabaseSeeder.php       # Entry point: panggil semua seeder
│   │       ├── WilayahSeeder.php        # 11 kecamatan + desa Tanah Laut
│   │       ├── DusunSeeder.php          # 2 dusun per desa
│   │       ├── RtSeeder.php             # 3 RT per dusun
│   │       ├── UserSeeder.php           # admin_dinas, admin_kecamatan, admin_desa
│   │       └── LaporanAgregatSeeder.php # Data dummy semua RT
│   └── Views/
│       ├── templates/           # main.php, navbar, sidebar, footer
│       ├── auth/                # login.php, profile.php
│       ├── dashboard/           # index.php (ZingChart)
│       ├── laporan/
│       │   ├── index.php            # Datatable (admin_dinas)
│       │   ├── index_desa.php       # Accordion per bulan (admin_desa)
│       │   ├── index_kecamatan.php  # Card grid per desa (admin_kecamatan)
│       │   ├── form.php             # Form input/edit laporan
│       │   └── export_pdf.php       # Template PDF dompdf
│       ├── desa/, dusun/, rt/   # CRUD views
│       ├── user/                # CRUD views user
│       └── public/              # landingpage.php
└── .claude/
    ├── agents/php-pro.md        # Agent spec untuk PHP 8.3+
    └── skills/code-reviewer/    # Script code review Python
```

---

## Skema Database

### Hierarki Wilayah

```
kecamatan (id_kecamatan, nama_kecamatan, kode_kecamatan)
  └── m_desa (id_desa, id_kecamatan, nama_desa, kode_desa)
        └── m_dusun (id_dusun, id_desa, nama_dusun)
              └── m_rt (id_rt, id_dusun, no_rt)
                    └── laporan_agregat (id_laporan, id_rt, id_user, bulan, tahun, ...~80 kolom statistik)
```

### Tabel `users`

| Kolom | Keterangan |
|---|---|
| id_user | PK |
| username | Unique |
| password | bcrypt via password_hash() |
| nama_lengkap | - |
| role | ENUM: admin_dinas, admin_kecamatan, admin_desa |
| id_kecamatan | FK nullable (untuk admin_kecamatan) |
| id_desa | FK nullable (untuk admin_desa) |

### Tabel `laporan_agregat` (kolom utama)

Setiap record mewakili laporan satu RT untuk satu periode bulan/tahun. Kolom statistik mencakup:
- Jiwa & KK per jenis kelamin
- Pendidikan KK (7 tingkat)
- Pekerjaan KK (8 jenis)
- Piramida penduduk per 5 tahun (0-4 s/d 85+), L dan P
- Kelompok khusus: balita, remaja, lansia
- Status perkawinan (4 kategori)
- Dokumen kependudukan: KTP-el, akta lahir, akta nikah, KK fisik
- JKN/BPJS: PBI, non-PBI, non-JKN
- KB & PUS

---

## Sistem Role & Akses

| Role | Scope Data | Akses Khusus |
|---|---|---|
| `admin_dinas` | Semua kecamatan | CRUD desa, CRUD user, lihat semua laporan |
| `admin_kecamatan` | Kecamatannya saja | Filter desa, lihat laporan kecamatannya |
| `admin_desa` | Desanya saja | Input laporan RT, lihat laporan desanya |

Sesi menyimpan: `id_user`, `id_kecamatan`, `id_desa`, `role`, `nama_lengkap`, `username`, `logged_in`.

Semua route kecuali `/`, `/login`, `/proses_login` diproteksi oleh filter `LoggedIn`.

---

## Routes Lengkap

```
# Public
GET  /                              PublicController::landingpage
GET  /public/landingpage            PublicController::landingpage
GET  /getDesaByKecamatan/:id        PublicController::getDesaByKecamatan

# Auth
GET  /login                         AuthController::index
POST /proses_login                  AuthController::proses_login
GET  /logout                        AuthController::logout
GET  /profile                       AuthController::profile
POST /profile/update                AuthController::update_profile
POST /profile/password              AuthController::change_password

# Dashboard
GET  /dashboard                     DashboardController::index
GET  /dashboard/getDesaByKecamatan/:id
GET  /dashboard/getDusunByDesa/:id
GET  /dashboard/getRtByDusun/:id

# Laporan Agregat
GET  /laporan                       LaporanAgregatController::index
POST /laporan                       LaporanAgregatController::index  (AJAX DataTables)
GET  /laporan/input                 LaporanAgregatController::create
POST /laporan/store                 LaporanAgregatController::store
GET  /laporan/edit/:id              LaporanAgregatController::edit
POST /laporan/update/:id            LaporanAgregatController::update
GET  /laporan/delete/:id            LaporanAgregatController::delete
GET  /laporan/export/:format        LaporanAgregatController::export  (excel|pdf)
GET  /laporan/getPreviousData       LaporanAgregatController::getPreviousData
GET  /laporan/detailDesa/:id        LaporanAgregatController::detailDesa  (AJAX)

# Master Wilayah
GET/POST /desa/*                    DesaController
GET/POST /dusun/*                   DusunController
GET/POST /rt/*                      RtController

# User Management
GET/POST /users/*                   UserController
```

---

## Alur Bisnis Utama

### 1. Input Laporan

1. Admin desa login, buka `/laporan/input`
2. Pilih RT, bulan, tahun lalu isi semua field statistik
3. Validasi duplikat: satu RT tidak boleh input 2x untuk periode yang sama
4. Ada fitur "salin data bulan lalu" via AJAX `GET /laporan/getPreviousData?id_rt=`
5. Data disimpan ke `laporan_agregat`

### 2. Monitoring & Review

- **admin_desa** buka `/laporan`: accordion per tahun/bulan, tampil status RT sudah/belum lapor
- **admin_kecamatan** buka `/laporan`: card grid per desa + progress bar capaian, modal detail RT via AJAX
- **admin_dinas** buka `/laporan`: datatable server-side dengan filter kecamatan/desa

### 3. Dashboard Statistik

- Filter berjenjang: kecamatan → desa → dusun → RT (chained dropdown AJAX)
- 7 chart ZingChart: piramida penduduk, pendidikan, status kawin, pekerjaan, gender, dokumen adminduk, JKN/BPJS
- Tabel ringkasan per wilayah (per kecamatan/desa/dusun tergantung filter)

### 4. Export

- Format: Excel (.xlsx via phpspreadsheet) atau PDF (A4 landscape via dompdf)
- Bisa difilter per kecamatan, desa, bulan, tahun

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

## Seeder & Data Dummy

Jalankan ulang semua data dummy:

```bash
php spark db:seed DatabaseSeeder
```

Urutan seeder: `WilayahSeeder` → `DusunSeeder` → `RtSeeder` → `UserSeeder` → `LaporanAgregatSeeder`

Akun default setelah seeding:

| Role | Username | Password |
|---|---|---|
| admin_dinas | admin_dinas | password123 |
| admin_kecamatan | admin_kec_kurau | kecamatan123 |
| admin_desa | admin_desa_kurau | desa123 |

---

## Hal Penting yang Harus Diperhatikan

1. **Jangan gunakan route `/laporan/create`** -- route yang benar adalah `/laporan/input` (method controller tetap `create()`)
2. **Model `LaporanAgregatModel`** memiliki ~80 `$allowedFields` -- saat menambah kolom baru, pastikan ditambahkan di sini juga
3. **AJAX DataTables** di `laporan/index.php` menggunakan POST, bukan GET -- pastikan CSRF token disertakan
4. **Role check di controller**: `DesaController` dan `UserController` memblokir non-`admin_dinas` di `__construct()`
5. **Tampilan laporan berbeda per role** -- `index_desa.php`, `index_kecamatan.php`, `index.php` masing-masing untuk role berbeda
6. **ZingChart** (bukan ApexCharts) digunakan untuk semua chart di dashboard
7. **Kolom tabel**: `m_rt.no_rt` (bukan `nomor_rt`), `m_desa` (bukan `desa`), `m_dusun` (bukan `dusun`)

---

## Public API

### Overview

API publik read-only berbasis API key. Data yang diekspos adalah semua data agregat kependudukan.

- Base URL: `/api/v1/`
- Auth: `Authorization: Bearer <api_key>` atau query param `?api_key=<key>`
- Rate limit: 1000 request/hari per key (reset tengah malam)
- Response format: JSON

### Tabel `api_keys`

| Kolom | Keterangan |
|---|---|
| id | PK |
| key_value | 64-char hex random, unique |
| label | Nama pengingat key |
| owner_name | Nama pemilik |
| owner_email | Email pemilik |
| owner_org | Instansi/organisasi (opsional) |
| daily_limit | Maks request per hari (default 1000) |
| requests_today | Counter hari ini (reset tiap hari) |
| last_reset_date | Tanggal terakhir reset counter |
| total_requests | Total kumulatif request |
| status | ENUM: active, revoked |

### Endpoint API

```
GET /api/docs                        Swagger UI (tanpa auth)
GET /api/register                    Form pendaftaran API key (tanpa auth)
POST /api/register                   Proses pendaftaran (tanpa auth)

GET /api/v1/info                     Info API dan daftar endpoint
GET /api/v1/kecamatan                Daftar semua kecamatan
GET /api/v1/desa                     Daftar desa (?id_kecamatan= opsional)
GET /api/v1/laporan                  Data laporan agregat per RT (paginasi, filter lengkap)
GET /api/v1/laporan/rekap/kecamatan  Rekapitulasi total per kecamatan
GET /api/v1/laporan/rekap/desa       Rekapitulasi per desa (?id_kecamatan= wajib)

GET /apikeys                         Admin: daftar semua key (admin_dinas only)
GET /apikeys/revoke/:id              Admin: nonaktifkan key
GET /apikeys/activate/:id            Admin: aktifkan kembali key
GET /apikeys/delete/:id              Admin: hapus key permanen
```

### File Terkait

| File | Fungsi |
|---|---|
| `app/Controllers/ApiController.php` | Endpoint data v1 |
| `app/Controllers/ApiDocsController.php` | Swagger UI + form registrasi |
| `app/Controllers/ApiKeyAdminController.php` | Manajemen key oleh admin_dinas |
| `app/Models/ApiKeyModel.php` | Model + validasi key + rate limit |
| `app/Filters/ApiKeyAuth.php` | Filter CI4 -- validasi key, cek limit, increment counter |
| `app/Views/api/docs.php` | Swagger UI (standalone, tanpa AdminLTE) |
| `app/Views/api/register.php` | Form pendaftaran key publik |
| `app/Views/api/register_success.php` | Halaman tampil key setelah daftar |
| `app/Views/api/admin_keys.php` | Tabel manajemen key untuk admin_dinas |

### Catatan Penting

- Route `/api/*` dikecualikan dari filter `loggedin` di `Filters.php`
- Filter `apikeyauth` hanya diterapkan pada grup `/api/v1/*`
- Halaman `/api/docs` dan `/api/register` tidak perlu auth apapun
- Key di-generate dengan `bin2hex(random_bytes(32))` -- 64 karakter hex
- Counter `requests_today` di-reset otomatis di `ApiKeyModel::validateKey()` saat tanggal berubah
