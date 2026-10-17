<?php $versi = '?v=' . config('Spensada')->versi; ?>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= esc(empty($title) ? 'Spensada' : $title . ' · Spensada') ?></title>
<link rel="icon" href="<?= base_url('favicon.ico') ?>" sizes="any">
<link rel="icon" href="<?= base_url('aset/logo/favicon-32.png') ?>" type="image/png">
<link rel="apple-touch-icon" href="<?= base_url('apple-touch-icon.png') ?>">
<link rel="stylesheet" href="<?= esc(base_url('aset/vendor/bootstrap/5.3.8/css/bootstrap.min.css') . $versi) ?>">
<script src="<?= esc(base_url('aset/vendor/bootstrap/5.3.8/js/bootstrap.bundle.min.js') . $versi) ?>" defer></script>
<script src="<?= esc(base_url('aset/js/konfirmasi.js') . $versi) ?>" defer></script>
