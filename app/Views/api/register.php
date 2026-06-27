<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar API Key - Sikada Tala</title>
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
            --line:#e5e1d8; --muted:#6b7280;
        }
        *{box-sizing:border-box;}
        body{background:var(--paper);font-family:"Source Sans 3",sans-serif;color:var(--ink);min-height:100vh;display:flex;align-items:center;justify-content:center;padding:40px 20px;}
        h2,h3{font-family:"Libre Baskerville",serif;}

        .register-card{
            background:var(--paper-2);border:1px solid var(--line);border-radius:14px;
            padding:40px;max-width:560px;width:100%;
            box-shadow:0 4px 24px rgba(15,25,35,0.08);
        }
        .register-card .brand{display:flex;align-items:center;gap:12px;margin-bottom:8px;}
        .register-card .brand-mark{
            width:44px;height:44px;background:var(--primary);border-radius:8px;
            display:flex;align-items:center;justify-content:center;
            font-family:"Libre Baskerville",serif;font-weight:700;font-size:1.5rem;color:#fff;flex-shrink:0;
        }
        .register-card h2{color:var(--ink);font-size:1.5rem;margin:0;}
        .register-card p.sub{color:var(--muted);font-size:0.9rem;margin:4px 0 28px;font-weight:300;}
        .form-control{background:var(--paper);border:1px solid var(--line);color:var(--ink);border-radius:6px;padding:10px 14px;}
        .form-control:focus{background:#fff;border-color:var(--primary);color:var(--ink);box-shadow:0 0 0 3px rgba(221,72,20,0.12);}
        .form-control::placeholder{color:#a8a39b;}
        label{color:var(--ink);font-size:0.88rem;font-weight:600;margin-bottom:4px;}
        .btn-register{background:var(--primary);border:none;color:#fff;font-weight:600;padding:12px 28px;border-radius:6px;width:100%;font-size:1rem;transition:background .15s;}
        .btn-register:hover{background:var(--primary-d);color:#fff;}
        .text-danger{font-size:0.8rem;}
        .back-link{color:var(--muted);font-size:0.85rem;display:block;text-align:center;margin-top:18px;text-decoration:none;}
        .back-link:hover{color:var(--primary);}
        .info-box{background:rgba(221,72,20,0.06);border:1px solid rgba(221,72,20,0.25);border-radius:6px;padding:12px 16px;margin-bottom:24px;color:var(--ink);font-size:0.85rem;}
        .alert-danger{background:rgba(221,72,20,0.08);border:1px solid rgba(221,72,20,0.25);color:var(--ink);}
    </style>
</head>
<body>
<div class="register-card">
    <div class="brand">
        <div class="brand-mark">S</div>
        <h2>Daftar API Key</h2>
    </div>
    <p class="sub">Gratis. 1000 request/hari. Akses semua data agregat kependudukan.</p>

    <div class="info-box">
        <i class="fas fa-info-circle mr-1"></i>
        API key akan ditampilkan <strong>sekali saja</strong> setelah pendaftaran. Simpan dengan baik.
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger py-2" style="font-size:0.85rem;">
            <ul class="mb-0 pl-3">
                <?php foreach ($errors as $e): ?><li><?= esc($e) ?></li><?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="POST" action="/api/register">
        <?= csrf_field() ?>

        <div class="form-group">
            <label>Nama Lengkap *</label>
            <input type="text" name="owner_name" class="form-control" placeholder="Budi Santoso"
                   value="<?= esc($old['owner_name'] ?? '') ?>" required>
        </div>

        <div class="form-group">
            <label>Email *</label>
            <input type="email" name="owner_email" class="form-control" placeholder="email@contoh.com"
                   value="<?= esc($old['owner_email'] ?? '') ?>" required>
        </div>

        <div class="form-group">
            <label>Instansi / Organisasi</label>
            <input type="text" name="owner_org" class="form-control" placeholder="Universitas / Pemerintah / dll (opsional)"
                   value="<?= esc($old['owner_org'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label>Label API Key *</label>
            <input type="text" name="label" class="form-control" placeholder="Misal: Website Desa Kurau"
                   value="<?= esc($old['label'] ?? '') ?>" required>
            <small style="color:var(--muted)">Nama pengingat untuk key ini</small>
        </div>

        <button type="submit" class="btn btn-register mt-2">
            <i class="fas fa-paper-plane mr-2"></i> Buat API Key Gratis
        </button>
    </form>

    <a href="/api/docs" class="back-link"><i class="fas fa-arrow-left mr-1"></i> Lihat Dokumentasi API</a>
</div>
</body>
</html>
