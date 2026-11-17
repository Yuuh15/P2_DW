<?php

declare(strict_types=1);

/**
 * Conexão PDO única com o MySQL/MariaDB.
 */
function db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $config = $GLOBALS['config']['db'];

    $dsn = sprintf(
        'mysql:host=%s;dbname=%s;charset=%s',
        $config['host'],
        $config['name'],
        $config['charset']
    );

    try {
        $pdo = new PDO($dsn, $config['user'], $config['pass'], [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    } catch (PDOException $exception) {
        error_log('Falha na conexão com o banco: ' . $exception->getMessage());
        http_response_code(500);

        $detalhe = !empty($GLOBALS['config']['debug'])
            ? $exception->getMessage()
            : null;

        include VIEWS_PATH . '/erros/banco.php';
        exit;
    }

    return $pdo;
}

/**
 * Atalhos para consultas preparadas.
 */
function db_all(string $sql, array $params = []): array
{
    $stmt = db()->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll();
}

function db_one(string $sql, array $params = []): ?array
{
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    $row = $stmt->fetch();

    return $row === false ? null : $row;
}

function db_value(string $sql, array $params = [], mixed $default = null): mixed
{
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    $value = $stmt->fetchColumn();

    return $value === false ? $default : $value;
}

function db_execute(string $sql, array $params = []): int
{
    $stmt = db()->prepare($sql);
    $stmt->execute($params);

    return $stmt->rowCount();
}

/**
 * Retorna o último ID inserido na conexão atual.
 */
function db_last_id(): int
{
    return (int) db()->lastInsertId();
}
