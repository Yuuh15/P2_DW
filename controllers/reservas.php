<?php

declare(strict_types=1);

$acao = isset($_GET['acao']) && is_string($_GET['acao']) ? $_GET['acao'] : 'index';
$id = (int) ($_GET['id'] ?? 0);

function reserva_buscar(int $id): ?array
{
    return db_one(
        'SELECT vw.*, p.descricao AS pacote_descricao, p.inclusos, p.duracao_dias,
                d.id AS destino_id, d.regiao AS destino_regiao
         FROM vw_reservas_financeiro vw
         INNER JOIN pacotes p ON p.id = (SELECT viagem.pacote_id FROM viagens viagem WHERE viagem.id = vw.viagem_id)
         INNER JOIN destinos d ON d.id = p.destino_id
         WHERE vw.id = ?
         LIMIT 1',
        [$id]
    );
}

function reserva_gerar_codigo(): string
{
    $ano = date('Y');
    $ultimo = (int) db_value(
        'SELECT COALESCE(MAX(CAST(SUBSTRING(codigo, 10) AS UNSIGNED)), 0)
         FROM reservas WHERE codigo LIKE ?',
        ['MRD-' . $ano . '-%'],
        0
    );

    return sprintf('MRD-%s-%04d', $ano, $ultimo + 1);
}

function reserva_lugares_ocupados(int $viagemId, ?int $ignorarReservaId = null): int
{
    $sql = "SELECT COALESCE(SUM(viajantes), 0) FROM reservas
            WHERE viagem_id = ? AND status IN ('pendente', 'confirmada')";
    $parametros = [$viagemId];

    if ($ignorarReservaId !== null) {
        $sql .= ' AND id <> ?';
        $parametros[] = $ignorarReservaId;
    }

    return (int) db_value($sql, $parametros, 0);
}

function reserva_pago(int $id): float
{
    return (float) db_value(
        "SELECT COALESCE(SUM(valor), 0) FROM pagamentos WHERE reserva_id = ? AND status = 'pago'",
        [$id],
        0
    );
}

switch ($acao) {
    case 'novo':
    case 'editar':
        $modo = $acao;
        $reserva = $modo === 'editar' ? reserva_buscar($id) : null;

        if ($modo === 'editar' && $reserva === null) {
            abortar(404);
        }

        $clientes = db_all('SELECT id, nome, cpf, cidade, estado FROM clientes ORDER BY nome ASC');
        if (!$clientes) {
            redirect_com_flash('clientes', 'info', 'Cadastre um cliente antes de registrar uma reserva.');
        }

        $viagens = db_all(
            'SELECT vw.*, GREATEST(vw.capacidade - vw.lugares_ocupados, 0) AS lugares_livres
             FROM vw_viagens_resumo vw
             WHERE vw.status <> \'cancelada\' OR vw.id = ?
             ORDER BY CASE WHEN vw.data_ida >= CURDATE() THEN 0 ELSE 1 END,
                      CASE WHEN vw.data_ida >= CURDATE() THEN vw.data_ida END ASC,
                      vw.data_ida DESC',
            [$reserva['viagem_id'] ?? 0]
        );

        if (!$viagens) {
            redirect_com_flash('viagens', 'info', 'Crie uma partida antes de registrar uma reserva.');
        }

        $clientePreSelecionado = (int) ($_GET['cliente_id'] ?? 0);
        $viagemPreSelecionada = (int) ($_GET['viagem_id'] ?? 0);

        $dados = $reserva ?? [
            'cliente_id'   => $clientePreSelecionado,
            'viagem_id'    => $viagemPreSelecionada,
            'data_reserva' => date('Y-m-d'),
            'viajantes'    => 1,
            'valor_total'  => '',
            'status'       => 'pendente',
            'observacoes'  => '',
        ];
        $erros = [];
        $pagoAtual = $modo === 'editar' ? reserva_pago($id) : 0.0;

        if (is_post()) {
            verify_csrf();

            $dados = [
                'cliente_id'   => (int) input('cliente_id'),
                'viagem_id'    => (int) input('viagem_id'),
                'data_reserva' => (string) input('data_reserva'),
                'viajantes'    => (int) input('viajantes'),
                'valor_total'  => str_replace(',', '.', (string) input('valor_total')),
                'status'       => (string) input('status', 'pendente'),
                'observacoes'  => trim((string) input('observacoes')),
            ];

            if (!db_value('SELECT id FROM clientes WHERE id = ? LIMIT 1', [$dados['cliente_id']])) {
                $erros['cliente_id'] = 'Selecione um cliente válido.';
            }

            $viagem = db_one(
                'SELECT vw.*, p.preco AS pacote_preco, p.titulo AS pacote_titulo
                 FROM vw_viagens_resumo vw
                 INNER JOIN pacotes p ON p.id = vw.pacote_id
                 WHERE vw.id = ? LIMIT 1',
                [$dados['viagem_id']]
            );

            if ($viagem === null) {
                $erros['viagem_id'] = 'Selecione uma partida válida.';
            }

            if (!data_valida($dados['data_reserva'])) {
                $erros['data_reserva'] = 'Informe uma data de reserva válida.';
            }

            if ($dados['viajantes'] < 1 || $dados['viajantes'] > 30) {
                $erros['viajantes'] = 'O número de viajantes deve ficar entre 1 e 30.';
            }

            if (!is_numeric($dados['valor_total']) || (float) $dados['valor_total'] <= 0) {
                $erros['valor_total'] = 'Informe um valor total maior que zero.';
            }

            if (!in_array($dados['status'], ['pendente', 'confirmada', 'cancelada'], true)) {
                $erros['status'] = 'Selecione uma situação válida.';
            }

            if ($viagem !== null) {
                if (in_array($viagem['status'], ['cancelada', 'concluida'], true)
                    && ($modo === 'novo' || (int) $dados['viagem_id'] !== (int) $reserva['viagem_id'])) {
                    $erros['viagem_id'] = 'Esta partida não aceita novas reservas.';
                }

                $outrosOcupados = reserva_lugares_ocupados($dados['viagem_id'], $modo === 'editar' ? $id : null);
                $livres = (int) $viagem['capacidade'] - $outrosOcupados;

                if ($livres <= 0) {
                    $erros['viagem_id'] = 'A partida está lotada. Escolha outra data.';
                } elseif ($dados['viajantes'] > $livres) {
                    $erros['viajantes'] = 'A partida tem apenas ' . $livres . ' lugar' . ($livres === 1 ? '' : 'es')
                        . ' disponíve' . ($livres === 1 ? 'l' : 'is') . '.';
                }
            }

            if ($dados['status'] === 'cancelada' && $pagoAtual > 0) {
                $erros['status'] = 'Não é possível cancelar uma reserva com pagamentos já recebidos.';
            }

            if (is_numeric($dados['valor_total']) && (float) $dados['valor_total'] < $pagoAtual) {
                $erros['valor_total'] = 'O valor total não pode ser menor que os ' . money($pagoAtual) . ' já pagos.';
            }

            if (mb_strlen($dados['observacoes']) > 2000) {
                $erros['observacoes'] = 'As observações devem ter no máximo 2.000 caracteres.';
            }

            if (!$erros) {
                $statusFinal = $dados['status'];
                if ($statusFinal !== 'cancelada' && (float) $dados['valor_total'] <= $pagoAtual && $pagoAtual > 0) {
                    $statusFinal = 'confirmada';
                }

                $parametros = [
                    $dados['cliente_id'],
                    $dados['viagem_id'],
                    $dados['data_reserva'],
                    $dados['viajantes'],
                    round((float) $dados['valor_total'], 2),
                    $statusFinal,
                    $dados['observacoes'] !== '' ? $dados['observacoes'] : null,
                ];

                if ($modo === 'editar') {
                    $parametros[] = $id;
                    db_execute(
                        'UPDATE reservas SET cliente_id = ?, viagem_id = ?, data_reserva = ?, viajantes = ?,
                         valor_total = ?, status = ?, observacoes = ? WHERE id = ?',
                        $parametros
                    );

                    if ($statusFinal === 'cancelada') {
                        db_execute(
                            "UPDATE pagamentos
                             SET status = 'estornado',
                                 observacoes = CONCAT(COALESCE(observacoes, ''), ' Cancelado com a reserva.')
                             WHERE reserva_id = ? AND status = 'pendente'",
                            [$id]
                        );
                    }

                    redirect_com_flash('reservas', 'sucesso', 'Reserva atualizada.', ['acao' => 'ver', 'id' => $id]);
                }

                $codigo = reserva_gerar_codigo();
                db_execute(
                    'INSERT INTO reservas (codigo, cliente_id, viagem_id, data_reserva, viajantes, valor_total, status, observacoes)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
                    array_merge([$codigo], $parametros)
                );
                $novaId = (int) db_last_id();

                redirect_com_flash(
                    'reservas',
                    'sucesso',
                    'Reserva ' . $codigo . ' registrada. Adicione o primeiro pagamento para acompanhar o saldo.',
                    ['acao' => 'ver', 'id' => $novaId]
                );
            }

            http_response_code(422);
        }

        $clientesPorId = [];
        foreach ($clientes as $cliente) {
            $clientesPorId[(int) $cliente['id']] = $cliente;
        }

        render('reservas/form', [
            'pageTitle'  => $modo === 'editar' ? 'Editar reserva' : 'Nova reserva',
            'modo'       => $modo,
            'dados'      => $dados,
            'erros'      => $erros,
            'reserva'    => $reserva,
            'clientes'   => $clientes,
            'viagens'    => $viagens,
            'pagoAtual'  => $pagoAtual,
        ]);
        exit;

    case 'confirmar':
        if (!is_post()) {
            redirect('reservas');
        }
        verify_csrf();

        $reserva = reserva_buscar($id);
        if ($reserva === null) {
            abortar(404);
        }

        if ($reserva['status'] !== 'pendente') {
            redirect_com_flash('reservas', 'info', 'Esta reserva não está pendente.', ['acao' => 'ver', 'id' => $id]);
        }

        if ($reserva['viagem_status'] === 'cancelada') {
            redirect_com_flash('reservas', 'erro', 'A partida foi cancelada. Escolha outra data antes de confirmar.', ['acao' => 'editar', 'id' => $id]);
        }

        db_execute("UPDATE reservas SET status = 'confirmada' WHERE id = ?", [$id]);
        redirect_com_flash('reservas', 'sucesso', 'Reserva confirmada. O saldo continua disponível para cobrança.', ['acao' => 'ver', 'id' => $id]);
        exit;

    case 'cancelar':
        if (!is_post()) {
            redirect('reservas');
        }
        verify_csrf();

        $reserva = reserva_buscar($id);
        if ($reserva === null) {
            abortar(404);
        }

        if ($reserva['status'] === 'cancelada') {
            redirect_com_flash('reservas', 'info', 'Esta reserva já está cancelada.', ['acao' => 'ver', 'id' => $id]);
        }

        $pago = reserva_pago($id);
        if ($pago > 0) {
            redirect_com_flash(
                'reservas',
                'erro',
                'Não é possível cancelar: há ' . money($pago) . ' em pagamentos recebidos. Registre o estorno financeiro antes.',
                ['acao' => 'ver', 'id' => $id]
            );
        }

        db_execute("UPDATE reservas SET status = 'cancelada' WHERE id = ?", [$id]);
        db_execute(
            "UPDATE pagamentos
             SET status = 'estornado',
                 observacoes = CONCAT(COALESCE(observacoes, ''), ' Cancelado com a reserva.')
             WHERE reserva_id = ? AND status = 'pendente'",
            [$id]
        );

        redirect_com_flash(
            'reservas',
            'sucesso',
            'Reserva cancelada. Os lugares foram liberados e as cobranças pendentes foram anuladas.',
            ['acao' => 'ver', 'id' => $id]
        );
        exit;

    case 'excluir':
        if (!is_post()) {
            redirect('reservas');
        }
        verify_csrf();

        $reserva = reserva_buscar($id);
        if ($reserva === null) {
            abortar(404);
        }

        $pagamentos = (int) db_value('SELECT COUNT(*) FROM pagamentos WHERE reserva_id = ?', [$id], 0);
        if ($pagamentos > 0) {
            redirect_com_flash(
                'reservas',
                'erro',
                'Não é possível excluir: existem ' . $pagamentos . ' pagamento(s) vinculado(s). Cancele a reserva para preservar o histórico.',
                ['acao' => 'ver', 'id' => $id]
            );
        }

        try {
            db_execute('DELETE FROM reservas WHERE id = ?', [$id]);
            redirect_com_flash('reservas', 'sucesso', 'Reserva excluída.');
        } catch (PDOException $exception) {
            error_log('Erro ao excluir reserva: ' . $exception->getMessage());
            redirect_com_flash('reservas', 'erro', 'Não foi possível excluir a reserva agora.');
        }
        exit;

    case 'ver':
        $reserva = reserva_buscar($id);
        if ($reserva === null) {
            abortar(404);
        }

        $cliente = db_one('SELECT * FROM clientes WHERE id = ? LIMIT 1', [(int) $reserva['cliente_id']]);
        $viagem = db_one(
            'SELECT vw.*, p.inclusos, p.descricao AS pacote_descricao
             FROM vw_viagens_resumo vw
             INNER JOIN pacotes p ON p.id = vw.pacote_id
             WHERE vw.id = ? LIMIT 1',
            [(int) $reserva['viagem_id']]
        );

        $pagamentos = db_all(
            'SELECT * FROM pagamentos WHERE reserva_id = ? ORDER BY
                CASE status WHEN \'pendente\' THEN 0 WHEN \'pago\' THEN 1 ELSE 2 END,
                COALESCE(data_pagamento, vencimento) DESC, id DESC',
            [$id]
        );

        $pago = 0.0;
        $pendente = 0.0;
        $estornado = 0.0;
        foreach ($pagamentos as $pagamento) {
            if ($pagamento['status'] === 'pago') {
                $pago += (float) $pagamento['valor'];
            } elseif ($pagamento['status'] === 'pendente') {
                $pendente += (float) $pagamento['valor'];
            } else {
                $estornado += (float) $pagamento['valor'];
            }
        }

        render('reservas/show', [
            'pageTitle'   => 'Reserva ' . $reserva['codigo'],
            'reserva'     => $reserva,
            'cliente'     => $cliente,
            'viagem'      => $viagem,
            'pagamentos'  => $pagamentos,
            'pago'        => $pago,
            'pendente'    => $pendente,
            'estornado'   => $estornado,
            'saldo'       => max(0, (float) $reserva['valor_total'] - $pago),
        ]);
        exit;

    case 'index':
    default:
        $busca = trim((string) query('busca'));
        $statusFiltro = trim((string) query('status'));
        $viagemFiltro = (int) query('viagem_id');
        $financeiro = trim((string) query('financeiro'));

        $condicoes = [];
        $parametros = [];

        if ($busca !== '') {
            $condicoes[] = '(codigo LIKE ? OR cliente_nome LIKE ? OR destino_nome LIKE ? OR pacote_titulo LIKE ?)';
            $termo = '%' . $busca . '%';
            array_push($parametros, $termo, $termo, $termo, $termo);
        }

        if (in_array($statusFiltro, ['pendente', 'confirmada', 'cancelada'], true)) {
            $condicoes[] = 'status = ?';
            $parametros[] = $statusFiltro;
        } else {
            $statusFiltro = '';
        }

        if ($viagemFiltro > 0) {
            $condicoes[] = 'viagem_id = ?';
            $parametros[] = $viagemFiltro;
        }

        if ($financeiro === 'aberto') {
            $condicoes[] = "status <> 'cancelada' AND saldo > 0";
        } elseif ($financeiro === 'pago') {
            $condicoes[] = "status <> 'cancelada' AND saldo <= 0";
        } elseif ($financeiro === 'vencido') {
            $condicoes[] = "EXISTS (
                SELECT 1 FROM pagamentos pg
                WHERE pg.reserva_id = vw.id
                  AND pg.status = 'pendente' AND pg.vencimento < CURDATE()
            )";
        } else {
            $financeiro = '';
        }

        $where = $condicoes ? ' WHERE ' . implode(' AND ', $condicoes) : '';

        $total = (int) db_value('SELECT COUNT(*) FROM vw_reservas_financeiro vw' . $where, $parametros, 0);
        $paginacao = paginar($total, 10);

        $reservas = db_all(
            'SELECT vw.*,
                    (SELECT COUNT(*) FROM pagamentos pg
                     WHERE pg.reserva_id = vw.id AND pg.status = \'pendente\' AND pg.vencimento < CURDATE()) AS vencidos
             FROM vw_reservas_financeiro vw' . $where . '
             ORDER BY CASE status WHEN \'pendente\' THEN 0 WHEN \'confirmada\' THEN 1 ELSE 2 END,
                      criado_em DESC, id DESC
             LIMIT ' . $paginacao['por_pagina'] . ' OFFSET ' . $paginacao['offset'],
            $parametros
        );

        $resumo = db_one(
            "SELECT COUNT(*) AS total,
                    COALESCE(SUM(viajantes), 0) AS viajantes,
                    COALESCE(SUM(valor_total), 0) AS contratado,
                    COALESCE(SUM(valor_pago), 0) AS pago,
                    COALESCE(SUM(CASE WHEN status <> 'cancelada' THEN GREATEST(saldo, 0) ELSE 0 END), 0) AS aberto
             FROM vw_reservas_financeiro vw" . $where,
            $parametros
        ) ?? ['total' => 0, 'viajantes' => 0, 'contratado' => 0, 'pago' => 0, 'aberto' => 0];

        $viagemFiltroInfo = $viagemFiltro > 0
            ? db_one(
                'SELECT vw.id, vw.destino_nome, vw.data_ida, vw.pacote_titulo
                 FROM vw_viagens_resumo vw WHERE vw.id = ? LIMIT 1',
                [$viagemFiltro]
            )
            : null;

        $temFiltros = $busca !== '' || $statusFiltro !== '' || $viagemFiltro > 0 || $financeiro !== '';

        render('reservas/index', [
            'pageTitle'       => 'Reservas',
            'reservas'        => $reservas,
            'busca'           => $busca,
            'statusFiltro'    => $statusFiltro,
            'viagemFiltro'    => $viagemFiltro,
            'viagemFiltroInfo' => $viagemFiltroInfo,
            'financeiro'      => $financeiro,
            'paginacao'       => $paginacao,
            'total'           => $total,
            'resumo'          => $resumo,
            'temFiltros'      => $temFiltros,
        ]);
        exit;
}
