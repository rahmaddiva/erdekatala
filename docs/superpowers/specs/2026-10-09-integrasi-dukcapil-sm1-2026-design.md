# Integrasi Data Dukcapil Semester I 2026 — Analisis Gap & Opsi

**Status:** memo keputusan (bukan rencana implementasi). Belum ada kode, migration, atau seeder yang diubah.
**Tanggal:** 2026-10-09
**Sumber data:** `DATA KEPENDUDUKAN KAB TALA SM I 2026.xlsx` (root project) — 8 sheet, 11 kecamatan, 135 desa.
**Baseline sistem:** `profil_dukcapil` (snapshot Dukcapil semester 2/2025) + `laporan_agregat` (laporan desa bulanan).

---

## 1. Latar Belakang

Erdekatala punya dua sumber angka kependudukan:

1. **`laporan_agregat`** — laporan bulanan yang dikirim admin desa. Unit lapor = desa, unique `(id_desa, bulan, tahun)`. Ini sumber utama dashboard dan — bila desa sudah lapor — landing page.
2. **`profil_dukcapil`** — snapshot resmi Dukcapil per desa per semester, dibuat migration `2026-10-01-000001_CreateProfilDukcapil.php`. Unique `(id_desa, tahun, semester)`. Dipakai landing page sebagai **fallback per desa** (Desain B): desa yang sudah lapor memakai angka laporannya, desa yang belum lapor memakai snapshot Dukcapil, tanpa double-count (`PublicController::landingpage()` baris 108–144).

Snapshot yang ada sekarang diisi `ProfilDukcapilSeeder` (semester 2, tahun 2025). Angkanya **bukan hasil impor Excel**, melainkan reuse `LaporanAgregatSeeder::getKecamatanData()` (tingkat kecamatan) yang lalu **dibagi proporsional ke tiap desa** dengan `distribute()` (acak 0.90–1.10). Jadi angka per-desa saat ini adalah hasil turunan sintetis, bukan data resmi per desa.

File `DATA KEPENDUDUKAN KAB TALA SM I 2026.xlsx` adalah data resmi **per desa** (ada kolom `KODE` = kode wilayah `63.01.xx.xxxx`). Pertanyaannya: bagaimana memasukkan 8 sheet ini ke skema yang ada tanpa merusak landing page, dashboard, dan API — padahal model datanya tidak sama 1:1.

### 1.1 Fakta terukur dari file Excel (diverifikasi langsung, bukan asumsi)

| Fakta | Nilai |
|---|---|
| Sheet | 8: `JML KK`, `JML KK PER JENIS KELAMIN`, `JML JIWA`, `JML JIWA PER JENIS KELAMIN`, `JML JIWA PER USIA`, `JML JIWA PER PENDIDIKAN`, `JML JIWA PER PEKERJAAN`, `KEPEMILIKAN DOK` |
| Baris data | **135 desa**, 11 blok `KECAMATAN`, tanpa duplikasi kode |
| Struktur baris | Baris 1–3 judul, baris 6 penanda `KECAMATAN xxx`, baris 7 header kolom (`NO/KODE/DESA/...`), baris 8 header kedua (nama sub-kolom), **data mulai baris 9** |
| Distribusi per kecamatan | Takisung 12, Jorong 11, Pelaihari 20, Kurau 11, Bati-Bati 14, Panyipatan 10, Kintap 14, Tambang Ulang 9, Batu Ampar 14, Bajuin 9, Bumi Makmur 11 |
| Konsistensi internal | **Semua 135 desa lolos**: `JML JIWA` = `Σ usia (16 grup)` = `Σ pendidikan (10)` = `Σ pekerjaan (11)`; `KK Lk + KK Pr` = `JML KK`; `Jiwa Lk + Jiwa Pr` = `JML JIWA` |
| Kecocokan kode dengan `m_desa` | **135/135 kode identik** dengan `WilayahSeeder` (`only in excel: []`, `only in db: []`) |
| Beda nama desa | **1**: kode `63.01.07.2013` → Excel `MEKAR SARI`, DB `MEKAR RAYA` |
| Total jiwa kabupaten (Excel) | 378.190 — vs baseline SM2/2025 375.583 (**+0,7%**) |
| Total KK kabupaten (Excel) | 132.030 — vs baseline SM2/2025 130.908 (**+0,9%**) |

Selisih +0,7% / +0,9% konsisten di semua 11 kecamatan (0,4–1,1%), jadi Excel ini memang penerus langsung baseline SM2/2025 — bukan data dengan definisi berbeda. Ini memperkuat opsi "ganti/refresh snapshot", bukan "tambah sumber baru yang berdiri sendiri".

**Tanggal potong (cut-off) tidak seragam antar sheet.** Menurut catatan hasil ekstraksi (`tests/data/dukcapil_sm1_2026.json` → `catatan`): 7 sheet KK/jiwa/usia/pendidikan/pekerjaan = **DKB Semester I Tahun 2026**, sedangkan sheet `KEPEMILIKAN DOK` = **DKB pertanggal 30-09-2026**. Artinya angka dokumen diambil **3 bulan setelah** akhir semester I (30 Juni), sehingga tidak se-perioda dengan angka jiwa/KK. Implikasi: rasio turunan seperti "cakupan KTP-el terhadap wajib KTP" atau "akta lahir terhadap jumlah anak 0–17" akan tercampur dua waktu potong. Perlu dicatat di landing page atau diminta versi dokumen per 30 Juni.

**Cross-check dengan hasil ekstraksi worker paralel** (`tests/data/dukcapil_sm1_2026.json`, selesai setelah memo ini disusun): angka di atas **terkonfirmasi identik** — 135 desa, total jiwa 378.190, total KK 132.030, `Σ usia = Σ pendidikan = Σ pekerjaan = 378.190`, 0 mismatch konsistensi internal. Ekstraksi itu juga sudah menormalisasi label `65-99` menjadi `65-69` (menguatkan §3.1) dan memakai kode desa sebagai kunci.

### 1.2 Yang harus diingat tentang konsumen data

| Konsumen | Jalur data | Konsekuensi jika skema berubah |
|---|---|---|
| Landing page `/` | `PublicController::landingpage()` → `getAggregatedStats()` (laporan + dukcapil) → `LaporanAgregatModel::statsToChartData()` | Chart dan tabel di Bagian 1 mengasumsikan **nama key chart tetap ada** (lihat §4) |
| Dashboard `/dashboard` | `DashboardController::index()` → `getAggregatedStats()` → `statsToChartData()` | 7 chart ZingChart; chart dokumen & JKN **hardcode key** di `dashboard/index.php` (lihat §4) |
| API `/api/v1/laporan`, `/rekap/*`, `/map/*` | `SELECT laporan_agregat.*` + `mapIndicatorFields()` | **Tidak menyentuh `profil_dukcapil` sama sekali** — aman dari perubahan skema Dukcapil, tapi juga belum mengekspos snapshot |
| Peta (Bagian 2 landing) | `getMapKecamatan()` / `getMapDesa()` — join `laporan_agregat`, pakai `m_desa.kode_desa` | Kunci join diambil dari `m_desa.kode_desa`, bukan dari data Dukcapil |

**Catatan penting:** `profil_dukcapil` saat ini **hanya** dipakai landing page. Dashboard, API, dan peta belum membacanya. Jadi perubahan pada `profil_dukcapil` berdampak langsung ke landing page saja — plus ke mana pun kita memilih untuk menghubungkannya nanti.

---

## 2. Temuan — Tabel Pemetaan per Sheet

Konvensi kolom tabel: **Kolom Excel** → **Kolom DB** → **Status** (`✅ pas` / `⚠️ sebagian` / `❌ tidak ada`).

### Sheet 1 — `JML KK` (4 kolom: NO, KODE, DESA, JML)

| Kolom Excel | Kolom DB | Status | Catatan |
|---|---|---|---|
| `KODE` (`63.01.xx.xxxx`) | `m_desa.kode_desa` | ✅ | 135/135 cocok persis |
| `DESA` | `m_desa.nama_desa` | ⚠️ | 1 beda (`63.01.07.2013`: MEKAR SARI vs MEKAR RAYA). Huruf besar semua di Excel |
| `JML` (total KK) | **tidak ada** | ❌ | DB hanya simpan `kk_l` + `kk_p`; total selalu dihitung (`kk_l + kk_p`) |

### Sheet 2 — `JML KK PER JENIS KELAMIN` (Lk, Pr, JML)

| Kolom Excel | Kolom DB | Status | Catatan |
|---|---|---|---|
| `Lk` | `kk_l` | ✅ | |
| `Pr` | `kk_p` | ✅ | |
| `JML` | turunan | ✅ | = `kk_l + kk_p` |

### Sheet 3 — `JML JIWA` (JML)

| Kolom Excel | Kolom DB | Status | Catatan |
|---|---|---|---|
| `JML` (total jiwa) | **tidak ada** | ❌ | DB menghitung `jiwa_l + jiwa_p` |

### Sheet 4 — `JML JIWA PER JENIS KELAMIN` (Lk, Pr, JML)

| Kolom Excel | Kolom DB | Status | Catatan |
|---|---|---|---|
| `Lk` | `jiwa_l` | ✅ | |
| `Pr` | `jiwa_p` | ✅ | |
| `JML` | turunan | ✅ | |

### Sheet 5 — `JML JIWA PER USIA` (16 grup, **tanpa** pemisahan L/P)

| # | Kolom Excel | Kolom DB | Status | Catatan |
|---|---|---|---|---|
| 1 | `00-04` | `u0_4_l` + `u0_4_p` | ⚠️ | Excel gabungan; DB terpisah L/P |
| 2 | `05-09` | `u5_9_l` + `u5_9_p` | ⚠️ | idem |
| 3 | `10-14` | `u10_14_l` + `u10_14_p` | ⚠️ | idem |
| 4 | `15-19` | `u15_19_l` + `u15_19_p` | ⚠️ | idem |
| 5 | `20-24` | `u20_24_l` + `u20_24_p` | ⚠️ | idem |
| 6 | `25-29` | `u25_29_l` + `u25_29_p` | ⚠️ | idem |
| 7 | `30-34` | `u30_34_l` + `u30_34_p` | ⚠️ | idem |
| 8 | `35-39` | `u35_39_l` + `u35_39_p` | ⚠️ | idem |
| 9 | `40-44` | `u40_44_l` + `u40_44_p` | ⚠️ | idem |
| 10 | `45-49` | `u45_49_l` + `u45_49_p` | ⚠️ | idem |
| 11 | `50-54` | `u50_54_l` + `u50_54_p` | ⚠️ | idem |
| 12 | `55-59` | `u55_59_l` + `u55_59_p` | ⚠️ | idem |
| 13 | `60-64` | `u60_64_l` + `u60_64_p` | ⚠️ | idem |
| 14 | **`65-99`** | `u65_69_l/p` | ⚠️ | **Dugaan typo utk `65-69`** — lihat §3.1 bukti angkanya |
| 15 | `70-74` | `u70_74_l` + `u70_74_p` | ⚠️ | idem |
| 16 | **`75+`** | `u75_79_l/p` + `u80_84_l/p` + `u85_atas_l/p` | ⚠️ | Excel 1 angka; DB butuh 3 grup terpisah L/P |

**Grup yang tidak ada di Excel:** `u75_79`, `u80_84`, `u85_atas` — tidak bisa diisi dari file ini tanpa asumsi.

### Sheet 6 — `JML JIWA PER PENDIDIKAN` (10 tingkat, **per JIWA**)

| # | Kolom Excel | Kolom DB | Status | Catatan |
|---|---|---|---|---|
| 1 | `TIDAK/BLM SEKOLAH` | `kk_pend_tidak_sekolah` | ❌ | **Beda populasi**: Excel jiwa, DB KK |
| 2 | `BELUM TAMAT SD/SEDERAJAT` | *tidak ada* | ❌ | DB tidak punya tingkat ini |
| 3 | `TAMAT SD/SEDERAJAT` | `kk_pend_sd` | ❌ | Beda populasi |
| 4 | `SLTP/SEDERAJAT` | `kk_pend_smp` | ❌ | Beda populasi |
| 5 | `SLTA/SEDERAJAT` | `kk_pend_sma` | ❌ | Beda populasi |
| 6 | `DIPLOMA I/II` | `kk_pend_diploma` | ❌ | Beda populasi (DB gabung D1/D2/D3) |
| 7 | `AKADEMI/DIPL.III/S.MUDA` | `kk_pend_diploma` | ❌ | Beda populasi; Excel terpisah dari D1/II |
| 8 | `DIPLOMA IV/STRATA I` | `kk_pend_s1` | ❌ | Beda populasi |
| 9 | `STRATA-II` | `kk_pend_s2_s3` | ❌ | Beda populasi |
| 10 | `STRATA-III` | `kk_pend_s2_s3` | ❌ | Beda populasi |

### Sheet 7 — `JML JIWA PER PEKERJAAN` (11 jenis, **per JIWA**)

| # | Kolom Excel | Kolom DB | Status | Catatan |
|---|---|---|---|---|
| 1 | `BELUM/TIDAK BEKERJA` | `kk_ker_tidak_kerja` | ❌ | Beda populasi (jiwa vs KK) |
| 2 | `APARATUR/PEJABAT NEGARA` | `kk_ker_pns` | ❌ | Beda populasi |
| 3 | `TENAGA PENGAJAR` | *tidak ada* | ❌ | DB tidak punya kategori ini |
| 4 | `WIRASWASTA` | `kk_ker_wiraswasta` | ❌ | Beda populasi; DB punya `kk_ker_swasta` & `kk_ker_pedagang` terpisah |
| 5 | `PERTANIAN/PETERNAKAN` | `kk_ker_tani` | ❌ | Beda populasi |
| 6 | `NELAYAN` | `kk_ker_nelayan` | ❌ | Beda populasi |
| 7 | `AGAMA DAN KEPERCAYAAN` | *tidak ada* | ❌ | |
| 8 | `PELAJAR/MAHASISWA` | *tidak ada* | ❌ | DB tidak punya |
| 9 | `TENAGA KESEHATAN` | *tidak ada* | ❌ | |
| 10 | `PENSIUNAN` | *tidak ada* | ❌ | |
| 11 | `LAINNYA` | `kk_ker_buruh` | ❌ | Beda populasi & beda makna |

**Yang ada di DB tapi tidak ada di Excel:** `kk_ker_swasta`, `kk_ker_pedagang`.

### Sheet 8 — `KEPEMILIKAN DOK` (6 kolom)

| # | Kolom Excel | Kolom DB | Status | Catatan |
|---|---|---|---|---|
| 1 | `PEREKAMAN KTP-el (Wajib)` | `pend_wajib_ktp` | ✅ | Semantik sama: penduduk wajib KTP yang sudah terekam |
| 2 | `KARTU KELUARGA` | `kk_punya_kartu_fisik` | ⚠️ | Excel tidak menyebut "fisik"; lawannya (`kk_belum_punya_kartu_fisik`) harus dihitung = total KK − nilai ini |
| 3 | `AKTA LAHIR USIA 0-17` | `pend_punya_akta_lahir` | ⚠️ | **Beda rentang**: Excel eksplisit 0–17 th; DB tidak mendefinisikan rentang |
| 4 | `KARTU IDENTITAS ANAK (KIA) usia 0-16` | **tidak ada** | ❌ | Indikator baru |
| 5 | `AKTA PERKAWINAN` | `kk_punya_akta_nikah` | ⚠️ | Nama DB berprefix `kk_` (per KK); Excel tidak menyebut basisnya |
| 6 | `AKTA PERCERAIAN` | **tidak ada** | ❌ | Indikator baru (bisa dikaitkan ke `kk_cerai_hidup`/`kk_cerai_mati` tapi tidak sama) |

### 2.1 Kolom DB yang tidak tercakup Excel sama sekali

Semua kolom ini **tidak punya sumber di file ini**. Saat ini diisi `ProfilDukcapilSeeder` dengan formula turunan (bukan data resmi):

| Kelompok | Kolom | Cara seeder sekarang mengisi |
|---|---|---|
| Kelompok khusus | `jml_balita`, `jml_remaja`, `jml_lansia` | Turunan dari piramida (`u0_4_*`, `u10_14_*+u15_19_*`, `u60_64_*`..`u85_*`) |
| Status kawin | `kk_kawin`, `kk_belum_kawin`, `kk_cerai_hidup`, `kk_cerai_mati` | Data SM2/2025 (sheet "mar" di `getKecamatanData()`) |
| JKN/BPJS | `pend_bpjs`, `pend_non_bpjs`, `pus_jkn`, `pus_pbi`, `pus_non_pbi`, `jkn`, `non_jkn` | Estimasi: `jkn = round(total_jiwa × 0.80)`, `pus_pbi = round(total_jiwa × 0.25)` |
| Hubungan KK | `penduduk_hub_kepala`, `...istri`, `...anak`, `...lainnya` | Estimasi dari `kk_total` & `total_jiwa` |
| Dokumen | `pend_wajib_ktp`, `pend_punya_akta_lahir`, `kk_punya_akta_nikah`, `kk_punya_kartu_fisik`, `kk_belum_punya_kartu_fisik` | Estimasi 70%/90%/70%/95% |
| KB & PUS | `kb_aktif`, `jml_pus`, `jml_penggunaan_alat_kontrasepsi` | Estimasi dari `kk_kawin × 0.85 × 0.60` |

Artinya: walau Excel diimpor penuh, **~20 kolom DB tetap harus diisi dengan turunan/estimasi** seperti sekarang, atau dibiarkan 0 (yang akan menurunkan angka landing page secara drastis). Ini keputusan terbuka (lihat §6).

---

## 3. Gap Utama — Perbedaan Model Data

### 3.1 Piramida: 16 grup gabungan vs 18 grup × L/P

- **Excel**: 16 grup, 1 angka per grup (L+P digabung). Tidak ada pemisahan jenis kelamin.
- **DB**: 18 grup × 2 = 36 kolom (`u0_4_l` .. `u85_atas_p`).
- `statsToChartData()` menghasilkan `ageLabels` (18 label) + `piramidaL[]` + `piramidaP[]`; **piramida penduduk adalah chart dua sisi** (`piramidaLneg` di landing, `maleRaw`/`femaleData` di dashboard). Kalau L/P tidak terpisah, chart piramida kehilangan fungsi utamanya.

**Soal label `65-99` — bukti angka mendukung "typo untuk 65-69":**

| Kecamatan | Excel `65-99` | Excel `70-74` | Excel `75+` | Seed SM2/2025 `65-69` | Seed `70-74` | Seed `75+` |
|---|---|---|---|---|---|---|
| Takisung | 1.104 | 696 | 789 | 1.102 | 688 | 759 |
| Bati-Bati | 1.251 | 703 | 834 | 1.246 | 669 | 803 |
| Pelaihari | 2.440 | 1.419 | 1.515 | 2.453 | 1.337 | 1.470 |
| **TOTAL** | **10.349** | **6.028** | **7.272** | **10.256** | **5.831** | **7.018** |

Selisih `65-99` vs seed `65-69` hanya **+0,9%** (10.349 vs 10.256) — pola yang sama dengan selisih total jiwa (+0,7%). Kalau `65-99` benar-benar berarti rentang 65–99 tahun (mencakup 65-69 + 70-74 + 75+), nilainya mestinya ≈ 23.649, bukan 10.349. **Kesimpulan: `65-99` = `65-69`, rentang 65-69 tidak tumpang tindih dengan 70-74/75+.** Catat sebagai asumsi yang harus dikonfirmasi ke Dukcapil.

**Gap yang tidak bisa ditutup:** `u75_79`, `u80_84`, `u85_atas` — Excel hanya punya satu angka `75+`. Seeder sekarang memecahnya 50% / 30% / 20% (lihat `mapKecamatanData()` baris 135–140). Pemecahan apa pun = asumsi.

### 3.2 Pendidikan: per JIWA (10 tingkat) vs per KK (7 tingkat)

- **Excel**: 10 tingkat, populasi = **jiwa**. Total = total jiwa (terverifikasi: `Σ pendidikan == JML JIWA` di 135/135 desa).
- **DB**: 7 kolom `kk_pend_*`, populasi = **kepala keluarga**.
- Beda dua dimensi sekaligus: **populasi** (jiwa ≈ 378rb vs KK ≈ 132rb) dan **granularitas** (10 vs 7).

Kalau angka jiwa ditaruh ke kolom `kk_pend_*`, chart "Distribusi tingkat pendidikan pada Kepala Keluarga" (label eksplisit di `landingpage.php:1516`) menampilkan jumlah yang ~2,9× lebih besar dari jumlah KK — **secara definisi salah**. Konsumen API/eksternal yang membaca `kk_pend_s1` akan mengartikannya sebagai "jumlah KK dengan pendidikan S1".

Mapping terdekat yang *bisa* dipertahankan:

| Excel | DB | Aksi |
|---|---|---|
| `BELUM TAMAT SD` + `TAMAT SD` | `kk_pend_sd` | gabung (seperti seeder: `edu[1] + edu[2]`) |
| `DIPLOMA I/II` + `AKADEMI/DIII` | `kk_pend_diploma` | gabung |
| `STRATA-II` + `STRATA-III` | `kk_pend_s2_s3` | gabung |
| sisanya | `kk_pend_tidak_sekolah`, `kk_pend_smp`, `kk_pend_sma`, `kk_pend_s1` | 1:1 (tapi populasi beda) |

### 3.3 Pekerjaan: per JIWA (11 jenis) vs per KK (8 jenis)

Sama seperti pendidikan: **populasi beda**. `Σ pekerjaan == JML JIWA` di 135/135 desa, sedangkan DB `kk_ker_*` adalah per KK (seeder: `Σ kk_ker_*` ≈ `kk_total`).

Masalah tambahan: Excel punya kategori yang **tidak bisa dipetakan bersih** — `TENAGA PENGAJAR`, `PELAJAR/MAHASISWA`, `TENAGA KESEHATAN`, `PENSIUNAN`, `AGAMA DAN KEPERCAYAAN`. Sebaliknya DB punya `kk_ker_swasta` & `kk_ker_pedagang` yang tidak ada di Excel (Excel cuma `WIRASWASTA`). Seeder sekarang memecah `emp[3]` (wiraswasta) jadi 40% swasta / 30% pedagang / 30% wiraswasta — jatah yang kalau dipakai ulang akan mengubah makna kolom.

### 3.4 Dokumen: 6 vs 4 (+2 indikator baru)

- DB: `pend_wajib_ktp`, `pend_punya_akta_lahir`, `kk_punya_akta_nikah`, `kk_punya_kartu_fisik` (+ `kk_belum_punya_kartu_fisik`).
- Excel punya 6 kolom; **2 di antaranya baru**: KIA (0–16 th) dan Akta Perceraian.
- `AKTA LAHIR USIA 0-17` lebih sempit dari `pend_punya_akta_lahir` yang tidak mendefinisikan rentang — mengisi langsung berarti mengubah definisi indikator yang sudah dipakai landing page.

### 3.5 Yang justru aman

`kk_l`, `kk_p`, `jiwa_l`, `jiwa_p` — 4 kolom inti yang menentukan kartu ringkasan landing page (jiwa, KK, JKN/BPJS) dan chart gender. **Ini yang memberi nilai paling besar dengan risiko paling kecil.**

### 3.6 Celah teknis lain yang relevan

- `ProfilDukcapilModel::getAggregatedStats()` memakai `array_diff($this->allowedFields, $meta)` lalu `SUM(kolom)` — **kolom baru otomatis ikut teragregasi** begitu masuk `$allowedFields`. Bagus (tidak perlu ubah query), tapi berarti kolom baru langsung muncul di agregasi tanpa bisa dikecualikan.
- `ProfilDukcapilModel::getLatestPerDesa()` mengambil baris pertama setelah `ORDER BY tahun DESC, semester DESC` per desa — bila ada **dua semester** (2025/2 dan 2026/1), yang dipakai adalah 2026/1. Jadi "tambah baris baru" otomatis menggantikan baseline. Perilaku ini sudah benar, tapi harus disadari: **insert SM1/2026 = baseline landing page langsung bergeser**.
- `PublicController` menggabungkan per kolom lewat `getAggregatedStatsKeys()` — kolom yang tidak ada di daftar itu (mis. kolom baru `jiwa_pend_*`) **tidak akan ikut terhitung** di landing page kecuali ditambahkan di `getAggregatedStatsKeys()` **dan** `getAggregatedStats()` (dua tempat berbeda, rawan lupa salah satu).
- `statsToChartData()` mengakses `$s['...']` secara langsung — kalau ada key yang hilang, PHP notice/error. Kolom baru yang dipakai chart harus ditambahkan ke sana juga.

---

## 4. Dampak ke Landing Page & Dashboard (bila skema diubah)

| Area | File | Ketergantungan |
|---|---|---|
| Kartu ringkasan + note-strip | `landingpage.php` (Bagian 1) | `totals` dari `statsToChartData()`; `$profilMeta['semester'/'tahun']` |
| Tab Data Pokok / Sosio Ekonomi | `landingpage.php:1516–1590` | Label chart **hardcode**: "…pada Kepala Keluarga" (pendidikan), "Sebaran lapangan pekerjaan utama pada Kepala Keluarga" |
| Tab Piramida | `landingpage.php:1591–1630`, JS 2279–2377 | `ageLabels` (18) + `piramidaL`/`piramidaP` — perubahan jumlah grup = perubahan JS |
| Tab Kesehatan & Dokumen | `landingpage.php:1657–1690` | `$dokumen` (4 key: KTP-el, Akta Lahir, Akta Nikah, KK Fisik) & `$jkn_bpjs` (3 key) |
| Dashboard 7 chart | `dashboard/index.php:474–658` | `piramidaL/P`, `pendidikan`, `status_kawin`, `pekerjaan`; chart dokumen & JKN **hardcode `$grafik['dokumen'][...]` / `$grafik['bpjs'][...]`** per key |
| API v1 | `ApiController` | Belum baca `profil_dukcapil` — tidak terdampak, tapi juga tidak akan mengekspos data baru tanpa pekerjaan tambahan |

**Intinya:** menambah kolom ke `profil_dukcapil` relatif murah (cukup migration + `$allowedFields`). Yang mahal adalah **mengubah basis populasi** (KK → jiwa) atau **mengubah jumlah grup piramida**, karena label, teks penjelas, dan JS chart harus diubah di dua view.

---

## 5. Opsi & Trade-off

### Opsi A — Tambah kolom baru di `profil_dukcapil` (+ migration)

Isi kolom existing yang bisa dipetakan (`kk_l/kk_p/jiwa_l/jiwa_p`, `pend_wajib_ktp`, …), lalu tambah kolom baru khusus basis jiwa: `jiwa_pend_*` (10), `jiwa_ker_*` (11), `usia_total_*` (16), `dok_kia`, `dok_akta_cerai`.

- **Pro:** semua angka Excel tersimpan apa adanya, tidak ada informasi hilang; `getAggregatedStats()` otomatis ikut menjumlahkan kolom baru.
- **Kontra:** tabel jadi ~120+ kolom; ~80 kolom lama tetap harus diisi estimasi agar landing page tidak drop; butuh migration baru + `$allowedFields`; kalau kolom baru mau tampil di landing page, harus ditambah **juga** ke `getAggregatedStatsKeys()` + `getAggregatedStats()` (rawan lupa).
- **Dampak:** landing page butuh tab/chart baru untuk memakai data jiwa; dashboard tidak berubah kalau tidak dihubungkan.

### Opsi B — Tabel baru terpisah (mis. `dukcapil_sm1_2026` / `profil_dukcapil_v2`)

Skema mengikuti bentuk Excel 1:1: 1 baris per desa, ~60 kolom (16 usia, 10 pendidikan, 11 pekerjaan, 6 dokumen, kk/jiwa L/P).

- **Pro:** struktur `profil_dukcapil` tetap bersih; tidak ada kompromi semantik; gampang diaudit terhadap Excel (tinggal bandingkan total per desa); bisa dipakai buat uji silang dengan laporan desa.
- **Kontra:** **dua sumber kebenaran** — landing page harus memilih, atau kode agregasi jadi dobel; `getLatestPerDesa()`/`getAggregatedStats()` tidak akan memakainya tanpa perubahan controller; API konsumen bingung dua versi.
- **Dampak:** butuh perubahan `PublicController` untuk memilih sumber; potensi inkonsistensi tampilan antar halaman.

### Opsi C — Turunkan/agregasi ke kolom yang ada (tanpa migration)

Petakan sedapat mungkin ke kolom existing:
- `jiwa_l/jiwa_p/kk_l/kk_p` langsung.
- Piramida: Excel 16 → DB 18 dengan asumsi: `65-99`→`u65_69`, `75+` dipecah 50/30/20 ke `u75_79/u80_84/u85_atas`, lalu **pecah L/P** memakai rasio gender desa (`jiwa_l : jiwa_p`) atau rasio dari SM2/2025.
- Pendidikan & pekerjaan: **skala ulang** dari basis jiwa ke basis KK (`nilai × total_kk / total_jiwa`) atau sebaliknya simpan apa adanya ke `kk_pend_*`/`kk_ker_*` dengan catatan definisi berubah.
- `jml_balita/remaja/lansia` dihitung dari piramida hasil turunan.

- **Pro:** **nol perubahan skema**; landing page & dashboard langsung terisi; paling cepat; sesuai semangat baseline yang ada (seeder sekarang pun sudah memakai asumsi `distribute()`, rasio 0.80/0.70/0.95, dll).
- **Kontra:** angka pendidikan/pekerjaan jadi **bukan angka resmi** (hasil skala ulang); piramida L/P hasil bagi rata, bukan data; label chart "pada Kepala Keluarga" jadi menyesatkan kalau tidak diubah; asumsi tersimpan di kode impor, sulit diaudit belakangan.
- **Dampak:** tampilan langsung berubah (total jiwa +0,7%); kalau rasio L/P dipakai untuk piramida, bentuk piramida akan mirip data asli tapi tidak persis.

### Opsi D — Abaikan sebagian / impor sebagian

Impor hanya sheet yang aman (`JML KK`, `JML JIWA`, `JML KK/JIWA PER JENIS KELAMIN`, `KEPEMILIKAN DOK`), sisanya (`USIA`, `PENDIDIKAN`, `PEKERJAAN`) dibiarkan mengikuti baseline lama / turunan.

- **Pro:** paling konservatif; tidak ada asumsi baru; kolom inti (jiwa, KK, gender, dokumen) jadi data resmi.
- **Kontra:** piramida, pendidikan, pekerjaan tetap memakai angka turunan SM2/2025 yang sudah kedaluwarsa — padahal justru itu yang paling terlihat di landing page.
- **Dampak:** minimal; hanya kartu ringkasan & chart gender/dokumen yang bergeser.

### 5.1 Ringkasan trade-off

| Opsi | Perubahan skema | Data resmi tersimpan | Asumsi/estimasi | Risiko tampilan | Usaha |
|---|---|---|---|---|---|
| A kolom baru | Migration + `$allowedFields` | ✅ lengkap | sedang (kolom lama tetap estimasi) | sedang (butuh view baru) | besar |
| B tabel baru | Tabel baru | ✅ lengkap & bersih | rendah | tinggi (dua sumber) | besar |
| C agregasi ke existing | **tidak ada** | ❌ sebagian | tinggi (skala ulang + rasio gender) | rendah–sedang | **kecil** |
| D impor sebagian | tidak ada | ❌ sebagian | sedang | rendah | kecil |

---

## 6. Rekomendasi

**Pilih Opsi C sebagai default minimal-invasive, dengan syarat eksplisit**, dan sediakan jalur eskalasi ke Opsi A/B bila user menolak asumsi.

Alasan:
1. `profil_dukcapil` **hanya** dikonsumsi landing page; dashboard & API tidak terdampak. Biaya salah langkah rendah.
2. Baseline yang ada sekarang **sudah** berisi asumsi (`distribute()` acak, `jkn = 0.80 × jiwa`, `akta lahir = 0.90 × jiwa`). Menggantinya dengan angka resmi + asumsi yang lebih sedikit **jelas merupakan peningkatan**, bukan regresi.
3. Excel terbukti merupakan penerus langsung SM2/2025 (selisih +0,7% jiwa / +0,9% KK, konsisten di 11 kecamatan) — jadi mengganti snapshot, bukan menambah sumber baru, adalah langkah yang benar secara konseptual.
4. Tidak ada migration = tidak ada risiko merusak `insertBatch` seeder atau `$allowedFields`.

### 6.1 Bentuk konkret Opsi C yang direkomendasikan

| Sheet | Aksi | Asumsi yang dipakai (harus dicatat) |
|---|---|---|
| `JML KK`, `JML JIWA`, `... PER JENIS KELAMIN` | Langsung ke `kk_l/kk_p/jiwa_l/jiwa_p` | — |
| `JML JIWA PER USIA` | 16 grup → DB 18 grup; `65-99`→`u65_69`; `75+` dipecah 50/30/20 ke `u75_79/u80_84/u85_atas`; pecah L/P dengan rasio `jiwa_l:jiwa_p` per desa | **3 asumsi**: typo `65-99`, rasio pecah 75+, rasio gender flat per grup |
| `JML JIWA PER PENDIDIKAN` | Gabung ke 7 tingkat, lalu **skala ulang** ke basis KK (`× kk_total / total_jiwa`) | Pengguna diperingatkan bahwa ini **bukan** angka resmi per-KK |
| `JML JIWA PER PEKERJAAN` | Gabung ke 8 jenis (pengajar→PNS?, pensiunan/lainnya→tidak_kerja/lain), skala ulang ke basis KK | Pemetaan kategori bersifat judgement |
| `KEPEMILIKAN DOK` | `PEREKAMAN KTP-el`→`pend_wajib_ktp`; `KARTU KELUARGA`→`kk_punya_kartu_fisik` (+ lawannya = `kk_total − nilai`); `AKTA LAHIR 0-17`→`pend_punya_akta_lahir`; `AKTA PERKAWINAN`→`kk_punya_akta_nikah` | Rentang usia akta lahir menyempit jadi 0–17 |
| KIA & Akta Perceraian | **Simpan di kolom baru** (2 kolom) atau abaikan dulu | Butuh migration kalau disimpan |
| Kolom tanpa sumber (JKN, KB, kawin, hub-KK) | Pertahankan formula turunan seeder yang ada | Tetap estimasi |

**Syarat:** setiap asumsi harus terdokumentasi di kode impor (komentar) dan — bila memungkinkan — ditampilkan sebagai catatan kaki di landing page, supaya angka turunan tidak dibaca sebagai angka resmi.

### 6.2 Kapan sebaiknya naik ke Opsi A atau B

- Naik ke **A** bila: user ingin angka per-jiwa tampil apa adanya (grafik pendidikan/pekerjaan berbasis jiwa), atau ingin KIA & akta cerai ikut tersimpan.
- Naik ke **B** bila: user ingin arsip semester berjalan berdampingan dan bisa dibandingkan (mis. grafik tren SM2/2025 vs SM1/2026), atau ingin audit 1:1 terhadap Excel tanpa transformasi.

---

## 7. Pertanyaan Terbuka untuk User

1. **SM1/2026 menggantikan atau melengkapi SM2/2025?**
   - (a) Ganti: upsert baris `(id_desa, 2026, 1)`; `getLatestPerDesa()` otomatis memakai yang baru.
   - (b) Lengkapi: simpan keduanya, landing page pilih terbaru; perlu UI/tab perbandingan.
   - Rekomendasi sementara: (a).

2. **Apakah chart pendidikan & pekerjaan di dashboard/landing page boleh pindah dari basis KK ke basis jiwa?**
   - Kalau ya → wajib Opsi A (kolom baru) + ubah label chart di `landingpage.php:1516,1540` & `dashboard/index.php`.
   - Kalau tidak → angka jiwa harus diskala ulang (Opsi C) dan labelnya menyesatkan.

3. **Apakah pemecahan piramida L/P boleh memakai rasio gender desa?** Data Excel tidak memisahkan L/P; alternatifnya memakai rasio SM2/2025 per desa, atau menampilkan piramida "gabungan" (chart jadi batang tunggal, bukan dua sisi).

4. **`65-99` memang typo untuk `65-69`?** Bukti angka mengarah ke situ, tapi perlu konfirmasi Dukcapil sebelum dipakai.

5. **Bagaimana memperlakukan `75+` yang harus dipecah jadi 3 grup DB (75-79, 80-84, 85+)?** Ikuti rasio 50/30/20 seperti seeder, atau rasio dari SM2/2025 per desa?

6. **Apakah desa Excel harus dicocokkan ke `m_desa.kode_desa` atau cukup nama?** Fakta: kode 135/135 cocok, nama 1 beda (`63.01.07.2013`: Excel `MEKAR SARI`, DB `MEKAR RAYA`). Rekomendasi: **join by kode**, dan laporkan 1 beda nama itu ke admin_dinas (apakah nama DB yang salah?).

7. **Apakah KIA (0–16) dan Akta Perceraian perlu disimpan?** Kalau ya → migration (2 kolom baru) meski kita memilih Opsi C.

8. **Bagaimana mengisi ~20 kolom yang tidak ada di Excel (JKN/BPJS, KB/PUS, status kawin, hubungan KK)?**
   - (a) Pertahankan estimasi seeder (0.80 JKN, 0.70 KTP, dst).
   - (b) Kosongkan/0 → kartu ringkasan landing page turun drastis.
   - (c) Minta sheet tambahan ke Dukcapil.

9. **Apakah data ini boleh diekspos lewat API?** Saat ini `profil_dukcapil` tidak dibaca `ApiController` sama sekali. Perlu keputusan terpisah bila konsumen eksternal butuh snapshot.

10. **Apakah impor harus berupa seeder atau command CLI?** Seeder sekarang melakukan `truncate()` lalu `insertBatch()` — berbahaya kalau dipakai untuk impor produksi. Rekomendasi: **command `php spark dukcapil:import`** dengan dry-run, bukan seeder (seperti pola `BkkbnWarmup`).

11. **Bagaimana menyikapi beda tanggal potong sheet `KEPEMILIKAN DOK` (30-09-2026) vs sheet lainnya (Semester I 2026 / 30-06)?**
    - (a) Terima apa adanya, beri catatan kaki di landing page.
    - (b) Minta ulang sheet dokumen per 30 Juni ke Dukcapil.
    - (c) Simpan tanggal potong sebagai kolom metadata per baris (butuh migration) supaya bisa ditelusuri.
    - Rekomendasi sementara: (b) kalau mudah, (a) bila tidak — karena rasio cakupan dokumen jadi tidak bisa dibandingkan bersih dengan angka jiwa/KK semester I.

---

## 8. Rencana Verifikasi

Verifikasi di bawah ini untuk rencana implementasi nanti — **belum dijalankan**, karena tugas ini hanya menghasilkan memo.

### 8.1 Verifikasi data (sebelum impor)

1. **Parse ulang 8 sheet** dan konfirmasi: 135 desa, 11 blok kecamatan, tidak ada kode duplikat. *(Sudah diverifikasi saat penyusunan memo — ✅ lolos.)*
2. **Uji konsistensi internal per desa** (135 baris): `JML JIWA == Σ usia == Σ pendidikan == Σ pekerjaan`; `KK Lk + KK Pr == JML KK`; `Jiwa Lk + Jiwa Pr == JML JIWA`. *(Sudah diverifikasi — ✅ 0 mismatch.)*
3. **Join kode**: `Excel.KODE` ↔ `m_desa.kode_desa` — hasil 135/135 cocok, 1 beda nama. Laporkan `63.01.07.2013` (MEKAR SARI vs MEKAR RAYA). *(Sudah diverifikasi — ✅.)*
4. **Sanity check agregat kabupaten**: total jiwa 378.190 vs baseline 375.583 (**+0,7%**), total KK 132.030 vs 130.908 (**+0,9%**). Bila impor menghasilkan selisih di luar rentang ini berarti ada baris yang salah masuk. *(Sudah diverifikasi — ✅.)*
5. **Cek banding per kecamatan**: pastikan 11 kecamatan semuanya naik 0,4–1,1% (bukan ada yang turun tajam / melonjak).
6. **Putuskan sikap atas beda tanggal potong** (dokumen = 30-09-2026 vs sheet lain = Semester I/30-06) sebelum impor — lihat pertanyaan terbuka #11. Ini menentukan apakah 6 kolom dokumen boleh masuk apa adanya.
7. **Cross-check dengan artefak ekstraksi** `tests/data/dukcapil_sm1_2026.json` (jika dipakai sebagai sumber impor): pastikan totalnya sama (135 desa, jiwa 378.190, KK 132.030) dan label usia sudah `65-69` (bukan `65-99`). *(Sudah diverifikasi saat memo disusun — ✅ identik.)*

### 8.2 Verifikasi setelah impor (akan dijalankan saat implementasi)

1. **Row count**: `SELECT COUNT(*) FROM profil_dukcapil WHERE tahun=2026 AND semester=1` → **135**.
2. **Total per kecamatan**: bandingkan `SUM(jiwa_l + jiwa_p)` per kecamatan dengan total Excel per kecamatan (toleransi 0 — angka jiwa diimpor langsung).
3. **Konsistensi turunan**: `jml_balita == u0_4_l + u0_4_p`, `jml_lansia == Σ u60_64_*..u85_*`, `kk_l + kk_p == kk_total`.
4. **Piramida**: `Σ semua u*_l + u*_p == jiwa_l + jiwa_p` (memastikan pemecahan L/P tidak mengubah total).
5. **Landing page**:
   - Buka `/` dan `/?sumber=dukcapil` → kartu jiwa/KK harus ≈ 378.190 / 132.030 (mode dukcapil murni).
   - `?sumber=desa` → tidak berubah dari sebelum impor.
   - Mode campuran → tidak ada double-count (total ≤ max(laporan, dukcapil) per desa).
   - `$profilMeta['semester'/'tahun']` → menampilkan **Semester I 2026** di note-strip.
   - Tab Piramida & Data Pokok tidak error (tidak ada "Undefined array key").
6. **Dashboard** `/dashboard` → 7 chart tetap render (dashboard tidak baca `profil_dukcapil`, jadi harusnya **identik** dengan sebelum impor — kalau berubah, ada yang salah).
7. **API**: `GET /api/v1/laporan` dan `/api/v1/map/desa` → response tidak berubah (konfirmasi tidak ada efek samping).
8. **Regression**: jalankan `php spark db:seed DatabaseSeeder` di DB cadangan → pastikan `ProfilDukcapilSeeder` masih jalan (ingat: seeder melakukan `truncate()`, jadi jangan dijalankan di DB yang sudah berisi impor resmi).

### 8.3 Hal yang harus diwaspadai

- `ProfilDukcapilSeeder` melakukan **`truncate()`** pada `profil_dukcapil` (baris 107). Menjalankan `DatabaseSeeder` setelah impor resmi akan **menghapus data SM1/2026**. Implementasi impor sebaiknya berupa command terpisah, bukan seeder.
- `getLatestPerDesa()` memilih berdasar `tahun DESC, semester DESC` — insert semester 1/2026 langsung mengalahkan semester 2/2025. Pastikan ini memang yang diinginkan (pertanyaan #1).
- Menambah kolom ke `$allowedFields` membuatnya otomatis ikut `SUM()` di `getAggregatedStats()`, tapi **tidak** otomatis ikut landing page (harus juga masuk `getAggregatedStatsKeys()` + `getAggregatedStats()` + `statsToChartData()`).

---

## 9. Lampiran — Referensi Kode

| File | Peran |
|---|---|
| `app/Models/ProfilDukcapilModel.php` | `$allowedFields` (~80 kolom), `getLatestPerDesa()`, `getAggregatedStats()` |
| `app/Database/Migrations/2026-10-01-000001_CreateProfilDukcapil.php` | Daftar kolom statistik, unique `(id_desa, tahun, semester)` |
| `app/Database/Seeds/ProfilDukcapilSeeder.php` | Sumber baseline SM2/2025; **truncate + insertBatch**; reuse `LaporanAgregatSeeder::getKecamatanData()/mapKecamatanData()/distribute()` |
| `app/Database/Seeds/LaporanAgregatSeeder.php` | `getKecamatanData()` (data per kecamatan), `mapKecamatanData()` (termasuk pecah 75+ = 50/30/20), `distribute()` (acak 0.90–1.10) |
| `app/Models/LaporanAgregatModel.php` | `getAggregatedStats()`, `getAggregatedStatsKeys()`, `statsToChartData()` (18 `ageLabels`, chart key), `mapIndicatorFields()` |
| `app/Controllers/PublicController.php` | `landingpage()` baris 68–165: filter `?sumber=`, penggabungan laporan + dukcapil, `$profilMeta` |
| `app/Views/public/landingpage.php` | Tab Bagian 1 (1516–1690), JS ZingChart (2279–2623) |
| `app/Controllers/DashboardController.php` / `app/Views/dashboard/index.php` | 7 chart; belum baca `profil_dukcapil` |
| `app/Database/Migrations/2026-01-11-052035_CreateDesa.php` | `m_desa.kode_desa` VARCHAR(50) — format `63.01.xx.xxxx` |
| `app/Commands/BkkbnWarmup.php` | Pola command CLI yang bisa ditiru untuk impor (bukan seeder) |
| `tests/data/dukcapil_sm1_2026.json` | Hasil ekstraksi Excel (worker paralel): 11 kecamatan → 135 desa, kunci `kode`, field `kk_total/kk_lk/kk_pr/jiwa_total/jiwa_lk/jiwa_pr/usia/pendidikan/pekerjaan/dokumen`; `catatan` berisi tanggal potong per kelompok sheet |
| `docs/superpowers/specs/2026-10-09-audit-excel-sm1-2026.md` | Audit ekstraksi (worker paralel) — rujukan silang untuk detail per-sheet |
