<?php

namespace App\Libraries;

use App\Controllers\BkkbnMirror;

/**
 * Pembungkus tipis trait BkkbnMirror supaya bisa dipakai di luar Controller,
 * khususnya dari spark command (CLI) yang tidak punya konteks HTTP.
 *
 * Kenapa perlu class ini: method di trait sengaja `private` agar tidak bocor
 * sebagai API publik. Method private hanya bisa dipanggil dari dalam class
 * yang memakai trait, jadi di sini disediakan wrapper publik yang sempit —
 * bukan membuka seluruh trait.
 *
 * Pemakaian: (new BkkbnMirrorService())->fetchMonitoringKrs(2026, 6157)
 */
class BkkbnMirrorService
{
    use BkkbnMirror;

    /**
     * Daftar kecamatan BKKBN: nama tampilan => id BKKBN.
     * Diambil dari trait supaya tidak ada daftar kedua yang bisa basi.
     */
    public function kecamatanMap(): array
    {
        return $this->bkkbnKecamatanMap();
    }

    /** Monitoring VERVAL KRS. $idKecamatanBkkbn null = level kabupaten. */
    public function fetchMonitoringKrs(int $tahun = 2026, ?int $idKecamatanBkkbn = null): array
    {
        return $this->fetchMonitoringKrsInternal($tahun, $idKecamatanBkkbn);
    }

    /** Laporan YanKB. $reportName: Tabel1 | Tabel21. */
    public function fetchYankbReport(string $reportName, int $tahun = 2026, int $bulan = 8): array
    {
        return $this->fetchYankbReportInternal($reportName, $tahun, $bulan);
    }

    /** Matriks Rekap Bangga Kencana (Dallap) level kabupaten. */
    public function fetchDallapMatrix(int $tahun = 2026, int $bulan = 8): array
    {
        return $this->fetchDallapMatrixInternal($tahun, $bulan);
    }

    /**
     * Definisi laporan Dallap (reportName => ['label','qtyCol','category','monthly']).
     * Dipakai command warm-up untuk menghitung nama file cache tanpa
     * menduplikasi definisinya.
     */
    public static function dallapReportDefs(): array
    {
        return (new self())->dallapReports();
    }
}
