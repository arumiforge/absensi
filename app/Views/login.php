<!doctype html>
<html lang="id">
<head>
<?= $this->include('layout/head') ?>
</head>
<body class="bg-body-tertiary">
<main class="container py-5">
<div class="row justify-content-center"><div class="col-sm-8 col-md-6 col-lg-4">
    <div class="text-center mb-4">
        <img src="<?= base_url('aset/logo/logo-sekolah.png') ?>" alt="" width="96" height="94" class="mb-3">
        <h1 class="h4 mb-1">Spensada</h1>
        <p class="text-body-secondary">Presensi siswa <?= esc(config('Spensada')->namaSekolah) ?></p>
    </div>
<?php if (session()->getFlashdata('galat')): ?>
    <div class="alert alert-danger" role="alert"><?= esc(session()->getFlashdata('galat')) ?></div>
<?php endif ?>
    <form method="post" action="<?= url_to('login.masuk') ?>" class="card card-body shadow-sm">
        <?= csrf_field() ?>
        <div class="mb-3">
            <label for="username" class="form-label">Username</label>
            <input type="text" class="form-control" id="username" name="username" value="<?= esc(old('username')) ?>" autocomplete="username" required autofocus>
        </div>
        <div class="mb-3">
            <label for="password" class="form-label">Password</label>
            <input type="password" class="form-control" id="password" name="password" autocomplete="current-password" required>
        </div>
        <button type="submit" class="btn btn-primary w-100">Login</button>
    </form>
</div></div>
</main>
</body>
</html>
