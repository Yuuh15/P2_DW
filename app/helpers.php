<?php

declare(strict_types=1);

// ------------------------------------------------------------
// Saída, URLs e navegação
// ------------------------------------------------------------

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function base_url(): string
{
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/index.php');
    $base = rtrim(dirname($script), '/.');

    return $base === '/' ? '' : $base;
}

function url(string $rota = 'dashboard', array $params = []): string
{
    $params = array_merge(['r' => $rota], $params);

    return base_url() . '/index.php?' . http_build_query($params);
}

function asset(string $path): string
{
    return base_url() . '/' . ltrim($path, '/');
}

function redirect(string $rota = 'dashboard', array $params = []): never
{
    header('Location: ' . url($rota, $params));
    exit;
}

function redirect_com_flash(string $rota, string $tipo, string $mensagem, array $params = []): never
{
    flash($tipo, $mensagem);
    redirect($rota, $params);
}

function request_method(): string
{
    return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
}

function is_post(): bool
{
    return request_method() === 'POST';
}

function input(string $campo, mixed $default = ''): mixed
{
    $valor = $_POST[$campo] ?? $default;

    return is_string($valor) ? trim($valor) : $valor;
}

function query(string $campo, mixed $default = ''): mixed
{
    $valor = $_GET[$campo] ?? $default;

    return is_string($valor) ? trim($valor) : $valor;
}

function pagina_atual(): int
{
    return max(1, (int) ($_GET['pagina'] ?? 1));
}

/**
 * Renderiza uma view dentro de um layout.
 */
function render(string $view, array $dados = [], string $layout = 'layout'): void
{
    $arquivo = VIEWS_PATH . '/' . $view . '.php';

    if (!is_file($arquivo)) {
        http_response_code(500);
        exit('View não encontrada: ' . e($view));
    }

    extract($dados, EXTR_SKIP);
    $conteudo = null;

    ob_start();
    include $arquivo;
    $conteudo = ob_get_clean();

    include VIEWS_PATH . '/' . $layout . '.php';
}

function abortar(int $codigo): never
{
    http_response_code($codigo);
    render('erros/' . $codigo);
    exit;
}

// ------------------------------------------------------------
// Sessão, CSRF e mensagens
// ------------------------------------------------------------

function flash(string $tipo, string $mensagem): void
{
    $_SESSION['flashes'][] = ['tipo' => $tipo, 'mensagem' => $mensagem];
}

function flashes(): array
{
    $mensagens = $_SESSION['flashes'] ?? [];
    unset($_SESSION['flashes']);

    return $mensagens;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): void
{
    $enviado = (string) ($_POST['_token'] ?? '');
    $esperado = (string) ($_SESSION['csrf_token'] ?? '');

    if ($esperado === '' || $enviado === '' || !hash_equals($esperado, $enviado)) {
        abortar(419);
    }
}

// ------------------------------------------------------------
// Formatação
// ------------------------------------------------------------

function money(float|int|string|null $valor): string
{
    $numero = (float) ($valor ?? 0);
    $formatado = number_format(abs($numero), 2, ',', '.');

    return ($numero < 0 ? '−' : '') . 'R$ ' . $formatado;
}

function valor_input(float|int|string|null $valor): string
{
    return number_format((float) ($valor ?? 0), 2, '.', '');
}

function data_br(?string $data): string
{
    if ($data === null || $data === '') {
        return '—';
    }

    $dt = DateTimeImmutable::createFromFormat('!Y-m-d', substr($data, 0, 10));

    return $dt ? $dt->format('d/m/Y') : '—';
}

function data_curta(?string $data): string
{
    if ($data === null || $data === '') {
        return '—';
    }

    $dt = DateTimeImmutable::createFromFormat('!Y-m-d', substr($data, 0, 10));

    return $dt ? $dt->format('d.m.Y') : '—';
}

function data_hora(?string $data): string
{
    if ($data === null || $data === '') {
        return '—';
    }

    $dt = new DateTimeImmutable($data);

    return $dt->format('d/m/Y \à\s H\hi');
}

function data_extenso(?string $data = null): string
{
    $dt = $data ? new DateTimeImmutable($data) : new DateTimeImmutable();

    $dias = [
        'Sunday' => 'domingo', 'Monday' => 'segunda-feira', 'Tuesday' => 'terça-feira',
        'Wednesday' => 'quarta-feira', 'Thursday' => 'quinta-feira',
        'Friday' => 'sexta-feira', 'Saturday' => 'sábado',
    ];
    $meses = [
        1 => 'janeiro', 'fevereiro', 'março', 'abril', 'maio', 'junho',
        'julho', 'agosto', 'setembro', 'outubro', 'novembro', 'dezembro',
    ];

    return $dias[$dt->format('l')] . ', ' . (int) $dt->format('d') . ' de '
        . $meses[(int) $dt->format('n')] . ' de ' . $dt->format('Y');
}

function dia(?string $data): string
{
    $dt = $data ? DateTimeImmutable::createFromFormat('!Y-m-d', substr($data, 0, 10)) : null;

    return $dt ? $dt->format('d') : '—';
}

function mes_abreviado(?string $data): string
{
    $meses = [
        1 => 'JAN', 'FEV', 'MAR', 'ABR', 'MAI', 'JUN',
        'JUL', 'AGO', 'SET', 'OUT', 'NOV', 'DEZ',
    ];
    $dt = $data ? DateTimeImmutable::createFromFormat('!Y-m-d', substr($data, 0, 10)) : null;

    return $dt ? $meses[(int) $dt->format('n')] : '—';
}

function nome_mes(int $mes): string
{
    $meses = [
        1 => 'janeiro', 'fevereiro', 'março', 'abril', 'maio', 'junho',
        'julho', 'agosto', 'setembro', 'outubro', 'novembro', 'dezembro',
    ];

    return $meses[$mes] ?? '';
}

function iniciais(?string $nome): string
{
    $partes = preg_split('/\s+/', trim((string) $nome)) ?: [];
    $partes = array_values(array_filter($partes));

    if (count($partes) === 0) {
        return '?';
    }

    $primeira = mb_substr($partes[0], 0, 1);
    $ultima = count($partes) > 1 ? mb_substr(end($partes), 0, 1) : '';

    return mb_strtoupper($primeira . $ultima);
}

function formatar_cpf(?string $cpf): string
{
    $numeros = preg_replace('/\D/', '', (string) $cpf);

    if (strlen($numeros) !== 11) {
        return $cpf ?: '—';
    }

    return substr($numeros, 0, 3) . '.' . substr($numeros, 3, 3) . '.'
        . substr($numeros, 6, 3) . '-' . substr($numeros, 9, 2);
}

function formatar_telefone(?string $telefone): string
{
    $numeros = preg_replace('/\D/', '', (string) $telefone);

    if (strlen($numeros) === 11) {
        return '(' . substr($numeros, 0, 2) . ') ' . substr($numeros, 2, 5)
            . '-' . substr($numeros, 7, 4);
    }

    if (strlen($numeros) === 10) {
        return '(' . substr($numeros, 0, 2) . ') ' . substr($numeros, 2, 4)
            . '-' . substr($numeros, 6, 4);
    }

    return $telefone ?: '—';
}

function resumir(?string $texto, int $limite = 90): string
{
    $texto = trim((string) $texto);

    if (mb_strlen($texto) <= $limite) {
        return $texto;
    }

    return rtrim(mb_substr($texto, 0, $limite - 1)) . '…';
}

// ------------------------------------------------------------
// Status e rótulos de domínio
// ------------------------------------------------------------

function status_viagem_label(string $status): string
{
    return [
        'planejada'    => 'Planejada',
        'confirmada'   => 'Confirmada',
        'em_andamento' => 'Em andamento',
        'concluida'    => 'Concluída',
        'cancelada'    => 'Cancelada',
    ][$status] ?? ucfirst($status);
}

function status_reserva_label(string $status): string
{
    return [
        'pendente'  => 'Pendente',
        'confirmada' => 'Confirmada',
        'cancelada' => 'Cancelada',
    ][$status] ?? ucfirst($status);
}

function status_pagamento_label(string $status): string
{
    return [
        'pendente'  => 'Pendente',
        'pago'      => 'Pago',
        'estornado' => 'Estornado',
    ][$status] ?? ucfirst($status);
}

function forma_pagamento_label(string $forma): string
{
    return [
        'pix'           => 'Pix',
        'cartao'        => 'Cartão',
        'boleto'        => 'Boleto',
        'transferencia' => 'Transferência',
    ][$forma] ?? ucfirst($forma);
}

function badge_tom(string $status): string
{
    return match ($status) {
        'confirmada', 'pago', 'concluida' => 'success',
        'pendente', 'em_andamento', 'planejada' => 'warning',
        'cancelada', 'estornado' => 'danger',
        default => 'neutral',
    };
}

/**
 * Situação operacional exibida nas partidas: combina o status
 * gravado com a proximidade da data de embarque.
 */
function situacao_viagem(array $viagem): array
{
    $status = $viagem['status'] ?? 'planejada';
    $hoje = new DateTimeImmutable('today');
    $ida = DateTimeImmutable::createFromFormat('!Y-m-d', substr((string) $viagem['data_ida'], 0, 10));
    $volta = DateTimeImmutable::createFromFormat('!Y-m-d', substr((string) $viagem['data_volta'], 0, 10));

    if ($status === 'cancelada') {
        return ['label' => 'Cancelada', 'tom' => 'danger'];
    }
    if ($status === 'concluida' || ($volta && $volta < $hoje)) {
        return ['label' => 'Concluída', 'tom' => 'neutral'];
    }
    if ($ida && $ida <= $hoje && (!$volta || $volta >= $hoje)) {
        return ['label' => 'Em andamento', 'tom' => 'warning'];
    }
    if ($ida && $ida->diff($hoje)->days <= 7 && $ida >= $hoje) {
        return ['label' => 'Embarque próximo', 'tom' => 'accent'];
    }

    return [
        'label' => status_viagem_label($status),
        'tom'   => $status === 'confirmada' ? 'success' : 'warning',
    ];
}

// ------------------------------------------------------------
// Validação
// ------------------------------------------------------------

function apenas_numeros(?string $valor): string
{
    return preg_replace('/\D+/', '', (string) $valor) ?? '';
}

function cpf_valido(?string $cpf): bool
{
    $cpf = apenas_numeros($cpf);

    if (strlen($cpf) !== 11 || preg_match('/^(\d)\1{10}$/', $cpf)) {
        return false;
    }

    for ($tamanho = 9; $tamanho < 11; $tamanho++) {
        $soma = 0;
        for ($i = 0; $i < $tamanho; $i++) {
            $soma += (int) $cpf[$i] * (($tamanho + 1) - $i);
        }
        $digito = ($soma * 10) % 11;
        $digito = $digito === 10 ? 0 : $digito;

        if ((int) $cpf[$tamanho] !== $digito) {
            return false;
        }
    }

    return true;
}

function data_valida(?string $data): bool
{
    if ($data === null || $data === '') {
        return false;
    }

    $dt = DateTimeImmutable::createFromFormat('!Y-m-d', $data);

    return $dt !== false && $dt->format('Y-m-d') === $data;
}

function email_valido(?string $email): bool
{
    return filter_var((string) $email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Valida um upload de imagem de destino e devolve o caminho relativo.
 *
 * @return array{0: ?string, 1: ?string} [caminho, erro]
 */
function upload_imagem_destino(string $campo, ?string $atual = null): array
{
    if (!isset($_FILES[$campo]) || ($_FILES[$campo]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return [$atual, null];
    }

    $arquivo = $_FILES[$campo];
    $config = $GLOBALS['config']['uploads'];

    if ($arquivo['error'] !== UPLOAD_ERR_OK) {
        return [null, 'Não foi possível enviar a imagem. Tente novamente.'];
    }

    if (($arquivo['size'] ?? 0) > $config['max_size']) {
        return [null, 'A imagem deve ter no máximo 4 MB.'];
    }

    $info = @getimagesize($arquivo['tmp_name']);

    if ($info === false) {
        return [null, 'O arquivo enviado não é uma imagem válida.'];
    }

    $permitidos = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
    ];

    if (!isset($permitidos[$info['mime']])) {
        return [null, 'Envie uma imagem JPG, PNG ou WebP.'];
    }

    if (!is_dir($config['destinos']) && !mkdir($config['destinos'], 0775, true) && !is_dir($config['destinos'])) {
        return [null, 'Não foi possível preparar a pasta de imagens.'];
    }

    $nome = 'destino-' . date('YmdHis') . '-' . bin2hex(random_bytes(4)) . '.' . $permitidos[$info['mime']];
    $destino = rtrim($config['destinos'], '/') . '/' . $nome;

    if (!move_uploaded_file($arquivo['tmp_name'], $destino)) {
        return [null, 'Não foi possível salvar a imagem enviada.'];
    }

    return [$config['url'] . '/' . $nome, null];
}

// ------------------------------------------------------------
// Paginação
// ------------------------------------------------------------

function paginar(int $total, int $porPagina = 10): array
{
    $paginas = max(1, (int) ceil($total / $porPagina));
    $pagina = min(max(1, pagina_atual()), $paginas);

    return [
        'total'      => $total,
        'por_pagina' => $porPagina,
        'pagina'     => $pagina,
        'paginas'    => $paginas,
        'offset'     => ($pagina - 1) * $porPagina,
        'inicio'     => $total === 0 ? 0 : (($pagina - 1) * $porPagina) + 1,
        'fim'        => min($pagina * $porPagina, $total),
    ];
}

/**
 * Gera a URL da página mantendo filtros atuais.
 */
function url_com_pagina(int $pagina): string
{
    $params = $_GET;
    $params['pagina'] = $pagina;

    $rota = $params['r'] ?? 'dashboard';
    unset($params['r']);

    return url($rota, $params);
}
