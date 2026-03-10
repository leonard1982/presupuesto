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
$currentUserLabel = $isAuthenticated
    ? ($currentUser['name'] !== '' ? (string) $currentUser['name'] : (string) $currentUser['login'])
    : '';
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo escape_html($title); ?></title>
    <link rel="manifest" href="<?php echo escape_html($baseUrlSafe); ?>/public/manifest.webmanifest?v=<?php echo escape_html($assetVersionSafe); ?>">
    <meta name="theme-color" content="#1f2937">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <?php if ($isAuthenticated) : ?>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/datatables.net-dt@1.13.11/css/jquery.dataTables.min.css">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/datatables.net-responsive-dt@2.5.1/css/responsive.dataTables.min.css">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/datatables.net-buttons-dt@2.4.2/css/buttons.dataTables.min.css">
    <?php endif; ?>
    <link rel="stylesheet" href="<?php echo escape_html($baseUrlSafe); ?>/public/assets/css/app.css?v=<?php echo escape_html($assetVersionSafe); ?>">
</head>
<body class="<?php echo escape_html($pageBodyClassSafe); ?>" data-base-url="<?php echo escape_html($baseUrlSafe); ?>" data-enable-pwa="<?php echo escape_html($enablePwaSafe); ?>" data-asset-version="<?php echo escape_html($assetVersionSafe); ?>">
<?php if ($isAuthenticated) : ?>
    <div class="app-shell">
        <aside class="app-sidebar" id="app-sidebar">
            <div class="sidebar-brand">
                <a href="<?php echo escape_html($baseUrlSafe); ?>/index.php?route=dashboard">
                    <i class="bi bi-wallet2"></i>
                    <span class="sidebar-brand-text">PRESUPUESTO</span>
                </a>
                <button type="button" id="sidebar-toggle" class="btn btn-ghost btn-icon" aria-label="Contraer menu">
                    <i class="bi bi-layout-sidebar-inset"></i>
                </button>
            </div>

            <nav class="sidebar-nav">
                <a class="<?php echo $activeMenuSafe === 'dashboard' ? 'active' : ''; ?>" href="<?php echo escape_html($baseUrlSafe); ?>/index.php?route=dashboard">
                    <i class="bi bi-speedometer2"></i>
                    <span class="nav-text">Dashboard</span>
                </a>
                <a class="<?php echo $activeMenuSafe === 'movimientos' ? 'active' : ''; ?>" href="<?php echo escape_html($baseUrlSafe); ?>/index.php?route=movimientos">
                    <i class="bi bi-receipt-cutoff"></i>
                    <span class="nav-text">Movimientos</span>
                </a>
                <a href="<?php echo escape_html($baseUrlSafe); ?>/index.php?route=movimientos/nuevo">
                    <i class="bi bi-plus-circle"></i>
                    <span class="nav-text">Nuevo movimiento</span>
                </a>
                <a class="<?php echo $activeMenuSafe === 'clasificaciones' ? 'active' : ''; ?>" href="<?php echo escape_html($baseUrlSafe); ?>/index.php?route=clasificaciones">
                    <i class="bi bi-tags"></i>
                    <span class="nav-text">Clasificaciones</span>
                </a>
                <a class="<?php echo $activeMenuSafe === 'medios_pago' ? 'active' : ''; ?>" href="<?php echo escape_html($baseUrlSafe); ?>/index.php?route=medios-pago">
                    <i class="bi bi-credit-card-2-front"></i>
                    <span class="nav-text">Medios de pago</span>
                </a>
            </nav>
        </aside>

        <div class="app-content">
            <header class="topbar">
                <div class="topbar-left">
                    <button type="button" id="sidebar-mobile-toggle" class="btn btn-ghost btn-icon" aria-label="Abrir menu">
                        <i class="bi bi-list"></i>
                    </button>
                    <div class="title-with-icon">
                        <i class="bi bi-graph-up-arrow"></i>
                        <span><?php echo escape_html($title); ?></span>
                    </div>
                </div>

                <div class="topbar-search">
                    <i class="bi bi-search"></i>
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
                    <button type="button" id="theme-toggle" class="btn btn-ghost btn-icon btn-theme-toggle" title="Cambiar tema" aria-label="Cambiar tema">
                        <i class="bi bi-moon-stars"></i>
                    </button>
                    <details class="user-menu">
                        <summary>
                            <i class="bi bi-person-circle"></i>
                            <span><?php echo escape_html($currentUserLabel); ?></span>
                        </summary>
                        <div class="user-menu-dropdown">
                            <a href="<?php echo escape_html($baseUrlSafe); ?>/index.php?route=dashboard">
                                <i class="bi bi-house"></i> Panel principal
                            </a>
                            <a href="<?php echo escape_html($baseUrlSafe); ?>/index.php?route=movimientos/nuevo">
                                <i class="bi bi-plus-circle"></i> Registrar movimiento
                            </a>
                            <form method="post" action="<?php echo escape_html($baseUrlSafe); ?>/index.php?route=logout">
                                <button type="submit" class="btn btn-secondary btn-inline">
                                    <i class="bi bi-box-arrow-right"></i> Cerrar sesion
                                </button>
                            </form>
                        </div>
                    </details>
                </div>
            </header>

            <main class="app-main">
                <?php echo $content; ?>
            </main>
        </div>
    </div>
    <div class="sidebar-backdrop" id="sidebar-backdrop"></div>
<?php else : ?>
    <header class="auth-topbar">
        <div class="title-with-icon">
            <i class="bi bi-wallet2"></i>
            <span>PRESUPUESTO</span>
        </div>
        <div class="header-badge"><i class="bi bi-shield-lock"></i> Acceso seguro</div>
    </header>
    <main class="app-main">
        <?php echo $content; ?>
    </main>
<?php endif; ?>

<?php if ($isAuthenticated) : ?>
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/datatables.net@1.13.11/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/datatables.net-responsive@2.5.1/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jszip@3.10.1/dist/jszip.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/pdfmake@0.2.7/build/pdfmake.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/pdfmake@0.2.7/build/vfs_fonts.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/datatables.net-buttons@2.4.2/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/datatables.net-buttons@2.4.2/js/buttons.html5.min.js"></script>
<?php endif; ?>
<script src="<?php echo escape_html($baseUrlSafe); ?>/public/assets/js/app.js?v=<?php echo escape_html($assetVersionSafe); ?>"></script>
</body>
</html>
