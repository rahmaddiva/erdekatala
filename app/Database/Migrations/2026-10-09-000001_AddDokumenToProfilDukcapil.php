<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Kelompok A — dua indikator dokumen Dukcapil yang sebelumnya dibuang
 * (butuh kolom baru, di luar scope Opsi C):
 *   - `dok_kia`        : KARTU IDENTITAS ANAK (penduduk usia 0-16 th yang
 *                        memiliki KIA). Sumber sheet KEPEMILIKAN DOK.
 *   - `dok_akta_cerai` : AKTA PERCERAIAN. Sumber sheet KEPEMILIKAN DOK.
 *
 * Keduanya basis PENDUDUK (bukan KK). Default 0 supaya baris laporan desa
 * (`laporan_agregat`) yang tidak punya kolom ini tetap aman saat landing page
 * menggabungkan (SUM) profil_dukcapil + laporan_agregat.
 *
 * Hanya `profil_dukcapil` yang bertambah kolom; `laporan_agregat` tidak diubah
 * (kolom Dukcapil-only), sehingga tidak mengganggu form input laporan desa.
 */
class AddDokumenToProfilDukcapil extends Migration
{
    public function up()
    {
        $this->forge->addColumn('profil_dukcapil', [
            'dok_kia' => [
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 0,
                'after'      => 'pend_punya_akta_lahir',
            ],
            'dok_akta_cerai' => [
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 0,
                'after'      => 'dok_kia',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('profil_dukcapil', 'dok_akta_cerai');
        $this->forge->dropColumn('profil_dukcapil', 'dok_kia');
    }
}
