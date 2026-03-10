<?php
/**
 * Proyecto PRESUPUESTO - Repositorio de soporte para modulo de correos.
 */

namespace App\Modules\Correos\Repositories;

use App\Core\Logger;
use mysqli;
use mysqli_stmt;

class CorreoRepository
{
    private $connection;
    private $logger;
    private $importLogTableExists;

    public function __construct(mysqli $connection, Logger $logger)
    {
        $this->connection = $connection;
        $this->logger = $logger;
        $this->importLogTableExists = null;
    }

    public function registerCorreoImportacion(array $payload)
    {
        if (!$this->hasImportLogTable()) {
            return false;
        }

        $query = 'INSERT INTO correo_importaciones_log (
                    correo_uid,
                    remitente,
                    asunto,
                    fecha_correo,
                    contenido_hash,
                    sugerencia_json,
                    movimiento_id,
                    estado,
                    confianza,
                    usuario,
                    observaciones,
                    fecha_registro
                 ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())';

        $statement = $this->connection->prepare($query);
        if (!$statement instanceof mysqli_stmt) {
            $this->logger->warning('app', 'No fue posible preparar insercion de correo_importaciones_log.', array(
                'error' => $this->connection->error,
            ));
            return false;
        }

        $correoUid = isset($payload['correo_uid']) ? (string) $payload['correo_uid'] : '';
        $remitente = isset($payload['remitente']) ? (string) $payload['remitente'] : '';
        $asunto = isset($payload['asunto']) ? (string) $payload['asunto'] : '';
        $fechaCorreo = isset($payload['fecha_correo']) ? (string) $payload['fecha_correo'] : '';
        $contenidoHash = isset($payload['contenido_hash']) ? (string) $payload['contenido_hash'] : '';
        $sugerenciaJson = isset($payload['sugerencia_json']) ? (string) $payload['sugerencia_json'] : '';
        $movimientoId = isset($payload['movimiento_id']) ? (int) $payload['movimiento_id'] : 0;
        $estado = isset($payload['estado']) ? (string) $payload['estado'] : 'CREADO';
        $confianza = isset($payload['confianza']) ? (float) $payload['confianza'] : 0.0;
        $usuario = isset($payload['usuario']) ? (string) $payload['usuario'] : '';
        $observaciones = isset($payload['observaciones']) ? (string) $payload['observaciones'] : '';

        $statement->bind_param(
            'ssssssisdss',
            $correoUid,
            $remitente,
            $asunto,
            $fechaCorreo,
            $contenidoHash,
            $sugerenciaJson,
            $movimientoId,
            $estado,
            $confianza,
            $usuario,
            $observaciones
        );

        $ok = $statement->execute();
        if (!$ok) {
            $this->logger->warning('app', 'No fue posible registrar importacion de correo.', array(
                'error' => $statement->error,
            ));
        }

        $statement->close();
        return $ok;
    }

    private function hasImportLogTable()
    {
        if ($this->importLogTableExists !== null) {
            return $this->importLogTableExists;
        }

        $result = $this->connection->query("SHOW TABLES LIKE 'correo_importaciones_log'");
        if ($result === false) {
            $this->importLogTableExists = false;
            return false;
        }

        $this->importLogTableExists = $result->num_rows > 0;
        return $this->importLogTableExists;
    }
}
