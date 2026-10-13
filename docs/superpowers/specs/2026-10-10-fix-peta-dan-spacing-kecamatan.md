# Perbaikan Peta Tidak Muncul + Spacing Section "Profil Kecamatan"

**Tanggal:** 2026-10-13
**File yang diubah:** `app/Views/public/kecamatan.php` (satu-satunya file aplikasi)
**File scratch/laporan:** `scratch/mapfix/*`, dokumen ini

---

## 1. ITEM 1 — Bug "Peta Tidak Muncul" (Leaflet)

### Akar masalah (terverifikasi)

Blok `<script>` peta Leaflet **tidak pernah ditutup** dengan `</script>`. IIFE peta
diakhiri `})();`, lalu baris berikutnya langsung membuka `<script>` baru (blok
cursor-follower) tanpa `</script>` di antaranya. Parser HTML memperlakukan `<script>`
kedua sebagai **teks literal** di dalam script yang belum ditutup → `SyntaxError` →
seluruh script peta tidak dieksekusi → `#map-desa` kosong.

**Bukti — keseimbangan tag `<script>` (sebelum perbaikan):**

```
$ grep -o "<script" app/Views/public/kecamatan.php | wc -l   → 12
$ grep -o "</script>" app/Views/public/kecamatan.php | wc -l → 11   ← tidak seimbang
```

**Bukti — versi HEAD JUGA tidak menutup script peta (bug lama, bukan edit setengah jalan):**

```
$ git show HEAD:app/Views/public/kecamatan.php > scratch/mapfix/kecamatan_HEAD.php
HEAD:     <script count 12  |  </script count 11
WORKING:  <script count 12  |  </script count 11   (identik → bug pre-existing)
```

Kontek HEAD (baris 1875–1879) — `})();` langsung diikuti `<script>`:

```
1875:            // Klik poligon (popup) dan hover highlight ditangani langsung
1876:            // via bindPopup + event layer di onEachDesa.
1877:        })();
1878:    <script>          ← TIDAK ada </script> sebelum ini
1879:        (function() {
```

**Bukti — blok mentah HEAD gagal parse (`node --check`):**

```
$ node --check scratch/mapfix/HEAD_map_block.js
scratch/mapfix/HEAD_map_block.js:1
    <script>
    ^
SyntaxError: Unexpected token '<'
```

Ini membuktikan `<script>` kedua memang dibaca sebagai teks di dalam script peta.

### Perbaikan

Menyisipkan `</script>` tepat setelah `})();` penutup IIFE peta, sebelum `<script>`
cursor-follower. Struktur akhir: `...peta... })(); </script>` lalu
`<div class="cursor-follower">` / `<script> ...cursor... </script>`.

```diff
             // Klik poligon (popup) dan hover highlight ditangani langsung
             // via bindPopup + event layer di onEachDesa.
         })();
+    </script>
     <script>
         (function() {
             var f = document.getElementById('cursorFollower');
```

### Catatan pemeriksaan edit setengah jalan

Seluruh `git diff` file diperiksa (`scratch/mapfix/diff_working.patch`, 424 baris,
262 insertions / 19 deletions). Diff tersebut adalah **penambahan fitur yang utuh dan
konsisten** (penanda sumber data Dukcapil/Desa/Campuran, indikator turunan, catatan
provenance) — tidak ada kode terpotong atau kurung tak seimbang. Satu-satunya cacat
adalah `</script>` peta yang hilang, dan itu **ada di HEAD juga** (bukan akibat edit
belum selesai).

---

## 2. ITEM 2 — Spacing Section "Profil Kecamatan"

### Masalah yang ditemukan

Selain ritme vertikal yang rapat, section ini **full-bleed**: ia berada di antara
`</header>` dan `<main class="container">`, sehingga isinya mepet ke tepi viewport
(foto di `x=0`, kartu sampai `x=1280`) — tidak selaras dengan section lain yang ada
di dalam `.container`. Ini juga pre-existing (HEAD identik).

Pengukuran sebelum (viewport 1280px): foto `x=0`, kartu kanan `x=1280` (mepet tepi).

### Perubahan & alasan

| # | Perubahan | Alasan |
|---|-----------|--------|
| 1 | Bungkus isi section dengan `<div class="container">` | Section lain (Desa, Peta, dst.) berada di dalam `.container` (max-width 1240px + gutter). Full-bleed membuat Profil tampak "tidak rapi" & tak konsisten. Setelah: konten mulai `x=32`, selaras dengan section lain. |
| 2 | `padding-top: 2rem` → `3rem` | Samakan dengan `section.block` bawaan (`padding: 3rem 0`), memberi napas dari hero. |
| 3 | `.profil-card` padding `1.5rem 1.75rem` → `1.75rem 2rem` | Padding internal kartu lebih lega → konten tidak terasa sesak. |
| 4 | `.profil-desc` margin-bottom `1.25rem` → `1.5rem` | Jarak deskripsi ke grid field lebih jelas. |
| 5 | `.profil-grid` gap `0.9rem 1.5rem` → `1rem 1.5rem` | Jarak antar-baris field sedikit lebih longgar. |
| 6 | `.profil-field` padding `0.75rem 0.9rem` → `0.9rem 1rem` | Padding internal tiap field lebih seimbang. |
| 7 | `profil-grid-wrap` gap `1.5rem` → `2rem` | Gap foto ↔ kartu lebih lega. |

Konten/teks tidak diubah. Perubahan hanya CSS + satu pembungkus `.container`.

```diff
-    <section class="block" style="padding-top: 2rem;">
+    <section class="block" style="padding-top: 3rem;">
+        <div class="container">
         <div class="section-head">
...
-        <div class="profil-grid-wrap" style="...gap: 1.5rem; align-items: start;">
+        <div class="profil-grid-wrap" style="...gap: 2rem; align-items: start;">
...
             </div>
         </div>
+        </div>          <!-- tutup .container -->
         <style>
```

```diff
-            border-radius: 10px; padding: 1.5rem 1.75rem; box-shadow: var(--shadow-sm);
+            border-radius: 10px; padding: 1.75rem 2rem; box-shadow: var(--shadow-sm);
-            font-size: 0.95rem; line-height: 1.7; color: var(--ink-soft); margin: 0 0 1.25rem;
+            font-size: 0.95rem; line-height: 1.7; color: var(--ink-soft); margin: 0 0 1.5rem;
-            display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 0.9rem 1.5rem;
+            display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 1rem 1.5rem;
-            border-radius: 8px; padding: 0.75rem 0.9rem;
+            border-radius: 8px; padding: 0.9rem 1rem;
```

---

## 3. Verifikasi

### 3.1 Lint PHP

```
$ php -l app/Views/public/kecamatan.php
No syntax errors detected in app/Views/public/kecamatan.php
```

### 3.2 Keseimbangan tag `<script>` (sumber & hasil render)

```
Sumber:  <script 12 | </script 12   (seimbang)
Rendered (curl /batu-ampar): <script 16 | </script 16   (seimbang)
```

Di HTML hasil render, `</script>` peta muncul tepat sebelum blok cursor-follower:

```
2345:         })();
2346:     </script>
2347:     <script>                       ← blok cursor-follower
2348:         (function() {
```

### 3.3 Parse tiap blok `<script>` inline (`node --check`)

7 blok inline diekstrak dari HTML hasil render → semua lolos:

```
OK  inline_1.js len=0
OK  inline_2.js len=15228
OK  inline_3.js len=12940
OK  inline_4.js len=3889
OK  inline_5.js len=16168      ← blok peta
OK  inline_6.js len=807
OK  inline_7.js len=1220       ← blok cursor
inline blocks checked: 7 | all ok: True
```

### 3.4 Dev server & render

CI4 dev server dijalankan background di port 8094 (`php spark serve --host 127.0.0.1 --port 8094`).

```
$ curl -s http://127.0.0.1:8094/batu-ampar -o scratch/mapfix/kecamatan_8094.html
HTTP 200 size=180345
```

### 3.5 Endpoint peta

```
$ curl -s "http://127.0.0.1:8094/api/v1/map/desa?id_kecamatan=9&sumber=dukcapil"
HTTP 200 size=124180
type: FeatureCollection | features: 14 | keys: ['type','features','sumber','sumber_label']
```

### 3.6 Render headless (Playwright + Chrome) — peta benar-benar ter-render

```
#map-desa: ada, class "leaflet-container" (ter-render)
tiles: 18 | polygons (path.leaflet-interactive): 14 | zoom control: 1 | page errors: []
```

Ini membuktikan bukan hanya "tag seimbang", tapi peta Leaflet sungguh tergambar
(14 poligon desa = 14 features GeoJSON).

### 3.7 Spacing setelah perubahan (viewport 1280px)

```
photoLeft: 32   (selaras gutter .container; sebelumnya 0)
cardRight: 1248 (dalam batas container 1240px+gutter; sebelumnya 1280 mepet tepi)
headToGrid: 28  | photoCardGap: 32 | descToFields: 24 | fieldGapX: 24
map: { leaflet: true, tiles: 18, polys: 14 }
```

Screenshot: `scratch/mapfix/profil_section_after.png`.

---

## 4. Batasan & catatan

- **Hanya `app/Views/public/kecamatan.php`** yang diubah. `ApiController.php` dan
  model **tidak disentuh**.
- **Tidak ada bug di luar view.** Endpoint `/api/v1/map/desa` mengembalikan
  FeatureCollection valid berisi 14 features, dan peta ter-render penuh di browser
  headless. Tidak ada temuan di controller/model.
- **Kedua bug bersifat pre-existing** (ada di HEAD), bukan akibat edit setengah jalan.
  Diff working yang besar adalah penambahan fitur yang utuh.
- Verifikasi render memakai Chrome headless via Playwright dari cache `npx`
  (`NODE_PATH` diarahkan ke cache karena modul tidak terpasang lokal). Screenshot
  diambil pada viewport 1280×900.
- Server dev di port 8094 dimatikan setelah verifikasi selesai.
