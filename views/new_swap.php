<?php

declare(strict_types=1);
?>
<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-body">
                <h1 class="h5 mb-3">Añadir trueque</h1>
                <form method="post" action="/swaps/new" class="d-grid gap-2">
                    <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
                    <label class="form-label mb-0">Qué tengo</label>
                    <textarea class="form-control" name="have_text" rows="3" required></textarea>
                    <label class="form-label mb-0 mt-2">Qué busco</label>
                    <textarea class="form-control" name="want_text" rows="3" required></textarea>
                    <button class="btn btn-primary mt-3" type="submit">Publicar</button>
                </form>
            </div>
        </div>
    </div>
</div>
