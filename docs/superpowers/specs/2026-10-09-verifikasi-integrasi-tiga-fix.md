# Verifikasi Integrasi Tiga Fix — 2026-10-09

Verifikasi end-to-end (read-only) atas tiga perbaikan yang mendarat bersamaan:
double-count periode, label sumber/skop landing page, dan HTTP 500 `get_cookie()`.

**Cara:** `php spark serve --host localhost --port 8086`, lalu `curl` tiap URL ke
`scratch/integ/*.html` + `curl` endpoint peta `/api/v1/map/{kecamatan,desa}`.
Server dimatikan setelah selesai. Tidak ada perubahan kode/DB (SELECT/GET saja).

## 1. Tabel per-URL

Kolom angka diambil dari kartu ringkasan:
- `Total Jiwa` → `<div class="label">Total Jiwa</div><div class="value">…`
- `Total KK` → kartu berlabel **`Kartu Keluarga`** (bukan "Total KK"; "Total KK"
  hanya muncul sebagai label di `<script>` pemetaan indikator).

| # | URL | HTTP | Total Jiwa | Total KK (Kartu Keluarga) | Skop | Catatan label / error |
|---|-----|------|-----------|---------------------------|------|-----------------------|
| 01 | `/` | 200 | **375.583** | **130.908** | Kabupaten | Catatan: "**KIA** & **Akta Cerai** dikecualikan: keduanya bersumber dari snapshot Dukcapil Kab. Tanah Laut (semester 1/2026)" |
| 02 | `/?sumber=desa` | 200 | **375.583** | **130.908** | Kabupaten | Sama; dropdown: `Hanya Laporan Desa` selected |
| 03 | `/?sumber=dukcapil` | 200 | **378.190** | **132.030** | Kabupaten | Catatan: "Data Wilayah ini bersumber dari snapshot Dukcapil Kab. Tanah Laut (semester 1/2026)" |
| 04 | `/?kecamatan=bajuin` | 200 | 20.676 | 7.451 | Kecamatan | — |
| 05 | `/?kecamatan=bajuin&desa=tidak-ada` | 200 | 20.676 | 7.451 | **Kecamatan** | `filter-warn`: "Desa “tidak-ada” tidak ditemukan di Kecamatan Bajuin. Menampilkan data **tingkat kecamatan**" |
| 06 | `/takisung` | 200 | **34.742** | 12.195 | (hal. kecamatan) | — |
| 07 | `/takisung?desa=ranggang` | 200 | **2.882** | 1.055 | (hal. kecamatan) | — |
| 08 | `/batu-ampar` | 200 | **29.387** | 10.269 | (hal. kecamatan) | — |
| 09 | `/batu-ampar?desa=batu-ampar` | 200 | **2.060** | 733 | (hal. kecamatan) | — |
| 10 | `/dashboard` | **302** | — | — | — | `Location: http://localhost:8086/login` |
| 11 | `/kecamatan` | **302** | — | — | — | `Location: http://localhost:8086/login` |
| 12 | `/login` | **200** | — | — | — | Halaman login normal |

Ekstra (pemeriksaan pendukung, bukan daftar wajib):

| URL | HTTP | Total Jiwa | Total KK | Catatan |
|-----|------|-----------|----------|---------|
| `/?kecamatan=bajuin&desa=bajuin` (desa valid) | 200 | 2.299 | 864 | Skop = **Desa** - Desa Bajuin, Kec. Bajuin; tanpa `filter-warn` → kontras yang benar dgn #05 |
| `/takisung?desa=ranggang-dalam` | 200 | 2.928 | 960 | — |
| `/batu-ampar?desa=gunung-malang` | 200 | 29.387 | 10.269 | Slug desa tidak di-resolve → jatuh ke skop kecamatan |

## 2. Status per butir

### Butir 3 — Cek angka → **PASS**

| Ekspektasi | Aktual | Status |
|-----------|--------|--------|
| `?sumber=desa` = 375.583 (bukan 380.525), KK 130.908 | 375.583 / 130.908 | PASS |
| `?sumber=dukcapil` = 378.190 / 132.030 | 378.190 / 132.030 | PASS |
| default = 375.583 (135/135 desa lapor → mode laporan) | 375.583 | PASS |
| `/takisung` = 34.742 (bukan 34.944) | 34.742 | PASS |
| `/takisung?desa=ranggang` = 2.882 | 2.882 | PASS |
| `/batu-ampar` = 29.387 | 29.387 | PASS |
| `/batu-ampar?desa=batu-ampar` = 2.060 | 2.060 | PASS |
| Jalur Dukcapil tetap 378.190 / 132.030 | 378.190 / 132.030 | PASS |

Bukti mentah (kartu `01_default.html`):

```
<div class="label">Total Jiwa</div>    <div class="value">375.583</div>
<div class="sub"><b>190.759 L</b> · <i>184.824 P</i></div>
<div class="label">Kartu Keluarga</div><div class="value">130.908</div>
<div class="sub">Rata-rata <b>2.9</b> jiwa/KK</div>
```

`380.525` / `380525` / `132.696`: **0 kemunculan** di semua HTML yang di-render
(kartu & tabel). Angka lama hanya tersisa di jalur peta (lihat butir 7).

### Butir 4 — Cek label → **PASS**

- **Desa bogus → skop kecamatan**: `/?kecamatan=bajuin&desa=tidak-ada`
  - `Skop: <strong>Kecamatan</strong> - Kec. Bajuin` (bukan "Desa")
  - `filter-warn`: `Desa “tidak-ada” tidak ditemukan di Kecamatan Bajuin. Menampilkan data tingkat kecamatan (seluruh desa di kecamatan ini).`
  - Kontras: desa valid (`?kecamatan=bajuin&desa=bajuin`) → `Skop: Desa - Desa Bajuin, Kec. Bajuin`, tanpa `filter-warn`.
- **Catatan KIA & Akta Cerai dari Dukcapil** (default & `?sumber=desa`):
  - `note-strip`: "…<strong>KIA</strong> &amp; <strong>Akta Cerai</strong> dikecualikan: keduanya bersumber dari snapshot Dukcapil Kab. Tanah Laut (semester 1/2026), karena laporan desa tidak memuatnya."
  - Chip per-indikator: `<span class="src-chip dukcapil" title="Sumber data indikator ini">Dukcapil</span>` tepat pada baris **KIA** (76.078) dan **Akta Cerai** (6.010).
- **Dropdown "Sumber data" jujur**:
  - default & `?sumber=desa`: `Otomatis — murni Laporan Desa (135/135 desa sudah lapor)` → **tidak** mengklaim "Dukcapil + Laporan Desa".
  - Kalimat pendamping: "Semua 135/135 desa di wilayah ini sudah lapor, sehingga mode otomatis menghasilkan **100% laporan desa** — Dukcapil tidak menyumbang angka **kecuali KIA & Akta Cerai**."
  - `?kecamatan=bajuin`: `(9/9 desa sudah lapor)`.
  - Grep `Dukcapil + Laporan` / `Dukcapil+Laporan` di semua HTML → **0 hasil**.

### Butir 5 — Cek HTTP code → **PASS**

```
HTTP/1.1 302 Found   Location: http://localhost:8086/login    (/dashboard)
HTTP/1.1 302 Found   Location: http://localhost:8086/login    (/kecamatan)
/login                HTTP 200
```

`Call to undefined function` → 0 kemunculan. `app/Filters/LoggedIn.php`
memanggil `helper('cookie')` **sebelum** `get_cookie('remember_token')`.

### Butir 6 — Cek error → **PASS**

Grep `Fatal error|Parse error|DatabaseException|Whoops|Uncaught|Undefined array key`
pada **semua** file HTML → **0 kecocokan** (15 file: 12 wajib + 3 ekstra).

### Butir 7 — PETA: **FAIL — inkonsistensi antar-bagian halaman (belum ikut diperbaiki)**

`getMapKecamatan()` / `getMapDesa()` (`app/Models/LaporanAgregatModel.php:497,523`)
masih menjalankan `SUM(laporan_agregat.<field>)` atas `$this->builder()` =
**semua baris**, tanpa `latestPerDesaSubquery()`. Jadi peta memakai basis
**380.525** sementara kartu ringkasan sudah **375.583**.

Bukti terukur:

| Sumber | Total Jiwa | Total KK |
|--------|-----------|----------|
| Kartu ringkasan (`/`, `?sumber=desa`) | **375.583** | **130.908** |
| `GET /api/v1/map/kecamatan` (11 fitur GeoJSON, `properties.total_jiwa`) | **380.525** | **132.696** |
| `GET /api/v1/map/desa` (135 fitur GeoJSON) | **380.525** | **132.696** |
| Array `buildMapKecamatan()` yang di-embed di landing page (11 kecamatan) | **380.525** | **132.696** |
| Selisih (peta − kartu) | **+4.942 jiwa** | **+1.788 KK** |

Per-kecamatan (array embedded vs kartu halaman kecamatan) — selisih persis sama
dengan nilai desa yang terhitung dua kali:

| Kecamatan | Peta `total_jiwa` | Kartu (benar) | Selisih | Penyebab |
|-----------|------------------|---------------|---------|----------|
| 001 Takisung | 37.624 | 34.742 | **+2.882** | = nilai Desa Ranggang (2.882) terhitung 2× |
| 009 Batu Ampar | 31.447 | 29.387 | **+2.060** | = nilai Desa Batu Ampar (2.060) terhitung 2× |
| 010 Bajuin | 20.676 | 20.676 | 0 | konsisten |
| 002–008, 011 | identik | identik | 0 | konsisten |
| **Total** | **380.525** | **375.583** | **+4.942** | 2.882 + 2.060 |

Detail level desa (GeoJSON `map/desa`, `properties.total_jiwa`):

| Desa | Peta | Nilai benar (kartu) | Selisih |
|------|------|--------------------|---------|
| 001.007 Ranggang (Kec. Takisung) | 5.764 | 2.882 | +2.882 (2× lipat) |
| 009.001 Batu Ampar (Kec. Batu Ampar) | 4.120 | 2.060 | +2.060 (2× lipat) |
| 001.010 Ranggang Dalam | 2.928 | 2.928 | 0 (kontrol: desa bertetangga tidak dobel) |

Catatan: `map/desa` memiliki 135 fitur (benar — tidak ada desa hilang/duplikat
baris), jadi kerusakannya murni nilai, bukan kardinalitas.

**Dampak terlihat:** pada halaman yang sama, kartu "Total Jiwa" menampilkan
375.583 sedangkan panel **Sebaran Spasial** (choropleth + tooltip per kecamatan/
desa) menjumlahkan 380.525. Tooltip Batu Ampar menunjukkan 31.447 padahal
`/batu-ampar` berkata 29.387; Takisung 37.624 vs 34.742.

**Ruang lingkup yang terdampak (belum diperbaiki):**
- `LaporanAgregatModel::getMapKecamatan()` (:497)
- `LaporanAgregatModel::getMapDesa()` (:523)
- `LaporanAgregatModel::getSummaryPerKecamatan()` (:163) → dipakai
  `PublicController::buildMapKecamatan()` (:552) untuk array kecamatan yang
  di-embed ke landing page (sumber 380.525 pada tabel di atas)
- `LaporanAgregatModel::getSummaryPerDesa()` (:147) → pola SUM tanpa filter
  periode yang sama

`getAggregatedStats()` (:408) dan `ProfilDukcapilModel::getAggregatedStats()`
(:163) sudah memakai `latestPerDesaSubquery()` — hanya jalur peta/rekap yang
tertiggal.

## 3. Ringkasan status

| Butir | Status |
|-------|--------|
| 3. Cek angka (375.583 / 130.908 / 378.190 / 132.030 / 34.742 / 2.882 / 29.387 / 2.060) | **PASS** |
| 4. Cek label (skop Kecamatan saat desa bogus, KIA & Akta Cerai = Dukcapil, dropdown jujur) | **PASS** |
| 5. Cek HTTP (302 /dashboard & /kecamatan, 200 /login, tanpa `Call to undefined function`) | **FAIL→PASS**: semua terpenuhi |
| 6. Cek error (0 Fatal/Parse/DatabaseException/Whoops/Uncaught/Undefined array key) | **PASS** |
| 7. Konsistensi peta | **FAIL** — peta masih basis 380.525 / 132.696 (selisih +4.942 jiwa, +1.788 KK) |

Tindakan yang disarankan (di luar batas tugas ini, belum dikerjakan):
jalankan `SUM` pada `getMapKecamatan()`/`getMapDesa()`/`getSummaryPerKecamatan()`/
`getSummaryPerDesa()` di atas `latestPerDesaSubquery()` seperti yang sudah
dilakukan `getAggregatedStats()`.
