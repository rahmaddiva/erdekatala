# Rencana Implementasi — Impor Dukcapil Semester I 2026

**Tanggal:** 2026-10-09
**Keputusan:** Opsi C (minimal-invasive, **tanpa migration**) — sesuai memo
`docs/superpowers/specs/2026-10-09-integrasi-dukcapil-sm1-2026-design.md` §6.
**Sumber data:** `DATA KEPENDUDUKAN KAB TALA SM I 2026.xlsx` (root project), dibaca
**langsung** via phpspreadsheet (bukan JSON turunan) agar command mandiri & reproducible.
**Baseline terdahulu:** `docs/superpowers/specs/2026-10-09-audit-excel-sm1-2026.md`.

---

## 0. Ringkasan keputusan

SM1/2026 **menggantikan** (bukan melengkapi) snapshot SM2/2025 → mode **REPLACE**:
tabel `profil_dukcapil` dikosongkan lalu diisi TEPAT 135 baris
`(id_desa, semester=1, tahun=2026)`. Keadaan akhir: hanya ada periode 2026/1.

Alasan (direvisi 2026-10-09): `PublicController` mengambil angka dari
`ProfilDukcapilModel::getAggregatedStats()` = `SUM()` tanpa filter periode, jadi
dua periode berdampingan akan terhitung ganda (≈753.773 jiwa) — lihat A8.

Tidak ada migration, tidak ada seeder yang diubah, tidak ada view/model yang diubah.

---

## 1. File map

| # | File | Status | Peran |
|---|---|---|---|
| 1 | `docs/superpowers/plans/2026-10-09-import-dukcapil-sm1-2026.md` | **baru** | Dokumen ini (rencana) |
| 2 | `app/Libraries/DukcapilMapping.php` | **baru** | Fungsi MURNI (tanpa DB, tanpa I/O): 1 baris Excel → array kolom `profil_dukcapil` |
| 3 | `app/Commands/DukcapilImport.php` | **baru** | Command `php spark dukcapil:import` — parse Excel, validasi, join `m_desa`, dry-run/commit |
| 4 | `tests/unit/DukcapilMappingTest.php` | **baru** | Unit test fungsi murni |
| 5 | `scratch/probe_*.php` | **baru** (scratch) | Skrip probe sekali pakai untuk memastikan layout Excel |

**TIDAK disentuh:** migration, `ProfilDukcapilSeeder`, `LaporanAgregatSeeder`,
`ProfilDukcapilModel`, controller, view, `.env`, database (kecuali bila `--commit`
dijalankan eksplisit oleh user).

---

## 2. Struktur Excel (terverifikasi langsung)

Semua 8 sheet berdimensi identik: 198 baris. Struktur blok per kecamatan:

| Baris (relatif) | Isi |
|---|---|
| 1 | Banner `KECAMATAN <NAMA>` (kolom A) |
| 2 | Header utama: `NO`,`KODE`,`DESA`(+`DESA/KEL` di PELAIHARI), lalu group header |
| 3 | Sub-header: label per kolom data (kosong di sheet `JML KK` & `JML JIWA`) |
| 4 … 4+n-1 | Baris desa (kolom B = `63.01.xx.xxxx`) |
| 4+n | Total kecamatan (kolom B = `63.01.xx`) |
| 4+n+1 | kosong |

Pembacaan **tidak** mengandalkan nomor baris absolut: parser memindai kolom B dan
mengelompokkan berdasar pola kode (`^63\.\d{2}\.\d{2}\.\d{4}$` = desa,
`^63\.\d{2}\.\d{2}$` = total kecamatan). Terverifikasi: **135 desa + 11 total
kecamatan di semua 8 sheet**.

Kolom data mulai di **D** untuk ke-8 sheet (A=NO, B=KODE, C=DESA). Header sub-kolom
diambil dari baris sub-header; bila kosong (sheet `JML KK`, `JML JIWA`) dipakai
header baris 2 (`JML`).

---

## 3. Aturan pemetaan A1–A8

### A1 — `65-99` = `65-69` (typo)

Header usia ke-14 di Excel tertulis `65-99`. Dipetakan ke `u65_69_l/p`.
**Bukti:** Σ 16 grup usia per desa == `JML JIWA` persis (135/135, selisih 0). Bila
`65-99` berarti 65–99 tahun, `70-74` dan `75+` terhitung ganda dan total tidak
mungkin sama. Nilai kabupaten 10.349 vs tetangganya (60-64: 14.462 > 10.349 > 70-74:
6.028) membentuk piramida menurun yang normal.
**Status:** asumsi — harus dikonfirmasi ke Dukcapil sebelum dipakai untuk keperluan resmi.

### A2 — `75+` dipecah 50% / 30% / 20%

Excel memberi satu angka `75+`; DB butuh `u75_79`, `u80_84`, `u85_atas`.
Dipakai rasio yang **sama persis** dengan `LaporanAgregatSeeder::mapKecamatanData()`:

```
u75_79 = round(v * 0.5)
u80_84 = round(v * 0.3)
u85    = v - u75_79 - u80_84        // sisa, anti-pembulatan
```

Diterapkan **setelah** pemecahan L/P (lihat A3), jadi tiap gender dibagi sendiri.

### A3 — Piramida gabungan → L/P memakai rasio gender desa

Excel hanya punya 1 angka per grup usia (L+P). Rasio desa:

```
r = jiwa_l / (jiwa_l + jiwa_p)          // fallback 0.5 bila penyebut 0
_l = round(v * r)
_p = v - _l
```

**Koreksi pembulatan (wajib):** setelah 18 grup dipecah, pastikan
`Σ(u*_l) == jiwa_l` dan `Σ(u*_p) == jiwa_p`. Selisih (biasanya 0…±2) dialokasikan
ke grup dengan nilai terbesar (`u40_44` di data nyata) supaya tidak ada grup yang
jadi negatif. Diunit-test (kasus a).

### A4 — Pendidikan & pekerjaan: basis JIWA → basis KK

Excel berbasis **jiwa** (Σ 10 tingkat = Σ 11 jenis = `JML JIWA`, terverifikasi
135/135). DB `kk_pend_*` / `kk_ker_*` berbasis **KK**. Faktor skala:

```
f = kk_total / jiwa_total              // ≈ 0,349 di kabupaten ini
```

**Pendidikan (10 → 7):**

| Excel | DB |
|---|---|
| `TIDAK/BLM SEKOLAH` | `kk_pend_tidak_sekolah` |
| `BELUM TAMAT SD` + `TAMAT SD` | `kk_pend_sd` |
| `SLTP/SEDERAJAT` | `kk_pend_smp` |
| `SLTA/SEDERAJAT` | `kk_pend_sma` |
| `DIPLOMA I/II` + `AKADEMI/DIPL.III/S.MUDA` | `kk_pend_diploma` |
| `DIPLOMA IV/STRATA I` | `kk_pend_s1` |
| `STRATA-II` + `STRATA-III` | `kk_pend_s2_s3` |

**Pekerjaan (11 → 8)** — keputusan judgement, tiap baris didokumentasikan di kode:

| Excel | DB | Alasan |
|---|---|---|
| `BELUM/TIDAK BEKERJA` | `kk_ker_tidak_kerja` | 1:1 |
| `APARATUR/PEJABAT NEGARA` | `kk_ker_pns` | 1:1 (APN = ASN/PNS) |
| `TENAGA PENGAJAR` | `kk_ker_pns` | Pengajar negeri dominan ASN; DB tidak punya kategori pendidik terpisah |
| `WIRASWASTA` | `kk_ker_wiraswasta` + `kk_ker_swasta` + `kk_ker_pedagang` (40/30/30) | Mengikuti pembeda seeder: Excel tidak memilah swasta/pedagang |
| `PERTANIAN/PETERNAKAN` | `kk_ker_tani` | 1:1 |
| `NELAYAN` | `kk_ker_nelayan` | 1:1 |
| `AGAMA DAN KEPERCAYAAN` | `kk_ker_tidak_kerja` | Jumlahnya sangat kecil (3–20 per desa); kategori terdekat yang netral |
| `PELAJAR/MAHASISWA` | `kk_ker_tidak_kerja` | Sesuai instruksi: pelajar/mahasiswa bukan angkatan kerja |
| `TENAGA KESEHATAN` | `kk_ker_pns` | Nakes publik dominan ASN; DP tidak punya kategori nakes |
| `PENSIUNAN` | `kk_ker_tidak_kerja` | Pensiunan sudah keluar dari angkatan kerja |
| `LAINNYA` | `kk_ker_buruh` + `kk_ker_tidak_kerja` (40/60) | Mengikuti rasio seeder (`lain_buruh = 0.4 × lain`) |

**Anti-pembulatan:** tiap kelompok diskalakan lalu dibulatkan; sisanya
(`kk_total − Σ`) dialokasikan ke komponen terbesar agar `Σ kk_pend_* == kk_total`
dan `Σ kk_ker_* == kk_total` (unit-test kasus b & c).

### A5 — Dokumen

| Excel | DB |
|---|---|
| `PEREKAMAN KTP-el (Wajib)` | `pend_wajib_ktp` |
| `KARTU KELUARGA` | `kk_punya_kartu_fisik` |
| — (turunan) | `kk_belum_punya_kartu_fisik = kk_total − kartu_keluarga` (min 0) |
| `AKTA LAHIR USIA 0-17` | `pend_punya_akta_lahir` |
| `AKTA PERKAWINAN` | `kk_punya_akta_nikah` |

**DISIMPAN (Kelompok A)** — awalnya direncanakan tidak disimpan karena butuh
kolom baru, kini sudah ada lewat migration
`2026-10-09-000001_AddDokumenToProfilDukcapil`:

| Excel | DB | Basis |
|---|---|---|
| `KARTU IDENTITAS ANAK (KIA) usia 0-16` | `dok_kia` | penduduk |
| `AKTA PERCERAIAN` | `dok_akta_cerai` | penduduk |

Keduanya basis PENDUDUK (bukan KK), jadi tidak ikut diskalakan A4. Kolom ada di
`profil_dukcapil` saja — `laporan_agregat` tidak punya kolom ini, sehingga
landing page mengambilnya khusus dari snapshot Dukcapil.

**Catatan tanggal potong:** sheet `KEPEMILIKAN DOK` = DKB per **30-09-2026**,
sedangkan 7 sheet lainnya = DKB **Semester I 2026** (30-06). Rasio turunan
(cakupan KTP-el / akta lahir) karena itu mencampur dua waktu potong. Dicatat di
plan ini & dicetak sebagai peringatan saat impor.

### A6 — Kelompok khusus dari piramida hasil pemetaan

```
jml_balita = u0_4_l + u0_4_p
jml_remaja = u10_14_l + u10_14_p + u15_19_l + u15_19_p
jml_lansia = Σ u60_64_* .. u85_atas_*   (6 grup × 2 gender)
```

Identik dengan formula `ProfilDukcapilSeeder` baris 65–73.

### A7 — Kolom tanpa sumber Excel → PORT formula estimasi seeder

Semua kolom berikut **ESTIMASI**, bukan data resmi. Ditandai jelas di kode
(komentar `// ESTIMASI`) dan dilaporkan terpisah di ringkasan dry-run.

| Kelompok | Kolom | Formula (port dari `ProfilDukcapilSeeder` baris 74–95) |
|---|---|---|
| Status kawin | `kk_kawin`, `kk_belum_kawin`, `kk_cerai_hidup`, `kk_cerai_mati` | **Rasio proporsional** dari `ProfilDukcapilSeeder`: `kawin = round(1.273 × kk_total)`, `belum_kawin = round(1.396 × kk_total)`, `cerai_hidup = round(0.067 × kk_total)`, `cerai_mati = round(0.133 × kk_total)` (rasio dihitung dari total SM2/2025: KK 130.908 vs kawin 166.683 / belum 182.694 / cerai hidup 8.737 / cerai mati 17.469) |
| JKN/BPJS | `jkn`, `non_jkn`, `pend_bpjs`, `pend_non_bpjs` | `jkn = round(jiwa_total × 0.80)`; `non_jkn = jiwa_total − jkn` |
| JKN/PUS | `pus_pbi`, `pus_non_pbi`, `pus_jkn` | `pus_pbi = round(jiwa_total × 0.25)`; `pus_non_pbi = jkn − pus_pbi`; `pus_jkn = pus_pbi + pus_non_pbi` |
| Hubungan KK | `penduduk_hub_kepala`, `…istri`, `…anak`, `…lainnya` | `kepala = kk_total`; `istri = round(kk_total × 0.85)`; `anak = round(jiwa_total × 0.30)`; `lainnya = max(0, jiwa_total − kepala − istri − anak)` |
| KB & PUS | `jml_pus`, `kb_aktif`, `jml_penggunaan_alat_kontrasepsi` | `jml_pus = round(kk_kawin × 0.85)`; `kb_aktif = round(jml_pus × 0.60)`; `alat = kb_aktif` |

Catatan: rasio kawin **tidak** lagi memakai `distribute()` acak 0.90–1.10 seperti
seeder — SM1/2026 memakai rasioDeterministik, sehingga reprodusibel.

### A8 — Penulisan baris: **REPLACE**, bukan append  *(direvisi 2026-10-09)*

**Revisi penting.** Rencana awal berbunyi "biarkan SM2/2025 sebagai riwayat".
Itu **salah** dan sudah dikoreksi. Alasan (terverifikasi di source):

- `PublicController::landingpage()` mengambil **angka** dari
  `ProfilDukcapilModel::getAggregatedStats($ids)` (baris ~110 & ~123).
- `getAggregatedStats()` menjalankan `SUM(kolom)` atas **SEMUA** baris milik
  `id_desa` tersebut, **tanpa filter periode**.
- `getLatestPerDesa()` / `orderBy('tahun DESC, semester DESC')->first()` **HANYA**
  dipakai untuk **label** semester/tahun di note-strip (baris ~132), bukan angka.
  `getLatestPerDesa()` bahkan tidak dipanggil di controller mana pun.

Akibatnya: menyisipkan `(id_desa, 2026, 1)` di samping `(id_desa, 2025, 2)` membuat
landing page **menjumlahkan keduanya** → ≈ 753.773 jiwa, sementara label tetap
"Semester I 2026". Itu silent wrong-data dan bertentangan dengan keputusan
"SM1/2026 menggantikan SM2/2025".

**Aturan yang berlaku sekarang:**

- Saat `--commit`, dalam satu transaksi: `DELETE FROM profil_dukcapil` lalu
  `insertBatch()` 135 baris periode target. Gagal → **rollback utuh**.
- **Kenapa `DELETE FROM`, bukan `TRUNCATE`:** di MySQL `TRUNCATE TABLE` adalah DDL
  yang memicu **implicit commit** → tidak bisa di-rollback; bila `insertBatch()`
  gagal setelahnya, tabel tertinggal kosong tanpa pemulihan otomatis. Terukur di
  DB ini: `DELETE FROM` + `transRollback()` mengembalikan 135 baris, sedangkan
  `TRUNCATE` + `transRollback()` menyisakan 0 baris.
  Catatan: `$builder->delete()` tanpa where **tidak bisa** dipakai — CI4 melempar
  `DatabaseException` ("Deletes are not allowed unless they contain a where or
  like clause"). Dipakai raw query dengan `protectIdentifiers()`.
  (`emptyTable()` juga dihindari karena itu sama dengan truncate.)
- Keadaan akhir: tabel berisi **TEPAT 135 baris**, hanya periode
  `(tahun=2026, semester=1)`; **tidak ada** periode lain.
- Idempoten: re-run menghasilkan keadaan akhir yang sama (replace, bukan akumulasi).
- Key baris: `(id_desa, semester=1, tahun=2026)`; unique key tabel tetap terpenuhi.
- Join `m_desa.kode_desa` = KODE Excel (135/135 cocok, terverifikasi memo §1.1).
- `imported_at = date('Y-m-d H:i:s')`, `imported_by = NULL`.
- **Dry-run (tanpa `--commit`) non-destruktif** tetapi WAJIB melaporkan rencana:
  jumlah baris & periode yang akan dihapus (mis. "135 baris 2/2025") dan jumlah
  baris yang akan disisipkan ("135 baris periode 1/2026").
- SM2/2025 **tidak** dipertahankan. Baseline lama sudah tercapture di
  `scratch/baseline_dukcapil.json` dan bisa diregenerasi lewat `ProfilDukcapilSeeder`
  (ingat: seeder itu sendiri melakukan `truncate()`).

---

## 5. Command — `php spark dukcapil:import`

```
php spark dukcapil:import [--file <path>] [--tahun <YYYY>] [--semester <1|2>] [--commit]
```

| Opsi | Default | Keterangan |
|---|---|---|
| `--file` | `DATA KEPENDUDUKAN KAB TALA SM I 2026.xlsx` (root project) | Path workbook |
| `--tahun` | `2026` | Tahun snapshot |
| `--semester` | `1` | Semester snapshot (1 atau 2) |
| `--commit` | *tidak ada* | Tanpa ini = **DRY-RUN**: hanya menghitung & mencetak, tidak menulis apa pun. Dengan ini = **REPLACE** |

**Sintaks spasi** (`--file nilai`), bukan `--file=nilai`. Parser opsi CI4 hanya
mengenal bentuk spasi; helper `opsi()` (port dari `BkkbnWarmup::opsi()`) menerima
kedua bentuk agar tidak menyesatkan.

**Output dry-run:**
1. Header: file, sheet yang terbaca, semester/tahun target.
2. Tabel per kecamatan: desa, KK, jiwa — dibandingkan dengan total Excel per
   kecamatan (kolom `Δ` harus 0).
3. Baris TOTAL kabupaten + assert V1–V3 (✅/❌).
4. Ringkasan **kolom resmi vs estimasi**: daftar kolom yang bersumber Excel vs
   yang ESTIMASI (A7).
5. Peringatan: tanggal potong dokumen (30-09-2026), asumsi A1–A8, nama desa beda.
6. **Rencana penulisan (replace)**: jumlah baris & periode yang akan dihapus dan
   yang akan disisipkan (lihat A8).
7. Penutup: `DRY-RUN — tidak ada baris diubah. Tambahkan --commit untuk REPLACE isi tabel.`

**Exit code:** `0` bila semua assert lolos; `1` bila ada yang gagal — termasuk bila
verifikasi pasca-`--commit` (COUNT=135, hanya periode target, jiwa 378.190,
KK 132.030) tidak terpenuhi.

---

## 6. Rencana task (checkbox)

- [x] **T1** — Baca memo desain, audit Excel, `AGENTS.md`, `BkkbnWarmup`,
      `ProfilDukcapilModel`, migration, kedua seeder.
- [x] **T2** — Probe layout Excel langsung via phpspreadsheet (8 sheet × struktur
      blok, header, posisi kolom) → konfirmasi 135 desa / 11 kec per sheet.
- [x] **T3** — Tulis rencana ini (`docs/superpowers/plans/…`) — **selesai sebelum
      implementasi**.
- [x] **T4** — `app/Libraries/DukcapilMapping.php`
  - [x] konstanta: `USIA_MAP` (`65-99`→`65-69`), `PENDIDIKAN_MAP`, `PEKERJAAN_MAP`
  - [x] `pecahGender(int $nilai, float $rasioLaki): array{int,int}`
  - [x] `pecah75plus(int $nilai): array{int,int,int}`
  - [x] `sebarPiramida(array $usia16, int $jiwaL, int $jiwaP): array` (+ koreksi pembulatan)
  - [x] `skalaPendidikan(array $pend10, int $kkTotal, int $jiwaTotal): array` (Σ == kk_total)
  - [x] `skalaPekerjaan(array $ker11, int $kkTotal, int $jiwaTotal): array` (Σ == kk_total)
  - [x] `petakDokumen(array $dok6, int $kkTotal): array`
  - [x] `hitungKelompokKhusus(array $row): array{int,int,int}`
  - [x] `estimasiTanpaSumber(int $kkTotal, int $jiwaTotal): array` (A7 — ditandai `ESTIMASI`)
  - [x] `petakSatuDesa(array $barisExcel, int $semester, int $tahun): array` (entry point)
  - [x] `kolomResmi(): array` / `kolomEstimasi(): array` (untuk ringkasan dry-run)
- [x] **T5** — `app/Commands/DukcapilImport.php`
  - [x] `run()`: baca opsi (`opsi()`), load workbook, hard-assert, join `m_desa`, ringkas
  - [x] `bacaWorkbook()` + `pindai()`: pemindaian blok by pola kode kolom B (tidak hardcode nomor baris)
  - [x] `validasi()`: V1–V5 (+ `cekKonsistensiBaris()` = V7 per baris)
  - [x] `joinDesa()`: V6 — join `m_desa.kode_desa`, laporkan tanpa pasangan & beda nama
  - [x] `ringkas()`: tabel per kecamatan, hard-assert, ringkasan resmi vs estimasi,
        dokumen, **rencana replace (yang dihapus + yang disisipkan)**
  - [x] `tulis()`: **REPLACE** — `DELETE FROM` (DML transaksional) + `insertBatch()`
        dalam transaksi, lalu verifikasi pasca-commit; hanya bila `--commit`
  - [x] **Nit transaksionalitas**: `truncate()` diganti `DELETE FROM` karena
        `TRUNCATE` = DDL + implicit commit (tidak bisa rollback). `delete()` tanpa
        where tidak bisa dipakai (CI4 melempar `DatabaseException`) → raw query
        + `protectIdentifiers()`. Terukur: `TRUNCATE`+rollback → 0 baris,
        `DELETE FROM`+rollback → 135 baris pulih.
  - [x] docblock kelas diperbaiki: alasan `getLatestPerDesa()` diganti penjelasan
        `getAggregatedStats()` (SUM tanpa filter periode) → replace
- [x] **T6** — `tests/unit/DukcapilMappingTest.php` — **26 test / 233 assertion, hijau semua**
  - [x] (a) Σ piramida == `jiwa_l + jiwa_p` (4 test: sintetis, rasio 0.6, kasus nol, tanpa negatif)
  - [x] (b) Σ `kk_pend_*` == `kk_total` (3 test: 1 skala, 6 skala, penggabungan SD)
  - [x] (c) Σ `kk_ker_*` == `kk_total` (3 test: 1 skala, 6 skala, tepat 8 kolom DB)
  - [x] (d) `65-99` → `u65_69_l/p` (2 test: label asli + label sudah diperbaiki)
  - [x] (e) `75+` terpecah ke `u75_79`/`u80_84`/`u85_atas` (3 test: 50/30/20, anti-hilang, rasio)
  - [x] (f) `kk_belum_punya_kartu_fisik == kk_total − kartu_keluarga` (3 test: normal, diklem, KIA/cerai)
  - [x] (g) `jml_balita`/`jml_remaja`/`jml_lansia` dari piramida (3 test)
  - [x] ekstra: 81 kolom tabel terpetakan semua; resmi vs estimasi tidak tumpang tindih;
        estimasi mengikuti formula seeder; `semester`/`tahun` ikut masuk; batas rasio gender
- [x] **T7** — Verifikasi sebelum commit: `php -l` (3 file baru) OK; phpunit 58/58 hijau;
      `php spark dukcapil:import` dry-run exit 0 + mencetak rencana replace; cross-check
      independen terhadap `tests/data/dukcapil_sm1_2026.json` → 135 desa, KK 132.030,
      jiwa 378.190, 0 mismatch.
- [x] **T8** — `php spark dukcapil:import --commit` **DIJALANKAN** atas persetujuan user
      (SM1/2026 menggantikan SM2/2025). Dibuktikan dengan SQL: `COUNT(*)=135`,
      periode tersisa hanya 2026/1, `SUM(jiwa_l+jiwa_p)=378.190`,
      `SUM(kk_l+kk_p)=132.030`. **TIDAK** commit git.
- [x] **T9** — Nit transaksionalitas (setelah T8): `truncate()` → `DELETE FROM`.
      Diverifikasi: `php -l` OK, dry-run exit 0 tanpa regresi, `--commit` ulang
      → COUNT=135 / periode 2026/1 / jiwa 378.190; dan simulasi kegagalan
      (DELETE + `transRollback()`) mengembalikan 135 baris → klaim
      "rollback utuh" kini benar. phpunit 58/58 tetap hijau.

---

## 7. Kriteria penerimaan

1. `php -l` lolos untuk `app/Libraries/DukcapilMapping.php`,
   `app/Commands/DukcapilImport.php`, `tests/unit/DukcapilMappingTest.php`.
2. `vendor\bin\phpunit tests/unit/DukcapilMappingTest.php` — **seluruh test hijau**
   (26 test / 233 assertion; mencakup 7 kasus a–g). Seluruh suite `tests/unit`
   juga harus tetap hijau (58 test).
   Catatan Windows/Laragon: bila `vendor\bin\phpunit` gagal
   ("%1 is not a valid Win32 application"), pakai
   `php vendor/phpunit/phpunit/phpunit tests/unit/DukcapilMappingTest.php`.
3. `php spark dukcapil:import` (dry-run) mencetak:
   - 135 desa ter-join (0 tanpa pasangan),
   - total KK **132.030** dan jiwa **378.190** — cocok dengan Excel,
   - tabel 11 kecamatan dengan Δ = 0 terhadap total Excel per kecamatan,
   - ringkasan kolom resmi vs estimasi,
   - **rencana replace**: jumlah baris & periode yang akan dihapus + yang akan
     disisipkan (mis. "Dihapus: 135 baris (135 baris 2/2025)").
4. Exit code 0 pada dry-run yang valid; non-zero bila assert gagal.
5. Tidak ada perubahan pada migration / seeder / model / view / controller
   (verifikasi lewat daftar file yang disentuh).
6. **Verifikasi WAJIB pasca-`--commit`** (dicetak otomatis oleh command, dan
   harus dicek ulang via SQL):
   - `SELECT COUNT(*) FROM profil_dukcapil` = **135**.
   - Daftar periode tersisa = **hanya (tahun=2026, semester=1)**; tidak ada
     periode lain (`SELECT tahun, semester, COUNT(*) ... GROUP BY tahun, semester`
     menghasilkan tepat 1 baris).
   - `SELECT SUM(jiwa_l + jiwa_p) FROM profil_dukcapil` = **378.190**
     (**BUKAN** ~753.773 — angka itu pertanda double-count periode).
   - `SELECT SUM(kk_l + kk_p) FROM profil_dukcapil` = **132.030**.
   - Bila salah satu gagal → command mengembalikan exit code non-zero.
7. **Pengosongan tabel harus transaksional**: memakai `DELETE FROM` (DML), BUKAN
   `TRUNCATE`/`emptyTable()` (DDL + implicit commit → tidak bisa di-rollback).
   Bukti: `DELETE FROM` + `transRollback()` mengembalikan 135 baris; `TRUNCATE` +
   `transRollback()` menyisakan 0 baris. `$builder->delete()` tanpa where tidak
   bisa dipakai (CI4 melempar `DatabaseException`) → raw query + `protectIdentifiers()`.

---

## 8. Cara verifikasi (perintah persis)

```bat
cd /d D:\laragon\www\erdekatala

REM 1. Sintaks
php -l app\Libraries\DukcapilMapping.php
php -l app\Commands\DukcapilImport.php
php -l tests\unit\DukcapilMappingTest.php

REM 2. Unit test fungsi murni
vendor\bin\phpunit tests/unit/DukcapilMappingTest.php
REM   (fallback bila bin proxy gagal:)
php vendor/phpunit/phpunit/phpunit tests/unit/DukcapilMappingTest.php

REM 3. Dry-run impor (TIDAK menulis apa pun; hanya laporan rencana replace)
php spark dukcapil:import

REM 4. Commit — REPLACE isi tabel (user sudah menyetujui SM1/2026 menggantikan SM2/2025)
php spark dukcapil:import --commit

REM 5. Verifikasi WAJIB pasca-commit
mysql -u root -e "SELECT COUNT(*) AS total_rows FROM erdekatala.profil_dukcapil"
mysql -u root -e "SELECT tahun, semester, COUNT(*) c FROM erdekatala.profil_dukcapil GROUP BY tahun, semester"
mysql -u root -e "SELECT SUM(jiwa_l+jiwa_p) AS total_jiwa, SUM(kk_l+kk_p) AS total_kk FROM erdekatala.profil_dukcapil"
```

Harapan setelah langkah 5:

| Query | Hasil yang benar | Pertanda GAGAL |
|---|---|---|
| `COUNT(*)` | **135** | 270 (periode ganda) |
| Daftar periode | **tepat 1 baris: 2026 / 1** | masih ada 2025 / 2 |
| `SUM(jiwa_l+jiwa_p)` | **378.190** | ~753.773 (double-count) |
| `SUM(kk_l+kk_p)` | **132.030** | ~263.938 (double-count) |

`php spark db:query` tidak tersedia di CI4 4.6.5 — verifikasi memakai `mysql`
langsung (atau `php -r` dengan mysqli).

Catatan landing page (mengikuti memo §8.2): `?sumber=dukcapil` → kartu jiwa/KK
≈ 378.190 / 132.030; note-strip menampilkan "Semester I 2026"; `/dashboard` tidak
berubah (dashboard tidak membaca `profil_dukcapil`).

---

## 9. Asumsi terbuka yang harus dikonfirmasi ke Dukcapil

1. **`65-99` = `65-69`** (A1) — bukti angka kuat, belum konfirmasi resmi.
2. **Pemecahan `75+` = 50/30/20** (A2) — mengikuti seeder, bukan data.
3. **Rasio gender flat per grup usia** (A3) — Excel tidak memisahkan L/P per usia.
4. **Skala jiwa→KK untuk pendidikan & pekerjaan** (A4) — angka `kk_pend_*` /
   `kk_ker_*` hasil impor **bukan** angka resmi per-KK; label landing page
   "...pada Kepala Keluarga" karenanya menyesatkan dan perlu ditinjau terpisah.
5. **Tanggal potong dokumen 30-09-2026 ≠ 30-06-2026** (A5) — rasio cakupan dokumen
   tidak se-perioda dengan angka jiwa/KK.
6. **KIA & Akta Perceraian kini DISIMPAN** (A5) — ~~perlu migration bila
   dibutuhkan~~ sudah selesai: migration `2026-10-09-000001` menambah `dok_kia`
   & `dok_akta_cerai` ke `profil_dukcapil`. Basis PENDUDUK, bukan KK. Yang masih
   terbuka hanya konfirmasi bahwa keduanya memang kepemilikan dokumen per
   penduduk (bukan per KK) sesuai judul kolom Excel.
7. **Nama desa `63.01.07.2013`** — Excel `MEKAR SARI`, DB `MEKAR RAYA`; salah satu
   perlu diluruskan admin_dinas (tidak menyentuhnya dalam tugas ini).
