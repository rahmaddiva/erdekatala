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
                        <div class="btn-group">
                            <button type="button" class="btn btn-success dropdown-toggle" data-toggle="dropdown">
                                <i class="fas fa-file-export mr-1"></i> Export Data
                            </button>
                            <div class="dropdown-menu dropdown-menu-right">
                                <button class="dropdown-item" type="button" onclick="doExport('excel')">
                                    <i class="fas fa-file-excel text-success mr-2"></i> File Excel (.xlsx)
                                </button>
                                <button class="dropdown-item" type="button" onclick="doExport('pdf')">
                                    <i class="fas fa-file-pdf text-danger mr-2"></i> File PDF (.pdf)
                                </button>
                            </div>
                        </div>
                    </div>

                    <script>
                        function doExport(format) {
                            // Ambil nilai dari filter yang ada di halaman
                            const idKec = $('#filter_kecamatan').val() || '';
                            const idDesa = $('#filter_desa').val() || $('select[name="id_desa"]').val() || '';

                            // Buat URL dengan query string
                            const url = `<?= base_url('laporan/export') ?>/${format}?id_kecamatan=${idKec}&id_desa=${idDesa}`;

                            // Arahkan browser untuk download
                            window.location.href = url;
                        }
                    </script>
                    <!-- admin kecamatan dan admin dinas -->
                    <?php if (in_array(session()->get('role'), ['admin_dinas', 'admin_kecamatan'])): ?>
                        <div class="card card-outline card-info shadow-sm mb-3">
                            <div class="card-body">
                                <div class="row">
                                    <?php if (session()->get('role') == 'admin_dinas'): ?>
                                        <div class="col-md-4">
                                            <label>Kecamatan:</label>
                                            <select id="filter_kecamatan" class="form-control select2bs4">
                                                <option value="">-- Semua Kecamatan --</option>
                                                <?php foreach ($list_kecamatan as $k): ?>
                                                    <option value="<?= $k['id_kecamatan'] ?>">
                                                        <?= strtoupper($k['nama_kecamatan']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    <?php endif; ?>

                                    <div class="col-md-4">
                                        <label>Desa:</label>
                                        <select id="filter_desa" class="form-control select2bs4"
                                            <?= (session()->get('role') == 'admin_dinas') ? 'disabled' : '' ?>>
                                            <option value="">-- Semua Desa --</option>
                                            <?php if (session()->get('role') == 'admin_kecamatan'): ?>
                                                <?php foreach ($list_desa as $d): ?>
                                                    <option value="<?= $d['id_desa'] ?>"><?= strtoupper($d['nama_desa']) ?></option>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </select>
                                    </div>
                                </div>
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
                            <!-- Search Row -->
                            <tr class="bg-white">
                                <th></th>
                                <th>
                                    <input type="text" class="form-control form-control-sm search-input"
                                        placeholder="Cari periode..." data-column="1">
                                </th>
                                <?php if (session()->get('role') == 'admin_kecamatan'): ?>
                                    <th>
                                        <input type="text" class="form-control form-control-sm search-input"
                                            placeholder="Cari desa..." data-column="2">
                                    </th>
                                <?php endif; ?>
                                <th>
                                    <input type="text" class="form-control form-control-sm search-input"
                                        placeholder="Cari dusun..."
                                        data-column="<?= (session()->get('role') == 'admin_kecamatan') ? '3' : '2' ?>">
                                </th>
                                <th>
                                    <input type="text" class="form-control form-control-sm search-input"
                                        placeholder="Cari RT..."
                                        data-column="<?= (session()->get('role') == 'admin_kecamatan') ? '4' : '3' ?>">
                                </th>
                                <th></th>
                                <th></th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
                    <script>
                        $(document).ready(function () {
                            const role = "<?= session()->get('role') ?>";

                            // Store search values
                            let searchValues = {};

                            const table = $('#tableLaporanServerSide').DataTable({
                                "processing": true,
                                "serverSide": true,
                                "ajax": {
                                    "url": "<?= base_url('laporan') ?>",
                                    "type": "POST",
                                    "data": function (d) {
                                        // Ambil nilai id_kecamatan jika ada (untuk Dinas)
                                        d.id_kecamatan = $('#filter_kecamatan').val();
                                        d.id_desa = $('#filter_desa').val() || $('select[name="id_desa"]').val();

                                        // Tambahkan multi-column search values
                                        d.search_periode = searchValues['periode'] || '';
                                        d.search_desa = searchValues['desa'] || '';
                                        d.search_dusun = searchValues['dusun'] || '';
                                        d.search_rt = searchValues['rt'] || '';

                                        d.<?= csrf_token() ?> = "<?= csrf_hash() ?>";
                                    }
                                },
                                "columnDefs": [
                                    { "targets": [0, -1], "orderable": false, "className": "text-center" },
                                ],
                                "language": {
                                    "processing": '<i class="fa fa-spinner fa-spin fa-3x fa-fw"></i><span class="sr-only">Loading...</span> '
                                }
                            });

                            // Multi-column search handler
                            $(document).on('keyup change', '.search-input', function () {
                                const column = $(this).data('column');
                                const value = $(this).val();

                                // Map column index to field name
                                const columnMap = {
                                    '1': 'periode',
                                    '2': 'desa',
                                    '3': 'dusun',
                                    '4': 'rt'
                                };

                                if (columnMap[column]) {
                                    searchValues[columnMap[column]] = value;
                                }

                                table.draw();
                            });

                            // 2. Logika Chained Dropdown (Kecamatan -> Desa)
                            $('#filter_kecamatan').on('change', function () {
                                const idKec = $(this).val();
                                const $desaSelect = $('#filter_desa');

                                if (idKec) {
                                    $desaSelect.prop('disabled', false).html('<option value="">Memuat...</option>');
                                    $.get('<?= base_url('dashboard/getDesaByKecamatan') ?>/' + idKec, function (res) {
                                        let html = '<option value="">-- Semua Desa --</option>';
                                        res.forEach(d => {
                                            html += `<option value="${d.id_desa}">${d.nama_desa.toUpperCase()}</option>`;
                                        });
                                        $desaSelect.html(html);
                                        table.draw(); // Refresh tabel setelah pilih kecamatan
                                    });
                                } else {
                                    $desaSelect.prop('disabled', true).html('<option value="">-- Semua Desa --</option>');
                                    table.draw();
                                }
                            });

                            // Trigger refresh saat Kecamatan berubah (Dinas)
                            $('#filter_kecamatan').on('change', function () {
                                table.draw();
                            });

                            // Trigger refresh saat Desa berubah (Dinas & Kecamatan)
                            $('#filter_desa, select[name="id_desa"]').on('change', function () {
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