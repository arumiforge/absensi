<?= $this->extend('layout/panel') ?>
<?= $this->section('head') ?>
<script src="<?= esc(base_url('aset/vendor/jsqr/1.4.0/jsQR.min.js') . '?v=' . config('Spensada')->versi) ?>" defer></script>
<script src="<?= esc(base_url('aset/js/scan.js') . '?v=' . config('Spensada')->versi) ?>" defer></script>
<?= $this->endSection() ?>
<?= $this->section('content') ?>
<div id="kios" class="bg-body-tertiary">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-1">
        <h1 class="h3 mb-0">Scan QR</h1>
        <div class="d-flex flex-wrap align-items-center gap-2">
            <select id="pilih-kamera" class="form-select form-select-sm w-auto" aria-label="Kamera" hidden></select>
            <button type="button" id="suara" class="btn btn-sm btn-outline-secondary" aria-pressed="false">Suara: nyala</button>
            <button type="button" id="layar-penuh" class="btn btn-sm btn-outline-secondary" hidden>Layar penuh</button>
        </div>
    </div>
    <p id="petunjuk" class="text-body-secondary">Sebelum pukul 07.00 tercatat Hadir, pukul 07.00 sampai 08.00 tercatat Terlambat. Setelah pukul 08.00 scan ditutup. Hasil scan langsung tampil dengan bunyi, lalu dikirim ke server otomatis.</p>
    <div class="row g-4">
        <div class="col-lg-7">
            <video id="kamera" class="w-100 rounded bg-dark" muted playsinline></video>
        </div>
        <div class="col-lg-5">
            <div id="hasil" class="alert alert-secondary fs-5" role="status" aria-live="polite">Arahkan kartu QR ke kamera.</div>
            <div class="d-flex flex-wrap align-items-center gap-2">
                <span id="antrean" class="text-body-secondary">Semua scan sudah terkirim.</span>
                <button type="button" id="kirim-sekarang" class="btn btn-sm btn-outline-primary" hidden>Kirim sekarang</button>
            </div>
            <div id="ditolak" class="alert alert-warning mt-3" role="alert" hidden></div>
            <form id="form-scan" method="post" action="<?= url_to('scan.simpan') ?>"><?= csrf_field() ?></form>
        </div>
    </div>
</div>
<script type="application/json" id="data-scan"><?= json_encode($data, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) ?></script>
<?= $this->endSection() ?>
