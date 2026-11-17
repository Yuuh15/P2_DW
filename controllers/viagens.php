<?php

declare(strict_types=1);

$acao = isset($_GET['acao']) && is_string($_GET['acao']) ? $_GET['acao'] : 'index';
$id = (int) ($_GET['id'] ?? 0);

function viagem_buscar(int $id): ?array
{
    return db_one(
        'SELECT vw.*, p.descricao AS pacote_descricao, p.inclusos, p.duracao_dias,
                d.id AS destino_id, d.descricao AS destino_descricao
         FROM vw_viagens_resumo vw
         INNER JOIN pacotes p ON p.id = vw.pacote_id
         INNER JOIN destinos d ON d.id = p.destino_id
         WHERE vw.id = ?
         LIMIT 1',
        [$id]
    );
}

function viagem_status_permitidos(string $atual): array
{
    return [
        'planejada'    => ['planejada', 'confirmada', 'cancelada'],
        'confirmada'   => ['confirmada', 'em_andamento', 'cancelada'],
        'em_andamento' => ['em_andamento', 'concluida'],
        'concluida'    => ['concluida'],
        'cancelada'    => ['cancelada'],
    ][$atual] ?? [$atual];
}

function viagem_lugares_ocupados(int $id): int
{
    return (int) db_value(
        "SELECT COALESCE(SUM(viajantes), 0) FROM reservas
         WHERE viagem_id = ? AND status IN ('pendente', 'confirmada')",
        [$id],
        0
    );
}

switch ($acao) {
    case 'novo':
    case 'editar':
        $modo = $acao;
        $viagem = $modo === 'editar' ? viagem_buscar($id) : null;

        if ($modo === 'editar' && $viagem === null) {
            abortar(404);
        }

        $pacotes = db_all(
            'SELECT p.id, p.titulo, p.duracao_dias, p.preco, d.nome AS destino_nome, d.pais AS destino_pais
             FROM pacotes p
             INNER JOIN destinos d ON d.id = p.destino_id
             ORDER BY d.nome ASC, p.titulo ASC'
        );

        if (!$pacotes) {
            redirect_com_flash('pacotes', 'info', 'Cadastre um pacote antes de abrir uma partida.');
        }

        $pacotePreSelecionado = (int) ($_GET['pacote_id'] ?? 0);
        $dados = $viagem ?? [
            'pacote_id'      => $pacotePreSelecionado,
            'data_ida'       => '',
            'data_volta'     => '',
            'capacidade'     => 16,
            'status'         => 'planejada',
            'ponto_encontro' => '',
            'observacoes'    => '',
        ];
        $erros = [];
        $ocupados = $modo === 'editar' ? viagem_lugares_ocupados($id) : 0;

        if (is_post()) {
            verify_csrf();

            $dados = [
                'pacote_id'      => (int) input('pacote_id'),
                'data_ida'       => (string) input('data_ida'),
                'data_volta'     => (string) input('data_volta'),
                'capacidade'     => (int) input('capacidade'),
                'status'         => (string) input('status', 'planejada'),
                'ponto_encontro' => trim((string) input('ponto_encontro')),
                'observacoes'    => trim((string) input('observacoes')),
            ];

            if (!db_value('SELECT id FROM pacotes WHERE id = ? LIMIT 1', [$dados['pacote_id']])) {
                $erros['pacote_id'] = 'Selecione um pacote válido.';
            }

            if (!data_valida($dados['data_ida'])) {
                $erros['data_ida'] = 'Informe uma data de ida válida.';
            }

            if (!data_valida($dados['data_volta'])) {
                $erros['data_volta'] = 'Informe uma data de volta válida.';
            }

            if (!isset($erros['data_ida']) && !isset($erros['data_volta']) && $dados['data_volta'] < $dados['data_ida']) {
                $erros['data_volta'] = 'A data de volta deve ser igual ou posterior à data de ida.';
            }

            if ($dados['capacidade'] < 1 || $dados['capacidade'] > 500) {
                $erros['capacidade'] = 'A capacidade deve ficar entre 1 e 500 lugares.';
            }

            if (!in_array($dados['status'], ['planejada', 'confirmada', 'em_andamento', 'concluida', 'cancelada'], true)) {
                $erros['status'] = 'Selecione uma situação válida.';
            }

            if ($modo === 'editar' && $viagem !== null && !isset($erros['status'])
                && !in_array($dados['status'], viagem_status_permitidos($viagem['status']), true)) {
                $erros['status'] = 'A situação não pode avançar de ' . status_viagem_label($viagem['status'])
                    . ' para ' . status_viagem_label($dados['status']) . '.';
            }

            if ($dados['capacidade'] < $ocupados) {
                $erros['capacidade'] = 'A capacidade não pode ser menor que os ' . $ocupados . ' lugares já ocupados.';
            }

            if (mb_strlen($dados['ponto_encontro']) > 180) {
                $erros['ponto_encontro'] = 'O ponto de encontro deve ter no máximo 180 caracteres.';
            }

            if (mb_strlen($dados['observacoes']) > 2000) {
                $erros['observacoes'] = 'As observações devem ter no máximo 2.000 caracteres.';
            }

            if (!$erros) {
                $parametros = [
                    $dados['pacote_id'],
                    $dados['data_ida'],
                    $dados['data_volta'],
                    $dados['capacidade'],
                    $dados['status'],
                    $dados['ponto_encontro'] !== '' ? $dados['ponto_encontro'] : null,
                    $dados['observacoes'] !== '' ? $dados['observacoes'] : null,
                ];

                if ($modo === 'editar') {
                    $parametros[] = $id;
                    db_execute(
                        'UPDATE viagens SET pacote_id = ?, data_ida = ?, data_volta = ?, capacidade = ?,
                         status = ?, ponto_encontro = ?, observacoes = ? WHERE id = ?',
                        $parametros
                    );
                    redirect_com_flash('viagens', 'sucesso', 'Partida atualizada.');
                }

                db_execute(
                    'INSERT INTO viagens (pacote_id, data_ida, data_volta, capacidade, status, ponto_encontro, observacoes)
                     VALUES (?, ?, ?, ?, ?, ?, ?)',
                    $parametros
                );
                redirect_com_flash('viagens', 'sucesso', 'Partida criada. Agora ela já pode receber reservas.');
            }

            http_response_code(422);
        }

        render('viagens/form', [
            'pageTitle' => $modo === 'editar' ? 'Editar partida' : 'Nova partida',
            'modo'      => $modo,
            'dados'     => $dados,
            'erros'     => $erros,
            'viagem'    => $viagem,
            'pacotes'   => $pacotes,
            'ocupados'  => $ocupados,
            'statusPermitidos' => $modo === 'editar' && $viagem
                ? viagem_status_permitidos($viagem['status'])
                : ['planejada', 'confirmada', 'em_andamento', 'concluida', 'cancelada'],
        ]);
        exit;

    case 'status':
        if (!is_post()) {
            redirect('viagens');
        }
        verify_csrf();

        $viagem = viagem_buscar($id);
        if ($viagem === null) {
            abortar(404);
        }

        $novo = (string) input('status');
        if (!in_array($novo, viagem_status_permitidos($viagem['status']), true) || $novo === $viagem['status']) {
            redirect_com_flash('viagens', 'erro', 'Essa mudança de situação não é permitida.', ['acao' => 'ver', 'id' => $id]);
        }

        if ($novo === 'cancelada') {
            $ativas = (int) db_value(
                "SELECT COUNT(*) FROM reservas WHERE viagem_id = ? AND status IN ('pendente', 'confirmada')",
                [$id],
                0
            );

            if ($ativas > 0) {
                redirect_com_flash(
                    'viagens',
                    'erro',
                    'Cancele ou transfira as ' . $ativas . ' reserva(s) ativa(s) antes de cancelar a partida.',
                    ['acao' => 'ver', 'id' => $id]
                );
            }
        }

        db_execute('UPDATE viagens SET status = ? WHERE id = ?', [$novo, $id]);
        redirect_com_flash(
            'viagens',
            'sucesso',
            'Situação da partida alterada para ' . mb_strtolower(status_viagem_label($novo)) . '.',
            ['acao' => 'ver', 'id' => $id]
        );
        exit;

    case 'excluir':
        if (!is_post()) {
            redirect('viagens');
        }
        verify_csrf();

        $viagem = viagem_buscar($id);
        if ($viagem === null) {
            abortar(404);
        }

        $reservas = (int) db_value('SELECT COUNT(*) FROM reservas WHERE viagem_id = ?', [$id], 0);
        if ($reservas > 0) {
            redirect_com_flash(
                'viagens',
                'erro',
                'Não é possível excluir esta partida: há ' . $reservas . ' reserva(s) vinculada(s). Cancele a partida para preservar o histórico.'
            );
        }

        try {
            db_execute('DELETE FROM viagens WHERE id = ?', [$id]);
            redirect_com_flash('viagens', 'sucesso', 'Partida excluída.');
        } catch (PDOException $exception) {
            error_log('Erro ao excluir viagem: ' . $exception->getMessage());
            redirect_com_flash('viagens', 'erro', 'Não foi possível excluir a partida agora.');
        }
        exit;

    case 'ver':
        $viagem = viagem_buscar($id);
        if ($viagem === null) {
            abortar(404);
        }

        $reservas = db_all(
            'SELECT * FROM vw_reservas_financeiro WHERE viagem_id = ? ORDER BY criado_em DESC, id DESC',
            [$id]
        );

        $totalPago = 0.0;
        $totalPendente = 0.0;
        $totalContratado = 0.0;
        foreach ($reservas as $reserva) {
            if ($reserva['status'] === 'cancelada') {
                continue;
            }
            $totalContratado += (float) $reserva['valor_total'];
            $totalPago += (float) $reserva['valor_pago'];
            $totalPendente += max(0, (float) $reserva['saldo']);
        }

        $proximas = db_all(
            'SELECT id, titulo, duracao_dias FROM pacotes WHERE destino_id = ? AND id <> ? ORDER BY titulo ASC LIMIT 3',
            [(int) $viagem['destino_id'], (int) $viagem['pacote_id']]
        );

        render('viagens/show', [
            'pageTitle'        => 'Partida · ' . $viagem['destino_nome'],
            'viagem'           => $viagem,
            'reservas'         => $reservas,
            'totalPago'        => $totalPago,
            'totalPendente'    => $totalPendente,
            'totalContratado'  => $totalContratado,
            'ocupados'         => (int) $viagem['lugares_ocupados'],
            'proximas'         => $proximas,
            'statusPermitidos' => viagem_status_permitidos($viagem['status']),
        ]);
        exit;

    case 'index':
    default:
        $busca = trim((string) query('busca'));
        $statusFiltro = trim((string) query('status'));
        $pacoteFiltro = (int) query('pacote_id');
        $periodo = trim((string) query('periodo'));

        $condicoes = [];
        $parametros = [];

        if ($busca !== '') {
            $condicoes[] = '(pacote_titulo LIKE ? OR destino_nome LIKE ? OR ponto_encontro LIKE ?)';
            $termo = '%' . $busca . '%';
            array_push($parametros, $termo, $termo, $termo);
        }

        if (in_array($statusFiltro, ['planejada', 'confirmada', 'em_andamento', 'concluida', 'cancelada'], true)) {
            $condicoes[] = 'status = ?';
            $parametros[] = $statusFiltro;
        } else {
            $statusFiltro = '';
        }

        if ($pacoteFiltro > 0) {
            $condicoes[] = 'pacote_id = ?';
            $parametros[] = $pacoteFiltro;
        }

        if ($periodo === 'proximas') {
            $condicoes[] = 'data_volta >= CURDATE()';
        } elseif ($periodo === 'passadas') {
            $condicoes[] = 'data_volta < CURDATE()';
        } else {
            $periodo = '';
        }

        $where = $condicoes ? ' WHERE ' . implode(' AND ', $condicoes) : '';

        $total = (int) db_value('SELECT COUNT(*) FROM vw_viagens_resumo' . $where, $parametros, 0);
        $paginacao = paginar($total, 10);

        $viagens = db_all(
            'SELECT vw.*,
                    GREATEST(vw.capacidade - vw.lugares_ocupados, 0) AS lugares_livres
             FROM vw_viagens_resumo vw' . $where . '
             ORDER BY CASE WHEN vw.data_ida >= CURDATE() THEN 0 ELSE 1 END,
                      CASE WHEN vw.data_ida >= CURDATE() THEN vw.data_ida END ASC,
                      vw.data_ida DESC
             LIMIT ' . $paginacao['por_pagina'] . ' OFFSET ' . $paginacao['offset'],
            $parametros
        );

        $resumo = db_one(
            'SELECT COUNT(*) AS partidas,
                    COALESCE(SUM(capacidade), 0) AS lugares,
                    COALESCE(SUM(lugares_ocupados), 0) AS ocupados,
                    COALESCE(SUM(receita_prevista), 0) AS receita
             FROM vw_viagens_resumo' . $where,
            $parametros
        ) ?? ['partidas' => 0, 'lugares' => 0, 'ocupados' => 0, 'receita' => 0];

        $pacotesFiltro = db_all(
            'SELECT p.id, p.titulo, d.nome AS destino_nome
             FROM pacotes p INNER JOIN destinos d ON d.id = p.destino_id
             ORDER BY d.nome ASC, p.titulo ASC'
        );

        render('viagens/index', [
            'pageTitle'     => 'Viagens',
            'viagens'       => $viagens,
            'pacotes'       => $pacotesFiltro,
            'busca'         => $busca,
            'statusFiltro'  => $statusFiltro,
            'pacoteFiltro'  => $pacoteFiltro,
            'periodo'       => $periodo,
            'paginacao'     => $paginacao,
            'total'         => $total,
            'resumo'        => $resumo,
        ]);
        exit;
}
