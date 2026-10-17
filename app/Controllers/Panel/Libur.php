<?php

namespace App\Controllers\Panel;

use App\Controllers\BaseController;
use App\Models\LiburModel;
use CodeIgniter\HTTP\RedirectResponse;

class Libur extends BaseController
{
    public function index(): string
    {
        return view('panel/libur', ['title' => 'Libur', 'libur' => model(LiburModel::class)->orderBy('tanggal', 'DESC')->findAll()]);
    }

    public function simpan(): RedirectResponse
    {
        $tanggal    = (string) $this->request->getPost('tanggal');
        $keterangan = trim((string) $this->request->getPost('keterangan'));

        if (! Rekap::tanggalValid($tanggal)) {
            return redirect()->back()->withInput()->with('galat', 'Tanggal belum diisi. Pilih tanggal lebih dulu.');
        }
        if ($keterangan === '' || mb_strlen($keterangan) > 100) {
            return redirect()->back()->withInput()->with('galat', 'Isi keterangan libur, paling panjang 100 karakter.');
        }

        model(LiburModel::class)->replace(['tanggal' => $tanggal, 'keterangan' => $keterangan]);

        return redirect()->to(url_to('libur'))->with('sukses', format_date($tanggal) . ' sudah ditandai libur.');
    }

    public function hapus(string $tanggal): RedirectResponse
    {
        if (Rekap::tanggalValid($tanggal)) {
            model(LiburModel::class)->delete($tanggal);
        }

        return redirect()->to(url_to('libur'))->with('sukses', format_date($tanggal) . ' tidak lagi libur.');
    }
}
