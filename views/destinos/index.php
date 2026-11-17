<div class="page-head">
    <div class="page-head__copy">
        <h1>Destinos</h1>
        <p class="page-head__sub"><?= (int) $total ?> destino<?= $total === 1 ? '' : 's' ?> no catálogo. Cada destino reúne pacotes, partidas e imagens de capa.</p>
    </div>
    <div class="page-head__actions">
        <a class="button button--primary" href="<?= url('destinos', ['acao' => 'novo']) ?>"><?= icon('plus', 'icon', 16) ?> Novo destino</a>
    </div>
</div>

<form class="toolbar" method="get" action="<?= url('destinos') ?>" role="search">
    <input type="hidden" name="r" value="destinos">
    <div class="toolbar__search">
        <?= icon('search', 'icon', 16) ?>
        <label class="sr-only" for="busca-destinos">Buscar destinos</label>
        <input type="search" id="busca-destinos" name="busca" value="<?= e($busca) ?>"
               placeholder="Buscar por cidade, país ou região…" autocomplete="off">
    </div>
    <div class="toolbar__filters">
        <button class="button button--secondary button--small" type="submit"><?= icon('filter', 'icon', 14) ?> Filtrar</button>
        <?php if ($busca !== ''): ?>
            <a class="toolbar__clear" href="<?= url('destinos') ?>">Limpar busca</a>
        <?php endif; ?>
    </div>
    <span class="toolbar__spacer"></span>
    <span class="list-meta" style="margin: 0;"><?= icon('globe', 'icon', 13) ?> <?= (int) $total ?> no catálogo</span>
</form>

<?php if ($destinos): ?>
    <div class="destination-grid">
        <?php foreach ($destinos as $destino): ?>
            <?php $imagem = $destino['imagem'] ? asset($destino['imagem']) : null; ?>
            <article class="destination-card">
                <?php if ($imagem): ?>
                    <img class="destination-card__image" src="<?= e($imagem) ?>" alt="Vista de <?= e($destino['nome']) ?>"
                         width="1280" height="853" loading="lazy">
                <?php else: ?>
                    <div class="image-fallback" style="position: absolute; inset: 0; background: var(--ink-soft); color: #9db0a9;">
                        <?= icon('map-pin', 'icon', 34) ?>
                    </div>
                <?php endif; ?>
                <div class="destination-card__body">
                    <p class="destination-card__meta">
                        <?= icon('globe', 'icon', 11) ?>
                        <?= e($destino['pais']) ?><?= $destino['regiao'] ? ' · ' . e($destino['regiao']) : '' ?>
                    </p>
                    <h3><a href="<?= url('pacotes', ['destino_id' => $destino['id']]) ?>"><?= e($destino['nome']) ?></a></h3>
                    <div class="destination-card__foot">
                        <span>
                            <?= (int) $destino['total_pacotes'] ?> pacote<?= (int) $destino['total_pacotes'] === 1 ? '' : 's' ?>
                            · <?= (int) $destino['viagens_futuras'] ?> partida<?= (int) $destino['viagens_futuras'] === 1 ? '' : 's' ?>
                        </span>
                        <span class="destination-card__actions">
                            <a class="action-link" href="<?= url('pacotes', ['destino_id' => $destino['id']]) ?>" aria-label="Ver pacotes de <?= e($destino['nome']) ?>" title="Ver pacotes">
                                <?= icon('package', 'icon', 14) ?>
                            </a>
                            <a class="action-link" href="<?= url('destinos', ['acao' => 'editar', 'id' => $destino['id']]) ?>" aria-label="Editar <?= e($destino['nome']) ?>" title="Editar">
                                <?= icon('edit', 'icon', 14) ?>
                            </a>
                            <form class="action-form" method="post"
                                  action="<?= url('destinos', ['acao' => 'excluir', 'id' => $destino['id']]) ?>"
                                  data-confirm="Excluir o destino <?= e($destino['nome']) ?>? Só é possível excluir destinos sem pacotes vinculados."
                                  data-confirm-label="Excluir destino">
                                <?= csrf_field() ?>
                                <button class="action-link action-link--danger" type="submit" aria-label="Excluir <?= e($destino['nome']) ?>" title="Excluir">
                                    <?= icon('trash', 'icon', 14) ?>
                                </button>
                            </form>
                        </span>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>
    </div>

    <?php if ($paginacao['paginas'] > 1): ?>
        <div class="panel" style="margin-top: 16px;">
            <?php include VIEWS_PATH . '/partials/pagination.php'; ?>
        </div>
    <?php endif; ?>
<?php else: ?>
    <section class="panel">
        <div class="empty-state">
            <span class="empty-state__mark"><?= icon('map-pin', 'icon', 20) ?></span>
            <h3><?= $busca !== '' ? 'Nenhum destino encontrado' : 'O catálogo está vazio' ?></h3>
            <p><?= $busca !== '' ? 'Tente buscar por outro cidade, país ou região.' : 'Comece cadastrando os destinos que a agência vende. Depois, vincule pacotes e partidas.' ?></p>
            <?php if ($busca !== ''): ?>
                <a class="button button--secondary" href="<?= url('destinos') ?>">Limpar busca</a>
            <?php else: ?>
                <a class="button button--primary" href="<?= url('destinos', ['acao' => 'novo']) ?>"><?= icon('plus', 'icon', 16) ?> Cadastrar destino</a>
            <?php endif; ?>
        </div>
    </section>
<?php endif; ?>
