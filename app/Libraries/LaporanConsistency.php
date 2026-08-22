<?php

namespace App\Libraries;

/**
 * Rule engine validasi konsistensi laporan agregat.
 * Fungsi murni: input array data POST, output daftar error/warning.
 * Spec: docs/superpowers/specs/2026-08-22-validasi-konsistensi-dan-bruteforce-design.md
 */
class LaporanConsistency
{
    /** Field statistik yang divalidasi C1 (semua allowedFields kecuali non-statistik). */
    public function statisticFields(): array
    {
        return [
            'jiwa_l', 'jiwa_p', 'kk_l', 'kk_p',
            // Pendidikan KK
            'kk_pend_tidak_sekolah', 'kk_pend_sd', 'kk_pend_smp', 'kk_pend_sma',
            'kk_pend_diploma', 'kk_pend_s1', 'kk_pend_s2_s3',
            // Pekerjaan KK
            'kk_ker_tani', 'kk_ker_nelayan', 'kk_ker_pns', 'kk_ker_swasta',
            'kk_ker_pedagang', 'kk_ker_wiraswasta', 'kk_ker_buruh', 'kk_ker_tidak_kerja',
            // Piramida penduduk
            'u0_4_l', 'u0_4_p', 'u5_9_l', 'u5_9_p', 'u10_14_l', 'u10_14_p',
            'u15_19_l', 'u15_19_p', 'u20_24_l', 'u20_24_p', 'u25_29_l', 'u25_29_p',
            'u30_34_l', 'u30_34_p', 'u35_39_l', 'u35_39_p', 'u40_44_l', 'u40_44_p',
            'u45_49_l', 'u45_49_p', 'u50_54_l', 'u50_54_p', 'u55_59_l', 'u55_59_p',
            'u60_64_l', 'u60_64_p', 'u65_69_l', 'u65_69_p', 'u70_74_l', 'u70_74_p',
            'u75_79_l', 'u75_79_p', 'u80_84_l', 'u80_84_p', 'u85_atas_l', 'u85_atas_p',
            // Kelompok khusus
            'jml_balita', 'jml_remaja', 'jml_lansia',
            // Status kawin
            'kk_kawin', 'kk_belum_kawin', 'kk_cerai_hidup', 'kk_cerai_mati',
            // Dokumen & kesehatan
            'pend_bpjs', 'pend_non_bpjs', 'kk_punya_kartu_fisik', 'kk_belum_punya_kartu_fisik',
            'penduduk_hub_kepala', 'penduduk_hub_istri', 'penduduk_hub_anak', 'penduduk_hub_lainnya',
            'pus_jkn', 'pus_pbi', 'pus_non_pbi', 'jkn', 'non_jkn',
            'pend_wajib_ktp', 'kk_punya_akta_nikah', 'pend_punya_akta_lahir',
            // KB & PUS
            'kb_aktif', 'jml_pus', 'jml_penggunaan_alat_kontrasepsi',
        ];
    }

    /**
     * Validasi dasar (C1): semua field statistik bilangan bulat >= 0.
     * Nilai kosong dianggap 0. Return array pesan error (kosong = lolos).
     */
    public function validateBasic(array $data): array
    {
        $errors = [];
        foreach ($this->statisticFields() as $field) {
            $value = $data[$field] ?? '';
            if ($value === '' || $value === null) {
                continue; // dianggap 0
            }
            if (!is_numeric($value) || (int) $value != $value + 0 || (int) $value < 0) {
                $errors[] = "Field {$field} harus berupa bilangan bulat tidak negatif.";
            }
        }
        return $errors;
    }

    /**
     * Pemeriksaan konsistensi (A1-A10, B1-B4, C2).
     * Return array ['code' => string, 'message' => string].
     */
    public function check(array $data): array
    {
        $n = function (array $data, string $field): int {
            $v = $data[$field] ?? 0;
            return is_numeric($v) ? (int) $v : 0;
        };
        $sum = function (array $fields) use ($data, $n): int {
            $total = 0;
            foreach ($fields as $f) {
                $total += $n($data, $f);
            }
            return $total;
        };

        $warnings = [];

        $totalKK   = $n($data, 'kk_l') + $n($data, 'kk_p');
        $totalJiwa = $n($data, 'jiwa_l') + $n($data, 'jiwa_p');

        $piramidaL = ['u0_4_l','u5_9_l','u10_14_l','u15_19_l','u20_24_l','u25_29_l','u30_34_l','u35_39_l','u40_44_l','u45_49_l','u50_54_l','u55_59_l','u60_64_l','u65_69_l','u70_74_l','u75_79_l','u80_84_l','u85_atas_l'];
        $piramidaP = ['u0_4_p','u5_9_p','u10_14_p','u15_19_p','u20_24_p','u25_29_p','u30_34_p','u35_39_p','u40_44_p','u45_49_p','u50_54_p','u55_59_p','u60_64_p','u65_69_p','u70_74_p','u75_79_p','u80_84_p','u85_atas_p'];

        $identityRules = [
            ['A1', 'Total KK', $totalKK, 'Jumlah pendidikan KK', $sum([
                'kk_pend_tidak_sekolah','kk_pend_sd','kk_pend_smp','kk_pend_sma',
                'kk_pend_diploma','kk_pend_s1','kk_pend_s2_s3'])],
            ['A2', 'Total KK', $totalKK, 'Jumlah pekerjaan KK', $sum([
                'kk_ker_tani','kk_ker_nelayan','kk_ker_pns','kk_ker_swasta',
                'kk_ker_pedagang','kk_ker_wiraswasta','kk_ker_buruh','kk_ker_tidak_kerja'])],
            ['A3', 'Total KK', $totalKK, 'Jumlah status kawin KK', $sum([
                'kk_belum_kawin','kk_kawin','kk_cerai_hidup','kk_cerai_mati'])],
            ['A4', 'Total KK', $totalKK, 'KK fisik + belum fisik',
                $n($data, 'kk_punya_kartu_fisik') + $n($data, 'kk_belum_punya_kartu_fisik')],
            ['A5', 'Total Jiwa L', $n($data, 'jiwa_l'), 'Piramida umur Laki-laki', $sum($piramidaL)],
            ['A6', 'Total Jiwa P', $n($data, 'jiwa_p'), 'Piramida umur Perempuan', $sum($piramidaP)],
            ['A7', 'Total Jiwa', $totalJiwa, 'Hubungan kepala keluarga', $sum([
                'penduduk_hub_kepala','penduduk_hub_istri','penduduk_hub_anak','penduduk_hub_lainnya'])],
            ['A8', 'Total Jiwa', $totalJiwa, 'JKN + non-JKN', $n($data, 'jkn') + $n($data, 'non_jkn')],
            ['A9', 'PUS punya JKN', $n($data, 'pus_jkn'), 'PUS PBI + non-PBI',
                $n($data, 'pus_pbi') + $n($data, 'pus_non_pbi')],
            ['A10', 'Jumlah balita', $n($data, 'jml_balita'), 'Umur 0-4 (L+P)',
                $n($data, 'u0_4_l') + $n($data, 'u0_4_p')],
        ];

        foreach ($identityRules as [$code, $leftLabel, $left, $rightLabel, $right]) {
            $diff = abs($left - $right);
            $tol  = max(2, (int) floor(0.01 * max($left, $right)));
            if ($diff > $tol) {
                $warnings[] = [
                    'code'    => $code,
                    'message' => "{$leftLabel} ({$left}) tidak sesuai {$rightLabel} ({$right}) — selisih {$diff}.",
                ];
            }
        }

        $exceedRules = [
            ['B1', 'Peserta KB aktif', $n($data, 'kb_aktif'), 'Jumlah PUS', $n($data, 'jml_pus')],
            ['B2', 'Penggunaan alat kontrasepsi', $n($data, 'jml_penggunaan_alat_kontrasepsi'), 'Jumlah PUS', $n($data, 'jml_pus')],
            ['B3', 'Wajib KTP', $n($data, 'pend_wajib_ktp'), 'Total jiwa', $totalJiwa],
            ['B4', 'Punya akta lahir', $n($data, 'pend_punya_akta_lahir'), 'Total jiwa', $totalJiwa],
        ];

        foreach ($exceedRules as [$code, $leftLabel, $left, $rightLabel, $right]) {
            if ($left > $right) {
                $warnings[] = [
                    'code'    => $code,
                    'message' => "{$leftLabel} ({$left}) melebihi {$rightLabel} ({$right}).",
                ];
            }
        }

        if ($totalJiwa === 0) {
            $warnings[] = [
                'code'    => 'C2',
                'message' => 'Seluruh data jiwa bernilai 0 — pastikan ini bukan kesalahan input.',
            ];
        }

        return $warnings;
    }
}
