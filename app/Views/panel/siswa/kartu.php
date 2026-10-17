<style>
    body { font-family: sans-serif; }
    table { width: 100%; border-collapse: collapse; }
    td { width: 33.3%; height: 66mm; border: 0.5pt dashed #999; text-align: center; vertical-align: middle; padding: 3mm; }
    div { text-align: center; }
    .sekolah { font-size: 9pt; color: #555; margin-bottom: 2mm; }
    .nama { font-size: 10pt; font-weight: bold; }
    .info { font-size: 9pt; }
</style>
<?php if ($kartu === []): ?>
<p>Belum ada siswa.</p>
<?php else: ?>
<table>
<?php foreach (array_chunk($kartu, 3) as $baris): ?>
    <tr>
<?php foreach ($baris as $siswa): ?>
        <td>
            <div class="sekolah"><?= esc(config('Spensada')->namaSekolah) ?></div>
            <div><img src="<?= $siswa['qr'] ?>" width="38mm" height="38mm" alt=""></div>
            <div class="nama"><?= esc($siswa['nama']) ?></div>
            <div class="info">NIS <?= esc($siswa['nis']) ?> · Kelas <?= esc($siswa['kelas']) ?></div>
        </td>
<?php endforeach ?>
<?php for ($i = count($baris); $i < 3; $i++): ?>
        <td></td>
<?php endfor ?>
    </tr>
<?php endforeach ?>
</table>
<?php endif ?>
