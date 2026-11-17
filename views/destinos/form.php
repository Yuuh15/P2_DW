<?php
$valor = static fn (string $campo, string $padrao = ''): string => (string) ($dados[$campo] ?? $padrao);
$erro = static fn (string $campo): ?string => $erros[$campo] ?? null;
$temErro = static fn (string $campo): bool => isset($erros[$campo]);
$urlFormulario = $modo === 'editar'
    ? url('destinos', ['acao' => 'editar', 'id' => $destino['id']])
    : url('destinos', ['acao' => 'novo']);
$imagemAtual = $valor('imagem') !== '' ? asset($valor('imagem')) : null;
?>

<div class="page-head page-head--compact">
    <div class="page-head__copy">
        <a class="page-head__back" href="<?= url('destinos') ?>"><?= icon('arrow-left', 'icon', 13) ?> Voltar para destinos</a>
        <h1><?= $modo === 'editar' ? 'Editar destino' : 'Novo destino' ?></h1>
        <p class="page-head__sub"><?= $modo === 'editar' ? 'Atualize a apresentação, a época ideal e a imagem de capa.' : 'Apresente o destino com uma boa imagem e informações que ajudem a equipe a vender melhor.' ?></p>
    </div>
</div>

<?php if ($erros): ?>
    <div class="flash flash--erro" role="alert">
        <?= icon('alert', 'flash__icon icon', 18) ?>
        <p>Revise os campos destacados para salvar o destino.</p>
    </div>
<?php endif; ?>

<div class="form-layout">
    <form class="form-panel" method="post" action="<?= $urlFormulario ?>" enctype="multipart/form-data" data-loading>
        <?= csrf_field() ?>

        <div class="form-panel__head">
            <h2>Informações do destino</h2>
            <p>Campos com <span class="required">*</span> são obrigatórios.</p>
        </div>

        <div class="form-section">
            <div class="form-section__title">Apresentação <span>01</span></div>
            <div class="form-grid form-grid--2">
                <div class="field field--full <?= $temErro('nome') ? 'field--error' : '' ?>">
                    <label for="nome">Nome do destino <span class="required">*</span></label>
                    <input type="text" id="nome" name="nome" value="<?= e($valor('nome')) ?>" maxlength="120"
                           placeholder="Ex.: Fernando de Noronha" required autocomplete="off"
                           <?= $temErro('nome') ? 'aria-invalid="true" aria-describedby="erro-nome"' : '' ?>>
                    <?php if ($erro('nome')): ?>
                        <p class="field__error" id="erro-nome"><?= icon('alert', 'icon', 11) ?> <?= e($erro('nome')) ?></p>
                    <?php endif; ?>
                </div>

                <div class="field <?= $temErro('pais') ? 'field--error' : '' ?>">
                    <label for="pais">País <span class="required">*</span></label>
                    <input type="text" id="pais" name="pais" value="<?= e($valor('pais', 'Brasil')) ?>" maxlength="80"
                           autocomplete="country-name" required
                           <?= $temErro('pais') ? 'aria-invalid="true" aria-describedby="erro-pais"' : '' ?>>
                    <?php if ($erro('pais')): ?>
                        <p class="field__error" id="erro-pais"><?= icon('alert', 'icon', 11) ?> <?= e($erro('pais')) ?></p>
                    <?php endif; ?>
                </div>

                <div class="field <?= $temErro('regiao') ? 'field--error' : '' ?>">
                    <label for="regiao">Região</label>
                    <input type="text" id="regiao" name="regiao" value="<?= e($valor('regiao')) ?>" maxlength="80"
                           placeholder="Ex.: Nordeste" autocomplete="off"
                           <?= $temErro('regiao') ? 'aria-invalid="true" aria-describedby="erro-regiao"' : '' ?>>
                    <?php if ($erro('regiao')): ?>
                        <p class="field__error" id="erro-regiao"><?= icon('alert', 'icon', 11) ?> <?= e($erro('regiao')) ?></p>
                    <?php endif; ?>
                </div>

                <div class="field <?= $temErro('melhor_epoca') ? 'field--error' : '' ?>">
                    <label for="melhor_epoca">Melhor época para viajar</label>
                    <input type="text" id="melhor_epoca" name="melhor_epoca" value="<?= e($valor('melhor_epoca')) ?>"
                           maxlength="100" placeholder="Ex.: Agosto a novembro"
                           <?= $temErro('melhor_epoca') ? 'aria-invalid="true" aria-describedby="erro-epoca"' : '' ?>>
                    <?php if ($erro('melhor_epoca')): ?>
                        <p class="field__error" id="erro-epoca"><?= icon('alert', 'icon', 11) ?> <?= e($erro('melhor_epoca')) ?></p>
                    <?php endif; ?>
                </div>

                <div class="field field--full <?= $temErro('descricao') ? 'field--error' : '' ?>">
                    <label for="descricao">Descrição</label>
                    <textarea id="descricao" name="descricao" maxlength="3000"
                              placeholder="Conte o que torna este destino especial, os passeios e o clima da viagem."><?= e($valor('descricao')) ?></textarea>
                    <p class="field__help">Esse texto aparece na apresentação do destino para a equipe comercial.</p>
                </div>
            </div>
        </div>

        <div class="form-section">
            <div class="form-section__title">Imagem e disponibilidade <span>02</span></div>
            <div class="form-grid">
                <div class="field <?= $temErro('imagem') ? 'field--error' : '' ?>">
                    <label for="imagem">Imagem de capa</label>

                    <?php if ($imagemAtual): ?>
                        <div class="upload-preview">
                            <img src="<?= e($imagemAtual) ?>" alt="Imagem atual de <?= e($valor('nome')) ?>" width="88" height="56" loading="lazy">
                            <div>
                                <strong>Imagem atual</strong>
                                <p>Envie um novo arquivo para substituir a capa deste destino.</p>
                            </div>
                        </div>
                    <?php endif; ?>

                    <input type="file" id="imagem" name="imagem" accept="image/jpeg,image/png,image/webp"
                           <?= $temErro('imagem') ? 'aria-invalid="true" aria-describedby="erro-imagem"' : 'aria-describedby="ajuda-imagem"' ?>>
                    <p class="field__help" id="ajuda-imagem">JPG, PNG ou WebP, até 4 MB. Prefira imagens horizontais (16:9).</p>
                    <?php if ($erro('imagem')): ?>
                        <p class="field__error" id="erro-imagem"><?= icon('alert', 'icon', 11) ?> <?= e($erro('imagem')) ?></p>
                    <?php endif; ?>

                    <?php if ($imagemAtual): ?>
                        <label class="switch-row" for="remover_imagem" style="margin-top: 4px;">
                            <input type="checkbox" id="remover_imagem" name="remover_imagem" value="1">
                            <span>
                                <span style="font-size: 11.5px; font-weight: 550;">Remover a imagem atual</span>
                                <small>O destino ficará com uma capa neutra até o próximo envio.</small>
                            </span>
                        </label>
                    <?php endif; ?>
                </div>

                <label class="switch-row" for="ativo" style="margin-top: 2px;">
                    <input type="checkbox" id="ativo" name="ativo" value="1" <?= (int) ($dados['ativo'] ?? 1) === 1 ? 'checked' : '' ?>>
                    <span>
                        <span style="font-size: 11.5px; font-weight: 550;">Destino ativo no catálogo</span>
                        <small>Destinos inativos não aparecem nas sugestões de novos pacotes.</small>
                    </span>
                </label>
            </div>
        </div>

        <div class="form-actions">
            <span class="form-actions__hint">A imagem é opcional · formatos JPG, PNG e WebP</span>
            <a class="button button--secondary" href="<?= url('destinos') ?>">Cancelar</a>
            <button class="button button--primary" type="submit">
                <?= icon('check', 'icon', 16) ?>
                <span data-button-label><?= $modo === 'editar' ? 'Salvar alterações' : 'Cadastrar destino' ?></span>
            </button>
        </div>
    </form>

    <aside class="form-aside" aria-label="Orientações sobre destinos">
        <div class="aside-card aside-card--tint">
            <h3><?= icon('map', 'icon', 15) ?> Uma boa capa vende</h3>
            <p>Use imagens nítidas, horizontais e com o destino como protagonista. A capa aparece no catálogo e nos relatórios internos.</p>
            <ul>
                <li>Proporção recomendada: 16:9.</li>
                <li>Evite textos e logotipos na imagem.</li>
                <li>O peso máximo é de 4 MB.</li>
            </ul>
        </div>
        <div class="aside-card">
            <h3><?= icon('package', 'icon', 15) ?> Antes de excluir</h3>
            <p>Destinos com pacotes vinculados são protegidos. Exclua ou mova os pacotes antes de remover o destino do catálogo.</p>
        </div>
    </aside>
</div>
