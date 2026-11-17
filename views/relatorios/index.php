<?php
$compacto = static function (float|int|string $valor): string {
    $numero = (float) $valor;
    if ($numero >= 1000) {
        return number_format($numero / 1000, 1, ',', '.') . ' mil';
    }

    return number_format($numero, 0, ',', '.');
};

$periodoLabel = data_br($inicio) . ' a ' . data_br($fim);
$destinoLabel = $destinoAtual ? $destinoAtual['nome'] : 'Todos os destinos';
$totalPeriodo = array_sum(array_column($meses, 'total'));
$taxaCancelamento = ((int) $kpis['reservas'] + (int) $kpis['canceladas']) > 0
    ? round(((int) $kpis['canceladas'] / ((int) $kpis['reservas'] + (int) $kpis['canceladas'])) * 100)
    : 0;

$topDestinoReceita = $porDestino[0] ?? null;
$fatiaTopDestino = ($topDestinoReceita !== null && (float) $kpis['receita'] > 0)
    ? (int) round(((float) $topDestinoReceita['total'] / (float) $kpis['receita']) * 100)
    : 0;
$partidaMaisCheia = $ocupacao[0] ?? null;
$shareSaldo = (float) $kpis['contratado'] > 0
    ? (int) round(((float) $kpis['saldo'] / (float) $kpis['contratado']) * 100)
    : 0;
$reservasConsideradas = (int) $kpis['reservas'] + (int) $kpis['canceladas'];

$paramsExportBase = ['acao' => 'exportar', 'inicio' => $inicio, 'fim' => $fim];
if ($destinoId > 0) {
    $paramsExportBase['destino_id'] = $destinoId;
}
?>

<div class="page-head">
    <div class="page-head__copy">
        <h1>Relatórios de operação</h1>
        <p class="page-head__sub">Receita, reservas, ocupação e desempenho por destino no período de <strong><?= e($periodoLabel) ?></strong> — <?= e($destinoLabel) ?>.</p>
    </div>
    <div class="page-head__actions">
        <button class="button button--ghost button--small" type="button" data-print>
            <?= icon('printer', 'icon', 15) ?> Imprimir
        </button>
        <a class="button button--secondary button--small" href="<?= e(url('relatorios', $paramsExportBase + ['tipo' => 'receita'])) ?>">
            <?= icon('download', 'icon', 14) ?> CSV receita
        </a>
        <a class="button button--secondary button--small" href="<?= e(url('relatorios', $paramsExportBase + ['tipo' => 'reservas'])) ?>">
            <?= icon('download', 'icon', 14) ?> CSV reservas
        </a>
    </div>
</div>

<form class="report-filters" method="get" action="<?= url('relatorios') ?>">
    <input type="hidden" name="r" value="relatorios">
    <div class="field">
        <label for="inicio">De</label>
        <input type="date" id="inicio" name="inicio" value="<?= e($inicio) ?>" required>
    </div>
    <div class="field">
        <label for="fim">Até</label>
        <input type="date" id="fim" name="fim" value="<?= e($fim) ?>" required>
    </div>
    <div class="field">
        <label for="destino_id">Destino</label>
        <select id="destino_id" name="destino_id">
            <option value="">Todos os destinos</option>
            <?php foreach ($destinos as $destino): ?>
                <option value="<?= (int) $destino['id'] ?>" <?= $destinoId === (int) $destino['id'] ? 'selected' : '' ?>>
                    <?= e($destino['nome']) ?> — <?= e($destino['pais']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="report-filters__actions">
        <?php if ($destinoId > 0 || $inicio !== ($inicioPadrao ?? '') || $fim !== date('Y-m-d')): ?>
            <a class="toolbar__clear" href="<?= url('relatorios') ?>">Período padrão</a>
        <?php endif; ?>
        <button class="button button--primary button--small" type="submit"><?= icon('filter', 'icon', 14) ?> Aplicar filtros</button>
    </div>
</form>

<section class="report-kpis" aria-label="Indicadores do período">
    <div class="report-kpi">
        <p class="report-kpi__label">Receita recebida</p>
        <p class="report-kpi__value"><?= e(money($kpis['receita'])) ?></p>
        <p class="report-kpi__meta"><?= (int) $kpis['recebimentos'] ?> recebimento<?= (int) $kpis['recebimentos'] === 1 ? '' : 's' ?></p>
    </div>
    <div class="report-kpi">
        <p class="report-kpi__label">Reservas ativas no período</p>
        <p class="report-kpi__value"><?= (int) $kpis['reservas'] ?></p>
        <p class="report-kpi__meta"><?= (int) $kpis['canceladas'] ?> cancelada<?= (int) $kpis['canceladas'] === 1 ? '' : 's' ?></p>
    </div>
    <div class="report-kpi">
        <p class="report-kpi__label">Viajantes</p>
        <p class="report-kpi__value"><?= (int) $kpis['viajantes'] ?></p>
        <p class="report-kpi__meta">em reservas não canceladas</p>
    </div>
    <div class="report-kpi">
        <p class="report-kpi__label">Ticket médio</p>
        <p class="report-kpi__value"><?= e(money($kpis['ticket'])) ?></p>
        <p class="report-kpi__meta">por reserva ativa</p>
    </div>
    <div class="report-kpi">
        <p class="report-kpi__label">Saldo em aberto</p>
        <p class="report-kpi__value"><?= e(money($kpis['saldo'])) ?></p>
        <p class="report-kpi__meta">de <?= e(money($kpis['contratado'])) ?> contratados</p>
    </div>
</section>

<section class="report-reading" aria-labelledby="report-reading-title">
    <header class="report-reading__head">
        <h2 class="report-reading__title" id="report-reading-title"><?= icon('sparkles', 'icon', 15) ?> Leitura rápida</h2>
        <p class="report-reading__hint">Três sinais para começar a análise do período.</p>
    </header>
    <div class="report-reading__grid">
        <article class="reading-item">
            <p class="reading-item__label"><span class="reading-item__index">01</span> Receita</p>
            <p class="reading-item__text">
                <?php if ((float) $kpis['receita'] > 0): ?>
                    <strong><?= e(money($kpis['receita'])) ?></strong> recebidos em <?= (int) $kpis['recebimentos'] ?> pagamento<?= (int) $kpis['recebimentos'] === 1 ? '' : 's' ?>.
                    <?php if ($topDestinoReceita !== null): ?>
                        <?= e($topDestinoReceita['destino']) ?> concentra <strong><?= $fatiaTopDestino ?>%</strong> do total.
                    <?php endif; ?>
                <?php else: ?>
                    Nenhum pagamento foi recebido no período selecionado.
                <?php endif; ?>
            </p>
        </article>
        <article class="reading-item">
            <p class="reading-item__label"><span class="reading-item__index">02</span> Ocupação</p>
            <p class="reading-item__text">
                <?php if ($partidaMaisCheia !== null): ?>
                    <strong><?= e($partidaMaisCheia['destino_nome']) ?></strong>, com partida em <?= e(data_br($partidaMaisCheia['data_ida'])) ?>, está com <strong><?= (int) $partidaMaisCheia['percentual'] ?>%</strong> dos lugares ocupados
                    (<?= (int) $partidaMaisCheia['lugares_ocupados'] ?> de <?= (int) $partidaMaisCheia['capacidade'] ?>).
                <?php else: ?>
                    Nenhuma partida ativa inicia no período selecionado.
                <?php endif; ?>
            </p>
        </article>
        <article class="reading-item <?= (float) $kpis['saldo'] > 0 ? 'reading-item--alert' : '' ?>">
            <p class="reading-item__label"><span class="reading-item__index">03</span> Atenção</p>
            <p class="reading-item__text">
                <?php if ((float) $kpis['saldo'] > 0): ?>
                    <strong><?= e(money($kpis['saldo'])) ?></strong> em aberto (<strong><?= $shareSaldo ?>%</strong> do contratado) em <?= (int) $kpis['reservas'] ?> reserva<?= (int) $kpis['reservas'] === 1 ? '' : 's' ?> ativa<?= (int) $kpis['reservas'] === 1 ? '' : 's' ?>.
                <?php else: ?>
                    Nenhum saldo em aberto nas reservas ativas.
                <?php endif; ?>
                <?php if ((int) $kpis['canceladas'] > 0 && $reservasConsideradas > 0): ?>
                    Taxa de cancelamento de <strong><?= $taxaCancelamento ?>%</strong>
                    (<?= (int) $kpis['canceladas'] ?> de <?= $reservasConsideradas ?> reservas).
                <?php elseif ($reservasConsideradas > 0): ?>
                    Nenhuma reserva cancelada no período.
                <?php endif; ?>
            </p>
        </article>
    </div>
</section>

<section class="report-section">
    <div class="panel">
        <header class="panel__head">
            <h2 class="panel__title">Receita recebida por mês <small><?= e($periodoLabel) ?></small></h2>
            <span class="list-meta" style="margin: 0;"><?= icon('banknote', 'icon', 12) ?> Total no gráfico: <strong><?= e(money($totalPeriodo)) ?></strong></span>
        </header>
        <div class="panel__body">
            <?php if ($meses): ?>
                <div style="overflow-x: auto;">
                    <div class="report-chart" role="img" aria-label="Gráfico de receita recebida por mês entre <?= e($periodoLabel) ?>">
                        <?php foreach ($meses as $mes): ?>
                            <?php $altura = $maiorMes > 0 ? max(3, (int) round(($mes['total'] / $maiorMes) * 100)) : 3; ?>
                            <div class="report-chart__col" style="min-width: 42px;" title="<?= e($mes['label'] . ': ' . money($mes['total'])) ?>">
                                <span class="report-chart__value"><?= e($compacto($mes['total'])) ?></span>
                                <span class="report-chart__track">
                                    <span class="report-chart__bar <?= $mes['atual'] ? 'report-chart__bar--accent' : '' ?>" style="height: <?= $altura ?>%"></span>
                                </span>
                                <span class="report-chart__label"><?= e($mes['label']) ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php if ($graficoLimitado): ?>
                    <p class="report-kpi__meta" style="margin-top: 10px;">O intervalo tem mais de 24 meses; o gráfico exibe os últimos 24. Os totais e o CSV consideram todo o período.</p>
                <?php endif; ?>
            <?php else: ?>
                <p class="muted">Nenhum mês no intervalo selecionado.</p>
            <?php endif; ?>
        </div>
    </div>
</section>

<div class="report-grid">
    <section class="panel">
        <header class="panel__head">
            <h2 class="panel__title">Receita por forma de pagamento</h2>
            <span class="list-meta" style="margin: 0;"><?= e(money($kpis['receita'])) ?> recebidos</span>
        </header>
        <div class="panel__body">
            <?php if ($porForma): ?>
                <div class="hbar-list">
                    <?php foreach ($porForma as $forma): ?>
                        <?php
                        $percentual = (float) $kpis['receita'] > 0
                            ? ((float) $forma['total'] / (float) $kpis['receita']) * 100
                            : 0;
                        ?>
                        <div class="hbar">
                            <span class="hbar__label"><?= e(forma_pagamento_label($forma['forma'])) ?></span>
                            <span class="hbar__track"><span class="hbar__fill" style="width: <?= round($percentual) ?>%"></span></span>
                            <span class="hbar__value"><?= e($compacto($forma['total'])) ?><br><small><?= round($percentual) ?>%</small></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state" style="padding: 24px;">
                    <p>Nenhum pagamento recebido no período.</p>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <section class="panel">
        <header class="panel__head">
            <h2 class="panel__title">Receita por destino</h2>
            <span class="list-meta" style="margin: 0;">Top <?= count($porDestino) ?></span>
        </header>
        <div class="panel__body">
            <?php if ($porDestino): ?>
                <div class="rank-list">
                    <?php foreach ($porDestino as $indice => $destino): ?>
                        <div class="rank-item">
                            <span class="rank-item__index"><?= str_pad((string) ($indice + 1), 2, '0', STR_PAD_LEFT) ?></span>
                            <span class="rank-item__name">
                                <strong><?= e($destino['destino']) ?></strong>
                                <small><?= e($destino['pais']) ?> · <?= (int) $destino['qtd'] ?> recebimento<?= (int) $destino['qtd'] === 1 ? '' : 's' ?></small>
                            </span>
                            <span class="rank-item__value"><?= e(money($destino['total'])) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="muted">Nenhuma receita por destino no período.</p>
            <?php endif; ?>
        </div>
    </section>

    <section class="panel">
        <header class="panel__head">
            <h2 class="panel__title">Ocupação das partidas</h2>
            <span class="list-meta" style="margin: 0;">Partidas que iniciam no período</span>
        </header>
        <div class="panel__body">
            <?php if ($ocupacao): ?>
                <div class="rank-list">
                    <?php foreach ($ocupacao as $partida): ?>
                        <div class="rank-item">
                            <span class="rank-item__index"><?= e(dia($partida['data_ida'])) ?>/<?= e(mes_abreviado($partida['data_ida'])) ?></span>
                            <span class="rank-item__name">
                                <strong><?= e($partida['destino_nome']) ?></strong>
                                <small><?= e($partida['pacote_titulo']) ?> · <?= (int) $partida['lugares_ocupados'] ?>/<?= (int) $partida['capacidade'] ?> lugares</small>
                            </span>
                            <span class="rank-item__value"><?= (int) $partida['percentual'] ?>%</span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="muted">Nenhuma partida ativa inicia no período selecionado.</p>
            <?php endif; ?>
        </div>
    </section>

    <section class="panel">
        <header class="panel__head">
            <h2 class="panel__title">Clientes por valor contratado</h2>
            <span class="list-meta" style="margin: 0;">Reservas do período</span>
        </header>
        <div class="panel__body">
            <?php if ($topClientes): ?>
                <div class="rank-list">
                    <?php foreach ($topClientes as $indice => $cliente): ?>
                        <div class="rank-item">
                            <span class="rank-item__index"><?= str_pad((string) ($indice + 1), 2, '0', STR_PAD_LEFT) ?></span>
                            <span class="rank-item__name">
                                <strong><?= e($cliente['cliente_nome']) ?></strong>
                                <small><?= (int) $cliente['reservas'] ?> reserva<?= (int) $cliente['reservas'] === 1 ? '' : 's' ?> · <?= (int) $cliente['viajantes'] ?> viajante<?= (int) $cliente['viajantes'] === 1 ? '' : 's' ?></small>
                            </span>
                            <span class="rank-item__value"><?= e(money($cliente['contratado'])) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="muted">Nenhuma reserva ativa no período.</p>
            <?php endif; ?>
        </div>
    </section>
</div>

<div class="report-grid">
    <section class="panel">
        <header class="panel__head">
            <h2 class="panel__title">Reservas por situação</h2>
        </header>
        <div class="panel__body">
            <?php if ($porStatus): ?>
                <div class="hbar-list">
                    <?php foreach ($porStatus as $status): ?>
                        <div class="hbar">
                            <span class="hbar__label"><?= e(status_reserva_label($status['status'])) ?></span>
                            <span class="hbar__track">
                                <span class="hbar__fill" style="width: <?= max(4, (int) round(((int) $status['qtd'] / $maiorStatus) * 100)) ?>%;<?= $status['status'] === 'cancelada' ? 'background: var(--danger);' : '' ?>"></span>
                            </span>
                            <span class="hbar__value"><?= (int) $status['qtd'] ?> · <?= (int) $status['viajantes'] ?> viaj.</span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="muted">Nenhuma reserva criada no período.</p>
            <?php endif; ?>
        </div>
    </section>

    <section class="panel">
        <header class="panel__head">
            <h2 class="panel__title">Resumo do período</h2>
        </header>
        <div class="panel__body">
            <dl class="info-list">
                <div class="info-row">
                    <dt>Total contratado</dt>
                    <dd class="mono"><?= e(money($kpis['contratado'])) ?></dd>
                </div>
                <div class="info-row">
                    <dt>Recebido das reservas</dt>
                    <dd class="mono"><?= e(money($kpis['pago'])) ?></dd>
                </div>
                <div class="info-row">
                    <dt>Saldo em aberto</dt>
                    <dd class="mono"><?= e(money($kpis['saldo'])) ?></dd>
                </div>
                <div class="info-row">
                    <dt>Taxa de cancelamento</dt>
                    <dd><?= $taxaCancelamento ?>% das reservas criadas</dd>
                </div>
                <div class="info-row">
                    <dt>Receita de pagamentos</dt>
                    <dd class="mono"><?= e(money($kpis['receita'])) ?></dd>
                </div>
            </dl>
            <p class="report-note">
                Receita recebida usa a data de pagamento. Reservas e ocupação usam a data de criação da reserva e a data de ida das partidas.
            </p>
        </div>
    </section>
</div>

<section class="report-section panel panel--flush">
    <header class="panel__head">
        <h2 class="panel__title">Reservas recentes no período <small><?= count($recentes) ?> de <?= (int) $kpis['reservas'] + (int) $kpis['canceladas'] ?></small></h2>
        <a class="section-head__link" href="<?= url('reservas') ?>">Abrir reservas <?= icon('arrow-up-right', 'icon', 11) ?></a>
    </header>
    <?php if ($recentes): ?>
        <div class="table-wrap">
            <table class="data-table">
                <caption class="sr-only">Reservas recentes no período do relatório</caption>
                <thead>
                    <tr>
                        <th scope="col">Reserva</th>
                        <th scope="col">Cliente</th>
                        <th scope="col">Destino</th>
                        <th scope="col">Partida</th>
                        <th scope="col">Situação</th>
                        <th scope="col">Total</th>
                        <th scope="col">Pago</th>
                        <th scope="col">Saldo</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentes as $reserva): ?>
                        <tr>
                            <td><a class="cell-main mono" href="<?= url('reservas', ['acao' => 'ver', 'id' => $reserva['id']]) ?>"><?= e($reserva['codigo']) ?></a></td>
                            <td><?= e($reserva['cliente_nome']) ?></td>
                            <td><?= e($reserva['destino_nome']) ?></td>
                            <td><?= e(data_br($reserva['data_ida'])) ?></td>
                            <td><span class="badge badge--<?= e(badge_tom($reserva['status'])) ?>"><?= e(status_reserva_label($reserva['status'])) ?></span></td>
                            <td class="mono"><?= e(money($reserva['valor_total'])) ?></td>
                            <td class="mono"><?= e(money($reserva['valor_pago'])) ?></td>
                            <td class="mono"><?= e(money(max(0, (float) $reserva['valor_total'] - (float) $reserva['valor_pago']))) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <span class="empty-state__mark"><?= icon('chart', 'icon', 20) ?></span>
            <h3>Nenhuma reserva no período</h3>
            <p>Ajuste as datas ou o destino para visualizar o relatório.</p>
        </div>
    <?php endif; ?>
</section>
