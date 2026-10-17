<?php

namespace App\Filters;

use App\Models\AdminModel;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Admin login check. A password change ends every other session, because
 * the session keeps a stamp of the password hash.
 */
class Sesi implements FilterInterface
{
    use BackgroundResponse;

    public function before(RequestInterface $request, $arguments = null)
    {
        $admin = session('admin_id') === null ? null : model(AdminModel::class)->find((int) session('admin_id'));

        if ($admin !== null && hash_equals(self::cap($admin['password_hash']), (string) session('cap'))) {
            return null;
        }

        session()->remove(['admin_id', 'cap']);

        if ($this->isBackground($request)) {
            return $this->jsonError(401, 'login_ulang', 'Sesi berakhir. Muat ulang halaman, lalu login lagi.');
        }

        return redirect()->to('/login')->with('galat', 'Login dulu untuk melanjutkan.');
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return null;
    }

    public static function cap(string $passwordHash): string
    {
        return hash('sha256', $passwordHash);
    }
}
