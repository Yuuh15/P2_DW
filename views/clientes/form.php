<?php
$valor = static fn (string $campo, string $padrao = ''): string => (string) ($dados[$campo] ?? $padrao);
$erro = static fn (string $campo): ?string => $erros[$campo] ?? null;
$temErro = static fn (string $campo): bool => isset($erros[$campo]);

$ufs = [
    'AC' => 'Acre', 'AL' => 'Alagoas', 'AP' => 'Amapá', 'AM' => 'Amazonas', 'BA' => 'Bahia',
    'CE' => 'Ceará', 'DF' => 'Distrito Federal', 'ES' => 'Espírito Santo', 'GO' => 'Goiás',
    'MA' => 'Maranhão', 'MT' => 'Mato Grosso', 'MS' => 'Mato Grosso do Sul', 'MG' => 'Minas Gerais',
    'PA' => 'Pará', 'PB' => 'Paraíba', 'PR' => 'Paraná', 'PE' => 'Pernambuco', 'PI' => 'Piauí',
    'RJ' => 'Rio de Janeiro', 'RN' => 'Rio Grande do Norte', 'RS' => 'Rio Grande do Sul',
    'RO' => 'Rondônia', 'RR' => 'Roraima', 'SC' => 'Santa Catarina', 'SP' => 'São Paulo',
    'SE' => 'Sergipe', 'TO' => 'Tocantins',
];

$urlFormulario = $modo === 'editar'
    ? url('clientes', ['acao' => 'editar', 'id' => $cliente['id']])
    : url('clientes', ['acao' => 'novo']);
?>

<div class="page-head page-head--compact">
    <div class="page-head__copy">
        <a class="page-head__back" href="<?= $modo === 'editar' ? url('clientes', ['acao' => 'ver', 'id' => $cliente['id']]) : url('clientes') ?>">
            <?= icon('arrow-left', 'icon', 13) ?> <?= $modo === 'editar' ? 'Voltar para a ficha' : 'Voltar para clientes' ?>
        </a>
        <h1><?= $modo === 'editar' ? 'Editar cliente' : 'Novo cliente' ?></h1>
        <p class="page-head__sub"><?= $modo === 'editar' ? 'Atualize os dados de contato e as preferências deste cliente.' : 'Preencha a ficha uma vez. O histórico de viagens e pagamentos fica vinculado a ela.' ?></p>
    </div>
</div>

<?php if ($erros): ?>
    <div class="flash flash--erro" role="alert">
        <?= icon('alert', 'flash__icon icon', 18) ?>
        <p>Revise os campos destacados abaixo para salvar a ficha do cliente.</p>
    </div>
<?php endif; ?>

<div class="form-layout">
    <form class="form-panel" method="post" action="<?= $urlFormulario ?>" data-loading>
        <?= csrf_field() ?>

        <div class="form-panel__head">
            <h2>Ficha do cliente</h2>
            <p>Campos com <span class="required">*</span> são obrigatórios.</p>
        </div>

        <div class="form-section">
            <div class="form-section__title">Identificação <span>01</span></div>
            <div class="form-grid form-grid--2">
                <div class="field field--full <?= $temErro('nome') ? 'field--error' : '' ?>">
                    <label for="nome">Nome completo <span class="required">*</span></label>
                    <input type="text" id="nome" name="nome" value="<?= e($valor('nome')) ?>"
                           autocomplete="name" maxlength="160" required
                           <?= $temErro('nome') ? 'aria-invalid="true" aria-describedby="erro-nome"' : '' ?>>
                    <?php if ($erro('nome')): ?>
                        <p class="field__error" id="erro-nome"><?= icon('alert', 'icon', 11) ?> <?= e($erro('nome')) ?></p>
                    <?php endif; ?>
                </div>

                <div class="field <?= $temErro('cpf') ? 'field--error' : '' ?>">
                    <label for="cpf">CPF <span class="required">*</span></label>
                    <input type="text" id="cpf" name="cpf" value="<?= e($valor('cpf') !== '' ? formatar_cpf($valor('cpf')) : '') ?>"
                           data-mask="cpf" inputmode="numeric" autocomplete="off" maxlength="14"
                           placeholder="000.000.000-00" required
                           <?= $temErro('cpf') ? 'aria-invalid="true" aria-describedby="erro-cpf"' : '' ?>>
                    <?php if ($erro('cpf')): ?>
                        <p class="field__error" id="erro-cpf"><?= icon('alert', 'icon', 11) ?> <?= e($erro('cpf')) ?></p>
                    <?php else: ?>
                        <p class="field__help">Usado para emitir passagens e documentos.</p>
                    <?php endif; ?>
                </div>

                <div class="field <?= $temErro('data_nascimento') ? 'field--error' : '' ?>">
                    <label for="data_nascimento">Data de nascimento</label>
                    <input type="date" id="data_nascimento" name="data_nascimento"
                           value="<?= e($valor('data_nascimento')) ?>" max="<?= e(date('Y-m-d')) ?>"
                           <?= $temErro('data_nascimento') ? 'aria-invalid="true" aria-describedby="erro-nascimento"' : '' ?>>
                    <?php if ($erro('data_nascimento')): ?>
                        <p class="field__error" id="erro-nascimento"><?= icon('alert', 'icon', 11) ?> <?= e($erro('data_nascimento')) ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="form-section">
            <div class="form-section__title">Contato e localização <span>02</span></div>
            <div class="form-grid form-grid--2">
                <div class="field <?= $temErro('email') ? 'field--error' : '' ?>">
                    <label for="email">E-mail <span class="required">*</span></label>
                    <input type="email" id="email" name="email" value="<?= e($valor('email')) ?>"
                           autocomplete="email" spellcheck="false" maxlength="160" placeholder="cliente@email.com" required
                           <?= $temErro('email') ? 'aria-invalid="true" aria-describedby="erro-email"' : '' ?>>
                    <?php if ($erro('email')): ?>
                        <p class="field__error" id="erro-email"><?= icon('alert', 'icon', 11) ?> <?= e($erro('email')) ?></p>
                    <?php endif; ?>
                </div>

                <div class="field <?= $temErro('telefone') ? 'field--error' : '' ?>">
                    <label for="telefone">Telefone <span class="required">*</span></label>
                    <input type="tel" id="telefone" name="telefone" value="<?= e($valor('telefone') !== '' ? formatar_telefone($valor('telefone')) : '') ?>"
                           data-mask="telefone" inputmode="tel" autocomplete="tel" maxlength="15"
                           placeholder="(11) 99999-9999" required
                           <?= $temErro('telefone') ? 'aria-invalid="true" aria-describedby="erro-telefone"' : '' ?>>
                    <?php if ($erro('telefone')): ?>
                        <p class="field__error" id="erro-telefone"><?= icon('alert', 'icon', 11) ?> <?= e($erro('telefone')) ?></p>
                    <?php endif; ?>
                </div>

                <div class="field <?= $temErro('cidade') ? 'field--error' : '' ?>">
                    <label for="cidade">Cidade</label>
                    <input type="text" id="cidade" name="cidade" value="<?= e($valor('cidade')) ?>"
                           autocomplete="address-level2" maxlength="100" placeholder="São Paulo"
                           <?= $temErro('cidade') ? 'aria-invalid="true" aria-describedby="erro-cidade"' : '' ?>>
                    <?php if ($erro('cidade')): ?>
                        <p class="field__error" id="erro-cidade"><?= icon('alert', 'icon', 11) ?> <?= e($erro('cidade')) ?></p>
                    <?php endif; ?>
                </div>

                <div class="field <?= $temErro('estado') ? 'field--error' : '' ?>">
                    <label for="estado">Estado</label>
                    <select id="estado" name="estado" autocomplete="address-level1"
                            <?= $temErro('estado') ? 'aria-invalid="true" aria-describedby="erro-estado"' : '' ?>>
                        <option value="">Selecione a UF</option>
                        <?php foreach ($ufs as $sigla => $nome): ?>
                            <option value="<?= e($sigla) ?>" <?= $valor('estado') === $sigla ? 'selected' : '' ?>><?= e($sigla . ' — ' . $nome) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if ($erro('estado')): ?>
                        <p class="field__error" id="erro-estado"><?= icon('alert', 'icon', 11) ?> <?= e($erro('estado')) ?></p>
                    <?php endif; ?>
                </div>

                <div class="field field--full">
                    <label for="observacoes">Preferências e observações</label>
                    <textarea id="observacoes" name="observacoes" maxlength="2000"
                              placeholder="Ex.: prefere assentos na janela, alergias alimentares, contato preferencial…"><?= e($valor('observacoes')) ?></textarea>
                    <p class="field__help">Essas informações aparecem na ficha e ajudam a equipe a personalizar o atendimento.</p>
                </div>
            </div>
        </div>

        <div class="form-actions">
            <span class="form-actions__hint"><?= $modo === 'editar' ? 'Última atualização registrada no sistema' : 'CPF e e-mail não podem se repetir' ?></span>
            <a class="button button--secondary" href="<?= $modo === 'editar' ? url('clientes', ['acao' => 'ver', 'id' => $cliente['id']]) : url('clientes') ?>">Cancelar</a>
            <button class="button button--primary" type="submit">
                <?= icon('check', 'icon', 16) ?>
                <span data-button-label><?= $modo === 'editar' ? 'Salvar alterações' : 'Cadastrar cliente' ?></span>
            </button>
        </div>
    </form>

    <aside class="form-aside" aria-label="Orientações">
        <div class="aside-card aside-card--tint">
            <h3><?= icon('user', 'icon', 15) ?> Ficha completa</h3>
            <p>Um cadastro bem preenchido evita retrabalho na emissão de passagens, contratos e notas fiscais.</p>
            <ul>
                <li>Confira o CPF antes de salvar.</li>
                <li>Use o campo de observações para preferências.</li>
                <li>O histórico de reservas é criado automaticamente.</li>
            </ul>
        </div>
        <div class="aside-card">
            <h3><?= icon('lock', 'icon', 15) ?> Dados protegidos</h3>
            <p>CPF e contato são usados apenas nas operações internas da agência e não aparecem em páginas públicas.</p>
        </div>
    </aside>
</div>
