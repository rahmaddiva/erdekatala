CREATE TABLE laporan_agregat (
    id_laporan INT PRIMARY KEY AUTO_INCREMENT,
    id_rt INT NOT NULL,
    bulan TINYINT,
    tahun YEAR,
    
    -- SHEET 1 & 2: Jumlah Jiwa & KK
    jiwa_l INT, jiwa_p INT,
    kk_l INT, kk_p INT,

    -- SHEET 3: KK Berdasarkan Tingkat Pendidikan
    kk_pend_tidak_sekolah INT, kk_pend_sd INT, kk_pend_smp INT, 
    kk_pend_sma INT, kk_pend_diploma INT, kk_pend_s1 INT, kk_pend_s2_s3 INT,

    -- SHEET 4: KK Berdasarkan Jenis Pekerjaan
    kk_ker_tani INT, kk_ker_nelayan INT, kk_ker_pns INT, kk_ker_swasta INT, 
    kk_ker_pedagang INT, kk_ker_wiraswasta INT, kk_ker_buruh INT, kk_ker_tidak_kerja INT,

    -- SHEET 5: Piramida Penduduk (5 Tahunan)
    u0_4_l INT, u0_4_p INT, u5_9_l INT, u5_9_p INT, u10_14_l INT, u10_14_p INT,
    u15_19_l INT, u15_19_p INT, u20_24_l INT, u20_24_p INT, u25_29_l INT, u25_29_p INT,
    u30_34_l INT, u30_34_p INT, u35_39_l INT, u35_39_p INT, u40_44_l INT, u40_44_p INT,
    u45_49_l INT, u45_49_p INT, u50_54_l INT, u50_54_p INT, u55_59_l INT, u55_59_p INT,
    u60_64_l INT, u60_64_p INT, u65_69_l INT, u65_69_p INT, u70_74_l INT, u70_74_p INT,
    u75_79_l INT, u75_79_p INT, u80_84_l INT, u80_84_p INT, u85_atas_l INT, u85_atas_p INT,

    -- SHEET 6: Kelompok Umur Khusus
    jml_balita INT, jml_remaja INT, jml_lansia INT,

    -- SHEET 7: KK Status Perkawinan
    kk_kawin INT, kk_belum_kawin INT, kk_cerai_hidup INT, kk_cerai_mati INT,

    -- SHEET 8 - 15: Dokumen & Kesehatan
    pend_bpjs INT, 
    pend_non_bpjs INT,
    kk_punya_kartu_fisik INT, 
    kk_belum_punya_kartu_fisik INT,
    penduduk_hub_kepala INT, penduduk_hub_istri INT, penduduk_hub_anak INT, penduduk_hub_lainnya INT,
    pus_jkn INT,
    pus_pbi INT,
    pus_non_pbi INT,
    jkn INT,
    non_jkn INT,
    pend_wajib_ktp INT,
    kk_punya_akta_nikah INT,
    pend_punya_akta_lahir INT,

    FOREIGN KEY (id_rt) REFERENCES m_rt(id_rt)
);