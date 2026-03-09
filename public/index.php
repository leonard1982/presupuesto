<?php
/**
 * Proyecto PRESUPUESTO - Punto de entrada unico.
 */

define('PROJECT_ROOT', dirname(__DIR__));

require_once PROJECT_ROOT . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Core' . DIRECTORY_SEPARATOR . 'Autoloader.php';

\App\Core\Autoloader::register();

$bootstrap = new \App\Core\Bootstrap();
$bootstrap->run();
