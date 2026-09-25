# erdekatala

## Deskripsi

**erdekatala** (Elektronik Rumah Data Kependudukan Tanah Laut) adalah sistem informasi kependudukan berbasis **CodeIgniter 4** untuk DP3AP2KB Kabupaten Tanah Laut, Kalimantan Selatan — pendataan penduduk agregat tingkat desa secara berkala, monitoring capaian pelaporan, statistik visual, dan keterbukaan data via halaman publik + Public API.
## Fitur Utama

- Struktur proyek CodeIgniter 4 siap pakai
- Contoh Controller, Model, View, dan konfigurasi umum
- Skrip migration dan seeder dasar
- Mudah dikonfigurasi melalui file `.env`

## Persyaratan

- PHP 8.1 atau lebih baru
- Ekstensi: `intl`, `mbstring`, `json`, `mysqlnd` (jika menggunakan MySQL), `curl`

## Instalasi

1. Clone repository:
   ```bash
   git clone <repo-url> erdekatala
   ```
2. Masuk ke folder proyek:
   ```bash
   cd erdekatala
   ```
3. Install dependensi:
   ```bash
   composer install
   ```
4. Salin file env dan sesuaikan:
   ```bash
   cp env .env
   ```
   Sesuaikan `app.baseURL` dan pengaturan database di `.env`.

## Menjalankan Aplikasi (Pengembangan)

- Menggunakan built-in server:
  ```bash
  php spark serve
  ```
  Akses http://localhost:8080 (atau `app.baseURL` yang diset).

## Konfigurasi Webserver

- Arahkan web server (Apache/Nginx) ke folder `public/` sebagai webroot untuk keamanan.

## Informasi Project

Project ini dibuat dan dikembangkan secara pribadi oleh:

| | |
|---|---|
| Nama | **rahmaddiva** |
| Email | mahendradiva64@gmail.com |
| Instansi | DP3AP2KB Kabupaten Tanah Laut, Kalimantan Selatan |
| Status | Active Development (2026) |

Dokumentasi lengkap konteks project (arsitektur, skema database, routes, konvensi kode) ada di [`AGENTS.md`](AGENTS.md); kebutuhan produk ada di [`PRD.md`](PRD.md).

## Kontribusi

- Laporkan bug dan fitur melalui *Issues*
- Kirimkan *Pull Request* dengan deskripsi perubahan dan pengujian singkat

## Lisensi

- Lisensi MIT — lihat file `LICENSE` untuk detail

## Kontak

- Untuk pertanyaan atau bantuan, buka issue di repository.

> Catatan: Pastikan PHP dan ekstensi terpasang sesuai persyaratan sebelum menjalankan aplikasi.

