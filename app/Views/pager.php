<?php

use CodeIgniter\Pager\PagerRenderer;

/** @var PagerRenderer $pager */
$pager->setSurroundCount(2);
?>
<nav aria-label="Halaman">
    <ul class="pagination flex-wrap">
<?php if ($pager->hasPrevious()): ?>
        <li class="page-item"><a class="page-link" href="<?= $pager->getFirst() ?>">Awal</a></li>
<?php endif ?>
<?php foreach ($pager->links() as $link): ?>
        <li class="page-item<?= $link['active'] ? ' active' : '' ?>"><a class="page-link" href="<?= $link['uri'] ?>"<?= $link['active'] ? ' aria-current="page"' : '' ?>><?= $link['title'] ?></a></li>
<?php endforeach ?>
<?php if ($pager->hasNext()): ?>
        <li class="page-item"><a class="page-link" href="<?= $pager->getLast() ?>">Akhir</a></li>
<?php endif ?>
    </ul>
</nav>
