<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar API Key - Erdekatala</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #0f3460; min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .register-card { background: #16213e; border-radius: 12px; padding: 40px; max-width: 560px; width: 100%; box-shadow: 0 8px 32px rgba(0,0,0,0.4); }
        .register-card h2 { color: #fff; font-size: 1.5rem; margin-bottom: 6px; }
        .register-card p.sub { color: rgba(255,255,255,0.6); font-size: 0.9rem; margin-bottom: 28px; }
        .form-control { background: #1a1a2e; border-color: rgba(255,255,255,0.15); color: #fff; }
        .form-control:focus { background: #1a1a2e; border-color: #e94560; color: #fff; box-shadow: none; }
        .form-control::placeholder { color: rgba(255,255,255,0.3); }
        label { color: rgba(255,255,255,0.8); font-size: 0.88rem; }
        .btn-register { background: #e94560; border: none; color: #fff; font-weight: 600; padding: 10px 28px; border-radius: 6px; width: 100%; font-size: 1rem; }
        .btn-register:hover { background: #c73652; color: #fff; }
        .text-danger { font-size: 0.8rem; }
        .back-link { color: rgba(255,255,255,0.5); font-size: 0.85rem; display: block; text-align: center; margin-top: 18px; }
        .back-link:hover { color: #fff; }
        .info-box { background: rgba(233,69,96,0.1); border: 1px solid rgba(233,69,96,0.3); border-radius: 6px; padding: 12px 16px; margin-bottom: 24px; color: rgba(255,255,255,0.7); font-size: 0.85rem; }
    </style>
</head>
<body>
<div class="register-card">
    <h2><i class="fas fa-key mr-2" style="color:#e94560"></i> Daftar API Key</h2>
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
            <small style="color:rgba(255,255,255,0.4)">Nama pengingat untuk key ini</small>
        </div>

        <button type="submit" class="btn btn-register mt-2">
            <i class="fas fa-paper-plane mr-2"></i> Buat API Key Gratis
        </button>
    </form>

    <a href="/api/docs" class="back-link"><i class="fas fa-arrow-left mr-1"></i> Lihat Dokumentasi API</a>
</div>
</body>
</html>
