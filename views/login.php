<?php

declare(strict_types=1);
?>
<div class="row justify-content-center">
    <div class="col-md-6 col-lg-4">
        <div class="card">
            <div class="card-body">
                <h1 class="h5 mb-3">Iniciar sesión</h1>
                <form method="post" action="/login" class="d-grid gap-2">
                    <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
                    <label class="form-label mb-0">Usuario</label>
                    <input class="form-control" type="text" name="username" required>
                    <label class="form-label mb-0 mt-2">Contraseña</label>
                    <input class="form-control" type="password" name="password" required>
                    <button class="btn btn-primary mt-3" type="submit">Entrar</button>
                </form>
            </div>
        </div>
    </div>
</div>
