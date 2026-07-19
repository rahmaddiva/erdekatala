<?= $this->extend('templates/main') ?>
<?= $this->section('content') ?>

<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <h1><?= esc($title) ?></h1>
            <!-- peringatan -->
            <div class="alert alert-info alert-dismissible mt-1">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                <h5><i class="icon fas fa-info-circle"></i> Informasi!</h5>
                Form laporan agregat digunakan untuk memasukkan data statistik kependudukan secara
                berkala (bulanan) berdasarkan Desa / wilayah. Pastikan data yang dimasukkan sudah benar
                sebelum disimpan ke dalam sistem.
            </div>
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
            <?= csrf_field() ?>
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
                            <div class="row mb-3">
                                <div class="col-md-12">
                                    <button type="button" id="btn-recycle" class="btn btn-outline-info btn-sm">
                                        <i class="fas fa-sync-alt"></i> Muat Ulang Data Bulan Lalu
                                    </button>
                                    <small class="text-muted d-block mt-1">*Pilih Desa terlebih dahulu sebelum
                                        menyalin data.</small>
                                </div>
                            </div>
                            <div class="row">

                                <div class="col-md-4">
                                    <label>Pilih Desa / Wilayah</label>
                                    <select name="id_desa" class="form-control select2bs4" required>
                                        <option value="">-- Pilih Desa --</option>
                                        <?php foreach ($list_desa as $desa): ?>
                                            <option value="<?= $desa['id_desa'] ?>" <?= (isset($laporan) && $laporan['id_desa'] == $desa['id_desa']) ? 'selected' : '' ?>>
                                                <?= esc($desa['nama_desa']) ?>
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
                    <div id="validasiBox" class="alert alert-warning d-none mb-2 py-2"></div>
                    <div class="float-right">
                        <a href="/laporan" class="btn btn-secondary">Batal</a>
                        <button type="submit" class="btn btn-success btn-submit">
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

<?= $this->section('scripts') ?>
<script>
    $(document).ready(function () {
        const isCreateMode = $('form').attr('action').includes('/store');
        const ignoreFields = ['id_laporan', 'id_desa', 'bulan', 'tahun', 'id_user', 'created_at', 'updated_at'];

        function n(name) { return parseInt($('[name="' + name + '"]').val(), 10) || 0; }
        function sum(names) { return names.reduce(function (a, f) { return a + n(f); }, 0); }

        // form pakai u85_plus_* (bukan u85_atas_*)
        var piramidaL = ['u0_4_l','u5_9_l','u10_14_l','u15_19_l','u20_24_l','u25_29_l','u30_34_l','u35_39_l','u40_44_l','u45_49_l','u50_54_l','u55_59_l','u60_64_l','u65_69_l','u70_74_l','u75_79_l','u80_84_l','u85_plus_l'];
        var piramidaP = ['u0_4_p','u5_9_p','u10_14_p','u15_19_p','u20_24_p','u25_29_p','u30_34_p','u35_39_p','u40_44_p','u45_49_p','u50_54_p','u55_59_p','u60_64_p','u65_69_p','u70_74_p','u75_79_p','u80_84_p','u85_plus_p'];
        var pendFields = ['kk_pend_tidak_sekolah','kk_pend_sd','kk_pend_smp','kk_pend_sma','kk_pend_diploma','kk_pend_s1','kk_pend_s2_s3'];
        var kerFields  = ['kk_ker_tani','kk_ker_nelayan','kk_ker_pns','kk_ker_swasta','kk_ker_pedagang','kk_ker_wiraswasta','kk_ker_buruh','kk_ker_tidak_kerja'];
        var kawinFields = ['kk_kawin','kk_belum_kawin','kk_cerai_hidup','kk_cerai_mati'];

        function runValidasi() {
            var errors = [];
            var jiwaL = n('jiwa_l'), jiwaP = n('jiwa_p');
            var kkL = n('kk_l'), kkP = n('kk_p');
            var totalJiwa = jiwaL + jiwaP;
            var totalKK = kkL + kkP;
            var sumPiramidaL = sum(piramidaL);
            var sumPiramidaP = sum(piramidaP);
            var sumPend = sum(pendFields);
            var sumKer = sum(kerFields);
            var sumKawin = sum(kawinFields);

            if (sumPiramidaL > 0 && sumPiramidaL !== jiwaL) {
                errors.push('Piramida umur Laki-laki (' + sumPiramidaL + ') ≠ Jiwa L (' + jiwaL + ')');
            }
            if (sumPiramidaP > 0 && sumPiramidaP !== jiwaP) {
                errors.push('Piramida umur Perempuan (' + sumPiramidaP + ') ≠ Jiwa P (' + jiwaP + ')');
            }
            if (sumPend > 0 && sumPend !== totalKK) {
                errors.push('Total Pendidikan KK (' + sumPend + ') ≠ Total KK (' + totalKK + ')');
            }
            if (sumKer > 0 && sumKer !== totalKK) {
                errors.push('Total Pekerjaan KK (' + sumKer + ') ≠ Total KK (' + totalKK + ')');
            }
            if (sumKawin > 0 && sumKawin !== totalKK) {
                errors.push('Total Status Kawin (' + sumKawin + ') ≠ Total KK (' + totalKK + ')');
            }
            if (n('kk_punya_kartu_fisik') + n('kk_belum_punya_kartu_fisik') > 0
                && n('kk_punya_kartu_fisik') + n('kk_belum_punya_kartu_fisik') !== totalKK) {
                errors.push('KK punya + belum kartu fisik ≠ Total KK');
            }

            var $box = $('#validasiBox');
            if (errors.length) {
                $box.removeClass('d-none').html('<strong><i class="fas fa-exclamation-triangle"></i> Validasi silang:</strong><ul class="mb-0 pl-3">' +
                    errors.map(function (e) { return '<li>' + e + '</li>'; }).join('') + '</ul>');
            } else {
                $box.addClass('d-none').empty();
            }
            return errors;
        }

        $('form input[type="number"]').on('change keyup', runValidasi);

        $('form').on('submit', function (e) {
            var errors = runValidasi();
            if (errors.length) {
                e.preventDefault();
                Swal.fire({
                    icon: 'warning',
                    title: 'Data tidak konsisten',
                    html: '<ul class="text-left">' + errors.map(function (x) { return '<li>' + x + '</li>'; }).join('') + '</ul>' +
                        '<p class="mb-0 mt-2">Perbaiki data, atau klik <b>Simpan tetap</b> jika memang sengaja.</p>',
                    showCancelButton: true,
                    confirmButtonText: 'Simpan tetap',
                    cancelButtonText: 'Perbaiki dulu'
                }).then(function (result) {
                    if (result.isConfirmed) {
                        $('form').off('submit').submit();
                    }
                });
            }
        });

        function fillFormFromData(data) {
            $('form input[type="number"]').each(function () {
                var fieldName = $(this).attr('name');
                if (data[fieldName] !== undefined && !ignoreFields.includes(fieldName)) {
                    $(this).val(data[fieldName]);
                }
            });
            runValidasi();
        }

        function fetchAndFill(silent) {
            var idDesa = $('select[name="id_desa"]').val();
            var bulan  = $('select[name="bulan"]').val();
            var tahun  = $('input[name="tahun"]').val();

            if (!idDesa || !bulan || !tahun) return;

            $.get('<?= base_url('laporan/getPreviousData') ?>', { id_desa: idDesa, bulan: bulan, tahun: tahun })
                .done(function (response) {
                    if (response.status === 'success') {
                        fillFormFromData(response.data);
                        if (!silent) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Sukses',
                                text: 'Data berhasil dimuat! Silakan periksa kembali sebelum menyimpan.'
                            });
                        } else {
                            Swal.fire({
                                toast: true,
                                position: 'top-end',
                                icon: 'info',
                                title: 'Data bulan sebelumnya dimuat otomatis.',
                                showConfirmButton: false,
                                timer: 3000
                            });
                        }
                    } else if (!silent) {
                        Swal.fire({ icon: 'error', title: 'Gagal', text: response.message || 'Gagal mengambil data.' });
                    }
                })
                .fail(function () {
                    if (!silent) {
                        Swal.fire({ icon: 'error', title: 'Error', text: 'Gagal mengambil data. Pastikan koneksi server tersedia.' });
                    }
                });
        }

        if (isCreateMode) {
            $('select[name="id_desa"], select[name="bulan"], input[name="tahun"]').on('change', function () {
                fetchAndFill(true);
            });
        }

        $('#btn-recycle').on('click', function () {
            var idDesa = $('select[name="id_desa"]').val();
            if (!idDesa) {
                Swal.fire({ icon: 'warning', title: 'Pilih Desa', text: 'Silakan pilih Desa terlebih dahulu!' });
                return;
            }
            Swal.fire({
                title: 'Muat ulang data bulan sebelumnya?',
                text: 'Data yang sudah Anda ketik manual akan tertimpa.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Muat Ulang',
                cancelButtonText: 'Batal'
            }).then(function(result) {
                if (result.isConfirmed) fetchAndFill(false);
            });
        });

        runValidasi();
    });
</script>

<?= $this->endSection() ?>