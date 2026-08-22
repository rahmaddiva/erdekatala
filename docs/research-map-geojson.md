# Riset: Peta GeoJSON Polygon (Kecamatan / Desa) untuk Web CodeIgniter 4 — Kabupaten Tanah Laut

> Tanggal riset: riset web pada sesi ini. Konteks aplikasi: sistem informasi kependudukan Erdekatala
> (CI4 + PHP 8.1 + MySQL, AdminLTE 3 dark, Bootstrap 4, jQuery, ZingChart v2.9.16, Laragon localhost).
> Tujuan: menampilkan polygon kecamatan Tanah Laut + highlight per desa, responsif, dark theme, **tanpa API key mahal**.

---

## Ringkasan Eksekutif

- **Rekomendasi map library: Leaflet 1.9.x + basemap raster** untuk kebutuhan ini — paling ringan, tanpa kunci API, tanpa konflik dengan AdminLTE/Bootstrap 4/jQuery, dan pola "choropleth + highlight polygon" 100% idiomatic (mengikuti tutorial resmi Interactive Choropleth).
- **Basemap bebas key**: gunakan **Carto light_all / dark_all** (`https://{s}.basemaps.cartocdn.com/...`) sebagai pilihan utama karena ada varian **dark** (cocok AdminLTE dark) dan bebas API key. Fallback: OpenFreeMap (`https://tiles.openfreemap.org/styles/...`) untuk MapLibre/OpenLayers, atau OpenStreets MapCDN (`https://tile.openstreetmap.de/`) yang juga tanpa key. Hindari `tile.openstreetmap.org` untuk produksi diatur kebijakan Fair Use OSMF (bukan API key, tapi rate/beban — perlu nilai batas penggunaan wajar).
- **Peta polygon desa tidak wajib memakai basemap tiles sama sekali** — cara paling ringan & "tanpa key sepenuhnya" adalah menampilkan polygon GeoJSON di atas canvas kosong / peta kartografis sederhana, tanpa tiles (cukup untuk pemetaan batas wilayah). Opsi ini direkomendasikan bila hanya ingin menampilkan peta kecamatan/desa saja.
- **Sumber data GeoJSON terbaik**: data BIG (Badan Informasi Geospasial) via **`Alf-Anas/batas-administrasi-indonesia`** (GeoJSON kecamatan & desa, ada download per-berkas lewat `https://batas-admin.geoit.dev/`) atau **`dmxsan/indonesia-admin-boundaries`** (turunan shapefile resmi BIG, per-kabupaten). Lisensi data BIG bebas untuk penggunaan pemerintah/umum dengan atribusi, tapi **wajib verifikasi lisensi/ketentuan pemakaian** masing-masing repo.
- **Filter per kecamatan**: tidak semua sumber menyediakan satu berkas per kecamatan. Strategi paling praktis: ambil berkas **tingkat kabupaten (Tanah Laut)** lalu **filter (split) per kecamatan/desa** — dimungkinkan via script (mis. `geojson-split.py` dari repo dmxsan, atau manual) dan simpan sebagai aset statis di `public/assets/maps/`.
- **Integrasi CI4 ringan**: jangan pakai bundler/npm — pakai **CDN (unpkg/jsdelivr)** Leaflet CSS+JS sekali di template, inisialisasi map hanya di halaman yang butuh, hindari konflik CSS (perlu prefix/scope `.leaflet-*`), dan pastikan atribusi OSM/Basemap tetap tercantum. Service worker/offline tidak perlu.
- **Kompatibilitas browser & server**: Leaflet berjalan penuh di PHP-rendered view tanpa konflik; MapLibre GL JS v6 adalah **ESM-only** (butuh `<script type="module">`) dan WebGL — lebih kompleks di template server-rendered CI4 lama, jadi hanya disarankan bila butuh rendering vector basemap halus.

---

## 1. Library Map Frontend (Tanpa API Key / Gratis)

### 1.1 Perbandingan singkat

| Library | Basemap | API key? | Tiles OSM bebas? | Kebutuhan | Cocok untuk |
|---|---|---|---|---|---|
| **Leaflet 1.9.x** | Raster XYZ (OSM/Carto/MapCDN) | **Tidak** | Ya (dengan kebijakan fair use) | Sangat ringan, tanpa WebGL | Polygon kecamatan + choropleth desa, template server-rendered lama |
| **MapLibre GL JS (v5/v6)** | Vector tiles (OpenFreeMap) atau raster | **Tidak** (OpenFreeMap gratis) | Ya (raster sources) | WebGL, ESM-only di v6 | Vector basemap halus, 3D, label responsif |
| **MapLibre + raster tiles** | Raster (Carto) | Tidak | Ya | WebGL, ESM | Middle ground bila mau vektor + remplis tanpa vector server |
| **OpenLayers 10.x** | XYZ/Vector/Bing/OSM | Tidak | Ya | Lebih berat, lebih kuat | Fitur GIS canggih, banyak sumber layer |

### 1.2 Analisis per opsi

**Leaflet + OSM basemap (raster)** — rekomendasi utama untuk kebutuhan ini.
- Tidak butuh API key. Tile `tile.openstreetmap.org` gratis **namun** diatur oleh [OSM Tile Usage Policy](https://operations.osmfoundation.org/policies/tiles/): bukan API key/moneter, tetapi larangan penggunaan intensif/komersial besar, wajib atribusi, user-agent identitas, dan tingkat permintaan wajar. Untuk aplikasi pemerintah kabupaten berskala kecil-menengah, umumnya diterima bila mematuhi kebijakan. Lebih aman gunakan **Carto basemaps** (gratis, ada dark) atau **MapCDN/OsmAnd standalone** yang secara eksplisit mengizinkan penggunaan publik tanpa kunci.
- Pola choropleth + highlight per polygon persis seperti tutorial resmi "[Interactive Choropleth Map](https://leafletjs.com/examples/choropleth/)" — menggunakan `L.geoJSON`, `style`, `onEachFeature`, `mouseover/mouseout` highlight, `resetStyle`. Ini paling idiomatic untuk "polygon kecamatan + highlight per desa".

**MapLibre GL JS + OpenFreeMap (vector)** — opsi terbaik bila menginginkan tampilan vektor halus & label indah tanpa key.
- [OpenFreeMap](https://openfreemap.org/quick_start/) 100% gratis & tanpa API key, style `https://tiles.openfreemap.org/styles/liberty` (juga `bright`, `positron`, `fiord`, `dark`). Datanya dari OpenStreetMap (atribusi wajib).
- MapLibre v6 **ESM-only** (`<script type="module">`, `import` dari UNPKP) — integrasi di view server-rendered CI4 (non-module, non-bundler) lebih rumit; butuh penanganan worker (`setWorkerUrl`/CSP `worker-src`, `img-src data: blob: 'self'`). Referensi: [MapLibre Introduction/Quickstart](https://maplibre.org/maplibre-gl-js/docs/), [MapLibre GeoJSON example](https://maplibre.org/maplibre-gl-js/docs/examples/).
- Bila memilih MapLibre, lebih mudah pakai **v5** (`<script src="https://unpkg.com/maplibre-gl@5/dist/maplibre-gl.js">`) yang mendukung pemuatan script klasik non-module.
- Untuk polygon choropleth MapLibre gunakan `map.addSource(...)` (type `geojson`) + `map.addLayer(...)` (type `fill`), styling via `fill-color` / `fill-opacity`, interaksi via `map.on('click', ...)` / `mousemove` + `map.queryRenderedFeatures()`. Contoh resmi: [Add a GeoJSON polygon](https://maplibre.org/maplibre-gl-js/docs/examples/add-a-geojson-polygon/), [View local GeoJSON](https://maplibre.org/maplibre-gl-js/docs/examples/view-local-geojson/).

**MapLibre GL JS + raster tiles** — kompromi: MapLibre WebGL rendering dengan sumber raster (Carto/OSM) tanpa vector server.
- Mempertahankan tampilan vektor halus MapLibre sambil memakai basemap raster gratis. Namun kompleksitas integrasi ESM/worker tetap ada tanpa benefit penuh vector tiles. Umumnya tidak dianjurkan dibanding Leaflet untuk kasus ini.

**OpenLayers 10.x** — kuat & bebas key, tapi lebih berat dan verbositas API lebih tinggi; `ol/style` polygon + `ol/interaction/Select` untuk highlight antar desa, dan `olmaplibre`/`ol-mapbox-style` bisa dipakai dengan OpenFreeMap. Hanya dipilih bila kebutuhan GIS LANJUT (multi-proyeksi, editing) — di luar kebutuhan saat ini.
- Referensi: [OpenLayers Docs](https://openlayers.org/), [ol-mapbox-style/olmaplibre](https://openlayers.org/ol-mapbox-style/).

### 1.3 Rekomendasi konkret

> **Paling tepat: Leaflet 1.9.x + CarTo (dark, untuk AdminLTE dark) atau canvas-kosong tanpa tiles.**
> Alasan: lingan, tanpa key, tanpa WebGL/worker, pola choropleth resmi tepat untuk polygon+highlight per desa, dan integrasi dengan template server-rendered CI4 sangat rendah-friction.
> Cadangan tampilan lebih modern: MapLibre GL JS v5 + OpenFreeMap vector style (gratis).

Kode referensi (Leaflet — inisialisasi + polygon + highlight):

```html
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<div id="peta-wilayah" style="height: 460px; width: 100%"></div>
<script>
  const map = L.map('peta-wilayah').setView([-3.85, 114.7], 10); // Tanah Laut Kalimantan Selatan

  // Basemap dark (cocok AdminLTE dark) tanpa API key — CarTo
  L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
    attribution: '&copy; OpenStreetMap &copy; CARTO'
  }).addTo(map);

  // Polygon GeoJSON kecamatan/desa (ambit via fetch atau var global)
  fetch(base_url + 'assets/maps/tanah-laut-desa.geojson')
    .then(r => r.json())
    .then(function (geojson) {
      L.geoJSON(geojson, {
        style: function (f) {
          return { color: '#38bdf8', weight: 1.5, fillColor: '#0ea5e9', fillOpacity: 0.35 };
        },
        onEachFeature: function (feat, layer) {
          layer.on({
            mouseover(e) { e.target.setStyle({ fillOpacity: 0.75, weight: 3, color: '#fff' }); },
            mouseout(e)  { layer.resetStyle(e.target); },
            click()      { map.fitBounds(layer.getBounds()); alert(feat.properties.name); }
          });
        }
      }).addTo(map);
    });
</script>
```

---

## 2. Sumber Data GeoJSON Batas Administrasi Indonesia / Tanah Laut (Kode 63.01, Kalimantan Selatan)

### 2.1 Repositori / sumber konkret yang diketahui

| Sumber | Cakupan | Lisensi / ketentuan | Filter per kecamatan? |
|---|---|---|---|
| **`Alf-Anas/batas-administrasi-indonesia`** (GitHub) + download site `https://batas-admin.geoit.dev/` | Provinsi (38), Kabupaten/Kota (514), **Kecamatan**, **Desa/Kelurahan** — format SHP/KML/GeoJSON/GPKG | Update Jun 2023; file di split untuk GitHub. Ketentuan lisensi & sumber data perlu dicek di README/resiko (data BIG/OSM). | Site berisi **download per-berkas** — praktis untuk select langsung per kecamatan/desa bila file disediakan; untuk ketelitian perlu verifikasi folder. |
| **`dmxsan/indonesia-admin-boundaries`** | National → district (kabupaten/kota), turunan **shapefile resmi BIG** (geoportal.big.go.id) | Data BIG, "refer to their terms of use". Repo menggunakan **Git LFS**. | Data terbagi **per kabupaten/kota**, bukan per kecamatan — perlu filter (fold `scripts/geojson-split.py` membelah berdasarkan atribut, mis. `nama_kecamatan`). |
| **`mahendrayudha/indonesia-geojson`** | SHP + GeoJSON batas admin Indonesia | Biasanya subset/fork data publik; cek lisensi masing-masing berkas. | Biasanya berkas-gabungan; perlu split manual. |
| **`pararawendy/border-desa-indonesia-geojson`** | **Per desa** (83.322 desa), bersumber **Portal GIS Dukcapil** (Kemendagri) | Zip satu berkas berisi semua desa — perlu filter per kabupaten. Formatnya array custom (bukan FeatureCollection standar) sehingga **perlu parsing khusus** (lihat README). | Tidak ada berkas per kecamatan; perlu filter. |
| **`bachtiarpanjaitan/geojson-id`** | Level 1–4 **berbasis data BPS 2020** | Publik BPS | Per kabupaten; filter manual. |
| **Overpass / OpenStreetMap boundary** | Batas admin dari OSM (relation) | OpenStreetMap DB/ODbL | Harus query Overpass per wilayah; lebih sulit & bergantung runtime. |

### 2.2 Rekomendasi sumber & cara ambil data Tanah Laut

- **Pilihan terbaik**: ambil berkas **tingkat kabupaten Tanah Laut** dari **`dmxsan/indonesia-admin-boundaries`** (daribatan resmi BIG) atau dari **`Alf-Anas`** (ada GeoJSON langsung). Karena unit lapor aplikasi adalah **desa** dan hierarki **kecamatan → desa**, ambil dataset kecamatan Tanah Laut + dataset desa Tanah Laut (levels 3 & 4).
- **Filter per kecamatan**: kebanyakan sumber menyediakan **full (gabungan)** — jangan berasumsi ada berkas per kecamatan. **Strategi yang disarankan:**
  1. Ambil 1 berkas kecamatan provinsi atau kabupaten.
  2. **Split** menjadi per-kecamatan dengan script (mis. `scripts/geojson-split.py` repo dmxsan) berdasarkan atribut `kecamatan`/`nama_kecamatan`.
  3. Untuk desa: ambil dataset desa Tanah Laut, filter per `id_desa`/nama untuk membangun peta per desa.
  4. Simpan hasil sebagai **aset statis** di `public/assets/maps/{kecamatan}.geojson`, `public/assets/maps/tanah-laut-desa.geojson`.
- **Verifikasi lisensi**: data batas admin resmi (BIG) untuk penggunaan pemerintah umumnya gratis dengan atribusi, dan data BPS publik. **Namun** selalu baca lisensi/`README` repo dan "*terms of use*" BIG di `https://geoportal.big.go.id/` sebelum dipakai produksi, dan cantumkan atribusi sumber. Repo komunitas tidak selalu mencantumkan lisensi eksplisit — sebaiknya dokumentasikan asal-ubah.

### 2.3 Catatan kualitas & format

- Pastikan GeoJSON berisi `FeatureCollection` standar dengan properti `kecamatan` / `nama_desa` / kode wilayah untuk join dengan tabel `kecamatan` / `m_desa` aplikasi.
- Perhatikan referensi koordinat (WGS84 / EPSG:4326 default Leaflet). Jika shapefile sumber EPSG lain, konversi dulu ke 4326.
- Data Dukcapil (pararawendy) tidak berupa FeatureCollection standar — hindari kecuali sudah dikonversi.

---

## 3. Kompatibilitas dengan Arsitektur CI4 + AdminLTE 3 (dark), Bootstrap 4, jQuery, ZingChart

### 3.1 Integrasi Leaflet/Maplibre tanpa konflik

- **Asset placement disarankan**:
  - Letakkan CSS/JS Leaflet di **halaman yang memakai peta saja** (melalui `$this->section('styles')` / `$this->section('scripts')` pada view yang extends `templates/main`), bukan di template global — supaya tidak membebani seluruh aplikasi.
  - Statik: taruh aset di `public/assets/` lalu panggil `base_url('assets/...')`.
  - CDN Leaflet via unpkg/jsdelivr, atau unduh & simpan lokal (`public/assets/leaflet/`) agar bekerja ofline di Laragon meski internet lambat.
- **Konflik CSS**: Leaflet memperkenalkan kelas `.leaflet-*`. Warna/tema dark AdminLTE bisa menimpa latar map — kasih tinggi eksplisit (`#peta-wilayah { height: 460px }`) dan, bila perlu, scope tile/style. Tidak ada konflik langsung dengan Bootstrap karena Leaflet memakai namespace sendiri; hanya pastikan zona `z-index` popup Leaflet cukup tinggi dibanding komponen AdminLTE (Leaflet pakai `z-index` tinggi pada kontrol/popup — biasanya aman di dalam card).
- **jQuery & ZingChart**: Leaflet tidak butuh jQuery; berjalan bersih. ZingChart menempati DOM terpisah — tidak bentrok. Hindari deklarasi variabel global yang sama (mis. jangan nama variabel `map` bentrok).
- **Dark theme**: gunakan tile `dark_all` (CarTo) atau set warna fill polygon/line agar kontras dengan latar gelap; popup Leaflet default berlatar putih — override CSS untuk dark agar senada.
- **MapLibre**: bila dipakai, utamakan v5 (script klasik) + set `setWorkerUrl`/CSP. Karena CI4 tidak memakai bundler dan halaman bukan module, v6 (ESM) lebih sulit — **batasi solusi ini** kecuali siap dengan `<script type="module">` dan konfigurasi CSP.

### 3.2 Caching / offline / ukuran

- Service worker & tile caching **tidak diperlukan** (sesuai kebutuhan task). Yang penting ringan & tanpa key.
- Jaga ukuran GeoJSON kecil: split per kecamatan & **simplifikasi geometri** (mis. dengan `mapshaper`/`tippecanoe ` atau script dissolve) agar transfer ringan. Hindari memuat data desa seluruh kabupaten dalam satu berkas besar sekaligus bila bisa dipaginasi per kecamatan.
- Ideal: jika hanya menampilkan batas wilayah tanpa basemap, bisa render polygon di canvas kosong — paling ringan & benar-benar tanpa ketergantungan tile eksternal.

---

## Lampiran: URL dokumentasi resmi untuk dibaca worker

- Leaflet — Interactive Choropleth Map: https://leafletjs.com/examples/choropleth/
- Leaflet — GeoJSON tutorial: https://leafletjs.com/examples/geojson/
- Leaflet download (CSS/JS): https://leafletjs.com/download.html
- MapLibre GL JS — Introduction / Quickstart: https://maplibre.org/maplibre-gl-js/docs/
- MapLibre GL JS — Examples index: https://maplibre.org/maplibre-gl-js/docs/examples/
  - Add a GeoJSON polygon: https://maplibre.org/maplibre-gl-js/docs/examples/add-a-geojson-polygon/
  - View local GeoJSON: https://maplibre.org/maplibre-gl-js/docs/examples/view-local-geojson/
- OpenFreeMap — Quick Start (style URL tanpa API key): https://openfreemap.org/quick_start/
- OpenLayer — ol-mapbox-style / olmaplibre: https://openlayers.org/ol-mapbox-style/
- OSM Tile Usage Policy (fair use, bukan API key): https://operations.osmfoundation.org/policies/tiles/
- Baseline Carto (dark_all/light_all gratis): https://carto.com/developers/carto-vector-tiles/ (dan `basemaps.cartocdn.com`)
- Data:
  - Alf-Anas/batas-administrasi-indonesia: https://github.com/Alf-Anas/batas-administrasi-indonesia (+ download site https://batas-admin.geoit.dev/)
  - dmxsan/indonesia-admin-boundaries (BIG): https://github.com/dmxsan/indonesia-admin-boundaries
  - mahendrayudha/indonesia-geojson: https://github.com/mahendrayudha/indonesia-geojson
  - pararawendy/border-desa-indonesia-geojson (sumber GIS Dukcapil): https://github.com/pararawendy/border-desa-indonesia-geojson
  - BIG geoportal (terms of use sumber resmi): https://geoportal.big.go.id/
