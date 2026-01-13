<!DOCTYPE html>
<html>

<head>
    <style>
        body {
            font-family: 'Helvetica', sans-serif;
            font-size: 9pt;
            color: #333;
        }

        .header {
            text-align: center;
            border-bottom: 2px solid #444;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        th {
            background-color: #343a40;
            color: white;
            padding: 8px;
            border: 1px solid #dee2e6;
        }

        td {
            padding: 6px;
            border: 1px solid #dee2e6;
            text-align: center;
            word-wrap: break-word;
        }

        tr:nth-child(even) {
            background-color: #f8f9fa;
        }

        .footer {
            position: fixed;
            bottom: 0;
            width: 100%;
            text-align: right;
            font-size: 8pt;
        }
    </style>
</head>

<body>
    <div class="header">
        <h2 style="margin:0;">
            <?= $title ?>
        </h2>
        <p style="margin:5px 0;">Wilayah:
            <?= session()->get('role') == 'admin_desa' ? 'Desa ' . $laporan[0]['nama_desa'] : 'Kabupaten/Kecamatan' ?>
        </p>
        <small>Dicetak pada:
            <?= $tanggal ?>
        </small>
    </div>

    <table>
        <thead>
            <tr>
                <th width="30px">No</th>
                <th>Kecamatan</th>
                <th>Desa</th>
                <th>RT/Dusun</th>
                <th>Periode</th>
                <th>Total Jiwa</th>
                <th>Total KK</th>
                <th>BPJS (PBI/Non)</th>
            </tr>
        </thead>
        <tbody>
            <?php $i = 1;
            foreach ($laporan as $r): ?>
                <tr>
                    <td>
                        <?= $i++ ?>
                    </td>
                    <td>
                        <?= strtoupper($r['nama_kecamatan']) ?>
                    </td>
                    <td>
                        <?= strtoupper($r['nama_desa']) ?>
                    </td>
                    <td>RT
                        <?= $r['no_rt'] ?> (
                        <?= $r['nama_dusun'] ?>)
                    </td>
                    <td>
                        <?= $r['bulan'] ?>/
                        <?= $r['tahun'] ?>
                    </td>
                    <td>
                        <?= $r['jiwa_l'] + $r['jiwa_p'] ?>
                    </td>
                    <td>
                        <?= $r['kk_l'] + $r['kk_p'] ?>
                    </td>
                    <td>
                        <?= $r['pus_pbi'] ?> /
                        <?= $r['pus_non_pbi'] ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="footer">
        <!-- Nomor halaman akan ditambahkan oleh Dompdf -->
    </div>
</body>

</html>