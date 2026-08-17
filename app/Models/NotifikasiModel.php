<?php

namespace App\Models;

use CodeIgniter\Model;

class NotifikasiModel extends Model
{
    protected $table = 'notifikasi';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $allowedFields = [
        'id_user',
        'judul',
        'pesan',
        'url',
        'jenis',
        'periode',
        'is_read',
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = '';

    private const BULAN = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

    public function generateReminder(int $idUser, string $role, ?int $idKecamatan = null, ?int $idDesa = null): void
    {
        $bulan  = (int) date('n');
        $tahun  = (int) date('Y');
        $periode = date('Y-m');
        $label  = self::BULAN[$bulan - 1] . ' ' . $tahun;
        $laporanModel = new LaporanAgregatModel();

        if ($role === 'admin_desa' && $idDesa) {
            $rows = array_values(array_filter($laporanModel->getStatusLaporDesa($bulan, $tahun), fn($r) => (int) $r['id_desa'] === (int) $idDesa));
            if ($rows && !$rows[0]['sudah_lapor']) {
                $this->insertIfNotExists($idUser, 'pengingat_laporan', $periode, "Belum lapor untuk $label", 'Desa Anda belum menginput laporan agregat bulan ini.', '/laporan/input');
            }
            return;
        }

        $idKec = $role === 'admin_kecamatan' ? $idKecamatan : null;
        $rows = $laporanModel->getStatusLaporDesa($bulan, $tahun, $idKec);
        $belum = count(array_filter($rows, fn($r) => !$r['sudah_lapor']));
        if (!$belum) {
            return;
        }
        $jenis = $role === 'admin_kecamatan' ? 'rekap_kecamatan' : 'rekap_dinas';
        $this->insertIfNotExists($idUser, $jenis, $periode, "$belum desa belum lapor", "$belum desa belum menginput laporan $label.", '/laporan');
    }

    public function getUnreadCount(int $idUser): int
    {
        return $this->where('id_user', $idUser)->where('is_read', 0)->countAllResults();
    }

    public function getLatest(int $idUser, int $limit = 10): array
    {
        return $this->where('id_user', $idUser)->orderBy('is_read', 'ASC')->orderBy('id', 'DESC')->findAll($limit);
    }

    public function markRead(int $id, int $idUser): bool
    {
        return $this->where('id', $id)->where('id_user', $idUser)->set('is_read', 1)->update();
    }

    public function markAllRead(int $idUser): bool
    {
        return $this->where('id_user', $idUser)->where('is_read', 0)->set('is_read', 1)->update();
    }

    public function deleteNotif(int $id, int $idUser): bool
    {
        return (bool) $this->where('id', $id)->where('id_user', $idUser)->delete();
    }

    private function insertIfNotExists(int $idUser, string $jenis, string $periode, string $judul, string $pesan, string $url): void
    {
        $exists = $this->where('id_user', $idUser)->where('jenis', $jenis)->where('periode', $periode)->countAllResults();
        if (!$exists) {
            $this->insert([
                'id_user' => $idUser,
                'jenis'   => $jenis,
                'periode' => $periode,
                'judul'   => $judul,
                'pesan'   => $pesan,
                'url'     => $url,
                'is_read' => 0,
            ]);
        }
    }
}
