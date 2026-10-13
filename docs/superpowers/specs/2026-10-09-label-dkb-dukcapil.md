# Label Sumber Data Resmi Dukcapil (DKB) di Landing Page Publik `/`

**Tanggal:** 2026-10-09
**Scope:** pelabelan/provenance murni. **Tidak** mengubah angka, logika agregasi,
maupun perilaku filter `?sumber=`. Server dev dijalankan di `localhost:8087`
(bukan `127.0.0.1`) dan dimatikan setelah verifikasi.

---

## 1. Label resmi yang diterapkan

Teks persis (byte-for-byte, termasuk tanda bintang di depan):

```
* Sumber data adalah Data Konsolidasi Bersih (DKB) Semester I Tahun 2026
```

Diverifikasi byte-for-byte terhadap isi konstanta (lihat §4).

---

## 2. Sentralisasi string — satu sumber kebenaran

### Lokasi konstanta

`app/Models/ProfilDukcapilModel.php` (baris 29):

```php
public const SUMBER_LABEL_DKB = '* Sumber data adalah Data Konsolidasi Bersih (DKB) Semester I Tahun 2026';
```

### Mengapa di `ProfilDukcapilModel`?

Model ini adalah representasi tabel snapshot Dukcapil (`profil_dukcapil`) — entitas
yang **memproduksi** data ini. Menaruh label provenance di model membuat string
"ikut" dengan sumber datanya: siapa pun yang memakai snapshot Dukcapil bisa
mengimpor labelnya. View tidak perlu tahu detail periode; controller tidak perlu
menduplikasi literal.

### Dinamis vs literal — pilihan: keduanya, dengan literal sebagai jaminan

Dibuat helper `ProfilDukcapilModel::sumberLabelDkb(?int $semester, ?int $tahun)`
yang membangun kalimat dengan **semester Romawi** (`I`/`II`) dan tahun dinamis:

```php
public static function sumberLabelDkb(?int $semester = 1, ?int $tahun = 2026): string
{
    if ($semester === null || $tahun === null) {
        return self::SUMBER_LABEL_DKB; // fallback literal (tabel kosong)
    }
    return sprintf(
        '* Sumber data adalah Data Konsolidasi Bersih (DKB) Semester %s Tahun %d',
        self::romawiSemester($semester),
        $tahun
    );
}
```

Controller memanggilnya dengan `$kelompokASemester` / `$kelompokATahun` (periode
snapshot yang benar-benar dipakai KIA & Akta Cerai) dan mengirim hasilnya ke view
lewat `$profilMeta['sumber_label_dkb']`. View memakai
`$profilMeta['sumber_label_dkb'] ?? \App\Models\ProfilDukcapilModel::SUMBER_LABEL_DKB`
sebagai jaring pengaman.

**Alasan memilih dinamis + fallback literal (bukan literal murni):**
- kata-kata kalimat tetap **persis** seperti permintaan user (diuji cocok byte-for-byte);
- bila snapshot berganti (mis. S2/2026), label **tidak basi** — semester Romawi &
  tahun mengikuti `$profilMeta['semester']`/`['tahun']` dari DB, bukan hardcode;
- tabel kosong / meta tidak lengkap tidak menghasilkan teks rusak — jatuh ke
  literal yang sudah benar.
- `romawiSemester()` mengembalikan `I` untuk semester 1 sehingga output kini
  identik dengan literal; tidak ada perilaku yang bergantung pada casing lama.

### Wiring controller (`app/Controllers/PublicController.php` baris 232)

```php
$profilMeta['sumber_label_dkb'] = ProfilDukcapilModel::sumberLabelDkb(
    $kelompokASemester,
    $kelompokATahun
);
```

Ditempatkan **setelah** blok Kelompok A yang sudah ada (baris ~215–228) — blok itu
sudah menjamin `$kelompokASemester`/`$kelompokATahun` terisi dari snapshot Dukcapil
walau mode utama = `laporan`/`campuran`. Tidak ada logika agregasi yang diubah.

---

## 3. Lokasi yang diubah (sebelum → sesudah)

Grep awal menemukan **satu-satunya** view publik yang menulis teks "Dukcapil":
`app/Views/public/landingpage.php` (file lain hanya CSS dark-mode
`theme_toggle.php`). Tidak ada view publik lain (mis. `kecamatan.php`) yang
merender angka Dukcapil.

| # | Titik | File:baris (sesudah) | Cara label dipasang |
|---|---|---|---|
| 1 | note-strip mode `dukcapil` | `landingpage.php:1514` | `<span class="src-official">` |
| 1 | note-strip mode `campuran` | `landingpage.php:1517` | `<span class="src-official">` |
| 2 | catatan KIA & Akta Cerai (note-strip) | `landingpage.php:1524` | `<span class="src-official">` |
| 3 | `.src-note` chart Dokumen | `landingpage.php:1912` | `<span class="src-official">` |
| 4 | chip `.src-chip` Dukcapil (KIA/Akta Cerai) | `landingpage.php:1950,1954` | `title="…"` (tooltip) |
| 5 | pill hero "Sumber: …" | `landingpage.php:1393` | `title="…"` (tooltip) |
| — | CSS label | `landingpage.php:841` | `.src-official` |
| — | CSS dark-mode | `theme_toggle.php:58` | `body.dark-mode .src-official` |

### 3.1 note-strip — mode `dukcapil` (titik 1)

**Sebelum:**
```html
Data Wilayah ini bersumber dari snapshot Dukcapil Kab. Tanah Laut (semester 1/2026).
```
**Sesudah:**
```html
Data Wilayah ini bersumber dari snapshot Dukcapil Kab. Tanah Laut.
<span class="src-official">* Sumber data adalah Data Konsolidasi Bersih (DKB) Semester I Tahun 2026</span>
```

### 3.2 note-strip — mode `campuran` (titik 1)

**Sebelum:**
```html
Data Wilayah ini gabungan: laporan desa + snapshot Dukcapil untuk desa yang belum lapor.
```
**Sesudah:** sama, ditambah `<span class="src-official">…DKB…</span>`.

### 3.3 catatan pengecualian KIA & Akta Cerai (titik 2)

**Sebelum:**
```html
<strong>KIA</strong> &amp; <strong>Akta Cerai</strong> dikecualikan: keduanya bersumber dari snapshot
Dukcapil Kab. Tanah Laut (semester 1/2026), karena laporan desa tidak memuatnya.
```
**Sesudah:**
```html
<strong>KIA</strong> &amp; <strong>Akta Cerai</strong> dikecualikan: keduanya bersumber dari snapshot
Dukcapil Kab. Tanah Laut, karena laporan desa tidak memuatnya.
<span class="src-official">* Sumber data adalah Data Konsolidasi Bersih (DKB) Semester I Tahun 2026</span>
```

### 3.4 `.src-note` di bawah chart Dokumen Kependudukan (titik 3)

**Sebelum (mode `dukcapil`):**
```html
<strong>Semua 6 indikator</strong> bersumber dari snapshot
<strong>Dukcapil Kab. Tanah Laut</strong> (semester 1/2026).
```
**Sesudah:** `…<strong>Dukcapil Kab. Tanah Laut</strong>.` + `<span class="src-official">…DKB…</span>`

**Sebelum (mode `laporan`/`campuran`, catatan KIA/Akta Cerai):**
```html
<strong>KIA</strong> &amp; <strong>Akta Cerai</strong> bersumber dari snapshot
<strong>Dukcapil Kab. Tanah Laut</strong> (semester 1/2026), bukan dari laporan desa seperti …
```
**Sesudah:** notasi `(semester 1/2026)` dihapus; kalimatnya tetap, label resmi
ditambahkan lewat `.src-official`.

### 3.5 Chip per-indikator `.src-chip` (titik 4)

Label chip yang **terlihat** tetap pendek (`Dukcapil`) — teks resmi terlalu panjang
untuk chip mini. Label resmi dipindah ke `title` (tooltip) chip untuk indikator
bersumber Dukcapil:

**Sebelum:** `title="Sumber data indikator ini"`
**Sesudah:** `title="* Sumber data adalah Data Konsolidasi Bersih (DKB) Semester I Tahun 2026"`

Chip non-Dukcapil tetap `title="Sumber data indikator ini"`. Label chip itu sendiri
tidak berubah.

### 3.6 Pill hero (titik 5, opsional)

Teks pill tetap pendek (`Sumber: Dukcapil` / `Dukcapil + laporan desa`); label resmi
masuk sebagai `title` **hanya** saat mode memuat Dukcapil (`dukcapil`/`campuran`).
Mode `laporan` (murni desa) tidak diberi `title` — tidak ada angka Dukcapil di situ.

### 3.7 Teks lama yang dihapus

Grep akhir `semester 1/2026`, `snapshot Dukcapil Kab. Tanah Laut (semester`, dan
`(semester <?=` pada `app/Views/**` + `PublicController.php` → **0 temuan**.
Semua notasi periode lama digantikan oleh label resmi. Tidak ada tempat yang
sengaja mempertahankan teks lama.

---

## 4. Bukti render

### 4.1 Perintah

```bash
php spark serve --host localhost --port 8087
curl -s "http://localhost:8087/?sumber=dukcapil" -o scratch/dkb_dukcapil.html
curl -s "http://localhost:8087/"                    -o scratch/dkb_default.html
curl -s "http://localhost:8087/?sumber=desa"        -o scratch/dkb_desa.html
```

Semua merespons **HTTP 200**. Server dimatikan setelah verifikasi (`taskkill`,
port 8087 CLOSED).

### 4.2 Ringkasan kemunculan label

| File | HTTP | Label resmi muncul | `semester 1/2026` (lama) | Error PHP |
|---|---|---|---|---|
| `dkb_dukcapil.html` | 200 | **9×** (2 `.src-official` + 1 hero title + 6 chip title) | 0 | 0 |
| `dkb_default.html` | 200 | **4×** (2 `.src-official` + 2 chip title KIA/Akta Cerai) | 0 | 0 |
| `dkb_desa.html` | 200 | **0×** (benar — murni laporan desa) | 0 | 0 |

Pencarian error: `Fatal error|PHP Warning|PHP Notice|PHP Deprecated|Uncaught|Whoops|Parse error`
→ **0 temuan** di ketiga file. (Kata `undefined`/`null` yang muncul di HTML hanya
token JavaScript pada blok KRS/leaflet — bukan error PHP.)

### 4.3 Kutipan HTML — `?sumber=dukcapil`

note-strip:
```html
<div class="note-strip">
    <i class="bi bi-info-circle-fill"></i>
    <p>
                        Data Wilayah ini bersumber dari snapshot Dukcapil Kab. Tanah Laut.
        <span class="src-official">* Sumber data adalah Data Konsolidasi Bersih (DKB) Semester I Tahun 2026</span>
                        Filter wilayah di atas menyesuaikan seluruh grafik dan tabel di bawah.
                        Periode laporan terbaru: <strong>Desember 2025</strong> (133 dari 135 desa; sisanya memakai laporan terakhirnya).
    </p>
</div>
```

`.src-note`:
```html
<div class="src-note">
    <i class="bi bi-tags-fill"></i>
    <p>
        <strong>Semua 6 indikator</strong> bersumber dari snapshot
        <strong>Dukcapil Kab. Tanah Laut</strong>.
        <span class="src-official">* Sumber data adalah Data Konsolidasi Bersih (DKB) Semester I Tahun 2026</span>
    </p>
</div>
```

Chip per-indikator (contoh KIA — keenam baris Dukcapil memuat title yang sama):
```html
<td class="dt-sub">KIA<span class="src-chip dukcapil" title="* Sumber data adalah Data Konsolidasi Bersih (DKB) Semester I Tahun 2026">Dukcapil</span></td>
```

Pill hero:
```html
<span class="status-pill muted" title="* Sumber data adalah Data Konsolidasi Bersih (DKB) Semester I Tahun 2026">
```

### 4.4 Kutipan HTML — default `/` (mode `laporan`; KIA & Akta Cerai dari Dukcapil)

note-strip:
```html
<p>
    Data Wilayah ini diturunkan dari laporan desa yang sudah diinput admin desa.
    <strong>KIA</strong> &amp; <strong>Akta Cerai</strong> dikecualikan: keduanya bersumber dari snapshot
    Dukcapil Kab. Tanah Laut, karena laporan desa tidak memuatnya.
    <span class="src-official">* Sumber data adalah Data Konsolidasi Bersih (DKB) Semester I Tahun 2026</span>
    …
</p>
```

`.src-note`:
```html
<div class="src-note">
    <i class="bi bi-tags-fill"></i>
    <p>
        <strong>KIA</strong> &amp; <strong>Akta Cerai</strong> bersumber dari snapshot
        <strong>Dukcapil Kab. Tanah Laut</strong>,
        bukan dari laporan desa seperti KTP-el, Akta Lahir, Akta Nikah &amp; KK Fisik.
        Laporan desa tidak memuat kedua indikator ini.
        <span class="src-official">* Sumber data adalah Data Konsolidasi Bersih (DKB) Semester I Tahun 2026</span>
    </p>
</div>
```

→ **Label resmi tetap muncul di catatan pengecualian KIA/Akta Cerai** meski mode
utama murni laporan desa. Ini sesuai harapan tugas.

### 4.5 Kutipan HTML — `?sumber=desa` (murni laporan desa)

```html
<p>
    Data Wilayah ini diturunkan dari laporan desa yang sudah diinput admin desa.
    Filter wilayah di atas menyesuaikan seluruh grafik dan tabel di bawah.
    …
</p>
```
- `.src-note` **tidak dirender** (KIA & Akta Cerai disembunyikan) → benar.
- Label resmi Dukcapil **0×** → benar.
- Chip hanya 4 baris `laporan desa` (KTP-el, Akta Lahir, Akta Nikah, KK Fisik).

### 4.6 Angka tidak berubah (bukti tak menyentuh agregasi)

Diambil dari HTML yang sama (nilai existing, tidak diubah tugas ini):

| Mode | Total Jiwa (stat card) |
|---|---|
| `?sumber=dukcapil` | 378.190 |
| default `/` | 375.583 |
| `?sumber=desa` | 375.583 |

---

## 5. `php -l`

```
No syntax errors detected in app/Models/ProfilDukcapilModel.php
No syntax errors detected in app/Controllers/PublicController.php
No syntax errors detected in app/Views/public/landingpage.php
No syntax errors detected in app/Views/templates/theme_toggle.php
```

---

## 6. ⚠️ CATATAN PENTING untuk keputusan user — label periode KIA & Akta Cerai

Ini **bukan** bug yang saya perbaiki; saya terapkan label persis seperti permintaan
user, tetapi perbedaan berikut perlu diketahui:

- Di file Excel sumber, label
  `* Sumber data adalah Data Konsolidasi Bersih (DKB) Semester I Tahun 2026`
  ada di **7 sheet**.
- Namun sheet **`KEPEMILIKAN DOK`** — yang menjadi sumber angka **KIA** dan
  **Akta Cerai** — justru berlabel **`DKB pertanggal 30092026`** (bukan Semester I).

**Konsekuensi:** untuk KIA & Akta Cerai, frasa "Semester I Tahun 2026" secara teknis
**kurang tepat** — sumbernya bertanggal 30-09-2026 (potret per tanggal), bukan
agregasi satu semester penuh.

Label yang diminta user tetap saya pasang di **semua** titik (termasuk catatan
KIA/Akta Cerai). Keputusan yang mungkin perlu diambil user:
1. Biarkan seragam (satu label di semua tempat) — sederhana, tapi tidak
   mencerminkan perbedaan asal sheet; atau
2. Tambahkan catatan tambahan khusus KIA/Akta Cerai, mis.
   "khusus KIA & Akta Cerai: DKB per 30-09-2026"; atau
3. Sediakan konstanta kedua (mis. `SUMBER_LABEL_DKB_TANGGAL`) untuk sheet
   `KEPEMILIKAN DOK`.

Saya **tidak** mengubah ini tanpa konfirmasi, sesuai instruksi.

---

## 7. Batasan & hal yang TIDAK disentuh

- Tidak ada perubahan angka, logika agregasi, atau perilaku filter.
- Tidak menyentuh `app/Commands/**`, `AGENTS.md`, `app/Filters/**`, migration, seeder.
- Tidak ada commit.
- File diubah: `app/Views/public/landingpage.php`,
  `app/Views/templates/theme_toggle.php`, `app/Controllers/PublicController.php`,
  `app/Models/ProfilDukcapilModel.php` (hanya tambah konstanta + helper label).
