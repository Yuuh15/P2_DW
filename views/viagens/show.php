<?php
$capacidade = (int) $viagem['capacidade'];
$ocupados = (int) $ocupados;
$livres = max(0, $capacidade - $ocupados);
$percentual = $capacidade > 0 ? min(100, (int) round(($ocupados / $capacidade) * 100)) : 0;
$situacao = situacao_viagem($viagem);
$reservasAtivas = 0;
foreach ($reservas as $reserva) {
    if (in_array($reserva['status'], ['pendente', 'confirmada'], true)) {
        $reservasAtivas++;
    }
}

$origem = 'São Paulo';
if (!empty($viagem['ponto_encontro'])) {
    $partes = preg_split('/\s+[—–-]\s+/', (string) $viagem['ponto_encontro']);
    $origem = trim((string) ($partes[0] ?? 'São Paulo'));
    $origem = preg_replace('/^(Aeroporto|Rodoviária|Terminal)\s+(de|do|da)?\s*/iu', '', $origem) ?: $origem;
}

$horario = null;
if (!empty($viagem['ponto_encontro']) && preg_match('/\b(\d{1,2}h\d{0,2})\b/i', (string) $viagem['ponto_encontro'], $achado)) {
    $horario = $achado[1];
}

$proximosStatus = array_values(array_filter(
    $statusPermitidos,
    static fn (string $status): bool => $status !== $viagem['status']
));

$hoje = new DateTimeImmutable('today');
$ida = new DateTimeImmutable($viagem['data_ida']);
$volta = new DateTimeImmutable($viagem['data_volta']);
$embarqueRealizado = $viagem['status'] === 'concluida' || ($viagem['status'] === 'em_andamento') || $ida < $hoje;
$retornoRealizado = $viagem['status'] === 'concluida' || $volta < $hoje;
$cancelada = $viagem['status'] === 'cancelada';
$confirmada = in_array($viagem['status'], ['confirmada', 'em_andamento', 'concluida'], true);
?>

<div class="page-head page-head--compact">
    <div class="page-head__copy">
        <a class="page-head__back" href="<?= url('viagens') ?>"><?= icon('arrow-left', 'icon', 13) ?> Todas as partidas</a>
        <h1>Ficha da partida</h1>
        <p class="page-head__sub">Operação, ocupação, reservas e financeiro de <?= e($viagem['destino_nome']) ?>.</p>
    </div>
    <div class="page-head__actions">
        <a class="button button--secondary" href="<?= url('viagens', ['acao' => 'editar', 'id' => $viagem['id']]) ?>">
            <?= icon('edit', 'icon', 15) ?> Editar partida
        </a>
        <a class="button button--primary" href="<?= url('reservas', ['acao' => 'novo', 'viagem_id' => $viagem['id']]) ?>">
            <?= icon('plus', 'icon', 15) ?> Nova reserva
        </a>
    </div>
</div>

<article class="boarding-pass" aria-label="Cartão da partida">
    <div class="boarding-pass__main">
        <p class="boarding-pass__eyebrow">
            <span>PARTIDA <strong><?= e(sprintf('MRD-PART-%03d', $viagem['id'])) ?></strong></span>
            <span class="dot" aria-hidden="true"></span>
            <span><?= e($viagem['pacote_titulo']) ?></span>
            <span class="dot" aria-hidden="true"></span>
            <span><?= e($viagem['destino_pais']) ?></span>
        </p>
        <div class="boarding-pass__route">
            <div class="boarding-pass__city">
                <small>Embarque</small>
                <h2><?= e($origem) ?></h2>
                <small><?= $horario ? 'Encontro às ' . e($horario) : 'Ponto de encontro a definir' ?></small>
            </div>
            <div class="boarding-pass__path" aria-hidden="true"><?= icon('plane', 'icon', 16) ?></div>
            <div class="boarding-pass__city" style="text-align: right;">
                <small>Destino</small>
                <h2><?= e($viagem['destino_nome']) ?></h2>
                <small><?= e($viagem['destino_pais']) ?> · <?= (int) $viagem['duracao_dias'] ?> dias</small>
            </div>
        </div>
        <div class="boarding-pass__facts">
            <div>
                <span class="boarding-fact__label">IDA</span>
                <span class="boarding-fact__value boarding-fact__value--lg"><?= e(data_br($viagem['data_ida'])) ?></span>
            </div>
            <div>
                <span class="boarding-fact__label">VOLTA</span>
                <span class="boarding-fact__value boarding-fact__value--lg"><?= e(data_br($viagem['data_volta'])) ?></span>
            </div>
            <div>
                <span class="boarding-fact__label">OCUPAÇÃO</span>
                <span class="boarding-fact__value boarding-fact__value--lg"><?= $ocupados ?>/<?= $capacidade ?> lugares</span>
            </div>
            <div>
                <span class="boarding-fact__label">RECEITA PREVISTA</span>
                <span class="boarding-fact__value boarding-fact__value--lg"><?= e(money($totalContratado)) ?></span>
            </div>
        </div>
    </div>
    <div class="boarding-pass__stub">
        <div>
            <span class="stub-label">SITUAÇÃO</span>
            <span class="stub-status"><?= e($situacao['label']) ?></span>
        </div>
        <div class="stub-divider" aria-hidden="true"></div>
        <div>
            <span class="stub-label">EMBARQUE</span>
            <span class="stub-value stub-value--big"><?= e(data_curta($viagem['data_ida'])) ?></span>
        </div>
        <div>
            <span class="stub-label">RETORNO</span>
            <span class="stub-value"><?= e(data_curta($viagem['data_volta'])) ?></span>
        </div>
        <div class="stub-divider" aria-hidden="true"></div>
        <div>
            <span class="stub-label">LUGARES LIVRES</span>
            <span class="stub-value"><?= $livres ?> de <?= $capacidade ?></span>
        </div>
    </div>
</article>

<section class="stat-strip" aria-label="Resumo da partida">
    <div class="stat">
        <p class="stat__label">Reservas ativas</p>
        <p class="stat__value"><?= $reservasAtivas ?></p>
    </div>
    <div class="stat">
        <p class="stat__label">Viajantes confirmados</p>
        <p class="stat__value"><?= $ocupados ?><span style="font-size: 13px; color: var(--ink-faint);"> / <?= $capacidade ?></span></p>
    </div>
    <div class="stat">
        <p class="stat__label">Valor contratado</p>
        <p class="stat__value mono"><?= e(money($totalContratado)) ?></p>
    </div>
    <div class="stat">
        <p class="stat__label">Valor recebido</p>
        <p class="stat__value mono"><?= e(money($totalPago)) ?></p>
    </div>
</section>

<div class="detail-grid">
    <div class="detail-stack">
        <section class="panel panel--flush">
            <header class="panel__head">
                <h2 class="panel__title">Reservas desta partida <small><?= count($reservas) ?> registro<?= count($reservas) === 1 ? '' : 's' ?></small></h2>
                <a class="section-head__link" href="<?= url('reservas', ['acao' => 'novo', 'viagem_id' => $viagem['id']]) ?>">Nova reserva <?= icon('plus', 'icon', 11) ?></a>
            </header>

            <?php if ($reservas): ?>
                <div class="table-wrap">
                    <table class="data-table">
                        <caption class="sr-only">Reservas da partida para <?= e($viagem['destino_nome']) ?></caption>
                        <thead>
                            <tr>
                                <th scope="col">Reserva</th>
                                <th scope="col">Cliente</th>
                                <th scope="col">Viajantes</th>
                                <th scope="col">Situação</th>
                                <th scope="col">Pagamento</th>
                                <th scope="col"><span class="sr-only">Ações</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reservas as $reserva): ?>
                                <?php
                                $pagoReserva = (float) $reserva['valor_pago'];
                                $totalReserva = (float) $reserva['valor_total'];
                                $percentualReserva = $totalReserva > 0 ? min(100, (int) round(($pagoReserva / $totalReserva) * 100)) : 0;
                                ?>
                                <tr>
                                    <td>
                                        <a class="cell-main mono" href="<?= url('reservas', ['acao' => 'ver', 'id' => $reserva['id']]) ?>"><?= e($reserva['codigo']) ?></a>
                                        <div class="cell-sub"><?= e(data_br($reserva['data_reserva'])) ?></div>
                                    </td>
                                    <td>
                                        <span class="cell-main"><?= e($reserva['cliente_nome']) ?></span>
                                        <div class="cell-sub"><?= e(formatar_telefone($reserva['cliente_telefone'])) ?></div>
                                    </td>
                                    <td class="cell-strong"><?= (int) $reserva['viajantes'] ?></td>
                                    <td><span class="badge badge--<?= e(badge_tom($reserva['status'])) ?>"><?= e(status_reserva_label($reserva['status'])) ?></span></td>
                                    <td>
                                        <div class="progress" style="min-width: 120px;">
                                            <div class="progress__head">
                                                <span><?= $percentualReserva ?>%</span>
                                                <strong><?= e(money($pagoReserva)) ?></strong>
                                            </div>
                                            <div class="progress__track">
                                                <div class="progress__fill <?= $percentualReserva >= 100 ? 'progress__fill--full' : ($percentualReserva < 50 ? 'progress__fill--warn' : '') ?>"
                                                     style="width: <?= $percentualReserva ?>%"></div>
                                            </div>
                                        </div>
                                        <div class="cell-sub">saldo <?= e(money(max(0, $totalReserva - $pagoReserva))) ?></div>
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
            <?php else: ?>
                <div class="empty-state">
                    <span class="empty-state__mark"><?= icon('ticket', 'icon', 20) ?></span>
                    <h3>Nenhuma reserva nesta partida</h3>
                    <p>Abra a primeira reserva para começar a ocupar os <?= $capacidade ?> lugares.</p>
                    <a class="button button--primary" href="<?= url('reservas', ['acao' => 'novo', 'viagem_id' => $viagem['id']]) ?>"><?= icon('plus', 'icon', 16) ?> Registrar reserva</a>
                </div>
            <?php endif; ?>
        </section>

        <section class="panel">
            <header class="panel__head">
                <h2 class="panel__title">Financeiro da partida</h2>
                <a class="section-head__link" href="<?= url('pagamentos', ['viagem_id' => $viagem['id']]) ?>">Ver pagamentos <?= icon('chevron-right', 'icon', 11) ?></a>
            </header>
            <div class="panel__body">
                <div class="progress">
                    <div class="progress__head">
                        <span>Recebido do total contratado</span>
                        <strong><?= e(money($totalPago)) ?> / <?= e(money($totalContratado)) ?></strong>
                    </div>
                    <?php $percentualFinanceiro = $totalContratado > 0 ? min(100, (int) round(($totalPago / $totalContratado) * 100)) : 0; ?>
                    <div class="progress__track">
                        <div class="progress__fill <?= $percentualFinanceiro >= 100 ? 'progress__fill--full' : 'progress__fill--warn' ?>" style="width: <?= $percentualFinanceiro ?>%"></div>
                    </div>
                    <p class="progress__meta"><?= $percentualFinanceiro ?>% recebido · <?= e(money($totalPendente)) ?> em aberto nas reservas ativas</p>
                </div>
            </div>
        </section>

        <section class="panel">
            <header class="panel__head">
                <h2 class="panel__title">Observações operacionais</h2>
            </header>
            <div class="panel__body">
                <?php if ($viagem['observacoes']): ?>
                    <p style="font-size: 12.5px; color: var(--ink-soft); line-height: 1.7;"><?= nl2br(e($viagem['observacoes'])) ?></p>
                <?php else: ?>
                    <p class="muted" style="font-size: 12px;">Nenhuma observação registrada para esta partida. Use a edição para incluir instruções ao grupo.</p>
                <?php endif; ?>
            </div>
        </section>
    </div>

    <aside class="detail-stack" aria-label="Dados operacionais da partida">
        <section class="panel">
            <header class="panel__head">
                <h2 class="panel__title">Situação operacional</h2>
            </header>
            <div class="panel__body">
                <div class="info-list">
                    <div class="info-row">
                        <dt>Situação atual</dt>
                        <dd><span class="stamp stamp--<?= e($situacao['tom']) ?>"><?= e($situacao['label']) ?></span></dd>
                    </div>
                    <div class="info-row">
                        <dt>Ocupação</dt>
                        <dd><?= $percentual ?>% · <?= $livres ?> livre<?= $livres === 1 ? '' : 's' ?></dd>
                    </div>
                    <div class="info-row">
                        <dt>Ponto de encontro</dt>
                        <dd><?= e($viagem['ponto_encontro'] ?: 'A definir') ?></dd>
                    </div>
                    <div class="info-row">
                        <dt>Pacote</dt>
                        <dd><a href="<?= url('pacotes', ['acao' => 'editar', 'id' => $viagem['pacote_id']]) ?>"><?= e($viagem['pacote_titulo']) ?></a></dd>
                    </div>
                </div>

                <?php if ($proximosStatus): ?>
                    <form method="post" action="<?= url('viagens', ['acao' => 'status', 'id' => $viagem['id']]) ?>" style="margin-top: 16px;">
                        <?= csrf_field() ?>
                        <div class="field">
                            <label for="status-rapido">Avançar situação</label>
                            <select id="status-rapido" name="status" required>
                                <?php foreach ($proximosStatus as $chave): ?>
                                    <option value="<?= e($chave) ?>"><?= e(status_viagem_label($chave)) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button class="button button--secondary button--small button--full" type="submit">
                            <?= icon('check', 'icon', 14) ?> Atualizar situação
                        </button>
                    </form>
                <?php else: ?>
                    <p class="muted" style="font-size: 11px; margin-top: 14px;">Esta partida está em uma situação final. Reabra a edição se precisar ajustar os dados cadastrais.</p>
                <?php endif; ?>
            </div>
        </section>

        <section class="panel">
            <header class="panel__head">
                <h2 class="panel__title">Linha do tempo</h2>
            </header>
            <div class="panel__body">
                <div class="timeline">
                    <div class="timeline__item">
                        <span class="timeline__date"><?= e(data_curta(substr($viagem['criado_em'] ?? date('Y-m-d'), 0, 10))) ?></span>
                        <div class="timeline__copy">
                            <strong>Partida cadastrada</strong>
                            <small>Vendas abertas para o pacote.</small>
                        </div>
                    </div>
                    <div class="timeline__item">
                        <span class="timeline__date"><?= e(data_curta($viagem['data_ida'])) ?></span>
                        <div class="timeline__copy">
                            <strong>Embarque para <?= e($viagem['destino_nome']) ?></strong>
                            <small><?= $cancelada ? 'Cancelada.' : ($confirmada ? 'Partida confirmada.' : 'Aguardando confirmação.') ?></small>
                        </div>
                    </div>
                    <div class="timeline__item">
                        <span class="timeline__date"><?= e(data_curta($viagem['data_volta'])) ?></span>
                        <div class="timeline__copy">
                            <strong>Retorno previsto</strong>
                            <small><?= $retornoRealizado ? 'Retorno realizado.' : 'Retorno ainda por acontecer.' ?></small>
                        </div>
                    </div>
                    <?php if ($cancelada): ?>
                        <div class="timeline__item">
                            <span class="timeline__date">—</span>
                            <div class="timeline__copy">
                                <strong>Partida cancelada</strong>
                                <small>Histórico preservado para consulta.</small>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <section class="panel">
            <header class="panel__head">
                <h2 class="panel__title">Pacote e destino</h2>
            </header>
            <div class="panel__body">
                <?php if ($viagem['destino_imagem']): ?>
                    <img class="thumb" src="<?= e(asset($viagem['destino_imagem'])) ?>" alt="<?= e($viagem['destino_nome']) ?>" width="280" height="140" loading="lazy" style="width: 100%; height: 140px; object-fit: cover; margin-bottom: 12px;">
                <?php endif; ?>
                <div class="info-list">
                    <div class="info-row">
                        <dt>Destino</dt>
                        <dd><?= e($viagem['destino_nome']) ?> — <?= e($viagem['destino_pais']) ?></dd>
                    </div>
                    <div class="info-row">
                        <dt>Duração</dt>
                        <dd><?= (int) $viagem['duracao_dias'] ?> dias</dd>
                    </div>
                    <div class="info-row">
                        <dt>Preço por pessoa</dt>
                        <dd class="mono"><?= e(money($viagem['pacote_preco'])) ?></dd>
                    </div>
                </div>
                <?php if ($viagem['inclusos']): ?>
                    <p style="margin-top: 12px; font-size: 11px; color: var(--ink-muted); line-height: 1.6;"><?= nl2br(e($viagem['inclusos'])) ?></p>
                <?php endif; ?>
            </div>
        </section>

        <section class="panel">
            <header class="panel__head">
                <h2 class="panel__title">Ações</h2>
            </header>
            <div class="panel__body">
                <div class="button-row">
                    <a class="button button--secondary button--small" href="<?= url('reservas', ['acao' => 'novo', 'viagem_id' => $viagem['id']]) ?>">
                        <?= icon('ticket', 'icon', 14) ?> Reservar
                    </a>
                    <a class="button button--ghost button--small" href="<?= url('pagamentos', ['viagem_id' => $viagem['id']]) ?>">
                        <?= icon('banknote', 'icon', 14) ?> Financeiro
                    </a>
                </div>
                <form method="post" action="<?= url('viagens', ['acao' => 'excluir', 'id' => $viagem['id']]) ?>"
                      data-confirm="Excluir a partida para <?= e($viagem['destino_nome']) ?> em <?= e(data_br($viagem['data_ida'])) ?>? Partidas com reservas não podem ser removidas."
                      data-confirm-label="Excluir partida" style="margin-top: 10px;">
                    <?= csrf_field() ?>
                    <button class="button button--ghost button--small button--full" type="submit" style="color: var(--danger);">
                        <?= icon('trash', 'icon', 14) ?> Excluir partida
                    </button>
                </form>
            </div>
        </section>
    </aside>
</div>
