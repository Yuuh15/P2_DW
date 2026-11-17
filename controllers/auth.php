<?php

declare(strict_types=1);

$rota = $_GET['r'] ?? 'login';

if ($rota === 'logout') {
    if (!is_post()) {
        redirect('dashboard');
    }

    verify_csrf();
    auth_logout();
    flash('sucesso', 'Você saiu da plataforma. Até logo.');
    redirect('login');
}

if (is_post()) {
    verify_csrf();

    $email = mb_strtolower(trim((string) input('email')));
    $senha = (string) ($_POST['senha'] ?? '');

    if ($email === '' || $senha === '') {
        flash('erro', 'Informe seu e-mail e sua senha para entrar.');
        redirect('login');
    }

    if (!auth_attempt($email, $senha)) {
        flash('erro', 'E-mail ou senha incorretos. Confira os dados e tente novamente.');
        redirect('login');
    }

    flash('sucesso', 'Bem-vindo de volta, ' . explode(' ', (string) auth_user()['nome'])[0] . '.');
    redirect('dashboard');
}

render('auth/login', [
    'pageTitle' => 'Entrar na plataforma',
    'bodyClass' => 'body--auth',
], 'auth');
