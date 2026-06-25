<?= $this->extend('templates/main') ?>
<?= $this->section('content') ?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1><i class="fas fa-print mr-2"></i>Cetak / Export Laporan</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="/laporan">Riwayat Laporan</a></li>
                        <li class="breadcrumb-item active">Pilihan Cetak</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            <form method="post" action="<?= base_url('laporan/export-options') ?>" id="exportForm">
                <?= csrf_field() ?>

                <div class="row">
                    <!-- Kolom Kiri: Filter Data -->
                    <div class="col-md-4">
                        <div class="card card-outline card-primary shadow-sm">
                            <div class="card-header">
                                <h3 class="card-title"><i class="fas fa-filter mr-2"></i>Filter Data</h3>
                            </div>
                            <div class="card-body">

                                <?php if ($role == 'admin_dinas'): ?>
                                    <div class="form-group">
                                        <label>Kecamatan</label>
                                        <select name="id_kecamatan" id="sel_kecamatan" class="form-control select2bs4">
                                            <option value="">-- Semua Kecamatan --</option>
                                            <?php foreach ($list_kecamatan as $k): ?>
                                                <option value="<?= $k['id_kecamatan'] ?>"><?= esc($k['nama_kecamatan']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>Desa</label>
                                        <select name="id_desa" id="sel_desa" class="form-control select2bs4" disabled>
                                            <option value="">-- Semua Desa --</option>
                                        </select>
                                    </div>
                                <?php elseif ($role == 'admin_kecamatan'): ?>
                                    <div class="form-group">
                                        <label>Desa</label>
                                        <select name="id_desa" id="sel_desa" class="form-control select2bs4">
                                            <option value="">-- Semua Desa --</option>
                                            <?php foreach ($list_desa as $d): ?>
                                                <option value="<?= $d['id_desa'] ?>"><?= esc($d['nama_desa']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                <?php endif; ?>

                                <div class="form-group">
                                    <label>Bulan</label>
                                    <select name="bulan" class="form-control select2bs4">
                                        <option value="">-- Semua Bulan --</option>
                                        <?php foreach ($bulanList as $num => $nama): ?>
                                            <option value="<?= $num ?>"><?= $nama ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label>Tahun</label>
                                    <select name="tahun" class="form-control">
                                        <option value="">-- Semua Tahun --</option>
                                        <?php for ($y = date('Y'); $y >= date('Y') - 5; $y--): ?>
                                            <option value="<?= $y ?>"><?= $y ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label>Format Output</label>
                                    <div>
                                        <div class="custom-control custom-radio custom-control-inline">
                                            <input type="radio" id="fmt_pdf" name="format" value="pdf" class="custom-control-input" checked>
                                            <label class="custom-control-label" for="fmt_pdf">
                                                <i class="fas fa-file-pdf text-danger mr-1"></i> PDF
                                            </label>
                                        </div>
                                        <div class="custom-control custom-radio custom-control-inline">
                                            <input type="radio" id="fmt_excel" name="format" value="excel" class="custom-control-input">
                                            <label class="custom-control-label" for="fmt_excel">
                                                <i class="fas fa-file-excel text-success mr-1"></i> Excel
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Kolom Kanan: Pilihan Kategori -->
                    <div class="col-md-8">
                        <div class="card card-outline card-info shadow-sm">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h3 class="card-title"><i class="fas fa-th-list mr-2"></i>Pilih Kategori yang Dicetak</h3>
                                <div>
                                    <button type="button" class="btn btn-xs btn-outline-secondary" id="btnSelectAll">Pilih Semua</button>
                                    <button type="button" class="btn btn-xs btn-outline-secondary ml-1" id="btnClearAll">Hapus Semua</button>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="row">

                                    <!-- Kategori 1 -->
                                    <div class="col-md-6 mb-3">
                                        <div class="card card-sm shadow-sm h-100">
                                            <div class="card-header py-2">
                                                <div class="custom-control custom-checkbox">
                                                    <input type="checkbox" class="custom-control-input section-check" id="sec_pokok" name="sections[]" value="pokok" checked>
                                                    <label class="custom-control-label font-weight-bold" for="sec_pokok">
                                                        <i class="fas fa-users mr-1 text-primary"></i> Data Pokok Jiwa & KK
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="card-body py-2">
                                                <small class="text-muted">Jumlah jiwa laki-laki & perempuan, jumlah kepala keluarga L & P.</small>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Kategori 2 -->
                                    <div class="col-md-6 mb-3">
                                        <div class="card card-sm shadow-sm h-100">
                                            <div class="card-header py-2">
                                                <div class="custom-control custom-checkbox">
                                                    <input type="checkbox" class="custom-control-input section-check" id="sec_pendidikan" name="sections[]" value="pendidikan" checked>
                                                    <label class="custom-control-label font-weight-bold" for="sec_pendidikan">
                                                        <i class="fas fa-graduation-cap mr-1 text-info"></i> Pendidikan KK
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="card-body py-2">
                                                <small class="text-muted">Tingkat pendidikan kepala keluarga: tidak sekolah, SD, SMP, SMA, Diploma, S1, S2/S3.</small>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Kategori 3 -->
                                    <div class="col-md-6 mb-3">
                                        <div class="card card-sm shadow-sm h-100">
                                            <div class="card-header py-2">
                                                <div class="custom-control custom-checkbox">
                                                    <input type="checkbox" class="custom-control-input section-check" id="sec_pekerjaan" name="sections[]" value="pekerjaan" checked>
                                                    <label class="custom-control-label font-weight-bold" for="sec_pekerjaan">
                                                        <i class="fas fa-briefcase mr-1 text-success"></i> Pekerjaan KK
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="card-body py-2">
                                                <small class="text-muted">Jenis pekerjaan KK: tani, nelayan, PNS, swasta, pedagang, wiraswasta, buruh, tidak bekerja.</small>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Kategori 4 -->
                                    <div class="col-md-6 mb-3">
                                        <div class="card card-sm shadow-sm h-100">
                                            <div class="card-header py-2">
                                                <div class="custom-control custom-checkbox">
                                                    <input type="checkbox" class="custom-control-input section-check" id="sec_piramida" name="sections[]" value="piramida" checked>
                                                    <label class="custom-control-label font-weight-bold" for="sec_piramida">
                                                        <i class="fas fa-chart-bar mr-1 text-danger"></i> Piramida Penduduk
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="card-body py-2">
                                                <small class="text-muted">Distribusi kelompok umur 0-4 s/d 85+ tahun, per jenis kelamin.</small>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Kategori 5 -->
                                    <div class="col-md-6 mb-3">
                                        <div class="card card-sm shadow-sm h-100">
                                            <div class="card-header py-2">
                                                <div class="custom-control custom-checkbox">
                                                    <input type="checkbox" class="custom-control-input section-check" id="sec_kawin" name="sections[]" value="kawin" checked>
                                                    <label class="custom-control-label font-weight-bold" for="sec_kawin">
                                                        <i class="fas fa-ring mr-1 text-warning"></i> Status Perkawinan
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="card-body py-2">
                                                <small class="text-muted">Status perkawinan KK: belum kawin, kawin, cerai hidup, cerai mati.</small>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Kategori 6 -->
                                    <div class="col-md-6 mb-3">
                                        <div class="card card-sm shadow-sm h-100">
                                            <div class="card-header py-2">
                                                <div class="custom-control custom-checkbox">
                                                    <input type="checkbox" class="custom-control-input section-check" id="sec_jkn" name="sections[]" value="jkn" checked>
                                                    <label class="custom-control-label font-weight-bold" for="sec_jkn">
                                                        <i class="fas fa-heartbeat mr-1 text-danger"></i> JKN / BPJS
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="card-body py-2">
                                                <small class="text-muted">Kepemilikan JKN: BPJS PBI, non-PBI, dan penduduk tidak ber-JKN.</small>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Kategori 7 -->
                                    <div class="col-md-6 mb-3">
                                        <div class="card card-sm shadow-sm h-100">
                                            <div class="card-header py-2">
                                                <div class="custom-control custom-checkbox">
                                                    <input type="checkbox" class="custom-control-input section-check" id="sec_dokumen" name="sections[]" value="dokumen" checked>
                                                    <label class="custom-control-label font-weight-bold" for="sec_dokumen">
                                                        <i class="fas fa-id-card mr-1 text-secondary"></i> Dokumen Adminduk
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="card-body py-2">
                                                <small class="text-muted">KTP elektronik, akta lahir, akta nikah, kartu keluarga fisik.</small>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Kategori 8 -->
                                    <div class="col-md-6 mb-3">
                                        <div class="card card-sm shadow-sm h-100">
                                            <div class="card-header py-2">
                                                <div class="custom-control custom-checkbox">
                                                    <input type="checkbox" class="custom-control-input section-check" id="sec_kb" name="sections[]" value="kb" checked>
                                                    <label class="custom-control-label font-weight-bold" for="sec_kb">
                                                        <i class="fas fa-baby mr-1 text-pink" style="color:#e91e8c"></i> KB & PUS
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="card-body py-2">
                                                <small class="text-muted">Balita, remaja, lansia, PUS, KB aktif, penggunaan alat kontrasepsi.</small>
                                            </div>
                                        </div>
                                    </div>

                                </div>

                                <!-- Validasi minimal 1 terpilih -->
                                <div id="alertNoSection" class="alert alert-danger d-none">
                                    <i class="fas fa-exclamation-triangle mr-2"></i>
                                    Pilih minimal satu kategori untuk dicetak.
                                </div>
                            </div>
                        </div>

                        <!-- Tombol Submit -->
                        <div class="d-flex justify-content-end">
                            <a href="/laporan" class="btn btn-secondary mr-2">
                                <i class="fas fa-arrow-left mr-1"></i> Kembali
                            </a>
                            <button type="submit" class="btn btn-primary" id="btnExport">
                                <i class="fas fa-print mr-1"></i> Cetak / Export Sekarang
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </section>
</div>

<script>
$(document).ready(function () {
    // Chained dropdown admin_dinas
    $('#sel_kecamatan').on('change', function () {
        var idKec = $(this).val();
        var $desa = $('#sel_desa');
        if (idKec) {
            $desa.prop('disabled', false).html('<option value="">Memuat...</option>');
            $.get('<?= base_url('dashboard/getDesaByKecamatan') ?>/' + idKec, function (res) {
                var html = '<option value="">-- Semua Desa --</option>';
                res.forEach(function (d) {
                    html += '<option value="' + d.id_desa + '">' + d.nama_desa + '</option>';
                });
                $desa.html(html);
            });
        } else {
            $desa.prop('disabled', true).html('<option value="">-- Semua Desa --</option>');
        }
    });

    // Pilih semua / hapus semua
    $('#btnSelectAll').on('click', function () {
        $('.section-check').prop('checked', true);
    });
    $('#btnClearAll').on('click', function () {
        $('.section-check').prop('checked', false);
    });

    // Validasi minimal 1 section sebelum submit
    $('#exportForm').on('submit', function (e) {
        var checked = $('.section-check:checked').length;
        if (checked === 0) {
            e.preventDefault();
            $('#alertNoSection').removeClass('d-none');
            $('html, body').animate({ scrollTop: $('#alertNoSection').offset().top - 100 }, 400);
        } else {
            $('#alertNoSection').addClass('d-none');
        }
    });

    // Sembunyikan alert saat ada yang dicentang
    $('.section-check').on('change', function () {
        if ($('.section-check:checked').length > 0) {
            $('#alertNoSection').addClass('d-none');
        }
    });
});
</script>

<?= $this->endSection() ?>
