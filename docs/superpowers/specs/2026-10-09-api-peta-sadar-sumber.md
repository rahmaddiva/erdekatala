# API Peta Sadar-Sumber — `GET /api/v1/map/kecamatan` & `/api/v1/map/desa`

Tanggal: 2026-10-09
File diubah: **hanya** `app/Controllers/ApiController.php` (tidak menyentuh `PublicController.php`, Views, Models, migration, seeder).
Tidak ada commit.

## Masalah (sebelum)

Landing page publik `/` sudah default **Dukcapil** (snapshot `profil_dukcapil` SM I/2026 — total **378.190 jiwa / 132.030 KK**), tetapi endpoint peta publik masih **selalu** berbasis laporan desa (`laporan_agregat`, total **375.583**). API publik jadi diam-diam tidak konsisten dengan halaman publik.

## Perubahan (sesudah)

Ditambahkan parameter **`?sumber=desa|dukcapil|campuran`** pada kedua endpoint peta, dengan **default `dukcapil`** (menyamai default landing page):

| `?sumber=` | Sumber baris |
|---|---|
| *(kosong)* / tak dikenal | `dukcapil` (DEFAULT) — `ProfilDukcapilModel::getMapDesa()` |
| `desa` | perilaku lama — `LaporanAgregatModel::getMapDesa()` |
| `dukcapil` | `ProfilDukcapilModel::getMapDesa()` |
| `campuran` | baseline Dukcapil, **ditimpa** baris laporan desa per-desa (kunci `kd_kecamatan.kd_kelurahan`) |

Untuk `mapKecamatan`, baris per-desa dari sumber aktif diagregasikan ke kecamatan (kunci `kd_kecamatan`), sehingga mode `campuran` tetap benar tanpa double-count — semantik ini sejalan dengan `PublicController::mapRowsPerDesa()` / `aggregateMapToKecamatan()` pada landing page (duplikasi kecil yang disengaja; tidak refactor lintas controller).

### Keputusan: nilai `?sumber=` tak dikenal
**Diperlakukan sebagai default (`dukcapil`)**, bukan 422. Alasan:
- Menyamai perilaku landing page (`PublicController` baris 120-125: nilai tak dikenal → default Dukcapil), sehingga API dan halaman konsisten.
- Tidak memecah klien lama hanya karena typo query param.
- Terlihat jelas di payload karena respons self-describing (`"sumber": "dukcapil"`).

### Respons self-describing
Foreign member di level atas FeatureCollection:
- `"sumber"` — mode aktif (`desa`|`dukcapil`|`campuran`)
- `"sumber_label"` — teks sumber:
  - dukcapil → `ProfilDukcapilModel::sumberLabelDkb()` = `* Sumber data adalah Data Konsolidasi Bersih (DKB) Semester I Tahun 2026`
  - desa → `Laporan desa (laporan_agregat, 1 baris terbaru per desa)`
  - campuran → `Gabungan: laporan desa bila ada, Dukcapil bila desa belum lapor`

### `info()` diperbarui
Kedua entri endpoint peta kini mendokumentasikan `?sumber=desa|dukcapil|campuran, default dukcapil`.

### Kompatibilitas
- Filter `?id_kecamatan=` pada `mapDesa` tetap berfungsi.
- Struktur FeatureCollection & nama properti per fitur **tidak berubah** (indikator tidak di-rename; 47 properti/fitur tetap).

## Helper lokal yang ditambahkan (di `ApiController`)
- `resolveSumber(): string`
- `sumberLabel(string): string`
- `mapRowsPerDesa(?int, string): array`
- `aggregateMapToKecamatan(array): array`
- Properti + instansiasi `ProfilDukcapilModel` di `initController()`.

## Verifikasi (bukti angka)

`php -l app/Controllers/ApiController.php` → **No syntax errors detected**.

Server: `php spark serve --host localhost --port 8091` (localhost). Respons disimpan di `scratch/apisrc/`; penjumlahan via skrip Python.

| Request | Σ `total_jiwa` (harapan) | Hasil | Fitur |
|---|---|---|---|
| `GET /api/v1/map/kecamatan` (default) | 378.190 | **378.190** ✅ | 11 |
| `GET /api/v1/map/kecamatan?sumber=desa` | 375.583 | **375.583** ✅ | 11 |
| `GET /api/v1/map/desa` (default) | 378.190 | **378.190** ✅ | 135 |
| `GET /api/v1/map/desa?id_kecamatan=10` | 20.774 | **20.774** ✅ | 9 |
| `GET /api/v1/map/desa?id_kecamatan=10&sumber=desa` | 20.676 | **20.676** ✅ | 9 |

Tambahan: `map/kecamatan?sumber=dukcapil` = 378.190; `map/kecamatan?sumber=campuran` = 375.583 (semua desa sudah lapor, jadi sama dengan laporan desa); `map/desa?sumber=bogus` = 378.190 dengan `sumber=dukcapil` (fallback default).

- `Content-Type: application/geo+json; charset=UTF-8` tetap pada ketiga request yang dicek. ✅
- Tidak ada `Fatal error`/`Warning`/`Notice` di payload tersimpan. ✅
- `php vendor/phpunit/phpunit/phpunit tests/unit` → **79 tests, 364 assertions, OK** (1 warning: no code coverage driver). ✅
- Server dimatikan setelah selesai (tidak ada listener di 8091). ✅

## Contoh payload (ringkas)

`GET /api/v1/map/kecamatan` (default, dukcapil) — kunci atas:
```json
{
  "type": "FeatureCollection",
  "features": [ { "type": "Feature", "properties": { "kd_kecamatan": "001", "nm_kecamatan": "...", "jiwa_l": 0, "...": 0, "total_jiwa": 0, "total_kk": 0 }, "geometry": { "...": "..." } }, "..." ],
  "sumber": "dukcapil",
  "sumber_label": "* Sumber data adalah Data Konsolidasi Bersih (DKB) Semester I Tahun 2026"
}
```

`GET /api/v1/map/desa?sumber=desa` — kunci atas:
```json
{
  "type": "FeatureCollection",
  "features": [ "..." ],
  "sumber": "desa",
  "sumber_label": "Laporan desa (laporan_agregat, 1 baris terbaru per desa)"
}
```

`GET /api/v1/map/kecamatan?sumber=campuran`:
```json
{ "type": "FeatureCollection", "features": [ "..." ],
  "sumber": "campuran",
  "sumber_label": "Gabungan: laporan desa bila ada, Dukcapil bila desa belum lapor" }
```
