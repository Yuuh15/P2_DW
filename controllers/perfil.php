<?php

declare(strict_types=1);

$usuario = auth_usuario_atual();

if ($usuario === null) {
    redirect('login');
}

$errosConta = [];
$errosSenha = [];
$dadosConta = [
    'nome'  => $usuario['nome'],
    'email' => $usuario['email'],
];

if (is_post()) {
    verify_csrf();
    $formulario = (string) input('form');

    if ($formulario === 'conta') {
        $dadosConta = [
            'nome'  => trim((string) input('nome')),
            'email' => mb_strtolower(trim((string) input('email'))),
        ];

        if (mb_strlen($dadosConta['nome']) < 3 || mb_strlen($dadosConta['nome']) > 120) {
            $errosConta['nome'] = 'Informe um nome entre 3 e 120 caracteres.';
        }

        if (!email_valido($dadosConta['email'])) {
            $errosConta['email'] = 'Informe um e-mail válido.';
        } elseif (db_value(
            'SELECT id FROM usuarios WHERE email = ? AND id <> ? LIMIT 1',
            [$dadosConta['email'], (int) $usuario['id']]
        )) {
            $errosConta['email'] = 'Este e-mail já está em uso por outra conta.';
        }

        if (!$errosConta) {
            db_execute(
                'UPDATE usuarios SET nome = ?, email = ? WHERE id = ?',
                [$dadosConta['nome'], $dadosConta['email'], (int) $usuario['id']]
            );
            $atualizado = db_one('SELECT * FROM usuarios WHERE id = ? LIMIT 1', [(int) $usuario['id']]);
            if ($atualizado !== null) {
                auth_atualizar_sessao($atualizado);
            }
            redirect_com_flash('perfil', 'sucesso', 'Dados da conta atualizados.');
        }

        http_response_code(422);
    }

    if ($formulario === 'senha') {
        $atual = (string) ($_POST['senha_atual'] ?? '');
        $nova = (string) ($_POST['nova_senha'] ?? '');
        $confirmacao = (string) ($_POST['confirmar_senha'] ?? '');

        if ($atual === '' || !password_verify($atual, $usuario['senha_hash'])) {
            $errosSenha['senha_atual'] = 'A senha atual não confere.';
        }

        if (mb_strlen($nova) < 8) {
            $errosSenha['nova_senha'] = 'A nova senha deve ter pelo menos 8 caracteres.';
        } elseif ($nova !== $confirmacao) {
            $errosSenha['confirmar_senha'] = 'A confirmação não corresponde à nova senha.';
        } elseif ($atual !== '' && $nova === $atual) {
            $errosSenha['nova_senha'] = 'Escolha uma senha diferente da atual.';
        }

        if (!$errosSenha) {
            db_execute(
                'UPDATE usuarios SET senha_hash = ? WHERE id = ?',
                [password_hash($nova, PASSWORD_DEFAULT), (int) $usuario['id']]
            );
            redirect_com_flash('perfil', 'sucesso', 'Senha alterada. Use a nova senha no próximo acesso.');
        }

        http_response_code(422);
    }
}

render('perfil/index', [
    'pageTitle'    => 'Meu perfil',
    'usuario'      => $usuario,
    'dadosConta'   => $dadosConta,
    'errosConta'   => $errosConta,
    'errosSenha'   => $errosSenha,
]);
