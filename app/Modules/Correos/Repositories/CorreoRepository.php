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
    private $hiddenInboxTableExists;

    public function __construct(mysqli $connection, Logger $logger)
    {
        $this->connection = $connection;
        $this->logger = $logger;
        $this->importLogTableExists = null;
        $this->hiddenInboxTableExists = null;
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

    public function hideInboxMessage(array $payload)
    {
        if (!$this->hasInboxHiddenTable()) {
            return false;
        }

        $query = 'INSERT INTO correo_inbox_hidden (
                    usuario,
                    correo_uid,
                    correo_hash,
                    remitente,
                    asunto,
                    fecha_correo,
                    ocultado_en
                 ) VALUES (?, ?, ?, ?, ?, ?, NOW())
                 ON DUPLICATE KEY UPDATE
                    remitente = VALUES(remitente),
                    asunto = VALUES(asunto),
                    fecha_correo = VALUES(fecha_correo),
                    ocultado_en = NOW()';

        $statement = $this->connection->prepare($query);
        if (!$statement instanceof mysqli_stmt) {
            $this->logger->warning('app', 'No fue posible preparar ocultamiento de correo en bandeja.', array(
                'error' => $this->connection->error,
            ));
            return false;
        }

        $usuario = isset($payload['usuario']) ? trim((string) $payload['usuario']) : '';
        $correoUid = isset($payload['correo_uid']) ? trim((string) $payload['correo_uid']) : '';
        $correoHash = isset($payload['correo_hash']) ? trim((string) $payload['correo_hash']) : '';
        $remitente = isset($payload['remitente']) ? trim((string) $payload['remitente']) : '';
        $asunto = isset($payload['asunto']) ? trim((string) $payload['asunto']) : '';
        $fechaCorreo = isset($payload['fecha_correo']) ? trim((string) $payload['fecha_correo']) : null;

        if ($usuario === '' || ($correoUid === '' && $correoHash === '')) {
            $statement->close();
            return false;
        }

        if ($fechaCorreo === '') {
            $fechaCorreo = null;
        }

        $statement->bind_param('ssssss', $usuario, $correoUid, $correoHash, $remitente, $asunto, $fechaCorreo);
        $ok = $statement->execute();
        if (!$ok) {
            $this->logger->warning('app', 'No fue posible ocultar correo de bandeja.', array(
                'error' => $statement->error,
                'usuario' => $usuario,
                'correo_uid' => $correoUid,
            ));
        }

        $statement->close();
        return $ok;
    }

    public function getHiddenInboxKeysByUser($username)
    {
        if (!$this->hasInboxHiddenTable()) {
            return array('uids' => array(), 'hashes' => array());
        }

        $userSafe = trim((string) $username);
        if ($userSafe === '') {
            return array('uids' => array(), 'hashes' => array());
        }

        $query = 'SELECT correo_uid, correo_hash FROM correo_inbox_hidden WHERE usuario = ?';
        $statement = $this->connection->prepare($query);
        if (!$statement instanceof mysqli_stmt) {
            $this->logger->warning('app', 'No fue posible preparar consulta de correos ocultos por usuario.', array(
                'error' => $this->connection->error,
            ));
            return array('uids' => array(), 'hashes' => array());
        }

        $statement->bind_param('s', $userSafe);
        $statement->execute();
        $result = $statement->get_result();

        $uids = array();
        $hashes = array();
        while ($result && ($row = $result->fetch_assoc())) {
            $uid = isset($row['correo_uid']) ? trim((string) $row['correo_uid']) : '';
            $hash = isset($row['correo_hash']) ? trim((string) $row['correo_hash']) : '';
            if ($uid !== '') {
                $uids[] = $uid;
            }
            if ($hash !== '') {
                $hashes[] = $hash;
            }
        }

        $statement->close();
        return array(
            'uids' => array_values(array_unique($uids)),
            'hashes' => array_values(array_unique($hashes)),
        );
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

    private function hasInboxHiddenTable()
    {
        if ($this->hiddenInboxTableExists !== null) {
            return $this->hiddenInboxTableExists;
        }

        $result = $this->connection->query("SHOW TABLES LIKE 'correo_inbox_hidden'");
        if ($result === false) {
            $this->hiddenInboxTableExists = false;
            return false;
        }

        $this->hiddenInboxTableExists = $result->num_rows > 0;
        return $this->hiddenInboxTableExists;
    }
}
