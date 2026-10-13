# Riset: Bisakah tab YanKB & Dallap difilter per kecamatan?

**Tanggal:** 2026-10-09
**Status:** Riset read-only (tidak ada kode aplikasi yang diubah)
**Tujuan:** Menjawab apakah tab **Cakupan YanKB** (`Tabel1`), **KB Baru** (`Tabel21`), dan **Dallap** bisa dibatasi ke 1 kecamatan mengikuti `?kecamatan=`, dan bagaimana tepatnya.

**Kesimpulan singkat (spoiler):**

| Pertanyaan | Jawaban |
|---|---|
| API mendukung `kecamatanId` untuk YanKB? | **Ya** — tapi hasilnya berubah level: baris menjadi **faskes/desa**, bukan kecamatan |
| API mendukung `kecamatanId` untuk Dallap? | **Ya** — baris menjadi **desa/kelurahan** (kode 4 digit) |
| `fetchYankbReportInternal()` sudah punya param kecamatan? | **Belum** (3 param, tanpa `$bkkbnId`) |
| `fetchDallapMatrixInternal()` sudah punya param kecamatan? | **Sudah** (4 param, `$idKecamatanBkkbn` + `$namaKecamatan`) |
| Cache YanKB sudah mengandung kecamatan? | **Belum** — akan bentrok bila ditambah filter |
| Cache Dallap sudah mengandung kecamatan? | **Sudah** (`_kec<id>`) |
| `parseYankbHtml()` bisa dipakai untuk hasil per-kecamatan? | **Tidak** — ia mengembalikan `[]` karena header berubah jadi `KODE / NAMA FASKES` (bukan `KODE / KECAMATAN`) |
| Rekomendasi | **YanKB → filter di PHP** (murah, aman). **Dallap → bisa keduanya**, tapi tetap **filter di PHP** untuk keseragaman & hemat cache |

---

## 1. Signature & perilaku fungsi fetch

### 1.1 `fetchYankbReportInternal()` — TANPA dukungan kecamatan

`app/Controllers/BkkbnMirror.php:132`

```php
132:    private function fetchYankbReportInternal(string $reportName, int $tahun = 2026, int $bulan = 8): array
133:    {
134:        $cacheKey  = $reportName . '_' . $tahun . '_' . str_pad((string) $bulan, 2, '0', STR_PAD_LEFT);
135:        $cacheFile = WRITEPATH . 'cache/yankb_' . $cacheKey . '.html';
136:        $ttl = 1800; // 30 menit
```

- **Parameter:** `$reportName` (`Tabel1` | `Tabel21`), `$tahun`, `$bulan`. **Tidak ada** `$idKecamatanBkkbn`.
- **Perilaku:** selalu menarik level **kabupaten** (11 baris kecamatan + 1 baris `JUMLAH TOTAL`).
- Filter JSON dibangun *hardcode* tanpa `kecamatanId` — lihat baris 145-153 di bawah.

### 1.2 `fetchDallapMatrixInternal()` — SUDAH ada dukungan kecamatan

`app/Controllers/BkkbnMirror.php:199`

```php
199:    private function fetchDallapMatrixInternal(int $tahun = 2026, int $bulan = 8, ?int $idKecamatanBkkbn = null, ?string $namaKecamatan = null): array
200:    {
201:        $defs = $this->dallapReports();
...
207:        foreach ($defs as $reportName => $def) {
208:            $r = $this->fetchDallapReport($reportName, $def, $tahun, $bulan, $idKecamatanBkkbn);
...
215:            $parsed = $this->parseDallapRows($r['html'] ?? '', (int) $def['qtyCol'], $idKecamatanBkkbn !== null ? 'desa' : 'kec');
```

- `$idKecamatanBkkbn !== null` → fetch per-kecamatan **dan** parse level berubah `'kec'` → `'desa'`.
- `$namaKecamatan` hanya diteruskan ke output (`'nama_kecamatan' => $namaKecamatan`, baris 239); tidak memengaruhi URL.

### 1.3 `fetchDallapReport()` — tempat `kecamatanId` benar-benar dipakai

`app/Controllers/BkkbnMirror.php:245-268`

```php
245:    private function fetchDallapReport(string $reportName, array $def, int $tahun, int $bulan, ?int $idKecamatanBkkbn = null): array
246:    {
247:        $suffix = $def['monthly'] ? '_' . str_pad((string) $bulan, 2, '0', STR_PAD_LEFT) : '';
248:        $suffix .= $idKecamatanBkkbn ? '_kec' . $idKecamatanBkkbn : '';
249:        $cacheFile = WRITEPATH . 'cache/dallap_' . $reportName . '_' . $tahun . $suffix . '.html';
...
259:        $filter = ['tahun' => (string) $tahun, 'provinsiId' => '22', 'kabupatenId' => '322',
260:            'reportName' => $reportName, 'reportType' => 'html', 'reportCategory' => $def['category']];
261:        if ($def['monthly']) {
262:            $filter['bulan'] = $bulan;
263:        }
264:        if ($idKecamatanBkkbn !== null) {
265:            $filter['kecamatanId'] = (string) $idKecamatanBkkbn;
266:        }
267:        $url = 'https://siga-api-gateway.bkkbn.go.id/report-siga-dallap-ocp/viewReport'
268:            . '?filterPencarian=' . urlencode(json_encode($filter));
```

### 1.4 Konsumen saat ini

| Lokasi | Pemanggilan | Kecamatan? |
|---|---|---|
| `app/Controllers/PublicController.php:274-275` | `fetchYankbReportInternal('Tabel1'\|'Tabel21', 2026, $yankbBulan)` | Tidak |
| `app/Controllers/PublicController.php:279` | `fetchDallapMatrixInternal(2026, $yankbBulan)` | Tidak (kab) |
| `app/Controllers/PublicController.php:356` | `fetchDallapMatrixInternal(2026, $bulan, $bkkbnId, $namaKec[...])` | **Ya** (AJAX drill-down) |
| `app/Controllers/ApiController.php:324` | `fetchDallapMatrixInternal($tahun, $bulan)` | Tidak |
| `app/Controllers/ApiController.php:350` | `fetchYankbReportInternal($reportName, $tahun, $bulan)` | Tidak |
| `app/Commands/BkkbnWarmup.php:142,158` | via `BkkbnMirrorService` | Tidak |
| `app/Libraries/BkkbnMirrorService.php:38,44` | wrapper publik — **signature-nya juga belum punya param kecamatan** | Tidak |

---

## 2. URL API yang dibentuk

### 2.1 YanKB — `report-siga-yankb-ocp/viewReport`

Kode pembentuk (`BkkbnMirror.php:145-155`):

```php
145:        $filter = json_encode([
146:            'tahun'          => (string) $tahun,
147:            'bulan'          => $bulan,
148:            'provinsiId'     => '22',
149:            'kabupatenId'    => '322',
150:            'reportName'     => $reportName,
151:            'reportType'     => 'html',
152:            'reportCategory' => 'YanKB-Bulanan-Kec',
153:        ]);
154:        $url = 'https://siga-api-gateway.bkkbn.go.id/report-siga-yankb-ocp/viewReport'
155:            . '?filterPencarian=' . urlencode($filter);
```

`filterPencarian` **decoded** — Tabel1, Agustus 2026, level kabupaten:

```json
{"tahun":"2026","bulan":8,"provinsiId":"22","kabupatenId":"322","reportName":"Tabel1","reportType":"html","reportCategory":"YanKB-Bulanan-Kec"}
```

URL lengkap (encoded):

```
https://siga-api-gateway.bkkbn.go.id/report-siga-yankb-ocp/viewReport?filterPencarian=%7B%22tahun%22%3A%222026%22%2C%22bulan%22%3A8%2C%22provinsiId%22%3A%2222%22%2C%22kabupatenId%22%3A%22322%22%2C%22reportName%22%3A%22Tabel1%22%2C%22reportType%22%3A%22html%22%2C%22reportCategory%22%3A%22YanKB-Bulanan-Kec%22%7D
```

Tabel21 sama, hanya `reportName` = `"Tabel21"`. `reportCategory` tetap `YanKB-Bulanan-Kec` untuk keduanya.

### 2.2 Dallap — `report-siga-dallap-ocp/viewReport`

Delapan report terdaftar (`BkkbnMirror.php:180-189`):

| reportName | label | qtyCol | category | monthly |
|---|---|---|---|---|
| Tabel2A  | PKB        | 3 | Dallap-Tahunan | tidak |
| Tabel11A | UPPKA      | 4 | Dallap-Tahunan | tidak |
| Tabel3A  | BKB        | 3 | Dallap-Bulanan | **ya** |
| Tabel10A | BKL        | 4 | Dallap-Tahunan | tidak |
| Tabel9B  | BKR        | 3 | Dallap-Tahunan | tidak |
| Tabel12A | PIK Remaja | 3 | Dallap-Tahunan | tidak |
| Tabel10  | PUS (SIGA) | 3 | Dallap-Bulanan | **ya** |
| Tabel11  | KB Aktif (SIGA) | 3 | Dallap-Bulanan | **ya** |

`filterPencarian` decoded — level kabupaten (tanpa `kecamatanId`), contoh Tabel10A:

```json
{"tahun":"2026","provinsiId":"22","kabupatenId":"322","reportName":"Tabel10A","reportType":"html","reportCategory":"Dallap-Tahunan"}
```

Untuk report bulanan (Tabel3A), `bulan` disisipkan **setelah** `tahun` (urutan kunci: tahun, bulan, provinsiId, …):

```json
{"tahun":"2026","bulan":8,"provinsiId":"22","kabupatenId":"322","reportName":"Tabel3A","reportType":"html","reportCategory":"Dallap-Bulanan"}
```

Dengan `kecamatanId` (Bumi Makmur = 6624):

```json
{"tahun":"2026","provinsiId":"22","kabupatenId":"322","kecamatanId":"6624","reportName":"Tabel10A","reportType":"html","reportCategory":"Dallap-Tahunan"}
```

---

## 3. Uji langsung ke API — apakah `kecamatanId` benar-benar menyaring?

**Waktu uji:** 2026-10-09 ~22:49-22:52 WITA/dari mesin dev. Semua request **HTTP 200**, durasi **1,5–6,8 detik** (API sehat, tidak timeout).
Perintah: `curl -sS -G --max-time 120 --data-urlencode 'filterPencarian={...}' <endpoint> -o <file>`
Respons mentah disimpan di `scratch/bkkbn/`.

| File | Endpoint | `kecamatanId` | HTTP | Waktu | Ukuran |
|---|---|---|---|---|---|
| `yankb_Tabel1_2026_08_kab.html` | yankb / Tabel1, bulan 8 | — | 200 | 1,50s | 84.915 B |
| `yankb_Tabel1_2026_08_kec6624.html` | yankb / Tabel1, bulan 8 | 6624 | 200 | 2,78s | 61.749 B |
| `yankb_Tabel21_2026_08_kec6624.html` | yankb / Tabel21, bulan 8 | 6624 | 200 | 6,77s | 68.188 B |
| `dallap_Tabel10A_2026_kab.html` | dallap / Tabel10A | — | 200 | 5,78s | 55.685 B |
| `dallap_Tabel10A_2026_kec6624.html` | dallap / Tabel10A | 6624 | 200 | 6,00s | 55.964 B |
| `dallap_Tabel3A_2026_08_kec6624.html` | dallap / Tabel3A, bulan 8 | 6624 | 200 | 2,18s | 44.207 B |

### 3.1 Hasil: header laporan berubah (bukti tersaring)

Header judul laporan pada respons:

```
yankb_Tabel1_2026_08_kab.html        → Prov : KALIMANTAN SELATAN | Kab : TANAH LAUT   (tanpa baris "Kec :")
yankb_Tabel1_2026_08_kec6624.html    → Prov : KALIMANTAN SELATAN | Kab : TANAH LAUT | Kec : BUMI MAKMUR
yankb_Tabel21_2026_08_kec6624.html   → ... | Kec : BUMI MAKMUR
dallap_Tabel10A_2026_kab.html        → ... | Kab : TANAH LAUT   (tanpa "Kec :")
dallap_Tabel10A_2026_kec6624.html    → ... | Kec : BUMI MAKMUR
dallap_Tabel3A_2026_08_kec6624.html  → ... | Kec : BUMI MAKMUR
```

Nama kecamatan yang muncul di body:

```
yankb_Tabel1_2026_08_kab.html        → TAKISUNG, JORONG, PELAIHARI, KURAU, BATI BATI, PANYIPATAN,
                                       KINTAP, TAMBANG ULANG, BATU AMPAR, BAJUIN, BUMI MAKMUR  (11)
yankb_Tabel1_2026_08_kec6624.html    → BUMI MAKMUR  (1)  ✅ tersaring
yankb_Tabel21_2026_08_kec6624.html   → BUMI MAKMUR  (1)  ✅ tersaring
dallap_Tabel10A_2026_kab.html        → 11 kecamatan  (semua kabupaten)
dallap_Tabel10A_2026_kec6624.html    → BUMI MAKMUR  (1)  ✅ tersaring
dallap_Tabel3A_2026_08_kec6624.html  → BUMI MAKMUR  (1)  ✅ tersaring
```

**Kesimpulan 3: API SUNGUUH menyaring per kecamatan.** Respons bukan "seluruh kabupaten dengan label berbeda" — baris datanya benar-benar hanya milik kecamatan itu.

### 3.2 TAPI — level baris ikut turun (temuan paling penting)

Struktur header tabel berubah saat `kecamatanId` dikirim:

| Report | Header KAB | Header + `kecamatanId` |
|---|---|---|
| YanKB Tabel1  | `KODE \| KECAMATAN \| KELURAHAN \| …` | `KODE \| **`NAMA FASKES`** \| …` |
| YanKB Tabel21 | `KODE \| KECAMATAN \| JUMLAH PESERTA KB BARU …` | `KODE \| **`KELURAHAN`** \| JUMLAH PESERTA KB BARU …` |
| Dallap Tabel10A | `KODE \| KECAMATAN \| …` | `KODE \| **`KELURAHAN`** \| …` |

Cuplikan data `yankb_Tabel1_2026_08_kec6624.html` (baris = **faskes**, kode 7-10 digit):

```
Kec : BUMI MAKMUR
6301033   | PUSKESMAS BUMI MAKMUR
630103302 | Poskesdes Handil Gayam
630103303 | Poskesdes Pantai Harapan
630103304 | Poskesdes Handil Maluka
630103305 | Poskesdes Sungai Rasau
630103306 | Pustu Handil Suruk
630103307 | Poskesdes Bumi Harapan
630103308 | Poskesdes Handil Birayang Atas
630103309 | Poskesdes Handil Birayang Bawah
630103310 | Poskesdes Handil Labuan Amas
630103312 | Pustu Handil Babirik
630103314 | Puskesdes Kurau Utara
6301084   | RS H. Darlan Ismail
JUMLAH TOTAL | 2 | 2 | 11 | 11 | 0 | 0 | 0 | 0 | 0 | 0
```

Cuplikan data `yankb_Tabel21_2026_08_kec6624.html` (baris = **faskes**):

```
6301033   | PUSKESMAS BUMI MAKMUR              | 35 | 0 | 5 | 1
630103314 | Puskesdes Kurau Utara              | 17 | 1 | 1 | 7
...
JUMLAH TOTAL | 137 | 9 | 19 | 32 | 20
```

Perhatikan: **`JUMLAH TOTAL` Tabel21 untuk kec6624 = 137** — angka ini **identik** dengan baris `11 | BUMI MAKMUR | 137 | 9 | 19 | 32` di laporan kabupaten. Jadi data per-kecamatan konsisten dengan baris kecamatan di laporan kabupaten — hanya saja sudah dipecah per faskes/desa.

Cuplikan `dallap_Tabel10A_2026_kec6624.html` (baris = **desa**, kode 4 digit — kolom jumlah ke-4):

```
2001 | HANDIL BABIRIK        | qty=1
2002 | KURAU UTARA           | qty=1
2003 | BUMI HARAPAN          | qty=1
2004 | SUNGAI RASAU          | qty=1
2005 | PANTAI HARAPAN        | qty=0
2006 | HANDIL SURUK          | qty=1
2007 | HANDIL GAYAM          | qty=1
2008 | HANDIL BIRAYANG ATAS  | qty=0
2009 | HANDIL BIRAYANG BAWAH | qty=1
2010 | HANDIL MALUKA         | qty=1
2011 | HANDIL LABUAN AMAS    | qty=1
Jumlah Total | 11 | 9 | 7 | 2 | 3 | 7 | 0 | 1 | 0 | 9 | 0
```

Bandingkan baris `11 | BUMI MAKMUR | 11 | 9 | 7 | 2 | 3 | 7 | 0 | 1 | 0 | 9 | 0` di `dallap_Tabel10A_2026_kab.html` — **identik**.

Cross-check kedua untuk Dallap bulanan (`Tabel3A`, qtyCol 3):
- Baris kab: `11 | BUMI MAKMUR | qty=10`
- Penjumlahan 11 baris desa di `dallap_Tabel3A_2026_08_kec6624.html` = **10** ✅ cocok.

> **Implikasi:** mengirim `kecamatanId` **bukan** memberi "1 baris kecamatan", melainkan **rincian per desa/faskes di kecamatan itu**. Ini bukan yang diinginkan tab "per kecamatan" — tab butuh **1 baris kecamatan**.

---

## 4. Cache — apakah kuncinya sudah memuat kecamatan?

### 4.1 YanKB — **BELUM**, akan bentrok

`app/Controllers/BkkbnMirror.php:134-135`

```php
134:        $cacheKey  = $reportName . '_' . $tahun . '_' . str_pad((string) $bulan, 2, '0', STR_PAD_LEFT);
135:        $cacheFile = WRITEPATH . 'cache/yankb_' . $cacheKey . '.html';
```

Nama file: **`yankb_Tabel1_2026_08.html`** (dan `yankb_Tabel21_2026_08.html`).
**Tidak ada komponen kecamatan.** Jika filter kecamatan ditambahkan tanpa mengubah `$cacheKey`, semua kecamatan akan **berbagi satu file cache kabupaten** → hasil filter salah/tidak konsisten (cache poisoning silang antar kecamatan).

Bukti isi `writable/cache/` saat ini — hanya ada file kabupaten:

```
yankb_Tabel1_2026_03.html  yankb_Tabel1_2026_06.html  yankb_Tabel1_2026_08.html
yankb_Tabel1_2026_09.html  yankb_Tabel1_2026_10.html  yankb_Tabel1_2026_11.html
yankb_Tabel21_2026_03.html … yankb_Tabel21_2026_11.html
```

### 4.2 Dallap — **SUDAH**, ada sufiks `_kec<id>`

`app/Controllers/BkkbnMirror.php:247-249`

```php
247:        $suffix = $def['monthly'] ? '_' . str_pad((string) $bulan, 2, '0', STR_PAD_LEFT) : '';
248:        $suffix .= $idKecamatanBkkbn ? '_kec' . $idKecamatanBkkbn : '';
249:        $cacheFile = WRITEPATH . 'cache/dallap_' . $reportName . '_' . $tahun . $suffix . '.html';
```

Nama file: `dallap_Tabel10A_2026.html` (kab) vs `dallap_Tabel10A_2026_kec6624.html` (per kecamatan).
Bukti di `writable/cache/` — sudah ada file per-kecamatan yang pernah dibuat (kec6625 = Bajuin):

```
dallap_Tabel10A_2026.html          dallap_Tabel10A_2026_kec6625.html
dallap_Tabel10_2026_08.html        dallap_Tabel10_2026_08_kec6625.html
dallap_Tabel11A_2026.html          dallap_Tabel11A_2026_kec6625.html
dallap_Tabel11_2026_08.html        dallap_Tabel11_2026_08_kec6625.html
dallap_Tabel12A_2026.html          dallap_Tabel12A_2026_kec6625.html
dallap_Tabel2A_2026.html           dallap_Tabel2A_2026_kec6625.html
dallap_Tabel3A_2026_08.html        dallap_Tabel3A_2026_08_kec6625.html
dallap_Tabel9B_2026.html           dallap_Tabel9B_2026_kec6625.html
```

**Catatan urutan sufiks:** `$suffix` = `_<bulan>` lalu `_kec<id>` → `dallap_Tabel3A_2026_08_kec6625.html`. Untuk report tahunan tanpa bulan → `dallap_Tabel10A_2026_kec6625.html`. Tidak ada benturan.

### 4.3 KRS (pembanding) — sudah pakai `_kab` / `_kec<id>`

`app/Controllers/BkkbnMirror.php:53-54`

```php
53:        $cacheKey  = $tahun . ($idKecamatanBkkbn ? '_kec' . $idKecamatanBkkbn : '_kab');
54:        $cacheFile = WRITEPATH . 'cache/krs_monitoring_' . $cacheKey . '.json';
```

Pola inilah yang sebaiknya ditiru bila YanKB akhirnya perlu cache per kecamatan.

---

## 5. Struktur HTML & `parseYankbHtml()` — bisakah difilter di PHP?

### 5.1 YA — `parseYankbHtml()` menghasilkan 1 baris per kecamatan

Output fungsi (`BkkbnMirror.php:488-492`): `['headers' => [...4 baris...], 'rows' => [['cells'=>[['t'=>..,'cs'=>..]], 'total'=>bool]], 'seps' => [...]]`.

Hasil parse aktual atas `writable/cache/yankb_Tabel1_2026_08.html` (dijalankan dengan replika fungsi persis baris-demi-baris):

```
nRows: 12
  [00]        kode=01         nama=TAKISUNG
  [01]        kode=02         nama=JORONG
  [02]        kode=03         nama=PELAIHARI
  [03]        kode=04         nama=KURAU
  [04]        kode=05         nama=BATI BATI
  [05]        kode=06         nama=PANYIPATAN
  [06]        kode=07         nama=KINTAP
  [07]        kode=08         nama=TAMBANG ULANG
  [08]        kode=09         nama=BATU AMPAR
  [09]        kode=10         nama=BAJUIN
  [10]        kode=11         nama=BUMI MAKMUR
  [11]*TOTAL* kode=JUMLAH TOTAL nama=79
```

Header (4 baris) dan sel baris TAKISUNG:

```
hdr0: KODE(cs1,rs3) | KECAMATAN(cs1,rs3) | KELURAHAN(cs3,rs2) | TEMPAT PELAYANAN KB PEMERINTAH(cs6,rs1) | TEMPAT PELAYANAN KB SWASTA(cs9,rs1)
hdr1: FASKES PEMERINTAH(cs3,rs1) | JARINGAN(cs3,rs1) | FASKES SWASTA(cs3,rs1) | PRAKTIK MANDIRI BIDAN(SETARA FASKES)(cs3,rs1) | PRAKTIK MANDIRI BIDANJEJARING(cs3,rs1)
hdr2: YANGADA | YANGLAPOR | % | YANGADA | YANGLAPOR | % | (×6 grup)
hdr3: 1 | 2 | 3 | 4 | 5=4/3*100 | 6 | … | 20=19/18*100

row TAKISUNG cells:
  c0: 01 (kode)   c1: TAKISUNG (nama)   c2..c19: angka
```

Baris BUMI MAKMUR (yang akan dipakai untuk filter): `c0=11, c1=BUMI MAKMUR, c2=11, c3=11, c4=100.00, c5=2, …`

**Kesimpulan:** struktur baris = `[kode(2 digit), NAMA KECAMATAN, ...angka]`. Sangat mudah difilter di PHP.

### 5.2 Cara filter di PHP

```php
// Setelah: $yankbData = $this->parseYankbHtml($yankb['html'] ?? '');
if ($filterKecNama !== null) {
    $target = strtoupper(trim(str_replace(['_', '-'], ' ', $filterKecNama))); // sama seperti resolveBkkbnKecamatanId()
    $target = preg_replace('/\s+/', ' ', $target);

    $kept = [];
    foreach (($yankbData['rows'] ?? []) as $r) {
        $nama = strtoupper(trim($r['cells'][1]['t'] ?? ''));
        if ($nama === $target || !empty($r['total'])) {
            $kept[] = $r;   // baris TOTAL sengaja dipertahankan → jadi total kecamatan
        }
    }
    $yankbData['rows'] = $kept;
}
```

Catatan:
- Bandingkan **cell index 1** (`NAMA KECAMATAN`), bukan index 0 (`KODE` = `01`..`11` — urut alfabet, bukan id BKKBN).
- Normalisasi harus sama dengan `resolveBkkbnKecamatanId()` (`BkkbnMirror.php:41-42`) supaya `Bati-Bati` (DB) cocok dengan `BATI BATI` (BKKBN).
- Baris `JUMLAH TOTAL` **tidak ikut tersaring** kalau tidak di-keep → tampil angka seluruh kabupaten di bawah 1 baris kecamatan. Untuk konsistensi, keep baris total (anganya sudah = kecamatan itu karena cuma 1 baris non-total yang tersisa) **atau** sembunyikan. Rekomendasi: **keep**, dan ubah labelnya di view (lihat §7).
- View sudah menghindari baris total saat menghitung "semua nol" (`landingpage.php:2291`: `if (!empty($r['total'])) continue;`) — aman.

### 5.3 PENTING: `parseYankbHtml()` TIDAK bisa dipakai untuk respons per-kecamatan

Diverifikasi dengan menjalankan replika persis `parseYankbHtml()` atas 4 file:

```
Tabel1 KAB cache  -> rows=13   ✅
Tabel1 KEC live   -> rows=0    ❌ (header KODE/KECAMATAN tidak ada → fungsi return [])
Tabel21 KAB cache -> rows=13   ✅
Tabel21 KEC live  -> rows=0    ❌
```

Penyebab — `BkkbnMirror.php:396-408`:

```php
396:        // Cari index baris 'KODE' → awal header
397:        $headerStart = -1;
398:        foreach ($allRows as $i => $row) {
399:            $merged = implode('', array_map(fn($c) => $c['t'], $row));
400:            $clean = preg_replace('/\s+/', '', $merged);
401:            if (strpos($clean, 'KODE') !== false && strpos($clean, 'KECAMATAN') !== false) {
402:                $headerStart = $i;
403:                break;
404:            }
405:        }
406:        if ($headerStart < 0) {
407:            return [];
408:        }
```

Respons `+kecamatanId` berheader `KODE | NAMA FASKES` (Tabel1) atau `KODE | KELURAHAN` (Tabel21) — tidak mengandung `KECAMATAN` → `$headerStart = -1` → `return []`.
View then falls back ke `<iframe srcdoc>` (`landingpage.php:2300-2306`) — tabel rapi hilang.

> Jadi kalauWorker memilih "filter lewat API" untuk YanKB, **wajib** juga menambah parser baru (mis. `parseYankbHtmlKec()` yang mencari `KODE` + (`NAMA FASKES`|`KELURAHAN`)), bukan sekadar menambah `kecamatanId`.

---

## 6. Dallap per kecamatan — pola `getDallapDesa()`

`app/Controllers/PublicController.php:340-364`

```php
340:    public function getDallapDesa(string $kecSlug)
341:    {
342:        $namaKec = $this->kecamatanModel
343:            ->where('LOWER(REPLACE(nama_kecamatan, " ", "-"))', strtolower($kecSlug))
344:            ->first();
345:        if (!$namaKec) {
346:            return $this->response->setStatusCode(404)->setJSON(['error' => 'kecamatan tidak dikenal', 'rows' => []]);
347:        }
348:        $bkkbnId = $this->resolveBkkbnKecamatanId($namaKec['nama_kecamatan']);
349:        if (!$bkkbnId) {
350:            return $this->response->setStatusCode(400)->setJSON(['error' => 'kecamatan tidak punya id BKKBN', 'rows' => []]);
351:        }
352:        $bulan = (int) ($this->request->getGet('bulan') ?? 8);
353:        if ($bulan < 1 || $bulan > 12) {
354:            $bulan = 8;
355:        }
356:        $m = $this->fetchDallapMatrixInternal(2026, $bulan, $bkkbnId, $namaKec['nama_kecamatan']);
357:        return $this->response->setJSON([
358:            'nama_kecamatan' => $namaKec['nama_kecamatan'],
359:            'defs'           => $m['defs'],
360:            'rows'           => $m['rows'],
361:            'updated_at'     => $m['updated_at'],
362:            'error'          => $m['error'],
363:        ]);
364:    }
```

**Apakah pola ini bisa dipakai untuk membatasi tab Dallap ke 1 kecamatan?**

**Bisa secara mekanis, TAPI salah secara semantik.** `fetchDallapMatrixInternal(..., $bkkbnId, ...)` mengembalikan **baris per desa** (karena `parseDallapRows(..., 'desa')` — lihat baris 215), sedangkan tab Dallap di landing page menampilkan **1 baris per kecamatan** dengan judul "Rekap Program Bangga Kencana — Kabupaten Tanah Laut" dan kolom `Nama Kecamatan` (`landingpage.php:2391,2400`).

Jadi kalau tab Dallap memanggil dengan `$bkkbnId`:
- Tabel akan berisi 11 baris **desa** Bumi Makmur, bukan 1 baris "Bumi Makmur".
- Header tetap "Nama Kecamatan" → menyesatkan.
- Tombol expand `▶` per baris (`landingpage.php:2410-2419`) akan mengembang ke desa-dari-desa → rusak.

**Alternatif yang benar:** panggil tetap level kabupaten, lalu **saring `$dallapRows` di PHP** berdasarkan `$row['nama']` — persis seperti pola `buildKrsTree()` (`PublicController.php:393-405`) yang menyaring `namaDaerah`. Struktur `$dallap['rows']` adalah `[['nama'=>'TAKISUNG','kode'=>1,'qty'=>[reportName=>int|null]]]` (`BkkbnMirror.php:224-232`) — sudah ada `nama` dan `kode`, tinggal filter:

```php
$dallapRows = array_values(array_filter($dallap['rows'], fn($r) =>
    $dallapNorm($r['nama']) === $dallapNorm($filterKecNama)));
```

`$dallapNorm()` sudah ada di view (`landingpage.php:2368-2371`) — tinggal dipakai/dipindah ke controller.

---

## 7. Rekomendasi implementasi

### 7.1 Keputusan: **filter di PHP setelah fetch** untuk KEDUA tab

| | Lewat parameter API (`kecamatanId`) | Filter di PHP setelah fetch |
|---|---|---|
| Akurasi | Sama-sama akurat (terbukti §3.2 angka desa = angka baris kecamatan) | Sama — baris kecamatan adalah sumber yang sama |
| Bentuk hasil | **Salah untuk tab**: baris faskes/desa, bukan kecamatan | **Benar**: 1 baris kecamatan |
| Parser | Butuh **parser baru** (`parseYankbHtml()` return `[]`, §5.3); Dallap butuh agregasi desa→kecamatan | Parser existing dipakai apa adanya |
| Biaya cache | 11 kecamatan × 2 report (YanKB) + 11 × 8 report (Dallap) = **110 file cache baru** per bulan, masing-masing 1 fetch lambat (2-7s) | **Nol** file tambahan — cache kabupaten yang sudah ada dipakai bersama |
| Risiko | Cache YanKB bentrok (§4.1); `BkkbnWarmup` harus diperluas; waktu muat halaman meledak kalau cache dingin (8 report × 6s = ~48s) | Tidak ada perubahan cache sama sekali |
| Konsistensi dengan tab KRS | Berbeda — KRS justru difilter di PHP (`buildKrsTree()` baris 393-405) | **Sama** dengan KRS |

**Kesimpulan:** untuk kasus "tab YanKB & Dallap mengikuti `?kecamatan=`" — yang diminta adalah **1 baris kecamatan**, bukan rincian desa — **filter di PHP adalah pilihan yang benar, bukan sekadar yang lebih murah.** Parameter API berguna untuk drill-down per-desa (sudah dipakai `getDallapDesa()`), bukan untuk mempersempit tab.

### 7.2 Perubahan konkret

#### A. `app/Controllers/PublicController.php` — `landingpage()`

Tambahkan helper normalisasi nama (atau reuse yang sudah ada di view) lalu saring setelah fetch. Tempat penyisipan: **setelah baris 279** (fetch), **sebelum** `$data = [...]` (baris 285).

```php
// Baris 279 saat ini:
$dallap = $this->fetchDallapMatrixInternal(2026, $yankbBulan);

// >>> TAMBAHKAN <<<
// Normalisasi nama wilayah: 'Bati-Bati' (DB) == 'BATI BATI' (BKKBN).
// Harus identik dengan resolveBkkbnKecamatanId() agar cocok dengan label SIGA.
$normKec = static function (string $s): string {
    $s = strtoupper(trim(str_replace(['_', '-'], ' ', $s)));
    return preg_replace('/\s+/', ' ', $s) ?? $s;
};
$targetKec = $filter_kec_nama !== null ? $normKec($filter_kec_nama) : null;

// Saring 1 baris kecamatan dari matriks Dallap (rows = ['nama','kode','qty']).
if ($targetKec !== null && !empty($dallap['rows'])) {
    $dallap['rows'] = array_values(array_filter(
        $dallap['rows'],
        fn($r) => $normKec((string) $r['nama']) === $targetKec
    ));
}

// Saring 1 baris kecamatan dari tabel YanKB (rows[i]['cells'][1] = NAMA KECAMATAN).
// Baris 'total' dipertahankan supaya footer tabel tetap ada (angkanya kini = kecamatan tsb).
$saringYankb = static function (array $parsed, ?string $target) use ($normKec): array {
    if ($target === null || empty($parsed['rows'])) {
        return $parsed;
    }
    $kept = [];
    foreach ($parsed['rows'] as $r) {
        $nama = $normKec((string) ($r['cells'][1]['t'] ?? ''));
        if ($nama === $target || !empty($r['total'])) {
            $kept[] = $r;
        }
    }
    $parsed['rows'] = $kept;
    return $parsed;
};
```

Lalu pada blok `$data` (baris 320-321) bungkus hasil parse:

```php
// Baris 320-321 saat ini:
'yankbData'       => $this->parseYankbHtml($yankb['html'] ?? ''),
'yankb21Data'     => $this->parseYankbHtml($yankb21['html'] ?? ''),

// >>> MENJADI <<<
'yankbData'       => $saringYankb($this->parseYankbHtml($yankb['html'] ?? ''), $targetKec),
'yankb21Data'     => $saringYankb($this->parseYankbHtml($yankb21['html'] ?? ''), $targetKec),
```

Dan `dallapRows` (baris 324) otomatis sudah tersaring karena `$dallap['rows']` dimodifikasi di atas.

**Variabel yang dipakai:** `$filter_kec_nama` (sudah di-resolve di baris 53 dari `?kecamatan=`), **bukan** slug mentah — supaya tidak perlu query ulang dan otomatis `null` saat slug tidak valid.

#### B. `app/Views/public/landingpage.php` — judul & label

Judul tab saat ini hardcode "Kabupaten Tanah Laut":
- baris 2282: `Cakupan Laporan Tempat Pelayanan KB — Kabupaten Tanah Laut`
- baris 2328: `Jumlah Pelayanan Peserta KB Baru — Kabupaten Tanah Laut`
- baris 2391: `Rekap Program Bangga Kencana — Kabupaten Tanah Laut`

Ganti jadi mengikuti filter, mencontoh `buildKrsTree()` yang memakai `'TANAH LAUT — ' . $filterName` (`PublicController.php:419`):

```php
<?php $skopKec = !empty($filter_kec_nama) ? 'Kecamatan ' . $filter_kec_nama : 'Kabupaten Tanah Laut'; ?>
<h3 style="color:#ffffff;">Rekap Program Bangga Kencana — <?= esc($skopKec) ?></h3>
```

Hal yang sama untuk baris 2282 & 2328. `$filter_kec_nama` sudah dikirim ke view (baris 290) ✅.

Untuk YanKB, baris `JUMLAH TOTAL` yang dipertahankan akan berisi angka kecamatan itu — pertimbangkan mengganti labelnya di `$renderYankbTable` atau menyembunyikannya saat filter aktif (hanya 1 baris data, total jadi redundan).

#### C. Opsional — `ApiController`

`bkkbnDallap()` (baris 317) dan `bkkbnYankb()` (baris 338) **tidak perlu diubah** untuk requirement ini. Kalau nanti ingin `?kecamatan=` di API publik, tambahkan filter yang sama di sana — jangan tambah `kecamatanId`, karena itu mengubah level baris (desa) dan akan merusak kontrak response yang sudah dipakai konsumen.

#### D. TIDAK perlu diubah

- `fetchYankbReportInternal()` — tidak perlu parameter baru (filter di PHP).
- `fetchDallapMatrixInternal()` — sudah punya param kecamatan; **tidak dipanggil** dengan `$bkkbnId` dari `landingpage()`.
- `BkkbnMirror.php` (cache key) — tidak ada perubahan berarti tidak ada risiko bentrok.
- `BkkbnMirrorService.php` — tidak perlu berubah.
- `BkkbnWarmup.php` — tidak perlu bertambah beban.

### 7.3 Risiko & trade-off yang perlu diketahui worker

1. **Tetap menarik seluruh kabupaten** — 1 fetch YanKB + 8 fetch Dallap per request, seperti sekarang. Ini **bukan regresi** (memang sudah begitu hari ini), tapi berarti halaman tidak menjadi lebih cepat saat filter aktif. Mitigasi: cache 30 menit sudah ada, dan cache kabupaten **terbukti hangat** (lihat `writable/cache/`, mtime 2026-10-09 22:19-22:21).
2. **Cache bentrok** — **tidak terjadi** pada rekomendasi ini karena tidak ada parameter/kunci baru. Risiko ini **hanya muncul** kalau worker menambah `kecamatanId` ke `fetchYankbReportInternal()` tanpa mengubah baris 134 (`$cacheKey`) — maka `yankb_Tabel1_2026_08.html` akan ditimpa bergantian oleh 11 kecamatan. **Ini jebakan utama.**
3. **Respons besar** — 85 KB (Tabel1) / 55-71 KB (Dallap per report) per request. Karena dipakai bersama oleh semua kecamatan lewat cache, total tetap 1× bukan 11×.
4. **Nama tidak cocok** — kalau nama kecamatan di DB berbeda ejaan dengan SIGA, filter menghasilkan 0 baris (tab tampil kosong) — **bukan** fallback ke kabupaten. Mitigasi: normalisasi wajib identik dengan `resolveBkkbnKecamatanId()`; dan pertimbangkan tetap tampilkan pesan "Data untuk kecamatan X belum tersedia" bila hasil filter kosong (pola `$kec_unresolved` sudah ada di controller).
5. **Baris TOTAL YanKB** — keputusan keep/hide harus konsisten dengan perhitungan "semua nol" di view (baris 2291 & 2337 sudah `continue` untuk baris total, jadi aman keduanya).
6. **API lambat/timeout** — `httpGet()` memakai `CURLOPT_TIMEOUT => 8` (`BkkbnMirror.php:503`), sehingga fetch yang > 8s gagal dan jatuh ke cache basi. Saat uji, respons 1,5-6,8s (masih di bawah 8s) — tapi ini rapuh. Alasan tambahan untuk tidak menambah 11× lipat jumlah fetch.

---

## 8. Artefak

Respons mentah API tersimpan di `scratch/bkkbn/`:

| File | Isi |
|---|---|
| `yankb_Tabel1_2026_08_kab.html` | YanKB Tabel1, Agustus 2026, level kabupaten (11 kecamatan) |
| `yankb_Tabel1_2026_08_kec6624.html` | YanKB Tabel1 + `kecamatanId 6624` → baris **faskes** Bumi Makmur |
| `yankb_Tabel21_2026_08_kec6624.html` | YanKB Tabel21 + `kecamatanId 6624` → baris **faskes** Bumi Makmur |
| `dallap_Tabel10A_2026_kab.html` | Dallap BKL (Tabel10A), level kabupaten |
| `dallap_Tabel10A_2026_kec6624.html` | Dallap BKL + `kecamatanId 6624` → baris **desa** |
| `dallap_Tabel3A_2026_08_kec6624.html` | Dallap BKB bulanan + `kecamatanId 6624` → baris **desa** |
| `_probe*.php` | Skrip replika parser (read-only) untuk membuktikan perilaku `parseYankbHtml()` / `parseDallapRows()` |

Perintah yang dipakai (bisa diulang):

```bash
# YanKB Tabel1 + kecamatanId 6624 (Bumi Makmur)
curl -sS -G --max-time 120 \
  --data-urlencode 'filterPencarian={"tahun":"2026","bulan":8,"provinsiId":"22","kabupatenId":"322","kecamatanId":"6624","reportName":"Tabel1","reportType":"html","reportCategory":"YanKB-Bulanan-Kec"}' \
  "https://siga-api-gateway.bkkbn.go.id/report-siga-yankb-ocp/viewReport" \
  -o scratch/bkkbn/yankb_Tabel1_2026_08_kec6624.html

# Dallap Tabel10A + kecamatanId 6624
curl -sS -G --max-time 120 \
  --data-urlencode 'filterPencarian={"tahun":"2026","provinsiId":"22","kabupatenId":"322","kecamatanId":"6624","reportName":"Tabel10A","reportType":"html","reportCategory":"Dallap-Tahunan"}' \
  "https://siga-api-gateway.bkkbn.go.id/report-siga-dallap-ocp/viewReport" \
  -o scratch/bkkbn/dallap_Tabel10A_2026_kec6624.html
```
