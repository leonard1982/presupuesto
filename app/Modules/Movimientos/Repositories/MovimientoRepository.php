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
        $query = 'SELECT gc.id, gc.fecha, gc.detalle, gc.valor, gc.gasto_costo, gc.tipo, gc.usuario, c.descripcion AS clasificacion
                  FROM gastos_costos gc
                  LEFT JOIN clasificaciones c ON c.id = gc.id_clasificacion
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
        }

        $statement->close();
        return $ok;
    }
}
