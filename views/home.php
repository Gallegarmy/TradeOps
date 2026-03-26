<?php

declare(strict_types=1);
?>
<div class="d-flex justify-content-between align-items-center mb-3 gap-2 flex-wrap">
    <h1 class="h4 m-0">Trueques publicados</h1>
    <form method="get" action="/" class="d-flex gap-2" id="searchForm">
        <input
            id="searchInput"
            class="form-control"
            type="search"
            name="q"
            placeholder="Buscar por tengo, busco o usuario"
            value="<?= h($search ?? '') ?>"
        >
        <button class="btn btn-outline-primary" type="submit">Buscar</button>
    </form>
</div>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="bg-white p-3 rounded border kanban-col">
            <h2 class="h5 mb-3">Tengo</h2>
            <?php if (empty($posts)): ?>
                <p class="text-muted m-0">No hay publicaciones.</p>
            <?php endif; ?>
            <?php foreach ($posts as $post): ?>
                <div class="card mb-2 card-post">
                    <div class="card-body">
                        <p class="mb-1"><strong><?= h((string) $post['username']) ?></strong></p>
                        <p class="mb-1"><span class="badge text-bg-primary">Tengo</span> <?= h((string) $post['have_text']) ?></p>
                        <p class="mb-2"><span class="badge text-bg-success">Busco</span> <?= h((string) $post['want_text']) ?></p>
                        <p class="small text-muted mb-2">Reputación: +<?= (int) $post['positive_count'] ?> / -<?= (int) $post['negative_count'] ?></p>
                        <?php if (current_user_id() !== null && current_user_id() !== (int) $post['user_id']): ?>
                            <form method="post" action="/offers/create">
                                <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
                                <input type="hidden" name="post_id" value="<?= (int) $post['id'] ?>">
                                <button class="btn btn-sm btn-outline-primary" type="submit">Solicitar trueque</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="bg-white p-3 rounded border kanban-col">
            <h2 class="h5 mb-3">Busco</h2>
            <?php if (empty($posts)): ?>
                <p class="text-muted m-0">No hay publicaciones.</p>
            <?php endif; ?>
            <?php foreach ($posts as $post): ?>
                <div class="card mb-2 card-want">
                    <div class="card-body">
                        <p class="mb-1"><strong><?= h((string) $post['username']) ?></strong></p>
                        <p class="mb-1"><span class="badge text-bg-success">Busco</span> <?= h((string) $post['want_text']) ?></p>
                        <p class="mb-2"><span class="badge text-bg-primary">Tengo</span> <?= h((string) $post['have_text']) ?></p>
                        <p class="small text-muted mb-0">Publicado: <?= h((string) $post['created_at']) ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php if (current_user_id() !== null): ?>
    <section class="mt-4">
        <h2 class="h5 mb-3">Mis solicitudes y trueques</h2>
        <div class="table-responsive bg-white border rounded">
            <table class="table table-sm align-middle mb-0">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Dueño</th>
                    <th>Solicitante</th>
                    <th>Publicación</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
                </thead>
                <tbody>
                <?php if (empty($myOffers)): ?>
                    <tr><td colspan="6" class="text-muted">Sin solicitudes todavía.</td></tr>
                <?php endif; ?>
                <?php foreach ($myOffers as $offer): ?>
                    <?php
                    $isOwner = current_user_id() === (int) $offer['owner_user_id'];
                    $completionCount = (int) $offer['completion_count'];
                    $canRate = $offer['status'] === 'accepted' && $completionCount >= 2 && !(bool) $offer['rated_by_me'];
                    ?>
                    <tr>
                        <td>#<?= (int) $offer['id'] ?></td>
                        <td><?= h((string) $offer['owner_username']) ?></td>
                        <td><?= h((string) $offer['requester_username']) ?></td>
                        <td>
                            <div><strong>Tengo:</strong> <?= h((string) $offer['have_text']) ?></div>
                            <div><strong>Busco:</strong> <?= h((string) $offer['want_text']) ?></div>
                        </td>
                        <td>
                            <span class="badge text-bg-secondary"><?= h((string) $offer['status']) ?></span>
                            <?php if ($offer['status'] === 'accepted'): ?>
                                <div class="small text-muted mt-1">Confirmaciones: <?= $completionCount ?>/2</div>
                            <?php endif; ?>
                        </td>
                        <td class="d-flex gap-1 flex-wrap">
                            <?php if ($isOwner && $offer['status'] === 'pending'): ?>
                                <form method="post" action="/offers/<?= (int) $offer['id'] ?>/accept">
                                    <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
                                    <button class="btn btn-sm btn-success" type="submit">Aceptar</button>
                                </form>
                                <form method="post" action="/offers/<?= (int) $offer['id'] ?>/reject">
                                    <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
                                    <button class="btn btn-sm btn-outline-danger" type="submit">Rechazar</button>
                                </form>
                            <?php endif; ?>

                            <?php if ($offer['status'] === 'accepted' && !(bool) $offer['completed_by_me']): ?>
                                <form method="post" action="/offers/<?= (int) $offer['id'] ?>/complete">
                                    <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
                                    <button class="btn btn-sm btn-primary" type="submit">Marcar completado</button>
                                </form>
                            <?php endif; ?>

                            <?php if ($canRate): ?>
                                <form method="post" action="/offers/<?= (int) $offer['id'] ?>/rate">
                                    <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
                                    <input type="hidden" name="rating" value="positive">
                                    <button class="btn btn-sm btn-outline-success" type="submit">+ Positivo</button>
                                </form>
                                <form method="post" action="/offers/<?= (int) $offer['id'] ?>/rate">
                                    <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
                                    <input type="hidden" name="rating" value="negative">
                                    <button class="btn btn-sm btn-outline-danger" type="submit">- Negativo</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
<?php endif; ?>
