# Audit & Perbaikan: Filter Wilayah (kecamatan/desa) + Filter Sumber Data

**Tanggal:** 2026-10-09
**Scope:** `PublicController::landingpage()` (`/`) + `app/Views/public/landingpage.php` + `app/Views/templates/theme_toggle.php`
**Metode:** render HTTP nyata via `php spark serve --host localhost --port 8088` untuk setiap kombinasi, angka diverifikasi silang dengan query `SELECT` langsung ke MySQL `erdekatala` (read-only). Artefak HTML tersimpan di `scratch/filterfix/` (sebelum) dan `scratch/filterfix/final2/` (sesudah).

**File yang diubah:** `app/Controllers/PublicController.php`, `app/Views/public/landingpage.php`. **Tidak diubah:** model, command, filter, migration, seeder, `theme_toggle.php` (diperiksa, tidak perlu diubah). **Tidak ada commit.**

---

## 0. Ringkasan eksekutif

Keluhan "filter kecamatan+desa, filter sumber data, dan kombinasinya tidak sesuai keinginan" **terkonfirmasi**. Empat defect nyata ditemukan dan diperbaiki:

1. **`?desa=` tanpa `?kecamatan=` diabaikan diam-diam** (angka tetap seluruh kabupaten). Diperbaiki: desa di-resolve global lalu kecamatan di-derive.
2. **`?kecamatan=` + `?desa=` yang tidak konsisten** → desa diam-diam diabaikan (angka seluruh kecamatan tanpa peringatan). Diperbaiki: peringatan eksplisit.
3. **Baris ringkasan KRS dilabeli "TANAH LAUT" padahal isinya 1 kecamatan** saat filter kecamatan aktif — label angka menyesatkan. Diperbaiki: label mengikuti scope.
4. **Dropdown "terpilih" tidak mencerminkan state URL** untuk slug berhuruf besar (`kecamatan=Bajuin`) dan untuk desa yang di-resolve global. Diperbaiki.

Plus: **propagasi filter yang tidak konsisten** antar bagian kini diberi keterangan jujur (peta & tabel BKKBN memang tidak bisa ikut filter wilayah/sumber), dan klaim note-strip "menyesuaikan **seluruh** grafik dan tabel" yang tidak benar telah dikoreksi.

**Tidak diubah (sesuai batasan):** semantik sumber utama (laporan desa vs Dukcapil) — default "campuran otomatis" yang faktanya 100% laporan desa dibiarkan apa adanya, hanya diberi keterangan. Angka Total Jiwa/KK tidak berubah oleh perbaikan ini (perbaikan hanya menyangkut propagasi filter, label, dan UX).

---

## 1. Matriks kombinasi — angka Total Jiwa (sebelum → sesudah)

Diambil dari `<div class="stat-card"><div class="label">Total Jiwa</div>` pada render `/`. Kolom "Skop" dari `section-head .crumb`. ✓ = sesuai keinginan (filter benar-benar diterapkan).

| # | Query | Skop SEBELUM | Jiwa SEBELUM | Skop SESUDAH | Jiwa SESUDAH | Status |
|---|---|---|---|---|---|---|
| 1 | *(kosong)* | Kabupaten | 375.583 | Kabupaten | 375.583 | ✓ |
| 2 | `?sumber=desa` | Kabupaten | 375.583 | Kabupaten | 375.583 | ✓ |
| 3 | `?sumber=dukcapil` | Kabupaten | 378.190 | Kabupaten | 378.190 | ✓ |
| 4 | `?kecamatan=bajuin` | Kec. Bajuin | 20.676 | Kec. Bajuin | 20.676 | ✓ |
| 5 | `?kecamatan=takisung` | Kec. Takisung | 34.742 | Kec. Takisung | 34.742 | ✓ |
| 6 | `?kecamatan=xxx-tidak-ada` | Kabupaten | 375.583 | Kabupaten | 375.583 | ✓ (+ peringatan) |
| 7 | `?kecamatan=bajuin&desa=bajuin` | Desa Bajuin | 2.299 | Desa Bajuin | 2.299 | ✓ |
| 8 | **`?desa=bajuin`** | **Kabupaten** | **375.583** | **Desa Bajuin** | **2.299** | **DEFECT-1 diperbaiki** |
| 9 | `?kecamatan=bajuin&desa=xxx-tidak-ada` | Kec. Bajuin | 20.676 | Kec. Bajuin | 20.676 | ✓ (+ peringatan) |
| 10 | `?kecamatan=bajuin&desa=takisung` | Kec. Bajuin | 20.676 | Kec. Bajuin | 20.676 | ✓ (+ peringatan) |
| 11 | `?kecamatan=bajuin&desa=bajuin&sumber=dukcapil` | Desa Bajuin | 2.180 | Desa Bajuin | 2.180 | ✓ |
| 12 | **`?desa=bajuin&sumber=dukcapil`** | **Kabupaten** | **378.190** | **Desa Bajuin** | **2.180** | **DEFECT-1 diperbaiki** |
| 13 | **`?kecamatan=xxx-tidak-ada&desa=bajuin`** | **Kabupaten** | **375.583** | **Desa Bajuin** | **2.299** | **DEFECT-1 diperbaiki** |
| 14 | `?desa=xxx-tidak-ada` | Kabupaten | 375.583 | Kabupaten | 375.583 | ✓ (+ peringatan) |
| 15 | `?kecamatan=Bajuin` (huruf besar) | Kec. Bajuin | 20.676 | Kec. Bajuin | 20.676 | ✓ (dropdown kini ter-select) |

KK mengikuti Jiwa pada semua baris (mis. #8: 130.908 → 864; #12: 132.030 → 810). Cakupan laporan mengikuti: #8 `135/135` → `1/1`.

---

## 2. Propagasi filter per bagian halaman

Diperiksa dari `PublicController::landingpage()` dan view. **Bukti: apakah bagian menerima argumen `$id_kecamatan_filter`/`$id_desa_filter`.**

| Bagian | Mengikuti filter wilayah? | Mengikuti `?sumber=`? | Bukti |
|---|---|---|---|
| Ringkasan Data | **Ya** | Ya | `$stats` dari `getAggregatedStats($id_kecamatan_filter, $id_desa_filter)` |
| Indikator Turunan | **Ya** | Ya | turunan `$chart` (piramida/KK) yang sama |
| Profil Kependudukan (4 tab) | **Ya** | Ya | `$chart` sama; KIA/Akta Cerai selalu Dukcapil (by design) |
| **Sebaran Spasial (peta)** | **Tidak** (hanya sorot/zoom) | **Tidak** | `buildMapKecamatan()` tanpa argumen → `getMapKecamatan()` (semua 11 kec, laporan desa) |
| Monitoring BKKBN — KRS | **Ya (kecamatan saja)** | Tidak | `buildKrsTree(2026, $id_kecamatan_filter)` memfilter baris ke 1 kecamatan |
| Monitoring BKKBN — YanKB (Tabel1/Tabel21) | **Tidak** | Tidak | `fetchYankbReportInternal()` tanpa argumen wilayah |
| Monitoring BKKBN — Dallap & PUS/KB Aktif | **Tidak** | Tidak | `fetchDallapMatrixInternal(2026, $yankbBulan)` tanpa argumen wilayah |
| Jelajahi Kecamatan | Tidak (navigasi) | Tidak | kartu link ke `/{slug}` (`PublicController::kecamatan()`, halaman lain) |

**Bukti inkonsistensi antar-bagian pada satu halaman** (`?kecamatan=bajuin`): Ringkasan Total Jiwa = **20.676** (Bajuin) sedangkan peta tetap menampilkan 11 kecamatan dengan total poligon 375.583, dan tab YanKB/Dallap tetap menampilkan 11 baris kecamatan. Sebelum perbaikan, tidak ada satu pun keterangan yang menjelaskan perbedaan ini di dekat bagian tersebut.

`desa_stats` (`getDesaStats($id_kecamatan_filter)`) **tidak dirender di `landingpage.php`** (hanya dipakai di `public/kecamatan.php`), jadi tidak ada tabel desa di landing page — lihat §5 pertanyaan.

---

## 3. Daftar defect

| # | Severity | Defect | Bukti (sebelum) | Perbaikan |
|---|---|---|---|---|
| **D1** | **Tinggi** | `?desa=<slug>` tanpa `?kecamatan=` diabaikan diam-diam → angka seluruh kabupaten; desa tidak ter-select | `PublicController.php:57-59` lama: `if ($slug_desa && $id_kecamatan_filter)`. Render `?desa=bajuin` → 375.583 (Kabupaten), selkec=[], seld=[] | `PublicController.php:59-99`: resolve desa global + derive kecamatan |
| **D2** | **Tinggi** | Baris ringkasan KRS dilabeli "TANAH LAUT" padahal berisi 1 kecamatan (filter aktif) — label angka menyesatkan | `landingpage.php:2228` hardcode `TANAH LAUT`; `PublicController.php:415` `sumKrsRows($kecamatan, 'TANAH LAUT')`. Render `?kecamatan=bajuin` → baris "TANAH LAUT" berisi 9/9/9 (Bajuin), bukan 135 | `PublicController.php:419` label `TANAH LAUT — Bajuin`; `landingpage.php:2235` pakai `$krsKab['namaDaerah']` |
| **D3** | **Sedang** | `?kecamatan=` + `?desa=` yang tidak konsisten (desa tak ada / beda kecamatan) → desa diabaikan tanpa peringatan jelas | Render `?kecamatan=bajuin&desa=takisung` → 20.676 (seluruh kecamatan). Peringatan lama hanya menyebut desa "tidak ditemukan di Kecamatan Bajuin" | `landingpage.php:1462-1481`: pesan peringatan eksplisit per kasus |
| **D4** | **Sedang** | Dropdown "terpilih" tidak mencerminkan URL: (a) slug huruf besar (`kecamatan=Bajuin`) tak ter-select; (b) desa hasil resolve global tak ter-select | Render `?kecamatan=Bajuin` → selkec=[]; `?desa=bajuin` → seld=[] | `landingpage.php:25-34, 1423-1426, 1439`: matching case-insensitive + fallback dari hasil resolusi |
| **D5** | **Sedang** | Klaim note-strip "Filter wilayah di atas menyesuaikan **seluruh** grafik dan tabel di bawah" tidak benar (peta & BKKBN tidak ikut) | `landingpage.php` (lama) teks note-strip | `landingpage.php:1557-1558`: daftar eksplisit bagian yang ikut, sisanya disebut sumber pihak ketiga |
| **D6** | **Rendah** | Peta tanpa keterangan cakupan → user mengira ikut filter | `landingpage.php` section 2 tanpa catatan | `landingpage.php:2030-2038`: note-strip "Peta menampilkan seluruh N kecamatan…" |
| **D7** | **Rendah** | Tab BKKBN tanpa keterangan cakupan saat filter wilayah aktif (KRS ikut, YanKB/Dallap tidak) | `landingpage.php` group-body tanpa catatan | `landingpage.php:2087-2093`: note-strip cakupan BKKBN |
| **D8** | **Rendah** | Mode `?sumber=dukcapil` masih menampilkan "Periode laporan terbaru: Agustus 2026" (data laporan) padahal angka dari Dukcapil S1/2026 | `landingpage.php` (lama) blok `$latestPeriod` tanpa syarat | `landingpage.php:1559`: hanya tampil bila `$profilMode !== 'dukcapil'` |
| **D9** | **Rendah** | Ganti filter wilayah lewat form hero menghapus `?sumber=` & `?bulan=` (hidden input tidak ada) | form hero tanpa hidden input | `landingpage.php:1447-1454`: hidden `sumber` & `bulan` |

**Catatan penting (BUKAN defect, keputusan desain user):** mode default "campuran otomatis" faktanya **100% laporan desa** (135/135 desa sudah lapor) — Total Jiwa 375.583 = persis `?sumber=desa`, Dukcapil menyumbang 0 kecuali KIA & Akta Cerai. Ini sudah didokumentasikan di `docs/superpowers/specs/2026-10-09-analisis-filter-sumber-data.md` dan **tidak diubah** sesuai instruksi.

---

## 4. Yang diperbaiki — detail

### 4.1 `app/Controllers/PublicController.php`

**Resolusi slug desa (D1)** — `landingpage()` baris 59-99:
- `?kecamatan=` valid → desa wajib ada di kecamatan itu (kalau tidak → `desa_unresolved`), sama seperti sebelumnya.
- `?kecamatan=` kosong **atau** gagal di-resolve → desa di-resolve **global** by slug; bila ketemu, `$id_kecamatan_filter` di-derive dari `$desa['id_kecamatan']` dan `$filter_kec_nama` diisi. Efek: `?desa=bajuin` kini benar-benar 1 desa (2.299), dan `?kecamatan=xxx-tidak-ada&desa=bajuin` juga 1 desa.

**Label ringkasan KRS (D2)** — `buildKrsTree()` baris ~415-419:
- `sumKrsRows($kecamatan, 'TANAH LAUT')` → `sumKrsRows($kecamatan, $filterName ? 'TANAH LAUT — ' . $filterName : 'TANAH LAUT')`. Saat filter kecamatan aktif, baris ringkasan jujur menyebut nama kecamatan (angkanya memang hanya kecamatan itu).

### 4.2 `app/Views/public/landingpage.php`

- **Normalisasi slug (D4):** `$filterKecSlugLower`, `$filterDesaSlugLower`, `$effectiveKecSlug` (baris 25-34).
- **Dropdown ter-select (D4):** `$kecSelected` cocok bila slug (case-insensitive) **atau** nama hasil resolusi cocok (baris 1423-1426); desa pakai `$filterDesaSlugLower` (baris 1439); `disabled` desa kini berdasar `$idKecFilter` bukan `$filter_kec_slug` (baris 1434).
- **Peringatan filter (D3):** pesan berbeda untuk (a) kecamatan tak dikenal tanpa desa → seluruh kabupaten; (b) kecamatan tak dikenal + desa valid → desa tetap diterapkan; (c) desa tak ada di kecamatan valid → tingkat kecamatan; (d) desa tak ada tanpa kecamatan → seluruh kabupaten (baris 1462-1481).
- **Note-strip jujur (D5):** klaim diganti daftar bagian yang benar-benar ikut filter (baris 1557-1558).
- **Periode laporan disembunyikan di mode Dukcapil (D8)** (baris 1559).
- **Peta: keterangan cakupan (D6)** (baris 2030-2038).
- **BKKBN: keterangan cakupan (D7)** (baris 2087-2093).
- **Form hero: hidden `sumber` & `bulan` (D9)** (baris 1447-1454).
- **Baris ringkasan KRS pakai `$krsKab['namaDaerah']` (D2)** (baris 2235).
- **Peta pakai `$effectiveKecSlug`** agar sorot poligon konsisten saat desa di-resolve global (baris 3295).

---

## 5. Verifikasi

- **Lint:** `php -l` OK untuk `PublicController.php` dan `landingpage.php`.
- **Unit test:** `php vendor/phpunit/phpunit/phpunit tests/unit` → **79 tests, 364 assertions, OK** (satu warning "No code coverage driver", sudah ada sebelum perubahan).
- **Render 16 kombinasi** (lihat §1) + route lain (`/`, `/bajuin`, `/bajuin?desa=bajuin`, `/takisung`, `/getDallapDesa/bajuin`, `/api/v1/map/kecamatan`, `/api/v1/map/desa?id_kecamatan=10`, `/login`): **tidak ada** `Fatal error` / `Warning` / `Undefined` / `Whoops` / `Unknown column`; `writable/logs` bersih dari ERROR/CRITICAL setelah perubahan.
- **Server dimatikan** setelah verifikasi.

---

## 6. Ambigu — JANGAN ditebak, butuh keputusan user

1. **Peta: apakah seharusnya ikut filter wilayah?** Saat ini peta selalu menampilkan 11 kecamatan (karena endpoint `/api/v1/map/kecamatan` mengagregasi `laporan_agregat` tanpa argumen wilayah). Saya hanya menambah keterangan jujur. Bila user ingin peta ikut filter, perlu perubahan di `ApiController`/`LaporanAgregatModel` — **di luar batas file tugas ini**.
2. **Tab YanKB & Dallap: apakah seharusnya ikut filter kecamatan?** Datanya dari SIGA (per-kecamatan) dan bisa difilter di sisi klien, tetapi itu menambah kompleksitas; saya tidak mengubahnya, hanya memberi keterangan.
3. **Default "campuran otomatis" = 100% laporan desa.** Apakah user ingin default benar-benar menampilkan campuran (mis. memaksa Dukcapil untuk indikator tertentu)? **Tidak saya tebak** — semantik sumber adalah keputusan user.
4. **KIA & Akta Cerai selalu dari Dukcapil** meski mode laporan. Sudah ada penanda `$dokumenSumber`, tetapi apakah user ingin indikator ini disembunyikan total di mode laporan (bukan hanya diberi catatan)? Keputusan user.
5. **`desa_stats` tidak dirender di landing page.** Bila memang diinginkan tabel per-desa saat 1 kecamatan dipilih (seperti di halaman `/{slug}`), itu penambahan fitur baru — perlu konfirmasi.
6. **`?desa=` beda kecamatan (`?kecamatan=bajuin&desa=takisung`).** Saya pilih: desa diabaikan + peringatan, scope tetap kecamatan Bajuin. Alternatif: resolusi desa menang atas kecamatan. Butuh keputusan user.

---

## 7. Artefak

| Lokasi | Isi |
|---|---|
| `scratch/filterfix/*.html` | Render SEBELUM perbaikan (16 kombinasi) |
| `scratch/filterfix/final2/*.html` + `summary.json` | Render SESUDAH perbaikan |
| `scratch/filterfix/smoke/` | Smoke test route lain |
