<?php
$formas = [
    'pix'           => 'Pix',
    'cartao'        => 'Cartão',
    'boleto'        => 'Boleto',
    'transferencia' => 'Transferência',
];
?>

<div class="page-head">
    <div class="page-head__copy">
        <h1>Pagamentos</h1>
        <p class="page-head__sub"><?= (int) $total ?> lançamento<?= $total === 1 ? '' : 's' ?> no recorte atual. Acompanhe parcelas, vencimentos e recebimentos vinculados a cada reserva.</p>
    </div>
    <div class="page-head__actions">
        <?php if ($viagemFiltroInfo): ?>
            <a class="button button--secondary" href="<?= url('viagens', ['acao' => 'ver', 'id' => $viagemFiltroInfo['id']]) ?>">
                <?= icon('plane', 'icon', 15) ?> Voltar à partida
            </a>
        <?php endif; ?>
        <a class="button button--primary" href="<?= url('pagamentos', ['acao' => 'novo']) ?>"><?= icon('plus', 'icon', 16) ?> Novo pagamento</a>
    </div>
</div>

<?php if ($viagemFiltroInfo): ?>
    <div class="list-meta">
        <?= icon('filter', 'icon', 12) ?>
        <span>Filtrando pela partida <strong><?= e($viagemFiltroInfo['destino_nome']) ?> · <?= e(data_br($viagemFiltroInfo['data_ida'])) ?></strong> — <?= e($viagemFiltroInfo['pacote_titulo']) ?></span>
        <a class="toolbar__clear" href="<?= url('pagamentos') ?>">Remover filtro</a>
    </div>
<?php endif; ?>

<section class="stat-strip" aria-label="Resumo financeiro filtrado">
    <div class="stat">
        <p class="stat__label">Lançado</p>
        <p class="stat__value mono"><?= e(money($resumo['lancado'])) ?></p>
    </div>
    <div class="stat">
        <p class="stat__label">Recebido</p>
        <p class="stat__value mono"><?= e(money($resumo['recebido'])) ?></p>
    </div>
    <div class="stat">
        <p class="stat__label">Pendente</p>
        <p class="stat__value mono"><?= e(money($resumo['pendente'])) ?></p>
    </div>
    <div class="stat">
        <p class="stat__label">Vencido</p>
        <p class="stat__value mono" style="<?= (float) $resumo['vencido'] > 0 ? 'color: var(--danger);' : '' ?>"><?= e(money($resumo['vencido'])) ?></p>
        <?php if ((float) $resumo['vencido'] > 0): ?>
            <p class="stat__meta" style="color: var(--danger);">cobrança em atraso</p>
        <?php endif; ?>
    </div>
</section>

<form class="toolbar" method="get" action="<?= url('pagamentos') ?>" role="search">
    <input type="hidden" name="r" value="pagamentos">
    <?php if ($viagemFiltro > 0): ?>
        <input type="hidden" name="viagem_id" value="<?= (int) $viagemFiltro ?>">
    <?php endif; ?>
    <div class="toolbar__search">
        <?= icon('search', 'icon', 16) ?>
        <label class="sr-only" for="busca-pagamentos">Buscar pagamentos</label>
        <input type="search" id="busca-pagamentos" name="busca" value="<?= e($busca) ?>"
               placeholder="Buscar por reserva, cliente, destino ou pacote…" autocomplete="off">
    </div>
    <div class="toolbar__filters">
        <label class="sr-only" for="filtro-status">Filtrar por situação</label>
        <select id="filtro-status" name="status">
            <option value="">Todas as situações</option>
            <option value="pendente" <?= $statusFiltro === 'pendente' ? 'selected' : '' ?>>Pendente</option>
            <option value="pago" <?= $statusFiltro === 'pago' ? 'selected' : '' ?>>Pago</option>
            <option value="estornado" <?= $statusFiltro === 'estornado' ? 'selected' : '' ?>>Estornado</option>
        </select>

        <label class="sr-only" for="filtro-forma">Filtrar por forma</label>
        <select id="filtro-forma" name="forma">
            <option value="">Todas as formas</option>
            <?php foreach ($formas as $chave => $rotulo): ?>
                <option value="<?= e($chave) ?>" <?= $formaFiltro === $chave ? 'selected' : '' ?>><?= e($rotulo) ?></option>
            <?php endforeach; ?>
        </select>

        <label class="sr-only" for="filtro-situacao">Filtrar por vencimento</label>
        <select id="filtro-situacao" name="situacao">
            <option value="">Qualquer vencimento</option>
            <option value="vencidos" <?= $situacao === 'vencidos' ? 'selected' : '' ?>>Vencidos</option>
            <option value="avencer" <?= $situacao === 'avencer' ? 'selected' : '' ?>>A vencer em 7 dias</option>
        </select>

        <button class="button button--secondary button--small" type="submit"><?= icon('filter', 'icon', 14) ?> Filtrar</button>
        <?php if ($temFiltros): ?>
            <a class="toolbar__clear" href="<?= url('pagamentos') ?>">Limpar filtros</a>
        <?php endif; ?>
    </div>
    <span class="toolbar__spacer"></span>
    <span class="list-meta" style="margin: 0;"><?= icon('banknote', 'icon', 13) ?> <?= (int) $total ?> lançamentos</span>
</form>

<section class="panel panel--flush">
    <?php if ($pagamentos): ?>
        <div class="table-wrap">
            <table class="data-table">
                <caption class="sr-only">Lançamentos de pagamento</caption>
                <thead>
                    <tr>
                        <th scope="col">Valor</th>
                        <th scope="col">Reserva / cliente</th>
                        <th scope="col">Partida</th>
                        <th scope="col">Forma</th>
                        <th scope="col">Situação</th>
                        <th scope="col">Vencimento</th>
                        <th scope="col" class="col-recebimento">Recebimento</th>
                        <th scope="col"><span class="sr-only">Ações</span></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pagamentos as $pagamento): ?>
                        <tr>
                            <td>
                                <span class="cell-strong mono"><?= e(money($pagamento['valor'])) ?></span>
                                <div class="cell-sub">#<?= str_pad((string) $pagamento['id'], 5, '0', STR_PAD_LEFT) ?></div>
                            </td>
                            <td>
                                <a class="cell-main mono" href="<?= url('reservas', ['acao' => 'ver', 'id' => $pagamento['reserva_id']]) ?>"><?= e($pagamento['reserva_codigo']) ?></a>
                                <div class="cell-sub"><?= e($pagamento['cliente_nome']) ?></div>
                            </td>
                            <td>
                                <span class="cell-main"><?= e($pagamento['destino_nome']) ?></span>
                                <div class="cell-sub cell-sub--clip" title="<?= e(data_br($pagamento['data_ida']) . ' · ' . $pagamento['pacote_titulo']) ?>"><?= e(data_br($pagamento['data_ida'])) ?> · <?= e($pagamento['pacote_titulo']) ?></div>
                            </td>
                            <td><?= e(forma_pagamento_label($pagamento['forma'])) ?></td>
                            <td>
                                <span class="badge badge--<?= e(badge_tom($pagamento['status'])) ?>"><?= e(status_pagamento_label($pagamento['status'])) ?></span>
                                <?php if ((int) $pagamento['vencido'] === 1): ?>
                                    <div class="cell-sub" style="color: var(--danger); margin-top: 4px;">em atraso</div>
                                <?php endif; ?>
                                <?php if ($pagamento['status'] === 'pago' && !empty($pagamento['data_pagamento'])): ?>
                                    <div class="cell-sub only-compact">receb. <?= e(data_br($pagamento['data_pagamento'])) ?></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="<?= (int) $pagamento['vencido'] === 1 ? 'cell-strong' : '' ?>" style="<?= (int) $pagamento['vencido'] === 1 ? 'color: var(--danger);' : '' ?>">
                                    <?= e(data_br($pagamento['vencimento'])) ?>
                                </span>
                            </td>
                            <td class="col-recebimento"><?= e(data_br($pagamento['data_pagamento'])) ?></td>
                            <td class="cell-actions">
                                <div class="cell-actions__group">
                                    <?php if ($pagamento['status'] === 'pendente'): ?>
                                        <form class="action-form" method="post" action="<?= url('pagamentos', ['acao' => 'pagar', 'id' => $pagamento['id']]) ?>">
                                            <?= csrf_field() ?>
                                            <button class="action-link" type="submit" aria-label="Marcar pagamento de <?= e(money($pagamento['valor'])) ?> como recebido" title="Marcar como recebido">
                                                <?= icon('check', 'icon', 15) ?>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    <a class="action-link" href="<?= url('pagamentos', ['acao' => 'editar', 'id' => $pagamento['id']]) ?>" aria-label="Editar pagamento de <?= e(money($pagamento['valor'])) ?>" title="Editar">
                                        <?= icon('edit', 'icon', 15) ?>
                                    </a>
                                    <?php if ($pagamento['status'] !== 'pago'): ?>
                                        <form class="action-form" method="post" action="<?= url('pagamentos', ['acao' => 'excluir', 'id' => $pagamento['id']]) ?>"
                                              data-confirm="Excluir o lançamento de <?= e(money($pagamento['valor'])) ?> da reserva <?= e($pagamento['reserva_codigo']) ?>?"
                                              data-confirm-label="Excluir lançamento">
                                            <?= csrf_field() ?>
                                            <button class="action-link action-link--danger" type="submit" aria-label="Excluir lançamento de <?= e(money($pagamento['valor'])) ?>" title="Excluir">
                                                <?= icon('trash', 'icon', 15) ?>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    <a class="action-link" href="<?= url('reservas', ['acao' => 'ver', 'id' => $pagamento['reserva_id']]) ?>" aria-label="Abrir reserva <?= e($pagamento['reserva_codigo']) ?>" title="Abrir reserva">
                                        <?= icon('arrow-up-right', 'icon', 15) ?>
                                    </a>
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
            <span class="empty-state__mark"><?= icon('banknote', 'icon', 20) ?></span>
            <h3><?= $temFiltros ? 'Nenhum lançamento encontrado' : 'Nenhum pagamento registrado' ?></h3>
            <p><?= $temFiltros ? 'Ajuste a busca ou os filtros para encontrar o lançamento.' : 'Registre o primeiro recebimento ou uma parcela a vencer.' ?></p>
            <?php if ($temFiltros): ?>
                <a class="button button--secondary" href="<?= url('pagamentos') ?>">Limpar filtros</a>
            <?php else: ?>
                <a class="button button--primary" href="<?= url('pagamentos', ['acao' => 'novo']) ?>"><?= icon('plus', 'icon', 16) ?> Registrar pagamento</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</section>
