<?php
/**
 * Proyecto PRESUPUESTO - Vista de formulario para nuevo movimiento.
 */

if (!function_exists('mov_form_escape')) {
    function mov_form_escape($value)
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('mov_form_currency_input')) {
    function mov_form_currency_input($value, $allowEmpty)
    {
        $stringValue = trim((string) $value);
        if ($stringValue === '') {
            return $allowEmpty ? '' : '0';
        }

        $isNegative = strpos($stringValue, '-') === 0;
        $digitsOnly = preg_replace('/[^0-9]/', '', $stringValue);
        if (!is_string($digitsOnly) || $digitsOnly === '') {
            return $allowEmpty ? '' : '0';
        }

        $normalizedDigits = ltrim($digitsOnly, '0');
        if ($normalizedDigits === '') {
            $normalizedDigits = '0';
        }

        $reverseChunks = str_split(strrev($normalizedDigits), 3);
        $formatted = strrev(implode('.', $reverseChunks));
        if ($formatted === '') {
            $formatted = '0';
        }

        return $isNegative ? '-' . $formatted : $formatted;
    }
}

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

$selectedGastoCosto = isset($formData['gasto_costo']) ? (string) $formData['gasto_costo'] : 'Gasto';
$selectedTipo = isset($formData['tipo']) ? (string) $formData['tipo'] : '';
$selectedClasificacion = isset($formData['id_clasificacion']) ? (int) $formData['id_clasificacion'] : 0;
$selectedPresupuesto = isset($formData['id_presupuesto']) ? (int) $formData['id_presupuesto'] : 0;
$selectedSaldoTipo = isset($formData['por_pagar_cobrar']) ? (string) $formData['por_pagar_cobrar'] : 'NINGUNO';
$fechaActual = date('Y-m-d\TH:i');

$isEdit = !empty($isEditMode);
$movementIdValue = isset($movementId) ? (int) $movementId : 0;
$actionRoute = isset($formActionRoute) ? (string) $formActionRoute : 'movimientos';
$supportsList = isset($existingSupports) && is_array($existingSupports) ? $existingSupports : array();
$maxUploadMb = isset($filesConfig['maxMb']) ? (int) $filesConfig['maxMb'] : 10;
$allowedExtensions = isset($filesConfig['allowedExtensions']) && is_array($filesConfig['allowedExtensions']) ? $filesConfig['allowedExtensions'] : array('jpg', 'jpeg', 'png', 'webp', 'pdf');
$allowedExtensionsCsv = implode(',', $allowedExtensions);

$valorInput = mov_form_currency_input(isset($formData['valor']) ? $formData['valor'] : '', true);
$valorNetoInput = mov_form_currency_input(isset($formData['valor_neto']) ? $formData['valor_neto'] : '', true);
$saldoInput = mov_form_currency_input(isset($formData['saldo']) ? $formData['saldo'] : '0', false);
?>
<section class="page-header card">
    <div>
        <span class="title-chip"><i class="bi bi-plus-circle"></i> Captura rapida</span>
        <h2><?php echo $isEdit ? 'Editar movimiento' : 'Nuevo movimiento'; ?></h2>
        <p class="muted">Registra gastos, costos o compras con clasificacion, medios y soportes.</p>
    </div>
    <a class="btn btn-secondary btn-inline" href="<?php echo mov_form_escape($baseUrl); ?>/index.php?route=movimientos">
        <i class="bi bi-arrow-left-circle"></i> Volver al listado
    </a>
</section>

<?php if (!empty($errorMessage)) : ?>
    <div class="alert alert-error"><?php echo mov_form_escape($errorMessage); ?></div>
<?php endif; ?>
<div id="movimiento-client-error" class="alert alert-error hidden"></div>

<section class="card form-card">
    <form id="movimiento-form" method="post" action="<?php echo mov_form_escape($baseUrl); ?>/index.php?route=<?php echo mov_form_escape($actionRoute); ?>" enctype="multipart/form-data" novalidate>
        <input type="hidden" name="<?php echo mov_form_escape($csrfTokenName); ?>" value="<?php echo mov_form_escape($csrfToken); ?>">
        <input type="hidden" id="soportes_clipboard_json" name="soportes_clipboard_json" value="">
        <?php if ($isEdit) : ?>
            <input type="hidden" name="movement_id" value="<?php echo $movementIdValue; ?>">
        <?php endif; ?>

        <div class="form-grid">
            <div class="form-field">
                <label for="fecha">Fecha y hora</label>
                <input id="fecha" name="fecha" type="datetime-local" value="<?php echo mov_form_escape(isset($formData['fecha']) ? str_replace(' ', 'T', substr((string) $formData['fecha'], 0, 16)) : $fechaActual); ?>" required>
            </div>

            <div class="form-field">
                <label for="gasto_costo">Categoria principal</label>
                <select id="gasto_costo" name="gasto_costo" required>
                    <option value="Gasto" <?php echo $selectedGastoCosto === 'Gasto' ? 'selected' : ''; ?>>Gasto</option>
                    <option value="Costo" <?php echo $selectedGastoCosto === 'Costo' ? 'selected' : ''; ?>>Costo</option>
                </select>
            </div>

            <div class="form-field">
                <label for="id_clasificacion">Clasificacion</label>
                <select id="id_clasificacion" name="id_clasificacion" class="js-searchable-select" data-placeholder="Selecciona una clasificacion" required>
                    <option value=""></option>
                    <?php foreach ($clasificaciones as $record) : ?>
                        <option value="<?php echo (int) $record['id']; ?>" <?php echo $selectedClasificacion === (int) $record['id'] ? 'selected' : ''; ?>>
                            <?php echo mov_form_escape($record['descripcion']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-field">
                <label for="tipo">Tipo o medio del movimiento</label>
                <select id="tipo" name="tipo" class="js-searchable-select" data-placeholder="Selecciona tipo o medio" required>
                    <option value=""></option>
                    <?php foreach ($movementTypes as $movementType) : ?>
                        <option value="<?php echo mov_form_escape($movementType); ?>" <?php echo $selectedTipo === $movementType ? 'selected' : ''; ?>>
                            <?php echo mov_form_escape($movementType); ?>
                        </option>
                    <?php endforeach; ?>
                    <?php foreach ($mediosPago as $medioPago) : ?>
                        <?php $medioValue = (string) $medioPago['medio']; ?>
                        <option value="<?php echo mov_form_escape($medioValue); ?>" <?php echo $selectedTipo === $medioValue ? 'selected' : ''; ?>>
                            <?php echo mov_form_escape($medioValue); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-field">
                <label for="id_presupuesto">Presupuesto asociado</label>
                <select id="id_presupuesto" name="id_presupuesto" class="js-searchable-select" data-placeholder="Opcional">
                    <option value="0">Sin presupuesto asociado</option>
                    <?php foreach ($presupuestosActivos as $presupuesto) : ?>
                        <option value="<?php echo (int) $presupuesto['id']; ?>" <?php echo $selectedPresupuesto === (int) $presupuesto['id'] ? 'selected' : ''; ?>>
                            #<?php echo (int) $presupuesto['id']; ?> - <?php echo mov_form_escape($presupuesto['descripcion']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-field">
                <label for="por_pagar_cobrar">Estado de saldo</label>
                <select id="por_pagar_cobrar" name="por_pagar_cobrar" required>
                    <option value="NINGUNO" <?php echo $selectedSaldoTipo === 'NINGUNO' ? 'selected' : ''; ?>>Ninguno</option>
                    <option value="PAGAR" <?php echo $selectedSaldoTipo === 'PAGAR' ? 'selected' : ''; ?>>Por pagar</option>
                    <option value="COBRAR" <?php echo $selectedSaldoTipo === 'COBRAR' ? 'selected' : ''; ?>>Por cobrar</option>
                </select>
            </div>

            <div class="form-field">
                <label for="valor">Valor</label>
                <input id="valor" name="valor" class="js-money-input" type="text" inputmode="numeric" value="<?php echo mov_form_escape($valorInput); ?>" placeholder="Ejemplo: 1.250.000" required>
            </div>

            <div class="form-field">
                <label for="valor_neto">Valor neto</label>
                <input id="valor_neto" name="valor_neto" class="js-money-input" data-empty-allowed="1" type="text" inputmode="numeric" value="<?php echo mov_form_escape($valorNetoInput); ?>" placeholder="Si lo dejas vacio, toma el valor">
            </div>

            <div class="form-field">
                <label for="saldo">Saldo</label>
                <input id="saldo" name="saldo" class="js-money-input" type="text" inputmode="numeric" value="<?php echo mov_form_escape($saldoInput); ?>">
            </div>

            <div class="form-field form-field-wide">
                <label for="detalle">Detalle</label>
                <textarea id="detalle" name="detalle" rows="4" maxlength="4000" placeholder="Describe la compra, gasto o costo..." required><?php echo mov_form_escape(isset($formData['detalle']) ? $formData['detalle'] : ''); ?></textarea>
            </div>

            <div class="form-field form-field-wide">
                <label for="soportes">Soportes (imagenes o PDF)</label>
                <div class="file-picker-shell">
                    <input id="soportes" class="file-picker-input" name="soportes[]" type="file" multiple accept=".jpg,.jpeg,.png,.webp,.pdf" data-max-mb="<?php echo $maxUploadMb; ?>" data-allowed-extensions="<?php echo mov_form_escape($allowedExtensionsCsv); ?>">
                    <div class="file-picker-toolbar">
                        <label for="soportes" class="btn btn-secondary btn-inline file-picker-button">
                            <i class="bi bi-cloud-arrow-up"></i> Elegir archivos
                        </label>
                        <span id="soportes-file-count" class="file-picker-count">Sin archivos seleccionados</span>
                    </div>
                    <ul id="soportes-file-list" class="file-picker-list hidden"></ul>
                    <div id="soportes-paste-zone" class="file-paste-zone" tabindex="0" aria-label="Pegar imagen desde portapapeles">
                        <i class="bi bi-clipboard-plus"></i>
                        <span>Pega una imagen aqui con <strong>Ctrl + V</strong></span>
                    </div>
                    <div id="soportes-paste-feedback" class="muted hidden"></div>
                </div>
                <p class="muted">Puedes seleccionar varios archivos. Extensiones permitidas: <?php echo mov_form_escape($allowedExtensionsCsv); ?>. Maximo por archivo: <?php echo $maxUploadMb; ?> MB.</p>
            </div>
        </div>

        <?php if ($isEdit && !empty($supportsList)) : ?>
            <div class="supports-panel">
                <h3><i class="bi bi-paperclip"></i> Soportes existentes</h3>
                <div class="table-actions-stack">
                    <?php foreach ($supportsList as $support) : ?>
                        <?php
                        $supportId = isset($support['support_id']) ? (int) $support['support_id'] : 0;
                        $originalName = isset($support['original_name']) ? (string) $support['original_name'] : '';
                        ?>
                        <?php if ($supportId > 0) : ?>
                            <a class="btn btn-ghost btn-inline btn-mini" href="<?php echo mov_form_escape($baseUrl); ?>/index.php?route=movimientos/soporte&id=<?php echo $movementIdValue; ?>&sid=<?php echo $supportId; ?>" target="_blank">
                                <i class="bi bi-download"></i> <?php echo mov_form_escape($originalName); ?>
                            </a>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary btn-inline"><i class="bi bi-check2-circle"></i> <?php echo $isEdit ? 'Actualizar movimiento' : 'Guardar movimiento'; ?></button>
            <a class="btn btn-secondary btn-inline" href="<?php echo mov_form_escape($baseUrl); ?>/index.php?route=movimientos"><i class="bi bi-x-circle"></i> Cancelar</a>
        </div>
    </form>
</section>
