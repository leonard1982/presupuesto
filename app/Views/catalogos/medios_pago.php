<?php
/**
 * Proyecto PRESUPUESTO - Vista de gestion de medios de pago.
 */

if (!function_exists('medio_escape')) {
    function medio_escape($value)
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}
?>
<section class="page-header card">
    <div>
        <span class="title-chip"><i class="bi bi-credit-card-2-front"></i> Catalogo de pago</span>
        <h2>Medios de pago</h2>
        <p class="muted">Configura opciones para registrar pagos, transferencias y compras.</p>
    </div>
</section>

<?php if (!empty($successMessage)) : ?>
    <div class="alert alert-success"><?php echo medio_escape($successMessage); ?></div>
<?php endif; ?>
<?php if (!empty($errorMessage)) : ?>
    <div class="alert alert-error"><?php echo medio_escape($errorMessage); ?></div>
<?php endif; ?>

<section class="grid-cards">
    <article class="card">
        <h3><i class="bi bi-plus-square"></i> Nuevo medio</h3>
        <form method="post" action="<?php echo medio_escape($baseUrl); ?>/index.php?route=medios-pago" class="compact-form" novalidate>
            <input type="hidden" name="<?php echo medio_escape($csrfTokenName); ?>" value="<?php echo medio_escape($csrfToken); ?>">

            <label for="medio">Nombre</label>
            <input id="medio" name="medio" type="text" maxlength="80" required>

            <button class="btn btn-primary btn-inline" type="submit"><i class="bi bi-check2-circle"></i> Guardar</button>
        </form>
    </article>

    <article class="card">
        <h3><i class="bi bi-search"></i> Buscar medios</h3>
        <form method="get" action="<?php echo medio_escape($baseUrl); ?>/index.php" class="compact-form">
            <input type="hidden" name="route" value="medios-pago">
            <label for="q">Filtro</label>
            <div class="search-row">
                <input id="q" name="q" type="text" value="<?php echo medio_escape($search); ?>" placeholder="Ejemplo: Tarjeta credito">
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
                <th>Medio</th>
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
                        <td><?php echo medio_escape($record['medio']); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
