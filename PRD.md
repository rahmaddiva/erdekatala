# PRD — Sikada Tala
## Sistem Informasi Kependudukan Agregat Kabupaten Tanah Laut

**Versi:** 1.0  
**Tanggal:** Juni 2026  
**Pengelola:** DP3AP2KB Kabupaten Tanah Laut, Kalimantan Selatan  
**Status:** Active Development

---

## 1. Latar Belakang

DP3AP2KB Kabupaten Tanah Laut membutuhkan sistem terpusat untuk mengumpulkan, memantau, dan melaporkan data agregat kependudukan tingkat RT secara berkala. Sebelumnya proses pelaporan dilakukan manual melalui spreadsheet atau kertas, yang menyebabkan keterlambatan data, inkonsistensi, dan kesulitan dalam rekap lintas wilayah.

**Sikada Tala** (singkatan dari RT-RW-Desa-Kecamatan-Laporan-Agregat) hadir sebagai solusi digital untuk menjembatani kebutuhan tersebut.

---

## 2. Tujuan Produk

- Menyediakan platform pelaporan data kependudukan per RT yang terstruktur dan konsisten.
- Memungkinkan monitoring capaian pelaporan secara real-time di level dinas, kecamatan, dan desa.
- Menghasilkan visualisasi statistik kependudukan yang dapat digunakan untuk pengambilan keputusan.
- Membuka akses data agregat kepada pihak ketiga melalui Public API.

---

## 3. Pengguna & Peran

| Role | Cakupan Data | Hak Akses |
|---|---|---|
| `admin_dinas` | Seluruh kabupaten | CRUD desa, CRUD user, lihat semua laporan, kelola API key |
| `admin_kecamatan` | Kecamatan yang bersangkutan | Lihat laporan dan progres desa di kecamatannya |
| `admin_desa` | Desa yang bersangkutan | Input laporan per RT, lihat laporan desanya |
| Publik (API) | Baca saja via API key | Akses data agregat via REST API dengan rate limit |

---

## 4. Ruang Lingkup Fitur

### 4.1 Autentikasi & Manajemen Profil

- Login dengan username dan password (bcrypt).
- Session berbasis file CI4.
- Semua halaman kecuali landing page, login, dan endpoint API publik diproteksi filter `LoggedIn`.
- Pengguna dapat mengubah nama lengkap dan password melalui halaman profil.

### 4.2 Master Wilayah

Hierarki wilayah: **Kecamatan > Desa > Dusun > RT**

- `admin_dinas` dapat melakukan CRUD desa, dusun, dan RT.
- Data kecamatan di-seed dari data resmi 11 kecamatan Kabupaten Tanah Laut.
- Setiap RT adalah satuan terkecil yang menjadi unit pelaporan.

### 4.3 Manajemen User

- Hanya `admin_dinas` yang dapat membuat, mengubah, dan menghapus akun user.
- Saat membuat user baru, role dan wilayah (kecamatan/desa) ditentukan sesuai cakupan tugasnya.

### 4.4 Input Laporan Agregat

Setiap laporan mewakili satu RT untuk satu periode bulan/tahun. Data yang diinput mencakup:

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
- Satu RT tidak dapat memiliki dua laporan untuk periode yang sama (validasi duplikat).
- Fitur salin data dari bulan sebelumnya tersedia via AJAX untuk mempercepat pengisian.

### 4.5 Monitoring Laporan

Tampilan berbeda sesuai role:

- **admin_desa:** accordion per tahun/bulan, menampilkan status setiap RT (sudah/belum lapor).
- **admin_kecamatan:** card grid per desa dengan progress bar capaian, modal detail RT via AJAX.
- **admin_dinas:** DataTable server-side dengan filter kecamatan dan desa.

### 4.6 Dashboard Statistik

- Filter berjenjang: Kecamatan > Desa > Dusun > RT (chained dropdown via AJAX).
- Visualisasi menggunakan ZingChart v2.9.16, terdiri dari 7 chart:
  1. Piramida penduduk
  2. Distribusi pendidikan KK
  3. Status perkawinan
  4. Jenis pekerjaan KK
  5. Komposisi gender
  6. Kepemilikan dokumen adminduk
  7. Cakupan JKN/BPJS
- Tabel ringkasan agregat per wilayah yang mengikuti granularitas filter aktif.

### 4.7 Export Laporan

- Format tersedia: **Excel (.xlsx)** via phpspreadsheet dan **PDF (A4 landscape)** via dompdf.
- Dapat difilter per kecamatan, desa, bulan, dan tahun sebelum ekspor.

### 4.8 Landing Page Publik

- Halaman tanpa autentikasi yang menampilkan informasi umum aplikasi.
- Menyediakan dropdown kecamatan dan desa sebagai preview wilayah.

### 4.9 Public REST API

API read-only berbasis API key untuk integrasi sistem eksternal.

**Spesifikasi:**
- Base URL: `/api/v1/`
- Autentikasi: `Authorization: Bearer <api_key>` atau query param `?api_key=<key>`
- Rate limit: 1.000 request/hari per key (reset tengah malam)
- Format respons: JSON

**Endpoint:**

| Method | Path | Deskripsi |
|---|---|---|
| GET | `/api/v1/info` | Info API dan daftar endpoint |
| GET | `/api/v1/kecamatan` | Daftar semua kecamatan |
| GET | `/api/v1/desa` | Daftar desa, opsional filter `?id_kecamatan=` |
| GET | `/api/v1/laporan` | Data laporan per RT dengan paginasi dan filter lengkap |
| GET | `/api/v1/laporan/rekap/kecamatan` | Rekapitulasi agregat per kecamatan |
| GET | `/api/v1/laporan/rekap/desa` | Rekapitulasi per desa, wajib `?id_kecamatan=` |

**Pendaftaran API Key:**
- Publik dapat mendaftar melalui formulir di `/api/register` tanpa autentikasi.
- API key di-generate sebagai 64 karakter hex (`bin2hex(random_bytes(32))`).
- `admin_dinas` dapat merevoke, mengaktifkan kembali, atau menghapus key melalui panel `/apikeys`.

**Dokumentasi API:**
- Swagger UI tersedia di `/api/docs` tanpa autentikasi.

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
kecamatan
  └── m_desa
        └── m_dusun
              └── m_rt
                    └── laporan_agregat (~80 kolom statistik)
```

---

## 6. Batasan & Asumsi

- Aplikasi ditujukan untuk penggunaan internal pemerintah daerah dan tidak memerlukan registrasi mandiri.
- Data kecamatan bersifat tetap (tidak di-CRUD), hanya desa ke bawah yang dapat dikelola.
- Tidak ada notifikasi otomatis (email/SMS) untuk pengingat pelaporan pada versi ini.
- Aplikasi belum memiliki fitur audit log per aksi user.
- API publik hanya menyajikan data agregat, tidak ada data individual warga.

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
| P0 | Input laporan agregat per RT | Selesai |
| P0 | Monitoring laporan per role | Selesai |
| P0 | Dashboard statistik + ZingChart | Selesai |
| P0 | Export Excel dan PDF | Selesai |
| P1 | Master wilayah CRUD | Selesai |
| P1 | Manajemen user | Selesai |
| P1 | Public REST API + API key management | Selesai |
| P1 | Landing page publik | Selesai |
| P2 | Notifikasi pengingat pelaporan | Belum |
| P2 | Audit log aktivitas user | Belum |
| P2 | Multi-bahasa (i18n) | Belum |
| P3 | Mobile-first PWA | Belum |
