<?php
$erroConta = static fn (string $campo): ?string => $errosConta[$campo] ?? null;
$erroSenha = static fn (string $campo): ?string => $errosSenha[$campo] ?? null;
?>

<div class="page-head page-head--compact">
    <div class="page-head__copy">
        <h1>Meu perfil</h1>
        <p class="page-head__sub">Atualize seus dados de acesso e mantenha a conta da operação protegida.</p>
    </div>
</div>

<div class="detail-grid">
    <div class="detail-stack">
        <section class="panel">
            <header class="panel__head">
                <h2 class="panel__title">Dados da conta</h2>
            </header>
            <div class="panel__body">
                <?php if ($errosConta): ?>
                    <div class="flash flash--erro" role="alert" style="margin-bottom: 16px;">
                        <?= icon('alert', 'flash__icon icon', 18) ?>
                        <p>Revise os campos destacados para atualizar a conta.</p>
                    </div>
                <?php endif; ?>

                <form class="form" method="post" action="<?= url('perfil') ?>" data-loading>
                    <?= csrf_field() ?>
                    <input type="hidden" name="form" value="conta">

                    <div class="form-grid form-grid--2">
                        <div class="field <?= $erroConta('nome') ? 'field--error' : '' ?>">
                            <label for="nome">Nome completo <span class="required">*</span></label>
                            <input type="text" id="nome" name="nome" value="<?= e($dadosConta['nome']) ?>"
                                   minlength="3" maxlength="120" autocomplete="name" required
                                   <?= $erroConta('nome') ? 'aria-invalid="true" aria-describedby="erro-nome"' : '' ?>>
                            <?php if ($erroConta('nome')): ?>
                                <p class="field__error" id="erro-nome"><?= icon('alert', 'icon', 11) ?> <?= e($erroConta('nome')) ?></p>
                            <?php endif; ?>
                        </div>

                        <div class="field <?= $erroConta('email') ? 'field--error' : '' ?>">
                            <label for="email">E-mail de acesso <span class="required">*</span></label>
                            <input type="email" id="email" name="email" value="<?= e($dadosConta['email']) ?>"
                                   maxlength="160" autocomplete="email" spellcheck="false" required
                                   <?= $erroConta('email') ? 'aria-invalid="true" aria-describedby="erro-email"' : '' ?>>
                            <?php if ($erroConta('email')): ?>
                                <p class="field__error" id="erro-email"><?= icon('alert', 'icon', 11) ?> <?= e($erroConta('email')) ?></p>
                            <?php else: ?>
                                <p class="field__help">Este e-mail é usado para entrar na plataforma.</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="form-actions" style="margin-top: 18px; padding-top: 16px;">
                        <span class="form-actions__hint">Perfil de <?= ($usuario['perfil'] ?? '') === 'admin' ? 'administradora' : 'operador' ?>.</span>
                        <button class="button button--primary" type="submit">
                            <?= icon('check', 'icon', 16) ?>
                            <span data-button-label>Salvar dados</span>
                        </button>
                    </div>
                </form>
            </div>
        </section>

        <section class="panel">
            <header class="panel__head">
                <h2 class="panel__title">Alterar senha</h2>
            </header>
            <div class="panel__body">
                <?php if ($errosSenha): ?>
                    <div class="flash flash--erro" role="alert" style="margin-bottom: 16px;">
                        <?= icon('alert', 'flash__icon icon', 18) ?>
                        <p>Não foi possível alterar a senha. Confira os campos.</p>
                    </div>
                <?php endif; ?>

                <form class="form" method="post" action="<?= url('perfil') ?>" data-loading>
                    <?= csrf_field() ?>
                    <input type="hidden" name="form" value="senha">

                    <div class="form-grid form-grid--2">
                        <div class="field field--full <?= $erroSenha('senha_atual') ? 'field--error' : '' ?>">
                            <label for="senha_atual">Senha atual <span class="required">*</span></label>
                            <div class="input-wrap">
                                <?= icon('lock', 'input-icon') ?>
                                <input type="password" id="senha_atual" name="senha_atual" autocomplete="current-password" required
                                       <?= $erroSenha('senha_atual') ? 'aria-invalid="true" aria-describedby="erro-senha-atual"' : '' ?>>
                                <button class="input-action" type="button" data-toggle-password aria-label="Mostrar senha atual" aria-pressed="false">
                                    <?= icon('eye', 'icon', 18) ?>
                                </button>
                            </div>
                            <?php if ($erroSenha('senha_atual')): ?>
                                <p class="field__error" id="erro-senha-atual"><?= icon('alert', 'icon', 11) ?> <?= e($erroSenha('senha_atual')) ?></p>
                            <?php endif; ?>
                        </div>

                        <div class="field <?= $erroSenha('nova_senha') ? 'field--error' : '' ?>">
                            <label for="nova_senha">Nova senha <span class="required">*</span></label>
                            <div class="input-wrap">
                                <?= icon('lock', 'input-icon') ?>
                                <input type="password" id="nova_senha" name="nova_senha" minlength="8" autocomplete="new-password" required
                                       <?= $erroSenha('nova_senha') ? 'aria-invalid="true" aria-describedby="erro-nova-senha"' : '' ?>>
                                <button class="input-action" type="button" data-toggle-password aria-label="Mostrar nova senha" aria-pressed="false">
                                    <?= icon('eye', 'icon', 18) ?>
                                </button>
                            </div>
                            <?php if ($erroSenha('nova_senha')): ?>
                                <p class="field__error" id="erro-nova-senha"><?= icon('alert', 'icon', 11) ?> <?= e($erroSenha('nova_senha')) ?></p>
                            <?php else: ?>
                                <p class="field__help">Use pelo menos 8 caracteres.</p>
                            <?php endif; ?>
                        </div>

                        <div class="field <?= $erroSenha('confirmar_senha') ? 'field--error' : '' ?>">
                            <label for="confirmar_senha">Confirmar nova senha <span class="required">*</span></label>
                            <div class="input-wrap">
                                <?= icon('lock', 'input-icon') ?>
                                <input type="password" id="confirmar_senha" name="confirmar_senha" minlength="8" autocomplete="new-password" required
                                       <?= $erroSenha('confirmar_senha') ? 'aria-invalid="true" aria-describedby="erro-confirmar-senha"' : '' ?>>
                            </div>
                            <?php if ($erroSenha('confirmar_senha')): ?>
                                <p class="field__error" id="erro-confirmar-senha"><?= icon('alert', 'icon', 11) ?> <?= e($erroSenha('confirmar_senha')) ?></p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="form-actions" style="margin-top: 18px; padding-top: 16px;">
                        <span class="form-actions__hint">Você continuará conectado após a troca.</span>
                        <button class="button button--primary" type="submit">
                            <?= icon('lock', 'icon', 15) ?>
                            <span data-button-label>Alterar senha</span>
                        </button>
                    </div>
                </form>
            </div>
        </section>
    </div>

    <aside class="detail-stack" aria-label="Resumo da conta">
        <section class="panel">
            <div class="panel__body">
                <div style="display: flex; flex-direction: column; align-items: center; text-align: center; gap: 10px; padding: 10px 0 16px;">
                    <span class="avatar avatar--lg" style="width: 68px; height: 68px; font-size: 22px;"><?= e(iniciais($usuario['nome'])) ?></span>
                    <div>
                        <h2 style="font-size: 19px; letter-spacing: -0.025em;"><?= e($usuario['nome']) ?></h2>
                        <p class="muted" style="font-size: 11px; margin-top: 3px;"><?= e($usuario['email']) ?></p>
                    </div>
                    <span class="badge badge--<?= ($usuario['perfil'] ?? '') === 'admin' ? 'accent' : 'neutral' ?>">
                        <?= ($usuario['perfil'] ?? '') === 'admin' ? 'Administradora' : 'Operador' ?>
                    </span>
                </div>

                <dl class="info-list">
                    <div class="info-row">
                        <dt>Conta criada em</dt>
                        <dd><?= e(data_br(substr((string) ($usuario['criado_em'] ?? ''), 0, 10))) ?></dd>
                    </div>
                    <div class="info-row">
                        <dt>Último acesso</dt>
                        <dd>sessão atual</dd>
                    </div>
                    <div class="info-row">
                        <dt>Ambiente</dt>
                        <dd>Mesa de operações</dd>
                    </div>
                </dl>
            </div>
        </section>

        <section class="panel">
            <header class="panel__head">
                <h2 class="panel__title">Segurança da conta</h2>
            </header>
            <div class="panel__body">
                <div class="aside-card aside-card--tint" style="margin: 0;">
                    <h3><?= icon('lock', 'icon', 15) ?> Boas práticas</h3>
                    <ul>
                        <li>Não compartilhe sua senha com a equipe.</li>
                        <li>Use uma senha exclusiva para a plataforma.</li>
                        <li>Saia da conta em computadores compartilhados.</li>
                    </ul>
                </div>
                <form method="post" action="<?= url('logout') ?>" style="margin-top: 14px;">
                    <?= csrf_field() ?>
                    <button class="button button--ghost button--small button--full" type="submit">
                        <?= icon('logout', 'icon', 14) ?> Sair da plataforma
                    </button>
                </form>
            </div>
        </section>
    </aside>
</div>
