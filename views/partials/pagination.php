<?php if (($paginacao['paginas'] ?? 1) > 1): ?>
    <nav class="pagination" aria-label="Paginação">
        <span>Mostrando <?= (int) $paginacao['inicio'] ?>–<?= (int) $paginacao['fim'] ?> de <?= (int) $paginacao['total'] ?> registros</span>
        <span class="pagination__pages">
            <a class="pagination__link <?= $paginacao['pagina'] <= 1 ? 'is-disabled' : '' ?>"
               href="<?= e(url_com_pagina(max(1, $paginacao['pagina'] - 1))) ?>"
               aria-label="Página anterior" <?= $paginacao['pagina'] <= 1 ? 'aria-disabled="true" tabindex="-1"' : '' ?>>
                <?= icon('chevron-left', 'icon', 12) ?>
            </a>
            <?php
            $inicioJanela = max(1, $paginacao['pagina'] - 2);
            $fimJanela = min($paginacao['paginas'], $paginacao['pagina'] + 2);
            ?>
            <?php if ($inicioJanela > 1): ?>
                <a class="pagination__link" href="<?= e(url_com_pagina(1)) ?>">1</a>
                <?php if ($inicioJanela > 2): ?><span class="pagination__link is-disabled">…</span><?php endif; ?>
            <?php endif; ?>
            <?php for ($p = $inicioJanela; $p <= $fimJanela; $p++): ?>
                <a class="pagination__link <?= $p === $paginacao['pagina'] ? 'is-current' : '' ?>"
                   href="<?= e(url_com_pagina($p)) ?>"
                   <?= $p === $paginacao['pagina'] ? 'aria-current="page"' : '' ?>><?= $p ?></a>
            <?php endfor; ?>
            <?php if ($fimJanela < $paginacao['paginas']): ?>
                <?php if ($fimJanela < $paginacao['paginas'] - 1): ?><span class="pagination__link is-disabled">…</span><?php endif; ?>
                <a class="pagination__link" href="<?= e(url_com_pagina($paginacao['paginas'])) ?>"><?= (int) $paginacao['paginas'] ?></a>
            <?php endif; ?>
            <a class="pagination__link <?= $paginacao['pagina'] >= $paginacao['paginas'] ? 'is-disabled' : '' ?>"
               href="<?= e(url_com_pagina(min($paginacao['paginas'], $paginacao['pagina'] + 1))) ?>"
               aria-label="Próxima página" <?= $paginacao['pagina'] >= $paginacao['paginas'] ? 'aria-disabled="true" tabindex="-1"' : '' ?>>
                <?= icon('chevron-right', 'icon', 12) ?>
            </a>
        </span>
    </nav>
<?php endif; ?>
