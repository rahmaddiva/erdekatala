# Redesign Halaman Login Erdekatala

Tanggal: 2026-08-24
Status: Draft (menunggu review pengguna)

## Konteks

Halaman login saat ini (`app/Views/auth/login.php`) memakai layout split-screen kustom:
panel kiri merah gradient (55%) berisi logo, tagline, daftar fitur, dan footer instansi;
panel kanan navy gelap (45%) berisi form login. Ada efek cursor-follower. Warna merah
(`#c0392b`) adalah identitas sistem, tetapi tema utama aplikasi (AdminLTE
`sidebar-dark-primary`) adalah biru.

## Keputusan Arah (hasil brainstorming)

- **Desain ulang total** — layout baru, bukan polish layout lama.
- **Gaya: minimalis bersih** — kartu terpusat, banyak ruang kosong, aksen halus.
- **Warna: merah, pertahankan identitas** — `#c0392b` tetap.
- **Pendekatan terpilih (B)**: latar gradient merah sangat lembut + kartu putih.

## Konten Halaman

Dipertahankan:

- Logo `assets/dist/img/erdekatala.png` (ditampilkan natural di atas latar putih, tanpa filter invert).
- Nama sistem "Erdekatala".
- Tagline "Sistem Informasi Kependudukan Agregat Kabupaten Tanah Laut".
- Footer instansi "DP3AP2KB Kabupaten Tanah Laut • Kalimantan Selatan".

Dihapus:

- Daftar fitur (4 item) — tidak cocok dengan gaya minimalis.
- Efek cursor-follower (`.cursor-follower` + script) — gimmick, tidak selaras dengan kesan bersih.

## Struktur & Visual

- **Latar**: gradient merah sangat lembut `#fdf3f2 → #fce8e5`, tinggi viewport penuh, konten di tengah secara vertikal & horizontal (flexbox).
- **Dekorasi**: 2 lingkaran blur merah transparan di pojok (pseudo-element atau div absolut) untuk kedalaman; `pointer-events: none`.
- **Kartu**: putih, lebar maks ±400px, `border-radius: 12px`, shadow lembut (`0 10px 40px rgba(192,57,43,0.12)`), padding nyaman (±36px).
- **Di dalam kartu (atas → bawah)**:
  1. Logo (±90px, natural).
  2. Judul "Erdekatala" (`#2d2d2d`, tebal).
  3. Tagline singkat (abu `#6c757d`, ukuran kecil, `text-align: center`).
  4. Form.
  5. Footer instansi (abu sangat muda, ukuran lebih kecil).
- **Input**: background putih, border abu halus (`#d6d6d6`), `border-radius: 8px`, ikon FontAwesome di dalam field (kiri); fokus → border & ring merah `#c0392b` lembut.
- **Checkbox "Ingat Saya"**: tetap `icheck-danger` (plugin icheck-bootstrap sudah ada).
- **Tombol "Masuk"**: merah `#c0392b` full-width, `border-radius: 8px`, hover `#a93226`.
- **Flash error**: `alert alert-danger alert-dismissible` tetap di atas form (tidak berubah).
- **Font**: Source Sans Pro (tetap, sudah dimuat).

## Perilaku & Responsive

- Form: action `/proses_login`, method POST, `csrf_field()`, field `username`, `password`, checkbox `remember` — tidak berubah sama sekali. Backend tidak disentuh.
- Responsive: kartu `width: 100%` dengan `max-width: 400px` + padding; di layar kecil padding halaman & kartu menyusut; tetap satu kolom.
- Tidak ada JS baru selain penghapusan cursor-follower; jQuery/Bootstrap/AdminLTE script tetap dimuat untuk kebutuhan icheck & alert dismiss.

## Cakupan & Non-Goals

- File yang diubah: hanya `app/Views/auth/login.php` (CSS + markup + script di file yang sama, mengikuti pola existing).
- Non-goals: tidak mengubah controller, route, filter, model, asset, atau halaman lain. Tidak menambah fitur (mis. lupa password) — di luar scope.

## Verifikasi

- Buka `/login` di browser: kartu terpusat, latar gradient lembut, logo & form tampil benar.
- Login dengan akun seeder (`admin_dinas` / `password123`) berhasil masuk ke `/dashboard`.
- Flash error muncul saat password salah.
- Viewport mobile (≤576px): tidak ada overflow horizontal, elemen menyusut proporsional.
