# Default Sumber = Dukcapil + Peta Ikut Filter Kecamatan/Sumber

**Tanggal:** 2026-10-09
**Cakupan:** `PublicController::landingpage()` (landing publik `/`), `app/Views/public/landingpage.php`, `app/Models/ProfilDukcapilModel.php`
**Di luar cakupan (turn berikutnya):** YanKB & Dallap ikut filter kecamatan.

---

## 1. Verifikasi data (WAJIB, dilakukan lebih dulu)

Angka DB `profil_dukcapil` dicocokkan dengan `tests/data/dukcapil_sm1_2026.json`:

| Sumber | n desa | Total Jiwa | Total KK |
|---|---|---|---|
| JSON (`dukcapil_sm1_2026.json`) | 135 | 378.190 | 132.030 |
| DB (`SELECT SUM(jiwa_l+jiwa_p), SUM(kk_l+kk_p) FROM profil_dukcapil`) | 135 | 378.190 | 132.030 |

Per-kecamatan (DB) juga identik dengan JSON, mis. **Bajuin = 20.774 jiwa / 7.500 KK**, **desa Bajuin (id_desa 116) = 2.180 jiwa / 810 KK**.
**Kesimpulan: DB cocok persis dengan JSON — tidak ada selisih.** (Snapshot tunggal 2026/S1, 135 baris, 135 desa.)

Referensi laporan desa (`laporan_agregat`, satu baris terbaru per desa) = **375.583 jiwa / 130.908 KK**.

---

## 2. Keputusan desain

### A. Default sumber = Dukcapil
- Sebelumnya: `?sumber=` kosong → `campuran` otomatis (laporan desa menimpa Dukcapil per desa; karena 135/135 desa sudah lapor → hasilnya 100% laporan desa = **375.583**).
- Sekarang: `?sumber=` kosong → **`dukcapil`** (snapshot resmi DKB SM I/2026 = **378.190**).
- Nilai eksplisit tetap didukung: `desa` (laporan), `dukcapil`, dan **`campuran`** (kemampuan lama dipertahankan sebagai pilihan eksplisit — tidak ada kemampuan yang hilang).
- `$profilMeta['mode']` sekarang = `sumberFilter` apa adanya (`dukcapil` saat default), sehingga badge/label **tidak lagi salah menyebut "laporan desa"** saat faktanya Dukcapil. Ini menghapus regresi yang dilaporkan user.
- Meta lama `mode_otomatis`/`laporan_count_otomatis`/`dukcapil_count_otomatis` **dihapus** (konsep "otomatis" tidak ada lagi). Diganti `laporan_count_scope`/`dukcapil_count_scope` untuk keterangan opsi "Campuran".

### B. Peta ikut filter kecamatan + konsisten sumber
- Sebelumnya: `buildMapKecamatan()` tanpa argumen; angka dari `LaporanAgregatModel::getSummaryPerKecamatan()` (laporan desa, semua 11 kecamatan) — **inkonsisten** dengan kartu saat kartu menampilkan Dukcapil.
- Sekarang: `buildMapKecamatan($idKecamatanFilter, $sumberFilter)` — (1) membatasi daftar ke kecamatan scope saat `?kecamatan=` aktif, dan (2) mengambil angka dari **sumber aktif**.
- **Temuan penting:** nilai di poligon peta TIDAK berasal dari `buildMapKecamatan()` (itu hanya metadata lat/lng/nama), melainkan dari properti GeoJSON endpoint `GET /api/v1/map/kecamatan` yang **selalu berbasis laporan desa** (`ApiController` — di luar batas file turn ini). Maka strategi: controller mengirim nilai indikator sumber aktif (`map_kecamatan[].*` + `map_desa[]`) dan **JS menimpa** properti endpoint dengan nilai tersebut, sekaligus **menyaring** fitur ke scope filter. Ini memenuhi B tanpa menyentuh `ApiController`.
- Agregasi Dukcapil per-kecamatan/per-desa dibuat **di `ProfilDukcapilModel`** (`getMapKecamatan()`, `getMapDesa()`) — tidak menyentuh `LaporanAgregatModel::getMapKecamatan()/getMapDesa()/latestPerDesaSubquery()`.
- Mode campuran: baris per-desa = Dukcapil lalu ditimpa laporan desa per desa (semantik sama dengan kartu) → diagregasi ke kecamatan tanpa double-count.

### Catatan "Periode laporan terbaru"
- Di mode **Dukcapil** (default) baris "Periode laporan terbaru: <bulan>" **disembunyikan** (tidak relevan/menyesatkan — data dari DKB, bukan pelaporan desa). Hanya tampil di mode `desa`/`campuran`.
- Pill hero periode ikut menyesuaikan: mode Dukcapil menampilkan **"Dukcapil · DKB Semester I 2026"** (bukan "Data terbaru <bulan>").

---

## 3. Perubahan file

| File | Perubahan |
|---|---|
| `app/Controllers/PublicController.php` | Default `sumberFilter='dukcapil'` (+ terima `campuran` eksplisit); `mode`=sumber aktif; meta scope; `buildMapKecamatan($idKec,$sumber)` + helper `mapRowsPerDesa()`, `aggregateMapToKecamatan()`, `buildMapDesa()`; kirim `map_desa` + `map_meta` ke view. |
| `app/Views/public/landingpage.php` | Dropdown "Hanya Dukcapil (default)" terpilih; pill Sumber + pill periode Dukcapil-aware; note-strip menyebut Dukcapil + label DKB; badge Bagian 1 = Dukcapil; hidden `sumber` hanya untuk desa/campuran; JS: nilai peta dari sumber aktif + filter fitur ke scope; crumb & note peta jujur (cakupan + sumber). |
| `app/Models/ProfilDukcapilModel.php` | +`getMapKecamatan(?int)`, `getMapDesa(?int)`, `normalizeMapRow()`, `kode3()`, `kodeDesa3()` (membungkus `latestPerDesaSubquery()` yang **tidak diubah**). |
| `app/Views/templates/theme_toggle.php` | **Tidak diubah** — kelas CSS `.tag.src-dukcapil`/`.src-desa` sudah ada & dipakai ulang; tidak ada penambahan gaya baru. |

Tidak menyentuh `app/Commands/**`, `app/Filters/**`, `AGENTS.md`, migration, seeder, `ApiController.php`. Tidak commit.

---

## 4. Bukti verifikasi

### 4.1 `php -l`
`No syntax errors detected` untuk ketiga file yang diubah.

### 4.2 Server `php spark serve --host localhost --port 8089` (curl → `scratch/dflt/`)

| Kasus | URL | Total Jiwa | KK | Map crumb | Catatan |
|---|---|---|---|---|---|
| Default | `/` | **378.190** | **132.030** | 11 kec. · Dukcapil | pill "Sumber: Dukcapil", note-strip + label DKB tampil |
| Eksplisit | `/?sumber=dukcapil` | 378.190 | — | 11 kec. · Dukcapil | identik default |
| Laporan | `/?sumber=desa` | **375.583** | — | 11 kec. · Laporan Desa | tidak rusak; "Periode laporan terbaru" tampil |
| Campuran | `/?sumber=campuran` | 375.583 | — | 11 kec. · Dukcapil + Laporan Desa | kemampuan lama utuh |
| Kecamatan | `/?kecamatan=bajuin` | **20.774** | 7.500 | **1 kec. · Dukcapil** | peta terbatas Bajuin saja |
| Kec + desa | `/?kecamatan=bajuin&desa=bajuin` | **2.180** | 810 | 1 kec. · Dukcapil | desa Bajuin disorot |
| Laporan+kec | `/?sumber=desa&kecamatan=bajuin` | 20.676 | — | 1 kec. · Laporan Desa | konsisten |
| Invalid kec | `/?kecamatan=tidak-ada` | 378.190 | — | 11 kec. | fallback kabupaten + warning tampil |
| Invalid desa | `/?kecamatan=bajuin&desa=tidak-ada` | 20.774 | — | 1 kec. | warning desa tampil |

**Bukti peta ikut filter (dari HTML, `const data = [...]`):**
- `/?kecamatan=bajuin` → `n kecamatan in JS data: 1` = `010 Bajuin: total_jiwa=20774 total_kk=7500` (bukan 11).
- `/` (default) → 11 kecamatan, nilai = Dukcapil (`003 Pelaihari: 84816`, dst).
- `/?kecamatan=bajuin` → `mapDesaData`: 9 desa, `sum total_jiwa = 20774`; desa Bajuin = 2180/810.

**Bukti endpoint tetap laporan (di-override di klien):** `GET /api/v1/map/kecamatan` → `003 Pelaihari total_jiwa=84227` (laporan); kartu/peta default menampilkan 84.816 (Dukcapil) — override bekerja.

**Scan error:** tidak ada `Fatal error`/`Warning:`/`Undefined`/`Whoops`/`Uncaught` pada semua HTML; HTTP 200 untuk semua kasus.

### 4.3 `php vendor/phpunit/phpunit/phpunit tests/unit`
`Tests: 79, Assertions: 364` — **OK, 79/79 hijau** (identik baseline; 1 warning "No code coverage driver" = environment, bukan kegagalan).

### 4.4 Server dimatikan
Port 8089 tidak lagi LISTENING; `curl` → HTTP 000 (connection refused).

---

## 5. Untuk turn berikutnya (YanKB + Dallap ikut filter kecamatan)

- Resolver slug→id sudah ada: `PublicController::kecamatanModel` (lookup `LOWER(REPLACE(nama_kecamatan," ","-"))` + `is_public=1`) → `$id_kecamatan_filter`, dan helper `resolveBkkbnKecamatanId(string $nama)` (trait `BkkbnMirror`) untuk pemetaan ke id BKKBN.
- Pola sumber-aware yang baru (`mapRowsPerDesa`/`aggregateMapToKecamatan`) bisa ditiru untuk agregasi per-kecamatan YanKB/Dallap.

---

# TURN 2 — Tab YanKB & Dallap Ikut Filter Kecamatan

**Tanggal:** 2026-10-09 (lanjutan)
**Dasar:** `docs/superpowers/specs/2026-10-09-riset-bkkbn-filter-kecamatan.md` (riset read-only).
**File diubah:** `app/Controllers/PublicController.php`, `app/Views/public/landingpage.php` (tidak ada file lain).

## 6. Keputusan implementasi

Sesuai riset: **filter di PHP** atas respons SIGA **level kabupaten**, **BUKAN** lewat parameter API `kecamatanId`.

Alasan (ringkas, detail di riset):
- Mengirim `kecamatanId` **menurunkan level baris** — YanKB Tabel1 → baris **FASKES**, YanKB Tabel21 & Dallap → baris **DESA/KELURAHAN** — sedangkan tab ini butuh **1 baris kecamatan**. Jadi parameter API memberi **bentuk yang salah**.
- `parseYankbHtml()` mengembalikan `rows=[]` untuk respons level-kecamatan (header jadi `KODE / NAMA FASKES`, tanpa `KECAMATAN`) → tabel rapi hilang, jatuh ke iframe.
- Filter di PHP memakai **cache kabupaten yang sudah ada** → **0 fetch tambahan** (tidak menambah beban/risiko timeout, tidak menyentuh kunci cache), dan **konsisten** dengan cara KRS difilter (`buildKrsTree()`).

### 6.1 YanKB (Tabel1 `yankbData` + Tabel21 `yankb21Data`)
- Saring `rows` berdasarkan `cells[1]['t']` = NAMA KECAMATAN (kode 2 digit di `cells[0]`), normalisasi **identik** `resolveBkkbnKecamatanId()`: `strtoupper`, `_`/`-`→spasi, rapatkan spasi → `Bati-Bati` (DB) cocok `BATI BATI` (SIGA).
- **Total di-recompute ke scope tersaring** (temuan penting): baris `JUMLAH TOTAL` SIGA = total **seluruh kabupaten** (mis. `79`). Bila dibiarkan, total 79 muncul di bawah judul "Kecamatan Bajuin" — **menyesatkan**. Solusi: angka baris kecamatan ditempel ke **template baris total** (struktur colspan dipertahankan); kolom persen tidak dijumlahkan (tidak relevan untuk 1 baris). Untuk 1 kecamatan hasilnya = angka kecamatan itu.
- Bila tak ada baris kecamatan yang cocok → `rows=[]`; view menampilkan pesan jujur ("belum tersedia untuk Kecamatan X"), **bukan** iframe seluruh kabupaten di bawah judul kecamatan (guard `$bkkbnIsKec || empty($yankbHtml)`).

### 6.2 Dallap (`dallapRows` + tab PUS & KB Aktif yang memakai `dallapRows` yang sama)
- Saring `$dallap['rows']` berdasarkan `row['nama']` (NAMA KECAMATAN), normalisasi sama.
- Total dihitung ulang oleh view dari `rows` (sudah begitu sebelumnya) → otomatis = total kecamatan.
- **Tidak** memakai `fetchDallapMatrixInternal(..., $bkkbnId, ...)` (itu baris **desa**, untuk drill-down `getDallapDesa()`).

### 6.3 Cache
- **Tidak diubah.** Karena tetap fetch level kabupaten, kunci cache `yankb_<report>_<tahun>_<bulan>.html` tetap benar — tidak ada risiko cache poisoning silang antar kecamatan. (Risiko itu hanya muncul bila menambah `kecamatanId` tanpa mengubah `$cacheKey`.)

## 7. Perubahan label/crumb view
- Crumb grup BKKBN: `SIGA BKKBN · Tahun 2026 · Tanah Laut` → `... · Kecamatan Bajuin` saat filter aktif (`$bkkbnScope` dari `bkkbnMeta`).
- Judul 4 tabel: "… — Kabupaten Tanah Laut" → "… — Kecamatan <Nama>" saat filter aktif.
- Note-strip BKKBN: **dihapus** klaim lama "YanKB & Dallap tetap menampilkan seluruh kabupaten"; diganti "kini mengikuti filter kecamatan — menampilkan Kecamatan X saja" (tetap jujur: YanKB & Dallap tidak difilter per **desa**).
- Note-strip utama (turn 1) disesuaikan: BKKBN "(KRS/YanKB/Dallap) … **mengikuti filter kecamatan**".

## 8. Bukti verifikasi (server `php spark serve --host localhost --port 8090`, bukti di `scratch/yankbfix/`)

| Kasus | YanKB Tabel1 baris | YanKB Tabel21 baris | Dallap baris | PUS&KB baris | Judul |
|---|---|---|---|---|---|
| `/` (default) | 11 kec + TOTAL(79) | 11 kec + TOTAL(2554) | **11** | 11 | "… — Kabupaten Tanah Laut" |
| `/?kecamatan=bajuin` | **1** (BAJUIN) + TOTAL(**2**) | **1** (BAJUIN) + TOTAL(**157**) | **1** (BAJUIN) | **1** | "… — Kecamatan Bajuin" |
| `/?kecamatan=bumi-makmur` | **1** (BUMI MAKMUR) + TOTAL(**11**) | **1** (BUMI MAKMUR) + TOTAL(**137**) | **1** (BUMI MAKMUR) | **1** | "… — Kecamatan Bumi Makmur" |
| `/?kecamatan=bajuin&sumber=desa` | **1** (BAJUIN) | **1** | **1** | **1** | "… — Kecamatan Bajuin" |

**Konsistensi angka (bukti total benar):**
- Baris `BAJUIN` di laporan kabupaten = `[10, BAJUIN, 2, 2, 100.00, 2, 2, 100.00, …]` → sama persis dengan baris hasil filter; `JUMLAH TOTAL` hasil filter = `2` (bukan `79` kabupaten). Tabel21: BAJUIN `157` → total `157` (bukan `2554`).
- `BUMI MAKMUR` Tabel21 = `137` — identik dengan `JUMLAH TOTAL` respons per-kecamatan `kecamatanId=6624` di riset §3.2 (cross-check independen).
- Dallap `BAJUIN` = `[2,1,13,6,9,4,3.639,2.880]` identik di default (11 baris) & terfilter (1 baris).

**Guard jujur:** saat filter kecamatan aktif dan hasil saring kosong, tab YanKB/Dallap menampilkan pesan "belum tersedia untuk Kecamatan X" (bukan iframe/isi kabupaten) — dicek di view (`$bkkbnIsKec || empty($yankbHtml)`).

**Scan error:** 0 `Fatal error`/`Warning:`/`Undefined`/`Whoops`/`Uncaught` pada keempat HTML; HTTP 200 semua.
**`php -l`:** No syntax errors (kedua file). **PHPUnit:** `79/79` OK (364 assertions) — identik baseline.
**Server dimatikan:** port 8090 tidak LISTENING; `curl` → HTTP 000.

## 9. Batasan yang dipatuhi
- Tidak menyentuh `app/Controllers/BkkbnMirror.php` (trait), `app/Commands/**`, `app/Filters/**`, `AGENTS.md`, migration, seeder, `ApiController.php`.
- Tidak mengubah `getAggregatedStats`/`latestPerDesaSubquery`. Tidak commit.
