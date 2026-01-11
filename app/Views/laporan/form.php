<?= $this->extend('templates/main') ?>
<?= $this->section('content') ?>
<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <h1>
                <?= $title ?>
            </h1>
        </div>
    </section>

    <section class="content">
        <?php if (session()->getFlashdata('error')): ?>
            <div class="alert alert-danger alert-dismissible">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                <h5><i class="icon fas fa-ban"></i> Error!</h5>
                <?= session()->getFlashdata('error') ?>
            </div>
        <?php endif; ?>

        <form action="<?= isset($laporan) ? '/laporan/update/' . $laporan['id_laporan'] : '/laporan/store' ?>"
            method="post">
            <div class="card card-primary card-outline card-tabs">
                <div class="card-header p-0 pt-1 border-bottom-0">
                    <ul class="nav nav-tabs" id="laporanTab" role="tablist">
                        <li class="nav-item"><a class="nav-link active" data-toggle="pill" href="#tab-umum">Data
                                Pokok</a></li>
                        <li class="nav-item"><a class="nav-link" data-toggle="pill"
                                href="#tab-karakter">Karakteristik</a></li>
                        <li class="nav-item"><a class="nav-link" data-toggle="pill" href="#tab-piramida">Piramida
                                Umur</a></li>
                        <li class="nav-item"><a class="nav-link" data-toggle="pill" href="#tab-sehat">Kesehatan & KB</a>
                        </li>
                    </ul>
                </div>
                <div class="card-body">
                    <div class="tab-content">
                        <div class="tab-pane fade show active" id="tab-umum">
                            <div class="row">
                                <div class="col-md-4">
                                    <label>Pilih RT</label>
                                    <select name="id_rt" class="form-control" required>
                                        <?php foreach ($list_rt as $rt): ?>
                                            <option value="<?= $rt['id_rt'] ?>" <?= (isset($laporan) && $laporan['id_rt'] == $rt['id_rt']) ? 'selected' : '' ?>>RT
                                                <?= $rt['no_rt'] ?> -
                                                <?= $rt['nama_dusun'] ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label>Bulan</label>
                                    <select name="bulan" class="form-control" required>
                                        <option value="">-- Pilih Bulan --</option>
                                        <?php foreach ($bulan as $num => $name): ?>
                                            <option value="<?= $num ?>" <?= (isset($laporan) && $laporan['bulan'] == $num) ? 'selected' : '' ?>>
                                                <?= $name ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-4"><label>Tahun</label><input type="number" name="tahun"
                                        class="form-control" value="<?= $laporan['tahun'] ?? date('Y') ?>"></div>
                            </div>
                            <hr>
                            <div class="row mt-3">
                                <div class="col-md-3"><label>Jiwa (L)</label><input type="number" name="jiwa_l"
                                        class="form-control" value="<?= $laporan['jiwa_l'] ?? 0 ?>"></div>
                                <div class="col-md-3"><label>Jiwa (P)</label><input type="number" name="jiwa_p"
                                        class="form-control" value="<?= $laporan['jiwa_p'] ?? 0 ?>"></div>
                                <div class="col-md-3"><label>KK (L)</label><input type="number" name="kk_l"
                                        class="form-control" value="<?= $laporan['kk_l'] ?? 0 ?>"></div>
                                <div class="col-md-3"><label>KK (P)</label><input type="number" name="kk_p"
                                        class="form-control" value="<?= $laporan['kk_p'] ?? 0 ?>"></div>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="tab-karakter">
                            <div class="row">
                                <div class="col-md-6">
                                    <h5>Pendidikan KK</h5>
                                    <label>SD</label><input type="number" name="kk_pend_sd" class="form-control mb-2"
                                        value="<?= $laporan['kk_pend_sd'] ?? 0 ?>">
                                    <label>SMP</label><input type="number" name="kk_pend_smp" class="form-control mb-2"
                                        value="<?= $laporan['kk_pend_smp'] ?? 0 ?>">
                                    <label>SMA</label><input type="number" name="kk_pend_sma" class="form-control mb-2"
                                        value="<?= $laporan['kk_pend_sma'] ?? 0 ?>">
                                </div>
                                <div class="col-md-6">
                                    <h5>Pekerjaan KK</h5>
                                    <label>Petani</label><input type="number" name="kk_ker_tani"
                                        class="form-control mb-2" value="<?= $laporan['kk_ker_tani'] ?? 0 ?>">
                                    <label>PNS</label><input type="number" name="kk_ker_pns" class="form-control mb-2"
                                        value="<?= $laporan['kk_ker_pns'] ?? 0 ?>">
                                    <label>Wiraswasta</label><input type="number" name="kk_ker_wiraswasta"
                                        class="form-control mb-2" value="<?= $laporan['kk_ker_wiraswasta'] ?? 0 ?>">
                                </div>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="tab-piramida">
                            <div class="row">
                                <div class="col-md-6">
                                    <h5>Laki-laki</h5>
                                    <div class="form-group row"><label class="col-sm-4">0-4 Thn</label>
                                        <div class="col-sm-8"><input type="number" name="u0_4_l" class="form-control"
                                                value="<?= $laporan['u0_4_l'] ?? 0 ?>"></div>
                                    </div>
                                    <div class="form-group row"><label class="col-sm-4">5-9 Thn</label>
                                        <div class="col-sm-8"><input type="number" name="u5_9_l" class="form-control"
                                                value="<?= $laporan['u5_9_l'] ?? 0 ?>"></div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <h5>Perempuan</h5>
                                    <div class="form-group row"><label class="col-sm-4">0-4 Thn</label>
                                        <div class="col-sm-8"><input type="number" name="u0_4_p" class="form-control"
                                                value="<?= $laporan['u0_4_p'] ?? 0 ?>"></div>
                                    </div>
                                    <div class="form-group row"><label class="col-sm-4">5-9 Thn</label>
                                        <div class="col-sm-8"><input type="number" name="u5_9_p" class="form-control"
                                                value="<?= $laporan['u5_9_p'] ?? 0 ?>"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="tab-sehat">
                            <div class="row">
                                <div class="col-md-6">
                                    <h5>Kesehatan</h5>
                                    <label>BPJS</label><input type="number" name="pend_bpjs" class="form-control mb-2"
                                        value="<?= $laporan['pend_bpjs'] ?? 0 ?>">
                                    <label>Balita</label><input type="number" name="jml_balita"
                                        class="form-control mb-2" value="<?= $laporan['jml_balita'] ?? 0 ?>">
                                </div>
                                <div class="col-md-6">
                                    <h5>KB & PUS</h5>
                                    <label>Jumlah PUS</label><input type="number" name="jml_pus"
                                        class="form-control mb-2" value="<?= $laporan['jml_pus'] ?? 0 ?>">
                                    <label>KB Aktif</label><input type="number" name="kb_aktif"
                                        class="form-control mb-2" value="<?= $laporan['kb_aktif'] ?? 0 ?>">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer text-right">
                    <button type="submit" class="btn btn-success btn-lg"><i class="fas fa-save"></i> Simpan
                        Laporan</button>
                </div>
            </div>
        </form>
    </section>
</div>
<?= $this->endSection() ?>