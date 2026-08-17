<?php if (empty($notifikasi)): ?>
    <span class="dropdown-item dropdown-header">Tidak ada notifikasi</span>
<?php else: ?>
    <span class="dropdown-item dropdown-header">Notifikasi</span>
    <?php foreach ($notifikasi as $n): ?>
        <div class="dropdown-item notif-item <?= $n['is_read'] ? '' : 'notif-unread' ?>" data-id="<?= $n['id'] ?>" data-url="<?= esc($n['url']) ?>" role="button">
            <i class="fas fa-bell mr-2 <?= $n['is_read'] ? 'text-secondary' : 'text-warning' ?>"></i>
            <div class="media-body">
                <h3 class="dropdown-item-title"><?= esc($n['judul']) ?></h3>
                <p class="text-sm mb-0"><?= esc($n['pesan']) ?></p>
                <p class="text-muted text-xs mb-0">
                    <?= esc(date('d M Y H:i', strtotime($n['created_at']))) ?>
                    <button type="button" class="notif-delete float-right btn btn-link btn-xs p-0 text-danger" data-id="<?= $n['id'] ?>" title="Hapus"><i class="fas fa-trash"></i></button>
                </p>
            </div>
        </div>
        <div class="dropdown-divider"></div>
    <?php endforeach; ?>
    <a href="#" class="dropdown-item dropdown-footer notif-read-all text-center">Tandai semua sudah dibaca</a>
<?php endif; ?>
