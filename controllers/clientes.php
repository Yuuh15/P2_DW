<?php

declare(strict_types=1);

$acao = isset($_GET['acao']) && is_string($_GET['acao']) ? $_GET['acao'] : 'index';
$id = (int) ($_GET['id'] ?? 0);

function cliente_buscar(int $id): ?array
{
    return db_one('SELECT * FROM clientes WHERE id = ? LIMIT 1', [$id]);
}

function cliente_validar(array $dados, int $idAtual = 0): array
{
    $erros = [];

    if (mb_strlen(trim((string) $dados['nome'])) < 3) {
        $erros['nome'] = 'Informe o nome completo do cliente.';
    } elseif (mb_strlen((string) $dados['nome']) > 160) {
        $erros['nome'] = 'O nome deve ter no máximo 160 caracteres.';
    }

    if (!email_valido($dados['email'])) {
        $erros['email'] = 'Informe um e-mail válido.';
    } else {
        $existe = db_value(
            'SELECT id FROM clientes WHERE email = ? AND id <> ? LIMIT 1',
            [mb_strtolower((string) $dados['email']), $idAtual]
        );
        if ($existe) {
            $erros['email'] = 'Já existe um cliente com este e-mail.';
        }
    }

    $telefone = apenas_numeros($dados['telefone'] ?? '');
    if (strlen($telefone) < 10 || strlen($telefone) > 11) {
        $erros['telefone'] = 'Informe um telefone com DDD, com 10 ou 11 dígitos.';
    }

    if (!cpf_valido($dados['cpf'] ?? '')) {
        $erros['cpf'] = 'Informe um CPF válido.';
    } else {
        $cpf = apenas_numeros($dados['cpf']);
        $existe = db_value(
            'SELECT id FROM clientes WHERE cpf = ? AND id <> ? LIMIT 1',
            [$cpf, $idAtual]
        );
        if ($existe) {
            $erros['cpf'] = 'Já existe um cliente com este CPF.';
        }
    }

    if (!empty($dados['data_nascimento'])) {
        if (!data_valida($dados['data_nascimento'])) {
            $erros['data_nascimento'] = 'Informe uma data de nascimento válida.';
        } elseif ($dados['data_nascimento'] > date('Y-m-d')) {
            $erros['data_nascimento'] = 'A data de nascimento não pode estar no futuro.';
        }
    }

    if (mb_strlen((string) ($dados['cidade'] ?? '')) > 100) {
        $erros['cidade'] = 'A cidade deve ter no máximo 100 caracteres.';
    }

    if (($dados['estado'] ?? '') !== '' && !preg_match('/^[A-Z]{2}$/', (string) $dados['estado'])) {
        $erros['estado'] = 'Selecione uma unidade federativa.';
    }

    return $erros;
}

switch ($acao) {
    case 'novo':
    case 'editar':
        $modo = $acao;
        $cliente = $modo === 'editar' ? cliente_buscar($id) : null;

        if ($modo === 'editar' && $cliente === null) {
            abortar(404);
        }

        $dados = $cliente ?? [
            'nome' => '', 'email' => '', 'telefone' => '', 'cpf' => '',
            'data_nascimento' => '', 'cidade' => '', 'estado' => '', 'observacoes' => '',
        ];
        $erros = [];

        if (is_post()) {
            verify_csrf();

            $dados = [
                'nome'            => trim((string) input('nome')),
                'email'           => mb_strtolower(trim((string) input('email'))),
                'telefone'        => apenas_numeros((string) input('telefone')),
                'cpf'             => apenas_numeros((string) input('cpf')),
                'data_nascimento' => trim((string) input('data_nascimento')),
                'cidade'          => trim((string) input('cidade')),
                'estado'          => mb_strtoupper(trim((string) input('estado'))),
                'observacoes'     => trim((string) input('observacoes')),
            ];
            $erros = cliente_validar($dados, $modo === 'editar' ? $id : 0);

            if (!$erros) {
                $parametros = [
                    $dados['nome'],
                    $dados['email'],
                    $dados['telefone'],
                    $dados['cpf'],
                    $dados['data_nascimento'] !== '' ? $dados['data_nascimento'] : null,
                    $dados['cidade'] !== '' ? $dados['cidade'] : null,
                    $dados['estado'] !== '' ? $dados['estado'] : null,
                    $dados['observacoes'] !== '' ? $dados['observacoes'] : null,
                ];

                if ($modo === 'editar') {
                    $parametros[] = $id;
                    db_execute(
                        'UPDATE clientes SET nome = ?, email = ?, telefone = ?, cpf = ?, data_nascimento = ?,
                         cidade = ?, estado = ?, observacoes = ? WHERE id = ?',
                        $parametros
                    );
                    redirect_com_flash('clientes', 'sucesso', 'Cliente atualizado.', ['acao' => 'ver', 'id' => $id]);
                }

                db_execute(
                    'INSERT INTO clientes (nome, email, telefone, cpf, data_nascimento, cidade, estado, observacoes)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
                    $parametros
                );
                $novoId = db_last_id();
                redirect_com_flash('clientes', 'sucesso', 'Cliente cadastrado.', ['acao' => 'ver', 'id' => $novoId]);
            }

            http_response_code(422);
        }

        render('clientes/form', [
            'pageTitle' => $modo === 'editar' ? 'Editar cliente' : 'Novo cliente',
            'modo'      => $modo,
            'dados'     => $dados,
            'erros'     => $erros,
            'cliente'   => $cliente,
        ]);
        exit;

    case 'ver':
        $cliente = cliente_buscar($id);
        if ($cliente === null) {
            abortar(404);
        }

        $reservas = db_all(
            'SELECT * FROM vw_reservas_financeiro WHERE cliente_id = ? ORDER BY data_ida DESC, id DESC',
            [$id]
        );

        $totalPago = 0.0;
        $totalContratado = 0.0;
        $proximas = 0;
        foreach ($reservas as $reserva) {
            if ($reserva['status'] !== 'cancelada') {
                $totalContratado += (float) $reserva['valor_total'];
            }
            $totalPago += (float) $reserva['valor_pago'];
            if ($reserva['data_ida'] >= date('Y-m-d') && $reserva['status'] !== 'cancelada') {
                $proximas++;
            }
        }

        render('clientes/show', [
            'pageTitle'       => $cliente['nome'],
            'cliente'         => $cliente,
            'reservas'        => $reservas,
            'totalPago'       => $totalPago,
            'totalContratado' => $totalContratado,
            'proximas'        => $proximas,
        ]);
        exit;

    case 'excluir':
        if (!is_post()) {
            redirect('clientes');
        }
        verify_csrf();

        $cliente = cliente_buscar($id);
        if ($cliente === null) {
            abortar(404);
        }

        $vinculos = (int) db_value('SELECT COUNT(*) FROM reservas WHERE cliente_id = ?', [$id], 0);

        if ($vinculos > 0) {
            redirect_com_flash(
                'clientes',
                'erro',
                'Não é possível excluir ' . explode(' ', $cliente['nome'])[0] . ': há ' . $vinculos
                . ' reserva(s) vinculada(s). Exclua ou cancele as reservas primeiro.',
                ['acao' => 'ver', 'id' => $id]
            );
        }

        try {
            db_execute('DELETE FROM clientes WHERE id = ?', [$id]);
            redirect_com_flash('clientes', 'sucesso', 'Cliente excluído.');
        } catch (PDOException $exception) {
            error_log('Erro ao excluir cliente: ' . $exception->getMessage());
            redirect_com_flash('clientes', 'erro', 'Não foi possível excluir o cliente agora.', ['acao' => 'ver', 'id' => $id]);
        }
        exit;

    case 'index':
    default:
        $busca = trim((string) query('busca'));
        $where = '';
        $parametros = [];

        if ($busca !== '') {
            $where = 'WHERE (c.nome LIKE ? OR c.email LIKE ? OR c.cpf LIKE ? OR c.cidade LIKE ?)';
            $termo = '%' . $busca . '%';
            $parametros = [$termo, $termo, apenas_numeros($busca) !== '' ? '%' . apenas_numeros($busca) . '%' : $termo, $termo];
        }

        $total = (int) db_value('SELECT COUNT(*) FROM clientes c ' . $where, $parametros, 0);
        $paginacao = paginar($total, 10);

        $clientes = db_all(
            'SELECT c.*,
                    (SELECT COUNT(*) FROM reservas r WHERE r.cliente_id = c.id) AS total_reservas,
                    (SELECT COALESCE(SUM(r.valor_total), 0) FROM reservas r WHERE r.cliente_id = c.id AND r.status <> \'cancelada\') AS total_contratado
             FROM clientes c ' . $where . '
             ORDER BY c.nome ASC
             LIMIT ' . $paginacao['por_pagina'] . ' OFFSET ' . $paginacao['offset'],
            $parametros
        );

        render('clientes/index', [
            'pageTitle'  => 'Clientes',
            'clientes'   => $clientes,
            'busca'      => $busca,
            'paginacao'  => $paginacao,
            'total'      => $total,
        ]);
        exit;
}
