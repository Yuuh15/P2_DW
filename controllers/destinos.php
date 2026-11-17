<?php

declare(strict_types=1);

$acao = isset($_GET['acao']) && is_string($_GET['acao']) ? $_GET['acao'] : 'index';
$id = (int) ($_GET['id'] ?? 0);

function destino_buscar(int $id): ?array
{
    return db_one('SELECT * FROM destinos WHERE id = ? LIMIT 1', [$id]);
}

switch ($acao) {
    case 'novo':
    case 'editar':
        $modo = $acao;
        $destino = $modo === 'editar' ? destino_buscar($id) : null;

        if ($modo === 'editar' && $destino === null) {
            abortar(404);
        }

        $dados = $destino ?? [
            'nome' => '', 'pais' => 'Brasil', 'regiao' => '', 'descricao' => '',
            'imagem' => '', 'melhor_epoca' => '', 'ativo' => 1,
        ];
        $erros = [];

        if (is_post()) {
            verify_csrf();

            $dados = [
                'nome'         => trim((string) input('nome')),
                'pais'         => trim((string) input('pais')),
                'regiao'       => trim((string) input('regiao')),
                'descricao'    => trim((string) input('descricao')),
                'imagem'       => $dados['imagem'] ?? '',
                'melhor_epoca' => trim((string) input('melhor_epoca')),
                'ativo'        => isset($_POST['ativo']) ? 1 : 0,
            ];

            if (mb_strlen($dados['nome']) < 2 || mb_strlen($dados['nome']) > 120) {
                $erros['nome'] = 'Informe o nome do destino (até 120 caracteres).';
            } else {
                $existente = db_value(
                    'SELECT id FROM destinos WHERE nome = ? AND id <> ? LIMIT 1',
                    [$dados['nome'], $modo === 'editar' ? $id : 0]
                );
                if ($existente) {
                    $erros['nome'] = 'Já existe um destino com este nome.';
                }
            }

            if ($dados['pais'] === '' || mb_strlen($dados['pais']) > 80) {
                $erros['pais'] = 'Informe o país do destino.';
            }

            if (mb_strlen($dados['regiao']) > 80) {
                $erros['regiao'] = 'A região deve ter no máximo 80 caracteres.';
            }

            if (mb_strlen($dados['melhor_epoca']) > 100) {
                $erros['melhor_epoca'] = 'Use no máximo 100 caracteres.';
            }

            if (mb_strlen($dados['descricao']) > 3000) {
                $erros['descricao'] = 'A descrição deve ter no máximo 3.000 caracteres.';
            }

            if (isset($_POST['remover_imagem']) && $dados['imagem'] !== '') {
                $dados['imagem'] = '';
            }

            if (!$erros) {
                [$caminho, $erroUpload] = upload_imagem_destino('imagem', $dados['imagem']);

                if ($erroUpload !== null) {
                    $erros['imagem'] = $erroUpload;
                } else {
                    $dados['imagem'] = $caminho ?? '';
                }
            }

            if (!$erros) {
                $parametros = [
                    $dados['nome'],
                    $dados['pais'],
                    $dados['regiao'] !== '' ? $dados['regiao'] : null,
                    $dados['descricao'] !== '' ? $dados['descricao'] : null,
                    $dados['imagem'] !== '' ? $dados['imagem'] : null,
                    $dados['melhor_epoca'] !== '' ? $dados['melhor_epoca'] : null,
                    (int) $dados['ativo'],
                ];

                if ($modo === 'editar') {
                    $parametros[] = $id;
                    db_execute(
                        'UPDATE destinos SET nome = ?, pais = ?, regiao = ?, descricao = ?, imagem = ?,
                         melhor_epoca = ?, ativo = ? WHERE id = ?',
                        $parametros
                    );
                    redirect_com_flash('destinos', 'sucesso', 'Destino atualizado.');
                }

                db_execute(
                    'INSERT INTO destinos (nome, pais, regiao, descricao, imagem, melhor_epoca, ativo)
                     VALUES (?, ?, ?, ?, ?, ?, ?)',
                    $parametros
                );
                redirect_com_flash('destinos', 'sucesso', 'Destino cadastrado.');
            }

            http_response_code(422);
        }

        render('destinos/form', [
            'pageTitle' => $modo === 'editar' ? 'Editar destino' : 'Novo destino',
            'modo'      => $modo,
            'dados'     => $dados,
            'erros'     => $erros,
            'destino'   => $destino,
        ]);
        exit;

    case 'excluir':
        if (!is_post()) {
            redirect('destinos');
        }
        verify_csrf();

        $destino = destino_buscar($id);
        if ($destino === null) {
            abortar(404);
        }

        $pacotes = (int) db_value('SELECT COUNT(*) FROM pacotes WHERE destino_id = ?', [$id], 0);
        if ($pacotes > 0) {
            redirect_com_flash(
                'destinos',
                'erro',
                'Não é possível excluir ' . $destino['nome'] . ': há ' . $pacotes . ' pacote(s) vinculado(s).'
            );
        }

        try {
            db_execute('DELETE FROM destinos WHERE id = ?', [$id]);

            // Remove apenas arquivos enviados pela aplicação, nunca as imagens do projeto.
            if (!empty($destino['imagem']) && str_starts_with($destino['imagem'], 'uploads/')) {
                $arquivo = BASE_PATH . '/' . $destino['imagem'];
                if (is_file($arquivo)) {
                    @unlink($arquivo);
                }
            }

            redirect_com_flash('destinos', 'sucesso', 'Destino excluído.');
        } catch (PDOException $exception) {
            error_log('Erro ao excluir destino: ' . $exception->getMessage());
            redirect_com_flash('destinos', 'erro', 'Não foi possível excluir o destino agora.');
        }
        exit;

    case 'index':
    default:
        $busca = trim((string) query('busca'));
        $where = '';
        $parametros = [];

        if ($busca !== '') {
            $where = 'WHERE d.nome LIKE ? OR d.pais LIKE ? OR d.regiao LIKE ?';
            $termo = '%' . $busca . '%';
            $parametros = [$termo, $termo, $termo];
        }

        $total = (int) db_value('SELECT COUNT(*) FROM destinos d ' . $where, $parametros, 0);
        $paginacao = paginar($total, 12);

        $destinos = db_all(
            'SELECT d.*,
                    (SELECT COUNT(*) FROM pacotes p WHERE p.destino_id = d.id) AS total_pacotes,
                    (SELECT COUNT(*) FROM viagens v INNER JOIN pacotes p ON p.id = v.pacote_id
                     WHERE p.destino_id = d.id AND v.data_ida >= CURDATE() AND v.status <> \'cancelada\') AS viagens_futuras
             FROM destinos d ' . $where . '
             ORDER BY d.nome ASC
             LIMIT ' . $paginacao['por_pagina'] . ' OFFSET ' . $paginacao['offset'],
            $parametros
        );

        render('destinos/index', [
            'pageTitle' => 'Destinos',
            'destinos'  => $destinos,
            'busca'     => $busca,
            'paginacao' => $paginacao,
            'total'     => $total,
        ]);
        exit;
}
