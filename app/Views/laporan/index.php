<?= $this->extend('templates/main') ?>
<?= $this->section('content') ?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1><i class="fas fa-file-alt mr-2"></i>Data Laporan Agregat</h1>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            <div class="card card-outline card-primary shadow">
                <div class="card-body">
                    <div class="col-sm-12 text-right">
                        <a href="/laporan/export" class="btn btn-success">
                            <i class="fas fa-file-excel"></i> Export ke Excel
                        </a>
                    </div>
                    <table id="tableLaporan" class="table table-bordered table-striped table-hover">
                        <thead>
                            <tr class="bg-light">
                                <th width="5%">No</th>
                                <th>Periode</th>
                                <th>Dusun</th>
                                <th>RT</th>
                                <th>Total Jiwa</th>
                                <th>Total KK</th>
                                <th width="12%">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Mapping Bulan Angka ke Nama
                            $bulanNama = [
                                1 => 'Januari',
                                2 => 'Februari',
                                3 => 'Maret',
                                4 => 'April',
                                5 => 'Mei',
                                6 => 'Juni',
                                7 => 'Juli',
                                8 => 'Agustus',
                                9 => 'September',
                                10 => 'Oktober',
                                11 => 'November',
                                12 => 'Desember'
                            ];

                            foreach ($laporan as $key => $row): ?>
                                <tr>
                                    <td class="text-center"><?= $key + 1 ?></td>
                                    <td>
                                        <strong><?= $bulanNama[$row['bulan']] ?? $row['bulan'] ?></strong>
                                        <?= $row['tahun'] ?>
                                    </td>
                                    <td><?= $row['nama_dusun'] ?></td>
                                    <td>RT <?= $row['no_rt'] ?></td>
                                    <td>
                                        <span class="badge badge-info"><?= number_format($row['jiwa_l'] + $row['jiwa_p']) ?>
                                            Jiwa</span>
                                    </td>
                                    <td>
                                        <span class="badge badge-success"><?= number_format($row['kk_l'] + $row['kk_p']) ?>
                                            KK</span>
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group">
                                            <a href="/laporan/edit/<?= $row['id_laporan'] ?>" class="btn btn-sm btn-warning"
                                                title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button type="button" class="btn btn-sm btn-danger"
                                                onclick="confirmDelete(<?= $row['id_laporan'] ?>)" title="Hapus">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>
</div>

<script>
    // Fungsi konfirmasi hapus agar lebih cantik
    function confirmDelete(id) {
        if (confirm('Apakah Anda yakin ingin menghapus data laporan ini?')) {
            window.location.href = '/laporan/delete/' + id;
        }
    }
</script>

<?= $this->endSection() ?>