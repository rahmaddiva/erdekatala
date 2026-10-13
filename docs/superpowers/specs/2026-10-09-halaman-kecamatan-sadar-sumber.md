# Halaman Kecamatan Sadar Sumber (`?sumber=`)

Tanggal: 2026-10-09
File diubah: `app/Controllers/PublicController.php` (`kecamatan()` + helper privat), `app/Views/public/kecamatan.php`
Tidak diubah: `ApiController.php`, `Models/**`, `landingpage.php`, `theme_toggle.php`, `AGENTS.md`, migration, seeder. Tanpa commit.

## Tujuan

Halaman per-kecamatan `/{slug}` sebelumnya **murni laporan desa** (`laporan_agregat`) dan
membuang KIA & Akta Cerai secara buta. Sekarang halaman ini **sadar sumber**, konsisten dengan
landing page: parameter `?sumber=desa|dukcapil|campuran`, **default `dukcapil`**.

## Perubahan controller (`PublicController::kecamatan()`)

- `?sumber=` dibaca & divalidasi; nilai tak dikenal → `dukcapil` (sama seperti `landingpage()` & `ApiController::resolveSumber()`).
- Scope desa: 1 desa bila `?desa=` dipilih, seluruh desa kecamatan bila tidak.
- Agregasi profil mengikuti pola landing page:
  - `dukcapil` → `ProfilDukcapilModel::getAggregatedStats($desaScope)`;
  - `desa` → `LaporanAgregatModel::getAggregatedStats($idKecamatan, $idDesaFilter)` (perilaku lama);
  - `campuran` → jumlah keduanya, Dukcapil **hanya** untuk desa yang belum lapor (`array_diff($desaScope, $desaLaporIds)`) → tanpa double-count.
- **KIA & Akta Cerai (Kelompok A)**: `unset` buta dihapus. Nilai diambil dari `profil_dukcapil`
  (`dok_kia`, `dok_akta_cerai`) untuk scope kecamatan/desa; ditampilkan bila sumber menyertakan
  Dukcapil; **disembunyikan hanya saat `?sumber=desa`** (persis landing page). Provenance per
  indikator dikirim lewat `profilMeta['dokumen_sumber']`.
- `profilMeta` dikirim ke view: `sumber_filter`/`mode`, `semester`/`tahun`, `dokumen_sumber`,
  `kelompok_a_aktif`, `sumber_label_dkb` (dari `ProfilDukcapilModel::sumberLabelDkb()` — tidak
  di-hardcode ulang).
- `$desa_stats` kini sadar sumber: `getDesaStats($idKecamatan, $sumber)`:
  - `desa` → laporan desa (perilaku lama);
  - `dukcapil` → snapshot Dukcapil per desa;
  - `campuran` → overlay per desa: laporan desa bila ada, kalau tidak snapshot.
  Setiap baris membawa penanda `sumber` (dipakai view untuk label "laporan"/"snapshot" jujur).
- Helper privat baru: `desaIdsWithLaporan()`, `getDesaStatsLaporan()`, `getDesaStatsDukcapil()`.

## Perubahan view (`public/kecamatan.php`)

- Badge/pill sumber di hero (`Sumber: Dukcapil Kab. Tanah Laut` / `Laporan Desa` / `Dukcapil + Laporan Desa`)
  + periode `DKB Semester I 2026` saat mode Dukcapil, dan **form dropdown "Sumber data"** (GET, menjaga `?desa=`)
  untuk mengganti sumber — pola & gaya sama dengan landing page.
- Label resmi DKB `* Sumber data adalah Data Konsolidasi Bersih (DKB) Semester I Tahun 2026`
  dirender (dari `ProfilDukcapilModel::sumberLabelDkb()`) di badge hero, info-strip, dan catatan chart Dokumen.
- Tabel per-desa: badge sumber + kolom "Laporan"/"Snapshot" mengikuti sumber; tautan baris membawa `&sumber=`.
- Teks yang menyatakan "laporan desa" dibuat dinamis: stat-card PUS, info-strip Skop data, kategori
  tabel KB & PUS, empty-state. Chart/tabel Dokumen menampilkan KIA & Akta Cerai saat Dukcapil dengan
  chip provenance (`src-chip dukcapil`, tooltip = label DKB) + catatan `src-note`.
- Peta desa (`/api/v1/map/desa`) kini dipanggil dengan `&sumber=` aktif → angka peta konsisten dengan kartu;
  tautan detail desa pada popup peta juga membawa `&sumber=`.
- CSS: `.tag/src-dukcapil/src-desa`, `.group-filter`, `.src-official`, `.src-chip`, `.src-note` — diambil
  dari gaya landing page (bukan desain baru).

## Verifikasi (bukti)

`php -l app/Controllers/PublicController.php` → **No syntax errors detected**
`php -l app/Views/public/kecamatan.php` → **No syntax errors detected**

`php spark serve --host localhost --port 8092` → curl disimpan di `scratch/kecsrc/`:

| URL | Total Jiwa | Badge sumber | KIA | Akta Cerai | Label DKB |
|---|---|---|---|---|---|
| `/bajuin` (default) | **20.774** ✔ | Dukcapil Kab. Tanah Laut | **4.417** ✔ tampil | **407** ✔ tampil | ada ✔ |
| `/bajuin?sumber=desa` | **20.676** ✔ | Laporan Desa | disembunyikan ✔ | disembunyikan ✔ | tidak ada ✔ |
| `/bajuin?desa=bajuin` (default) | **2.180** ✔ | Dukcapil Kab. Tanah Laut | **464** tampil | **44** tampil | ada ✔ |
| `/bajuin?desa=bajuin&sumber=desa` | **2.299** ✔ | Laporan Desa | disembunyikan ✔ | disembunyikan ✔ | tidak ada ✔ |
| `/takisung` (default) | **34.944** ✔ | Dukcapil Kab. Tanah Laut | **6.410** tampil | **546** tampil | ada ✔ |

Ekstra:
- `/bajuin?sumber=campuran` → 20.676; tabel per-desa = overlay (semua desa sudah lapor → sama dengan
  laporan), **SUM tabel = 20.676 = kartu** ✔. KIA/Akta Cerai tetap tampil.
- Edge: `?sumber=bogus` → 20.774 (fallback Dukcapil) ✔; `?desa=tidakada` → 20.774 (diabaikan) ✔.
- Peta: `/api/v1/map/desa?id_kecamatan=10&sumber=dukcapil` → sum `total_jiwa` = **20.774**;
  `&sumber=desa` → **20.676** (peta ikut sumber) ✔.
- **0** `Fatal error` / `Warning` / `Undefined` / `Whoops` / `Parse error` di semua respons, dan
  tidak ada `CRITICAL`/`ERROR` di `writable/logs/log-2026-10-09.log` selama jendela pengujian.
- Regresi landing page: `/` → 378.190 (Dukcapil), `/?sumber=desa` → 375.583 — tetap utuh ✔.

`php vendor/phpunit/phpunit/phpunit tests/unit` → **OK, Tests: 79, Assertions: 364** (hanya peringatan
`No code coverage driver available`, bukan kegagalan).

Server `localhost:8092` **dimatikan** setelah selesai (port 8092 bebas).

## Catatan / keterbatasan

- Pada data saat ini **semua desa sudah lapor**, sehingga mode `campuran` (level kecamatan) menghasilkan
  angka sama dengan `desa`; perbedaan hanya muncul bila ada desa tanpa laporan. Logika sudah disiapkan untuk
  kasus itu (`array_diff($desaScope, $desaLaporIds)`).
- `$desa_stats` mode `dukcapil` memakai snapshot terbaru per desa (`SUM ... GROUP BY m_desa.id_desa`); untuk
  data saat ini (hanya S1/2026) hasilnya sama dengan satu baris per desa.
- Endpoint peta tetap milik worker lain (`ApiController.php`, tidak diubah); view hanya meneruskan `?sumber=`.
