<?php
/**
 * Proyecto PRESUPUESTO - Vista de login.
 */

if (!function_exists('view_escape_html')) {
    function view_escape_html($value)
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}
?>
<section class="auth-shell">
    <aside class="auth-visual card">
        <p class="eyebrow">Gestion diaria sin friccion</p>
        <h2>Presupuestos, movimientos y soportes en un solo flujo</h2>
        <p class="muted">
            Registra ingresos, gastos y costos con rapidez, consulta indicadores clave y centraliza evidencias para auditoria.
        </p>
        <ul class="feature-list">
            <li>Panel de control para decisiones diarias</li>
            <li>Movimientos clasificados y trazables</li>
            <li>Base lista para API, automatizaciones e IA</li>
        </ul>
    </aside>

    <section class="card auth-card">
        <span class="title-chip"><i class="bi bi-box-arrow-in-right"></i> Acceso</span>
        <h2>Ingreso al sistema</h2>
        <p class="muted">Accede con tu cuenta para continuar.</p>

        <?php if (!empty($errorMessage)) : ?>
            <div class="alert alert-error"><?php echo view_escape_html($errorMessage); ?></div>
        <?php endif; ?>
        <div id="login-client-error" class="alert alert-error hidden" role="alert"></div>

        <form id="login-form" method="post" action="<?php echo view_escape_html($baseUrl); ?>/index.php?route=login" novalidate>
            <input type="hidden" name="<?php echo view_escape_html($csrfTokenName); ?>" value="<?php echo view_escape_html($csrfToken); ?>">

            <label for="username">Usuario</label>
            <input
                id="username"
                name="username"
                type="text"
                maxlength="190"
                autocomplete="username"
                value="<?php echo view_escape_html(isset($rememberedUsername) ? (string) $rememberedUsername : ''); ?>"
                required
            >

            <label for="password">Contrasena</label>
            <div class="password-row">
                <input id="password" name="password" type="password" minlength="4" autocomplete="current-password" required>
                <button class="btn btn-ghost" type="button" id="toggle-password" aria-label="Mostrar u ocultar contrasena">Ver</button>
            </div>

            <label class="check-row" for="remember_username">
                <input
                    id="remember_username"
                    name="remember_username"
                    type="checkbox"
                    value="1"
                    <?php echo !empty($rememberUsernameChecked) ? 'checked' : ''; ?>
                >
                <span>Recordar usuario en este dispositivo</span>
            </label>

            <button type="submit" class="btn btn-primary"><i class="bi bi-door-open"></i> Ingresar</button>
        </form>
    </section>
</section>
