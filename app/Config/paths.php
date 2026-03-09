<?php
/**
 * Proyecto PRESUPUESTO - Configuracion centralizada de rutas internas.
 */

$projectRoot = defined('PROJECT_ROOT') ? PROJECT_ROOT : dirname(dirname(__DIR__));

return array(
    'project_root' => $projectRoot,
    'app_root' => $projectRoot . DIRECTORY_SEPARATOR . 'app',
    'public_root' => $projectRoot . DIRECTORY_SEPARATOR . 'public',
    'storage_root' => $projectRoot . DIRECTORY_SEPARATOR . 'storage',
    'views_root' => $projectRoot . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Views',
    'logs_app' => $projectRoot . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'app',
    'logs_api' => $projectRoot . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'api',
    'cache_root' => $projectRoot . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'cache',
    'uploads_root' => $projectRoot . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'uploads',
    'uploads_soportes' => $projectRoot . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'soportes',
    'uploads_temp' => $projectRoot . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'temp',
);
