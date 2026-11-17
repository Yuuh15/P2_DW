<?php

declare(strict_types=1);

$hoje = new DateTimeImmutable('today');
$inicioMes = new DateTimeImmutable('first day of this month');
$fimMes = new DateTimeImmutable('last day of this month');

$receitaMes = (float) db_value(
    "SELECT COALESCE(SUM(valor), 0) FROM pagamentos
     WHERE status = 'pago' AND data_pagamento BETWEEN ? AND ?",
    [$inicioMes->format('Y-m-d'), $fimMes->format('Y-m-d')],
    0
);

$inicioMesAnterior = $inicioMes->modify('-1 month');
$fimMesAnterior = $inicioMes->modify('-1 day');
$receitaMesAnterior = (float) db_value(
    "SELECT COALESCE(SUM(valor), 0) FROM pagamentos
     WHERE status = 'pago' AND data_pagamento BETWEEN ? AND ?",
    [$inicioMesAnterior->format('Y-m-d'), $fimMesAnterior->format('Y-m-d')],
    0
);

$variacaoReceita = $receitaMesAnterior > 0
    ? (($receitaMes - $receitaMesAnterior) / $receitaMesAnterior) * 100
    : null;

$pagamentosRecebidosMes = (int) db_value(
    "SELECT COUNT(*) FROM pagamentos
     WHERE status = 'pago' AND data_pagamento BETWEEN ? AND ?",
    [$inicioMes->format('Y-m-d'), $fimMes->format('Y-m-d')],
    0
);

$reservasAtivas = (int) db_value("SELECT COUNT(*) FROM reservas WHERE status IN ('pendente', 'confirmada')", [], 0);
$reservasPendentes = (int) db_value("SELECT COUNT(*) FROM reservas WHERE status = 'pendente'", [], 0);

$viajantesConfirmados = (int) db_value(
    "SELECT COALESCE(SUM(r.viajantes), 0)
     FROM reservas r
     INNER JOIN viagens v ON v.id = r.viagem_id
     WHERE r.status = 'confirmada' AND v.data_ida >= CURDATE() AND v.status <> 'cancelada'",
    [],
    0
);

$partidasAtivas = (int) db_value(
    "SELECT COUNT(*) FROM viagens
     WHERE status IN ('planejada', 'confirmada') AND data_ida >= CURDATE()",
    [],
    0
);

$aReceber = (float) db_value(
    "SELECT COALESCE(SUM(GREATEST(valor_total - valor_pago, 0)), 0)
     FROM vw_reservas_financeiro
     WHERE status IN ('pendente', 'confirmada')",
    [],
    0
);

$vencido = (float) db_value(
    "SELECT COALESCE(SUM(valor), 0) FROM pagamentos
     WHERE status = 'pendente' AND vencimento < CURDATE()",
    [],
    0
);

$qtdVencidos = (int) db_value(
    "SELECT COUNT(*) FROM pagamentos WHERE status = 'pendente' AND vencimento < CURDATE()",
    [],
    0
);

$proximasPartidas = db_all(
    "SELECT * FROM vw_viagens_resumo
     WHERE status <> 'cancelada' AND data_volta >= CURDATE()
     ORDER BY data_ida ASC
     LIMIT 6"
);

$pagamentosAtencao = db_all(
    "SELECT pg.id, pg.valor, pg.vencimento, r.id AS reserva_id, r.codigo,
            c.nome AS cliente_nome,
            DATEDIFF(CURDATE(), pg.vencimento) AS dias_atraso
     FROM pagamentos pg
     INNER JOIN reservas r ON r.id = pg.reserva_id
     INNER JOIN clientes c ON c.id = r.cliente_id
     WHERE pg.status = 'pendente' AND pg.vencimento < CURDATE()
     ORDER BY pg.vencimento ASC
     LIMIT 4"
);

$reservasRecentes = db_all(
    "SELECT * FROM vw_reservas_financeiro
     ORDER BY criado_em DESC, id DESC
     LIMIT 6"
);

// Receita dos últimos seis meses, incluindo meses sem movimento.
$primeiroMes = new DateTimeImmutable('first day of -5 months');
$receitaMensal = db_all(
    "SELECT DATE_FORMAT(data_pagamento, '%Y-%m') AS mes, SUM(valor) AS total
     FROM pagamentos
     WHERE status = 'pago' AND data_pagamento >= ?
     GROUP BY mes",
    [$primeiroMes->format('Y-m-d')]
);

$totaisPorMes = [];
foreach ($receitaMensal as $linha) {
    $totaisPorMes[$linha['mes']] = (float) $linha['total'];
}

$meses = [];
for ($i = 5; $i >= 0; $i--) {
    $mes = (new DateTimeImmutable('first day of this month'))->modify("-$i months");
    $chave = $mes->format('Y-m');
    $meses[] = [
        'chave'  => $chave,
        'label'  => mb_substr(nome_mes((int) $mes->format('n')), 0, 3),
        'total'  => $totaisPorMes[$chave] ?? 0.0,
        'atual'  => $i === 0,
    ];
}

$maiorReceita = max(array_column($meses, 'total')) ?: 1;
$totalSemestre = array_sum(array_column($meses, 'total'));

$hora = (int) date('G');
$saudacao = $hora < 12 ? 'Bom dia' : ($hora < 18 ? 'Boa tarde' : 'Boa noite');
$primeiroNome = explode(' ', (string) auth_user()['nome'])[0];

$qtdAtencao = count($pagamentosAtencao);
render('dashboard', [
    'pageTitle'           => 'Visão geral',
    'dashboard'           => [
        'receitaMes'          => $receitaMes,
        'receitaMesAnterior'  => $receitaMesAnterior,
        'variacaoReceita'     => $variacaoReceita,
        'pagamentosMes'       => $pagamentosRecebidosMes,
        'reservasAtivas'      => $reservasAtivas,
        'reservasPendentes'   => $reservasPendentes,
        'viajantesConfirmados' => $viajantesConfirmados,
        'partidasAtivas'      => $partidasAtivas,
        'aReceber'            => $aReceber,
        'vencido'             => $vencido,
        'qtdVencidos'         => $qtdVencidos,
        'mesAnterior'         => nome_mes((int) $inicioMesAnterior->format('n')),
        'saudacao'            => $saudacao,
        'primeiroNome'        => $primeiroNome,
    ],
    'proximasPartidas'  => $proximasPartidas,
    'pagamentosAtencao' => $pagamentosAtencao,
    'reservasRecentes'  => $reservasRecentes,
    'meses'             => $meses,
    'maiorReceita'      => $maiorReceita,
    'totalSemestre'     => $totalSemestre,
]);
