<?php
$total = (float) $reserva['valor_total'];
$percentualPago = $total > 0 ? min(100, (int) round(($pago / $total) * 100)) : 0;
$situacaoTom = badge_tom($reserva['status']);
$hoje = date('Y-m-d');

$origem = 'São Paulo';
if (!empty($viagem['ponto_encontro'])) {
    $partes = preg_split('/\s+[—–-]\s+/', (string) $viagem['ponto_encontro']);
    $origem = trim((string) ($partes[0] ?? 'São Paulo'));
    $origem = preg_replace('/^(Aeroporto|Rodoviária|Terminal)\s+(de|do|da)?\s*/iu', '', $origem) ?: $origem;
}
?>

<div class="page-head page-head--compact">
    <div class="page-head__copy">
        <a class="page-head__back" href="<?= url('reservas') ?>"><?= icon('arrow-left', 'icon', 13) ?> Todas as reservas</a>
        <h1>Ficha da reserva</h1>
        <p class="page-head__sub">Localizador, passageiros, partida e situação financeira de <?= e($cliente['nome'] ?? $reserva['cliente_nome']) ?>.</p>
    </div>
    <div class="page-head__actions">
        <button class="button button--ghost button--small" type="button" data-print>
            <?= icon('printer', 'icon', 15) ?> Imprimir
        </button>
        <a class="button button--secondary" href="<?= url('reservas', ['acao' => 'editar', 'id' => $reserva['id']]) ?>">
            <?= icon('edit', 'icon', 15) ?> Editar
        </a>
        <a class="button button--primary" href="<?= url('pagamentos', ['acao' => 'novo', 'reserva_id' => $reserva['id']]) ?>">
            <?= icon('plus', 'icon', 15) ?> Pagamento
        </a>
    </div>
</div>

<article class="boarding-pass" aria-label="Cartão da reserva">
    <div class="boarding-pass__main">
        <p class="boarding-pass__eyebrow">
            <span>RESERVA <strong><?= e($reserva['codigo']) ?></strong></span>
            <span class="dot" aria-hidden="true"></span>
            <span><?= e($cliente['nome'] ?? $reserva['cliente_nome']) ?></span>
            <span class="dot" aria-hidden="true"></span>
            <span><?= e($viagem['pacote_titulo']) ?></span>
        </p>
        <div class="boarding-pass__route">
            <div class="boarding-pass__city">
                <small>Embarque</small>
                <h2><?= e($origem) ?></h2>
                <small><?= e(data_br($viagem['data_ida'])) ?></small>
            </div>
            <div class="boarding-pass__path" aria-hidden="true"><?= icon('plane', 'icon', 16) ?></div>
            <div class="boarding-pass__city" style="text-align: right;">
                <small>Destino</small>
                <h2><?= e($reserva['destino_nome']) ?></h2>
                <small><?= e($reserva['destino_pais']) ?></small>
            </div>
        </div>
        <div class="boarding-pass__facts">
            <div>
                <span class="boarding-fact__label">PASSAGEIRO PRINCIPAL</span>
                <span class="boarding-fact__value boarding-fact__value--lg"><?= e($cliente['nome'] ?? $reserva['cliente_nome']) ?></span>
            </div>
            <div>
                <span class="boarding-fact__label">VIAJANTES</span>
                <span class="boarding-fact__value boarding-fact__value--lg"><?= (int) $reserva['viajantes'] ?> pessoa<?= (int) $reserva['viajantes'] === 1 ? '' : 's' ?></span>
            </div>
            <div>
                <span class="boarding-fact__label">IDA</span>
                <span class="boarding-fact__value"><?= e(data_br($viagem['data_ida'])) ?></span>
            </div>
            <div>
                <span class="boarding-fact__label">VOLTA</span>
                <span class="boarding-fact__value"><?= e(data_br($viagem['data_volta'])) ?></span>
            </div>
        </div>
    </div>
    <div class="boarding-pass__stub">
        <div>
            <span class="stub-label">SITUAÇÃO</span>
            <span class="stub-status"><?= e(status_reserva_label($reserva['status'])) ?></span>
        </div>
        <div class="stub-divider" aria-hidden="true"></div>
        <div>
            <span class="stub-label">LOCALIZADOR</span>
            <span class="stub-value stub-value--big"><?= e($reserva['codigo']) ?></span>
        </div>
        <div>
            <span class="stub-label">TOTAL</span>
            <span class="stub-value"><?= e(money($total)) ?></span>
        </div>
        <div class="stub-divider" aria-hidden="true"></div>
        <div>
            <span class="stub-label">SALDO</span>
            <span class="stub-value"><?= e(money($saldo)) ?></span>
        </div>
    </div>
</article>

<section class="stat-strip" aria-label="Resumo financeiro da reserva">
    <div class="stat">
        <p class="stat__label">Valor da reserva</p>
        <p class="stat__value mono"><?= e(money($total)) ?></p>
    </div>
    <div class="stat">
        <p class="stat__label">Pago</p>
        <p class="stat__value mono"><?= e(money($pago)) ?></p>
        <p class="stat__meta"><?= $percentualPago ?>% quitado</p>
    </div>
    <div class="stat">
        <p class="stat__label">A pagar</p>
        <p class="stat__value mono"><?= e(money($pendente)) ?></p>
        <p class="stat__meta"><?= count(array_filter($pagamentos, static fn (array $p): bool => $p['status'] === 'pendente')) ?> parcela(s)</p>
    </div>
    <div class="stat">
        <p class="stat__label">Saldo</p>
        <p class="stat__value mono"><?= e(money($saldo)) ?></p>
    </div>
</section>

<div class="detail-grid">
    <div class="detail-stack">
        <section class="panel panel--flush">
            <header class="panel__head">
                <h2 class="panel__title">Pagamentos <small><?= count($pagamentos) ?> lançamento<?= count($pagamentos) === 1 ? '' : 's' ?></small></h2>
                <a class="section-head__link" href="<?= url('pagamentos', ['acao' => 'novo', 'reserva_id' => $reserva['id']]) ?>">Novo pagamento <?= icon('plus', 'icon', 11) ?></a>
            </header>

            <?php if ($pagamentos): ?>
                <div class="table-wrap">
                    <table class="data-table">
                        <caption class="sr-only">Pagamentos da reserva <?= e($reserva['codigo']) ?></caption>
                        <thead>
                            <tr>
                                <th scope="col">Valor</th>
                                <th scope="col">Forma</th>
                                <th scope="col">Situação</th>
                                <th scope="col">Vencimento</th>
                                <th scope="col">Pagamento</th>
                                <th scope="col">Observação</th>
                                <th scope="col"><span class="sr-only">Ações</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pagamentos as $pagamento): ?>
                                <?php
                                $vencido = $pagamento['status'] === 'pendente'
                                    && !empty($pagamento['vencimento'])
                                    && $pagamento['vencimento'] < $hoje;
                                ?>
                                <tr>
                                    <td class="cell-strong mono"><?= e(money($pagamento['valor'])) ?></td>
                                    <td><?= e(forma_pagamento_label($pagamento['forma'])) ?></td>
                                    <td>
                                        <span class="badge badge--<?= e(badge_tom($pagamento['status'])) ?>"><?= e(status_pagamento_label($pagamento['status'])) ?></span>
                                        <?php if ($vencido): ?>
                                            <div class="cell-sub" style="color: var(--danger);">vencido</div>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= e(data_br($pagamento['vencimento'])) ?></td>
                                    <td><?= e(data_br($pagamento['data_pagamento'])) ?></td>
                                    <td><span class="cell-sub"><?= e(resumir($pagamento['observacoes'], 48) ?: '—') ?></span></td>
                                    <td class="cell-actions">
                                        <a class="action-link" href="<?= url('pagamentos', ['acao' => 'editar', 'id' => $pagamento['id']]) ?>" aria-label="Editar pagamento de <?= e(money($pagamento['valor'])) ?>" title="Editar pagamento">
                                            <?= icon('edit', 'icon', 15) ?>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <span class="empty-state__mark"><?= icon('banknote', 'icon', 20) ?></span>
                    <h3>Nenhum pagamento registrado</h3>
                    <p>Registre o sinal ou a primeira parcela para acompanhar o saldo desta reserva.</p>
                    <a class="button button--primary" href="<?= url('pagamentos', ['acao' => 'novo', 'reserva_id' => $reserva['id']]) ?>"><?= icon('plus', 'icon', 16) ?> Registrar pagamento</a>
                </div>
            <?php endif; ?>
        </section>

        <section class="panel">
            <header class="panel__head">
                <h2 class="panel__title">Situação financeira</h2>
            </header>
            <div class="panel__body">
                <div class="progress">
                    <div class="progress__head">
                        <span>Progresso do pagamento</span>
                        <strong><?= e(money($pago)) ?> de <?= e(money($total)) ?></strong>
                    </div>
                    <div class="progress__track">
                        <div class="progress__fill <?= $percentualPago >= 100 ? 'progress__fill--full' : ($percentualPago < 50 ? 'progress__fill--warn' : '') ?>"
                             style="width: <?= $percentualPago ?>%"></div>
                    </div>
                    <p class="progress__meta"><?= $percentualPago ?>% quitado · <?= e(money($pendente)) ?> programado em parcelas pendentes<?= $estornado > 0 ? ' · ' . e(money($estornado)) . ' estornado' : '' ?></p>
                </div>
                <?php if ($reserva['status'] !== 'cancelada' && $saldo > 0): ?>
                    <p class="muted" style="font-size: 11px; margin-top: 12px;">O saldo de <?= e(money($saldo)) ?> é confirmado automaticamente quando a soma dos pagamentos pagos alcançar o valor total da reserva.</p>
                <?php elseif ($saldo <= 0 && $reserva['status'] !== 'cancelada'): ?>
                    <p class="muted" style="font-size: 11px; margin-top: 12px; color: var(--sea);">Reserva quitada. O pagamento total confirma automaticamente a reserva.</p>
                <?php endif; ?>
            </div>
        </section>

        <section class="panel">
            <header class="panel__head">
                <h2 class="panel__title">Observações da reserva</h2>
            </header>
            <div class="panel__body">
                <?php if ($reserva['observacoes']): ?>
                    <p style="font-size: 12.5px; color: var(--ink-soft); line-height: 1.7;"><?= nl2br(e($reserva['observacoes'])) ?></p>
                <?php else: ?>
                    <p class="muted" style="font-size: 12px;">Nenhuma observação registrada para esta reserva.</p>
                <?php endif; ?>
            </div>
        </section>
    </div>

    <aside class="detail-stack" aria-label="Dados da reserva">
        <section class="panel">
            <header class="panel__head">
                <h2 class="panel__title">Cliente</h2>
                <a class="section-head__link" href="<?= url('clientes', ['acao' => 'ver', 'id' => $reserva['cliente_id']]) ?>">Ficha <?= icon('arrow-up-right', 'icon', 11) ?></a>
            </header>
            <div class="panel__body">
                <div class="person-cell" style="margin-bottom: 12px;">
                    <span class="avatar avatar--lg"><?= e(iniciais($cliente['nome'] ?? $reserva['cliente_nome'])) ?></span>
                    <div class="person-cell__copy">
                        <span class="cell-main" style="font-size: 14px;"><?= e($cliente['nome'] ?? $reserva['cliente_nome']) ?></span>
                        <div class="cell-sub"><?= e(formatar_cpf($cliente['cpf'] ?? '')) ?></div>
                    </div>
                </div>
                <dl class="info-list">
                    <div class="info-row">
                        <dt>E-mail</dt>
                        <dd><a href="mailto:<?= e($cliente['email'] ?? $reserva['cliente_email']) ?>"><?= e($cliente['email'] ?? $reserva['cliente_email']) ?></a></dd>
                    </div>
                    <div class="info-row">
                        <dt>Telefone</dt>
                        <dd><a href="tel:+55<?= e(apenas_numeros($cliente['telefone'] ?? $reserva['cliente_telefone'])) ?>"><?= e(formatar_telefone($cliente['telefone'] ?? $reserva['cliente_telefone'])) ?></a></dd>
                    </div>
                    <?php if (!empty($cliente['cidade'])): ?>
                        <div class="info-row">
                            <dt>Cidade</dt>
                            <dd><?= e($cliente['cidade']) ?><?= $cliente['estado'] ? ', ' . e($cliente['estado']) : '' ?></dd>
                        </div>
                    <?php endif; ?>
                    <div class="info-row">
                        <dt>Reserva em</dt>
                        <dd><?= e(data_br($reserva['data_reserva'])) ?></dd>
                    </div>
                </dl>
            </div>
        </section>

        <section class="panel">
            <header class="panel__head">
                <h2 class="panel__title">Partida</h2>
                <a class="section-head__link" href="<?= url('viagens', ['acao' => 'ver', 'id' => $reserva['viagem_id']]) ?>">Ficha <?= icon('arrow-up-right', 'icon', 11) ?></a>
            </header>
            <div class="panel__body">
                <dl class="info-list">
                    <div class="info-row">
                        <dt>Destino</dt>
                        <dd><?= e($reserva['destino_nome']) ?> — <?= e($reserva['destino_pais']) ?></dd>
                    </div>
                    <div class="info-row">
                        <dt>Período</dt>
                        <dd><?= e(data_br($reserva['data_ida'])) ?> a <?= e(data_br($reserva['data_volta'])) ?></dd>
                    </div>
                    <div class="info-row">
                        <dt>Pacote</dt>
                        <dd><?= e($reserva['pacote_titulo']) ?></dd>
                    </div>
                    <div class="info-row">
                        <dt>Situação da viagem</dt>
                        <dd><span class="badge badge--<?= e(badge_tom($reserva['viagem_status'])) ?>"><?= e(status_viagem_label($reserva['viagem_status'])) ?></span></dd>
                    </div>
                    <div class="info-row">
                        <dt>Encontro</dt>
                        <dd><?= e($viagem['ponto_encontro'] ?? 'A definir') ?></dd>
                    </div>
                </dl>
            </div>
        </section>

        <section class="panel">
            <header class="panel__head">
                <h2 class="panel__title">Ações da reserva</h2>
            </header>
            <div class="panel__body">
                <div class="button-row">
                    <?php if ($reserva['status'] === 'pendente'): ?>
                        <form method="post" action="<?= url('reservas', ['acao' => 'confirmar', 'id' => $reserva['id']]) ?>">
                            <?= csrf_field() ?>
                            <button class="button button--secondary button--small" type="submit"><?= icon('check', 'icon', 14) ?> Confirmar</button>
                        </form>
                    <?php endif; ?>
                    <a class="button button--ghost button--small" href="<?= url('pagamentos', ['acao' => 'novo', 'reserva_id' => $reserva['id']]) ?>">
                        <?= icon('banknote', 'icon', 14) ?> Cobrar
                    </a>
                </div>

                <?php if ($reserva['status'] !== 'cancelada'): ?>
                    <form method="post" action="<?= url('reservas', ['acao' => 'cancelar', 'id' => $reserva['id']]) ?>"
                          data-confirm="Cancelar a reserva <?= e($reserva['codigo']) ?>? Os lugares serão liberados e as cobranças pendentes serão anuladas."
                          data-confirm-label="Cancelar reserva" style="margin-top: 12px;">
                        <?= csrf_field() ?>
                        <button class="button button--ghost button--small button--full" type="submit" style="color: var(--danger);">
                            <?= icon('x', 'icon', 14) ?> Cancelar reserva
                        </button>
                    </form>
                <?php endif; ?>

                <?php if (!$pagamentos): ?>
                    <form method="post" action="<?= url('reservas', ['acao' => 'excluir', 'id' => $reserva['id']]) ?>"
                          data-confirm="Excluir definitivamente a reserva <?= e($reserva['codigo']) ?>? Esta ação não pode ser desfeita."
                          data-confirm-label="Excluir reserva" style="margin-top: 8px;">
                        <?= csrf_field() ?>
                        <button class="button button--ghost button--small button--full" type="submit" style="color: var(--danger);">
                            <?= icon('trash', 'icon', 14) ?> Excluir reserva
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </section>
    </aside>
</div>
