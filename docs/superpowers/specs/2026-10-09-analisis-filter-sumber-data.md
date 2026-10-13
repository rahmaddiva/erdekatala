# Analisis: Filter Kecamatan & Desa — Data dari Mana?

**Tanggal:** 2026-10-09
**Scope:** `PublicController::landingpage()` (`/`), `PublicController::kecamatan()` (`/{slug}`), model agregasi, view `public/landingpage.php`
**Jenis:** Audit/analisis kode — **tidak ada kode aplikasi, migration, seeder, config, atau DB yang diubah.**
**Status error `dok_kia`:** saat pengujian (2026-10-09) landing page **merespons HTTP 200 normal**, tidak lagi error `Unknown column 'dok_kia'`. Semua angka di bawah diambil dari render HTTP sungguhan + query DB read-only.

---

## 0. Ringkasan eksekutif (jawaban singkat)

> **Filter Kecamatan/Desa tidak memilih sumber data — ia hanya mempersempit `desaScope` (daftar `id_desa`). Sumber data ditentukan oleh parameter `?sumber=` dan oleh fakta "desa ini sudah lapor atau belum".**
>
> **Kondisi DB saat ini: 135/135 desa sudah punya baris di `laporan_agregat`.** Akibatnya mode "campuran otomatis" (default, tanpa `?sumber=`) **berujung 100% laporan desa** — Dukcapil tidak menyumbang satu pun angka, kecuali dua indikator (KIA & Akta Cerai) yang di-override dari Dukcapil secara terpisah.

Tiga temuan utama:

1. **Default = laporan desa, bukan campuran** (terbukti angka: Total Jiwa 380.525 identik dengan `?sumber=desa`, berbeda dari `?sumber=dukcapil` 378.190).
2. **Dua indikator (KIA & Akta Cerai) selalu dari Dukcapil** meski label/pill/note-strip bilang "laporan desa" — ketidaksesuaian label nyata.
3. **Total Jiwa terinflasi 4.942 jiwa** karena `getAggregatedStats()` menjumlahkan semua baris laporan tanpa filter periode, dan 2 desa punya 2 baris (12/2025 + 7/2026 / 8/2026).

---

## 1. Fakta DB (verifikasi read-only)

Skrip: `scratch/audit_sumber_data.php`, `scratch/audit_sumber_data2.php`, `scratch/audit_sumber_data3.php` (semua read-only, hanya `SELECT` / `SHOW COLUMNS`).

| Metrik | Nilai |
|---|---|
| Total desa (`m_desa`) | **135** |
| Desa di kecamatan `is_public=1` | **135** |
| Desa punya baris di `laporan_agregat` (distinct) | **135** |
| Baris `laporan_agregat` | **137** (2 desa punya 2 baris) |
| Baris `profil_dukcapil` | **135** (1 baris per desa, tidak ada duplikat) |
| Snapshot Dukcapil | semester **1 / 2026**, 135 desa |
| Kecamatan tanpa satu pun desa lapor | **0** |
| Desa belum lapor | **0** |

**Pembuktian "campuran = laporan":**

| Mode agregasi | `jiwa_l + jiwa_p` |
|---|---|
| Murni laporan (`SUM` semua baris `laporan_agregat`) | **380.525** |
| Murni Dukcapil (`SUM` `profil_dukcapil`) | **378.190** |
| Campuran (laporan utk desa lapor + Dukcapil utk sisanya) | **380.525** ← identik dengan murni laporan |

Karena `desaLaporIds == desaScope` (135 = 135), `array_diff($desaScope, $desaLaporIds)` = `[]` → `getAggregatedStats([])` mengembalikan semua nol (`ProfilDukcapilModel.php:81-84`). Jadi Dukcapil menyumbang **0**.

**Distribusi periode laporan (temuan sampingan):**

| Periode | Baris | Desa | jiwa_l | jiwa_p |
|---|---|---|---|---|
| 12/2025 | 135 | 135 | 190.759 | 184.824 |
| 7/2026 | 1 | 1 (Ranggang, Takisung) | 1.512 | 1.370 |
| 8/2026 | 1 | 1 (Batu Ampar, Batu Ampar) | 1.078 | 982 |

`getAggregatedStats()` (`LaporanAgregatModel.php:251-311`) **tidak punya filter bulan/tahun** → Ranggang & Batu Ampar terhitung **dua kali**. Total benar untuk 1 periode = **375.583**; yang ditampilkan **380.525** (selisih **+4.942 jiwa**).

---

## 2. Alur kode penentu sumber (bukti baris)

| Langkah | Lokasi | Perilaku |
|---|---|---|
| Resolve slug kecamatan → `id_kecamatan_filter` | `PublicController.php:40-48` | Wajib `is_public=1`; slug tidak dikenal → `null` (diam-diam) |
| Resolve slug desa → `id_desa_filter` | `PublicController.php:51-59` | **Hanya diproses kalau kecamatan berhasil di-resolve** |
| Normalisasi `?sumber=` | `PublicController.php:74-77` | Hanya `desa` / `dukcapil` diterima; selain itu → `''` (campuran) |
| Bangun `desaScope` | `PublicController.php:80-90` | 1 desa / semua desa 1 kec / **semua desa di `m_desa`** |
| Desa sudah lapor (`desaLaporIds`) | `PublicController.php:93-101` | `SELECT DISTINCT id_desa FROM laporan_agregat WHERE id_desa IN (scope)` — **tanpa filter periode** |
| Cabang `dukcapil` | `PublicController.php:108-111` | Dukcapil utk seluruh scope; `desaLaporIds` dikosongkan |
| Cabang `desa` | `PublicController.php:112-117` | Hanya laporan |
| Cabang campuran | `PublicController.php:118-128` | `$dukcapilIds = array_diff($desaScope, $desaLaporIds)` (baris 122); laporan utk sisanya |
| Gabung per kolom | `PublicController.php:139-144` | `$stats[$k] = laporan[$k] + dukcapil[$k]` |
| `profilMeta['mode']` | `PublicController.php:153-163` | `sumberFilter` kalau ada; kalau tidak → `laporanCount ? (dukcapilCount ? 'campuran' : 'laporan') : 'dukcapil'` |
| Override KIA/Akta Cerai | `PublicController.php:173-179` | **Selalu dari Dukcapil atas seluruh `desaScope`**, kecuali `?sumber=desa` (di-unset) |

---

## 3. Tabel keadaan filter → sumber angka

Sumber angka = Ringkasan Data (Total Jiwa/KK/JKN) + Profil Kependudukan (kecuali KIA/Akta Cerai) + Indikator Turunan.

| # | Keadaan | `desaScope` | Sumber angka | Bukti kode | Bukti angka (jiwa / KK) |
|---|---|---|---|---|---|
| 1 | **Default** (tanpa parameter) | 135 desa | **100% laporan desa** (mode `laporan`) | `108-128` → `dukcapilIds=[]`; `160-162` → mode `laporan` | **380.525 / 132.696** = persis `?sumber=desa` |
| 2 | `?kecamatan=bajuin` | 9 desa Bajuin | **100% laporan desa** (9/9 sudah lapor) | sama | **20.676 / 7.451** (vs Dukcapil 20.774 / 7.500) |
| 3 | `?kecamatan=bajuin&desa=bajuin` | **1 desa** | **100% laporan desa** — campuran jadi per-desa | `81-82` | **2.299 / 864** (vs Dukcapil 2.180 / 810) |
| 4 | `?sumber=dukcapil` (± wilayah) | sesuai scope | **100% Dukcapil** | `108-111` | Kab: **378.190 / 132.030**; Bajuin: **20.774 / 7.500**; Desa Bajuin: **2.180 / 810** |
| 5 | `?sumber=desa` (± wilayah) | sesuai scope | **100% laporan desa**; KIA/Akta Cerai disembunyikan | `112-117`, `173-174` | Kab: **380.525 / 132.696**; Bajuin: **20.676 / 7.451** |

**Catatan penting untuk #1–#3:** meski `?sumber=` tidak diisi (dropdown menampilkan *"Dukcapil + Laporan Desa (otomatis)"*), hasilnya **bukan campuran**. Mode `campuran` baru muncul kalau ada desa di scope yang belum lapor. Saat ini tidak ada → `profilMeta['mode'] = 'laporan'`.

### Khusus KIA & Akta Cerai (berbeda dari tabel di atas)

`PublicController.php:173-179`: selama bukan `?sumber=desa`, KIA & Akta Cerai **selalu** diambil dari Dukcapil atas **seluruh** `desaScope` — bukan hanya desa yang belum lapor.

| Keadaan | KIA | Akta Cerai | Jumlah "jenis" di kartu |
|---|---|---|---|
| Default | **76.078** (Dukcapil) | **6.010** (Dukcapil) | 6 jenis |
| `?sumber=dukcapil` | 76.078 | 6.010 | 6 jenis |
| `?sumber=desa` | disembunyikan | disembunyikan | **4 jenis** |

---

## 4. Jawaban 9 pertanyaan

**Q1 — Default (tanpa parameter): sumber mana?**
Desainnya campuran (laporan menimpa Dukcapil per desa). **Faktanya saat ini: 100% laporan desa**, karena 135/135 desa sudah lapor sehingga himpunan "desa yang pakai Dukcapil" kosong (`PublicController.php:122` → `array_diff` = `[]`). Terbukti: Total Jiwa default 380.525 **identik** dengan `?sumber=desa` dan berbeda dari `?sumber=dukcapil` (378.190). **Pengecualian:** KIA & Akta Cerai tetap dari Dukcapil (baris 176-178).

**Q2 — `?kecamatan=<slug>`: sumber mana?**
Sama seperti Q1: **100% laporan desa** untuk desa-desa di kecamatan itu (Bajuin 9/9 sudah lapor → 20.676). Filter kecamatan hanya mempersempit `desaScope` (baris 83-87); ia **tidak** memilih sumber.

**Q3 — `?kecamatan=<slug>&desa=<slug>`: sumber mana? Apakah `desaScope` jadi 1 desa dan campuran jadi per-desa?**
**Ya.** `desaScope = [$id_desa_filter]` (baris 81-82). Mode tetap campuran (default), sehingga logikanya menjadi per-desa: kalau desa itu punya baris laporan → pakai laporan; kalau tidak → pakai snapshot Dukcapil desa itu. Desa Bajuin sudah lapor → **2.299 jiwa** dari laporan (Dukcapil: 2.180).

**Q4 — `?sumber=dukcapil` (dengan/tanpa filter wilayah)?**
**100% Dukcapil** untuk seluruh `desaScope` (baris 108-111), laporan desa diabaikan total (`desaLaporIds` dikosongkan). Berlaku konsisten di tingkat kabupaten (378.190), kecamatan (20.774), dan desa (2.180).

**Q5 — `?sumber=desa` (dengan/tanpa filter wilayah)?**
**100% laporan desa** (baris 112-117). KIA & Akta Cerai dihapus dari chart (baris 173-174) karena `laporan_agregat` tidak punya kolom itu (terbukti: `SHOW COLUMNS FROM laporan_agregat LIKE 'dok_kia'` → kosong).

**Q6 — Apakah pill "Sumber:" dan note-strip SELALU konsisten?** → **Tidak.** Daftar ketidaksesuaian di §5.

**Q7 — Apakah "Indikator Turunan" memakai sumber yang sama dengan Ringkasan/Profil?**
**Ya, sama** — semuanya diturunkan dari satu array `$chart` hasil `statsToChartData($stats)` (baris 165), yang berasal dari `$stats` gabungan (baris 139-144). `$piramidaL/$piramidaP`, `$totals`, `$dokumen['KK Fisik']` semuanya dari situ.
**Namun:** di dalam satu halaman yang sama, "Dokumen Kependudukan" (KIA/Akta Cerai) memakai sumber **berbeda** (Dukcapil murni) dari Profil Kependudukan lainnya. Jadi konsistensi internal ada **satu pengecualian**: chart dokumen.

**Q8 — Kasus tepi:**
- **(a) Kecamatan tanpa desa lapor → jatuh ke Dukcapil?** **Ya.** `desaLaporIds = []` → `array_diff` = seluruh desa kecamatan → Dukcapil penuh; `statsLaporan` = nol; `mode` = `'dukcapil'` (baris 160-162). Saat ini tidak ada kecamatan seperti itu (0 dari 11).
- **(b) Desa belum lapor dalam mode campuran → pakai Dukcapil per desa?** **Ya**, persis begitu: desa itu masuk `$dukcapilIds` (baris 122) dan disumbangkan oleh `ProfilDukcapilModel::getAggregatedStats($dukcapilIds)` (baris 123).
- **(c) `?sumber=dukcapil` + `?desa=` yang tidak ada di scope?** Parameter `desa` **diabaikan** (karena `$id_desa_filter` tetap `null`), dan scope jatuh ke level berikutnya: tanpa `?kecamatan=` → seluruh kabupaten (378.190, skop "Kabupaten"); dengan `?kecamatan=bajuin&desa=tidak-ada&sumber=dukcapil` → 9 desa Bajuin (20.774) tetapi **skop tetap tertulis "Desa"** → lihat ketidaksesuaian #3.

**Q9 — "Jelajahi Kecamatan" memakai filter yang sama?**
**Tidak.** Kartu di `landingpage.php:2271-2279` menaut ke `/{slug}` → `Routes.php:134` → **`PublicController::kecamatan()`**, halaman berbeda.

| | `/` (`landingpage()`) | `/{slug}` (`kecamatan()`) |
|---|---|---|
| Sumber angka | laporan +/atau Dukcapil, tergantung `?sumber=` & status lapor | **Hanya `laporan_agregat`** (`PublicController.php:409-413`) |
| Filter `?sumber=` | ada | **tidak ada** — tidak ada Dukcapil sama sekali |
| Fallback Dukcapil utk desa belum lapor | ada (mode campuran) | **tidak ada** — desa belum lapor = 0 |
| KIA / Akta Cerai | ada (dari Dukcapil) | **selalu dihapus** (`PublicController.php:417`) |
| Filter desa | `?desa=<slug>` | `?desa=<slug>` (resolusi manual, `PublicController.php:396-406`) |
| Bukti angka | Bajuin `?sumber=dukcapil` = 20.774 | `/bajuin` = **20.676** (= nilai laporan) |

---

## 5. Daftar ketidaksesuaian label

| # | Lokasi | Label yang tampil | Sumber angka sebenarnya | Tingkat |
|---|---|---|---|---|
| **1** | `landingpage.php:1479` (dropdown) + `1285-1289` (pill) | Dropdown terpilih **"Dukcapil + Laporan Desa (otomatis)"** | **100% laporan desa** (mode `laporan`, Dukcapil menyumbang 0) | Sedang — pill "laporan desa" benar, tapi dropdown menjanjikan campuran |
| **2** | `landingpage.php:1285-1289`, `1392-1393`, `1457-1466` | pill "**laporan desa**", note-strip "**diturunkan dari laporan desa**", badge "**Laporan Desa**" | **KIA 76.078 & Akta Cerai 6.010 dari Dukcapil** (`PublicController.php:176-178`) | **Tinggi** — nama sumber eksplisit, angkanya bukan dari sumber itu |
| **3** | `landingpage.php:15` & `1342` | Skop "**Desa**" / "desa terpilih" saat `?desa=<slug tidak valid>` | Angka masih **seluruh kecamatan** (20.676, cakupan 9/9) — `$filter_desa_slug` dipakai mentah, bukan `$id_desa_filter` | **Tinggi** — label skop berbohong |
| **4** | `landingpage.php:1395` | "Filter wilayah di atas menyesuaikan **seluruh grafik dan tabel** di bawah" | Tidak berlaku untuk: YanKB (`PublicController.php:189-190`, tanpa argumen kecamatan), Dallap (`:194`, tanpa argumen kecamatan), dan Sebaran Spasial (`:176-179`, tidak mengikuti `?sumber=`) | Sedang |
| **5** | `landingpage.php:1796` | Badge hardcoded "**Laporan Desa**" di Sebaran Spasial | Benar (peta dari `getMapKecamatan()` → `laporan_agregat` saja), tapi **tidak pernah berubah** saat `?sumber=dukcapil` | Rendah — konsisten, tapi tidak mengikuti filter |
| **6** | `landingpage.php:1356-1361`, `1396-1398` | "Cakupan Laporan **135/135 desa**", "Periode laporan terbaru: **Agustus 2026**" | Tetap tampil di mode `?sumber=dukcapil`, padahal angka di halaman 100% Dukcapil semester 1/2026 | Rendah — membingungkan, bukan salah |
| **7** | `PublicController.php:189-194` vs `181` | Tidak ada label | KRS ikut filter kecamatan, YanKB & Dallap **tidak** — ketidakkonsistenan internal antar-tabel BKKBN | Rendah |
| **8** | `PublicController.php:89` | — | `desaScope` default = `desaModel->findAll()` (**semua** desa, termasuk kecamatan `is_public=0`), sementara `getCoverage()` (`:184-216`) memfilter `is_public=1` | Rendah (tidak aktif: 135 = 135) |

**Yang sudah konsisten:** mode `?sumber=dukcapil` (pill "Dukcapil", note-strip menyebut semester 1/2026, badge "Dukcapil Kab. Tanah Laut" — semuanya cocok dengan angka 378.190) dan mode `?sumber=desa` (semua label "laporan desa", KIA/Akta Cerai disembunyikan).
Badge "N desa lapor · M Dukcapil" (`landingpage.php:1467-1471`) **tidak pernah muncul** saat ini karena mode tidak pernah `campuran`.

---

## 6. Temuan sampingan (di luar pertanyaan, tapi material)

**Duplikasi periode → Total Jiwa terinflasi.** `LaporanAgregatModel::getAggregatedStats()` (`251-311`) menjumlahkan **semua** baris `laporan_agregat` tanpa filter `bulan/tahun`. Dua desa punya 2 baris dengan nilai identik:
- Ranggang (Takisung): 12/2025 + 7/2026 → 1.512 L + 1.370 P dihitung dua kali
- Batu Ampar (Batu Ampar): 12/2025 + 8/2026 → 1.078 L + 982 P dihitung dua kali

Dampak: Total Jiwa 380.525 seharusnya **375.583** untuk satu periode (+4.942). Ini ikut memengaruhi Ringkasan Data, Profil Kependudukan, dan seluruh Indikator Turunan (rasio ketergantungan, usia produktif, jiwa/KK). Hal yang sama berlaku di halaman `/{slug}`.

Catatan: komentar di `PublicController.php:120-121` mengklaim "tidak ada double-count walau desa punya banyak baris laporan" — klaim itu **tidak berlaku** untuk desa dengan banyak baris; yang dicegah hanya tumpang-tindih Dukcapil↔laporan.

---

## 7. Rekomendasi singkat (tanpa implementasi)

1. **Pisahkan label sumber untuk indikator Kelompok A.** KIA & Akta Cerai butuh penanda sendiri ("Dukcapil") di chart/tabel Dokumen Kependudukan, atau ikut di-unset juga saat bukan mode Dukcapil — jangan dibiarkan di bawah label "laporan desa".
2. **Perbaiki label skop Q3/8c.** `landingpage.php:15` & `1342` harus memakai hasil resolusi (`$id_desa_filter`), bukan string `?desa=` mentah.
3. **Tambahkan filter periode** di `getAggregatedStats()` (atau agregasi "1 baris terbaru per desa") agar total tidak dobel; alternatifnya tampilkan periode yang dipakai di Ringkasan.
4. **Sederhanakan label default.** Kalau mode faktualnya `laporan`, dropdown sebaiknya menampilkan "Laporan Desa (semua desa sudah lapor)" + info "135/135 desa lapor", bukan "Dukcapil + Laporan Desa (otomatis)".
5. **Seragamkan `?sumber=` lintas halaman** — `/{slug}` sebaiknya menerima `?sumber=` juga, atau diberi keterangan eksplisit bahwa ia murni laporan desa (tanpa KIA/Akta Cerai).
6. **Batasi `desaScope` ke `is_public=1`** agar konsisten dengan `getCoverage()`.
7. **Koreksi teks note-strip** "menyesuaikan seluruh grafik dan tabel di bawah" → sebutkan pengecualian (YanKB, Dallap, peta).

---

## 8. Artefak verifikasi (scratch/, read-only)

| File | Isi |
|---|---|
| `scratch/audit_sumber_data.php` | 135 vs 135 desa lapor, per-kecamatan, SUM 3 mode, ada/tidaknya `dok_kia` |
| `scratch/audit_sumber_data2.php` | Slug desa Bajuin, desa multi-baris, simulasi "desa belum lapor", baris per desa Dukcapil |
| `scratch/audit_sumber_data3.php` | Rincian baris multi-periode, distribusi periode, SUM per periode |
| `scratch/audit_*.html` | Render HTTP sungguhan: `default`, `dukcapil`, `desa`, `kec_bajuin`, `desa_bajuin_*`, `kec_bogus`, `desa_bogus`, `page_bajuin` |
