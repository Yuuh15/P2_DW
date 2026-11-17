<?php

declare(strict_types=1);

$acao = isset($_GET['acao']) && is_string($_GET['acao']) ? $_GET['acao'] : 'index';
$id = (int) ($_GET['id'] ?? 0);

function pacote_buscar(int $id): ?array
{
    return db_one('SELECT * FROM pacotes WHERE id = ? LIMIT 1', [$id]);
}

switch ($acao) {
    case 'novo':
    case 'editar':
        $modo = $acao;
        $pacote = $modo === 'editar' ? pacote_buscar($id) : null;

        if ($modo === 'editar' && $pacote === null) {
            abortar(404);
        }

        $destinos = db_all('SELECT id, nome, pais FROM destinos ORDER BY nome ASC');
        if (!$destinos) {
            redirect_com_flash('destinos', 'info', 'Cadastre um destino antes de criar um pacote.');
        }

        $destinoPreSelecionado = (int) ($_GET['destino_id'] ?? 0);
        $dados = $pacote ?? [
            'destino_id'   => $destinoPreSelecionado,
            'titulo'       => '',
            'descricao'    => '',
            'duracao_dias' => 5,
            'preco'        => '',
            'inclusos'     => '',
            'ativo'        => 1,
        ];
        $erros = [];

        if (is_post()) {
            verify_csrf();

            $dados = [
                'destino_id'   => (int) input('destino_id'),
                'titulo'       => trim((string) input('titulo')),
                'descricao'    => trim((string) input('descricao')),
                'duracao_dias' => (int) input('duracao_dias'),
                'preco'        => str_replace(',', '.', (string) input('preco')),
                'inclusos'     => trim((string) input('inclusos')),
                'ativo'        => isset($_POST['ativo']) ? 1 : 0,
            ];

            if (!db_value('SELECT id FROM destinos WHERE id = ? LIMIT 1', [$dados['destino_id']])) {
                $erros['destino_id'] = 'Selecione um destino válido.';
            }

            if (mb_strlen($dados['titulo']) < 3 || mb_strlen($dados['titulo']) > 160) {
                $erros['titulo'] = 'Informe um título entre 3 e 160 caracteres.';
            }

            if ($dados['duracao_dias'] < 1 || $dados['duracao_dias'] > 60) {
                $erros['duracao_dias'] = 'A duração deve ficar entre 1 e 60 dias.';
            }

            if (!is_numeric($dados['preco']) || (float) $dados['preco'] <= 0) {
                $erros['preco'] = 'Informe um preço por pessoa maior que zero.';
            }

            if (mb_strlen($dados['descricao']) > 3000) {
                $erros['descricao'] = 'A descrição deve ter no máximo 3.000 caracteres.';
            }

            if (mb_strlen($dados['inclusos']) > 2000) {
                $erros['inclusos'] = 'A lista de inclusos deve ter no máximo 2.000 caracteres.';
            }

            if (!$erros) {
                $parametros = [
                    $dados['destino_id'],
                    $dados['titulo'],
                    $dados['descricao'] !== '' ? $dados['descricao'] : null,
                    $dados['duracao_dias'],
                    round((float) $dados['preco'], 2),
                    $dados['inclusos'] !== '' ? $dados['inclusos'] : null,
                    (int) $dados['ativo'],
                ];

                if ($modo === 'editar') {
                    $parametros[] = $id;
                    db_execute(
                        'UPDATE pacotes SET destino_id = ?, titulo = ?, descricao = ?, duracao_dias = ?, preco = ?,
                         inclusos = ?, ativo = ? WHERE id = ?',
                        $parametros
                    );
                    redirect_com_flash('pacotes', 'sucesso', 'Pacote atualizado.');
                }

                db_execute(
                    'INSERT INTO pacotes (destino_id, titulo, descricao, duracao_dias, preco, inclusos, ativo)
                     VALUES (?, ?, ?, ?, ?, ?, ?)',
                    $parametros
                );
                redirect_com_flash('pacotes', 'sucesso', 'Pacote cadastrado.');
            }

            http_response_code(422);
        }

        render('pacotes/form', [
            'pageTitle' => $modo === 'editar' ? 'Editar pacote' : 'Novo pacote',
            'modo'      => $modo,
            'dados'     => $dados,
            'erros'     => $erros,
            'pacote'    => $pacote,
            'destinos'  => $destinos,
        ]);
        exit;

    case 'excluir':
        if (!is_post()) {
            redirect('pacotes');
        }
        verify_csrf();

        $pacote = pacote_buscar($id);
        if ($pacote === null) {
            abortar(404);
        }

        $viagens = (int) db_value('SELECT COUNT(*) FROM viagens WHERE pacote_id = ?', [$id], 0);
        if ($viagens > 0) {
            redirect_com_flash(
                'pacotes',
                'erro',
                'Não é possível excluir este pacote: há ' . $viagens . ' viagem(ns) vinculada(s).'
            );
        }

        try {
            db_execute('DELETE FROM pacotes WHERE id = ?', [$id]);
            redirect_com_flash('pacotes', 'sucesso', 'Pacote excluído.');
        } catch (PDOException $exception) {
            error_log('Erro ao excluir pacote: ' . $exception->getMessage());
            redirect_com_flash('pacotes', 'erro', 'Não foi possível excluir o pacote agora.');
        }
        exit;

    case 'index':
    default:
        $busca = trim((string) query('busca'));
        $destinoFiltro = (int) query('destino_id');
        $condicoes = [];
        $parametros = [];

        if ($busca !== '') {
            $condicoes[] = '(p.titulo LIKE ? OR d.nome LIKE ?)';
            $termo = '%' . $busca . '%';
            $parametros[] = $termo;
            $parametros[] = $termo;
        }

        if ($destinoFiltro > 0) {
            $condicoes[] = 'p.destino_id = ?';
            $parametros[] = $destinoFiltro;
        }

        $where = $condicoes ? 'WHERE ' . implode(' AND ', $condicoes) : '';
        $total = (int) db_value(
            'SELECT COUNT(*) FROM pacotes p INNER JOIN destinos d ON d.id = p.destino_id ' . $where,
            $parametros,
            0
        );
        $paginacao = paginar($total, 10);

        $pacotes = db_all(
            'SELECT p.*, d.nome AS destino_nome, d.pais AS destino_pais, d.imagem AS destino_imagem,
                    (SELECT COUNT(*) FROM viagens v WHERE v.pacote_id = p.id) AS total_viagens,
                    (SELECT COUNT(*) FROM viagens v WHERE v.pacote_id = p.id AND v.data_ida >= CURDATE() AND v.status <> \'cancelada\') AS viagens_futuras
             FROM pacotes p
             INNER JOIN destinos d ON d.id = p.destino_id ' . $where . '
             ORDER BY d.nome ASC, p.titulo ASC
             LIMIT ' . $paginacao['por_pagina'] . ' OFFSET ' . $paginacao['offset'],
            $parametros
        );

        $destinosFiltro = db_all('SELECT id, nome FROM destinos ORDER BY nome');

        render('pacotes/index', [
            'pageTitle'      => 'Pacotes',
            'pacotes'        => $pacotes,
            'destinos'       => $destinosFiltro,
            'busca'          => $busca,
            'destinoFiltro'  => $destinoFiltro,
            'paginacao'      => $paginacao,
            'total'          => $total,
        ]);
        exit;
}
