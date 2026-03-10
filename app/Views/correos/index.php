<?php
/**
 * Proyecto PRESUPUESTO - Vista de bandeja de correos para importacion de movimientos.
 */

if (!function_exists('correo_escape')) {
    function correo_escape($value)
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('correo_money')) {
    function correo_money($value)
    {
        return '$ ' . number_format((float) $value, 0, ',', '.');
    }
}

if (!function_exists('correo_datetime_local')) {
    function correo_datetime_local($value)
    {
        $text = trim((string) $value);
        if ($text === '') {
            return date('Y-m-d\TH:i');
        }

        $normalized = str_replace('T', ' ', $text);
        $timestamp = strtotime($normalized);
        if ($timestamp === false) {
            return date('Y-m-d\TH:i');
        }

        return date('Y-m-d\TH:i', $timestamp);
    }
}

if (!function_exists('correo_substr')) {
    function correo_substr($value, $length)
    {
        $text = trim((string) $value);
        $limit = (int) $length;
        if ($text === '' || $limit <= 0) {
            return '';
        }

        if (function_exists('mb_strlen') && function_exists('mb_substr')) {
            if (mb_strlen($text, 'UTF-8') <= $limit) {
                return $text;
            }
            return mb_substr($text, 0, $limit, 'UTF-8');
        }

        if (strlen($text) <= $limit) {
            return $text;
        }

        return substr($text, 0, $limit);
    }
}

$selectedEmailSafe = is_array($selectedEmail) ? $selectedEmail : null;
$sugerencia = is_array($suggestedData) ? $suggestedData : array();
$confidenceValue = isset($sugerencia['confidence']) ? (float) $sugerencia['confidence'] : 0.0;
$confidencePercent = (int) round($confidenceValue * 100);
$suggestionSource = isset($sugerencia['source']) ? trim((string) $sugerencia['source']) : '';
$shouldOpenSuggestion = !empty($shouldOpenSuggestionModal) && $selectedEmailSafe !== null;
$isRelevantSuggestion = isset($sugerencia['is_relevant']) ? (bool) $sugerencia['is_relevant'] : true;
$irrelevantReason = isset($sugerencia['irrelevant_reason']) ? trim((string) $sugerencia['irrelevant_reason']) : '';

$movementTypes = array(
    'Compra',
    'Transferencia',
    'Pago proveedor',
    'Nomina',
    'Suscripcion',
    'Servicio',
    'Tarjeta',
    'Efectivo',
);
foreach ($mediosPago as $medioPagoItem) {
    $medioLabel = isset($medioPagoItem['medio']) ? trim((string) $medioPagoItem['medio']) : '';
    if ($medioLabel !== '' && !in_array($medioLabel, $movementTypes, true)) {
        $movementTypes[] = $medioLabel;
    }
}
?>

<section class="page-header card">
    <div>
        <span class="title-chip"><i class="bi bi-envelope-open"></i> Integracion correo + IA</span>
        <h2>Bandeja de correos</h2>
        <p class="muted">Selecciona un correo, revisa la sugerencia IA y crea el movimiento con soporte de correo.</p>
    </div>
</section>

<?php if (!empty($successMessage)) : ?>
    <div class="alert alert-success"><?php echo correo_escape($successMessage); ?></div>
<?php endif; ?>
<?php if (!empty($errorMessage)) : ?>
    <div class="alert alert-error"><?php echo correo_escape($errorMessage); ?></div>
<?php endif; ?>

<section class="card">
    <form method="get" action="<?php echo correo_escape($baseUrl); ?>/index.php" class="compact-form">
        <input type="hidden" name="route" value="correos">
        <label for="q">Buscar en correos</label>
        <div class="search-row">
            <input id="q" name="q" type="text" value="<?php echo correo_escape($searchText); ?>" placeholder="Ejemplo: compraste, transferencia, banco">
            <button class="btn btn-secondary btn-inline" type="submit"><i class="bi bi-search"></i> Buscar</button>
        </div>
    </form>
</section>

<section class="email-workspace">
    <article class="card table-card">
        <div class="table-header">
            <h3><i class="bi bi-inbox"></i> Correos recientes</h3>
            <span class="muted"><?php echo count($emails); ?> encontrados</span>
        </div>
        <div class="table-wrapper">
            <table class="table-professional table-email-compact-index js-data-table js-indexed-table" data-page-length="20" data-export-name="bandeja_correos" data-preference-key="correos_table_length">
                <thead>
                <tr>
                    <th class="no-export">#</th>
                    <th>Fecha</th>
                    <th>Remitente</th>
                    <th>Asunto</th>
                    <th class="no-export">Extracto</th>
                    <th class="no-export">Accion</th>
                </tr>
                </thead>
                <tbody>
                <?php if (empty($emails)) : ?>
                    <tr>
                        <td colspan="6" class="muted"><?php echo correo_escape($inboxMessage); ?></td>
                    </tr>
                <?php else : ?>
                    <?php foreach ($emails as $emailRow) : ?>
                        <?php
                        $rowUid = isset($emailRow['uid']) ? (int) $emailRow['uid'] : 0;
                        $isSelected = $selectedUid > 0 && $selectedUid === $rowUid;
                        $selectUrl = correo_escape($baseUrl) . '/index.php?route=correos&uid=' . $rowUid . '&analizar=1';
                        $extractFrom = isset($emailRow['from']) ? (string) $emailRow['from'] : '';
                        $extractSubject = isset($emailRow['subject']) ? (string) $emailRow['subject'] : '';
                        $extractDate = isset($emailRow['date_sql']) ? (string) $emailRow['date_sql'] : '';
                        $extractBody = isset($emailRow['body_plain']) ? correo_substr((string) $emailRow['body_plain'], 12000) : '';
                        if (trim($extractBody) === '' && isset($emailRow['snippet'])) {
                            $extractBody = (string) $emailRow['snippet'];
                        }
                        $extractBodyBase64 = base64_encode($extractBody);
                        if (trim((string) $searchText) !== '') {
                            $selectUrl .= '&q=' . rawurlencode((string) $searchText);
                        }
                        ?>
                        <tr class="<?php echo $isSelected ? 'email-row-selected' : ''; ?>">
                            <td></td>
                            <td><?php echo correo_escape(isset($emailRow['date_sql']) ? $emailRow['date_sql'] : ''); ?></td>
                            <td><?php echo correo_escape(isset($emailRow['from']) ? $emailRow['from'] : ''); ?></td>
                            <td><?php echo correo_escape(isset($emailRow['subject']) ? $emailRow['subject'] : ''); ?></td>
                            <td class="email-extract-cell">
                                <button
                                    type="button"
                                    class="btn btn-ghost btn-inline btn-mini btn-icon-only js-open-email-extract-modal"
                                    data-email-from="<?php echo correo_escape($extractFrom); ?>"
                                    data-email-subject="<?php echo correo_escape($extractSubject); ?>"
                                    data-email-date="<?php echo correo_escape($extractDate); ?>"
                                    data-email-body-base64="<?php echo correo_escape($extractBodyBase64); ?>"
                                    aria-label="Ver extracto del correo"
                                    title="Ver extracto">
                                    <i class="bi bi-file-earmark-text"></i>
                                </button>
                                <span class="movement-detail-hidden-text"><?php echo correo_escape(isset($emailRow['snippet']) ? correo_substr($emailRow['snippet'], 220) : ''); ?></span>
                            </td>
                            <td>
                                <a class="btn btn-ghost btn-inline btn-mini <?php echo $isSelected ? 'btn-primary' : ''; ?>" href="<?php echo $selectUrl; ?>">
                                    <i class="bi bi-magic"></i> Analizar
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </article>
</section>

<div id="email-extract-modal" class="modal-overlay hidden" aria-hidden="true">
    <div class="modal-card email-extract-modal-card">
        <div class="modal-header">
            <h3 id="email-extract-modal-title"><i class="bi bi-envelope-paper"></i> Contenido del correo</h3>
            <button type="button" class="btn btn-ghost btn-icon js-close-email-extract-modal" aria-label="Cerrar">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <div class="modal-body">
            <div id="email-extract-modal-meta" class="email-extract-meta"></div>
            <pre id="email-extract-modal-body" class="email-extract-content"></pre>
        </div>
    </div>
</div>

<?php if ($selectedEmailSafe !== null) : ?>
    <div id="email-suggestion-modal" class="modal-overlay <?php echo $shouldOpenSuggestion ? '' : 'hidden'; ?>" aria-hidden="<?php echo $shouldOpenSuggestion ? 'false' : 'true'; ?>">
        <div class="modal-card email-suggestion-modal-card">
            <div class="modal-header">
                <h3><i class="bi bi-cpu"></i> Sugerencia de movimiento</h3>
                <button type="button" class="btn btn-ghost btn-icon js-close-email-suggestion-modal" aria-label="Cerrar">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="table-actions-stack">
                    <?php if ($confidencePercent > 0) : ?>
                        <span class="muted">Confianza IA: <?php echo $confidencePercent; ?>%</span>
                    <?php endif; ?>
                    <?php if ($suggestionSource !== '') : ?>
                        <span class="muted"><?php echo correo_escape($suggestionSource); ?></span>
                    <?php endif; ?>
                </div>

                <div class="email-preview-card">
                    <p><strong>UID:</strong> <?php echo (int) (isset($selectedEmailSafe['uid']) ? $selectedEmailSafe['uid'] : 0); ?></p>
                    <p><strong>Remitente:</strong> <?php echo correo_escape(isset($selectedEmailSafe['from']) ? $selectedEmailSafe['from'] : ''); ?></p>
                    <p><strong>Asunto:</strong> <?php echo correo_escape(isset($selectedEmailSafe['subject']) ? $selectedEmailSafe['subject'] : ''); ?></p>
                    <p><strong>Fecha:</strong> <?php echo correo_escape(isset($selectedEmailSafe['date_sql']) ? $selectedEmailSafe['date_sql'] : ''); ?></p>
                    <pre><?php echo correo_escape(correo_substr(isset($selectedEmailSafe['body_plain']) ? $selectedEmailSafe['body_plain'] : '', 1800)); ?></pre>
                </div>

                <?php if (!$isRelevantSuggestion) : ?>
                    <div class="alert alert-error">
                        <?php echo correo_escape($irrelevantReason !== '' ? $irrelevantReason : 'Este correo no parece un movimiento economico para sugerencia automatica.'); ?>
                    </div>
                <?php else : ?>
                    <form id="movimiento-form" method="post" action="<?php echo correo_escape($baseUrl); ?>/index.php?route=correos/guardar" class="compact-form" novalidate>
                        <div id="movimiento-client-error" class="alert alert-error hidden"></div>
                        <input type="hidden" name="<?php echo correo_escape($csrfTokenName); ?>" value="<?php echo correo_escape($csrfToken); ?>">
                        <input type="hidden" name="email_uid" value="<?php echo (int) (isset($selectedEmailSafe['uid']) ? $selectedEmailSafe['uid'] : 0); ?>">
                        <input type="hidden" name="email_from" value="<?php echo correo_escape(isset($selectedEmailSafe['from']) ? $selectedEmailSafe['from'] : ''); ?>">
                        <input type="hidden" name="email_subject" value="<?php echo correo_escape(isset($selectedEmailSafe['subject']) ? $selectedEmailSafe['subject'] : ''); ?>">
                        <input type="hidden" name="email_date" value="<?php echo correo_escape(isset($selectedEmailSafe['date_sql']) ? $selectedEmailSafe['date_sql'] : ''); ?>">
                        <input type="hidden" name="email_hash" value="<?php echo correo_escape(isset($selectedEmailSafe['hash']) ? $selectedEmailSafe['hash'] : ''); ?>">
                        <input type="hidden" name="email_body" value="<?php echo correo_escape(correo_substr(isset($selectedEmailSafe['body_plain']) ? $selectedEmailSafe['body_plain'] : '', 7000)); ?>">
                        <input type="hidden" name="confidence" value="<?php echo correo_escape((string) $confidenceValue); ?>">

                        <div class="form-grid">
                            <div class="form-field">
                                <label for="fecha">Fecha y hora</label>
                                <input id="fecha" name="fecha" type="datetime-local" value="<?php echo correo_escape(correo_datetime_local(isset($sugerencia['fecha']) ? $sugerencia['fecha'] : '')); ?>" required>
                            </div>

                            <div class="form-field">
                                <label for="gasto_costo">Categoria principal</label>
                                <select id="gasto_costo" name="gasto_costo" required>
                                    <option value="Ingreso" <?php echo (isset($sugerencia['gasto_costo']) && $sugerencia['gasto_costo'] === 'Ingreso') ? 'selected' : ''; ?>>Ingreso</option>
                                    <option value="Gasto" <?php echo (isset($sugerencia['gasto_costo']) && $sugerencia['gasto_costo'] === 'Gasto') ? 'selected' : ''; ?>>Gasto</option>
                                    <option value="Costo" <?php echo (isset($sugerencia['gasto_costo']) && $sugerencia['gasto_costo'] === 'Costo') ? 'selected' : ''; ?>>Costo</option>
                                </select>
                            </div>

                            <div class="form-field">
                                <label for="id_clasificacion">Clasificacion</label>
                                <select id="id_clasificacion" name="id_clasificacion" class="js-searchable-select" data-placeholder="Selecciona una clasificacion" required>
                                    <option value=""></option>
                                    <?php foreach ($clasificaciones as $clasificacionItem) : ?>
                                        <?php $idValue = isset($clasificacionItem['id']) ? (int) $clasificacionItem['id'] : 0; ?>
                                        <option value="<?php echo $idValue; ?>" <?php echo (isset($sugerencia['id_clasificacion']) && (int) $sugerencia['id_clasificacion'] === $idValue) ? 'selected' : ''; ?>>
                                            <?php echo correo_escape(isset($clasificacionItem['descripcion']) ? $clasificacionItem['descripcion'] : ''); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-field">
                                <label for="tipo">Tipo o medio</label>
                                <select id="tipo" name="tipo" class="js-searchable-select" data-placeholder="Selecciona tipo o medio" required>
                                    <option value=""></option>
                                    <?php foreach ($movementTypes as $movementType) : ?>
                                        <option value="<?php echo correo_escape($movementType); ?>" <?php echo (isset($sugerencia['tipo']) && (string) $sugerencia['tipo'] === $movementType) ? 'selected' : ''; ?>>
                                            <?php echo correo_escape($movementType); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-field">
                                <label for="valor">Valor</label>
                                <input id="valor" name="valor" class="js-money-input" type="text" inputmode="numeric" value="<?php echo correo_escape(number_format(isset($sugerencia['valor']) ? (float) $sugerencia['valor'] : 0, 0, ',', '.')); ?>" required>
                            </div>

                            <div class="form-field">
                                <label for="valor_neto">Valor neto</label>
                                <input id="valor_neto" name="valor_neto" class="js-money-input" type="text" inputmode="numeric" value="<?php echo correo_escape(number_format(isset($sugerencia['valor_neto']) ? (float) $sugerencia['valor_neto'] : 0, 0, ',', '.')); ?>">
                            </div>

                            <div class="form-field">
                                <label for="saldo">Saldo</label>
                                <input id="saldo" name="saldo" class="js-money-input" type="text" inputmode="numeric" value="<?php echo correo_escape(number_format(isset($sugerencia['saldo']) ? (float) $sugerencia['saldo'] : 0, 0, ',', '.')); ?>">
                            </div>

                            <div class="form-field">
                                <label for="id_presupuesto">Presupuesto asociado (opcional)</label>
                                <select id="id_presupuesto" name="id_presupuesto" class="js-searchable-select" data-placeholder="Opcional">
                                    <option value="0">Sin presupuesto asociado</option>
                                    <?php foreach ($presupuestosActivos as $presupuestoItem) : ?>
                                        <?php $presupuestoId = isset($presupuestoItem['id']) ? (int) $presupuestoItem['id'] : 0; ?>
                                        <option value="<?php echo $presupuestoId; ?>" <?php echo (isset($sugerencia['id_presupuesto']) && (int) $sugerencia['id_presupuesto'] === $presupuestoId) ? 'selected' : ''; ?>>
                                            #<?php echo $presupuestoId; ?> - <?php echo correo_escape(isset($presupuestoItem['descripcion']) ? $presupuestoItem['descripcion'] : ''); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-field">
                                <label for="por_pagar_cobrar">Estado de saldo</label>
                                <select id="por_pagar_cobrar" name="por_pagar_cobrar" required>
                                    <option value="NINGUNO" <?php echo (isset($sugerencia['por_pagar_cobrar']) && $sugerencia['por_pagar_cobrar'] === 'NINGUNO') ? 'selected' : ''; ?>>Ninguno</option>
                                    <option value="PAGAR" <?php echo (isset($sugerencia['por_pagar_cobrar']) && $sugerencia['por_pagar_cobrar'] === 'PAGAR') ? 'selected' : ''; ?>>Por pagar</option>
                                    <option value="COBRAR" <?php echo (isset($sugerencia['por_pagar_cobrar']) && $sugerencia['por_pagar_cobrar'] === 'COBRAR') ? 'selected' : ''; ?>>Por cobrar</option>
                                </select>
                            </div>

                            <div class="form-field form-field-wide">
                                <label for="detalle">Detalle</label>
                                <textarea id="detalle" name="detalle" rows="3" maxlength="4000" required><?php echo correo_escape(isset($sugerencia['detalle']) ? $sugerencia['detalle'] : ''); ?></textarea>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary btn-inline">
                                <i class="bi bi-check2-circle"></i> Guardar con soporte de correo
                            </button>
                            <a class="btn btn-secondary btn-inline" href="<?php echo correo_escape($baseUrl); ?>/index.php?route=movimientos/nuevo">
                                <i class="bi bi-pencil-square"></i> Abrir formulario manual
                            </a>
                        </div>
                    </form>

                    <p class="muted">Al guardar, se genera y adjunta una imagen de soporte del correo para trazabilidad.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
<?php endif; ?>
