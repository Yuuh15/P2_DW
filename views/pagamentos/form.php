<?php
$valor = static fn (string $campo, string $padrao = ''): string => (string) ($dados[$campo] ?? $padrao);
$erro = static fn (string $campo): ?string => $erros[$campo] ?? null;
$temErro = static fn (string $campo): bool => isset($erros[$campo]);
$urlFormulario = $modo === 'editar'
    ? url('pagamentos', ['acao' => 'editar', 'id' => $pagamento['id']])
    : url('pagamentos', ['acao' => 'novo']);

$reservaSelecionada = null;
foreach ($reservas as $reserva) {
    if ((int) $reserva['id'] === (int) $valor('reserva_id')) {
        $reservaSelecionada = $reserva;
        break;
    }
}

$valorAtualPagamento = $modo === 'editar' ? (float) $pagamento['valor'] : 0.0;
?>

<div class="page-head page-head--compact">
    <div class="page-head__copy">
        <a class="page-head__back" href="<?= $modo === 'editar' && $pagamento ? url('reservas', ['acao' => 'ver', 'id' => $pagamento['reserva_id']]) : url('pagamentos') ?>">
            <?= icon('arrow-left', 'icon', 13) ?> <?= $modo === 'editar' ? 'Voltar para a reserva' : 'Voltar para pagamentos' ?>
        </a>
        <h1><?= $modo === 'editar' ? 'Editar pagamento' : 'Novo pagamento' ?></h1>
        <p class="page-head__sub"><?= $modo === 'editar' ? 'Ajuste valor, forma, vencimento ou situação. O saldo da reserva é recalculado automaticamente.' : 'Registre um recebimento ou programe uma parcela. O sistema impede que a soma dos lançamentos ultrapasse o valor da reserva.' ?></p>
    </div>
</div>

<?php if ($erros): ?>
    <div class="flash flash--erro" role="alert">
        <?= icon('alert', 'flash__icon icon', 18) ?>
        <p>Revise os campos destacados para salvar o pagamento.</p>
    </div>
<?php endif; ?>

<div class="form-layout">
    <form class="form-panel" method="post" action="<?= $urlFormulario ?>" data-loading data-pagamento-form>
        <?= csrf_field() ?>

        <div class="form-panel__head">
            <h2>Dados do lançamento</h2>
            <p>Campos com <span class="required">*</span> são obrigatórios.</p>
        </div>

        <div class="form-section">
            <div class="form-section__title">Reserva e valor <span>01</span></div>
            <div class="form-grid form-grid--2">
                <div class="field field--full <?= $temErro('reserva_id') ? 'field--error' : '' ?>">
                    <label for="reserva_id">Reserva <span class="required">*</span></label>
                    <select id="reserva_id" name="reserva_id" required data-reserva-pagamento-select
                            <?= $temErro('reserva_id') ? 'aria-invalid="true" aria-describedby="erro-reserva"' : '' ?>>
                        <option value="" data-saldo="0">Selecione a reserva</option>
                        <?php foreach ($reservas as $reserva): ?>
                            <?php
                            $saldoOpcao = max(0, (float) $reserva['valor_total'] - (float) $reserva['valor_pago'] - (float) $reserva['valor_a_pagar']);
                            if ($modo === 'editar' && (int) $reserva['id'] === (int) $valor('reserva_id') && $pagamento['status'] !== 'estornado') {
                                $saldoOpcao += $valorAtualPagamento;
                            }
                            ?>
                            <option value="<?= (int) $reserva['id'] ?>"
                                    data-saldo="<?= e(valor_input($saldoOpcao)) ?>"
                                    <?= (int) $valor('reserva_id') === (int) $reserva['id'] ? 'selected' : '' ?>>
                                <?= e($reserva['codigo']) ?> · <?= e($reserva['cliente_nome']) ?> · <?= e($reserva['destino_nome']) ?>
                                · <?= e(data_br($reserva['data_ida'])) ?>
                                · saldo <?= e(money($saldoOpcao)) ?><?= $reserva['status'] === 'cancelada' ? ' · cancelada' : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if ($erro('reserva_id')): ?>
                        <p class="field__error" id="erro-reserva"><?= icon('alert', 'icon', 11) ?> <?= e($erro('reserva_id')) ?></p>
                    <?php else: ?>
                        <p class="field__help">O saldo exibido já desconta pagamentos recebidos e parcelas pendentes.</p>
                    <?php endif; ?>
                </div>

                <div class="field <?= $temErro('valor') ? 'field--error' : '' ?>">
                    <label for="valor">Valor do pagamento <span class="required">*</span></label>
                    <div class="input-wrap">
                        <?= icon('banknote', 'input-icon') ?>
                        <input type="number" id="valor" name="valor" value="<?= e($valor('valor')) ?>"
                               min="0.01" step="0.01" inputmode="decimal" placeholder="0,00" required
                               data-pagamento-valor
                               <?= $temErro('valor') ? 'aria-invalid="true" aria-describedby="erro-valor"' : '' ?>>
                        <button class="input-action" type="button" data-usar-saldo="<?= e(valor_input($disponivel)) ?>"
                                aria-label="Preencher com o saldo disponível" title="Usar saldo">
                            <?= icon('wallet', 'icon', 16) ?>
                        </button>
                    </div>
                    <?php if ($erro('valor')): ?>
                        <p class="field__error" id="erro-valor"><?= icon('alert', 'icon', 11) ?> <?= e($erro('valor')) ?></p>
                    <?php else: ?>
                        <p class="field__help">Saldo disponível para lançamentos: <strong><?= e(money($disponivel)) ?></strong>.</p>
                    <?php endif; ?>
                </div>

                <div class="field <?= $temErro('forma') ? 'field--error' : '' ?>">
                    <label for="forma">Forma de pagamento <span class="required">*</span></label>
                    <select id="forma" name="forma" required
                            <?= $temErro('forma') ? 'aria-invalid="true" aria-describedby="erro-forma"' : '' ?>>
                        <option value="pix" <?= $valor('forma', 'pix') === 'pix' ? 'selected' : '' ?>>Pix</option>
                        <option value="cartao" <?= $valor('forma') === 'cartao' ? 'selected' : '' ?>>Cartão</option>
                        <option value="boleto" <?= $valor('forma') === 'boleto' ? 'selected' : '' ?>>Boleto</option>
                        <option value="transferencia" <?= $valor('forma') === 'transferencia' ? 'selected' : '' ?>>Transferência</option>
                    </select>
                    <?php if ($erro('forma')): ?>
                        <p class="field__error" id="erro-forma"><?= icon('alert', 'icon', 11) ?> <?= e($erro('forma')) ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="form-section">
            <div class="form-section__title">Situação e datas <span>02</span></div>
            <div class="form-grid form-grid--2">
                <div class="field <?= $temErro('status') ? 'field--error' : '' ?>">
                    <label for="status">Situação do lançamento <span class="required">*</span></label>
                    <select id="status" name="status" required data-pagamento-status
                            <?= $temErro('status') ? 'aria-invalid="true" aria-describedby="erro-status"' : '' ?>>
                        <option value="pendente" <?= $valor('status') === 'pendente' ? 'selected' : '' ?>>Pendente — a receber</option>
                        <option value="pago" <?= $valor('status', 'pago') === 'pago' ? 'selected' : '' ?>>Pago — recebido</option>
                        <option value="estornado" <?= $valor('status') === 'estornado' ? 'selected' : '' ?>>Estornado — devolvido</option>
                    </select>
                    <?php if ($erro('status')): ?>
                        <p class="field__error" id="erro-status"><?= icon('alert', 'icon', 11) ?> <?= e($erro('status')) ?></p>
                    <?php else: ?>
                        <p class="field__help">Recebimentos e saldo da reserva usam apenas lançamentos pagos.</p>
                    <?php endif; ?>
                </div>

                <div class="field <?= $temErro('vencimento') ? 'field--error' : '' ?>">
                    <label for="vencimento">Vencimento</label>
                    <input type="date" id="vencimento" name="vencimento" value="<?= e($valor('vencimento')) ?>"
                           data-vencimento
                           <?= $temErro('vencimento') ? 'aria-invalid="true" aria-describedby="erro-vencimento"' : '' ?>>
                    <?php if ($erro('vencimento')): ?>
                        <p class="field__error" id="erro-vencimento"><?= icon('alert', 'icon', 11) ?> <?= e($erro('vencimento')) ?></p>
                    <?php else: ?>
                        <p class="field__help">Obrigatório para parcelas pendentes.</p>
                    <?php endif; ?>
                </div>

                <div class="field <?= $temErro('data_pagamento') ? 'field--error' : '' ?>">
                    <label for="data_pagamento">Data do pagamento</label>
                    <input type="date" id="data_pagamento" name="data_pagamento" value="<?= e($valor('data_pagamento')) ?>"
                           data-data-pagamento
                           <?= $temErro('data_pagamento') ? 'aria-invalid="true" aria-describedby="erro-data-pagamento"' : '' ?>>
                    <?php if ($erro('data_pagamento')): ?>
                        <p class="field__error" id="erro-data-pagamento"><?= icon('alert', 'icon', 11) ?> <?= e($erro('data_pagamento')) ?></p>
                    <?php else: ?>
                        <p class="field__help">Obrigatória para lançamentos pagos.</p>
                    <?php endif; ?>
                </div>

                <div class="field field--full <?= $temErro('observacoes') ? 'field--error' : '' ?>">
                    <label for="observacoes">Observação</label>
                    <input type="text" id="observacoes" name="observacoes" value="<?= e($valor('observacoes')) ?>"
                           maxlength="255" placeholder="Ex.: Sinal da reserva, saldo em 3x, boleto reemitido…" autocomplete="off"
                           <?= $temErro('observacoes') ? 'aria-invalid="true" aria-describedby="erro-observacoes"' : '' ?>>
                    <?php if ($erro('observacoes')): ?>
                        <p class="field__error" id="erro-observacoes"><?= icon('alert', 'icon', 11) ?> <?= e($erro('observacoes')) ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="form-actions">
            <span class="form-actions__hint">
                <?php if ($reservaSelecionada): ?>
                    <?= e($reservaSelecionada['codigo']) ?> · <?= e($reservaSelecionada['cliente_nome']) ?> · <?= e($reservaSelecionada['destino_nome']) ?>
                <?php else: ?>
                    Selecione a reserva para lançar o pagamento
                <?php endif; ?>
            </span>
            <a class="button button--secondary" href="<?= $modo === 'editar' && $pagamento ? url('reservas', ['acao' => 'ver', 'id' => $pagamento['reserva_id']]) : url('pagamentos') ?>">Cancelar</a>
            <button class="button button--primary" type="submit">
                <?= icon('check', 'icon', 16) ?>
                <span data-button-label><?= $modo === 'editar' ? 'Salvar alterações' : 'Registrar pagamento' ?></span>
            </button>
        </div>
    </form>

    <aside class="form-aside" aria-label="Orientações sobre pagamentos">
        <?php if ($reservaSelecionada): ?>
            <div class="aside-card aside-card--tint">
                <h3><?= icon('receipt', 'icon', 15) ?> Reserva selecionada</h3>
                <p><strong><?= e($reservaSelecionada['codigo']) ?></strong><br><?= e($reservaSelecionada['cliente_nome']) ?> · <?= e($reservaSelecionada['destino_nome']) ?></p>
                <ul>
                    <li>Total: <?= e(money($reservaSelecionada['valor_total'])) ?></li>
                    <li>Recebido: <?= e(money($reservaSelecionada['valor_pago'])) ?></li>
                    <li>Pendente: <?= e(money($reservaSelecionada['valor_a_pagar'])) ?></li>
                    <li>Disponível: <?= e(money($disponivel)) ?></li>
                </ul>
            </div>
        <?php endif; ?>

        <div class="aside-card">
            <h3><?= icon('wallet', 'icon', 15) ?> Regras do financeiro</h3>
            <p>O valor lançado não pode ultrapassar o saldo disponível da reserva. Quitar 100% do total confirma a reserva automaticamente.</p>
            <ul>
                <li>Pendente gera cobrança e vencimento.</li>
                <li>Pago entra no valor recebido.</li>
                <li>Estornado sai do saldo comprometido.</li>
            </ul>
        </div>

        <?php if ($modo === 'editar' && $pagamento && $pagamento['status'] === 'pago'): ?>
            <div class="aside-card aside-card--warn">
                <h3><?= icon('alert', 'icon', 15) ?> Pagamento recebido</h3>
                <p>Este lançamento já entrou no caixa. Se precisar desfazer o recebimento, altere a situação para <strong>estornado</strong> em vez de excluir o registro.</p>
            </div>
        <?php endif; ?>
    </aside>
</div>
