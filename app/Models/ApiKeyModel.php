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
        'owner_name', 'owner_org',
    ];

    protected $validationRules = [];

    // Generate plaintext key (64-char hex) -- caller hashes before storing
    public function generateKey(): string
    {
        return bin2hex(random_bytes(32));
    }

    // Hash key for storage/lookup -- use SHA-256
    public function hashKey(string $plaintext): string
    {
        return hash('sha256', $plaintext);
    }

    // Validate key: lookup by hash, check active, then atomic rate limit
    public function validateKey(string $keyValue): ?array
    {
        // Sanitasi format
        if (!preg_match('/^[a-f0-9]{32,64}$/', $keyValue)) {
            return null;
        }

        $keyHash = $this->hashKey($keyValue);

        $key = $this->where('api_key', $keyHash)
                    ->where('is_active', 1)
                    ->first();

        if (!$key) {
            return null;
        }

        // Atomic rate limit check + increment via exclusive file lock
        $today     = date('Ymd');
        $cacheFile = WRITEPATH . 'cache/api_rl_' . md5($keyHash) . '_' . $today . '.txt';

        $fp = fopen($cacheFile, 'c+');
        if (!$fp) {
            return $key; // jika file tidak bisa dibuka, izinkan request
        }

        flock($fp, LOCK_EX);
        $count = (int) stream_get_contents($fp);

        if ($count >= (int) $key['rate_limit']) {
            flock($fp, LOCK_UN);
            fclose($fp);
            $key['_rate_exceeded']  = true;
            $key['_requests_today'] = $count;
            return $key;
        }

        // Increment atomically
        ftruncate($fp, 0);
        rewind($fp);
        fwrite($fp, $count + 1);
        flock($fp, LOCK_UN);
        fclose($fp);

        $key['_requests_today'] = $count + 1;
        return $key;
    }

    public function incrementUsage(int $id): void
    {
        $this->db->query('UPDATE api_keys SET last_used_at = NOW() WHERE id = ?', [$id]);
    }
}

