<?php
/**
 * Proyecto PRESUPUESTO - Layout principal de interfaz.
 */

if (!function_exists('escape_html')) {
    function escape_html($value)
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

$title = isset($pageTitle) ? (string) $pageTitle : 'PRESUPUESTO';
$baseUrlSafe = isset($baseUrl) ? rtrim((string) $baseUrl, '/') : '';
$assetVersionSafe = isset($assetVersion) ? (string) $assetVersion : '0.1.0';
$enablePwaSafe = !empty($enablePwa) ? 'true' : 'false';
$pageBodyClassSafe = isset($pageBodyClass) ? trim((string) $pageBodyClass) : '';
$activeMenuSafe = isset($activeMenu) ? trim((string) $activeMenu) : '';
$isAuthenticated = !empty($currentUser) && is_array($currentUser);
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo escape_html($title); ?></title>
    <link rel="manifest" href="<?php echo escape_html($baseUrlSafe); ?>/public/manifest.webmanifest?v=<?php echo escape_html($assetVersionSafe); ?>">
    <meta name="theme-color" content="#1f2937">
    <link rel="stylesheet" href="<?php echo escape_html($baseUrlSafe); ?>/public/assets/css/app.css?v=<?php echo escape_html($assetVersionSafe); ?>">
    <?php if ($isAuthenticated) : ?>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
    <?php endif; ?>
</head>
<body class="<?php echo escape_html($pageBodyClassSafe); ?>" data-base-url="<?php echo escape_html($baseUrlSafe); ?>" data-enable-pwa="<?php echo escape_html($enablePwaSafe); ?>" data-asset-version="<?php echo escape_html($assetVersionSafe); ?>">
<header class="app-header">
    <div class="brand-block brand-block-inline">
        <?php if ($isAuthenticated) : ?>
            <button type="button" id="nav-toggle" class="btn btn-ghost nav-toggle" aria-label="Abrir menu">Menu</button>
        <?php endif; ?>
        <h1>PRESUPUESTO</h1>
        <p>Control financiero operativo y presupuestal</p>
    </div>

    <?php if ($isAuthenticated) : ?>
        <div class="header-search">
            <input id="global-nav-search" type="search" list="global-nav-options" placeholder="Buscar modulo o accion..." autocomplete="off">
            <datalist id="global-nav-options">
                <option value="Dashboard"></option>
                <option value="Movimientos"></option>
                <option value="Nuevo movimiento"></option>
                <option value="Clasificaciones"></option>
                <option value="Medios de pago"></option>
            </datalist>
        </div>

        <div class="user-block">
            <details class="user-menu">
                <summary>
                    <?php echo escape_html($currentUser['name'] !== '' ? $currentUser['name'] : $currentUser['login']); ?>
                </summary>
                <div class="user-menu-dropdown">
                    <a href="<?php echo escape_html($baseUrlSafe); ?>/index.php?route=dashboard">Panel principal</a>
                    <a href="<?php echo escape_html($baseUrlSafe); ?>/index.php?route=movimientos/nuevo">Registrar movimiento</a>
                    <form method="post" action="<?php echo escape_html($baseUrlSafe); ?>/index.php?route=logout">
                        <button type="submit" class="btn btn-secondary">Cerrar sesion</button>
                    </form>
                </div>
            </details>
        </div>
    <?php elseif ($pageBodyClassSafe === 'login-body') : ?>
        <div class="header-badge">Acceso seguro</div>
    <?php endif; ?>
</header>

<?php if ($isAuthenticated) : ?>
    <nav class="main-nav" id="main-nav">
        <a class="<?php echo $activeMenuSafe === 'dashboard' ? 'active' : ''; ?>" href="<?php echo escape_html($baseUrlSafe); ?>/index.php?route=dashboard">Dashboard</a>
        <a class="<?php echo $activeMenuSafe === 'movimientos' ? 'active' : ''; ?>" href="<?php echo escape_html($baseUrlSafe); ?>/index.php?route=movimientos">Movimientos</a>
        <a class="<?php echo $activeMenuSafe === 'clasificaciones' ? 'active' : ''; ?>" href="<?php echo escape_html($baseUrlSafe); ?>/index.php?route=clasificaciones">Clasificaciones</a>
        <a class="<?php echo $activeMenuSafe === 'medios_pago' ? 'active' : ''; ?>" href="<?php echo escape_html($baseUrlSafe); ?>/index.php?route=medios-pago">Medios de pago</a>
    </nav>
<?php endif; ?>

<main class="app-main">
    <?php echo $content; ?>
</main>
<?php if ($isAuthenticated) : ?>
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<?php endif; ?>
<script src="<?php echo escape_html($baseUrlSafe); ?>/public/assets/js/app.js?v=<?php echo escape_html($assetVersionSafe); ?>"></script>
</body>
</html>
