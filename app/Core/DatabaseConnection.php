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

        if ($connection->connect_errno === 1049 && !empty($databaseConfig['auto_create_database'])) {
            self::createDatabase($databaseConfig, $logger);
            $connection = @new mysqli(
                $databaseConfig['host'],
                $databaseConfig['user'],
                $databaseConfig['password'],
                $databaseConfig['name'],
                (int) $databaseConfig['port']
            );
        }

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

    private static function createDatabase(array $databaseConfig, Logger $logger = null)
    {
        $serverConnection = @new mysqli(
            $databaseConfig['host'],
            $databaseConfig['user'],
            $databaseConfig['password'],
            '',
            (int) $databaseConfig['port']
        );

        if ($serverConnection->connect_errno) {
            if ($logger instanceof Logger) {
                $logger->error('app', 'No fue posible crear base de datos automaticamente: fallo conexion al servidor.', array(
                    'code' => $serverConnection->connect_errno,
                    'error' => $serverConnection->connect_error,
                ));
            }
            return;
        }

        $databaseName = isset($databaseConfig['name']) ? (string) $databaseConfig['name'] : '';
        if ($databaseName === '') {
            if ($logger instanceof Logger) {
                $logger->warning('app', 'No fue posible crear base de datos automaticamente: nombre vacio.');
            }
            $serverConnection->close();
            return;
        }

        $safeDatabaseName = str_replace('`', '``', $databaseName);
        $charset = isset($databaseConfig['charset']) ? (string) $databaseConfig['charset'] : 'utf8mb4';
        if ($charset === '') {
            $charset = 'utf8mb4';
        }
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $charset)) {
            $charset = 'utf8mb4';
        }

        $collation = self::resolveCollationByCharset($charset);
        $sql = "CREATE DATABASE IF NOT EXISTS `{$safeDatabaseName}` CHARACTER SET {$charset} COLLATE {$collation}";
        $created = $serverConnection->query($sql);
        if ($created === false && $logger instanceof Logger) {
            $logger->error('app', 'No fue posible crear base de datos automaticamente.', array(
                'database' => $databaseName,
                'error' => $serverConnection->error,
            ));
        } elseif ($created !== false && $logger instanceof Logger) {
            $logger->info('app', 'Base de datos creada automaticamente si no existia.', array(
                'database' => $databaseName,
                'charset' => $charset,
                'collation' => $collation,
            ));
        }

        $serverConnection->close();
    }

    private static function resolveCollationByCharset($charset)
    {
        $charsetNormalized = strtolower((string) $charset);
        if ($charsetNormalized === 'utf8mb4') {
            return 'utf8mb4_unicode_ci';
        }
        if ($charsetNormalized === 'utf8') {
            return 'utf8_general_ci';
        }

        return $charsetNormalized . '_general_ci';
    }
}
