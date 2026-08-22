# Design Spec: Validasi Konsistensi Laporan & Brute-Force Protection Login

**Tanggal:** 2026-08-22
**Status:** Approved (brainstorming)
**Project:** Erdekatala — Sistem Informasi Kependudukan Agregat Kabupaten Tanah Laut

---

## 1. Latar Belakang

Dua gap keamanan & kualitas data yang teridentifikasi dari review fitur:

1. **Form laporan agregat (~80 kolom numerik) hanya tervalidasi cek duplikat periode.** Tidak ada validasi server-side untuk nilai numerik, dan tidak ada pemeriksaan konsistensi antar kolom (mis. total piramida umur vs total jiwa). Typo/kesalahan input langsung masuk ke statistik kabupaten.
2. **Login tidak dibatasi.** `AuthController::proses_login()` menerima percobaan gagal tanpa batas — rentan brute-force.

### Keputusan hasil brainstorming dengan user

| Topik | Keputusan |
|---|---|
| Perilaku saat data tidak konsisten | **Warning + boleh tetap simpan** (konfirmasi eksplisit) |
| Toleransi perbandingan antar kolom | **Toleransi kecil** (selisih wajar tidak memicu warning) |
| Dasar penguncian brute-force | **Per kombinasi username + IP** |
| Storage counter login gagal | **File cache + `flock`** (pola sama dengan rate-limit API key) |
| Reset password | **Tidak dibuat** — user yang lupa password menghubungi admin_dinas |
| Workflow verifikasi laporan | **Tidak disentuh** — kondisi saat ini sudah sesuai kebutuhan |

### Bonus bug fix (ditemukan saat eksplorasi)

Form input (`app/Views/laporan/form.php`) memakai nama field `u85_plus_l`/`u85_plus_p` untuk kelompok umur 85+, sedangkan kolom database dan `LaporanAgregatModel::$allowedFields` memakai `u85_atas_l`/`u85_atas_p`. Akibatnya data umur 85+ yang diinput **tidak pernah tersimpan** (silently discarded oleh `$protectFields`). Diperbaiki dalam spec ini karena aturan piramida penduduk bergantung padanya.

---

## 2. Fitur 1: Validasi Konsistensi Laporan

### 2.1 Arsitektur

Rule engine baru: **`App\Libraries\LaporanConsistency`** — satu kelas, dua method publik:

```php
class LaporanConsistency
{
    // Hard errors: memblokir simpan. Return array string pesan error.
    public function validateBasic(array $data): array;

    // Soft warnings: tidak memblokir. Return array ['code' => string, 'message' => string].
    public function check(array $data): array;
}
```

Kedua method menerima array data POST laporan dan berupa fungsi murni (tanpa akses DB/session) sehingga mudah di-unit-test.

### 2.2 Aturan dasar (hard — memblokir simpan)

Dijalankan oleh `validateBasic()`:

- **C1**: Setiap field statistik wajib bilangan bulat ≥ 0. Nilai kosong/string kosong dianggap `0` (sesuai default form). Nilai negatif atau non-numerik → error yang menyebut nama field-nya.

Field yang dicakup C1: semua field dalam `LaporanAgregatModel::$allowedFields` kecuali `id_desa`, `id_user`, `bulan`, `tahun` (keempatnya divalidasi terpisah sesuai aturan existing).

### 2.3 Aturan konsistensi (soft — warning)

**Toleransi:** selisih `|a − b|` dianggap wajar jika `≤ max(2, floor(0.01 × max(a, b)))`.

**Aturan identitas** — kedua sisi harus sama dalam batas toleransi:

| Kode | Sisi kiri | Sisi kanan |
|---|---|---|
| A1 | `kk_l + kk_p` | Jumlah 7 field pendidikan KK (`kk_pend_tidak_sekolah` s/d `kk_pend_s2_s3`) |
| A2 | `kk_l + kk_p` | Jumlah 8 field pekerjaan KK (`kk_ker_tani` s/d `kk_ker_tidak_kerja`) |
| A3 | `kk_l + kk_p` | Jumlah 4 field status kawin (`kk_belum_kawin`, `kk_kawin`, `kk_cerai_hidup`, `kk_cerai_mati`) |
| A4 | `kk_l + kk_p` | `kk_punya_kartu_fisik + kk_belum_punya_kartu_fisik` |
| A5 | `jiwa_l` | Jumlah 18 field piramida laki-laki (`u0_4_l` s/d `u85_atas_l`) |
| A6 | `jiwa_p` | Jumlah 18 field piramida perempuan (`u0_4_p` s/d `u85_atas_p`) |
| A7 | `jiwa_l + jiwa_p` | `penduduk_hub_kepala + penduduk_hub_istri + penduduk_hub_anak + penduduk_hub_lainnya` |
| A8 | `jiwa_l + jiwa_p` | `jkn + non_jkn` |
| A9 | `pus_jkn` | `pus_pbi + pus_non_pbi` |
| A10 | `jml_balita` | `u0_4_l + u0_4_p` |

**Aturan kewajaran "tidak boleh melebihi"** (warning jika kiri > kanan):

| Kode | Aturan |
|---|---|
| B1 | `kb_aktif ≤ jml_pus` |
| B2 | `jml_penggunaan_alat_kontrasepsi ≤ jml_pus` |
| B3 | `pend_wajib_ktp ≤ jiwa_l + jiwa_p` |
| B4 | `pend_punya_akta_lahir ≤ jiwa_l + jiwa_p` |

Aturan B tidak memakai toleransi (pelanggaran berapapun → warning), namun tetap tidak memblokir.

**Aturan data kosong** (warning, dikembalikan oleh `check()`):

- **C2**: Total jiwa (`jiwa_l + jiwa_p`) = 0 → warning "Seluruh data jiwa bernilai 0 — pastikan ini bukan kesalahan input." Tidak memblokir simpan.

**Format pesan warning** (bahasa Indonesia, menyebut kedua nilai dan selisih):

> "Piramida umur Laki-laki (620) tidak sesuai Total Jiwa L (650) — selisih 30."

Daftar label sisi kiri/kanan disimpan sebagai konstanta di dalam library.

### 2.4 Alur validasi di controller

Berlaku sama untuk `LaporanAgregatController::store()` dan `update()`:

1. Cek role (existing, tidak berubah).
2. **`validateBasic()`** → jika ada hard error: `redirect()->back()->withInput()->with('error', ...)` (perilaku standar).
3. Cek duplikat periode (existing, tidak berubah — hanya di `store()`).
4. **`check()`** → kumpulkan warnings.
5. Jika warnings tidak kosong **dan** POST tidak mengandung `force_save=1`: render ulang view form secara langsung (tanpa redirect) dengan variabel `$warnings` berisi daftar warning. Nilai isian dipertahankan via `old()`.
6. Jika `force_save=1`: simpan seperti biasa; warnings diabaikan. Audit log (`laporan_log`) dicatat normal (aksi `create`/`update`) — tidak ada penanda khusus bahwa laporan disimpan dengan warning.

### 2.5 Perubahan view form (`app/Views/laporan/form.php`)

1. **Dukungan `old()`**: semua input numerik berubah dari `value="<?= $laporan[$field] ?? 0 ?>"` menjadi `value="<?= old($field, $laporan[$field] ?? 0) ?>"` agar isian tidak hilang saat render ulang akibat warning/error. Select `id_desa`/`bulan` dan input `tahun` juga memakai `old()`.
2. **Fix bug 85+**: array `$groups` berubah dari `'85_plus'` menjadi `'85_atas'`; label khusus untuk grup terakhir ditampilkan sebagai "85+" (bukan "85-atas").
3. **Dialog warning**: jika view menerima `$warnings` tidak kosong, tampilkan SweetAlert2 modal saat halaman dimuat:
   - Judul: "Data Belum Konsisten"
   - Isi: daftar warning (di-`esc()`; diinjeksi ke JS via `json_encode` + `esc()`)
   - Tombol **"Perbaiki Data"** (sekunder): menutup dialog, user mengedit form.
   - Tombol **"Tetap Simpan"** (primer, warning): menambahkan hidden input `force_save=1` ke form lalu submit.
4. Hidden input `force_save` tidak dirender secara default; hanya diinjeksi JS saat "Tetap Simpan" diklik.

### 2.6 Yang TIDAK berubah

- Halaman export, dashboard, data-tabel, API — tidak terdampak.
- Skema database — tidak ada migration.
- Audit log — tidak ada perubahan skema/perilaku.

---

## 3. Fitur 2: Brute-Force Protection Login

### 3.1 Library baru: `App\Libraries\LoginThrottle`

```php
class LoginThrottle
{
    public const MAX_ATTEMPTS   = 5;     // percobaan gagal sebelum terkunci
    public const WINDOW_SECONDS = 900;   // jendela penghitungan (15 menit)
    public const LOCK_SECONDS   = 900;   // durasi penguncian (15 menit)

    public function __construct(?string $cacheDir = null); // default: WRITEPATH.'cache'

    // Terkunci? Return sisa detik jika ya, false jika tidak.
    public function isLocked(string $username, string $ip): int|false;

    // Catat satu kegagalan; mengunci otomatis saat mencapai MAX_ATTEMPTS.
    public function registerFailure(string $username, string $ip): void;

    // Hapus counter (dipanggil saat login sukses).
    public function clear(string $username, string $ip): void;
}
```

### 3.2 Mekanisme penyimpanan

- File: `<cacheDir>/login_bf_<sha256(strtolower($username) . '|' . $ip)>.json`
- Isi: `{"count": int, "window_start": <unix ts>, "locked_until": <unix ts|null>}`
- Semua baca-tulis memakai `flock` (LOCK_SH untuk baca, LOCK_EX untuk tulis) — pola yang sama dengan `ApiKeyModel::validateKey()`.
- Semantik:
  - `isLocked()`: jika `locked_until > now` → return sisa detik; selain itu `false`. File tidak ada → `false`.
  - `registerFailure()`: jika tidak ada file, atau `window_start` lebih tua dari `WINDOW_SECONDS` (dan tidak terkunci) → mulai jendela baru (`count=1`, `window_start=now`). Selain itu `count++`. Jika `count >= MAX_ATTEMPTS` → `locked_until = now + LOCK_SECONDS`, lalu `count` di-reset ke 0 agar setelah kunci lewat pengguna mendapat 5 percobaan baru.
  - `clear()`: hapus file jika ada.
- Tidak ada migration; tidak ada cron pembersihan — file berukuran ±100 byte dan hanya dibaca saat ada upaya login untuk kombinasi tersebut.

### 3.3 Integrasi ke `AuthController::proses_login()`

Urutan baru:

1. Validasi form (existing).
2. **`isLocked(username, ip)`** → jika terkunci: `redirect()->back()->withInput()->with('error', "Terlalu banyak percobaan login yang gagal. Silakan coba lagi dalam X menit.")` — kredensial tidak dicek; pesan tidak mengungkapkan ada-tidaknya akun.
3. Lookup user + `password_verify` (existing).
4. **Gagal** → `registerFailure()` lalu redirect dengan pesan generik existing ("Username atau password salah") — tanpa informasi sisa percobaan.
5. **Sukses** → `clear()` lalu lanjut alur login normal (session, remember-me, notifikasi).

IP diambil dari `$this->request->getIPAddress()`.

### 3.4 Catatan operasional

- Login otomatis via cookie remember-me (filter `LoggedIn`) **tidak** melewati `proses_login()`, sehingga tidak terpengaruh throttle.
- Deployment di belakang proxy/Cloudflare: agar IP klien benar, `Config\App::$proxyIPs` harus diset (dokumentasi deployment; di luar kode spec ini).
- Kunci per kombinasi username+IP: satu kantor dengan IP publik bersama tidak saling memblokir selama username berbeda; akun yang sama dari perangkat/IP berbeda juga tidak saling mengunci.

---

## 4. Pengujian

### 4.1 Unit test baru: `tests/unit/LaporanConsistencyTest.php`

- Data sepenuhnya konsisten → `check()` return array kosong.
- Untuk tiap aturan A1–A10 dan B1–B4: satu kasus dilanggar → warning dengan kode yang bersesuaian muncul.
- Batas toleransi: dengan sisi kiri 1.000 — selisih 10 (≤ max(2, 10)) → OK; selisih 11 → warning. Kasus kecil: kiri 50, kanan 52 (selisih 2) → OK; kanan 53 → warning.
- `validateBasic()`: nilai negatif → error; string kosong → diperlakukan 0 (tanpa error); non-numerik ("abc") → error.
- C2: semua jiwa 0 → warning C2 muncul.

### 4.2 Unit test baru: `tests/unit/LoginThrottleTest.php`

Memakai direktori temp (via constructor `$cacheDir`), dibersihkan di `tearDown()`:

- 4 kegagalan → belum terkunci; kegagalan ke-5 → `isLocked()` return sisa detik > 0.
- `clear()` setelah gagal → `isLocked()` false, counter mulai dari awal.
- Jendela lewat (manipulasi `window_start` dengan menulis file langsung) → kegagalan berikutnya dihitung sebagai percobaan pertama.
- `locked_until` lewat → tidak terkunci lagi.

### 4.3 Verifikasi manual

1. Login sebagai admin_desa; isi form dengan data tidak konsisten (mis. total jiwa ≠ piramida) → muncul dialog "Data Belum Konsisten"; tombol "Perbaiki Data" menutup dialog dengan isian utuh; "Tetap Simpan" menyimpan laporan.
2. Input negatif → error hard, tidak tersimpan.
3. Salah password 5× berturut-turut → percobaan ke-6 (meski password benar) ditolak dengan pesan sisa waktu; setelah 15 menit login normal kembali.
4. Cek audit log: laporan yang disimpan via "Tetap Simpan" tetap tercatat di `/laporan/aktivitas`.

---

## 5. File Terdampak

| File | Jenis | Perubahan |
|---|---|---|
| `app/Libraries/LaporanConsistency.php` | Baru | Rule engine (validateBasic + check + daftar aturan) |
| `app/Libraries/LoginThrottle.php` | Baru | Counter file + flock (isLocked/registerFailure/clear) |
| `app/Controllers/LaporanAgregatController.php` | Ubah | `store()`/`update()`: panggil rule engine, tangani `force_save` + render ulang dengan `$warnings` |
| `app/Controllers/AuthController.php` | Ubah | `proses_login()`: integrasi LoginThrottle |
| `app/Views/laporan/form.php` | Ubah | `old()` di semua input, fix `85_plus`→`85_atas`, dialog SweetAlert warning + injeksi `force_save` |
| `tests/unit/LaporanConsistencyTest.php` | Baru | Sesuai §4.1 |
| `tests/unit/LoginThrottleTest.php` | Baru | Sesuai §4.2 |

Tidak ada migration database. Tidak ada file config yang wajib berubah.

---

## 6. Checklist DoD (PRD §7)

- [x] Validasi input di sisi server (rule engine server-side; dialog hanya menampilkan hasil).
- [x] Akses antar role tidak berubah (`store`/`update` tetap memblokir admin_kecamatan).
- [x] Output HTML warning memakai `esc()`.
- [x] CSRF tetap menyertai re-submit form (form yang sama, token existing).
- [x] Tampilan dialog konsisten dengan SweetAlert2/AdminLTE yang sudah dipakai project.
