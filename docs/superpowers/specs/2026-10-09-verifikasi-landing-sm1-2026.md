# Laporan Verifikasi Visual Landing Page — Dukcapil Semester I 2026

**Tanggal:** 2026-10-09
**Tujuan:** memastikan landing page `/` merender angka profil kependudukan yang benar
setelah impor Dukcapil SM I 2026, dan **tidak** terjadi double-count.
**Sifat kerja:** murni verifikasi — tidak ada kode aplikasi, migration, seeder, config,
atau DB yang diubah. Semua query DB bersifat `SELECT`; semua interaksi app via `HTTP GET`.

---

## 1. Cara menjalankan & base URL

Server development CodeIgniter:

```bash
nohup php spark serve --port 8081 > scratch/serve_8081.log 2>&1 &
sleep 6; cat scratch/serve_8081.log
```

Log startup:

```
CodeIgniter v4.6.5 Command Line Tool - Server Time: 2026-10-09 14:46:42 UTC+08:00

CodeIgniter development server started on http://localhost:8081
Press Control-C to stop.
[Fri Oct  9 14:46:43 2026] PHP 8.3.29 Development Server (http://localhost:8081) started
```

**Base URL yang dipakai: `http://localhost:8081`** (bukan `http://127.0.0.1:8081`).

Catatan lingkungan (penting kalau diulang): `php spark serve` di mesin ini bind ke
IPv6 loopback saja — `netstat -ano | grep 8081` menunjukkan
`TCP [::1]:8081 LISTENING`. Permintaan ke `http://127.0.0.1:8081/` gagal dengan
`curl: (7) Failed to connect to 127.0.0.1 port 8081`. Pakai `localhost`.

### Perintah pengambilan HTML

```bash
mkdir -p scratch
curl -sS -o scratch/landing_dukcapil.html -w "code=%{http_code} time=%{time_total} size=%{size_download}\n" --max-time 300 "http://localhost:8081/?sumber=dukcapil"
curl -sS -o scratch/landing_desa.html     -w "code=%{http_code} time=%{time_total} size=%{size_download}\n" --max-time 300 "http://localhost:8081/?sumber=desa"
curl -sS -o scratch/landing_default.html  -w "code=%{http_code} time=%{time_total} size=%{size_download}\n" --max-time 300 "http://localhost:8081/"
```

Hasil:

| File | HTTP | Waktu | Ukuran |
|---|---|---|---|
| `scratch/landing_dukcapil.html` | 200 | 42,51 s | 270.763 B |
| `scratch/landing_desa.html` | 200 | 0,70 s | 270.753 B |
| `scratch/landing_default.html` | 200 | 0,51 s | 270.753 B |

Permintaan pertama lambat (42 s) karena bagian **Monitoring BKKBN** menarik API
eksternal SIGA; dua permintaan berikutnya cepat karena sudah ter-cache. Sesuai
instruksi, bagian BKKBN **diabaikan** dan tidak dinilai.

---

## 2. Referensi angka dari DB (SELECT saja, untuk pembanding)

```sql
SELECT tahun, semester, COUNT(*) n, SUM(jiwa_l+jiwa_p) jiwa, SUM(kk_l+kk_p) kk,
       SUM(jiwa_l) L, SUM(jiwa_p) P, SUM(kk_l) kkl, SUM(kk_p) kkp
FROM profil_dukcapil GROUP BY tahun, semester ORDER BY tahun, semester;
```

```json
{"tahun":"2026","semester":"1","n":"135","jiwa":"378190","kk":"132030","L":"192047","P":"186143","kkl":"103688","kkp":"28342"}
```

**Hanya satu periode ada: `(2026, 1)`, tepat 135 baris.** Tidak ada periode
`(2025, 2)` yang tersisa → syarat utama anti-double-count terpenuhi di level data.

Pembanding `laporan_agregat` (sumber `?sumber=desa`):

```
n=137 baris, 135 desa distinct, 3 periode distinct
jiwa=380525, kk=132696, L=193349, P=187176, kk_l=104626, kk_p=28070
per periode: (8,2026) n=1 jiwa=2060 | (7,2026) n=1 jiwa=2882 | (12,2025) n=135 jiwa=375583
```

---

## 3. Hasil per parameter `?sumber=`

### Ringkasan angka

| Parameter | Total Jiwa | L / P | Total KK | KK L / P | Status |
|---|---|---|---|---|---|
| `?sumber=dukcapil` | **378.190** | 192.047 / 186.143 | **132.030** | 103.688 / 28.342 | ✅ sama persis dengan DB |
| `?sumber=desa` | 380.525 | 193.349 / 187.176 | 132.696 | 104.626 / 28.070 | ✅ sama persis dengan `laporan_agregat` |
| default (campuran) | 380.525 | 193.349 / 187.176 | 132.696 | 104.626 / 28.070 | ✅ = laporan desa (135/135 desa sudah lapor) |

Turunan lain pada `?sumber=dukcapil`: Balita 24.249, Remaja 66.064, Lansia 38.111,
PUS 142.906, Cakupan JKN 80 % (302.552 dari 378.190 jiwa), Rata-rata 2,9 jiwa/KK.

---

## 4. Status tiap kriteria

| # | Kriteria | Bukti | Status |
|---|---|---|---|
| K1 | Total jiwa `?sumber=dukcapil` = **378.190** | `<div class="value">378.190</div>` (2×: stat card + tabel data pokok) | **PASS** |
| K2 | Total KK `?sumber=dukcapil` = **132.030** | `<div class="value">132.030</div>` | **PASS** |
| K3 | Label menyebut **Semester I 2026** | note-strip: `snapshot Dukcapil Kab. Tanah Laut (semester 1/2026)` | **PASS** (dengan catatan wujud, lihat §5) |
| K4 | **Tidak** ada 753.773 atau ≈2× 378.190 | grep `753[.,]?[0-9]{0,3}` hanya memunculkan `7531` (potongan string JS, bukan angka tampilan) dan `75638` (nilai array `jknValues`, bukan total); tidak ada angka 7xx.xxx lain | **PASS** |
| K5 | Tidak ada error PHP | grep `Fatal error`,`Warning:`,`Notice:`,`Deprecated:`,`Undefined array key`,`Whoops`,`DatabaseException`,`Parse error`,`exception` → **0 temuan** di ketiga file | **PASS** |
| K6 | Konsistensi internal chart | Σ piramida = 378.190 (selisih 0); Σ pend = Σ kerja = 132.030; Σ JKN = 378.190 | **PASS** |

---

## 5. Catatan penting tentang label periode (K3)

Kriteria asli berbunyi "cari 'Semester' di sekitar angka 2026". Yang **benar-benar
dirender** adalah:

```html
<div class="note-strip">
    <i class="bi bi-info-circle-fill"></i>
    <p>
                                    Data Wilayah ini bersumber dari snapshot Dukcapil Kab. Tanah Laut (semester 1/2026).
                                Filter wilayah di atas menyesuaikan seluruh grafik dan tabel di bawah.
                                        Periode laporan terbaru: <strong>Agustus 2026</strong>.
                                </p>
</div>
```

- Kata **"Semester"** (kapital) **tidak** muncul di HTML — teksnya memakai huruf kecil
  **`semester 1/2026`**. Pencarian case-sensitive untuk `Semester` akan menghasilkan
  nol dan bisa disalahartikan sebagai FAIL. Pencarian case-insensitive menemukan tepat
  **1 kemunculan**: `semester 1/2026).`
- Angka **2026** memang menyertainya, jadi label periode benar: **Semester I 2026**.
- Sumber: `app/Views/public/landingpage.php:1365`
  `(semester <?= esc((string) ($profilMeta['semester'] ?? '2')) ?>/<?= (int) ($profilMeta['tahun'] ?? 2025) ?>)`,
  dengan `$profilMeta['semester']`/`['tahun']` diisi dari
  `ProfilDukcapilModel->orderBy('tahun DESC, semester DESC')->first()`
  (`PublicController.php:130-137`). Karena tabel kini hanya berisi 2026/1, nilainya
  `1` dan `2026`.
- Baris kedua note-strip ("Periode laporan terbaru: **Agustus 2026**") berasal dari
  `laporan_agregat`, bukan dari snapshot Dukcapil — jadi beda periode wajar dan bukan
  indikator masalah.

Kesimpulan K3: **PASS secara substansi** (periode snapshot benar-benar Semester I
2026, diambil dari DB, bukan hardcode), meski wujud teksnya `semester 1/2026`
huruf kecil dan tidak memuat frasa "Semester I 2026" kata-per-kata.

---

## 6. Kenapa `?sumber=desa` dan default berbeda dari `dukcapil`

- `?sumber=dukcapil` → `ProfilDukcapilModel::getAggregatedStats()` = snapshot Dukcapil
  SM I 2026 → **378.190 / 132.030**.
- `?sumber=desa` → `LaporanAgregatModel::getAggregatedStats()` = laporan desa →
  **380.525 / 132.696**.
- default (**campuran**) → laporan desa menimpa Dukcapil **per desa**
  (`PublicController.php:118-128`: `$dukcapilIds = array_diff($desaScope, $desaLaporIds)`).
  Karena **135/135 desa sudah punya laporan**, himpunan "desa yang pakai Dukcapil"
  kosong → hasil campuran **identik dengan `?sumber=desa`** (380.525 / 132.696).
  Ini konsisten dengan badge "Cakupan Laporan: 135/135 desa, 11 dari 11 kecamatan".

Selisih 2.335 jiwa antara laporan desa dan Dukcapil berasal dari fakta bahwa
`laporan_agregat` berisi **3 periode** yang dijumlahkan tanpa filter periode
(12/2025 n=135 → 375.583; 7/2026 n=1 → 2.882; 8/2026 n=1 → 2.060; total 380.525) —
komposisi yang memang berbeda dari satu snapshot semester. Ini perilaku existing
di luar scope tugas (verifikasi profil kependudukan), tidak ada error, dan tidak
terjadi double-count pada jalur Dukcapil. Dicetak di sini hanya sebagai konteks
kenapa angkanya berbeda.

---

## 7. Bukti HTML — potongan terpakai

### 7.1 Stat card `?sumber=dukcapil` (baris 1212–1230)

```html
<div class="stat-card">
    <i class="bi bi-people-fill icon"></i>
    <div class="label">Total Jiwa</div>
    <div class="value">378.190</div>
    <div class="sub"><b>192.047 L</b> · <i>186.143 P</i></div>
</div>
<div class="stat-card">
    <i class="bi bi-house-heart-fill icon"></i>
    <div class="label">Kartu Keluarga</div>
    <div class="value">132.030</div>
    <div class="sub">Rata-rata <b>2.9</b> jiwa/KK</div>
</div>
<div class="stat-card">
    <i class="bi bi-shield-check icon"></i>
    <div class="label">Cakupan JKN/BPJS</div>
    <div class="value">80<span class="unit">%</span></div>
    <div class="meter pink" role="progressbar" aria-valuenow="80" aria-valuemin="0" aria-valuemax="100" aria-label="Cakupan JKN BPJS"><span style="width: 80%"></span></div>
    <div class="sub">302.552 dari 378.190 jiwa</div>
</div>
```

### 7.2 Tabel Data Pokok `?sumber=dukcapil` (baris 1313–1345)

```html
<tr>
    <td class="dt-cat">Jiwa</td>
    <td class="dt-num">192.047</td>
    <td class="dt-num">186.143</td>
    <td class="dt-num dt-total">378.190</td>
</tr>
<tr>
    <td class="dt-cat">Kartu Keluarga</td>
    <td class="dt-num">103.688</td>
    <td class="dt-num">28.342</td>
    <td class="dt-num dt-total">132.030</td>
</tr>
<tr>
    <td class="dt-cat">Balita</td>
    <td class="dt-num">—</td>
    <td class="dt-num">—</td>
    <td class="dt-num dt-total">24.249</td>
</tr>
```

### 7.3 Filter sumber (mengonfirmasi parameter yang aktif)

`?sumber=dukcapil`:

```html
<select id="sumber-data" class="form-select" ...>
    <option value="campuran" >Dukcapil + Laporan Desa (otomatis)</option>
    <option value="desa"     >Hanya Laporan Desa</option>
    <option value="dukcapil" selected>Hanya Dukcapil</option>
</select>
```

`?sumber=desa` → `value="desa" selected`; default → `value="campuran" selected`.

### 7.4 Data chart piramida (baris 2533–2535) — konsisten dengan total

```js
const ageLabels    = ["0-4","5-9","10-14","15-19","20-24","25-29","30-34","35-39","40-44","45-49","50-54","55-59","60-64","65-69","70-74","75-79","80-84","85+"];
const piramidaL    = [12316,16342,17016,16530,15792,14702,14238,14413,14907,14169,12477,9815,7341,5249,3057,1873,1105,705];
const piramidaP    = [11933,15836,16506,16012,15303,14247,13793,13951,14435,13730,12098,9518,7121,5100,2971,1829,1081,679];
```

Dijumlahkan: Σ L = **192.047**, Σ P = **186.143**, Σ total = **378.190** (selisih 0
dengan stat card dan dengan DB). Turunan cocok pula: 0-4 → balita 24.249;
10-14 + 15-19 → remaja 66.064; Σ 60-64..85+ → lansia 38.111.

Chart lain (baris 2537–2547) juga konsisten:
`pendValues` Σ = 132.030 (= KK), `pkrValues` Σ = 132.030 (= KK),
`jknValues` Σ = 378.190 (= jiwa; PBI 94.567 + Non PBI 207.985 = 302.552 punya JKN).

---

## 8. Pemeriksaan negatif (yang dicari tapi tidak ditemukan)

- `753.773` / `753773` / `756.380` → **tidak ada** di ketiga file.
- Semua angka berpola `NNN.NNN` pada halaman dukcapil, diurutkan: 378.190 (3×),
  192.047 (2×), 186.143 (2×), 142.906 (2×), 132.030 (2×), lalu nilai-nilai turunan
  yang lebih kecil (302.552, 272.362, 207.985, 184.259, 168.115, 131.216, 113.936,
  108.896, 103.688, …). Tidak ada satupun di kisaran 2× 378.190.
- `Fatal error`, `Parse error`, `Warning:`, `Notice:`, `Deprecated:`,
  `Undefined array key`, `Whoops`, `DatabaseException`, `exception` → **0 temuan**
  (`grep -c` mengembalikan 0 untuk ketiga file).
- Tidak ada output debug/Whoops; ketiga halaman ber-HTTP 200 dengan markup lengkap
  (~270 KB, 3.880 baris).

---

## 9. Kesimpulan

**SEMUA KRITERIA LULUS.** Landing page `?sumber=dukcapil` merender **378.190 jiwa**
dan **132.030 KK** — sama persis dengan `SELECT` langsung ke `profil_dukcapil`
(135 baris, hanya periode 2026/1) — dengan label periode `semester 1/2026`, tanpa
double-count (tidak ada ≈753.773), dan tanpa error PHP. Piramida usia serta seluruh
chart turunan konsisten dengan total tersebut (selisih 0). `?sumber=desa` dan default
memberi 380.525 / 132.696 karena mengambil `laporan_agregat` (135/135 desa sudah
lapor sehingga mode campuran sepenuhnya terisi laporan desa) — berbeda wajar, bukan
double-count.

### Artefak

- `scratch/landing_dukcapil.html` — HTML mentah `?sumber=dukcapil`
- `scratch/landing_desa.html` — HTML mentah `?sumber=desa`
- `scratch/landing_default.html` — HTML mentah tanpa parameter
- `scratch/serve_8081.log` — log server development

### Keadaan akhir

Tidak ada file aplikasi, migration, seeder, atau config yang diubah; tidak ada
`INSERT`/`UPDATE`/`DELETE` di DB (hanya `SELECT`); tidak ada commit. Server
`php spark serve --port 8081` dimatikan setelah verifikasi selesai.
