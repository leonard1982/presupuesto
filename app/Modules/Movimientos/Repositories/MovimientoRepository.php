<?php
/**
 * Proyecto PRESUPUESTO - Repositorio de movimientos operativos.
 */

namespace App\Modules\Movimientos\Repositories;

use App\Core\Logger;
use mysqli;
use mysqli_stmt;

class MovimientoRepository
{
    private $connection;
    private $logger;

    public function __construct(mysqli $connection, Logger $logger)
    {
        $this->connection = $connection;
        $this->logger = $logger;
    }

    public function getClasificaciones()
    {
        $rows = array();
        $query = 'SELECT id, descripcion FROM clasificaciones ORDER BY descripcion ASC';
        $result = $this->connection->query($query);
        if (!$result) {
            $this->logger->error('app', 'No fue posible listar clasificaciones para movimientos.', array(
                'error' => $this->connection->error,
            ));
            return $rows;
        }

        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }

        return $rows;
    }

    public function getMediosPago()
    {
        $rows = array();
        $query = 'SELECT id, medio FROM medios ORDER BY medio ASC';
        $result = $this->connection->query($query);
        if (!$result) {
            $this->logger->error('app', 'No fue posible listar medios de pago para movimientos.', array(
                'error' => $this->connection->error,
            ));
            return $rows;
        }

        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }

        return $rows;
    }

    public function getPresupuestosActivos()
    {
        $rows = array();
        $query = "SELECT id, descripcion FROM presupuesto WHERE estado = 'ACTIVO' ORDER BY fecha_creacion DESC";
        $result = $this->connection->query($query);
        if (!$result) {
            $this->logger->warning('app', 'No fue posible listar presupuestos activos.', array(
                'error' => $this->connection->error,
            ));
            return $rows;
        }

        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }

        return $rows;
    }

    public function getRecentMovimientos($limit)
    {
        $query = 'SELECT gc.id, gc.fecha, gc.detalle, gc.valor, gc.gasto_costo, gc.tipo, gc.usuario, c.descripcion AS clasificacion, COALESCE(s.total_soportes, 0) AS soportes_count
                  FROM gastos_costos gc
                  LEFT JOIN clasificaciones c ON c.id = gc.id_clasificacion
                  LEFT JOIN (
                    SELECT id_ingreso, COUNT(*) AS total_soportes
                    FROM ingresos_detalle
                    GROUP BY id_ingreso
                  ) s ON s.id_ingreso = gc.id
                  ORDER BY gc.fecha DESC
                  LIMIT ?';

        $statement = $this->connection->prepare($query);
        if (!$statement instanceof mysqli_stmt) {
            $this->logger->error('app', 'No fue posible preparar consulta de listado de movimientos.', array(
                'error' => $this->connection->error,
            ));
            return array();
        }

        $limitInt = (int) $limit;
        if ($limitInt < 1) {
            $limitInt = 20;
        }

        $statement->bind_param('i', $limitInt);
        $statement->execute();
        $result = $statement->get_result();

        $rows = array();
        while ($result && ($row = $result->fetch_assoc())) {
            $rows[] = $row;
        }

        $statement->close();
        return $rows;
    }

    public function findMovimientoById($movementId)
    {
        $query = 'SELECT gc.id, gc.fecha, gc.id_clasificacion, gc.detalle, gc.valor, gc.fecha_periodo, gc.id_presupuesto, gc.soporte, gc.gasto_costo, gc.tipo, gc.por_pagar_cobrar, gc.valor_neto, gc.saldo, gc.id_costo, gc.usuario, c.descripcion AS clasificacion
                  FROM gastos_costos gc
                  LEFT JOIN clasificaciones c ON c.id = gc.id_clasificacion
                  WHERE gc.id = ?
                  LIMIT 1';
        $statement = $this->connection->prepare($query);
        if (!$statement instanceof mysqli_stmt) {
            $this->logger->error('app', 'No fue posible preparar consulta de movimiento por ID.', array(
                'error' => $this->connection->error,
            ));
            return null;
        }

        $movementIdInt = (int) $movementId;
        $statement->bind_param('i', $movementIdInt);
        $statement->execute();
        $result = $statement->get_result();
        $row = $result ? $result->fetch_assoc() : null;
        $statement->close();

        return $row ?: null;
    }

    public function createMovimiento(array $movementData)
    {
        $query = 'INSERT INTO gastos_costos (
                    fecha,
                    id_clasificacion,
                    detalle,
                    valor,
                    fecha_periodo,
                    id_presupuesto,
                    soporte,
                    gasto_costo,
                    tipo,
                    por_pagar_cobrar,
                    valor_neto,
                    saldo,
                    id_costo,
                    usuario
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';

        $statement = $this->connection->prepare($query);
        if (!$statement instanceof mysqli_stmt) {
            $this->logger->error('app', 'No fue posible preparar insercion de movimiento.', array(
                'error' => $this->connection->error,
            ));
            return false;
        }

        $statement->bind_param(
            'sisdsissssddis',
            $movementData['fecha'],
            $movementData['id_clasificacion'],
            $movementData['detalle'],
            $movementData['valor'],
            $movementData['fecha_periodo'],
            $movementData['id_presupuesto'],
            $movementData['soporte'],
            $movementData['gasto_costo'],
            $movementData['tipo'],
            $movementData['por_pagar_cobrar'],
            $movementData['valor_neto'],
            $movementData['saldo'],
            $movementData['id_costo'],
            $movementData['usuario']
        );

        $ok = $statement->execute();
        if (!$ok) {
            $this->logger->error('app', 'Error al insertar movimiento.', array(
                'error' => $statement->error,
            ));
            $statement->close();
            return false;
        }

        $insertId = (int) $statement->insert_id;
        $statement->close();
        return $insertId > 0 ? $insertId : false;
    }

    public function updateMovimiento($movementId, array $movementData)
    {
        $query = 'UPDATE gastos_costos
                  SET fecha = ?, id_clasificacion = ?, detalle = ?, valor = ?, fecha_periodo = ?, id_presupuesto = ?, gasto_costo = ?, tipo = ?, por_pagar_cobrar = ?, valor_neto = ?, saldo = ?, usuario = ?
                  WHERE id = ?
                  LIMIT 1';

        $statement = $this->connection->prepare($query);
        if (!$statement instanceof mysqli_stmt) {
            $this->logger->error('app', 'No fue posible preparar actualizacion de movimiento.', array(
                'error' => $this->connection->error,
            ));
            return false;
        }

        $movementIdInt = (int) $movementId;
        $statement->bind_param(
            'sisdsisssddsi',
            $movementData['fecha'],
            $movementData['id_clasificacion'],
            $movementData['detalle'],
            $movementData['valor'],
            $movementData['fecha_periodo'],
            $movementData['id_presupuesto'],
            $movementData['gasto_costo'],
            $movementData['tipo'],
            $movementData['por_pagar_cobrar'],
            $movementData['valor_neto'],
            $movementData['saldo'],
            $movementData['usuario'],
            $movementIdInt
        );

        $ok = $statement->execute();
        if (!$ok) {
            $this->logger->error('app', 'Error al actualizar movimiento.', array(
                'error' => $statement->error,
                'movement_id' => $movementIdInt,
            ));
        }

        $statement->close();
        return $ok;
    }

    public function deleteMovimiento($movementId)
    {
        $query = 'DELETE FROM gastos_costos WHERE id = ? LIMIT 1';
        $statement = $this->connection->prepare($query);
        if (!$statement instanceof mysqli_stmt) {
            $this->logger->error('app', 'No fue posible preparar eliminacion de movimiento.', array(
                'error' => $this->connection->error,
            ));
            return false;
        }

        $movementIdInt = (int) $movementId;
        $statement->bind_param('i', $movementIdInt);
        $ok = $statement->execute();

        if (!$ok) {
            $this->logger->error('app', 'Error al eliminar movimiento.', array(
                'error' => $statement->error,
                'movement_id' => $movementIdInt,
            ));
        }

        $statement->close();
        return $ok;
    }

    public function getSupportsByMovementIds(array $movementIds)
    {
        if (empty($movementIds)) {
            return array();
        }

        $uniqueIds = array_values(array_unique(array_map('intval', $movementIds)));
        $placeholders = implode(',', array_fill(0, count($uniqueIds), '?'));
        $types = str_repeat('i', count($uniqueIds));

        $query = 'SELECT id, id_ingreso, fechayhora, imagen, usuario
                  FROM ingresos_detalle
                  WHERE id_ingreso IN (' . $placeholders . ')
                  ORDER BY id DESC';

        $statement = $this->connection->prepare($query);
        if (!$statement instanceof mysqli_stmt) {
            $this->logger->error('app', 'No fue posible preparar consulta de soportes por movimientos.', array(
                'error' => $this->connection->error,
            ));
            return array();
        }

        $params = array($types);
        foreach ($uniqueIds as $index => $idValue) {
            $params[] = &$uniqueIds[$index];
        }

        call_user_func_array(array($statement, 'bind_param'), $params);
        $statement->execute();
        $result = $statement->get_result();

        $grouped = array();
        while ($result && ($row = $result->fetch_assoc())) {
            $movementId = isset($row['id_ingreso']) ? (int) $row['id_ingreso'] : 0;
            if ($movementId <= 0) {
                continue;
            }

            if (!isset($grouped[$movementId])) {
                $grouped[$movementId] = array();
            }

            $grouped[$movementId][] = $row;
        }

        $statement->close();
        return $grouped;
    }

    public function getSupportsByMovementId($movementId)
    {
        $movementIdInt = (int) $movementId;
        if ($movementIdInt <= 0) {
            return array();
        }

        $query = 'SELECT id, id_ingreso, fechayhora, imagen, usuario
                  FROM ingresos_detalle
                  WHERE id_ingreso = ?
                  ORDER BY id DESC';
        $statement = $this->connection->prepare($query);
        if (!$statement instanceof mysqli_stmt) {
            $this->logger->error('app', 'No fue posible preparar consulta de soportes por movimiento.', array(
                'error' => $this->connection->error,
            ));
            return array();
        }

        $statement->bind_param('i', $movementIdInt);
        $statement->execute();
        $result = $statement->get_result();

        $rows = array();
        while ($result && ($row = $result->fetch_assoc())) {
            $rows[] = $row;
        }

        $statement->close();
        return $rows;
    }

    public function findSupportById($supportId)
    {
        $supportIdInt = (int) $supportId;
        if ($supportIdInt <= 0) {
            return null;
        }

        $query = 'SELECT id, id_ingreso, fechayhora, imagen, usuario
                  FROM ingresos_detalle
                  WHERE id = ?
                  LIMIT 1';
        $statement = $this->connection->prepare($query);
        if (!$statement instanceof mysqli_stmt) {
            $this->logger->error('app', 'No fue posible preparar consulta de soporte por ID.', array(
                'error' => $this->connection->error,
            ));
            return null;
        }

        $statement->bind_param('i', $supportIdInt);
        $statement->execute();
        $result = $statement->get_result();
        $row = $result ? $result->fetch_assoc() : null;
        $statement->close();

        return $row ?: null;
    }

    public function createSupportRecord($movementId, $imageName, $username)
    {
        $query = 'INSERT INTO ingresos_detalle (id_ingreso, fechayhora, imagen, usuario)
                  VALUES (?, NOW(), ?, ?)';
        $statement = $this->connection->prepare($query);
        if (!$statement instanceof mysqli_stmt) {
            $this->logger->error('app', 'No fue posible preparar insercion de soporte.', array(
                'error' => $this->connection->error,
            ));
            return false;
        }

        $movementIdInt = (int) $movementId;
        $imageNameSafe = (string) $imageName;
        $usernameSafe = (string) $username;
        $statement->bind_param('iss', $movementIdInt, $imageNameSafe, $usernameSafe);
        $ok = $statement->execute();

        if (!$ok) {
            $this->logger->error('app', 'Error al crear soporte en ingresos_detalle.', array(
                'error' => $statement->error,
                'movement_id' => $movementIdInt,
                'image_name' => $imageNameSafe,
            ));
            $statement->close();
            return false;
        }

        $supportId = (int) $statement->insert_id;
        $statement->close();
        return $supportId > 0 ? $supportId : false;
    }

    public function deleteSupportsByMovementId($movementId)
    {
        $movementIdInt = (int) $movementId;
        $query = 'DELETE FROM ingresos_detalle WHERE id_ingreso = ?';
        $statement = $this->connection->prepare($query);
        if (!$statement instanceof mysqli_stmt) {
            $this->logger->error('app', 'No fue posible preparar eliminacion de soportes de movimiento.', array(
                'error' => $this->connection->error,
                'movement_id' => $movementIdInt,
            ));
            return false;
        }

        $statement->bind_param('i', $movementIdInt);
        $ok = $statement->execute();

        if (!$ok) {
            $this->logger->error('app', 'Error al eliminar soportes de movimiento.', array(
                'error' => $statement->error,
                'movement_id' => $movementIdInt,
            ));
        }

        $statement->close();
        return $ok;
    }

    public function deleteSupportsByIds(array $supportIds)
    {
        if (empty($supportIds)) {
            return true;
        }

        $uniqueIds = array_values(array_unique(array_map('intval', $supportIds)));
        $placeholders = implode(',', array_fill(0, count($uniqueIds), '?'));
        $types = str_repeat('i', count($uniqueIds));
        $query = 'DELETE FROM ingresos_detalle WHERE id IN (' . $placeholders . ')';

        $statement = $this->connection->prepare($query);
        if (!$statement instanceof mysqli_stmt) {
            $this->logger->error('app', 'No fue posible preparar eliminacion de soportes por IDs.', array(
                'error' => $this->connection->error,
            ));
            return false;
        }

        $params = array($types);
        foreach ($uniqueIds as $index => $idValue) {
            $params[] = &$uniqueIds[$index];
        }

        call_user_func_array(array($statement, 'bind_param'), $params);
        $ok = $statement->execute();

        if (!$ok) {
            $this->logger->error('app', 'Error al eliminar soportes por IDs.', array(
                'error' => $statement->error,
            ));
        }

        $statement->close();
        return $ok;
    }
}
