# PRD — Erdekatala
## Sistem Informasi Kependudukan Agregat Kabupaten Tanah Laut

**Versi:** 1.1
**Tanggal:** Agustus 2026
**Pengelola:** DP3AP2KB Kabupaten Tanah Laut, Kalimantan Selatan
**Status:** Active Development

---

## 1. Latar Belakang

DP3AP2KB Kabupaten Tanah Laut membutuhkan sistem terpusat untuk mengumpulkan, memantau, dan melaporkan data agregat kependudukan tingkat desa secara berkala. Sebelumnya proses pelaporan dilakukan manual melalui spreadsheet atau kertas, yang menyebabkan keterlambatan data, inkonsistensi, dan kesulitan dalam rekap lintas wilayah.

**Erdekatala** (singkatan dari RT-Desa-Kecamatan-Tanah Laut-Agregat) hadir sebagai solusi digital untuk menjembatani kebutuhan tersebut.

---

## 2. Tujuan Produk

- Menyediakan platform pelaporan data kependudukan per desa yang terstruktur dan konsisten.
- Memungkinkan monitoring capaian pelaporan secara real-time di level dinas, kecamatan, dan desa.
- Menghasilkan visualisasi statistik kependudukan yang dapat digunakan untuk pengambilan keputusan.
- Membuka akses data agregat kepada pihak ketiga melalui Public API.
- Menyediakan kanal publik (landing page + halaman per kecamatan) untuk transparansi data.

---

## 3. Pengguna & Peran

| Role | Cakupan Data | Hak Akses |
|---|---|---|
| `admin_dinas` | Seluruh kabupaten | CRUD kecamatan/desa/dusun/rt, CRUD user, lihat semua laporan + aktivitas, kelola API key |
| `admin_kecamatan` | Kecamatan yang bersangkutan | Lihat laporan, aktivitas, dan daftar akun admin desa di kecamatannya |
| `admin_desa` | Desa yang bersangkutan | Input laporan desanya, lihat laporan desanya |
| Publik (API) | Baca saja via API key | Akses data agregat via REST API dengan rate limit |
| Publik (Web) | Tanpa login | Landing page, halaman profil per kecamatan, monitoring KRS |

---

## 4. Ruang Lingkup Fitur

### 4.1 Autentikasi & Manajemen Profil

- Login dengan username dan password (bcrypt).
- Fitur **"Ingat Saya" (remember me)**: cookie `remember_token` 30 hari, auto-login via filter `LoggedIn`.
- Session berbasis file CI4.
- Semua halaman kecuali landing page, halaman publik kecamatan, login, dan endpoint API publik diproteksi filter `LoggedIn`.
- Pengguna dapat mengubah nama lengkap dan password melalui halaman profil.

### 4.2 Master Wilayah

Hierarki wilayah: **Kecamatan > Desa > Dusun > RT**

- `admin_dinas` dapat melakukan CRUD kecamatan, desa, dusun, dan RT.
- Kecamatan memiliki kolom profil publik (slug, deskripsi, kontak, `is_public`) yang ditampilkan di halaman publik.
- Setiap desa adalah unit pelaporan (satu laporan per desa per periode bulan/tahun).

### 4.3 Manajemen User

- `admin_dinas` dapat membuat, mengubah, dan menghapus akun user.
- `admin_kecamatan` dapat melihat daftar akun admin desa di kecamatannya (read-only).
- Saat membuat user baru, role dan wilayah (kecamatan/desa) ditentukan sesuai cakupan tugasnya.

### 4.4 Input Laporan Agregat

Setiap laporan mewakili satu desa untuk satu periode bulan/tahun. Data yang diinput mencakup:

- **Jiwa & KK:** total jiwa dan kepala keluarga per jenis kelamin.
- **Pendidikan KK:** 7 tingkat (tidak sekolah s/d perguruan tinggi).
- **Pekerjaan KK:** 8 jenis (petani, nelayan, PNS, wiraswasta, dll).
- **Piramida penduduk:** per kelompok umur 5 tahun (0-4 s/d 85+), laki-laki dan perempuan.
- **Kelompok khusus:** balita, remaja, lansia.
- **Status perkawinan:** 4 kategori (belum kawin, kawin, cerai hidup, cerai mati).
- **Dokumen kependudukan:** KTP-el, akta lahir, akta nikah, KK fisik.
- **JKN/BPJS:** PBI, non-PBI, non-JKN.
- **KB & PUS:** data keluarga berencana dan pasangan usia subur.

**Aturan bisnis:**
- Satu desa tidak dapat memiliki dua laporan untuk periode yang sama (validasi duplikat `id_desa` + bulan + tahun).
- Fitur salin data dari bulan sebelumnya tersedia via AJAX untuk mempercepat pengisian.
- Setiap aksi simpan/ubah/hapus dicatat ke **audit log** (`laporan_log`).

### 4.5 Monitoring Laporan

Tampilan berbeda sesuai role:

- **admin_desa:** daftar laporan desanya per tahun/bulan (sudah/belum lapor).
- **admin_kecamatan:** card grid per desa dengan progress bar capaian, modal detail desa via AJAX.
- **admin_dinas:** DataTable server-side dengan filter kecamatan dan desa.
- **Aktivitas Perubahan** (`/laporan/aktivitas`): log seluruh aksi create/update/delete (admin_kecamatan & admin_dinas).
- **Riwayat per laporan** (`/laporan/riwayat/:id`): detail perubahan satu laporan via AJAX.

### 4.6 Dashboard Statistik

- Filter berjenjang: Kecamatan > Desa (chained dropdown via AJAX).
- Visualisasi menggunakan ZingChart v2.9.16, terdiri dari 7 chart:
  1. Piramida penduduk
  2. Distribusi pendidikan KK
  3. Status perkawinan
  4. Jenis pekerjaan KK
  5. Komposisi gender
  6. Kepemilikan dokumen adminduk
  7. Cakupan JKN/BPJS
- Tabel ringkasan agregat per wilayah yang mengikuti granularitas filter aktif.

### 4.7 Data Tabel

- Halaman `/data-tabel` menampilkan seluruh kolom statistik + nama wilayah dalam satu tabel.
- Data dibatasi sesuai role (desa/kecamatan/semua).
- Filter bulan dan tahun via query string; daftar tahun diambil dari data terbaru.

### 4.8 Export Laporan

- Format tersedia: **Excel (.xlsx)** via phpspreadsheet dan **PDF (A4 landscape)** via dompdf.
- Alur: halaman `/laporan/export-options` untuk memilih kecamatan, desa, bulan, dan tahun, lalu `/laporan/export/:format`.

### 4.9 Notifikasi Pengingat Laporan

- Bell notifikasi per user di navbar (unread count, tandai dibaca, hapus).
- Notifikasi pengingat dibuat **saat login** (bukan cron) untuk periode bulan berjalan:
  - `admin_desa`: peringatan jika desanya belum lapor.
  - `admin_kecamatan` / `admin_dinas`: rekap jumlah desa yang belum lapor.
- Idempotent per `(id_user, jenis, periode)` — tidak ada duplikat.

### 4.10 Landing Page & Halaman Kecamatan Publik

- **Landing page** (`/`): statistik agregat kabupaten, filter `?kecamatan=slug&desa=slug`, chart, dan tree **Monitoring KRS BKKBN**.
- **Halaman per kecamatan** (`/:segment`, mis. `/kurau`, `/pelaihari`): profil kecamatan (deskripsi, kontak, camat), statistik, tabel per desa, filter `?desa=slug`. Halaman non-publik (`is_public=0`) mengembalikan 404.
- **Monitoring KRS BKKBN**: data live dari API BKKBN (`siga-api-gateway.bkkbn.go.id`, idProvinsi=22, idKabupaten=322), cache file 30 menit, drill-down per kecamatan via AJAX `/public/krs-desa/:id`.

### 4.11 Public REST API

API read-only berbasis API key untuk integrasi sistem eksternal.

**Spesifikasi:**
- Base URL: `/api/v1/`
- Autentikasi: `Authorization: Bearer <api_key>` atau query param `?api_key=<key>`
- Rate limit: 1.000 request/hari per key (reset tengah malam)
- Format respons: JSON
- CSRF tidak diterapkan pada `/api/v1/*`

**Endpoint:**

| Method | Path | Deskripsi |
|---|---|---|
| GET | `/api/v1/info` | Info API dan daftar endpoint |
| GET | `/api/v1/kecamatan` | Daftar semua kecamatan |
| GET | `/api/v1/desa` | Daftar desa, opsional filter `?id_kecamatan=` |
| GET | `/api/v1/laporan` | Data laporan per desa dengan paginasi dan filter lengkap |
| GET | `/api/v1/laporan/rekap/kecamatan` | Rekapitulasi agregat per kecamatan |
| GET | `/api/v1/laporan/rekap/desa` | Rekapitulasi per desa, wajib `?id_kecamatan=` |

**Pendaftaran API Key:**
- Publik dapat mendaftar melalui formulir di `/api/register` tanpa autentikasi.
- API key di-generate sebagai 64 karakter hex (`bin2hex(random_bytes(32))`).
- `admin_dinas` dapat membuat key manual, merevoke, mengaktifkan kembali, atau menghapus key melalui panel `/apikeys`.

**Dokumentasi API:**
- Swagger UI tersedia di `/api/docs` tanpa autentikasi.
- Panduan integrasi tersedia di `/api/guide` tanpa autentikasi.

---

## 5. Arsitektur Teknis

| Layer | Teknologi |
|---|---|
| Framework | CodeIgniter 4 (PHP 8.1+) |
| Database | MySQL, driver MySQLi |
| Frontend | AdminLTE 3 (dark mode), Bootstrap 4, jQuery |
| Visualisasi | ZingChart v2.9.16 |
| Tabel data | DataTables (server-side processing) |
| Export | dompdf (PDF), phpspreadsheet (Excel) |
| Session | CI4 file-based session |
| Dev server | Laragon |

**Skema database (hierarki):**

```
kecamatan (profil publik: slug, deskripsi, kontak, is_public)
  └── m_desa
        ├── m_dusun
        │     └── m_rt          (master wilayah; bukan unit lapor)
        └── laporan_agregat     (unit lapor = desa; ~80 kolom statistik)
```

**Tabel pendukung:**
- `users` — plus `remember_token` untuk remember me.
- `laporan_log` — audit log (aksi create/update/delete + detail perubahan).
- `notifikasi` — bell notifikasi per user (jenis, periode, is_read).
- `api_keys` — key + rate limit + info pemilik.

---

## 6. Batasan & Asumsi

- Aplikasi ditujukan untuk penggunaan internal pemerintah daerah dan tidak memerlukan registrasi mandiri.
- Data kecamatan dapat dikelola oleh `admin_dinas` (CRUD + profil publik).
- Notifikasi pengingat laporan hanya muncul saat login (belum ada pengiriman email/SMS otomatis).
- Audit log terbatas pada aksi laporan agregat (create/update/delete), belum mencakup seluruh aksi user.
- API publik hanya menyajikan data agregat, tidak ada data individual warga.
- Monitoring KRS BKKBN bergantung pada ketersediaan API eksternal BKKBN; jika API mati, ditampilkan cache ±30 menit terakhir.

---

## 7. Kriteria Penerimaan (Definition of Done)

Sebuah fitur dianggap selesai jika:

1. Fungsionalitas berjalan sesuai alur bisnis yang didefinisikan.
2. Validasi input berjalan di sisi server (tidak hanya client-side).
3. Akses antar role sudah diverifikasi (tidak ada kebocoran data lintas wilayah).
4. Output HTML menggunakan `esc()` untuk mencegah XSS.
5. CSRF token disertakan pada semua request form dan AJAX POST.
6. Tampilan responsif dan konsisten dengan tema AdminLTE dark mode.

---

## 8. Roadmap & Prioritas

| Prioritas | Fitur | Status |
|---|---|---|
| P0 | Autentikasi & session management | Selesai |
| P0 | Input laporan agregat per desa | Selesai |
| P0 | Monitoring laporan per role | Selesai |
| P0 | Dashboard statistik + ZingChart | Selesai |
| P0 | Export Excel dan PDF | Selesai |
| P1 | Master wilayah CRUD (termasuk kecamatan) | Selesai |
| P1 | Manajemen user | Selesai |
| P1 | Public REST API + API key management | Selesai |
| P1 | Landing page publik + halaman per kecamatan | Selesai |
| P1 | Notifikasi pengingat pelaporan | Selesai |
| P1 | Audit log aktivitas laporan | Selesai |
| P1 | Data Tabel | Selesai |
| P1 | Remember me | Selesai |
| P1 | Monitoring KRS BKKBN (publik) | Selesai |
| P2 | Multi-bahasa (i18n) | Belum |
| P3 | Mobile-first PWA | Belum |
