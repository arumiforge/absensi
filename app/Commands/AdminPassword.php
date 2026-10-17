<?php

namespace App\Commands;

use App\Models\AdminModel;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class AdminPassword extends BaseCommand
{
    protected $group       = 'Spensada';
    protected $name        = 'admin:password';
    protected $description = 'Membuat akun admin, atau membuat password baru bila admin lupa password.';
    protected $usage       = 'admin:password [--username admin]';
    protected $options     = ['--username' => 'Username admin. Bila kosong: username lama, atau ditanyakan.'];

    public function run(array $params): int
    {
        $model    = model(AdminModel::class);
        $admin    = $model->first();
        $username = trim((string) ($params['username'] ?? $admin['username'] ?? CLI::prompt('Username', 'admin', 'required')));
        $password = bin2hex(random_bytes(5));
        $data     = ['username' => $username, 'password_hash' => password_hash($password, PASSWORD_DEFAULT)];

        // There is only one admin account.
        $admin === null ? $model->insert($data) : $model->update($admin['id'], $data);

        CLI::write('Akun admin siap. Username: ' . CLI::color($username, 'yellow'), 'green');
        CLI::write('Password: ' . CLI::color($password, 'yellow'));
        CLI::write('Password ini hanya tampil sekali. Catat, lalu ganti lewat menu Ganti password.');

        return EXIT_SUCCESS;
    }
}
