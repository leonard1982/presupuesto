<?php
/**
 * Proyecto PRESUPUESTO - Configuracion de base de datos.
 */

use App\Core\Environment;

return array(
    'host' => Environment::get('DB_HOST', '127.0.0.1'),
    'port' => Environment::getInteger('DB_PORT', 3306),
    'name' => Environment::get('DB_NAME', 'presupuestos'),
    'user' => Environment::get('DB_USER', 'root'),
    'password' => Environment::get('DB_PASS', ''),
    'charset' => Environment::get('DB_CHARSET', 'utf8mb4'),
);
