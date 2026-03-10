<?php
/**
 * Proyecto PRESUPUESTO - Vista de configuracion de sesion.
 */

if (!function_exists('config_escape')) {
    function config_escape($value)
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}
?>
<section class="page-header card">
    <div>
        <span class="title-chip"><i class="bi bi-shield-lock"></i> Seguridad de acceso</span>
        <h2>Configuracion de sesion</h2>
        <p class="muted">Define cuanto tiempo se mantiene activa tu sesion antes de solicitar ingreso nuevamente.</p>
    </div>
</section>

<?php if (!empty($successMessage)) : ?>
    <div class="alert alert-success"><?php echo config_escape($successMessage); ?></div>
<?php endif; ?>
<?php if (!empty($errorMessage)) : ?>
    <div class="alert alert-error"><?php echo config_escape($errorMessage); ?></div>
<?php endif; ?>

<section class="card">
    <form method="post" action="<?php echo config_escape($baseUrl); ?>/index.php?route=configuracion/sesion" class="compact-form">
        <input type="hidden" name="<?php echo config_escape($csrfTokenName); ?>" value="<?php echo config_escape($csrfToken); ?>">
        <label for="session_hours">Duracion de sesion activa</label>
        <select id="session_hours" name="session_hours" class="js-searchable-select" data-placeholder="Selecciona duracion">
            <?php foreach ($sessionOptionsHours as $hoursOption) : ?>
                <option value="<?php echo (int) $hoursOption; ?>" <?php echo ((int) $currentSessionHours === (int) $hoursOption) ? 'selected' : ''; ?>>
                    <?php echo (int) $hoursOption; ?> horas
                </option>
            <?php endforeach; ?>
        </select>
        <p class="muted">Configuracion actual: <?php echo (int) $currentSessionHours; ?> horas.</p>
        <div class="form-actions">
            <button class="btn btn-primary btn-inline" type="submit">
                <i class="bi bi-check2-circle"></i> Guardar configuracion
            </button>
            <a class="btn btn-secondary btn-inline" href="<?php echo config_escape($baseUrl); ?>/index.php?route=dashboard">
                <i class="bi bi-arrow-left-circle"></i> Volver al panel
            </a>
        </div>
    </form>
</section>
