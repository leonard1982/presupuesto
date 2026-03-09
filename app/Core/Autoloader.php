<?php
/**
 * Proyecto PRESUPUESTO - Cargador automatico PSR-4 para namespace App.
 */

namespace App\Core;

class Autoloader
{
    public static function register()
    {
        spl_autoload_register(array(__CLASS__, 'autoload'));
    }

    private static function autoload($className)
    {
        $prefix = 'App\\';

        if (strpos($className, $prefix) !== 0) {
            return;
        }

        $relativeClass = substr($className, strlen($prefix));
        $relativePath = str_replace('\\', DIRECTORY_SEPARATOR, $relativeClass) . '.php';
        $absolutePath = PROJECT_ROOT . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . $relativePath;

        if (is_file($absolutePath)) {
            require_once $absolutePath;
        }
    }
}
