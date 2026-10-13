# 2026-10-09 — Label sumber data resmi DKB di command `dukcapil:import` + AGENTS.md

Tugas teks/dokumentasi. **Logika impor tidak diubah** (tidak ada perubahan angka,
validasi V1–V7, atau alur REPLACE).

Label resmi (baku, termasuk tanda bintang di depan):

```
* Sumber data adalah Data Konsolidasi Bersih (DKB) Semester I Tahun 2026
```

## Lokasi perubahan

### 1. `app/Commands/DukcapilImport.php`

**1a. Helper label — satu sumber kebenaran (baru).** Ditaruh setelah deklarasi
properti `$tanpaPasangan` / `$namaBeda`, sebelum `run()`:

```php
// SEBELUM: tidak ada
// SESUDAH:
    /** Semester -> angka Romawi (label resmi memakai Romawi: "Semester I"). */
    private const ROMAWI = [1 => 'I', 2 => 'II'];

    public static function labelSumber(int $semester, int $tahun): string
    {
        return '* Sumber data adalah ' . self::sumberSingkat($semester, $tahun);
    }

    private static function sumberSingkat(int $semester, int $tahun): string
    {
        return 'Data Konsolidasi Bersih (DKB) Semester '
            . (self::ROMAWI[$semester] ?? (string) $semester)
            . ' Tahun ' . $tahun;
    }
```

Alasan dibuat dinamis (bukan literal): command punya opsi `--semester`/`--tahun`.
Literal akan membuat output membaca "Semester I Tahun 2026" walau yang diimpor
2/2027. Kata-katanya tetap persis; hanya angka Romawi semester & tahun yang
mengikuti periode. Default (1/2026) menghasilkan label di atas **karakter demi
karakter**. Helper `public static` supaya view/lainnya bisa reuse satu sumber
kebenaran tanpa hardcode ulang.

**1b. Header command — `Sumber:` ikut helper** (sebelumnya literal "Semester I 2026"):

```php
// SEBELUM:
CLI::write('  Sumber   : Data Konsolidasi Bersih (DKB) Semester I 2026');
// SESUDAH:
CLI::write('  Sumber   : ' . self::sumberSingkat($semester, $tahun));
```

**1c. Blok baru `SUMBER DATA RESMI` di `ringkas()`** — ditempatkan setelah daftar
ASUMSI & CATATAN (A1–A8 + nama desa beda), sebelum "Baris siap ditulis". Dipakai
di **dua** mode karena `ringkas()` dipanggil sebelum percabangan `--commit`:

```php
// SESUDAH (disisipkan):
        CLI::newLine();
        CLI::write(CLI::color('  SUMBER DATA RESMI', 'green'));
        CLI::write('    ' . self::labelSumber($semester, $tahun));
        CLI::write('    (Lihat A5: sheet KEPEMILIKAN DOK di workbook terlabel "DKB pertanggal 30092026" —');
        CLI::write('     beda potongan; 7 sheet lain memakai label resmi di atas.)');
        CLI::newLine();
```

**1d. Blok yang sama di akhir `tulis()`** (ringkasan pasca-commit), setelah
"Verifikasi pasca-commit LOLOS":

```php
// SESUDAH (disisipkan):
        CLI::write('  Verifikasi pasca-commit LOLOS — tidak ada double-count periode.', 'green');
        CLI::newLine();
        CLI::write(CLI::color('  SUMBER DATA RESMI', 'green'));
        CLI::write('    ' . self::labelSumber($semester, $tahun));
```

**1e. Docblock kelas** — mencatat label baku + arah ke helper.

### 2. `AGENTS.md`

**2a. Butir #17 (`php spark dukcapil:import`)** — ditambahkan di ekor butir yang
sudah ada (butir lain tidak diubah/dihapus, penomoran tetap 1..21):

> … **Atribusi sumber data RESMI** yang dipakai aplikasi (baku — jangan ubah
> ejaannya, termasuk tanda bintang di depan): `* Sumber data adalah Data
> Konsolidasi Bersih (DKB) Semester I Tahun 2026`. Sumber kebenarannya SATU helper:
> `DukcapilImport::labelSumber(int $semester, int $tahun)` (`public static`) …
> Catatan workbook: label di atas ada di **7 sheet**; sheet `KEPEMILIKAN DOK`
> terlabel **"DKB pertanggal 30092026"** (beda potongan — dasar asumsi A5).

**2b. Bagian `### Tabel profil_dukcapil`** — paragraf baru setelah "Unique key …":

> **Atribusi sumber data RESMI** untuk tabel ini (baku …): `* Sumber data adalah
> Data Konsolidasi Bersih (DKB) Semester I Tahun 2026`. Dihasilkan oleh
> `DukcapilImport::labelSumber($semester, $tahun)` … Lihat butir #17.

## Bukti verifikasi

**1. Lint:**

```
$ php -l app/Commands/DukcapilImport.php
No syntax errors detected in app/Commands/DukcapilImport.php
```

**2. DRY-RUN (tanpa `--commit`; DB tidak disentuh):**

```
$ php spark dukcapil:import
IMPOR DATA DUKCAPIL — MODE DRY-RUN
  File     : D:\laragon\www\erdekatala\DATA KEPENDUDUKAN KAB TALA SM I 2026.xlsx
  Target   : semester 1 / tahun 2026
  Sumber   : Data Konsolidasi Bersih (DKB) Semester I Tahun 2026
...
  SUMBER DATA RESMI
    * Sumber data adalah Data Konsolidasi Bersih (DKB) Semester I Tahun 2026
    (Lihat A5: sheet KEPEMILIKAN DOK di workbook terlabel "DKB pertanggal 30092026" —
     beda potongan; 7 sheet lain memakai label resmi di atas.)

  Baris siap ditulis: 135 — target (id_desa, semester=1, tahun=2026)
  RENCANA PENULISAN (replace)
    Mode       : DRY-RUN — tidak menulis apa pun
    Dihapus    : 135 baris (135 baris 1/2026)
    Disisipkan : 135 baris periode 1/2026

  DRY-RUN — tidak ada baris diubah. Tambahkan --commit untuk REPLACE isi tabel.
```

Potongan di atas memuat label resmi persis, termasuk tanda bintang.

**3. Label mengikuti opsi (bukti dinamis, tetap dry-run):**

```
$ php spark dukcapil:import --semester 2 --tahun 2027
  Sumber   : Data Konsolidasi Bersih (DKB) Semester II Tahun 2027
  ...
  SUMBER DATA RESMI
    * Sumber data adalah Data Konsolidasi Bersih (DKB) Semester II Tahun 2027
```

**4. Nomor butir AGENTS.md masih konsisten** — 21 butir, urutan 1..21 tanpa
celah. Satu-satunya "duplikasi" (1–7) adalah dua daftar bernomor terpisah di
bagian berbeda dan **sudah ada di HEAD** sebelum perubahan ini (diverifikasi
dengan `git show HEAD:AGENTS.md` → duplikasi identik 1–7).

## Catatan (tidak diubah)

Di file Excel sumber, label resmi ada di **7 sheet**; sheet `KEPEMILIKAN DOK`
berlabel **"DKB pertanggal 30092026"** (potongan berbeda). Hanya dicatat,
sesuai permintaan — tidak ada perubahan pada data maupun asumsi A5.

## Batas kepatuhan

- File disentuh: hanya `app/Commands/DukcapilImport.php` dan `AGENTS.md`
  (+ laporan `docs/superpowers/specs/…` ini).
- Tidak menyentuh `app/Views/**`, `PublicController.php`, `app/Models/**`,
  `app/Filters/**`, migration, seeder.
- Tidak menjalankan `--commit`; tidak commit ke git.
