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
    'auto_create_database' => Environment::getBoolean('DB_AUTO_CREATE_DATABASE', true),
    'auto_schema_sync' => Environment::getBoolean('DB_AUTO_SCHEMA_SYNC', true),
    'schema_sync_check_seconds' => Environment::getInteger('DB_AUTO_SCHEMA_CHECK_SECONDS', 300),
);
