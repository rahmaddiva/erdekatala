<?php

namespace App\Models;

use CodeIgniter\Model;

class ApiKeyModel extends Model
{
    protected $table          = 'api_keys';
    protected $primaryKey     = 'id';
    protected $returnType     = 'array';
    protected $useSoftDeletes = false;
    protected $useTimestamps  = true;

    protected $allowedFields = [
        'api_key', 'name', 'email', 'is_active', 'rate_limit',
        'created_by', 'last_used_at',
    ];

    // Validasi ditangani di controller, bukan di model
    protected $validationRules = [];

    public function generateKey(): string
    {
        return bin2hex(random_bytes(32)); // 64-char hex
    }

    // Validasi key: cek aktif, lalu rate limit harian via cache file
    public function validateKey(string $keyValue): ?array
    {
        // Sanitasi: hanya hex string yang valid
        if (!preg_match('/^[a-f0-9]{32,64}$/', $keyValue)) {
            return null;
        }

        $key = $this->where('api_key', $keyValue)
                    ->where('is_active', 1)
                    ->first();

        if (!$key) {
            return null;
        }

        // Rate limiting harian via file cache (karena tabel tidak punya counter)
        $today     = date('Ymd');
        $cacheFile = WRITEPATH . 'cache/api_rl_' . md5($keyValue) . '_' . $today . '.txt';
        $count     = file_exists($cacheFile) ? (int) file_get_contents($cacheFile) : 0;

        if ($count >= (int) $key['rate_limit']) {
            $key['_rate_exceeded'] = true;
            $key['_requests_today'] = $count;
            return $key; // kembalikan dengan flag exceeded
        }

        $key['_requests_today'] = $count;
        return $key;
    }

    public function incrementUsage(int $id, string $keyValue): void
    {
        // Update last_used_at di DB
        $this->db->query('UPDATE api_keys SET last_used_at = NOW() WHERE id = ?', [$id]);

        // Increment file counter
        $today     = date('Ymd');
        $cacheFile = WRITEPATH . 'cache/api_rl_' . md5($keyValue) . '_' . $today . '.txt';
        $count     = file_exists($cacheFile) ? (int) file_get_contents($cacheFile) : 0;
        file_put_contents($cacheFile, $count + 1, LOCK_EX);
    }
}
