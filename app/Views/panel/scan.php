<?= $this->extend('layout/panel') ?>
<?= $this->section('head') ?>
<script src="<?= esc(base_url('aset/vendor/jsqr/1.4.0/jsQR.min.js') . '?v=' . config('Spensada')->versi) ?>" defer></script>
<script src="<?= esc(base_url('aset/js/scan.js') . '?v=' . config('Spensada')->versi) ?>" defer></script>
<?= $this->endSection() ?>
<?= $this->section('content') ?>
<h1 class="h3 mb-1">Scan QR</h1>
<p class="text-body-secondary">Sebelum pukul 07.00 tercatat Hadir, pukul 07.00 sampai 08.00 tercatat Terlambat. Setelah pukul 08.00 scan ditutup.</p>
<div class="row g-4">
    <div class="col-lg-7">
        <video id="kamera" class="w-100 rounded bg-dark" muted playsinline></video>
    </div>
    <div class="col-lg-5">
        <div id="hasil" class="alert alert-secondary fs-5" role="status" aria-live="polite">Arahkan kartu QR ke kamera.</div>
        <form id="form-scan" method="post" action="<?= url_to('scan.simpan') ?>"><?= csrf_field() ?></form>
    </div>
</div>
<?= $this->endSection() ?>
