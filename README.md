# erdekatala

## Deskripsi

**erdekatala** adalah aplikasi berbasis **CodeIgniter 4** yang disiapkan untuk pengembangan lokal sistem informasi pendataan statistik pada DP3AP2KB, baik dalam lingkup kabupaten Tanah Laut, Kecamatan bahkan sampai Desa, yang tujuannya untuk pendataan penduduk secara berkala pada wilayah kabupaten Tanah Laut
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

## Kontribusi

- Laporkan bug dan fitur melalui *Issues*
- Kirimkan *Pull Request* dengan deskripsi perubahan dan pengujian singkat

## Lisensi

- Lisensi MIT — lihat file `LICENSE` untuk detail

## Kontak

- Untuk pertanyaan atau bantuan, buka issue di repository.

> Catatan: Pastikan PHP dan ekstensi terpasang sesuai persyaratan sebelum menjalankan aplikasi.

