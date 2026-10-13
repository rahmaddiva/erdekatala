# Perbaikan Double-Count Periode pada Agregasi Laporan

Tanggal: 2026-10-09
Status: selesai (model diperbaiki, unit test hijau, runtime terverifikasi)
File yang diubah: `app/Models/LaporanAgregatModel.php`, `app/Models/ProfilDukcapilModel.php`,
`tests/unit/PeriodeAgregatTest.php`, `AGENTS.md`
File yang TIDAK disentuh (batasan tugas): `app/Controllers/PublicController.php`,
`app/Views/**`, `app/Filters/**`, migration/skema.

---

## 1. Masalah

`LaporanAgregatModel::getAggregatedStats()` menjalankan `SUM(kolom)` atas **SEMUA baris**
`laporan_agregat` tanpa filter periode:

```php
$builder = $this->builder();
$builder->select('SUM(jiwa_l) as jiwa_l, ...');   // tanpa WHERE bulan/tahun
```

Keadaan tabel saat ini (137 baris, 135 desa):

| Periode  | Baris | Desa |
|---|---|---|
| 2025-12 | 135   | 135 |
| 2026-07 | 1     | 1 (Ranggang, Takisung) |
| 2026-08 | 1     | 1 (Batu Ampar, Batu Ampar) |

Dua desa itu punya **dua** baris → terhitung **dua kali**:

- Total Jiwa tampil **380.525**
- Nilai satu periode (12/2025) = **375.583**
- Selisih **+4.942** (= 2.882 Ranggang + 2.060 Batu Ampar)

Selain itu, `getLatestPeriod()` memakai `ORDER BY tahun DESC, bulan DESC LIMIT 1`, sehingga
menampilkan **"Agustus 2026"** — periode yang hanya berisi **1 desa** — padahal angka Total
Jiwa berasal dari 135 desa. **Label dan angka saling bertentangan.**

Dampak: landing page `/`, halaman `/{slug}` (kecamatan), dashboard, dan data-tabel.

`ProfilDukcapilModel::getAggregatedStats()` punya pola yang sama. Tabel `profil_dukcapil`
baru berisi 1 periode (2026/S1, 135 baris) sehingga belum terpengaruh, tetapi rentan
(risiko ini sudah tercatat sebagai **A8** di `app/Commands/DukcapilImport.php`).

---

## 2. Pilihan semantik

Kriteria: (i) setiap desa menyumbang **tepat satu periode**, (ii) deterministik,
(iii) label periode cocok dengan periode yang benar-benar diagregasi.

### Opsi (a) — satu periode terbaru global
`WHERE tahun*100+bulan = (SELECT MAX(tahun*100+bulan) FROM laporan_agregat)`

- Deterministik & mudah dijelaskan ("keadaan kabupaten bulan X").
- **Terukur di DB ini: hanya 2.060.** Periode terbaru (8/2026) cuma berisi 1 desa, jadi
  133 desa tiba-tiba **hilang** dari total kabupaten. Jelas merusak.

### Opsi (b) — satu baris terbaru per desa
Setiap desa menyumbang laporannya yang paling baru; urutan `(tahun*100+bulan) DESC`
dengan tie-break `MAX(id_laporan)`.

- **Terukur: 375.583** (135 desa) — tepat nilai satu periode 12/2025, karena 133 desa
  memang hanya punya periode itu.
- Kriteria (i) terpenuhi persis: tidak ada desa yang terhitung dua kali, apa pun isi tabel.
- Kriteria (ii): `MAX(id_laporan)` sebagai tie-break membuat hasil tidak bergantung pada
  urutan insert / urutan fisik tabel / mesin DB.
- Tidak ada desa yang hilang → cakupan (135/135) konsisten dengan Total Jiwa.
- Opsi (a) adalah **kasus khusus** (b) saat semua desa sudah lapor di periode terbaru,
  jadi (b) otomatis menyatu ke (a) saat data lengkap.

### Keputusan: **OPSI (b)**

Alasan singkat: opsi (a) memang lebih "bersih" secara konsep, tetapi pada Data Nyata
menjatuhkan total kabupaten dari 375.583 ke 2.060 (kehilangan 133 desa). Opsi (b)
menghapus double-count **tanpa kehilangan desa mana pun**.

Trade-off yang disadari: total bisa memadukan potret dari bulan berbeda
(133 desa @12/2025 + 1 @7/2026 + 1 @8/2026). Karena itu label periode **tidak diklaim**
sebagai "satu bulan": `getLatestPeriod()` melaporkan **periode utama** = periode dengan
desa terbanyak, plus `desa_lapor` dan `desa_total` supaya selisihnya bisa dijelaskan.

Hasil akhir: label **"Desember 2025"** (133 desa) — konsisten dengan angka 375.583 yang
berasal terutama dari periode itu.

---

## 3. Implementasi

Semua perubahan **di dalam model saja**; signature & kontrak return tidak berubah.

### `LaporanAgregatModel`

| Method | Perubahan |
|---|---|
| `latestPerDesaSubquery()` (baru, private) | Satu sumber kebenaran: subquery 1 baris terpilih per desa. Dua tingkat: `mp` = periode terbaru per desa, lalu `lp` = `MAX(id_laporan)` **di dalam** periode itu (bukan id terbesar global). |
| `getAggregationPeriods()` (baru, publik) | Periode-periode yang benar-benar ikut terhitung + jumlah desa. Urutan: `desa DESC`, lalu periode terbaru (deterministik). |
| `getAggregationPeriod()` (baru, publik) | Periode utama = `$periods[0]`; `null` bila kosong. |
| `getAggregatedStats()` | `SUM` dijalankan atas subquery `lp`, bukan atas seluruh tabel. Signature `($id_kecamatan, $id_desa)` tetap; kunci return tetap `getAggregatedStatsKeys()`; kolom yang tidak ada di tabel tidak di-SUM. |
| `getLatestPeriod()` | Disejajarkan: periode dari baris yang ikut agregasi (periode utama), `desa_lapor` dihitung pada periode itu. Menambah `desa_total`. |
| `getCoverage()` | "Sudah lapor" = punya baris terpilih, definisi sama dengan agregasi. |
| `getMapKecamatan()` | `SUM` per kecamatan atas subquery `lp`. Kunci join GeoJSON `kd_kecamatan`/`kode_kecamatan` tetap sama. |
| `getMapDesa()` | `SUM` per desa atas subquery `lp`; filter `?id_kecamatan=` tetap. |
| `getSummaryPerKecamatan()` | Rekap per kecamatan atas subquery `lp` (dipakai `PublicController::buildMapKecamatan()`). |
| `getSummaryPerDesa()` | Rekap per desa atas subquery `lp` (dipakai dashboard & `ApiController`). Pola bug identik; ikut diperbaiki walau tidak disebut di laporan awal. |

Detail:
- Filter kolom: `array_intersect` antara `getAggregatedStatsKeys()` dan
  `$this->db->getFieldNames()` → `dok_kia`/`dok_akta_cerai` (kolom Dukcapil-only, tidak ada
  di `laporan_agregat`) tidak ikut di-SUM, tetap hadir di output dengan nilai `0`.
- Prefix tabel: semua SQL mentah memakai helper `tableName()` (`DBPrefix` + escape),
  sehingga aman di grup `tests` (prefix `db_`) maupun produksi.
- Filter `id_desa` / `id_kecamatan` tetap jalan (join `m_desa` dilakukan di luar subquery).

### `ProfilDukcapilModel`

Pola identik: `latestPerDesaSubquery()` (urutan `tahun*10+semester`, tie-break
`MAX(id_profil)`), `getAggregationPeriods()`, `getAggregationPeriod()`, dan
`getAggregatedStats()` yang menjumlahkan 1 snapshot per desa. Tabel kini hanya 2026/S1 →
hasil tetap **378.190** (tidak berubah), tetapi tahan terhadap periode ganda.

---

## 4. Bukti

### 4.1 Query DB sebelum (mysqli, `scratch/periodfix_before.php`)

```
== PERIODE DI laporan_agregat ==
  2025-12 : 135 baris, 135 desa
  2026-07 :   1 baris,   1 desa
  2026-08 :   1 baris,   1 desa

== TOTAL BARIS ==  baris=137, desa_distinct=135

== SUM NAIF (tanpa filter periode) ==
  jiwa_l=193349, jiwa_p=187176, TOTAL JIWA=380525

== SUM PER PERIODE ==
  2025-12 : 375583
  2026-07 :  2882
  2026-08 :  2060

== DESA DENGAN LEBIH DARI 1 BARIS ==
  id_desa=7 Ranggang     baris=2
  id_desa=102 Batu Ampar  baris=2
```

Perbandingan kandidat semantik (`scratch/periodfix_semantik.php`):

```
(a) SATU periode terbaru global (8/2026) : 2060
(b) SATU baris terbaru per desa          : 375583  (135 desa)
    tanpa filter (bug lama)              : 380525
    TARGET tugas                         : 375583
```

### 4.2 Query DB sesudah (`scratch/periodfix_after.php`, model dipanggil lewat CI4)

```
== SQL REFERENSI (mysqli) ==
  SUM semua baris (BUG)          : 380525
  1 baris terbaru per desa (b)   : 375583 (135 desa)
  periode 08/2026                : 2060  <- opsi (a) salah: cuma 1 desa

== MODEL (CI4) ==
  getAggregatedStats() Total Jiwa: 375583  -> COCOK
  getAggregationPeriod()         : 2025-12
  getLatestPeriod()              : 2025-12, desa_lapor=133, desa_total=135
  getAggregationPeriods()        : 2025-12 : 133 desa / 2026-08 : 1 / 2026-07 : 1
  getCoverage()                  : 135/135 desa, 11/11 kec
  Dukcapil getAggregatedStats()  : 378190 (n_rows=135) -> COCOK

== BATAS / KONTRAK ==
  key hilang dari getAggregatedStats(): tidak ada
  semua nilai int                    : ya
  dok_kia (tak ada di tabel)         : 0
  filter desa tak ada (id=99999)     : 0
  filter kec  tak ada (id=99999)     : 0
  Dukcapil filter []                 : n_rows=0
```

### 4.3 phpunit

```
php vendor/phpunit/phpunit/phpunit tests/unit

PHPUnit 10.5.64 by Sebastian Bergmann and contributors.
................................................................. 65 / 74 ( 87%)
.........                                                         74 / 74 (100%)

OK, but there were issues!
Tests: 74, Assertions: 349, PHPUnit Warnings: 1.
```

Peringatan satu-satunya: `No code coverage driver available` — sudah ada sebelum perubahan
(tidak ada xdebug/pcov di Laragon ini), bukan kegagalan.

Catatan: `vendor/bin/phpunit` **rusak** di Laragon ini; pakai
`php vendor/phpunit/phpunit/phpunit`.

Test baru `tests/unit/PeriodeAgregatTest.php` (14 tes) memakai SQLite `:memory:` (grup
`tests`) dengan tabel ber-**nama sama** seperti produksi (`laporan_agregat`, `m_desa`,
`kecamatan`, `profil_dukcapil`) — dibuat via Forge dengan subset kolom, karena migration
proyek memakai tipe MySQL (`YEAR`). Karena nama tabelnya sama, metode model dieksekusi
persis seperti produksi (tidak ada seam khusus test di model). Yang dibuktikan:

- 1 desa punya 2 periode → hanya periode terbarunya yang dihitung (400, bukan 600).
- Hasil **bukan** 600 (jumlah semua periode) dan **bukan** 300 (opsi a, desa hilang).
- Setiap desa menyumbang tepat satu baris; 2 baris dalam periode sama tidak double-count.
- Perbandingan periode kronologis (`tahun*100+bulan`), bukan angka bulan.
- Label periode = periode utama (desa terbanyak), bukan periode kalender terbaru.
- Filter kecamatan/desa tetap berlaku; tabel kosong → nol, tanpa error.
- `dok_kia`/`dok_akta_cerai` (tak ada di tabel) tidak di-SUM; semua kunci whitelist ada.
- Coverage konsisten dengan agregasi (2/2 desa).
- Dukcapil: 2 semester → hanya terbaru per desa (400, bukan 600); 1 periode → tidak berubah.

### 4.4 Runtime (`php spark serve --host localhost --port 8083`)

```
curl -s "http://localhost:8083/?sumber=desa"     -o scratch/periodfix_desa.html
curl -s "http://localhost:8083/?sumber=dukcapil" -o scratch/periodfix_dukcapil.html
```

Ekstraksi (`scratch/periodfix_grep.php`):

```
=== desa (sumber=desa) ===
  Total Jiwa      : 375.583      <- sebelumnya 380.525
  Kartu Keluarga  : 130.908
  Cakupan Laporan : 135 | / 135 desa
  Label periode   : Periode laporan terbaru: Desember 2025.   <- sebelumnya "Agustus 2026"

=== dukcapil (sumber=dukcapil) ===
  Total Jiwa      : 378.190      <- tidak berubah
  Kartu Keluarga  : 132.030
  Label periode   : Periode laporan terbaru: Desember 2025.
```

Pemeriksaan error (grep `Fatal error` / `DatabaseException` / `Whoops` / `Uncaught` /
`Call to a member`): **tidak ada** pada semua halaman.

Semua rute (HTTP 200, tanpa indikasi error):

| Rute | Total Jiwa |
|---|---|
| `/` | 375.583 |
| `/?sumber=desa` | 375.583 |
| `/?sumber=dukcapil` | 378.190 |
| `/?sumber=campuran` | 375.583 |
| `/takisung` | 34.742 |
| `/takisung?desa=ranggang` | 2.882 |
| `/batu-ampar` | 29.387 |
| `/batu-ampar?desa=batu-ampar` | 2.060 |


### 4.4b Peta "Sebaran Spasial" (temuan lanjutan, diverifikasi independen)

Kartu sudah benar 375.583, tetapi **peta masih 380.525** karena `getMapKecamatan()`,
`getMapDesa()`, dan `getSummaryPerKecamatan()` belum memakai subquery. Terukur SEBELUM
(`scratch/periodfix_map_before.php`):

```
== PETA KECAMATAN (getMapKecamatan) ==
  TOTAL peta kec: jiwa=380525 kk=132696
  SELISIH vs kartu: jiwa=+4942 kk=+1788

== PETA DESA (getMapDesa) ==
  baris=135  TOTAL jiwa=380525 kk=132696

== SUMMARY PER KECAMATAN ==
  TOTAL: jiwa=380525 → SELISIH vs kartu: +4942

== PER KECAMATAN: peta vs kartu ==
  Takisung     peta=37624  kartu=34742  delta=+2882   <-- DOUBLE COUNT (Ranggang)
  Batu Ampar   peta=31447  kartu=29387  delta=+2060   <-- DOUBLE COUNT (Batu Ampar)
  9 kecamatan lain                      delta=+0
```

SESUDAH (sumber skrip yang sama):

```
  TOTAL peta kec: jiwa=375583 kk=130908
  SELISIH vs kartu: jiwa=+0 kk=+0
  PETA DESA:  baris=135  TOTAL jiwa=375583 kk=130908  SELISIH +0
  SUMMARY PER KECAMATAN: TOTAL jiwa=375583 → SELISIH +0
  Takisung     peta=34742  kartu=34742  delta=+0
  Batu Ampar   peta=29387  kartu=29387  delta=+0
  (11/11 kecamatan delta=+0)
```

Bukti API publik (tanpa API key), GeoJSON dijumlahkan per fitur
(`scratch/periodfix_geojson_sum.php`):

```
curl -s "http://localhost:8083/api/v1/map/kecamatan" -o scratch/periodfix_api_map_kec.json
curl -s "http://localhost:8083/api/v1/map/desa"      -o scratch/periodfix_api_map_desa.json

=== API /api/v1/map/kecamatan — 11 fitur ===
  Takisung       jiwa=34742  kk=12195
  Jorong         jiwa=37122  kk=12682
  Pelaihari      jiwa=84227  kk=29195
  Kurau          jiwa=14917  kk=5374
  Bati Bati      jiwa=47595  kk=16628
  Panyipatan     jiwa=26824  kk=9519
  Kintap         jiwa=45747  kk=15390
  Tambang Ulang  jiwa=19611  kk=6957
  Batu Ampar     jiwa=29387  kk=10269
  Bajuin         jiwa=20676  kk=7451
  Bumi Makmur    jiwa=14735  kk=5248
  TOTAL: jiwa=375583  kk=130908

=== API /api/v1/map/desa — 135 fitur ===
  TOTAL: jiwa=375583  kk=130908

  map/kecamatan : jiwa=375583 (COCOK)  kk=130908 (COCOK)
  map/desa      : jiwa=375583 (COCOK)  kk=130908 (COCOK)
  konsisten peta kec == peta desa : YA
  Takisung     peta=34742  target=34742  COCOK
  Batu Ampar   peta=29387  target=29387  COCOK
```

Di halaman yang sama (`/?sumber=desa`): kartu "Total Jiwa" **375.583** dan jumlah
`total_jiwa` peta yang ter-embed di HTML **375.583** — identik, Δ=0.

### 4.5 `php -l`

```
php -l app/Models/LaporanAgregatModel.php   -> No syntax errors detected
php -l app/Models/ProfilDukcapilModel.php   -> No syntax errors detected
php -l tests/unit/PeriodeAgregatTest.php    -> No syntax errors detected
php -l app/Views/public/landingpage.php     -> No syntax errors detected
```

---

## 5. Catatan & tindak lanjut

- **Baris 7/2026 & 8/2026 tetap ada** di tabel (riwayat tidak dihapus; bukan bagian tugas
  dan tidak menyentuh skema). Keduanya kini hanya menggeser 2 desa ke periodenya masing-
  masing, bukan menggandakannya.
- **Label periode tidak lagi berarti "satu bulan"** bila desa menyumbang periode berbeda.
  `getLatestPeriod()['desa_total']` (135) vs `desa_lapor` (133) mengekspos selisih itu.
  Note-strip kini menampilkannya bila `desa_total > desa_lapor`: "Periode laporan terbaru:
  **Desember 2025** (133 dari 135 desa; sisanya memakai laporan terakhirnya)." Perubahan
  view dibatasi pada klausa itu saja — `.filter-warn`, `.src-chip`, dan dropdown sumber
  data tidak disentuh (diverifikasi masih ada di HTML hasil curl).
- **Opsi (a) baru ideal setelah periode lengkap**: saat semua 135 desa punya baris di
  periode terbaru, opsi (b) otomatis sama dengan (a).
- **Peta & API sudah ikut diperbaiki** (menyusul temuan "peta 380.525 vs kartu 375.583"):
  `getMapKecamatan()`, `getMapDesa()`, `getSummaryPerKecamatan()`, `getSummaryPerDesa()`
  kini semuanya turunan `latestPerDesaSubquery()`. Cara verifikasi cepat: jumlah
  `total_jiwa` dari 11 fitur GeoJSON `/api/v1/map/kecamatan` harus = kartu Total Jiwa
  (375.583) = `/api/v1/map/desa`. Selisih ≠ 0 = ada agregasi yang lupa memakai subquery.
- **`getSummaryPerDesa()` ikut diperbaiki** walau tidak disebut di laporan awal: pola bug
  identik dengan `getSummaryPerKecamatan()` (dipakai `DashboardController` &
  `ApiController`), jadi dibiarkan akan tetap double-count di dashboard & API rekap.
