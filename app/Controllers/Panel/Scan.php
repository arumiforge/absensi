<?php

namespace App\Controllers\Panel;

use App\Controllers\BaseController;
use App\Services\Presensi;
use App\Services\Sistem\Jam;
use CodeIgniter\HTTP\ResponseInterface;
use JsonException;

class Scan extends BaseController
{
    /**
     * Most scans in one batch from the scan page.
     */
    public const BATCH_MAKS = 100;

    public function index(): string
    {
        return view('panel/scan', ['title' => 'Scan QR', 'data' => (new Presensi())->dataScan()]);
    }

    /**
     * Batch of scans kept on the device: `{"scan": [{"id", "nis", "waktu"}, …]}`.
     * Answers with the result per scan and the server clock.
     */
    public function simpan(): ResponseInterface
    {
        try {
            $body = json_decode($this->request->getBody() ?? '', true, 4, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            $body = null;
        }

        $daftar = is_array($body) && is_array($body['scan'] ?? null) && array_is_list($body['scan']) ? $body['scan'] : null;
        $valid  = $daftar !== null && count($daftar) <= self::BATCH_MAKS
            && count(array_filter($daftar, self::scanValid(...))) === count($daftar);

        if (! $valid) {
            return $this->response->setStatusCode(400)
                ->setJSON(['kode' => 'tidak_valid', 'pesan' => 'Data scan tidak terbaca. Muat ulang halaman scan.']);
        }

        $hasil = (new Presensi())->scanBanyak(array_map(
            static fn (array $scan): array => ['id' => $scan['id'], 'nis' => $scan['nis'], 'waktu' => $scan['waktu']],
            $daftar,
        ));

        return $this->response->setJSON(['jam' => Presensi::milidetik((new Jam())->now()), 'hasil' => $hasil]);
    }

    private static function scanValid(mixed $scan): bool
    {
        return is_array($scan)
            && is_string($scan['id'] ?? null) && $scan['id'] !== '' && strlen($scan['id']) <= 64
            && is_string($scan['nis'] ?? null) && strlen($scan['nis']) <= 100
            && is_string($scan['waktu'] ?? null);
    }
}
