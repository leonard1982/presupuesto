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
?>
<section class="page-header card">
    <div>
        <h2>Nuevo movimiento</h2>
        <p class="muted">Registra gastos, costos o compras con clasificacion y medio de pago.</p>
    </div>
    <a class="btn btn-secondary btn-inline" href="<?php echo mov_form_escape($baseUrl); ?>/index.php?route=movimientos">Volver al listado</a>
</section>

<?php if (!empty($errorMessage)) : ?>
    <div class="alert alert-error"><?php echo mov_form_escape($errorMessage); ?></div>
<?php endif; ?>
<div id="movimiento-client-error" class="alert alert-error hidden"></div>

<section class="card form-card">
    <form id="movimiento-form" method="post" action="<?php echo mov_form_escape($baseUrl); ?>/index.php?route=movimientos" novalidate>
        <input type="hidden" name="<?php echo mov_form_escape($csrfTokenName); ?>" value="<?php echo mov_form_escape($csrfToken); ?>">

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
                <input id="valor" name="valor" type="text" inputmode="decimal" value="<?php echo mov_form_escape(isset($formData['valor']) ? $formData['valor'] : ''); ?>" placeholder="Ejemplo: 1250000.00" required>
            </div>

            <div class="form-field">
                <label for="valor_neto">Valor neto</label>
                <input id="valor_neto" name="valor_neto" type="text" inputmode="decimal" value="<?php echo mov_form_escape(isset($formData['valor_neto']) ? $formData['valor_neto'] : '0'); ?>">
            </div>

            <div class="form-field">
                <label for="saldo">Saldo</label>
                <input id="saldo" name="saldo" type="text" inputmode="decimal" value="<?php echo mov_form_escape(isset($formData['saldo']) ? $formData['saldo'] : '0'); ?>">
            </div>

            <div class="form-field form-field-wide">
                <label for="detalle">Detalle</label>
                <textarea id="detalle" name="detalle" rows="4" maxlength="4000" placeholder="Describe la compra, gasto o costo..." required><?php echo mov_form_escape(isset($formData['detalle']) ? $formData['detalle'] : ''); ?></textarea>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary btn-inline">Guardar movimiento</button>
            <a class="btn btn-secondary btn-inline" href="<?php echo mov_form_escape($baseUrl); ?>/index.php?route=movimientos">Cancelar</a>
        </div>
    </form>
</section>
