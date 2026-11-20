<?php

namespace App\Libraries;

/**
 * Katalog permission per fitur. Satu sumber kebenaran untuk:
 * - checkbox di halaman Manajemen Role,
 * - validasi input (hanya key yang dikenal yang disimpan),
 * - default permission role sistem.
 */
class Permissions
{
    public const GROUPS = [
        'Dashboard' => [
            'dashboard.view' => 'Lihat dashboard statistik',
        ],
        'Laporan Agregat' => [
            'laporan.view'      => 'Lihat daftar & detail laporan',
            'laporan.input'     => 'Input laporan baru',
            'laporan.edit'      => 'Ubah laporan',
            'laporan.delete'    => 'Hapus laporan',
            'laporan.export'    => 'Export PDF / Excel',
            'laporan.aktivitas' => 'Lihat aktivitas perubahan',
        ],
        'Data Tabel' => [
            'data_tabel.view' => 'Lihat data tabel',
        ],
        'Master Wilayah' => [
            'kecamatan.manage' => 'Kelola kecamatan',
            'desa.manage'      => 'Kelola desa',
            'dusun.manage'     => 'Kelola dusun',
            'rt.manage'        => 'Kelola RT',
        ],
        'Pengguna & Akses' => [
            'users.view'      => 'Lihat daftar pengguna',
            'users.manage'    => 'Tambah / ubah / hapus pengguna',
            'roles.manage'    => 'Kelola role & permission',
            'apikeys.manage'  => 'Kelola API key',
        ],
    ];

    /** @return list<string> */
    public static function all(): array
    {
        $keys = [];
        foreach (self::GROUPS as $items) {
            $keys = array_merge($keys, array_keys($items));
        }

        return $keys;
    }

    /** @return list<string> */
    public static function sanitize($input): array
    {
        if (!is_array($input)) {
            return [];
        }

        return array_values(array_intersect(self::all(), $input));
    }

    /** Permission default untuk tiga role sistem (perilaku lama dipertahankan). */
    public static function defaults(): array
    {
        return [
            'admin_dinas'     => self::all(),
            'admin_kecamatan' => [
                'dashboard.view', 'laporan.view', 'laporan.export',
                'laporan.aktivitas', 'data_tabel.view', 'users.view',
            ],
            'admin_desa' => [
                'dashboard.view', 'laporan.view', 'laporan.input',
                'laporan.edit', 'laporan.delete', 'laporan.export',
                'data_tabel.view',
            ],
        ];
    }
}
