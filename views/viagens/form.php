<?php
$valor = static fn (string $campo, string $padrao = ''): string => (string) ($dados[$campo] ?? $padrao);
$erro = static fn (string $campo): ?string => $erros[$campo] ?? null;
$temErro = static fn (string $campo): bool => isset($erros[$campo]);
$urlFormulario = $modo === 'editar'
    ? url('viagens', ['acao' => 'editar', 'id' => $viagem['id']])
    : url('viagens', ['acao' => 'novo']);
$pacoteAtual = null;
foreach ($pacotes as $pacote) {
    if ((int) $pacote['id'] === (int) $valor('pacote_id')) {
        $pacoteAtual = $pacote;
        break;
    }
}
$rotulosStatus = [
    'planejada'    => 'Planejada — vendas abertas',
    'confirmada'   => 'Confirmada — partida garantida',
    'em_andamento' => 'Em andamento — grupo viajando',
    'concluida'    => 'Concluída — retorno realizado',
    'cancelada'    => 'Cancelada — sem operação',
];
?>

<div class="page-head page-head--compact">
    <div class="page-head__copy">
        <a class="page-head__back" href="<?= $modo === 'editar' ? url('viagens', ['acao' => 'ver', 'id' => $viagem['id']]) : url('viagens') ?>">
            <?= icon('arrow-left', 'icon', 13) ?> <?= $modo === 'editar' ? 'Voltar para a partida' : 'Voltar para viagens' ?>
        </a>
        <h1><?= $modo === 'editar' ? 'Editar partida' : 'Nova partida' ?></h1>
        <p class="page-head__sub"><?= $modo === 'editar' ? 'Atualize datas, capacidade e situação operacional. A ocupação é recalculada a partir das reservas ativas.' : 'Abra uma data de embarque para um pacote existente. A capacidade limita as reservas ativas da partida.' ?></p>
    </div>
</div>

<?php if ($erros): ?>
    <div class="flash flash--erro" role="alert">
        <?= icon('alert', 'flash__icon icon', 18) ?>
        <p>Revise os campos destacados para salvar a partida.</p>
    </div>
<?php endif; ?>

<div class="form-layout">
    <form class="form-panel" method="post" action="<?= $urlFormulario ?>" data-loading data-sync-dates>
        <?= csrf_field() ?>

        <div class="form-panel__head">
            <h2>Dados da partida</h2>
            <p>Campos com <span class="required">*</span> são obrigatórios.</p>
        </div>

        <div class="form-section">
            <div class="form-section__title">Roteiro e datas <span>01</span></div>
            <div class="form-grid form-grid--2">
                <div class="field field--full <?= $temErro('pacote_id') ? 'field--error' : '' ?>">
                    <label for="pacote_id">Pacote turístico <span class="required">*</span></label>
                    <select id="pacote_id" name="pacote_id" required
                            <?= $temErro('pacote_id') ? 'aria-invalid="true" aria-describedby="erro-pacote"' : '' ?>>
                        <option value="">Selecione o pacote</option>
                        <?php foreach ($pacotes as $pacote): ?>
                            <option value="<?= (int) $pacote['id'] ?>" <?= (int) $valor('pacote_id') === (int) $pacote['id'] ? 'selected' : '' ?>>
                                <?= e($pacote['destino_nome']) ?> · <?= e($pacote['titulo']) ?> — <?= (int) $pacote['duracao_dias'] ?> dias · <?= e(money($pacote['preco'])) ?>/pessoa
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if ($erro('pacote_id')): ?>
                        <p class="field__error" id="erro-pacote"><?= icon('alert', 'icon', 11) ?> <?= e($erro('pacote_id')) ?></p>
                    <?php else: ?>
                        <p class="field__help">O destino, a duração e o preço por pessoa vêm do pacote selecionado.</p>
                    <?php endif; ?>
                </div>

                <div class="field <?= $temErro('data_ida') ? 'field--error' : '' ?>">
                    <label for="data_ida">Data de ida <span class="required">*</span></label>
                    <input type="date" id="data_ida" name="data_ida" value="<?= e($valor('data_ida')) ?>" required
                           data-data-ida
                           <?= $temErro('data_ida') ? 'aria-invalid="true" aria-describedby="erro-ida"' : '' ?>>
                    <?php if ($erro('data_ida')): ?>
                        <p class="field__error" id="erro-ida"><?= icon('alert', 'icon', 11) ?> <?= e($erro('data_ida')) ?></p>
                    <?php endif; ?>
                </div>

                <div class="field <?= $temErro('data_volta') ? 'field--error' : '' ?>">
                    <label for="data_volta">Data de volta <span class="required">*</span></label>
                    <input type="date" id="data_volta" name="data_volta" value="<?= e($valor('data_volta')) ?>" required
                           data-data-volta
                           <?= $temErro('data_volta') ? 'aria-invalid="true" aria-describedby="erro-volta"' : '' ?>>
                    <?php if ($erro('data_volta')): ?>
                        <p class="field__error" id="erro-volta"><?= icon('alert', 'icon', 11) ?> <?= e($erro('data_volta')) ?></p>
                    <?php else: ?>
                        <p class="field__help">A volta não pode ser anterior à ida.</p>
                    <?php endif; ?>
                </div>

                <div class="field <?= $temErro('capacidade') ? 'field--error' : '' ?>">
                    <label for="capacidade">Capacidade de viajantes <span class="required">*</span></label>
                    <input type="number" id="capacidade" name="capacidade" value="<?= e($valor('capacidade')) ?>"
                           min="<?= max(1, $ocupados) ?>" max="500" step="1" required inputmode="numeric"
                           <?= $temErro('capacidade') ? 'aria-invalid="true" aria-describedby="erro-capacidade"' : '' ?>>
                    <?php if ($erro('capacidade')): ?>
                        <p class="field__error" id="erro-capacidade"><?= icon('alert', 'icon', 11) ?> <?= e($erro('capacidade')) ?></p>
                    <?php elseif ($modo === 'editar' && $ocupados > 0): ?>
                        <p class="field__help"><?= $ocupados ?> lugar<?= $ocupados === 1 ? '' : 'es' ?> já ocupado<?= $ocupados === 1 ? '' : 's' ?> por reservas ativas.</p>
                    <?php else: ?>
                        <p class="field__help">Limite máximo de passageiros nesta partida.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="form-section">
            <div class="form-section__title">Operação e embarque <span>02</span></div>
            <div class="form-grid form-grid--2">
                <div class="field <?= $temErro('status') ? 'field--error' : '' ?>">
                    <label for="status">Situação da partida <span class="required">*</span></label>
                    <select id="status" name="status" required
                            <?= $temErro('status') ? 'aria-invalid="true" aria-describedby="erro-status"' : '' ?>>
                        <?php foreach ($statusPermitidos as $chave): ?>
                            <option value="<?= e($chave) ?>" <?= $valor('status', 'planejada') === $chave ? 'selected' : '' ?>>
                                <?= e($rotulosStatus[$chave] ?? status_viagem_label($chave)) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if ($erro('status')): ?>
                        <p class="field__error" id="erro-status"><?= icon('alert', 'icon', 11) ?> <?= e($erro('status')) ?></p>
                    <?php else: ?>
                        <p class="field__help">A situação avança em ordem: planejada, confirmada, em andamento e concluída.</p>
                    <?php endif; ?>
                </div>

                <div class="field <?= $temErro('ponto_encontro') ? 'field--error' : '' ?>">
                    <label for="ponto_encontro">Ponto de encontro</label>
                    <input type="text" id="ponto_encontro" name="ponto_encontro" value="<?= e($valor('ponto_encontro')) ?>"
                           maxlength="180" placeholder="Ex.: Aeroporto de Guarulhos — Terminal 2, 07h10" autocomplete="off"
                           <?= $temErro('ponto_encontro') ? 'aria-invalid="true" aria-describedby="erro-encontro"' : '' ?>>
                    <p class="field__help">Aparece na ficha da partida para orientar o grupo.</p>
                    <?php if ($erro('ponto_encontro')): ?>
                        <p class="field__error" id="erro-encontro"><?= icon('alert', 'icon', 11) ?> <?= e($erro('ponto_encontro')) ?></p>
                    <?php endif; ?>
                </div>

                <div class="field field--full <?= $temErro('observacoes') ? 'field--error' : '' ?>">
                    <label for="observacoes">Observações operacionais</label>
                    <textarea id="observacoes" name="observacoes" maxlength="2000"
                              placeholder="Instruções sobre documentação, traslados, guias ou necessidades do grupo."><?= e($valor('observacoes')) ?></textarea>
                    <p class="field__help">Use este espaço para informações internas da equipe.</p>
                    <?php if ($erro('observacoes')): ?>
                        <p class="field__error"><?= icon('alert', 'icon', 11) ?> <?= e($erro('observacoes')) ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="form-actions">
            <span class="form-actions__hint"><?= $pacoteAtual ? 'Pacote: ' . e($pacoteAtual['destino_nome']) . ' · ' . e($pacoteAtual['titulo']) : 'Selecione um pacote para continuar' ?></span>
            <a class="button button--secondary" href="<?= $modo === 'editar' ? url('viagens', ['acao' => 'ver', 'id' => $viagem['id']]) : url('viagens') ?>">Cancelar</a>
            <button class="button button--primary" type="submit">
                <?= icon('check', 'icon', 16) ?>
                <span data-button-label><?= $modo === 'editar' ? 'Salvar alterações' : 'Criar partida' ?></span>
            </button>
        </div>
    </form>

    <aside class="form-aside" aria-label="Orientações sobre partidas">
        <div class="aside-card aside-card--tint">
            <h3><?= icon('plane', 'icon', 15) ?> Partida x pacote</h3>
            <p>O pacote é a oferta comercial. A partida é a data de embarque, com capacidade e situação próprias.</p>
            <ul>
                <li>Um pacote pode ter várias partidas.</li>
                <li>Reservas ativas ocupam lugares.</li>
                <li>Cancelar não apaga o histórico.</li>
            </ul>
        </div>
        <div class="aside-card">
            <h3><?= icon('sliders', 'icon', 15) ?> Fluxo de situação</h3>
            <p>Planejada → Confirmada → Em andamento → Concluída. Uma partida só pode ser cancelada depois que as reservas ativas forem canceladas ou transferidas.</p>
        </div>
        <?php if ($modo === 'editar' && $viagem): ?>
            <div class="aside-card">
                <h3><?= icon('ticket', 'icon', 15) ?> Ocupação atual</h3>
                <p><?= $ocupados ?> de <?= (int) $viagem['capacidade'] ?> lugares ocupados. A capacidade mínima aceita é <?= max(1, $ocupados) ?>.</p>
            </div>
        <?php endif; ?>
    </aside>
</div>
