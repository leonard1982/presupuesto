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
        <table class="table-professional js-data-table js-indexed-table js-exportable" data-page-length="20" data-export-name="movimientos_registro_operativo">
            <thead>
            <tr>
                <th class="no-export">#</th>
                <th>ID</th>
                <th>Fecha</th>
                <th>Clasificacion</th>
                <th>Detalle</th>
                <th>Categoria</th>
                <th>Tipo</th>
                <th>Valor</th>
                <th class="no-export">Soportes</th>
                <th>Usuario</th>
                <th class="no-export">Acciones</th>
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
                            <?php
                            $supportsPayload = array();
                            foreach ($supports as $support) {
                                $supportId = isset($support['support_id']) ? (int) $support['support_id'] : 0;
                                if ($supportId <= 0) {
                                    continue;
                                }

                                $originalName = isset($support['original_name']) ? (string) $support['original_name'] : '';
                                $supportsPayload[] = array(
                                    'id' => $supportId,
                                    'name' => $originalName,
                                    'url' => rtrim((string) $baseUrl, '/') . '/index.php?route=movimientos/soporte&id=' . (int) $movement['id'] . '&sid=' . $supportId,
                                );
                            }
                            $supportsPayloadJson = json_encode($supportsPayload, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
                            if ($supportsPayloadJson === false) {
                                $supportsPayloadJson = '[]';
                            }
                            ?>
                            <?php if (empty($supportsPayload)) : ?>
                                <span class="muted">Sin soporte</span>
                            <?php else : ?>
                                <button
                                    type="button"
                                    class="btn btn-ghost btn-inline btn-mini btn-icon-only supports-trigger js-open-supports-modal"
                                    title="Ver soportes"
                                    aria-label="Ver soportes"
                                    data-supports-json="<?php echo mov_escape($supportsPayloadJson); ?>"
                                    data-supports-title="Soportes del movimiento #<?php echo (int) $movement['id']; ?>">
                                    <i class="bi bi-paperclip"></i>
                                    <span class="supports-count-badge"><?php echo (int) count($supportsPayload); ?></span>
                                </button>
                            <?php endif; ?>
                        </td>
                        <td><?php echo mov_escape($movement['usuario']); ?></td>
                        <td>
                            <div class="table-actions-stack">
                                <a class="btn btn-ghost btn-inline btn-mini btn-icon-only" title="Ver ticket" aria-label="Ver ticket" href="<?php echo mov_escape($baseUrl); ?>/index.php?route=movimientos/ticket&id=<?php echo (int) $movement['id']; ?>" target="_blank">
                                    <i class="bi bi-receipt"></i>
                                </a>
                                <a class="btn btn-secondary btn-inline btn-mini btn-icon-only" title="Editar movimiento" aria-label="Editar movimiento" href="<?php echo mov_escape($baseUrl); ?>/index.php?route=movimientos/editar&id=<?php echo (int) $movement['id']; ?>">
                                    <i class="bi bi-pencil-square"></i>
                                </a>
                                <form method="post" action="<?php echo mov_escape($baseUrl); ?>/index.php?route=movimientos/eliminar" class="inline-form js-confirm-delete" data-confirm-message="Se eliminara este movimiento y sus soportes. Deseas continuar?">
                                    <input type="hidden" name="<?php echo mov_escape($csrfTokenName); ?>" value="<?php echo mov_escape($csrfToken); ?>">
                                    <input type="hidden" name="movement_id" value="<?php echo (int) $movement['id']; ?>">
                                    <button type="submit" class="btn btn-danger btn-inline btn-mini btn-icon-only" title="Eliminar movimiento" aria-label="Eliminar movimiento">
                                        <i class="bi bi-trash3"></i>
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

<div id="supports-modal" class="modal-overlay hidden" aria-hidden="true">
    <div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="supports-modal-title">
        <div class="modal-header">
            <h3 id="supports-modal-title"><i class="bi bi-paperclip"></i> Soportes</h3>
            <button type="button" class="btn btn-secondary btn-inline btn-mini btn-icon-only js-close-supports-modal" title="Cerrar" aria-label="Cerrar">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <div id="supports-modal-body" class="modal-body">
            <p class="muted">No hay soportes para mostrar.</p>
        </div>
    </div>
</div>
