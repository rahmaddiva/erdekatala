# Fix Label/Sumber Landing Page (#2, #3, #5)

**Tanggal:** 2026-10-09
**Acuan audit:** `docs/superpowers/specs/2026-10-09-analisis-filter-sumber-data.md`
**Scope:** 3 perbaikan LABEL/PROVENANCE di landing page publik `/`. **Bukan** angka agregasi
(model `app/Models/**` tidak disentuh — worker lain sedang memperbaiki double-count periode).

**File diubah (3):**

| File | Bagian |
|---|---|
| `app/Controllers/PublicController.php` | resolve slug (tracking hasil resolusi), blok Kelompok A (provenance), `$profilMeta` (+ `mode_otomatis`), pengiriman data ke view |
| `app/Views/public/landingpage.php` | label skop, note-strip, badge, dropdown "Sumber data", catatan + chip sumber chart/tabel Dokumen |
| `app/Views/templates/theme_toggle.php` | gaya dark-mode untuk komponen baru (`.filter-warn`, `.src-note`, `.src-chip`, `.sumber-hint`) |

**Tidak disentuh:** `app/Models/**`, `app/Filters/**`, `AGENTS.md`, migration, seeder.

---

## Kondisi data saat pengerjaan

135/135 desa sudah punya baris di `laporan_agregat` → mode default (tanpa `?sumber=`) berujung
**100% laporan desa** (`profilMeta['mode'] = 'laporan'`). KIA & Akta Cerai tetap di-override dari
Dukcapil karena `laporan_agregat` tidak punya kolomnya.

Angka yang terlihat saat verifikasi (dicatat apa adanya, bisa berubah karena worker lain
sedang memperbaiki agregasi periode):

| Mode | Total Jiwa |
|---|---|
| default | 380.525 |
| `?sumber=desa` | 380.525 |
| `?sumber=dukcapil` | 378.190 |
| `?kecamatan=bajuin&desa=tidak-ada` | 20.676 |

Fokus laporan ini **konsistensi label vs sumber**, bukan nilai total.

---

# #2 — Label sumber KIA & Akta Cerai (severity TINGGI) ✅

## Masalah

`PublicController.php:173-179` (sebelum diubah): selama bukan `?sumber=desa`, KIA & Akta Cerai
**selalu** diisi dari `profilDukcapilModel->getAggregatedStats($desaScope)`. Namun badge Bagian 1,
pill hero, dan note-strip menyatakan **"Laporan Desa"** — label menyesatkan karena dua angka itu
(76.078 & 6.010) bukan dari laporan desa.

## Perbaikan

**Controller** — tambah provenance per-indikator di blok Kelompok A:

- `$dokumenSumber[label] = 'dukcapil' | 'utama'` — KIA & Akta Cerai selalu `dukcapil`;
  KTP-el/Akta Lahir/Akta Nikah/KK Fisik mengikuti mode utama. Saat `?sumber=dukcapil` **semua**
  jadi `dukcapil` (label konsisten).
- `$profilMeta['dokumen_sumber']`, `kelompok_a`, `kelompok_a_aktif`, `kelompok_a_semester`,
  `kelompok_a_tahun` dikirim ke view. Semester/tahun diambil ulang dari `profil_dukcapil` karena
  di mode `laporan` nilai `$semesterDukcapil` bisa `null` (statsDukcapil kosong) padahal
  KIA/Akta Cerai tetap dari Dukcapil.

**View** — tiga penanda, tanpa menghapus data:

1. **Catatan di bawah chart Dokumen** (`.src-note`) — menyebut KIA & Akta Cerai dari snapshot
   Dukcapil + semester/tahun, dan menyebut bahwa laporan desa tidak memuatnya.
2. **Chip sumber per-indikator** di tabel Kesehatan & Dokumen (`.src-chip`).
3. **Label sumbu chart** diberi sufiks `· Dukcapil` + **warna berbeda** per bar
   (biru = Dukcapil, primer = sumber mode utama) via `dokColors` / `dokColor()`.
4. **Note-strip** Bagian Ringkasan kini menyebut pengecualian KIA & Akta Cerai.

## Sebelum → Sesudah

**Sebelum (default):** badge "Laporan Desa", note-strip "diturunkan dari laporan desa",
chart 6 jenis tanpa keterangan — KIA 76.078 & Akta Cerai 6.010 dari Dukcapil tanpa penanda.

**Sesudah (default):**

```html
<div class="src-note">
  <i class="bi bi-tags-fill"></i>
  <p>
    <strong>KIA</strong> &amp; <strong>Akta Cerai</strong> bersumber dari snapshot
    <strong>Dukcapil Kab. Tanah Laut</strong> (semester 1/2026),
    bukan dari laporan desa seperti KTP-el, Akta Lahir, Akta Nikah &amp; KK Fisik.
    Laporan desa tidak memuat kedua indikator ini.
  </p>
</div>
```

Note-strip:

> Data Wilayah ini diturunkan dari laporan desa yang sudah diinput admin desa.
> **KIA & Akta Cerai dikecualikan:** keduanya bersumber dari snapshot Dukcapil Kab. Tanah Laut
> (semester 1/2026), karena laporan desa tidak memuatnya.

Tabel (chip per-indikator):

```
KTP-el      → chip "laporan desa"
Akta Lahir  → chip "laporan desa"
Akta Nikah  → chip "laporan desa"
KK Fisik    → chip "laporan desa"
KIA         → chip "Dukcapil"
Akta Cerai  → chip "Dukcapil"
```

Chart JS:

```js
dokLabels = ["KTP-el","Akta Lahir","Akta Nikah","KK Fisik","KIA · Dukcapil","Akta Cerai · Dukcapil"]
dokColors = ["C_UTAMA","C_UTAMA","C_UTAMA","C_UTAMA","C_DUKCAPIL","C_DUKCAPIL"]
```

**`?sumber=dukcapil` — label konsisten (semua Dukcapil):**

```html
<div class="src-note">
  <i class="bi bi-tags-fill"></i>
  <p>
    <strong>Semua 6 indikator</strong> bersumber dari snapshot
    <strong>Dukcapil Kab. Tanah Laut</strong> (semester 1/2026).
  </p>
</div>
```

```js
dokLabels = ["KTP-el · Dukcapil","Akta Lahir · Dukcapil","Akta Nikah · Dukcapil",
             "KK Fisik · Dukcapil","KIA · Dukcapil","Akta Cerai · Dukcapil"]
dokColors = ["C_DUKCAPIL","C_DUKCAPIL","C_DUKCAPIL","C_DUKCAPIL","C_DUKCAPIL","C_DUKCAPIL"]
```

**`?sumber=desa`** — KIA & Akta Cerai tetap disembunyikan (perilaku lama dipertahankan),
tidak ada `.src-note`:

```js
dokLabels = ["KTP-el","Akta Lahir","Akta Nikah","KK Fisik"]   // 4 jenis
```

---

# #3 — Label skop salah saat `?desa=` tidak valid (severity TINGGI) ✅

## Masalah

`landingpage.php:15` & `:1342` memakai `$filter_desa_slug` **mentah**. Saat slug desa tidak bisa
di-resolve, halaman menulis skop "Desa"/"desa terpilih" padahal `desaScope` masih seluruh kecamatan.

## Perbaikan

**Controller** — resolve slug kini mencatat hasilnya, dan semuanya dikirim ke view:

- `$filter_kec_nama`, `$filter_desa_nama` (nama hasil lookup DB)
- `$kec_unresolved`, `$desa_unresolved` (slug ada tapi gagal di-resolve)
- `$id_kec_filter`, `$id_desa_filter`

**View** — `$pendudukKey` dan `$scopeDetail` diturunkan dari **hasil resolusi**
(`$id_desa_filter` → `Desa`; `$id_kec_filter` → `Kecamatan`; selain itu `Kabupaten`),
bukan dari string `?desa=`. Plus strip peringatan `.filter-warn` sebagai umpan balik.

## Sebelum → Sesudah

**Sebelum:** `/?kecamatan=bajuin&desa=tidak-ada`
→ `Skop: <strong>Desa</strong> - desa terpilih` (salah; angka 20.676 = 9 desa Bajuin)

**Sesudah:**

```html
<div class="filter-warn">
  <i class="bi bi-exclamation-triangle-fill"></i>
  <p>
    Desa &ldquo;tidak-ada&rdquo; tidak ditemukan di Kecamatan Bajuin.
    Menampilkan data <strong>tingkat kecamatan</strong> (seluruh desa di kecamatan ini).
  </p>
</div>

<span class="crumb">Skop: <strong>Kecamatan</strong> - Kec. Bajuin</span>
```

Kasus lain (bonus, slug kecamatan tidak valid):

`/?kecamatan=xxx-tidak-ada`

```html
<div class="filter-warn">
  <i class="bi bi-exclamation-triangle-fill"></i>
  <p>
    Kecamatan &ldquo;xxx-tidak-ada&rdquo; tidak dikenali atau tidak dipublikasikan.
    Menampilkan data <strong>seluruh kabupaten</strong>.
  </p>
</div>

<span class="crumb">Skop: <strong>Kabupaten</strong> - seluruh kabupaten</span>
```

Kasus valid tetap benar (tidak ada `.filter-warn`):

| URL | Skop |
|---|---|
| `/` | `Skop: <strong>Kabupaten</strong> - seluruh kabupaten` |
| `/?kecamatan=bajuin` | `Skop: <strong>Kecamatan</strong> - Kec. Bajuin` |
| `/?kecamatan=bajuin&desa=bajuin` | `Skop: <strong>Desa</strong> - Desa Bajuin, Kec. Bajuin` |

---

# #5 — Dropdown "Sumber data" default menyesatkan (severity SEDANG) ✅

## Masalah

`landingpage.php:1479` — opsi `campuran` berlabel **"Dukcapil + Laporan Desa (otomatis)"** dan
tampil terpilih di mode default, padahal faktanya **murni laporan desa** (135/135 desa sudah lapor,
Dukcapil menyumbang 0).

## Perbaikan

**Controller** — `$profilMeta` mendapat mode faktual yang **tid bergantung pada `?sumber=` aktif**:

```php
'mode_otomatis'  => count($desaLaporIds)
    ? (count(array_diff($desaScope, $desaLaporIds)) ? 'campuran' : 'laporan')
    : 'dukcapil',
'laporan_count_otomatis'  => count($desaLaporIds),
'dukcapil_count_otomatis' => count(array_diff($desaScope, $desaLaporIds)),
```

Alasan: `mode` yang lama ikut `?sumber=` aktif, sehingga saat `?sumber=desa` label opsi "otomatis"
jatuh ke cabang `campuran` dan menulis "campuran (135 desa lapor · 0 Dukcapil)" — salah.
`mode_otomatis` selalu menggambarkan **apa yang terjadi jika filter dilepas**.

**View** — label opsi + hint di sebelah dropdown mengikuti `$otoMode`, angka cakupan ikut scope.

## Sebelum → Sesudah

**Sebelum:** `<option value="campuran" selected>Dukcapil + Laporan Desa (otomatis)</option>`

**Sesudah (default):**

```html
<option value="campuran" selected>Otomatis — murni Laporan Desa (135/135 desa sudah lapor)</option>
<option value="desa" >Hanya Laporan Desa</option>
<option value="dukcapil" >Hanya Dukcapil</option>

<span class="sumber-hint">
  <i class="bi bi-info-circle"></i>
  Semua 135/135 desa di wilayah ini sudah lapor, sehingga mode otomatis
  menghasilkan <strong>100% laporan desa</strong> — Dukcapil tidak menyumbang angka
  kecuali KIA &amp; Akta Cerai.
</span>
```

Hint ini juga menutup celah #2: menyebut bahwa pengecualiannya cuma KIA & Akta Cerai.

**Filter tetap berfungsi** — JS tidak diubah, `?sumber=` masih bekerja:

```js
if (this.value === 'campuran') { url.searchParams.delete('sumber'); }
else { url.searchParams.set('sumber', this.value); }
```

Saat `?sumber=desa` atau `?sumber=dukcapil` aktif, hint disembunyikan (yang ditampilkan adalah
mode eksplisit), dan label opsi "otomatis" tetap menggambarkan kondisi nyata:

| URL | Label opsi "otomatis" | Hint |
|---|---|---|
| `/` | `Otomatis — murni Laporan Desa (135/135 desa sudah lapor)` | 100% laporan desa, kecuali KIA & Akta Cerai |
| `/?kecamatan=bajuin` | `Otomatis — murni Laporan Desa (9/9 desa sudah lapor)` | idem |
| `/?kecamatan=bajuin&desa=bajuin` | `Otomatis — murni Laporan Desa (1/1 desa sudah lapor)` | idem |
| `/?sumber=desa` | `Otomatis — murni Laporan Desa (135/135 desa sudah lapor)` | (disembunyikan) |
| `/?sumber=dukcapil` | `Otomatis — murni Laporan Desa (135/135 desa sudah lapor)` | (disembunyikan) |

---

## Verifikasi

### 1. `php -l`

```
No syntax errors detected in app/Views/public/landingpage.php
No syntax errors detected in app/Views/templates/theme_toggle.php
No syntax errors detected in app/Controllers/PublicController.php
```

### 2. Render HTTP (`php spark serve --host localhost --port 8084`, lalu dimatikan)

Semua 7 URL merespons **HTTP 200**. Tidak ada `Fatal error`, `Warning:`, `Undefined`, `Notice:`,
`Deprecated:`, atau `Whoops` di HTML hasil render:

```
default     → HTTP 200, ERRORS: NONE
dukcapil    → HTTP 200, ERRORS: NONE
desa        → HTTP 200, ERRORS: NONE
kecbajuin   → HTTP 200, ERRORS: NONE
desabajuin  → HTTP 200, ERRORS: NONE
bogusdesa   → HTTP 200, ERRORS: NONE
boguskec    → HTTP 200, ERRORS: NONE
```

| URL | Hasil yang diminta | Status |
|---|---|---|
| `/?sumber=dukcapil` | semua 6 indikator berlabel Dukcapil | ✅ |
| `/` | sumber utama "laporan desa", KIA/Akta Cerai ditandai Dukcapil | ✅ |
| `/?kecamatan=bajuin&desa=tidak-ada` | skop **Kecamatan** (bukan "Desa") + peringatan | ✅ |

### 3. Artefak

`scratch/label_fix/`: `default.html`, `dukcapil.html`, `desa.html`, `kecbajuin.html`,
`desabajuin.html`, `bogusdesa.html`, `boguskec.html` — render HTTP sungguhan untuk kutipan di atas.

### 4. Dark mode

Konvensi mengikuti pola yang sudah ada di `theme_toggle.php` (`body.dark-mode ... !important`):

```css
body.dark-mode .filter-warn { background: #3a2d10 !important; border-color: #5c4718 !important;
                              border-left-color: #d99a1a !important; }
body.dark-mode .filter-warn > i { color: #f0b37e !important; }
body.dark-mode .filter-warn p { color: #f2e2c0 !important; }
body.dark-mode .filter-warn strong { color: #ffe6b0 !important; }
body.dark-mode .src-note { background: #0b1526 !important; border-color: #26364f !important; }
body.dark-mode .src-note > i { color: #6db4e8 !important; }
body.dark-mode .src-note p { color: #c6d3e6 !important; }
body.dark-mode .src-note strong { color: #e8eef6 !important; }
body.dark-mode .src-chip.dukcapil { background: #12314f !important; color: #9ec7f0 !important;
                                    border-color: #24547f !important; }
body.dark-mode .src-chip.desa { background: #1b2c47 !important; color: #c6d3e6 !important;
                                border-color: #26364f !important; }
body.dark-mode .group-filter .sumber-hint { color: #8fa1b8 !important; }
body.dark-mode .group-filter .sumber-hint > i { color: #6db4e8 !important; }
body.dark-mode .group-filter .sumber-hint strong { color: #e8eef6 !important; }
```

---

## Catatan / tidak dilakukan

- **Tidak ada data yang dihapus.** KIA & Akta Cerai tetap tampil (kecuali `?sumber=desa`,
  perilaku lama yang sudah ada).
- **Tidak menyentuh agregasi.** Total Jiwa bisa berubah (380.525 → 375.583) saat worker lain
  selesai memperbaiki filter periode — tidak ada hard-assert nilai total di kode maupun laporan ini.
- **Temuan audit yang sengaja tidak dikerjakan** (di luar 3 perbaikan yang diminta):
  #4 note-strip "seluruh grafik dan tabel" (YanKB/Dallap/peta tidak ikut filter),
  #5 audit badge hardcoded "Laporan Desa" di Sebaran Spasial (audit #5, beda nomor),
  #6 "Cakupan Laporan 135/135" tetap tampil di mode Dukcapil,
  #8 `desaScope` default tidak memfilter `is_public=1`.
- Belum di-commit (sesuai instruksi).
