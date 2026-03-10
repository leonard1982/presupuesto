<?php
/**
 * Proyecto PRESUPUESTO - Vista de listado de movimientos.
 */

if (!function_exists('mov_escape')) {
    function mov_escape($value)
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('mov_money')) {
    function mov_money($value)
    {
        return '$ ' . number_format((float) $value, 0, ',', '.');
    }
}
?>
<section class="page-header card">
    <div>
        <span class="title-chip"><i class="bi bi-receipt-cutoff"></i> Registro operativo</span>
        <h2>Movimientos</h2>
        <p class="muted">Gestiona gastos, costos y compras en un flujo rapido.</p>
    </div>
    <a class="btn btn-primary btn-inline" href="<?php echo mov_escape($baseUrl); ?>/index.php?route=movimientos/nuevo">
        <i class="bi bi-plus-circle"></i> Nuevo movimiento
    </a>
</section>

<?php if (!empty($successMessage)) : ?>
    <div class="alert alert-success"><?php echo mov_escape($successMessage); ?></div>
<?php endif; ?>
<?php if (!empty($errorMessage)) : ?>
    <div class="alert alert-error"><?php echo mov_escape($errorMessage); ?></div>
<?php endif; ?>

<section class="card table-card">
    <div class="table-wrapper">
        <table class="table-professional js-data-table js-indexed-table" data-page-length="20">
            <thead>
            <tr>
                <th>#</th>
                <th>ID</th>
                <th>Fecha</th>
                <th>Clasificacion</th>
                <th>Detalle</th>
                <th>Categoria</th>
                <th>Tipo</th>
                <th>Valor</th>
                <th>Soportes</th>
                <th>Usuario</th>
                <th>Acciones</th>
            </tr>
            </thead>
            <tbody>
            <?php if (empty($movimientos)) : ?>
                <tr>
                    <td colspan="11" class="muted">No hay movimientos registrados.</td>
                </tr>
            <?php else : ?>
                <?php foreach ($movimientos as $movement) : ?>
                    <?php $supports = isset($movement['supports']) && is_array($movement['supports']) ? $movement['supports'] : array(); ?>
                    <tr>
                        <td></td>
                        <td><?php echo (int) $movement['id']; ?></td>
                        <td><?php echo mov_escape($movement['fecha']); ?></td>
                        <td><?php echo mov_escape($movement['clasificacion'] !== null ? $movement['clasificacion'] : 'Sin clasificacion'); ?></td>
                        <td><?php echo mov_escape($movement['detalle']); ?></td>
                        <td><?php echo mov_escape($movement['gasto_costo']); ?></td>
                        <td><?php echo mov_escape($movement['tipo']); ?></td>
                        <td><?php echo mov_money($movement['valor']); ?></td>
                        <td>
                            <?php if (empty($supports)) : ?>
                                <span class="muted">Sin soportes</span>
                            <?php else : ?>
                                <div class="table-actions-stack">
                                    <?php foreach ($supports as $support) : ?>
                                        <?php
                                        $storedName = isset($support['stored_name']) ? (string) $support['stored_name'] : '';
                                        $originalName = isset($support['original_name']) ? (string) $support['original_name'] : $storedName;
                                        ?>
                                        <a class="btn btn-ghost btn-inline btn-mini" href="<?php echo mov_escape($baseUrl); ?>/index.php?route=movimientos/soporte&id=<?php echo (int) $movement['id']; ?>&file=<?php echo rawurlencode($storedName); ?>" target="_blank">
                                            <i class="bi bi-paperclip"></i> <?php echo mov_escape($originalName); ?>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td><?php echo mov_escape($movement['usuario']); ?></td>
                        <td>
                            <div class="table-actions-stack">
                                <a class="btn btn-secondary btn-inline btn-mini" href="<?php echo mov_escape($baseUrl); ?>/index.php?route=movimientos/editar&id=<?php echo (int) $movement['id']; ?>">
                                    <i class="bi bi-pencil-square"></i> Editar
                                </a>
                                <form method="post" action="<?php echo mov_escape($baseUrl); ?>/index.php?route=movimientos/eliminar" class="inline-form js-confirm-delete" data-confirm-message="Se eliminara este movimiento y sus soportes. Deseas continuar?">
                                    <input type="hidden" name="<?php echo mov_escape($csrfTokenName); ?>" value="<?php echo mov_escape($csrfToken); ?>">
                                    <input type="hidden" name="movement_id" value="<?php echo (int) $movement['id']; ?>">
                                    <button type="submit" class="btn btn-danger btn-inline btn-mini">
                                        <i class="bi bi-trash3"></i> Eliminar
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
