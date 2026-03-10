<?php
/**
 * Proyecto PRESUPUESTO - Repositorio de consultas del dashboard.
 */

namespace App\Modules\Dashboard\Repositories;

use App\Core\Logger;
use mysqli;
use mysqli_stmt;

class DashboardRepository
{
    private $connection;
    private $logger;

    public function __construct(mysqli $connection, Logger $logger)
    {
        $this->connection = $connection;
        $this->logger = $logger;
    }

    public function getMonthlyTotals($periodStart, $periodEndExclusive)
    {
        $legacyIngresos = $this->sumIngresos($periodStart, $periodEndExclusive);
        $movimientosIngresos = $this->sumGastosByType($periodStart, $periodEndExclusive, 'Ingreso');

        return array(
            'ingresos' => $legacyIngresos + $movimientosIngresos,
            'ingresos_legacy' => $legacyIngresos,
            'ingresos_movimientos' => $movimientosIngresos,
            'gastos' => $this->sumGastosByType($periodStart, $periodEndExclusive, 'Gasto'),
            'costos' => $this->sumGastosByType($periodStart, $periodEndExclusive, 'Costo'),
        );
    }

    public function getRecentMovements($limit)
    {
        $query = 'SELECT gc.id, gc.fecha, gc.detalle, gc.valor, gc.gasto_costo, gc.tipo, gc.usuario, c.descripcion AS clasificacion
                  FROM gastos_costos gc
                  LEFT JOIN clasificaciones c ON c.id = gc.id_clasificacion
                  ORDER BY gc.fecha DESC
                  LIMIT ?';
        $statement = $this->connection->prepare($query);
        if (!$statement instanceof mysqli_stmt) {
            $this->logger->error('app', 'No fue posible preparar consulta de movimientos recientes.', array(
                'error' => $this->connection->error,
            ));
            return array();
        }

        $limitInt = (int) $limit;
        if ($limitInt < 1) {
            $limitInt = 10;
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

    public function getTopClasificaciones($periodStart, $periodEndExclusive, $limit)
    {
        $query = 'SELECT c.descripcion AS clasificacion, SUM(gc.valor) AS total
                  FROM gastos_costos gc
                  INNER JOIN clasificaciones c ON c.id = gc.id_clasificacion
                  WHERE gc.fecha_periodo >= ? AND gc.fecha_periodo < ?
                  GROUP BY c.id, c.descripcion
                  ORDER BY total DESC
                  LIMIT ?';
        $statement = $this->connection->prepare($query);
        if (!$statement instanceof mysqli_stmt) {
            $this->logger->error('app', 'No fue posible preparar consulta de top clasificaciones.', array(
                'error' => $this->connection->error,
            ));
            return array();
        }

        $limitInt = (int) $limit;
        if ($limitInt < 1) {
            $limitInt = 5;
        }

        $statement->bind_param('ssi', $periodStart, $periodEndExclusive, $limitInt);
        $statement->execute();
        $result = $statement->get_result();

        $rows = array();
        while ($result && ($row = $result->fetch_assoc())) {
            $rows[] = $row;
        }

        $statement->close();
        return $rows;
    }

    public function getMonthlyTrend($months)
    {
        $monthsInt = (int) $months;
        if ($monthsInt < 2) {
            $monthsInt = 6;
        }

        $startDate = date('Y-m-01', strtotime('-' . ($monthsInt - 1) . ' months'));
        $endDateExclusive = date('Y-m-01', strtotime('+1 month'));

        $ingresosMap = $this->getIngresosByMonth($startDate . ' 00:00:00', $endDateExclusive . ' 00:00:00');
        $gastosCostosMap = $this->getGastosCostosByMonth($startDate, $endDateExclusive);

        $labels = array();
        $ingresos = array();
        $gastos = array();
        $costos = array();

        for ($monthOffset = $monthsInt - 1; $monthOffset >= 0; $monthOffset -= 1) {
            $monthDate = strtotime('-' . $monthOffset . ' months');
            $monthKey = date('Y-m', $monthDate);

            $labels[] = date('M y', $monthDate);
            $ingresoLegacy = isset($ingresosMap[$monthKey]) ? (float) $ingresosMap[$monthKey] : 0.0;
            $ingresoMovimientos = isset($gastosCostosMap[$monthKey]['Ingreso']) ? (float) $gastosCostosMap[$monthKey]['Ingreso'] : 0.0;
            $ingresos[] = $ingresoLegacy + $ingresoMovimientos;
            $gastos[] = isset($gastosCostosMap[$monthKey]['Gasto']) ? (float) $gastosCostosMap[$monthKey]['Gasto'] : 0.0;
            $costos[] = isset($gastosCostosMap[$monthKey]['Costo']) ? (float) $gastosCostosMap[$monthKey]['Costo'] : 0.0;
        }

        return array(
            'labels' => $labels,
            'ingresos' => $ingresos,
            'gastos' => $gastos,
            'costos' => $costos,
        );
    }

    private function sumIngresos($periodStart, $periodEndExclusive)
    {
        $query = 'SELECT COALESCE(SUM(valor), 0) AS total
                  FROM ingresos
                  WHERE fecha >= ? AND fecha < ?';
        $statement = $this->connection->prepare($query);
        if (!$statement instanceof mysqli_stmt) {
            $this->logger->error('app', 'No fue posible preparar suma de ingresos.', array(
                'error' => $this->connection->error,
            ));
            return 0.0;
        }

        $statement->bind_param('ss', $periodStart, $periodEndExclusive);
        $statement->execute();
        $result = $statement->get_result();
        $row = $result ? $result->fetch_assoc() : null;
        $statement->close();

        return $row ? (float) $row['total'] : 0.0;
    }

    private function sumGastosByType($periodStart, $periodEndExclusive, $movementType)
    {
        $query = 'SELECT COALESCE(SUM(valor), 0) AS total
                  FROM gastos_costos
                  WHERE fecha_periodo >= ? AND fecha_periodo < ? AND gasto_costo = ?';
        $statement = $this->connection->prepare($query);
        if (!$statement instanceof mysqli_stmt) {
            $this->logger->error('app', 'No fue posible preparar suma de gastos/costos.', array(
                'error' => $this->connection->error,
            ));
            return 0.0;
        }

        $statement->bind_param('sss', $periodStart, $periodEndExclusive, $movementType);
        $statement->execute();
        $result = $statement->get_result();
        $row = $result ? $result->fetch_assoc() : null;
        $statement->close();

        return $row ? (float) $row['total'] : 0.0;
    }

    private function getIngresosByMonth($periodStart, $periodEndExclusive)
    {
        $query = "SELECT DATE_FORMAT(fecha, '%Y-%m') AS periodo, COALESCE(SUM(valor), 0) AS total
                  FROM ingresos
                  WHERE fecha >= ? AND fecha < ?
                  GROUP BY DATE_FORMAT(fecha, '%Y-%m')";

        $statement = $this->connection->prepare($query);
        if (!$statement instanceof mysqli_stmt) {
            $this->logger->error('app', 'No fue posible preparar consulta de tendencia mensual de ingresos.', array(
                'error' => $this->connection->error,
            ));
            return array();
        }

        $statement->bind_param('ss', $periodStart, $periodEndExclusive);
        $statement->execute();
        $result = $statement->get_result();

        $map = array();
        while ($result && ($row = $result->fetch_assoc())) {
            $periodKey = isset($row['periodo']) ? (string) $row['periodo'] : '';
            if ($periodKey === '') {
                continue;
            }

            $map[$periodKey] = (float) $row['total'];
        }

        $statement->close();
        return $map;
    }

    private function getGastosCostosByMonth($periodStart, $periodEndExclusive)
    {
        $query = "SELECT DATE_FORMAT(fecha_periodo, '%Y-%m') AS periodo, gasto_costo, COALESCE(SUM(valor), 0) AS total
                  FROM gastos_costos
                  WHERE fecha_periodo >= ? AND fecha_periodo < ?
                  GROUP BY DATE_FORMAT(fecha_periodo, '%Y-%m'), gasto_costo";

        $statement = $this->connection->prepare($query);
        if (!$statement instanceof mysqli_stmt) {
            $this->logger->error('app', 'No fue posible preparar consulta de tendencia mensual de gastos y costos.', array(
                'error' => $this->connection->error,
            ));
            return array();
        }

        $statement->bind_param('ss', $periodStart, $periodEndExclusive);
        $statement->execute();
        $result = $statement->get_result();

        $map = array();
        while ($result && ($row = $result->fetch_assoc())) {
            $periodKey = isset($row['periodo']) ? (string) $row['periodo'] : '';
            $typeKey = isset($row['gasto_costo']) ? (string) $row['gasto_costo'] : '';
            if ($periodKey === '' || $typeKey === '') {
                continue;
            }

            if (!isset($map[$periodKey])) {
                $map[$periodKey] = array();
            }

            $map[$periodKey][$typeKey] = (float) $row['total'];
        }

        $statement->close();
        return $map;
    }
}
