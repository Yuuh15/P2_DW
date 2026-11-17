<div class="page-head">
    <div class="page-head__copy">
        <h1>Pacotes turísticos</h1>
        <p class="page-head__sub"><?= (int) $total ?> pacote<?= $total === 1 ? '' : 's' ?> no catálogo. Defina duração, preço por pessoa e o que está incluso antes de abrir partidas.</p>
    </div>
    <div class="page-head__actions">
        <a class="button button--primary" href="<?= url('pacotes', ['acao' => 'novo']) ?>"><?= icon('plus', 'icon', 16) ?> Novo pacote</a>
    </div>
</div>

<form class="toolbar" method="get" action="<?= url('pacotes') ?>" role="search">
    <input type="hidden" name="r" value="pacotes">
    <div class="toolbar__search">
        <?= icon('search', 'icon', 16) ?>
        <label class="sr-only" for="busca-pacotes">Buscar pacotes</label>
        <input type="search" id="busca-pacotes" name="busca" value="<?= e($busca) ?>"
               placeholder="Buscar por pacote ou destino…" autocomplete="off">
    </div>
    <div class="toolbar__filters">
        <label class="sr-only" for="filtro-destino">Filtrar por destino</label>
        <select id="filtro-destino" name="destino_id">
            <option value="">Todos os destinos</option>
            <?php foreach ($destinos as $destino): ?>
                <option value="<?= (int) $destino['id'] ?>" <?= $destinoFiltro === (int) $destino['id'] ? 'selected' : '' ?>><?= e($destino['nome']) ?></option>
            <?php endforeach; ?>
        </select>
        <button class="button button--secondary button--small" type="submit"><?= icon('filter', 'icon', 14) ?> Filtrar</button>
        <?php if ($busca !== '' || $destinoFiltro > 0): ?>
            <a class="toolbar__clear" href="<?= url('pacotes') ?>">Limpar filtros</a>
        <?php endif; ?>
    </div>
    <span class="toolbar__spacer"></span>
    <span class="list-meta" style="margin: 0;"><?= icon('package', 'icon', 13) ?> <?= (int) $total ?> cadastrados</span>
</form>

<section class="panel panel--flush">
    <?php if ($pacotes): ?>
        <div class="table-wrap">
            <table class="data-table">
                <caption class="sr-only">Pacotes turísticos cadastrados</caption>
                <thead>
                    <tr>
                        <th scope="col">Pacote</th>
                        <th scope="col">Destino</th>
                        <th scope="col">Duração</th>
                        <th scope="col">Preço por pessoa</th>
                        <th scope="col">Partidas</th>
                        <th scope="col">Status</th>
                        <th scope="col"><span class="sr-only">Ações</span></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pacotes as $pacote): ?>
                        <tr>
                            <td>
                                <a class="cell-main" href="<?= url('pacotes', ['acao' => 'editar', 'id' => $pacote['id']]) ?>"><?= e($pacote['titulo']) ?></a>
                                <div class="cell-sub cell-sub--clip" title="<?= e($pacote['inclusos'] ?: 'Sem itens inclusos descritos') ?>"><?= e(resumir($pacote['inclusos'], 64) ?: 'Sem itens inclusos descritos') ?></div>
                            </td>
                            <td>
                                <div class="person-cell">
                                    <?php if ($pacote['destino_imagem']): ?>
                                        <img class="thumb" src="<?= e(asset($pacote['destino_imagem'])) ?>" alt="" width="62" height="42" loading="lazy">
                                    <?php endif; ?>
                                    <div class="person-cell__copy">
                                        <span class="cell-main"><?= e($pacote['destino_nome']) ?></span>
                                        <div class="cell-sub"><?= e($pacote['destino_pais']) ?></div>
                                    </div>
                                </div>
                            </td>
                            <td class="cell-strong"><?= (int) $pacote['duracao_dias'] ?> dias</td>
                            <td class="cell-strong mono"><?= e(money($pacote['preco'])) ?></td>
                            <td>
                                <span class="cell-strong"><?= (int) $pacote['total_viagens'] ?></span>
                                <div class="cell-sub"><?= (int) $pacote['viagens_futuras'] ?> futura<?= (int) $pacote['viagens_futuras'] === 1 ? '' : 's' ?></div>
                            </td>
                            <td>
                                <span class="badge badge--<?= (int) $pacote['ativo'] === 1 ? 'success' : 'neutral' ?>"><?= (int) $pacote['ativo'] === 1 ? 'Ativo' : 'Inativo' ?></span>
                            </td>
                            <td class="cell-actions">
                                <div class="cell-actions__group">
                                    <a class="action-link" href="<?= url('viagens', ['pacote_id' => $pacote['id']]) ?>" aria-label="Ver partidas de <?= e($pacote['titulo']) ?>" title="Ver partidas">
                                        <?= icon('calendar', 'icon', 15) ?>
                                    </a>
                                    <a class="action-link" href="<?= url('pacotes', ['acao' => 'editar', 'id' => $pacote['id']]) ?>" aria-label="Editar <?= e($pacote['titulo']) ?>" title="Editar">
                                        <?= icon('edit', 'icon', 15) ?>
                                    </a>
                                    <form class="action-form" method="post" action="<?= url('pacotes', ['acao' => 'excluir', 'id' => $pacote['id']]) ?>"
                                          data-confirm="Excluir o pacote <?= e($pacote['titulo']) ?>? Pacotes com partidas vinculadas não podem ser removidos."
                                          data-confirm-label="Excluir pacote">
                                        <?= csrf_field() ?>
                                        <button class="action-link action-link--danger" type="submit" aria-label="Excluir <?= e($pacote['titulo']) ?>" title="Excluir">
                                            <?= icon('trash', 'icon', 15) ?>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php include VIEWS_PATH . '/partials/pagination.php'; ?>
    <?php else: ?>
        <div class="empty-state">
            <span class="empty-state__mark"><?= icon('package', 'icon', 20) ?></span>
            <h3><?= ($busca !== '' || $destinoFiltro > 0) ? 'Nenhum pacote encontrado' : 'Nenhum pacote cadastrado' ?></h3>
            <p><?= ($busca !== '' || $destinoFiltro > 0) ? 'Ajuste a busca ou o filtro de destino para encontrar o pacote.' : 'Cadastre o primeiro pacote e depois abra partidas com datas e capacidade.' ?></p>
            <?php if ($busca !== '' || $destinoFiltro > 0): ?>
                <a class="button button--secondary" href="<?= url('pacotes') ?>">Limpar filtros</a>
            <?php else: ?>
                <a class="button button--primary" href="<?= url('pacotes', ['acao' => 'novo']) ?>"><?= icon('plus', 'icon', 16) ?> Cadastrar pacote</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</section>
