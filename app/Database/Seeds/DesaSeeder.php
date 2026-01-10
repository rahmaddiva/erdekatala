<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class DesaSeeder extends Seeder
{
    public function run()
    {
        $data = [
            // KECAMATAN TAKISUNG (63.01.01) - 12 Desa
            ['nama_desa' => 'Batilai', 'kode_desa' => '63.01.01.2001'],
            ['nama_desa' => 'Benua Lawas', 'kode_desa' => '63.01.01.2002'],
            ['nama_desa' => 'Benua Tengah', 'kode_desa' => '63.01.01.2003'],
            ['nama_desa' => 'Gunung Makmur', 'kode_desa' => '63.01.01.2004'],
            ['nama_desa' => 'Kuala Tambangan', 'kode_desa' => '63.01.01.2005'],
            ['nama_desa' => 'Pagatan Besar', 'kode_desa' => '63.01.01.2006'],
            ['nama_desa' => 'Ranggang', 'kode_desa' => '63.01.01.2007'],
            ['nama_desa' => 'Ranggang Dalam', 'kode_desa' => '63.01.01.2008'],
            ['nama_desa' => 'Sumber Makmur', 'kode_desa' => '63.01.01.2009'],
            ['nama_desa' => 'Tabanio', 'kode_desa' => '63.01.01.2010'],
            ['nama_desa' => 'Takisung', 'kode_desa' => '63.01.01.2011'],
            ['nama_desa' => 'Telaga Langsat', 'kode_desa' => '63.01.01.2012'],

            // KECAMATAN JORONG (63.01.02) - 11 Desa
            ['nama_desa' => 'Alur', 'kode_desa' => '63.01.02.2001'],
            ['nama_desa' => 'Asam Asam', 'kode_desa' => '63.01.02.2002'],
            ['nama_desa' => 'Asam Jaya', 'kode_desa' => '63.01.02.2003'],
            ['nama_desa' => 'Asri Mulya', 'kode_desa' => '63.01.02.2004'],
            ['nama_desa' => 'Batalang', 'kode_desa' => '63.01.02.2005'],
            ['nama_desa' => 'Jorong', 'kode_desa' => '63.01.02.2006'],
            ['nama_desa' => 'Karang Rejo', 'kode_desa' => '63.01.02.2007'],
            ['nama_desa' => 'Muara Asam Asam', 'kode_desa' => '63.01.02.2008'],
            ['nama_desa' => 'Sabuhur', 'kode_desa' => '63.01.02.2009'],
            ['nama_desa' => 'Simpang Empat Sungai Baru', 'kode_desa' => '63.01.02.2010'],
            ['nama_desa' => 'Swarangan', 'kode_desa' => '63.01.02.2011'],

            // KECAMATAN PELAIHARI (63.01.03) - 5 Kelurahan, 15 Desa
            ['nama_desa' => 'Angsau', 'kode_desa' => '63.01.03.1001'], // Kelurahan
            ['nama_desa' => 'Karang Taruna', 'kode_desa' => '63.01.03.1002'], // Kelurahan
            ['nama_desa' => 'Pabahanan', 'kode_desa' => '63.01.03.1003'], // Kelurahan
            ['nama_desa' => 'Pelaihari', 'kode_desa' => '63.01.03.1004'], // Kelurahan
            ['nama_desa' => 'Sarang Halang', 'kode_desa' => '63.01.03.1005'], // Kelurahan
            ['nama_desa' => 'Ambungan', 'kode_desa' => '63.01.03.2006'],
            ['nama_desa' => 'Atu-Atu', 'kode_desa' => '63.01.03.2007'],
            ['nama_desa' => 'Bumi Jaya', 'kode_desa' => '63.01.03.2008'],
            ['nama_desa' => 'Guntung Besar', 'kode_desa' => '63.01.03.2009'],
            ['nama_desa' => 'Kampung Baru', 'kode_desa' => '63.01.03.2010'],
            ['nama_desa' => 'Panggung', 'kode_desa' => '63.01.03.2011'],
            ['nama_desa' => 'Panggung Baru', 'kode_desa' => '63.01.03.2012'],
            ['nama_desa' => 'Panjaratan', 'kode_desa' => '63.01.03.2013'],
            ['nama_desa' => 'Pemuda', 'kode_desa' => '63.01.03.2014'],
            ['nama_desa' => 'Sumber Mulia', 'kode_desa' => '63.01.03.2015'],
            ['nama_desa' => 'Sungai Riam', 'kode_desa' => '63.01.03.2016'],
            ['nama_desa' => 'Tampang', 'kode_desa' => '63.01.03.2017'],
            ['nama_desa' => 'Telaga', 'kode_desa' => '63.01.03.2018'],
            ['nama_desa' => 'Tungkaran', 'kode_desa' => '63.01.03.2019'],
            ['nama_desa' => 'Ujung Batu', 'kode_desa' => '63.01.03.2020'],

            // KECAMATAN KURAU (63.01.04) - 11 Desa
            ['nama_desa' => 'Bawah Layung', 'kode_desa' => '63.01.04.2001'],
            ['nama_desa' => 'Handil Negara', 'kode_desa' => '63.01.04.2002'],
            ['nama_desa' => 'Kali Besar', 'kode_desa' => '63.01.04.2003'],
            ['nama_desa' => 'Kurau', 'kode_desa' => '63.01.04.2004'],
            ['nama_desa' => 'Maluka Baulin', 'kode_desa' => '63.01.04.2005'],
            ['nama_desa' => 'Padang Luas', 'kode_desa' => '63.01.04.2006'],
            ['nama_desa' => 'Raden', 'kode_desa' => '63.01.04.2007'],
            ['nama_desa' => 'Sarikandi', 'kode_desa' => '63.01.04.2008'],
            ['nama_desa' => 'Sungai Bakau', 'kode_desa' => '63.01.04.2009'],
            ['nama_desa' => 'Tambak Karya', 'kode_desa' => '63.01.04.2010'],
            ['nama_desa' => 'Tambak Sarinah', 'kode_desa' => '63.01.04.2011'],

            // KECAMATAN BATI-BATI (63.01.05) - 14 Desa
            ['nama_desa' => 'Banyu Irang', 'kode_desa' => '63.01.05.2001'],
            ['nama_desa' => 'Bati-Bati', 'kode_desa' => '63.01.05.2002'],
            ['nama_desa' => 'Bentok Darat', 'kode_desa' => '63.01.05.2003'],
            ['nama_desa' => 'Bentok Kampung', 'kode_desa' => '63.01.05.2004'],
            ['nama_desa' => 'Benua Raya', 'kode_desa' => '63.01.05.2005'],
            ['nama_desa' => 'Kait-Kait', 'kode_desa' => '63.01.05.2006'],
            ['nama_desa' => 'Kait-Kait Baru', 'kode_desa' => '63.01.05.2007'],
            ['nama_desa' => 'Liang Anggang', 'kode_desa' => '63.01.05.2008'],
            ['nama_desa' => 'Nusa Indah', 'kode_desa' => '63.01.05.2009'],
            ['nama_desa' => 'Padang', 'kode_desa' => '63.01.05.2010'],
            ['nama_desa' => 'Pandahan', 'kode_desa' => '63.01.05.2011'],
            ['nama_desa' => 'Sambangan', 'kode_desa' => '63.01.05.2012'],
            ['nama_desa' => 'Ujung', 'kode_desa' => '63.01.05.2013'],
            ['nama_desa' => 'Ujung Baru', 'kode_desa' => '63.01.05.2014'],

            // KECAMATAN PANYIPATAN (63.01.06) - 10 Desa
            ['nama_desa' => 'Batakan', 'kode_desa' => '63.01.06.2001'],
            ['nama_desa' => 'Batu Mulya', 'kode_desa' => '63.01.06.2002'],
            ['nama_desa' => 'Batu Tungku', 'kode_desa' => '63.01.06.2003'],
            ['nama_desa' => 'Bumi Asih', 'kode_desa' => '63.01.06.2004'],
            ['nama_desa' => 'Kandangan Baru', 'kode_desa' => '63.01.06.2005'],
            ['nama_desa' => 'Kandangan Lama', 'kode_desa' => '63.01.06.2006'],
            ['nama_desa' => 'Kuringkit', 'kode_desa' => '63.01.06.2007'],
            ['nama_desa' => 'Panyipatan', 'kode_desa' => '63.01.06.2008'],
            ['nama_desa' => 'Suka Ramah', 'kode_desa' => '63.01.06.2009'],
            ['nama_desa' => 'Tanjung Dewa', 'kode_desa' => '63.01.06.2010'],

            // KECAMATAN KINTAP (63.01.07) - 14 Desa
            ['nama_desa' => 'Bukit Mulia', 'kode_desa' => '63.01.07.2001'],
            ['nama_desa' => 'Kebun Raya', 'kode_desa' => '63.01.07.2002'],
            ['nama_desa' => 'Kintap', 'kode_desa' => '63.01.07.2003'],
            ['nama_desa' => 'Kintap Kecil', 'kode_desa' => '63.01.07.2004'],
            ['nama_desa' => 'Kintapura', 'kode_desa' => '63.01.07.2005'],
            ['nama_desa' => 'Mekar Sari', 'kode_desa' => '63.01.07.2006'],
            ['nama_desa' => 'Muara Kintap', 'kode_desa' => '63.01.07.2007'],
            ['nama_desa' => 'Pandan Sari', 'kode_desa' => '63.01.07.2008'],
            ['nama_desa' => 'Pasir Putih', 'kode_desa' => '63.01.07.2009'],
            ['nama_desa' => 'Riam Adungan', 'kode_desa' => '63.01.07.2010'],
            ['nama_desa' => 'Salaman', 'kode_desa' => '63.01.07.2011'],
            ['nama_desa' => 'Sebamban Baru', 'kode_desa' => '63.01.07.2012'],
            ['nama_desa' => 'Sumber Jaya', 'kode_desa' => '63.01.07.2013'],
            ['nama_desa' => 'Sungai Cuka', 'kode_desa' => '63.01.07.2014'],

            // KECAMATAN TAMBANG ULANG (63.01.08) - 9 Desa
            ['nama_desa' => 'Bingkulu', 'kode_desa' => '63.01.08.2001'],
            ['nama_desa' => 'Gunung Raja', 'kode_desa' => '63.01.08.2002'],
            ['nama_desa' => 'Kayu Habang', 'kode_desa' => '63.01.08.2003'],
            ['nama_desa' => 'Martadah', 'kode_desa' => '63.01.08.2004'],
            ['nama_desa' => 'Martadah Baru', 'kode_desa' => '63.01.08.2005'],
            ['nama_desa' => 'Pulau Sari', 'kode_desa' => '63.01.08.2006'],
            ['nama_desa' => 'Sungai Jelai', 'kode_desa' => '63.01.08.2007'],
            ['nama_desa' => 'Sungai Pinang', 'kode_desa' => '63.01.08.2008'],
            ['nama_desa' => 'Tambang Ulang', 'kode_desa' => '63.01.08.2009'],

            // KECAMATAN BATU AMPAR (63.01.09) - 14 Desa
            ['nama_desa' => 'Ambawang', 'kode_desa' => '63.01.09.2001'],
            ['nama_desa' => 'Batu Ampar', 'kode_desa' => '63.01.09.2002'],
            ['nama_desa' => 'Bluru', 'kode_desa' => '63.01.09.2003'],
            ['nama_desa' => 'Damar Lima', 'kode_desa' => '63.01.09.2004'],
            ['nama_desa' => 'Damit', 'kode_desa' => '63.01.09.2005'],
            ['nama_desa' => 'Damit Hulu', 'kode_desa' => '63.01.09.2006'],
            ['nama_desa' => 'Durian Bungkuk', 'kode_desa' => '63.01.09.2007'],
            ['nama_desa' => 'Gunung Mas', 'kode_desa' => '63.01.09.2008'],
            ['nama_desa' => 'Gunung Melati', 'kode_desa' => '63.01.09.2009'],
            ['nama_desa' => 'Jilatan', 'kode_desa' => '63.01.09.2010'],
            ['nama_desa' => 'Jilatan Alur', 'kode_desa' => '63.01.09.2011'],
            ['nama_desa' => 'Pantai Linuh', 'kode_desa' => '63.01.09.2012'],
            ['nama_desa' => 'Tajau Mulya', 'kode_desa' => '63.01.09.2013'],
            ['nama_desa' => 'Tajau Pecah', 'kode_desa' => '63.01.09.2014'],

            // KECAMATAN BAJUIN (63.01.10) - 9 Desa
            ['nama_desa' => 'Bajuin', 'kode_desa' => '63.01.10.2001'],
            ['nama_desa' => 'Galam', 'kode_desa' => '63.01.10.2002'],
            ['nama_desa' => 'Ketapang', 'kode_desa' => '63.01.10.2003'],
            ['nama_desa' => 'Kunyit', 'kode_desa' => '63.01.10.2004'],
            ['nama_desa' => 'Pamalongan', 'kode_desa' => '63.01.10.2005'],
            ['nama_desa' => 'Sungai Bakar', 'kode_desa' => '63.01.10.2006'],
            ['nama_desa' => 'Tanjung', 'kode_desa' => '63.01.10.2007'],
            ['nama_desa' => 'Tebing Siring', 'kode_desa' => '63.01.10.2008'],
            ['nama_desa' => 'Tirta Jaya', 'kode_desa' => '63.01.10.2009'],

            // KECAMATAN BUMI MAKMUR (63.01.11) - 11 Desa
            ['nama_desa' => 'Bumi Harapan', 'kode_desa' => '63.01.11.2001'],
            ['nama_desa' => 'Handil Babirik', 'kode_desa' => '63.01.11.2002'],
            ['nama_desa' => 'Handil Birayang Atas', 'kode_desa' => '63.01.11.2003'],
            ['nama_desa' => 'Handil Birayang Bawah', 'kode_desa' => '63.01.11.2004'],
            ['nama_desa' => 'Handil Gayam', 'kode_desa' => '63.01.11.2005'],
            ['nama_desa' => 'Handil Labuan Amas', 'kode_desa' => '63.01.11.2006'],
            ['nama_desa' => 'Handil Maluka', 'kode_desa' => '63.01.11.2007'],
            ['nama_desa' => 'Handil Suruk', 'kode_desa' => '63.01.11.2008'],
            ['nama_desa' => 'Kurau Utara', 'kode_desa' => '63.01.11.2009'],
            ['nama_desa' => 'Pantai Harapan', 'kode_desa' => '63.01.11.2010'],
            ['nama_desa' => 'Sungai Rasau', 'kode_desa' => '63.01.11.2011'],
        ];

        $this->db->table('m_desa')->insertBatch($data);
    }
}