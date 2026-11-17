<?php
$percentualRecebido = (float) $resumo['contratado'] > 0
    ? min(100, (int) round(((float) $resumo['pago'] / (float) $resumo['contratado']) * 100))
    : 0;
$financeiroOpcoes = [
    'aberto'  => 'Com saldo em aberto',
    'pago'    => 'Totalmente pagas',
    'vencido' => 'Com parcela vencida',
];
?>

<div class="page-head">
    <div class="page-head__copy">
        <h1>Reservas</h1>
        <p class="page-head__sub"><?= (int) $total ?> reserva<?= $total === 1 ? '' : 's' ?> no recorte atual. Cada reserva ocupa lugares na partida e reúne o histórico de pagamentos do cliente.</p>
    </div>
    <div class="page-head__actions">
        <?php if ($viagemFiltroInfo): ?>
            <a class="button button--secondary" href="<?= url('viagens', ['acao' => 'ver', 'id' => $viagemFiltroInfo['id']]) ?>">
                <?= icon('plane', 'icon', 15) ?> Voltar à partida
            </a>
        <?php endif; ?>
        <a class="button button--primary" href="<?= url('reservas', ['acao' => 'novo']) ?>"><?= icon('plus', 'icon', 16) ?> Nova reserva</a>
    </div>
</div>

<?php if ($viagemFiltroInfo): ?>
    <div class="list-meta">
        <?= icon('filter', 'icon', 12) ?>
        <span>Filtrando pela partida <strong><?= e($viagemFiltroInfo['destino_nome']) ?> · <?= e(data_br($viagemFiltroInfo['data_ida'])) ?></strong> — <?= e($viagemFiltroInfo['pacote_titulo']) ?></span>
        <a class="toolbar__clear" href="<?= url('reservas') ?>">Remover filtro</a>
    </div>
<?php endif; ?>

<section class="stat-strip" aria-label="Resumo das reservas filtradas">
    <div class="stat">
        <p class="stat__label">Reservas</p>
        <p class="stat__value"><?= (int) $resumo['total'] ?></p>
    </div>
    <div class="stat">
        <p class="stat__label">Viajantes</p>
        <p class="stat__value"><?= (int) $resumo['viajantes'] ?></p>
    </div>
    <div class="stat">
        <p class="stat__label">Total contratado</p>
        <p class="stat__value mono"><?= e(money($resumo['contratado'])) ?></p>
    </div>
    <div class="stat">
        <p class="stat__label">Saldo em aberto</p>
        <p class="stat__value mono"><?= e(money($resumo['aberto'])) ?></p>
        <p class="stat__meta"><?= $percentualRecebido ?>% recebido</p>
    </div>
</section>

<form class="toolbar" method="get" action="<?= url('reservas') ?>" role="search">
    <input type="hidden" name="r" value="reservas">
    <?php if ($viagemFiltro > 0): ?>
        <input type="hidden" name="viagem_id" value="<?= (int) $viagemFiltro ?>">
    <?php endif; ?>
    <div class="toolbar__search">
        <?= icon('search', 'icon', 16) ?>
        <label class="sr-only" for="busca-reservas">Buscar reservas</label>
        <input type="search" id="busca-reservas" name="busca" value="<?= e($busca) ?>"
               placeholder="Buscar por código, cliente, destino ou pacote…" autocomplete="off">
    </div>
    <div class="toolbar__filters">
        <label class="sr-only" for="filtro-status">Filtrar por situação</label>
        <select id="filtro-status" name="status">
            <option value="">Todas as situações</option>
            <option value="pendente" <?= $statusFiltro === 'pendente' ? 'selected' : '' ?>>Pendente</option>
            <option value="confirmada" <?= $statusFiltro === 'confirmada' ? 'selected' : '' ?>>Confirmada</option>
            <option value="cancelada" <?= $statusFiltro === 'cancelada' ? 'selected' : '' ?>>Cancelada</option>
        </select>

        <label class="sr-only" for="filtro-financeiro">Filtrar por situação financeira</label>
        <select id="filtro-financeiro" name="financeiro">
            <option value="">Qualquer financeiro</option>
            <?php foreach ($financeiroOpcoes as $chave => $rotulo): ?>
                <option value="<?= e($chave) ?>" <?= $financeiro === $chave ? 'selected' : '' ?>><?= e($rotulo) ?></option>
            <?php endforeach; ?>
        </select>

        <button class="button button--secondary button--small" type="submit"><?= icon('filter', 'icon', 14) ?> Filtrar</button>
        <?php if ($temFiltros): ?>
            <a class="toolbar__clear" href="<?= url('reservas') ?>">Limpar filtros</a>
        <?php endif; ?>
    </div>
    <span class="toolbar__spacer"></span>
    <span class="list-meta" style="margin: 0;"><?= icon('ticket', 'icon', 13) ?> <?= (int) $total ?> reservas</span>
</form>

<section class="panel panel--flush">
    <?php if ($reservas): ?>
        <div class="table-wrap">
            <table class="data-table">
                <caption class="sr-only">Reservas cadastradas</caption>
                <thead>
                    <tr>
                        <th scope="col">Reserva</th>
                        <th scope="col">Cliente</th>
                        <th scope="col">Partida</th>
                        <th scope="col">Viajantes</th>
                        <th scope="col">Situação</th>
                        <th scope="col">Financeiro</th>
                        <th scope="col"><span class="sr-only">Ações</span></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reservas as $item): ?>
                        <?php
                        $pago = (float) $item['valor_pago'];
                        $totalReserva = (float) $item['valor_total'];
                        $saldo = max(0, $totalReserva - $pago);
                        $percentual = $totalReserva > 0 ? min(100, (int) round(($pago / $totalReserva) * 100)) : 0;
                        $vencidos = (int) ($item['vencidos'] ?? 0);
                        ?>
                        <tr>
                            <td>
                                <a class="cell-main mono" href="<?= url('reservas', ['acao' => 'ver', 'id' => $item['id']]) ?>"><?= e($item['codigo']) ?></a>
                                <div class="cell-sub"><?= e(data_br($item['data_reserva'])) ?></div>
                            </td>
                            <td>
                                <div class="person-cell">
                                    <span class="avatar"><?= e(iniciais($item['cliente_nome'])) ?></span>
                                    <div class="person-cell__copy">
                                        <a class="cell-main" href="<?= url('clientes', ['acao' => 'ver', 'id' => $item['cliente_id']]) ?>"><?= e($item['cliente_nome']) ?></a>
                                        <div class="cell-sub"><?= e($item['cliente_telefone']) ?></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="cell-main"><?= e($item['destino_nome']) ?></span>
                                <div class="cell-sub cell-sub--clip" title="<?= e(data_br($item['data_ida']) . ' — ' . $item['pacote_titulo']) ?>"><?= e(data_br($item['data_ida'])) ?> — <?= e($item['pacote_titulo']) ?></div>
                            </td>
                            <td class="cell-strong"><?= (int) $item['viajantes'] ?></td>
                            <td>
                                <span class="badge badge--<?= e(badge_tom($item['status'])) ?>"><?= e(status_reserva_label($item['status'])) ?></span>
                                <?php if ($vencidos > 0 && $item['status'] !== 'cancelada'): ?>
                                    <div class="cell-sub" style="color: var(--danger); margin-top: 4px;"><?= $vencidos ?> parcela<?= $vencidos === 1 ? '' : 's' ?> vencida<?= $vencidos === 1 ? '' : 's' ?></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="progress progress--finance">
                                    <div class="progress__head">
                                        <span><?= $percentual ?>% pago</span>
                                        <strong><?= e(money($pago)) ?></strong>
                                    </div>
                                    <div class="progress__track">
                                        <div class="progress__fill <?= $percentual >= 100 ? 'progress__fill--full' : ($percentual < 50 ? 'progress__fill--warn' : '') ?>"
                                             style="width: <?= $percentual ?>%"></div>
                                    </div>
                                </div>
                                <div class="cell-sub">saldo <?= e(money($saldo)) ?></div>
                            </td>
                            <td class="cell-actions">
                                <div class="cell-actions__group">
                                    <a class="action-link" href="<?= url('reservas', ['acao' => 'ver', 'id' => $item['id']]) ?>" aria-label="Abrir reserva <?= e($item['codigo']) ?>" title="Abrir reserva">
                                        <?= icon('eye', 'icon', 15) ?>
                                    </a>
                                    <a class="action-link" href="<?= url('reservas', ['acao' => 'editar', 'id' => $item['id']]) ?>" aria-label="Editar reserva <?= e($item['codigo']) ?>" title="Editar">
                                        <?= icon('edit', 'icon', 15) ?>
                                    </a>
                                    <a class="action-link" href="<?= url('pagamentos', ['acao' => 'novo', 'reserva_id' => $item['id']]) ?>" aria-label="Adicionar pagamento à reserva <?= e($item['codigo']) ?>" title="Adicionar pagamento">
                                        <?= icon('banknote', 'icon', 15) ?>
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
            <span class="empty-state__mark"><?= icon('ticket', 'icon', 20) ?></span>
            <h3><?= $temFiltros ? 'Nenhuma reserva encontrada' : 'Nenhuma reserva cadastrada' ?></h3>
            <p><?= $temFiltros ? 'Ajuste a busca ou os filtros para encontrar a reserva.' : 'Registre a primeira reserva para vincular um cliente a uma partida.' ?></p>
            <?php if ($temFiltros): ?>
                <a class="button button--secondary" href="<?= url('reservas') ?>">Limpar filtros</a>
            <?php else: ?>
                <a class="button button--primary" href="<?= url('reservas', ['acao' => 'novo']) ?>"><?= icon('plus', 'icon', 16) ?> Registrar reserva</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</section>
