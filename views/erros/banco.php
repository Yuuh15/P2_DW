<?php /** @var string|null $detalhe */ ?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0f2725">
    <title>Banco indisponível · Meridiano Viagens</title>
    <link rel="stylesheet" href="<?= asset('assets/css/app.css') ?>">
    <link rel="icon" href="<?= asset('assets/img/favicon.svg') ?>" type="image/svg+xml">
</head>
<body class="body--auth">
<main style="display: grid; place-items: center; min-height: 100vh; padding: 24px;">
    <section class="panel" style="width: 100%; max-width: 580px;" aria-labelledby="erro-banco-titulo">
        <div class="panel__body" style="padding: 42px 34px; text-align: center;">
            <span class="avatar avatar--lg" style="margin: 0 auto 16px; width: 58px; height: 58px; background: var(--danger-soft); color: var(--danger);">
                <?= icon('alert', 'icon', 24) ?>
            </span>
            <p class="auth__eyebrow" style="justify-content: center;">Meridiano · operações</p>
            <h1 id="erro-banco-titulo" style="font-size: 28px; letter-spacing: -0.035em; margin: 10px 0 8px;">Banco de dados indisponível</h1>
            <p style="font-size: 13px; color: var(--ink-muted); line-height: 1.65; max-width: 420px; margin: 0 auto;">
                A plataforma não conseguiu falar com o MySQL/MariaDB. Verifique se o serviço está em execução e se as credenciais do arquivo <code>config.php</code> estão corretas.
            </p>

            <?php if (!empty($detalhe)): ?>
                <pre style="text-align: left; overflow-x: auto; margin: 20px 0 0; padding: 12px 14px; background: var(--paper-deep); border: 1px solid var(--line); border-radius: var(--radius-sm); font-family: var(--font-mono); font-size: 10px; color: var(--danger); white-space: pre-wrap;"><?= e($detalhe) ?></pre>
            <?php endif; ?>

            <div class="button-row" style="justify-content: center; margin-top: 24px;">
                <a class="button button--primary" href="<?= url('login') ?>"><?= icon('arrow-right', 'icon', 15) ?> Tentar novamente</a>
            </div>

            <p style="font-family: var(--font-mono); font-size: 8.5px; color: var(--ink-faint); margin-top: 22px; line-height: 1.7;">
                Docker: <code>docker compose up -d</code> · XAMPP: inicie o MySQL no painel e importe <code>database/schema.sql</code>.
            </p>
        </div>
    </section>
</main>
</body>
</html>
