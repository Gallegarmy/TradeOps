<?php

declare(strict_types=1);
?><!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Trueque Simple</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f3f5f7; }
        .kanban-col { min-height: 320px; }
        .card-post { border-left: 4px solid #0d6efd; }
        .card-want { border-left: 4px solid #198754; }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg bg-body-tertiary border-bottom">
    <div class="container">
        <a class="navbar-brand fw-semibold" href="/">Trueque</a>
        <div class="ms-auto d-flex gap-2 align-items-center">
            <?php if ($user): ?>
                <span class="text-muted small">Hola, <?= h((string) $user['username']) ?></span>
                <a class="btn btn-sm btn-primary" href="/swaps/new">Añadir trueque</a>
                <form method="post" action="/logout" class="d-inline">
                    <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
                    <button class="btn btn-sm btn-outline-secondary" type="submit">Salir</button>
                </form>
            <?php else: ?>
                <a class="btn btn-sm btn-outline-primary" href="/login">Entrar</a>
                <a class="btn btn-sm btn-primary" href="/register">Registro</a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<main class="container py-4">
    <?php foreach ($flashes as $flash): ?>
        <?php
        $type = in_array($flash['type'], ['success', 'danger', 'warning', 'info'], true) ? $flash['type'] : 'secondary';
        ?>
        <div class="alert alert-<?= h($type) ?>"> <?= h((string) $flash['message']) ?> </div>
    <?php endforeach; ?>

    <?php require $viewPath; ?>
</main>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script>
    $(function () {
        $('#searchInput').on('input', function () {
            const value = $(this).val();
            if (value === '') {
                $('#searchForm').submit();
            }
        });
    });
</script>
</body>
</html>
