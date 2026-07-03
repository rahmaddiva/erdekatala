<?php

namespace App\Filters;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Filters\FilterInterface;
use App\Models\UserModel;

class LoggedIn implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $session = \Config\Services::session();

        if ($session->get('logged_in')) {
            return;
        }

        // Coba auto-login via remember_token cookie
        $token = get_cookie('remember_token');
        if ($token) {
            $userModel = new UserModel();
            $user = $userModel->where('remember_token', $token)->first();
            if ($user) {
                $session->regenerate(true);
                $session->set([
                    'id_user'      => $user['id_user'],
                    'id_kecamatan' => $user['id_kecamatan'],
                    'nama_lengkap' => $user['nama_lengkap'],
                    'id_desa'      => $user['id_desa'],
                    'username'     => $user['username'],
                    'role'         => $user['role'],
                    'logged_in'    => true,
                ]);
                return;
            }
        }

        return redirect()->to('/login');
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // No action required after the request
    }
}
