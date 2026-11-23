<?php
declare(strict_types=1);

session_start();
$config = require __DIR__ . '/config.php';

// Una conexión compartida y consultas parametrizadas para todas las páginas.
function db(): PDO
{
    static $pdo;
    global $config;
    if (!isset($pdo)) {
        $dsn = 'mysql:host=' . $config['db_host'] . ';dbname=' . $config['db_name'] . ';charset=utf8mb4';
        $pdo = new PDO($dsn, $config['db_user'], $config['db_pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }
    return $pdo;
}

function url(string $route = ''): string
{
    global $config;
    return $config['base_url'] . '/index.php' . ($route !== '' ? '?r=' . rawurlencode($route) : '');
}

function asset(string $path): string
{
    global $config;
    return $config['base_url'] . '/' . ltrim($path, '/');
}

function h(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function require_role(string ...$roles): void
{
    if (!user()) {
        header('Location: ' . url('login'));
        exit;
    }
    if ($roles && !in_array(user()['rol'], $roles, true)) {
        http_response_code(403);
        render('Acceso denegado', '<div class="alert alert-danger">No tienes permiso para esta acción.</div>');
        exit;
    }
}

function csrf(): string
{
    return $_SESSION['csrf'] ??= bin2hex(random_bytes(32));
}

function check_csrf(): void
{
    if (!hash_equals(csrf(), (string) ($_POST['csrf'] ?? ''))) {
        http_response_code(419);
        exit('Formulario caducado. Recarga la página e inténtalo de nuevo.');
    }
}

function post_only(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        exit('Método no permitido');
    }
    check_csrf();
}

function go(string $route, string $message = ''): never
{
    if ($message !== '') $_SESSION['flash'] = $message;
    header('Location: ' . url($route));
    exit;
}

function rows(string $sql, array $params = []): array
{
    $s = db()->prepare($sql);
    $s->execute($params);
    return $s->fetchAll();
}

function one(string $sql, array $params = []): ?array
{
    $s = db()->prepare($sql);
    $s->execute($params);
    return $s->fetch() ?: null;
}

function money(mixed $amount): string
{
    return '$' . number_format((float) $amount, 2, '.', ',');
}

function render(string $title, string $body): void
{
    $current = user();
    $flash = $_SESSION['flash'] ?? '';
    unset($_SESSION['flash']);
    $nav = [
        ['inicio', 'Inicio'], ['menu', 'Menú'],
        ['pedidos', 'Pedidos'], ['clientes', 'Clientes'],
        ['productos', 'Productos'], ['finanzas', 'Finanzas'], ['usuarios', 'Usuarios'],
    ];
    ?>
    <!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?= h($title) ?> · Pizzería 34</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= h(asset('assets/app.css')) ?>" rel="stylesheet"></head><body>
    <nav class="navbar navbar-expand-lg navbar-dark"><div class="container">
    <a class="navbar-brand fw-bold" href="<?= h(url('inicio')) ?>">🍕 Pizzería 34</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav"><span class="navbar-toggler-icon"></span></button>
    <div class="collapse navbar-collapse" id="nav"><div class="navbar-nav me-auto">
    <?php foreach ($nav as [$route, $label]):
        if (in_array($route, ['pedidos','clientes'], true) && !$current) continue;
        if ($route === 'productos' && !in_array($current['rol'] ?? '', ['Administrador'], true)) continue;
        if (in_array($route, ['finanzas','usuarios'], true) && ($current['rol'] ?? '') !== 'Administrador') continue;
        if ($route === 'clientes' && ($current['rol'] ?? '') === 'Cliente') continue;
    ?><a class="nav-link" href="<?= h(url($route)) ?>"><?= h($label) ?></a><?php endforeach; ?>
    </div><div class="navbar-nav align-items-lg-center gap-lg-2">
    <?php if ($current): ?><span class="navbar-text small"><?= h($current['nombre']) ?> · <?= h($current['rol']) ?></span>
    <form method="post" action="<?= h(url('salir')) ?>"><input type="hidden" name="csrf" value="<?= h(csrf()) ?>"><button class="btn btn-outline-light btn-sm">Salir</button></form>
    <?php else: ?><a class="btn btn-outline-light btn-sm" href="<?= h(url('login')) ?>">Ingresar</a><a class="btn btn-warning btn-sm" href="<?= h(url('registro')) ?>">Crear cuenta</a><?php endif; ?>
    </div></div></div></nav>
    <main class="container py-4 py-lg-5"><div class="d-flex align-items-center justify-content-between mb-4"><h1 class="h2 m-0"><?= h($title) ?></h1></div>
    <?php if ($flash): ?><div class="alert alert-info alert-dismissible fade show" role="alert"><?= h($flash) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
    <?= $body ?></main><footer class="container py-4 text-secondary small">Pizzería 34 · Proyecto académico en PHP y MySQL</footer>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script><script src="<?= h(asset('assets/app.js')) ?>"></script></body></html>
    <?php
}

function page(string $title, callable $content): void
{
    ob_start();
    $content();
    render($title, (string) ob_get_clean());
}

function fail(string $message, string $route): never
{
    go($route, $message);
}
