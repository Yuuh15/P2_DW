<?php

declare(strict_types=1);

$acao = isset($_GET['acao']) && is_string($_GET['acao']) ? $_GET['acao'] : 'index';

$inicioPadrao = (new DateTimeImmutable('first day of -5 months'))->format('Y-m-d');
$inicio = (string) query('inicio', $inicioPadrao);
$fim = (string) query('fim', date('Y-m-d'));

if (!data_valida($inicio)) {
    $inicio = $inicioPadrao;
}

if (!data_valida($fim)) {
    $fim = date('Y-m-d');
}

if ($inicio > $fim) {
    [$inicio, $fim] = [$fim, $inicio];
}

$destinoId = (int) query('destino_id');
$destinos = db_all('SELECT id, nome, pais FROM destinos ORDER BY nome ASC');
$destinoAtual = null;
foreach ($destinos as $destino) {
    if ((int) $destino['id'] === $destinoId) {
        $destinoAtual = $destino;
        break;
    }
}
if ($destinoAtual === null) {
    $destinoId = 0;
}

$parametrosBase = [$inicio, $fim];
$filtroReceita = '';
$filtroReserva = '';
if ($destinoId > 0) {
    $filtroReceita = ' AND d.id = ?';
    $filtroReserva = ' AND destino_id = ?';
    $parametrosBase[] = $destinoId;
}

$parametrosReceita = $parametrosBase;
$parametrosReserva = $parametrosBase;

$juncaoReceita = ' FROM pagamentos pg
                   INNER JOIN reservas r ON r.id = pg.reserva_id
                   INNER JOIN clientes c ON c.id = r.cliente_id
                   INNER JOIN viagens v ON v.id = r.viagem_id
                   INNER JOIN pacotes p ON p.id = v.pacote_id
                   INNER JOIN destinos d ON d.id = p.destino_id';

$receitaResumo = db_one(
    "SELECT COUNT(*) AS recebimentos, COALESCE(SUM(pg.valor), 0) AS total
     " . $juncaoReceita . "
     WHERE pg.status = 'pago' AND pg.data_pagamento BETWEEN ? AND ?" . $filtroReceita,
    $parametrosReceita
) ?? ['recebimentos' => 0, 'total' => 0];

$resumoReservas = db_one(
    "SELECT COUNT(*) AS total,
            COALESCE(SUM(CASE WHEN status <> 'cancelada' THEN 1 ELSE 0 END), 0) AS ativas,
            COALESCE(SUM(CASE WHEN status = 'cancelada' THEN 1 ELSE 0 END), 0) AS canceladas,
            COALESCE(SUM(CASE WHEN status <> 'cancelada' THEN viajantes ELSE 0 END), 0) AS viajantes,
            COALESCE(SUM(CASE WHEN status <> 'cancelada' THEN valor_total ELSE 0 END), 0) AS contratado,
            COALESCE(SUM(CASE WHEN status <> 'cancelada' THEN valor_pago ELSE 0 END), 0) AS pago,
            COALESCE(SUM(CASE WHEN status <> 'cancelada' THEN GREATEST(valor_total - valor_pago, 0) ELSE 0 END), 0) AS saldo
     FROM vw_reservas_financeiro
     WHERE data_reserva BETWEEN ? AND ?" . $filtroReserva,
    $parametrosReserva
) ?? [
    'total' => 0, 'ativas' => 0, 'canceladas' => 0, 'viajantes' => 0,
    'contratado' => 0, 'pago' => 0, 'saldo' => 0,
];

$linhasMes = db_all(
    "SELECT DATE_FORMAT(pg.data_pagamento, '%Y-%m') AS mes,
            COUNT(*) AS recebimentos,
            SUM(pg.valor) AS total
     " . $juncaoReceita . "
     WHERE pg.status = 'pago' AND pg.data_pagamento BETWEEN ? AND ?" . $filtroReceita . '
     GROUP BY mes
     ORDER BY mes ASC',
    $parametrosReceita
);

$totaisMes = [];
foreach ($linhasMes as $linha) {
    $totaisMes[$linha['mes']] = (float) $linha['total'];
}

$meses = [];
$cursor = (new DateTimeImmutable($inicio))->modify('first day of this month');
$limite = new DateTimeImmutable($fim);
$graficoLimitado = false;

// Evita gráficos ilegíveis em intervalos muito longos; os totais seguem completos.
$mesesNoIntervalo = ((int) $limite->format('Y') - (int) $cursor->format('Y')) * 12
    + ((int) $limite->format('n') - (int) $cursor->format('n')) + 1;
if ($mesesNoIntervalo > 24) {
    $cursor = $limite->modify('-23 months')->modify('first day of this month');
    $graficoLimitado = true;
}

while ($cursor <= $limite) {
    $chave = $cursor->format('Y-m');
    $meses[] = [
        'chave' => $chave,
        'label' => mb_strtoupper(mb_substr(nome_mes((int) $cursor->format('n')), 0, 3)) . '/' . $cursor->format('y'),
        'total' => $totaisMes[$chave] ?? 0.0,
        'atual' => $chave === date('Y-m'),
    ];
    $cursor = $cursor->modify('+1 month');
}
$maiorMes = max(array_column($meses, 'total') ?: [0]) ?: 1;

$porForma = db_all(
    "SELECT pg.forma, COUNT(*) AS qtd, SUM(pg.valor) AS total
     " . $juncaoReceita . "
     WHERE pg.status = 'pago' AND pg.data_pagamento BETWEEN ? AND ?" . $filtroReceita . '
     GROUP BY pg.forma
     ORDER BY total DESC',
    $parametrosReceita
);

$porDestino = db_all(
    "SELECT d.nome AS destino, d.pais, COUNT(*) AS qtd, SUM(pg.valor) AS total
     " . $juncaoReceita . "
     WHERE pg.status = 'pago' AND pg.data_pagamento BETWEEN ? AND ?" . $filtroReceita . '
     GROUP BY d.id, d.nome, d.pais
     ORDER BY total DESC
     LIMIT 6',
    $parametrosReceita
);

$topClientes = db_all(
    "SELECT cliente_id, cliente_nome, COUNT(*) AS reservas, SUM(viajantes) AS viajantes,
            SUM(valor_total) AS contratado, SUM(valor_pago) AS pago
     FROM vw_reservas_financeiro
     WHERE data_reserva BETWEEN ? AND ? AND status <> 'cancelada'" . $filtroReserva . '
     GROUP BY cliente_id, cliente_nome
     ORDER BY contratado DESC
     LIMIT 5',
    $parametrosReserva
);

$porStatus = db_all(
    "SELECT status, COUNT(*) AS qtd, COALESCE(SUM(viajantes), 0) AS viajantes, COALESCE(SUM(valor_total), 0) AS total
     FROM vw_reservas_financeiro
     WHERE data_reserva BETWEEN ? AND ?" . $filtroReserva . '
     GROUP BY status',
    $parametrosReserva
);

$ordemStatus = ['confirmada' => 0, 'pendente' => 1, 'cancelada' => 2];
usort($porStatus, static fn (array $a, array $b): int => ($ordemStatus[$a['status']] ?? 9) <=> ($ordemStatus[$b['status']] ?? 9));
$maiorStatus = max(array_column($porStatus, 'qtd') ?: [0]) ?: 1;

$ocupacao = db_all(
    'SELECT vw.*, ROUND((vw.lugares_ocupados / NULLIF(vw.capacidade, 0)) * 100) AS percentual
     FROM vw_viagens_resumo vw
     WHERE vw.data_ida BETWEEN ? AND ? AND vw.status <> \'cancelada\'' . ($destinoId > 0 ? ' AND vw.destino_id = ?' : '') . '
     ORDER BY percentual DESC, vw.data_ida ASC
     LIMIT 8',
    $parametrosReserva
);

$recentes = db_all(
    "SELECT * FROM vw_reservas_financeiro
     WHERE data_reserva BETWEEN ? AND ?" . $filtroReserva . '
     ORDER BY criado_em DESC, id DESC
     LIMIT 8',
    $parametrosReserva
);

$kpis = [
    'receita'      => (float) $receitaResumo['total'],
    'recebimentos' => (int) $receitaResumo['recebimentos'],
    'reservas'     => (int) $resumoReservas['ativas'],
    'canceladas'   => (int) $resumoReservas['canceladas'],
    'viajantes'    => (int) $resumoReservas['viajantes'],
    'contratado'   => (float) $resumoReservas['contratado'],
    'pago'         => (float) $resumoReservas['pago'],
    'saldo'        => (float) $resumoReservas['saldo'],
    'ticket'       => (int) $resumoReservas['ativas'] > 0
        ? (float) $resumoReservas['contratado'] / (int) $resumoReservas['ativas']
        : 0.0,
];

if ($acao === 'exportar') {
    $tipo = (string) query('tipo', 'receita');
    $nomeArquivo = 'meridiano-' . ($tipo === 'reservas' ? 'reservas' : 'receita') . '-' . $inicio . '-a-' . $fim . '.csv';

    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $nomeArquivo . '"');
    header('Cache-Control: no-store, no-cache, must-revalidate');

    $saida = fopen('php://output', 'w');
    if ($saida === false) {
        exit;
    }

    fwrite($saida, "\xEF\xBB\xBF");

    if ($tipo === 'reservas') {
        fputcsv($saida, [
            'Código', 'Data da reserva', 'Cliente', 'Destino', 'Pacote', 'Ida', 'Volta',
            'Viajantes', 'Valor total', 'Pago', 'Saldo', 'Situação',
        ], ';', '"', '');

        $dadosCsv = db_all(
            "SELECT * FROM vw_reservas_financeiro
             WHERE data_reserva BETWEEN ? AND ?" . $filtroReserva . '
             ORDER BY data_reserva ASC, codigo ASC',
            $parametrosReserva
        );

        foreach ($dadosCsv as $linha) {
            fputcsv($saida, [
                $linha['codigo'],
                data_br($linha['data_reserva']),
                $linha['cliente_nome'],
                $linha['destino_nome'],
                $linha['pacote_titulo'],
                data_br($linha['data_ida']),
                data_br($linha['data_volta']),
                (int) $linha['viajantes'],
                number_format((float) $linha['valor_total'], 2, ',', '.'),
                number_format((float) $linha['valor_pago'], 2, ',', '.'),
                number_format(max(0, (float) $linha['valor_total'] - (float) $linha['valor_pago']), 2, ',', '.'),
                status_reserva_label($linha['status']),
            ], ';', '"', '');
        }
    } else {
        fputcsv($saida, [
            'Data do pagamento', 'Reserva', 'Cliente', 'Destino', 'Pacote', 'Forma', 'Valor',
        ], ';', '"', '');

        $dadosCsv = db_all(
            'SELECT pg.data_pagamento, r.codigo, c.nome AS cliente_nome, d.nome AS destino_nome,
                    p.titulo AS pacote_titulo, pg.forma, pg.valor
             ' . $juncaoReceita . "
             WHERE pg.status = 'pago' AND pg.data_pagamento BETWEEN ? AND ?" . $filtroReceita . '
             ORDER BY pg.data_pagamento ASC, r.codigo ASC',
            $parametrosReceita
        );

        foreach ($dadosCsv as $linha) {
            fputcsv($saida, [
                data_br($linha['data_pagamento']),
                $linha['codigo'],
                $linha['cliente_nome'],
                $linha['destino_nome'],
                $linha['pacote_titulo'],
                forma_pagamento_label($linha['forma']),
                number_format((float) $linha['valor'], 2, ',', '.'),
            ], ';', '"', '');
        }
    }

    fclose($saida);
    exit;
}

render('relatorios/index', [
    'pageTitle'       => 'Relatórios',
    'inicio'          => $inicio,
    'inicioPadrao'    => $inicioPadrao,
    'fim'             => $fim,
    'destinoId'       => $destinoId,
    'destinoAtual'    => $destinoAtual,
    'destinos'        => $destinos,
    'kpis'            => $kpis,
    'meses'           => $meses,
    'maiorMes'        => $maiorMes,
    'graficoLimitado' => $graficoLimitado,
    'porForma'        => $porForma,
    'porDestino'      => $porDestino,
    'topClientes'     => $topClientes,
    'porStatus'       => $porStatus,
    'maiorStatus'     => $maiorStatus,
    'ocupacao'        => $ocupacao,
    'recentes'        => $recentes,
]);
