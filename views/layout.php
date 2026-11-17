<?php

$usuario = auth_user();
$rotaAtual = isset($_GET['r']) && is_string($_GET['r']) ? $_GET['r'] : 'dashboard';
$acaoAtual = isset($_GET['acao']) && is_string($_GET['acao']) ? $_GET['acao'] : '';

/*
 * Evita dois CTAs iguais na mesma tela. O dashboard e as listas de reservas
 * já trazem a ação principal no cabeçalho da página.
 */
$ocultarCtaTopbar = $rotaAtual === 'dashboard'
    || ($rotaAtual === 'reservas' && $acaoAtual !== 'ver');

$secoes = [
    'dashboard'  => 'Visão geral',
    'viagens'    => 'Viagens',
    'reservas'   => 'Reservas',
    'pagamentos' => 'Pagamentos',
    'clientes'   => 'Clientes',
    'destinos'   => 'Destinos',
    'pacotes'    => 'Pacotes',
    'relatorios' => 'Relatórios',
    'perfil'     => 'Meu perfil',
];
$secaoAtual = $secoes[$rotaAtual] ?? 'Mesa de operações';

$vencidas = (int) db_value(
    "SELECT COUNT(*) FROM pagamentos WHERE status = 'pendente' AND vencimento < CURDATE()"
);

$navPrincipal = [
    ['rota' => 'dashboard', 'label' => 'Visão geral', 'icone' => 'grid'],
    ['rota' => 'viagens', 'label' => 'Viagens', 'icone' => 'plane'],
    ['rota' => 'reservas', 'label' => 'Reservas', 'icone' => 'ticket'],
    ['rota' => 'pagamentos', 'label' => 'Pagamentos', 'icone' => 'banknote', 'badge' => $vencidas],
];
$navCadastros = [
    ['rota' => 'clientes', 'label' => 'Clientes', 'icone' => 'users'],
    ['rota' => 'destinos', 'label' => 'Destinos', 'icone' => 'map-pin'],
    ['rota' => 'pacotes', 'label' => 'Pacotes', 'icone' => 'package'],
];
$navGestao = [
    ['rota' => 'relatorios', 'label' => 'Relatórios', 'icone' => 'chart'],
];
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0f2725">
    <meta name="description" content="Meridiano Viagens — plataforma interna de operações, reservas e pagamentos.">
    <title><?= e($pageTitle ?? $secaoAtual) ?> · Meridiano Viagens</title>
    <script>
        (() => {
            try {
                const escolha = localStorage.getItem('meridiano-theme');
                const escuro = escolha === 'dark'
                    || (escolha !== 'light' && window.matchMedia('(prefers-color-scheme: dark)').matches);
                if (escuro) document.documentElement.dataset.theme = 'dark';
            } catch (erro) {
                /* Mantém o tema claro quando o armazenamento local não está disponível. */
            }
        })();
    </script>
    <link rel="preload" href="<?= asset('assets/fonts/archivo-latin.woff2') ?>" as="font" type="font/woff2" crossorigin>
    <link rel="preload" href="<?= asset('assets/fonts/plexmono-400.woff2') ?>" as="font" type="font/woff2" crossorigin>
    <link rel="stylesheet" href="<?= asset('assets/css/app.css') ?>?v=<?= filemtime(BASE_PATH . '/assets/css/app.css') ?>">
    <link rel="icon" href="<?= asset('assets/img/favicon.svg') ?>" type="image/svg+xml">
</head>
<body class="<?= e($bodyClass ?? '') ?>">
<a class="skip-link" href="#conteudo">Pular para o conteúdo</a>

<div class="app-shell">
    <aside class="sidebar" id="sidebar" aria-label="Navegação principal">
        <a class="brand" href="<?= url('dashboard') ?>" aria-label="Meridiano Viagens — ir para a visão geral">
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

        <div class="sidebar__context">
            <span class="status-dot" aria-hidden="true"></span>
            <span>Mesa de operações</span>
            <span class="sidebar__context-date"><?= e(data_curta(date('Y-m-d'))) ?></span>
        </div>

        <nav class="nav">
            <p class="nav__label">Operação</p>
            <?php foreach ($navPrincipal as $item): ?>
                <a class="nav__link <?= $rotaAtual === $item['rota'] ? 'is-active' : '' ?>"
                   href="<?= url($item['rota']) ?>"
                   <?= $rotaAtual === $item['rota'] ? 'aria-current="page"' : '' ?>>
                    <?= icon($item['icone'], 'nav__icon') ?>
                    <span><?= e($item['label']) ?></span>
                    <?php if (!empty($item['badge'])): ?>
                        <span class="nav__badge" aria-label="<?= (int) $item['badge'] ?> pagamentos vencidos"><?= (int) $item['badge'] ?></span>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>

            <p class="nav__label">Cadastros</p>
            <?php foreach ($navCadastros as $item): ?>
                <a class="nav__link <?= $rotaAtual === $item['rota'] ? 'is-active' : '' ?>"
                   href="<?= url($item['rota']) ?>"
                   <?= $rotaAtual === $item['rota'] ? 'aria-current="page"' : '' ?>>
                    <?= icon($item['icone'], 'nav__icon') ?>
                    <span><?= e($item['label']) ?></span>
                </a>
            <?php endforeach; ?>

            <p class="nav__label">Gestão</p>
            <?php foreach ($navGestao as $item): ?>
                <a class="nav__link <?= $rotaAtual === $item['rota'] ? 'is-active' : '' ?>"
                   href="<?= url($item['rota']) ?>"
                   <?= $rotaAtual === $item['rota'] ? 'aria-current="page"' : '' ?>>
                    <?= icon($item['icone'], 'nav__icon') ?>
                    <span><?= e($item['label']) ?></span>
                </a>
            <?php endforeach; ?>
        </nav>

        <div class="sidebar__footer">
            <div class="sidebar-user">
                <a class="sidebar-user__link" href="<?= url('perfil') ?>">
                    <span class="avatar"><?= e(iniciais($usuario['nome'] ?? '')) ?></span>
                    <span class="sidebar-user__info">
                        <strong><?= e($usuario['nome'] ?? '') ?></strong>
                        <small><?= ($usuario['perfil'] ?? '') === 'admin' ? 'Administradora' : 'Operador' ?></small>
                    </span>
                </a>
                <form method="post" action="<?= url('logout') ?>">
                    <?= csrf_field() ?>
                    <button class="icon-button icon-button--quiet" type="submit" aria-label="Sair da plataforma" title="Sair">
                        <?= icon('logout', 'icon', 18) ?>
                    </button>
                </form>
            </div>
        </div>
    </aside>

    <div class="sidebar-backdrop" data-sidebar-close hidden></div>

    <div class="app-frame">
        <header class="topbar">
            <button class="icon-button topbar__menu" type="button" data-sidebar-toggle
                    aria-controls="sidebar" aria-expanded="false" aria-label="Abrir menu de navegação">
                <?= icon('menu', 'icon', 22) ?>
            </button>
            <p class="topbar__context">
                <span>Meridiano</span>
                <span class="topbar__slash" aria-hidden="true">/</span>
                <span class="topbar__current"><?= e($secaoAtual) ?></span>
            </p>
            <div class="topbar__actions">
                <button class="icon-button topbar__theme" type="button" data-theme-toggle
                        data-tooltip="Ativar modo escuro" aria-label="Ativar modo escuro" aria-pressed="false">
                    <span class="topbar__theme-icon topbar__theme-icon--moon"><?= icon('moon', 'icon', 17) ?></span>
                    <span class="topbar__theme-icon topbar__theme-icon--sun"><?= icon('sun', 'icon', 17) ?></span>
                </button>
                <time class="topbar__date" datetime="<?= e(date('Y-m-d')) ?>"><?= e(data_extenso()) ?></time>
                <?php if (!$ocultarCtaTopbar): ?>
                    <a class="button button--primary button--small topbar__cta" href="<?= url('reservas', ['acao' => 'novo']) ?>">
                        <?= icon('plus', 'icon', 16) ?>
                        <span>Nova reserva</span>
                    </a>
                <?php endif; ?>
            </div>
        </header>

        <main class="main" id="conteudo">
            <?php $mensagens = flashes(); ?>
            <?php if ($mensagens): ?>
                <div class="flash-stack" aria-live="polite">
                    <?php foreach ($mensagens as $mensagem): ?>
                        <div class="flash flash--<?= e($mensagem['tipo']) ?>">
                            <?= icon($mensagem['tipo'] === 'erro' ? 'alert' : 'check', 'flash__icon icon', 18) ?>
                            <p><?= e($mensagem['mensagem']) ?></p>
                            <button type="button" class="flash__close" data-dismiss-flash aria-label="Fechar aviso">
                                <?= icon('x', 'icon', 16) ?>
                            </button>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?= $conteudo ?>
        </main>

        <footer class="app-footer">
            <p>Meridiano Viagens · plataforma interna de operações</p>
            <p><span class="status-dot status-dot--small" aria-hidden="true"></span> Ambiente de demonstração</p>
        </footer>
    </div>
</div>

<dialog class="confirm-dialog" id="confirm-dialog" aria-labelledby="confirm-title">
    <div class="confirm-dialog__body">
        <span class="confirm-dialog__mark" aria-hidden="true"><?= icon('alert', 'icon', 24) ?></span>
        <div>
            <h2 id="confirm-title">Confirmar exclusão</h2>
            <p id="confirm-message">Esta ação não pode ser desfeita.</p>
        </div>
    </div>
    <div class="confirm-dialog__actions">
        <button type="button" class="button button--ghost" data-confirm-cancel>Manter registro</button>
        <button type="button" class="button button--danger" data-confirm-accept>Excluir</button>
    </div>
</dialog>

<script src="<?= asset('assets/js/app.js') ?>?v=<?= filemtime(BASE_PATH . '/assets/js/app.js') ?>" defer></script>
</body>
</html>
