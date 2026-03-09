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
</head>
<body class="<?php echo escape_html($pageBodyClassSafe); ?>" data-base-url="<?php echo escape_html($baseUrlSafe); ?>" data-enable-pwa="<?php echo escape_html($enablePwaSafe); ?>" data-asset-version="<?php echo escape_html($assetVersionSafe); ?>">
<header class="app-header">
    <div class="brand-block">
        <h1>PRESUPUESTO</h1>
        <p>Control financiero operativo y presupuestal</p>
    </div>
    <?php if (!empty($currentUser)) : ?>
        <div class="user-block">
            <span><?php echo escape_html($currentUser['name'] !== '' ? $currentUser['name'] : $currentUser['login']); ?></span>
            <form method="post" action="<?php echo escape_html($baseUrlSafe); ?>/index.php?route=logout">
                <button type="submit" class="btn btn-secondary">Cerrar sesion</button>
            </form>
        </div>
    <?php elseif ($pageBodyClassSafe === 'login-body') : ?>
        <div class="header-badge">Acceso seguro</div>
    <?php endif; ?>
</header>
<main class="app-main">
    <?php echo $content; ?>
</main>
<script src="<?php echo escape_html($baseUrlSafe); ?>/public/assets/js/app.js?v=<?php echo escape_html($assetVersionSafe); ?>"></script>
</body>
</html>
