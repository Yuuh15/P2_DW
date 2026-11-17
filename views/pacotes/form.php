<?php
$valor = static fn (string $campo, string $padrao = ''): string => (string) ($dados[$campo] ?? $padrao);
$erro = static fn (string $campo): ?string => $erros[$campo] ?? null;
$temErro = static fn (string $campo): bool => isset($erros[$campo]);
$urlFormulario = $modo === 'editar'
    ? url('pacotes', ['acao' => 'editar', 'id' => $pacote['id']])
    : url('pacotes', ['acao' => 'novo']);
$destinoAtual = null;
foreach ($destinos as $destino) {
    if ((int) $destino['id'] === (int) $valor('destino_id')) {
        $destinoAtual = $destino;
        break;
    }
}
?>

<div class="page-head page-head--compact">
    <div class="page-head__copy">
        <a class="page-head__back" href="<?= url('pacotes') ?>"><?= icon('arrow-left', 'icon', 13) ?> Voltar para pacotes</a>
        <h1><?= $modo === 'editar' ? 'Editar pacote' : 'Novo pacote' ?></h1>
        <p class="page-head__sub"><?= $modo === 'editar' ? 'Atualize os valores e o conteúdo do pacote. As partidas já criadas não são alteradas.' : 'Monte a oferta que será vendida pela agência. As partidas são cadastradas separadamente.' ?></p>
    </div>
</div>

<?php if ($erros): ?>
    <div class="flash flash--erro" role="alert">
        <?= icon('alert', 'flash__icon icon', 18) ?>
        <p>Revise os campos destacados para salvar o pacote.</p>
    </div>
<?php endif; ?>

<div class="form-layout">
    <form class="form-panel" method="post" action="<?= $urlFormulario ?>" data-loading>
        <?= csrf_field() ?>

        <div class="form-panel__head">
            <h2>Conteúdo do pacote</h2>
            <p>Campos com <span class="required">*</span> são obrigatórios.</p>
        </div>

        <div class="form-section">
            <div class="form-section__title">Oferta <span>01</span></div>
            <div class="form-grid form-grid--2">
                <div class="field <?= $temErro('destino_id') ? 'field--error' : '' ?>">
                    <label for="destino_id">Destino <span class="required">*</span></label>
                    <select id="destino_id" name="destino_id" required
                            <?= $temErro('destino_id') ? 'aria-invalid="true" aria-describedby="erro-destino"' : '' ?>>
                        <option value="">Selecione o destino</option>
                        <?php foreach ($destinos as $destino): ?>
                            <option value="<?= (int) $destino['id'] ?>" <?= (int) $valor('destino_id') === (int) $destino['id'] ? 'selected' : '' ?>>
                                <?= e($destino['nome']) ?> — <?= e($destino['pais']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if ($erro('destino_id')): ?>
                        <p class="field__error" id="erro-destino"><?= icon('alert', 'icon', 11) ?> <?= e($erro('destino_id')) ?></p>
                    <?php endif; ?>
                </div>

                <div class="field <?= $temErro('duracao_dias') ? 'field--error' : '' ?>">
                    <label for="duracao_dias">Duração em dias <span class="required">*</span></label>
                    <input type="number" id="duracao_dias" name="duracao_dias" value="<?= e($valor('duracao_dias')) ?>"
                           min="1" max="60" step="1" required inputmode="numeric"
                           <?= $temErro('duracao_dias') ? 'aria-invalid="true" aria-describedby="erro-duracao"' : '' ?>>
                    <?php if ($erro('duracao_dias')): ?>
                        <p class="field__error" id="erro-duracao"><?= icon('alert', 'icon', 11) ?> <?= e($erro('duracao_dias')) ?></p>
                    <?php endif; ?>
                </div>

                <div class="field field--full <?= $temErro('titulo') ? 'field--error' : '' ?>">
                    <label for="titulo">Título do pacote <span class="required">*</span></label>
                    <input type="text" id="titulo" name="titulo" value="<?= e($valor('titulo')) ?>" maxlength="160"
                           placeholder="Ex.: Noronha Inteira — 7 dias" required autocomplete="off"
                           <?= $temErro('titulo') ? 'aria-invalid="true" aria-describedby="erro-titulo"' : '' ?>>
                    <?php if ($erro('titulo')): ?>
                        <p class="field__error" id="erro-titulo"><?= icon('alert', 'icon', 11) ?> <?= e($erro('titulo')) ?></p>
                    <?php endif; ?>
                </div>

                <div class="field <?= $temErro('preco') ? 'field--error' : '' ?>">
                    <label for="preco">Preço por pessoa <span class="required">*</span></label>
                    <input type="number" id="preco" name="preco" value="<?= e($valor('preco')) ?>"
                           min="0.01" step="0.01" inputmode="decimal" placeholder="0,00" required
                           <?= $temErro('preco') ? 'aria-invalid="true" aria-describedby="erro-preco"' : '' ?>>
                    <?php if ($erro('preco')): ?>
                        <p class="field__error" id="erro-preco"><?= icon('alert', 'icon', 11) ?> <?= e($erro('preco')) ?></p>
                    <?php else: ?>
                        <p class="field__help">O valor da reserva é calculado por pessoa e pode ser ajustado manualmente.</p>
                    <?php endif; ?>
                </div>

                <label class="switch-row" for="ativo">
                    <input type="checkbox" id="ativo" name="ativo" value="1" <?= (int) ($dados['ativo'] ?? 1) === 1 ? 'checked' : '' ?>>
                    <span>
                        <span style="font-size: 11.5px; font-weight: 550;">Pacote disponível para venda</span>
                        <small>Pacotes inativos continuam no histórico, mas não devem receber novas reservas.</small>
                    </span>
                </label>
            </div>
        </div>

        <div class="form-section">
            <div class="form-section__title">Descrição e inclusos <span>02</span></div>
            <div class="form-grid">
                <div class="field <?= $temErro('descricao') ? 'field--error' : '' ?>">
                    <label for="descricao">Descrição</label>
                    <textarea id="descricao" name="descricao" maxlength="3000"
                              placeholder="Descreva a experiência, o ritmo da viagem e o perfil ideal de viajante."><?= e($valor('descricao')) ?></textarea>
                    <?php if ($erro('descricao')): ?>
                        <p class="field__error"><?= icon('alert', 'icon', 11) ?> <?= e($erro('descricao')) ?></p>
                    <?php endif; ?>
                </div>

                <div class="field <?= $temErro('inclusos') ? 'field--error' : '' ?>">
                    <label for="inclusos">O que está incluso</label>
                    <textarea id="inclusos" name="inclusos" maxlength="2000"
                              placeholder="Ex.: Aéreo, hotel 4 estrelas com café, transfers e ingressos."><?= e($valor('inclusos')) ?></textarea>
                    <p class="field__help">Separe os itens por vírgulas. Eles aparecem na ficha do pacote e na reserva.</p>
                    <?php if ($erro('inclusos')): ?>
                        <p class="field__error"><?= icon('alert', 'icon', 11) ?> <?= e($erro('inclusos')) ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="form-actions">
            <span class="form-actions__hint"><?= $destinoAtual ? 'Destino selecionado: ' . e($destinoAtual['nome']) : 'Selecione um destino para continuar' ?></span>
            <a class="button button--secondary" href="<?= url('pacotes') ?>">Cancelar</a>
            <button class="button button--primary" type="submit">
                <?= icon('check', 'icon', 16) ?>
                <span data-button-label><?= $modo === 'editar' ? 'Salvar alterações' : 'Cadastrar pacote' ?></span>
            </button>
        </div>
    </form>

    <aside class="form-aside" aria-label="Orientações sobre pacotes">
        <div class="aside-card aside-card--tint">
            <h3><?= icon('package', 'icon', 15) ?> Pacote ou partida?</h3>
            <p>O pacote descreve a oferta: destino, duração, preço e inclusos. A partida é uma data específica com capacidade e status de embarque.</p>
            <ul>
                <li>Crie o pacote uma vez.</li>
                <li>Abra quantas partidas precisar.</li>
                <li>Cada partida tem ocupação própria.</li>
            </ul>
        </div>
        <div class="aside-card">
            <h3><?= icon('banknote', 'icon', 15) ?> Preço por pessoa</h3>
            <p>O valor da reserva é sugerido automaticamente como preço × número de viajantes. Descontos podem ser aplicados na reserva.</p>
        </div>
    </aside>
</div>
