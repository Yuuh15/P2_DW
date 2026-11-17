<?php
$valor = static fn (string $campo, string $padrao = ''): string => (string) ($dados[$campo] ?? $padrao);
$erro = static fn (string $campo): ?string => $erros[$campo] ?? null;
$temErro = static fn (string $campo): bool => isset($erros[$campo]);
$urlFormulario = $modo === 'editar'
    ? url('reservas', ['acao' => 'editar', 'id' => $reserva['id']])
    : url('reservas', ['acao' => 'novo']);

$viagemAtual = null;
foreach ($viagens as $viagem) {
    if ((int) $viagem['id'] === (int) $valor('viagem_id')) {
        $viagemAtual = $viagem;
        break;
    }
}

$clienteAtual = null;
foreach ($clientes as $cliente) {
    if ((int) $cliente['id'] === (int) $valor('cliente_id')) {
        $clienteAtual = $cliente;
        break;
    }
}

$maxViajantes = 30;
if ($viagemAtual !== null) {
    $extras = $modo === 'editar' && (int) $viagemAtual['id'] === (int) ($reserva['viagem_id'] ?? 0)
        ? (int) ($reserva['viajantes'] ?? 0)
        : 0;
    $maxViajantes = max(1, min(30, (int) $viagemAtual['capacidade'] - (int) $viagemAtual['lugares_ocupados'] + $extras));
}
?>

<div class="page-head page-head--compact">
    <div class="page-head__copy">
        <a class="page-head__back" href="<?= $modo === 'editar' ? url('reservas', ['acao' => 'ver', 'id' => $reserva['id']]) : url('reservas') ?>">
            <?= icon('arrow-left', 'icon', 13) ?> <?= $modo === 'editar' ? 'Voltar para a reserva' : 'Voltar para reservas' ?>
        </a>
        <h1><?= $modo === 'editar' ? 'Editar reserva' : 'Nova reserva' ?></h1>
        <p class="page-head__sub"><?= $modo === 'editar' ? 'Atualize cliente, partida, viajantes e valor. O valor não pode ficar abaixo do que já foi pago.' : 'Vincule um cliente a uma partida. Os lugares são reservados assim que a reserva é registrada.' ?></p>
    </div>
</div>

<?php if ($erros): ?>
    <div class="flash flash--erro" role="alert">
        <?= icon('alert', 'flash__icon icon', 18) ?>
        <p>Revise os campos destacados para salvar a reserva.</p>
    </div>
<?php endif; ?>

<div class="form-layout">
    <form class="form-panel" method="post" action="<?= $urlFormulario ?>" data-loading
          data-reserva-form data-autocalc="<?= $modo === 'novo' ? 'true' : 'false' ?>">
        <?= csrf_field() ?>

        <div class="form-panel__head">
            <h2>Dados da reserva</h2>
            <p>Campos com <span class="required">*</span> são obrigatórios.</p>
        </div>

        <div class="form-section">
            <div class="form-section__title">Cliente e partida <span>01</span></div>
            <div class="form-grid form-grid--2">
                <div class="field field--full <?= $temErro('cliente_id') ? 'field--error' : '' ?>">
                    <label for="cliente_id">Cliente <span class="required">*</span></label>
                    <select id="cliente_id" name="cliente_id" required
                            <?= $temErro('cliente_id') ? 'aria-invalid="true" aria-describedby="erro-cliente"' : '' ?>>
                        <option value="">Selecione o cliente</option>
                        <?php foreach ($clientes as $cliente): ?>
                            <option value="<?= (int) $cliente['id'] ?>" <?= (int) $valor('cliente_id') === (int) $cliente['id'] ? 'selected' : '' ?>>
                                <?= e($cliente['nome']) ?> — <?= e(formatar_cpf($cliente['cpf'])) ?><?= $cliente['cidade'] ? ' · ' . e($cliente['cidade']) : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if ($erro('cliente_id')): ?>
                        <p class="field__error" id="erro-cliente"><?= icon('alert', 'icon', 11) ?> <?= e($erro('cliente_id')) ?></p>
                    <?php else: ?>
                        <p class="field__help">Não encontrou? <a href="<?= url('clientes', ['acao' => 'novo']) ?>">Cadastre um cliente</a> e volte para a reserva.</p>
                    <?php endif; ?>
                </div>

                <div class="field field--full <?= $temErro('viagem_id') ? 'field--error' : '' ?>">
                    <label for="viagem_id">Partida <span class="required">*</span></label>
                    <select id="viagem_id" name="viagem_id" required data-viagem-select
                            <?= $temErro('viagem_id') ? 'aria-invalid="true" aria-describedby="erro-viagem"' : '' ?>>
                        <option value="" data-preco="0">Selecione a partida</option>
                        <?php foreach ($viagens as $viagem): ?>
                            <?php $livresViagem = max(0, (int) $viagem['capacidade'] - (int) $viagem['lugares_ocupados']); ?>
                            <option value="<?= (int) $viagem['id'] ?>"
                                    data-preco="<?= e(valor_input($viagem['pacote_preco'])) ?>"
                                    <?= (int) $valor('viagem_id') === (int) $viagem['id'] ? 'selected' : '' ?>>
                                <?= e($viagem['destino_nome']) ?> · <?= e(data_br($viagem['data_ida'])) ?> a <?= e(data_br($viagem['data_volta'])) ?>
                                · <?= e($viagem['pacote_titulo']) ?> · <?= $livresViagem ?> livre<?= $livresViagem === 1 ? '' : 's' ?>
                                · <?= e(money($viagem['pacote_preco'])) ?>/pessoa
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if ($erro('viagem_id')): ?>
                        <p class="field__error" id="erro-viagem"><?= icon('alert', 'icon', 11) ?> <?= e($erro('viagem_id')) ?></p>
                    <?php else: ?>
                        <p class="field__help">As opções mostram a ocupação atual de cada partida.</p>
                    <?php endif; ?>
                </div>

                <div class="field <?= $temErro('data_reserva') ? 'field--error' : '' ?>">
                    <label for="data_reserva">Data da reserva <span class="required">*</span></label>
                    <input type="date" id="data_reserva" name="data_reserva" value="<?= e($valor('data_reserva')) ?>" required
                           <?= $temErro('data_reserva') ? 'aria-invalid="true" aria-describedby="erro-data-reserva"' : '' ?>>
                    <?php if ($erro('data_reserva')): ?>
                        <p class="field__error" id="erro-data-reserva"><?= icon('alert', 'icon', 11) ?> <?= e($erro('data_reserva')) ?></p>
                    <?php endif; ?>
                </div>

                <div class="field <?= $temErro('viajantes') ? 'field--error' : '' ?>">
                    <label for="viajantes">Número de viajantes <span class="required">*</span></label>
                    <input type="number" id="viajantes" name="viajantes" value="<?= e($valor('viajantes')) ?>"
                           min="1" max="<?= $maxViajantes ?>" step="1" required inputmode="numeric" data-viajantes
                           <?= $temErro('viajantes') ? 'aria-invalid="true" aria-describedby="erro-viajantes"' : '' ?>>
                    <?php if ($erro('viajantes')): ?>
                        <p class="field__error" id="erro-viajantes"><?= icon('alert', 'icon', 11) ?> <?= e($erro('viajantes')) ?></p>
                    <?php else: ?>
                        <p class="field__help">A capacidade disponível é validada no servidor.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="form-section">
            <div class="form-section__title">Valores e situação <span>02</span></div>
            <div class="form-grid form-grid--2">
                <div class="field <?= $temErro('valor_total') ? 'field--error' : '' ?>">
                    <label for="valor_total">Valor total da reserva <span class="required">*</span></label>
                    <div class="input-wrap">
                        <?= icon('banknote', 'input-icon') ?>
                        <input type="number" id="valor_total" name="valor_total" value="<?= e($valor('valor_total')) ?>"
                               min="0.01" step="0.01" inputmode="decimal" placeholder="0,00" required data-valor-total
                               <?= $temErro('valor_total') ? 'aria-invalid="true" aria-describedby="erro-valor"' : '' ?>>
                        <button class="input-action" type="button" data-recalcular aria-label="Recalcular pelo preço do pacote" title="Recalcular">
                            <?= icon('sparkles', 'icon', 16) ?>
                        </button>
                    </div>
                    <?php if ($erro('valor_total')): ?>
                        <p class="field__error" id="erro-valor"><?= icon('alert', 'icon', 11) ?> <?= e($erro('valor_total')) ?></p>
                    <?php else: ?>
                        <p class="field__help" data-reserva-preview>Preço do pacote × viajantes é sugerido automaticamente.</p>
                    <?php endif; ?>
                </div>

                <div class="field <?= $temErro('status') ? 'field--error' : '' ?>">
                    <label for="status">Situação da reserva <span class="required">*</span></label>
                    <select id="status" name="status" required
                            <?= $temErro('status') ? 'aria-invalid="true" aria-describedby="erro-status"' : '' ?>>
                        <option value="pendente" <?= $valor('status', 'pendente') === 'pendente' ? 'selected' : '' ?>>Pendente</option>
                        <option value="confirmada" <?= $valor('status') === 'confirmada' ? 'selected' : '' ?>>Confirmada</option>
                        <option value="cancelada" <?= $valor('status') === 'cancelada' ? 'selected' : '' ?>>Cancelada</option>
                    </select>
                    <?php if ($erro('status')): ?>
                        <p class="field__error" id="erro-status"><?= icon('alert', 'icon', 11) ?> <?= e($erro('status')) ?></p>
                    <?php else: ?>
                        <p class="field__help">Ao quitar o valor, a reserva é confirmada automaticamente.</p>
                    <?php endif; ?>
                </div>

                <div class="field field--full <?= $temErro('observacoes') ? 'field--error' : '' ?>">
                    <label for="observacoes">Observações da reserva</label>
                    <textarea id="observacoes" name="observacoes" maxlength="2000"
                              placeholder="Preferências do cliente, composição do grupo, solicitações especiais."><?= e($valor('observacoes')) ?></textarea>
                    <p class="field__help">Essas observações aparecem na ficha da reserva e não substituem as preferências do cadastro do cliente.</p>
                    <?php if ($erro('observacoes')): ?>
                        <p class="field__error"><?= icon('alert', 'icon', 11) ?> <?= e($erro('observacoes')) ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="form-actions">
            <span class="form-actions__hint">
                <?php if ($viagemAtual && $clienteAtual): ?>
                    <?= e($clienteAtual['nome']) ?> · <?= e($viagemAtual['destino_nome']) ?> · <?= e(data_br($viagemAtual['data_ida'])) ?>
                <?php elseif ($viagemAtual): ?>
                    Partida: <?= e($viagemAtual['destino_nome']) ?> · <?= e(data_br($viagemAtual['data_ida'])) ?>
                <?php else: ?>
                    Selecione cliente e partida para continuar
                <?php endif; ?>
            </span>
            <a class="button button--secondary" href="<?= $modo === 'editar' ? url('reservas', ['acao' => 'ver', 'id' => $reserva['id']]) : url('reservas') ?>">Cancelar</a>
            <button class="button button--primary" type="submit">
                <?= icon('check', 'icon', 16) ?>
                <span data-button-label><?= $modo === 'editar' ? 'Salvar alterações' : 'Registrar reserva' ?></span>
            </button>
        </div>
    </form>

    <aside class="form-aside" aria-label="Orientações sobre reservas">
        <?php if ($viagemAtual): ?>
            <div class="aside-card aside-card--tint">
                <h3><?= icon('plane', 'icon', 15) ?> Partida selecionada</h3>
                <p><strong><?= e($viagemAtual['destino_nome']) ?></strong><br><?= e(data_br($viagemAtual['data_ida'])) ?> a <?= e(data_br($viagemAtual['data_volta'])) ?></p>
                <ul>
                    <li><?= e($viagemAtual['pacote_titulo']) ?></li>
                    <li><?= (int) $viagemAtual['capacidade'] - (int) $viagemAtual['lugares_ocupados'] ?> de <?= (int) $viagemAtual['capacidade'] ?> lugares livres</li>
                    <li>Preço do pacote: <?= e(money($viagemAtual['pacote_preco'])) ?> por pessoa</li>
                </ul>
            </div>
        <?php endif; ?>

        <?php if ($modo === 'editar' && $pagoAtual > 0): ?>
            <div class="aside-card aside-card--warn">
                <h3><?= icon('wallet', 'icon', 15) ?> Pagamentos recebidos</h3>
                <p>Esta reserva já recebeu <strong><?= e(money($pagoAtual)) ?></strong>. O novo valor total não pode ser menor que esse montante e a reserva não pode ser cancelada pela edição.</p>
            </div>
        <?php endif; ?>

        <div class="aside-card">
            <h3><?= icon('ticket', 'icon', 15) ?> Como funciona</h3>
            <p>A reserva bloqueia os lugares imediatamente, esteja pendente ou confirmada. Cancelar libera os lugares e preserva o histórico financeiro.</p>
            <ul>
                <li>Código gerado automaticamente.</li>
                <li>Uma reserva pode ter vários pagamentos.</li>
                <li>Quitação total confirma a reserva.</li>
            </ul>
        </div>
    </aside>
</div>
