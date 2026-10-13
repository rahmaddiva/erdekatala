# Seeder Laporan Desa Bersumber dari Dukcapil

**Tanggal:** 2026-10-10
**Ruang lingkup:** `LaporanAgregatSeeder`, `DatabaseSeeder`, `AGENTS.md`, artefak `scratch/seed/`
**Status:** selesai & terverifikasi

---

## 1. Keputusan & akar masalah

### Keputusan
`laporan_agregat` yang di-seed kini **MIRROR per-desa** dari snapshot
`profil_dukcapil`, bukan lagi distribusi sintetis angka kecamatan SM2/2025.

### Akar / alasan
`LaporanAgregatSeeder` sebelumnya membangun laporan dari `getKecamatanData()`
(angka agregat kecamatan SM2/2025) → `mapKecamatanData()` → `distribute()`
(pembagian acak `mt_rand` per desa). Akibatnya angka laporan desa **tidak
mencerminkan data Dukcapil per desa** dan tidak reprodusibel (acak).

Padahal kebutuhan user: **data Dukcapil = data AWAL** bagi operator desa untuk
membandingkan data riil desa vs Dukcapil, lalu operator menambah data yang
kurang lewat form laporan. Jadi baris awal `laporan_agregat` harus sama persis
dengan snapshot Dukcapil desa tersebut.

### Desain
1. Ambil daftar desa + nama kecamatan (join seperti semula) dan peta user
   `admin_desa` per desa (seperti semula).
2. Baca `profil_dukcapil`; pilih **SATU baris terbaru per desa**
   (urut `tahun DESC, semester DESC, id_profil DESC`, ambil pertama per
   `id_desa`).
3. Bangun baris `laporan_agregat` = `{id_desa, id_user, bulan, tahun}` + SEMUA
   kolom statistik bersama **disalin apa adanya** (tanpa hitung ulang turunan,
   tanpa `dok_kia`/`dok_akta_cerai`).
4. Kolom bersama dihitung **DINAMIS** = irisan
   `getFieldNames(laporan_agregat)` ∩ `getFieldNames(profil_dukcapil)` minus
   himpunan `{id_laporan, id_profil, id_desa, id_user, semester, tahun,
   imported_at, imported_by, bulan}`. Dinamis agar tidak "drift" bila ada
   migration menambah kolom. **Prefix-safe** (`$db->prefixTable()` +
   `protectIdentifiers()`) sehingga test SQLite (prefix `db_`) tetap jalan.
5. `truncate()` lalu `insertBatch()`.
6. Bila `profil_dukcapil` KOSONG → cetak peringatan jelas dan **jatuh ke
   perilaku LAMA** (distribusi sintetis via `getKecamatanData()` /
   `mapKecamatanData()` / `distribute()`), supaya `php spark db:seed
   DatabaseSeeder` pada DB benar-benar kosong tetap jalan.
7. Cetak ringkasan: jumlah baris, periode (bulan/tahun), sumber.

Method `getKecamatanData()`, `distribute()`, `mapKecamatanData()` **tetap
`public` dan tidak diubah** (dipakai `ProfilDukcapilSeeder`).

---

## 2. ASUMSI PERIODE (menonjol — baca ini)

> **Laporan MIRROR periode snapshot Dukcapil:**
> **semester 1 → `bulan=6` (Juni)**, **semester 2 → `bulan=12` (Desember)**,
> **`tahun` mengikuti snapshot.**

Alasan & konsekuensi:

- Snapshot Dukcapil tidak punya konsep "bulan"; ia punya `semester` + `tahun`.
  `laporan_agregat` butuh `bulan`. Dipilih semester 1 = Juni (akhir semester
  pertama) dan semester 2 = Desember (akhir semester kedua) sebagai konvensi.
- **Alternatif yang tidak dipilih:** `bulan = semester * 6` identik hasilnya
  untuk 1/2; `bulan = 1` atau `bulan = 12` untuk semua tidak membedakan
  semester. Bila kebijakan periode berubah, ubah **satu baris** di
  `run()`: `$bulan = ((int) $snap['semester'] === 2) ? 12 : 6;`
- **Konsekuensi terverifikasi:** data resmi Dukcapil SM I/2026 → laporan
  tersimpan sebagai **135 baris `bulan=6, tahun=2026`**. Seed penuh dengan
  `ProfilDukcapilSeeder` (sintetis SM2/2025) → **135 baris `bulan=12,
  tahun=2025`**.

---

## 3. Perubahan file (diff)

### 3.1 `app/Database/Seeds/LaporanAgregatSeeder.php`

- `run()` diganti: alur mirror Dukcapil (butir 1–7 di atas) + ringkasan cetak.
- Method baru:
  - `getLatestDukcapilPerDesa($db): array` — 1 baris snapshot terbaru per desa
    (`SELECT * FROM profil_dukcapil ORDER BY tahun DESC, semester DESC,
    id_profil DESC`, ambil pertama per `id_desa`). Prefix-safe.
  - `sharedStatisticColumns($db): array` — irisan skema dinamis minus kolom meta.
- Method lama `run()` dipindah utuh menjadi **`runLegacyDistribusi($db,
  $allDesa, $userCache, $fallbackUser)`** (`private`) — dipakai hanya sebagai
  fallback. Logika distribusi TIDAK diubah.
- `getKecamatanData()`, `mapKecamatanData()`, `distribute()` **tidak diubah**
  (tetap `public`).
- Duplikasi blok cache user yang tadinya muncul di dua tempat kini hanya di
  `run()` (diteruskan sebagai parameter ke fallback).

Ringkasan output seeder:
```
LaporanAgregatSeeder: 135 baris disisipkan — MIRROR snapshot Dukcapil.
  Sumber  : profil_dukcapil (1 baris terbaru per desa: tahun DESC, semester DESC, id_profil DESC)
  Periode : 2026-06 (135 desa)
  Asumsi  : semester 1 -> bulan=6 (Juni), semester 2 -> bulan=12 (Desember)
  Kolom   : 81 kolom statistik bersama disalin apa adanya (tanpa turunan)
```

### 3.2 `app/Database/Seeds/DatabaseSeeder.php`

`$this->call('ProfilDukcapilSeeder')` dipindah **sebelum**
`$this->call('LaporanAgregatSeeder')`, agar pada seed fresh snapshot sudah
terisi saat laporan di-seed. Komentar urutan diperbarui (dan mencatat interaksi
sintetis → laporan).

```diff
-        // 5. Seed laporan agregat (bergantung pada m_rt & users)
+        // 5. Seed snapshot Dukcapil (baseline profil kependudukan). HARUS
+        //    SEBELUM LaporanAgregatSeeder: seeder itu kini MIRROR per-desa dari
+        //    profil_dukcapil, jadi snapshot harus sudah terisi saat laporan di-seed.
+        //    Catatan: seeder ini menghasilkan data SINTETIS (semester 2/2025) dan
+        //    men-truncate profil_dukcapil; untuk data resmi jalankan
+        //    `php spark dukcapil:import --commit` lalu `php spark db:seed LaporanAgregatSeeder`.
+        $this->call('ProfilDukcapilSeeder');
+
+        // 6. Seed laporan agregat (bergantung pada m_desa & users) — MIRROR
+        //    snapshot profil_dukcapil di atas (fallback distribusi sintetis bila kosong).
         $this->call('LaporanAgregatSeeder');
```

### 3.3 `AGENTS.md`

- Bagian **"Seeder & Data Dummy"**: urutan seeder diperbarui
  (`… → ProfilDukcapilSeeder → LaporanAgregatSeeder`), ditambah penjelasan
  mirror Dukcapil, asumsi periode, perilaku desa tanpa snapshot, fallback DB
  kosong, dan interaksi jujur: **seed penuh = laporan berbasis Dukcapil
  SINTETIS**; untuk data resmi `php spark dukcapil:import --commit` lalu
  `php spark db:seed LaporanAgregatSeeder`.
- **Catatan #20**: angka `?sumber=desa` kini **= Dukcapil (378.190)**, bukan lagi
  375.583 (angka 375.583 ditandai **historis**). Kesetaraan `?sumber=desa` ↔
  `?sumber=dukcapil` sekarang **wajar**. Label periode data dev = 6/2026,
  desa_lapor=135, desa_total=135.

### 3.4 Artefak `scratch/seed/`

- `laporan_agregat_before.sql`, `profil_dukcapil_before.sql` (backup `mysqldump`)
- `users_before.sql`, `m_rt_before.sql`, `m_dusun_before.sql`, `m_desa_before.sql`,
  `kecamatan_before.sql` (backup sebelum uji `DatabaseSeeder`)
- `_cmp_all.php` (pembanding kolom-per-kolom), `_sum_map.php`, `_sum_mapdesa.php`
- `mapkec_desa.json`, `mapkec_dukcapil.json`, `mapdesa_desa.json`,
  `mapdesa_dukcapil.json`, `landing_desa.html`, `landing_dukcapil.html`

---

## 4. Bukti verifikasi

Semua perintah dijalankan pada DB dev `erdekatala` (Laragon), setelah backup.

### 4.1 Backup (mysqldump)
`mysqldump … laporan_agregat` dan `… profil_dukcapil` → `scratch/seed/*_before.sql`
(141 & 142 baris SQL). Tambahan 5 tabel lain di-backup sebelum uji `DatabaseSeeder`.

### 4.2 `php -l`
```
No syntax errors detected in app/Database/Seeds/LaporanAgregatSeeder.php
No syntax errors detected in app/Database/Seeds/DatabaseSeeder.php
```

### 4.3 `php spark db:seed LaporanAgregatSeeder`
Output ringkas: `135 baris disisipkan — MIRROR snapshot Dukcapil`, periode
`2026-06 (135 desa)`, 81 kolom statistik bersama.

### 4.4 COUNT & rentang periode
```
SELECT COUNT(*) n, MIN(tahun), MAX(tahun), MIN(bulan), MAX(bulan) FROM laporan_agregat;
→ n=135, min_tahun=2026, max_tahun=2026, min_bulan=6, max_bulan=6   ✅ (harap 135/2026/6)
SELECT SUM(jiwa_l+jiwa_p) total_jiwa, SUM(kk_l+kk_p) total_kk FROM laporan_agregat;
→ total_jiwa=378190, total_kk=132030
```

### 4.5 Mirror penuh (SEMUA 81 kolom, 135 baris)
`php scratch/seed/_cmp_all.php` membandingkan seluruh kolom statistik bersama
per desa (`laporan_agregat` vs `profil_dukcapil`):
```
shared cols: 81
has dok_kia in laporan? no
rows compared: 135
cell diffs: 0            ✅ IDENTIK
```
Kolom kunci yang diminta (`jiwa_l, jiwa_p, kk_l, kk_p, u0_4_l, u0_4_p, kk_kawin,
jml_balita, jml_remaja, jml_lansia, jkn, non_jkn, kb_aktif, jml_pus,
pend_wajib_ktp, kk_punya_kartu_fisik`) termasuk di dalam 81 kolom tsb → 0 diff.

Contoh 3 desa (identik laporan ↔ dukcapil):
| id_desa | desa | jiwa_l | jiwa_p | kk_l | kk_p | jkn | non_jkn | kb_aktif | jml_pus | pend_wajib_ktp | kk_punya_kartu_fisik |
|---|---|---|---|---|---|---|---|---|---|---|---|
| 1 | Benua Lawas | 1153 | 1132 | 616 | 185 | 1828 | 457 | 520 | 867 | 1633 | 790 |
| 2 | Tabanio | 2039 | 2033 | 1116 | 368 | 3258 | 814 | 964 | 1607 | 3018 | 1474 |
| 3 | Kuala Tambangan | 1266 | 1220 | 674 | 180 | 1989 | 497 | 554 | 924 | 1746 | 846 |

### 4.6 Total lintas sumber HARUS SAMA (endpoint publik)
Dev server `php spark serve --port 8091`, lalu:
```
GET /api/v1/map/kecamatan?sumber=desa     → total_jiwa = 378190, total_kk = 132030  (11 fitur)
GET /api/v1/map/kecamatan?sumber=dukcapil → total_jiwa = 378190, total_kk = 132030  (11 fitur)
GET /api/v1/map/desa?sumber=desa          → total_jiwa = 378190  (135 fitur)
GET /api/v1/map/desa?sumber=dukcapil      → total_jiwa = 378190  (135 fitur)
```
`?sumber=desa` == `?sumber=dukcapil` == **378.190** ✅
Landing page `?sumber=desa` memuat angka `378.190` (4×) — konsisten.

### 4.7 Unit test
```
php vendor/phpunit/phpunit/phpunit tests/unit
Tests: 79, Assertions: 364, PHPUnit Warnings: 1 (No code coverage driver available)
```
Semua lulus (79/79). Peringatan hanya soal coverage driver, bukan kegagalan test.

### 4.8 `php spark db:seed DatabaseSeeder` tidak crash
Dijalankan setelah backup 7 tabel. Output:
```
Seeded: WilayahSeeder … UserSeeder
ProfilDukcapilSeeder: 135 snapshot Dukcapil (semester 2/2025) inserted.
Seeded: ProfilDukcapilSeeder
LaporanAgregatSeeder: 135 baris disisipkan — MIRROR snapshot Dukcapil.
  Periode : 2025-12 (135 desa)
Seeded: LaporanAgregatSeeder
Seeded: DatabaseSeeder            ✅ tidak crash
```
Urutan baru terbukti: `ProfilDukcapilSeeder` jalan sebelum `LaporanAgregatSeeder`
→ laporan `2025-12` = mirror snapshot sintetis SM2/2025. Sesudah uji, DB dev
dipulihkan dari backup dan di-seed ulang dengan `LaporanAgregatSeeder` (data
resmi SM I/2026 → 135 baris @ 6/2026).

### 4.9 Fallback DB kosong (uji terpisah)
`TRUNCATE profil_dukcapil` → `php spark db:seed LaporanAgregatSeeder`:
```
Peringatan: tabel profil_dukcapil KOSONG — laporan desa di-seed dari
  DISTRIBUSI SINTETIS LAMA (angka kecamatan SM2/2025), BUKAN data Dukcapil.
LaporanAgregatSeeder: 135 records inserted.
→ COUNT=135, bulan 12/2025 (legacy)
```
DB dipulihkan kembali. ✅

### 4.10 Pemilihan "baris terbaru per desa" (uji terpisah)
Disisipkan baris lama `(id_desa=1, semester=2, tahun=2025)` di samping baris
`(semester=1, tahun=2026)`. Seeder memilih baris **2026/S1** (bulan 6) →
membuktikan urutan `tahun DESC, semester DESC` benar. Baris uji dihapus,
DB dipulihkan. ✅

---

## 5. Batasan

1. **Asumsi periode** (semester 1 → bulan 6, semester 2 → bulan 12) adalah
   konvensi, bukan data resmi Dukcapil. Bila salah, ubah satu baris di
   `run()` (lihat §2).
2. **Seed penuh `DatabaseSeeder` = laporan berbasis Dukcapil SINTETIS**
   (`ProfilDukcapilSeeder` men-`truncate()` `profil_dukcapil` lalu mengisi
   snapshot sintetis SM2/2025). Untuk data resmi: `php spark dukcapil:import
   --commit` **lalu** `php spark db:seed LaporanAgregatSeeder`. Jangan
   re-seed di produksi (menghapus data resmi).
3. `dok_kia`/`dok_akta_cerai` **tidak** ikut ke `laporan_agregat` (kolom tidak
   ada di skema itu); kolom itu tetap hanya di `profil_dukcapil`.
4. Seeder tidak mengisi `created_at` pada `laporan_agregat` (dibiarkan NULL,
   sama seperti perilaku lama; kolom itu `null => true`).
5. Desa tanpa snapshot Dukcapil **dilewati** (tidak ditebak); bila ada, seeder
   mencetak `Catatan: N desa tanpa snapshot Dukcapil`.
6. Uji `DatabaseSeeder` dijalankan di DB dev **setelah backup penuh 7 tabel**;
   DB dipulihkan. Tidak ada perubahan skema/migration.
7. Tidak ada perubahan pada `DukcapilMapping`, `DukcapilImport`,
   `ProfilDukcapilModel`, `ProfilDukcapilSeeder`, controller, view, migration
   (sesuai batasan tugas).
