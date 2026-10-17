<?php

namespace App\Controllers;

use App\Filters\Sesi;
use App\Models\AdminModel;
use CodeIgniter\HTTP\RedirectResponse;

class Login extends BaseController
{
    public function index(): RedirectResponse|string
    {
        if (session('admin_id') !== null) {
            return redirect()->to('/panel');
        }

        return view('login', ['title' => 'Login']);
    }

    public function masuk(): RedirectResponse
    {
        $username = trim((string) $this->request->getPost('username'));
        $password = (string) $this->request->getPost('password');

        session()->setFlashdata('_ci_old_input', ['get' => [], 'post' => ['username' => $username]]);

        // 10 tries per minute per network address.
        if (! service('throttler')->check('login-' . md5($this->request->getIPAddress()), 10, MINUTE)) {
            return redirect()->to('/login', 303)->with('galat', 'Terlalu banyak percobaan login. Tunggu 1 menit, lalu coba lagi.');
        }

        $admin = $username === '' ? null : model(AdminModel::class)->where('username', $username)->first();

        if ($admin === null || ! password_verify($password, $admin['password_hash'])) {
            return redirect()->to('/login', 303)->with('galat', 'Username atau password belum cocok. Coba lagi.');
        }

        session()->regenerate(true);
        session()->set(['admin_id' => (int) $admin['id'], 'cap' => Sesi::cap($admin['password_hash'])]);

        return redirect()->to('/panel', 303);
    }

    public function keluar(): RedirectResponse
    {
        session()->destroy();

        return redirect()->to('/login', 303);
    }
}
