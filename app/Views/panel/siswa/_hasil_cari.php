<?php
/**
 * Student search results, at most DaftarSiswa::BATAS_CARI (docs/10 EP-MD-01,
 * docs/09 RT-14 item 4, docs/08 UI-28): plain links, so Tab and Enter work.
 * Rendered alone as the search fragment. Render with ['saveData' => false].
 *
 * @var list<array<string, mixed>> $rows  From DaftarSiswa::cari()
 * @var int                        $total All matches
 */
$label = config('Label')->codes['siswa.status'];
$batas = \App\Services\MasterData\DaftarSiswa::BATAS_CARI;
?>
<?php if ($total === 0): ?>
<p class="mb-0">Tidak ada siswa yang cocok.</p>
<?php else: ?>
<p class="mb-2"><?= $total > $batas
    ? 'Ada ' . format_number($total) . ' siswa yang cocok. Menampilkan ' . format_number($batas) . ' yang pertama. Persempit pencarian.'
    : format_number($total) . ' siswa cocok.' ?></p>
<ul class="list-group">
<?php foreach ($rows as $row): ?>
    <li class="list-group-item">
        <a class="d-flex align-items-center gap-2" href="<?= esc(url_to('panel.siswa.lihat', $row['id']), 'attr') ?>">
            <?= view('komponen/foto_siswa', ['name' => $row['nama'], 'src' => $row['foto_file'] === null ? null : url_to('panel.siswa_foto.index', $row['id']) . '?ukuran=kecil'], ['saveData' => false]) ?>
            <span><?= esc($row['nama']) ?> · <?= esc($row['nisn']) ?> · <?= $row['rombel_nama'] === null ? 'Tanpa kelas' : 'Kelas ' . esc($row['rombel_nama']) ?><?= $row['status'] === 'aktif' ? '' : ' · ' . esc($label[$row['status']]) ?></span>
        </a>
    </li>
<?php endforeach ?>
</ul>
<?php endif ?>
