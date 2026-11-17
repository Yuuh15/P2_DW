<?php

declare(strict_types=1);

$acao = isset($_GET['acao']) && is_string($_GET['acao']) ? $_GET['acao'] : 'index';
$id = (int) ($_GET['id'] ?? 0);

function pagamento_buscar(int $id): ?array
{
    return db_one(
        'SELECT pg.*, r.codigo AS reserva_codigo, r.cliente_id, r.viagem_id, r.valor_total,
                c.nome AS cliente_nome, d.nome AS destino_nome, p.titulo AS pacote_titulo
         FROM pagamentos pg
         INNER JOIN reservas r ON r.id = pg.reserva_id
         INNER JOIN clientes c ON c.id = r.cliente_id
         INNER JOIN viagens v ON v.id = r.viagem_id
         INNER JOIN pacotes p ON p.id = v.pacote_id
         INNER JOIN destinos d ON d.id = p.destino_id
         WHERE pg.id = ?
         LIMIT 1',
        [$id]
    );
}

/**
 * Mantém a reserva confirmada quando a soma dos pagamentos recebidos
 * alcança o valor total contratado.
 */
function pagamento_sincronizar_reserva(int $reservaId): void
{
    $total = (float) db_value('SELECT valor_total FROM reservas WHERE id = ? LIMIT 1', [$reservaId], 0);
    $pago = (float) db_value(
        "SELECT COALESCE(SUM(valor), 0) FROM pagamentos WHERE reserva_id = ? AND status = 'pago'",
        [$reservaId],
        0
    );

    if ($total > 0 && $pago >= $total) {
        db_execute(
            "UPDATE reservas SET status = 'confirmada' WHERE id = ? AND status = 'pendente'",
            [$reservaId]
        );
    }
}

function pagamento_disponivel(int $reservaId, ?int $ignorarPagamentoId = null): float
{
    $total = (float) db_value('SELECT valor_total FROM reservas WHERE id = ? LIMIT 1', [$reservaId], 0);

    $sql = "SELECT COALESCE(SUM(valor), 0) FROM pagamentos
            WHERE reserva_id = ? AND status IN ('pago', 'pendente')";
    $parametros = [$reservaId];

    if ($ignorarPagamentoId !== null) {
        $sql .= ' AND id <> ?';
        $parametros[] = $ignorarPagamentoId;
    }

    $comprometido = (float) db_value($sql, $parametros, 0);

    return max(0, round($total - $comprometido, 2));
}

switch ($acao) {
    case 'novo':
    case 'editar':
        $modo = $acao;
        $pagamento = $modo === 'editar' ? pagamento_buscar($id) : null;

        if ($modo === 'editar' && $pagamento === null) {
            abortar(404);
        }

        $reservaPreSelecionada = (int) ($_GET['reserva_id'] ?? 0);
        $reservaAtualId = $modo === 'editar' ? (int) $pagamento['reserva_id'] : $reservaPreSelecionada;

        $reservas = db_all(
            "SELECT vw.id, vw.codigo, vw.cliente_nome, vw.destino_nome, vw.data_ida,
                    vw.valor_total, vw.valor_pago, vw.valor_a_pagar, r.status,
                    GREATEST(vw.valor_total - vw.valor_pago - vw.valor_a_pagar, 0) AS disponivel
             FROM vw_reservas_financeiro vw
             INNER JOIN reservas r ON r.id = vw.id
             WHERE vw.status <> 'cancelada' OR vw.id = ?
             ORDER BY CASE WHEN vw.status = 'cancelada' THEN 1 ELSE 0 END,
                      vw.data_ida ASC, vw.codigo ASC",
            [$reservaAtualId]
        );

        if (!$reservas) {
            redirect_com_flash('reservas', 'info', 'Registre uma reserva antes de lançar pagamentos.');
        }

        $dados = $pagamento ?? [
            'reserva_id'     => $reservaPreSelecionada,
            'valor'          => '',
            'forma'          => 'pix',
            'status'         => 'pago',
            'vencimento'     => '',
            'data_pagamento' => date('Y-m-d'),
            'observacoes'    => '',
        ];
        $erros = [];
        $disponivel = $reservaAtualId > 0
            ? pagamento_disponivel($reservaAtualId, $modo === 'editar' ? $id : null)
            : 0.0;

        if (is_post()) {
            verify_csrf();

            $dados = [
                'reserva_id'     => (int) input('reserva_id'),
                'valor'          => str_replace(',', '.', (string) input('valor')),
                'forma'          => (string) input('forma', 'pix'),
                'status'         => (string) input('status', 'pago'),
                'vencimento'     => (string) input('vencimento'),
                'data_pagamento' => (string) input('data_pagamento'),
                'observacoes'    => trim((string) input('observacoes')),
            ];

            $reserva = db_one(
                'SELECT r.id, r.codigo, r.valor_total, r.status FROM reservas r WHERE r.id = ? LIMIT 1',
                [$dados['reserva_id']]
            );

            if ($reserva === null) {
                $erros['reserva_id'] = 'Selecione uma reserva válida.';
            } elseif ($reserva['status'] === 'cancelada' && $modo === 'novo') {
                $erros['reserva_id'] = 'Reservas canceladas não recebem novos pagamentos.';
            }

            if (!is_numeric($dados['valor']) || (float) $dados['valor'] <= 0) {
                $erros['valor'] = 'Informe um valor maior que zero.';
            }

            if (!in_array($dados['forma'], ['pix', 'cartao', 'boleto', 'transferencia'], true)) {
                $erros['forma'] = 'Selecione uma forma de pagamento válida.';
            }

            if (!in_array($dados['status'], ['pendente', 'pago', 'estornado'], true)) {
                $erros['status'] = 'Selecione uma situação válida.';
            }

            if ($dados['vencimento'] !== '' && !data_valida($dados['vencimento'])) {
                $erros['vencimento'] = 'Informe um vencimento válido.';
            }

            if ($dados['data_pagamento'] !== '' && !data_valida($dados['data_pagamento'])) {
                $erros['data_pagamento'] = 'Informe uma data de pagamento válida.';
            }

            if ($dados['status'] === 'pago' && $dados['data_pagamento'] === '') {
                $erros['data_pagamento'] = 'Pagamentos recebidos precisam da data de pagamento.';
            }

            if ($dados['status'] === 'pendente' && $dados['vencimento'] === '') {
                $erros['vencimento'] = 'Informe o vencimento da parcela pendente.';
            }

            if (in_array($dados['status'], ['pago', 'pendente'], true)
                && $reserva !== null && is_numeric($dados['valor'])
                && !isset($erros['valor'])) {
                $disponivel = pagamento_disponivel($dados['reserva_id'], $modo === 'editar' ? $id : null);

                if ((float) $dados['valor'] > $disponivel) {
                    $erros['valor'] = 'O valor excede o saldo disponível de ' . money($disponivel)
                        . ' na reserva ' . $reserva['codigo'] . '.';
                }
            }

            if (mb_strlen($dados['observacoes']) > 255) {
                $erros['observacoes'] = 'A observação deve ter no máximo 255 caracteres.';
            }

            if (!$erros) {
                $vencimento = $dados['vencimento'] !== '' ? $dados['vencimento'] : null;
                $dataPagamento = $dados['status'] === 'pago' && $dados['data_pagamento'] !== ''
                    ? $dados['data_pagamento']
                    : null;

                $parametros = [
                    $dados['reserva_id'],
                    round((float) $dados['valor'], 2),
                    $dados['forma'],
                    $dados['status'],
                    $vencimento,
                    $dataPagamento,
                    $dados['observacoes'] !== '' ? $dados['observacoes'] : null,
                ];

                if ($modo === 'editar') {
                    $parametros[] = $id;
                    db_execute(
                        'UPDATE pagamentos SET reserva_id = ?, valor = ?, forma = ?, status = ?,
                         vencimento = ?, data_pagamento = ?, observacoes = ? WHERE id = ?',
                        $parametros
                    );
                    pagamento_sincronizar_reserva((int) $dados['reserva_id']);
                    redirect_com_flash(
                        'reservas',
                        'sucesso',
                        'Pagamento atualizado. Saldo da reserva recalculado.',
                        ['acao' => 'ver', 'id' => $dados['reserva_id']]
                    );
                }

                db_execute(
                    'INSERT INTO pagamentos (reserva_id, valor, forma, status, vencimento, data_pagamento, observacoes)
                     VALUES (?, ?, ?, ?, ?, ?, ?)',
                    $parametros
                );
                pagamento_sincronizar_reserva((int) $dados['reserva_id']);
                redirect_com_flash(
                    'reservas',
                    'sucesso',
                    'Pagamento registrado na reserva. O financeiro foi atualizado.',
                    ['acao' => 'ver', 'id' => $dados['reserva_id']]
                );
            }

            http_response_code(422);
        }

        if ($reservaAtualId > 0) {
            $disponivel = pagamento_disponivel($reservaAtualId, $modo === 'editar' ? $id : null);
        }

        if (is_post() && (int) $dados['reserva_id'] > 0) {
            $disponivel = pagamento_disponivel((int) $dados['reserva_id'], $modo === 'editar' ? $id : null);
        }

        render('pagamentos/form', [
            'pageTitle'  => $modo === 'editar' ? 'Editar pagamento' : 'Novo pagamento',
            'modo'       => $modo,
            'dados'      => $dados,
            'erros'      => $erros,
            'pagamento'  => $pagamento,
            'reservas'   => $reservas,
            'disponivel' => $disponivel,
        ]);
        exit;

    case 'pagar':
        if (!is_post()) {
            redirect('pagamentos');
        }
        verify_csrf();

        $pagamento = pagamento_buscar($id);
        if ($pagamento === null) {
            abortar(404);
        }

        if ($pagamento['status'] !== 'pendente') {
            redirect_com_flash('pagamentos', 'info', 'Este lançamento não está pendente.');
        }

        db_execute(
            "UPDATE pagamentos SET status = 'pago', data_pagamento = CURDATE() WHERE id = ?",
            [$id]
        );
        pagamento_sincronizar_reserva((int) $pagamento['reserva_id']);
        redirect_com_flash('pagamentos', 'sucesso', 'Pagamento marcado como recebido.');
        exit;

    case 'excluir':
        if (!is_post()) {
            redirect('pagamentos');
        }
        verify_csrf();

        $pagamento = pagamento_buscar($id);
        if ($pagamento === null) {
            abortar(404);
        }

        if ($pagamento['status'] === 'pago') {
            redirect_com_flash(
                'pagamentos',
                'erro',
                'Não é possível excluir um pagamento recebido. Edite o lançamento e registre o estorno.'
            );
        }

        try {
            db_execute('DELETE FROM pagamentos WHERE id = ?', [$id]);
            pagamento_sincronizar_reserva((int) $pagamento['reserva_id']);
            redirect_com_flash('pagamentos', 'sucesso', 'Lançamento removido.');
        } catch (PDOException $exception) {
            error_log('Erro ao excluir pagamento: ' . $exception->getMessage());
            redirect_com_flash('pagamentos', 'erro', 'Não foi possível excluir o pagamento agora.');
        }
        exit;

    case 'index':
    default:
        $busca = trim((string) query('busca'));
        $statusFiltro = trim((string) query('status'));
        $formaFiltro = trim((string) query('forma'));
        $viagemFiltro = (int) query('viagem_id');
        $situacao = trim((string) query('situacao'));

        $from = ' FROM pagamentos pg
                  INNER JOIN reservas r ON r.id = pg.reserva_id
                  INNER JOIN clientes c ON c.id = r.cliente_id
                  INNER JOIN viagens v ON v.id = r.viagem_id
                  INNER JOIN pacotes p ON p.id = v.pacote_id
                  INNER JOIN destinos d ON d.id = p.destino_id';

        $condicoes = [];
        $parametros = [];

        if ($busca !== '') {
            $condicoes[] = '(r.codigo LIKE ? OR c.nome LIKE ? OR d.nome LIKE ? OR p.titulo LIKE ?)';
            $termo = '%' . $busca . '%';
            array_push($parametros, $termo, $termo, $termo, $termo);
        }

        if (in_array($statusFiltro, ['pendente', 'pago', 'estornado'], true)) {
            $condicoes[] = 'pg.status = ?';
            $parametros[] = $statusFiltro;
        } else {
            $statusFiltro = '';
        }

        if (in_array($formaFiltro, ['pix', 'cartao', 'boleto', 'transferencia'], true)) {
            $condicoes[] = 'pg.forma = ?';
            $parametros[] = $formaFiltro;
        } else {
            $formaFiltro = '';
        }

        if ($viagemFiltro > 0) {
            $condicoes[] = 'r.viagem_id = ?';
            $parametros[] = $viagemFiltro;
        }

        if ($situacao === 'vencidos') {
            $condicoes[] = "pg.status = 'pendente' AND pg.vencimento < CURDATE()";
        } elseif ($situacao === 'avencer') {
            $condicoes[] = "pg.status = 'pendente' AND pg.vencimento BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)";
        } else {
            $situacao = '';
        }

        $where = $condicoes ? ' WHERE ' . implode(' AND ', $condicoes) : '';

        $total = (int) db_value('SELECT COUNT(*)' . $from . $where, $parametros, 0);
        $paginacao = paginar($total, 12);

        $pagamentos = db_all(
            'SELECT pg.*, r.codigo AS reserva_codigo, r.id AS reserva_id, c.nome AS cliente_nome,
                    d.nome AS destino_nome, p.titulo AS pacote_titulo, v.data_ida, r.status AS reserva_status,
                    CASE WHEN pg.status = \'pendente\' AND pg.vencimento < CURDATE() THEN 1 ELSE 0 END AS vencido
             ' . $from . $where . '
             ORDER BY CASE
                        WHEN pg.status = \'pendente\' AND pg.vencimento < CURDATE() THEN 0
                        WHEN pg.status = \'pendente\' THEN 1
                        WHEN pg.status = \'pago\' THEN 2
                        ELSE 3
                      END,
                      COALESCE(pg.vencimento, pg.data_pagamento) ASC, pg.id DESC
             LIMIT ' . $paginacao['por_pagina'] . ' OFFSET ' . $paginacao['offset'],
            $parametros
        );

        $resumo = db_one(
            "SELECT COUNT(*) AS total,
                    COALESCE(SUM(pg.valor), 0) AS lancado,
                    COALESCE(SUM(CASE WHEN pg.status = 'pago' THEN pg.valor ELSE 0 END), 0) AS recebido,
                    COALESCE(SUM(CASE WHEN pg.status = 'pendente' THEN pg.valor ELSE 0 END), 0) AS pendente,
                    COALESCE(SUM(CASE WHEN pg.status = 'pendente' AND pg.vencimento < CURDATE() THEN pg.valor ELSE 0 END), 0) AS vencido
             " . $from . $where,
            $parametros
        ) ?? ['total' => 0, 'lancado' => 0, 'recebido' => 0, 'pendente' => 0, 'vencido' => 0];

        $viagemFiltroInfo = $viagemFiltro > 0
            ? db_one(
                'SELECT id, destino_nome, data_ida, pacote_titulo FROM vw_viagens_resumo WHERE id = ? LIMIT 1',
                [$viagemFiltro]
            )
            : null;

        $temFiltros = $busca !== '' || $statusFiltro !== '' || $formaFiltro !== '' || $viagemFiltro > 0 || $situacao !== '';

        render('pagamentos/index', [
            'pageTitle'        => 'Pagamentos',
            'pagamentos'       => $pagamentos,
            'busca'            => $busca,
            'statusFiltro'     => $statusFiltro,
            'formaFiltro'      => $formaFiltro,
            'viagemFiltro'     => $viagemFiltro,
            'viagemFiltroInfo' => $viagemFiltroInfo,
            'situacao'         => $situacao,
            'paginacao'        => $paginacao,
            'total'            => $total,
            'resumo'           => $resumo,
            'temFiltros'       => $temFiltros,
        ]);
        exit;
}
