<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API Key Berhasil Dibuat</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #0f3460; min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .success-card { background: #16213e; border-radius: 12px; padding: 40px; max-width: 600px; width: 100%; box-shadow: 0 8px 32px rgba(0,0,0,0.4); }
        .icon-success { color: #00d084; font-size: 3.5rem; text-align: center; margin-bottom: 16px; }
        h2 { color: #fff; text-align: center; font-size: 1.6rem; margin-bottom: 8px; }
        .sub { color: rgba(255,255,255,0.6); text-align: center; font-size: 0.9rem; margin-bottom: 28px; }
        .key-box { background: #1a1a2e; border: 2px solid #e94560; border-radius: 8px; padding: 16px; margin-bottom: 20px; position: relative; }
        .key-label { color: rgba(255,255,255,0.5); font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 6px; }
        .key-value { color: #00d084; font-family: 'Courier New', monospace; font-size: 0.95rem; word-break: break-all; line-height: 1.5; }
        .btn-copy { position: absolute; top: 12px; right: 12px; background: #e94560; border: none; color: #fff; padding: 6px 14px; border-radius: 4px; font-size: 0.8rem; cursor: pointer; }
        .btn-copy:hover { background: #c73652; }
        .alert-warning { background: rgba(255,193,7,0.1); border: 1px solid rgba(255,193,7,0.3); color: rgba(255,255,255,0.8); font-size: 0.85rem; }
        .btn-docs { background: #e94560; border: none; color: #fff; font-weight: 600; padding: 10px 28px; border-radius: 6px; width: 100%; margin-top: 12px; }
        .btn-docs:hover { background: #c73652; color: #fff; }
        .back-link { color: rgba(255,255,255,0.5); font-size: 0.85rem; display: block; text-align: center; margin-top: 18px; }
        .back-link:hover { color: #fff; }
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

    <div style="background: rgba(233,69,96,0.08); border-radius: 6px; padding: 14px; margin-bottom: 20px; font-size: 0.85rem; color: rgba(255,255,255,0.7);">
        <strong style="color: #fff;">Cara Penggunaan:</strong><br>
        <code style="background: #1a1a2e; padding: 2px 6px; border-radius: 3px; color: #00d084;">Authorization: Bearer <?= substr(esc($key_value), 0, 20) ?>...</code><br>
        atau query param: <code style="background: #1a1a2e; padding: 2px 6px; border-radius: 3px; color: #00d084;">?api_key=YOUR_KEY</code>
    </div>

    <a href="/api/docs" class="btn btn-docs btn-block">
        <i class="fas fa-book mr-2"></i> Lihat Dokumentasi & Coba API
    </a>

    <a href="/" class="back-link"><i class="fas fa-home mr-1"></i> Kembali ke Beranda</a>
</div>

<script>
    localStorage.setItem('erdekatala_api_key', '<?= esc($key_value) ?>');
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
