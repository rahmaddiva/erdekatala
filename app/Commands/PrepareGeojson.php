<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Proses file GeoJSON BPS di root project menjadi versi ringan
 * untuk peta sebaran di public/geojson.
 *
 * Pemakaian: php spark geojson:prepare
 */
class PrepareGeojson extends BaseCommand
{
    protected $group       = 'Erdekatala';
    protected $name        = 'geojson:prepare';
    protected $description = 'Proses file GeoJSON BPS (kecamatan/kelurahan/kabupaten) menjadi versi ringan untuk peta sebaran.';

    protected $usage = 'geojson:prepare';

    public function run(array $params)
    {
        $srcKecamatan = ROOTPATH . '63.01_kecamatan.geojson';
        $srcKelurahan = ROOTPATH . '63.01_kelurahan.geojson';
        $srcKabupaten = ROOTPATH . '63.01_Tanah_Laut.geojson';

        $outBase = FCPATH . 'geojson';
        $outDesa = $outBase . DIRECTORY_SEPARATOR . 'desa';

        if (!is_dir($outDesa) && !mkdir($outDesa, 0755, true)) {
            CLI::error('Gagal membuat direktori ' . $outDesa);
            return EXIT_ERROR;
        }

        foreach (['kecamatan.geojson', 'kabupaten.geojson'] as $f) {
            $p = $outBase . DIRECTORY_SEPARATOR . $f;
            if (is_file($p)) {
                unlink($p);
            }
        }
        foreach (glob($outDesa . DIRECTORY_SEPARATOR . '*.geojson') ?: [] as $f) {
            unlink($f);
        }

        foreach ([
            'kecamatan' => $srcKecamatan,
            'kelurahan' => $srcKelurahan,
            'kabupaten' => $srcKabupaten,
        ] as $label => $src) {
            if (!is_file($src)) {
                CLI::error("Sumber tidak ditemukan: {$src}");
                return EXIT_ERROR;
            }
            CLI::write('Memproses ' . $label . ' ...', 'green');
        }

        $kec = $this->loadFeatureCollection($srcKecamatan);
        $kel = $this->loadFeatureCollection($srcKelurahan);
        $kab = $this->loadFeatureCollection($srcKabupaten);

        $kecCount = count($kec['features'] ?? []);
        $kelCount = count($kel['features'] ?? []);

        $kec['features'] = array_map([$this, 'cleanFeature'], $kec['features'] ?? []);
        $kel['features'] = array_map([$this, 'cleanFeature'], $kel['features'] ?? []);
        $kab['features'] = array_map([$this, 'cleanFeature'], $kab['features'] ?? []);

        $kecPath = $this->writeJson($outBase . DIRECTORY_SEPARATOR . 'kecamatan.geojson', $kec);
        $kabPath = $this->writeJson($outBase . DIRECTORY_SEPARATOR . 'kabupaten.geojson', $kab);

        // Pecah per kecamatan: desa/001.geojson ... desa/011.geojson
        $perKec = [];
        foreach ($kel['features'] as $f) {
            $kode = (string) ($f['properties']['kd_kecamatan'] ?? '000');
            $perKec[$kode][] = $f;
        }
        $desaFiles = 0;
        foreach ($perKec as $kode => $features) {
            $fc = ['type' => 'FeatureCollection', 'features' => $features];
            $this->writeJson($outDesa . DIRECTORY_SEPARATOR . $kode . '.geojson', $fc);
            $desaFiles++;
        }

        CLI::write('');
        CLI::write(sprintf('Kecamatan : %d fitur -> %s', $kecCount, $this->humanSize($kecPath)));
        CLI::write(sprintf('Kelurahan : %d fitur -> %d file per kecamatan', $kelCount, $desaFiles));
        CLI::write(sprintf('Kabupaten : %d fitur -> %s', count($kab['features'] ?? []), $this->humanSize($kabPath)));
        CLI::write('Selesai.', 'green');

        return EXIT_SUCCESS;
    }

    private function loadFeatureCollection(string $path): array
    {
        $raw = file_get_contents($path);
        $data = json_decode($raw, true);
        if (!is_array($data) || ($data['type'] ?? null) !== 'FeatureCollection') {
            CLI::error('Format GeoJSON tidak valid: ' . $path);
            throw new \RuntimeException('Invalid GeoJSON: ' . $path);
        }

        return $data;
    }

    /**
     * Buang koordinat ke-3 (elevasi), bulatkan ke 6 desimal.
     */
    private function cleanFeature(array $feature): array
    {
        $geom = $feature['geometry'] ?? null;
        if (is_array($geom)) {
            $geom['coordinates'] = $this->cleanCoordinates($geom['type'] ?? '', $geom['coordinates'] ?? []);
            $feature['geometry'] = $geom;
        }

        return $feature;
    }

    private function cleanCoordinates(string $type, array $coords): array
    {
        if ($type === 'Point') {
            return $this->cleanPoint($coords);
        }
        if ($type === 'MultiPoint') {
            return array_map([$this, 'cleanPoint'], $coords);
        }
        if ($type === 'LineString') {
            return array_map([$this, 'cleanPoint'], $coords);
        }
        if ($type === 'MultiLineString') {
            return array_map(fn(array $line) => array_map([$this, 'cleanPoint'], $line), $coords);
        }
        if ($type === 'Polygon') {
            return array_map(fn(array $ring) => array_map([$this, 'cleanPoint'], $ring), $coords);
        }
        if ($type === 'MultiPolygon') {
            return array_map(
                fn(array $poly) => array_map(fn(array $ring) => array_map([$this, 'cleanPoint'], $ring), $poly),
                $coords
            );
        }

        return $coords;
    }

    private function cleanPoint(array $pt): array
    {
        $lng = round((float) ($pt[0] ?? 0.0), 6);
        $lat = round((float) ($pt[1] ?? 0.0), 6);

        return [$lng, $lat];
    }

    private function writeJson(string $path, array $data): string
    {
        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            CLI::error('Gagal encode JSON: ' . $path);
            throw new \RuntimeException('JSON encode failed: ' . $path);
        }
        if (file_put_contents($path, $json) === false) {
            CLI::error('Gagal menulis: ' . $path);
            throw new \RuntimeException('Write failed: ' . $path);
        }

        return $path;
    }

    private function humanSize(string $path): string
    {
        $bytes = filesize($path);

        return number_format($bytes / 1024, 1) . ' KB';
    }
}
