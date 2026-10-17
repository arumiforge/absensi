<?php

namespace App\Controllers\Panel;

use App\Controllers\BaseController;
use App\Services\Presensi;
use CodeIgniter\HTTP\ResponseInterface;

class Scan extends BaseController
{
    public function index(): string
    {
        return view('panel/scan', ['title' => 'Scan QR']);
    }

    public function simpan(): ResponseInterface
    {
        return $this->response->setJSON((new Presensi())->scan((string) $this->request->getPost('nis')));
    }
}
