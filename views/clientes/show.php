<?php
$idade = null;
if (!empty($cliente['data_nascimento'])) {
    $nascimento = new DateTimeImmutable($cliente['data_nascimento']);
    $idade = $nascimento->diff(new DateTimeImmutable('today'))->y;
}
$quantidadeReservas = count($reservas);
?>
<div class="page-head page-head--compact">
    <div class="page-head__copy">
        <a class="page-head__back" href="<?= url('clientes') ?>"><?= icon('arrow-left', 'icon', 13) ?> Todos os clientes</a>
        <h1>Ficha do cliente</h1>
        <p class="page-head__sub">Histórico de reservas, viagens e pagamentos de <?= e($cliente['nome']) ?>.</p>
    </div>
</div>

<section class="detail-hero">
    <span class="avatar avatar--lg"><?= e(iniciais($cliente['nome'])) ?></span>
    <div class="detail-hero__copy">
        <div class="detail-hero__top">
            <h1 style="font-size: clamp(24px, 2.4vw, 32px);"><?= e($cliente['nome']) ?></h1>
            <span class="badge badge--neutral">Cliente desde <?= e(data_br(substr($cliente['criado_em'], 0, 10))) ?></span>
        </div>
        <p class="detail-hero__sub">
            <span><?= icon('mail', 'icon', 12) ?> <?= e($cliente['email']) ?></span>
            <span class="dot" aria-hidden="true"></span>
            <span><?= icon('phone', 'icon', 12) ?> <?= e(formatar_telefone($cliente['telefone'])) ?></span>
            <?php if ($cliente['cidade']): ?>
                <span class="dot" aria-hidden="true"></span>
                <span><?= icon('map-pin', 'icon', 12) ?> <?= e($cliente['cidade']) ?><?= $cliente['estado'] ? ', ' . e($cliente['estado']) : '' ?></span>
            <?php endif; ?>
        </p>
    </div>
    <div class="detail-hero__actions">
        <a class="button button--secondary button--small" href="<?= url('clientes', ['acao' => 'editar', 'id' => $cliente['id']]) ?>">
            <?= icon('edit', 'icon', 14) ?> Editar
        </a>
        <a class="button button--primary button--small" href="<?= url('reservas', ['acao' => 'novo', 'cliente_id' => $cliente['id']]) ?>">
            <?= icon('plus', 'icon', 14) ?> Nova reserva
        </a>
        <form method="post" action="<?= url('clientes', ['acao' => 'excluir', 'id' => $cliente['id']]) ?>"
              data-confirm="Excluir o cliente <?= e($cliente['nome']) ?>? Esta ação não pode ser desfeita."
              data-confirm-label="Excluir cliente" style="display: inline-flex;">
            <?= csrf_field() ?>
            <button class="icon-button" type="submit" aria-label="Excluir cliente" title="Excluir cliente">
                <?= icon('trash', 'icon', 15) ?>
            </button>
        </form>
    </div>
</section>

<section class="stat-strip" aria-label="Resumo do cliente">
    <div class="stat">
        <p class="stat__label">Reservas registradas</p>
        <p class="stat__value"><?= $quantidadeReservas ?></p>
    </div>
    <div class="stat">
        <p class="stat__label">Próximas partidas</p>
        <p class="stat__value"><?= (int) $proximas ?></p>
    </div>
    <div class="stat">
        <p class="stat__label">Total pago</p>
        <p class="stat__value mono"><?= e(money($totalPago)) ?></p>
    </div>
</section>

<div class="detail-grid">
    <div class="detail-stack">
        <section class="panel panel--flush">
            <header class="panel__head">
                <h2 class="panel__title">Histórico de reservas <small><?= $quantidadeReservas ?> registro<?= $quantidadeReservas === 1 ? '' : 's' ?></small></h2>
                <a class="section-head__link" href="<?= url('reservas', ['acao' => 'novo', 'cliente_id' => $cliente['id']]) ?>">Nova reserva <?= icon('plus', 'icon', 11) ?></a>
            </header>

            <?php if ($reservas): ?>
                <div class="table-wrap">
                    <table class="data-table">
                        <caption class="sr-only">Reservas de <?= e($cliente['nome']) ?></caption>
                        <thead>
                            <tr>
                                <th scope="col">Reserva</th>
                                <th scope="col">Partida</th>
                                <th scope="col">Viajantes</th>
                                <th scope="col">Situação</th>
                                <th scope="col">Valor</th>
                                <th scope="col">Pago</th>
                                <th scope="col"><span class="sr-only">Ações</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reservas as $reserva): ?>
                                <?php $pago = (float) $reserva['valor_pago']; $total = (float) $reserva['valor_total']; ?>
                                <tr>
                                    <td>
                                        <a class="cell-main mono" href="<?= url('reservas', ['acao' => 'ver', 'id' => $reserva['id']]) ?>"><?= e($reserva['codigo']) ?></a>
                                        <div class="cell-sub"><?= e(data_br($reserva['data_reserva'])) ?></div>
                                    </td>
                                    <td>
                                        <span class="cell-main"><?= e($reserva['destino_nome']) ?></span>
                                        <div class="cell-sub"><?= e(data_br($reserva['data_ida'])) ?> — <?= e(data_br($reserva['data_volta'])) ?></div>
                                    </td>
                                    <td class="cell-strong"><?= (int) $reserva['viajantes'] ?></td>
                                    <td><span class="badge badge--<?= e(badge_tom($reserva['status'])) ?>"><?= e(status_reserva_label($reserva['status'])) ?></span></td>
                                    <td class="cell-strong mono"><?= e(money($total)) ?></td>
                                    <td>
                                        <span class="cell-strong mono"><?= e(money($pago)) ?></span>
                                        <div class="cell-sub">saldo <?= e(money(max(0, $total - $pago))) ?></div>
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
                    <h3>Nenhuma reserva ainda</h3>
                    <p>Quando este cliente reservar uma viagem, o histórico completo aparece aqui.</p>
                    <a class="button button--secondary" href="<?= url('reservas', ['acao' => 'novo', 'cliente_id' => $cliente['id']]) ?>">Registrar primeira reserva</a>
                </div>
            <?php endif; ?>
        </section>

        <section class="panel">
            <header class="panel__head">
                <h2 class="panel__title">Observações e preferências</h2>
            </header>
            <div class="panel__body">
                <?php if ($cliente['observacoes']): ?>
                    <p style="font-size: 12.5px; color: var(--ink-soft); line-height: 1.7;"><?= nl2br(e($cliente['observacoes'])) ?></p>
                <?php else: ?>
                    <p class="muted" style="font-size: 12px;">Nenhuma preferência registrada. Use a edição da ficha para anotar necessidades de viagem.</p>
                <?php endif; ?>
            </div>
        </section>
    </div>

    <aside class="detail-stack" aria-label="Dados cadastrais">
        <section class="panel">
            <header class="panel__head">
                <h2 class="panel__title">Dados cadastrais</h2>
            </header>
            <div class="panel__body">
                <dl class="info-list">
                    <div class="info-row">
                        <dt>CPF</dt>
                        <dd class="mono"><?= e(formatar_cpf($cliente['cpf'])) ?></dd>
                    </div>
                    <div class="info-row">
                        <dt>Nascimento</dt>
                        <dd><?= e(data_br($cliente['data_nascimento'])) ?><?= $idade !== null ? ' · ' . $idade . ' anos' : '' ?></dd>
                    </div>
                    <div class="info-row">
                        <dt>E-mail</dt>
                        <dd><a href="mailto:<?= e($cliente['email']) ?>"><?= e($cliente['email']) ?></a></dd>
                    </div>
                    <div class="info-row">
                        <dt>Telefone</dt>
                        <dd><a href="tel:+55<?= e(apenas_numeros($cliente['telefone'])) ?>"><?= e(formatar_telefone($cliente['telefone'])) ?></a></dd>
                    </div>
                    <div class="info-row">
                        <dt>Cidade</dt>
                        <dd><?= e($cliente['cidade'] ?: '—') ?><?= $cliente['estado'] ? ' — ' . e($cliente['estado']) : '' ?></dd>
                    </div>
                    <div class="info-row">
                        <dt>Cadastro</dt>
                        <dd><?= e(data_br(substr($cliente['criado_em'], 0, 10))) ?></dd>
                    </div>
                </dl>
            </div>
        </section>

        <section class="panel">
            <header class="panel__head">
                <h2 class="panel__title">Resumo financeiro</h2>
            </header>
            <div class="panel__body">
                <div class="progress">
                    <div class="progress__head">
                        <span>Total pago</span>
                        <strong><?= e(money($totalPago)) ?></strong>
                    </div>
                    <?php
                    $percentualPago = $totalContratado > 0 ? min(100, round(($totalPago / $totalContratado) * 100)) : 0;
                    ?>
                    <div class="progress__track">
                        <div class="progress__fill <?= $percentualPago >= 100 ? 'progress__fill--full' : ($percentualPago < 50 ? 'progress__fill--warn' : '') ?>"
                             style="width: <?= $percentualPago ?>%"></div>
                    </div>
                    <p class="progress__meta"><?= $percentualPago ?>% de <?= e(money($totalContratado)) ?> contratados</p>
                </div>
            </div>
        </section>
    </aside>
</div>
