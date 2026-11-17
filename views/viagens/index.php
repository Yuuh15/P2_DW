<?php
$percentualOcupacao = (int) $resumo['lugares'] > 0
    ? (int) round(((int) $resumo['ocupados'] / (int) $resumo['lugares']) * 100)
    : 0;
$temFiltros = $busca !== '' || $statusFiltro !== '' || $pacoteFiltro > 0 || $periodo !== '';
$statusOpcoes = [
    'planejada'    => 'Planejada',
    'confirmada'   => 'Confirmada',
    'em_andamento' => 'Em andamento',
    'concluida'    => 'Concluída',
    'cancelada'    => 'Cancelada',
];
?>

<div class="page-head">
    <div class="page-head__copy">
        <h1>Partidas e viagens</h1>
        <p class="page-head__sub"><?= (int) $total ?> partida<?= $total === 1 ? '' : 's' ?> no recorte atual. Acompanhe datas, ocupação, situação operacional e receita prevista de cada embarque.</p>
    </div>
    <div class="page-head__actions">
        <a class="button button--secondary" href="<?= url('pacotes') ?>"><?= icon('package', 'icon', 16) ?> Ver pacotes</a>
        <a class="button button--primary" href="<?= url('viagens', ['acao' => 'novo']) ?>"><?= icon('plus', 'icon', 16) ?> Nova partida</a>
    </div>
</div>

<section class="stat-strip" aria-label="Resumo das partidas filtradas">
    <div class="stat">
        <p class="stat__label">Partidas</p>
        <p class="stat__value"><?= (int) $resumo['partidas'] ?></p>
    </div>
    <div class="stat">
        <p class="stat__label">Lugares ofertados</p>
        <p class="stat__value"><?= (int) $resumo['lugares'] ?></p>
    </div>
    <div class="stat">
        <p class="stat__label">Ocupação média</p>
        <p class="stat__value"><?= $percentualOcupacao ?>%</p>
    </div>
    <div class="stat">
        <p class="stat__label">Receita prevista</p>
        <p class="stat__value mono"><?= e(money($resumo['receita'])) ?></p>
    </div>
</section>

<form class="toolbar" method="get" action="<?= url('viagens') ?>" role="search">
    <input type="hidden" name="r" value="viagens">
    <div class="toolbar__search">
        <?= icon('search', 'icon', 16) ?>
        <label class="sr-only" for="busca-viagens">Buscar partidas</label>
        <input type="search" id="busca-viagens" name="busca" value="<?= e($busca) ?>"
               placeholder="Buscar por destino, pacote ou ponto de encontro…" autocomplete="off">
    </div>
    <div class="toolbar__filters">
        <label class="sr-only" for="filtro-status">Filtrar por situação</label>
        <select id="filtro-status" name="status">
            <option value="">Todas as situações</option>
            <?php foreach ($statusOpcoes as $chave => $rotulo): ?>
                <option value="<?= e($chave) ?>" <?= $statusFiltro === $chave ? 'selected' : '' ?>><?= e($rotulo) ?></option>
            <?php endforeach; ?>
        </select>

        <label class="sr-only" for="filtro-pacote">Filtrar por pacote</label>
        <select id="filtro-pacote" name="pacote_id">
            <option value="">Todos os pacotes</option>
            <?php foreach ($pacotes as $pacote): ?>
                <option value="<?= (int) $pacote['id'] ?>" <?= $pacoteFiltro === (int) $pacote['id'] ? 'selected' : '' ?>>
                    <?= e($pacote['destino_nome']) ?> · <?= e($pacote['titulo']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label class="sr-only" for="filtro-periodo">Filtrar por período</label>
        <select id="filtro-periodo" name="periodo">
            <option value="">Todas as datas</option>
            <option value="proximas" <?= $periodo === 'proximas' ? 'selected' : '' ?>>Próximas</option>
            <option value="passadas" <?= $periodo === 'passadas' ? 'selected' : '' ?>>Já realizadas</option>
        </select>

        <button class="button button--secondary button--small" type="submit"><?= icon('filter', 'icon', 14) ?> Filtrar</button>
        <?php if ($temFiltros): ?>
            <a class="toolbar__clear" href="<?= url('viagens') ?>">Limpar filtros</a>
        <?php endif; ?>
    </div>
    <span class="toolbar__spacer"></span>
    <span class="list-meta" style="margin: 0;"><?= icon('plane', 'icon', 13) ?> <?= (int) $total ?> partidas</span>
</form>

<section class="panel panel--flush">
    <?php if ($viagens): ?>
        <div class="table-wrap">
            <table class="data-table">
                <caption class="sr-only">Partidas de viagens cadastradas</caption>
                <thead>
                    <tr>
                        <th scope="col">Partida</th>
                        <th scope="col">Período</th>
                        <th scope="col">Ocupação</th>
                        <th scope="col">Receita prevista</th>
                        <th scope="col">Situação</th>
                        <th scope="col"><span class="sr-only">Ações</span></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($viagens as $item): ?>
                        <?php
                        $ocupados = (int) $item['lugares_ocupados'];
                        $capacidade = (int) $item['capacidade'];
                        $percentual = $capacidade > 0 ? min(100, (int) round(($ocupados / $capacidade) * 100)) : 0;
                        $situacao = situacao_viagem($item);
                        ?>
                        <tr>
                            <td>
                                <div style="display: flex; align-items: center; gap: 12px;">
                                    <div class="board-row__date">
                                        <span class="board-row__day"><?= e(dia($item['data_ida'])) ?></span>
                                        <span class="board-row__month"><?= e(mes_abreviado($item['data_ida'])) ?></span>
                                    </div>
                                    <div class="board-row__destination">
                                        <a class="board-row__city" href="<?= url('viagens', ['acao' => 'ver', 'id' => $item['id']]) ?>"><?= e($item['destino_nome']) ?></a>
                                        <div class="board-row__package" title="<?= e($item['pacote_titulo'] . ' · ' . (int) $item['duracao_dias'] . ' dias') ?>"><?= e($item['pacote_titulo']) ?> · <?= (int) $item['duracao_dias'] ?> dias</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="cell-main"><?= e(data_br($item['data_ida'])) ?></span>
                                <div class="cell-sub">até <?= e(data_br($item['data_volta'])) ?></div>
                            </td>
                            <td>
                                <div class="seat-bar" style="max-width: 170px;">
                                    <span class="seat-bar__track">
                                        <span class="seat-bar__fill <?= $percentual >= 100 ? 'seat-bar__fill--full' : ($percentual < 40 ? 'seat-bar__fill--low' : '') ?>"
                                              style="width: <?= $percentual ?>%"></span>
                                    </span>
                                    <span class="seat-bar__label"><?= $ocupados ?>/<?= $capacidade ?></span>
                                </div>
                                <div class="cell-sub"><?= max(0, $capacidade - $ocupados) ?> lugar<?= max(0, $capacidade - $ocupados) === 1 ? '' : 'es' ?> livre<?= max(0, $capacidade - $ocupados) === 1 ? '' : 's' ?></div>
                            </td>
                            <td>
                                <span class="cell-strong mono"><?= e(money($item['receita_prevista'])) ?></span>
                                <div class="cell-sub"><?= (int) $item['total_reservas'] ?> reserva<?= (int) $item['total_reservas'] === 1 ? '' : 's' ?> ativa<?= (int) $item['total_reservas'] === 1 ? '' : 's' ?></div>
                            </td>
                            <td><span class="stamp stamp--<?= e($situacao['tom']) ?>"><?= e($situacao['label']) ?></span></td>
                            <td class="cell-actions">
                                <div class="cell-actions__group">
                                    <a class="action-link" href="<?= url('viagens', ['acao' => 'ver', 'id' => $item['id']]) ?>" aria-label="Abrir partida para <?= e($item['destino_nome']) ?>" title="Abrir partida">
                                        <?= icon('eye', 'icon', 15) ?>
                                    </a>
                                    <a class="action-link" href="<?= url('viagens', ['acao' => 'editar', 'id' => $item['id']]) ?>" aria-label="Editar partida para <?= e($item['destino_nome']) ?>" title="Editar">
                                        <?= icon('edit', 'icon', 15) ?>
                                    </a>
                                    <form class="action-form" method="post" action="<?= url('viagens', ['acao' => 'excluir', 'id' => $item['id']]) ?>"
                                          data-confirm="Excluir a partida de <?= e($item['destino_nome']) ?> em <?= e(data_br($item['data_ida'])) ?>? Partidas com reservas não podem ser removidas."
                                          data-confirm-label="Excluir partida">
                                        <?= csrf_field() ?>
                                        <button class="action-link action-link--danger" type="submit" aria-label="Excluir partida de <?= e($item['destino_nome']) ?>" title="Excluir">
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
            <span class="empty-state__mark"><?= icon('plane', 'icon', 20) ?></span>
            <h3><?= $temFiltros ? 'Nenhuma partida encontrada' : 'Nenhuma partida cadastrada' ?></h3>
            <p><?= $temFiltros ? 'Ajuste a busca ou os filtros para encontrar uma partida.' : 'Abra a primeira partida de um pacote e comece a receber reservas.' ?></p>
            <?php if ($temFiltros): ?>
                <a class="button button--secondary" href="<?= url('viagens') ?>">Limpar filtros</a>
            <?php else: ?>
                <a class="button button--primary" href="<?= url('viagens', ['acao' => 'novo']) ?>"><?= icon('plus', 'icon', 16) ?> Criar partida</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</section>
