<?= $this->extend('templates/main') ?>
<?= $this->section('content') ?>
<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Data Laporan Agregat</h1>
                </div>

            </div>
        </div>
    </div>

    <section class="content">
        <div class="card card-outline card-primary">
            <div class="card-body">
                <table class="table table-bordered table-striped" id="tableLaporan">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Periode</th>
                            <th>Dusun</th>
                            <th>RT</th>
                            <th>Total Jiwa</th>
                            <th>Total KK</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
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
                                <td>
                                    <?= $key + 1 ?>
                                </td>
                                <td>
                                    <?= $bulanNama[$row['bulan']] ?? 'Tidak Diketahui' ?>
                                    <?= $row['tahun'] ?>
                                </td>
                                <td>
                                    <?= $row['nama_dusun'] ?>
                                </td>
                                <td>RT
                                    <?= $row['no_rt'] ?>
                                </td>
                                <td>
                                    <?= number_format($row['jiwa_l'] + $row['jiwa_p']) ?>
                                </td>
                                <td>
                                    <?= number_format($row['kk_l'] + $row['kk_p']) ?>
                                </td>
                                <td>
                                    <a href="/laporan/edit/<?= $row['id_laporan'] ?>" class="btn btn-sm btn-warning"><i
                                            class="fas fa-edit"></i></a>
                                    <a href="/laporan/delete/<?= $row['id_laporan'] ?>"
                                        onclick="return confirm('Hapus data?')" class="btn btn-sm btn-danger"><i
                                            class="fas fa-trash"></i></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</div>
<?= $this->endSection() ?>