<?php
/**
 * Proyecto PRESUPUESTO - Conexion mysqli centralizada.
 */

namespace App\Core;

use mysqli;
use RuntimeException;

class DatabaseConnection
{
    private static $connection;

    public static function getConnection(array $databaseConfig, Logger $logger = null)
    {
        if (self::$connection instanceof mysqli) {
            return self::$connection;
        }

        mysqli_report(MYSQLI_REPORT_OFF);

        $connection = @new mysqli(
            $databaseConfig['host'],
            $databaseConfig['user'],
            $databaseConfig['password'],
            $databaseConfig['name'],
            (int) $databaseConfig['port']
        );

        if ($connection->connect_errno) {
            if ($logger instanceof Logger) {
                $logger->error('app', 'No se pudo establecer conexion con base de datos.', array(
                    'code' => $connection->connect_errno,
                    'error' => $connection->connect_error,
                ));
            }

            throw new RuntimeException('Error de conexion a base de datos.');
        }

        if (!$connection->set_charset($databaseConfig['charset'])) {
            if ($logger instanceof Logger) {
                $logger->warning('app', 'No fue posible aplicar charset en base de datos.', array(
                    'charset' => $databaseConfig['charset'],
                    'error' => $connection->error,
                ));
            }
        }

        self::$connection = $connection;
        return self::$connection;
    }
}
