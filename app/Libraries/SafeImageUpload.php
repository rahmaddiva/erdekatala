<?php

namespace App\Libraries;

use CodeIgniter\HTTP\Files\UploadedFile;

/**
 * Validasi upload gambar dari konten asli (finfo), bukan dari nama file klien.
 * Nama hasil selalu diacak + ekstensi diturunkan dari MIME yang diizinkan.
 */
class SafeImageUpload
{
    private const MIME_EXT = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
    ];

    public static function safeName(UploadedFile $file): ?string
    {
        if (! $file->isValid() || $file->hasMoved()) {
            return null;
        }

        $ext = self::MIME_EXT[$file->getMimeType()] ?? null;
        if ($ext === null) {
            return null;
        }

        // Pastikan file benar-benar gambar yang bisa dibaca, bukan polyglot.
        if (@getimagesize($file->getTempName()) === false) {
            return null;
        }

        return bin2hex(random_bytes(16)) . '.' . $ext;
    }
}
