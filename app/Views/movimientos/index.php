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
        return '$ ' . number_format((float) $value, 2, ',', '.');
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
        <table class="table-professional js-data-table" data-page-length="10">
            <thead>
            <tr>
                <th>Fecha</th>
                <th>Clasificacion</th>
                <th>Detalle</th>
                <th>Categoria</th>
                <th>Tipo</th>
                <th>Valor</th>
                <th>Usuario</th>
            </tr>
            </thead>
            <tbody>
            <?php if (empty($movimientos)) : ?>
                <tr>
                    <td colspan="7" class="muted">No hay movimientos registrados.</td>
                </tr>
            <?php else : ?>
                <?php foreach ($movimientos as $movement) : ?>
                    <tr>
                        <td><?php echo mov_escape($movement['fecha']); ?></td>
                        <td><?php echo mov_escape($movement['clasificacion'] !== null ? $movement['clasificacion'] : 'Sin clasificacion'); ?></td>
                        <td><?php echo mov_escape($movement['detalle']); ?></td>
                        <td><?php echo mov_escape($movement['gasto_costo']); ?></td>
                        <td><?php echo mov_escape($movement['tipo']); ?></td>
                        <td><?php echo mov_money($movement['valor']); ?></td>
                        <td><?php echo mov_escape($movement['usuario']); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
