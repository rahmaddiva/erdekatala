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
                    <div class="col-sm-12 text-right mb-2">
                        <a href="/laporan/export" class="btn btn-success">
                            <i class="fas fa-file-excel"></i> Export ke Excel
                        </a>
                    </div>
                    <?php if (session()->get('role') == 'admin_kecamatan'): ?>
                        <div class="card card-outline card-info shadow-sm mb-3">
                            <div class="card-body">
                                <form action="/laporan" method="get">
                                    <div class="row align-items-end">
                                        <div class="col-md-4">
                                            <label>Filter Berdasarkan Desa:</label>
                                            <select name="id_desa" class="form-control select2bs4">
                                                <option value="">-- Tampilkan Semua Desa --</option>
                                                <?php foreach ($list_desa as $d): ?>
                                                    <option value="<?= $d['id_desa'] ?>" <?= ($filter_desa == $d['id_desa']) ? 'selected' : '' ?>>
                                                        <?= $d['nama_desa'] ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-filter"></i> Terapkan Filter
                                            </button>
                                            <a href="/laporan" class="btn btn-secondary">
                                                <i class="fas fa-sync"></i> Reset
                                            </a>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    <?php endif; ?>
                    <table id="tableLaporanServerSide" class="table table-bordered table-striped table-hover w-100">
                        <thead>
                            <tr class="bg-light">
                                <th width="5%">No</th>
                                <th>Periode</th>
                                <?php if (session()->get('role') == 'admin_kecamatan'): ?>
                                    <th>Desa</th>
                                <?php endif; ?>
                                <th>Dusun</th>
                                <th>RT</th>
                                <th>Total Jiwa</th>
                                <th>Total KK</th>
                                <th width="10%">Aksi</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
                    <script>
                        $(document).ready(function () {
                            const role = "<?= session()->get('role') ?>";
                            const table = $('#tableLaporanServerSide').DataTable({
                                "processing": true,
                                "serverSide": true,
                                "ajax": {
                                    "url": "<?= base_url('laporan') ?>", // Mengarah ke method index
                                    "type": "POST",
                                    "data": function (d) {
                                        d.id_desa = $('select[name="id_desa"]').val();
                                        d.<?= csrf_token() ?> = "<?= csrf_hash() ?>"; // Jika CSRF aktif
                                    }
                                },
                                "columnDefs": [
                                    { "targets": [0, -1], "orderable": false, "className": "text-center" },
                                ],
                                "language": {
                                    "processing": '<i class="fa fa-spinner fa-spin fa-3x fa-fw"></i><span class="sr-only">Loading...</span> '
                                }
                            });

                            // Refresh tabel saat filter desa diubah (untuk Admin Kecamatan)
                            $('select[name="id_desa"]').on('change', function () {
                                table.draw();
                            });
                        });

                        function confirmDelete(id) {
                            if (confirm('Hapus data laporan ini?')) {
                                window.location.href = '/laporan/delete/' + id;
                            }
                        }
                    </script>
                </div>
            </div>
        </div>
    </section>
</div>

<?= $this->endSection() ?>