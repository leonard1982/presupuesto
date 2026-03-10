<?php
/**
 * Proyecto PRESUPUESTO - Layout de impresion para tickets y comprobantes.
 */

if (!function_exists('print_escape')) {
    function print_escape($value)
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

$title = isset($pageTitle) ? (string) $pageTitle : 'PRESUPUESTO';
$baseUrlSafe = isset($baseUrl) ? rtrim((string) $baseUrl, '/') : '';
$assetVersionSafe = isset($assetVersion) ? (string) $assetVersion : '0.1.0';
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo print_escape($title); ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?php echo print_escape($baseUrlSafe); ?>/public/assets/css/app.css?v=<?php echo print_escape($assetVersionSafe); ?>">
</head>
<body class="ticket-page">
<main class="ticket-page-wrap">
    <?php echo $content; ?>
</main>
</body>
</html>
