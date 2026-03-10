<?php
/**
 * Proyecto PRESUPUESTO - Vista de gestion de clasificaciones.
 */

if (!function_exists('cat_escape')) {
    function cat_escape($value)
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}
?>
<section class="page-header card">
    <div>
        <span class="title-chip"><i class="bi bi-tags"></i> Catalogo base</span>
        <h2>Clasificaciones</h2>
        <p class="muted">Organiza categorias para facilitar reportes y consultas.</p>
    </div>
</section>

<?php if (!empty($successMessage)) : ?>
    <div class="alert alert-success"><?php echo cat_escape($successMessage); ?></div>
<?php endif; ?>
<?php if (!empty($errorMessage)) : ?>
    <div class="alert alert-error"><?php echo cat_escape($errorMessage); ?></div>
<?php endif; ?>

<section class="grid-cards">
    <article class="card">
        <h3><i class="bi bi-plus-square"></i> Nueva clasificacion</h3>
        <form method="post" action="<?php echo cat_escape($baseUrl); ?>/index.php?route=clasificaciones" class="compact-form" novalidate>
            <input type="hidden" name="<?php echo cat_escape($csrfTokenName); ?>" value="<?php echo cat_escape($csrfToken); ?>">

            <label for="descripcion">Nombre</label>
            <input id="descripcion" name="descripcion" type="text" maxlength="80" required>

            <button class="btn btn-primary btn-inline" type="submit"><i class="bi bi-check2-circle"></i> Guardar</button>
        </form>
    </article>

    <article class="card">
        <h3><i class="bi bi-search"></i> Buscar clasificaciones</h3>
        <form method="get" action="<?php echo cat_escape($baseUrl); ?>/index.php" class="compact-form">
            <input type="hidden" name="route" value="clasificaciones">
            <label for="q">Filtro</label>
            <div class="search-row">
                <input id="q" name="q" type="text" value="<?php echo cat_escape($search); ?>" placeholder="Ejemplo: Transporte">
                <button class="btn btn-secondary btn-inline" type="submit"><i class="bi bi-funnel"></i> Buscar</button>
            </div>
        </form>
    </article>
</section>

<section class="card table-card">
    <div class="table-wrapper">
        <table class="table-professional js-data-table js-indexed-table" data-page-length="20">
            <thead>
            <tr>
                <th>#</th>
                <th>ID</th>
                <th>Descripcion</th>
            </tr>
            </thead>
            <tbody>
            <?php if (empty($records)) : ?>
                <tr>
                    <td colspan="3" class="muted">No hay registros para mostrar.</td>
                </tr>
            <?php else : ?>
                <?php foreach ($records as $record) : ?>
                    <tr>
                        <td></td>
                        <td><?php echo (int) $record['id']; ?></td>
                        <td><?php echo cat_escape($record['descripcion']); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
