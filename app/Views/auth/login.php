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
<section class="card auth-card">
    <h2>Ingreso al sistema</h2>
    <p class="muted">Compatible con hash moderno y contrasena legacy MD5 (migracion gradual).</p>

    <?php if (!empty($errorMessage)) : ?>
        <div class="alert alert-error"><?php echo view_escape_html($errorMessage); ?></div>
    <?php endif; ?>

    <form id="login-form" method="post" action="<?php echo view_escape_html($baseUrl); ?>/index.php?route=login" novalidate>
        <input type="hidden" name="<?php echo view_escape_html($csrfTokenName); ?>" value="<?php echo view_escape_html($csrfToken); ?>">

        <label for="username">Usuario</label>
        <input id="username" name="username" type="text" maxlength="190" autocomplete="username" required>

        <label for="password">Contrasena</label>
        <input id="password" name="password" type="password" minlength="4" autocomplete="current-password" required>

        <button type="submit" class="btn btn-primary">Ingresar</button>
    </form>
</section>
