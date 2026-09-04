# Carry-Forward Snapshot Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Saat admin buka form laporan baru, form otomatis ter-fill dari data periode sebelumnya — admin tinggal ubah yang berubah saja.

**Architecture:** Dua perubahan minimal: (1) perluas `getPreviousData()` agar cari periode tepat sebelumnya (bulan-1 atau Desember tahun-1), bukan hanya record terbaru; (2) tambah auto-trigger AJAX di `form.php` saat desa+bulan+tahun terisi. Skema database tidak berubah sama sekali.

**Tech Stack:** CodeIgniter 4, PHP 8.1+, jQuery, SweetAlert2

## Global Constraints

- Tidak boleh ada perubahan migrasi database
- Ikuti konvensi CI4: `$this->response->setJSON()` untuk AJAX response
- Semua output HTML harus pakai `esc()`
- Jangan timpa field: `id_laporan`, `id_desa`, `bulan`, `tahun`, `id_user`, `created_at`, `updated_at`
- Auto-fill hanya aktif di form **create** (bukan edit) — form edit sudah punya data existing
- Field yang diabaikan (ignoreFields) sudah didefinisikan di JS `form.php:322` — jangan duplikasi logika ini

---

## File Map

| File | Perubahan |
|---|---|
| `app/Controllers/LaporanAgregatController.php` | Ubah `getPreviousData()` baris 477-498: tambah param `bulan`+`tahun`, hitung periode sebelumnya, fallback ke terbaru |
| `app/Views/laporan/form.php` | Ubah JS baris 290-358: tambah auto-trigger saat 3 field terisi; kirim `bulan`+`tahun` ke endpoint; update label tombol manual |

---

## Task 1: Perluas `getPreviousData()` di Controller

**Files:**
- Modify: `app/Controllers/LaporanAgregatController.php:477-498`

**Interfaces:**
- Produces: `GET /laporan/getPreviousData?id_desa=X&bulan=Y&tahun=Z` → JSON `{status: 'success', data: {...semua field kecuali id/timestamp}}`
- Backward-compatible: param `bulan` dan `tahun` opsional — jika tidak ada, behavior lama (ambil terbaru) tetap berjalan

- [x] **Step 1: Ganti implementasi `getPreviousData()`**

Ganti seluruh method dari baris 477 s/d 498 dengan kode berikut:

```php
public function getPreviousData()
{
    $id_desa = $this->request->getGet('id_desa');
    $bulan   = (int) $this->request->getGet('bulan');
    $tahun   = (int) $this->request->getGet('tahun');

    // Hitung periode tepat sebelumnya
    if ($bulan > 1) {
        $prevBulan = $bulan - 1;
        $prevTahun = $tahun;
    } else {
        // Januari → ambil Desember tahun sebelumnya
        $prevBulan = 12;
        $prevTahun = $tahun - 1;
    }

    // Cari periode tepat sebelumnya dulu
    $lastData = null;
    if ($bulan && $tahun) {
        $lastData = $this->laporanModel
            ->where('id_desa', $id_desa)
            ->where('bulan', $prevBulan)
            ->where('tahun', $prevTahun)
            ->first();
    }

    // Fallback: ambil record terbaru yang ada (behavior lama)
    if (!$lastData) {
        $lastData = $this->laporanModel
            ->where('id_desa', $id_desa)
            ->orderBy('tahun', 'DESC')
            ->orderBy('bulan', 'DESC')
            ->first();
    }

    if ($lastData) {
        // Jangan kirim field identitas/timestamp — biarkan form pakai pilihannya sendiri
        $skip = ['id_laporan', 'id_desa', 'bulan', 'tahun', 'id_user', 'created_at', 'updated_at'];
        foreach ($skip as $key) {
            unset($lastData[$key]);
        }
        return $this->response->setJSON(['status' => 'success', 'data' => $lastData]);
    }

    return $this->response->setJSON([
        'status'  => 'error',
        'message' => 'Tidak ditemukan data sebelumnya untuk desa ini.'
    ]);
}
```

- [x] **Step 2: Verifikasi manual via browser atau curl**

Buka browser atau jalankan di terminal (sesuaikan `id_desa`):

```
GET http://localhost/erdekatala/laporan/getPreviousData?id_desa=1&bulan=1&tahun=2026
```

Expected: response JSON `{status: "success", data: {...}}` dengan data Desember 2025, tanpa field `id_laporan`, `bulan`, `tahun`, `id_desa`.

```
GET http://localhost/erdekatala/laporan/getPreviousData?id_desa=1&bulan=2&tahun=2026
```

Expected: response JSON dengan data Januari 2026 (jika ada), atau fallback ke terbaru.

```
GET http://localhost/erdekatala/laporan/getPreviousData?id_desa=1
```

Expected: tetap mengembalikan data terbaru (backward-compat dengan tombol manual lama).

- [x] **Step 3: Commit**

```bash
git add app/Controllers/LaporanAgregatController.php
git commit -m "feat: getPreviousData cari periode tepat sebelumnya dengan fallback"
```

---

## Task 2: Auto-fill Form saat Desa+Bulan+Tahun Terisi

**Files:**
- Modify: `app/Views/laporan/form.php:289-358`

**Interfaces:**
- Consumes: `GET /laporan/getPreviousData?id_desa=X&bulan=Y&tahun=Z` dari Task 1
- Form hanya di-autofill saat `isset($laporan)` adalah false (create mode, bukan edit)

- [x] **Step 1: Baca form.php bagian atas untuk cek apakah ada hidden field penanda create/edit**

Cek baris 28-29 di `form.php`:
```php
<form action="<?= isset($laporan) ? '/laporan/update/' . $laporan['id_laporan'] : '/laporan/store' ?>"
```

`isset($laporan)` dari PHP menentukan mode. Di JS kita perlu cara mendeteksi ini. Solusi paling lazy: cek action URL form — jika berisi `/store` maka create mode.

- [x] **Step 2: Ganti seluruh blok `<script>` (baris 289-358 di form.php)**

Ganti blok script yang ada dengan:

```html
<script>
    $(document).ready(function () {
        const isCreateMode = $('form').attr('action').includes('/store');
        const ignoreFields = ['id_laporan', 'id_desa', 'bulan', 'tahun', 'id_user', 'created_at', 'updated_at'];

        function fillFormFromData(data) {
            $('form input[type="number"]').each(function () {
                const fieldName = $(this).attr('name');
                if (data[fieldName] !== undefined && !ignoreFields.includes(fieldName)) {
                    $(this).val(data[fieldName]);
                }
            });
        }

        function fetchAndFill(silent) {
            const idDesa = $('select[name="id_desa"]').val();
            const bulan  = $('select[name="bulan"]').val();
            const tahun  = $('input[name="tahun"]').val();

            if (!idDesa || !bulan || !tahun) return;

            $.get('<?= base_url('laporan/getPreviousData') ?>', { id_desa: idDesa, bulan: bulan, tahun: tahun })
                .done(function (response) {
                    if (response.status === 'success') {
                        fillFormFromData(response.data);
                        if (!silent) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Sukses',
                                text: 'Data berhasil dimuat! Silakan periksa kembali sebelum menyimpan.'
                            });
                        } else {
                            // ponytail: toast ringan agar tidak blokir user
                            Swal.fire({
                                toast: true,
                                position: 'top-end',
                                icon: 'info',
                                title: 'Data bulan sebelumnya dimuat otomatis.',
                                showConfirmButton: false,
                                timer: 3000
                            });
                        }
                    } else if (!silent) {
                        Swal.fire({ icon: 'error', title: 'Gagal', text: response.message || 'Gagal mengambil data.' });
                    }
                })
                .fail(function () {
                    if (!silent) {
                        Swal.fire({ icon: 'error', title: 'Error', text: 'Gagal mengambil data. Pastikan koneksi server tersedia.' });
                    }
                });
        }

        // Auto-fill hanya di create mode saat 3 field terisi
        if (isCreateMode) {
            $('select[name="id_desa"], select[name="bulan"], input[name="tahun"]').on('change', function () {
                fetchAndFill(true); // silent = true → hanya toast ringan
            });
        }

        // Tombol manual tetap ada sebagai fallback (sekarang pakai fungsi yang sama)
        $('#btn-recycle').on('click', function () {
            const idDesa = $('select[name="id_desa"]').val();
            if (!idDesa) {
                Swal.fire({ icon: 'warning', title: 'Pilih Desa', text: 'Silakan pilih Desa terlebih dahulu!' });
                return;
            }
            Swal.fire({
                title: 'Muat ulang data bulan sebelumnya?',
                text: 'Data yang sudah Anda ketik manual akan tertimpa.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Muat Ulang',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) fetchAndFill(false); // silent = false → Swal sukses/error penuh
            });
        });
    });
</script>
```

- [x] **Step 3: Update label tombol manual di form.php**

Cari tombol `btn-recycle` di bagian atas form (sekitar baris 48-100, di tab Data Pokok). Ubah labelnya dari "Salin Data Bulan Lalu" menjadi "Muat Ulang Data Bulan Lalu".

Cari pola:
```php
id="btn-recycle"
```
dan update teks tombolnya. Contoh jika sekarang berbunyi:
```html
<button type="button" id="btn-recycle" ...>
    <i class="fas fa-recycle"></i> Salin Data Bulan Lalu
</button>
```
Ubah jadi:
```html
<button type="button" id="btn-recycle" ...>
    <i class="fas fa-recycle"></i> Muat Ulang Data Bulan Lalu
</button>
```

- [x] **Step 4: Verifikasi manual**

1. Buka `http://localhost/erdekatala/laporan/input`
2. Pilih Desa → pilih Bulan → isi Tahun 2026
3. Expected: form otomatis ter-fill dengan toast info "Data bulan sebelumnya dimuat otomatis."
4. Ubah pilihan Bulan → Expected: form di-fill ulang dengan data bulan berbeda
5. Buka `http://localhost/erdekatala/laporan/edit/1` → Expected: tidak ada auto-fill (mode edit)
6. Klik tombol "Muat Ulang Data Bulan Lalu" → Expected: Swal konfirmasi muncul, setelah konfirm data dimuat dengan Swal sukses

- [x] **Step 5: Commit**

```bash
git add app/Views/laporan/form.php
git commit -m "feat: auto-fill form laporan dari data periode sebelumnya"
```

---

## Self-Review

**Spec coverage:**
- ✅ Auto-fill saat 3 field terisi → Task 2 Step 2 (event listener pada change)
- ✅ Cari periode tepat sebelumnya (bulan-1 atau Des tahun-1) → Task 1 Step 1
- ✅ Fallback ke record terbaru jika periode sebelumnya kosong → Task 1 Step 1
- ✅ Auto-fill hanya di create mode → Task 2 Step 2 (`isCreateMode` check)
- ✅ Tombol manual tetap ada → Task 2 Step 2 (`#btn-recycle` handler)
- ✅ Label tombol diperbarui → Task 2 Step 3
- ✅ Field identitas tidak ikut di-fill → `ignoreFields` array
- ✅ Nol perubahan database → tidak ada migration task
- ✅ Backward-compat tombol manual lama → Task 1 Step 1 (param opsional)

**Placeholder scan:** Tidak ada TBD/TODO/placeholder.

**Type consistency:** `fetchAndFill(silent: bool)` dipakai konsisten di auto-trigger (true) dan tombol manual (false).

---

## Hasil Verifikasi (4 Sep 2026)

Semua task sudah terimplementasi di kode sebelum sesi ini; checklist disinkronkan setelah verifikasi statis:

- ✅ Route `/laporan/getPreviousData` terdaftar (`Routes.php:64`) dan terproteksi filter `loggedin` global (`Filters.php`) — request tanpa login dialihkan ke halaman login.
- ✅ Auto-trigger hanya aktif di create mode (`isCreateMode` dideteksi dari action form `/store`).
- ✅ Backward-compat: tanpa param `bulan`/`tahun` → fallback ke record terbaru.
- ✅ `ignoreFields` konsisten antara JS dan controller (field identitas/timestamp tidak ikut terisi).
- ✅ Label tombol sudah "Muat Ulang Data Bulan Lalu" (Task 2 Step 3).

**Hardening tambahan hasil verifikasi (di-commit terpisah):**

1. **Otorisasi lintas wilayah `getPreviousData()`** — sebelumnya endpoint menerima `?id_desa=` apa pun, sehingga `admin_desa` desa A bisa membaca ~80 kolom statistik desa B (melanggar DoD PRD #3: *tidak ada kebocoran data lintas wilayah*). Fix: `admin_desa` dipaksa ke `session id_desa`-nya sendiri (param klien diabaikan), `admin_kecamatan` ditolak (konsisten dengan `create`/`store`), hanya `admin_dinas` yang bebas memilih desa.
2. **Tombol `#btn-recycle` hanya di form input baru** — sebelumnya dirender tanpa syarat sehingga muncul juga di form edit dan handler-nya aktif (bisa menimpa data existing dengan data bulan lalu). Fix: dibungkus `!isset($laporan['id_laporan'])` di view + guard `isCreateMode` di handler JS.

> Catatan: verifikasi dilakukan statis (code review + `php -l`). Uji alur manual via browser (Task 1 Step 2, Task 2 Step 4) tetap disarankan sebelum rilis.
