<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use App\Models\ApiKeyModel;

class ApiKeyAuth implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $keyValue = null;

        // Prioritaskan header Bearer (lebih aman dari query param)
        $authHeader = $request->getHeaderLine('Authorization');
        if (!empty($authHeader) && str_starts_with($authHeader, 'Bearer ')) {
            $keyValue = trim(substr($authHeader, 7));
        }

        // Fallback ke query param
        if (empty($keyValue)) {
            $keyValue = $request->getGet('api_key');
        }

        if (empty($keyValue)) {
            return $this->errorResponse(401, 'API key diperlukan. Gunakan header Authorization: Bearer <key> atau query param ?api_key=<key>.');
        }

        $model = new ApiKeyModel();
        $key   = $model->validateKey($keyValue);

        if ($key === null) {
            return $this->errorResponse(401, 'API key tidak valid atau tidak aktif.');
        }

        // Cek rate limit
        if (!empty($key['_rate_exceeded'])) {
            return $this->errorResponse(429, 'Batas request harian tercapai (' . $key['rate_limit'] . ' request/hari). Reset besok.', [
                'limit'     => $key['rate_limit'],
                'used'      => $key['_requests_today'],
                'reset'     => date('Y-m-d', strtotime('tomorrow')),
            ]);
        }

        // Increment usage
        $model->incrementUsage($key['id']);
        $request->apiKey = $key;

        return $request;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // CORS headers agar API bisa diakses dari domain lain
        $response->setHeader('Access-Control-Allow-Origin', '*');
        $response->setHeader('Access-Control-Allow-Methods', 'GET, OPTIONS');
        $response->setHeader('Access-Control-Allow-Headers', 'Authorization, Content-Type, X-Requested-With');
        $response->setHeader('X-Content-Type-Options', 'nosniff');
        $response->setHeader('X-Frame-Options', 'DENY');

        // Rate limit info headers
        if (isset($request->apiKey)) {
            $key      = $request->apiKey;
            $today    = date('Ymd');
            // cache file menggunakan hash dari api_key (yang sudah di-hash di DB)
            $cache = WRITEPATH . 'cache/api_rl_' . md5($key['api_key']) . '_' . $today . '.txt';
            $used  = file_exists($cache) ? (int) file_get_contents($cache) : 0;

            $response->setHeader('X-RateLimit-Limit',     $key['rate_limit']);
            $response->setHeader('X-RateLimit-Remaining', max(0, $key['rate_limit'] - $used));
            $response->setHeader('X-RateLimit-Reset',     date('Y-m-d', strtotime('tomorrow')));
        }
    }

    private function errorResponse(int $code, string $message, array $extra = [])
    {
        $body = array_merge(['status' => 'error', 'code' => $code, 'message' => $message], $extra);
        $response = service('response');
        $response->setHeader('Access-Control-Allow-Origin', '*');
        return $response->setStatusCode($code)->setJSON($body);
    }
}
