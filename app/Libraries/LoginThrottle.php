<?php

namespace App\Libraries;

/**
 * Proteksi brute-force login: counter kegagalan per kombinasi username+IP,
 * disimpan sebagai file JSON ber-flock di writable/cache (pola yang sama
 * dengan rate-limit API key di ApiKeyModel::validateKey()).
 * Spec: docs/superpowers/specs/2026-08-22-validasi-konsistensi-dan-bruteforce-design.md
 */
class LoginThrottle
{
    public const MAX_ATTEMPTS   = 5;   // kegagalan sebelum terkunci
    public const WINDOW_SECONDS = 900; // jendela penghitungan (15 menit)
    public const LOCK_SECONDS   = 900; // durasi penguncian (15 menit)

    private string $cacheDir;

    public function __construct(?string $cacheDir = null)
    {
        $this->cacheDir = rtrim($cacheDir ?? WRITEPATH . 'cache', '/\\');
    }

    /**
     * Terkunci? Return sisa detik (> 0) jika ya, false jika tidak.
     */
    public function isLocked(string $username, string $ip): int|false
    {
        $state = $this->readState($this->path($username, $ip));
        if ($state === null) {
            return false;
        }
        $lockedUntil = (int) ($state['locked_until'] ?? 0);
        if ($lockedUntil > time()) {
            return $lockedUntil - time();
        }
        return false;
    }

    /**
     * Catat satu kegagalan login; otomatis mengunci saat mencapai MAX_ATTEMPTS.
     */
    public function registerFailure(string $username, string $ip): void
    {
        $path = $this->path($username, $ip);

        try {
            $fp = fopen($path, 'c+');
            if ($fp === false) {
                return; // cache tidak bisa ditulis → fail-open (login tetap jalan)
            }
            if (!flock($fp, LOCK_EX)) {
                fclose($fp);
                return; // gagal memperoleh lock → fail-open
            }

            // ---- read-modify-write dalam SATU LOCK_EX (pola ApiKeyModel::validateKey) ----
            $isi   = stream_get_contents($fp);
            $state = json_decode((string) $isi, true);
            $state = is_array($state) ? $state : null;
            $now   = time();

            $locked = $state !== null && (int) ($state['locked_until'] ?? 0) > $now;

            if ($state === null || (!$locked && ($now - (int) ($state['window_start'] ?? 0)) > self::WINDOW_SECONDS)) {
                $state = ['count' => 0, 'window_start' => $now, 'locked_until' => null];
            }

            $state['count'] = (int) $state['count'] + 1;

            if ($state['count'] >= self::MAX_ATTEMPTS) {
                $state['locked_until'] = $now + self::LOCK_SECONDS;
                $state['count']        = 0; // 5 percobaan baru setelah kunci lewat
            }

            ftruncate($fp, 0);
            rewind($fp);
            fwrite($fp, json_encode($state));
            fflush($fp);
            flock($fp, LOCK_UN);
            fclose($fp);
        } catch (\Throwable $e) {
            // fail-open: masalah I/O → kegagalan tidak dicatat, login tidak diblokir
        }
    }

    /**
     * Hapus counter (dipanggil setelah login sukses).
     */
    public function clear(string $username, string $ip): void
    {
        $path = $this->path($username, $ip);
        if (is_file($path)) {
            @unlink($path);
        }
    }

    private function path(string $username, string $ip): string
    {
        return $this->cacheDir . DIRECTORY_SEPARATOR
            . 'login_bf_' . hash('sha256', strtolower($username) . '|' . $ip) . '.json';
    }

    private function readState(string $path): ?array
    {
        try {
            if (!is_file($path)) {
                return null;
            }
            $fp = fopen($path, 'r');
            if ($fp === false) {
                return null;
            }
            flock($fp, LOCK_SH);
            $isi = stream_get_contents($fp);
            flock($fp, LOCK_UN);
            fclose($fp);

            $state = json_decode((string) $isi, true);
            return is_array($state) ? $state : null;
        } catch (\Throwable $e) {
            return null; // fail-open: tidak terbaca → anggap tidak terkunci
        }
    }
}
