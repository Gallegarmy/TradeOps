<?php

declare(strict_types=1);

function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function redirect(string $path): never
{
    header('Location: ' . $path);
    exit;
}

function current_path(): string
{
    $uri = $_SERVER['REQUEST_URI'] ?? '/';

    return strtok($uri, '?') ?: '/';
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return (string) $_SESSION['csrf_token'];
}

function validate_csrf(): bool
{
    $token = $_POST['_csrf'] ?? '';

    return is_string($token) && hash_equals((string) ($_SESSION['csrf_token'] ?? ''), $token);
}

function flash(string $type, string $message): void
{
    $_SESSION['flashes'][] = ['type' => $type, 'message' => $message];
}

function pull_flashes(): array
{
    $flashes = $_SESSION['flashes'] ?? [];
    unset($_SESSION['flashes']);

    return is_array($flashes) ? $flashes : [];
}

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function current_user_id(): ?int
{
    $user = current_user();

    return $user ? (int) $user['id'] : null;
}

function require_login(): void
{
    if (current_user_id() === null) {
        flash('warning', 'Debes iniciar sesión para continuar.');
        redirect('/login');
    }
}

function render(string $view, array $data = []): void
{
    $viewPath = __DIR__ . '/../views/' . $view . '.php';

    if (!is_file($viewPath)) {
        http_response_code(500);
        echo 'Vista no encontrada';
        return;
    }

    extract($data, EXTR_SKIP);
    $flashes = pull_flashes();
    $user = current_user();

    require __DIR__ . '/../views/layout.php';
}
