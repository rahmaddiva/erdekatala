<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API Key Berhasil Dibuat</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Libre+Baskerville:wght@400;700&family=Source+Sans+3:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        :root{
            --ink:#0f1923; --ink-2:#1a2733;
            --primary:#dd4814; --primary-d:#b83a10;
            --paper:#f7f5f1; --paper-2:#ffffff;
            --line:#e5e1d8; --muted:#6b7280; --ok:#0f9d58;
        }
        *{box-sizing:border-box;}
        body{background:var(--paper);font-family:"Source Sans 3",sans-serif;color:var(--ink);min-height:100vh;display:flex;align-items:center;justify-content:center;padding:40px 20px;}
        h2{font-family:"Libre Baskerville",serif;}

        .success-card{background:var(--paper-2);border:1px solid var(--line);border-radius:14px;padding:40px;max-width:600px;width:100%;box-shadow:0 4px 24px rgba(15,25,35,0.08);}
        .icon-success{color:var(--ok);font-size:3.5rem;text-align:center;margin-bottom:16px;}
        h2{color:var(--ink);text-align:center;font-size:1.6rem;margin-bottom:8px;}
        .sub{color:var(--muted);text-align:center;font-size:0.9rem;margin-bottom:28px;font-weight:300;}
        .key-box{background:var(--paper);border:2px solid var(--primary);border-radius:8px;padding:16px;margin-bottom:20px;position:relative;}
        .key-label{color:var(--muted);font-size:0.75rem;text-transform:uppercase;letter-spacing:1px;margin-bottom:6px;}
        .key-value{color:var(--primary);font-family:'Courier New',monospace;font-size:0.95rem;word-break:break-all;line-height:1.5;}
        .btn-copy{position:absolute;top:12px;right:12px;background:var(--primary);border:none;color:#fff;padding:6px 14px;border-radius:4px;font-size:0.8rem;cursor:pointer;transition:background .15s;}
        .btn-copy:hover{background:var(--primary-d);}
        .usage-box{background:rgba(221,72,20,0.06);border:1px solid rgba(221,72,20,0.2);border-radius:6px;padding:14px;margin-bottom:20px;font-size:0.85rem;color:var(--ink);}
        .usage-box strong{color:var(--ink);}
        code{background:var(--paper);padding:2px 6px;border-radius:3px;color:var(--primary);font-family:'Courier New',monospace;}
        .alert-warning{background:rgba(221,72,20,0.08);border:1px solid rgba(221,72,20,0.25);color:var(--ink);font-size:0.85rem;}
        .btn-docs{background:var(--primary);border:none;color:#fff;font-weight:600;padding:12px 28px;border-radius:6px;width:100%;margin-top:12px;transition:background .15s;}
        .btn-docs:hover{background:var(--primary-d);color:#fff;}
        .back-link{color:var(--muted);font-size:0.85rem;display:block;text-align:center;margin-top:18px;text-decoration:none;}
        .back-link:hover{color:var(--primary);}
    </style>
</head>
<body>
<div class="success-card">
    <div class="icon-success"><i class="fas fa-check-circle"></i></div>
    <h2>API Key Berhasil Dibuat!</h2>
    <p class="sub">Simpan key ini dengan aman. Tidak akan ditampilkan lagi.</p>

    <div class="alert alert-warning">
        <i class="fas fa-exclamation-triangle mr-2"></i>
        <strong>Penting:</strong> Key ini hanya ditampilkan sekali. Segera salin dan simpan di tempat aman.
    </div>

    <div class="key-box">
        <button class="btn-copy" onclick="copyKey()"><i class="fas fa-copy mr-1"></i> Salin</button>
        <div class="key-label">API Key Anda</div>
        <div class="key-value" id="apiKey"><?= esc($key_value) ?></div>
    </div>

    <div class="usage-box">
        <strong>Cara Penggunaan:</strong><br>
        <code>Authorization: Bearer <?= substr(esc($key_value), 0, 20) ?>...</code><br>
        atau query param: <code>?api_key=YOUR_KEY</code>
    </div>

    <a href="/api/docs" class="btn btn-docs btn-block">
        <i class="fas fa-book mr-2"></i> Lihat Dokumentasi & Coba API
    </a>

    <a href="/" class="back-link"><i class="fas fa-home mr-1"></i> Kembali ke Beranda</a>
</div>

<script>
    function copyKey() {
        const key = document.getElementById('apiKey').textContent;
        navigator.clipboard.writeText(key).then(() => {
            const btn = document.querySelector('.btn-copy');
            btn.innerHTML = '<i class="fas fa-check mr-1"></i> Tersalin!';
            setTimeout(() => {
                btn.innerHTML = '<i class="fas fa-copy mr-1"></i> Salin';
            }, 2000);
        });
    }
</script>
</body>
</html>
