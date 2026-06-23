<?= $this->extend('templates/main') ?>
<?= $this->section('content') ?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0"><i class="fas fa-chart-line mr-2"></i><?= $title ?></h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="#">Home</a></li>
                        <li class="breadcrumb-item active">Dashboard</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">

            <!-- Filter Card dengan Chained Dropdown -->
            <div class="card card-outline card-primary shadow-sm mb-4">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-filter mr-2"></i>Filter Wilayah</h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-tool" data-card-widget="collapse">
                            <i class="fas fa-minus"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <form action="/dashboard" method="get" id="filterForm">
                        <div class="row">
                            <?php if (session()->get('role') == 'admin_dinas'): ?>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label><i class="fas fa-map-marked-alt mr-1"></i>Kecamatan</label>
                                            <select name="id_kecamatan" id="filter_kecamatan" class="form-control select2bs4">
                                                <option value="">-- Semua Kecamatan --</option>
                                                <?php foreach ($list_kecamatan as $k): ?>
                                                        <option value="<?= $k['id_kecamatan'] ?>" <?= ($filter_kec == $k['id_kecamatan']) ? 'selected' : '' ?>>
                                                            <?= $k['nama_kecamatan'] ?>
                                                        </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                            <?php endif; ?>

                            <?php if (session()->get('role') == 'admin_dinas' || session()->get('role') == 'admin_kecamatan'): ?>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label><i class="fas fa-map-marker-alt mr-1"></i>Desa</label>
                                            <select name="id_desa" id="filter_desa" class="form-control select2bs4">
                                                <option value="">-- Semua Desa --</option>
                                                <?php foreach ($list_desa as $d): ?>
                                                        <option value="<?= $d['id_desa'] ?>" <?= ($filter_desa == $d['id_desa']) ? 'selected' : '' ?>>
                                                            <?= $d['nama_desa'] ?>
                                                        </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                            <?php endif; ?>

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label><i class="fas fa-home mr-1"></i>Dusun</label>
                                    <select name="id_dusun" id="filter_dusun" class="form-control select2bs4">
                                        <option value="">-- Semua Dusun --</option>
                                        <?php foreach ($list_dusun as $dus): ?>
                                                <option value="<?= $dus['id_dusun'] ?>" <?= ($filter_dusun == $dus['id_dusun']) ? 'selected' : '' ?>>
                                                    <?= $dus['nama_dusun'] ?>
                                                </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-2">
                                <div class="form-group">
                                    <label><i class="fas fa-users mr-1"></i>RT</label>
                                    <select name="id_rt" id="filter_rt" class="form-control select2bs4">
                                        <option value="">-- Semua RT --</option>
                                        <?php foreach ($list_rt as $rt): ?>
                                                <option value="<?= $rt['id_rt'] ?>" <?= ($filter_rt == $rt['id_rt']) ? 'selected' : '' ?>>
                                                    RT <?= $rt['no_rt'] ?>
                                                </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-1">
                                <div class="form-group">
                                    <label>&nbsp;</label>
                                    <button type="submit" class="btn btn-primary btn-block">
                                        <i class="fas fa-search"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Info Boxes - Statistik Utama -->
            <div class="row">
                <div class="col-12 col-sm-6 col-md-3">
                    <div class="info-box shadow-sm">
                        <span class="info-box-icon bg-gradient-primary elevation-1">
                            <i class="fas fa-users"></i>
                        </span>
                        <div class="info-box-content">
                            <span class="info-box-text">Total Penduduk</span>
                            <span class="info-box-number">
                                <?= number_format($totals['jiwa_l'] + $totals['jiwa_p']) ?>
                            </span>
                            <small class="text-muted">
                                <i class="fas fa-male text-info"></i> <?= number_format($totals['jiwa_l']) ?> | 
                                <i class="fas fa-female text-danger"></i> <?= number_format($totals['jiwa_p']) ?>
                            </small>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-sm-6 col-md-3">
                    <div class="info-box shadow-sm">
                        <span class="info-box-icon bg-gradient-success elevation-1">
                            <i class="fas fa-home"></i>
                        </span>
                        <div class="info-box-content">
                            <span class="info-box-text">Total Kepala Keluarga</span>
                            <span class="info-box-number">
                                <?= number_format($totals['kk_l'] + $totals['kk_p']) ?>
                            </span>
                            <small class="text-muted">
                                KK Laki: <?= number_format($totals['kk_l']) ?> | 
                                KK Perempuan: <?= number_format($totals['kk_p']) ?>
                            </small>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-sm-6 col-md-3">
                    <div class="info-box shadow-sm">
                        <span class="info-box-icon bg-gradient-warning elevation-1">
                            <i class="fas fa-baby"></i>
                        </span>
                        <div class="info-box-content">
                            <span class="info-box-text">Balita (0-5 Tahun)</span>
                            <span class="info-box-number">
                                <?= number_format($totals['balita']) ?>
                            </span>
                            <small class="text-muted">Anak usia 0-5 tahun</small>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-sm-6 col-md-3">
                    <div class="info-box shadow-sm">
                        <span class="info-box-icon bg-gradient-danger elevation-1">
                            <i class="fas fa-heart"></i>
                        </span>
                        <div class="info-box-content">
                            <span class="info-box-text">Pasangan Usia Subur</span>
                            <span class="info-box-number">
                                <?= number_format($totals['pus']) ?>
                            </span>
                            <small class="text-muted">PUS aktif</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabel Ringkasan per Wilayah -->
            <?php if (!empty($data_summary)): ?>
                <div class="card card-outline card-info shadow-sm">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-table mr-2"></i>
                            Ringkasan Data 
                            <?php if ($filter_desa): ?>
                                    Per Dusun
                            <?php elseif ($filter_kec): ?>
                                    Per Desa
                            <?php else: ?>
                                    Per Kecamatan
                            <?php endif; ?>
                        </h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover m-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th>Wilayah</th>
                                        <th class="text-center">Total KK</th>
                                        <th class="text-center">Total Jiwa</th>
                                        <th class="text-center">Laki-laki</th>
                                        <th class="text-center">Perempuan</th>
                                        <th class="text-center">Balita</th>
                                        <th class="text-center">PUS</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($data_summary as $s): ?>
                                            <tr>
                                                <td>
                                                    <strong>
                                                        <?= $s['nama_kecamatan'] ?? $s['nama_desa'] ?? $s['nama_dusun'] ?>
                                                    </strong>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge badge-success">
                                                        <?= number_format($s['total_kk']) ?>
                                                    </span>
                                                </td>
                                                <td class="text-center">
                                                    <strong><?= number_format($s['total_jiwa']) ?></strong>
                                                </td>
                                                <td class="text-center">
                                                    <i class="fas fa-male text-info"></i> 
                                                    <?= number_format($s['total_jiwa_l']) ?>
                                                </td>
                                                <td class="text-center">
                                                    <i class="fas fa-female text-danger"></i> 
                                                    <?= number_format($s['total_jiwa_p']) ?>
                                                </td>
                                                <td class="text-center">
                                                    <?= number_format($s['total_balita']) ?>
                                                </td>
                                                <td class="text-center">
                                                    <?= number_format($s['total_pus']) ?>
                                                </td>
                                            </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Grafik Statistik -->
            <div class="row">
                <!-- Piramida Penduduk -->
                <div class="col-md-12">
                    <div class="card card-danger shadow-sm">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-chart-bar mr-2"></i>Piramida Penduduk</h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                    <i class="fas fa-minus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div id="chartPiramida"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Tingkat Pendidikan -->
                <div class="col-md-6">
                    <div class="card card-info shadow-sm">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-graduation-cap mr-2"></i>Tingkat Pendidikan</h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                    <i class="fas fa-minus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div id="chartPendidikan"></div>
                        </div>
                    </div>
                </div>

                <!-- Status Perkawinan -->
                <div class="col-md-6">
                    <div class="card card-warning shadow-sm">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-ring mr-2"></i>Status Perkawinan</h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                    <i class="fas fa-minus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div id="chartKawin"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Jenis Pekerjaan -->
                <div class="col-md-12">
                    <div class="card card-success shadow-sm">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-briefcase mr-2"></i>Jenis Pekerjaan</h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                    <i class="fas fa-minus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div id="chartPekerjaan"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-8">
                    <div class="card card-outline card-primary shadow">
                        <div class="card-header">
                            <h3 class="card-title">Statistik Jiwa & Kepala Keluarga (Gender)</h3>
                        </div>
                        <div class="card-body">
                            <div id="chartGender"></div>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card card-outline card-success shadow">
                        <div class="card-header">
                            <h3 class="card-title">Capaian Dokumen Adminduk</h3>
                        </div>
                        <div class="card-body">
                            <div id="chartDokumen"></div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card card-outline card-info shadow">
                        <div class="card-header">
                            <h3 class="card-title">Distribusi JKN / BPJS</h3>
                        </div>
                        <div class="card-body">
                            <div id="chartBPJS"></div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </section>
</div>

<script src="https://cdn.zingchart.com/zingchart.min.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function () {
    // --- ZingChart theme helpers ---
    const C = {
        blue:   '#3b82f6',
        pink:   '#ec4899',
        green:  '#10b981',
        amber:  '#f59e0b',
        red:    '#ef4444',
        cyan:   '#06b6d4',
        teal:   '#14b8a6',
        purple: '#8b5cf6',
        indigo: '#6366f1'
    };
    const BG   = '#1e2a38';
    const TEXT = '#e2e8f0';
    const GRID = '#2d3748';
    const FONT = { 'font-family': 'Source Sans Pro, sans-serif', 'font-size': '12px', 'font-color': TEXT };
    const TIP  = { 'font-color': '#fff', 'background-color': '#2d3748', 'border-radius': '4px', padding: '6px' };

    // ============================================================
    // 1. PIRAMIDA PENDUDUK — hbar stacked, L negatif / P positif
    // ============================================================
    const maleRaw    = <?= json_encode(array_map('intval', $piramidaL)) ?>;
    const femaleData = <?= json_encode(array_map('intval', $piramidaP)) ?>;
    const maleNeg    = maleRaw.map(v => -v);
    const ageLabels  = <?= json_encode($ageLabels) ?>;
    zingchart.render({
        id: 'chartPiramida',
        height: 530,
        data: {
            type: 'hbar',
            'background-color': BG,
            stacked: true,
            title: { visible: false },
            legend: {
                layout: 'x2', 'background-color': 'none', 'border-width': 0,
                item: Object.assign({}, FONT)
            },
            plot: {
                animation: { effect: 2, speed: 600 },
                'bar-width': '82%',
                tooltip: Object.assign({ text: '%t Umur %scale-key-text: %node-value orang' }, TIP)
            },
            'scale-x': { values: ageLabels, item: Object.assign({}, FONT), guide: { 'line-color': GRID } },
            'scale-y': {
                label: { text: 'Jumlah Penduduk', 'font-color': TEXT },
                item: Object.assign({}, FONT),
                guide: { 'line-color': GRID },
                format: '%v'
            },
            series: [
                { text: 'Laki-laki',  values: maleNeg,    'background-color': C.blue,  'legend-marker': { 'background-color': C.blue } },
                { text: 'Perempuan', values: femaleData, 'background-color': C.pink,  'legend-marker': { 'background-color': C.pink } }
            ]
        }
    });

    // ============================================================
    // 2. TINGKAT PENDIDIKAN KK — pie chart
    // ============================================================
    const pendLabels = <?= json_encode(array_keys($pendidikan)) ?>;
    const pendValues = <?= json_encode(array_values($pendidikan)) ?>;
    const pendColors = [C.indigo, C.purple, C.pink, C.amber, C.green, C.blue, C.cyan];
    zingchart.render({
        id: 'chartPendidikan',
        height: 380,
        data: {
            type: 'pie',
            'background-color': BG,
            title: { visible: false },
            plot: {
                'value-box': { text: '%npv%', 'font-size': '11px', 'font-color': '#fff', placement: 'out' },
                tooltip: Object.assign({ text: '%t\n%v KK (%npv%)' }, TIP),
                animation: { effect: 4, speed: 600 },
                slice: 70
            },
            legend: { layout: 'x3', 'background-color': 'none', 'border-width': 0, item: Object.assign({}, FONT) },
            series: pendValues.map((v, i) => ({
                text: pendLabels[i], values: [v],
                'background-color': pendColors[i % pendColors.length]
            }))
        }
    });

    // ============================================================
    // 3. STATUS PERKAWINAN KK — ring (donut) chart
    // ============================================================
    const kawinLabels = <?= json_encode(array_keys($status_kawin)) ?>;
    const kawinValues = <?= json_encode(array_values($status_kawin)) ?>;
    const kawinColors = [C.green, C.blue, C.amber, C.red];
    zingchart.render({
        id: 'chartKawin',
        height: 380,
        data: {
            type: 'ring',
            'background-color': BG,
            title: { visible: false },
            plot: {
                'value-box': { text: '%npv%', 'font-size': '11px', 'font-color': '#fff', placement: 'out' },
                tooltip: Object.assign({ text: '%t\n%v KK (%npv%)' }, TIP),
                animation: { effect: 4, speed: 600 },
                slice: 60
            },
            legend: { layout: 'x2', 'background-color': 'none', 'border-width': 0, item: Object.assign({}, FONT) },
            series: kawinValues.map((v, i) => ({
                text: kawinLabels[i], values: [v],
                'background-color': kawinColors[i % kawinColors.length]
            }))
        }
    });

    // ============================================================
    // 4. JENIS PEKERJAAN KK — hbar chart
    // ============================================================
    const pkLabels = <?= json_encode(array_keys($pekerjaan)) ?>;
    const pkValues = <?= json_encode(array_values($pekerjaan)) ?>;
    zingchart.render({
        id: 'chartPekerjaan',
        height: 400,
        data: {
            type: 'hbar',
            'background-color': BG,
            title: { visible: false },
            plot: {
                'background-color': C.green,
                'border-radius': '3px',
                animation: { effect: 2, speed: 500 },
                'value-box': { text: '%v KK', 'font-size': '11px', 'font-color': TEXT, placement: 'top-out' },
                tooltip: Object.assign({ text: '%t: %v KK' }, TIP)
            },
            'scale-x': { values: pkLabels, item: Object.assign({}, FONT), guide: { 'line-color': GRID } },
            'scale-y': { label: { text: 'Jumlah KK', 'font-color': TEXT }, item: Object.assign({}, FONT), guide: { 'line-color': GRID } },
            series: [{ text: 'Jumlah KK', values: pkValues }]
        }
    });

    // ============================================================
    // 5. GENDER JIWA & KK — grouped bar vertikal
    // ============================================================
    zingchart.render({
        id: 'chartGender',
        height: 350,
        data: {
            type: 'bar',
            'background-color': BG,
            title: { visible: false },
            plot: {
                'bar-width': '40%',
                'border-radius': '4px',
                animation: { effect: 5, speed: 500 },
                'value-box': { text: '%v', 'font-size': '11px', 'font-color': TEXT, placement: 'top' },
                tooltip: Object.assign({ text: '%t: %v orang' }, TIP)
            },
            'scale-x': { values: ['Jumlah Jiwa', 'Jumlah KK'], item: Object.assign({}, FONT), guide: { 'line-color': GRID } },
            'scale-y': { label: { text: 'Jumlah', 'font-color': TEXT }, item: Object.assign({}, FONT), guide: { 'line-color': GRID } },
            legend: { layout: 'x2', 'background-color': 'none', 'border-width': 0, item: Object.assign({}, FONT) },
            series: [
                { text: 'Laki-laki',  values: [<?= intval($grafik['gender']['jiwa_l']) ?>, <?= intval($grafik['gender']['kk_l']) ?>], 'background-color': C.blue, 'legend-marker': { 'background-color': C.blue } },
                { text: 'Perempuan', values: [<?= intval($grafik['gender']['jiwa_p']) ?>, <?= intval($grafik['gender']['kk_p']) ?>], 'background-color': C.pink, 'legend-marker': { 'background-color': C.pink } }
            ]
        }
    });

    // ============================================================
    // 6. DOKUMEN ADMINDUK — hbar chart
    // ============================================================
    zingchart.render({
        id: 'chartDokumen',
        height: 350,
        data: {
            type: 'hbar',
            'background-color': BG,
            title: { visible: false },
            plot: {
                'background-color': C.teal,
                'border-radius': '3px',
                animation: { effect: 2, speed: 500 },
                'value-box': { text: '%v', 'font-size': '11px', 'font-color': TEXT, placement: 'top-out' },
                tooltip: Object.assign({ text: '%t: %v orang' }, TIP)
            },
            'scale-x': { values: ['KTP-el', 'Akta Lahir', 'Akta Nikah', 'KK Fisik'], item: Object.assign({}, FONT), guide: { 'line-color': GRID } },
            'scale-y': { label: { text: 'Jumlah Penduduk', 'font-color': TEXT }, item: Object.assign({}, FONT), guide: { 'line-color': GRID } },
            series: [{ text: 'Pemilik Dokumen', values: [<?= intval($grafik['dokumen']['ktp_elektronik']) ?>, <?= intval($grafik['dokumen']['akta_lahir']) ?>, <?= intval($grafik['dokumen']['akta_nikah']) ?>, <?= intval($grafik['dokumen']['kk_fisik']) ?>] }]
        }
    });

    // ============================================================
    // 7. JKN / BPJS — ring chart
    // ============================================================
    zingchart.render({
        id: 'chartBPJS',
        height: 350,
        data: {
            type: 'ring',
            'background-color': BG,
            title: { visible: false },
            plot: {
                'value-box': { text: '%npv%', 'font-size': '11px', 'font-color': '#fff', placement: 'out' },
                tooltip: Object.assign({ text: '%t\n%v orang (%npv%)' }, TIP),
                animation: { effect: 4, speed: 600 },
                slice: 65
            },
            legend: { layout: 'x1', 'background-color': 'none', 'border-width': 0, item: Object.assign({}, FONT) },
            series: [
                { text: 'BPJS PBI (Bantuan)',     values: [<?= intval($grafik['bpjs']['pbi']) ?>],     'background-color': C.cyan },
                { text: 'BPJS Non-PBI (Mandiri)', values: [<?= intval($grafik['bpjs']['non_pbi']) ?>], 'background-color': C.teal },
                { text: 'Tidak Ber-JKN',           values: [<?= intval($grafik['bpjs']['non_jkn']) ?>], 'background-color': C.amber }
            ]
        }
    });

    // Chained Dropdown Logic
    <?php if (session()->get('role') == 'admin_dinas'): ?>
        $('#filter_kecamatan').change(function() {
            const idKecamatan = $(this).val();
            $('#filter_desa').html('<option value="">-- Semua Desa --</option>');
            $('#filter_dusun').html('<option value="">-- Semua Dusun --</option>');
            $('#filter_rt').html('<option value="">-- Semua RT --</option>');
        
            if (idKecamatan) {
                $.get('/dashboard/getDesaByKecamatan/' + idKecamatan, function(data) {
                    data.forEach(function(desa) {
                        $('#filter_desa').append(`<option value="${desa.id_desa}">${desa.nama_desa}</option>`);
                    });
                });
            }
        });
    <?php endif; ?>

    $('#filter_desa').change(function() {
        const idDesa = $(this).val();
        $('#filter_dusun').html('<option value="">-- Semua Dusun --</option>');
        $('#filter_rt').html('<option value="">-- Semua RT --</option>');
        
        if (idDesa) {
            $.get('/dashboard/getDusunByDesa/' + idDesa, function(data) {
                data.forEach(function(dusun) {
                    $('#filter_dusun').append(`<option value="${dusun.id_dusun}">${dusun.nama_dusun}</option>`);
                });
            });
        }
    });

    $('#filter_dusun').change(function() {
        const idDusun = $(this).val();
        $('#filter_rt').html('<option value="">-- Semua RT --</option>');
        
        if (idDusun) {
            $.get('/dashboard/getRtByDusun/' + idDusun, function(data) {
                data.forEach(function(rt) {
                    $('#filter_rt').append(`<option value="${rt.id_rt}">RT ${rt.no_rt}</option>`);
                });
            });
        }
    });
});
</script>

<?= $this->endSection() ?>