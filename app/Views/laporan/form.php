<?= $this->extend('templates/main') ?>
<?= $this->section('content') ?>

<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <h1><?= $title ?></h1>
        </div>
    </section>

    <section class="content">
        <?php if (session()->getFlashdata('error')): ?>
            <div class="alert alert-danger alert-dismissible">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                <h5><i class="icon fas fa-ban"></i> Peringatan!</h5>
                <?= session()->getFlashdata('error') ?>
            </div>
        <?php endif; ?>

        <form action="<?= isset($laporan) ? '/laporan/update/' . $laporan['id_laporan'] : '/laporan/store' ?>"
            method="post">
            <div class="card card-primary card-outline card-tabs">
                <div class="card-header p-0 pt-1 border-bottom-0">
                    <ul class="nav nav-tabs" id="laporanTab" role="tablist">
                        <li class="nav-item"><a class="nav-link active" data-toggle="pill" href="#tab-umum">1. Data
                                Pokok</a></li>
                        <li class="nav-item"><a class="nav-link" data-toggle="pill" href="#tab-sosial">2.
                                Sosio-Ekonomi</a></li>
                        <li class="nav-item"><a class="nav-link" data-toggle="pill" href="#tab-piramida">3. Piramida
                                Umur</a></li>
                        <li class="nav-item"><a class="nav-link" data-toggle="pill" href="#tab-dokumen">4. Kesehatan &
                                Dokumen</a></li>
                    </ul>
                </div>

                <div class="card-body">
                    <div class="tab-content">

                        <div class="tab-pane fade show active" id="tab-umum">
                            <div class="row">
                                <div class="col-md-4">
                                    <label>RT / Dusun</label>
                                    <select name="id_rt" class="form-control" required>
                                        <option value="">-- Pilih RT --</option>
                                        <?php foreach ($list_rt as $rt): ?>
                                            <option value="<?= $rt['id_rt'] ?>" <?= (isset($laporan) && $laporan['id_rt'] == $rt['id_rt']) ? 'selected' : '' ?>>
                                                RT <?= $rt['no_rt'] ?> - <?= $rt['nama_dusun'] ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label>Bulan (Periode)</label>
                                    <select name="bulan" class="form-control" required>
                                        <?php
                                        $nama_bulan = [1 => "Januari", "Februari", "Maret", "April", "Mei", "Juni", "Juli", "Agustus", "September", "Oktober", "November", "Desember"];
                                        foreach ($nama_bulan as $val => $name): ?>
                                            <option value="<?= $val ?>" <?= (isset($laporan) && $laporan['bulan'] == $val) ? 'selected' : (date('n') == $val ? 'selected' : '') ?>>
                                                <?= $name ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label>Tahun</label>
                                    <input type="number" name="tahun" class="form-control"
                                        value="<?= $laporan['tahun'] ?? date('Y') ?>" required>
                                </div>
                            </div>
                            <hr>
                            <div class="row">
                                <?php
                                $pokok = [
                                    'jiwa_l' => 'Total Jiwa (L)',
                                    'jiwa_p' => 'Total Jiwa (P)',
                                    'kk_l' => 'Total KK (L)',
                                    'kk_p' => 'Total KK (P)'
                                ];
                                foreach ($pokok as $field => $label): ?>
                                    <div class="col-md-3">
                                        <label><?= $label ?></label>
                                        <input type="number" name="<?= $field ?>" class="form-control"
                                            value="<?= $laporan[$field] ?? 0 ?>" min="0">
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="tab-sosial">
                            <div class="row">
                                <div class="col-md-4">
                                    <h5 class="text-primary"><i class="fas fa-graduation-cap"></i> Pendidikan KK</h5>
                                    <?php
                                    $pend = [
                                        'kk_pend_tidak_sekolah' => 'Tdk Sekolah',
                                        'kk_pend_sd' => 'SD/Sederajat',
                                        'kk_pend_smp' => 'SMP/Sederajat',
                                        'kk_pend_sma' => 'SMA/Sederajat',
                                        'kk_pend_diploma' => 'Diploma (D1-D3)',
                                        'kk_pend_s1' => 'Sarjana (S1)',
                                        'kk_pend_s2_s3' => 'Pascasarjana (S2-S3)'
                                    ];
                                    foreach ($pend as $f => $l): ?>
                                        <div class="form-group mb-1">
                                            <small><?= $l ?></small>
                                            <input type="number" name="<?= $f ?>" class="form-control form-control-sm"
                                                value="<?= $laporan[$f] ?? 0 ?>">
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <div class="col-md-4">
                                    <h5 class="text-primary"><i class="fas fa-briefcase"></i> Pekerjaan KK</h5>
                                    <?php
                                    $kerja = [
                                        'kk_ker_tani' => 'Petani',
                                        'kk_ker_nelayan' => 'Nelayan',
                                        'kk_ker_pns' => 'PNS/TNI/Polri',
                                        'kk_ker_swasta' => 'Karyawan Swasta',
                                        'kk_ker_pedagang' => 'Pedagang',
                                        'kk_ker_wiraswasta' => 'Wiraswasta',
                                        'kk_ker_buruh' => 'Buruh Habis Pakai',
                                        'kk_ker_tidak_kerja' => 'Tdk Bekerja'
                                    ];
                                    foreach ($kerja as $f => $l): ?>
                                        <div class="form-group mb-1">
                                            <small><?= $l ?></small>
                                            <input type="number" name="<?= $f ?>" class="form-control form-control-sm"
                                                value="<?= $laporan[$f] ?? 0 ?>">
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <div class="col-md-4">
                                    <h5 class="text-primary"><i class="fas fa-heart"></i> Status Kawin KK</h5>
                                    <?php
                                    $kawin = [
                                        'kk_belum_kawin' => 'Belum Kawin',
                                        'kk_kawin' => 'Kawin',
                                        'kk_cerai_hidup' => 'Cerai Hidup',
                                        'kk_cerai_mati' => 'Cerai Mati'
                                    ];
                                    foreach ($kawin as $f => $l): ?>
                                        <div class="form-group mb-1">
                                            <small><?= $l ?></small>
                                            <input type="number" name="<?= $f ?>" class="form-control form-control-sm"
                                                value="<?= $laporan[$f] ?? 0 ?>">
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="tab-piramida">
                            <div class="row">
                                <?php
                                $groups = ['0_4', '5_9', '10_14', '15_19', '20_24', '25_29', '30_34', '35_39', '40_44', '45_49', '50_54', '55_59', '60_64', '65_69', '70_74', '75_79', '80_84', '85_plus'];
                                ?>
                                <div class="col-md-6 border-right">
                                    <h5 class="text-center text-primary">Laki-Laki (L)</h5>
                                    <?php foreach ($groups as $g): ?>
                                        <div class="form-group row mb-1">
                                            <label class="col-sm-5 col-form-label-sm">Umur
                                                <?= str_replace('_', '-', $g) ?></label>
                                            <div class="col-sm-7">
                                                <input type="number" name="u<?= $g ?>_l"
                                                    class="form-control form-control-sm"
                                                    value="<?= $laporan["u{$g}_l"] ?? 0 ?>">
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <div class="col-md-6">
                                    <h5 class="text-center text-danger">Perempuan (P)</h5>
                                    <?php foreach ($groups as $g): ?>
                                        <div class="form-group row mb-1">
                                            <label class="col-sm-5 col-form-label-sm">Umur
                                                <?= str_replace('_', '-', $g) ?></label>
                                            <div class="col-sm-7">
                                                <input type="number" name="u<?= $g ?>_p"
                                                    class="form-control form-control-sm"
                                                    value="<?= $laporan["u{$g}_p"] ?? 0 ?>">
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="tab-dokumen">
                            <div class="row">
                                <div class="col-md-4">
                                    <h5 class="text-primary">Hubungan & JKN</h5>
                                    <?php
                                    $misc1 = [
                                        'penduduk_hub_kepala' => 'Hub: Kepala Kel.',
                                        'penduduk_hub_istri' => 'Hub: Istri',
                                        'penduduk_hub_anak' => 'Hub: Anak',
                                        'penduduk_hub_lainnya' => 'Hub: Lainnya',
                                        'jkn' => 'Punya JKN/BPJS',
                                        'non_jkn' => 'Tdk Punya JKN',
                                        'pend_bpjs' => 'Total Peserta BPJS'
                                    ];
                                    foreach ($misc1 as $f => $l): ?>
                                        <div class="form-group mb-1">
                                            <small><?= $l ?></small>
                                            <input type="number" name="<?= $f ?>" class="form-control form-control-sm"
                                                value="<?= $laporan[$f] ?? 0 ?>">
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <div class="col-md-4">
                                    <h5 class="text-primary">KB & Pasangan Usia Subur</h5>
                                    <?php
                                    $misc2 = [
                                        'jml_pus' => 'Jumlah PUS',
                                        'kb_aktif' => 'Peserta KB Aktif',
                                        'pus_jkn' => 'PUS Punya JKN',
                                        'pus_pbi' => 'PUS JKN PBI',
                                        'pus_non_pbi' => 'PUS JKN Non-PBI',
                                        'jml_balita' => 'Jumlah Balita',
                                        'jml_penggunaan_alat_kontrasepsi' => 'Alat Kontrasepsi'
                                    ];
                                    foreach ($misc2 as $f => $l): ?>
                                        <div class="form-group mb-1">
                                            <small><?= $l ?></small>
                                            <input type="number" name="<?= $f ?>" class="form-control form-control-sm"
                                                value="<?= $laporan[$f] ?? 0 ?>">
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <div class="col-md-4">
                                    <h5 class="text-primary">Dokumen Kependudukan</h5>
                                    <?php
                                    $misc3 = [
                                        'pend_wajib_ktp' => 'Wajib KTP',
                                        'kk_punya_kartu_fisik' => 'KK Cetak Fisik',
                                        'kk_belum_punya_kartu_fisik' => 'KK Belum Fisik',
                                        'kk_punya_akta_nikah' => 'Punya Akta Nikah',
                                        'pend_punya_akta_lahir' => 'Punya Akta Lahir'
                                    ];
                                    foreach ($misc3 as $f => $l): ?>
                                        <div class="form-group mb-1">
                                            <small><?= $l ?></small>
                                            <input type="number" name="<?= $f ?>" class="form-control form-control-sm"
                                                value="<?= $laporan[$f] ?? 0 ?>">
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <div class="card-footer">
                    <div class="float-right">
                        <a href="/laporan" class="btn btn-secondary">Batal</a>
                        <button type="submit" class="btn btn-success btn-lg">
                            <i class="fas fa-save"></i>
                            <?= isset($laporan) ? 'Update Laporan' : 'Simpan Laporan Baru' ?>
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </section>
</div>

<?= $this->endSection() ?>