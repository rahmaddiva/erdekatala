<?php

/**
 * Mapping SENTRAL indikator peta tematik: label Indonesia -> field snake_case
 * yang tersedia pada properti FeatureCollection endpoint /api/v1/map/kecamatan
 * dan /api/v1/map/desa.
 *
 * Dipakai bersama oleh:
 *  - app/Views/public/landingpage.php (#map-kecamatan)
 *  - app/Views/public/kecamatan.php   (#map-desa)
 *
 * Konsumsi:
 *  - PHP (render dropdown):  $grup['group'] + $it['field'] / $it['label']
 *  - JS (lookup label):      hasil json_encode() array ini
 *
 * Field harus tersedia di properti FeatureCollection — dihasilkan oleh
 * LaporanAgregatModel::mapIndicatorFields() + turunan total_jiwa & total_kk.
 * Urutan array menentukan urutan optgroup pada pemilih indikator.
 */
return [
    [
        'group' => 'Jiwa & KK',
        'items' => [
            ['field' => 'total_jiwa', 'label' => 'Total Jiwa'],
            ['field' => 'total_kk',   'label' => 'Total KK'],
            ['field' => 'jiwa_l',     'label' => 'Jiwa Laki-laki'],
            ['field' => 'jiwa_p',     'label' => 'Jiwa Perempuan'],
            ['field' => 'kk_l',       'label' => 'KK Laki-laki'],
            ['field' => 'kk_p',       'label' => 'KK Perempuan'],
        ],
    ],
    [
        'group' => 'Kelompok Umur',
        'items' => [
            ['field' => 'jml_balita', 'label' => 'Balita'],
            ['field' => 'jml_remaja', 'label' => 'Remaja'],
            ['field' => 'jml_lansia', 'label' => 'Lansia'],
        ],
    ],
    [
        'group' => 'Pendidikan KK',
        'items' => [
            ['field' => 'kk_pend_tidak_sekolah', 'label' => 'Tidak Sekolah'],
            ['field' => 'kk_pend_sd',            'label' => 'SD'],
            ['field' => 'kk_pend_smp',           'label' => 'SMP'],
            ['field' => 'kk_pend_sma',           'label' => 'SMA'],
            ['field' => 'kk_pend_diploma',       'label' => 'Diploma'],
            ['field' => 'kk_pend_s1',            'label' => 'S1'],
            ['field' => 'kk_pend_s2_s3',         'label' => 'S2/S3'],
        ],
    ],
    [
        'group' => 'Pekerjaan KK',
        'items' => [
            ['field' => 'kk_ker_tani',        'label' => 'Petani'],
            ['field' => 'kk_ker_nelayan',     'label' => 'Nelayan'],
            ['field' => 'kk_ker_pns',         'label' => 'PNS'],
            ['field' => 'kk_ker_swasta',      'label' => 'Swasta'],
            ['field' => 'kk_ker_pedagang',    'label' => 'Pedagang'],
            ['field' => 'kk_ker_wiraswasta',  'label' => 'Wiraswasta'],
            ['field' => 'kk_ker_buruh',       'label' => 'Buruh'],
            ['field' => 'kk_ker_tidak_kerja', 'label' => 'Tidak Bekerja'],
        ],
    ],
    [
        'group' => 'Status Perkawinan',
        'items' => [
            ['field' => 'kk_kawin',        'label' => 'Kawin'],
            ['field' => 'kk_belum_kawin',  'label' => 'Belum Kawin'],
            ['field' => 'kk_cerai_hidup',  'label' => 'Cerai Hidup'],
            ['field' => 'kk_cerai_mati',   'label' => 'Cerai Mati'],
        ],
    ],
    [
        'group' => 'Dokumen Kependudukan',
        'items' => [
            ['field' => 'pend_wajib_ktp',              'label' => 'KTP-el'],
            ['field' => 'pend_punya_akta_lahir',       'label' => 'Akta Lahir'],
            ['field' => 'kk_punya_akta_nikah',         'label' => 'Akta Nikah'],
            ['field' => 'kk_punya_kartu_fisik',        'label' => 'KK Fisik'],
            ['field' => 'kk_belum_punya_kartu_fisik',  'label' => 'Belum Punya KK Fisik'],
        ],
    ],
    [
        'group' => 'JKN / BPJS',
        'items' => [
            ['field' => 'pend_bpjs',    'label' => 'Peserta BPJS/JKN'],
            ['field' => 'pend_non_bpjs','label' => 'Bukan Peserta BPJS'],
            ['field' => 'pus_jkn',      'label' => 'PUS Peserta JKN'],
            ['field' => 'pus_pbi',      'label' => 'PUS PBI'],
            ['field' => 'pus_non_pbi',  'label' => 'PUS Non-PBI'],
            ['field' => 'jkn',          'label' => 'Terdaftar JKN'],
            ['field' => 'non_jkn',      'label' => 'Tidak Punya JKN'],
        ],
    ],
    [
        'group' => 'KB & PUS',
        'items' => [
            ['field' => 'jml_pus',                            'label' => 'Jumlah PUS'],
            ['field' => 'kb_aktif',                           'label' => 'KB Aktif'],
            ['field' => 'jml_penggunaan_alat_kontrasepsi',    'label' => 'Penggunaan Alat Kontrasepsi'],
        ],
    ],
];
