<?php $versi = esc(config('Spensada')->versi, 'url'); ?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title><?= $this->renderSection('judul') ?> · Spensada</title>
    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="icon" href="/aset/logo/favicon-32.png" type="image/png">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">
    <link rel="stylesheet" href="/aset/vendor/bootstrap/5.3.8/css/bootstrap.min.css?v=<?= $versi ?>">
</head>
<body>
    <main class="container py-5 text-center">
        <img src="/aset/logo/ikon-192.png" width="96" height="96" alt="" class="mb-2">
        <p class="fw-bold mb-4">Spensada</p>
        <h1 class="h5 mb-4"><?= $this->renderSection('isi') ?></h1>
        <a class="btn btn-primary" href="/"><?= lang('Galat.keHalamanAwal') ?></a>
    </main>
</body>
</html>
