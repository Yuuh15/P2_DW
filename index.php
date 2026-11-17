<?php

declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';

$rota = isset($_GET['r']) && is_string($_GET['r']) ? $_GET['r'] : 'dashboard';

$rotas = [
    'login'      => __DIR__ . '/controllers/auth.php',
    'logout'     => __DIR__ . '/controllers/auth.php',
    'dashboard'  => __DIR__ . '/controllers/dashboard.php',
    'clientes'   => __DIR__ . '/controllers/clientes.php',
    'destinos'   => __DIR__ . '/controllers/destinos.php',
    'pacotes'    => __DIR__ . '/controllers/pacotes.php',
    'viagens'    => __DIR__ . '/controllers/viagens.php',
    'reservas'   => __DIR__ . '/controllers/reservas.php',
    'pagamentos' => __DIR__ . '/controllers/pagamentos.php',
    'relatorios' => __DIR__ . '/controllers/relatorios.php',
    'perfil'     => __DIR__ . '/controllers/perfil.php',
];

// A tela de login é pública; todas as demais exigem autenticação.
if (!auth_check() && $rota !== 'login') {
    redirect('login');
}

if (auth_check() && $rota === 'login') {
    redirect('dashboard');
}

if (!isset($rotas[$rota])) {
    http_response_code(404);
    render('erros/404');
    exit;
}

require $rotas[$rota];
