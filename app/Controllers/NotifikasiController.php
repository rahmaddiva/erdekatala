<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\NotifikasiModel;

class NotifikasiController extends BaseController
{
    protected $notifModel;

    public function __construct()
    {
        $this->notifModel = new NotifikasiModel();
    }

    public function list()
    {
        $idUser = session()->get('id_user');
        $data = [
            'count' => $this->notifModel->getUnreadCount($idUser),
            'html'  => view('notifikasi/partials/list', ['notifikasi' => $this->notifModel->getLatest($idUser)]),
        ];
        return $this->response->setJSON($data);
    }

    public function read($id)
    {
        $this->notifModel->markRead((int) $id, (int) session()->get('id_user'));
        return $this->response->setJSON(['status' => 'ok']);
    }

    public function read_all()
    {
        $this->notifModel->markAllRead((int) session()->get('id_user'));
        return $this->response->setJSON(['status' => 'ok']);
    }

    public function delete($id)
    {
        $this->notifModel->deleteNotif((int) $id, (int) session()->get('id_user'));
        return $this->response->setJSON(['status' => 'ok']);
    }
}
