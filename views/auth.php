<?php $mensagens = flashes(); ?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0f2725">
    <meta name="description" content="Acesso à mesa de operações da Meridiano Viagens.">
    <title><?= e($pageTitle ?? 'Entrar') ?> · Meridiano Viagens</title>
    <link rel="preload" href="<?= asset('assets/fonts/archivo-latin.woff2') ?>" as="font" type="font/woff2" crossorigin>
    <link rel="stylesheet" href="<?= asset('assets/css/app.css') ?>?v=<?= filemtime(BASE_PATH . '/assets/css/app.css') ?>">
    <link rel="icon" href="<?= asset('assets/img/favicon.svg') ?>" type="image/svg+xml">
</head>
<body class="<?= e($bodyClass ?? 'body--auth') ?>">
<main class="auth">
    <section class="auth__scene" aria-label="Meridiano Viagens">
        <div class="auth__scene-content">
            <a class="brand brand--light" href="<?= url('login') ?>" aria-label="Meridiano Viagens">
                <svg class="brand__mark" viewBox="0 0 40 40" aria-hidden="true">
                    <circle cx="20" cy="20" r="18" fill="none" stroke="currentColor" stroke-width="1.4"/>
                    <ellipse cx="20" cy="20" rx="8.5" ry="18" fill="none" stroke="currentColor" stroke-width="1.1"/>
                    <path d="M2 20h36" fill="none" stroke="currentColor" stroke-width="1.1"/>
                    <circle cx="20" cy="20" r="2.4" fill="currentColor"/>
                </svg>
                <span class="brand__word">
                    <span class="brand__name">meridiano</span>
                    <span class="brand__descriptor">viagens · operações</span>
                </span>
            </a>

            <div class="auth__statement">
                <p class="auth__kicker">Mesa de operações</p>
                <h1>Toda partida começa<br>nos bastidores.</h1>
                <p class="auth__lead">Clientes, partidas, reservas e recebimentos no mesmo lugar — para a agência cuidar do que importa: a próxima viagem.</p>
            </div>

            <div class="auth__board" aria-label="Próxima partida de demonstração">
                <div class="auth__board-route">
                    <span class="auth__board-code">GRU</span>
                    <span class="auth__board-line" aria-hidden="true"></span>
                    <span class="auth__board-plane"><?= icon('plane', 'icon', 18) ?></span>
                    <span class="auth__board-line" aria-hidden="true"></span>
                    <span class="auth__board-code">FEN</span>
                </div>
                <div class="auth__board-meta">
                    <span>São Paulo → Fernando de Noronha</span>
                    <span class="auth__board-status">Confirmada</span>
                </div>
            </div>

            <p class="auth__caption">Fotografia: Fernando de Noronha, Brasil.</p>
        </div>
    </section>

    <section class="auth__panel">
        <div class="auth__panel-inner">
            <a class="brand brand--mobile" href="<?= url('login') ?>" aria-label="Meridiano Viagens">
                <svg class="brand__mark" viewBox="0 0 40 40" aria-hidden="true">
                    <circle cx="20" cy="20" r="18" fill="none" stroke="currentColor" stroke-width="1.4"/>
                    <ellipse cx="20" cy="20" rx="8.5" ry="18" fill="none" stroke="currentColor" stroke-width="1.1"/>
                    <path d="M2 20h36" fill="none" stroke="currentColor" stroke-width="1.1"/>
                    <circle cx="20" cy="20" r="2.4" fill="currentColor"/>
                </svg>
                <span class="brand__word">
                    <span class="brand__name">meridiano</span>
                    <span class="brand__descriptor">viagens · operações</span>
                </span>
            </a>

            <div class="auth__form-head">
                <p class="auth__eyebrow">Acesso da equipe</p>
                <h2>Entre na mesa<br>de operações.</h2>
                <p>Use sua conta da Meridiano para gerenciar as viagens da agência.</p>
            </div>

            <?php if ($mensagens): ?>
                <div class="flash-stack" aria-live="polite">
                    <?php foreach ($mensagens as $mensagem): ?>
                        <div class="flash flash--<?= e($mensagem['tipo']) ?>">
                            <?= icon($mensagem['tipo'] === 'erro' ? 'alert' : 'check', 'flash__icon icon', 18) ?>
                            <p><?= e($mensagem['mensagem']) ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form class="form auth__form" method="post" action="<?= url('login') ?>" novalidate>
                <?= csrf_field() ?>
                <div class="field">
                    <label for="email">E-mail</label>
                    <div class="input-wrap">
                        <?= icon('mail', 'input-icon') ?>
                        <input type="email" id="email" name="email" inputmode="email" spellcheck="false"
                               autocomplete="username" placeholder="voce@meridiano.tur.br" required autofocus>
                    </div>
                </div>

                <div class="field">
                    <div class="field__label-row">
                        <label for="senha">Senha</label>
                        <span class="field__hint">Acesso restrito à equipe</span>
                    </div>
                    <div class="input-wrap">
                        <?= icon('lock', 'input-icon') ?>
                        <input type="password" id="senha" name="senha" autocomplete="current-password"
                               placeholder="Sua senha" required>
                        <button class="input-action" type="button" data-toggle-password aria-label="Mostrar senha" aria-pressed="false">
                            <?= icon('eye', 'icon', 18) ?>
                        </button>
                    </div>
                </div>

                <button class="button button--primary button--full button--lg" type="submit">
                    Entrar na plataforma
                    <?= icon('arrow-right', 'icon', 18) ?>
                </button>
            </form>

            <aside class="demo-access" aria-label="Credenciais de demonstração">
                <div class="demo-access__head">
                    <?= icon('info', 'icon', 16) ?>
                    <strong>Acesso de demonstração</strong>
                </div>
                <dl>
                    <div><dt>E-mail</dt><dd>admin@meridiano.tur.br</dd></div>
                    <div><dt>Senha</dt><dd>meridiano123</dd></div>
                </dl>
            </aside>

            <p class="auth__foot">Meridiano Viagens · plataforma interna de operações</p>
        </div>
    </section>
</main>

<script src="<?= asset('assets/js/app.js') ?>?v=<?= filemtime(BASE_PATH . '/assets/js/app.js') ?>" defer></script>
</body>
</html>
