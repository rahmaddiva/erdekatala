<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class DesaKoordinatSeeder extends Seeder
{
    // Koordinat centroid (perkiraan) per kecamatan Tanah Laut.
    // Bisa disesuaikan via form CRUD Desa di admin.
    private $centroid = [
        'Takisung'       => ['lat' => -3.8616, 'lng' => 114.6266],
        'Jorong'         => ['lat' => -3.9650, 'lng' => 114.7166],
        'Pelaihari'      => ['lat' => -3.8000, 'lng' => 114.7500],
        'Kurau'          => ['lat' => -3.7100, 'lng' => 114.6500],
        'Bati-Bati'      => ['lat' => -3.6500, 'lng' => 114.7800],
        'Panyipatan'     => ['lat' => -3.5800, 'lng' => 114.5600],
        'Kintap'         => ['lat' => -3.9600, 'lng' => 115.1300],
        'Tambang Ulang'  => ['lat' => -3.4600, 'lng' => 115.0500],
        'Batu Ampar'     => ['lat' => -3.6300, 'lng' => 114.9000],
        'Bajuin'         => ['lat' => -3.7000, 'lng' => 114.8500],
        'Bumi Makmur'    => ['lat' => -3.5000, 'lng' => 114.7000],
    ];

    public function run()
    {
        $db = \Config\Database::connect();

        foreach ($this->centroid as $namaKec => $c) {
            $kec = $db->table('kecamatan')
                ->where('nama_kecamatan', $namaKec)
                ->get()
                ->getRowArray();

            if (!$kec) {
                continue;
            }

            $desas = $db->table('m_desa')
                ->where('id_kecamatan', $kec['id_kecamatan'])
                ->orderBy('id_desa', 'ASC')
                ->get()
                ->getResultArray();

            $i = 0;
            foreach ($desas as $d) {
                // Offset kecil deterministik agar desa tidak bertumpuk di centroid.
                $offLat = (($i % 5) - 2) * 0.0015;
                $offLng = ((int) (($i % 7) / 2) - 1) * 0.0018;
                $i++;

                $db->table('m_desa')
                    ->where('id_desa', $d['id_desa'])
                    ->update([
                        'latitude'  => round($c['lat'] + $offLat, 7),
                        'longitude' => round($c['lng'] + $offLng, 7),
                    ]);
            }
        }
    }
}
