<?php

namespace App\Controllers\Panel;

use App\Controllers\BaseController;
use App\Filters\Sesi;
use App\Models\AdminModel;
use CodeIgniter\HTTP\RedirectResponse;

class Password extends BaseController
{
    public function index(): string
    {
        return view('panel/password', ['title' => 'Ganti password']);
    }

    public function ganti(): RedirectResponse
    {
        $admin = model(AdminModel::class)->find((int) session('admin_id'));
        $lama  = (string) $this->request->getPost('password_lama');
        $baru  = (string) $this->request->getPost('password_baru');

        if (! password_verify($lama, $admin['password_hash'])) {
            return redirect()->to(url_to('password'))->with('galat', 'Password lama belum cocok. Coba lagi.');
        }
        if (mb_strlen($baru) < 8) {
            return redirect()->to(url_to('password'))->with('galat', 'Password baru paling sedikit 8 karakter.');
        }
        if ($baru !== (string) $this->request->getPost('password_ulang')) {
            return redirect()->to(url_to('password'))->with('galat', 'Ulangi password baru dengan isi yang sama.');
        }

        $hash = password_hash($baru, PASSWORD_DEFAULT);
        model(AdminModel::class)->update($admin['id'], ['password_hash' => $hash]);
        session()->set('cap', Sesi::cap($hash));

        return redirect()->to(url_to('password'))->with('sukses', 'Password sudah diganti.');
    }
}
