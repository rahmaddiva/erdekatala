# Fix: HTTP 500 `Call to undefined function App\Filters\get_cookie()`

**Tanggal:** 2026-10-09
**Lingkungan:** CodeIgniter 4.6.5, PHP 8.3.29 (Laragon/Windows)
**Scope file:** `app/Filters/LoggedIn.php` (satu-satunya file yang diubah)

---

## 1. Ringkasan

Halaman yang melewati filter `loggedin` mengembalikan **HTTP 500** karena
`get_cookie()` dipanggil di dalam filter tanpa memuat helper `cookie` terlebih
dahulu. Di CI4 helper **tidak** di-autoload, sehingga fungsi tersebut tidak
ada di namespace `App\Filters`.

Perbaikan: memuat `helper('cookie')` secara eksplisit **sebelum** pemanggilan
`get_cookie()`. Tidak ada perubahan logika bisnis.

---

## 2. Audit pemakaian helper cookie di `app/`

Grep: `get_cookie|set_cookie|delete_cookie|helper('cookie')`

| # | Lokasi | Pemakaian | Status | Tindakan |
|---|--------|-----------|--------|----------|
| 1 | `app/Filters/LoggedIn.php:21` | `get_cookie('remember_token')` | **BUG** — helper tidak dimuat | **DIPERBAIKI** |
| 2 | `app/Controllers/AuthController.php:215` | `$response->setCookie(...)` | Aman — method object `Response` | Tidak diubah |
| 3 | `app/Controllers/AuthController.php:235` | `service('response')->deleteCookie(...)` | Aman — method object `Response` | Tidak diubah |

### Kenapa #2 dan #3 aman

Keduanya memakai **API framework berbasis objek**
(`CodeIgniter\HTTP\ResponseTrait::setCookie()` / `deleteCookie()`), bukan fungsi
prosedural dari helper. Keduanya tidak butuh `helper('cookie')`.

Catatan: helper `cookie` **tidak** dimuat di mana pun sebelumnya
(`app/Config/Autoload.php :: $helpers = []`, `app/Common.php` kosong, tidak ada
pemuatan di `Events.php` maupun `BaseController`). Jadi kasus #1 benar-benar
berujung fatal error.

---

## 3. Perbaikan

**File:** `app/Filters/LoggedIn.php`

### Sebelum (500)

```php
        // Coba auto-login via remember_token cookie
        $token = get_cookie('remember_token');
```

### Sesudah

```php
        // Helper cookie tidak di-autoload; wajib dimuat eksplisit di konteks filter.
        // Urutan penting: helper('cookie') harus dipanggil SEBELUM get_cookie().
        helper('cookie');
        // Coba auto-login via remember_token cookie
        $token = get_cookie('remember_token');
```

### Mengapa pendekatan ini dipilih

- **Perilaku identik.** `get_cookie('remember_token')` secara internal memanggil
  `service('request')->getCookie($prefix . $index, FILTER_DEFAULT)` dengan
  prefix dari `Config\Cookie` (`''` di aplikasi ini). Menggantinya dengan
  `Services::request()->getCookie('remember_token')` juga valid dan semantiknya
  sama (return `null` bila cookie tidak ada), tetapi memuat helper menjaga
  baris pemanggil tetap persis seperti semula — nol risiko regresi pada
  semantik prefix/filter.
- **Idiomatis CI4.** Dokumentasi CI4 menyatakan helper harus dimuat eksplisit
  (`helper('cookie');`) sebelum dipakai; ini pola yang direkomendasikan.
- **Urutan terjaga.** `helper('cookie')` diletakkan tepat sebelum pemakaian,
  bukan di constructor, agar tetap aman untuk semua jalur eksekusi filter.

### Yang TIDAK diubah (sesuai instruksi)

Logika bisnis dibiarkan utuh: nama cookie `remember_token`, durasi 30 hari,
httpOnly, serta alur `cookie hilang -> redirect('/login')`.

---

## 4. Verifikasi

### 4.1 Lint

```
$ php -l app/Filters/LoggedIn.php
No syntax errors detected in app/Filters/LoggedIn.php
```

### 4.2 Server

```
$ php spark serve --host localhost --port 8085
CodeIgniter development server started on http://localhost:8085
```

### 4.3 HTTP status codes

| Endpoint | Hasil | Ekspektasi | Status |
|----------|-------|------------|--------|
| `/dashboard` | **302** | 302 (redirect `/login`) | PASS |
| `/kecamatan` | **302** | 302/200, bukan 500 | PASS |
| `/login` | **200** | 200 | PASS |

Header `/dashboard` mengonfirmasi redirect tepat ke login:

```
HTTP/1.1 302 Found
Location: http://localhost:8085/login
```

### 4.4 Tidak ada `Call to undefined function`

Output body untuk `/dashboard`, `/kecamatan`, `/login` disaring terhadap
`undefined function|Fatal error|Parse error|Call to undefined` → **bersih**
pada ketiganya.

### 4.5 Auto-login via `remember_token` tetap berfungsi

Diuji secara end-to-end agar "perilaku identik" benar-benar terbukti, bukan
hanya "tidak lagi 500":

| Skenario | Hasil | Ekspektasi |
|----------|-------|------------|
| Token tidak ada | 302 → `/login` | redirect |
| Token sampah (`remember_token=deadbeef...`, tidak cocok DB) | **302** → `/login` | redirect, bukan 500 |
| Token valid (ditanam sementara di `users.remember_token`) | **200** pada `/dashboard` dan `/kecamatan` | auto-login sukses |

Token uji yang ditanam sementara **sudah dikembalikan ke `NULL`**
(`users.id_user=1` diverifikasi kembali `(null)`), dan setelah itu
`/dashboard` kembali 302 seperti semula.

### 4.6 Log

`writable/logs/log-2026-10-09.log` memperlihatkan error sebelum perbaikan:

```
CRITICAL - 2026-10-09 15:51:46 --> Error: Call to undefined function App\Filters\get_cookie()
CRITICAL - 2026-10-09 15:57:58 --> Error: Call to undefined function App\Filters\get_cookie()
```

Setelah perbaikan (request jam 16:18) tidak ada lagi entri `get_cookie`;
yang tersisa hanya baris `Session: Class initialized` (DEBUG, normal).

### 4.7 Server dimatikan

Port 8085 dipastikan bebas (`no 8085 sockets at all`).

---

## 5. Files touched

- `app/Filters/LoggedIn.php` — ditambahkan `helper('cookie');` (1 lokasi)

Tidak ada file lain yang diubah. `git status` menunjukkan perubahan lain di
repo (Views, Models, Seeders, Migrations, `AGENTS.md`) adalah milik pekerja
lain dan tidak disentuh.

**Tidak dilakukan commit** sesuai instruksi.
