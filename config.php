<?php

/**
 * Configuração central da aplicação.
 *
 * As variáveis de ambiente têm prioridade para facilitar o uso com Docker.
 * Em uma instalação XAMPP comum, os valores padrão (root sem senha) funcionam.
 */
return [
    'app_name' => 'Meridiano Viagens',
    'timezone' => 'America/Sao_Paulo',
    'debug'    => filter_var(getenv('APP_DEBUG') ?: false, FILTER_VALIDATE_BOOL),
    'db' => [
        'host'    => getenv('DB_HOST') ?: '127.0.0.1',
        'name'    => getenv('DB_NAME') ?: 'meridiano',
        'user'    => getenv('DB_USER') ?: 'root',
        'pass'    => getenv('DB_PASS') !== false ? getenv('DB_PASS') : '',
        'charset' => getenv('DB_CHARSET') ?: 'utf8mb4',
    ],
    'uploads' => [
        'destinos' => __DIR__ . '/uploads/destinos',
        'url'      => 'uploads/destinos',
        'max_size' => 4 * 1024 * 1024,
    ],
];
