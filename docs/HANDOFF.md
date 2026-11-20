# HANDOFF SESI — Erdekatala (2026-11-20)

Dokumen ringkasan kerja. Tujuannya: sesi baru bisa langsung lanjut tanpa membaca
ulang seluruh riwayat. **Dokumen ini tidak berisi kredensial, alamat server, atau
detail infrastruktur.** Detail operasional disimpan terpisah oleh operator.

---

## 1. Tujuan proyek (singkat)

**Erdekatala** = sistem informasi kependudukan **agregat** tingkat desa/kecamatan
untuk Kabupaten Tanah Laut (Kalimantan Selatan).

- Framework: **CodeIgniter 4.7.6**, PHP **8.2+** (runtime dev: 8.3)
- Database: **MySQL**
- Frontend: **AdminLTE**, grafik **ZingChart**, peta **Leaflet** (+ basemap CARTO)
- Sumber data: snapshot Dukcapil (`profil_dukcapil`) dan laporan desa (`laporan_agregat`)
- Konteks detail: `AGENTS.md` (sumber konteks lengkap)

---

## 2. Status git

- Branch: **`main`**
- Commit terakhir (2026-11-20): RBAC dinamis, hardening keamanan, dan upgrade dependency.
- Perubahan sudah di-push ke remote.
- File yang sengaja tidak di-commit: `DEPLOY.md`, paket zip deploy, dan artefak
  lokal lain yang berisi/menyertakan konfigurasi lingkungan.

---

## 3. Pekerjaan yang sudah selesai

### 3.1 Fix peta + spacing halaman kecamatan
- Blok `<script>` peta yang tidak ditutup menyebabkan `SyntaxError`; peta tidak render.
- Spacing section "Profil Kecamatan" dirapikan.
- Laporan: `docs/superpowers/specs/2026-10-10-fix-peta-dan-spacing-kecamatan.md`.

### 3.2 Seeder laporan MIRROR Dukcapil
- `LaporanAgregatSeeder` memakai snapshot `profil_dukcapil` per desa, bukan distribusi
  sintetis.
- `ProfilDukcapilSeeder` dijalankan sebelum `LaporanAgregatSeeder`.
- Asumsi periode: semester 1 → bulan 6; semester 2 → bulan 12.
- Laporan: `docs/superpowers/specs/2026-10-10-seeder-laporan-dari-dukcapil.md`.

### 3.3 Keadaan data DB development
- `laporan_agregat`: **135 baris @ 6/2026**.
- Total **378.190 jiwa** / **132.030 KK**, identik dengan `profil_dukcapil`.
- `?sumber=desa` dan `?sumber=dukcapil` sama-sama 378.190 — wajar karena laporan
  di-mirror dari snapshot Dukcapil.

### 3.4 Koreksi fakta `CI_ENVIRONMENT`
- Tanpa `.env`, CodeIgniter memakai `production` (bukan `development`).
- Konsekuensinya debug mati dan `DBDebug` nonaktif di produksi.
- `.env` tetap diperlukan untuk kredensial DB dan konfigurasi layanan eksternal.

### 3.5 RBAC dinamis (2026-11-20)
- Tabel `roles` (`nama_role`, `label`, `scope`, `permissions` JSON, `is_system`) dan
  `users.role` VARCHAR(50).
- Katalog permission: `App\Libraries\Permissions::GROUPS`; helper `can()` / `role_scope()`.
- Halaman `/roles` hanya untuk role sistem `admin_dinas`.
- Batas kelola user untuk aktor non-`admin_dinas`: hanya akun scope desa; aktor
  kecamatan dibatasi kecamatannya. Role yang boleh diberikan juga hanya scope desa.
- Produksi wajib `php spark migrate` — migration
  `2026-11-20-000001_CreateRolesAndPermissions`.
- Permission default: `admin_dinas` 16/16; `admin_kecamatan` 6; `admin_desa` 7.

### 3.6 Security hardening (2026-11-20)
- Root `.htaccess` memblokir akses langsung ke file/folder internal (`.env`, `.git`,
  `app/`, `vendor/`, `writable/`, `docs/`, `scratch/`, `tests/`, `build/`, file
  dokumen/data/arsip).
- **Docroot produksi tetap wajib `public/`**; `.htaccess` hanya lapisan kedua.
- SQL injection filter `bulan`/`tahun` halaman `/laporan` (scope kecamatan) diperbaiki
  dengan cast `int` + validasi rentang.
- Role kustom tidak bisa mengelola akun/role sistem. `RoleController` hanya untuk
  `admin_dinas`.
- Logout hanya `POST` + CSRF. Cookie `remember_token` terpasang dengan benar dan
  `Secure` hanya aktif saat HTTPS.
- Iframe HTML YanKB dari BKKBN diberi `sandbox`; nama user di navbar/sidebar di-escape.
- Upload foto memakai `App\Libraries\SafeImageUpload` (finfo + `getimagesize`, nama
  acak, ekstensi dari MIME). Folder upload menolak eksekusi PHP via `.htaccess`.
  Ditemukan dan diperbaiki: file `.php` sebelumnya bisa tersimpan di folder upload
  publik (critical).
- Dependency: CodeIgniter **4.7.6**, dompdf **3.1.6**, PhpSpreadsheet **5.11.0**;
  `composer audit` bersih. Config 4.7 yang sempat hilang sudah disinkronkan
  (`Format::$jsonEncodeDepth`, `Hostnames`, `Paths::$envDirectory`, dll.).

### 3.7 Catatan keamanan operasional
- Refresh cache BKKBN secara berkala dengan `php spark bkkbn:warmup`; gateway eksternal
  bisa lambat/tidak stabil.
- Jika file environment pernah terekspos di luar lingkungan lokal, rotasi kredensial
  DB dan API key layanan eksternal.
- Sisa hardening opsional: CSP/HSTS/Permissions-Policy, penghapusan dukungan
  `?api_key=`, dan MFA untuk `admin_dinas`.

---

## 4. Aturan penting yang berlaku

1. **Agregasi laporan = "1 baris terbaru per desa"** via
   `LaporanAgregatModel::latestPerDesaSubquery()`. Jangan menulis `SUM(...)`/`COUNT(...)`
   langsung atas `laporan_agregat` tanpa subquery itu.
2. **Default sumber data aplikasi = `dukcapil`**. Parameter
   `?sumber=desa|dukcapil|campuran` berlaku di landing page, halaman kecamatan, dan
   endpoint peta.
3. **JANGAN jalankan `php spark db:seed DatabaseSeeder` di produksi** — seeder
   men-`truncate` tabel dan akan menghapus data resmi.
4. **PHPUnit**: `vendor/bin/phpunit` rusak di Laragon ini. Pakai
   `php vendor/phpunit/phpunit/phpunit tests/unit`.
5. Jangan menempelkan kredensial/private key ke dokumen, chat, atau log.
6. SQL mentah di model/perintah wajib memakai prefix tabel
   (`$this->db->prefixTable()` / `protectIdentifiers`).

---

## 5. Langkah berikutnya

- [ ] Deploy dengan docroot `public/` dan HTTPS.
- [ ] Set `CI_ENVIRONMENT=production`, isi kredensial environment, dan pastikan debug mati.
- [ ] Jalankan `php spark migrate`.
- [ ] Jangan jalankan seeder produksi; gunakan data resmi/backup yang sudah diverifikasi.
- [ ] Uji restore backup dan siapkan monitoring/log rotation.
- [ ] Jadwalkan `php spark bkkbn:warmup` (mis. tiap 15–20 menit).
- [ ] UAT semua role + browser/mobile sebelum membuka akses publik.
- [ ] Ganti password akun awal dan terapkan kebijakan password yang lebih kuat.

---

## 6. Cara melanjutkan

1. `AGENTS.md` — konteks lengkap proyek.
2. `docs/HANDOFF.md` — dokumen ini.
3. `docs/superpowers/specs/` — laporan teknis per topik:
   - `2026-10-10-fix-peta-dan-spacing-kecamatan.md`
   - `2026-10-10-seeder-laporan-dari-dukcapil.md`

Panduan deploy tersedia terpisah di lingkungan operator dan sengaja tidak disimpan
di repo publik.

---

## 7. Tanggal

Dokumen dibuat: **2026-10-13**.
Diperbarui: **2026-11-20** — RBAC, hardening keamanan, dan upgrade dependency.
