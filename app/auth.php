<?php

declare(strict_types=1);

function auth_user(): ?array
{
    return $_SESSION['usuario'] ?? null;
}

function auth_check(): bool
{
    return isset($_SESSION['usuario']['id']);
}

function auth_attempt(string $email, string $senha): bool
{
    $usuario = db_one(
        'SELECT id, nome, email, senha_hash, perfil FROM usuarios WHERE email = ? AND ativo = 1 LIMIT 1',
        [mb_strtolower(trim($email))]
    );

    if ($usuario === null || !password_verify($senha, $usuario['senha_hash'])) {
        return false;
    }

    session_regenerate_id(true);
    $_SESSION['usuario'] = [
        'id'     => (int) $usuario['id'],
        'nome'   => $usuario['nome'],
        'email'  => $usuario['email'],
        'perfil' => $usuario['perfil'],
    ];

    return true;
}

function auth_logout(): void
{
    unset($_SESSION['usuario']);
    session_regenerate_id(true);
}

/**
 * Atualiza os dados do usuário guardados na sessão.
 */
function auth_atualizar_sessao(array $usuario): void
{
    $_SESSION['usuario'] = [
        'id'     => (int) $usuario['id'],
        'nome'   => $usuario['nome'],
        'email'  => $usuario['email'],
        'perfil' => $usuario['perfil'],
    ];
}

function auth_usuario_atual(): ?array
{
    $id = auth_user()['id'] ?? null;

    if ($id === null) {
        return null;
    }

    return db_one('SELECT * FROM usuarios WHERE id = ? LIMIT 1', [$id]);
}
