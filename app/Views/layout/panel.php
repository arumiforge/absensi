<?php
$menu = ['scan' => 'Scan QR', 'rekap' => 'Rekap harian', 'siswa' => 'Siswa', 'libur' => 'Libur'];
$path = service('request')->getPath();
?>
<!doctype html>
<html lang="id">
<head>
<?= $this->include('layout/head') ?>
<?= $this->renderSection('head') ?>
</head>
<body class="bg-body-tertiary">
<nav class="navbar navbar-expand-md bg-primary" data-bs-theme="dark">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center gap-2" href="<?= url_to('rekap') ?>">
            <img src="<?= base_url('aset/logo/logo-sekolah.png') ?>" alt="" width="30" height="29">Spensada
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menu" aria-controls="menu" aria-expanded="false" aria-label="Buka menu">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="menu">
            <ul class="navbar-nav me-auto">
<?php foreach ($menu as $route => $label): ?>
<?php $aktif = str_starts_with($path, 'panel/' . $route); ?>
                <li class="nav-item"><a class="nav-link<?= $aktif ? ' active' : '' ?>"<?= $aktif ? ' aria-current="page"' : '' ?> href="<?= url_to($route) ?>"><?= $label ?></a></li>
<?php endforeach ?>
            </ul>
            <ul class="navbar-nav">
                <li class="nav-item"><a class="nav-link<?= $path === 'panel/password' ? ' active' : '' ?>" href="<?= url_to('password') ?>">Ganti password</a></li>
                <li class="nav-item">
                    <form method="post" action="<?= url_to('logout') ?>">
                        <?= csrf_field() ?>
                        <button type="submit" class="nav-link">Logout</button>
                    </form>
                </li>
            </ul>
        </div>
    </div>
</nav>
<main class="container py-4">
<?php foreach (['sukses' => 'success', 'galat' => 'danger'] as $kunci => $warna): ?>
<?php if (session()->getFlashdata($kunci)): ?>
    <div class="alert alert-<?= $warna ?>" role="<?= $kunci === 'galat' ? 'alert' : 'status' ?>"><?= esc(session()->getFlashdata($kunci)) ?></div>
<?php endif ?>
<?php endforeach ?>
<?= $this->renderSection('content') ?>
</main>
</body>
</html>
