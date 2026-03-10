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

if (!function_exists('mov_build_supports_payload')) {
    function mov_build_supports_payload(array $supports, $baseUrl, $movementId)
    {
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
                'url' => rtrim((string) $baseUrl, '/') . '/index.php?route=movimientos/soporte&id=' . (int) $movementId . '&sid=' . $supportId,
            );
        }

        return $supportsPayload;
    }
}

if (!function_exists('mov_encode_json')) {
    function mov_encode_json($payload)
    {
        $jsonText = json_encode($payload, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
        return $jsonText === false ? '[]' : $jsonText;
    }
}

if (!function_exists('mov_filter_key')) {
    function mov_filter_key($value)
    {
        $text = trim((string) $value);
        if ($text === '') {
            return '';
        }

        if (function_exists('mb_strtolower')) {
            return mb_strtolower($text, 'UTF-8');
        }

        return strtolower($text);
    }
}

if (!function_exists('mov_register_option')) {
    function mov_register_option(array &$optionMap, $value)
    {
        $label = trim((string) $value);
        if ($label === '') {
            return;
        }

        $key = mov_filter_key($label);
        if ($key === '') {
            return;
        }

        $optionMap[$key] = $label;
    }
}

if (!function_exists('mov_date_only')) {
    function mov_date_only($value)
    {
        return substr(trim((string) $value), 0, 10);
    }
}

$movementTypesBase = array(
    'Compra',
    'Transferencia',
    'Pago proveedor',
    'Nomina',
    'Suscripcion',
    'Servicio',
    'Tarjeta',
    'Efectivo',
);

$clasificacionesCatalogo = isset($clasificacionesFiltro) && is_array($clasificacionesFiltro) ? $clasificacionesFiltro : array();
$mediosPagoCatalogo = isset($mediosPagoFiltro) && is_array($mediosPagoFiltro) ? $mediosPagoFiltro : array();

$clasificacionOptions = array();
foreach ($clasificacionesCatalogo as $clasificacionCatalogo) {
    mov_register_option($clasificacionOptions, isset($clasificacionCatalogo['descripcion']) ? $clasificacionCatalogo['descripcion'] : '');
}

$tipoOptions = array();
foreach ($movementTypesBase as $tipoBase) {
    mov_register_option($tipoOptions, $tipoBase);
}
foreach ($mediosPagoCatalogo as $medioCatalogo) {
    mov_register_option($tipoOptions, isset($medioCatalogo['medio']) ? $medioCatalogo['medio'] : '');
}

foreach ($movimientos as $movementItem) {
    mov_register_option($clasificacionOptions, isset($movementItem['clasificacion']) ? $movementItem['clasificacion'] : '');
    mov_register_option($tipoOptions, isset($movementItem['tipo']) ? $movementItem['tipo'] : '');
}

asort($clasificacionOptions, SORT_NATURAL | SORT_FLAG_CASE);
asort($tipoOptions, SORT_NATURAL | SORT_FLAG_CASE);
?>
<section class="page-header card">
    <div>
        <span class="title-chip"><i class="bi bi-receipt-cutoff"></i> Registro operativo</span>
        <h2>Movimientos</h2>
        <p class="muted">Gestiona ingresos, gastos, costos y compras en un flujo rapido.</p>
    </div>
    <a class="btn btn-primary btn-inline" href="<?php echo mov_escape($baseUrl); ?>/index.php?route=movimientos/nuevo">
        <i class="bi bi-plus-circle"></i> Nuevo movimiento
    </a>
</section>

<?php if (!empty($successMessage)) : ?>
    <div id="movement-save-modal" class="modal-overlay notification-overlay" aria-hidden="false">
        <div class="modal-card notification-card" role="dialog" aria-modal="true" aria-labelledby="movement-save-modal-title">
            <div class="modal-header notification-header">
                <h3 id="movement-save-modal-title"><i class="bi bi-check-circle-fill"></i> Registro guardado</h3>
                <button type="button" class="btn btn-secondary btn-inline btn-mini btn-icon-only js-close-notification-modal" title="Cerrar" aria-label="Cerrar">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
            <div class="modal-body notification-body">
                <p><?php echo mov_escape($successMessage); ?></p>
                <div class="notification-actions">
                    <button type="button" class="btn btn-primary btn-inline js-close-notification-modal">
                        <i class="bi bi-check2-circle"></i> Entendido
                    </button>
                    <a class="btn btn-secondary btn-inline" href="<?php echo mov_escape($baseUrl); ?>/index.php?route=movimientos/nuevo">
                        <i class="bi bi-plus-circle"></i> Nuevo movimiento
                    </a>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>
<?php if (!empty($errorMessage)) : ?>
    <div class="alert alert-error"><?php echo mov_escape($errorMessage); ?></div>
<?php endif; ?>

<section class="card movement-filters-card movement-filters-sticky">
    <div class="movement-filters-header">
        <h3><i class="bi bi-funnel"></i> Filtros de movimientos</h3>
        <button type="button" id="movement-filters-toggle" class="btn btn-ghost btn-inline btn-mini" aria-expanded="true">
            <i class="bi bi-funnel"></i> Ocultar filtros
        </button>
    </div>
    <div id="movement-filters-body">
    <div class="movement-filters-grid">
        <div class="form-field">
            <label for="movement-filter-date">Fecha</label>
            <input id="movement-filter-date" type="date" value="">
        </div>
        <div class="form-field">
            <label for="movement-filter-clasificacion">Clasificacion</label>
            <select id="movement-filter-clasificacion" class="js-searchable-select" data-placeholder="Todas las clasificaciones">
                <option value="">Todas</option>
                <?php foreach ($clasificacionOptions as $clasificacionOption) : ?>
                    <option value="<?php echo mov_escape($clasificacionOption); ?>"><?php echo mov_escape($clasificacionOption); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-field">
            <label for="movement-filter-categoria">Categoria</label>
            <select id="movement-filter-categoria">
                <option value="">Todas</option>
                <option value="Ingreso">Ingreso</option>
                <option value="Gasto">Gasto</option>
                <option value="Costo">Costo</option>
            </select>
        </div>
        <div class="form-field">
            <label for="movement-filter-tipo">Tipo o medio</label>
            <select id="movement-filter-tipo" class="js-searchable-select" data-placeholder="Todos los tipos o medios">
                <option value="">Todos</option>
                <?php foreach ($tipoOptions as $tipoOption) : ?>
                    <option value="<?php echo mov_escape($tipoOption); ?>"><?php echo mov_escape($tipoOption); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <div class="movement-filters-actions">
        <div class="movement-quick-date-buttons">
            <button type="button" class="btn btn-ghost btn-inline btn-mini js-movement-quick-date" data-range="today">
                <i class="bi bi-calendar-day"></i> Hoy
            </button>
            <button type="button" class="btn btn-ghost btn-inline btn-mini js-movement-quick-date" data-range="week">
                <i class="bi bi-calendar-week"></i> Semana
            </button>
            <button type="button" class="btn btn-ghost btn-inline btn-mini js-movement-quick-date" data-range="month">
                <i class="bi bi-calendar3"></i> Mes
            </button>
            <button type="button" class="btn btn-ghost btn-inline btn-mini js-movement-quick-date" data-range="all">
                <i class="bi bi-collection"></i> Todo
            </button>
        </div>
        <div class="movement-filters-actions-right">
            <a class="btn btn-primary btn-inline btn-mini" href="<?php echo mov_escape($baseUrl); ?>/index.php?route=movimientos/nuevo&modo=rapido">
                <i class="bi bi-lightning-charge"></i> Carga rapida
            </a>
            <a class="btn btn-secondary btn-inline btn-mini" href="<?php echo mov_escape($baseUrl); ?>/index.php?route=movimientos/nuevo&categoria=Ingreso">
                <i class="bi bi-cash-coin"></i> Registrar ingreso
            </a>
            <button type="button" id="movement-summary-toggle" class="btn btn-ghost btn-inline btn-mini" aria-expanded="true">
                <i class="bi bi-layout-sidebar-inset"></i> Ocultar panel
            </button>
            <span class="shortcut-chip"><i class="bi bi-keyboard"></i> Atajos: N, F, E</span>
        </div>
        <button type="button" id="movement-filter-reset" class="btn btn-secondary btn-inline btn-mini">
            <i class="bi bi-arrow-counterclockwise"></i> Limpiar filtros
        </button>
        <span id="movement-filter-result-info" class="muted"></span>
    </div>
    </div>
</section>

<section class="movement-workspace" id="movement-workspace">
    <div class="card table-card movement-list-card">
        <div class="table-wrapper movement-table-wrapper">
            <table class="table-professional table-movement-compact-index js-data-table js-indexed-table js-exportable js-movimientos-table" data-page-length="20" data-export-name="movimientos_registro_operativo" data-preference-key="movimientos_table_length">
                <thead>
                <tr>
                    <th class="no-export">#</th>
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
                        <td colspan="10" class="muted">No hay movimientos registrados.</td>
                    </tr>
                <?php else : ?>
                    <?php foreach ($movimientos as $movement) : ?>
                        <?php
                        $movementId = isset($movement['id']) ? (int) $movement['id'] : 0;
                        $supports = isset($movement['supports']) && is_array($movement['supports']) ? $movement['supports'] : array();
                        $supportsPayload = mov_build_supports_payload($supports, $baseUrl, $movementId);
                        $ticketUrl = rtrim((string) $baseUrl, '/') . '/index.php?route=movimientos/ticket&id=' . $movementId;
                        $editUrl = rtrim((string) $baseUrl, '/') . '/index.php?route=movimientos/editar&id=' . $movementId;
                        $movementDetailPayload = array(
                            'id' => $movementId,
                            'fecha' => isset($movement['fecha']) ? (string) $movement['fecha'] : '',
                            'clasificacion' => isset($movement['clasificacion']) && $movement['clasificacion'] !== null ? (string) $movement['clasificacion'] : 'Sin clasificacion',
                            'detalle' => isset($movement['detalle']) ? (string) $movement['detalle'] : '',
                            'categoria' => isset($movement['gasto_costo']) ? (string) $movement['gasto_costo'] : '',
                            'tipo' => isset($movement['tipo']) ? (string) $movement['tipo'] : '',
                            'valor' => mov_money(isset($movement['valor']) ? $movement['valor'] : 0),
                            'usuario' => isset($movement['usuario']) ? (string) $movement['usuario'] : '',
                            'soportes' => count($supportsPayload),
                            'support_items' => $supportsPayload,
                            'urls' => array(
                                'ticket' => $ticketUrl,
                                'editar' => $editUrl,
                            ),
                        );
                        $movementDetailPayloadJson = mov_encode_json($movementDetailPayload);
                        ?>
                        <tr class="js-movement-row-summary" data-movement-json="<?php echo mov_escape($movementDetailPayloadJson); ?>">
                            <td></td>
                            <td><?php echo mov_escape($movement['fecha']); ?></td>
                            <td><?php echo mov_escape($movement['clasificacion'] !== null ? $movement['clasificacion'] : 'Sin clasificacion'); ?></td>
                            <td class="movement-detail-icon-cell">
                                <button type="button" class="btn btn-ghost btn-inline btn-mini btn-icon-only js-open-movement-mobile-modal" title="Ver detalle" aria-label="Ver detalle" data-movement-json="<?php echo mov_escape($movementDetailPayloadJson); ?>">
                                    <i class="bi bi-card-text"></i>
                                </button>
                                <span class="movement-detail-hidden-text"><?php echo mov_escape($movement['detalle']); ?></span>
                            </td>
                            <td><?php echo mov_escape($movement['gasto_costo']); ?></td>
                            <td><?php echo mov_escape($movement['tipo']); ?></td>
                            <td><?php echo mov_money($movement['valor']); ?></td>
                            <td>
                                <?php if (empty($supportsPayload)) : ?>
                                    <span class="supports-indicator supports-indicator-empty" title="Sin soportes">-</span>
                                <?php else : ?>
                                    <details class="supports-inline-dropdown">
                                        <summary class="btn btn-ghost btn-inline btn-mini btn-icon-only supports-trigger" title="Ver soportes" aria-label="Ver soportes">
                                            <i class="bi bi-paperclip"></i>
                                            <span class="supports-count-badge"><?php echo (int) count($supportsPayload); ?></span>
                                        </summary>
                                        <div class="supports-inline-panel">
                                            <ul class="supports-inline-list">
                                                <?php foreach ($supportsPayload as $supportItem) : ?>
                                                    <li class="supports-inline-item">
                                                        <span class="supports-inline-name"><?php echo mov_escape(isset($supportItem['name']) ? $supportItem['name'] : 'Soporte'); ?></span>
                                                        <span class="supports-inline-actions">
                                                            <a class="btn btn-ghost btn-inline btn-mini btn-icon-only" href="<?php echo mov_escape(isset($supportItem['url']) ? $supportItem['url'] : '#'); ?>" target="_blank" title="Ver soporte" aria-label="Ver soporte">
                                                                <i class="bi bi-eye"></i>
                                                            </a>
                                                            <a class="btn btn-ghost btn-inline btn-mini btn-icon-only" href="<?php echo mov_escape(isset($supportItem['url']) ? $supportItem['url'] : '#'); ?>" target="_blank" download title="Descargar soporte" aria-label="Descargar soporte">
                                                                <i class="bi bi-download"></i>
                                                            </a>
                                                        </span>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </div>
                                    </details>
                                <?php endif; ?>
                            </td>
                            <td><?php echo mov_escape($movement['usuario']); ?></td>
                            <td>
                                <div class="table-actions-stack">
                                    <a class="btn btn-ghost btn-inline btn-mini btn-icon-only" title="Ver ticket" aria-label="Ver ticket" href="<?php echo mov_escape($ticketUrl); ?>" target="_blank">
                                        <i class="bi bi-receipt"></i>
                                    </a>
                                    <a class="btn btn-secondary btn-inline btn-mini btn-icon-only" title="Editar movimiento" aria-label="Editar movimiento" href="<?php echo mov_escape($editUrl); ?>">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>
                                    <form method="post" action="<?php echo mov_escape($baseUrl); ?>/index.php?route=movimientos/eliminar" class="inline-form js-confirm-delete" data-confirm-title="Confirmar eliminacion" data-confirm-message="Se eliminara este movimiento y sus soportes. Esta accion no se puede deshacer." data-confirm-accept="Si, eliminar">
                                        <input type="hidden" name="<?php echo mov_escape($csrfTokenName); ?>" value="<?php echo mov_escape($csrfToken); ?>">
                                        <input type="hidden" name="movement_id" value="<?php echo $movementId; ?>">
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

        <div class="mobile-movement-list">
            <?php if (empty($movimientos)) : ?>
                <div class="mobile-movement-empty muted">No hay movimientos registrados.</div>
            <?php else : ?>
                <?php foreach ($movimientos as $movement) : ?>
                    <?php
                    $movementId = isset($movement['id']) ? (int) $movement['id'] : 0;
                    $supports = isset($movement['supports']) && is_array($movement['supports']) ? $movement['supports'] : array();
                    $supportsPayload = mov_build_supports_payload($supports, $baseUrl, $movementId);
                    $ticketUrl = rtrim((string) $baseUrl, '/') . '/index.php?route=movimientos/ticket&id=' . $movementId;
                    $editUrl = rtrim((string) $baseUrl, '/') . '/index.php?route=movimientos/editar&id=' . $movementId;
                    $movementDetailPayload = array(
                        'id' => $movementId,
                        'fecha' => isset($movement['fecha']) ? (string) $movement['fecha'] : '',
                        'clasificacion' => isset($movement['clasificacion']) && $movement['clasificacion'] !== null ? (string) $movement['clasificacion'] : 'Sin clasificacion',
                        'detalle' => isset($movement['detalle']) ? (string) $movement['detalle'] : '',
                        'categoria' => isset($movement['gasto_costo']) ? (string) $movement['gasto_costo'] : '',
                        'tipo' => isset($movement['tipo']) ? (string) $movement['tipo'] : '',
                        'valor' => mov_money(isset($movement['valor']) ? $movement['valor'] : 0),
                        'usuario' => isset($movement['usuario']) ? (string) $movement['usuario'] : '',
                        'soportes' => count($supportsPayload),
                        'support_items' => $supportsPayload,
                        'urls' => array(
                            'ticket' => $ticketUrl,
                            'editar' => $editUrl,
                        ),
                    );
                    $movementDetailPayloadJson = mov_encode_json($movementDetailPayload);
                    $movementDateOnly = mov_date_only($movementDetailPayload['fecha']);
                    ?>
                    <article
                        class="mobile-movement-card"
                        data-filter-fecha="<?php echo mov_escape($movementDateOnly); ?>"
                        data-filter-clasificacion="<?php echo mov_escape(mov_filter_key($movementDetailPayload['clasificacion'])); ?>"
                        data-filter-categoria="<?php echo mov_escape(mov_filter_key($movementDetailPayload['categoria'])); ?>"
                        data-filter-tipo="<?php echo mov_escape(mov_filter_key($movementDetailPayload['tipo'])); ?>">
                        <div class="mobile-movement-head">
                            <span class="mobile-movement-date"><i class="bi bi-calendar-event"></i> <?php echo mov_escape($movementDetailPayload['fecha']); ?></span>
                        </div>
                        <div class="mobile-movement-summary">
                            <strong><?php echo mov_escape($movementDetailPayload['clasificacion']); ?></strong>
                            <span><?php echo mov_escape($movementDetailPayload['valor']); ?></span>
                        </div>
                        <div class="mobile-movement-meta">
                            <span class="mobile-movement-tag"><?php echo mov_escape($movementDetailPayload['categoria']); ?></span>
                            <span class="mobile-movement-tag"><?php echo mov_escape($movementDetailPayload['tipo']); ?></span>
                            <?php if (!empty($supportsPayload)) : ?>
                                <span class="mobile-movement-support-tag" title="Tiene soportes">
                                    <i class="bi bi-paperclip"></i> <?php echo (int) count($supportsPayload); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        <div class="mobile-movement-actions mobile-movement-actions-primary">
                            <button type="button" class="btn btn-ghost btn-inline btn-mini btn-icon-only js-open-movement-mobile-modal" title="Ver detalle" aria-label="Ver detalle" data-movement-json="<?php echo mov_escape($movementDetailPayloadJson); ?>">
                                <i class="bi bi-eye"></i>
                            </button>
                            <a class="btn btn-secondary btn-inline btn-mini btn-icon-only" title="Editar movimiento" aria-label="Editar movimiento" href="<?php echo mov_escape($editUrl); ?>">
                                <i class="bi bi-pencil-square"></i>
                            </a>
                            <form method="post" action="<?php echo mov_escape($baseUrl); ?>/index.php?route=movimientos/eliminar" class="inline-form js-confirm-delete" data-confirm-title="Confirmar eliminacion" data-confirm-message="Se eliminara este movimiento y sus soportes. Esta accion no se puede deshacer." data-confirm-accept="Si, eliminar">
                                <input type="hidden" name="<?php echo mov_escape($csrfTokenName); ?>" value="<?php echo mov_escape($csrfToken); ?>">
                                <input type="hidden" name="movement_id" value="<?php echo $movementId; ?>">
                                <button type="submit" class="btn btn-danger btn-inline btn-mini btn-icon-only" title="Eliminar movimiento" aria-label="Eliminar movimiento">
                                    <i class="bi bi-trash3"></i>
                                </button>
                            </form>
                        </div>
                    </article>
                <?php endforeach; ?>
                <div id="movement-mobile-empty-filter" class="mobile-movement-empty muted hidden">No hay movimientos para el filtro seleccionado.</div>
            <?php endif; ?>
        </div>
    </div>

    <aside class="card movement-summary-card" id="movement-summary-card">
        <div class="movement-summary-head">
            <h3><i class="bi bi-card-checklist"></i> Resumen rapido</h3>
            <span class="muted">Selecciona un movimiento</span>
        </div>
        <div id="movement-summary-body" class="movement-summary-body">
            <p class="muted">Haz clic en un registro del listado para ver detalle completo y accesos directos.</p>
        </div>
    </aside>
</section>

<div id="movement-mobile-modal" class="modal-overlay hidden" aria-hidden="true">
    <div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="movement-mobile-modal-title">
        <div class="modal-header">
            <h3 id="movement-mobile-modal-title"><i class="bi bi-card-text"></i> Detalle del movimiento</h3>
            <button type="button" class="btn btn-secondary btn-inline btn-mini btn-icon-only js-close-movement-mobile-modal" title="Cerrar" aria-label="Cerrar">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <div id="movement-mobile-modal-body" class="modal-body">
            <p class="muted">Selecciona un registro para ver su detalle.</p>
        </div>
    </div>
</div>
