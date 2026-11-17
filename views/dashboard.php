<?php
$kpi = $dashboard;
$deltaPositivo = ($kpi['variacaoReceita'] ?? 0) >= 0;
?>
<div class="dashboard-hero">
    <div>
        <p class="dashboard-hero__date"><?= e(data_extenso()) ?></p>
        <h1><?= e($kpi['saudacao']) ?>, <?= e($kpi['primeiroNome']) ?>.</h1>
        <p class="dashboard-hero__sub">
            <span><?= (int) $kpi['partidasAtivas'] ?> partidas ativas</span>
            <span class="dot" aria-hidden="true"></span>
            <span><?= (int) $kpi['reservasPendentes'] ?> reservas aguardando confirmação</span>
            <?php if ($kpi['qtdVencidos'] > 0): ?>
                <span class="dot" aria-hidden="true"></span>
                <span><?= (int) $kpi['qtdVencidos'] ?> pagamentos vencidos</span>
            <?php endif; ?>
        </p>
    </div>
    <div class="page-head__actions">
        <a class="button button--secondary" href="<?= url('relatorios') ?>"><?= icon('chart', 'icon', 16) ?> Relatórios</a>
        <a class="button button--primary" href="<?= url('reservas', ['acao' => 'novo']) ?>"><?= icon('plus', 'icon', 16) ?> Registrar reserva</a>
    </div>
</div>

<section class="kpi-strip" aria-label="Indicadores do mês">
    <div class="kpi">
        <p class="kpi__label"><?= icon('banknote', 'icon', 13) ?> Receita recebida em <?= e(nome_mes((int) date('n'))) ?></p>
        <p class="kpi__value"><?= e(money($kpi['receitaMes'])) ?></p>
        <p class="kpi__meta">
            <?php if ($kpi['variacaoReceita'] === null): ?>
                <span>Sem base de comparação no mês anterior</span>
            <?php else: ?>
                <span class="kpi__delta <?= $deltaPositivo ? '' : 'kpi__delta--down' ?>">
                    <?= icon($deltaPositivo ? 'arrow-up-right' : 'arrow-down-right', 'icon', 12) ?>
                    <?= e(number_format(abs($kpi['variacaoReceita']), 1, ',', '.')) ?>%
                </span>
                <span>vs. <?= e($kpi['mesAnterior']) ?></span>
            <?php endif; ?>
            <span class="dot" aria-hidden="true"></span>
            <span><?= (int) $kpi['pagamentosMes'] ?> recebimentos</span>
        </p>
    </div>
    <div class="kpi">
        <p class="kpi__label"><?= icon('ticket', 'icon', 13) ?> Reservas ativas</p>
        <p class="kpi__value"><?= (int) $kpi['reservasAtivas'] ?></p>
        <p class="kpi__meta">
            <span><?= (int) $kpi['reservasPendentes'] ?> aguardando confirmação</span>
        </p>
    </div>
    <div class="kpi">
        <p class="kpi__label"><?= icon('users', 'icon', 13) ?> Viajantes confirmados</p>
        <p class="kpi__value"><?= (int) $kpi['viajantesConfirmados'] ?></p>
        <p class="kpi__meta">
            <span>em <?= (int) $kpi['partidasAtivas'] ?> partidas ativas</span>
        </p>
    </div>
    <div class="kpi">
        <p class="kpi__label"><?= icon('wallet', 'icon', 13) ?> A receber</p>
        <p class="kpi__value"><?= e(money($kpi['aReceber'])) ?></p>
        <p class="kpi__meta">
            <?php if ($kpi['vencido'] > 0): ?>
                <span class="kpi__delta kpi__delta--down"><?= e(money($kpi['vencido'])) ?> vencido</span>
            <?php else: ?>
                <span>Nenhum valor vencido</span>
            <?php endif; ?>
        </p>
    </div>
</section>

<div class="dashboard-grid">
    <section class="board" aria-labelledby="board-title">
        <header class="board__head">
            <h2 class="board__title" id="board-title">Próximas partidas <span>por data de embarque</span></h2>
            <a class="board__head-link" href="<?= url('viagens') ?>">Ver todas <?= icon('arrow-right', 'icon', 12) ?></a>
        </header>

        <?php if ($proximasPartidas): ?>
            <div class="board__columns" aria-hidden="true">
                <span>Data</span>
                <span>Destino / pacote</span>
                <span>Ocupação</span>
                <span>Situação</span>
                <span></span>
            </div>

            <?php foreach ($proximasPartidas as $indice => $partida): ?>
                <?php
                $capacidade = max(1, (int) $partida['capacidade']);
                $ocupados = (int) $partida['lugares_ocupados'];
                $percentual = min(100, (int) round(($ocupados / $capacidade) * 100));
                $situacao = situacao_viagem($partida);
                $fillClass = $percentual >= 100 ? 'seat-bar__fill--full' : ($percentual < 50 ? 'seat-bar__fill--low' : '');
                ?>
                <a class="board-row" style="--row: <?= (int) $indice ?>"
                   href="<?= url('viagens', ['acao' => 'ver', 'id' => $partida['id']]) ?>">
                    <span class="board-row__date">
                        <span class="board-row__day"><?= e(dia($partida['data_ida'])) ?></span>
                        <span class="board-row__month"><?= e(mes_abreviado($partida['data_ida'])) ?></span>
                    </span>
                    <span class="board-row__destination">
                        <span class="board-row__city"><?= e($partida['destino_nome']) ?> <span class="flag">· <?= e($partida['destino_pais']) ?></span></span>
                        <span class="board-row__package">V-<?= str_pad((string) $partida['id'], 2, '0', STR_PAD_LEFT) ?> · <?= e($partida['pacote_titulo']) ?></span>
                    </span>
                    <span class="board-row__seats">
                        <span class="seat-bar">
                            <span class="seat-bar__track"><span class="seat-bar__fill <?= $fillClass ?>" style="width: <?= $percentual ?>%"></span></span>
                            <span class="seat-bar__label"><?= $ocupados ?>/<?= (int) $partida['capacidade'] ?></span>
                        </span>
                        <small><?= $percentual ?>% reservado</small>
                    </span>
                    <span class="board-row__status">
                        <span class="stamp stamp--<?= e($situacao['tom']) ?>"><?= e($situacao['label']) ?></span>
                    </span>
                    <span class="board-row__chevron"><?= icon('chevron-right', 'icon', 15) ?></span>
                </a>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-state">
                <span class="empty-state__mark"><?= icon('plane', 'icon', 20) ?></span>
                <h3>Nenhuma partida programada</h3>
                <p>Cadastre uma viagem para acompanhar embarques, ocupação e receita prevista.</p>
                <a class="button button--secondary" href="<?= url('viagens', ['acao' => 'novo']) ?>">Programar viagem</a>
            </div>
        <?php endif; ?>
    </section>

    <div class="side-stack">
        <section class="panel" aria-labelledby="chart-title">
            <header class="panel__head">
                <h2 class="panel__title" id="chart-title">Receita recebida <small>últimos 6 meses</small></h2>
                <a class="section-head__link" href="<?= url('relatorios') ?>">Detalhar <?= icon('arrow-up-right', 'icon', 11) ?></a>
            </header>
            <div class="panel__body">
                <figure>
                    <div class="chart" role="img" aria-label="Receita recebida nos últimos seis meses">
                        <?php foreach ($meses as $mes): ?>
                            <?php $altura = $maiorReceita > 0 ? max(3, (int) round(($mes['total'] / $maiorReceita) * 100)) : 3; ?>
                            <div class="chart__col" title="<?= e($mes['label'] . ': ' . money($mes['total'])) ?>">
                                <span class="chart__value"><?= e($mes['total'] > 0 ? money($mes['total']) : '—') ?></span>
                                <span class="chart__track">
                                    <span class="chart__bar <?= $mes['atual'] ? 'chart__bar--current' : '' ?>" style="height: <?= $altura ?>%"></span>
                                </span>
                                <span class="chart__label"><?= e($mes['label']) ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <figcaption class="sr-only">
                        <?php foreach ($meses as $mes): ?>
                            <?= e($mes['label']) ?>: <?= e(money($mes['total'])) ?>.
                        <?php endforeach; ?>
                    </figcaption>
                </figure>
            </div>
            <footer class="panel__foot">
                <span>Total no período</span>
                <strong class="mono"><?= e(money($totalSemestre)) ?></strong>
            </footer>
        </section>

        <section class="panel" aria-labelledby="atencao-title">
            <header class="panel__head">
                <h2 class="panel__title" id="atencao-title">
                    Precisa de atenção
                    <?php if ($kpi['qtdVencidos'] > 0): ?>
                        <span class="badge badge--danger"><?= (int) $kpi['qtdVencidos'] ?></span>
                    <?php endif; ?>
                </h2>
                <a class="section-head__link" href="<?= url('pagamentos', ['status' => 'pendente']) ?>">Ver pagamentos <?= icon('arrow-up-right', 'icon', 11) ?></a>
            </header>

            <?php if ($pagamentosAtencao): ?>
                <div class="attention-list">
                    <?php foreach ($pagamentosAtencao as $pagamento): ?>
                        <a class="attention-item" href="<?= url('reservas', ['acao' => 'ver', 'id' => $pagamento['reserva_id']]) ?>">
                            <span class="attention-item__mark"><?= icon('clock', 'icon', 14) ?></span>
                            <span class="attention-item__copy">
                                <strong><?= e($pagamento['cliente_nome']) ?></strong>
                                <small><?= e($pagamento['codigo']) ?> · venceu <?= e(data_br($pagamento['vencimento'])) ?></small>
                            </span>
                            <span class="attention-item__value">
                                <strong><?= e(money($pagamento['valor'])) ?></strong>
                                <small><?= (int) $pagamento['dias_atraso'] ?>d em atraso</small>
                            </span>
                        </a>
                    <?php endforeach; ?>
                </div>
                <footer class="panel__foot">
                    <span>Total vencido</span>
                    <strong class="mono" style="color: var(--danger)"><?= e(money($kpi['vencido'])) ?></strong>
                </footer>
            <?php else: ?>
                <div class="panel__body">
                    <p class="muted" style="font-size: 12px;">Nenhum pagamento vencido. A operação está em dia.</p>
                </div>
            <?php endif; ?>
        </section>
    </div>
</div>

<section class="panel panel--flush" aria-labelledby="recentes-title">
    <header class="panel__head">
        <h2 class="panel__title" id="recentes-title">Reservas recentes <small>últimos lançamentos</small></h2>
        <a class="section-head__link" href="<?= url('reservas') ?>">Ver todas <?= icon('arrow-right', 'icon', 11) ?></a>
    </header>
    <div class="table-wrap">
        <table class="data-table">
            <caption class="sr-only">Reservas mais recentes da agência</caption>
            <thead>
                <tr>
                    <th scope="col">Reserva</th>
                    <th scope="col">Cliente</th>
                    <th scope="col">Partida</th>
                    <th scope="col">Situação</th>
                    <th scope="col">Pagamento</th>
                    <th scope="col"><span class="sr-only">Ações</span></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($reservasRecentes as $reserva): ?>
                    <?php $pago = (float) $reserva['valor_pago']; $total = (float) $reserva['valor_total']; $pct = $total > 0 ? min(100, round(($pago / $total) * 100)) : 0; ?>
                    <tr>
                        <td>
                            <a class="cell-main mono" href="<?= url('reservas', ['acao' => 'ver', 'id' => $reserva['id']]) ?>"><?= e($reserva['codigo']) ?></a>
                            <div class="cell-sub"><?= e(data_br($reserva['data_reserva'])) ?></div>
                        </td>
                        <td>
                            <a class="cell-main" href="<?= url('clientes', ['acao' => 'ver', 'id' => $reserva['cliente_id']]) ?>"><?= e($reserva['cliente_nome']) ?></a>
                            <div class="cell-sub"><?= (int) $reserva['viajantes'] ?> viajante<?= (int) $reserva['viajantes'] > 1 ? 's' : '' ?></div>
                        </td>
                        <td>
                            <span class="cell-main"><?= e($reserva['destino_nome']) ?></span>
                            <div class="cell-sub"><?= e(data_curta($reserva['data_ida'])) ?> — <?= e(data_curta($reserva['data_volta'])) ?></div>
                        </td>
                        <td><span class="badge badge--<?= e(badge_tom($reserva['status'])) ?>"><?= e(status_reserva_label($reserva['status'])) ?></span></td>
                        <td>
                            <span class="cell-strong mono"><?= e(money($pago)) ?></span>
                            <div class="cell-sub">de <?= e(money($total)) ?> · <?= (int) $pct ?>%</div>
                        </td>
                        <td class="cell-actions">
                            <a class="action-link" href="<?= url('reservas', ['acao' => 'ver', 'id' => $reserva['id']]) ?>" aria-label="Abrir reserva <?= e($reserva['codigo']) ?>" title="Abrir reserva">
                                <?= icon('arrow-up-right', 'icon', 15) ?>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
