<?php
require_once __DIR__ . '/config.php';

function db(): PDO
{
    static $pdo = null;
    if ($pdo) {
        return $pdo;
    }

    $dsn = sprintf('%s:Server=%s;Database=%s', DB_DRIVER, DB_HOST, DB_NAME);
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ];

    $pdo = new PDO($dsn, DB_USER, DB_PASSWORD, $options);
    return $pdo;
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function start_session(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

function current_user(): ?array
{
    start_session();
    return $_SESSION['user'] ?? null;
}

function require_login(array $roles = []): void
{
    $user = current_user();
    if (!$user) {
        header('Location: login.php');
        exit;
    }

    if ($roles && !in_array($user['role'], $roles, true)) {
        http_response_code(403);
        echo 'Access denied';
        exit;
    }
}

function log_access(int $userId, string $event): void
{
    $stmt = db()->prepare('INSERT INTO access_logs (user_id, event, ip_address, user_agent) VALUES (:uid, :event, :ip, :ua)');
    $stmt->execute([
        'uid' => $userId,
        'event' => $event,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
        'ua' => $_SERVER['HTTP_USER_AGENT'] ?? null,
    ]);
}

function fetch_menu_items(): array
{
    $stmt = db()->prepare('SELECT mi.id, mi.name, mi.price, mi.image_url, c.name AS category FROM menu_items mi JOIN categories c ON mi.category_id = c.id ORDER BY c.name, mi.name');
    $stmt->execute();
    return $stmt->fetchAll();
}

function format_money($amount): string
{
    return '₱' . number_format((float) $amount, 2);
}

function add_flash(string $type, string $message): void
{
    start_session();
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function get_flashes(): array
{
    start_session();
    $flashes = $_SESSION['flash'] ?? [];
    $_SESSION['flash'] = [];
    return $flashes;
}

