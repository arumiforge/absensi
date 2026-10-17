<?php use App\Services\Presensi; ?>
<style>
    body { font-family: sans-serif; font-size: 10pt; }
    h1 { font-size: 14pt; margin: 0; }
    table { width: 100%; border-collapse: collapse; margin-top: 8pt; }
    th, td { border: 0.5pt solid #555; padding: 3pt 4pt; }
    th { background: #e9ecef; text-align: left; }
    .ringkasan td { border: none; padding: 1pt 4pt; }
</style>
<h1>Rekap Presensi Harian <?= esc(config('Spensada')->namaSekolah) ?></h1>
<p><?= esc(format_date($tanggal, 'full')) ?><?= $kelas === '' ? '' : ' · Kelas ' . esc($kelas) ?><?= $rekap['libur'] === null ? '' : ' · Libur: ' . esc($rekap['libur']) ?></p>
<table>
    <thead><tr><th>No</th><th>NIS</th><th>Nama</th><th>Kelas</th><th>Status</th><th>Jam scan</th><th>Keterangan</th></tr></thead>
    <tbody>
<?php foreach ($rekap['baris'] as $i => $siswa): ?>
        <tr>
            <td><?= $i + 1 ?></td>
            <td><?= esc($siswa['nis']) ?></td>
            <td><?= esc($siswa['nama']) ?></td>
            <td><?= esc($siswa['kelas']) ?></td>
            <td><?= Presensi::STATUS[$siswa['status']] ?? '–' ?></td>
            <td><?= esc(format_time($siswa['jam_scan'])) ?></td>
            <td><?= esc($siswa['keterangan'] ?? '') ?></td>
        </tr>
<?php endforeach ?>
    </tbody>
</table>
<table class="ringkasan">
<?php foreach ($ringkasan as $label => $jumlah): ?>
    <tr><td width="30%"><?= $label ?></td><td><?= format_number($jumlah) ?></td></tr>
<?php endforeach ?>
</table>
<p>Dicetak <?= esc(format_datetime((new App\Services\Sistem\Jam())->now())) ?> WIB</p>
