# Ground-Truth Angka Filter Landing Page (read-only)

**Tanggal:** 2026-10-09
**Tujuan:** menyediakan angka *ground-truth* dari DB `erdekatala` untuk memverifikasi
apakah landing page `/` menampilkan angka yang **benar** pada setiap kombinasi filter
(`?kecamatan=`, `?desa=`, `?sumber=`).
**Sifat kerja:** murni perhitungan data. Tidak ada kode aplikasi, migration, seeder,
config, atau DB yang diubah. Semua query `SELECT`; file baru hanya di `scratch/` dan
laporan ini. Tidak ada `git commit`.

Deliverable:

- `scratch/filter_groundtruth.json` — seluruh angka dalam bentuk terstruktur
- `scratch/sql/groundtruth_filter.sql` — kumpulan query SQL yang dipakai (bisa diaudit)
- `scratch/gen_groundtruth.py` — generator JSON (memanggil `mysql -B -N -e`)
- `scratch/crosscheck_models.php` — cross-check lewat model CI4 yang dipakai landing page
- laporan ini

---

## 0. Semantik agregasi yang dipakai (aturan resmi aplikasi)

| Sumber | Sumber baris | Urutan pemilihan | Tie-break |
|---|---|---|---|
| Laporan desa | **1 baris terbaru per desa** dari `laporan_agregat` | `tahun*100 + bulan DESC` | `MAX(id_laporan)` **di dalam periode terpilih** (bukan id terbesar global) |
| Dukcapil | **1 baris terbaru per desa** dari `profil_dukcapil` | `tahun*10 + semester DESC` | `MAX(id_profil)` di dalam periode terpilih |

Turunan: `jiwa = SUM(jiwa_l + jiwa_p)`, `kk = SUM(kk_l + kk_p)`.

Subquery ini **identik** dengan `LaporanAgregatModel::latestPerDesaSubquery()`
(app/Models/LaporanAgregatModel.php:239-263) dan
`ProfilDukcapilModel::latestPerDesaSubquery()` (app/Models/ProfilDukcapilModel.php:126-145),
hanya tanpa prefix/escape CI4.

```sql
-- lp = latest laporan per desa
SELECT la.*
FROM laporan_agregat la
JOIN (
    SELECT it.id_desa, MAX(it.id_laporan) AS id_laporan
    FROM laporan_agregat it
    JOIN (
        SELECT id_desa, MAX(tahun * 100 + bulan) AS periode
        FROM laporan_agregat GROUP BY id_desa
    ) mp ON mp.id_desa = it.id_desa AND mp.periode = (it.tahun * 100 + it.bulan)
    GROUP BY it.id_desa
) lpf ON lpf.id_laporan = la.id_laporan;

-- dp = latest dukcapil per desa
SELECT pd.*
FROM profil_dukcapil pd
JOIN (
    SELECT it.id_desa, MAX(it.id_profil) AS id_profil
    FROM profil_dukcapil it
    JOIN (
        SELECT id_desa, MAX(tahun * 10 + semester) AS periode
        FROM profil_dukcapil GROUP BY id_desa
    ) mp ON mp.id_desa = it.id_desa AND mp.periode = (it.tahun * 10 + it.semester)
    GROUP BY it.id_desa
) lpf ON lpf.id_profil = pd.id_profil;
```

### Keadaan data saat pengukuran

| Fakta | Nilai |
|---|---|
| Desa di `m_desa` | **135** |
| Kecamatan (`is_public = 1`) | **11** |
| Baris `laporan_agregat` | **137** (135 desa tercover) |
| Baris `profil_dukcapil` | **135** (135 desa tercover) |
| Desa tanpa laporan | **0** |
| Desa tanpa dukcapil | **0** |

Sebaran periode:

| Tabel | Periode | Baris |
|---|---|---:|
| `laporan_agregat` | 12/2025 | 135 |
| `laporan_agregat` | 7/2026 | 1 (Ranggang, id_laporan 136) |
| `laporan_agregat` | 8/2026 | 1 (Batu Ampar, id_laporan 137) |
| `profil_dukcapil` | S1/2026 | 135 |

Catatan: `laporan_agregat` punya 137 baris untuk 135 desa karena dua desa punya
periode ganda (lihat §3b).

---

## 1. Tingkat kabupaten

| Sumber | Total Jiwa (`jiwa_l+jiwa_p`) | Total KK (`kk_l+kk_p`) | Desa | Jiwa L | Jiwa P | KK L | KK P |
|---|---:|---:|---:|---:|---:|---:|---:|
| **(a) Laporan desa — latest per desa** | **375.583** | **130.908** | 135 | 190.759 | 184.824 | 103.177 | 27.731 |
| **(b) Dukcapil — latest per desa** | **378.190** | **132.030** | 135 | 192.047 | 186.143 | 103.688 | 28.342 |

Pembanding (**BUKAN** nilai yang benar): `SUM` atas semua 137 baris
`laporan_agregat` tanpa dedup = **380.525** jiwa / **132.696** KK. Selisih
+4.942 jiwa / +1.788 KK adalah double-count Ranggang (2.882 / 1.055) dan
Batu Ampar (2.060 / 733). Kalau landing page menampilkan 380.525, berarti
subquery latest-per-desa tidak dipakai.

```sql
SELECT SUM(jiwa_l + jiwa_p) AS jiwa, SUM(kk_l + kk_p) AS kk, COUNT(*) AS n_desa
FROM ( <lp di atas> ) lp;

SELECT SUM(jiwa_l + jiwa_p) AS jiwa, SUM(kk_l + kk_p) AS kk, COUNT(*) AS n_desa
FROM ( <dp di atas> ) dp;
```

---

## 2. Per kecamatan (11 kecamatan)

| kode_kecamatan | nama | desa | laporan_jiwa | laporan_kk | dukcapil_jiwa | dukcapil_kk |
|---|---|---:|---:|---:|---:|---:|
| 63.01.01 | Takisung | 12 | 34.742 | 12.195 | 34.944 | 12.275 |
| 63.01.02 | Jorong | 11 | 37.122 | 12.682 | 37.431 | 12.823 |
| 63.01.03 | Pelaihari | 20 | 84.227 | 29.195 | 84.816 | 29.432 |
| 63.01.04 | Kurau | 11 | 14.917 | 5.374 | 14.995 | 5.407 |
| 63.01.05 | Bati-Bati | 14 | 47.595 | 16.628 | 48.022 | 16.805 |
| 63.01.06 | Panyipatan | 10 | 26.824 | 9.519 | 27.069 | 9.606 |
| 63.01.07 | Kintap | 14 | 45.747 | 15.390 | 46.070 | 15.538 |
| 63.01.08 | Tambang Ulang | 9 | 19.611 | 6.957 | 19.740 | 7.000 |
| 63.01.09 | Batu Ampar | 14 | 29.387 | 10.269 | 29.529 | 10.354 |
| 63.01.10 | Bajuin | 9 | 20.676 | 7.451 | 20.774 | 7.500 |
| 63.01.11 | Bumi Makmur | 11 | 14.735 | 5.248 | 14.800 | 5.290 |
| **Jumlah** | | **135** | **375.583** | **130.908** | **378.190** | **132.030** |

Jumlah per kecamatan = angka kabupaten di §1 (cross-check sudah diverifikasi di
`scratch/gen_groundtruth.py`).

Slug URL kecamatan (dari `LOWER(REPLACE(nama_kecamatan,' ','-'))`): `takisung`,
`jorong`, `pelaihari`, `kurau`, `bati-bati`, `panyipatan`, `kintap`,
`tambang-ulang`, `batu-ampar`, `bajuin`, `bumi-makmur`.
(Kolom `kecamatan.slug` di DB **semua NULL** — resolusi slug memakai ekspresi di atas,
bukan kolom itu.)

---

## 3. Per desa — 3 kecamatan sampel

Format periode laporan: `B/T`; periode dukcapil: `S/T`. `#id` = PK baris terpilih.

### 3.1 Kecamatan Bajuin (id_kecamatan = 10, 9 desa)

| desa | slug | laporan_jiwa | laporan_kk | lap. terpilih | dukcapil_jiwa | dukcapil_kk | duk. terpilih | KIA | Akta Cerai |
|---|---|---:|---:|---|---:|---:|---:|---:|---:|
| Bajuin | bajuin | 2.299 | 864 | 12/2025 `#116` | 2.180 | 810 | S1/2026 `#386` | 464 | 44 |
| Galam | galam | 2.302 | 827 | 12/2025 `#120` | 1.499 | 546 | S1/2026 `#390` | 281 | 31 |
| Ketapang | ketapang | 2.287 | 819 | 12/2025 `#118` | 1.800 | 676 | S1/2026 `#388` | 402 | 51 |
| Kunyit | kunyit | 2.350 | 788 | 12/2025 `#122` | 1.373 | 471 | S1/2026 `#392` | 289 | 24 |
| Pemalongan | pemalongan | 2.311 | 871 | 12/2025 `#121` | 1.920 | 727 | S1/2026 `#391` | 396 | 53 |
| Sungai Bakar | sungai-bakar | 2.385 | 792 | 12/2025 `#117` | 1.680 | 612 | S1/2026 `#387` | 276 | 32 |
| Tanjung | tanjung | 2.137 | 814 | 12/2025 `#124` | 4.642 | 1.644 | S1/2026 `#394` | 1.051 | 74 |
| Tebing Siring | tebing-siring | 2.385 | 854 | 12/2025 `#123` | 2.788 | 988 | S1/2026 `#393` | 649 | 43 |
| Tirta Jaya | tirta-jaya | 2.220 | 822 | 12/2025 `#119` | 2.892 | 1.026 | S1/2026 `#389` | 609 | 55 |
| **Jumlah** | | **20.676** | **7.451** | | **20.774** | **7.500** | | **4.417** | **407** |

### 3.2 Kecamatan Takisung (id_kecamatan = 1, 12 desa)

| desa | slug | laporan_jiwa | laporan_kk | lap. terpilih | dukcapil_jiwa | dukcapil_kk | duk. terpilih | KIA | Akta Cerai |
|---|---|---:|---:|---|---:|---:|---:|---:|---:|
| Batilai | batilai | 3.046 | 945 | 12/2025 `#9` | 1.124 | 392 | S1/2026 `#279` | 216 | 15 |
| Benua Lawas | benua-lawas | 2.709 | 969 | 12/2025 `#138` | 2.285 | 801 | S1/2026 `#271` | 428 | 27 |
| Benua Tengah | benua-tengah | 2.988 | 1.050 | 12/2025 `#6` | 4.190 | 1.505 | S1/2026 `#276` | 765 | 85 |
| Gunung Makmur | gunung-makmur | 2.680 | 950 | 12/2025 `#5` | 4.373 | 1.559 | S1/2026 `#275` | 763 | 99 |
| Kuala Tambangan | kuala-tambangan | 2.898 | 1.025 | 12/2025 `#3` | 2.486 | 854 | S1/2026 `#273` | 404 | 18 |
| Pagatan Besar | pagatan-besar | 2.832 | 1.043 | 12/2025 `#8` | 3.048 | 1.023 | S1/2026 `#278` | 661 | 35 |
| **Ranggang** (⚠ multi-baris) | ranggang | 2.882 | 1.055 | **7/2026 `#136`** | 3.751 | 1.302 | S1/2026 `#277` | 649 | 57 |
| Ranggang Dalam | ranggang-dalam | 2.928 | 960 | 12/2025 `#10` | 1.548 | 529 | S1/2026 `#280` | 251 | 28 |
| Sumber Makmur | sumber-makmur | 2.879 | 1.231 | 12/2025 `#12` | 2.747 | 992 | S1/2026 `#282` | 481 | 54 |
| Tabanio | tabanio | 2.921 | 969 | 12/2025 `#2` | 4.072 | 1.484 | S1/2026 `#272` | 750 | 54 |
| Takisung | takisung | 2.972 | 991 | 12/2025 `#4` | 3.678 | 1.240 | S1/2026 `#274` | 772 | 48 |
| Telaga Langsat | telaga-langsat | 3.007 | 1.007 | 12/2025 `#11` | 1.642 | 594 | S1/2026 `#281` | 270 | 26 |
| **Jumlah** | | **34.742** | **12.195** | | **34.944** | **12.275** | | **6.410** | **546** |

### 3.3 Kecamatan Kintap (id_kecamatan = 7, 14 desa)

| desa | slug | laporan_jiwa | laporan_kk | lap. terpilih | dukcapil_jiwa | dukcapil_kk | duk. terpilih | KIA | Akta Cerai |
|---|---|---:|---:|---|---:|---:|---:|---:|---:|
| Bukit Mulia | bukit-mulia | 3.058 | 1.172 | 12/2025 `#89` | 2.508 | 797 | S1/2026 `#359` | 667 | 40 |
| Kebun Raya | kebun-raya | 3.483 | 1.030 | 12/2025 `#90` | 3.223 | 1.091 | S1/2026 `#360` | 883 | 50 |
| Kintap | kintap | 3.298 | 1.077 | 12/2025 `#80` | 3.839 | 1.335 | S1/2026 `#350` | 716 | 34 |
| Kintap Kecil | kintap-kecil | 3.092 | 1.166 | 12/2025 `#86` | 3.529 | 1.208 | S1/2026 `#356` | 692 | 45 |
| Kintapura | kintapura | 3.342 | 1.140 | 12/2025 `#81` | 5.097 | 1.795 | S1/2026 `#351` | 974 | 58 |
| Mekar Raya | mekar-raya | 3.017 | 1.032 | 12/2025 `#91` | 1.881 | 621 | S1/2026 `#361` | 455 | 15 |
| Muara Kintap | muara-kintap | 3.294 | 1.107 | 12/2025 `#84` | 4.675 | 1.470 | S1/2026 `#354` | 755 | 35 |
| Pandan Sari | pandan-sari | 3.019 | 1.101 | 12/2025 `#79` | 5.002 | 1.724 | S1/2026 `#349` | 1.105 | 64 |
| Pasir Putih | pasir-putih | 3.045 | 1.023 | 12/2025 `#87` | 2.950 | 1.014 | S1/2026 `#357` | 638 | 30 |
| Riam Adungan | riam-adungan | 3.267 | 1.121 | 12/2025 `#83` | 1.517 | 547 | S1/2026 `#353` | 293 | 8 |
| Salaman | salaman | 3.331 | 1.092 | 12/2025 `#85` | 2.257 | 788 | S1/2026 `#355` | 355 | 17 |
| Sebamban Baru | sebamban-baru | 3.814 | 1.136 | 12/2025 `#92` | 2.167 | 713 | S1/2026 `#362` | 564 | 31 |
| Sumber Jaya | sumber-jaya | 3.209 | 1.092 | 12/2025 `#88` | 2.765 | 917 | S1/2026 `#358` | 717 | 44 |
| Sungai Cuka | sungai-cuka | 3.478 | 1.101 | 12/2025 `#82` | 4.660 | 1.518 | S1/2026 `#352` | 1.103 | 52 |
| **Jumlah** | | **45.747** | **15.390** | | **46.070** | **15.538** | | **9.917** | **523** |

### 3b. Desa dengan lebih dari satu baris `laporan_agregat`

Hanya **2 desa**: **Ranggang** (Takisung, id_desa 7) dan **Batu Ampar**
(Batu Ampar, id_desa 102).

| id_laporan | desa | kecamatan | bulan/tahun | jiwa_l | jiwa_p | kk_l | kk_p | jiwa | kk | TERPILIH |
|---:|---|---|---:|---:|---:|---:|---:|---:|---:|:--:|
| 7 | Ranggang | Takisung | 12/2025 | 1.512 | 1.370 | 849 | 206 | 2.882 | 1.055 | ✗ |
| **136** | Ranggang | Takisung | **7/2026** | 1.512 | 1.370 | 849 | 206 | 2.882 | 1.055 | **✓** |
| 102 | Batu Ampar | Batu Ampar | 12/2025 | 1.078 | 982 | 600 | 133 | 2.060 | 733 | ✗ |
| **137** | Batu Ampar | Batu Ampar | **8/2026** | 1.078 | 982 | 600 | 133 | 2.060 | 733 | **✓** |

Kedua desa ini nilainya **identik di dua periode** (jiwa & kk sama persis), jadi
angka totalnya tidak berubah — yang berubah hanya *baris mana yang dipakai* dan
karenanya *label periode*. Yang penting: masing-masing desa tetap menyumbang
**satu** baris (2.882 dan 2.060), bukan dua.

Jika landing page (atau API) menjumlahkan 137 baris, Takisung akan tampil
37.624 (+2.882) dan Batu Ampar 31.447 (+2.060) — penanda double-count yang jelas.

---

## 4. Kasus campuran (`?sumber=` kosong)

Karena **135/135 desa punya laporan**, himpunan `array_diff($desaScope, $desaLaporIds)`
di `PublicController` baris 134 menjadi kosong → `getAggregatedStats([])` untuk
Dukcapil mengembalikan nol (`ProfilDukcapilModel` baris 210-213).

| Mode campuran kabupaten | Total Jiwa | Total KK | Sama dengan |
|---|---:|---:|---|
| Laporan (135 desa) | 375.583 | 130.908 | — |
| Dukcapil (0 desa tersisa) | 0 | 0 | — |
| **Total campuran** | **375.583** | **130.908** | **= (1a) ✓** |

**Konfirmasi: mode campuran = 100% laporan desa, identik dengan (1a).**

Pengecualian: **KIA & Akta Cerai tidak mengikuti aturan campuran.**
`PublicController` baris 207 memanggil
`profilDukcapilModel->getAggregatedStats($desaScope)` — **seluruh** scope, bukan hanya
desa yang belum lapor — sehingga KIA/Akta Cerai tetap 76.078 / 6.010 di mode default.

---

## 5. KIA & Akta Cerai (hanya dari `profil_dukcapil`)

`laporan_agregat` **tidak punya** kolom `dok_kia` / `dok_akta_cerai` (keduanya ada di
`profil_dukcapil` saja, nullable default 0). Karena itu angkanya seluruhnya dari
snapshot Dukcapil latest-per-desa.

### Kabupaten

| Indikator | Nilai | Desa |
|---|---:|---:|
| KIA (`dok_kia`) | **76.078** | 135 |
| Akta Cerai (`dok_akta_cerai`) | **6.010** | 135 |

### Per kecamatan

| kode_kecamatan | nama | KIA | Akta Cerai |
|---|---|---:|---:|
| 63.01.01 | Takisung | 6.410 | 546 |
| 63.01.02 | Jorong | 7.639 | 544 |
| 63.01.03 | Pelaihari | 16.930 | 1.797 |
| 63.01.04 | Kurau | 3.256 | 180 |
| 63.01.05 | Bati-Bati | 8.891 | 658 |
| 63.01.06 | Panyipatan | 5.383 | 381 |
| 63.01.07 | Kintap | 9.917 | 523 |
| 63.01.08 | Tambang Ulang | 4.211 | 342 |
| 63.01.09 | Batu Ampar | 5.638 | 502 |
| 63.01.10 | Bajuin | 4.417 | 407 |
| 63.01.11 | Bumi Makmur | 3.386 | 130 |
| **Jumlah** | | **76.078** | **6.010** |

Perilaku UI yang menyertai (kode saat ini, app/Controllers/PublicController.php:204-211):

- `?sumber=desa` → KIA & Akta Cerai **dihapus/disseembunyikan** dari kartu Dokumen
- selain itu (default & `?sumber=dukcapil`) → selalu diisi dari Dukcapil

---

## 6. Tabel "yang SEHARUSNYA tampil"

Catatan `mode`: "campuran" di sini faktualnya **laporan** karena 135/135 desa sudah lapor.

| URL | Yang seharusnya tampil (Total Jiwa / Total KK) | KIA | Akta Cerai | Catatan skop |
|---|---:|---:|---:|---|
| `/` | **375.583 / 130.908** | 76.078 | 6.010 | default campuran → 100% laporan; 135 desa |
| `/?sumber=desa` | **375.583 / 130.908** | — | — | murni laporan; KIA/Akta Cerai disembunyikan |
| `/?sumber=dukcapil` | **378.190 / 132.030** | 76.078 | 6.010 | murni snapshot Dukcapil S1/2026; 135 desa |
| `/?kecamatan=bajuin` | **20.676 / 7.451** | 4.417 | 407 | 9 desa |
| `/?kecamatan=bajuin&sumber=dukcapil` | **20.774 / 7.500** | 4.417 | 407 | 9 desa |
| `/?kecamatan=takisung` | **34.742 / 12.195** | 6.410 | 546 | 12 desa |
| `/?kecamatan=takisung&sumber=dukcapil` | **34.944 / 12.275** | 6.410 | 546 | 12 desa |
| `/?kecamatan=kintap` | **45.747 / 15.390** | 9.917 | 523 | 14 desa |
| `/?kecamatan=kintap&sumber=dukcapil` | **46.070 / 15.538** | 9.917 | 523 | 14 desa |
| `/?kecamatan=bajuin&desa=bajuin` | **2.299 / 864** | 464 | 44 | id_desa 116; laporan #116 (12/2025) |
| `/?kecamatan=bajuin&desa=bajuin&sumber=dukcapil` | **2.180 / 810** | 464 | 44 | id_desa 116; dukcapil #386 (S1/2026) |
| `/?kecamatan=bajuin&desa=ranggang` | **20.676 / 7.451** | 4.417 | 407 | desa tidak ada di kecamatan ini → lihat §6.1 |

### 6.1 Kasus `?kecamatan=bajuin&desa=<desa di luar Bajuin>`

Fakta DB: desa **Ranggang** (id_desa 7) berada di **Kec. Takisung**, bukan Bajuin.
Desa di Bajuin hanya: Bajuin, Galam, Ketapang, Kunyit, Pemalongan, Sungai Bakar,
Tanjung, Tebing Siring, Tirta Jaya.

Perilaku kode saat ini (`PublicController.php:60-71`): desa hanya di-resolve bila
`id_kecamatan_filter` sudah didapat, dan resolve-nya dibatasi
`->where('id_kecamatan', $id_kecamatan_filter)`. Karena `ranggang` tidak ada di
Bajuin:

- `$desa_unresolved = true`
- `$id_desa_filter` tetap `null` → agregasi memakai **seluruh desa di Kec. Bajuin**

Jadi yang seharusnya tampil **menurut kode saat ini**: **20.676 jiwa / 7.451 KK**
(= tingkat kecamatan Bajuin), plus peringatan di
`app/Views/public/landingpage.php:1438-1449`: *"Desa "ranggang" tidak ditemukan di
Kecamatan Bajuin. Menampilkan data tingkat kecamatan (seluruh desa di kecamatan ini)."*

| Opsi produk | Total Jiwa | Total KK |
|---|---:|---:|
| **A. Fallback ke kecamatan (kode saat ini)** | **20.676** | **7.451** |
| B. Anggap tidak valid → tampilkan 0 / kosong | 0 | 0 |
| C. Cari desa di kecamatan lain (Ranggang @ Takisung) | 2.882 | 1.055 |

Angka A dipakai di JSON (`6_yang_seharusnya_tampil`), dengan B & C dicatat sebagai
alternatif bila produk memilih lain. Jika worker paralel mengubah perilaku ini,
tabel di atas memberi tahu angka mana yang menjadi benar.

---

## 7. Cross-check lewat model CI4

`scratch/crosscheck_models.php` memanggil model yang benar-benar dipakai landing page
(`LaporanAgregatModel::getAggregatedStats()`, `ProfilDukcapilModel::getAggregatedStats()`).
Output aktual:

```
CI4 laporan  kab : jiwa=375583 kk=130908
CI4 dukcapil kab : jiwa=378190 kk=132030 kia=76078 akta=6010 (n_rows=135)
63.01.01 Takisung       lap:  34742/12195  duk:  34944/12275  kia: 6410 akta: 546 desa:12
63.01.02 Jorong         lap:  37122/12682  duk:  37431/12823  kia: 7639 akta: 544 desa:11
63.01.03 Pelaihari      lap:  84227/29195  duk:  84816/29432  kia:16930 akta:1797 desa:20
63.01.04 Kurau          lap:  14917/5374   duk:  14995/5407   kia: 3256 akta: 180 desa:11
63.01.05 Bati-Bati      lap:  47595/16628  duk:  48022/16805  kia: 8891 akta: 658 desa:14
63.01.06 Panyipatan     lap:  26824/9519   duk:  27069/9606   kia: 5383 akta: 381 desa:10
63.01.07 Kintap         lap:  45747/15390  duk:  46070/15538  kia: 9917 akta: 523 desa:14
63.01.08 Tambang Ulang  lap:  19611/6957   duk:  19740/7000   kia: 4211 akta: 342 desa:9
63.01.09 Batu Ampar     lap:  29387/10269  duk:  29529/10354  kia: 5638 akta: 502 desa:14
63.01.10 Bajuin         lap:  20676/7451   duk:  20774/7500   kia: 4417 akta: 407 desa:9
63.01.11 Bumi Makmur    lap:  14735/5248   duk:  14800/5290   kia: 3386 akta: 130 desa:11
Desa Bajuin (116) lap: jiwa=2299 kk=864 | duk: jiwa=2180 kk=810 kia=464 akta=44
```

Semua cocok dengan angka SQL di atas → model dan ground-truth SQL **konsisten**.
Dengan kata lain: bila landing page menampilkan angka lain, penyebabnya ada di
layer controller/view (pemilihan scope/parameter), bukan di definisi subquery model.

---

## 8. Cara reproduce

```bash
# JSON ground-truth
python scratch/gen_groundtruth.py

# Cross-check lewat model CI4
php scratch/crosscheck_models.php

# Query SQL lengkap (audit)
mysql -uroot erdekatala < scratch/sql/groundtruth_filter.sql
```

---

## 9. Batasan & catatan

- Semua pengukuran diambil 2026-10-09 dari DB `erdekatala` (MySQL, Laragon, user `root`).
- Tidak ada perubahan pada aplikasi, migration, seeder, config, maupun DB; tidak ada commit.
- Server aplikasi tidak dihidupkan untuk tugas ini — seluruh angka berasal dari query DB
  dan dari model CI4 yang dipanggil langsung lewat CLI (`scratch/crosscheck_models.php`).
- Angka KIA/Akta Cerai sepenuhnya bergantung pada snapshot `profil_dukcapil` S1/2026;
  bila impor semester baru terjadi, angka di §5 berubah.
- `kecamatan.slug` bernilai NULL untuk semua 11 kecamatan; resolusi `?kecamatan=`
  memakai `LOWER(REPLACE(nama_kecamatan,' ','-'))`. Slug di tabel §2 mengikuti
  rumus itu (`bati-bati`, `tambang-ulang`, `batu-ampar`, `bumi-makmur`).
