# Redesign Halaman Login Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Ganti total tampilan halaman login Erdekatala menjadi kartu putih terpusat di atas latar gradient merah lembut, tanpa mengubah perilaku form.

**Architecture:** Tulis ulang `app/Views/auth/login.php` (CSS inline + markup + script di file yang sama, mengikuti pola existing). Layout split-screen kiri/kanan diganti satu kartu terpusat (flexbox centering). Tidak ada perubahan controller, route, model, atau asset; backend dan form behavior identik.

**Tech Stack:** CodeIgniter 4 view (PHP), Bootstrap 4, AdminLTE 3 asset, icheck-bootstrap, FontAwesome, Source Sans Pro.

## Global Constraints

- File yang diubah: HANYA `app/Views/auth/login.php`.
- Form: action `<?= base_url('proses_login') ?>`, method POST, `csrf_field()`, field `username` + `password`, checkbox `remember` — nama & struktur harus persis seperti sekarang.
- Warna identitas: merah `#c0392b`, hover `#a93226`.
- Output user input dengan `esc()`; semua URL via `base_url()`.
- Daftar fitur & cursor-follower DIHAPUS; logo, nama sistem, tagline, footer instansi DIPERTAHANKAN.
- Tidak menambah fitur (mis. link lupa password) — di luar scope.

---

### Task 1: Tulis Ulang `app/Views/auth/login.php`

**Files:**
- Rewrite: `app/Views/auth/login.php`

**Interfaces:**
- Consumes: tidak ada (halaman standalone, bukan bagian template `main`).
- Produces: halaman `/login` dengan markup + CSS baru; form identik fungsional.

- [ ] **Step 1: Tulis seluruh isi file baru**

Ganti seluruh isi `app/Views/auth/login.php` dengan kode berikut:

```html
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Erdekatala | Log in</title>

    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <link rel="stylesheet" href="<?= base_url('assets/plugins/fontawesome-free/css/all.min.css'); ?>">
    <link rel="stylesheet" href="<?= base_url('assets/plugins/icheck-bootstrap/icheck-bootstrap.min.css'); ?>">
    <link rel="stylesheet" href="<?= base_url('assets/dist/css/adminlte.min.css'); ?>">
    <link rel="icon" type="image/png" href="<?= base_url('assets/dist/img/erdekatala.png') ?>">
    <link rel="shortcut icon" href="<?= base_url('favicon.ico') ?>">

    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Source Sans Pro', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            background: linear-gradient(150deg, #fdf3f2 0%, #fce8e5 100%);
            overflow: hidden;
        }

        /* Dekorasi lingkaran blur */
        .blob {
            position: fixed;
            border-radius: 50%;
            pointer-events: none;
            filter: blur(80px);
            z-index: 0;
        }

        .blob-1 {
            width: 420px;
            height: 420px;
            background: rgba(192, 57, 43, 0.18);
            top: -120px;
            right: -100px;
        }

        .blob-2 {
            width: 360px;
            height: 360px;
            background: rgba(192, 57, 43, 0.14);
            bottom: -110px;
            left: -90px;
        }

        /* Kartu login */
        .login-card {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 400px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(192, 57, 43, 0.12);
            padding: 40px 36px 28px;
            text-align: center;
        }

        .login-card .logo-wrap img {
            width: 90px;
            margin-bottom: 18px;
        }

        .login-card h2 {
            font-size: 1.5rem;
            font-weight: 700;
            color: #2d2d2d;
            margin-bottom: 6px;
        }

        .login-card .tagline {
            color: #6c757d;
            font-size: 0.875rem;
            line-height: 1.6;
            margin-bottom: 28px;
        }

        /* Field dengan ikon di dalam */
        .login-card form { text-align: left; }

        .login-card .input-field { position: relative; }

        .login-card .input-field > i {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #adb5bd;
            font-size: 0.9rem;
            pointer-events: none;
            z-index: 2;
        }

        .login-card .input-field .form-control {
            border: 1px solid #d6d6d6;
            border-radius: 8px;
            padding-left: 40px;
            height: 46px;
            color: #333;
        }

        .login-card .input-field .form-control::placeholder {
            color: #9aa0a6;
        }

        .login-card .input-field .form-control:focus {
            border-color: #c0392b;
            box-shadow: 0 0 0 0.2rem rgba(192, 57, 43, 0.12);
        }

        /* Checkbox & tombol */
        .login-card .remember-row {
            margin-bottom: 24px;
        }

        .login-card .remember-row label {
            color: #6c757d;
            font-size: 0.875rem;
        }

        .login-card .btn-masuk {
            background: #c0392b;
            border: none;
            border-radius: 8px;
            padding: 10px;
            height: 46px;
            font-size: 0.95rem;
            font-weight: 600;
            letter-spacing: 0.5px;
            transition: background 0.2s;
        }

        .login-card .btn-masuk:hover {
            background: #a93226;
        }

        /* Footer instansi */
        .login-card .instansi {
            margin-top: 24px;
            padding-top: 16px;
            border-top: 1px solid #f0f0f0;
            font-size: 0.75rem;
            color: #adb5bd;
            line-height: 1.6;
            text-align: center;
        }

        /* Responsive */
        @media (max-width: 576px) {
            body { padding: 16px; }
            .login-card { padding: 32px 24px 24px; }
            .blob-1 { width: 260px; height: 260px; }
            .blob-2 { width: 220px; height: 220px; }
        }
    </style>
</head>

<body>

    <div class="blob blob-1"></div>
    <div class="blob blob-2"></div>

    <div class="login-card">
        <div class="logo-wrap">
            <img src="<?= base_url('assets/dist/img/erdekatala.png') ?>" alt="Erdekatala">
        </div>

        <h2>Erdekatala</h2>
        <p class="tagline">Sistem Informasi Kependudukan Agregat<br>Kabupaten Tanah Laut</p>

        <?php if (session()->getFlashdata('error')): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= session()->getFlashdata('error') ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        <?php endif; ?>

        <form action="<?= base_url('proses_login') ?>" method="post">
            <?= csrf_field() ?>

            <div class="input-field mb-3">
                <i class="fas fa-user"></i>
                <input type="text" name="username" class="form-control" placeholder="Username"
                    required autofocus value="<?= esc(old('username')) ?>">
            </div>

            <div class="input-field mb-4">
                <i class="fas fa-lock"></i>
                <input type="password" name="password" class="form-control" placeholder="Password" required>
            </div>

            <div class="remember-row">
                <div class="icheck-danger">
                    <input type="checkbox" name="remember" id="remember" value="1">
                    <label for="remember">Ingat Saya</label>
                </div>
            </div>

            <button type="submit" class="btn btn-danger btn-block btn-masuk">Masuk</button>
        </form>

        <p class="instansi">
            DP3AP2KB Kabupaten Tanah Laut<br>
            Kalimantan Selatan
        </p>
    </div>

    <script src="<?= base_url('assets/plugins/jquery/jquery.min.js') ?>"></script>
    <script src="<?= base_url('assets/plugins/bootstrap/js/bootstrap.bundle.min.js') ?>"></script>
    <script src="<?= base_url('assets/dist/js/adminlte.min.js') ?>"></script>
</body>

</html>
```

- [ ] **Step 2: Pastikan tidak ada sisa markup lama**

Cek file: tidak boleh ada `login-left`, `login-right`, `cursor-follower`, `feature-list`, atau `login-form-wrap`.

- [ ] **Step 3: Verifikasi layout di browser (desktop)**

Jalankan app (Laragon/`php spark serve`), buka `/login` dengan browser. Periksa:
- Kartu putih terpusat (horizontal & vertikal) di atas gradient `#fdf3f2 → #fce8e5`.
- Logo, judul "Erdekatala", tagline, form, footer instansi tampil di dalam kartu.
- Dua lingkaran blur merah samar di pojok kiri-atas kanan; tidak menutupi konten.
- Ikon user/lock di dalam field, focus ring merah saat input diklik.
- Checkbox "Ingat Saya" merah (icheck-danger).

- [ ] **Step 4: Verifikasi flash error**

Login dengan username salah → halaman kembali, alert merah "error" tampil di atas form dan bisa ditutup (X).

- [ ] **Step 5: Verifikasi login sukses**

Login dengan akun seeder (`admin_dinas` / `password123`) → redirect ke `/dashboard`, session aktif.

- [ ] **Step 6: Verifikasi viewport mobile**

Set viewport ≤576px: kartu menyusut (padding 24px), tidak ada overflow horizontal, blob mengecil.

- [ ] **Step 7: Commit**

```bash
git add app/Views/auth/login.php
git commit -m "feat: redesign login page to minimalist centered card"
```

---
